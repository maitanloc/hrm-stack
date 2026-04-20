<template>
  <div class="base-schedule-tab">
    <!-- Status banner if published -->
    <div v-if="schedule.isPublished" class="status-banner published">
      🔒 Lịch đã chốt. Các sửa đổi phải qua Tab 2 (Ghi đè).
    </div>
    
    <!-- Forms section -->
    <div class="forms-section">
      <!-- Form 1: Assign base schedule (disabled if locked) -->
      <div class="form-card">
        <h3>📋 Gán Ca Mặc Định</h3>
        <div v-if="!schedule.canEditBaseSchedule" class="form-locked">
          ⚠️ Lịch đã chốt. Không thể sửa lịch nền.
        </div>
        <AssignShiftForm v-else :shifts="schedule.shifts" />
      </div>
      
      <!-- Form 2: Bulk assign -->
      <div class="form-card">
        <h3>⚡ Gán Nhanh Hàng Loạt</h3>
        <div v-if="!schedule.canEditBaseSchedule" class="form-locked">
          ⚠️ Lịch đã chốt. Không thể sửa lịch nền.
        </div>
        <BulkAssignForm v-else :shifts="schedule.shifts" :employees="schedule.employees" />
      </div>
      
      <!-- Form 3: Copy week -->
      <div class="form-card">
        <h3>📋 Copy Tuần</h3>
        <div v-if="!schedule.canEditBaseSchedule" class="form-locked">
          ⚠️ Lịch đã chốt. Không thể sửa lịch nền.
        </div>
        <CopyWeekForm v-else />
      </div>
    </div>
    
    <!-- Matrix view section -->
    <div class="matrix-section">
      <h3>📊 Ma Trận Tuần</h3>
      <WeeklyMatrixGrid 
        :employees="schedule.employees"
        :assignments="schedule.assignments"
        :overrides="schedule.overrides"
        :shifts="schedule.shifts"
        :can-edit="schedule.canEditBaseSchedule"
      />
    </div>
  </div>
</template>

<script setup>
import { useScheduleStore } from '@/stores/useScheduleStore.js';
import AssignShiftForm from './AssignShiftForm.vue';
import BulkAssignForm from './BulkAssignForm.vue';
import CopyWeekForm from './CopyWeekForm.vue';
import WeeklyMatrixGrid from './WeeklyMatrixGrid.vue';

const schedule = useScheduleStore();
</script>

<style scoped>
.base-schedule-tab {
  display: flex;
  flex-direction: column;
  gap: 2rem;
}

.status-banner {
  padding: 1rem;
  border-radius: 4px;
  font-weight: 600;
  text-align: center;
}

.status-banner.published {
  background: #fff3cd;
  border: 1px solid #ffc107;
  color: #856404;
}

.forms-section {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
  gap: 1.5rem;
}

.form-card {
  background: #f9f9f9;
  border: 1px solid #e0e0e0;
  border-radius: 8px;
  padding: 1.5rem;
}

.form-card h3 {
  margin-top: 0;
  margin-bottom: 1rem;
  color: #333;
}

.form-locked {
  background: #ffebee;
  border: 1px solid #ef5350;
  color: #c62828;
  padding: 1rem;
  border-radius: 4px;
  text-align: center;
  font-weight: 600;
}

.matrix-section {
  background: #f5f5f5;
  border-radius: 8px;
  padding: 1.5rem;
}

.matrix-section h3 {
  margin-top: 0;
  margin-bottom: 1rem;
  color: #333;
}

@media (max-width: 1024px) {
  .forms-section {
    grid-template-columns: 1fr;
  }
}
</style>
