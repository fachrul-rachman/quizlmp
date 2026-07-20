<?php

namespace Database\Seeders;

use App\Models\Division;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        Division::query()->updateOrCreate(
            ['code' => Division::HR],
            ['name' => 'Human Resources'],
        );
        Division::query()->updateOrCreate(
            ['code' => Division::BUSINESS_DEVELOPMENT],
            ['name' => 'Business Development'],
        );

        $users = [
            [
                'email' => 'superadmin@lestari.com',
                'name' => 'Super Admin',
                'role' => 'super_admin',
            ],
        ];

        foreach ($users as $u) {
            $user = User::withTrashed()->updateOrCreate(
                ['email' => $u['email']],
                [
                    'name' => $u['name'],
                    'password' => Hash::make('password'),
                    'role' => $u['role'],
                    'division_id' => null,
                    'is_active' => true,
                ],
            );

            if ($user->trashed()) {
                $user->restore();
            }
        }
    }
}
