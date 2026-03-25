<?php

namespace App\Services;

use App\Models\Loja;
use App\Models\Motoboy;
use App\Models\Rota;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;

class PedidoService
{
    // ==========================================
    // PEDIDOS
    // ==========================================

    public function salvar(int $lojaId, string $numeroPedido, array $payload): void
    {
        Redis::set("pedido:{$lojaId}:{$numeroPedido}", json_encode($payload));
        Redis::rpush("pedidos_separacao:{$lojaId}", $numeroPedido);

        // Verifica modo emergência
        $this->verificarModoEmergencia($lojaId);

        // Verifica se há turbo aguardando para casar
        $this->verificarTurboAguardando($lojaId, $numeroPedido);
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

                \App\Jobs\TimerInativarMotoboyJob::dispatch(
                    (int) $motoboyId,
                    $lojaId
                )->delay(now()->addMinutes(\App\Jobs\TimerInativarMotoboyJob::PRAZO_MINUTOS));

            } else {
                $this->verificarSlotMotoboy((int) $motoboyId, $lojaId);
            }
        }

        $this->verificarModoEmergencia($lojaId);
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
        // É turbo? Fluxo diferente
        if ($this->isPedidoTurbo($lojaId, $numeroPedido)) {
            $motoboy = $this->associarTurbo($lojaId, $numeroPedido, $loja);

            if ($motoboy) {
                $config = $loja->configuracao;
                // Timer startRoute da turbo
                \App\Jobs\TimerStartRouteJob::dispatch(
                    $motoboy->id,
                    $lojaId,
                    $numeroPedido
                )->delay(now()->addMinutes($config->turbo_prazo_minutos ?? 5));
            }

            return $motoboy;
        }

        // Fluxo normal...
        $coords        = $this->coordenadas($lojaId, $numeroPedido);
        if (!$coords) return null;

        $rotasDoPedido = $this->rotasParaPedido($lojaId, $coords);
        $limite        = $loja->configuracao->pedidos_por_rota ?? 1;

        foreach ($rotasDoPedido as $rota) {
            $motoboyId = Redis::get("rota_motoboy:{$rota['id']}");
            if ($motoboyId) {
                $totalPedidos = Redis::llen("motoboy_pedidos:{$motoboyId}");
                if ($totalPedidos < $limite) {
                    $motoboy = Motoboy::find($motoboyId);
                    if ($motoboy) {
                        $this->vincularPedidoMotoboy($numeroPedido, $motoboy, $rotasDoPedido, $lojaId, $limite);
                        $this->verificarModoEmergencia($lojaId);
                        return $motoboy;
                    }
                }
            }
        }

        $proximoId = Redis::lpop("fila_loja_{$lojaId}");
        if (!$proximoId) return null;

        $motoboy = Motoboy::find($proximoId);
        if (!$motoboy) return null;

        $pedidosSelecionados = $this->selecionarPedidosParaMotoboy($lojaId, $limite, $loja);

        foreach ($pedidosSelecionados as $pedido) {
            $coordsPedido = $this->coordenadas($lojaId, $pedido);
            $rotasPedido  = $coordsPedido ? $this->rotasParaPedido($lojaId, $coordsPedido) : [];
            $this->vincularPedidoMotoboy($pedido, $motoboy, $rotasPedido, $lojaId, $limite);
        }

        $this->verificarModoEmergencia($lojaId);

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
        $temPreferencia = Redis::get("motoboy_saiu_turbo_solo:{$motoboyId}");

        // Remove se já estiver na fila
        Redis::lrem("fila_loja_{$lojaId}", 0, $motoboyId);
        Redis::lrem("fila_preferencia:{$lojaId}", 0, $motoboyId);

        if ($temPreferencia) {
            // Entra na fila de preferência
            Redis::rpush("fila_preferencia:{$lojaId}", $motoboyId);
            Redis::del("motoboy_saiu_turbo_solo:{$motoboyId}");
            Log::info('[TURBO] Motoboy entrou na fila com preferência', ['motoboy_id' => $motoboyId]);
        }

        // Reconstrói fila: preferência primeiro, depois normais
        $filaPreferencia = Redis::lrange("fila_preferencia:{$lojaId}", 0, -1);
        $filaNormal      = Redis::lrange("fila_loja_{$lojaId}", 0, -1);

        // Remove da fila normal se está na preferência
        $filaNormal = array_filter($filaNormal, fn($id) => !in_array($id, $filaPreferencia));

        // Reconstrói fila completa
        Redis::del("fila_loja_{$lojaId}");
        foreach ($filaPreferencia as $id) {
            Redis::rpush("fila_loja_{$lojaId}", $id);
        }
        foreach ($filaNormal as $id) {
            Redis::rpush("fila_loja_{$lojaId}", $id);
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

    // ==========================================
    // MODO EMERGÊNCIA
    // ==========================================

    public function verificarModoEmergencia(int $lojaId): void
    {
        $loja = Loja::find($lojaId);
        if (!$loja || !$loja->configuracao) return;

        $config = $loja->configuracao;

        // Funcionalidade desabilitada na loja
        if (!$config->modo_emergencia) {
            Redis::del("modo_emergencia:{$lojaId}");
            return;
        }

        // Conta pedidos em separação SEM motoboy
        $pedidosSeparacao = Redis::lrange("pedidos_separacao:{$lojaId}", 0, -1);
        $semMotoboy = collect($pedidosSeparacao)->filter(function ($numeroPedido) {
            return !Redis::get("pedido_motoboy:{$numeroPedido}");
        })->count();

        if ($semMotoboy >= $config->gatilho_emergencia) {
            Redis::set("modo_emergencia:{$lojaId}", true);
            Log::info('Modo emergência ATIVADO', [
                'loja_id'    => $lojaId,
                'sem_motoboy' => $semMotoboy,
                'gatilho'    => $config->gatilho_emergencia,
            ]);
        } else {
            Redis::del("modo_emergencia:{$lojaId}");
            Log::info('Modo emergência DESATIVADO', [
                'loja_id'    => $lojaId,
                'sem_motoboy' => $semMotoboy,
                'gatilho'    => $config->gatilho_emergencia,
            ]);
        }
    }

    public function modoEmergenciaAtivo(int $lojaId): bool
    {
        return (bool) Redis::get("modo_emergencia:{$lojaId}");
    }

    // Agrupa pedidos para um motoboy respeitando FIFO + proximidade no modo emergência
    public function selecionarPedidosParaMotoboy(int $lojaId, int $limite, Loja $loja): array
    {
        $pedidosSeparacao = Redis::lrange("pedidos_separacao:{$lojaId}", 0, -1);

        // Filtra apenas pedidos sem motoboy
        $pedidosDisponiveis = collect($pedidosSeparacao)->filter(function ($numeroPedido) {
            return !Redis::get("pedido_motoboy:{$numeroPedido}");
        })->values();

        if ($pedidosDisponiveis->isEmpty()) return [];

        // Primeiro pedido é sempre FIFO
        $primeiroPedido  = $pedidosDisponiveis->first();
        $pedidosSelecionados = [$primeiroPedido];

        if ($limite === 1 || $pedidosDisponiveis->count() === 1) {
            return $pedidosSelecionados;
        }

        $emergenciaAtiva = $this->modoEmergenciaAtivo($lojaId);

        // Pega coordenadas do primeiro pedido
        $coordsPrimeiro = $this->coordenadas($lojaId, $primeiroPedido);

        // Pedidos candidatos para agrupar (exclui o primeiro)
        $candidatos = $pedidosDisponiveis->slice(1)->map(function ($numeroPedido) use ($lojaId, $coordsPrimeiro, $emergenciaAtiva) {
            $coords    = $this->coordenadas($lojaId, $numeroPedido);
            $distancia = $coords && $coordsPrimeiro
                ? $this->calcularDistancia($coordsPrimeiro, $coords)
                : PHP_INT_MAX;

            return [
                'numero'    => $numeroPedido,
                'distancia' => $distancia,
            ];
        });

        if ($emergenciaAtiva) {
            // Modo emergência: ordena candidatos por proximidade
            $candidatos = $candidatos->sortBy('distancia')->values();
        }
        // Modo normal: candidatos já estão em ordem FIFO

        // Pega até limite-1 pedidos adicionais
        foreach ($candidatos->take($limite - 1) as $candidato) {
            $pedidosSelecionados[] = $candidato['numero'];
        }

        return $pedidosSelecionados;
    }

    // Calcula distância entre dois pontos em metros (Haversine)
    private function calcularDistancia(array $a, array $b): float
    {
        $raio = 6371000; // metros
        $lat1 = deg2rad($a['lat']);
        $lat2 = deg2rad($b['lat']);
        $dLat = deg2rad($b['lat'] - $a['lat']);
        $dLng = deg2rad($b['lng'] - $a['lng']);

        $h = sin($dLat / 2) ** 2
        + cos($lat1) * cos($lat2) * sin($dLng / 2) ** 2;

        return 2 * $raio * asin(sqrt($h));
    }

    // ==========================================
    // TURBO
    // ==========================================

    public function isPedidoTurbo(int $lojaId, string $numeroPedido): bool
    {
        $payload = $this->buscar($lojaId, $numeroPedido);
        if (!$payload) return false;

        // Ze Delivery marca pedidos turbo no tipo
        return ($payload['type'] ?? '') === 'TURBO'
            || ($payload['turbo'] ?? false) === true;
    }

    public function associarTurbo(int $lojaId, string $numeroPedido, Loja $loja): ?Motoboy
    {
        $config        = $loja->configuracao;
        $coords        = $this->coordenadas($lojaId, $numeroPedido);
        $rotasTurbo    = $coords ? $this->rotasParaPedido($lojaId, $coords) : [];
        $limite        = $config->pedidos_por_rota ?? 1;
        $emergencia    = $this->modoEmergenciaAtivo($lojaId);

        // Define se turbo pode casar
        $podeCasar = $emergencia
            ? $config->turbo_casa_modo_emergencia
            : $config->turbo_casa;

        $fila = Redis::lrange("fila_loja_{$lojaId}", 0, -1);

        if ($podeCasar) {
            return $this->associarTurboComCasa(
                $lojaId, $numeroPedido, $loja, $fila, $rotasTurbo, $limite, $config
            );
        } else {
            return $this->associarTurboSemCasa(
                $lojaId, $numeroPedido, $loja, $fila, $rotasTurbo, $limite, $config
            );
        }
    }

    private function associarTurboComCasa(
        int    $lojaId,
        string $numeroPedido,
        Loja   $loja,
        array  $fila,
        array  $rotasTurbo,
        int    $limite,
        $config
    ): ?Motoboy {
        foreach ($fila as $motoboyId) {
            $motoboy      = Motoboy::find($motoboyId);
            $pedidosMotoboy = Redis::lrange("motoboy_pedidos:{$motoboyId}", 0, -1);
            $totalPedidos = count($pedidosMotoboy);

            if ($totalPedidos === 0) {
                // Motoboy sem nota — aguarda X minutos por nota para casar
                Log::info('[TURBO] Motoboy sem nota, aguardando para casar', [
                    'numero'  => $numeroPedido,
                    'motoboy' => $motoboy->nome,
                    'minutos' => $config->turbo_espera_casa_minutos,
                ]);

                // Marca turbo como aguardando casa
                Redis::set("turbo_aguardando:{$numeroPedido}", $motoboyId);
                Redis::set("turbo_motoboy_reservado:{$motoboyId}", $numeroPedido);

                // Timer de espera — se não casar, vai sozinha
                \App\Jobs\TurboAguardarCasaJob::dispatch(
                    $lojaId,
                    $numeroPedido,
                    (int) $motoboyId
                )->delay(now()->addMinutes($config->turbo_espera_casa_minutos));

                return $motoboy;
            }

            // Tem nota — verifica se não é turbo e se cobre a mesma rota
            $temTurbo = collect($pedidosMotoboy)->contains(
                fn($p) => Redis::exists("pedido_turbo:{$p}")
            );

            if ($temTurbo) {
                // Turbo nunca casa com turbo — pula
                continue;
            }

            if ($totalPedidos < $limite) {
                // Verifica rota em comum
                $rotasMotoboy = Redis::smembers("motoboy_rotas:{$motoboyId}");
                $rotasTurboIds = collect($rotasTurbo)->pluck('id')->toArray();
                $temRotaComum  = !empty(array_intersect(
                    array_map('intval', $rotasMotoboy),
                    $rotasTurboIds
                ));

                if ($temRotaComum) {
                    // Casa!
                    $this->vincularTurboAoMotoboy($numeroPedido, $motoboy, $rotasTurbo, $lojaId, $limite, true);
                    Log::info('[TURBO] Casou com nota comum', [
                        'numero'  => $numeroPedido,
                        'motoboy' => $motoboy->nome,
                    ]);
                    return $motoboy;
                }
            }

            // Rota diferente ou sem slot — pula para próximo
        }

        // Nenhum motoboy disponível — fica em separação
        Log::warning('[TURBO] Nenhum motoboy disponível para casar', ['numero' => $numeroPedido]);
        return null;
    }

    private function associarTurboSemCasa(
        int    $lojaId,
        string $numeroPedido,
        Loja   $loja,
        array  $fila,
        array  $rotasTurbo,
        int    $limite,
        $config
    ): ?Motoboy {
        foreach ($fila as $motoboyId) {
            $motoboy      = Motoboy::find($motoboyId);
            $totalPedidos = Redis::llen("motoboy_pedidos:{$motoboyId}");

            if ($totalPedidos === 0) {
                // Motoboy sem nota — turbo vai sozinha
                $this->vincularTurboAoMotoboy($numeroPedido, $motoboy, $rotasTurbo, $lojaId, $limite, false);

                if ($config->turbo_preferencia) {
                    Redis::set("motoboy_saiu_turbo_solo:{$motoboy->id}", 1);
                }

                Log::info('[TURBO] Sem casa — turbo vai sozinha', [
                    'numero'  => $numeroPedido,
                    'motoboy' => $motoboy->nome,
                ]);

                return $motoboy;
            }
        }

        Log::warning('[TURBO] Sem casa — nenhum motoboy sem nota', ['numero' => $numeroPedido]);
        return null;
    }

    public function vincularTurboAoMotoboy(
        string  $numeroPedido,
        Motoboy $motoboy,
        array   $rotas,
        int     $lojaId,
        int     $limite,
        bool    $casou
    ): void {
        // Marca pedido como turbo
        Redis::set("pedido_turbo:{$numeroPedido}", 1);

        // Remove da fila e salva posição
        $this->sairDaFila($motoboy->id, $lojaId);

        Redis::rpush("motoboy_pedidos:{$motoboy->id}", $numeroPedido);
        Redis::set("pedido_motoboy:{$numeroPedido}", $motoboy->id);
        Redis::lrem("pedidos_separacao:{$lojaId}", 0, $numeroPedido);

        foreach ($rotas as $rota) {
            Redis::set("rota_motoboy:{$rota['id']}", $motoboy->id);
            Redis::sadd("motoboy_rotas:{$motoboy->id}", $rota['id']);
        }

        Redis::set("motoboy_status_{$motoboy->id}", 'carregando');

        // Se casou → sem preferência. Se não casou → preferência definida pelo config
        if (!$casou) {
            Redis::set("motoboy_saiu_turbo_solo:{$motoboy->id}", 1);
        }
    }

    // Verifica se pedido é turbo ao entrar na fila
    // e se há turbo aguardando para casar com nota nova
    public function verificarTurboAguardando(int $lojaId, string $numeroPedidoNovo): void
    {
        // Verifica se é turbo — turbo não casa com turbo
        if ($this->isPedidoTurbo($lojaId, $numeroPedidoNovo)) return;

        // Busca motoboys com turbo aguardando casa
        $fila = Redis::lrange("fila_loja_{$lojaId}", 0, -1);

        foreach ($fila as $motoboyId) {
            $turboPendente = Redis::get("turbo_motoboy_reservado:{$motoboyId}");
            if (!$turboPendente) continue;

            // Verifica rota em comum
            $coordsNovo     = $this->coordenadas($lojaId, $numeroPedidoNovo);
            $coordsTurbo    = $this->coordenadas($lojaId, $turboPendente);

            if (!$coordsNovo || !$coordsTurbo) continue;

            $rotasNovo  = $this->rotasParaPedido($lojaId, $coordsNovo);
            $rotasTurbo = $this->rotasParaPedido($lojaId, $coordsTurbo);

            $rotasNovoIds  = collect($rotasNovo)->pluck('id')->toArray();
            $rotasTurboIds = collect($rotasTurbo)->pluck('id')->toArray();
            $temRotaComum  = !empty(array_intersect($rotasNovoIds, $rotasTurboIds));

            if ($temRotaComum) {
                // Casa! Cancela o timer de espera
                Redis::del("turbo_aguardando:{$turboPendente}");
                Redis::del("turbo_motoboy_reservado:{$motoboyId}");

                // Vincula nota nova ao motoboy
                $motoboy   = Motoboy::find($motoboyId);
                $loja      = Loja::find($lojaId);
                $limite    = $loja->configuracao->pedidos_por_rota ?? 1;

                $this->vincularPedidoMotoboy($numeroPedidoNovo, $motoboy, $rotasNovo, $lojaId, $limite);

                Log::info('[TURBO] Nota nova casou com turbo aguardando', [
                    'turbo'   => $turboPendente,
                    'novo'    => $numeroPedidoNovo,
                    'motoboy' => $motoboy->nome,
                ]);

                return;
            }
        }
    }
}
