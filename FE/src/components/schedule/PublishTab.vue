<template>
  <div class="publish-tab">
    <!-- Status card -->
    <div class="status-card" :class="{ published: schedule.isPublished }">
      <div class="status-icon">
        <span v-if="schedule.isPublished">🔒</span>
        <span v-else>📋</span>
      </div>
      <div class="status-content">
        <div class="status-label">Trạng thái lịch</div>
        <div class="status-value">
          <span v-if="schedule.isPublished" class="badge badge-success">ĐÃ CHỐT</span>
          <span v-else class="badge badge-draft">DRAFT</span>
        </div>
        <div v-if="schedule.isPublished" class="status-meta">
          Chốt lúc: {{ formatDateTime(schedule.publishedAt) }} bởi {{ schedule.publishedBy }}
        </div>
      </div>
    </div>
    
    <!-- Validation checklist -->
    <div class="checklist-card">
      <h3>Kiểm Tra Trước Publish</h3>
      <div class="checklist">
        <div class="check-item" :class="{ pass: schedule.totalEmployees > 0 }">
          <span class="check-icon">{{ schedule.totalEmployees > 0 ? '✓' : '✗' }}</span>
          <span class="check-text">Toàn bộ nhân sự đã gán ({{ schedule.totalEmployees }}/{{ schedule.totalEmployees }})</span>
        </div>
        <div class="check-item" :class="{ pass: schedule.unassignedEmployees === 0 }">
          <span class="check-icon">{{ schedule.unassignedEmployees === 0 ? '✓' : '✗' }}</span>
          <span class="check-text">Không còn vị trí chưa gán ({{ schedule.unassignedEmployees }} chưa)</span>
        </div>
        <div class="check-item" :class="{ pass: schedule.warningCount === 0, warning: schedule.warningCount > 0 }">
          <span class="check-icon">{{ schedule.warningCount === 0 ? '✓' : '⚠️' }}</span>
          <span class="check-text">Cảnh báo ({{ schedule.warningCount }} cảnh báo)</span>
        </div>
        <div class="check-item pass">
          <span class="check-icon">✓</span>
          <span class="check-text">API validation (sẽ kiểm tra khi publish)</span>
        </div>
      </div>
    </div>
    
    <!-- Action buttons -->
    <div class="action-section">
      <button 
        v-if="!schedule.isPublished"
        class="btn btn-publish"
        @click="onPublish"
        :disabled="schedule.submitting"
      >
        <span v-if="!schedule.submitting">🔒 Chốt Lịch</span>
        <span v-else>⏳ Đang xác thực...</span>
      </button>
      
      <button 
        v-if="schedule.isPublished"
        class="btn btn-secondary"
        @click="onRefresh"
        :disabled="schedule.submitting"
      >
        🔄 Làm mới trạng thái
      </button>
    </div>
    
    <!-- Note -->
    <div class="note-section">
      <p><strong>⚠️ Lưu ý:</strong></p>
      <ul>
        <li>Sau khi chốt, lịch nền sẽ bị khóa</li>
        <li>Chỉ có thể tạo ghi đè (Tab 2) để sửa lịch</li>
        <li>Nhân sự sẽ được thông báo khi lịch chốt</li>
      </ul>
    </div>
  </div>
</template>

<script setup>
import { useScheduleStore } from '@/stores/useScheduleStore.js';

const schedule = useScheduleStore();

const formatDateTime = (dateString) => {
  if (!dateString) return '';
  const d = new Date(dateString);
  return d.toLocaleString('vi-VN');
};

const onPublish = async () => {
  try {
    // Validate first
    const validation = await schedule.validatePublish();
    
    if (validation?.success === false || validation?.data?.has_error) {
      schedule.openModal('blockPublish', {
        title: 'Không thể chốt lịch',
        errors: validation.data?.errors || [],
        message: validation.data?.message || 'Có lỗi không cho phép chốt lịch',
      });
      return;
    }
    
    // All good, show confirm
    schedule.openModal('confirmAction', {
      title: 'Xác nhận chốt lịch',
      message: 'Bạn sắp chốt lịch. Lịch nền sẽ bị khóa. Tiếp tục?',
      action: 'publishSchedule',
      payload: {},
    });
  } catch (error) {
    console.error('Validation error:', error);
    schedule.openModal('blockPublish', {
      title: 'Lỗi khi xác thực',
      message: error.message || 'Lỗi không xác định',
    });
  }
};

const onRefresh = () => {
  if (schedule.selectedDepartmentId && schedule.selectedDateRange.from && schedule.selectedDateRange.to) {
    schedule.fetchScheduleData(
      schedule.selectedDepartmentId,
      schedule.selectedDateRange.from,
      schedule.selectedDateRange.to
    );
  }
};
</script>

<style scoped>
.publish-tab {
  display: flex;
  flex-direction: column;
  gap: 2rem;
}

.status-card {
  background: #f9f9f9;
  border: 2px solid #ff9800;
  border-radius: 8px;
  padding: 2rem;
  display: flex;
  gap: 2rem;
  align-items: flex-start;
}

.status-card.published {
  background: #c8e6c9;
  border-color: #4CAF50;
}

.status-icon {
  font-size: 3rem;
}

.status-content {
  flex: 1;
}

.status-label {
  font-size: 0.85rem;
  color: #999;
  text-transform: uppercase;
  font-weight: 600;
  margin-bottom: 0.5rem;
}

.status-value {
  margin-bottom: 0.75rem;
}

.badge {
  display: inline-block;
  padding: 0.4rem 1rem;
  border-radius: 20px;
  font-weight: 700;
  font-size: 0.9rem;
  text-transform: uppercase;
}

.badge-success {
  background: #4CAF50;
  color: white;
}

.badge-draft {
  background: #ff9800;
  color: white;
}

.status-meta {
  font-size: 0.85rem;
  color: #666;
}

.checklist-card {
  background: white;
  border: 1px solid #ddd;
  border-radius: 8px;
  padding: 1.5rem;
}

.checklist-card h3 {
  margin-top: 0;
  margin-bottom: 1.5rem;
  color: #333;
}

.checklist {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.check-item {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 0.75rem;
  border-radius: 4px;
  background: #f5f5f5;
}

.check-item.pass {
  background: #e8f5e9;
}

.check-item.warning {
  background: #fff3e0;
}

.check-icon {
  font-size: 1.5rem;
  flex-shrink: 0;
}

.check-text {
  flex: 1;
  color: #333;
}

.action-section {
  display: flex;
  gap: 1rem;
}

.btn {
  padding: 1rem 2rem;
  border: none;
  border-radius: 4px;
  font-weight: 700;
  cursor: pointer;
  font-size: 1rem;
  transition: all 0.2s;
}

.btn-publish {
  background: #4CAF50;
  color: white;
  flex: 1;
}

.btn-publish:hover:not(:disabled) {
  background: #45a049;
}

.btn-publish:disabled {
  background: #ccc;
  cursor: not-allowed;
}

.btn-secondary {
  background: #2196F3;
  color: white;
  flex: 1;
}

.btn-secondary:hover:not(:disabled) {
  background: #1976D2;
}

.btn-secondary:disabled {
  background: #ccc;
  cursor: not-allowed;
}

.note-section {
  background: #ffebee;
  border: 1px solid #ef5350;
  border-radius: 4px;
  padding: 1rem;
  color: #c62828;
}

.note-section p {
  margin-top: 0;
  margin-bottom: 0.5rem;
  font-weight: 700;
}

.note-section ul {
  margin: 0;
  padding-left: 1.5rem;
}

.note-section li {
  margin-bottom: 0.25rem;
}
</style>
