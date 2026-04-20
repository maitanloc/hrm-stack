<template>
  <transition name="slide-up">
    <div v-if="store.showRecalcHint" class="fixed bottom-8 right-8 z-[150] w-full max-w-sm">
      <div class="bg-[var(--sys-text-primary)] text-white p-5 rounded-[28px] shadow-2xl flex items-center gap-4 border border-white/10 backdrop-blur-md">
        <div class="w-10 h-10 rounded-full bg-white/10 flex items-center justify-center shrink-0">
          <span class="material-symbols-rounded text-amber-400">rebase_edit</span>
        </div>
        <div class="flex-1">
          <p class="text-[11px] font-black uppercase tracking-widest text-white/50 mb-0.5">Lưu ý nghiệp vụ</p>
          <p class="text-xs font-bold leading-relaxed">Lịch thay đổi lớn. Bảng công & Lương có thể cần tính toán lại để đồng bộ.</p>
        </div>
        <button @click="store.showRecalcHint = false" class="w-8 h-8 rounded-full hover:bg-white/10 flex items-center justify-center transition-colors">
          <span class="material-symbols-rounded text-sm">close</span>
        </button>
      </div>
    </div>
  </transition>
</template>

<script setup>
import { useScheduleStore } from '@/stores/useScheduleStore';
const store = useScheduleStore();
</script>

<style scoped>
.slide-up-enter-active, .slide-up-leave-active {
  transition: all 0.5s cubic-bezier(0.2, 1, 0.3, 1);
}
.slide-up-enter-from {
  transform: translateY(100%) scale(0.9);
  opacity: 0;
}
.slide-up-leave-to {
  transform: translateY(20px) scale(0.95);
  opacity: 0;
}
</style>
