<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('home');
})->name('dashboard');

Route::get('/motoboys', function () {
    return view('home');
})->name('motoboys.index');

Route::get('/saidas', function () {
    return view('home');
})->name('saidas.index');

Route::get('/configuracoes', function () {
    return view('home');
})->name('configuracoes');

Route::get('/rotas', function () {
    return view('home');
})->name('rotas.index');