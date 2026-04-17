<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Quiz;
use App\Models\QuizLink;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminQuizLinkController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $quizId = (string) $request->query('quiz_id', '');
        $status = (string) $request->query('status', 'all');

        $query = QuizLink::query()
            ->with('quiz:id,title')
            ->orderByDesc('id');

        if ($search !== '') {
            $needle = mb_strtolower($search);
            $query->whereRaw('LOWER(token) LIKE ?', ['%'.$needle.'%']);
        }

        if ($quizId !== '') {
            $query->where('quiz_id', (int) $quizId);
        }

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $links = $query->paginate(20)->withQueryString();

        $quizzes = Quiz::query()
            ->orderBy('title')
            ->get(['id', 'title']);

        return view('admin.links.index', [
            'links' => $links,
            'quizzes' => $quizzes,
            'search' => $search,
            'quizId' => $quizId,
            'status' => $status,
        ]);
    }

    public function show(QuizLink $quizLink): View
    {
        $quizLink->load(['quiz:id,title', 'creator:id,name', 'attempt']);

        return view('admin.links.show', [
            'link' => $quizLink,
        ]);
    }
}

