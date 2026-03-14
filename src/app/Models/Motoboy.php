<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Redis;

class Motoboy extends Model
{
    protected $fillable = [
        'loja_id',
        'usuario_id',
        'nome',
        'telefone',
    ];

    public function loja()
    {
        return $this->belongsTo(Loja::class);
    }

    public function usuario()
    {
        return $this->belongsTo(Usuario::class);
    }

    // Status via Redis
    public function getStatus(): string
    {
        return Redis::get("motoboy_status_{$this->id}") ?? 'inativo';
    }

    public function setStatus(string $status): void
    {
        Redis::set("motoboy_status_{$this->id}", $status);
    }

    // Fila via Redis
    public static function entrarNaFila(int $lojaId, int $motoboyId): void
    {
        Redis::rpush("fila_loja_{$lojaId}", $motoboyId);
        Redis::set("motoboy_status_{$motoboyId}", 'aguardando');
    }

    public static function proximoDaFila(int $lojaId): ?int
    {
        $motoboyId = Redis::lpop("fila_loja_{$lojaId}");
        if ($motoboyId) {
            Redis::set("motoboy_status_{$motoboyId}", 'em_rota');
        }
        return $motoboyId ? (int) $motoboyId : null;
    }

    public static function filaAtual(int $lojaId): array
    {
        return Redis::lrange("fila_loja_{$lojaId}", 0, -1);
    }
}