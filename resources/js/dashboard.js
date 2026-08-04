import QRCode from 'qrcode';

const state = {
    apiKey: document.querySelector('meta[name="wa-gateway-api-key"]')?.content || '',
    devices: [],
    clients: [],
    messages: [],
    activeDevice: null,
    qrPoll: null,
    refreshTimer: null,
};

const $ = (selector) => document.querySelector(selector);
const elements = {
    deviceGrid: $('#device-grid'),
    emptyState: $('#empty-state'),
    loadingState: $('#loading-state'),
    totalDevices: $('#total-devices'),
    connectedDevices: $('#connected-devices'),
    disconnectedDevices: $('#disconnected-devices'),
    connectionRate: $('#connection-rate'),
    engineStatus: $('#engine-status'),
    lastUpdated: $('#last-updated'),
    addModal: $('#add-modal'),
    addForm: $('#add-form'),
    addName: $('#add-name'),
    pairModal: $('#pair-modal'),
    pairTitle: $('#pair-title'),
    pairStatus: $('#pair-status'),
    pairCanvas: $('#pair-canvas'),
    pairPlaceholder: $('#pair-placeholder'),
    pairPhone: $('#pair-phone'),
    sendModal: $('#send-modal'),
    sendForm: $('#send-form'),
    sendDeviceName: $('#send-device-name'),
    sendRecipient: $('#send-recipient'),
    sendBody: $('#send-body'),
    toastContainer: $('#toast-container'),
    clientGrid: $('#client-grid'),
    clientEmpty: $('#client-empty'),
    clientModal: $('#client-modal'),
    clientForm: $('#client-form'),
    clientName: $('#client-name'),
    clientEmail: $('#client-email'),
    clientLimit: $('#client-limit'),
    clientDelay: $('#client-delay'),
    keyModal: $('#key-modal'),
    generatedKey: $('#generated-key'),
    copyKey: $('#copy-key'),
    messageList: $('#message-list'),
    messageEmpty: $('#message-empty'),
};

function escapeHtml(value) {
    return String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
}

async function api(path, options = {}) {
    if (!state.apiKey) throw new Error('API key belum diatur.');

    const response = await fetch(`/api/v1${path}`, {
        ...options,
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-API-Key': state.apiKey,
            ...(options.headers || {}),
        },
    });
    const payload = response.status === 204 ? null : await response.json();
    if (!response.ok) {
        const validation = payload?.errors ? Object.values(payload.errors).flat()[0] : null;
        throw new Error(validation || payload?.message || `Request gagal (${response.status})`);
    }
    return payload;
}

function toast(message, type = 'success') {
    const item = document.createElement('div');
    item.className = `toast toast-${type}`;
    item.innerHTML = `<span>${type === 'success' ? '✓' : '!'}</span><p>${escapeHtml(message)}</p>`;
    elements.toastContainer.append(item);
    setTimeout(() => item.classList.add('toast-show'), 20);
    setTimeout(() => {
        item.classList.remove('toast-show');
        setTimeout(() => item.remove(), 250);
    }, 3500);
}

function openModal(modal) {
    modal.hidden = false;
    requestAnimationFrame(() => modal.classList.add('is-open'));
}

function closeModal(modal) {
    modal.classList.remove('is-open');
    setTimeout(() => {
        modal.hidden = true;
        if (modal === elements.pairModal && state.qrPoll) {
            clearInterval(state.qrPoll);
            state.qrPoll = null;
        }
    }, 180);
}

function statusLabel(status) {
    return {
        connected: 'Terhubung',
        connecting: 'Menghubungkan',
        qr: 'Menunggu scan',
        disconnected: 'Terputus',
        logged_out: 'Logout',
        error: 'Bermasalah',
    }[status] || status;
}

function deviceCard(device) {
    const connected = device.status === 'connected';
    const initial = escapeHtml(device.name.slice(0, 1).toUpperCase());
    const phone = device.phone_number ? `+${escapeHtml(device.phone_number)}` : 'Nomor belum terdeteksi';
    const lastSeen = device.last_seen_at
        ? new Intl.DateTimeFormat('id-ID', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(device.last_seen_at))
        : 'Belum pernah terhubung';

    return `
        <article class="device-card" data-device-id="${device.id}">
            <div class="device-card-head">
                <div class="device-avatar">${initial}<span class="presence ${connected ? 'online' : ''}"></span></div>
                <div class="device-title">
                    <h3>${escapeHtml(device.name)}</h3>
                    <p>${phone}</p>
                </div>
                <span class="status-pill status-${escapeHtml(device.status)}">${escapeHtml(statusLabel(device.status))}</span>
            </div>
            <div class="device-meta">
                <div><span>Device ID</span><code title="${device.id}">${device.id.slice(0, 8)}…${device.id.slice(-4)}</code></div>
                <div><span>Terakhir aktif</span><strong>${escapeHtml(lastSeen)}</strong></div>
            </div>
            ${device.last_error ? `<div class="device-error">${escapeHtml(device.last_error)}</div>` : ''}
            <div class="device-actions">
                ${connected
                    ? `<button class="btn btn-primary btn-sm" data-action="send">Kirim pesan</button>
                       <button class="btn btn-soft btn-sm" data-action="disconnect">Disconnect</button>`
                    : `<button class="btn btn-primary btn-sm" data-action="pair">Hubungkan</button>`}
                <button class="icon-btn" data-action="menu" aria-label="Menu device">•••</button>
                <div class="device-menu" hidden>
                    <button data-action="refresh">Segarkan status</button>
                    <a href="/pair/${device.id}">Buka halaman pairing</a>
                    <button data-action="logout">Logout & hapus sesi</button>
                    <button class="danger" data-action="delete">Hapus device</button>
                </div>
            </div>
        </article>`;
}

function render() {
    elements.loadingState.hidden = true;
    elements.deviceGrid.innerHTML = state.devices.map(deviceCard).join('');
    elements.emptyState.hidden = state.devices.length > 0;
    elements.deviceGrid.hidden = state.devices.length === 0;

    const connected = state.devices.filter((item) => item.status === 'connected').length;
    const disconnected = state.devices.length - connected;
    const rate = state.devices.length ? Math.round((connected / state.devices.length) * 100) : 0;
    elements.totalDevices.textContent = state.devices.length;
    elements.connectedDevices.textContent = connected;
    elements.disconnectedDevices.textContent = disconnected;
    elements.connectionRate.textContent = `${rate}%`;
    elements.lastUpdated.textContent = `Diperbarui ${new Intl.DateTimeFormat('id-ID', { timeStyle: 'medium' }).format(new Date())}`;
}

function clientCard(client) {
    const initial = escapeHtml(client.name.slice(0, 1).toUpperCase());
    return `
        <article class="client-card" data-client-id="${client.id}">
            <div class="client-head">
                <div class="client-avatar">${initial}</div>
                <div class="client-name">
                    <strong>${escapeHtml(client.name)}</strong>
                    <span>${escapeHtml(client.email)}</span>
                </div>
                <span class="client-state ${client.is_active ? '' : 'inactive'}">${client.is_active ? 'Aktif' : 'Nonaktif'}</span>
            </div>
            <div class="client-info">
                <div><span>Device</span><strong>${client.devices_count ?? 0}</strong></div>
                <div><span>Limit harian</span><strong>${client.daily_message_limit} pesan</strong></div>
                <div><span>Jeda minimum</span><strong>${client.min_delay_seconds} detik</strong></div>
                <div><span>Terakhir dipakai</span><strong>${client.last_used_at ? new Intl.DateTimeFormat('id-ID', { dateStyle: 'short', timeStyle: 'short' }).format(new Date(client.last_used_at)) : 'Belum pernah'}</strong></div>
            </div>
            <div class="client-key"><span>Key tersimpan aman</span><code>${escapeHtml(client.key_prefix)}••••••••</code></div>
            <div class="client-actions">
                <button data-client-action="regenerate">Buat key baru</button>
                <button class="${client.is_active ? 'danger' : ''}" data-client-action="toggle">${client.is_active ? 'Nonaktifkan' : 'Aktifkan'}</button>
            </div>
        </article>`;
}

function renderClients() {
    elements.clientGrid.innerHTML = state.clients.map(clientCard).join('');
    elements.clientGrid.hidden = state.clients.length === 0;
    elements.clientEmpty.hidden = state.clients.length > 0;
}

function renderMessages() {
    elements.messageList.innerHTML = state.messages.map((message) => {
        const createdAt = new Intl.DateTimeFormat('id-ID', {
            dateStyle: 'medium',
            timeStyle: 'short',
        }).format(new Date(message.created_at));
        const devicePhone = message.device?.phone_number ? `+${message.device.phone_number}` : 'Nomor belum terdeteksi';

        return `<tr>
            <td><strong>${escapeHtml(createdAt)}</strong><div class="message-time">${escapeHtml(message.id.slice(0, 8))}…</div></td>
            <td class="message-device"><strong>${escapeHtml(message.device?.name || 'Device dihapus')}</strong><span>${escapeHtml(devicePhone)}</span></td>
            <td class="message-recipient">+${escapeHtml(message.recipient)}</td>
            <td class="message-body">${escapeHtml(message.body)}${message.error ? `<div class="device-error">${escapeHtml(message.error)}</div>` : ''}</td>
            <td><span class="message-status ${escapeHtml(message.status)}">${escapeHtml(message.status)}</span></td>
        </tr>`;
    }).join('');
    elements.messageEmpty.hidden = state.messages.length > 0;
    elements.messageList.closest('table').hidden = state.messages.length === 0;
}

async function loadMessages() {
    try {
        const payload = await api('/messages');
        state.messages = payload.data.data;
        renderMessages();
    } catch (error) {
        toast(error.message, 'error');
    }
}

async function loadClients() {
    if (!elements.clientGrid) return;
    try {
        const payload = await api('/clients');
        state.clients = payload.data;
        renderClients();
    } catch (error) {
        toast(error.message, 'error');
    }
}

async function loadDevices(showLoading = false) {
    if (showLoading) {
        elements.loadingState.hidden = false;
        elements.deviceGrid.hidden = true;
        elements.emptyState.hidden = true;
    }
    try {
        const payload = await api('/devices');
        state.devices = payload.data;
        elements.engineStatus.className = 'service-badge online';
        elements.engineStatus.innerHTML = '<span></span> API aktif';
        render();
    } catch (error) {
        elements.loadingState.hidden = true;
        elements.engineStatus.className = 'service-badge offline';
        elements.engineStatus.innerHTML = '<span></span> API bermasalah';
        if (error.message.toLowerCase().includes('api key')) {
            window.location.assign('/login');
        }
        toast(error.message, 'error');
    }
}

async function refreshDevice(deviceId, silent = false) {
    try {
        const payload = await api(`/devices/${deviceId}`);
        const index = state.devices.findIndex((item) => item.id === deviceId);
        if (index >= 0) state.devices[index] = payload.data;
        render();
        if (!silent) toast('Status device diperbarui.');
        return payload.data;
    } catch (error) {
        if (!silent) toast(error.message, 'error');
        return null;
    }
}

async function startPairing(device) {
    state.activeDevice = device;
    elements.pairTitle.textContent = device.name;
    elements.pairStatus.textContent = 'Memulai koneksi ke WhatsApp…';
    elements.pairPhone.textContent = device.phone_number ? `+${device.phone_number}` : 'Belum terdeteksi';
    elements.pairCanvas.hidden = true;
    elements.pairPlaceholder.hidden = false;
    openModal(elements.pairModal);

    try {
        const payload = await api(`/devices/${device.id}/connect`, { method: 'POST', body: '{}' });
        await updatePairing(payload);
        if (!state.qrPoll) {
            state.qrPoll = setInterval(async () => {
                try {
                    await updatePairing(await api(`/devices/${device.id}/qr`));
                } catch (error) {
                    elements.pairStatus.textContent = error.message;
                }
            }, 2000);
        }
    } catch (error) {
        elements.pairStatus.textContent = error.message;
        toast(error.message, 'error');
    }
}

async function updatePairing(payload) {
    const device = payload.data;
    elements.pairStatus.textContent = {
        connecting: 'Sedang membuat QR, tunggu sebentar…',
        qr: 'QR siap. Scan dari WhatsApp di HP.',
        connected: 'Berhasil terhubung. Device siap digunakan.',
        error: device.last_error || 'Koneksi bermasalah.',
    }[device.status] || statusLabel(device.status);
    elements.pairPhone.textContent = device.phone_number ? `+${device.phone_number}` : 'Belum terdeteksi';

    if (payload.qr) {
        elements.pairPlaceholder.hidden = true;
        elements.pairCanvas.hidden = false;
        await QRCode.toCanvas(elements.pairCanvas, payload.qr, {
            width: 300,
            margin: 3,
            errorCorrectionLevel: 'L',
            color: { dark: '#12221e', light: '#ffffff' },
        });
    }
    if (device.status === 'connected') {
        clearInterval(state.qrPoll);
        state.qrPoll = null;
        elements.pairCanvas.hidden = true;
        elements.pairPlaceholder.hidden = false;
        elements.pairPlaceholder.innerHTML = '<div class="pair-success">✓</div><strong>WhatsApp terhubung</strong>';
        await refreshDevice(device.id, true);
        toast(`${device.name} berhasil terhubung.`);
    }
}

async function handleDeviceAction(button) {
    const card = button.closest('[data-device-id]');
    const device = state.devices.find((item) => item.id === card.dataset.deviceId);
    const action = button.dataset.action;
    if (!device) return;

    if (action === 'menu') {
        const menu = card.querySelector('.device-menu');
        menu.hidden = !menu.hidden;
        return;
    }
    if (action === 'pair') return startPairing(device);
    if (action === 'send') {
        state.activeDevice = device;
        elements.sendDeviceName.textContent = device.name;
        elements.sendForm.reset();
        openModal(elements.sendModal);
        elements.sendRecipient.focus();
        return;
    }
    if (action === 'refresh') return refreshDevice(device.id);

    if (action === 'disconnect') {
        if (!confirm(`Putuskan koneksi ${device.name} sementara?`)) return;
        try {
            await api(`/devices/${device.id}/disconnect`, { method: 'POST', body: '{}' });
            toast('Device berhasil diputus sementara.');
            await loadDevices();
        } catch (error) { toast(error.message, 'error'); }
    }
    if (action === 'logout') {
        if (!confirm(`Logout ${device.name} dan hapus sesi WhatsApp lokal?`)) return;
        try {
            await api(`/devices/${device.id}/logout`, { method: 'POST', body: '{}' });
            toast('Device berhasil logout.');
            await loadDevices();
        } catch (error) { toast(error.message, 'error'); }
    }
    if (action === 'delete') {
        if (!confirm(`Hapus device “${device.name}”? Tindakan ini tidak dapat dibatalkan.`)) return;
        try {
            await api(`/devices/${device.id}`, { method: 'DELETE' });
            toast('Device berhasil dihapus.');
            await loadDevices();
        } catch (error) { toast(error.message, 'error'); }
    }
}

async function handleClientAction(button) {
    const card = button.closest('[data-client-id]');
    const client = state.clients.find((item) => item.id === card?.dataset.clientId);
    if (!client) return;

    if (button.dataset.clientAction === 'toggle') {
        const verb = client.is_active ? 'nonaktifkan' : 'aktifkan';
        if (!confirm(`${verb[0].toUpperCase()}${verb.slice(1)} akses ${client.name}?`)) return;
        try {
            await api(`/clients/${client.id}`, {
                method: 'PATCH',
                body: JSON.stringify({ is_active: !client.is_active }),
            });
            toast(`Client berhasil di${verb}.`);
            await loadClients();
        } catch (error) { toast(error.message, 'error'); }
    }

    if (button.dataset.clientAction === 'regenerate') {
        if (!confirm(`Buat API key baru untuk ${client.name}? Key lama langsung tidak berlaku.`)) return;
        try {
            const payload = await api(`/clients/${client.id}/regenerate-key`, { method: 'POST', body: '{}' });
            elements.generatedKey.value = payload.api_key;
            openModal(elements.keyModal);
            toast('API key baru berhasil dibuat.');
            await loadClients();
        } catch (error) { toast(error.message, 'error'); }
    }
}

$('#add-device').addEventListener('click', () => {
    elements.addForm.reset();
    openModal(elements.addModal);
    elements.addName.focus();
});
$('#empty-add').addEventListener('click', () => $('#add-device').click());
$('#show-clients')?.addEventListener('click', () => $('#clients-section').scrollIntoView({ behavior: 'smooth' }));
$('#show-messages').addEventListener('click', () => $('#messages-section').scrollIntoView({ behavior: 'smooth' }));
$('#refresh-messages').addEventListener('click', async () => {
    await loadMessages();
    toast('Riwayat pesan diperbarui.');
});
$('#add-client')?.addEventListener('click', () => {
    elements.clientForm.reset();
    elements.clientLimit.value = 60;
    elements.clientDelay.value = 30;
    openModal(elements.clientModal);
    elements.clientName.focus();
});
$('#refresh-all').addEventListener('click', async () => {
    await loadDevices();
    await Promise.all(state.devices.map((device) => refreshDevice(device.id, true)));
    toast('Semua status berhasil disegarkan.');
});

document.addEventListener('click', (event) => {
    const closeButton = event.target.closest('[data-close]');
    if (closeButton) closeModal(document.querySelector(closeButton.dataset.close));
    const actionButton = event.target.closest('[data-action]');
    if (actionButton) handleDeviceAction(actionButton);
    const clientActionButton = event.target.closest('[data-client-action]');
    if (clientActionButton) handleClientAction(clientActionButton);
    if (event.target.classList.contains('modal')) closeModal(event.target);
});

elements.clientForm?.addEventListener('submit', async (event) => {
    event.preventDefault();
    const submit = event.submitter;
    submit.disabled = true;
    submit.textContent = 'Membuat…';
    try {
        const payload = await api('/clients', {
            method: 'POST',
            body: JSON.stringify({
                name: elements.clientName.value.trim(),
                email: elements.clientEmail.value.trim(),
                daily_message_limit: Number(elements.clientLimit.value),
                min_delay_seconds: Number(elements.clientDelay.value),
            }),
        });
        closeModal(elements.clientModal);
        elements.generatedKey.value = payload.api_key;
        openModal(elements.keyModal);
        await loadClients();
    } catch (error) {
        toast(error.message, 'error');
    } finally {
        submit.disabled = false;
        submit.textContent = 'Buat API client';
    }
});

elements.copyKey?.addEventListener('click', async () => {
    try {
        await navigator.clipboard.writeText(elements.generatedKey.value);
        toast('API key disalin.');
    } catch {
        elements.generatedKey.select();
        document.execCommand('copy');
        toast('API key disalin.');
    }
});

elements.addForm.addEventListener('submit', async (event) => {
    event.preventDefault();
    const submit = event.submitter;
    submit.disabled = true;
    submit.textContent = 'Membuat…';
    try {
        const payload = await api('/devices', {
            method: 'POST',
            body: JSON.stringify({ name: elements.addName.value.trim() }),
        });
        closeModal(elements.addModal);
        toast('Device baru berhasil dibuat.');
        await loadDevices();
        await startPairing(payload.data);
    } catch (error) {
        toast(error.message, 'error');
    } finally {
        submit.disabled = false;
        submit.textContent = 'Buat & hubungkan';
    }
});

elements.sendForm.addEventListener('submit', async (event) => {
    event.preventDefault();
    const submit = event.submitter;
    submit.disabled = true;
    submit.textContent = 'Mengirim…';
    try {
        await api(`/devices/${state.activeDevice.id}/messages`, {
            method: 'POST',
            body: JSON.stringify({
                recipient: elements.sendRecipient.value,
                message: elements.sendBody.value,
            }),
        });
        closeModal(elements.sendModal);
        toast('Pesan masuk antrean pengiriman.');
        await loadMessages();
    } catch (error) {
        toast(error.message, 'error');
    } finally {
        submit.disabled = false;
        submit.textContent = 'Kirim pesan';
    }
});

loadDevices(true);
loadClients();
loadMessages();
state.refreshTimer = setInterval(async () => {
    if (!state.apiKey || document.hidden) return;
    await loadDevices();
    await loadMessages();
    await Promise.all(state.devices.map((device) => refreshDevice(device.id, true)));
}, 15000);
