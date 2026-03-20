<?php

namespace App\Http\Controllers\Funcionario;

use App\Http\Controllers\Controller;
use App\Models\Motoboy;
use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redis;

class MotoboyController extends Controller
{
    public function index()
    {
        $lojaId  = auth()->user()->loja_id;
        $motoboys = Motoboy::where('loja_id', $lojaId)->latest()->paginate(10);

        // Adiciona status do Redis em cada motoboy
        $motoboys->each(function ($motoboy) {
            $motoboy->status = Redis::get("motoboy_status_{$motoboy->id}") ?? 'inativo';
        });

        return view('funcionario.motoboys.index', compact('motoboys'));
    }

    public function create()
    {
        return view('funcionario.motoboys.create');
    }

    public function store(Request $request)
    {
        $lojaId = auth()->user()->loja_id;

        $request->validate([
            'nome'                  => 'required|string|max:255',
            'telefone'              => 'nullable|string|max:20',
            'email'                 => 'required|email|unique:usuarios,email',
            'password'              => 'required|min:6|confirmed',
        ]);

        // Cria o usuário do motoboy
        $usuario = Usuario::create([
            'loja_id'  => $lojaId,
            'nome'     => $request->nome,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
            'perfil'   => 'motoboy',
            'ativo'    => true,
        ]);

        // Cria o motoboy vinculado ao usuário
        Motoboy::create([
            'loja_id'    => $lojaId,
            'usuario_id' => $usuario->id,
            'nome'       => $request->nome,
            'telefone'   => $request->telefone,
        ]);

        return redirect()->route('funcionario.motoboys.index')
                         ->with('sucesso', 'Motoboy cadastrado com sucesso!');
    }

    public function destroy(Motoboy $motoboy)
    {
        // Remove status do Redis
        Redis::del("motoboy_status_{$motoboy->id}");

        // Remove o usuário vinculado
        $motoboy->usuario()->delete();
        $motoboy->delete();

        return redirect()->route('funcionario.motoboys.index')
                         ->with('sucesso', 'Motoboy removido com sucesso!');
    }
}