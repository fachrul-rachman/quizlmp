<?php

use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminQuizController;
use App\Http\Controllers\Admin\AdminQuizTemplateController;
use App\Http\Controllers\Admin\AdminGenerateLinkController;
use App\Http\Controllers\Admin\AdminQuizLinkController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->name('admin.')->group(function () {
    Route::redirect('/', '/admin/dashboard')->name('home');

    Route::view('/login', 'admin.auth.login')->middleware('guest')->name('login');
    Route::post('/login', [AdminAuthController::class, 'store'])->middleware('guest')->name('login.store');

    Route::middleware('auth')->group(function () {
        Route::get('/dashboard', AdminDashboardController::class)->name('dashboard');

        Route::get('/quizzes', [AdminQuizController::class, 'index'])->name('quizzes.index');
        Route::get('/quizzes/template', AdminQuizTemplateController::class)->name('quizzes.template');
        Route::get('/quizzes/create', [AdminQuizController::class, 'create'])->name('quizzes.create');
        Route::get('/quizzes/{quiz}', [AdminQuizController::class, 'show'])->name('quizzes.show');
        Route::get('/quizzes/{quiz}/edit', [AdminQuizController::class, 'edit'])->name('quizzes.edit');
        Route::delete('/quizzes/{quiz}', [AdminQuizController::class, 'destroy'])->name('quizzes.destroy');

        Route::get('/generate-link', [AdminGenerateLinkController::class, 'index'])->name('links.generate');
        Route::post('/generate-link', [AdminGenerateLinkController::class, 'store'])->name('links.generate.store');

        Route::get('/links', [AdminQuizLinkController::class, 'index'])->name('links.index');
        Route::get('/links/{quizLink}', [AdminQuizLinkController::class, 'show'])->name('links.show');

        Route::view('/results', 'admin.placeholder')->name('results.index');
        Route::view('/results/{id}', 'admin.placeholder')->name('results.show');

        Route::view('/users', 'admin.placeholder')->middleware('super_admin')->name('users.index');
    });
});

Route::view('/quiz/{token}', 'participant.start')->name('participant.quiz.start');
Route::view('/quiz/{token}/work', 'participant.work')->name('participant.quiz.work');
Route::view('/quiz/{token}/done', 'participant.done')->name('participant.quiz.done');
