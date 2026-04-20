<template>
  <form @submit.prevent="onSubmit" class="assign-form">
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
      <label>Áp dụng từ ngày:</label>
      <input v-model="formData.from_date" type="date" required class="form-input" />
    </div>
    
    <div class="form-group">
      <label>Đến ngày:</label>
      <input v-model="formData.to_date" type="date" required class="form-input" />
    </div>
    
    <div class="form-group">
      <label>Ghi chú:</label>
      <textarea v-model="formData.notes" class="form-input" rows="3" />
    </div>
    
    <button type="submit" class="btn btn-primary" :disabled="submitting">
      <span v-if="!submitting">✓ Gán Ca Mặc Định</span>
      <span v-else>⏳ Đang gán...</span>
    </button>
  </form>
</template>

<script setup>
import { ref } from 'vue';
import { useScheduleStore } from '@/stores/useScheduleStore.js';

defineProps({
  shifts: {
    type: Array,
    default: () => [],
  },
});

const schedule = useScheduleStore();
const submitting = ref(false);
const formData = ref({
  shift_type_id: '',
  from_date: '',
  to_date: '',
  notes: '',
});

const onSubmit = async () => {
  try {
    submitting.value = true;
    
    // Show preview modal
    schedule.openModal('preview', {
      title: 'Xác nhận gán ca',
      message: `Gán ca ID ${formData.value.shift_type_id} từ ngày ${formData.value.from_date} đến ${formData.value.to_date}?`,
      action: 'assignShift',
      payload: {
        department_id: schedule.selectedDepartmentId,
        shift_type_id: formData.value.shift_type_id,
        from_date: formData.value.from_date,
        to_date: formData.value.to_date,
        notes: formData.value.notes,
      },
    });
  } finally {
    submitting.value = false;
  }
};
</script>

<style scoped>
.assign-form {
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
