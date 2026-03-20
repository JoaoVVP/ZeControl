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
    ];

    protected function casts(): array
    {
        return [
            'modo_emergencia'  => 'boolean',
            'auto_start_route' => 'boolean',
        ];
    }

    public function loja()
    {
        return $this->belongsTo(Loja::class);
    }
}