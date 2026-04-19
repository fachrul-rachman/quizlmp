<?php

use App\Models\Quiz;
use App\Models\QuizCategory;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\delete;
use function Pest\Laravel\get;
use function Pest\Laravel\post;

it('allows admin to access quiz categories page', function () {
    $user = User::factory()->create(['role' => 'admin']);

    actingAs($user)
        ->get('/admin/quiz-categories')
        ->assertOk()
        ->assertSee('Kategori Quiz');
});

it('allows admin to create a quiz category', function () {
    $user = User::factory()->create(['role' => 'admin']);

    actingAs($user)
        ->post('/admin/quiz-categories', [
            'name' => 'HRD',
        ])
        ->assertRedirect('/admin/quiz-categories')
        ->assertSessionHas('success', 'Kategori quiz berhasil dibuat.');

    expect(QuizCategory::query()->where('name', 'HRD')->exists())->toBeTrue();
});

it('prevents deleting a category that is still used by a quiz', function () {
    $user = User::factory()->create(['role' => 'admin']);
    $category = QuizCategory::query()->create([
        'name' => 'HRD',
    ]);

    Quiz::query()->create([
        'title' => 'WPT',
        'description' => null,
        'category_id' => $category->id,
        'duration_minutes' => 60,
        'shuffle_questions' => false,
        'shuffle_options' => false,
        'is_active' => true,
        'created_by' => $user->id,
        'updated_by' => $user->id,
    ]);

    actingAs($user)
        ->from('/admin/quiz-categories')
        ->delete('/admin/quiz-categories/'.$category->id)
        ->assertRedirect('/admin/quiz-categories')
        ->assertSessionHas('error', 'Kategori tidak bisa dihapus karena masih dipakai oleh quiz.');

    expect($category->fresh())->not->toBeNull();
});

it('allows deleting an empty category', function () {
    $user = User::factory()->create(['role' => 'admin']);
    $category = QuizCategory::query()->create([
        'name' => 'Finance',
    ]);

    actingAs($user)
        ->delete('/admin/quiz-categories/'.$category->id)
        ->assertRedirect('/admin/quiz-categories')
        ->assertSessionHas('success', 'Kategori quiz berhasil dihapus.');

    expect(QuizCategory::query()->whereKey($category->id)->exists())->toBeFalse();
});
