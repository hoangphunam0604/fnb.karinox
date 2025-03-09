<script setup>
import { ref } from 'vue';
import { usePage } from '@inertiajs/inertia-vue3';
import { Inertia } from '@inertiajs/inertia';
import { route } from 'ziggy-js'; // Đảm bảo import đúng từ Ziggy

import Main from '@/Admin/Layouts/Main.vue';
const page = usePage();
const title = page.props.title || 'Tạo mới Chi nhánh';

const form = ref({
  name: '',
  address: '',
  phone_number: '',
  email: '',
});

const submitForm = () => {
  Inertia.post(route('admin.branches.store'), form.value);
};
</script>

<template>
  <Main>
    <div class="page-titles">
      <ol class="breadcrumb">
        <li class="breadcrumb-item">
          <a :href="route('admin.dashboard')">Dashboard</a>
        </li>
        <li class="breadcrumb-item active">
          <a href="javascript:void(0)">Chi nhánh</a>
        </li>
      </ol>
    </div>
    <div class="card">
      <div class="card-header">
        <h4 class="card-title">{{ title }}</h4>
      </div>
      <div class="card-body">
        <!-- Nội dung form ở đây -->
        <form @submit.prevent="submitForm">
          <div class="mb-3">
            <label for="name" class="form-label">Tên chi nhánh</label>
            <input type="text" class="form-control" id="name" v-model="form.name" placeholder="Nhập tên chi nhánh" required />
          </div>
          <div class="mb-3">
            <label for="address" class="form-label">Địa chỉ</label>
            <input type="text" class="form-control" id="address" v-model="form.address" placeholder="Nhập địa chỉ" required />
          </div>
          <div class="mb-3">
            <label for="phone_number" class="form-label">Số điện thoại</label>
            <input type="text" class="form-control" id="phone_number" v-model="form.phone_number" placeholder="Nhập số điện thoại" />
          </div>
          <div class="mb-3">
            <label for="email" class="form-label">Email</label>
            <input type="email" class="form-control" id="email" v-model="form.email" placeholder="Nhập email" />
          </div>
          <button type="submit" class="btn btn-primary">Lưu</button>
        </form>
      </div>
    </div>
  </Main>
</template>