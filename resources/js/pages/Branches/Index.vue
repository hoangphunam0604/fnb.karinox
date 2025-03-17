<script setup lang="ts">
import { defineProps } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { Inertia } from '@inertiajs/inertia';



const deleteBranch = (id) => {
  if (confirm('Bạn có chắc chắn muốn xóa chi nhánh này?')) {
    Inertia.delete(`/admin/branches/${id}`);
  }
};

const paginate = (url) => {
  Inertia.visit(url);
};

const breadcrumbs = [
  { title: 'Dashboard', href: '/dashboard' },
  { title: 'Chi nhánh', href: '/admin/branches' },
];
</script>

<template>
  <Head :title="title || 'Danh sách Chi nhánh'" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="max-w-6xl mx-auto p-6">
      <div class="flex justify-between items-center mb-4">
        <h1 class="text-2xl font-semibold text-gray-800">Danh sách Chi nhánh</h1>
        <a :href="route('admin.branches.create')" class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700">Tạo mới chi nhánh</a>
      </div>
      
      <div v-if="branches.links" class="mb-4 flex space-x-2">
        <button v-for="link in branches.links" :key="link.label" :disabled="!link.url" @click="paginate(link.url)"
          class="px-3 py-1 border rounded hover:bg-gray-200 disabled:opacity-50">
          {{ link.label }}
        </button>
      </div>

      <div class="overflow-x-auto border rounded-lg shadow">
        <table class="w-full text-sm text-left text-gray-700">
          <thead class="bg-gray-100 text-gray-700">
            <tr>
              <th class="p-3">Tên chi nhánh</th>
              <th class="p-3">Địa chỉ</th>
              <th class="p-3">Điện thoại</th>
              <th class="p-3">Email</th>
              <th class="p-3 text-center">Hành động</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="branch in branches.data" :key="branch.id" class="border-b">
              <td class="p-3 whitespace-normal">{{ branch.name }}</td>
              <td class="p-3 whitespace-normal">{{ branch.address }}</td>
              <td class="p-3 whitespace-normal">{{ branch.phone_number }}</td>
              <td class="p-3 whitespace-normal">{{ branch.email }}</td>
              <td class="p-3 text-center">
                <router-link class="text-blue-600 hover:underline mr-2" :to="`/admin/branches/${branch.id}/edit`">
                  Sửa
                </router-link>
                <button class="text-red-600 hover:underline" @click="deleteBranch(branch.id)">
                  Xóa
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      
      <div v-if="branches.links" class="mt-4 flex space-x-2">
        <button v-for="link in branches.links" :key="link.label" :disabled="!link.url" @click="paginate(link.url)"
          class="px-3 py-1 border rounded hover:bg-gray-200 disabled:opacity-50">
          {{ link.label }}
        </button>
      </div>
    </div>
  </AppLayout>
</template>
