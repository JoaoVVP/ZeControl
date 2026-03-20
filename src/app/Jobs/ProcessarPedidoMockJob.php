<?php

namespace App\Jobs;

use App\Mock\PedidoMock;
use App\Models\Loja;
use App\Models\Motoboy;
use App\Services\PedidoService;
use App\Services\WahaService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class ProcessarPedidoMockJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int    $lojaId,
        public string $numeroPedido = '100000001'
    ) {}

    public function handle(PedidoService $pedidoService, WahaService $waha): void
    {
        $loja = Loja::find($this->lojaId);
        if (!$loja) {
            Log::error('Loja não encontrada', ['loja_id' => $this->lojaId]);
            return;
        }

        // 1. Gera o payload mock
        $payload = PedidoMock::gerar($this->lojaId, $this->numeroPedido);
        Log::info('Pedido mock gerado', ['numero' => $this->numeroPedido]);

        // 2. Salva no Redis
        $pedidoService->salvar($this->lojaId, $this->numeroPedido, $payload);

        // 3. Associa ao motoboy
        $motoboy = $pedidoService->associarMotoboy($this->lojaId, $this->numeroPedido, $loja);

        if (!$motoboy) {
            Log::warning('Nenhum motoboy disponível para o pedido', [
                'numero' => $this->numeroPedido,
                'loja'   => $this->lojaId,
            ]);
            return;
        }

        Log::info('Pedido associado ao motoboy', [
            'numero'  => $this->numeroPedido,
            'motoboy' => $motoboy->nome,
        ]);

        // 4. Envia para API Ze Entregador (função fantasma)
        $this->enviarParaZeEntregador($this->numeroPedido, $motoboy);

        // 5. Timer de startRoute automático
        $configuracao = $loja->configuracao;
        if ($configuracao && $configuracao->auto_start_route && $configuracao->start_route_minutos) {
            TimerStartRouteJob::dispatch(
                $motoboy->id,
                $this->lojaId,
                $this->numeroPedido
            )->delay(now()->addMinutes($configuracao->start_route_minutos));

            Log::info('Timer startRoute agendado', [
                'motoboy' => $motoboy->nome,
                'minutos' => $configuracao->start_route_minutos,
            ]);
        }

        // 6. Envia mensagem WhatsApp para o motoboy
        if ($motoboy->telefone) {
            $cliente  = $payload['customer']['name'];
            $telefone = $payload['customer']['phone']['number'];
            $endereco = $payload['delivery']['deliveryAddress']['formattedAddress'];
            $total    = $payload['total']['orderAmount']['value'];
            $itens    = collect($payload['items'])
                ->map(fn($i) => "• {$i['quantity']}x {$i['name']}")
                ->join("\n");

            $mensagem  = "🛵 *Olá, {$motoboy->nome}!*\n\n";
            $mensagem .= "📦 *Novo pedido: #{$this->numeroPedido}*\n\n";
            $mensagem .= "*Itens:*\n{$itens}\n\n";
            $mensagem .= "📍 *Endereço:*\n{$endereco}\n\n";
            $mensagem .= "👤 *Cliente:* {$cliente}\n";
            $mensagem .= "📞 *Telefone:* {$telefone}\n\n";
            $mensagem .= "💰 *Total: R$ {$total}*\n\n";
            $mensagem .= "✅ Dirija-se ao balcão para retirar o pedido.";

            $enviado = $waha->enviarMensagem($motoboy->telefone, $mensagem);

            Log::info('WhatsApp ' . ($enviado ? 'enviado' : 'falhou'), [
                'motoboy'  => $motoboy->nome,
                'telefone' => $motoboy->telefone,
            ]);
        }
    }

    private function enviarParaZeEntregador(string $numeroPedido, Motoboy $motoboy): void
    {
        // TODO: implementar integração com API Ze Entregador
        // $ze->orderPicked($loja, $numeroPedido, $motoboy->usuario->email);
        Log::info('[ZE ENTREGADOR] orderPicked simulado', [
            'numero'  => $numeroPedido,
            'motoboy' => $motoboy->nome,
        ]);
    }
}