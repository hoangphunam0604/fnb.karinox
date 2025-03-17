<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

import AppearanceTabs from '@/components/AppearanceTabs.vue';
import HeadingSmall from '@/components/HeadingSmall.vue';
import { type BreadcrumbItem } from '@/types';

import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';

import { ref, onMounted } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { Inertia } from '@inertiajs/inertia';

const breadcrumbItems: BreadcrumbItem[] = [
  {
    title: 'Appearance settings',
    href: '/settings/appearance',
  },
];

const file = ref(null);
const importStatus = ref(null);
const props = defineProps({
  branches: Array, // Danh sách chi nhánh từ backend
});
const form = useForm({
  branch_id: props.branches.length ? props.branches[0].id : null,
  file: null
});
const handleFileChange = (event) => {
  form.file = event.target.files[0];
};

const submitImport = () => {
  if (!form.file) {
    importStatus.value = { success: false, message: "Vui lòng chọn file!" };
    return;
  }

  Inertia.post('/admin/import-products', form, {
    onSuccess: (response) => {
      importStatus.value = response.props.flash;
    },
    onError: (error) => {
      importStatus.value = { success: false, message: "Import thất bại!" };
      console.error(error);
    }
  });
};
</script>

<template>
  <AppLayout :breadcrumbs="breadcrumbItems">

    <Head title="Appearance settings" />

    <div class="max-w-2xl mx-auto p-6 bg-white rounded-lg shadow-md">
      <h2 class="text-xl font-semibold mb-4">Nhập dữ liệu sản phẩm từ Excel</h2>
      <select v-model="form.branch_id" class="mb-4 block w-full border rounded-lg p-2">
        <option v-for="branch in branches" :key="branch.id" :value="branch.id">{{ branch.name }}</option>
      </select>
      <input type="file" @change="handleFileChange" accept=".xlsx" class="mb-4 block w-full border rounded-lg p-2" />

      <button @click="submitImport" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
        Import Sản Phẩm
      </button>

      <div v-if="importStatus" :class="importStatus.success ? 'text-green-600' : 'text-red-600'" class="mt-4 font-medium">
        {{ importStatus.message }}
      </div>
    </div>
  </AppLayout>
</template>
