@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold mb-0">Dashboard</h4>
        <span class="text-muted small">{{ now()->format('d/m/Y H:i') }}</span>
    </div>

    {{-- Linha 1: Pedidos --}}
    <p class="text-muted text-uppercase fw-bold small mb-2">Pedidos</p>
    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center"
                         style="width:52px;height:52px;background:#fff3e0;">
                        <i class="bi bi-box-seam fs-4" style="color:#f57c00;"></i>
                    </div>
                    <div>
                        <div class="text-muted small">Em Separação</div>
                        <div class="fw-bold fs-4" id="total-separacao">{{ $totalPedidosSeparacao }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center"
                         style="width:52px;height:52px;background:#e8ffe8;">
                        <i class="bi bi-bicycle fs-4" style="color:#198754;"></i>
                    </div>
                    <div>
                        <div class="text-muted small">Em Rota</div>
                        <div class="fw-bold fs-4" id="total-pedidos-rota">{{ $totalPedidosEmRota }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Linha 2: Motoboys --}}
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
                        <div class="fw-bold fs-4" id="total-fila">{{ $totalNaFila }}</div>
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
                        <div class="fw-bold fs-4" id="total-motoboys-rota">{{ $totalEmRota }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Filas --}}
    <div class="row g-3">

        {{-- Fila de Motoboys --}}
        <div class="col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 pt-3 d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold mb-0">Fila de Motoboys</h6>
                    <span class="badge bg-warning text-dark" id="badge-fila">{{ $totalNaFila }}</span>
                </div>
                <div class="card-body p-0">
                    <div id="lista-fila-motoboys">
                        @forelse($filaDetalhada as $i => $motoboy)
                            <div class="d-flex align-items-center gap-3 p-3 {{ $i < count($filaDetalhada) - 1 ? 'border-bottom' : '' }}">
                                <span class="badge rounded-circle d-flex align-items-center justify-content-center fw-bold"
                                      style="width:32px;height:32px;background:var(--ze-yellow);color:var(--ze-dark);">
                                    {{ $i + 1 }}
                                </span>
                                <div>
                                    <div class="fw-bold small">{{ $motoboy->nome }}</div>
                                    <div class="text-muted small">
                                        <i class="bi bi-telephone me-1"></i>
                                        {{ $motoboy->telefone ?? '-' }}
                                    </div>
                                </div>
                                <span class="ms-auto badge bg-warning text-dark">Na Fila</span>
                            </div>
                        @empty
                            <div class="text-center text-muted py-4 small">
                                Nenhum motoboy na fila.
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        {{-- Fila de Pedidos --}}
        <div class="col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 pt-3 d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold mb-0">Pedidos em Separação</h6>
                    <span class="badge bg-warning text-dark" id="badge-separacao">{{ $totalPedidosSeparacao }}</span>
                </div>
                <div class="card-body p-0">
                    <div id="lista-pedidos-separacao">
                        @forelse($pedidosFilaDetalhada as $i => $pedido)
                            <div class="d-flex align-items-center gap-3 p-3 {{ $i < count($pedidosFilaDetalhada) - 1 ? 'border-bottom' : '' }}">
                                <span class="badge rounded-circle d-flex align-items-center justify-content-center fw-bold"
                                      style="width:32px;height:32px;background:var(--ze-dark);color:var(--ze-yellow);">
                                    {{ $i + 1 }}
                                </span>
                                <div>
                                    <div class="fw-bold small">#{{ $pedido['numero'] }}</div>
                                    <div class="text-muted small">{{ $pedido['cliente'] }}</div>
                                    <div class="text-muted small">
                                        <i class="bi bi-geo-alt me-1"></i>{{ $pedido['endereco'] }}
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="text-center text-muted py-4 small">
                                Nenhum pedido em separação.
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

    </div>

@endsection

@push('scripts')
<script>
    const pollingUrl = "{{ route('funcionario.fila.status') }}";

    function polling() {
        fetch(pollingUrl)
            .then(res => res.json())
            .then(data => {
                // Atualiza cards
                document.getElementById('total-separacao').textContent      = data.pedidos_separacao;
                document.getElementById('total-pedidos-rota').textContent   = data.pedidos_em_rota;
                document.getElementById('total-fila').textContent           = data.total_fila;
                document.getElementById('total-motoboys-rota').textContent  = data.total_em_rota;
                document.getElementById('badge-fila').textContent           = data.total_fila;
                document.getElementById('badge-separacao').textContent      = data.pedidos_separacao;

                // Atualiza fila de motoboys
                let htmlFila = '';
                if (data.fila.length === 0) {
                    htmlFila = '<div class="text-center text-muted py-4 small">Nenhum motoboy na fila.</div>';
                } else {
                    data.fila.forEach((m, i) => {
                        htmlFila += `
                            <div class="d-flex align-items-center gap-3 p-3 ${i < data.fila.length - 1 ? 'border-bottom' : ''}">
                                <span class="badge rounded-circle d-flex align-items-center justify-content-center fw-bold"
                                      style="width:32px;height:32px;background:var(--ze-yellow);color:var(--ze-dark);">
                                    ${i + 1}
                                </span>
                                <div>
                                    <div class="fw-bold small">${m.nome}</div>
                                </div>
                                <span class="ms-auto badge bg-warning text-dark">Na Fila</span>
                            </div>`;
                    });
                }
                document.getElementById('lista-fila-motoboys').innerHTML = htmlFila;

                // Atualiza fila de pedidos
                let htmlPedidos = '';
                if (data.pedidos_fila.length === 0) {
                    htmlPedidos = '<div class="text-center text-muted py-4 small">Nenhum pedido em separação.</div>';
                } else {
                    data.pedidos_fila.forEach((p, i) => {
                        htmlPedidos += `
                            <div class="d-flex align-items-center gap-3 p-3 ${i < data.pedidos_fila.length - 1 ? 'border-bottom' : ''}">
                                <span class="badge rounded-circle d-flex align-items-center justify-content-center fw-bold"
                                      style="width:32px;height:32px;background:var(--ze-dark);color:var(--ze-yellow);">
                                    ${i + 1}
                                </span>
                                <div>
                                    <div class="fw-bold small">#${p.numero}</div>
                                    <div class="text-muted small">${p.cliente}</div>
                                    <div class="text-muted small">${p.endereco}</div>
                                </div>
                            </div>`;
                    });
                }
                document.getElementById('lista-pedidos-separacao').innerHTML = htmlPedidos;
            })
            .catch(err => console.error('Polling error:', err));
    }

    setInterval(polling, 30000);
</script>
@endpush