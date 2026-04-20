<template>
  <div class="schedule-tabs">
    <div class="tabs-header">
      <button
        v-for="tab in tabs"
        :key="tab.id"
        :class="['tab-btn', { active: schedule.activeTab === tab.id }]"
        @click="schedule.setActiveTab(tab.id)"
      >
        {{ tab.label }}
      </button>
    </div>
    
    <div class="tabs-content">
      <BaseScheduleTab v-if="schedule.activeTab === 1" />
      <OverrideTab v-if="schedule.activeTab === 2" />
      <WarningTab v-if="schedule.activeTab === 3" />
      <PublishTab v-if="schedule.activeTab === 4" />
    </div>
  </div>
</template>

<script setup>
import { useScheduleStore } from '@/stores/useScheduleStore.js';
import BaseScheduleTab from './BaseScheduleTab.vue';
import OverrideTab from './OverrideTab.vue';
import WarningTab from './WarningTab.vue';
import PublishTab from './PublishTab.vue';

const schedule = useScheduleStore();

const tabs = [
  { id: 1, label: '1️⃣ Tạo lịch nền' },
  { id: 2, label: '2️⃣ Ngoại lệ & Ghi đè' },
  { id: 3, label: '3️⃣ Cảnh báo & Rà soát' },
  { id: 4, label: '4️⃣ Chốt & Publish' },
];
</script>

<style scoped>
.schedule-tabs {
  background: white;
  border-radius: 8px;
  overflow: hidden;
  box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.tabs-header {
  display: flex;
  border-bottom: 2px solid #eee;
  background: #fafafa;
  overflow-x: auto;
}

.tab-btn {
  flex: 1;
  padding: 1rem;
  border: none;
  background: transparent;
  color: #666;
  cursor: pointer;
  font-weight: 600;
  border-bottom: 2px solid transparent;
  margin-bottom: -2px;
  white-space: nowrap;
  transition: all 0.2s;
}

.tab-btn:hover {
  background: #f5f5f5;
  color: #333;
}

.tab-btn.active {
  color: #2196F3;
  border-bottom-color: #2196F3;
  background: white;
}

.tabs-content {
  padding: 2rem;
  min-height: 400px;
}

@media (max-width: 768px) {
  .tab-btn {
    padding: 0.75rem;
    font-size: 0.85rem;
  }
  
  .tabs-content {
    padding: 1.5rem;
  }
}
</style>
