<template>
  <div class="modal-overlay" @click.self="onClose">
    <div class="modal-content">
      <div class="modal-header success">
        <h3>✅ {{ modal.title || 'Thành công' }}</h3>
        <button class="close-btn" @click="onClose">✕</button>
      </div>
      
      <div class="modal-body">
        <p class="message">{{ modal.message }}</p>
        
        <div class="modal-actions">
          <button class="btn btn-primary" @click="onClose">Đóng</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { useScheduleStore } from '@/stores/useScheduleStore.js';

const schedule = useScheduleStore();

const modal = computed(() => schedule.modals.success.data || {});

const onClose = () => {
  schedule.closeModal('success');
};
</script>

<style scoped>
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.3);
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
  border-bottom: 2px solid #4CAF50;
}

.modal-header.success {
  background: #c8e6c9;
}

.modal-header h3 {
  margin: 0;
  color: #2e7d32;
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
  padding: 2rem 1.5rem;
  text-align: center;
}

.message {
  margin: 0;
  color: #666;
  line-height: 1.6;
  font-size: 1.05rem;
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
  background: #4CAF50;
  color: white;
}

.btn-primary:hover {
  background: #45a049;
}
</style>
