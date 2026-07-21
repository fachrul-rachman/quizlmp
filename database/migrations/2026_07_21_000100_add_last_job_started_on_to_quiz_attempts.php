<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quiz_attempts', function (Blueprint $table): void {
            $table->date('participant_last_job_started_on')
                ->nullable()
                ->after('participant_last_company');
        });
    }

    public function down(): void
    {
        // Intentionally preserved to avoid deleting participant data on rollback.
    }
};
