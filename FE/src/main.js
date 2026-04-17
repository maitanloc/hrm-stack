import { createApp } from 'vue'
import 'bootstrap/dist/css/bootstrap.min.css'
import 'bootstrap/dist/js/bootstrap.bundle.min.js'
import './style.css'
import './styles/module-crud.css'
import App from './App.vue'
import router from './router'
import { installJsonServerBridge } from '@/services/jsonServerBridge.js'

const CANONICAL_HOST = 'anhsinhvienfpoly.click'
const LEGACY_HOSTS = new Set(['157.66.46.75', 'www.anhsinhvienfpoly.click'])

const redirectToCanonicalHost = () => {
  if (typeof window === 'undefined') return
  const { hostname, protocol, pathname, search, hash } = window.location
  const isLocal = ['localhost', '127.0.0.1'].includes(String(hostname || '').toLowerCase())
  if (isLocal) return

  const shouldRedirect = LEGACY_HOSTS.has(hostname) || protocol !== 'https:'
  if (!shouldRedirect) return

  const target = `https://${CANONICAL_HOST}${pathname}${search}${hash}`
  if (target !== window.location.href) {
    window.location.replace(target)
  }
}

redirectToCanonicalHost()

installJsonServerBridge()

const app = createApp(App)
app.use(router)
app.mount('#app')
