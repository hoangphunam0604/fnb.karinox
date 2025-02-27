import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import path from 'node:path';

export default defineConfig({
  plugins: [
    laravel({
      input: [
        'resources/js/admin.js',
        'resources/js/pos.js',
        'resources/js/kitchen.js',
        'resources/js/manager.js'
      ],
      refresh: true,
    }),
    vue(),
  ],
  resolve: {
    alias: {
      '@': path.resolve(__dirname, 'resources/js'),
      '@POS': path.resolve(__dirname, 'resources/js/POS'),
    },
  },
  server: {
    host: 'fnb.karinox.nam',
    port: 5173, // Port mặc định của Vite
    hmr: {
      host: 'fnb.karinox.nam',
    },
  },
});
