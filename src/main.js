import { createApp } from 'vue';
import axios from 'axios';
import { createRouter, createWebHashHistory } from 'vue-router';
import App from './App.vue';
import Login from './Pages/Login.vue';
import CrearC from './Pages/CrearC.vue';
import Oficina from './Pages/Oficina.vue';
import Monitor from './Pages/Monitor.vue';

// En desarrollo (Vite), usamos proxy para evitar CORS (/api -> http://127.0.0.1:8000).
// En producción/electron, se usa directamente el servidor PHP.
const isDev = import.meta.env.DEV;
const API_BASE_URL = isDev ? '' : (import.meta.env.VITE_API_BASE_URL ?? 'http://127.0.0.1:8000');
axios.defaults.baseURL = API_BASE_URL;

const routes = [
  { path: '/', component: Login },
  { path: '/crear-cuenta', component: CrearC },
  { path: '/oficina', component: Oficina },
  { path: '/monitor', component: Monitor }
];

const router = createRouter({
  history: createWebHashHistory(),
  routes,
});

createApp(App).use(router).mount('#app');
