<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('configuracoes_loja', function (Blueprint $table) {
            $table->decimal('loja_lat', 10, 8)->nullable();
            $table->decimal('loja_lng', 11, 8)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('configuracoes_loja', function (Blueprint $table) {
            $table->dropColumn(['loja_lat', 'loja_lng']);
        });
    }
};