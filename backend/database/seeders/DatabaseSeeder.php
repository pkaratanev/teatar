<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Base data only: the launch theatres, plus a local admin account. For a
 * full test setup (real programme + demo people) run `php artisan teatar:demo`.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(LaunchTheatresSeeder::class);

        if (! app()->isProduction()) {
            User::firstOrCreate(['email' => 'admin@teatar.test'], [
                'name' => 'Admin',
                'username' => 'admin',
                'password' => 'password',
            ])->forceFill(['is_admin' => true])->save();
        }
    }
}
