<template>
  <div>
    <h2>Chọn Chi Nhánh</h2>
    <button v-for="branch in branches" :key="branch.id" @click="selectBranch(branch.id)">
      {{ branch.name }}
    </button>
  </div>
</template>

<script setup>
import { defineProps } from "vue";
import { router } from "@inertiajs/vue3";
defineProps({ branches: Array });

const selectBranch = (branchId) => {
  router.post("/select-branch", { branch_id: branchId });
};
</script>
