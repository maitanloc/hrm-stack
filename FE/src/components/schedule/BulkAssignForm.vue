<template>
  <form @submit.prevent="onSubmit" class="bulk-form">
    <div class="form-group">
      <label>Chọn nhân sự:</label>
      <div class="employee-list">
        <label v-for="emp in employees" :key="emp.employee_id" class="checkbox-item">
          <input 
            type="checkbox" 
            :value="emp.employee_id"
            v-model.number="formData.employee_ids"
          />
          <span>{{ emp.full_name }} ({{ emp.employee_code }})</span>
        </label>
      </div>
    </div>
    
    <div class="form-group">
      <label>Chọn ca:</label>
      <select v-model="formData.shift_type_id" required class="form-input">
        <option value="" disabled>-- Chọn ca --</option>
        <option v-for="shift in shifts" :key="shift.shift_type_id" :value="shift.shift_type_id">
          {{ shift.shift_code }} - {{ shift.shift_name }}
        </option>
      </select>
    </div>
    
    <div class="form-group">
      <label>Ngày áp dụng:</label>
      <input v-model="formData.work_date" type="date" required class="form-input" />
    </div>
    
    <button type="submit" class="btn btn-primary" :disabled="submitting || formData.employee_ids.length === 0">
      <span v-if="!submitting">⚡ Gán Nhanh ({{ formData.employee_ids.length }}) người</span>
      <span v-else>⏳ Đang gán...</span>
    </button>
  </form>
</template>

<script setup>
import { ref } from 'vue';
import { useScheduleStore } from '@/stores/useScheduleStore.js';

defineProps({
  shifts: { type: Array, default: () => [] },
  employees: { type: Array, default: () => [] },
});

const schedule = useScheduleStore();
const submitting = ref(false);
const formData = ref({
  employee_ids: [],
  shift_type_id: '',
  work_date: '',
});

const onSubmit = async () => {
  if (formData.value.employee_ids.length === 0) return;
  
  try {
    submitting.value = true;
    
    schedule.openModal('preview', {
      title: 'Xác nhận gán nhanh',
      message: `Gán ${formData.value.employee_ids.length} nhân sự vào ngày ${formData.value.work_date}?`,
      action: 'bulkAssignShift',
      payload: {
        employee_ids: formData.value.employee_ids,
        shift_type_id: formData.value.shift_type_id,
        work_date: formData.value.work_date,
      },
    });
  } finally {
    submitting.value = false;
  }
};
</script>

<style scoped>
.bulk-form {
  display: flex;
  flex-direction: column;
  gap: 1rem;
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

.employee-list {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  max-height: 150px;
  overflow-y: auto;
  border: 1px solid #ddd;
  padding: 0.5rem;
  border-radius: 4px;
  background: #fafafa;
}

.checkbox-item {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  cursor: pointer;
  font-size: 0.95rem;
}

.checkbox-item input {
  cursor: pointer;
}

.form-input {
  padding: 0.5rem;
  border: 1px solid #ddd;
  border-radius: 4px;
  font-size: 0.95rem;
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

.btn-primary {
  background: #2196F3;
  color: white;
}

.btn-primary:hover:not(:disabled) {
  background: #1976D2;
}

.btn-primary:disabled {
  background: #ccc;
  cursor: not-allowed;
}
</style>
