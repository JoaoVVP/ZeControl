<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Rota extends Model
{
    protected $fillable = [
        'loja_id',
        'nome',
        'cor',
        'coordenadas',
        'ativo',
    ];

    protected function casts(): array
    {
        return [
            'coordenadas' => 'array',
            'ativo'       => 'boolean',
        ];
    }

    public function loja()
    {
        return $this->belongsTo(Loja::class);
    }
}