@extends('layouts.app')

@section('title', 'Lojas')

@section('content')

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold mb-0">Lojas</h4>
        <a href="{{ route('admin.lojas.create') }}" class="btn btn-ze">
            <i class="bi bi-plus-lg"></i> Nova Loja
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
                        <th>Telefone</th>
                        <th>Cadastro</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($lojas as $loja)
                        <tr>
                            <td class="text-muted small">{{ $loja->id }}</td>
                            <td>{{ $loja->nome }}</td>
                            <td>{{ $loja->email }}</td>
                            <td>{{ $loja->telefone ?? '-' }}</td>
                            <td>{{ $loja->created_at->format('d/m/Y') }}</td>
                            <td class="text-end">
                                <form action="{{ route('admin.lojas.destroy', $loja) }}"
                                      method="POST"
                                      onsubmit="return confirm('Remover loja {{ $loja->nome }}?')">
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
                                Nenhuma loja cadastrada ainda.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($lojas->hasPages())
            <div class="card-footer bg-white border-0">
                {{ $lojas->links() }}
            </div>
        @endif
    </div>

@endsection