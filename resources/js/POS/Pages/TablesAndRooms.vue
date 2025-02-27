<template>
  <POSLayout>
    <div class="container">
      <h1>Chọn Bàn/Phòng</h1>

      <div class="filter">
        <label>Chi nhánh: </label>
        <select v-model="branchId" @change="fetchTables">
          <option v-for="branch in branches" :key="branch.id" :value="branch.id">
            {{ branch.name }}
          </option>
        </select>
      </div>

      <div class="grid">
        <div v-for="table in tables" :key="table.id" class="table-card" :class="{ occupied: table.status !== 'trống' }" @click="selectTable(table)">
          <h3>{{ table.name }}</h3>
          <p>{{ table.status }}</p>
        </div>
      </div>
    </div>
  </POSLayout>
</template>

<script setup>
import { ref, onMounted } from "vue";
import axios from "axios";
import POSLayout from "@/POS/Layouts/POSLayout.vue";

const tables = ref([]);
const branches = ref([]);
const branchId = ref(null);

onMounted(async () => {
  // Lấy danh sách chi nhánh
  const branchRes = await axios.get("/api/branches");
  branches.value = branchRes.data;
  branchId.value = branches.value[0]?.id || null;

  // Lấy danh sách bàn/phòng theo chi nhánh đầu tiên
  fetchTables();
});

const fetchTables = async () => {
  if (!branchId.value) return;
  const response = await axios.get("/api/tables-and-rooms", {
    params: { branch_id: branchId.value },
  });
  tables.value = response.data;
};

const selectTable = (table) => {
  if (table.status !== "trống") {
    alert("Bàn này đang được sử dụng!");
    return;
  }
  alert(`Chọn ${table.name} thành công!`);
};
</script>

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
