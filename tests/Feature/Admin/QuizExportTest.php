<?php

use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Quiz;
use App\Models\ShortAnswerKey;
use App\Models\User;
use PhpOffice\PhpSpreadsheet\IOFactory;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\post;

it('exports selected quizzes to one worksheet per quiz with their questions and answers', function () {
    $admin = User::factory()->create();
    $firstQuiz = createQuizForExport($admin, 'Tes / Potensi: Umum');
    $secondQuiz = createQuizForExport($admin, 'Wawancara HR');

    $choiceQuestion = createQuestionForExport($firstQuiz, [
        'question_type' => 'multiple_choice',
        'question_text' => '=Pertanyaan yang harus tetap berupa teks',
        'question_image_path' => 'questions/diagram-1.png',
        'difficulty_level' => 'sedang',
        'order_number' => 1,
    ]);

    QuestionOption::query()->create([
        'question_id' => $choiceQuestion->id,
        'option_key' => 'A',
        'option_text' => 'Pilihan pertama',
        'option_image_path' => 'options/pilihan-a.png',
        'is_correct' => true,
        'sort_order' => 1,
    ]);
    QuestionOption::query()->create([
        'question_id' => $choiceQuestion->id,
        'option_key' => 'B',
        'option_text' => 'Pilihan kedua',
        'is_correct' => false,
        'sort_order' => 2,
    ]);

    $shortQuestion = createQuestionForExport($secondQuiz, [
        'question_type' => 'short_answer',
        'question_text' => 'Sebutkan warna langit.',
        'difficulty_level' => 'mudah',
        'order_number' => 1,
    ]);
    ShortAnswerKey::query()->create([
        'question_id' => $shortQuestion->id,
        'answer_text' => 'Biru',
        'normalized_answer_text' => 'biru',
        'sort_order' => 1,
    ]);

    actingAs($admin);
    $response = post('/admin/quizzes/export', [
        'quiz_ids' => [$secondQuiz->id, $firstQuiz->id],
    ]);

    $response->assertOk()->assertDownload();

    $path = $response->baseResponse->getFile()->getPathname();
    $spreadsheet = IOFactory::load($path);

    expect($spreadsheet->getSheetCount())->toBe(2)
        ->and($spreadsheet->getSheet(0)->getTitle())->toBe('Wawancara HR')
        ->and($spreadsheet->getSheet(1)->getTitle())->toBe('Tes Potensi Umum')
        ->and($spreadsheet->getSheet(0)->getCell('C4')->getValue())->toBe('Wawancara HR')
        ->and($spreadsheet->getSheet(0)->getCell('E16')->getValue())->toBe('Sebutkan warna langit.')
        ->and($spreadsheet->getSheet(0)->getCell('R16')->getValue())->toBe('Biru')
        ->and($spreadsheet->getSheet(1)->getCell('E16')->getValue())->toBe('=Pertanyaan yang harus tetap berupa teks')
        ->and($spreadsheet->getSheet(1)->getCell('E16')->getDataType())->toBe('s')
        ->and($spreadsheet->getSheet(1)->getCell('F16')->getValue())->toBe('questions/diagram-1.png')
        ->and($spreadsheet->getSheet(1)->getCell('G16')->getValue())->toBe('Pilihan pertama')
        ->and($spreadsheet->getSheet(1)->getCell('H16')->getValue())->toBe('options/pilihan-a.png')
        ->and($spreadsheet->getSheet(1)->getCell('Q16')->getValue())->toBe('A');

    $spreadsheet->disconnectWorksheets();
    @unlink($path);
});

it('rejects the whole export when a regular admin selects another owners quiz', function () {
    $admin = User::factory()->create();
    $otherAdmin = User::factory()->create();
    $ownQuiz = createQuizForExport($admin, 'Quiz Milik Sendiri');
    $otherQuiz = createQuizForExport($otherAdmin, 'Quiz Milik Orang Lain');

    actingAs($admin);
    post('/admin/quizzes/export', [
        'quiz_ids' => [$ownQuiz->id, $otherQuiz->id],
    ])->assertNotFound();
});

it('allows a super admin to export quizzes from different owners', function () {
    $firstAdmin = User::factory()->create();
    $secondAdmin = User::factory()->create();
    $superAdmin = User::factory()->create(['role' => 'super_admin']);
    $firstQuiz = createQuizForExport($firstAdmin, str_repeat('Quiz panjang / ', 4));
    $secondQuiz = createQuizForExport($secondAdmin, str_repeat('Quiz panjang / ', 4));

    actingAs($superAdmin);
    $response = post('/admin/quizzes/export', [
        'quiz_ids' => [$firstQuiz->id, $secondQuiz->id],
    ]);

    $response->assertOk()->assertDownload();

    $path = $response->baseResponse->getFile()->getPathname();
    $spreadsheet = IOFactory::load($path);
    $titles = array_map(
        fn ($sheet) => $sheet->getTitle(),
        $spreadsheet->getAllSheets(),
    );

    expect($titles[0])->not->toBe($titles[1])
        ->and(mb_strlen($titles[0]))->toBeLessThanOrEqual(31)
        ->and(mb_strlen($titles[1]))->toBeLessThanOrEqual(31);

    $spreadsheet->disconnectWorksheets();
    @unlink($path);
});

it('requires at least one quiz and shows export controls on the quiz list', function () {
    $admin = User::factory()->create();
    createQuizForExport($admin, 'Quiz Pilihan');

    actingAs($admin);
    post('/admin/quizzes/export', [])
        ->assertRedirect()
        ->assertSessionHasErrors('quiz_ids');

    get('/admin/quizzes')
        ->assertOk()
        ->assertSee('Export Terpilih')
        ->assertSee('quiz_ids[]', false);
});

function createQuizForExport(User $creator, string $title): Quiz
{
    return Quiz::query()->create([
        'title' => $title,
        'description' => 'Deskripsi quiz',
        'duration_minutes' => 25,
        'shuffle_questions' => true,
        'shuffle_options' => false,
        'instant_feedback_enabled' => true,
        'difficulty_levels_enabled' => true,
        'is_active' => true,
        'created_by' => $creator->id,
        'updated_by' => $creator->id,
    ]);
}

function createQuestionForExport(Quiz $quiz, array $attributes): Question
{
    return Question::query()->create(array_merge([
        'quiz_id' => $quiz->id,
        'question_type' => 'multiple_choice',
        'question_text' => 'Pertanyaan',
        'difficulty_level' => 'mudah',
        'order_number' => 1,
        'is_active' => true,
        'created_by' => $quiz->created_by,
        'updated_by' => $quiz->created_by,
    ], $attributes));
}
