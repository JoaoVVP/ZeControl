<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Redis;

class Pedido extends Model
{
    protected $fillable = [
        'loja_id',
        'numero_pedido',
        'status',
        'payload',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
        ];
    }

    public function loja()
    {
        return $this->belongsTo(Loja::class);
    }

    // Qual motoboy está com esse pedido
    public function getMotoboy(): ?Motoboy
    {
        $motoboyId = Redis::get("pedido_motoboy_{$this->id}");
        return $motoboyId ? Motoboy::find($motoboyId) : null;
    }

    public function associarMotoboy(int $motoboyId): void
    {
        Redis::set("pedido_motoboy_{$this->id}", $motoboyId);
        $this->update(['status' => 'em_rota']);
    }

    public function finalizar(): void
    {
        Redis::del("pedido_motoboy_{$this->id}");
        $this->update(['status' => 'entregue']);
    }
}