<template>
  <form @submit.prevent="onSubmit" class="copy-form">
    <p class="info-text">
      Copy lịch từ tuần trước sang tuần hiện tại.
    </p>
    
    <div class="form-group">
      <label>Nhân xét:</label>
      <textarea v-model="formData.notes" class="form-input" rows="3" placeholder="Lý do copy..." />
    </div>
    
    <button type="submit" class="btn btn-secondary" :disabled="submitting">
      <span v-if="!submitting">📋 Copy Tuần Trước</span>
      <span v-else>⏳ Đang copy...</span>
    </button>
  </form>
</template>

<script setup>
import { ref } from 'vue';
import { useScheduleStore } from '@/stores/useScheduleStore.js';
import { useWeekMatrix } from '@/composables/useWeekMatrix.js';

const schedule = useScheduleStore();
const weekMatrix = useWeekMatrix(schedule.selectedDateRange);

const submitting = ref(false);
const formData = ref({
  notes: '',
});

const onSubmit = async () => {
  try {
    submitting.value = true;
    
    // For Phase 1, just copy from week before to current week
    // In Phase 3, we'll add more flexible week selection
    const currentWeekStart = weekMatrix.currentWeek.value?.startDate;
    const prevWeekStart = new Date(currentWeekStart);
    prevWeekStart.setDate(prevWeekStart.getDate() - 7);
    
    schedule.openModal('confirmAction', {
      title: 'Xác nhận copy tuần',
      message: `Copy lịch từ tuần trước? Sẽ ghi đè tất cả trong tuần hiện tại.`,
      action: 'copyWeek',
      payload: {
        from_date: prevWeekStart.toISOString().split('T')[0],
        to_date: currentWeekStart,
        notes: formData.value.notes,
      },
    });
  } finally {
    submitting.value = false;
  }
};
</script>

<style scoped>
.copy-form {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.info-text {
  margin: 0;
  padding: 0.75rem;
  background: #e3f2fd;
  color: #1565c0;
  border-radius: 4px;
  font-size: 0.9rem;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 0.3rem;
}

.form-group label {
  font-size: 0.85rem;
  font-weight: 600;
  color: #666;
}

.form-input {
  padding: 0.5rem;
  border: 1px solid #ddd;
  border-radius: 4px;
  font-size: 0.95rem;
  font-family: inherit;
}

.form-input:focus {
  outline: none;
  border-color: #2196F3;
  box-shadow: 0 0 0 3px rgba(33, 150, 243, 0.1);
}

.btn {
  padding: 0.75rem 1.5rem;
  border: none;
  border-radius: 4px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-secondary {
  background: #FF9800;
  color: white;
}

.btn-secondary:hover:not(:disabled) {
  background: #F57C00;
}

.btn-secondary:disabled {
  background: #ccc;
  cursor: not-allowed;
}
</style>
