<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Loja;
use App\Models\Usuario;

class DashboardController extends Controller
{
    public function index()
    {
        $totalLojas        = Loja::count();
        $totalFuncionarios = Usuario::where('perfil', 'funcionario')->count();
        $totalMotoboys     = Usuario::where('perfil', 'motoboy')->count();
        $ultimasLojas      = Loja::latest()->take(5)->get();

        return view('admin.dashboard', compact(
            'totalLojas',
            'totalFuncionarios',
            'totalMotoboys',
            'ultimasLojas'
        ));
    }
}