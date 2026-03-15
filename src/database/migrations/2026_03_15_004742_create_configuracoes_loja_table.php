<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('configuracoes_loja', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loja_id')->constrained('lojas')->onDelete('cascade');
            $table->unsignedInteger('pedidos_por_rota')->default(1);
            $table->boolean('modo_emergencia')->default(false);
            $table->unsignedInteger('gatilho_emergencia')->default(5);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('configuracoes_loja');
    }
};