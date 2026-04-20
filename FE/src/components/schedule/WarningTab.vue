<template>
  <div class="warning-tab">
    <div v-if="schedule.warnings.length === 0" class="empty-state">
      ✓ Không có cảnh báo nào.
    </div>
    
    <div v-else class="warning-list">
      <div v-for="(warning, idx) in schedule.warnings" :key="idx" class="warning-item">
        <div class="warning-icon" :class="warning.level">
          <span v-if="warning.level === 'ERROR'">❌</span>
          <span v-else-if="warning.level === 'WARNING'">⚠️</span>
          <span v-else>ℹ️</span>
        </div>
        <div class="warning-content">
          <div class="warning-title">{{ warning.message || warning.title }}</div>
          <div v-if="warning.detail" class="warning-detail">{{ warning.detail }}</div>
          <div v-if="warning.employee_id" class="warning-meta">
            Nhân sự: {{ getEmployeeName(warning.employee_id) }} | 
            Ngày: {{ formatDate(warning.work_date) }}
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { useScheduleStore } from '@/stores/useScheduleStore.js';

const schedule = useScheduleStore();

const getEmployeeName = (employeeId) => {
  const emp = schedule.employees.find(e => e.employee_id === employeeId);
  return emp ? `${emp.full_name}` : 'Unknown';
};

const formatDate = (dateString) => {
  if (!dateString) return '';
  const d = new Date(dateString + 'T00:00:00');
  return d.toLocaleDateString('vi-VN');
};
</script>

<style scoped>
.warning-tab {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.empty-state {
  text-align: center;
  padding: 2rem;
  color: #2e7d32;
  background: #c8e6c9;
  border-radius: 4px;
  font-weight: 600;
}

.warning-list {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.warning-item {
  display: flex;
  gap: 1rem;
  padding: 1rem;
  border-radius: 4px;
  border-left: 4px solid #999;
}

.warning-item:has(.warning-icon.ERROR) {
  background: #ffebee;
  border-color: #f44336;
}

.warning-item:has(.warning-icon.WARNING) {
  background: #fff3e0;
  border-color: #ff9800;
}

.warning-item:has(.warning-icon.INFO) {
  background: #e3f2fd;
  border-color: #2196F3;
}

.warning-icon {
  font-size: 1.5rem;
  flex-shrink: 0;
}

.warning-content {
  flex: 1;
}

.warning-title {
  font-weight: 700;
  color: #333;
  margin-bottom: 0.25rem;
}

.warning-detail {
  font-size: 0.9rem;
  color: #666;
  margin-bottom: 0.5rem;
}

.warning-meta {
  font-size: 0.8rem;
  color: #999;
  font-style: italic;
}
</style>
