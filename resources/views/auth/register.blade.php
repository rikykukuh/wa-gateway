<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar · WA Gateway</title>
    <style>
        :root { --green:#0b806b; --green-dark:#086552; --ink:#15201d; --muted:#697570; --line:#dce5e2; }
        * { box-sizing:border-box; }
        body { margin:0; min-height:100vh; display:grid; place-items:center; padding:24px; color:var(--ink); font-family:Inter,ui-sans-serif,system-ui,sans-serif; background:radial-gradient(circle at 12% 10%,rgba(30,200,142,.2),transparent 34%),radial-gradient(circle at 90% 90%,rgba(11,128,107,.16),transparent 32%),#f3f7f5; }
        .shell { width:min(920px,100%); display:grid; grid-template-columns:1fr 440px; overflow:hidden; border-radius:24px; background:white; box-shadow:0 28px 80px rgba(16,42,36,.14); }
        .welcome { padding:55px; color:white; background:linear-gradient(145deg,#0d332b,#075e4e); }
        .brand { display:flex; align-items:center; gap:12px; }.mark { display:grid; place-items:center; width:42px; height:42px; border-radius:13px; color:#083b31; background:#28d39a; font-weight:900; }
        .brand strong,.brand small { display:block; }.brand small { color:#9ec8bc; }
        .copy { margin-top:100px; }.copy span { color:#60e4b8; font-size:12px; font-weight:800; text-transform:uppercase; letter-spacing:.14em; }
        h1 { margin:10px 0 15px; font-size:38px; line-height:1.12; letter-spacing:-1px; }.copy p { color:#b6d3cb; line-height:1.7; }
        .notice { margin-top:28px; padding:14px; border:1px solid rgba(255,255,255,.13); border-radius:11px; color:#d8e9e4; font-size:13px; line-height:1.55; }
        .panel { padding:42px 52px; }.panel h2 { margin:0 0 8px; font-size:27px; }.subtitle { margin:0 0 25px; color:var(--muted); font-size:14px; line-height:1.55; }
        .field { margin-bottom:14px; } label { display:block; margin-bottom:6px; font-size:12px; font-weight:800; }
        input { width:100%; padding:12px 14px; border:1px solid var(--line); border-radius:10px; outline:none; font:inherit; } input:focus { border-color:var(--green); box-shadow:0 0 0 4px rgba(11,128,107,.1); }
        .error { margin-bottom:17px; padding:10px 12px; border-radius:9px; color:#a83232; background:#fff0f0; font-size:12px; }
        button { width:100%; margin-top:7px; padding:13px; border:0; border-radius:10px; color:white; background:var(--green); font:inherit; font-weight:800; cursor:pointer; } button:hover { background:var(--green-dark); }
        .login { margin-top:18px; color:var(--muted); font-size:13px; text-align:center; }.login a { color:var(--green); font-weight:800; text-decoration:none; }
        @media(max-width:760px){.shell{grid-template-columns:1fr}.welcome{display:none}.panel{padding:36px 28px}}
    </style>
</head>
<body>
<main class="shell">
    <section class="welcome">
        <div class="brand"><div class="mark">W</div><div><strong>WA Gateway</strong><small>Multi-device messaging</small></div></div>
        <div class="copy"><span>Daftar akun</span><h1>Kelola WhatsApp Anda sendiri.</h1><p>Setiap akun memiliki device, riwayat pesan, dan API key yang terpisah.</p><div class="notice">Setelah mendaftar, akun harus diaktifkan oleh administrator sebelum dapat digunakan.</div></div>
    </section>
    <section class="panel">
        <h2>Buat akun baru</h2>
        <p class="subtitle">Isi data berikut. Anda akan dapat masuk setelah admin menyetujui akun.</p>
        @if($errors->any())<div class="error">{{ $errors->first() }}</div>@endif
        <form method="POST" action="{{ route('register.store') }}">
            @csrf
            <div class="field"><label for="name">Nama</label><input id="name" name="name" value="{{ old('name') }}" required maxlength="100" autofocus autocomplete="name"></div>
            <div class="field"><label for="email">Email</label><input id="email" name="email" type="email" value="{{ old('email') }}" required autocomplete="email"></div>
            <div class="field"><label for="password">Password</label><input id="password" name="password" type="password" required minlength="8" autocomplete="new-password"></div>
            <div class="field"><label for="password_confirmation">Ulangi password</label><input id="password_confirmation" name="password_confirmation" type="password" required minlength="8" autocomplete="new-password"></div>
            <button type="submit">Daftar akun</button>
        </form>
        <div class="login">Sudah punya akun? <a href="{{ route('login') }}">Kembali ke login</a></div>
    </section>
</main>
</body>
</html>
