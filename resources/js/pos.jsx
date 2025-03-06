import React from 'react';
import { createRoot } from 'react-dom/client';
import { createInertiaApp } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';

createInertiaApp({
  resolve: (name) => resolvePageComponent(`./POS/Pages/${name}.jsx`, import.meta.glob('./POS/Pages/**/*.jsx')),
  setup({ el, App, props }) {
    console.log('POS');
    const root = createRoot(el);
    root.render(<App {...props} />);
  },
});
