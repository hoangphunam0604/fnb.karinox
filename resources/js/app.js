import '../css/app.css';
import './bootstrap';

import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h } from 'vue';
import { ZiggyVue } from 'ziggy-js';
const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
  title: (title) => `${title} - ${appName}`,
  resolve: (name) =>
    resolvePageComponent(
      `./App/Pages/${name}.vue`,
      import.meta.glob('./App/Pages/**/*.vue')
    ),
  setup({ el, App, props, plugin }) {

    const ziggyData = props.initialPage.props.ziggy;
    if (typeof window !== 'undefined') {
      window.Ziggy = ziggyData;
    }

    createApp({ render: () => h(App, props) })
      .use(plugin)
      .use(ZiggyVue, window.Ziggy)
      .mount(el);
  },
  progress: {
    color: '#4B5563',
  },
});
