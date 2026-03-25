@extends('layouts.app')

@section('title', 'Novo Motoboy')

@section('content')

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold mb-0">Novo Motoboy</h4>
        <a href="{{ route('funcionario.motoboys.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> Voltar
        </a>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">

            <form method="POST" action="{{ route('funcionario.motoboys.store') }}">
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
                        <label class="form-label">Telefone</label>
                        <input type="text"
                               name="telefone"
                               data-mask="telefone"
                               class="form-control {{ $errors->has('telefone') ? 'is-invalid' : '' }}"
                               value="{{ old('telefone') }}"
                               placeholder="(21) 99999-9999">
                        @error('telefone')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Email</label>
                        <input type="email"
                               name="email"
                               class="form-control {{ $errors->has('email') ? 'is-invalid' : '' }}"
                               value="{{ old('email') }}"
                               placeholder="motoboy@email.com"
                               required>
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-3">
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

                    <div class="col-md-3">
                        <label class="form-label">Confirmar Senha</label>
                        <input type="password"
                               name="password_confirmation"
                               class="form-control"
                               placeholder="Repita a senha"
                               required>
                    </div>
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-ze">
                        <i class="bi bi-check-lg"></i> Cadastrar Motoboy
                    </button>
                </div>

            </form>
        </div>
    </div>

@endsection