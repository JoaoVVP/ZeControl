<?php

namespace App\Services;

use App\Models\Loja;
use App\Models\Motoboy;
use App\Models\Rota;
use Illuminate\Support\Facades\Redis;

class PedidoService
{
    // ==========================================
    // PEDIDOS
    // ==========================================

    public function salvar(int $lojaId, string $numeroPedido, array $payload): void
    {
        Redis::set("pedido:{$lojaId}:{$numeroPedido}", json_encode($payload));
        Redis::rpush("pedidos_separacao:{$lojaId}", $numeroPedido);
    }

    public function buscar(int $lojaId, string $numeroPedido): ?array
    {
        $data = Redis::get("pedido:{$lojaId}:{$numeroPedido}");
        return $data ? json_decode($data, true) : null;
    }

    public function esquecer(int $lojaId, string $numeroPedido): void
    {
        $motoboyId = Redis::get("pedido_motoboy:{$numeroPedido}");

        Redis::del("pedido:{$lojaId}:{$numeroPedido}");
        Redis::lrem("pedidos_separacao:{$lojaId}", 0, $numeroPedido);
        Redis::del("pedido_motoboy:{$numeroPedido}");

        if ($motoboyId) {
            Redis::lrem("motoboy_pedidos:{$motoboyId}", 0, $numeroPedido);
            $pedidosRestantes = Redis::llen("motoboy_pedidos:{$motoboyId}");

            if ($pedidosRestantes === 0) {
                Redis::set("motoboy_status_{$motoboyId}", 'disponivel');
                $this->liberarRotasMotoboy((int) $motoboyId);
                Redis::del("motoboy_posicao:{$lojaId}:{$motoboyId}");

                // Dispara timer de inativação
                \App\Jobs\TimerInativarMotoboyJob::dispatch(
                    (int) $motoboyId,
                    $lojaId
                )->delay(now()->addMinutes(\App\Jobs\TimerInativarMotoboyJob::PRAZO_MINUTOS));

                Log::info('Timer inativação agendado', [
                    'motoboy_id' => $motoboyId,
                    'prazo'      => \App\Jobs\TimerInativarMotoboyJob::PRAZO_MINUTOS . ' minutos',
                ]);
            } else {
                $this->verificarSlotMotoboy((int) $motoboyId, $lojaId);
            }
        }
    }

    public function coordenadas(int $lojaId, string $numeroPedido): ?array
    {
        $payload = $this->buscar($lojaId, $numeroPedido);
        if (!$payload) return null;

        return [
            'lat' => $payload['delivery']['deliveryAddress']['coordinates']['latitude'],
            'lng' => $payload['delivery']['deliveryAddress']['coordinates']['longitude'],
        ];
    }

    public function emSeparacao(int $lojaId): array
    {
        $ids = Redis::lrange("pedidos_separacao:{$lojaId}", 0, -1);
        return collect($ids)
            ->map(fn($id) => $this->buscar($lojaId, $id))
            ->filter()
            ->values()
            ->toArray();
    }

    // ==========================================
    // ASSOCIAÇÃO PEDIDO → MOTOBOY
    // ==========================================

    public function associarMotoboy(int $lojaId, string $numeroPedido, Loja $loja): ?Motoboy
    {
        $coords        = $this->coordenadas($lojaId, $numeroPedido);
        if (!$coords) return null;

        $rotasDoPedido = $this->rotasParaPedido($lojaId, $coords);
        $limite        = $loja->configuracao->pedidos_por_rota ?? 1;

        // Verifica se alguma rota já tem motoboy com slot disponível
        foreach ($rotasDoPedido as $rota) {
            $motoboyId = Redis::get("rota_motoboy:{$rota['id']}");
            if ($motoboyId) {
                $totalPedidos = Redis::llen("motoboy_pedidos:{$motoboyId}");
                if ($totalPedidos < $limite) {
                    $motoboy = Motoboy::find($motoboyId);
                    if ($motoboy) {
                        $this->vincularPedidoMotoboy($numeroPedido, $motoboy, $rotasDoPedido, $lojaId, $limite);
                        return $motoboy;
                    }
                }
            }
        }

        // Nenhuma rota tem motoboy disponível → pega próximo da fila
        $proximoId = Redis::lpop("fila_loja_{$lojaId}");
        if (!$proximoId) return null;

        $motoboy = Motoboy::find($proximoId);
        if (!$motoboy) return null;

        $this->vincularPedidoMotoboy($numeroPedido, $motoboy, $rotasDoPedido, $lojaId, $limite);

        return $motoboy;
    }

    private function vincularPedidoMotoboy(
        string $numeroPedido,
        Motoboy $motoboy,
        array $rotas,
        int $lojaId,
        int $limite
    ): void {
        Redis::rpush("motoboy_pedidos:{$motoboy->id}", $numeroPedido);
        Redis::set("pedido_motoboy:{$numeroPedido}", $motoboy->id);
        Redis::lrem("pedidos_separacao:{$lojaId}", 0, $numeroPedido);

        // Marca rotas do pedido para esse motoboy
        foreach ($rotas as $rota) {
            Redis::set("rota_motoboy:{$rota['id']}", $motoboy->id);
            Redis::sadd("motoboy_rotas:{$motoboy->id}", $rota['id']);
        }

        $totalPedidos = Redis::llen("motoboy_pedidos:{$motoboy->id}");

        // Se atingiu o limite → sai da fila e salva posição
        if ($totalPedidos >= $limite) {
            $this->sairDaFila($motoboy->id, $lojaId);
        }

        Redis::set("motoboy_status_{$motoboy->id}", 'carregando');
    }

    // ==========================================
    // CANCELAMENTO
    // ==========================================

    public function cancelarPedido(int $lojaId, string $numeroPedido): void
    {
        $motoboyId = Redis::get("pedido_motoboy:{$numeroPedido}");

        // Remove pedido do Redis
        Redis::del("pedido:{$lojaId}:{$numeroPedido}");
        Redis::lrem("pedidos_separacao:{$lojaId}", 0, $numeroPedido);
        Redis::del("pedido_motoboy:{$numeroPedido}");

        if (!$motoboyId) return;

        Redis::lrem("motoboy_pedidos:{$motoboyId}", 0, $numeroPedido);

        $estado = Redis::get("motoboy_estado:{$motoboyId}") ?? 'carregando';

        // Só redistribui se ainda estiver carregando (não saiu da loja)
        if ($estado === 'carregando') {
            $this->redistribuirAposCancel((int) $motoboyId, $lojaId);
        } else {
            // Já em rota — apenas verifica slot
            $this->verificarSlotMotoboy((int) $motoboyId, $lojaId);
        }
    }

    private function redistribuirAposCancel(int $motoboyId, int $lojaId): void
    {
        $loja   = Loja::find($lojaId);
        $limite = $loja->configuracao->pedidos_por_rota ?? 1;

        $totalPedidos = Redis::llen("motoboy_pedidos:{$motoboyId}");

        // Motoboy agora tem slot livre
        // Verifica se algum motoboy fora da fila tem pedido que compartilha rota
        $rotasMotoboy = Redis::smembers("motoboy_rotas:{$motoboyId}");

        foreach ($rotasMotoboy as $rotaId) {
            // Busca motoboys que cobrem essa rota e estão fora da fila
            $outromotoboyId = $this->buscarMotoboyForaDaFilaComRota((int) $rotaId, $motoboyId, $lojaId);

            if ($outromotoboyId) {
                $outroMotoboy      = Motoboy::find($outromotoboyId);
                $pedidosOutro      = Redis::lrange("motoboy_pedidos:{$outromotoboyId}", 0, -1);
                $totalPedidosOutro = count($pedidosOutro);

                if ($totalPedidosOutro > 0 && $totalPedidos < $limite) {
                    // Transfere um pedido do outro motoboy para esse
                    $pedidoTransferir = $pedidosOutro[0];

                    Redis::lrem("motoboy_pedidos:{$outromotoboyId}", 0, $pedidoTransferir);
                    Redis::rpush("motoboy_pedidos:{$motoboyId}", $pedidoTransferir);
                    Redis::set("pedido_motoboy:{$pedidoTransferir}", $motoboyId);

                    // Outro motoboy perdeu pedido → volta para fila na posição original
                    $this->voltarParaFila($outromotoboyId, $lojaId);

                    // Esse motoboy atingiu limite → sai da fila
                    $totalPedidos = Redis::llen("motoboy_pedidos:{$motoboyId}");
                    if ($totalPedidos >= $limite) {
                        $this->sairDaFila($motoboyId, $lojaId);
                    }

                    return;
                }
            }
        }

        // Nenhuma redistribuição → motoboy volta para fila na posição original
        $this->voltarParaFila($motoboyId, $lojaId);
    }

    private function buscarMotoboyForaDaFilaComRota(int $rotaId, int $excluirMotoboyId, int $lojaId): ?int
    {
        // Busca motoboys que cobrem essa rota
        $motoboyId = Redis::get("rota_motoboy:{$rotaId}");

        if ($motoboyId && (int) $motoboyId !== $excluirMotoboyId) {
            // Verifica se está fora da fila (não tem posição na fila)
            $posicao = Redis::get("motoboy_posicao:{$lojaId}:{$motoboyId}");
            $naFila  = in_array($motoboyId, Redis::lrange("fila_loja_{$lojaId}", 0, -1));

            if (!$naFila) {
                return (int) $motoboyId;
            }
        }

        return null;
    }

    // ==========================================
    // FILA
    // ==========================================

    private function sairDaFila(int $motoboyId, int $lojaId): void
    {
        // Salva posição original antes de sair
        $fila    = Redis::lrange("fila_loja_{$lojaId}", 0, -1);
        $posicao = array_search((string) $motoboyId, $fila);

        if ($posicao !== false) {
            Redis::set("motoboy_posicao:{$lojaId}:{$motoboyId}", $posicao);
            Redis::lrem("fila_loja_{$lojaId}", 0, $motoboyId);
        }
    }

    public function voltarParaFila(int $motoboyId, int $lojaId): void
    {
        $posicaoOriginal = Redis::get("motoboy_posicao:{$lojaId}:{$motoboyId}");
        $fila            = Redis::lrange("fila_loja_{$lojaId}", 0, -1);

        // Remove se já estiver na fila
        Redis::lrem("fila_loja_{$lojaId}", 0, $motoboyId);

        if ($posicaoOriginal !== null && isset($fila[(int) $posicaoOriginal - 1])) {
            // Reconstrói a fila inserindo na posição original
            $novaFila = array_merge(
                array_slice($fila, 0, (int) $posicaoOriginal),
                [$motoboyId],
                array_slice($fila, (int) $posicaoOriginal)
            );

            Redis::del("fila_loja_{$lojaId}");
            foreach ($novaFila as $id) {
                Redis::rpush("fila_loja_{$lojaId}", $id);
            }
        } else {
            // Posição não existe mais → adiciona no final
            Redis::rpush("fila_loja_{$lojaId}", $motoboyId);
        }

        Redis::set("motoboy_status_{$motoboyId}", 'aguardando');
        Redis::del("motoboy_posicao:{$lojaId}:{$motoboyId}");
    }

    private function verificarSlotMotoboy(int $motoboyId, int $lojaId): void
    {
        $loja         = Loja::find($lojaId);
        $limite       = $loja->configuracao->pedidos_por_rota ?? 1;
        $totalPedidos = Redis::llen("motoboy_pedidos:{$motoboyId}");

        if ($totalPedidos === 0) {
            // Sem pedidos → disponivel e libera rotas
            Redis::set("motoboy_status_{$motoboyId}", 'disponivel');
            $this->liberarRotasMotoboy($motoboyId);
            Redis::del("motoboy_posicao:{$lojaId}:{$motoboyId}");
        } elseif ($totalPedidos < $limite) {
            // Tem slot → volta para fila
            $this->voltarParaFila($motoboyId, $lojaId);
        }
    }

    // ==========================================
    // EM ROTA
    // ==========================================

    public function marcarEmRota(int $motoboyId): void
    {
        Redis::set("motoboy_estado:{$motoboyId}", 'em_rota');
        Redis::set("motoboy_status_{$motoboyId}", 'em_rota');
    }

    // ==========================================
    // ROTAS
    // ==========================================

    public function rotasParaPedido(int $lojaId, array $coordenadas): array
    {
        $rotas = Rota::where('loja_id', $lojaId)->where('ativo', true)->get();

        return $rotas->filter(function ($rota) use ($coordenadas) {
            return $this->pontoNoPoligono($coordenadas, $rota->coordenadas);
        })->map(fn($r) => ['id' => $r->id, 'nome' => $r->nome])
          ->values()
          ->toArray();
    }

    private function liberarRotasMotoboy(int $motoboyId): void
    {
        $rotaIds = Redis::smembers("motoboy_rotas:{$motoboyId}");
        foreach ($rotaIds as $rotaId) {
            Redis::del("rota_motoboy:{$rotaId}");
        }
        Redis::del("motoboy_rotas:{$motoboyId}");
        Redis::del("motoboy_estado:{$motoboyId}");
    }

    // ==========================================
    // GEOMETRIA
    // ==========================================

    private function pontoNoPoligono(array $ponto, array $poligono): bool
    {
        $lat    = $ponto['lat'];
        $lng    = $ponto['lng'];
        $dentro = false;
        $n      = count($poligono);

        for ($i = 0, $j = $n - 1; $i < $n; $j = $i++) {
            $xi = $poligono[$i]['lat'];
            $yi = $poligono[$i]['lng'];
            $xj = $poligono[$j]['lat'];
            $yj = $poligono[$j]['lng'];

            $intersecta = (($yi > $lng) !== ($yj > $lng))
                && ($lat < ($xj - $xi) * ($lng - $yi) / ($yj - $yi) + $xi);

            if ($intersecta) $dentro = !$dentro;
        }

        return $dentro;
    }
}
