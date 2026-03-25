<?php

namespace App\Http\Controllers\Funcionario;

use App\Http\Controllers\Controller;
use App\Models\Motoboy;
use App\Models\Usuario;
use App\Helpers\Formatter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redis;

class MotoboyController extends Controller
{
    public function index()
    {
        $lojaId   = auth()->user()->loja_id;
        $motoboys = Motoboy::where('loja_id', $lojaId)->latest()->paginate(10);

        $motoboys->each(function ($motoboy) {
            $motoboy->status         = Redis::get("motoboy_status_{$motoboy->id}") ?? 'inativo';
            $motoboy->telefone_exibicao = $motoboy->telefone
                ? Formatter::telefoneExibicao($motoboy->telefone)
                : '-';
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
            'nome'                 => 'required|string|max:255',
            'telefone'             => 'nullable|string',
            'email'                => 'required|email|unique:usuarios,email',
            'password'             => 'required|min:6|confirmed',
        ], [
            'email.unique' => 'Esse email já está sendo usado por outro usuário.',
        ]);

        $nomeFormatado    = Formatter::nome($request->nome);
        $telefoneFormatado = $request->telefone ? Formatter::telefoneBanco($request->telefone) : null;

        // Nome único por loja
        $nomeExiste = Motoboy::where('loja_id', $lojaId)
                            ->where('nome', $nomeFormatado)
                            ->exists();

        if ($nomeExiste) {
            return back()
                ->withInput()
                ->withErrors(['nome' => 'Já existe um motoboy com esse nome nessa loja.']);
        }

        // Telefone único por loja
        if ($telefoneFormatado) {
            $telefoneExiste = Motoboy::where('loja_id', $lojaId)
                                    ->where('telefone', $telefoneFormatado)
                                    ->exists();

            if ($telefoneExiste) {
                return back()
                    ->withInput()
                    ->withErrors(['telefone' => 'Esse telefone já está cadastrado para outro motoboy.']);
            }
        }

        $usuario = Usuario::create([
            'loja_id'  => $lojaId,
            'nome'     => $nomeFormatado,
            'email'    => Formatter::email($request->email),
            'password' => Hash::make($request->password),
            'perfil'   => 'motoboy',
            'ativo'    => true,
        ]);

        Motoboy::create([
            'loja_id'    => $lojaId,
            'usuario_id' => $usuario->id,
            'nome'       => $nomeFormatado,
            'telefone'   => $telefoneFormatado,
        ]);

        return redirect()->route('funcionario.motoboys.index')
                        ->with('sucesso', 'Motoboy cadastrado com sucesso!');
    }

    public function destroy(Motoboy $motoboy)
    {
        Redis::del("motoboy_status_{$motoboy->id}");
        $motoboy->usuario()->delete();
        $motoboy->delete();

        return redirect()->route('funcionario.motoboys.index')
                         ->with('sucesso', 'Motoboy removido com sucesso!');
    }
}