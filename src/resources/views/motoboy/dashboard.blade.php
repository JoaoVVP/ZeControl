@extends('layouts.app')

@section('title', 'Início')

@section('content')

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold mb-0">Olá, {{ auth()->user()->nome }}!</h4>
        <span class="text-muted small">{{ now()->format('d/m/Y') }}</span>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body text-center py-5">
            <i class="bi bi-person-badge fs-1" style="color:var(--ze-yellow);"></i>
            <h5 class="mt-3 fw-bold">Pronto para trabalhar?</h5>
            <p class="text-muted">Clique no botão abaixo para entrar na fila de entregas.</p>
            <button class="btn btn-ze mt-2" id="btn-fila">
                <i class="bi bi-play-fill"></i> Entrar na Fila
            </button>
        </div>
    </div>

@endsection