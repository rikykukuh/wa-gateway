<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="wa-gateway-api-key" content="{{ $apiKey }}">
    <meta name="wa-gateway-is-admin" content="{{ $isAdmin ? '1' : '0' }}">
    <title>Dashboard · WA Gateway</title>
    @vite('resources/js/dashboard.js')
    <style>
        :root {
            --green: #0b806b; --green-dark: #086552; --green-soft: #e4f5f0;
            --ink: #15201d; --muted: #67736f; --line: #e3e9e7; --surface: #fff;
            --canvas: #f5f7f6; --danger: #c43d3d; --shadow: 0 12px 35px rgba(22,43,36,.08);
        }
        * { box-sizing: border-box; }
        [hidden] { display: none !important; }
        body { margin: 0; color: var(--ink); background: var(--canvas); font-family: Inter, ui-sans-serif, system-ui, -apple-system, sans-serif; }
        button, input, textarea { font: inherit; }
        button { cursor: pointer; }
        .app-shell { min-height: 100vh; }
        .sidebar {
            position: fixed; inset: 0 auto 0 0; width: 246px; padding: 28px 18px;
            color: white; background: #102a24; z-index: 10;
        }
        .brand { display: flex; align-items: center; gap: 12px; padding: 0 10px 30px; }
        .brand-mark { display: grid; place-items: center; width: 38px; height: 38px; border-radius: 12px; background: #22c98e; font-weight: 900; }
        .brand strong { display: block; font-size: 17px; }
        .brand small { color: #9cc1b6; }
        .nav-label { margin: 14px 12px 8px; color: #789e93; font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: .12em; }
        .nav-item {
            display: flex; align-items: center; gap: 11px; width: 100%; margin: 5px 0; padding: 11px 13px;
            border: 0; border-radius: 10px; color: #bcd2cc; background: transparent; text-decoration: none; text-align: left;
        }
        .nav-item:hover, .nav-item.active { color: white; background: rgba(255,255,255,.09); }
        .nav-icon { width: 21px; text-align: center; }
        .sidebar-bottom { position: absolute; left: 18px; right: 18px; bottom: 22px; }
        .side-note { padding: 14px; border: 1px solid rgba(255,255,255,.1); border-radius: 12px; color: #9cc1b6; font-size: 12px; line-height: 1.5; }
        .main { margin-left: 246px; min-height: 100vh; }
        .topbar {
            height: 76px; display: flex; align-items: center; padding: 0 42px;
            border-bottom: 1px solid var(--line); background: rgba(255,255,255,.9); backdrop-filter: blur(12px);
        }
        .topbar-title strong { display: block; font-size: 16px; }
        .topbar-title span { color: var(--muted); font-size: 12px; }
        .topbar-actions { margin-left: auto; display: flex; align-items: center; gap: 12px; }
        .service-badge { display: flex; align-items: center; gap: 7px; padding: 8px 11px; border-radius: 999px; color: var(--muted); background: #eef2f1; font-size: 12px; font-weight: 700; }
        .service-badge span { width: 7px; height: 7px; border-radius: 50%; background: #9da7a4; }
        .service-badge.online span { background: #17b978; box-shadow: 0 0 0 4px rgba(23,185,120,.12); }
        .service-badge.offline span { background: #e35353; }
        .content { padding: 38px 42px 60px; max-width: 1480px; margin: auto; }
        .page-head { display: flex; align-items: flex-end; gap: 20px; margin-bottom: 28px; }
        .page-head h1 { margin: 0 0 7px; font-size: 29px; letter-spacing: -.7px; }
        .page-head p { margin: 0; color: var(--muted); }
        .page-head-actions { margin-left: auto; display: flex; gap: 10px; }
        .btn { padding: 11px 16px; border: 1px solid transparent; border-radius: 9px; font-weight: 750; transition: .15s ease; }
        .btn:hover { transform: translateY(-1px); }
        .btn-primary { color: white; background: var(--green); }
        .btn-primary:hover { background: var(--green-dark); }
        .btn-soft { color: #3e4c48; border-color: var(--line); background: white; }
        .btn-sm { padding: 9px 12px; font-size: 12px; }
        .stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 28px; }
        .stat-card { padding: 20px; border: 1px solid var(--line); border-radius: 14px; background: var(--surface); box-shadow: 0 4px 16px rgba(22,43,36,.025); }
        .stat-top { display: flex; justify-content: space-between; align-items: center; color: var(--muted); font-size: 12px; font-weight: 700; }
        .stat-icon { display: grid; place-items: center; width: 34px; height: 34px; border-radius: 10px; background: var(--green-soft); color: var(--green); }
        .stat-value { margin: 12px 0 3px; font-size: 28px; font-weight: 850; letter-spacing: -.6px; }
        .stat-card small { color: #8a9591; }
        .section-head { display: flex; align-items: center; margin: 30px 0 14px; }
        .section-head h2 { margin: 0; font-size: 17px; }
        #last-updated { margin-left: auto; color: #8a9591; font-size: 12px; }
        .device-grid { display: grid; grid-template-columns: repeat(3, minmax(280px, 1fr)); gap: 18px; }
        .client-grid { display:grid; grid-template-columns:repeat(3,minmax(260px,1fr)); gap:14px; margin-bottom:34px; }
        .client-card { padding:17px; border:1px solid var(--line); border-radius:13px; background:white; }
        .client-head { display:flex; align-items:flex-start; gap:10px; }
        .client-avatar { display:grid; place-items:center; width:38px; height:38px; flex:none; border-radius:11px; color:#385f54; background:#edf5f2; font-weight:850; }
        .client-name { min-width:0; }
        .client-name strong,.client-name span { display:block; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
        .client-name strong { font-size:13px; }
        .client-name span { margin-top:3px; color:var(--muted); font-size:11px; }
        .client-state { margin-left:auto; padding:4px 7px; border-radius:999px; color:#087653; background:#daf7ec; font-size:9px; font-weight:850; text-transform:uppercase; }
        .client-state.inactive { color:#a83232; background:#fde5e5; }
        .client-info { display:grid; grid-template-columns:1fr 1fr; gap:8px; margin:15px 0; }
        .client-info div { padding:9px; border-radius:8px; background:#f5f7f6; }
        .client-info span,.client-info strong { display:block; }
        .client-info span { color:#89938f; font-size:9px; text-transform:uppercase; }
        .client-info strong { margin-top:3px; font-size:11px; }
        .client-key { display:flex; justify-content:space-between; align-items:center; margin-bottom:13px; color:#68736f; font-size:10px; }
        .client-key code { color:#2f4841; }
        .client-actions { display:flex; gap:7px; }
        .client-actions button { flex:1; padding:8px; border:1px solid var(--line); border-radius:8px; background:white; color:#4d5a56; font-size:10px; font-weight:750; }
        .client-actions button:hover { background:#f1f5f3; }
        .client-actions .danger { color:var(--danger); }
        .client-empty { margin-bottom:30px; padding:22px; border:1px dashed #cad6d2; border-radius:12px; color:var(--muted); text-align:center; font-size:13px; }
        .message-panel { overflow:hidden; margin-bottom:34px; border:1px solid var(--line); border-radius:14px; background:white; }
        .message-table-wrap { overflow-x:auto; }
        .message-table { width:100%; border-collapse:collapse; font-size:12px; }
        .message-table th { padding:11px 14px; color:#7a8581; background:#f6f8f7; text-align:left; font-size:10px; text-transform:uppercase; letter-spacing:.05em; }
        .message-table td { padding:13px 14px; border-top:1px solid #edf1ef; vertical-align:top; }
        .message-device strong,.message-device span { display:block; white-space:nowrap; }
        .message-device span,.message-time { margin-top:3px; color:var(--muted); font-size:10px; }
        .message-body { min-width:220px; max-width:430px; line-height:1.45; overflow-wrap:anywhere; }
        .message-recipient { white-space:nowrap; font-family:ui-monospace,monospace; }
        .message-status { display:inline-block; padding:4px 7px; border-radius:999px; font-size:9px; font-weight:850; text-transform:uppercase; }
        .message-status.sent { color:#087653; background:#daf7ec; }
        .message-status.pending,.message-status.queued { color:#80610a; background:#fff3c7; }
        .message-status.processing { color:#155c9a; background:#dceeff; }
        .message-status.paused { color:#88461d; background:#ffe6d5; }
        .message-status.failed { color:#a83232; background:#fde5e5; }
        .message-empty { padding:34px 20px; color:var(--muted); text-align:center; }
        .key-warning { margin-bottom:14px; padding:11px; border-radius:9px; color:#755b0a; background:#fff5ce; font-size:12px; line-height:1.5; }
        .key-box { display:flex; align-items:center; gap:8px; padding:10px 10px 10px 13px; border:1px solid var(--line); border-radius:10px; background:#f7faf9; }
        .key-box code { min-width:0; flex:1; overflow-wrap:anywhere; color:#1d3a32; font-size:12px; }
        .key-box button { padding:8px 11px; border:0; border-radius:7px; color:white; background:var(--green); font-size:11px; font-weight:800; }
        .device-card { position: relative; padding: 20px; border: 1px solid var(--line); border-radius: 15px; background: white; box-shadow: 0 4px 18px rgba(22,43,36,.035); }
        .device-card:hover { border-color: #c8d7d2; box-shadow: var(--shadow); }
        .device-card-head { display: flex; align-items: center; gap: 12px; }
        .device-avatar { position: relative; display: grid; place-items: center; width: 44px; height: 44px; flex: none; border-radius: 13px; color: var(--green); background: var(--green-soft); font-weight: 850; font-size: 18px; }
        .presence { position: absolute; right: -2px; bottom: -2px; width: 12px; height: 12px; border: 2px solid white; border-radius: 50%; background: #aab3b0; }
        .presence.online { background: #1abd7b; }
        .device-title { min-width: 0; }
        .device-title h3 { margin: 0 0 3px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-size: 15px; }
        .device-title p { margin: 0; color: var(--muted); font-size: 12px; }
        .status-pill { margin-left: auto; padding: 5px 8px; border-radius: 999px; color: #69736f; background: #eef1f0; font-size: 10px; font-weight: 850; text-transform: uppercase; }
        .status-connected { color: #087653; background: #daf7ec; }
        .status-qr, .status-connecting { color: #80610a; background: #fff3c7; }
        .status-error { color: #a83232; background: #fde5e5; }
        .device-meta { margin: 18px 0; padding: 14px 0; border-block: 1px solid #eef1f0; display: grid; gap: 10px; }
        .device-meta div { display: flex; justify-content: space-between; gap: 10px; font-size: 11px; }
        .device-meta span { color: #89938f; }
        .device-meta code, .device-meta strong { max-width: 65%; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; color: #40504b; font-size: 11px; font-weight: 650; }
        .device-error { margin: -7px 0 13px; padding: 8px; border-radius: 7px; color: #a83232; background: #fff1f1; font-size: 11px; }
        .device-actions { position: relative; display: flex; gap: 8px; }
        .icon-btn { margin-left: auto; width: 36px; border: 1px solid var(--line); border-radius: 9px; color: #64716d; background: white; font-weight: 900; }
        .device-menu { position: absolute; right: 0; bottom: 43px; width: 195px; padding: 6px; border: 1px solid var(--line); border-radius: 10px; background: white; box-shadow: var(--shadow); z-index: 4; }
        .device-menu button, .device-menu a { display: block; width: 100%; padding: 9px 10px; border: 0; border-radius: 7px; color: #35433f; background: none; text-align: left; text-decoration: none; font-size: 12px; }
        .device-menu button:hover, .device-menu a:hover { background: #f3f6f5; }
        .device-menu .danger { color: var(--danger); }
        .empty, .loading { padding: 70px 20px; border: 1px dashed #cfd9d6; border-radius: 15px; background: rgba(255,255,255,.55); text-align: center; }
        .empty-icon { display: grid; place-items: center; width: 64px; height: 64px; margin: 0 auto 16px; border-radius: 20px; color: var(--green); background: var(--green-soft); font-size: 28px; }
        .empty h3 { margin: 0 0 7px; }
        .empty p { margin: 0 0 20px; color: var(--muted); }
        .loader { width: 32px; height: 32px; margin: auto; border: 3px solid #dce5e2; border-top-color: var(--green); border-radius: 50%; animation: spin .8s linear infinite; }
        @keyframes spin { to { transform: rotate(360deg); } }
        .modal { position: fixed; inset: 0; display: grid; place-items: center; padding: 18px; background: rgba(9,25,20,.5); backdrop-filter: blur(4px); z-index: 50; opacity: 0; transition: .18s; }
        .modal.is-open { opacity: 1; }
        .modal-card { width: min(470px, 100%); padding: 25px; border-radius: 16px; background: white; box-shadow: 0 24px 70px rgba(0,0,0,.2); transform: translateY(10px); transition: .18s; }
        .modal.is-open .modal-card { transform: translateY(0); }
        .modal-card-lg { width: min(760px, 100%); }
        .modal-head { display: flex; align-items: flex-start; margin-bottom: 20px; }
        .modal-head h2 { margin: 0 0 5px; font-size: 20px; }
        .modal-head p { margin: 0; color: var(--muted); font-size: 13px; }
        .modal-close { margin-left: auto; width: 32px; height: 32px; border: 0; border-radius: 8px; color: #68736f; background: #f0f3f2; font-size: 20px; }
        .field { margin-bottom: 16px; }
        .field label { display: block; margin-bottom: 7px; font-size: 12px; font-weight: 750; }
        .field input, .field textarea { width: 100%; padding: 12px 13px; border: 1px solid #d5ddda; border-radius: 9px; outline: none; }
        .field input:focus, .field textarea:focus { border-color: var(--green); box-shadow: 0 0 0 3px rgba(11,128,107,.1); }
        .field small { display: block; margin-top: 6px; color: #89938f; }
        .modal-actions { display: flex; justify-content: flex-end; gap: 9px; margin-top: 22px; }
        .pair-layout { display: grid; grid-template-columns: 1fr 330px; gap: 28px; align-items: center; }
        .pair-copy h3 { margin: 0 0 8px; font-size: 25px; }
        .pair-copy p { color: var(--muted); line-height: 1.6; }
        .pair-steps { margin: 22px 0; padding-left: 19px; color: #52605c; font-size: 13px; line-height: 1.9; }
        .pair-phone { padding: 10px 12px; border-radius: 9px; color: #52605c; background: #f1f5f3; font-size: 12px; }
        .qr-box { min-height: 330px; display: grid; place-items: center; padding: 14px; border: 1px solid var(--line); border-radius: 14px; background: #fafcfb; text-align: center; }
        #pair-placeholder { color: var(--muted); }
        #pair-placeholder strong { display: block; margin-top: 9px; color: var(--ink); }
        .pair-success { display: grid; place-items: center; width: 72px; height: 72px; margin: auto; border-radius: 50%; color: var(--green); background: var(--green-soft); font-size: 38px; }
        .toasts { position: fixed; right: 22px; bottom: 22px; display: grid; gap: 10px; z-index: 100; }
        .toast { display: flex; align-items: center; gap: 10px; width: min(350px, 90vw); padding: 12px 14px; border: 1px solid #cce8de; border-radius: 11px; background: white; box-shadow: var(--shadow); opacity: 0; transform: translateY(10px); transition: .2s; }
        .toast-show { opacity: 1; transform: translateY(0); }
        .toast span { display: grid; place-items: center; width: 24px; height: 24px; border-radius: 50%; color: white; background: var(--green); font-weight: 900; }
        .toast p { margin: 0; font-size: 13px; }
        .toast-error { border-color: #f2cccc; }
        .toast-error span { background: var(--danger); }
        @media (max-width: 1100px) { .device-grid,.client-grid { grid-template-columns: repeat(2, 1fr); } .stats { grid-template-columns: repeat(2, 1fr); } }
        @media (max-width: 760px) {
            .sidebar { display: none; } .main { margin-left: 0; } .topbar { padding: 0 18px; }
            .content { padding: 26px 18px 50px; } .page-head { align-items: flex-start; flex-direction: column; }
            .page-head-actions { margin-left: 0; width: 100%; } .page-head-actions .btn { flex: 1; }
            .device-grid, .client-grid, .stats { grid-template-columns: 1fr; } .pair-layout { grid-template-columns: 1fr; }
            .qr-box { order: -1; } .service-badge { display: none; }
        }
    </style>
</head>
<body>
<div class="app-shell">
    <aside class="sidebar">
        <div class="brand">
            <div class="brand-mark">W</div>
            <div><strong>WA Gateway</strong><small>Multi-device API</small></div>
        </div>
        <div class="nav-label">Workspace</div>
        <a class="nav-item active" href="{{ route('dashboard') }}"><span class="nav-icon">▦</span> Dashboard</a>
        <button class="nav-item" id="add-device"><span class="nav-icon">＋</span> Tambah device</button>
        @if($isAdmin)<button class="nav-item" id="show-clients"><span class="nav-icon">♙</span> API Clients</button>@endif
        <button class="nav-item" id="show-messages"><span class="nav-icon">✉</span> Riwayat Pesan</button>
        <div class="nav-label">Developer</div>
        <a class="nav-item" href="{{ route('docs') }}"><span class="nav-icon">⌘</span> API Documentation</a>
        <a class="nav-item" href="{{ route('password.edit') }}"><span class="nav-icon">⚿</span> Ganti Password</a>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button class="nav-item" type="submit"><span class="nav-icon">↪</span> Keluar</button>
        </form>
        <div class="sidebar-bottom">
            <div class="side-note">Gunakan gateway secara bertanggung jawab dan hanya kirim pesan kepada penerima yang telah memberi izin.</div>
        </div>
    </aside>

    <div class="main">
        <header class="topbar">
            <div class="topbar-title"><strong>Device Management</strong><span>Kelola seluruh koneksi WhatsApp dari satu tempat</span></div>
            <div class="topbar-actions">
                <div id="engine-status" class="service-badge"><span></span> Memeriksa API</div>
                <a href="{{ route('docs') }}" class="btn btn-soft btn-sm">Swagger</a>
            </div>
        </header>

        <main class="content">
            <div class="page-head">
                <div><h1>WhatsApp Devices</h1><p>Hubungkan, pantau, dan gunakan beberapa akun WhatsApp.</p></div>
                <div class="page-head-actions">
                    <button id="refresh-all" class="btn btn-soft">↻ Segarkan</button>
                    <button class="btn btn-primary" onclick="document.querySelector('#add-device').click()">＋ Tambah device</button>
                </div>
            </div>

            <section class="stats">
                <div class="stat-card"><div class="stat-top">Total device <span class="stat-icon">▦</span></div><div id="total-devices" class="stat-value">0</div><small>Device terdaftar</small></div>
                <div class="stat-card"><div class="stat-top">Terhubung <span class="stat-icon">✓</span></div><div id="connected-devices" class="stat-value">0</div><small>Siap mengirim pesan</small></div>
                <div class="stat-card"><div class="stat-top">Tidak aktif <span class="stat-icon">○</span></div><div id="disconnected-devices" class="stat-value">0</div><small>Perlu dihubungkan</small></div>
                <div class="stat-card"><div class="stat-top">Connection rate <span class="stat-icon">↗</span></div><div id="connection-rate" class="stat-value">0%</div><small>Persentase online</small></div>
            </section>

            @if($isAdmin)
                <div id="clients-section" class="section-head"><h2>API Clients</h2><button id="add-client" class="btn btn-soft btn-sm" style="margin-left:auto">＋ Tambah client</button></div>
                <section id="client-grid" class="client-grid"></section>
                <section id="client-empty" class="client-empty" hidden>Belum ada API client. Tambahkan user agar mereka mendapatkan API key terpisah.</section>
            @endif

            <div class="section-head"><h2>Daftar device</h2><span id="last-updated">Belum diperbarui</span></div>
            <div id="loading-state" class="loading"><div class="loader"></div></div>
            <section id="device-grid" class="device-grid" hidden></section>
            <section id="empty-state" class="empty" hidden>
                <div class="empty-icon">＋</div><h3>Belum ada device</h3>
                <p>Tambahkan device pertama dan hubungkan melalui QR WhatsApp.</p>
                <button id="empty-add" class="btn btn-primary">Tambah device pertama</button>
            </section>

            <div id="messages-section" class="section-head"><h2>Riwayat pesan</h2><button id="refresh-messages" class="btn btn-soft btn-sm" style="margin-left:auto">↻ Segarkan</button></div>
            <section class="message-panel">
                <div class="message-table-wrap">
                    <table class="message-table">
                        <thead><tr><th>Waktu</th><th>Device pengirim</th><th>Nomor tujuan</th><th>Pesan</th><th>Status</th></tr></thead>
                        <tbody id="message-list"></tbody>
                    </table>
                </div>
                <div id="message-empty" class="message-empty" hidden>Belum ada pesan yang dikirim.</div>
            </section>
        </main>
    </div>
</div>

<div id="add-modal" class="modal" hidden>
    <div class="modal-card">
        <div class="modal-head"><div><h2>Tambah device baru</h2><p>Beri nama yang mudah dikenali, lalu hubungkan WhatsApp.</p></div><button class="modal-close" data-close="#add-modal">×</button></div>
        <form id="add-form">
            <div class="field"><label for="add-name">Nama device</label><input id="add-name" required maxlength="100" placeholder="Contoh: Customer Service"><small>Nomor telepon terdeteksi otomatis setelah pairing.</small></div>
            <div class="modal-actions"><button type="button" class="btn btn-soft" data-close="#add-modal">Batal</button><button type="submit" class="btn btn-primary">Buat & hubungkan</button></div>
        </form>
    </div>
</div>

<div id="client-modal" class="modal" hidden>
    <div class="modal-card">
        <div class="modal-head"><div><h2>Tambah API client</h2><p>Client hanya dapat mengakses device dan pesannya sendiri.</p></div><button class="modal-close" data-close="#client-modal">×</button></div>
        <form id="client-form">
            <div class="field"><label for="client-name">Nama</label><input id="client-name" required maxlength="100" placeholder="Contoh: Toko Cabang Bandung"></div>
            <div class="field"><label for="client-email">Email</label><input id="client-email" type="email" required placeholder="user@domain.com"></div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                <div class="field"><label for="client-limit">Limit pesan/hari</label><input id="client-limit" type="number" required min="1" max="10000" value="60"></div>
                <div class="field"><label for="client-delay">Jeda minimum</label><input id="client-delay" type="number" required min="1" max="3600" value="30"><small>Dalam detik.</small></div>
            </div>
            <div class="modal-actions"><button type="button" class="btn btn-soft" data-close="#client-modal">Batal</button><button type="submit" class="btn btn-primary">Buat API client</button></div>
        </form>
    </div>
</div>

<div id="key-modal" class="modal" hidden>
    <div class="modal-card">
        <div class="modal-head"><div><h2>API key berhasil dibuat</h2><p>Salin dan simpan sekarang. Key ini tidak dapat ditampilkan kembali.</p></div><button class="modal-close" data-close="#key-modal">×</button></div>
        <div class="key-warning">⚠ Jangan kirim key melalui chat publik atau menyimpannya di source code.</div>
        <div class="key-box"><code id="generated-key"></code><button id="copy-key" type="button">Salin</button></div>
        <button class="btn btn-primary" data-close="#key-modal" style="width:100%;margin-top:18px">Saya sudah menyimpan key</button>
    </div>
</div>

<div id="pair-modal" class="modal" hidden>
    <div class="modal-card modal-card-lg">
        <div class="modal-head"><div><h2>Hubungkan WhatsApp</h2><p>QR diperbarui otomatis sampai device terhubung.</p></div><button class="modal-close" data-close="#pair-modal">×</button></div>
        <div class="pair-layout">
            <div class="pair-copy">
                <h3 id="pair-title">Device</h3><p id="pair-status">Memulai koneksi…</p>
                <ol class="pair-steps"><li>Buka WhatsApp di HP.</li><li>Pilih <strong>Perangkat tertaut</strong>.</li><li>Pilih <strong>Tautkan perangkat</strong>.</li><li>Scan QR di sebelah.</li></ol>
                <div class="pair-phone">Nomor: <strong id="pair-phone">Belum terdeteksi</strong></div>
            </div>
            <div class="qr-box"><div id="pair-placeholder"><div class="loader"></div><strong>Menyiapkan QR…</strong></div><canvas id="pair-canvas" hidden></canvas></div>
        </div>
    </div>
</div>

<div id="send-modal" class="modal" hidden>
    <div class="modal-card">
        <div class="modal-head"><div><h2>Kirim pesan</h2><p>Melalui device <strong id="send-device-name"></strong></p></div><button class="modal-close" data-close="#send-modal">×</button></div>
        <form id="send-form">
            <div class="field"><label for="send-recipient">Nomor penerima</label><input id="send-recipient" required placeholder="628123456789"><small>Gunakan kode negara tanpa tanda +.</small></div>
            <div class="field"><label for="send-body">Pesan</label><textarea id="send-body" required maxlength="4096" rows="5" placeholder="Tulis pesan…"></textarea></div>
            <div class="modal-actions"><button type="button" class="btn btn-soft" data-close="#send-modal">Batal</button><button type="submit" class="btn btn-primary">Kirim pesan</button></div>
        </form>
    </div>
</div>
<div id="toast-container" class="toasts"></div>
</body>
</html>
