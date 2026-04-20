<template>
  <div class="team-schedule-container">
    <!-- Header -->
    <ScheduleHeader />
    
    <!-- Summary Cards -->
    <ScheduleSummaryCards v-if="!schedule.loading" />
    
    <!-- Main Tabs -->
    <ScheduleTabs />
    
    <!-- Global Modals -->
    <ConfirmActionModal v-if="schedule.modals.confirmAction.open" />
    <PreviewModal v-if="schedule.modals.preview.open" />
    <PublishBlockModal v-if="schedule.modals.blockPublish.open" />
    <SuccessModal v-if="schedule.modals.success.open" />
  </div>
</template>

<script setup>
import { onMounted, watch } from 'vue';
import { useScheduleStore } from '@/stores/useScheduleStore.js';
import { useLookupOptions } from '@/composables/useLookupOptions.js';
import ScheduleHeader from './ScheduleHeader.vue';
import ScheduleSummaryCards from './ScheduleSummaryCards.vue';
import ScheduleTabs from './ScheduleTabs.vue';
import ConfirmActionModal from './modals/ConfirmActionModal.vue';
import PreviewModal from './modals/PreviewModal.vue';
import PublishBlockModal from './modals/PublishBlockModal.vue';
import SuccessModal from './modals/SuccessModal.vue';

const schedule = useScheduleStore();
const lookup = useLookupOptions();

// On mount: fetch shift types and listen for filter changes
onMounted(async () => {
  try {
    await schedule.fetchShiftTypes();
  } catch (error) {
    console.error('Error fetching shift types:', error);
  }
});

// Watch for filter changes
watch(
  () => ({
    dept: schedule.selectedDepartmentId,
    from: schedule.selectedDateRange.from,
    to: schedule.selectedDateRange.to,
  }),
  (newVal) => {
    if (newVal.dept && newVal.from && newVal.to) {
      schedule.fetchScheduleData(newVal.dept, newVal.from, newVal.to);
    }
  },
  { deep: true }
);
</script>

<style scoped>
.team-schedule-container {
  padding: 1rem;
  background: #f5f5f5;
  min-height: 100vh;
}
</style>
