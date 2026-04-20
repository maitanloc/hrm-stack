<template>
  <div class="schedule-header">
    <div class="header-row">
      <div class="header-title">
        <h2>Quản Lý Phân Ca Làm Việc</h2>
      </div>
      
      <div class="header-controls">
        <!-- Department select -->
        <div class="control-group">
          <label>Phòng ban:</label>
          <select 
            v-model="schedule.selectedDepartmentId"
            class="input-select"
            @change="onFilterChange"
          >
            <option value="" disabled>-- Chọn phòng ban --</option>
            <option 
              v-for="dept in departments" 
              :key="dept.department_id"
              :value="dept.department_id"
            >
              {{ dept.department_name }}
            </option>
          </select>
        </div>
        
        <!-- Date range -->
        <div class="control-group">
          <label>Từ ngày:</label>
          <input 
            v-model="schedule.selectedDateRange.from"
            type="date"
            class="input-date"
            @change="onFilterChange"
          />
        </div>
        
        <div class="control-group">
          <label>Đến ngày:</label>
          <input 
            v-model="schedule.selectedDateRange.to"
            type="date"
            class="input-date"
            @change="onFilterChange"
          />
        </div>
        
        <!-- Refresh button -->
        <button 
          class="btn-refresh"
          @click="onRefresh"
          :disabled="schedule.loading"
        >
          <span v-if="!schedule.loading">🔄 Làm mới</span>
          <span v-else>⏳ Đang tải...</span>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useScheduleStore } from '@/stores/useScheduleStore.js';
import { apiRequest } from '@/services/beApi.js';

const schedule = useScheduleStore();
const departments = ref([]);

onMounted(async () => {
  try {
    const response = await apiRequest('/departments', {
      query: { sort: 'department_name', sort_dir: 'asc' },
    });
    departments.value = response?.data || [];
  } catch (error) {
    console.error('Failed to fetch departments:', error);
  }
});

const onFilterChange = () => {
  // Store will watch and fetch
};

const onRefresh = () => {
  if (schedule.selectedDepartmentId && schedule.selectedDateRange.from && schedule.selectedDateRange.to) {
    schedule.fetchScheduleData(
      schedule.selectedDepartmentId,
      schedule.selectedDateRange.from,
      schedule.selectedDateRange.to
    );
  }
};
</script>

<style scoped>
.schedule-header {
  background: white;
  border-radius: 8px;
  padding: 1.5rem;
  margin-bottom: 1.5rem;
  box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.header-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 1.5rem;
}

.header-title h2 {
  margin: 0;
  font-size: 1.5rem;
  color: #333;
}

.header-controls {
  display: flex;
  gap: 1rem;
  flex-wrap: wrap;
  align-items: flex-end;
}

.control-group {
  display: flex;
  flex-direction: column;
  gap: 0.3rem;
}

.control-group label {
  font-size: 0.875rem;
  font-weight: 600;
  color: #666;
}

.input-select,
.input-date {
  padding: 0.5rem 0.75rem;
  border: 1px solid #ddd;
  border-radius: 4px;
  font-size: 0.95rem;
  min-width: 150px;
}

.input-select:focus,
.input-date:focus {
  outline: none;
  border-color: #2196F3;
  box-shadow: 0 0 0 3px rgba(33, 150, 243, 0.1);
}

.btn-refresh {
  padding: 0.5rem 1rem;
  background: #4CAF50;
  color: white;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  font-weight: 600;
  transition: background 0.2s;
}

.btn-refresh:hover:not(:disabled) {
  background: #45a049;
}

.btn-refresh:disabled {
  background: #ccc;
  cursor: not-allowed;
}

@media (max-width: 768px) {
  .header-row {
    flex-direction: column;
    align-items: stretch;
  }
  
  .header-controls {
    flex-direction: column;
    align-items: stretch;
  }
  
  .input-select,
  .input-date {
    width: 100%;
    min-width: auto;
  }
}
</style>
