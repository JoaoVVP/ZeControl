@extends('layouts.app')

@section('title', 'Rotas')

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
        <h4 class="fw-bold mb-0">Rotas</h4>
        <div class="d-flex gap-2">
            @if(!$configuracao->loja_lat || !$configuracao->loja_lng)
                <button class="btn btn-outline-warning" onclick="ativarModoLoja()">
                    <i class="bi bi-geo-alt-fill"></i> Definir Local da Loja
                </button>
            @else
                <button class="btn btn-outline-secondary" disabled>
                    <i class="bi bi-geo-alt-fill text-success"></i> Local da Loja Definido
                </button>
            @endif
            <a href="{{ route('funcionario.rotas.create') }}" class="btn btn-ze">
                <i class="bi bi-plus-lg"></i> Nova Rota
            </a>
        </div>
    </div>

    @if(session('sucesso'))
        <div class="alert alert-success py-2">{{ session('sucesso') }}</div>
    @endif

    @if(session('aviso'))
        <div class="alert alert-warning py-2">{{ session('aviso') }}</div>
    @endif

    {{-- Instrução modo loja --}}
    <div id="aviso-modo-loja" class="alert alert-warning py-2 mb-3" style="display:none;">
        <i class="bi bi-info-circle me-2"></i>
        Clique no mapa para definir o local da loja. Clique novamente para confirmar.
        <button class="btn btn-sm btn-outline-secondary ms-2" onclick="cancelarModoLoja()">Cancelar</button>
    </div>

    {{-- Mapa --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-0">
            <div id="mapa" style="height:500px;border-radius:8px;"></div>
        </div>
    </div>

    {{-- Lista --}}
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Cor</th>
                        <th>Nome</th>
                        <th>Pontos</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rotas as $rota)
                        <tr>
                            <td>
                                <div class="rounded-circle"
                                     style="width:24px;height:24px;background:{{ $rota->cor }};"></div>
                            </td>
                            <td>{{ $rota->nome }}</td>
                            <td>{{ count($rota->coordenadas) }} pontos</td>
                            <td>
                                <form action="{{ route('funcionario.rotas.toggle', $rota) }}" method="POST">
                                    @csrf
                                    <div class="form-check form-switch mb-0">
                                        <input class="form-check-input"
                                               type="checkbox"
                                               {{ $rota->ativo ? 'checked' : '' }}
                                               onchange="this.form.submit()">
                                    </div>
                                </form>
                            </td>
                            <td class="text-end">
                                <form action="{{ route('funcionario.rotas.destroy', $rota) }}"
                                      method="POST"
                                      onsubmit="return confirm('Remover rota {{ $rota->nome }}?')">
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
                            <td colspan="5" class="text-center text-muted py-3">
                                Nenhuma rota cadastrada ainda.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

@endsection

@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
@endpush

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
    const map = L.map('mapa').setView([-15.7801, -47.9292], 5);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap'
    }).addTo(map);

    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(pos => {
            map.setView([pos.coords.latitude, pos.coords.longitude], 14);
        });
    }

    // Renderiza rotas existentes
    const rotas = @json($rotas->map(fn($r) => ['nome' => $r->nome, 'cor' => $r->cor, 'coordenadas' => $r->coordenadas]));
    rotas.forEach(rota => {
        if (rota.coordenadas && rota.coordenadas.length >= 3) {
            L.polygon(rota.coordenadas.map(c => [c.lat, c.lng]), {
                color: rota.cor,
                fillColor: rota.cor,
                fillOpacity: 0.2,
                weight: 2,
            }).addTo(map).bindTooltip(rota.nome);
        }
    });

    // Local da loja existente
    @if($configuracao->loja_lat && $configuracao->loja_lng)
        const lojaIcon = L.divIcon({
            className: '',
            html: '<div style="width:20px;height:20px;background:#FFD200;border:3px solid #1a1a1a;border-radius:50%;box-shadow:0 0 6px rgba(0,0,0,0.5);"></div>',
            iconSize: [20, 20],
            iconAnchor: [10, 10],
        });

        L.marker([{{ $configuracao->loja_lat }}, {{ $configuracao->loja_lng }}], { icon: lojaIcon })
            .addTo(map)
            .bindTooltip('Local da Loja', { permanent: true, direction: 'top' });

        // Círculo de 250m
        L.circle([{{ $configuracao->loja_lat }}, {{ $configuracao->loja_lng }}], {
            radius: 250,
            color: '#FFD200',
            fillColor: '#FFD200',
            fillOpacity: 0.1,
            weight: 2,
            dashArray: '5,5',
        }).addTo(map);

        map.setView([{{ $configuracao->loja_lat }}, {{ $configuracao->loja_lng }}], 15);
    @endif

    // Modo definir local da loja
    let modoLoja      = false;
    let marcadorLoja  = null;
    let circuloLoja   = null;

    const lojaIconTemp = L.divIcon({
        className: '',
        html: '<div style="width:20px;height:20px;background:#FFD200;border:3px solid #1a1a1a;border-radius:50%;box-shadow:0 0 6px rgba(0,0,0,0.5);"></div>',
        iconSize: [20, 20],
        iconAnchor: [10, 10],
    });

    function ativarModoLoja() {
        modoLoja = true;
        document.getElementById('aviso-modo-loja').style.display = '';
        map.getContainer().style.cursor = 'crosshair';
    }

    function cancelarModoLoja() {
        modoLoja = false;
        document.getElementById('aviso-modo-loja').style.display = 'none';
        map.getContainer().style.cursor = '';
        if (marcadorLoja) { map.removeLayer(marcadorLoja); marcadorLoja = null; }
        if (circuloLoja)  { map.removeLayer(circuloLoja);  circuloLoja  = null; }
    }

    map.on('click', function(e) {
        if (!modoLoja) return;

        const { lat, lng } = e.latlng;

        // Remove anterior
        if (marcadorLoja) map.removeLayer(marcadorLoja);
        if (circuloLoja)  map.removeLayer(circuloLoja);

        // Adiciona novo marcador
        marcadorLoja = L.marker([lat, lng], { icon: lojaIconTemp })
            .addTo(map)
            .bindTooltip('Local da Loja', { permanent: true, direction: 'top' });

        // Círculo de 250m
        circuloLoja = L.circle([lat, lng], {
            radius: 250,
            color: '#FFD200',
            fillColor: '#FFD200',
            fillOpacity: 0.1,
            weight: 2,
            dashArray: '5,5',
        }).addTo(map);

        // Confirma salvamento
        if (confirm('Definir este local como a loja?')) {
            fetch("{{ route('funcionario.configuracoes.loja.localizacao') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                },
                body: JSON.stringify({ lat, lng })
            })
            .then(res => res.json())
            .then(data => {
                if (data.sucesso) {
                    mostrarToast('Local da loja salvo com sucesso!', 'success');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    mostrarToast('Erro ao salvar local da loja.', 'danger');
                }
            });

            modoLoja = false;
            document.getElementById('aviso-modo-loja').style.display = 'none';
            map.getContainer().style.cursor = '';
        }
    });

    function mostrarToast(mensagem, tipo = 'danger') {
        const toast    = document.getElementById('toast');
        const toastMsg = document.getElementById('toast-mensagem');
        toast.classList.remove('bg-success', 'bg-danger', 'bg-warning', 'text-white');
        toast.classList.add(`bg-${tipo}`, 'text-white');
        toastMsg.textContent = mensagem;
        new bootstrap.Toast(toast, { delay: 4000 }).show();
    }
</script>
@endpush