<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public $withinTransaction = true;

    /**
     * Production accounts that existed before division context was introduced.
     *
     * Both the immutable database ID and the expected account name must match
     * before an assignment is made. This prevents an unrelated account from
     * being reassigned if another environment uses different seed data.
     *
     * @var array<int, array{id: int, name: string, division: string}>
     */
    private const LEGACY_ADMIN_DIVISIONS = [
        ['id' => 2, 'name' => 'Admin HRD', 'division' => 'hr'],
        ['id' => 3, 'name' => 'Admin BD', 'division' => 'business_development'],
        ['id' => 4, 'name' => 'Admin HR AMG', 'division' => 'hr'],
    ];

    public function up(): void
    {
        $divisionIds = DB::table('divisions')
            ->whereIn('code', ['hr', 'business_development'])
            ->pluck('id', 'code');

        foreach (self::LEGACY_ADMIN_DIVISIONS as $assignment) {
            $divisionId = $divisionIds->get($assignment['division']);

            if (! is_numeric($divisionId)) {
                continue;
            }

            DB::table('users')
                ->where('id', $assignment['id'])
                ->where('name', $assignment['name'])
                ->where('role', 'admin')
                ->whereNull('division_id')
                ->update([
                    'division_id' => (int) $divisionId,
                    'updated_at' => now(),
                ]);
        }

        $adminDivisions = DB::table('users')
            ->where('role', 'admin')
            ->whereNotNull('division_id')
            ->get(['id', 'division_id']);

        foreach ($adminDivisions as $admin) {
            $quizIds = DB::table('quizzes')
                ->where('created_by', $admin->id)
                ->pluck('id');

            if ($quizIds->isEmpty()) {
                continue;
            }

            DB::table('quiz_links')
                ->whereIn('quiz_id', $quizIds)
                ->whereNull('division_id')
                ->update([
                    'division_id' => (int) $admin->division_id,
                    'updated_at' => now(),
                ]);
        }

        foreach ($divisionIds as $divisionId) {
            DB::table('quiz_attempts')
                ->whereNull('division_id')
                ->whereIn(
                    'quiz_link_id',
                    DB::table('quiz_links')
                        ->select('id')
                        ->where('division_id', (int) $divisionId),
                )
                ->update([
                    'division_id' => (int) $divisionId,
                    'updated_at' => now(),
                ]);
        }
    }

    public function down(): void
    {
        // Intentionally left blank: legacy division assignments are preserved.
    }
};
