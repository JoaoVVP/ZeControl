<?php

namespace App\Jobs;

use App\Models\Loja;
use App\Models\Motoboy;
use App\Services\PedidoService;
use App\Services\ZeDeliveryService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;

class TimerStartRouteJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int    $motoboyId,
        public int    $lojaId,
        public string $numeroPedido
    ) {}

    public function handle(ZeDeliveryService $ze, PedidoService $pedidoService): void
    {
        $motoboy = Motoboy::find($this->motoboyId);
        $loja    = Loja::find($this->lojaId);

        if (!$motoboy || !$loja) return;

        // Verifica se o pedido ainda está com esse motoboy
        $motoboyDoPedido = Redis::get("pedido_motoboy:{$this->numeroPedido}");
        if ((int) $motoboyDoPedido !== $this->motoboyId) {
            Log::info('TimerStartRoute ignorado — pedido já não está com esse motoboy', [
                'numero'  => $this->numeroPedido,
                'motoboy' => $this->motoboyId,
            ]);
            return;
        }

        // Verifica se motoboy já está em rota
        $estado = Redis::get("motoboy_estado:{$this->motoboyId}");
        if ($estado === 'em_rota') {
            Log::info('TimerStartRoute ignorado — motoboy já está em rota', [
                'motoboy' => $this->motoboyId,
            ]);
            return;
        }

        // Pega todos os pedidos do motoboy e chama startRoute
        $pedidos = Redis::lrange("motoboy_pedidos:{$this->motoboyId}", 0, -1);

        foreach ($pedidos as $pedido) {
            if ($motoboy->usuario) {
                $ze->startRoute($loja, $pedido, $motoboy->usuario->email);
                Log::info('[ZE] startRoute chamado', [
                    'numero'  => $pedido,
                    'motoboy' => $motoboy->nome,
                ]);
            }
        }

        // Marca motoboy como em rota
        $pedidoService->marcarEmRota($this->motoboyId);

        Log::info('Motoboy marcado em rota via timer', [
            'motoboy' => $motoboy->nome,
            'pedidos' => $pedidos,
        ]);
    }
}