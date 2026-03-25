<?php

namespace App\Http\Controllers\Funcionario;

use App\Http\Controllers\Controller;
use App\Models\Motoboy;
use Illuminate\Support\Facades\Redis;

class DashboardController extends Controller
{
    public function index()
    {
        $lojaId  = auth()->user()->loja_id;
        $motoboys = Motoboy::where('loja_id', $lojaId)->get();

        // Pedidos via Redis
        $pedidosSeparacao = Redis::lrange("pedidos_separacao:{$lojaId}", 0, -1);
        $pedidosEmRota    = $motoboys->flatMap(fn($m) =>
            Redis::lrange("motoboy_pedidos:{$m->id}", 0, -1)
        )->filter()->values();

        $totalPedidosSeparacao = count($pedidosSeparacao);
        $totalPedidosEmRota    = $pedidosEmRota->count();

        // Motoboys via Redis
        $totalMotoboys    = $motoboys->count();
        $motoboysFila     = collect(Redis::lrange("fila_loja_{$lojaId}", 0, -1));
        $totalNaFila      = $motoboysFila->count();
        $totalEmRota      = $motoboys->filter(fn($m) =>
            Redis::get("motoboy_status_{$m->id}") === 'em_rota'
        )->count();

        // Fila detalhada de motoboys
        $filaDetalhada = $motoboysFila->map(function ($id) use ($motoboys) {
            return $motoboys->firstWhere('id', (int) $id);
        })->filter()->values();

        // Fila detalhada de pedidos em separação
        $pedidosFilaDetalhada = collect($pedidosSeparacao)->map(function ($numeroPedido) use ($lojaId) {
            $payload = Redis::get("pedido:{$lojaId}:{$numeroPedido}");
            if (!$payload) return null;
            $data = json_decode($payload, true);
            return [
                'numero'  => $numeroPedido,
                'cliente' => $data['customer']['name'] ?? '-',
                'endereco' => $data['delivery']['deliveryAddress']['formattedAddress'] ?? '-',
            ];
        })->filter()->values();

        return view('funcionario.dashboard', compact(
            'totalPedidosSeparacao',
            'totalPedidosEmRota',
            'totalMotoboys',
            'totalNaFila',
            'totalEmRota',
            'filaDetalhada',
            'pedidosFilaDetalhada'
        ));
    }
}