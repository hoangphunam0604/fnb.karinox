import vue from '@vitejs/plugin-vue';
import laravel from 'laravel-vite-plugin';
import path from 'node:path';
import { defineConfig } from 'vite';

export default defineConfig({
  plugins: [
    laravel({
      input: [
        'resources/js/admin.ts',
        'resources/js/pos.ts',
        'resources/js/kitchen.ts',
        'resources/js/manager.ts',
      ],
      refresh: true,
    }),
    vue({
      template: {
        transformAssetUrls: {
          base: null,
          includeAbsolute: false,
        },
      },
    }),
  ],
  resolve: {
    alias: {
      vue: 'vue/dist/vue.esm-bundler.js',
      '@': path.resolve(__dirname, 'resources/js'),
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
