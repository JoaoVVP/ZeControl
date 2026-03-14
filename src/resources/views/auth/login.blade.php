<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ZeControl - Login</title>
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
        .login-box {
            background: var(--ze-dark-2);
            border-radius: 12px;
            padding: 40px;
            width: 100%;
            max-width: 400px;
        }
        .login-box .brand {
            color: var(--ze-yellow);
            font-size: 1.8rem;
            font-weight: bold;
            letter-spacing: 2px;
        }
        .form-control {
            background: var(--ze-dark-3);
            border: 1px solid #ffffff15;
            color: #fff;
        }
        .form-control:focus {
            background: var(--ze-dark-3);
            border-color: var(--ze-yellow);
            color: #fff;
            box-shadow: 0 0 0 0.2rem rgba(255, 210, 0, 0.15);
        }
        .form-control::placeholder { color: #ffffff40; }
        .btn-ze {
            background: var(--ze-yellow);
            color: var(--ze-dark);
            font-weight: bold;
            border: none;
            width: 100%;
            padding: 10px;
        }
        .btn-ze:hover {
            background: var(--ze-yellow-dark);
            color: var(--ze-dark);
        }
        label { color: var(--ze-gray); }
        .form-check-label { color: var(--ze-gray); }
    </style>
</head>
<body>

    <div class="login-box">
        <div class="text-center mb-4">
            <div class="brand">🛵 ZeControl</div>
            <p class="text-muted mt-1 small">Faça login para continuar</p>
        </div>

        @if ($errors->any())
            <div class="alert alert-danger py-2 small">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('login.submit') }}">
            @csrf

            <div class="mb-3">
                <label>Email</label>
                <input type="email"
                       name="email"
                       class="form-control mt-1"
                       placeholder="seu@email.com"
                       value="{{ old('email') }}"
                       required>
            </div>

            <div class="mb-3">
                <label>Senha</label>
                <input type="password"
                       name="password"
                       class="form-control mt-1"
                       placeholder="••••••••"
                       required>
            </div>

            <div class="mb-4">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="lembrar" id="lembrar">
                    <label class="form-check-label" for="lembrar">Lembrar de mim</label>
                </div>
            </div>

            <button type="submit" class="btn btn-ze">Entrar</button>
        </form>
    </div>

</body>
</html>