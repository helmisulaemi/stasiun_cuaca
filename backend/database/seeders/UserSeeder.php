<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'id' => Str::uuid()->toString(),
            'name' => 'Admin',
            'email' => 'admin@monitoring.test',
            'password_hash' => 'password',
        ]);

        User::create([
            'id' => Str::uuid()->toString(),
            'name' => 'Operator',
            'email' => 'operator@monitoring.test',
            'password_hash' => 'password',
        ]);
    }
}
