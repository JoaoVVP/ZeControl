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

        @elseif(auth()->user()->perfil === 'funcionario')

            <p class="menu-section">Geral</p>
            <a href="{{ route('funcionario.dashboard') }}"
               class="nav-link {{ request()->routeIs('funcionario.dashboard') ? 'active' : '' }}">
                <i class="bi bi-speedometer2"></i> Dashboard
            </a>

            <p class="menu-section">Operação</p>
            <a href="{{ route('funcionario.motoboys.index') }}"
               class="nav-link {{ request()->routeIs('funcionario.motoboys.*') ? 'active' : '' }}">
                <i class="bi bi-person-badge"></i> Motoboys
            </a>
            <a href="{{ route('funcionario.saidas.index') }}"
               class="nav-link {{ request()->routeIs('funcionario.saidas.*') ? 'active' : '' }}">
                <i class="bi bi-box-arrow-right"></i> Saídas
            </a>
            <a href="{{ route('funcionario.rotas.index') }}"
               class="nav-link {{ request()->routeIs('funcionario.rotas.*') ? 'active' : '' }}">
                <i class="bi bi-geo-alt-fill"></i> Rotas
            </a>

            <p class="menu-section">Sistema</p>
            <a href="{{ route('funcionario.configuracoes') }}"
               class="nav-link {{ request()->routeIs('funcionario.configuracoes') ? 'active' : '' }}">
                <i class="bi bi-gear"></i> Configurações
            </a>

        @elseif(auth()->user()->perfil === 'motoboy')

            <p class="menu-section">Motoboy</p>
            <a href="{{ route('motoboy.dashboard') }}"
               class="nav-link {{ request()->routeIs('motoboy.dashboard') ? 'active' : '' }}">
                <i class="bi bi-speedometer2"></i> Início
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