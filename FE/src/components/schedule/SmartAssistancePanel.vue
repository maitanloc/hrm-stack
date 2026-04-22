<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-lg font-bold text-slate-900">Smart Assistance</h2>
        <p class="text-sm text-slate-500">Hệ thống phân tích và đưa ra các gợi ý giúp bạn lập lịch nhanh hơn.</p>
      </div>
      <button 
        @click="store.fetchSuggestions"
        :disabled="store.loading"
        class="flex items-center gap-2 px-4 py-2 bg-white border border-slate-200 rounded-xl text-sm font-bold text-slate-700 hover:bg-slate-50 transition-all shadow-sm"
      >
        <span class="material-symbols-outlined text-[18px]" :class="{ 'animate-spin': store.loading }">refresh</span>
        Làm mới gợi ý
      </button>
    </div>

    <!-- Loading State -->
    <div v-if="store.loading" class="flex flex-col items-center justify-center py-20 bg-white rounded-3xl border border-slate-100 shadow-sm animate-pulse">
       <div class="w-12 h-12 bg-blue-50 text-blue-600 rounded-xl flex items-center justify-center mb-4">
          <span class="material-symbols-outlined animate-spin">refresh</span>
       </div>
       <p class="text-slate-500 font-bold">Đang phân tích dữ liệu...</p>
       <p class="text-slate-400 text-xs mt-1">Hệ thống đang tìm kiếm các pattern lập lịch tối ưu.</p>
    </div>

    <!-- Suggestions Grid (Always show some baseline cards) -->
    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
      <!-- Card: Fill Empty Slots (Dynamic) -->
      <div v-if="store.suggestions.length > 0" class="bg-white p-5 rounded-2xl border border-blue-200 bg-blue-50/10 shadow-sm hover:border-blue-400 transition-all group relative overflow-hidden">
        <div class="absolute top-0 right-0 p-4 opacity-5 group-hover:opacity-10 transition-opacity">
          <span class="material-symbols-outlined text-5xl text-blue-600">magic_button</span>
        </div>
        <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center mb-4">
          <span class="material-symbols-outlined">auto_fix_high</span>
        </div>
        <h3 class="font-bold text-slate-900 mb-1">Điền các ô trống</h3>
        <p class="text-xs text-slate-500 mb-4 leading-relaxed">
          Tự động gợi ý ca làm việc cho {{ store.suggestions.length }} vị trí còn trống dựa trên lịch sử gần nhất.
        </p>
        <div class="flex items-center justify-between mt-auto">
          <span class="text-[10px] font-bold text-blue-600 bg-blue-50 px-2 py-1 rounded-lg uppercase">Khuyên dùng</span>
          <button 
            @click="store.previewSuggestion('fill_empty')"
            class="text-sm font-bold text-blue-600 hover:text-blue-700 flex items-center gap-1"
          >
            Xem trước <span class="material-symbols-outlined text-[16px]">chevron_right</span>
          </button>
        </div>
      </div>

      <!-- Card: Copy Week (Static Action) -->
      <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm hover:border-indigo-300 transition-all group relative overflow-hidden">
        <div class="absolute top-0 right-0 p-4 opacity-5 group-hover:opacity-10 transition-opacity">
          <span class="material-symbols-outlined text-5xl text-indigo-600">content_copy</span>
        </div>
        <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center mb-4">
          <span class="material-symbols-outlined">history</span>
        </div>
        <h3 class="font-bold text-slate-900 mb-1">Sao chép tuần trước</h3>
        <p class="text-xs text-slate-500 mb-4 leading-relaxed">
          Lấy lịch làm việc của 7 ngày trước đó. Hệ thống sẽ so sánh và chỉ ra các thay đổi trước khi áp dụng.
        </p>
        <div class="flex items-center justify-between mt-auto">
          <span class="text-[10px] font-bold text-slate-400 bg-slate-100 px-2 py-1 rounded-lg uppercase">An toàn</span>
          <button 
            @click="handleCopyWeekPreview"
            class="text-sm font-bold text-indigo-600 hover:text-indigo-700 flex items-center gap-1"
          >
            Xem trước <span class="material-symbols-outlined text-[16px]">chevron_right</span>
          </button>
        </div>
      </div>

      <!-- Card: Recalculate Hint (Static Informational) -->
      <div class="bg-slate-50 p-5 rounded-2xl border border-dashed border-slate-300 group relative">
        <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center mb-4">
          <span class="material-symbols-outlined">analytics</span>
        </div>
        <h3 class="font-bold text-slate-800 mb-1">Rà soát Chấm công</h3>
        <p class="text-xs text-slate-500 mb-4 leading-relaxed italic">
          Mọi thay đổi sau khi dữ liệu đã được ghi nhận có thể ảnh hưởng đến bảng công và tính lương tháng.
        </p>
        <div class="flex items-center gap-2 text-[10px] font-bold text-amber-700 bg-amber-50/50 p-2 rounded-lg border border-amber-100">
           <span class="material-symbols-outlined text-[14px]">info</span>
           Lưu ý rà soát lại lịch cũ.
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-if="store.suggestions.length === 0 && !store.loading" class="flex flex-col items-center justify-center py-12 bg-white rounded-3xl border border-slate-100 border-dashed">
       <div class="w-16 h-16 bg-slate-50 text-slate-300 rounded-full flex items-center justify-center mb-4">
          <span class="material-symbols-outlined text-3xl">lightbulb_outline</span>
       </div>
       <p class="text-slate-500 text-sm font-medium">Hiện không có gợi ý mới cho tuần này.</p>
       <p class="text-slate-400 text-xs mt-1">Lịch của bạn đã khá đầy đủ hoặc không có pattern rõ ràng.</p>
    </div>
  </div>
</template>

<script setup>
import { useScheduleStore } from '@/stores/useScheduleStore';
const store = useScheduleStore();

const handleCopyWeekPreview = async () => {
  try {
    await store.previewSuggestion('copy_week');
  } catch (error) {
    alert(error.message);
  }
};
</script>
