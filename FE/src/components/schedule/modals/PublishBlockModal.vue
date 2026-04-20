<template>
  <div class="modal-overlay" @click.self="onClose">
    <div class="modal-content">
      <div class="modal-header error">
        <h3>❌ {{ modal.title || 'Lỗi' }}</h3>
        <button class="close-btn" @click="onClose">✕</button>
      </div>
      
      <div class="modal-body">
        <p class="message">{{ modal.message }}</p>
        
        <div v-if="modal.errors && modal.errors.length > 0" class="error-list">
          <div class="error-title">Lỗi phát hiện:</div>
          <ul>
            <li v-for="(err, idx) in modal.errors" :key="idx">
              {{ err.message || err }}
            </li>
          </ul>
        </div>
        
        <div class="modal-actions">
          <button class="btn btn-cancel" @click="onClose">Đóng</button>
          <button class="btn btn-secondary" @click="onBack">Quay lại</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { useScheduleStore } from '@/stores/useScheduleStore.js';

const schedule = useScheduleStore();

const modal = computed(() => schedule.modals.blockPublish.data || {});

const onClose = () => {
  schedule.closeModal('blockPublish');
};

const onBack = () => {
  // Go back to Tab 3 (warnings)
  schedule.setActiveTab(3);
  schedule.closeModal('blockPublish');
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
  border-bottom: 2px solid #f44336;
}

.modal-header.error {
  background: #ffebee;
}

.modal-header h3 {
  margin: 0;
  color: #c62828;
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
  color: #333;
  line-height: 1.5;
  font-weight: 600;
}

.error-list {
  background: #fff3e0;
  border: 1px solid #ff9800;
  border-radius: 4px;
  padding: 1rem;
  margin: 1rem 0;
}

.error-title {
  font-weight: 700;
  color: #e65100;
  margin-bottom: 0.5rem;
}

.error-list ul {
  margin: 0;
  padding-left: 1.5rem;
}

.error-list li {
  color: #666;
  margin-bottom: 0.5rem;
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

.btn-cancel {
  background: #f44336;
  color: white;
}

.btn-cancel:hover {
  background: #d32f2f;
}

.btn-secondary {
  background: #ff9800;
  color: white;
}

.btn-secondary:hover {
  background: #f57c00;
}
</style>
