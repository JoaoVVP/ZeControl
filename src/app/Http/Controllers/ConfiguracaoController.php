<?php

namespace App\Http\Controllers;

use App\Helpers\Formatter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class ConfiguracaoController extends Controller
{
    public function index()
    {
        return view('configuracoes.index');
    }

    public function atualizarPerfil(Request $request)
    {
        $request->validate([
            'nome'  => 'required|string|max:255',
            'email' => 'required|email|unique:usuarios,email,' . auth()->id(),
        ]);

        auth()->user()->update([
            'nome'  => Formatter::nome($request->nome),
            'email' => Formatter::email($request->email),
        ]);

        return back()->with('sucesso', 'Perfil atualizado com sucesso!');
    }

    public function atualizarSenha(Request $request)
    {
        $request->validate([
            'senha_atual' => 'required',
            'password'    => 'required|min:6|confirmed',
        ]);

        if (!Hash::check($request->senha_atual, auth()->user()->password)) {
            return back()->withErrors(['senha_atual' => 'Senha atual incorreta.']);
        }

        auth()->user()->update([
            'password' => Hash::make($request->password),
        ]);

        return back()->with('sucesso', 'Senha atualizada com sucesso!');
    }
}