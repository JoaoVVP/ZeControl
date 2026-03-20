@extends('layouts.app')

@section('title', 'Configurações')

@section('content')

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold mb-0">Configurações</h4>
    </div>

    @if(session('sucesso'))
        <div class="alert alert-success py-2">{{ session('sucesso') }}</div>
    @endif

    <div class="row g-4">

        {{-- Perfil --}}
        <div class="col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 pt-3">
                    <h6 class="fw-bold mb-0"><i class="bi bi-person me-2"></i>Dados do Perfil</h6>
                </div>
                <div class="card-body">

                    @if($errors->has('nome') || $errors->has('email'))
                        <div class="alert alert-danger py-2 small">
                            <ul class="mb-0">
                                @foreach($errors->only(['nome', 'email']) as $erro)
                                    <li>{{ $erro }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ auth()->user()->perfil === 'admin' ? route('admin.configuracoes.perfil') : route('funcionario.configuracoes.perfil') }}">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label class="form-label">Nome</label>
                            <input type="text"
                                   name="nome"
                                   class="form-control"
                                   value="{{ old('nome', auth()->user()->nome) }}"
                                   required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email"
                                   name="email"
                                   class="form-control"
                                   value="{{ old('email', auth()->user()->email) }}"
                                   required>
                        </div>

                        <button type="submit" class="btn btn-ze">
                            <i class="bi bi-check-lg"></i> Salvar
                        </button>
                    </form>
                </div>
            </div>
        </div>

        {{-- Senha --}}
        <div class="col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 pt-3">
                    <h6 class="fw-bold mb-0"><i class="bi bi-lock me-2"></i>Alterar Senha</h6>
                </div>
                <div class="card-body">

                    @if($errors->has('senha_atual') || $errors->has('password'))
                        <div class="alert alert-danger py-2 small">
                            <ul class="mb-0">
                                @foreach($errors->only(['senha_atual', 'password']) as $erro)
                                    <li>{{ $erro }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ auth()->user()->perfil === 'admin' ? route('admin.configuracoes.senha') : route('funcionario.configuracoes.senha') }}">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label class="form-label">Senha Atual</label>
                            <input type="password"
                                   name="senha_atual"
                                   class="form-control"
                                   placeholder="••••••••"
                                   required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Nova Senha</label>
                            <input type="password"
                                   name="password"
                                   class="form-control"
                                   placeholder="Mínimo 6 caracteres"
                                   required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Confirmar Nova Senha</label>
                            <input type="password"
                                   name="password_confirmation"
                                   class="form-control"
                                   placeholder="Repita a nova senha"
                                   required>
                        </div>

                        <button type="submit" class="btn btn-ze">
                            <i class="bi bi-check-lg"></i> Alterar Senha
                        </button>
                    </form>
                </div>
            </div>
        </div>

    </div>

@endsection