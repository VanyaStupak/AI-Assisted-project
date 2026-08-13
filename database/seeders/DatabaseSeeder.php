<?php

namespace Database\Seeders;

use App\Models\PromoCode;
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
        User::factory()->create([
            'name' => 'Demo Player',
            'email' => 'player@example.com',
            'password' => 'password',
        ]);

        PromoCode::factory()->create([
            'code' => 'WELCOME10',
            'bonus_amount' => 10,
            'expires_at' => null,
        ]);

        PromoCode::factory()->create([
            'code' => 'BONUS50',
            'bonus_amount' => 50,
            'expires_at' => now()->addDays(30),
        ]);

        PromoCode::factory()->expired()->create([
            'code' => 'OLDCODE1',
            'bonus_amount' => 25,
        ]);
    }
}
