<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Masuk — INAweb Studio</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --page-bg: #fff8f7;
            --surface-strong: #ffffff;
            --border-soft: rgba(148, 163, 184, .18);
            --border-mid: rgba(148, 163, 184, .34);
            --text-main: #0f172a;
            --text-muted: #475569;
            --text-faint: #94a3b8;
            --brand: #dc2626;
            --brand-dark: #991b1b;
            --brand-soft: #fee2e2;
            --shadow-soft: 0 18px 45px rgba(15, 23, 42, .08);
            --shadow-tight: 0 8px 24px rgba(15, 23, 42, .06);
            --r-lg: 24px;
            --font: "Plus Jakarta Sans", ui-sans-serif, system-ui, sans-serif;
        }

        * { box-sizing: border-box; }
        html, body { min-height: 100%; }
        body {
            margin: 0;
            font-family: var(--font);
            color: var(--text-main);
            background:
                radial-gradient(1100px 480px at 82% -10%, #ffe7e1 0%, rgba(255, 231, 225, 0) 60%),
                radial-gradient(760px 420px at 4% 108%, #fdece5 0%, rgba(253, 236, 229, 0) 62%),
                var(--page-bg);
            -webkit-font-smoothing: antialiased;
            display: flex;
            flex-direction: column;
        }
        a { color: inherit; text-decoration: none; }
        button { font: inherit; cursor: pointer; }

        .topbar {
            height: 72px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 28px;
            background: rgba(255, 248, 247, .78);
            backdrop-filter: blur(14px);
            border-bottom: 1px solid var(--border-soft);
        }
        .brand {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .brand-mark {
            width: 34px;
            height: 34px;
            border-radius: 11px;
            display: grid;
            place-items: center;
            background: linear-gradient(150deg, var(--brand), #b91c1c);
            color: #fff;
            font-weight: 800;
            font-size: 19px;
            box-shadow: 0 8px 18px rgba(220, 38, 38, .28);
        }
        .brand-text {
            font-weight: 600;
            font-size: 16px;
            letter-spacing: -.01em;
        }
        .brand-text strong { font-weight: 800; }
        .brand-text em {
            font-style: normal;
            color: var(--text-faint);
            font-weight: 500;
            margin-left: 2px;
        }
        .pill {
            display: inline-flex;
            align-items: center;
            padding: 6px 12px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 700;
            background: var(--brand-soft);
            color: var(--brand-dark);
        }

        .auth-shell {
            flex: 1;
            display: grid;
            place-items: center;
            padding: 32px 20px 48px;
        }
        .auth-card {
            width: 100%;
            max-width: 440px;
            background: var(--surface-strong);
            border: 1px solid var(--border-soft);
            border-radius: var(--r-lg);
            box-shadow: var(--shadow-soft);
            padding: 32px;
        }
        .eyebrow {
            display: inline-block;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: .14em;
            text-transform: uppercase;
            color: var(--brand);
            margin-bottom: 8px;
        }
        .auth-card h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 800;
            letter-spacing: -.02em;
            line-height: 1.2;
        }
        .lead {
            margin: 8px 0 24px;
            font-size: 13.5px;
            color: var(--text-muted);
            line-height: 1.55;
        }

        .alert-error {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: var(--brand-dark);
            border-radius: 12px;
            padding: 11px 14px;
            font-size: 12.5px;
            font-weight: 600;
            margin-bottom: 18px;
        }

        .field { margin-bottom: 16px; }
        .field label {
            display: block;
            font-size: 12px;
            font-weight: 700;
            color: var(--text-muted);
            margin-bottom: 6px;
        }
        .input {
            width: 100%;
            font: inherit;
            font-size: 13.5px;
            color: var(--text-main);
            background: #fff;
            border: 1px solid var(--border-mid);
            border-radius: 10px;
            padding: 10px 12px;
            transition: .16s;
            outline: 0;
        }
        .input:focus {
            border-color: var(--brand);
            box-shadow: 0 0 0 3px rgba(220, 38, 38, .10);
        }
        .input.is-invalid {
            border-color: var(--brand);
        }
        .field-error {
            margin: 5px 0 0;
            font-size: 11.5px;
            font-weight: 600;
            color: var(--brand);
        }

        .remember-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin: 4px 0 22px;
        }
        .checkbox-label {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            font-weight: 600;
            color: var(--text-muted);
            cursor: pointer;
        }
        .checkbox-label input[type="checkbox"] {
            width: 16px;
            height: 16px;
            accent-color: var(--brand);
            border-radius: 4px;
        }

        .btn-primary {
            width: 100%;
            border: 0;
            border-radius: 12px;
            padding: 12px 18px;
            font-size: 14px;
            font-weight: 700;
            background: var(--brand);
            color: #fff;
            box-shadow: 0 10px 22px rgba(220, 38, 38, .28);
            transition: .18s;
        }
        .btn-primary:hover { background: var(--brand-dark); }

        .auth-footer {
            margin-top: 22px;
            padding-top: 18px;
            border-top: 1px solid var(--border-soft);
            text-align: center;
            font-size: 13px;
            color: var(--text-muted);
        }
        .auth-footer a {
            color: var(--brand);
            font-weight: 700;
        }
        .auth-footer a:hover { text-decoration: underline; }

        @media (max-width: 520px) {
            .topbar { padding: 0 16px; }
            .pill { display: none; }
            .auth-card { padding: 24px 20px; }
        }
    </style>
</head>
<body>
    <header class="topbar">
        <a class="brand" href="{{ route('login') }}">
            <span class="brand-mark">i</span>
            <span class="brand-text">INA<strong>web</strong> <em>Studio</em></span>
        </a>
        <span class="pill">Gratis untuk anggota INAmikro</span>
    </header>

    <main class="auth-shell">
        <section class="auth-card" aria-labelledby="login-heading">
            <span class="eyebrow">Akun UMKM</span>
            <h1 id="login-heading">Masuk ke INAweb Studio</h1>
            <p class="lead">Kelola website usaha Anda dengan cepat dan bagikan ke calon pembeli.</p>

            @if ($errors->any() && ! $errors->has('email') && ! $errors->has('password'))
                <div class="alert-error" role="alert">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}" novalidate>
                @csrf

                <div class="field">
                    <label for="email">Email</label>
                    <input
                        id="email"
                        name="email"
                        type="email"
                        class="input @error('email') is-invalid @enderror"
                        value="{{ old('email') }}"
                        placeholder="nama@usaha.com"
                        required
                        autofocus
                        autocomplete="email"
                    >
                    @error('email')
                        <p class="field-error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="field">
                    <label for="password">Kata Sandi</label>
                    <input
                        id="password"
                        name="password"
                        type="password"
                        class="input @error('password') is-invalid @enderror"
                        placeholder="Masukkan kata sandi"
                        required
                        autocomplete="current-password"
                    >
                    @error('password')
                        <p class="field-error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="remember-row">
                    <label class="checkbox-label" for="remember">
                        <input id="remember" name="remember" type="checkbox" value="1" @checked(old('remember'))>
                        <span>Ingat saya</span>
                    </label>
                </div>

                <button type="submit" class="btn-primary">Masuk</button>
            </form>

            <p class="auth-footer">
                Belum punya akun?
                <a href="{{ route('register') }}">Daftar sekarang</a>
            </p>
        </section>
    </main>
</body>
</html>
