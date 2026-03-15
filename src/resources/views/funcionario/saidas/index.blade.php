@extends('layouts.app')

@section('title', 'Saídas')

@section('content')

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold mb-0">Saídas</h4>
        <span class="text-muted small">Atualizado em {{ now()->format('H:i:s') }}</span>
    </div>

    @if($dados->isEmpty())
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center py-5">
                <i class="bi bi-bicycle fs-1 text-muted"></i>
                <p class="text-muted mt-3 mb-0">Nenhum motoboy ativo no momento.</p>
            </div>
        </div>
    @else
        <div class="row g-3">
            @foreach($dados as $item)
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm h-100">

                        {{-- Header do card --}}
                        <div class="card-header border-0 pt-3 pb-2"
                             style="background: var(--ze-dark);">
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="bi bi-person-badge text-warning fs-5"></i>
                                    <span class="text-white fw-bold">{{ $item['motoboy']->nome }}</span>
                                </div>
                                @if($item['status'] === 'em_rota')
                                    <span class="badge bg-success">Em Rota</span>
                                @elseif($item['status'] === 'aguardando')
                                    <span class="badge bg-warning text-dark">Na Fila</span>
                                @elseif($item['status'] === 'disponivel')
                                    <span class="badge bg-info">Disponível</span>
                                @endif
                            </div>
                            <div class="text-muted small mt-1" style="color:#adb5bd !important;">
                                <i class="bi bi-telephone me-1"></i>{{ $item['motoboy']->telefone ?? '-' }}
                            </div>
                        </div>

                        {{-- Pedidos --}}
                        <div class="card-body p-0">
                            @if($item['pedidos']->isEmpty())
                                <div class="text-center text-muted py-4 small">
                                    Nenhum pedido associado.
                                </div>
                            @else
                                @foreach($item['pedidos'] as $i => $pedido)
                                    <div class="p-3 {{ $i < count($item['pedidos']) - 1 ? 'border-bottom' : '' }}">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <span class="badge mb-1"
                                                      style="background:var(--ze-yellow);color:var(--ze-dark);">
                                                    #{{ $pedido['numero_pedido'] }}
                                                </span>
                                                <div class="fw-bold small">{{ $pedido['cliente'] }}</div>
                                                <div class="text-muted small">
                                                    <i class="bi bi-geo-alt me-1"></i>{{ $pedido['endereco'] }}
                                                </div>
                                            </div>
                                            <div class="text-end">
                                                <span class="small fw-bold text-success">
                                                    R$ {{ $pedido['total'] }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            @endif
                        </div>

                        {{-- Footer --}}
                        <div class="card-footer bg-white border-0 pb-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="text-muted small">
                                    {{ count($item['pedidos']) }} pedido(s)
                                </span>
                            </div>
                        </div>

                    </div>
                </div>
            @endforeach
        </div>
    @endif

@endsection