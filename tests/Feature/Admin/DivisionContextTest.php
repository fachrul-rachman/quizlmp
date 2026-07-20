<?php

use App\Livewire\Participant\QuizStart;
use App\Models\Division;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizLink;
use App\Models\User;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\post;

it('keeps quiz visibility private to its creator while super admin can see every quiz', function () {
    $hr = Division::query()->where('code', Division::HR)->firstOrFail();
    $business = Division::query()->where('code', Division::BUSINESS_DEVELOPMENT)->firstOrFail();

    $hrAdmin = User::factory()->create(['division_id' => $hr->id]);
    $otherHrAdmin = User::factory()->create(['division_id' => $hr->id]);
    $businessAdmin = User::factory()->create(['division_id' => $business->id]);
    $superAdmin = User::factory()->create(['role' => 'super_admin', 'division_id' => null]);

    $hrQuiz = createDivisionContextQuiz($hrAdmin, 'Quiz HR');
    $superAdminQuiz = createDivisionContextQuiz($superAdmin, 'Quiz Superadmin');

    actingAs($hrAdmin);
    get('/admin/quizzes/'.$hrQuiz->id)->assertOk();

    actingAs($otherHrAdmin);
    get('/admin/quizzes/'.$hrQuiz->id)->assertNotFound();

    actingAs($businessAdmin);
    get('/admin/quizzes/'.$hrQuiz->id)->assertNotFound();

    actingAs($superAdmin);
    get('/admin/quizzes/'.$hrQuiz->id)->assertOk();

    actingAs($hrAdmin);
    get('/admin/quizzes/'.$superAdminQuiz->id)->assertNotFound();
});

it('uses the authenticated admin division when generating links and ignores spoofed division input', function () {
    $hr = Division::query()->where('code', Division::HR)->firstOrFail();
    $business = Division::query()->where('code', Division::BUSINESS_DEVELOPMENT)->firstOrFail();
    $admin = User::factory()->create(['division_id' => $hr->id]);
    $quiz = createDivisionContextQuiz($admin);

    actingAs($admin);
    post('/admin/generate-link', [
        'quiz_id' => $quiz->id,
        'count' => 1,
        'usage_type' => 'single',
        'division_id' => $business->id,
    ])->assertRedirect('/admin/generate-link');

    $link = QuizLink::query()->sole();

    expect($link->division_id)->toBe($hr->id);
});

it('requires a super admin to choose the division when generating a link', function () {
    $business = Division::query()->where('code', Division::BUSINESS_DEVELOPMENT)->firstOrFail();
    $superAdmin = User::factory()->create(['role' => 'super_admin', 'division_id' => null]);
    $quiz = createDivisionContextQuiz($superAdmin);

    actingAs($superAdmin);
    post('/admin/generate-link', [
        'quiz_id' => $quiz->id,
        'count' => 1,
        'usage_type' => 'single',
    ])->assertSessionHasErrors('division_id');

    post('/admin/generate-link', [
        'quiz_id' => $quiz->id,
        'count' => 1,
        'usage_type' => 'single',
        'division_id' => $business->id,
    ])->assertRedirect('/admin/generate-link');

    expect(QuizLink::query()->sole()->division_id)->toBe($business->id);
});

it('requires divisions for regular admins and keeps super admin divisionless', function () {
    $hr = Division::query()->where('code', Division::HR)->firstOrFail();
    $superAdmin = User::factory()->create(['role' => 'super_admin', 'division_id' => null]);

    actingAs($superAdmin);
    post('/admin/users', [
        'name' => 'Admin Tanpa Divisi',
        'email' => 'missing-division@example.com',
        'password' => 'password123',
        'role' => 'admin',
        'is_active' => '1',
    ])->assertSessionHasErrors('division_id');

    post('/admin/users', [
        'name' => 'Admin HR',
        'email' => 'admin-hr@example.com',
        'password' => 'password123',
        'role' => 'admin',
        'division_id' => $hr->id,
        'is_active' => '1',
    ])->assertRedirectToRoute('admin.users.index');

    post('/admin/users', [
        'name' => 'Super Admin Baru',
        'email' => 'superadmin-baru@example.com',
        'password' => 'password123',
        'role' => 'super_admin',
        'division_id' => $hr->id,
        'is_active' => '1',
    ])->assertRedirectToRoute('admin.users.index');

    expect(User::query()->where('email', 'admin-hr@example.com')->sole()->division_id)->toBe($hr->id)
        ->and(User::query()->where('email', 'superadmin-baru@example.com')->sole()->division_id)->toBeNull();
});

it('shows the link division to participants and snapshots it on the attempt', function () {
    $business = Division::query()->where('code', Division::BUSINESS_DEVELOPMENT)->firstOrFail();
    $admin = User::factory()->create(['division_id' => $business->id]);
    $quiz = createDivisionContextQuiz($admin);
    $link = QuizLink::query()->create([
        'quiz_id' => $quiz->id,
        'division_id' => $business->id,
        'token' => 'business-division-link',
        'usage_type' => 'single',
        'status' => 'unused',
        'created_by' => $admin->id,
    ]);

    Livewire::test(QuizStart::class, ['token' => $link->token])
        ->assertSet('divisionName', 'Business Development')
        ->assertSee('Business Development')
        ->set('participantName', 'Budi')
        ->set('participantAppliedFor', 'Sales Manager')
        ->call('startTest')
        ->assertRedirect('/quiz/'.$link->token.'/work');

    $attempt = QuizAttempt::query()->sole();

    expect($attempt->division_id)->toBe($business->id);
});

function createDivisionContextQuiz(User $creator, string $title = 'Quiz Division Context'): Quiz
{
    return Quiz::query()->create([
        'title' => $title,
        'description' => null,
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
