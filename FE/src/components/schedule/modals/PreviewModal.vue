<template>
  <div class="modal-overlay" @click.self="onClose">
    <div class="modal-content">
      <div class="modal-header">
        <h3>📋 {{ modal.title || 'Preview' }}</h3>
        <button class="close-btn" @click="onClose">✕</button>
      </div>
      
      <div class="modal-body">
        <p class="message">{{ modal.message }}</p>
        
        <div v-if="modal.preview" class="preview-section">
          <div v-for="(item, idx) in modal.preview" :key="idx" class="preview-item">
            {{ item }}
          </div>
        </div>
        
        <div v-if="modal.count" class="count-section">
          <strong>Ảnh hưởng:</strong> {{ modal.count }} mục dữ liệu
        </div>
        
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

const modal = computed(() => schedule.modals.preview.data || {});

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
      default:
        console.warn('Unknown action:', action);
        return;
    }
    
    schedule.openModal('success', {
      title: 'Thành công',
      message: 'Thao tác đã hoàn thành.',
    });
    
    schedule.closeModal('preview');
  } catch (error) {
    alert('Error: ' + error.message);
  } finally {
    submitting.value = false;
  }
};

const onClose = () => {
  schedule.closeModal('preview');
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
}

.message {
  margin: 0 0 1rem 0;
  color: #666;
  line-height: 1.5;
}

.preview-section {
  background: #f5f5f5;
  border-radius: 4px;
  padding: 1rem;
  margin: 1rem 0;
  max-height: 200px;
  overflow-y: auto;
}

.preview-item {
  padding: 0.5rem;
  border-bottom: 1px solid #eee;
  font-size: 0.9rem;
  color: #666;
}

.preview-item:last-child {
  border-bottom: none;
}

.count-section {
  background: #e3f2fd;
  padding: 0.75rem;
  border-radius: 4px;
  color: #1565c0;
  margin: 1rem 0;
}

.modal-actions {
  display: flex;
  gap: 0.75rem;
  margin-top: 1.5rem;
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
