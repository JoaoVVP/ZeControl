<?php

namespace App\Jobs;

use App\Models\Loja;
use App\Services\PedidoService;
use App\Services\ZeDeliveryService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessarEventosZeJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $lojaId) {}

    public function handle(ZeDeliveryService $ze, PedidoService $pedidoService): void
    {
        $loja = Loja::find($this->lojaId);
        if (!$loja || !$loja->ze_client_id) return;

        $eventos = $ze->buscarEventos($loja);
        if (empty($eventos)) return;

        $processados = [];

        foreach ($eventos as $evento) {
            $numeroPedido = (string) $evento['orderId'];

        match ($evento['eventType']) {
            'CREATED'   => $this->processarCriado($ze, $pedidoService, $loja, $numeroPedido, $evento),
            'DISPATCHED'=> $pedidoService->marcarEmRota(
                                (int) Redis::get("pedido_motoboy:{$numeroPedido}")
                        ),
            'CANCELLED' => $pedidoService->cancelarPedido($loja->id, $numeroPedido),
            'CONCLUDED' => $pedidoService->esquecer($loja->id, $numeroPedido),
            default     => null,
        };

            $processados[] = $evento;
        }

        // Acknowledgment de todos os eventos processados
        if (!empty($processados)) {
            $ze->acknowledgment($loja, $processados);
        }
    }

    private function processarCriado(
        ZeDeliveryService $ze,
        PedidoService $pedidoService,
        Loja $loja,
        string $numeroPedido,
        array $evento
    ): void {
        // Busca detalhes completos do pedido
        $payload = $ze->buscarPedido($loja, $numeroPedido);
        if (!$payload) return;

        // Confirma pedido no Ze
        $ze->confirmarPedido($loja, $numeroPedido);

        // Salva no Redis
        $pedidoService->salvar($loja->id, $numeroPedido, $payload);

        // Associa ao motoboy
        $motoboy = $pedidoService->associarMotoboy($loja->id, $numeroPedido, $loja);

        // Se associou, chama orderPicked no Ze
        if ($motoboy && $motoboy->usuario) {
            $ze->orderPicked($loja, $numeroPedido, $motoboy->usuario->email);
        }
    }
}