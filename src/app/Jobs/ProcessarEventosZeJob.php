<?php

namespace App\Jobs;

use App\Models\Loja;
use App\Models\Motoboy;
use App\Services\PedidoService;
use App\Services\WahaService;
use App\Services\ZeDeliveryService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class ProcessarEventosZeJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $lojaId) {}

    public function handle(
        ZeDeliveryService $ze,
        PedidoService     $pedidoService,
        WahaService       $waha
    ): void {
        $loja = Loja::find($this->lojaId);
        if (!$loja || !$loja->ze_client_id) return;

        $eventos = $ze->buscarEventos($loja, [
            'CREATED',
            'CONFIRMED',
            'DISPATCHED',
            'CONCLUDED',
            'CANCELLED',
            'EDITED',
        ]);

        if (empty($eventos)) return;

        $processados = [];

        foreach ($eventos as $evento) {
            $numeroPedido = (string) $evento['orderId'];
            $tipo         = $evento['eventType'];

            try {
                match ($tipo) {
                    'CREATED'             => $this->processarCreated($ze, $loja, $numeroPedido),
                    'CONFIRMED', 'EDITED' => $this->processarConfirmed($ze, $pedidoService, $loja, $numeroPedido),
                    'DISPATCHED'          => $this->processarDispatched($pedidoService, $waha, $loja, $numeroPedido),
                    'CONCLUDED'           => $this->processarConcluded($pedidoService, $loja, $numeroPedido),
                    'CANCELLED'           => $this->processarCancelled($pedidoService, $loja, $numeroPedido),
                    default               => null,
                };

                $processados[] = $evento;

            } catch (\Exception $e) {
                Log::error("Erro ao processar evento {$tipo}", [
                    'numero' => $numeroPedido,
                    'erro'   => $e->getMessage(),
                ]);
            }
        }

        if (!empty($processados)) {
            $ze->acknowledgment($loja, $processados);
        }
    }

    // ==========================================
    // CREATED → apenas confirma na API Ze
    // ==========================================
    private function processarCreated(
        ZeDeliveryService $ze,
        Loja              $loja,
        string            $numeroPedido
    ): void {
        $confirmado = $ze->confirmarPedido($loja, $numeroPedido);

        Log::info('[ZE] CREATED processado', [
            'numero'     => $numeroPedido,
            'confirmado' => $confirmado,
        ]);
    }

    // ==========================================
    // CONFIRMED / EDITED → salva, associa, orderPicked
    // ==========================================
    private function processarConfirmed(
        ZeDeliveryService $ze,
        PedidoService     $pedidoService,
        Loja              $loja,
        string            $numeroPedido
    ): void {
        // Busca payload completo
        $payload = $ze->buscarPedido($loja, $numeroPedido);
        if (!$payload) {
            Log::warning('[ZE] CONFIRMED — payload não encontrado', ['numero' => $numeroPedido]);
            return;
        }

        // Se já existe no Redis (EDITED) — atualiza payload mantendo motoboy
        $jaExiste = Redis::exists("pedido:{$loja->id}:{$numeroPedido}");
        if ($jaExiste) {
            Redis::set("pedido:{$loja->id}:{$numeroPedido}", json_encode($payload));
            Log::info('[ZE] EDITED — payload atualizado no Redis', ['numero' => $numeroPedido]);
            return;
        }

        // Salva no Redis (separação)
        $pedidoService->salvar($loja->id, $numeroPedido, $payload);

        // Associa ao motoboy
        $motoboy = $pedidoService->associarMotoboy($loja->id, $numeroPedido, $loja);

        if (!$motoboy) {
            Log::warning('[ZE] CONFIRMED — nenhum motoboy disponível', ['numero' => $numeroPedido]);
            return;
        }

        Log::info('[ZE] CONFIRMED — motoboy associado', [
            'numero'  => $numeroPedido,
            'motoboy' => $motoboy->nome,
        ]);

        // orderPicked na API Ze
        if ($motoboy->usuario) {
            $ze->orderPicked($loja, $numeroPedido, $motoboy->usuario->email);
            Log::info('[ZE] orderPicked chamado', [
                'numero'  => $numeroPedido,
                'motoboy' => $motoboy->nome,
            ]);
        }

        // Timer startRoute automático
        $configuracao = $loja->configuracao;
        if ($configuracao && $configuracao->auto_start_route && $configuracao->start_route_minutos) {
            TimerStartRouteJob::dispatch(
                $motoboy->id,
                $loja->id,
                $numeroPedido
            )->delay(now()->addMinutes($configuracao->start_route_minutos));

            Log::info('[ZE] Timer startRoute agendado', [
                'motoboy' => $motoboy->nome,
                'minutos' => $configuracao->start_route_minutos,
            ]);
        }
    }

    // ==========================================
    // DISPATCHED → WhatsApp + marcar em rota
    // ==========================================
    private function processarDispatched(
        PedidoService     $pedidoService,
        WahaService       $waha,
        ZeDeliveryService $ze,
        Loja              $loja,
        string            $numeroPedido
    ): void {
        $motoboyId = Redis::get("pedido_motoboy:{$numeroPedido}");

        // Pedido não está no nosso Redis — foi escaneado diretamente pelo motoboy
        if (!$motoboyId) {
            Log::info('[ZE] DISPATCHED — pedido fora do fluxo, buscando entregador na API', [
                'numero' => $numeroPedido,
            ]);

            // Busca quem está com o pedido na API Ze
            $detalhes = $ze->buscarDetalhesEntrega($loja, $numeroPedido);
            if (!$detalhes) return;

            $emailMotoboy = $detalhes['deliveryMan']['email'] ?? null;
            if (!$emailMotoboy) return;

            // Busca motoboy pelo email
            $usuario = \App\Models\Usuario::where('email', $emailMotoboy)->first();
            if (!$usuario) return;

            $motoboy = \App\Models\Motoboy::where('usuario_id', $usuario->id)->first();
            if (!$motoboy) return;

            // Salva pedido no Redis associado ao motoboy
            $payload = $ze->buscarPedido($loja, $numeroPedido);
            if ($payload) {
                Redis::set("pedido:{$loja->id}:{$numeroPedido}", json_encode($payload));
                Redis::rpush("motoboy_pedidos:{$motoboy->id}", $numeroPedido);
                Redis::set("pedido_motoboy:{$numeroPedido}", $motoboy->id);
            }

            // Marca em rota
            $pedidoService->marcarEmRota($motoboy->id);

            // Envia WhatsApp
            if ($motoboy->telefone && $payload) {
                $this->enviarWhatsAppDispatched($waha, $motoboy, $numeroPedido, $payload);
            }

            return;
        }

        // Fluxo normal — pedido estava no Redis
        $motoboy = \App\Models\Motoboy::find($motoboyId);
        if (!$motoboy) return;

        $pedidoService->marcarEmRota((int) $motoboyId);

        if ($motoboy->telefone) {
            $payload = $pedidoService->buscar($loja->id, $numeroPedido);
            if ($payload) {
                $this->enviarWhatsAppDispatched($waha, $motoboy, $numeroPedido, $payload);
            }
        }
    }

    private function enviarWhatsAppDispatched(
        WahaService $waha,
        \App\Models\Motoboy $motoboy,
        string $numeroPedido,
        array $payload
    ): void {
        $endereco = $payload['delivery']['deliveryAddress']['formattedAddress'] ?? '-';
        $cliente  = $payload['customer']['name'] ?? '-';
        $telefone = $payload['customer']['phone']['number'] ?? '-';
        $total    = $payload['total']['orderAmount']['value'] ?? '-';
        $itens    = collect($payload['items'] ?? [])
            ->map(fn($i) => "• {$i['quantity']}x {$i['name']}")
            ->join("\n");

        $mensagem  = "🛵 *Olá, {$motoboy->nome}!*\n\n";
        $mensagem .= "📦 *Pedido: #{$numeroPedido} despachado!*\n\n";
        $mensagem .= "*Itens:*\n{$itens}\n\n";
        $mensagem .= "📍 *Endereço:*\n{$endereco}\n\n";
        $mensagem .= "👤 *Cliente:* {$cliente}\n";
        $mensagem .= "📞 *Telefone:* {$telefone}\n\n";
        $mensagem .= "💰 *Total: R$ {$total}*\n\n";
        $mensagem .= "✅ Siga para o endereço de entrega!";

        $enviado = $waha->enviarMensagem($motoboy->telefone, $mensagem);

        Log::info('[ZE] DISPATCHED — WhatsApp ' . ($enviado ? 'enviado' : 'falhou'), [
            'motoboy'  => $motoboy->nome,
            'telefone' => $motoboy->telefone,
        ]);
    }

    // ==========================================
    // CONCLUDED → remove pedido, verifica motoboy
    // ==========================================
    private function processarConcluded(
        PedidoService $pedidoService,
        Loja          $loja,
        string        $numeroPedido
    ): void {
        $motoboyId    = Redis::get("pedido_motoboy:{$numeroPedido}");
        $pedidoService->esquecer($loja->id, $numeroPedido);

        Log::info('[ZE] CONCLUDED — pedido removido do Redis', [
            'numero'     => $numeroPedido,
            'motoboy_id' => $motoboyId,
        ]);
    }

    // ==========================================
    // CANCELLED → redistribuição se carregando
    // ==========================================
    private function processarCancelled(
        PedidoService $pedidoService,
        Loja          $loja,
        string        $numeroPedido
    ): void {
        $pedidoService->cancelarPedido($loja->id, $numeroPedido);

        Log::info('[ZE] CANCELLED — pedido cancelado', [
            'numero' => $numeroPedido,
        ]);
    }
}