<script setup>
import { defineProps } from 'vue';
import { Inertia } from '@inertiajs/inertia';

import Main from '@/Admin/Layouts/Main.vue';
const props = defineProps({
  branches: Object,
  title: String
});

const deleteBranch = (id) => {
  if (confirm('Bạn có chắc chắn muốn xóa chi nhánh này?')) {
    Inertia.delete(`/admin/branches/${id}`);
  }
};

const paginate = (url) => {
  Inertia.visit(url);
};
</script>

<template>
  <Main>
    <div class="page-titles">
      <ol class="breadcrumb">
        <li class="breadcrumb-item"><a :href="route('admin.dashboard')">Dashboard</a></li>
        <li class="breadcrumb-item active"><a href="javascript:void(0)">Chi nhánh</a></li>
      </ol>
    </div>
    <div class="card">
      <div class="card-header">
        <h4 class="card-title">{{ title }}</h4>
      </div>
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-responsive-md">
            <thead>
              <tr>
                <th>Tên chi nhánh</th>
                <th>Địa chỉ</th>
                <th>Điện thoại</th>
                <th>Email</th>
                <th>Hành động</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="branch in branches.data" :key="branch.id">
                <td>{{ branch.name }}</td>
                <td>{{ branch.address }}</td>
                <td>{{ branch.phone_number }}</td>
                <td>{{ branch.email }}</td>
                <td>
                  <router-link class="btn btn-primary shadow btn-xs sharp mr-1" :to="`/admin/branches/${branch.id}/edit`">
                    <i class="fa fa-pencil"></i>
                  </router-link>
                  <button class="btn btn-danger shadow btn-xs sharp" @click="deleteBranch(branch.id)">
                    <i class="fa fa-trash"></i>
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
    <a :href="route('admin.branches.create')" class="btn btn-primary">
      Tạo mới chi nhánh
    </a>

    <div v-if="branches.links">
      <button v-for="link in branches.links" :key="link.label" :disabled="!link.url" @click="paginate(link.url)">
        {{ link.label }}
      </button>
    </div>
  </Main>
</template>
