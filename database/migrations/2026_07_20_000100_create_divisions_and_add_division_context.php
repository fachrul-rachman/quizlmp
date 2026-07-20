<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('divisions', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 64)->unique();
            $table->string('name', 255);
            $table->timestamps();
        });

        $now = now();

        DB::table('divisions')->insert([
            [
                'code' => 'hr',
                'name' => 'Human Resources',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 'business_development',
                'name' => 'Business Development',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        Schema::table('users', function (Blueprint $table): void {
            $table->foreignId('division_id')
                ->nullable()
                ->after('role')
                ->constrained('divisions');
        });

        Schema::table('quiz_links', function (Blueprint $table): void {
            $table->foreignId('division_id')
                ->nullable()
                ->after('quiz_id')
                ->constrained('divisions');
        });

        Schema::table('quiz_attempts', function (Blueprint $table): void {
            $table->foreignId('division_id')
                ->nullable()
                ->after('quiz_id')
                ->constrained('divisions');
        });
    }

    public function down(): void
    {
        Schema::table('quiz_attempts', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('division_id');
        });

        Schema::table('quiz_links', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('division_id');
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('division_id');
        });

        Schema::dropIfExists('divisions');
    }
};
