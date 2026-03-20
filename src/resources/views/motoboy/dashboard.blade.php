@extends('layouts.app')

@section('title', 'Início')

@section('content')

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold mb-0">Olá, {{ $motoboy->nome }}!</h4>
        <span class="text-muted small">{{ now()->format('d/m/Y') }}</span>
    </div>

    {{-- Status card --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body text-center py-4">

            @if($status === 'inativo')
                <i class="bi bi-moon-stars fs-1 text-secondary"></i>
                <h5 class="mt-3 fw-bold">Você está inativo</h5>
                <p class="text-muted">Entre na fila para começar a receber pedidos.</p>
                <form method="POST" action="{{ route('motoboy.fila.entrar') }}">
                    @csrf
                    <button class="btn btn-ze mt-2">
                        <i class="bi bi-play-fill"></i> Entrar na Fila
                    </button>
                </form>

            @elseif($status === 'aguardando')
                <i class="bi bi-hourglass-split fs-1" style="color:var(--ze-yellow);"></i>
                <h5 class="mt-3 fw-bold">Você está na fila</h5>
                <p class="text-muted">
                    @if($posicao)
                        Sua posição: <strong>{{ $posicao }}º</strong>
                    @else
                        Aguardando posição...
                    @endif
                </p>
                <form method="POST" action="{{ route('motoboy.fila.sair') }}">
                    @csrf
                    <button class="btn btn-outline-danger mt-2">
                        <i class="bi bi-x-lg"></i> Sair da Fila
                    </button>
                </form>

            @elseif($status === 'em_rota')
                <i class="bi bi-bicycle fs-1 text-success"></i>
                <h5 class="mt-3 fw-bold">Você está em rota!</h5>
                <p class="text-muted">Realize a entrega e volte para receber mais pedidos.</p>

            @elseif($status === 'disponivel')
                <i class="bi bi-check-circle fs-1 text-info"></i>
                <h5 class="mt-3 fw-bold">Entrega concluída!</h5>
                <p class="text-muted">Entre na fila novamente para receber mais pedidos.</p>
                <form method="POST" action="{{ route('motoboy.fila.entrar') }}">
                    @csrf
                    <button class="btn btn-ze mt-2">
                        <i class="bi bi-arrow-repeat"></i> Entrar na Fila Novamente
                    </button>
                </form>

            @endif

        </div>
    </div>

@endsection