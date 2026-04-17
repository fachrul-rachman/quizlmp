<?php

namespace App\Livewire\Admin;

use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Quiz;
use App\Models\ShortAnswerKey;
use App\Services\QuizQuestionImportParser;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithFileUploads;

class QuizBuilder extends Component
{
    use WithFileUploads;

    public ?int $quizId = null;

    public string $title = '';
    public ?string $description = null;
    public int $durationMinutes = 1;
    public bool $shuffleQuestions = false;
    public bool $shuffleOptions = false;
    public bool $isActive = true;

    /**
     * @var array<int, array<string, mixed>>
     */
    public array $questions = [];

    public $importFile;

    public function mount(?int $quizId = null): void
    {
        $this->quizId = $quizId;

        if ($quizId === null) {
            $this->questions = [
                $this->blankQuestion(1),
            ];

            return;
        }

        $quiz = Quiz::query()
            ->with(['questions' => fn ($q) => $q->orderBy('order_number'), 'questions.options' => fn ($q) => $q->orderBy('sort_order'), 'questions.shortAnswerKeys' => fn ($q) => $q->orderBy('sort_order')])
            ->findOrFail($quizId);

        $this->title = $quiz->title;
        $this->description = $quiz->description;
        $this->durationMinutes = (int) $quiz->duration_minutes;
        $this->shuffleQuestions = (bool) $quiz->shuffle_questions;
        $this->shuffleOptions = (bool) $quiz->shuffle_options;
        $this->isActive = (bool) $quiz->is_active;

        $this->questions = $quiz->questions->map(function (Question $question) {
            if ($question->question_type === 'short_answer') {
                $keys = $question->shortAnswerKeys->pluck('answer_text')->all();
                $keyString = implode('|', $keys);
            } else {
                $keyString = '';
            }

            $options = $question->options->map(function (QuestionOption $opt) {
                return [
                    'id' => $opt->id,
                    'option_text' => (string) ($opt->option_text ?? ''),
                    'is_correct' => (bool) $opt->is_correct,
                ];
            })->all();

            if (count($options) < 2) {
                $options = [
                    $this->blankOption(),
                    $this->blankOption(),
                ];
            }

            return [
                'id' => $question->id,
                'question_text' => $question->question_text,
                'question_type' => $question->question_type,
                'is_active' => (bool) $question->is_active,
                'options' => $options,
                'short_answers' => $keyString,
            ];
        })->all();

        if ($this->questions === []) {
            $this->questions = [
                $this->blankQuestion(1),
            ];
        }
    }

    public function addQuestion(): void
    {
        $this->questions[] = $this->blankQuestion(count($this->questions) + 1);
    }

    public function removeQuestion(int $index): void
    {
        unset($this->questions[$index]);
        $this->questions = array_values($this->questions);
    }

    public function addOption(int $questionIndex): void
    {
        $options = $this->questions[$questionIndex]['options'] ?? [];
        if (count($options) >= 5) {
            return;
        }

        $options[] = $this->blankOption();
        $this->questions[$questionIndex]['options'] = $options;
    }

    public function removeOption(int $questionIndex, int $optionIndex): void
    {
        $options = $this->questions[$questionIndex]['options'] ?? [];
        unset($options[$optionIndex]);
        $options = array_values($options);

        if (count($options) < 2) {
            $options[] = $this->blankOption();
        }

        $this->questions[$questionIndex]['options'] = $options;
    }

    public function markCorrect(int $questionIndex, int $optionIndex): void
    {
        $options = $this->questions[$questionIndex]['options'] ?? [];
        foreach ($options as $idx => $opt) {
            $options[$idx]['is_correct'] = ($idx === $optionIndex);
        }
        $this->questions[$questionIndex]['options'] = $options;
    }

    public function save(): void
    {
        $this->validateBase();
        $this->validateQuestions();

        DB::transaction(function (): void {
            $quiz = $this->quizId
                ? Quiz::query()->findOrFail($this->quizId)
                : new Quiz();

            $quiz->fill([
                'title' => $this->title,
                'description' => $this->description,
                'duration_minutes' => $this->durationMinutes,
                'shuffle_questions' => $this->shuffleQuestions,
                'shuffle_options' => $this->shuffleOptions,
                'is_active' => $this->isActive,
            ]);

            if (! $quiz->exists) {
                $quiz->created_by = auth()->id();
            }

            $quiz->updated_by = auth()->id();
            $quiz->save();

            $keepQuestionIds = [];

            foreach (array_values($this->questions) as $pos => $q) {
                $question = null;
                if (! empty($q['id'])) {
                    $question = Question::query()
                        ->where('quiz_id', $quiz->id)
                        ->where('id', (int) $q['id'])
                        ->first();
                }

                if (! $question) {
                    $question = new Question();
                    $question->quiz_id = $quiz->id;
                    $question->created_by = auth()->id();
                }

                $question->fill([
                    'question_type' => $q['question_type'],
                    'question_text' => $q['question_text'],
                    'order_number' => $pos + 1,
                    'is_active' => (bool) ($q['is_active'] ?? true),
                ]);
                $question->updated_by = auth()->id();
                $question->save();

                $keepQuestionIds[] = $question->id;

                if ($q['question_type'] === 'multiple_choice') {
                    $this->syncOptions($question, $q['options']);
                    ShortAnswerKey::query()->where('question_id', $question->id)->delete();
                } else {
                    QuestionOption::query()->where('question_id', $question->id)->delete();
                    $this->syncShortAnswers($question, (string) ($q['short_answers'] ?? ''));
                }
            }

            Question::query()
                ->where('quiz_id', $quiz->id)
                ->whereNotIn('id', $keepQuestionIds)
                ->delete();
        });

        session()->flash('success', 'Quiz berhasil disimpan.');
        $this->redirect('/admin/quizzes', navigate: false);
    }

    public function importFromXlsx(QuizQuestionImportParser $parser): void
    {
        $this->validate([
            'importFile' => ['required', 'file', 'mimes:xlsx'],
        ], [
            'importFile.required' => 'File import wajib diisi.',
            'importFile.mimes' => 'File import tidak valid.',
        ]);

        $parsed = $parser->parse($this->importFile);

        foreach ($parsed as $q) {
            if ($q['question_type'] === 'multiple_choice') {
                $options = array_map(fn ($opt) => [
                    'id' => null,
                    'option_text' => $opt['option_text'],
                    'is_correct' => (bool) $opt['is_correct'],
                ], $q['options']);

                if (count($options) < 2) {
                    $options = [
                        $this->blankOption(),
                        $this->blankOption(),
                    ];
                }

                $this->questions[] = [
                    'id' => null,
                    'question_text' => $q['question_text'],
                    'question_type' => 'multiple_choice',
                    'is_active' => true,
                    'options' => $options,
                    'short_answers' => '',
                ];
            } else {
                $this->questions[] = [
                    'id' => null,
                    'question_text' => $q['question_text'],
                    'question_type' => 'short_answer',
                    'is_active' => true,
                    'options' => [
                        $this->blankOption(),
                        $this->blankOption(),
                    ],
                    'short_answers' => implode('|', $q['short_answers']),
                ];
            }
        }

        $this->importFile = null;
        session()->flash('success', 'Import soal berhasil dilakukan.');
    }

    private function validateBase(): void
    {
        $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'durationMinutes' => ['required', 'integer', 'min:1'],
            'shuffleQuestions' => ['boolean'],
            'shuffleOptions' => ['boolean'],
            'isActive' => ['boolean'],
        ], [], [
            'title' => 'Nama quiz',
            'description' => 'Deskripsi',
            'durationMinutes' => 'Durasi',
            'shuffleQuestions' => 'Shuffle Soal',
            'shuffleOptions' => 'Shuffle Opsi',
            'isActive' => 'Status Aktif',
        ]);
    }

    private function validateQuestions(): void
    {
        if (count($this->questions) < 1) {
            throw ValidationException::withMessages([
                'questions' => 'Minimal 1 soal harus diisi.',
            ]);
        }

        foreach (array_values($this->questions) as $qi => $q) {
            if (trim((string) ($q['question_text'] ?? '')) === '') {
                throw ValidationException::withMessages([
                    "questions.$qi.question_text" => 'Soal wajib diisi.',
                ]);
            }

            $type = (string) ($q['question_type'] ?? '');
            if (! in_array($type, ['multiple_choice', 'short_answer'], true)) {
                throw ValidationException::withMessages([
                    "questions.$qi.question_type" => 'Jenis jawaban tidak valid.',
                ]);
            }

            if ($type === 'multiple_choice') {
                $options = $q['options'] ?? [];
                if (! is_array($options) || count($options) < 2) {
                    throw ValidationException::withMessages([
                        "questions.$qi.options" => 'Minimal 2 opsi harus diisi.',
                    ]);
                }

                $filled = [];
                $correctCount = 0;
                foreach (array_values($options) as $oi => $opt) {
                    $text = trim((string) ($opt['option_text'] ?? ''));
                    if ($text === '') {
                        throw ValidationException::withMessages([
                            "questions.$qi.options.$oi.option_text" => 'Opsi wajib diisi.',
                        ]);
                    }
                    $filled[] = $text;
                    if (! empty($opt['is_correct'])) {
                        $correctCount++;
                    }
                }

                if ($correctCount !== 1) {
                    throw ValidationException::withMessages([
                        "questions.$qi.options" => 'Tepat 1 opsi harus ditandai benar.',
                    ]);
                }
            } else {
                $raw = (string) ($q['short_answers'] ?? '');
                $keys = $this->parseShortAnswers($raw);
                if (count($keys) < 1) {
                    throw ValidationException::withMessages([
                        "questions.$qi.short_answers" => 'Jawaban benar wajib diisi.',
                    ]);
                }
            }
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $options
     */
    private function syncOptions(Question $question, array $options): void
    {
        $keys = ['A', 'B', 'C', 'D', 'E'];
        $keepOptionIds = [];

        foreach (array_values($options) as $idx => $opt) {
            $option = null;
            if (! empty($opt['id'])) {
                $option = QuestionOption::query()
                    ->where('question_id', $question->id)
                    ->where('id', (int) $opt['id'])
                    ->first();
            }

            if (! $option) {
                $option = new QuestionOption();
                $option->question_id = $question->id;
            }

            $option->fill([
                'option_key' => $keys[$idx] ?? 'E',
                'option_text' => trim((string) $opt['option_text']),
                'option_image_path' => null,
                'is_correct' => (bool) ($opt['is_correct'] ?? false),
                'sort_order' => $idx + 1,
            ]);
            $option->save();

            $keepOptionIds[] = $option->id;
        }

        QuestionOption::query()
            ->where('question_id', $question->id)
            ->whereNotIn('id', $keepOptionIds)
            ->delete();
    }

    private function syncShortAnswers(Question $question, string $raw): void
    {
        $keys = $this->parseShortAnswers($raw);

        ShortAnswerKey::query()->where('question_id', $question->id)->delete();

        foreach ($keys as $idx => $answer) {
            ShortAnswerKey::create([
                'question_id' => $question->id,
                'answer_text' => $answer,
                'normalized_answer_text' => $this->normalizeShortAnswer($answer),
                'sort_order' => $idx + 1,
            ]);
        }
    }

    /**
     * @return array<int, string>
     */
    private function parseShortAnswers(string $raw): array
    {
        $parts = array_map('trim', explode('|', $raw));
        $parts = array_values(array_filter($parts, fn ($v) => $v !== ''));

        $seen = [];
        $out = [];
        foreach ($parts as $p) {
            $norm = $this->normalizeShortAnswer($p);
            if ($norm === '' || isset($seen[$norm])) {
                continue;
            }
            $seen[$norm] = true;
            $out[] = $p;
        }

        return $out;
    }

    private function normalizeShortAnswer(string $text): string
    {
        $text = mb_strtolower(trim($text));
        $text = preg_replace('/\s+/', ' ', $text) ?? $text;
        return $text;
    }

    /**
     * @return array<string, mixed>
     */
    private function blankQuestion(int $order): array
    {
        return [
            'id' => null,
            'question_text' => '',
            'question_type' => 'multiple_choice',
            'is_active' => true,
            'options' => [
                $this->blankOption(),
                $this->blankOption(),
            ],
            'short_answers' => '',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function blankOption(): array
    {
        return [
            'id' => null,
            'option_text' => '',
            'is_correct' => false,
        ];
    }

    public function render()
    {
        return view('livewire.admin.quiz-builder');
    }
}
