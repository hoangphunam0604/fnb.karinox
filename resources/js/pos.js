import { createInertiaApp } from '@inertiajs/vue3';
import { createApp, h } from 'vue';

createInertiaApp({
  resolve: (name) => import(`./POS/Pages/${name}.vue`),
  setup({ el, App, props, plugin }) {
    console.log('POS');
    createApp({ render: () => h(App, props) })
      .use(plugin)
      .mount(el);
  },
});
