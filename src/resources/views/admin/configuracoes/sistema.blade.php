@extends('layouts.app')

@section('title', 'Configurações do Sistema')

@section('content')

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold mb-0">Configurações do Sistema</h4>
    </div>

    @if(session('sucesso'))
        <div class="alert alert-success py-2">{{ session('sucesso') }}</div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger py-2 small">
            <ul class="mb-0">
                @foreach($errors->all() as $erro)
                    <li>{{ $erro }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row g-4">

        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 pt-3 d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold mb-0">
                        <i class="bi bi-whatsapp text-success me-2"></i>WhatsApp
                    </h6>
                    <div class="d-flex align-items-center gap-2">
                        <span class="text-muted small">Status:</span>
                        <span id="waha-status-badge" class="badge
                            {{ $wahaStatus === 'WORKING' ? 'bg-success' :
                               ($wahaStatus === 'SCAN_QR_CODE' ? 'bg-warning text-dark' : 'bg-danger') }}">
                            {{ $wahaStatus }}
                        </span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row g-4 align-items-center">

                        {{-- Botões --}}
                        <div class="col-md-4">
                            @if($wahaStatus === 'WORKING')
                                <div class="mb-3">
                                    <i class="bi bi-check-circle-fill text-success me-2"></i>
                                    <span class="text-success fw-bold">Conectado</span>
                                </div>
                                <form method="POST" action="{{ route('admin.configuracoes.waha.desconectar') }}">
                                    @csrf
                                    <button type="submit"
                                            class="btn btn-outline-danger"
                                            onclick="return confirm('Desconectar e excluir sessão do WhatsApp?')">
                                        <i class="bi bi-x-circle"></i> Desconectar
                                    </button>
                                </form>
                            @else
                                <p class="text-muted small mb-3">
                                    Clique em "Gerar QR Code" e escaneie com o WhatsApp para conectar.
                                </p>
                                <button type="button" class="btn btn-outline-success" onclick="gerarQrCode()">
                                    <i class="bi bi-qr-code"></i> Gerar QR Code
                                </button>
                            @endif
                        </div>

                        {{-- QR Code / Status --}}
                        <div class="col-md-8 text-center">

                            <div id="qr-loading" style="display:none;">
                                <div class="spinner-border text-success" role="status"></div>
                                <p class="text-muted small mt-2">Gerando sessão...</p>
                            </div>

                            <div id="qr-container" style="display:none;">
                                <p class="text-muted small mb-2">Escaneie com o WhatsApp</p>
                                <img id="qr-image" src="" alt="QR Code" style="max-width:250px;">
                                <p class="text-muted small mt-2">
                                    <i class="bi bi-arrow-repeat me-1"></i>
                                    <span id="qr-timer">Atualizando em 30s...</span>
                                </p>
                            </div>

                            <div id="qr-connected" style="{{ $wahaStatus === 'WORKING' ? '' : 'display:none' }}">
                                <i class="bi bi-check-circle-fill text-success" style="font-size:4rem;"></i>
                                <p class="fw-bold text-success mt-2 mb-0">WhatsApp Conectado!</p>
                            </div>

                            <div id="qr-vazio" style="{{ $wahaStatus !== 'WORKING' ? '' : 'display:none' }}">
                                <i class="bi bi-whatsapp text-muted" style="font-size:4rem;"></i>
                                <p class="text-muted small mt-2">Nenhuma sessão ativa.</p>
                            </div>

                        </div>

                    </div>
                </div>
            </div>
        </div>

    </div>

@endsection

@push('scripts')
<script>
    let verificacaoInterval = null;
    let timerInterval       = null;
    let timerSegundos       = 30;

    function gerarQrCode() {
        document.getElementById('qr-vazio').style.display     = 'none';
        document.getElementById('qr-container').style.display = 'none';
        document.getElementById('qr-connected').style.display = 'none';
        document.getElementById('qr-loading').style.display   = '';

        fetch("{{ route('admin.configuracoes.qrcode') }}", {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json',
            }
        })
        .then(res => res.json())
        .then(data => {
            document.getElementById('qr-loading').style.display = 'none';

            if (data.sucesso) {
                setTimeout(() => {
                    carregarQrImagem();
                    verificarConexao();
                    iniciarTimerQr();
                }, 2000);
            } else {
                document.getElementById('qr-vazio').style.display = '';
                alert('Erro ao gerar QR Code. Tente novamente.');
            }
        })
        .catch(() => {
            document.getElementById('qr-loading').style.display = 'none';
            document.getElementById('qr-vazio').style.display   = '';
            alert('Erro ao conectar com o WAHA.');
        });
    }

    function carregarQrImagem() {
        const img = document.getElementById('qr-image');
        img.src   = "{{ route('admin.configuracoes.waha.qr') }}?t=" + Date.now();
        document.getElementById('qr-container').style.display = '';
    }

    function iniciarTimerQr() {
        timerSegundos = 30;
        clearInterval(timerInterval);

        timerInterval = setInterval(() => {
            timerSegundos--;
            document.getElementById('qr-timer').textContent = `Atualizando em ${timerSegundos}s...`;

            if (timerSegundos <= 0) {
                timerSegundos = 30;
                carregarQrImagem(); // Recarrega o QR Code
            }
        }, 1000);
    }

    function verificarConexao() {
        clearInterval(verificacaoInterval);

        verificacaoInterval = setInterval(() => {
            fetch("{{ route('admin.configuracoes.waha.status') }}")
                .then(res => res.json())
                .then(data => {
                    document.getElementById('waha-status-badge').textContent = data.status;

                    if (data.status === 'WORKING') {
                        clearInterval(verificacaoInterval);
                        clearInterval(timerInterval);
                        document.getElementById('qr-container').style.display  = 'none';
                        document.getElementById('qr-connected').style.display  = '';
                        document.getElementById('waha-status-badge').className = 'badge bg-success';
                    }
                });
        }, 3000);
    }
</script>
@endpush