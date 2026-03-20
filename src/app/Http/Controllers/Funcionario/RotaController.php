<?php

namespace App\Http\Controllers\Funcionario;

use App\Http\Controllers\Controller;
use App\Models\Rota;
use Illuminate\Http\Request;

class RotaController extends Controller
{
    public function index()
    {
        $lojaId = auth()->user()->loja_id;
        $rotas  = Rota::where('loja_id', $lojaId)->latest()->get();

        return view('funcionario.rotas.index', compact('rotas'));
    }

    public function create()
    {
        $rotas = Rota::where('loja_id', auth()->user()->loja_id)->where('ativo', true)->get();
        return view('funcionario.rotas.create', compact('rotas'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nome'        => 'required|string|max:255',
            'cor'         => 'required|string',
            'coordenadas' => 'required|json',
        ]);

        $coordenadas = json_decode($request->coordenadas, true);

        if (count($coordenadas) < 3) {
            return back()->withErrors(['coordenadas' => 'Desenhe um polígono com pelo menos 3 pontos.']);
        }

        Rota::create([
            'loja_id'     => auth()->user()->loja_id,
            'nome'        => $request->nome,
            'cor'         => $request->cor,
            'coordenadas' => $coordenadas,
            'ativo'       => true,
        ]);

        return redirect()->route('funcionario.rotas.index')
                         ->with('sucesso', 'Rota criada com sucesso!');
    }

    public function destroy(Rota $rota)
    {
        $rota->delete();
        return redirect()->route('funcionario.rotas.index')
                         ->with('sucesso', 'Rota removida com sucesso!');
    }

    public function toggleAtivo(Rota $rota)
    {
        $rota->update(['ativo' => !$rota->ativo]);
        return back();
    }
}