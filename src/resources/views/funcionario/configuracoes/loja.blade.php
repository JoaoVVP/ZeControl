@extends('layouts.app')

@section('title', 'Configurações da Loja')

@section('content')

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold mb-0">Configurações da Loja</h4>
    </div>

    @if(session('sucesso'))
        <div class="alert alert-success py-2">{{ session('sucesso') }}</div>
    @endif

    <form method="POST" action="{{ route('funcionario.configuracoes.loja.update') }}">
        @csrf
        @method('PUT')

        <div class="row g-4">

            {{-- Credenciais Ze Delivery --}}
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-0 pt-3 d-flex justify-content-between align-items-center">
                        <h6 class="fw-bold mb-0"><i class="bi bi-plug me-2"></i>Integração Zé Delivery</h6>
                        @if($zeConfigurado)
                            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="editarZe()">
                                <i class="bi bi-pencil"></i> Editar
                            </button>
                        @endif
                    </div>
                    <div class="card-body">

                        @if($zeConfigurado)
                            {{-- Modo leitura --}}
                            <div id="ze-leitura">
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label text-muted small">Merchant ID</label>
                                        <p class="fw-bold mb-0">••••••••</p>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label text-muted small">Client ID</label>
                                        <p class="fw-bold mb-0">••••••••</p>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label text-muted small">Client Secret</label>
                                        <p class="fw-bold mb-0">••••••••</p>
                                    </div>
                                </div>
                                <p class="text-muted small mt-2 mb-0">
                                    <i class="bi bi-shield-check text-success"></i>
                                    Credenciais configuradas e criptografadas.
                                </p>
                            </div>

                            {{-- Modo edição --}}
                            <div id="ze-edicao" style="display:none">
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label">Merchant ID</label>
                                        <input type="text" name="ze_merchant_id" class="form-control" placeholder="ID do estabelecimento">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Client ID</label>
                                        <input type="text" name="ze_client_id" class="form-control" placeholder="Client ID">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Client Secret</label>
                                        <input type="password" name="ze_client_secret" class="form-control" placeholder="••••••••">
                                    </div>
                                </div>
                                <button type="button" class="btn btn-outline-secondary btn-sm mt-2" onclick="cancelarEdicaoZe()">
                                    <i class="bi bi-x"></i> Cancelar
                                </button>
                            </div>

                        @else
                            {{-- Nunca configurado --}}
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label">Merchant ID</label>
                                    <input type="text" name="ze_merchant_id" class="form-control" placeholder="ID do estabelecimento">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Client ID</label>
                                    <input type="text" name="ze_client_id" class="form-control" placeholder="Client ID">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Client Secret</label>
                                    <input type="password" name="ze_client_secret" class="form-control" placeholder="••••••••">
                                </div>
                            </div>
                        @endif

                    </div>
                </div>
            </div>

            {{-- Pedidos por rota --}}
            <div class="col-md-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white border-0 pt-3">
                        <h6 class="fw-bold mb-0"><i class="bi bi-box-seam me-2"></i>Rotas</h6>
                    </div>
                    <div class="card-body">
                        <label class="form-label">Pedidos por rota do motoboy</label>
                        <input type="number"
                               name="pedidos_por_rota"
                               class="form-control"
                               min="1"
                               max="10"
                               value="{{ old('pedidos_por_rota', $configuracao->pedidos_por_rota) }}"
                               required>
                        <div class="form-text">Quantidade máxima de pedidos que um motoboy carrega por saída.</div>
                    </div>
                </div>
            </div>

            {{-- Auto Start Route --}}
            <div class="col-md-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white border-0 pt-3 d-flex justify-content-between align-items-center">
                        <h6 class="fw-bold mb-0">
                            <i class="bi bi-play-circle me-2 text-success"></i>Start Route Automático
                        </h6>
                        <div class="form-check form-switch mb-0">
                            <input class="form-check-input"
                                   type="checkbox"
                                   name="auto_start_route"
                                   id="auto_start_route"
                                   {{ $configuracao->auto_start_route ? 'checked' : '' }}
                                   onchange="toggleStartRoute(this)">
                        </div>
                    </div>
                    <div class="card-body">
                        <p class="text-muted small mb-3">
                            O sistema chamará o <strong>startRoute</strong> automaticamente após X minutos da primeira nota.
                        </p>

                        <div id="start-route-config" style="{{ $configuracao->auto_start_route ? '' : 'display:none' }}">
                            <label class="form-label">Tempo para start automático (minutos)</label>
                            <input type="number"
                                   name="start_route_minutos"
                                   class="form-control"
                                   min="1"
                                   max="60"
                                   value="{{ old('start_route_minutos', $configuracao->start_route_minutos) }}"
                                   placeholder="Ex: 10">
                        </div>

                        <div id="start-route-desativado" style="{{ $configuracao->auto_start_route ? 'display:none' : '' }}">
                            <p class="text-muted small mb-0">Ative para configurar o tempo de start automático.</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Modo Emergência --}}
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-0 pt-3 d-flex justify-content-between align-items-center">
                        <h6 class="fw-bold mb-0">
                            <i class="bi bi-exclamation-triangle me-2 text-warning"></i>Modo Emergência
                        </h6>
                        <div class="form-check form-switch mb-0">
                            <input class="form-check-input"
                                   type="checkbox"
                                   name="modo_emergencia"
                                   id="modo_emergencia"
                                   {{ $configuracao->modo_emergencia ? 'checked' : '' }}>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <p class="text-muted small mb-0">
                                    Quando ativado, o sistema agrupa pedidos por <strong>ordem de saída + proximidade</strong> em vez de apenas ordem de saída se o gatilho for atingido.
                                </p>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Gatilho (pedidos acumulados)</label>
                                <input type="number"
                                       name="gatilho_emergencia"
                                       class="form-control"
                                       min="1"
                                       value="{{ old('gatilho_emergencia', $configuracao->gatilho_emergencia) }}"
                                       required>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Turbo --}}
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-0 pt-3">
                        <h6 class="fw-bold mb-0">
                            <i class="bi bi-lightning-charge text-warning me-2"></i>Configurações Turbo
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-4">
                            <div class="col-md-4">
                                <label class="form-label">Prazo para sair (minutos)</label>
                                <input type="number"
                                    name="turbo_prazo_minutos"
                                    class="form-control"
                                    min="1"
                                    value="{{ old('turbo_prazo_minutos', $configuracao->turbo_prazo_minutos) }}">
                                <div class="form-text">Tempo máximo para startRoute da turbo.</div>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Espera para casar (minutos)</label>
                                <input type="number"
                                    name="turbo_espera_casa_minutos"
                                    class="form-control"
                                    min="1"
                                    value="{{ old('turbo_espera_casa_minutos', $configuracao->turbo_espera_casa_minutos) }}">
                                <div class="form-text">Tempo de espera por nota comum para casar.</div>
                            </div>

                            <div class="col-md-4 d-flex flex-column gap-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <label class="form-label mb-0 small fw-bold">Turbo pode casar</label>
                                    </div>
                                    <div class="form-check form-switch mb-0">
                                        <input class="form-check-input"
                                            type="checkbox"
                                            name="turbo_casa"
                                            {{ $configuracao->turbo_casa ? 'checked' : '' }}>
                                    </div>
                                </div>

                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <label class="form-label mb-0 small fw-bold">Casar no modo emergência</label>
                                    </div>
                                    <div class="form-check form-switch mb-0">
                                        <input class="form-check-input"
                                            type="checkbox"
                                            name="turbo_casa_modo_emergencia"
                                            {{ $configuracao->turbo_casa_modo_emergencia ? 'checked' : '' }}>
                                    </div>
                                </div>

                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <label class="form-label mb-0 small fw-bold">Preferência na fila</label>
                                    </div>
                                    <div class="form-check form-switch mb-0">
                                        <input class="form-check-input"
                                            type="checkbox"
                                            name="turbo_preferencia"
                                            {{ $configuracao->turbo_preferencia ? 'checked' : '' }}>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Botão de Salvar --}}
            <div class="col-12 mt-4">
                <button type="submit" class="btn btn-ze px-4 py-2">
                    <i class="bi bi-check-lg"></i> Salvar Configurações
                </button>
            </div>

        </div> {{-- Fecha Row Principal --}}
    </form>

    {{-- Localização da Loja — FORA do form principal --}}
    <div class="row mt-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 pt-3 d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold mb-0">
                        <i class="bi bi-geo-alt-fill me-2 text-warning"></i>Local da Loja
                    </h6>
                    @if($configuracao->loja_lat && $configuracao->loja_lng)
                        <span class="badge bg-success">Configurado</span>
                    @else
                        <span class="badge bg-danger">Não configurado</span>
                    @endif
                </div>
                <div class="card-body">
                    @if($configuracao->loja_lat && $configuracao->loja_lng)
                        <div class="d-flex justify-content-between align-items-center">
                            <p class="text-muted small mb-0">
                                <i class="bi bi-check-circle text-success me-1"></i>
                                Local definido em: <strong>{{ $configuracao->loja_lat }}, {{ $configuracao->loja_lng }}</strong>
                            </p>
                            <form method="POST" action="{{ route('funcionario.configuracoes.loja.localizacao.excluir') }}"
                                  onsubmit="return confirm('Excluir local da loja? Os motoboys não conseguirão entrar na fila até um novo local ser definido.')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-outline-danger btn-sm">
                                    <i class="bi bi-trash"></i> Excluir Local
                                </button>
                            </form>
                        </div>
                    @else
                        <div class="alert alert-warning py-2 mb-0">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            Local da loja não configurado! Os motoboys não conseguirão entrar na fila.
                            <a href="{{ route('funcionario.rotas.index') }}" class="fw-bold ms-1 text-decoration-none">
                                Configurar agora →
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
<script>
    function toggleStartRoute(checkbox) {
        const config     = document.getElementById('start-route-config');
        const desativado = document.getElementById('start-route-desativado');
        if (checkbox.checked) {
            config.style.display     = '';
            desativado.style.display = 'none';
        } else {
            config.style.display     = 'none';
            desativado.style.display = '';
        }
    }

    function editarZe() {
        document.getElementById('ze-leitura').style.display = 'none';
        document.getElementById('ze-edicao').style.display  = '';
        document.querySelector('[onclick="editarZe()"]').style.display = 'none';
    }

    function cancelarEdicaoZe() {
        document.getElementById('ze-leitura').style.display = '';
        document.getElementById('ze-edicao').style.display  = 'none';
        document.querySelector('[onclick="editarZe()"]').style.display = '';
    }
</script>
@endpush