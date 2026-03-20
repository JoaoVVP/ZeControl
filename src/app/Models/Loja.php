<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Loja extends Model
{
    protected $fillable = [
        'nome',
        'email',
        'telefone',
        'api_token',
        'ze_merchant_id',
        'ze_client_id',
        'ze_client_secret',
    ];

    protected function casts(): array
    {
        return [
            'ze_merchant_id'   => 'encrypted',
            'ze_client_id'     => 'encrypted',
            'ze_client_secret' => 'encrypted',
        ];
    }

    public function usuarios()
    {
        return $this->hasMany(Usuario::class);
    }

    public function configuracao()
    {
        return $this->hasOne(ConfiguracaoLoja::class);
    }
}