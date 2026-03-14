<div id="sidebar">
    <div class="sidebar-brand">
        <span class="text-white fw-bold fs-5">ZeControl</span>
    </div>

    <nav class="mt-3">
        <p class="menu-section">Geral</p>
        <a href="{{ route('dashboard') }}"
           class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
            <i class="bi bi-speedometer2"></i> Dashboard
        </a>

        <p class="menu-section">Operação</p>
        <a href="{{ route('motoboys.index') }}"
           class="nav-link {{ request()->routeIs('motoboys.*') ? 'active' : '' }}">
            <i class="bi bi-person-badge"></i> Motoboys
        </a>
        <a href="{{ route('saidas.index') }}"
           class="nav-link {{ request()->routeIs('saidas.*') ? 'active' : '' }}">
            <i class="bi bi-box-arrow-right"></i> Saídas
        </a>
        <a href="{{ route('rotas.index') }}"
           class="nav-link {{ request()->routeIs('rotas.*') ? 'active' : '' }}">
            <i class=" bi-geo-alt-fill"></i> Rotas
        </a>

        <p class="menu-section">Sistema</p>
        <a href="{{ route('configuracoes') }}"
           class="nav-link {{ request()->routeIs('configuracoes') ? 'active' : '' }}">
            <i class="bi bi-gear"></i> Configurações
        </a>
    </nav>
    <div class="mt-auto p-3" style="position: absolute; bottom: 0; width: 100%;">
        <hr style="border-color: #ffffff15;">
        <a href="#" class="nav-link text-danger">
            <i class="bi bi-box-arrow-left"></i> Sair
        </a>
    </div>
</div>