import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import POSLayout from '@/POS/Layouts/POSLayout.vue';

createInertiaApp({
  resolve: name => {
    return import(`@/Admin/Pages/${name}.vue`).then(module => {
      module.default.layout = POSLayout;
      return module;
    });
  },

  setup({ el, App, props, plugin }) {
    createApp({ render: () => h(App, props) })
      .use(plugin)
      .mount(el);
  },
});
