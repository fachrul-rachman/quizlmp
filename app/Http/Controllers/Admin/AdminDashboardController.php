<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;

class AdminDashboardController extends Controller
{
    public function __invoke(): View
    {
        $stats = [
            'total_quizzes' => DB::table('quizzes')->count(),
            'total_links' => DB::table('quiz_links')->count(),
            'total_results' => DB::table('quiz_results')->count(),
            'total_admin_users' => User::count(),
        ];

        $latestResults = DB::table('quiz_results')
            ->join('quizzes', 'quizzes.id', '=', 'quiz_results.quiz_id')
            ->join('quiz_attempts', 'quiz_attempts.id', '=', 'quiz_results.quiz_attempt_id')
            ->select([
                'quiz_results.id',
                'quizzes.title as quiz_title',
                'quiz_attempts.participant_name',
                'quiz_attempts.participant_applied_for',
                'quiz_results.score_percentage',
                'quiz_results.grade_letter',
                'quiz_results.grade_label',
                'quiz_results.result_status',
                'quiz_results.calculated_at',
            ])
            ->orderByDesc('quiz_results.calculated_at')
            ->limit(10)
            ->get();

        return view('admin.dashboard', [
            'stats' => $stats,
            'latestResults' => $latestResults,
        ]);
    }
}
