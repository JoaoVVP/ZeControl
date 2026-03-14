@extends('layouts.app')

@section('title', 'Nova Loja')

@section('content')

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold mb-0">Nova Loja</h4>
        <a href="{{ route('admin.lojas.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> Voltar
        </a>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">

            @if($errors->any())
                <div class="alert alert-danger py-2 small">
                    <ul class="mb-0">
                        @foreach($errors->all() as $erro)
                            <li>{{ $erro }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('admin.lojas.store') }}">
                @csrf

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Nome da Loja</label>
                        <input type="text"
                               name="nome"
                               class="form-control"
                               value="{{ old('nome') }}"
                               placeholder="Ex: Zé Itaipu"
                               required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Email</label>
                        <input type="email"
                               name="email"
                               class="form-control"
                               value="{{ old('email') }}"
                               placeholder="loja@email.com"
                               required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Telefone</label>
                        <input type="text"
                               name="telefone"
                               class="form-control"
                               value="{{ old('telefone') }}"
                               placeholder="(21) 99999-9999">
                    </div>
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-ze">
                        <i class="bi bi-check-lg"></i> Cadastrar Loja
                    </button>
                </div>

            </form>
        </div>
    </div>

@endsection