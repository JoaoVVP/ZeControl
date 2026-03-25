<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConfiguracaoLoja extends Model
{
    protected $table = 'configuracoes_loja';

    protected $fillable = [
        'loja_id',
        'pedidos_por_rota',
        'modo_emergencia',
        'gatilho_emergencia',
        'auto_start_route',
        'start_route_minutos',
        'turbo_casa',
        'turbo_prazo_minutos',
        'turbo_espera_casa_minutos',
        'turbo_preferencia',
        'turbo_casa_modo_emergencia',
    ];

    protected function casts(): array
    {
        return [
            'modo_emergencia'           => 'boolean',
            'auto_start_route'          => 'boolean',
            'turbo_casa'                => 'boolean',
            'turbo_preferencia'         => 'boolean',
            'turbo_casa_modo_emergencia' => 'boolean',
        ];
    }

    public function loja()
    {
        return $this->belongsTo(Loja::class);
    }
}