<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function index()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        $credenciais = $request->only('email', 'password');
        $lembrar     = $request->boolean('lembrar');

        if (!Auth::attempt($credenciais, $lembrar)) {
            return back()->withErrors([
                'email' => 'Email ou senha incorretos.',
            ]);
        }

        $request->session()->regenerate();

        $perfil = auth()->user()->perfil;

        return match($perfil) {
            'admin'                    => redirect()->route('admin.dashboard'),
            'dono', 'funcionario'      => redirect()->route('dashboard'),
            'motoboy'                  => redirect()->route('motoboy.dashboard'),
            default                    => redirect()->route('dashboard'),
        };
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }
}