<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AdminUserSeeder extends Seeder
{
    /**
     * Seeds the initial administrator.
     *
     * Credentials come from the environment, never from source control. The
     * previous hardcoded password was committed to the repository and was still
     * live on the running system.
     *
     * Set these in .env before seeding:
     *   ADMIN_EMAIL=you@example.com
     *   ADMIN_PASSWORD=<a real password>
     *
     * With no ADMIN_PASSWORD set a random one is generated and printed once, so
     * a seeded install is never reachable with a known default.
     */
    public function run()
    {
        $email = env('ADMIN_EMAIL', 'admin@eldogas.local');
        $password = env('ADMIN_PASSWORD');
        $generated = false;

        if (empty($password)) {
            $password = Str::random(16);
            $generated = true;
        }

        $user = User::updateOrCreate(
            ['email' => $email],
            [
                'name' => env('ADMIN_NAME', 'Administrator'),
                'password' => Hash::make($password),
                'role' => 'admin',
                'status' => 'active',
                // Every admin and POS route sits behind the `verified`
                // middleware. A seeded admin left unverified can log in and is
                // then trapped on the verification notice, which it can never
                // clear unless outbound mail happens to be configured.
                'email_verified_at' => now(),
            ]
        );

        $this->command?->info("Admin user ready: {$user->email}");

        if ($generated) {
            $this->command?->warn('No ADMIN_PASSWORD was set, so one was generated.');
            $this->command?->warn("Password: {$password}");
            $this->command?->warn('Store it now - it will not be shown again.');
        }
    }
}
