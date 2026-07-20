<?php

use App\Models\Division;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizLink;
use App\Models\User;

it('backfills legacy production divisions without deleting or reassigning existing data', function () {
    $hr = Division::query()->where('code', Division::HR)->firstOrFail();
    $business = Division::query()->where('code', Division::BUSINESS_DEVELOPMENT)->firstOrFail();

    $superAdmin = User::factory()->create([
        'id' => 1,
        'name' => 'Super Admin',
        'role' => 'super_admin',
        'division_id' => null,
    ]);
    $hrAdmin = User::factory()->create([
        'id' => 2,
        'name' => 'Admin HRD',
        'division_id' => null,
    ]);
    $businessAdmin = User::factory()->create([
        'id' => 3,
        'name' => 'Admin BD',
        'division_id' => null,
    ]);
    $otherHrAdmin = User::factory()->create([
        'id' => 4,
        'name' => 'Admin HR AMG',
        'division_id' => null,
    ]);

    $superQuiz = createLegacyBackfillQuiz($superAdmin, 'Superadmin Quiz');
    $hrQuiz = createLegacyBackfillQuiz($hrAdmin, 'HR Quiz');
    $businessQuiz = createLegacyBackfillQuiz($businessAdmin, 'BD Quiz');
    $otherHrQuiz = createLegacyBackfillQuiz($otherHrAdmin, 'Other HR Quiz');

    $superLink = createLegacyBackfillLink($superQuiz, $superAdmin, 'super-link');
    $hrLink = createLegacyBackfillLink($hrQuiz, $hrAdmin, 'hr-link');
    $businessLink = createLegacyBackfillLink($businessQuiz, $businessAdmin, 'bd-link');
    $otherHrLink = createLegacyBackfillLink($otherHrQuiz, $otherHrAdmin, 'other-hr-link');
    $superCreatedHrLink = createLegacyBackfillLink($hrQuiz, $superAdmin, 'super-created-hr-link');

    $attempts = collect([
        $superLink,
        $hrLink,
        $businessLink,
        $otherHrLink,
        $superCreatedHrLink,
    ])->map(fn (QuizLink $link) => createLegacyBackfillAttempt($link));

    $countsBefore = legacyBackfillCounts();

    $migration = require database_path('migrations/2026_07_20_000300_backfill_legacy_division_context.php');
    $migration->up();

    expect($superAdmin->fresh()->division_id)->toBeNull()
        ->and($hrAdmin->fresh()->division_id)->toBe($hr->id)
        ->and($businessAdmin->fresh()->division_id)->toBe($business->id)
        ->and($otherHrAdmin->fresh()->division_id)->toBe($hr->id)
        ->and($superLink->fresh()->division_id)->toBeNull()
        ->and($hrLink->fresh()->division_id)->toBe($hr->id)
        ->and($businessLink->fresh()->division_id)->toBe($business->id)
        ->and($otherHrLink->fresh()->division_id)->toBe($hr->id)
        ->and($superCreatedHrLink->fresh()->division_id)->toBe($hr->id)
        ->and($attempts[0]->fresh()->division_id)->toBeNull()
        ->and($attempts[1]->fresh()->division_id)->toBe($hr->id)
        ->and($attempts[2]->fresh()->division_id)->toBe($business->id)
        ->and($attempts[3]->fresh()->division_id)->toBe($hr->id)
        ->and($attempts[4]->fresh()->division_id)->toBe($hr->id)
        ->and(legacyBackfillCounts())->toBe($countsBefore);
});

function createLegacyBackfillQuiz(User $creator, string $title): Quiz
{
    return Quiz::query()->create([
        'title' => $title,
        'duration_minutes' => 30,
        'shuffle_questions' => false,
        'shuffle_options' => false,
        'instant_feedback_enabled' => false,
        'difficulty_levels_enabled' => false,
        'is_active' => true,
        'created_by' => $creator->id,
        'updated_by' => $creator->id,
    ]);
}

function createLegacyBackfillLink(Quiz $quiz, User $creator, string $token): QuizLink
{
    return QuizLink::query()->create([
        'quiz_id' => $quiz->id,
        'division_id' => null,
        'token' => $token,
        'usage_type' => 'single',
        'status' => 'opened',
        'created_by' => $creator->id,
    ]);
}

function createLegacyBackfillAttempt(QuizLink $link): QuizAttempt
{
    return QuizAttempt::query()->create([
        'quiz_link_id' => $link->id,
        'quiz_id' => $link->quiz_id,
        'division_id' => null,
        'participant_name' => 'Legacy Participant',
        'participant_applied_for' => 'Legacy Position',
        'time_limit_minutes' => 30,
        'status' => 'not_started',
    ]);
}

/**
 * @return array<string, int>
 */
function legacyBackfillCounts(): array
{
    return [
        'users' => User::query()->count(),
        'quizzes' => Quiz::query()->count(),
        'links' => QuizLink::query()->count(),
        'attempts' => QuizAttempt::query()->count(),
    ];
}
