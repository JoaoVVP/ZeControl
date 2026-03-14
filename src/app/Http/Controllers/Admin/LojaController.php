<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Loja;
use Illuminate\Http\Request;

class LojaController extends Controller
{
    public function index()
    {
        $lojas = Loja::latest()->paginate(10);
        return view('admin.lojas.index', compact('lojas'));
    }

    public function create()
    {
        return view('admin.lojas.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'nome'     => 'required|string|max:255',
            'email'    => 'required|email|unique:lojas,email',
            'telefone' => 'nullable|string|max:20',
        ]);

        Loja::create($request->only('nome', 'email', 'telefone'));

        return redirect()->route('admin.lojas.index')
                         ->with('sucesso', 'Loja cadastrada com sucesso!');
    }

    public function destroy(Loja $loja)
    {
        $loja->delete();
        return redirect()->route('admin.lojas.index')
                         ->with('sucesso', 'Loja removida com sucesso!');
    }
}