<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Quiz;
use App\Services\Export\QuizExportXlsxService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AdminQuizController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $isSuperAdmin = (($user?->role ?? null) === 'super_admin');

        $search = trim((string) $request->query('search', ''));
        $status = (string) $request->query('status', 'all');

        $query = Quiz::query()
            ->with(['creator:id,name'])
            ->addSelect([
                'active_questions_count' => DB::table('questions')
                    ->selectRaw('count(*)')
                    ->whereColumn('questions.quiz_id', 'quizzes.id')
                    ->whereNull('questions.deleted_at')
                    ->where('questions.is_active', true),
            ]);

        if (! $isSuperAdmin && $user) {
            $query->where('created_by', (int) $user->id);
        }

        if ($search !== '') {
            $needle = mb_strtolower($search);
            $query->whereRaw('LOWER(title) LIKE ?', ['%'.$needle.'%']);
        }

        if ($status === 'active') {
            $query->where('is_active', true);
        } elseif ($status === 'inactive') {
            $query->where('is_active', false);
        }

        $quizzes = $query
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        return view('admin.quizzes.index', [
            'quizzes' => $quizzes,
            'search' => $search,
            'status' => $status,
        ]);
    }

    public function create(): View
    {
        return view('admin.quizzes.create');
    }

    public function export(Request $request, QuizExportXlsxService $exportService): BinaryFileResponse
    {
        $data = $request->validate([
            'quiz_ids' => ['required', 'array', 'min:1', 'max:50'],
            'quiz_ids.*' => ['required', 'integer', 'distinct'],
        ], [
            'quiz_ids.required' => 'Pilih minimal satu quiz untuk diekspor.',
            'quiz_ids.min' => 'Pilih minimal satu quiz untuk diekspor.',
        ]);

        $user = $request->user();
        $quizIds = array_map('intval', array_values($data['quiz_ids']));
        $quizzesById = Quiz::query()
            ->when(
                ($user?->role ?? null) !== 'super_admin',
                fn ($query) => $query->where('created_by', (int) ($user?->id ?? 0)),
            )
            ->whereIntegerInRaw('id', $quizIds)
            ->with([
                'questions' => fn ($query) => $query->orderBy('order_number'),
                'questions.options' => fn ($query) => $query->orderBy('sort_order'),
                'questions.shortAnswerKeys' => fn ($query) => $query->orderBy('sort_order'),
            ])
            ->get()
            ->keyBy('id');

        if ($quizzesById->count() !== count($quizIds)) {
            abort(404);
        }

        $quizzes = collect($quizIds)->map(fn (int $quizId) => $quizzesById->get($quizId));
        $export = $exportService->exportToTempFile($quizzes);

        return response()
            ->download($export['path'], $export['download_name'])
            ->deleteFileAfterSend(true);
    }

    public function show(Quiz $quiz): View
    {
        $user = request()->user();
        $isSuperAdmin = (($user?->role ?? null) === 'super_admin');
        if (! $isSuperAdmin && (int) $quiz->created_by !== (int) ($user?->id ?? 0)) {
            abort(404);
        }

        $quiz->load([
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
        $user = request()->user();
        $isSuperAdmin = (($user?->role ?? null) === 'super_admin');
        if (! $isSuperAdmin && (int) $quiz->created_by !== (int) ($user?->id ?? 0)) {
            abort(404);
        }

        return view('admin.quizzes.edit', [
            'quiz' => $quiz,
        ]);
    }

    public function destroy(Quiz $quiz): RedirectResponse
    {
        $user = request()->user();
        $isSuperAdmin = (($user?->role ?? null) === 'super_admin');
        if (! $isSuperAdmin && (int) $quiz->created_by !== (int) ($user?->id ?? 0)) {
            abort(404);
        }

        $quiz->delete();

        return redirect()
            ->to('/admin/quizzes')
            ->with('success', 'Quiz berhasil dihapus.');
    }
}
