import express from 'express'
import fs from 'node:fs/promises'
import path from 'node:path'
import { fileURLToPath } from 'node:url'
import { Boom } from '@hapi/boom'
import makeWASocket, {
  Browsers,
  DisconnectReason,
  fetchLatestBaileysVersion,
  useMultiFileAuthState,
} from '@whiskeysockets/baileys'
import pino from 'pino'

const app = express()
const port = Number(process.env.PORT || 3100)
const engineSecret = process.env.WA_ENGINE_SECRET || 'change-this-engine-secret'
const currentDirectory = path.dirname(fileURLToPath(import.meta.url))
const sessionsDirectory = process.env.WA_SESSIONS_DIR
  ? path.resolve(process.env.WA_SESSIONS_DIR)
  : path.resolve(currentDirectory, '../sessions')
const logger = pino({ level: process.env.LOG_LEVEL || 'info' })
const devices = new Map()
const deviceIdPattern = /^[0-9a-f]{8}-[0-9a-f]{4}-[1-8][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i

app.use(express.json({ limit: '1mb' }))

app.get('/', (_request, response) => {
  response.json({ service: 'wa-gateway-engine', status: 'ok' })
})

app.get('/health', (_request, response) => {
  response.json({ status: 'ok', devices: devices.size })
})

app.use((request, response, next) => {
  const supplied = request.header('X-Engine-Secret') || ''
  const expected = Buffer.from(engineSecret)
  const actual = Buffer.from(supplied)

  if (actual.length !== expected.length || !cryptoSafeEqual(actual, expected)) {
    return response.status(401).json({ message: 'Unauthorized' })
  }

  next()
})

function cryptoSafeEqual(actual, expected) {
  let difference = 0
  for (let index = 0; index < actual.length; index += 1) {
    difference |= actual[index] ^ expected[index]
  }
  return difference === 0
}

function assertDeviceId(deviceId) {
  if (!deviceIdPattern.test(deviceId)) {
    const error = new Error('Invalid device ID')
    error.status = 422
    throw error
  }
}

function publicState(deviceId) {
  const state = devices.get(deviceId)
  return {
    id: deviceId,
    status: state?.status || 'disconnected',
    qr: state?.qr || null,
    phoneNumber: state?.phoneNumber || null,
    error: state?.error || null,
  }
}

async function startDevice(deviceId) {
  assertDeviceId(deviceId)

  const existing = devices.get(deviceId)
  if (existing?.socket && ['connecting', 'qr', 'connected'].includes(existing.status)) {
    return publicState(deviceId)
  }

  const sessionPath = path.join(sessionsDirectory, deviceId)
  const { state: authState, saveCreds } = await useMultiFileAuthState(sessionPath)
  const { version } = await fetchLatestBaileysVersion()
  const state = {
    socket: null,
    status: 'connecting',
    qr: null,
    phoneNumber: existing?.phoneNumber || null,
    error: null,
    manuallyStopped: false,
  }
  devices.set(deviceId, state)

  const socket = makeWASocket({
    version,
    auth: authState,
    logger: logger.child({ deviceId }),
    browser: Browsers.ubuntu('WA Gateway'),
    markOnlineOnConnect: false,
    generateHighQualityLinkPreview: false,
    syncFullHistory: false,
  })
  state.socket = socket

  socket.ev.on('creds.update', saveCreds)
  socket.ev.on('connection.update', async ({ connection, lastDisconnect, qr }) => {
    if (qr) {
      state.qr = qr
      state.status = 'qr'
      state.error = null
    }

    if (connection === 'open') {
      state.status = 'connected'
      state.qr = null
      state.error = null
      state.phoneNumber = socket.user?.id?.split(':')[0]?.split('@')[0] || null
      logger.info({ deviceId, phoneNumber: state.phoneNumber }, 'WhatsApp device connected')
    }

    if (connection === 'close') {
      const statusCode = new Boom(lastDisconnect?.error).output.statusCode
      const loggedOut = statusCode === DisconnectReason.loggedOut
      state.socket = null
      state.qr = null
      state.status = loggedOut ? 'logged_out' : 'disconnected'
      state.error = lastDisconnect?.error?.message || null

      if (loggedOut) {
        await fs.rm(sessionPath, { recursive: true, force: true })
      } else if (!state.manuallyStopped) {
        setTimeout(() => startDevice(deviceId).catch((error) => {
          state.status = 'error'
          state.error = error.message
          logger.error({ error, deviceId }, 'Device reconnect failed')
        }), 3000)
      }
    }
  })

  return publicState(deviceId)
}

app.post('/devices/:deviceId/connect', async (request, response, next) => {
  try {
    response.status(202).json(await startDevice(request.params.deviceId))
  } catch (error) {
    next(error)
  }
})

app.get('/devices/:deviceId', (request, response, next) => {
  try {
    assertDeviceId(request.params.deviceId)
    response.json(publicState(request.params.deviceId))
  } catch (error) {
    next(error)
  }
})

app.post('/devices/:deviceId/disconnect', async (request, response, next) => {
  try {
    const { deviceId } = request.params
    assertDeviceId(deviceId)
    const state = devices.get(deviceId)

    if (state?.socket) {
      state.manuallyStopped = true
      if (request.body.logout === true) {
        await state.socket.logout()
      } else {
        state.socket.end(undefined)
      }
    }

    if (request.body.logout === true) {
      await fs.rm(path.join(sessionsDirectory, deviceId), { recursive: true, force: true })
    }

    devices.set(deviceId, {
      socket: null,
      status: request.body.logout === true ? 'logged_out' : 'disconnected',
      qr: null,
      phoneNumber: state?.phoneNumber || null,
      error: null,
      manuallyStopped: true,
    })
    response.json(publicState(deviceId))
  } catch (error) {
    next(error)
  }
})

app.delete('/devices/:deviceId', async (request, response, next) => {
  try {
    const { deviceId } = request.params
    assertDeviceId(deviceId)
    const state = devices.get(deviceId)
    if (state?.socket) {
      state.manuallyStopped = true
      state.socket.end(undefined)
    }
    devices.delete(deviceId)
    await fs.rm(path.join(sessionsDirectory, deviceId), { recursive: true, force: true })
    response.status(204).end()
  } catch (error) {
    next(error)
  }
})

app.post('/devices/:deviceId/messages', async (request, response, next) => {
  try {
    const { deviceId } = request.params
    assertDeviceId(deviceId)
    const state = devices.get(deviceId)

    if (!state?.socket || state.status !== 'connected') {
      return response.status(409).json({ message: 'Device is not connected' })
    }

    const recipient = String(request.body.recipient || '').replace(/\D/g, '')
    const body = String(request.body.body || '')
    if (!/^[1-9][0-9]{7,14}$/.test(recipient) || body.length < 1 || body.length > 4096) {
      return response.status(422).json({ message: 'Invalid recipient or message body' })
    }

    const jid = `${recipient}@s.whatsapp.net`
    const [lookup] = await state.socket.onWhatsApp(jid)
    if (!lookup?.exists) {
      return response.status(422).json({ message: 'Recipient is not registered on WhatsApp' })
    }

    const result = await state.socket.sendMessage(lookup.jid, { text: body })
    response.status(201).json({ messageId: result.key.id, recipient: lookup.jid })
  } catch (error) {
    next(error)
  }
})

app.use((error, _request, response, _next) => {
  logger.error({ error }, 'Engine request failed')
  response.status(error.status || 500).json({ message: error.message || 'Internal engine error' })
})

async function restoreSessions() {
  const entries = await fs.readdir(sessionsDirectory, { withFileTypes: true })
  for (const entry of entries) {
    if (entry.isDirectory() && deviceIdPattern.test(entry.name)) {
      startDevice(entry.name).catch((error) => logger.error({ error, deviceId: entry.name }, 'Session restore failed'))
    }
  }
}

async function bootstrap() {
  await fs.mkdir(sessionsDirectory, { recursive: true })

  app.listen(port, '0.0.0.0', () => {
    logger.info({ port }, 'WhatsApp engine listening')
    restoreSessions().catch((error) => logger.error({ error }, 'Could not restore sessions'))
  })
}

bootstrap().catch((error) => {
  logger.fatal({ error }, 'WhatsApp engine could not start')
})
