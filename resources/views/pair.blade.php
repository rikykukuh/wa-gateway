<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="wa-gateway-api-key" content="{{ $apiKey }}">
    <title>Pair {{ $device->name }} · WA Gateway</title>
    @vite('resources/js/pair.js')
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0; min-height: 100vh; color: #17202a;
            background: radial-gradient(circle at top left, #d9fdd3, transparent 38%), #f0f2f5;
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, sans-serif;
        }
        header {
            height: 82px; padding: 0 6vw; display: flex; align-items: center;
            background: #00a884; color: white;
        }
        header strong { font-size: 19px; letter-spacing: -.2px; }
        header a { margin-left: auto; color: white; text-decoration: none; opacity: .9; }
        main { width: min(940px, 92vw); margin: 46px auto; }
        .card {
            display: grid; grid-template-columns: 1fr 380px; gap: 44px;
            padding: 42px; background: white; border-radius: 18px;
            box-shadow: 0 12px 40px rgba(17, 27, 33, .12);
        }
        .eyebrow { color: #008069; font-size: 13px; font-weight: 700; text-transform: uppercase; letter-spacing: .08em; }
        h1 { margin: 8px 0 8px; font-size: 32px; letter-spacing: -.7px; }
        .device-id { color: #667781; font-size: 12px; word-break: break-all; }
        label { display: block; margin: 30px 0 8px; font-size: 13px; font-weight: 700; }
        input {
            width: 100%; padding: 13px 14px; border: 1px solid #d4dadd;
            border-radius: 9px; font: inherit; outline: none;
        }
        input:focus { border-color: #00a884; box-shadow: 0 0 0 3px rgba(0,168,132,.12); }
        button {
            width: 100%; margin-top: 12px; padding: 13px; border: 0; border-radius: 9px;
            background: #008069; color: white; font: inherit; font-weight: 700; cursor: pointer;
        }
        button:hover { background: #006b58; }
        button:disabled { opacity: .6; cursor: wait; }
        .status-row { display: flex; align-items: center; gap: 10px; margin-top: 28px; }
        #status-badge {
            padding: 5px 10px; border-radius: 999px; background: #e9edef;
            color: #54656f; font-size: 12px; font-weight: 800; text-transform: uppercase;
        }
        #status-badge[data-status="qr"] { color: #806000; background: #fff4c2; }
        #status-badge[data-status="connected"] { color: #087c56; background: #d9fdd3; }
        #status-badge[data-status="error"] { color: #b42318; background: #fee4e2; }
        #status-message { margin: 10px 0; color: #54656f; line-height: 1.55; }
        .phone { font-size: 13px; color: #667781; }
        .qr-panel {
            min-height: 380px; display: grid; place-items: center; padding: 28px;
            border: 1px solid #e5e9eb; border-radius: 14px; background: #fafbfb;
        }
        #qr-placeholder { text-align: center; color: #667781; line-height: 1.6; }
        #qr-placeholder strong { display: block; color: #17202a; margin-top: 8px; }
        .phone-icon { display: block; font-size: 54px; }
        .success-icon {
            display: grid; place-items: center; width: 72px; height: 72px; margin: auto;
            border-radius: 50%; background: #d9fdd3; color: #008069; font-size: 38px;
        }
        .steps { margin-top: 26px; color: #54656f; font-size: 14px; line-height: 1.8; }
        @media (max-width: 760px) {
            .card { grid-template-columns: 1fr; padding: 26px; }
            .qr-panel { order: -1; min-height: 340px; }
        }
    </style>
</head>
<body>
    <header>
        <strong>WA Gateway · Device Pairing</strong>
        <a href="{{ route('docs') }}">API Docs →</a>
    </header>
    <main data-pair-device="{{ $device->id }}">
        <section class="card">
            <div>
                <div class="eyebrow">WhatsApp Multi-device</div>
                <h1>{{ $device->name }}</h1>
                <div class="device-id">{{ $device->id }}</div>

                <input id="api-key" type="hidden">
                <button id="connect-button" type="button">Mulai koneksi & tampilkan QR</button>

                <div class="status-row">
                    <span id="status-badge" data-status="{{ $device->status }}">{{ $device->status }}</span>
                </div>
                <p id="status-message">Masukkan API key, lalu mulai koneksi.</p>
                <div class="phone">Nomor: <strong id="phone-number">{{ $device->phone_number ?? 'Belum terdeteksi' }}</strong></div>

                <div class="steps">
                    <strong>Cara scan:</strong><br>
                    1. Buka WhatsApp di HP.<br>
                    2. Pilih <strong>Perangkat tertaut</strong>.<br>
                    3. Pilih <strong>Tautkan perangkat</strong>.<br>
                    4. Scan QR di sebelah.
                </div>
            </div>
            <div class="qr-panel">
                <div id="qr-placeholder">
                    <span class="phone-icon">▣</span>
                    <strong>QR belum dibuat</strong>
                    Klik tombol mulai koneksi.
                </div>
                <canvas id="qr-canvas" hidden></canvas>
            </div>
        </section>
    </main>
</body>
</html>
