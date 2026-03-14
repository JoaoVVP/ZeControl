@extends('layouts.app')

@section('title', 'Funcionários')

@section('content')

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold mb-0">Funcionários</h4>
        <a href="{{ route('admin.usuarios.create') }}" class="btn btn-ze">
            <i class="bi bi-plus-lg"></i> Novo Funcionário
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
                        <th>Email</th>
                        <th>Loja</th>
                        <th>Situação</th>
                        <th>Cadastro</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($usuarios as $usuario)
                        <tr>
                            <td class="text-muted small">{{ $usuario->id }}</td>
                            <td>{{ $usuario->nome }}</td>
                            <td>{{ $usuario->email }}</td>
                            <td>{{ $usuario->loja->nome ?? '-' }}</td>
                            <td>
                                @if($usuario->ativo)
                                    <span class="badge bg-success">Ativo</span>
                                @else
                                    <span class="badge bg-danger">Inativo</span>
                                @endif
                            </td>
                            <td>{{ $usuario->created_at->format('d/m/Y') }}</td>
                            <td class="text-end">
                                <form action="{{ route('admin.usuarios.destroy', $usuario) }}"
                                      method="POST"
                                      onsubmit="return confirm('Remover {{ $usuario->nome }}?')">
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
                            <td colspan="7" class="text-center text-muted py-3">
                                Nenhum funcionário cadastrado ainda.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($usuarios->hasPages())
            <div class="card-footer bg-white border-0">
                {{ $usuarios->links() }}
            </div>
        @endif
    </div>

@endsection