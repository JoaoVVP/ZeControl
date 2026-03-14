<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\LojaController;
use App\Http\Controllers\Admin\UsuarioController;
use App\Http\Controllers\Admin\MotoboyAdminController;

// Auth
Route::get('/login', [LoginController::class, 'index'])->name('login')->middleware('guest');
Route::post('/login', [LoginController::class, 'login'])->name('login.submit');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// Sistema — protegido por auth
Route::middleware('auth')->group(function () {

    // Admin do sistema
    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
        Route::resource('lojas', LojaController::class);
        Route::resource('usuarios', UsuarioController::class);
        Route::get('/motoboys', [MotoboyAdminController::class, 'index'])->name('motoboys.index');
    });

    // Loja (dono + funcionario)
    Route::get('/', function () { return view('home'); })->name('dashboard');
    Route::get('/motoboys', function () { return view('home'); })->name('motoboys.index');
    Route::get('/saidas', function () { return view('home'); })->name('saidas.index');
    Route::get('/rotas', function () { return view('home'); })->name('rotas.index');
    Route::get('/configuracoes', function () { return view('home'); })->name('configuracoes');

    // Motoboy
    Route::prefix('motoboy')->name('motoboy.')->group(function () {
        Route::get('/dashboard', function () { return view('home'); })->name('dashboard');
    });

});