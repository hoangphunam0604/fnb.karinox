import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';

export default defineConfig({
  plugins: [
    laravel({
      input: [
        'resources/js/app.js',
        'resources/js/admin.js',
        'resources/js/pos.js',
        'resources/css/app.css',
      ],
      refresh: true,
    }),
    react(),
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
