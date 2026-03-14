<div id="sidebar">
    <div class="sidebar-brand">
        <span>🛵 ZeControl</span>
    </div>

    <nav class="mt-3">
        @if(auth()->user()->perfil === 'admin')

            <p class="menu-section">Sistema</p>
            <a href="{{ route('admin.dashboard') }}"
            class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                <i class="bi bi-speedometer2"></i> Dashboard
            </a>
            <a href="{{ route('admin.lojas.index') }}"
            class="nav-link {{ request()->routeIs('admin.lojas.*') ? 'active' : '' }}">
                <i class="bi bi-shop"></i> Lojas
            </a>
            <a href="{{ route('admin.usuarios.index') }}"
            class="nav-link {{ request()->routeIs('admin.usuarios.*') ? 'active' : '' }}">
                <i class="bi bi-people"></i> Funcionários
            </a>

        @else

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
                <i class="bi bi-geo-alt-fill"></i> Rotas
            </a>

            <p class="menu-section">Sistema</p>
            <a href="{{ route('configuracoes') }}"
               class="nav-link {{ request()->routeIs('configuracoes') ? 'active' : '' }}">
                <i class="bi bi-gear"></i> Configurações
            </a>

        @endif
    </nav>

    <div class="mt-auto p-3" style="position: absolute; bottom: 0; width: 100%;">
        <hr style="border-color: #ffffff15;">
        <form action="{{ route('logout') }}" method="POST">
            @csrf
            <button type="submit" class="nav-link text-danger w-100 border-0 bg-transparent text-start">
                <i class="bi bi-box-arrow-left"></i> Sair
            </button>
        </form>
    </div>
</div>