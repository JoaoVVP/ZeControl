<header id="header">
    <div class="d-flex align-items-center gap-2">
        <i class="bi bi-shop text-warning"></i>
        <span class="text-muted small">Loja:</span>
        <span class="fw-bold">Zé Itaipu</span>
    </div>

    <div class="d-flex align-items-center gap-3">
        <span class="text-muted small d-none d-md-block">Sistema</span>

        <div class="d-flex align-items-center gap-2">
            <span class="small" id="status-label">Ativo</span>
            <div class="form-check form-switch mb-0">
                <input class="form-check-input" type="checkbox" id="toggle-sistema" checked>
            </div>
        </div>
    </div>
</header>

<script>
    const toggle = document.getElementById('toggle-sistema');
    const label = document.getElementById('status-label');

    toggle.addEventListener('change', function () {
        if (this.checked) {
            label.textContent = 'Ativo';
            label.classList.remove('text-danger');
            label.classList.add('text-success');
        } else {
            label.textContent = 'Inativo';
            label.classList.remove('text-success');
            label.classList.add('text-danger');
        }
    });
</script>