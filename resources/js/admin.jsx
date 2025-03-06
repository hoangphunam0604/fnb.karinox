import React from 'react';
import { createRoot } from 'react-dom/client';
import { createInertiaApp } from '@inertiajs/react';
import POSLayout from '@/POS/Layouts/POSLayout'; // Đảm bảo POSLayout là React Component

createInertiaApp({
  resolve: name => {
    return import(`@/Admin/Pages/${name}.jsx`).then(module => {
      const Page = module.default;

      // Gán layout mặc định cho trang
      Page.layout = Page.layout || ((page) => <POSLayout>{page}</POSLayout>);

      return Page;
    });
  },
  setup({ el, App, props }) {
    const root = createRoot(el);
    root.render(<App {...props} />);
  },
});
