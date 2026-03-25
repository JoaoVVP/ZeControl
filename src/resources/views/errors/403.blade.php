<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ZeControl - Acesso Negado</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
    <style>
        body {
            background: var(--ze-dark);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
    </style>
</head>
<body>
    <div class="text-center text-white">
        <div class="mb-4" style="color:var(--ze-yellow);font-size:4rem;">🛵</div>
        <h1 class="fw-bold" style="color:var(--ze-yellow);">403</h1>
        <h4 class="mb-2">Acesso Negado</h4>
        <p class="text-muted mb-4">Você não tem permissão para acessar essa página.</p>
        <a href="{{ route('login') }}" class="btn btn-ze">
            <i class="bi bi-box-arrow-in-right me-2"></i>Voltar ao Login
        </a>
    </div>
</body>
</html>