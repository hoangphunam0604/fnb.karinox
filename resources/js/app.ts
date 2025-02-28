import '../css/app.css';
import './bootstrap';

import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, DefineComponent, h } from 'vue';
import type { Config } from 'ziggy-js'; // 👈 Import kiểu dữ liệu Config
import { ZiggyVue } from 'ziggy-js';
const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
  title: (title) => `${title} - ${appName}`,
  resolve: (name) =>
    resolvePageComponent(
      `./App/Pages/${name}.vue`,
      import.meta.glob<DefineComponent>('./App/Pages/**/*.vue')
    ),
  setup({ el, App, props, plugin }) {
    const ziggyData: Config = props.initialPage.props.ziggy as Config;

    if (typeof window !== 'undefined') {
      window.Ziggy = ziggyData;
    }

    console.log('🚀 Props received in Vue (app.ts):', props);
    console.log('🚀 Ziggy in app.ts:', ziggyData);

    createApp({ render: () => h(App, props) })
      .use(plugin)
      .use(ZiggyVue, window.Ziggy)
      .mount(el);
  },
  progress: {
    color: '#4B5563',
  },
});
