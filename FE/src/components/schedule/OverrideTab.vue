<template>
  <div class="override-tab">
    <div class="section-header">
      <h3>Danh Sách Ghi Đè</h3>
      <button class="btn btn-small" @click="onCreateNew">
        ➕ Tạo ghi đè mới
      </button>
    </div>
    
    <div v-if="schedule.overrides.length === 0" class="empty-state">
      Chưa có ghi đè nào.
    </div>
    
    <div v-else class="override-list">
      <div 
        v-for="override in schedule.overrides" 
        :key="`${override.employee_id}-${override.work_date}`"
        class="override-item"
        @click="onEditOverride(override)"
      >
        <div class="override-header">
          <span class="emp-name">{{ getEmployeeName(override.employee_id) }}</span>
          <span class="override-date">{{ formatDate(override.work_date) }}</span>
        </div>
        <div class="override-details">
          <span class="shift-code">{{ getShiftCode(override.shift_type_id) }}</span>
          <span class="override-reason">{{ override.reason || '-' }}</span>
        </div>
      </div>
    </div>
    
    <!-- Modal for edit -->
    <OverrideEditModal 
      v-if="schedule.modals.overrideEdit.open"
      :employee="schedule.modals.overrideEdit.data?.employee"
      :date="schedule.modals.overrideEdit.data?.date"
      :shifts="schedule.shifts"
    />
  </div>
</template>

<script setup>
import { useScheduleStore } from '@/stores/useScheduleStore.js';
import OverrideEditModal from './modals/OverrideEditModal.vue';

const schedule = useScheduleStore();

const getEmployeeName = (employeeId) => {
  const emp = schedule.employees.find(e => e.employee_id === employeeId);
  return emp ? `${emp.full_name} (${emp.employee_code})` : 'Unknown';
};

const getShiftCode = (shiftTypeId) => {
  if (!shiftTypeId) return '❌ Xóa';
  const shift = schedule.shifts.find(s => s.shift_type_id === shiftTypeId);
  return shift ? `${shift.shift_code}` : 'Unknown';
};

const formatDate = (dateString) => {
  const d = new Date(dateString + 'T00:00:00');
  return d.toLocaleDateString('vi-VN');
};

const onCreateNew = () => {
  schedule.openModal('overrideEdit', {
    employee: null,
    date: null,
  });
};

const onEditOverride = (override) => {
  const emp = schedule.employees.find(e => e.employee_id === override.employee_id);
  schedule.openModal('overrideEdit', {
    employee: emp,
    date: override.work_date,
    isEdit: true,
  });
};
</script>

<style scoped>
.override-tab {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.section-header h3 {
  margin: 0;
  color: #333;
}

.btn-small {
  padding: 0.5rem 1rem;
  background: #4CAF50;
  color: white;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  font-weight: 600;
}

.btn-small:hover {
  background: #45a049;
}

.empty-state {
  text-align: center;
  padding: 2rem;
  color: #999;
  background: #f9f9f9;
  border-radius: 4px;
}

.override-list {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.override-item {
  background: #f9f9f9;
  border: 1px solid #e0e0e0;
  border-radius: 4px;
  padding: 1rem;
  cursor: pointer;
  transition: all 0.2s;
}

.override-item:hover {
  background: #f0f0f0;
  border-color: #2196F3;
  box-shadow: 0 2px 4px rgba(33, 150, 243, 0.2);
}

.override-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.5rem;
}

.emp-name {
  font-weight: 700;
  color: #333;
}

.override-date {
  font-size: 0.85rem;
  color: #999;
}

.override-details {
  display: flex;
  gap: 1rem;
  font-size: 0.9rem;
}

.shift-code {
  font-weight: 600;
  background: #e3f2fd;
  padding: 0.2rem 0.5rem;
  border-radius: 3px;
  color: #1565c0;
}

.override-reason {
  color: #666;
  flex: 1;
}
</style>
