<?php

namespace App\Http\Controllers\Funcionario;

use App\Http\Controllers\Controller;
use App\Models\Motoboy;
use App\Models\Pedido;
use Illuminate\Support\Facades\Redis;

class FilaController extends Controller
{
    public function status()
    {
        $lojaId  = auth()->user()->loja_id;
        $motoboys = \App\Models\Motoboy::where('loja_id', $lojaId)->get();

        // Fila motoboys
        $filaIds       = Redis::lrange("fila_loja_{$lojaId}", 0, -1);
        $filaDetalhada = collect($filaIds)->map(function ($id) use ($motoboys) {
            $motoboy = $motoboys->firstWhere('id', (int) $id);
            return $motoboy ? ['id' => $motoboy->id, 'nome' => $motoboy->nome] : null;
        })->filter()->values();

        // Motoboys em rota
        $totalEmRota = $motoboys->filter(fn($m) =>
            Redis::get("motoboy_status_{$m->id}") === 'em_rota'
        )->count();

        // Pedidos em separação
        $pedidosSeparacaoIds = Redis::lrange("pedidos_separacao:{$lojaId}", 0, -1);
        $totalSeparacao      = count($pedidosSeparacaoIds);

        $pedidosFila = collect($pedidosSeparacaoIds)->map(function ($numeroPedido) use ($lojaId) {
            $payload = Redis::get("pedido:{$lojaId}:{$numeroPedido}");
            if (!$payload) return null;
            $data = json_decode($payload, true);
            return [
                'numero'   => $numeroPedido,
                'cliente'  => $data['customer']['name'] ?? '-',
                'endereco' => $data['delivery']['deliveryAddress']['formattedAddress'] ?? '-',
            ];
        })->filter()->values();

        // Pedidos em rota
        $totalPedidosRota = $motoboys->flatMap(fn($m) =>
            Redis::lrange("motoboy_pedidos:{$m->id}", 0, -1)
        )->filter()->count();

        return response()->json([
            'fila'              => $filaDetalhada,
            'total_fila'        => $filaDetalhada->count(),
            'total_em_rota'     => $totalEmRota,
            'pedidos_separacao' => $totalSeparacao,
            'pedidos_em_rota'   => $totalPedidosRota,
            'pedidos_fila'      => $pedidosFila,
        ]);
    }
}