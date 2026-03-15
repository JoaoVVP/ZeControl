<?php

namespace App\Http\Controllers\Motoboy;

use App\Http\Controllers\Controller;
use App\Models\Motoboy;
use Illuminate\Support\Facades\Redis;

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

    public function entrarFila()
    {
        $usuario = auth()->user();
        $motoboy = Motoboy::where('usuario_id', $usuario->id)->firstOrFail();
        $status  = Redis::get("motoboy_status_{$motoboy->id}") ?? 'inativo';

        // Só entra na fila se estiver inativo ou disponivel
        if (in_array($status, ['inativo', 'disponivel'])) {
            Redis::rpush("fila_loja_{$motoboy->loja_id}", $motoboy->id);
            Redis::set("motoboy_status_{$motoboy->id}", 'aguardando');
        }

        return redirect()->route('motoboy.dashboard');
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
}