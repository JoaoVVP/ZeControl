<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Loja: nome único
        Schema::table('lojas', function (Blueprint $table) {
            $table->unique('nome');
        });

        // Usuarios: nome único por loja
        Schema::table('usuarios', function (Blueprint $table) {
            $table->unique(['loja_id', 'nome']);
        });

        // Motoboys: nome único por loja
        Schema::table('motoboys', function (Blueprint $table) {
            $table->unique(['loja_id', 'nome']);
        });
    }

    public function down(): void
    {
        Schema::table('lojas', function (Blueprint $table) {
            $table->dropUnique(['nome']);
        });

        Schema::table('usuarios', function (Blueprint $table) {
            $table->dropUnique(['loja_id', 'nome']);
        });

        Schema::table('motoboys', function (Blueprint $table) {
            $table->dropUnique(['loja_id', 'nome']);
        });
    }
};