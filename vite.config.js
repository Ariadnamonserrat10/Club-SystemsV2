import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'

// https://vite.dev/config/
export default defineConfig({
  base: './',
  plugins: [vue()],
  server: {
    proxy: {
      '/api': {
        target: 'http://127.0.0.1:8000',
        changeOrigin: true,
        // El backend PHP se sirve desde la carpeta Backend (el servidor PHP se lanza con cwd=Backend),
        // por lo que no debe añadirse '/Backend' en las rutas.
        rewrite: (path) => path.replace(/^\/api/, ''),
      },
    },
  },
})
