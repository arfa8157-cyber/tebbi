<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $name = config('services.tebbi.admin_name');
        $email = config('services.tebbi.admin_email');
        $password = config('services.tebbi.admin_password');

        if (! $name && ! $email && ! $password) {
            return;
        }

        if (! $name || ! $email || ! $password || strlen($password) < 16) {
            throw new \RuntimeException('Set TEBBI_ADMIN_NAME, TEBBI_ADMIN_EMAIL, and a 16-character TEBBI_ADMIN_PASSWORD before seeding the admin.');
        }

        if (User::where('role', 'admin')->exists()) {
            return;
        }

        User::create([
            'name' => $name,
            'email' => $email,
            'email_verified_at' => now(),
            'password' => $password,
            'role' => 'admin',
            'country' => 'مصر',
        ]);
    }
}
