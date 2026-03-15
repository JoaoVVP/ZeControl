<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Credenciais Ze na tabela lojas
        Schema::table('lojas', function (Blueprint $table) {
            $table->text('ze_merchant_id')->nullable();
            $table->text('ze_client_id')->nullable();
            $table->text('ze_client_secret')->nullable();
        });

        // Start Route na tabela configuracoes_loja
        Schema::table('configuracoes_loja', function (Blueprint $table) {
            $table->boolean('auto_start_route')->default(false);
            $table->unsignedInteger('start_route_minutos')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('lojas', function (Blueprint $table) {
            $table->dropColumn(['ze_merchant_id', 'ze_client_id', 'ze_client_secret']);
        });

        Schema::table('configuracoes_loja', function (Blueprint $table) {
            $table->dropColumn(['auto_start_route', 'start_route_minutos']);
        });
    }
};