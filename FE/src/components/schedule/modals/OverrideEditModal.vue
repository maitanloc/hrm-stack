<template>
  <div class="modal-overlay" @click.self="onClose">
    <div class="modal-content">
      <div class="modal-header">
        <h3>Ghi Đè Ca Làm Việc</h3>
        <button class="close-btn" @click="onClose">✕</button>
      </div>
      
      <div class="modal-body">
        <!-- Employee & Date (read-only) -->
        <div class="info-section">
          <div class="info-row">
            <label>Nhân sự:</label>
            <span>{{ employee?.full_name || 'N/A' }} ({{ employee?.employee_code || '' }})</span>
          </div>
          <div class="info-row">
            <label>Ngày:</label>
            <span>{{ formatDate(date) }}</span>
          </div>
        </div>
        
        <!-- Current assignment (if exists) -->
        <div v-if="currentBase" class="info-section">
          <div class="info-row">
            <label>Lịch nền hiện tại:</label>
            <span>{{ currentBase }}</span>
          </div>
        </div>
        
        <!-- Override form -->
        <form @submit.prevent="onSubmit" class="override-form">
          <div class="form-group">
            <label>Chọn ca mới (hoặc để trống nếu xóa):</label>
            <select v-model="formData.shift_type_id" class="form-input">
              <option value="">-- Không ghi đè (Xóa) --</option>
              <option v-for="shift in shifts" :key="shift.shift_type_id" :value="shift.shift_type_id">
                {{ shift.shift_code }} - {{ shift.shift_name }}
              </option>
              <option value="null">❌ Xóa ghi đè này</option>
            </select>
          </div>
          
          <div class="form-group">
            <label>Lý do:</label>
            <select v-model="formData.reason" class="form-input">
              <option value="">-- Chọn lý do --</option>
              <option value="Yêu cầu">Yêu cầu</option>
              <option value="Phép">Phép</option>
              <option value="Bệnh">Bệnh</option>
              <option value="WFH">WFH</option>
              <option value="Công tác">Công tác</option>
              <option value="Khác">Khác</option>
            </select>
          </div>
          
          <div class="form-group">
            <label>Ghi chú:</label>
            <textarea v-model="formData.notes" class="form-input" rows="2" />
          </div>
          
          <div class="modal-actions">
            <button type="submit" class="btn btn-primary" :disabled="submitting">
              <span v-if="!submitting">✓ Lưu</span>
              <span v-else>⏳ Đang lưu...</span>
            </button>
            <button 
              v-if="isEdit"
              type="button" 
              class="btn btn-danger" 
              @click="onDelete"
              :disabled="submitting"
            >
              <span v-if="!submitting">🗑️ Xóa</span>
              <span v-else>⏳ Đang xóa...</span>
            </button>
            <button type="button" class="btn btn-cancel" @click="onClose">Hủy</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useScheduleStore } from '@/stores/useScheduleStore.js';

const props = defineProps({
  employee: { type: Object, default: null },
  date: { type: String, default: null },
  currentAssignment: { type: Object, default: null },
  shifts: { type: Array, default: () => [] },
});

const schedule = useScheduleStore();
const submitting = ref(false);

const formData = ref({
  shift_type_id: '',
  reason: '',
  notes: '',
});

const isEdit = computed(() => {
  return schedule.modals.overrideEdit.data?.isEdit || false;
});

const currentBase = computed(() => {
  if (!props.currentAssignment) return null;
  if (props.currentAssignment.type === 'base') {
    const shift = props.shifts.find(s => s.shift_type_id === props.currentAssignment.data.shift_type_id);
    return shift ? `${shift.shift_code} (Lịch nền)` : 'Lịch nền';
  }
  return null;
});

const formatDate = (dateString) => {
  if (!dateString) return '';
  const d = new Date(dateString + 'T00:00:00');
  return d.toLocaleDateString('vi-VN', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
};

const onSubmit = async () => {
  if (!props.employee || !props.date) {
    alert('Thiếu thông tin nhân sự hoặc ngày');
    return;
  }
  
  if (formData.value.shift_type_id === 'null') {
    // Delete override
    await onDelete();
    return;
  }
  
  try {
    submitting.value = true;
    
    const payload = {
      employee_id: props.employee.employee_id,
      work_date: props.date,
      shift_type_id: formData.value.shift_type_id || null,
      reason: formData.value.reason,
      notes: formData.value.notes,
    };
    
    await schedule.createOrUpdateOverride(payload);
    
    schedule.openModal('success', {
      title: 'Ghi đè thành công',
      message: 'Ghi đè ca làm việc đã được cập nhật.',
    });
    
    schedule.closeModal('overrideEdit');
  } catch (error) {
    alert('Error: ' + error.message);
  } finally {
    submitting.value = false;
  }
};

const onDelete = async () => {
  if (!confirm('Xác nhận xóa ghi đè này?')) return;
  
  try {
    submitting.value = true;
    await schedule.deleteOverride(props.employee.employee_id, props.date);
    
    schedule.openModal('success', {
      title: 'Xóa ghi đè thành công',
      message: 'Ghi đè đã được xóa. Sẽ quay lại lịch nền.',
    });
    
    schedule.closeModal('overrideEdit');
  } catch (error) {
    alert('Error: ' + error.message);
  } finally {
    submitting.value = false;
  }
};

const onClose = () => {
  schedule.closeModal('overrideEdit');
};
</script>

<style scoped>
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}

.modal-content {
  background: white;
  border-radius: 8px;
  max-width: 500px;
  width: 90%;
  max-height: 80vh;
  overflow: auto;
  box-shadow: 0 5px 40px rgba(0, 0, 0, 0.16);
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.5rem;
  border-bottom: 1px solid #eee;
}

.modal-header h3 {
  margin: 0;
  color: #333;
}

.close-btn {
  background: none;
  border: none;
  font-size: 1.5rem;
  cursor: pointer;
  color: #999;
}

.close-btn:hover {
  color: #333;
}

.modal-body {
  padding: 1.5rem;
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.info-section {
  background: #f5f5f5;
  padding: 1rem;
  border-radius: 4px;
}

.info-row {
  display: flex;
  justify-content: space-between;
  margin-bottom: 0.5rem;
}

.info-row:last-child {
  margin-bottom: 0;
}

.info-row label {
  font-weight: 600;
  color: #333;
}

.info-row span {
  color: #666;
  text-align: right;
}

.override-form {
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
  font-weight: 600;
  color: #333;
  font-size: 0.9rem;
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

.modal-actions {
  display: flex;
  gap: 0.75rem;
  margin-top: 1rem;
}

.btn {
  padding: 0.75rem 1rem;
  border: none;
  border-radius: 4px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
  flex: 1;
}

.btn-primary {
  background: #2196F3;
  color: white;
}

.btn-primary:hover:not(:disabled) {
  background: #1976D2;
}

.btn-danger {
  background: #f44336;
  color: white;
}

.btn-danger:hover:not(:disabled) {
  background: #d32f2f;
}

.btn-cancel {
  background: #ccc;
  color: #333;
}

.btn-cancel:hover {
  background: #bbb;
}

.btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}
</style>
