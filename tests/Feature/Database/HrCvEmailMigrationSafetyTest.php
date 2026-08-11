<?php

use Illuminate\Support\Facades\Schema;

it('preserves the HR CV email column when the migration down method is invoked', function () {
    expect(Schema::hasColumn('quiz_attempts', 'participant_cv_email'))->toBeTrue();

    $migration = require database_path('migrations/2026_08_11_000100_add_participant_cv_email_to_quiz_attempts.php');
    $migration->down();

    expect(Schema::hasColumn('quiz_attempts', 'participant_cv_email'))->toBeTrue();
});
