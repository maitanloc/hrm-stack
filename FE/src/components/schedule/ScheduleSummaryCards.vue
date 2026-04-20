<template>
  <div class="summary-cards">
    <div class="card">
      <div class="card-icon">👥</div>
      <div class="card-content">
        <div class="card-label">Nhân sự</div>
        <div class="card-value">{{ schedule.totalEmployees }} người</div>
      </div>
    </div>
    
    <div class="card">
      <div class="card-icon">❓</div>
      <div class="card-content">
        <div class="card-label">Chưa gán</div>
        <div class="card-value" :class="{ 'has-issue': schedule.unassignedEmployees > 0 }">
          {{ schedule.unassignedEmployees }} người
        </div>
      </div>
    </div>
    
    <div class="card">
      <div class="card-icon">⚠️</div>
      <div class="card-content">
        <div class="card-label">Cảnh báo</div>
        <div class="card-value" :class="{ 'has-issue': schedule.warningCount > 0 }">
          {{ schedule.warningCount }} cảnh báo
        </div>
      </div>
    </div>
    
    <div class="card">
      <div class="card-icon" :class="{ published: schedule.isPublished }">
        <span v-if="schedule.isPublished">✓</span>
        <span v-else>📋</span>
      </div>
      <div class="card-content">
        <div class="card-label">Trạng thái</div>
        <div class="card-value">
          <span v-if="schedule.isPublished" class="badge badge-success">PUBLISHED</span>
          <span v-else class="badge badge-draft">DRAFT</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { useScheduleStore } from '@/stores/useScheduleStore.js';

const schedule = useScheduleStore();
</script>

<style scoped>
.summary-cards {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 1.5rem;
  margin-bottom: 2rem;
}

.card {
  background: white;
  border-radius: 8px;
  padding: 1.5rem;
  display: flex;
  align-items: center;
  gap: 1rem;
  box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.card-icon {
  font-size: 2.5rem;
  width: 60px;
  height: 60px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #f0f0f0;
  border-radius: 8px;
}

.card-icon.published {
  background: #c8e6c9;
  color: #2e7d32;
}

.card-content {
  flex: 1;
}

.card-label {
  font-size: 0.85rem;
  color: #999;
  font-weight: 600;
  text-transform: uppercase;
  margin-bottom: 0.5rem;
}

.card-value {
  font-size: 1.5rem;
  font-weight: 700;
  color: #333;
}

.card-value.has-issue {
  color: #f44336;
}

.badge {
  display: inline-block;
  padding: 0.3rem 0.8rem;
  border-radius: 20px;
  font-size: 0.75rem;
  font-weight: 700;
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

@media (max-width: 768px) {
  .summary-cards {
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  }
}
</style>
