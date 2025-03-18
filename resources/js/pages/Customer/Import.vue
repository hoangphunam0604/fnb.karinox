<script setup>
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { Inertia } from '@inertiajs/inertia';

const file = ref(null);
const importStatus = ref(null);
const form = useForm({ file: null });

const handleFileChange = (event) => {
  form.file = event.target.files[0];
};

const submitImport = () => {
  if (!form.file) {
    importStatus.value = { success: false, message: "Vui lòng chọn file!" };
    return;
  }

  Inertia.post('/admin/customers/import', form, {
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
  <AppLayout>

    <Head title="Import Khách Hàng" />
    <div class="max-w-2xl mx-auto p-6 bg-white rounded-lg shadow-md">
      <h2 class="text-xl font-semibold mb-4">Nhập dữ liệu khách hàng từ Excel</h2>
      <input type="file" @change="handleFileChange" accept=".xlsx" class="mb-4 block w-full border rounded-lg p-2" />
      <button @click="submitImport" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
        Import Khách Hàng
      </button>
      <div v-if="importStatus" :class="importStatus.success ? 'text-green-600' : 'text-red-600'" class="mt-4 font-medium">
        {{ importStatus.message }}
      </div>
    </div>
  </AppLayout>
</template>
