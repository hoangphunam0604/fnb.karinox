<script setup lang="ts">
import { usePage } from "@inertiajs/vue3";

import LogoutButton from '@/Components/LogoutButton.vue';
const { auth } = usePage().props;
</script>

<template>
  <div class="container">
    <header>
      <h1>Chào mừng đến với hệ thống POS</h1>
      <p class="subtitle">Quản lý bán hàng hiệu quả và nhanh chóng</p>
    </header>

    <div v-if="auth?.user" class="welcome-box">
      <h2>Xin chào, {{ auth.user.name }} 👋</h2>
      <p>Chúc bạn một ngày làm việc vui vẻ!</p>

      <LogoutButton />
    </div>

    <div v-else class="auth-buttons">
      <a href="/login" class="btn login">Đăng nhập</a>
      <a href="/register" class="btn register">Đăng ký</a>
    </div>

  </div>
</template>

<style scoped>
.container {
  text-align: center;
  max-width: 600px;
  margin: auto;
  padding: 50px 20px;
}

header h1 {
  font-size: 2rem;
  color: #333;
}

.subtitle {
  color: #777;
  font-size: 1.2rem;
}

.welcome-box {
  background: #f0f4ff;
  padding: 20px;
  border-radius: 8px;
  box-shadow: 2px 2px 10px rgba(0, 0, 0, 0.1);
  margin-top: 20px;
}

.auth-buttons {
  margin-top: 20px;
}

.btn {
  display: inline-block;
  padding: 10px 20px;
  margin: 10px;
  border-radius: 5px;
  font-size: 1rem;
  text-decoration: none;
  font-weight: bold;
}

.login {
  background: #4caf50;
  color: white;
}

.register {
  background: #008cba;
  color: white;
}
</style>
