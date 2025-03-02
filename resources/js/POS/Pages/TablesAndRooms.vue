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
  const response = await axios.get<Table[]>("/api/POS/tables-and-rooms");
  tables.value = response.data;
};

onMounted(fetchTables);
</script>

<template>
  <div>
    <h1>Danh sách bàn/phòng</h1>
    <div v-for="area in areas" :key="area.id">
      <h2>{{ area.name }}</h2>
      <p>{{ area.note }}</p>
      <ul>
        <li v-for="table in tables" :key="table.id">
          {{ table.name }} - {{ table.status }}
        </li>
      </ul>
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
