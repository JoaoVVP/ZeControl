<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\LojaController;
use App\Http\Controllers\Admin\UsuarioController;
use App\Http\Controllers\Funcionario\DashboardController as FuncionarioDashboardController;
use App\Http\Controllers\Funcionario\MotoboyController;

// Auth
Route::get('/login', [LoginController::class, 'index'])->name('login')->middleware('guest');
Route::post('/login', [LoginController::class, 'login'])->name('login.submit');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

Route::middleware('auth')->group(function () {

    // Admin
    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');
        Route::resource('lojas', LojaController::class);
        Route::resource('usuarios', UsuarioController::class);
    });

    // Funcionário
    Route::prefix('funcionario')->name('funcionario.')->group(function () {
        Route::get('/dashboard', [FuncionarioDashboardController::class, 'index'])->name('dashboard');
        Route::resource('motoboys', MotoboyController::class);
        Route::get('/saidas', function () { return view('home'); })->name('saidas.index');
        Route::get('/rotas', function () { return view('home'); })->name('rotas.index');
        Route::get('/configuracoes', function () { return view('home'); })->name('configuracoes');
    });

    Route::prefix('motoboy')->name('motoboy.')->group(function () {
        Route::get('/dashboard', function () { return view('motoboy.dashboard'); })->name('dashboard');
    });
});