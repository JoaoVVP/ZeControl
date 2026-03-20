<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConfiguracaoSistema extends Model
{
    protected $table = 'configuracoes_sistema';

    protected $fillable = ['chave', 'valor'];

    public static function get(string $chave, $default = null): mixed
    {
        $config = static::where('chave', $chave)->first();
        return $config?->valor ?? $default;
    }

    public static function set(string $chave, mixed $valor): void
    {
        static::updateOrCreate(
            ['chave' => $chave],
            ['valor' => $valor]
        );
    }
}