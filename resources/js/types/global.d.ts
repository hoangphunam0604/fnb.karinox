import { PageProps as InertiaPageProps } from '@inertiajs/core';
import { AxiosInstance } from 'axios';
import { Ziggy, route as ziggyRoute } from 'ziggy-js';
import { PageProps as AppPageProps } from './';

declare global {
  interface Window {
    axios: AxiosInstance;
    Ziggy: Config;
  }

  /* eslint-disable no-var */
  var route: typeof ziggyRoute;
}

declare module 'vue' {
  interface ComponentCustomProperties {
    route: typeof ziggyRoute;
  }
}

declare module '@inertiajs/core' {
  interface PageProps extends InertiaPageProps, AppPageProps {}
}

declare module '@inertiajs/vue3' {
  interface PageProps {
    ziggy: Ziggy;
  }
}
