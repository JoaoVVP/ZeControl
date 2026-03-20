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
        $this->call([
            LojaSeeder::class,
        ]);
    }

    public function run(): void
    {
        $this->call([
            LojaSeeder::class,
            PedidoMockSeeder::class,
        ]);
    }
}
