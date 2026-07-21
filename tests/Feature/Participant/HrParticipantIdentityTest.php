<?php

use App\Livewire\Participant\QuizStart;
use App\Models\Division;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizLink;
use App\Models\User;
use Livewire\Livewire;

it('shows and requires additional identity fields only for HR links', function () {
    $link = createHrIdentityLink(Division::HR);

    Livewire::test(QuizStart::class, ['token' => $link->token])
        ->assertSet('isHrDivision', true)
        ->assertDontSee('Jabatan')
        ->assertSee('Usia')
        ->assertSee('Tinggi Badan (cm)')
        ->assertSee('Berat Badan (kg)')
        ->assertSee('Pekerjaan Terakhir')
        ->assertSee('Perusahaan Terakhir')
        ->assertSee('Sejak Kapan Bekerja')
        ->assertSee('Domisili Sekarang')
        ->set('participantName', 'Budi')
        ->call('saveIdentity')
        ->assertHasErrors([
            'participantAge' => 'required',
            'participantHeightCm' => 'required',
            'participantWeightKg' => 'required',
            'participantLastJob' => 'required',
            'participantLastCompany' => 'required',
            'participantLastJobStartedOn' => 'required',
            'participantCurrentDomicile' => 'required',
        ])
        ->assertHasNoErrors(['participantAppliedFor']);
});

it('stores validated HR identity data on the quiz attempt', function () {
    $link = createHrIdentityLink(Division::HR);

    Livewire::test(QuizStart::class, ['token' => $link->token])
        ->set('participantName', 'Siti')
        ->set('participantAge', '27')
        ->set('participantHeightCm', '163.5')
        ->set('participantWeightKg', '54.5')
        ->set('participantLastJob', 'Talent Acquisition')
        ->set('participantLastCompany', 'PT Contoh Indonesia')
        ->set('participantLastJobStartedOn', '2023-01')
        ->set('participantCurrentDomicile', 'Jakarta Selatan')
        ->call('saveIdentity')
        ->assertHasNoErrors();

    $attempt = QuizAttempt::query()->sole();

    expect($attempt->participant_age)->toBe(27)
        ->and($attempt->participant_applied_for)->toBe('')
        ->and($attempt->participant_height_cm)->toBe('163.50')
        ->and($attempt->participant_weight_kg)->toBe('54.50')
        ->and($attempt->participant_last_job)->toBe('Talent Acquisition')
        ->and($attempt->participant_last_company)->toBe('PT Contoh Indonesia')
        ->and($attempt->participant_last_job_started_on?->toDateString())->toBe('2023-01-01')
        ->and($attempt->participant_current_domicile)->toBe('Jakarta Selatan');
});

it('rejects an invalid HR employment start month', function () {
    $link = createHrIdentityLink(Division::HR);

    Livewire::test(QuizStart::class, ['token' => $link->token])
        ->set('participantName', 'Siti')
        ->set('participantAge', '27')
        ->set('participantHeightCm', '163.5')
        ->set('participantWeightKg', '54.5')
        ->set('participantLastJob', 'Talent Acquisition')
        ->set('participantLastCompany', 'PT Contoh Indonesia')
        ->set('participantLastJobStartedOn', '2023-13')
        ->set('participantCurrentDomicile', 'Jakarta Selatan')
        ->call('saveIdentity')
        ->assertHasErrors(['participantLastJobStartedOn' => 'date_format']);
});

it('does not show or require HR identity fields for Business Development links', function () {
    $link = createHrIdentityLink(Division::BUSINESS_DEVELOPMENT);

    Livewire::test(QuizStart::class, ['token' => $link->token])
        ->assertSet('isHrDivision', false)
        ->assertDontSee('Tinggi Badan (cm)')
        ->assertDontSee('Berat Badan (kg)')
        ->assertDontSee('Pekerjaan Terakhir')
        ->assertDontSee('Perusahaan Terakhir')
        ->assertDontSee('Sejak Kapan Bekerja')
        ->assertDontSee('Domisili Sekarang')
        ->assertSee('Jabatan/Peringkat')
        ->set('participantName', 'Andi')
        ->set('participantAppliedFor', 'Sales Manager')
        ->call('saveIdentity')
        ->assertHasNoErrors();

    $attempt = QuizAttempt::query()->sole();

    expect($attempt->participant_age)->toBeNull()
        ->and($attempt->participant_height_cm)->toBeNull()
        ->and($attempt->participant_weight_kg)->toBeNull()
        ->and($attempt->participant_last_job)->toBeNull()
        ->and($attempt->participant_last_company)->toBeNull()
        ->and($attempt->participant_last_job_started_on)->toBeNull()
        ->and($attempt->participant_current_domicile)->toBeNull();
});

function createHrIdentityLink(string $divisionCode): QuizLink
{
    $division = Division::query()->where('code', $divisionCode)->firstOrFail();
    $admin = User::factory()->create(['division_id' => $division->id]);
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

    return QuizLink::query()->create([
        'quiz_id' => $quiz->id,
        'division_id' => $division->id,
        'token' => 'identity-'.$divisionCode,
        'usage_type' => 'single',
        'status' => 'unused',
        'created_by' => $admin->id,
    ]);
}
