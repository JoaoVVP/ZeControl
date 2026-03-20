<?php

namespace App\Jobs;

use App\Services\PedidoService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;

class TimerInativarMotoboyJob implements ShouldQueue
{
    use Queueable;

    // Para testes: 2 minutos. Em prod: 40 minutos
    const PRAZO_MINUTOS = 2;

    public function __construct(
        public int $motoboyId,
        public int $lojaId
    ) {}

    public function handle(PedidoService $pedidoService): void
    {
        $status = Redis::get("motoboy_status_{$this->motoboyId}");

        // Se ainda estiver disponivel após o prazo → inativa
        if ($status === 'disponivel') {
            Redis::set("motoboy_status_{$this->motoboyId}", 'inativo');
            $pedidoService->liberarRotasMotoboy($this->motoboyId);
            Redis::del("motoboy_posicao:{$this->lojaId}:{$this->motoboyId}");

            Log::info('Motoboy inativado por timeout', [
                'motoboy_id' => $this->motoboyId,
                'prazo'      => self::PRAZO_MINUTOS . ' minutos',
            ]);
        }
    }
}