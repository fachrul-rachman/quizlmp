<?php

namespace App\Livewire\Participant;

use App\Models\AttemptAnswer;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\QuizAttempt;
use App\Models\QuizLink;
use App\Models\QuizResult;
use App\Services\GradeService;
use App\Services\Pdf\ResultPdfService;
use App\Support\DeterministicShuffle;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class QuizWork extends Component
{
    public string $token;

    public string $state = 'loading';
    public string $title = '';
    public string $participantName = '';
    public string $participantAppliedFor = '';
    public int $secondsRemaining = 0;

    public int $attemptId = 0;
    public int $quizId = 0;

    public int $step = 1;

    /** @var array<int, int> */
    public array $questionIds = [];

    public ?int $currentQuestionId = null;
    public ?string $currentQuestionText = null;
    public ?string $currentQuestionType = null;

    /** @var array<int, array{id:int,label:string,text:string,is_correct:bool}> */
    public array $currentOptions = [];

    public ?int $selectedOptionId = null;
    public string $shortAnswerText = '';

    public int $answeredCount = 0;
    public int $totalQuestions = 0;
    public bool $canSubmit = false;
    public bool $showSubmitConfirm = false;

    protected array $queryString = [
        'step' => ['except' => 1],
    ];

    public function mount(string $token): void
    {
        $this->token = $token;

        $link = QuizLink::query()
            ->with(['quiz:id,title,is_active,shuffle_questions,shuffle_options', 'attempt'])
            ->where('token', $token)
            ->first();

        if (! $link) {
            $this->state = 'invalid';
            return;
        }

        if (in_array($link->status, ['submitted', 'expired'], true)) {
            $this->state = $link->status === 'submitted' ? 'submitted' : 'expired';
            return;
        }

        if (! $link->quiz || ! $link->quiz->is_active) {
            $this->state = 'unavailable';
            return;
        }

        if (! $link->attempt) {
            $this->redirect('/quiz/'.$token, navigate: false);
            return;
        }

        if ($link->attempt->status !== 'in_progress') {
            $this->redirect('/quiz/'.$token, navigate: false);
            return;
        }

        $this->title = (string) $link->quiz->title;
        $this->participantName = (string) $link->attempt->participant_name;
        $this->participantAppliedFor = (string) $link->attempt->participant_applied_for;

        $this->attemptId = (int) $link->attempt->id;
        $this->quizId = (int) $link->attempt->quiz_id;

        $this->secondsRemaining = $this->calculateSecondsRemaining($link->attempt);
        if ($this->secondsRemaining <= 0) {
            $this->finalizeAutoIfNeeded();
            return;
        }

        $this->questionIds = $this->buildOrderedQuestionIds($this->quizId, (bool) $link->quiz->shuffle_questions, $this->attemptId, (int) $link->quiz->id);
        if ($this->questionIds === []) {
            $this->state = 'no_questions';
            return;
        }

        $this->step = max(1, min((int) $this->step, count($this->questionIds)));
        $this->loadStep($this->attemptId, $this->quizId, (bool) $link->quiz->shuffle_options, (int) $link->quiz->id);
        $this->refreshProgress();

        $this->state = 'work';
    }

    public function tick(): void
    {
        if ($this->attemptId <= 0) {
            return;
        }

        $attempt = QuizAttempt::query()->find($this->attemptId);
        if (! $attempt) {
            $this->state = 'invalid';
            return;
        }

        $this->secondsRemaining = $this->calculateSecondsRemaining($attempt);
        if ($this->secondsRemaining <= 0) {
            $this->finalizeAutoIfNeeded();
        }
    }

    public function updatedSelectedOptionId(): void
    {
        if ($this->state !== 'work') {
            return;
        }

        $this->saveAnswerDraft();
        $this->refreshProgress();
    }

    public function updatedShortAnswerText(): void
    {
        if ($this->state !== 'work') {
            return;
        }

        $this->saveAnswerDraft();
        $this->refreshProgress();
    }

    public function prev(): void
    {
        if ($this->step <= 1) {
            return;
        }

        $this->saveAnswerDraft();
        $this->step--;
        $this->reloadForStep();
    }

    public function next(): void
    {
        if ($this->step >= count($this->questionIds)) {
            return;
        }

        $this->saveAnswerDraft();
        $this->step++;
        $this->reloadForStep();
    }

    public function goToStep(int $step): void
    {
        $step = max(1, min($step, count($this->questionIds)));
        if ($step === $this->step) {
            return;
        }

        $this->saveAnswerDraft();
        $this->step = $step;
        $this->reloadForStep();
    }

    public function openSubmitConfirm(): void
    {
        $this->saveAnswerDraft();
        $this->refreshProgress();

        if (! $this->canSubmit) {
            return;
        }

        $this->showSubmitConfirm = true;
    }

    public function cancelSubmit(): void
    {
        $this->showSubmitConfirm = false;
    }

    public function submit(): void
    {
        $this->saveAnswerDraft();
        $this->refreshProgress();

        if (! $this->canSubmit) {
            return;
        }

        $this->finalize('submitted');
        $this->redirect('/quiz/'.$this->token.'/done', navigate: false);
    }

    private function reloadForStep(): void
    {
        $link = QuizLink::query()
            ->with(['quiz:id,shuffle_options', 'attempt'])
            ->where('token', $this->token)
            ->first();

        if (! $link || ! $link->attempt || ! $link->quiz) {
            $this->state = 'invalid';
            return;
        }

        $this->secondsRemaining = $this->calculateSecondsRemaining($link->attempt);
        if ($this->secondsRemaining <= 0) {
            $this->finalizeAutoIfNeeded();
            return;
        }

        $this->attemptId = (int) $link->attempt->id;
        $this->quizId = (int) $link->attempt->quiz_id;

        $this->loadStep($this->attemptId, $this->quizId, (bool) $link->quiz->shuffle_options, (int) $link->quiz->id);
        $this->refreshProgress();
    }

    private function saveAnswerDraft(): void
    {
        $link = QuizLink::query()
            ->with('attempt')
            ->where('token', $this->token)
            ->first();

        if (! $link || ! $link->attempt) {
            return;
        }

        $this->secondsRemaining = $this->calculateSecondsRemaining($link->attempt);
        if ($this->secondsRemaining <= 0) {
            return;
        }

        if (! $this->currentQuestionId || ! $this->currentQuestionType) {
            return;
        }

        if ($this->currentQuestionType === 'multiple_choice') {
            $selected = $this->selectedOptionId;
            AttemptAnswer::updateOrCreate(
                ['quiz_attempt_id' => $link->attempt->id, 'question_id' => $this->currentQuestionId],
                [
                    'selected_option_id' => $selected ?: null,
                    'answer_text' => null,
                    'answered_at' => $selected ? now() : null,
                ],
            );

            return;
        }

        $text = trim($this->shortAnswerText);
        AttemptAnswer::updateOrCreate(
            ['quiz_attempt_id' => $link->attempt->id, 'question_id' => $this->currentQuestionId],
            [
                'selected_option_id' => null,
                'answer_text' => $text !== '' ? $text : null,
                'answered_at' => $text !== '' ? now() : null,
            ],
        );
    }

    private function calculateSecondsRemaining(QuizAttempt $attempt): int
    {
        if (! $attempt->started_at) {
            return 0;
        }

        $startedAt = CarbonImmutable::parse($attempt->started_at);
        $deadline = $startedAt->addMinutes((int) $attempt->time_limit_minutes);
        $diff = $deadline->diffInSeconds(CarbonImmutable::now(), false) * -1;
        return max(0, (int) $diff);
    }

    private function refreshProgress(): void
    {
        $this->totalQuestions = count($this->questionIds);
        if ($this->attemptId <= 0 || $this->totalQuestions === 0) {
            $this->answeredCount = 0;
            $this->canSubmit = false;
            return;
        }

        $answers = AttemptAnswer::query()
            ->where('quiz_attempt_id', $this->attemptId)
            ->whereIn('question_id', $this->questionIds)
            ->get(['question_id', 'selected_option_id', 'answer_text'])
            ->keyBy('question_id');

        $answered = 0;
        foreach ($this->questionIds as $qid) {
            $a = $answers->get($qid);
            if (! $a) {
                continue;
            }

            if ($a->selected_option_id) {
                $answered++;
                continue;
            }

            if (is_string($a->answer_text) && trim($a->answer_text) !== '') {
                $answered++;
            }
        }

        $this->answeredCount = $answered;
        $this->canSubmit = ($answered === $this->totalQuestions);
    }

    /**
     * @return array<int, int>
     */
    private function buildOrderedQuestionIds(int $quizId, bool $shuffleQuestions, int $attemptId, int $quizPk): array
    {
        $ids = DB::table('questions')
            ->where('quiz_id', $quizId)
            ->whereNull('deleted_at')
            ->where('is_active', true)
            ->orderBy('order_number')
            ->pluck('id')
            ->map(fn ($v) => (int) $v)
            ->all();

        if (! $shuffleQuestions || count($ids) <= 1) {
            return $ids;
        }

        $seed = $this->seedFromAttempt($attemptId, $quizPk);
        return DeterministicShuffle::shuffle($ids, $seed);
    }

    private function seedFromAttempt(int $attemptId, int $quizPk): int
    {
        $hash = hash('sha256', 'attempt:'.$attemptId.':quiz:'.$quizPk, true);
        $unpacked = unpack('N', substr($hash, 0, 4));
        return (int) ($unpacked[1] ?? 1);
    }

    private function seedForQuestion(int $attemptId, int $quizPk, int $questionId): int
    {
        $hash = hash('sha256', 'attempt:'.$attemptId.':quiz:'.$quizPk.':q:'.$questionId, true);
        $unpacked = unpack('N', substr($hash, 0, 4));
        return (int) ($unpacked[1] ?? 1);
    }

    private function loadStep(int $attemptId, int $quizId, bool $shuffleOptions, int $quizPk): void
    {
        $questionId = $this->questionIds[$this->step - 1] ?? null;
        if (! $questionId) {
            throw ValidationException::withMessages(['step' => 'Soal tidak ditemukan.']);
        }

        $question = Question::query()
            ->where('quiz_id', $quizId)
            ->where('id', $questionId)
            ->first();

        if (! $question) {
            throw ValidationException::withMessages(['step' => 'Soal tidak ditemukan.']);
        }

        $this->currentQuestionId = (int) $question->id;
        $this->currentQuestionText = (string) $question->question_text;
        $this->currentQuestionType = (string) $question->question_type;

        $answer = AttemptAnswer::query()
            ->where('quiz_attempt_id', $attemptId)
            ->where('question_id', $question->id)
            ->first();

        $this->selectedOptionId = $answer?->selected_option_id ? (int) $answer->selected_option_id : null;
        $this->shortAnswerText = (string) ($answer?->answer_text ?? '');

        $this->currentOptions = [];
        if ($question->question_type === 'multiple_choice') {
            $options = QuestionOption::query()
                ->where('question_id', $question->id)
                ->whereNull('deleted_at')
                ->orderBy('sort_order')
                ->get(['id', 'option_text', 'sort_order']);

            $items = $options->map(fn ($o) => [
                'id' => (int) $o->id,
                'text' => (string) ($o->option_text ?? ''),
                'sort_order' => (int) $o->sort_order,
            ])->all();

            if ($shuffleOptions && count($items) > 1) {
                $seed = $this->seedForQuestion($attemptId, $quizPk, (int) $question->id);
                $items = DeterministicShuffle::shuffle($items, $seed);
            }

            $labels = ['A', 'B', 'C', 'D', 'E'];
            foreach (array_values($items) as $idx => $it) {
                $this->currentOptions[] = [
                    'id' => (int) $it['id'],
                    'label' => $labels[$idx] ?? '',
                    'text' => (string) $it['text'],
                    'is_correct' => false,
                ];
            }
        }
    }

    private function finalizeAutoIfNeeded(): void
    {
        $this->finalize('auto_submitted');
        $this->state = 'expired_view';
        $this->redirect('/quiz/'.$this->token.'/done', navigate: false);
    }

    /**
     * @param  'submitted'|'auto_submitted'  $resultStatus
     */
    private function finalize(string $resultStatus): void
    {
        if ($this->attemptId <= 0) {
            return;
        }

        $gradeService = new GradeService();
        $resultId = null;

        DB::transaction(function () use ($resultStatus, $gradeService, &$resultId): void {
            $attempt = QuizAttempt::query()
                ->where('id', $this->attemptId)
                ->lockForUpdate()
                ->first();

            $link = QuizLink::query()
                ->where('token', $this->token)
                ->lockForUpdate()
                ->first();

            if (! $attempt || ! $link) {
                return;
            }

            if (in_array($attempt->status, ['submitted', 'auto_submitted'], true)) {
                return;
            }

            if (in_array($link->status, ['submitted', 'expired'], true)) {
                return;
            }

            $secondsRemaining = $this->calculateSecondsRemaining($attempt);
            $isExpired = $secondsRemaining <= 0;

            $finalAttemptStatus = $isExpired ? 'auto_submitted' : $resultStatus;
            $finalLinkStatus = $isExpired ? 'expired' : 'submitted';

            $now = now();

            $attempt->status = $finalAttemptStatus;
            $attempt->submitted_at = $now;
            $attempt->save();

            $link->status = $finalLinkStatus;
            $link->submitted_at = $now;
            $link->expired_at = $now;
            $link->save();

            $questionIds = DB::table('questions')
                ->where('quiz_id', $attempt->quiz_id)
                ->whereNull('deleted_at')
                ->where('is_active', true)
                ->pluck('id')
                ->map(fn ($v) => (int) $v)
                ->all();

            $total = count($questionIds);

            $answers = AttemptAnswer::query()
                ->where('quiz_attempt_id', $attempt->id)
                ->whereIn('question_id', $questionIds)
                ->get(['id', 'question_id', 'selected_option_id', 'answer_text'])
                ->keyBy('question_id');

            $questions = Question::query()
                ->whereIn('id', $questionIds)
                ->get(['id', 'question_type'])
                ->keyBy('id');

            $correct = 0;
            $wrong = 0;
            $unanswered = 0;

            $isCorrectByQuestionId = [];

            foreach ($questionIds as $qid) {
                $question = $questions->get($qid);
                if (! $question) {
                    continue;
                }

                $answer = $answers->get($qid);
                if (! $answer) {
                    $unanswered++;
                    $isCorrectByQuestionId[$qid] = false;
                    continue;
                }

                if ($question->question_type === 'multiple_choice') {
                    if (! $answer->selected_option_id) {
                        $unanswered++;
                        $isCorrectByQuestionId[$qid] = false;
                        continue;
                    }

                    $isCorrect = (bool) DB::table('question_options')
                        ->where('id', $answer->selected_option_id)
                        ->where('question_id', $qid)
                        ->whereNull('deleted_at')
                        ->value('is_correct');

                    $isCorrectByQuestionId[$qid] = $isCorrect;

                    if ($isCorrect) {
                        $correct++;
                    } else {
                        $wrong++;
                    }

                    continue;
                }

                $text = trim((string) ($answer->answer_text ?? ''));
                if ($text === '') {
                    $unanswered++;
                    $isCorrectByQuestionId[$qid] = false;
                    continue;
                }

                $normalized = $this->normalizeShortAnswer($text);
                $exists = DB::table('short_answer_keys')
                    ->where('question_id', $qid)
                    ->where('normalized_answer_text', $normalized)
                    ->exists();

                $isCorrectByQuestionId[$qid] = $exists;

                if ($exists) {
                    $correct++;
                } else {
                    $wrong++;
                }
            }

            if ($total <= 0) {
                $score = 0.0;
            } else {
                $score = ($correct / $total) * 100.0;
            }

            $score2 = (float) number_format($score, 2, '.', '');
            $grade = $gradeService->fromScorePercentage($score2);

            $result = QuizResult::updateOrCreate(
                ['quiz_attempt_id' => $attempt->id],
                [
                    'quiz_id' => $attempt->quiz_id,
                    'total_questions' => $total,
                    'correct_answers' => $correct,
                    'wrong_answers' => $wrong,
                    'unanswered_answers' => $unanswered,
                    'score_percentage' => $score2,
                    'grade_letter' => $grade['grade_letter'],
                    'grade_label' => $grade['grade_label'],
                    'result_status' => $finalAttemptStatus === 'auto_submitted' ? 'auto_submitted' : 'submitted',
                    'calculated_at' => $now,
                ],
            );

            $resultId = (int) $result->id;

            foreach ($answers as $qid => $a) {
                AttemptAnswer::query()
                    ->where('id', $a->id)
                    ->update(['is_correct' => (bool) ($isCorrectByQuestionId[$qid] ?? false)]);
            }
        });

        if ($resultId) {
            try {
                app(ResultPdfService::class)->generateForResultId($resultId);
            } catch (\Throwable $e) {
                Log::error('result pdf generation threw', [
                    'quiz_result_id' => $resultId,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    private function normalizeShortAnswer(string $text): string
    {
        $text = mb_strtolower(trim($text));
        $text = preg_replace('/\s+/', ' ', $text) ?? $text;
        return $text;
    }

    public function render()
    {
        return view('livewire.participant.quiz-work');
    }
}
