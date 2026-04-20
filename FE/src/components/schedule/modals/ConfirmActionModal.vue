<template>
  <div class="modal-overlay" @click.self="onClose">
    <div class="modal-content">
      <div class="modal-header">
        <h3>❓ {{ modal.title || 'Xác nhận' }}</h3>
        <button class="close-btn" @click="onClose">✕</button>
      </div>
      
      <div class="modal-body">
        <p class="message">{{ modal.message }}</p>
        
        <div class="modal-actions">
          <button class="btn btn-primary" @click="onConfirm" :disabled="submitting">
            <span v-if="!submitting">✓ Xác nhận</span>
            <span v-else>⏳ Đang xử lý...</span>
          </button>
          <button class="btn btn-cancel" @click="onClose">Hủy</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useScheduleStore } from '@/stores/useScheduleStore.js';

const schedule = useScheduleStore();
const submitting = ref(false);

const modal = computed(() => schedule.modals.confirmAction.data || {});

const onConfirm = async () => {
  try {
    submitting.value = true;
    
    const action = modal.value.action;
    const payload = modal.value.payload;
    
    let result;
    
    switch(action) {
      case 'assignShift':
        result = await schedule.assignShift(payload);
        break;
      case 'bulkAssignShift':
        result = await schedule.bulkAssignShift(payload);
        break;
      case 'copyWeek':
        result = await schedule.copyScheduleWeek(payload.from_date, payload.to_date);
        break;
      case 'publishSchedule':
        result = await schedule.publishSchedule();
        break;
      default:
        console.warn('Unknown action:', action);
        return;
    }
    
    // Show success
    schedule.openModal('success', {
      title: 'Thành công',
      message: 'Thao tác đã hoàn thành.',
    });
    
    schedule.closeModal('confirmAction');
  } catch (error) {
    alert('Error: ' + error.message);
  } finally {
    submitting.value = false;
  }
};

const onClose = () => {
  schedule.closeModal('confirmAction');
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
  max-width: 400px;
  width: 90%;
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
}

.message {
  margin: 0 0 1.5rem 0;
  color: #666;
  line-height: 1.5;
}

.modal-actions {
  display: flex;
  gap: 0.75rem;
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
  background: #4CAF50;
  color: white;
}

.btn-primary:hover:not(:disabled) {
  background: #45a049;
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
