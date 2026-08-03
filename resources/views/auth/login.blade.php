<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login · WA Gateway</title>
    <style>
        :root { --green:#0b806b; --green-dark:#086552; --ink:#15201d; --muted:#697570; --line:#dce5e2; }
        * { box-sizing: border-box; }
        body {
            margin: 0; min-height: 100vh; display: grid; place-items: center; padding: 24px;
            color: var(--ink); font-family: Inter, ui-sans-serif, system-ui, -apple-system, sans-serif;
            background:
                radial-gradient(circle at 12% 10%, rgba(30,200,142,.2), transparent 34%),
                radial-gradient(circle at 90% 90%, rgba(11,128,107,.16), transparent 32%),
                #f3f7f5;
        }
        .login-shell { width: min(920px, 100%); display: grid; grid-template-columns: 1fr 440px; overflow: hidden; border: 1px solid rgba(255,255,255,.8); border-radius: 24px; background: white; box-shadow: 0 28px 80px rgba(16,42,36,.14); }
        .welcome { position: relative; min-height: 590px; padding: 55px; color: white; background: linear-gradient(145deg, #0d332b, #075e4e); }
        .welcome::after { content:""; position:absolute; right:-80px; bottom:-100px; width:330px; height:330px; border:65px solid rgba(255,255,255,.05); border-radius:50%; }
        .brand { display:flex; align-items:center; gap:12px; }
        .brand-mark { display:grid; place-items:center; width:42px; height:42px; border-radius:13px; color:#083b31; background:#28d39a; font-weight:900; }
        .brand strong { display:block; font-size:18px; }.brand small { color:#9ec8bc; }
        .welcome-copy { position:relative; z-index:1; margin-top:125px; }
        .eyebrow { color:#60e4b8; font-size:12px; font-weight:800; text-transform:uppercase; letter-spacing:.14em; }
        h1 { margin:10px 0 15px; max-width:380px; font-size:38px; line-height:1.12; letter-spacing:-1px; }
        .welcome-copy p { max-width:370px; color:#b6d3cb; line-height:1.7; }
        .feature { display:flex; align-items:center; gap:10px; margin-top:28px; color:#d8e9e4; font-size:13px; }
        .feature span { display:grid; place-items:center; width:25px; height:25px; border-radius:50%; color:#063b30; background:#4bddad; font-weight:900; }
        .form-panel { display:flex; flex-direction:column; justify-content:center; padding:52px; }
        .form-panel h2 { margin:0 0 8px; font-size:27px; letter-spacing:-.5px; }
        .subtitle { margin:0 0 32px; color:var(--muted); font-size:14px; line-height:1.55; }
        label { display:block; margin:0 0 7px; font-size:12px; font-weight:800; }
        .field { margin-bottom:17px; }
        input { width:100%; padding:13px 14px; border:1px solid var(--line); border-radius:10px; outline:none; font:inherit; }
        input:focus { border-color:var(--green); box-shadow:0 0 0 4px rgba(11,128,107,.1); }
        .error { margin:0 0 17px; padding:10px 12px; border-radius:9px; color:#a83232; background:#fff0f0; font-size:12px; }
        .success { margin:0 0 17px; padding:10px 12px; border-radius:9px; color:#087653; background:#e2f7ef; font-size:12px; line-height:1.5; }
        button { width:100%; margin-top:6px; padding:13px; border:0; border-radius:10px; color:white; background:var(--green); font:inherit; font-weight:800; cursor:pointer; }
        button:hover { background:var(--green-dark); }
        .security { margin-top:24px; color:#8b9692; font-size:11px; text-align:center; line-height:1.5; }
        .register-link { margin-top:20px; color:var(--muted); font-size:13px; text-align:center; }
        .register-link a { color:var(--green); font-weight:800; text-decoration:none; }
        @media(max-width:760px){ .login-shell{grid-template-columns:1fr}.welcome{display:none}.form-panel{padding:38px 28px;min-height:520px} }
    </style>
</head>
<body>
    <main class="login-shell">
        <section class="welcome">
            <div class="brand"><div class="brand-mark">W</div><div><strong>WA Gateway</strong><small>Multi-device messaging</small></div></div>
            <div class="welcome-copy">
                <div class="eyebrow">Admin workspace</div>
                <h1>Kelola WhatsApp dari satu panel.</h1>
                <p>Pantau device, hubungkan QR, kirim pesan, dan lihat seluruh status koneksi dengan aman.</p>
                <div class="feature"><span>✓</span> Session login terenkripsi</div>
                <div class="feature"><span>✓</span> API key terpasang otomatis</div>
            </div>
        </section>
        <section class="form-panel">
            <h2>Selamat datang</h2>
            <p class="subtitle">Masuk menggunakan akun administrator untuk membuka dashboard.</p>
            @if (session('status'))
                <div class="success">{{ session('status') }}</div>
            @endif
            @if ($errors->any())
                <div class="error">{{ $errors->first() }}</div>
            @endif
            <form method="POST" action="{{ route('login.store') }}">
                @csrf
                <div class="field"><label for="email">Email</label><input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="username" placeholder="admin@domain.com"></div>
                <div class="field"><label for="password">Password</label><input id="password" name="password" type="password" required autocomplete="current-password" placeholder="Masukkan password"></div>
                <button type="submit">Masuk ke dashboard</button>
            </form>
            <div class="register-link">Belum punya akun? <a href="{{ route('register') }}">Daftar sekarang</a></div>
            <div class="security">Dilindungi session Laravel, CSRF protection, dan pembatasan percobaan login.</div>
        </section>
    </main>
</body>
</html>
