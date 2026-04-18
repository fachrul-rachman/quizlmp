<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AttemptAnswer;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizResult;
use App\Models\ResultPdf;
use App\Models\ShortAnswerKey;
use App\Support\DeterministicShuffle;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AdminResultController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $quizId = (string) $request->query('quiz_id', '');
        $status = (string) $request->query('status', 'all');

        $results = QuizResult::query()
            ->select('quiz_results.*')
            ->with([
                'quiz:id,title',
                'attempt:id,quiz_id,participant_name,participant_applied_for,status,submitted_at',
            ])
            ->when($search !== '', function ($query) use ($search) {
                $needle = mb_strtolower($search);

                $query->where(function ($inner) use ($needle) {
                    $inner->whereHas('quiz', function ($quizQuery) use ($needle) {
                        $quizQuery->whereRaw('LOWER(title) LIKE ?', ['%'.$needle.'%']);
                    })->orWhereHas('attempt', function ($attemptQuery) use ($needle) {
                        $attemptQuery
                            ->whereRaw('LOWER(participant_name) LIKE ?', ['%'.$needle.'%'])
                            ->orWhereRaw('LOWER(participant_applied_for) LIKE ?', ['%'.$needle.'%']);
                    });
                });
            })
            ->when($quizId !== '', fn ($query) => $query->where('quiz_results.quiz_id', (int) $quizId))
            ->when($status !== 'all', fn ($query) => $query->where('quiz_results.result_status', $status))
            ->orderByDesc('quiz_results.calculated_at')
            ->orderByDesc('quiz_results.id')
            ->paginate(20)
            ->withQueryString();

        $pdfByResultId = ResultPdf::query()
            ->whereIn('quiz_result_id', $results->getCollection()->pluck('id'))
            ->get(['quiz_result_id', 'google_drive_url', 'uploaded_at'])
            ->keyBy('quiz_result_id');

        $rows = $results->getCollection()->map(function (QuizResult $result) use ($pdfByResultId) {
            $pdf = $pdfByResultId->get($result->id);

            return [
                'result' => $result,
                'pdf' => $pdf,
            ];
        });

        $quizzes = Quiz::query()
            ->orderBy('title')
            ->get(['id', 'title']);

        return view('admin.results.index', [
            'results' => $results,
            'rows' => $rows,
            'quizzes' => $quizzes,
            'search' => $search,
            'quizId' => $quizId,
            'status' => $status,
        ]);
    }

    public function show(QuizResult $quizResult): View
    {
        $quizResult->load([
            'quiz:id,title,description,duration_minutes,shuffle_questions,shuffle_options',
            'attempt:id,quiz_id,participant_name,participant_applied_for,started_at,submitted_at,time_limit_minutes,status',
        ]);

        $attempt = $quizResult->attempt;
        $quiz = $quizResult->quiz;
        abort_unless($attempt && $quiz, 404);

        $pdf = ResultPdf::query()
            ->where('quiz_result_id', $quizResult->id)
            ->first(['quiz_result_id', 'file_name', 'local_path', 'google_drive_url', 'generated_at', 'uploaded_at']);

        $questionIds = $this->orderedQuestionIds($quiz, $attempt);
        $questionRows = $this->buildQuestionRows($attempt, $questionIds, (bool) $quiz->shuffle_options);

        return view('admin.results.show', [
            'result' => $quizResult,
            'quiz' => $quiz,
            'attempt' => $attempt,
            'pdf' => $pdf,
            'questionRows' => $questionRows,
        ]);
    }

    /**
     * @return array<int, int>
     */
    private function orderedQuestionIds(Quiz $quiz, QuizAttempt $attempt): array
    {
        $ids = Question::query()
            ->where('quiz_id', $quiz->id)
            ->whereNull('deleted_at')
            ->where('is_active', true)
            ->orderBy('order_number')
            ->pluck('id')
            ->map(fn ($value) => (int) $value)
            ->all();

        if (! $quiz->shuffle_questions || count($ids) <= 1) {
            return $ids;
        }

        return DeterministicShuffle::shuffle($ids, $this->seedFromAttempt((int) $attempt->id, (int) $quiz->id));
    }

    /**
     * @param  array<int, int>  $questionIds
     * @return array<int, array<string, mixed>>
     */
    private function buildQuestionRows(QuizAttempt $attempt, array $questionIds, bool $shuffleOptions): array
    {
        $questions = Question::query()
            ->whereIn('id', $questionIds)
            ->get(['id', 'question_text', 'question_image_path', 'question_type'])
            ->keyBy('id');

        $answers = AttemptAnswer::query()
            ->where('quiz_attempt_id', $attempt->id)
            ->whereIn('question_id', $questionIds)
            ->get(['question_id', 'selected_option_id', 'answer_text', 'is_correct'])
            ->keyBy('question_id');

        $optionsByQuestion = QuestionOption::query()
            ->whereIn('question_id', $questionIds)
            ->whereNull('deleted_at')
            ->orderBy('sort_order')
            ->get(['id', 'question_id', 'option_key', 'option_text', 'option_image_path', 'is_correct', 'sort_order'])
            ->groupBy('question_id');

        $keysByQuestion = ShortAnswerKey::query()
            ->whereIn('question_id', $questionIds)
            ->orderBy('sort_order')
            ->get(['question_id', 'answer_text'])
            ->groupBy('question_id');

        $rows = [];

        foreach (array_values($questionIds) as $index => $questionId) {
            $question = $questions->get($questionId);
            if (! $question) {
                continue;
            }

            $answer = $answers->get($questionId);
            $status = 'unanswered';
            $participantAnswer = null;
            $correctAnswer = null;
            $options = [];
            $acceptedAnswers = [];

            if ($question->question_type === 'multiple_choice') {
                $optionItems = $optionsByQuestion->get($questionId, collect())
                    ->map(fn ($option) => [
                        'id' => (int) $option->id,
                        'option_key' => (string) $option->option_key,
                        'option_text' => (string) ($option->option_text ?? ''),
                        'option_image_url' => $this->publicStorageUrl($option->option_image_path),
                        'is_correct' => (bool) $option->is_correct,
                        'sort_order' => (int) $option->sort_order,
                    ])
                    ->values()
                    ->all();

                if ($shuffleOptions && count($optionItems) > 1) {
                    $optionItems = DeterministicShuffle::shuffle(
                        $optionItems,
                        $this->seedForQuestion((int) $attempt->id, (int) $attempt->quiz_id, $questionId)
                    );
                }

                $selectedId = $answer?->selected_option_id ? (int) $answer->selected_option_id : null;

                foreach ($optionItems as $optionItem) {
                    $isSelected = $selectedId !== null && $selectedId === (int) $optionItem['id'];
                    $options[] = $optionItem + ['is_selected' => $isSelected];

                    if ($isSelected) {
                        $participantAnswer = $this->formatOptionAnswer($optionItem);
                    }

                    if (! empty($optionItem['is_correct'])) {
                        $correctAnswer = $this->formatOptionAnswer($optionItem);
                    }
                }

                $status = $participantAnswer === null
                    ? 'unanswered'
                    : (($answer?->is_correct ?? false) ? 'correct' : 'wrong');
            } else {
                $participantAnswer = is_string($answer?->answer_text) ? trim($answer->answer_text) : null;
                $acceptedAnswers = $keysByQuestion->get($questionId, collect())
                    ->pluck('answer_text')
                    ->map(fn ($text) => (string) $text)
                    ->values()
                    ->all();
                $correctAnswer = $acceptedAnswers !== [] ? implode(' | ', $acceptedAnswers) : null;
                $status = $participantAnswer === null || $participantAnswer === ''
                    ? 'unanswered'
                    : (($answer?->is_correct ?? false) ? 'correct' : 'wrong');
            }

            $rows[] = [
                'no' => $index + 1,
                'question_type' => (string) $question->question_type,
                'question_text' => (string) ($question->question_text ?? ''),
                'question_image_url' => $this->publicStorageUrl($question->question_image_path),
                'participant_answer' => $participantAnswer,
                'correct_answer' => $correctAnswer,
                'accepted_answers' => $acceptedAnswers,
                'status' => $status,
                'options' => $options,
            ];
        }

        return $rows;
    }

    private function formatOptionAnswer(array $option): string
    {
        $parts = [];

        if (($option['option_key'] ?? '') !== '') {
            $parts[] = $option['option_key'].'.';
        }

        if (($option['option_text'] ?? '') !== '') {
            $parts[] = $option['option_text'];
        }

        if (($option['option_image_url'] ?? null) !== null) {
            $parts[] = '[gambar]';
        }

        return implode(' ', $parts);
    }

    private function publicStorageUrl(?string $path): ?string
    {
        if (! is_string($path) || $path === '') {
            return null;
        }

        return Storage::disk('public')->url($path);
    }

    private function seedFromAttempt(int $attemptId, int $quizId): int
    {
        $hash = hash('sha256', 'attempt:'.$attemptId.':quiz:'.$quizId, true);
        $unpacked = unpack('N', substr($hash, 0, 4));

        return (int) ($unpacked[1] ?? 1);
    }

    private function seedForQuestion(int $attemptId, int $quizId, int $questionId): int
    {
        $hash = hash('sha256', 'attempt:'.$attemptId.':quiz:'.$quizId.':q:'.$questionId, true);
        $unpacked = unpack('N', substr($hash, 0, 4));

        return (int) ($unpacked[1] ?? 1);
    }
}
