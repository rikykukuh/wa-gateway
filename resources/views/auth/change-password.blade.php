<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ganti Password · WA Gateway</title>
    <style>
        :root { --green:#0b806b; --green-dark:#086552; --ink:#15201d; --muted:#697570; --line:#dce5e2; }
        * { box-sizing:border-box; }
        body { margin:0; min-height:100vh; display:grid; place-items:center; padding:24px; color:var(--ink); font-family:Inter,ui-sans-serif,system-ui,sans-serif; background:radial-gradient(circle at 12% 10%,rgba(30,200,142,.2),transparent 34%),radial-gradient(circle at 90% 90%,rgba(11,128,107,.16),transparent 32%),#f3f7f5; }
        .card { width:min(480px,100%); padding:38px; border:1px solid rgba(255,255,255,.8); border-radius:20px; background:white; box-shadow:0 24px 70px rgba(16,42,36,.13); }
        .brand { display:flex; align-items:center; gap:11px; margin-bottom:30px; }.mark { display:grid; place-items:center; width:39px; height:39px; border-radius:12px; color:#083b31; background:#28d39a; font-weight:900; }
        .brand strong,.brand small { display:block; }.brand small { color:var(--muted); font-size:11px; }
        h1 { margin:0 0 8px; font-size:27px; letter-spacing:-.5px; }.subtitle { margin:0 0 27px; color:var(--muted); font-size:13px; line-height:1.6; }
        .field { margin-bottom:16px; } label { display:block; margin-bottom:7px; font-size:12px; font-weight:800; }
        input { width:100%; padding:13px 14px; border:1px solid var(--line); border-radius:10px; outline:none; font:inherit; } input:focus { border-color:var(--green); box-shadow:0 0 0 4px rgba(11,128,107,.1); }
        .error { margin-bottom:18px; padding:11px 13px; border-radius:9px; color:#a83232; background:#fff0f0; font-size:12px; line-height:1.5; }
        .actions { display:flex; gap:10px; margin-top:24px; }.btn { flex:1; padding:12px 14px; border:1px solid var(--line); border-radius:10px; font:inherit; font-weight:800; text-align:center; text-decoration:none; cursor:pointer; }
        .cancel { color:#46534f; background:white; }.save { color:white; border-color:var(--green); background:var(--green); }.save:hover { background:var(--green-dark); }
        .note { margin-top:20px; padding:11px; border-radius:9px; color:#6e5a16; background:#fff6d8; font-size:11px; line-height:1.55; }
        @media(max-width:520px){.card{padding:30px 24px}.actions{flex-direction:column}}
    </style>
</head>
<body>
<main class="card">
    <div class="brand"><div class="mark">W</div><div><strong>WA Gateway</strong><small>Keamanan akun</small></div></div>
    <h1>Ganti password</h1>
    <p class="subtitle">Masukkan password lama untuk memastikan perubahan ini dilakukan oleh pemilik akun.</p>
    @if($errors->any())<div class="error">{{ $errors->first() }}</div>@endif
    <form method="POST" action="{{ route('password.update') }}">
        @csrf
        @method('PUT')
        <div class="field"><label for="current_password">Password lama</label><input id="current_password" name="current_password" type="password" required autofocus autocomplete="current-password"></div>
        <div class="field"><label for="password">Password baru</label><input id="password" name="password" type="password" required minlength="8" autocomplete="new-password"></div>
        <div class="field"><label for="password_confirmation">Ulangi password baru</label><input id="password_confirmation" name="password_confirmation" type="password" required minlength="8" autocomplete="new-password"></div>
        <div class="actions"><a class="btn cancel" href="{{ route('dashboard') }}">Batal</a><button class="btn save" type="submit">Simpan password</button></div>
    </form>
    <div class="note">Setelah password berhasil diubah, Anda akan keluar otomatis dan perlu login kembali.</div>
</main>
</body>
</html>
