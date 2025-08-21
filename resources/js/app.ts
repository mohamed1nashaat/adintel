import { createPinia } from 'pinia'
import { createApp } from 'vue'
import { createI18n } from 'vue-i18n'
import '../css/app.css'
import App from './App.vue'
import './bootstrap'
import router from './router/unified'

// Import i18n messages
import ar from './locales/ar.json'
import en from './locales/en.json'

// Create i18n instance
const i18n = createI18n({
  legacy: false,
  locale: 'en',
  fallbackLocale: 'en',
  messages: {
    en,
    ar
  }
})

// Create Pinia store
const pinia = createPinia()

// Create and mount the app
const app = createApp(App)

app.use(pinia)
app.use(router)
app.use(i18n)

app.mount('#app')
