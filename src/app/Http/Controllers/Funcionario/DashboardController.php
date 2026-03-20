<?php

namespace App\Http\Controllers\Funcionario;

use App\Http\Controllers\Controller;
use App\Models\Motoboy;
use App\Models\Pedido;
use Illuminate\Support\Facades\Redis;

class DashboardController extends Controller
{
    public function index()
    {
        $lojaId = auth()->user()->loja_id;

        // Pedidos
        $totalPedidosDia  = Pedido::where('loja_id', $lojaId)
                                  ->whereDate('created_at', today())
                                  ->count();
        $pedidosSeparacao = Pedido::where('loja_id', $lojaId)
                                  ->where('status', 'separacao')
                                  ->count();
        $pedidosEmRota    = Pedido::where('loja_id', $lojaId)
                                  ->where('status', 'em_rota')
                                  ->count();

        // Motoboys
        $motoboys      = Motoboy::where('loja_id', $lojaId)->get();
        $totalMotoboys = $motoboys->count();
        $motoboyIds    = $motoboys->pluck('id');

        $motoboysFila  = collect(Redis::lrange("fila_loja_{$lojaId}", 0, -1));

        $motoboysEmRota = $motoboyIds->filter(fn($id) =>
            Redis::get("motoboy_status_{$id}") === 'em_rota'
        )->count();

        $filaDetalhada = $motoboysFila->map(fn($id) => Motoboy::find($id));

        $pedidosAtivos = Pedido::where('loja_id', $lojaId)
                               ->where('status', 'em_rota')
                               ->get()
                               ->map(function ($pedido) {
                                   $motoboyId = Redis::get("pedido_motoboy_{$pedido->id}");
                                   $pedido->motoboy = $motoboyId ? Motoboy::find($motoboyId) : null;
                                   return $pedido;
                               });

        return view('funcionario.dashboard', compact(
            'totalPedidosDia',
            'pedidosSeparacao',
            'pedidosEmRota',
            'totalMotoboys',
            'motoboysFila',
            'motoboysEmRota',
            'filaDetalhada',
            'pedidosAtivos'
        ));
    }
}