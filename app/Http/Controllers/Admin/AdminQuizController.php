<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Quiz;
use App\Models\QuizCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AdminQuizController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $status = (string) $request->query('status', 'all');
        $categoryId = (string) $request->query('category_id', 'all');

        $query = Quiz::query()
            ->with(['creator:id,name', 'category:id,name'])
            ->addSelect([
                'active_questions_count' => DB::table('questions')
                    ->selectRaw('count(*)')
                    ->whereColumn('questions.quiz_id', 'quizzes.id')
                    ->whereNull('questions.deleted_at')
                    ->where('questions.is_active', true),
            ]);

        if ($search !== '') {
            $needle = mb_strtolower($search);
            $query->whereRaw('LOWER(title) LIKE ?', ['%'.$needle.'%']);
        }

        if ($status === 'active') {
            $query->where('is_active', true);
        } elseif ($status === 'inactive') {
            $query->where('is_active', false);
        }

        if ($categoryId === 'default') {
            $query->whereNull('category_id');
        } elseif ($categoryId !== 'all' && ctype_digit($categoryId)) {
            $query->where('category_id', (int) $categoryId);
        }

        $quizzes = $query
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        $categories = QuizCategory::query()
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('admin.quizzes.index', [
            'quizzes' => $quizzes,
            'search' => $search,
            'status' => $status,
            'categoryId' => $categoryId,
            'categories' => $categories,
        ]);
    }

    public function create(): View
    {
        return view('admin.quizzes.create');
    }

    public function show(Quiz $quiz): View
    {
        $quiz->load([
            'category:id,name',
            'creator:id,name',
            'updater:id,name',
            'questions' => fn ($q) => $q->orderBy('order_number'),
            'questions.options' => fn ($q) => $q->orderBy('sort_order'),
            'questions.shortAnswerKeys' => fn ($q) => $q->orderBy('sort_order'),
        ]);

        return view('admin.quizzes.show', [
            'quiz' => $quiz,
        ]);
    }

    public function edit(Quiz $quiz): View
    {
        return view('admin.quizzes.edit', [
            'quiz' => $quiz,
        ]);
    }

    public function destroy(Quiz $quiz): RedirectResponse
    {
        $quiz->delete();

        return redirect()
            ->to('/admin/quizzes')
            ->with('success', 'Quiz berhasil dihapus.');
    }
}
