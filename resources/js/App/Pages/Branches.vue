<template>
  <div>
    <h2>Chọn Chi Nhánh</h2>
    <ul>
      <li>
        <button v-for="branch in branches" :key="branch.id" @click="selectBranch(branch.id)">
          {{ branch.name }}
        </button>
      </li>
    </ul>
  </div>
</template>

<script setup>
import { defineProps, onMounted } from "vue";
import { router } from "@inertiajs/vue3";
import { route } from 'ziggy-js';
import { useUserStore } from "@/stores/useUserStore";
const userStore = useUserStore();
defineProps({ branches: Array });
onMounted(() => {
  if (userStore.user) {
    console.log('userStore', userStore.user.login_redirect)
  }
});
const selectBranch = (branchId) => {
  router.post(route('branches.select'), { branch_id: branchId });
};
</script>
