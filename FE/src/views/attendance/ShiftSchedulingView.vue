<template>
  <div class="shift-scheduling-page space-y-6 pb-20">
    <!-- 1. Header & Global Filters -->
    <SchedulingHeader />

    <!-- 2. Insights & Summary -->
    <SchedulingSummary />

    <!-- 3. Workflow Tabs Navigation -->
    <SchedulingTabs />

    <!-- 4. Tab Views -->
    <div class="tab-content-wrapper min-h-[600px] animation-fade-in">
      <transition name="tab-fade" mode="out-in">
        <component :is="activeTabComponent" :key="store.activeTab" />
      </transition>
    </div>

    <!-- Global Loading Overlay -->
    <transition name="fade">
      <div
        v-if="store.loading && !store.employees.length"
        class="fixed inset-0 z-[100] bg-white/80 backdrop-blur-sm flex flex-col items-center justify-center"
      >
        <div
          class="w-16 h-16 border-4 border-[var(--sys-brand-soft)] border-t-[var(--sys-brand-solid)] rounded-full animate-spin mb-4"
        ></div>
        <p
          class="text-sm font-bold text-[var(--sys-text-secondary)] uppercase tracking-[0.2em]"
        >
          Đang đồng bộ dữ liệu...
        </p>
      </div>
    </transition>
  </div>
</template>

<script setup>
import { computed, onMounted, defineAsyncComponent, watch } from "vue";
import { useScheduleStore } from "@/stores/useScheduleStore";
import { useCurrentUser } from "@/composables/useCurrentUser";

import SchedulingHeader from "./components/SchedulingHeader.vue";
import SchedulingSummary from "./components/SchedulingSummary.vue";
import SchedulingTabs from "./components/SchedulingTabs.vue";
import BaseScheduleTab from "./components/BaseScheduleTab.vue";
import OverrideTab from "./components/OverrideTab.vue";
import WarningTab from "./components/WarningTab.vue";
import PublishTab from "./components/PublishTab.vue";

const store = useScheduleStore();
const { deptId } = useCurrentUser();

const activeTabComponent = computed(() => {
  switch (store.activeTab) {
    case 1:
      return BaseScheduleTab;
    case 2:
      return OverrideTab;
    case 3:
      return WarningTab;
    case 4:
      return PublishTab;
    default:
      return BaseScheduleTab;
  }
});

// Initialize context
onMounted(() => {
  if (deptId.value && !store.selectedDepartmentId) {
    store.selectedDepartmentId = deptId.value;
  }

  store.fetchShiftTypes();

  if (store.selectedDepartmentId && store.selectedDateRange.from) {
    store.fetchScheduleData(
      store.selectedDepartmentId,
      store.selectedDateRange.from,
      store.selectedDateRange.to
    );
  }
});

// Auto-reload when context changes
watch(
  [
    () => store.selectedDepartmentId,
    () => store.selectedDateRange.from,
    () => store.selectedDateRange.to,
  ],
  () => {
    if (
      store.selectedDepartmentId &&
      store.selectedDateRange.from &&
      store.selectedDateRange.to
    ) {
      store.fetchScheduleData(
        store.selectedDepartmentId,
        store.selectedDateRange.from,
        store.selectedDateRange.to
      );
    }
  },
  { deep: true }
);
</script>

<style>
/* Global styles for the scheduling module */
:root {
  --sys-bg-surface: #ffffff;
  --sys-bg-surface-elevated: #f8fafc;
  --sys-bg-page: #f1f5f9;
  --sys-bg-hover: #f1f5f9;
  --sys-border-subtle: #e2e8f0;
  --sys-text-primary: #0f172a;
  --sys-text-secondary: #475569;
  --sys-text-disabled: #94a3b8;
  --sys-brand-solid: #6366f1;
  --sys-brand-soft: #eef2ff;
  --sys-brand-border: #c7d2fe;
  --sys-warning-solid: #f59e0b;
  --sys-warning-soft: #fffbeb;
  --sys-warning-border: #fef3c7;
  --sys-warning-text: #b45309;
  --sys-danger-solid: #ef4444;
  --sys-danger-soft: #fef2f2;
  --sys-danger-border: #fee2e2;
  --sys-danger-text: #b91c1c;
  --sys-success-solid: #10b981;
  --sys-success-soft: #ecfdf5;
  --sys-success-text: #047857;
}

.shift-scheduling-page {
  max-width: 1600px;
  margin: 0 auto;
  padding: 2rem;
}

.animation-fade-in {
  animation: fade-in 0.6s cubic-bezier(0.2, 0, 0, 1);
}

@keyframes fade-in {
  from {
    opacity: 0;
    transform: translateY(10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.tab-fade-enter-active,
.tab-fade-leave-active {
  transition: all 0.3s ease;
}
.tab-fade-enter-from {
  opacity: 0;
  transform: translateX(10px);
}
.tab-fade-leave-to {
  opacity: 0;
  transform: translateX(-10px);
}

.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
