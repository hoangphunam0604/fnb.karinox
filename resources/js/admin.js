import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h } from 'vue';
import { InertiaProgress } from '@inertiajs/progress';
import { ZiggyVue } from 'ziggy-js';
const appName = 'Karinox Admin';


createInertiaApp({
  title: (title) => `${title} - ${appName}`,
  resolve: (name) =>
    resolvePageComponent(
      `./Admin/Pages/${name}.vue`,
      import.meta.glob('./Admin/Pages/**/*.vue')
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
    color: '#FF0000',
  },
});
InertiaProgress.init({
  color: '#4B5563',
  showSpinner: true,
});