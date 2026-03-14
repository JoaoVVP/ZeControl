@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold mb-0">Dashboard</h4>
        <span class="text-muted small">{{ now()->format('d/m/Y') }}</span>
    </div>

    {{-- Pedidos --}}
    <p class="text-muted text-uppercase fw-bold small mb-2">Pedidos</p>
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center"
                         style="width:52px;height:52px;background:#fff8cc;">
                        <i class="bi bi-bag fs-4" style="color:var(--ze-yellow-dark);"></i>
                    </div>
                    <div>
                        <div class="text-muted small">Total do Dia</div>
                        <div class="fw-bold fs-4">{{ $totalPedidosDia }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center"
                         style="width:52px;height:52px;background:#fff3e0;">
                        <i class="bi bi-box-seam fs-4" style="color:#f57c00;"></i>
                    </div>
                    <div>
                        <div class="text-muted small">Em Separação</div>
                        <div class="fw-bold fs-4">{{ $pedidosSeparacao }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center"
                         style="width:52px;height:52px;background:#e8ffe8;">
                        <i class="bi bi-bicycle fs-4" style="color:#198754;"></i>
                    </div>
                    <div>
                        <div class="text-muted small">Em Rota</div>
                        <div class="fw-bold fs-4">{{ $pedidosEmRota }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Motoboys --}}
    <p class="text-muted text-uppercase fw-bold small mb-2">Motoboys</p>
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center"
                         style="width:52px;height:52px;background:#e8f4ff;">
                        <i class="bi bi-people fs-4" style="color:#0d6efd;"></i>
                    </div>
                    <div>
                        <div class="text-muted small">Total</div>
                        <div class="fw-bold fs-4">{{ $totalMotoboys }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center"
                         style="width:52px;height:52px;background:#fff8cc;">
                        <i class="bi bi-hourglass-split fs-4" style="color:var(--ze-yellow-dark);"></i>
                    </div>
                    <div>
                        <div class="text-muted small">Na Fila</div>
                        <div class="fw-bold fs-4">{{ $motoboysFila->count() }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center"
                         style="width:52px;height:52px;background:#e8ffe8;">
                        <i class="bi bi-person-badge fs-4" style="color:#198754;"></i>
                    </div>
                    <div>
                        <div class="text-muted small">Em Rota</div>
                        <div class="fw-bold fs-4">{{ $motoboysEmRota }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        {{-- Fila atual --}}
        <div class="col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 pt-3">
                    <h6 class="fw-bold mb-0">Fila de Motoboys</h6>
                </div>
                <div class="card-body p-0">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Motoboy</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($filaDetalhada as $i => $motoboy)
                                <tr>
                                    <td class="text-muted">{{ $i + 1 }}º</td>
                                    <td>{{ $motoboy->nome ?? '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="2" class="text-center text-muted py-3">
                                        Nenhum motoboy na fila.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Motoboy x Pedido --}}
        <div class="col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 pt-3">
                    <h6 class="fw-bold mb-0">Pedidos em Rota</h6>
                </div>
                <div class="card-body p-0">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Pedido</th>
                                <th>Motoboy</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($pedidosAtivos as $pedido)
                                <tr>
                                    <td>{{ $pedido->numero_pedido }}</td>
                                    <td>{{ $pedido->motoboy->nome ?? '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="2" class="text-center text-muted py-3">
                                        Nenhum pedido em rota.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

@endsection