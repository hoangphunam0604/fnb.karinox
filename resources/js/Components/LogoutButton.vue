<script setup>
import { router } from '@inertiajs/vue3';
import { route } from 'ziggy-js'; // Đảm bảo import đúng từ Ziggy
import { useUserStore } from '@/stores/useUserStore';

const userStore = useUserStore();

const logout = async () => {
  await router.post(route('logout'), {}, {
    onSuccess: () => {
      userStore.clearUser(); // Xóa dữ liệu người dùng trong Pinia
    }
  });
};
</script>

<template>
  <button @click="logout" class="btn btn-danger">
    Đăng xuất
  </button>
</template>
