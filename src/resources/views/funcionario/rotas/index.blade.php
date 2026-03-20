@extends('layouts.app')

@section('title', 'Rotas')

@section('content')

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold mb-0">Rotas</h4>
        <a href="{{ route('funcionario.rotas.create') }}" class="btn btn-ze">
            <i class="bi bi-plus-lg"></i> Nova Rota
        </a>
    </div>

    @if(session('sucesso'))
        <div class="alert alert-success py-2">{{ session('sucesso') }}</div>
    @endif

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
</script>
@endpush