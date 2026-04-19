<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\QuizCategory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminQuizCategoryController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));

        $categories = QuizCategory::query()
            ->withCount('quizzes')
            ->when($search !== '', function ($query) use ($search) {
                $needle = mb_strtolower($search);

                $query->whereRaw('LOWER(name) LIKE ?', ['%'.$needle.'%']);
            })
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('admin.quiz-categories.index', [
            'categories' => $categories,
            'search' => $search,
        ]);
    }

    public function create(): View
    {
        return view('admin.quiz-categories.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique(QuizCategory::class, 'name')],
        ], [], [
            'name' => 'Nama kategori',
        ]);

        QuizCategory::create([
            'name' => $data['name'],
        ]);

        return redirect()
            ->route('admin.quiz-categories.index')
            ->with('success', 'Kategori quiz berhasil dibuat.');
    }

    public function edit(QuizCategory $quizCategory): View
    {
        return view('admin.quiz-categories.edit', [
            'quizCategory' => $quizCategory,
        ]);
    }

    public function update(Request $request, QuizCategory $quizCategory): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique(QuizCategory::class, 'name')->ignore($quizCategory->id)],
        ], [], [
            'name' => 'Nama kategori',
        ]);

        $quizCategory->update([
            'name' => $data['name'],
        ]);

        return redirect()
            ->route('admin.quiz-categories.index')
            ->with('success', 'Kategori quiz berhasil diperbarui.');
    }

    public function destroy(QuizCategory $quizCategory): RedirectResponse
    {
        if ($quizCategory->quizzes()->count() > 0) {
            return back()->with('error', 'Kategori tidak bisa dihapus karena masih dipakai oleh quiz.');
        }

        $quizCategory->delete();

        return redirect()
            ->route('admin.quiz-categories.index')
            ->with('success', 'Kategori quiz berhasil dihapus.');
    }
}
