<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\LojaController;
use App\Http\Controllers\Admin\UsuarioController;
use App\Http\Controllers\Funcionario\DashboardController as FuncionarioDashboardController;
use App\Http\Controllers\Funcionario\MotoboyController;
use App\Http\Controllers\Motoboy\DashboardController as MotoboyDashboardController;
use App\Http\Controllers\Funcionario\FilaController;
use App\Http\Controllers\ConfiguracaoController;
use App\Http\Controllers\Funcionario\ConfiguracaoLojaController;
use App\Http\Controllers\Funcionario\RotaController;
use App\Http\Controllers\Funcionario\SaidaController;
use App\Http\Controllers\Admin\ConfiguracaoSistemaController;



// Auth
Route::get('/login', [LoginController::class, 'index'])->name('login')->middleware('guest');
Route::post('/login', [LoginController::class, 'login'])->name('login.submit');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

Route::middleware('auth')->group(function () {
    // Apenas em desenvolvimento
    if (app()->environment('local')) {
        Route::get('/mock/pedido/{loja_id}/{numero?}', function ($lojaId, $numero = '100000001') {
            \App\Jobs\ProcessarPedidoMockJob::dispatch($lojaId, $numero);
            return response()->json(['message' => 'Job disparado!', 'numero' => $numero]);
        })->middleware('auth')->name('mock.pedido');
    }

    // Admin
    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');
        Route::resource('lojas', LojaController::class);
        Route::resource('usuarios', UsuarioController::class);
        Route::get('/configuracoes', [ConfiguracaoController::class, 'index'])->name('configuracoes');
        Route::put('/configuracoes/perfil', [ConfiguracaoController::class, 'atualizarPerfil'])->name('configuracoes.perfil');
        Route::put('/configuracoes/senha', [ConfiguracaoController::class, 'atualizarSenha'])->name('configuracoes.senha');
        Route::get('/configuracoes', [ConfiguracaoSistemaController::class, 'index'])->name('configuracoes');
        Route::put('/configuracoes', [ConfiguracaoSistemaController::class, 'update'])->name('configuracoes.update');
        Route::post('/configuracoes/qrcode', [ConfiguracaoSistemaController::class, 'gerarQrCode'])->name('configuracoes.qrcode');
        Route::get('/configuracoes/waha/status', [ConfiguracaoSistemaController::class, 'statusWaha'])->name('configuracoes.waha.status');
        Route::post('/configuracoes/waha/desconectar', [ConfiguracaoSistemaController::class, 'desconectar'])->name('configuracoes.waha.desconectar');
        Route::get('/configuracoes/waha/qr', [ConfiguracaoSistemaController::class, 'qrCodeImagem'])->name('configuracoes.waha.qr');
    });

    // Funcionário
    Route::prefix('funcionario')->name('funcionario.')->group(function () {
        Route::get('/dashboard', [FuncionarioDashboardController::class, 'index'])->name('dashboard');
        Route::resource('motoboys', MotoboyController::class);
        Route::get('/saidas', function () { return view('home'); })->name('saidas.index');
        Route::get('/rotas', function () { return view('home'); })->name('rotas.index');
        Route::get('/configuracoes', function () { return view('home'); })->name('configuracoes');
        Route::get('/fila/status', [FilaController::class, 'status'])->name('fila.status');
        Route::get('/configuracoes', [ConfiguracaoController::class, 'index'])->name('configuracoes');
        Route::put('/configuracoes/perfil', [ConfiguracaoController::class, 'atualizarPerfil'])->name('configuracoes.perfil');
        Route::put('/configuracoes/senha', [ConfiguracaoController::class, 'atualizarSenha'])->name('configuracoes.senha');
        Route::get('/configuracoes/loja', [ConfiguracaoLojaController::class, 'index'])->name('configuracoes.loja');
        Route::put('/configuracoes/loja', [ConfiguracaoLojaController::class, 'update'])->name('configuracoes.loja.update');
        Route::resource('rotas', RotaController::class);
        Route::post('/rotas/{rota}/toggle', [RotaController::class, 'toggleAtivo'])->name('rotas.toggle');
        Route::get('/saidas', [SaidaController::class, 'index'])->name('saidas.index');
    });

    Route::prefix('motoboy')->name('motoboy.')->group(function () {
        Route::get('/dashboard', [MotoboyDashboardController::class, 'index'])->name('dashboard');
        Route::post('/fila/entrar', [MotoboyDashboardController::class, 'entrarFila'])->name('fila.entrar');
        Route::post('/fila/sair', [MotoboyDashboardController::class, 'sairFila'])->name('fila.sair');
    });
});