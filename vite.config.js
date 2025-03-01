import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
  plugins: [
    laravel({
      input: [
        'resources/js/app.js',
        'resources/css/app.css'
      ],
      refresh: true,
    }),
    vue(),
  ],
  resolve: {
    alias: {
      '@': '/resources/js',
      '@App': '/resources/js/App',
      '@POS': '/resources/js/POS',
      '@Kitchen': '/resources/js/Kitchen',
      '@Manager': '/resources/js/Manager',
      '@Admin': '/resources/js/Admin',
    },
  },
  server: {
    host: 'localhost',
    port: 5173,
    hmr: {
      host: 'localhost',
    },
  },
});
