@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold mb-0">Dashboard</h4>
        <span class="text-muted small">Visão geral do sistema</span>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center"
                         style="width:52px;height:52px;background:#fff8cc;">
                        <i class="bi bi-shop fs-4" style="color:var(--ze-yellow-dark);"></i>
                    </div>
                    <div>
                        <div class="text-muted small">Total de Lojas</div>
                        <div class="fw-bold fs-4">{{ $totalLojas }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center"
                         style="width:52px;height:52px;background:#e8f4ff;">
                        <i class="bi bi-people fs-4" style="color:#0d6efd;"></i>
                    </div>
                    <div>
                        <div class="text-muted small">Total de Funcionários</div>
                        <div class="fw-bold fs-4">{{ $totalFuncionarios }}</div>
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
                        <div class="text-muted small">Total de Motoboys</div>
                        <div class="fw-bold fs-4">{{ $totalMotoboys }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-0 pt-3">
            <h6 class="fw-bold mb-0">Últimas Lojas Cadastradas</h6>
        </div>
        <div class="card-body p-0">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Nome</th>
                        <th>Email</th>
                        <th>Telefone</th>
                        <th>Cadastro</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($ultimasLojas as $loja)
                        <tr>
                            <td>{{ $loja->nome }}</td>
                            <td>{{ $loja->email }}</td>
                            <td>{{ $loja->telefone ?? '-' }}</td>
                            <td>{{ $loja->created_at->format('d/m/Y') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted py-3">
                                Nenhuma loja cadastrada ainda.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

@endsection