<?php

namespace App\Services;

use App\Models\Loja;
use App\Models\Motoboy;
use App\Models\Rota;
use Illuminate\Support\Facades\Redis;

class PedidoService
{
    // Salva pedido no Redis (sem TTL)
    public function salvar(int $lojaId, string $numeroPedido, array $payload): void
    {
        Redis::set("pedido:{$lojaId}:{$numeroPedido}", json_encode($payload));
        Redis::rpush("pedidos_separacao:{$lojaId}", $numeroPedido);
    }

    // Busca pedido
    public function buscar(int $lojaId, string $numeroPedido): ?array
    {
        $data = Redis::get("pedido:{$lojaId}:{$numeroPedido}");
        return $data ? json_decode($data, true) : null;
    }

    // Remove pedido do Redis
    public function esquecer(int $lojaId, string $numeroPedido): void
    {
        Redis::del("pedido:{$lojaId}:{$numeroPedido}");
        Redis::lrem("pedidos_separacao:{$lojaId}", 0, $numeroPedido);

        $motoboyId = Redis::get("pedido_motoboy:{$numeroPedido}");
        if ($motoboyId) {
            Redis::lrem("motoboy_pedidos:{$motoboyId}", 0, $numeroPedido);
            Redis::del("pedido_motoboy:{$numeroPedido}");

            // Verifica se motoboy ficou sem pedidos → disponivel
            $pedidosRestantes = Redis::llen("motoboy_pedidos:{$motoboyId}");
            if ($pedidosRestantes === 0) {
                Redis::set("motoboy_status_{$motoboyId}", 'disponivel');
                // Libera rotas do motoboy
                $this->liberarRotasMotoboy((int) $motoboyId, $lojaId);
            }
        }
    }

    // Coordenadas do pedido
    public function coordenadas(int $lojaId, string $numeroPedido): ?array
    {
        $payload = $this->buscar($lojaId, $numeroPedido);
        if (!$payload) return null;

        return [
            'lat' => $payload['delivery']['deliveryAddress']['coordinates']['latitude'],
            'lng' => $payload['delivery']['deliveryAddress']['coordinates']['longitude'],
        ];
    }

    // Lista pedidos em separação
    public function emSeparacao(int $lojaId): array
    {
        $ids = Redis::lrange("pedidos_separacao:{$lojaId}", 0, -1);
        return collect($ids)
            ->map(fn($id) => $this->buscar($lojaId, $id))
            ->filter()
            ->values()
            ->toArray();
    }

    // Verifica em quais rotas o pedido está (point in polygon)
    public function rotasParaPedido(int $lojaId, array $coordenadas): array
    {
        $rotas = Rota::where('loja_id', $lojaId)->where('ativo', true)->get();

        return $rotas->filter(function ($rota) use ($coordenadas) {
            return $this->pontoNoPoligono($coordenadas, $rota->coordenadas);
        })->values()->toArray();
    }

    // Associa pedido ao motoboy
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
                        $this->vincularPedidoMotoboy($numeroPedido, $motoboy, $rotasDoPedido);
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

        $this->vincularPedidoMotoboy($numeroPedido, $motoboy, $rotasDoPedido);
        Redis::set("motoboy_status_{$motoboy->id}", 'em_rota');

        return $motoboy;
    }

    // Vincula pedido ao motoboy e marca as rotas
    private function vincularPedidoMotoboy(string $numeroPedido, Motoboy $motoboy, array $rotas): void
    {
        Redis::rpush("motoboy_pedidos:{$motoboy->id}", $numeroPedido);
        Redis::set("pedido_motoboy:{$numeroPedido}", $motoboy->id);
        Redis::lrem("pedidos_separacao:{$motoboy->loja_id}", 0, $numeroPedido);

        // Marca todas as rotas do pedido para esse motoboy
        foreach ($rotas as $rota) {
            Redis::set("rota_motoboy:{$rota['id']}", $motoboy->id);
        }

        // Salva quais rotas o motoboy está cobrindo
        foreach ($rotas as $rota) {
            Redis::sadd("motoboy_rotas:{$motoboy->id}", $rota['id']);
        }
    }

    // Libera rotas quando motoboy termina
    private function liberarRotasMotoboy(int $motoboyId, int $lojaId): void
    {
        $rotaIds = Redis::smembers("motoboy_rotas:{$motoboyId}");
        foreach ($rotaIds as $rotaId) {
            Redis::del("rota_motoboy:{$rotaId}");
        }
        Redis::del("motoboy_rotas:{$motoboyId}");
    }

    // Ray casting — ponto dentro do polígono
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