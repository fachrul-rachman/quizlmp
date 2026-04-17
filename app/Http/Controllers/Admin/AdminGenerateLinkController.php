<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Quiz;
use App\Models\QuizLink;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AdminGenerateLinkController extends Controller
{
    public function index(Request $request): View
    {
        $activeQuizzes = Quiz::query()
            ->where('is_active', true)
            ->orderBy('title')
            ->get(['id', 'title']);

        $generatedIds = $request->session()->get('generated_link_ids', []);
        $generatedLinks = collect();
        if (is_array($generatedIds) && $generatedIds !== []) {
            $generatedLinks = QuizLink::query()
                ->with('quiz:id,title')
                ->whereIn('id', $generatedIds)
                ->orderBy('id')
                ->get();
        }

        return view('admin.links.generate', [
            'activeQuizzes' => $activeQuizzes,
            'generatedLinks' => $generatedLinks,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'quiz_id' => ['required', 'integer', 'exists:quizzes,id'],
            'count' => ['required', 'integer', 'min:1'],
        ], [
            'quiz_id.required' => 'Pilih Quiz wajib diisi.',
            'count.required' => 'Jumlah Link wajib diisi.',
            'count.min' => 'Jumlah Link harus angka positif.',
        ]);

        $quiz = Quiz::query()->findOrFail((int) $data['quiz_id']);
        if (! $quiz->is_active) {
            return back()
                ->withInput()
                ->withErrors(['quiz_id' => 'Quiz tidak aktif.']);
        }

        $count = (int) $data['count'];

        $generatedIds = [];

        DB::transaction(function () use ($quiz, $count, &$generatedIds): void {
            $userId = (int) auth()->id();

            for ($i = 0; $i < $count; $i++) {
                $generatedIds[] = $this->createUniqueLink($quiz->id, $userId)->id;
            }
        });

        $request->session()->flash('success', 'Link quiz berhasil dibuat.');
        $request->session()->put('generated_link_ids', $generatedIds);

        return redirect()->to('/admin/generate-link');
    }

    private function createUniqueLink(int $quizId, int $userId): QuizLink
    {
        for ($attempt = 0; $attempt < 10; $attempt++) {
            try {
                return QuizLink::create([
                    'quiz_id' => $quizId,
                    'token' => Str::random(40),
                    'status' => 'unused',
                    'opened_at' => null,
                    'started_at' => null,
                    'submitted_at' => null,
                    'expired_at' => null,
                    'created_by' => $userId,
                ]);
            } catch (QueryException $e) {
                $sqlState = $e->errorInfo[0] ?? null;
                $isUniqueViolation = $sqlState === '23505';
                if (! $isUniqueViolation) {
                    throw $e;
                }
            }
        }

        throw new \RuntimeException('Gagal membuat token unik.');
    }
}

