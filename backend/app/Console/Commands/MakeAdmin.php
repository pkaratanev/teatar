<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Makes an account an admin (access to /admin). Works in production, where the
 * seeder's local admin@teatar.test is never created.
 *
 *   php artisan teatar:make-admin you@example.com                 # new account, password generated and shown once
 *   php artisan teatar:make-admin you@example.com --password=...  # new account with this password
 *   php artisan teatar:make-admin existing@example.com            # promote an existing account, password unchanged
 */
class MakeAdmin extends Command
{
    protected $signature = 'teatar:make-admin
        {email : Email of the account to create or promote}
        {--name= : Display name for a new account (defaults to the part before @)}
        {--password= : Password to set; generated for a new account when left out}';

    protected $description = 'Create an admin account, or give an existing account admin access';

    public function handle(): int
    {
        $email = mb_strtolower(trim((string) $this->argument('email')));
        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $this->error("Not a valid email: {$email}");

            return self::FAILURE;
        }

        $password = $this->option('password');
        if ($password !== null && mb_strlen((string) $password) < 8) {
            $this->error('The password needs at least 8 characters.');

            return self::FAILURE;
        }

        $user = User::where('email', $email)->first();
        $generated = null;

        if ($user === null) {
            if ($password === null) {
                $password = $generated = Str::password(16, symbols: false);
            }
            $local = Str::before($email, '@');
            $user = User::create([
                'name' => $this->option('name') ?: $local,
                'username' => $this->freeUsername($local),
                'email' => $email,
                'password' => $password,
            ]);
            $this->info("Created {$email} (@{$user->username}).");
        } else {
            if ($password !== null) {
                $user->password = (string) $password;
                $this->info("Password changed for {$email}.");
            }
            $this->info("Found {$email} (@{$user->username}).");
        }

        $user->forceFill(['is_admin' => true])->save();
        $this->info('Admin access: yes. Sign in at '.url('/admin'));

        if ($generated !== null) {
            $this->newLine();
            $this->warn("Password (shown only now): {$generated}");
        }

        return self::SUCCESS;
    }

    /** Usernames allow a-z, 0-9, _ and . and are unique; add a number when taken. */
    private function freeUsername(string $from): string
    {
        $base = Str::of(Str::ascii($from))->lower()->replaceMatches('/[^a-z0-9_.]/', '')->limit(28, '')->toString();
        if (mb_strlen($base) < 3) {
            $base = 'admin';
        }

        $name = $base;
        for ($i = 2; User::where('username', $name)->exists(); $i++) {
            $name = $base.$i;
        }

        return $name;
    }
}
