import { createApp } from 'vue'
import App from './App.vue'
import './bootstrap'
import router from './router/phase2'

// Import CSS
import '../css/app.css'

const app = createApp(App)

app.use(router)

app.mount('#app')
