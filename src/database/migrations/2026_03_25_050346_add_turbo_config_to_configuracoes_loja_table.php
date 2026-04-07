<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('configuracoes_loja', function (Blueprint $table) {
            $table->boolean('turbo_casa')->default(false);
            $table->unsignedInteger('turbo_prazo_minutos')->default(5);
            $table->unsignedInteger('turbo_espera_casa_minutos')->default(3);
            $table->boolean('turbo_preferencia')->default(false);
            $table->boolean('turbo_casa_modo_emergencia')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('configuracoes_loja', function (Blueprint $table) {
            $table->dropColumn([
                'turbo_casa',
                'turbo_prazo_minutos',
                'turbo_espera_casa_minutos',
                'turbo_preferencia',
                'turbo_casa_modo_emergencia',
            ]);
        });
    }
};