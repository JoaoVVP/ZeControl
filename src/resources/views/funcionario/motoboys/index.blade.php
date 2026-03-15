@extends('layouts.app')

@section('title', 'Motoboys')

@section('content')

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold mb-0">Motoboys</h4>
        <a href="{{ route('funcionario.motoboys.create') }}" class="btn btn-ze">
            <i class="bi bi-plus-lg"></i> Novo Motoboy
        </a>
    </div>

    @if(session('sucesso'))
        <div class="alert alert-success py-2">{{ session('sucesso') }}</div>
    @endif

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Nome</th>
                        <th>Telefone</th>
                        <th>Status</th>
                        <th>Cadastro</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($motoboys as $motoboy)
                        <tr>
                            <td class="text-muted small">{{ $motoboy->id }}</td>
                            <td>{{ $motoboy->nome }}</td>
                            <td>{{ $motoboy->telefone ?? '-' }}</td>
                            <td>
                                @php $status = $motoboy->status @endphp
                                @if($status === 'aguardando')
                                    <span class="badge bg-warning text-dark">Na Fila</span>
                                @elseif($status === 'em_rota')
                                    <span class="badge bg-success">Em Rota</span>
                                @elseif($status === 'disponivel')
                                    <span class="badge bg-info">Disponível</span>
                                @else
                                    <span class="badge bg-secondary">Inativo</span>
                                @endif
                            </td>
                            <td>{{ $motoboy->created_at->format('d/m/Y') }}</td>
                            <td class="text-end">
                                <form action="{{ route('funcionario.motoboys.destroy', $motoboy) }}"
                                      method="POST"
                                      onsubmit="return confirm('Remover {{ $motoboy->nome }}?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-3">
                                Nenhum motoboy cadastrado ainda.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($motoboys->hasPages())
            <div class="card-footer bg-white border-0">
                {{ $motoboys->links() }}
            </div>
        @endif
    </div>

@endsection