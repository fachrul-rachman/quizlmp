<?php

use App\Models\Division;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizLink;
use App\Models\QuizResult;
use App\Models\User;
use App\Services\Discord\DiscordResultWebhookService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

it('renders HR identity data in the result PDF view', function () {
    [$attempt, $result, $quiz] = createHrResultOutputRecords();

    $html = view('pdf.result', [
        'quiz' => $quiz,
        'attempt' => $attempt,
        'result' => $result,
        'rows' => [],
        'printedAt' => now(),
    ])->render();

    expect($html)
        ->toContain('Data Peserta HR')
        ->toContain('27 tahun')
        ->toContain('163.50 cm')
        ->toContain('54.50 kg')
        ->toContain('Talent Acquisition')
        ->toContain('PT Contoh Indonesia')
        ->toContain('Januari 2023')
        ->not->toContain('(Recruiter)')
        ->toContain('Jakarta Selatan');
});

it('includes HR identity data in the Discord result payload', function () {
    [, $result] = createHrResultOutputRecords();

    Http::fake([
        'https://discord.com/api/webhooks/*' => Http::response('', 204),
    ]);

    putenv('DISCORD_WEBHOOK_ENABLED=true');
    $_ENV['DISCORD_WEBHOOK_ENABLED'] = 'true';
    $_SERVER['DISCORD_WEBHOOK_ENABLED'] = 'true';

    app(DiscordResultWebhookService::class)->sendForResultId($result->id);

    Http::assertSent(function (Request $request): bool {
        $fields = collect(data_get($request->data(), 'embeds.0.fields', []));

        return $request->url() === 'https://discord.com/api/webhooks/test/token'
            && $fields->contains(fn (array $field) => $field['name'] === 'Usia' && $field['value'] === '27 tahun')
            && $fields->contains(fn (array $field) => $field['name'] === 'Tinggi / Berat Badan' && $field['value'] === '163.50 cm / 54.50 kg')
            && $fields->contains(fn (array $field) => $field['name'] === 'Pekerjaan Terakhir' && $field['value'] === 'Talent Acquisition')
            && $fields->contains(fn (array $field) => $field['name'] === 'Perusahaan Terakhir' && $field['value'] === 'PT Contoh Indonesia')
            && $fields->contains(fn (array $field) => $field['name'] === 'Sejak Kapan Bekerja' && $field['value'] === 'Januari 2023')
            && $fields->doesntContain(fn (array $field) => $field['name'] === 'Jabatan')
            && $fields->contains(fn (array $field) => $field['name'] === 'Domisili Sekarang' && $field['value'] === 'Jakarta Selatan');
    });

    putenv('DISCORD_WEBHOOK_ENABLED');
    unset($_ENV['DISCORD_WEBHOOK_ENABLED'], $_SERVER['DISCORD_WEBHOOK_ENABLED']);
});

/**
 * @return array{0: QuizAttempt, 1: QuizResult, 2: Quiz}
 */
function createHrResultOutputRecords(): array
{
    $division = Division::query()->where('code', Division::HR)->firstOrFail();
    $admin = User::factory()->create([
        'division_id' => $division->id,
        'discord_webhook_url' => 'https://discord.com/api/webhooks/test/token',
    ]);
    $quiz = Quiz::query()->create([
        'title' => 'WPT',
        'description' => null,
        'duration_minutes' => 12,
        'shuffle_questions' => false,
        'shuffle_options' => false,
        'instant_feedback_enabled' => false,
        'difficulty_levels_enabled' => false,
        'is_active' => true,
        'created_by' => $admin->id,
        'updated_by' => $admin->id,
    ]);
    $link = QuizLink::query()->create([
        'quiz_id' => $quiz->id,
        'division_id' => $division->id,
        'token' => 'hr-result-output',
        'usage_type' => 'single',
        'status' => 'submitted',
        'created_by' => $admin->id,
    ]);
    $attempt = QuizAttempt::query()->create([
        'quiz_link_id' => $link->id,
        'quiz_id' => $quiz->id,
        'division_id' => $division->id,
        'participant_name' => 'Siti',
        'participant_applied_for' => 'Recruiter',
        'participant_age' => 27,
        'participant_height_cm' => 163.5,
        'participant_weight_kg' => 54.5,
        'participant_last_job' => 'Talent Acquisition',
        'participant_last_company' => 'PT Contoh Indonesia',
        'participant_last_job_started_on' => '2023-01-01',
        'participant_current_domicile' => 'Jakarta Selatan',
        'started_at' => now()->subMinutes(10),
        'submitted_at' => now(),
        'time_limit_minutes' => 12,
        'status' => 'submitted',
    ]);
    $result = QuizResult::query()->create([
        'quiz_attempt_id' => $attempt->id,
        'quiz_id' => $quiz->id,
        'total_questions' => 10,
        'correct_answers' => 8,
        'wrong_answers' => 2,
        'unanswered_answers' => 0,
        'score_percentage' => 80,
        'grade_letter' => 'A',
        'grade_label' => 'Sangat Baik',
        'result_status' => 'submitted',
        'calculated_at' => now(),
    ]);

    return [$attempt->load('division'), $result, $quiz];
}
