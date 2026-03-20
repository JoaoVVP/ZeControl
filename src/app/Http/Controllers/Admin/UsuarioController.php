<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Loja;
use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UsuarioController extends Controller
{
    public function index()
    {
        $usuarios = Usuario::with('loja')
                           ->where('perfil', 'funcionario')
                           ->latest()
                           ->paginate(10);

        return view('admin.usuarios.index', compact('usuarios'));
    }

    public function create()
    {
        $lojas = Loja::orderBy('nome')->get();
        return view('admin.usuarios.create', compact('lojas'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'loja_id'  => 'required|exists:lojas,id',
            'nome'     => 'required|string|max:255',
            'email'    => 'required|email|unique:usuarios,email',
            'password' => 'required|min:6|confirmed',
        ]);

        Usuario::create([
            'loja_id'  => $request->loja_id,
            'nome'     => $request->nome,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
            'perfil'   => 'funcionario',
            'ativo'    => true,
        ]);

        return redirect()->route('admin.usuarios.index')
                         ->with('sucesso', 'Funcionário cadastrado com sucesso!');
    }

    public function destroy(Usuario $usuario)
    {
        $usuario->delete();
        return redirect()->route('admin.usuarios.index')
                         ->with('sucesso', 'Funcionário removido com sucesso!');
    }
}