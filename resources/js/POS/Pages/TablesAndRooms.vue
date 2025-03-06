<script setup>
import { ref, onMounted } from "vue";

import POSLayout from '@/POS/Layouts/POSLayout.vue';
const props = defineProps({
  areas: {
    type: Array,
    default: () => [],
  },
});
onMounted(() => {
  console.log('userStore', props.areas)
});
</script>

<template>
  <POSLayout>
    <div class="flex h-screen p-4 bg-gray-100">
      <div class="w-full">
        <h2 class="text-2xl mb-4">Chọn bàn</h2>
        <div v-for="area in areas" :key="area.id" class="border rounded-lg p-2">

          <div class="w-full">
            <h3 class="text-lg font-semibold mb-2">{{ area.name }}</h3>
            <div v-for="table in area.tables" :key="table.id" class="grid grid-cols-2 gap-2">
              <button @click="selectTable(table)" class="bg-blue-500 text-white p-2 rounded-md hover:bg-blue-600">
                {{ table.name }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </POSLayout>
</template>

<style scoped>
.container {
  padding: 20px;
}

.filter {
  margin-bottom: 10px;
}

.grid {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
}

.table-card {
  width: 120px;
  height: 100px;
  background: lightgray;
  border-radius: 8px;
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  cursor: pointer;
}

.table-card.occupied {
  background: red;
  color: white;
}
</style>
