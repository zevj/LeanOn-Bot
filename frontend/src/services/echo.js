import Echo from 'laravel-echo'
import Pusher from 'pusher-js'

window.Pusher = Pusher

let echoInstance = null

export function getEchoInstance() {
  if (echoInstance) return echoInstance

  const key = import.meta.env.VITE_REVERB_APP_KEY || import.meta.env.VITE_PUSHER_APP_KEY || 'reverb_app_key'
  const host = import.meta.env.VITE_REVERB_HOST || window.location.hostname || '127.0.0.1'
  const port = import.meta.env.VITE_REVERB_PORT || 8080
  const scheme = import.meta.env.VITE_REVERB_SCHEME || 'http'
  const apiHost = import.meta.env.VITE_API_BASE_URL || ''

  try {
    echoInstance = new Echo({
      broadcaster: 'reverb',
      key: key,
      wsHost: host,
      wsPort: port,
      wssPort: port,
      forceTLS: scheme === 'https',
      enabledTransports: ['ws', 'wss'],
      authEndpoint: `${apiHost}/api/broadcasting/auth`,
      auth: {
        headers: {
          Authorization: `Bearer ${localStorage.getItem('token') || ''}`,
          Accept: 'application/json',
        },
      },
    })
  } catch (err) {
    console.warn('Echo initialization failed, fallback polling will be used:', err)
    echoInstance = null
  }

  return echoInstance
}

export function subscribeToPrivateChannel(channelName, eventName, callback) {
  const echo = getEchoInstance()
  if (!echo) return null

  try {
    const channel = echo.private(channelName)
    channel.listen(`.${eventName}`, callback)
    channel.listen(eventName, callback)
    return channel
  } catch (err) {
    console.warn(`Error subscribing to channel ${channelName}:`, err)
    return null
  }
}

export function unsubscribeFromChannel(channelName) {
  if (!echoInstance) return
  try {
    echoInstance.leave(channelName)
  } catch (err) {
    // Ignore leave errors
  }
}
