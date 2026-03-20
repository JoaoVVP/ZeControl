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
        $lojaId = auth()->user()->loja_id;

        // Fila atual
        $filaIds       = Redis::lrange("fila_loja_{$lojaId}", 0, -1);
        $filaDetalhada = collect($filaIds)->map(function ($id) {
            $motoboy = Motoboy::find($id);
            return $motoboy ? [
                'id'   => $motoboy->id,
                'nome' => $motoboy->nome,
            ] : null;
        })->filter()->values();

        // Motoboys em rota
        $motoboysEmRota = Motoboy::where('loja_id', $lojaId)
            ->get()
            ->filter(fn($m) => Redis::get("motoboy_status_{$m->id}") === 'em_rota')
            ->map(function ($motoboy) {
                $pedidoId = Redis::get("motoboy_pedido_{$motoboy->id}");
                $pedido   = $pedidoId ? Pedido::find($pedidoId) : null;
                return [
                    'id'            => $motoboy->id,
                    'nome'          => $motoboy->nome,
                    'numero_pedido' => $pedido?->numero_pedido ?? '-',
                ];
            })->values();

        // Contadores pedidos
        $totalPedidosDia  = Pedido::where('loja_id', $lojaId)
                                  ->whereDate('created_at', today())
                                  ->count();
        $pedidosSeparacao = Pedido::where('loja_id', $lojaId)
                                  ->where('status', 'separacao')
                                  ->count();
        $pedidosEmRota    = Pedido::where('loja_id', $lojaId)
                                  ->where('status', 'em_rota')
                                  ->count();

        return response()->json([
            'fila'             => $filaDetalhada,
            'em_rota'          => $motoboysEmRota,
            'total_pedidos_dia' => $totalPedidosDia,
            'pedidos_separacao' => $pedidosSeparacao,
            'pedidos_em_rota'   => $pedidosEmRota,
            'total_fila'        => $filaDetalhada->count(),
            'total_em_rota'     => $motoboysEmRota->count(),
        ]);
    }
}