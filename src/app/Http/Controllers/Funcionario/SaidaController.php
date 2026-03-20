<?php

namespace App\Http\Controllers\Funcionario;

use App\Http\Controllers\Controller;
use App\Models\Motoboy;
use App\Services\PedidoService;
use Illuminate\Support\Facades\Redis;

class SaidaController extends Controller
{
    public function index(PedidoService $pedidoService)
    {
        $lojaId  = auth()->user()->loja_id;
        $motoboys = Motoboy::where('loja_id', $lojaId)->get();

        $dados = $motoboys->map(function ($motoboy) use ($lojaId, $pedidoService) {
            $status    = Redis::get("motoboy_status_{$motoboy->id}") ?? 'inativo';
            $pedidoIds = Redis::lrange("motoboy_pedidos:{$motoboy->id}", 0, -1);

            $pedidos = collect($pedidoIds)->map(function ($numeroPedido) use ($lojaId, $pedidoService) {
                $payload = $pedidoService->buscar($lojaId, $numeroPedido);
                if (!$payload) return null;

                return [
                    'numero_pedido' => $numeroPedido,
                    'cliente'       => $payload['customer']['name'] ?? '-',
                    'endereco'      => $payload['delivery']['deliveryAddress']['formattedAddress'] ?? '-',
                    'total'         => $payload['total']['orderAmount']['value'] ?? '-',
                ];
            })->filter()->values();

            return [
                'motoboy' => $motoboy,
                'status'  => $status,
                'pedidos' => $pedidos,
            ];
        })->filter(fn($d) => $d['status'] !== 'inativo')->values();

        return view('funcionario.saidas.index', compact('dados'));
    }
}