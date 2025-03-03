<script setup lang="ts">
import { ref, onMounted } from "vue";
import axios from "axios";

interface Table {
  id: number;
  name: string;
  status: string;
}

const tables = ref<Table[]>([]);
const fetchTables = async () => {
  const response = await axios.get<Table[]>("/api/pos/tables-and-rooms");
  tables.value = response.data;
};

onMounted(fetchTables);
</script>

<template>
  <div class="flex h-screen p-4 bg-gray-100">
    <div class="w-full">
      <h2 class="text-2xl mb-4">Chọn bàn</h2>
      <div class="grid grid-cols-3 gap-4">
        <div v-for="area in areas" :key="area.id" class="border rounded-lg p-2">
          <h3 class="text-lg font-semibold mb-2">{{ area.name }}</h3>
          <div class="grid grid-cols-2 gap-2">
            <button
              v-for="table in area.tables"
              :key="table.id"
              @click="selectTable(table)"
              class="bg-blue-500 text-white p-2 rounded-md hover:bg-blue-600"
            >
              {{ table.name }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
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
