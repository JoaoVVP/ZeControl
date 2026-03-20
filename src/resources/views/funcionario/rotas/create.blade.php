@extends('layouts.app')

@section('title', 'Nova Rota')

@section('content')

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold mb-0">Nova Rota</h4>
        <a href="{{ route('funcionario.rotas.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> Voltar
        </a>
    </div>

    @if($errors->any())
        <div class="alert alert-danger py-2 small mb-3">
            <ul class="mb-0">
                @foreach($errors->all() as $erro)
                    <li>{{ $erro }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Mapa --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-0 pt-3 d-flex justify-content-between align-items-center">
            <h6 class="fw-bold mb-0">Desenhe a zona de entrega</h6>
            <span class="text-muted small">Clique para adicionar pontos • Clique no primeiro ponto para fechar</span>
        </div>
        <div class="card-body p-0">
            <div id="mapa" style="height:500px;border-radius:0 0 8px 8px;cursor:crosshair;"></div>
        </div>
    </div>

    {{-- Formulário --}}
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ route('funcionario.rotas.store') }}" id="form-rota">
                @csrf

                <input type="hidden" name="cor" id="cor">
                <input type="hidden" name="coordenadas" id="coordenadas">

                <div class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label">Nome da Rota</label>
                        <input type="text"
                               name="nome"
                               class="form-control"
                               placeholder="Ex: Zona Norte"
                               value="{{ old('nome') }}"
                               required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small text-muted">Pontos desenhados</label>
                        <div class="fw-bold" id="contador-pontos">0 pontos</div>
                    </div>
                    <div class="col-md-3">
                        <div class="d-flex align-items-center gap-2">
                            <span class="text-muted small">Cor gerada:</span>
                            <div id="preview-cor" class="rounded-circle" style="width:24px;height:24px;"></div>
                        </div>
                    </div>
                    <div class="col-md-3 d-flex gap-2 justify-content-end">
                        <button type="button" class="btn btn-outline-danger" onclick="limparDesenho()">
                            <i class="bi bi-trash"></i> Limpar
                        </button>
                        <button type="submit" class="btn btn-ze" id="btn-salvar" disabled>
                            <i class="bi bi-check-lg"></i> Salvar Rota
                        </button>
                    </div>
                </div>

            </form>
        </div>
    </div>

@endsection

@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<style>
    #mapa { cursor: crosshair; }
</style>
@endpush

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
    // Cor aleatória gerada uma vez
    const corAleatoria = '#' + Math.floor(Math.random() * 0xFFFFFF).toString(16).padStart(6, '0');
    document.getElementById('cor').value        = corAleatoria;
    document.getElementById('preview-cor').style.background = corAleatoria;

    const map = L.map('mapa').setView([-15.7801, -47.9292], 5);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap'
    }).addTo(map);

    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(pos => {
            map.setView([pos.coords.latitude, pos.coords.longitude], 14);
        });
    }

    // Rotas existentes — apenas outline
    const rotasExistentes = @json($rotas->map(fn($r) => ['nome' => $r->nome, 'cor' => $r->cor, 'coordenadas' => $r->coordenadas]));
    rotasExistentes.forEach(rota => {
        if (rota.coordenadas && rota.coordenadas.length >= 3) {
            L.polygon(rota.coordenadas.map(c => [c.lat, c.lng]), {
                color: rota.cor,
                fillOpacity: 0,       // sem preenchimento
                weight: 2,
                dashArray: '6,4',
            }).addTo(map).bindTooltip(rota.nome);
        }
    });

    // Desenho
    let pontos     = [];
    let marcadores = [];
    let linhas     = [];
    let poligono   = null;

    const iconePonto = L.divIcon({
        className: '',
        html: `<div style="width:10px;height:10px;background:${corAleatoria};border:2px solid white;border-radius:50%;"></div>`,
        iconSize: [10, 10],
        iconAnchor: [5, 5],
    });

    const iconeInicio = L.divIcon({
        className: '',
        html: `<div style="width:14px;height:14px;background:${corAleatoria};border:2px solid white;border-radius:50%;box-shadow:0 0 0 3px white;" title="Clique para fechar"></div>`,
        iconSize: [14, 14],
        iconAnchor: [7, 7],
    });

    map.on('click', function(e) {
        if (poligono) return; // já fechado

        const { lat, lng } = e.latlng;

        // Verifica se clicou perto do primeiro ponto para fechar
        if (pontos.length >= 3) {
            const primeiro = pontos[0];
            const dist = map.distance([lat, lng], [primeiro.lat, primeiro.lng]);
            if (dist < 40) {
                fecharPoligono();
                return;
            }
        }

        pontos.push({ lat, lng });

        const isInicio = pontos.length === 1;
        const marker   = L.marker([lat, lng], { icon: isInicio ? iconeInicio : iconePonto }).addTo(map);
        marcadores.push(marker);

        if (pontos.length > 1) {
            const prev  = pontos[pontos.length - 2];
            const linha = L.polyline([[prev.lat, prev.lng], [lat, lng]], {
                color: corAleatoria,
                weight: 2,
            }).addTo(map);
            linhas.push(linha);
        }

        atualizarContador();
    });

    function fecharPoligono() {
        if (pontos.length < 3) return;

        linhas.forEach(l => map.removeLayer(l));
        marcadores.forEach(m => map.removeLayer(m));
        linhas     = [];
        marcadores = [];

        poligono = L.polygon(pontos.map(p => [p.lat, p.lng]), {
            color: corAleatoria,
            fillColor: corAleatoria,
            fillOpacity: 0.2,
            weight: 2,
        }).addTo(map);

        document.getElementById('coordenadas').value      = JSON.stringify(pontos);
        document.getElementById('btn-salvar').disabled    = false;
        atualizarContador();
    }

    function limparDesenho() {
        pontos = [];
        marcadores.forEach(m => map.removeLayer(m));
        linhas.forEach(l => map.removeLayer(l));
        marcadores = [];
        linhas     = [];
        if (poligono) { map.removeLayer(poligono); poligono = null; }
        document.getElementById('coordenadas').value   = '';
        document.getElementById('btn-salvar').disabled = true;
        atualizarContador();
    }

    function atualizarContador() {
        document.getElementById('contador-pontos').textContent = pontos.length + ' pontos';
    }
</script>
@endpush