<?php

namespace App\Http\Controllers\Motoboy;

use App\Http\Controllers\Controller;
use App\Models\Motoboy;
use Illuminate\Support\Facades\Redis;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $usuario  = auth()->user();
        $motoboy  = Motoboy::where('usuario_id', $usuario->id)->firstOrFail();
        $status   = Redis::get("motoboy_status_{$motoboy->id}") ?? 'inativo';
        $posicao  = $this->posicaoNaFila($motoboy);

        return view('motoboy.dashboard', compact('motoboy', 'status', 'posicao'));
    }

    public function sairFila()
    {
        $usuario = auth()->user();
        $motoboy = Motoboy::where('usuario_id', $usuario->id)->firstOrFail();
        $status  = Redis::get("motoboy_status_{$motoboy->id}") ?? 'inativo';

        // Só sai da fila se estiver aguardando
        if ($status === 'aguardando') {
            $fila = Redis::lrange("fila_loja_{$motoboy->loja_id}", 0, -1);
            $fila = array_filter($fila, fn($id) => (int)$id !== $motoboy->id);
            Redis::del("fila_loja_{$motoboy->loja_id}");
            foreach (array_values($fila) as $id) {
                Redis::rpush("fila_loja_{$motoboy->loja_id}", $id);
            }
            Redis::set("motoboy_status_{$motoboy->id}", 'inativo');
        }

        return redirect()->route('motoboy.dashboard');
    }

    private function posicaoNaFila(Motoboy $motoboy): ?int
    {
        $fila = Redis::lrange("fila_loja_{$motoboy->loja_id}", 0, -1);
        $pos  = array_search((string) $motoboy->id, $fila);
        return $pos !== false ? $pos + 1 : null;
    }

    public function entrarFila(Request $request)
    {
        $usuario = auth()->user();
        $motoboy = Motoboy::where('usuario_id', $usuario->id)->firstOrFail();
        $status  = Redis::get("motoboy_status_{$motoboy->id}") ?? 'inativo';

        // Busca localização da loja
        $configuracao = \App\Models\ConfiguracaoLoja::where('loja_id', $motoboy->loja_id)->first();

        if (!$configuracao || !$configuracao->loja_lat || !$configuracao->loja_lng) {
            return response()->json([
                'sucesso' => false,
                'mensagem' => 'Local da loja não configurado. Contate o administrador.',
            ], 422);
        }

        // Valida localização do motoboy
        $request->validate([
            'lat' => 'required|numeric',
            'lng' => 'required|numeric',
        ]);

        $distancia = $this->calcularDistancia(
            $request->lat, $request->lng,
            $configuracao->loja_lat, $configuracao->loja_lng
        );

        if ($distancia > 250) {
            return response()->json([
                'sucesso'   => false,
                'mensagem'  => "Você precisa estar na loja para entrar na fila. (distância: {$distancia}m)",
                'distancia' => $distancia,
            ], 422);
        }

        if (in_array($status, ['inativo', 'disponivel'])) {
            Redis::rpush("fila_loja_{$motoboy->loja_id}", $motoboy->id);
            Redis::set("motoboy_status_{$motoboy->id}", 'aguardando');
        }

        return response()->json(['sucesso' => true]);
    }

    private function calcularDistancia(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $raio = 6371000;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat/2) ** 2
        + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng/2) ** 2;

        return round(2 * $raio * asin(sqrt($a)));
    }
}