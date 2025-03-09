<script setup>
import { onMounted } from 'vue';

import NavHeader from '@/Admin/components/NavHeader.vue';
import Header from '@/Admin/components/Header.vue';
import Sidebar from '@/Admin/components/Sidebar.vue';
import Footer from '@/Admin/components/Footer.vue';

import { defineProps } from 'vue';

import { usePage } from '@inertiajs/inertia-vue3';

const page = usePage();
console.log('Main.vue Props:', page.props); // Kiểm tra props trong Main layout
</script>
<template>
  <NavHeader />
  <Header />
  <Sidebar />
  <div class="content-body">
    <div class="container-fluid">
      {{ title }}
      <slot />
    </div>
  </div>
</template>