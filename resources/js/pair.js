import QRCode from 'qrcode';

const root = document.querySelector('[data-pair-device]');
const deviceId = root.dataset.pairDevice;
const apiKeyInput = document.querySelector('#api-key');
const connectButton = document.querySelector('#connect-button');
const qrCanvas = document.querySelector('#qr-canvas');
const qrPlaceholder = document.querySelector('#qr-placeholder');
const statusBadge = document.querySelector('#status-badge');
const statusMessage = document.querySelector('#status-message');
const phoneNumber = document.querySelector('#phone-number');
let pollTimer = null;
let lastQr = null;

apiKeyInput.value = document.querySelector('meta[name="wa-gateway-api-key"]')?.content
    || sessionStorage.getItem('wa_gateway_api_key')
    || '';

function headers() {
    const apiKey = apiKeyInput.value.trim();
    if (!apiKey) {
        throw new Error('Masukkan API key terlebih dahulu.');
    }
    sessionStorage.setItem('wa_gateway_api_key', apiKey);

    return {
        Accept: 'application/json',
        'Content-Type': 'application/json',
        'X-API-Key': apiKey,
    };
}

function updateStatus(device) {
    const status = device.status || 'disconnected';
    statusBadge.textContent = status;
    statusBadge.dataset.status = status;
    phoneNumber.textContent = device.phone_number || 'Belum terdeteksi';

    const messages = {
        disconnected: 'Device belum terhubung. Klik mulai koneksi.',
        connecting: 'Menghubungkan ke WhatsApp, tunggu sebentar…',
        qr: 'QR siap. Scan menggunakan WhatsApp di HP.',
        connected: 'Device berhasil terhubung dan siap mengirim pesan.',
        logged_out: 'Sesi sudah logout. Mulai koneksi untuk menautkan ulang.',
        error: device.last_error || 'Terjadi masalah pada koneksi.',
    };
    statusMessage.textContent = messages[status] || status;

    if (status === 'connected') {
        clearInterval(pollTimer);
        pollTimer = null;
        qrCanvas.hidden = true;
        qrPlaceholder.hidden = false;
        qrPlaceholder.innerHTML = '<span class="success-icon">✓</span><strong>WhatsApp terhubung</strong>';
        connectButton.textContent = 'Sudah terhubung';
        connectButton.disabled = true;
    }
}

async function renderQr(qr) {
    if (!qr || qr === lastQr) return;

    lastQr = qr;
    qrPlaceholder.hidden = true;
    qrCanvas.hidden = false;
    await QRCode.toCanvas(qrCanvas, qr, {
        width: 320,
        margin: 3,
        errorCorrectionLevel: 'L',
        color: { dark: '#111827', light: '#ffffff' },
    });
}

async function request(endpoint, options = {}) {
    const response = await fetch(`/api/v1/devices/${deviceId}${endpoint}`, {
        ...options,
        headers: headers(),
    });
    const payload = response.status === 204 ? {} : await response.json();
    if (!response.ok) {
        throw new Error(payload.message || `Request gagal (${response.status})`);
    }
    return payload;
}

async function pollQr() {
    try {
        const payload = await request('/qr');
        updateStatus(payload.data);
        await renderQr(payload.qr);
    } catch (error) {
        statusMessage.textContent = error.message;
        statusBadge.textContent = 'error';
        statusBadge.dataset.status = 'error';
    }
}

async function connect() {
    connectButton.disabled = true;
    connectButton.textContent = 'Memulai koneksi…';

    try {
        const payload = await request('/connect', { method: 'POST', body: '{}' });
        updateStatus(payload.data);
        await renderQr(payload.qr);
        await pollQr();

        if (!pollTimer && payload.data.status !== 'connected') {
            pollTimer = setInterval(pollQr, 2000);
        }
        connectButton.textContent = 'Menyegarkan QR…';
    } catch (error) {
        statusMessage.textContent = error.message;
        connectButton.textContent = 'Coba lagi';
    } finally {
        connectButton.disabled = false;
    }
}

connectButton.addEventListener('click', connect);
apiKeyInput.addEventListener('keydown', (event) => {
    if (event.key === 'Enter') connect();
});
