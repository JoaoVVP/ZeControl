<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\Usuario;

class LojaSeeder extends Seeder
{
    public function run(): void
    {
        Usuario::create([
            'loja_id'  => null,
            'nome'     => 'Admin Sistema',
            'email'    => 'admin@zecontrol.com',
            'password' => Hash::make('admin123'),
            'perfil'   => 'admin',
            'ativo'    => true,
        ]);
    }
}