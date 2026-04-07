@extends('layouts.app')

@section('title', 'Início')

@section('content')

    {{-- Toast container --}}
    <div class="position-fixed top-0 end-0 p-3" style="z-index: 9999">
        <div id="toast" class="toast align-items-center border-0" role="alert">
            <div class="d-flex">
                <div class="toast-body fw-bold" id="toast-mensagem"></div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold mb-0">Olá, {{ $motoboy->nome }}!</h4>
        <span class="text-muted small">{{ now()->format('d/m/Y') }}</span>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body text-center py-5">

            @if($status === 'inativo')
                <i class="bi bi-moon-stars fs-1 text-secondary"></i>
                <h5 class="mt-3 fw-bold">Você está inativo</h5>
                <p class="text-muted">Entre na fila para começar a receber pedidos.</p>
                <button class="btn btn-ze mt-2" onclick="entrarNaFila()">
                    <i class="bi bi-play-fill"></i> Entrar na Fila
                </button>

            @elseif($status === 'aguardando')
                <i class="bi bi-hourglass-split fs-1" style="color:var(--ze-yellow);"></i>
                <h5 class="mt-3 fw-bold">Você está na fila</h5>
                <p class="text-muted">
                    @if($posicao)
                        Sua posição: <strong>{{ $posicao }}º</strong>
                    @else
                        Aguardando posição...
                    @endif
                </p>
                <form method="POST" action="{{ route('motoboy.fila.sair') }}">
                    @csrf
                    <button class="btn btn-outline-danger mt-2">
                        <i class="bi bi-x-lg"></i> Sair da Fila
                    </button>
                </form>

            @elseif($status === 'carregando')
                <i class="bi bi-box-seam fs-1 text-warning"></i>
                <h5 class="mt-3 fw-bold">Coletando pedidos!</h5>
                <p class="text-muted">Dirija-se ao balcão para retirar os pedidos.</p>

            @elseif($status === 'em_rota')
                <i class="bi bi-bicycle fs-1 text-success"></i>
                <h5 class="mt-3 fw-bold">Você está em rota!</h5>
                <p class="text-muted">Realize a entrega e volte para receber mais pedidos.</p>

            @elseif($status === 'disponivel')
                <i class="bi bi-check-circle fs-1 text-info"></i>
                <h5 class="mt-3 fw-bold">Entrega concluída!</h5>
                <p class="text-muted">Entre na fila novamente para receber mais pedidos.</p>
                <button class="btn btn-ze mt-2" onclick="entrarNaFila()">
                    <i class="bi bi-arrow-repeat"></i> Entrar na Fila Novamente
                </button>
            @endif

        </div>
    </div>

@endsection

@push('scripts')
<script>
    function mostrarToast(mensagem, tipo = 'danger') {
        const toast    = document.getElementById('toast');
        const toastMsg = document.getElementById('toast-mensagem');

        toast.classList.remove('bg-success', 'bg-danger', 'bg-warning', 'text-white');
        toast.classList.add(`bg-${tipo}`, 'text-white');
        toastMsg.textContent = mensagem;

        new bootstrap.Toast(toast, { delay: 4000 }).show();
    }

function entrarNaFila() {
    if (!navigator.geolocation) {
        mostrarToast('Seu dispositivo não suporta geolocalização.');
        return;
    }

    mostrarToast('Verificando sua localização...', 'warning');

    navigator.geolocation.getCurrentPosition(
        function(pos) {
            fetch("{{ route('motoboy.fila.entrar') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                },
                body: JSON.stringify({
                    lat: pos.coords.latitude,
                    lng: pos.coords.longitude,
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.sucesso) {
                    mostrarToast('Você entrou na fila!', 'success');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    mostrarToast(data.mensagem ?? 'Erro desconhecido.', 'danger');
                }
            })
            .catch(err => {
                console.error(err);
                mostrarToast('Erro de conexão: ' + err.message);
            });
        },
        function(erro) {
            const erros = {
                1: 'Permissão de localização negada.',
                2: 'Localização indisponível.',
                3: 'Tempo esgotado ao obter localização.',
            };
            mostrarToast(erros[erro.code] ?? 'Erro ao obter localização.');
        },
        { enableHighAccuracy: true, timeout: 10000 }
    );
}
</script>
@endpush