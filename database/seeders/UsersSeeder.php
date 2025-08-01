<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::create([
            'name' => 'Jose Andres  Hernandez',
            'email' => 'andresjosehr@gmail.com',
            'password' => Hash::make('Paralelepipe2'),
        ]);
    }
}
