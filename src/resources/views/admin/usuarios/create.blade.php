@extends('layouts.app')

@section('title', 'Novo Funcionário')

@section('content')

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold mb-0">Novo Funcionário</h4>
        <a href="{{ route('admin.usuarios.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> Voltar
        </a>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">

            <form method="POST" action="{{ route('admin.usuarios.store') }}">
                @csrf

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Nome</label>
                        <input type="text"
                               name="nome"
                               class="form-control {{ $errors->has('nome') ? 'is-invalid' : '' }}"
                               value="{{ old('nome') }}"
                               placeholder="Nome completo"
                               required>
                        @error('nome')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Email</label>
                        <input type="email"
                               name="email"
                               class="form-control {{ $errors->has('email') ? 'is-invalid' : '' }}"
                               value="{{ old('email') }}"
                               placeholder="funcionario@email.com"
                               required>
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Senha</label>
                        <input type="password"
                               name="password"
                               class="form-control {{ $errors->has('password') ? 'is-invalid' : '' }}"
                               placeholder="Mínimo 6 caracteres"
                               required>
                        @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Confirmar Senha</label>
                        <input type="password"
                               name="password_confirmation"
                               class="form-control"
                               placeholder="Repita a senha"
                               required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Loja</label>
                        <select name="loja_id"
                                class="form-select {{ $errors->has('loja_id') ? 'is-invalid' : '' }}"
                                required>
                            <option value="">Selecione a loja</option>
                            @foreach($lojas as $loja)
                                <option value="{{ $loja->id }}"
                                    {{ old('loja_id') == $loja->id ? 'selected' : '' }}>
                                    {{ $loja->nome }}
                                </option>
                            @endforeach
                        </select>
                        @error('loja_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-ze">
                        <i class="bi bi-check-lg"></i> Cadastrar Funcionário
                    </button>
                </div>

            </form>
        </div>
    </div>

@endsection