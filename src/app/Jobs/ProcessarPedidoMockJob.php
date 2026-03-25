<?php

namespace App\Jobs;

use App\Mock\PedidoMock;
use App\Models\Loja;
use App\Services\PedidoService;
use App\Services\ZeDeliveryService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ProcessarPedidoMockJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int    $lojaId,
        public string $numeroPedido = '100000001'
    ) {}

    public function handle(PedidoService $pedidoService, ZeDeliveryService $ze): void
    {
        $loja = Loja::find($this->lojaId);
        if (!$loja) return;

        $payload = PedidoMock::gerar($this->lojaId, $this->numeroPedido);

        // Simula evento CONFIRMED entrando pelo polling
        $eventoConfirmed = [[
            'eventId'   => 'mock-' . $this->numeroPedido . '-confirmed',
            'orderId'   => $this->numeroPedido,
            'eventType' => 'CONFIRMED',
        ]];

        // Dispara o job real passando o evento mock
        ProcessarEventosMockJob::dispatch($this->lojaId, $eventoConfirmed, $payload);

        // Após 1 minuto → simula DISPATCHED
        ProcessarEventosMockJob::dispatch(
            $this->lojaId,
            [[
                'eventId'   => 'mock-' . $this->numeroPedido . '-dispatched',
                'orderId'   => $this->numeroPedido,
                'eventType' => 'DISPATCHED',
            ]],
            $payload
        )->delay(now()->addMinute());

        // Após 3 minutos → simula CONCLUDED
        ProcessarEventosMockJob::dispatch(
            $this->lojaId,
            [[
                'eventId'   => 'mock-' . $this->numeroPedido . '-concluded',
                'orderId'   => $this->numeroPedido,
                'eventType' => 'CONCLUDED',
            ]],
            $payload
        )->delay(now()->addMinutes(3));

        Log::info('[MOCK] Eventos agendados', [
            'numero'     => $this->numeroPedido,
            'confirmed'  => 'imediato',
            'dispatched' => '1 minuto',
            'concluded'  => '3 minutos',
        ]);
    }
}