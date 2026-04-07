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

class TurboAguardarCasaJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int    $lojaId,
        public string $numeroPedidoTurbo,
        public int    $motoboyId
    ) {}

    public function handle(PedidoService $pedidoService, ZeDeliveryService $ze, WahaService $waha): void
    {
        $loja    = Loja::find($this->lojaId);
        $motoboy = Motoboy::find($this->motoboyId);

        if (!$loja || !$motoboy) return;

        // Verifica se turbo ainda está aguardando (não foi casada ainda)
        $status = Redis::get("turbo_aguardando:{$this->numeroPedidoTurbo}");
        if (!$status) {
            Log::info('[TURBO] Já foi casada ou cancelada, ignorando timer', [
                'numero' => $this->numeroPedidoTurbo,
            ]);
            return;
        }

        // Prazo esgotado — turbo vai sozinha
        Log::info('[TURBO] Prazo de espera esgotado, turbo vai sozinha', [
            'numero'  => $this->numeroPedidoTurbo,
            'motoboy' => $motoboy->nome,
        ]);

        Redis::del("turbo_aguardando:{$this->numeroPedidoTurbo}");

        // Vincula turbo ao motoboy sozinha
        $coords        = $pedidoService->coordenadas($this->lojaId, $this->numeroPedidoTurbo);
        $rotasTurbo    = $coords ? $pedidoService->rotasParaPedido($this->lojaId, $coords) : [];
        $config        = $loja->configuracao;
        $limite        = $config->pedidos_por_rota ?? 1;

        $pedidoService->vincularTurboAoMotoboy(
            $this->numeroPedidoTurbo,
            $motoboy,
            $rotasTurbo,
            $this->lojaId,
            $limite,
            false // não casou
        );

        // Marca preferência se configurado
        if ($config->turbo_preferencia) {
            Redis::sadd("motoboy_saiu_turbo_solo:{$this->motoboyId}", 1);
        }

        // Timer startRoute da turbo
        TimerStartRouteJob::dispatch(
            $this->motoboyId,
            $this->lojaId,
            $this->numeroPedidoTurbo
        )->delay(now()->addMinutes($config->turbo_prazo_minutos));

        Log::info('[TURBO] Timer startRoute turbo agendado', [
            'numero'  => $this->numeroPedidoTurbo,
            'minutos' => $config->turbo_prazo_minutos,
        ]);
    }
}