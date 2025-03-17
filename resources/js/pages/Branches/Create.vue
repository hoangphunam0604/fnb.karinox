<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

const form = useForm({
  name: '',
  address: '',
  phone_number: '',
  email: '',
});

const submitForm = () => {
  form.post(route('admin.branches.store'));
};

const breadcrumbs = [
  { title: 'Dashboard', href: '/dashboard' },
  { title: 'Chi nhánh', href: '/admin/branches' },
  { title: 'Thêm chi nhánh', href: '/admin/branches/create' },
];
</script>

<template>
  <Head title="Thêm chi nhánh" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex flex-col gap-6 p-6 rounded-xl max-w-2xl text-left">
      <h1 class="text-2xl font-semibold text-gray-800">Thêm chi nhánh</h1>
      
      <form @submit.prevent="submitForm" class="space-y-4">
        <div class="grid grid-cols-6 gap-4 items-center">
          <label for="name" class="col-span-2 text-sm font-medium text-gray-700 text-left">Tên chi nhánh</label>
          <input type="text" id="name" v-model="form.name" class="col-span-4 p-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500" placeholder="Nhập tên chi nhánh" required />
        </div>

        <div class="grid grid-cols-6 gap-4 items-center">
          <label for="address" class="col-span-2 text-sm font-medium text-gray-700 text-left">Địa chỉ</label>
          <input type="text" id="address" v-model="form.address" class="col-span-4 p-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500" placeholder="Nhập địa chỉ" required />
        </div>

        <div class="grid grid-cols-6 gap-4 items-center">
          <label for="phone_number" class="col-span-2 text-sm font-medium text-gray-700 text-left">Số điện thoại</label>
          <input type="text" id="phone_number" v-model="form.phone_number" class="col-span-4 p-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500" placeholder="Nhập số điện thoại" />
        </div>

        <div class="grid grid-cols-6 gap-4 items-center">
          <label for="email" class="col-span-2 text-sm font-medium text-gray-700 text-left">Email</label>
          <input type="email" id="email" v-model="form.email" class="col-span-4 p-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500" placeholder="Nhập email" />
        </div>

        <div class="flex justify-end">
          <button type="submit" class="px-4 py-2 bg-indigo-600 text-white font-semibold rounded-lg hover:bg-indigo-700 focus:outline-none focus:ring focus:ring-indigo-300">Lưu</button>
        </div>
      </form>
    </div>
  </AppLayout>
</template>