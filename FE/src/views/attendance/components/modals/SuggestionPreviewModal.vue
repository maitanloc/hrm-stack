<template>
  <div class="fixed inset-0 z-[200] flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/60 backdrop-blur-md" @click="close"></div>
    <div class="relative w-full max-w-4xl bg-[var(--sys-bg-surface)] rounded-[40px] shadow-2xl border border-[var(--sys-border-subtle)] animation-pop-in flex flex-col max-h-[90vh]">
      
      <div class="p-8 border-b border-[var(--sys-border-subtle)] flex items-center justify-between">
        <div class="flex items-center gap-4">
          <div class="w-12 h-12 rounded-2xl bg-[var(--sys-brand-soft)] text-[var(--sys-brand-solid)] flex items-center justify-center">
             <span class="material-symbols-rounded text-2xl">{{ data?.icon || 'preview' }}</span>
          </div>
          <div>
            <h3 class="text-xl font-black tracking-tight text-[var(--sys-text-primary)]">Xem trước thay đổi</h3>
            <p class="text-xs text-[var(--sys-text-secondary)] font-bold uppercase tracking-widest opacity-60">Gợi ý: {{ data?.title }}</p>
          </div>
        </div>
        <button @click="close" class="w-10 h-10 rounded-full flex items-center justify-center hover:bg-[var(--sys-bg-hover)] transition-all">
          <span class="material-symbols-rounded">close</span>
        </button>
      </div>

      <div class="flex-1 overflow-y-auto p-8 custom-scrollbar">
        <!-- Impact Summary -->
        <div class="grid grid-cols-3 gap-6 mb-10">
           <div class="p-5 rounded-3xl bg-[var(--sys-bg-page)] border border-[var(--sys-border-subtle)]">
              <span class="text-[10px] font-black uppercase tracking-widest text-[var(--sys-text-disabled)] block mb-1">Nhân sự</span>
              <span class="text-2xl font-black text-[var(--sys-text-primary)]">{{ impactedEmployees.length }}</span>
           </div>
           <div class="p-5 rounded-3xl bg-[var(--sys-bg-page)] border border-[var(--sys-border-subtle)]">
              <span class="text-[10px] font-black uppercase tracking-widest text-[var(--sys-text-disabled)] block mb-1">Số ca gán thêm</span>
              <span class="text-2xl font-black text-[var(--sys-brand-solid)]">{{ data?.impacted_cells || 0 }}</span>
           </div>
           <div class="p-5 rounded-3xl bg-[var(--sys-brand-soft)] border border-[var(--sys-brand-border)]">
              <span class="text-[10px] font-black uppercase tracking-widest text-[var(--sys-brand-solid)] block mb-1">Xung đột (Conflict)</span>
              <span class="text-2xl font-black text-[var(--sys-success-text)]">0</span>
           </div>
        </div>

        <!-- Explanation -->
        <div class="mb-10 p-6 rounded-3xl bg-amber-50 border border-amber-100 flex items-start gap-4">
           <span class="material-symbols-rounded text-amber-500 mt-0.5">help</span>
           <div class="space-y-1">
              <p class="text-sm font-black text-amber-800">Tại sao hệ thống gợi ý điều này?</p>
              <p class="text-xs text-amber-700 leading-relaxed font-bold italic">{{ data?.explanation }}</p>
              <ul class="text-[11px] text-amber-600 mt-3 space-y-1 list-disc ml-4 font-bold">
                 <li>Tự động chọn ca làm việc dựa trên dữ liệu 4 tuần gần nhất.</li>
                 <li>Chỉ gợi ý điền vào các ô đang để trống.</li>
                 <li>Bỏ qua các ô đã có "Ghi đè" của Quản lý.</li>
              </ul>
           </div>
        </div>

        <!-- Comparison Preview (Mock Table) -->
        <h4 class="text-xs font-black uppercase tracking-[0.2em] mb-4 ml-2">Xem chi tiết thay đổi theo nhân sự</h4>
        <div class="rounded-3xl border border-[var(--sys-border-subtle)] overflow-hidden">
           <table class="w-full text-left text-xs bg-white">
              <thead class="bg-[var(--sys-bg-page)]">
                 <tr>
                    <th class="px-6 py-4 font-black">Nhân sự</th>
                    <th class="px-6 py-4 font-black">Ngày</th>
                    <th class="px-6 py-4 font-black">Hiện tại</th>
                    <th class="px-6 py-4 font-black">Gợi ý mới</th>
                 </tr>
              </thead>
              <tbody class="divide-y divide-[var(--sys-border-subtle)]">
                 <tr v-for="imp in impactedEmployees" :key="imp.id">
                    <td class="px-6 py-4 font-bold text-[var(--sys-text-primary)]">{{ imp.full_name }}</td>
                    <td class="px-6 py-4 text-[var(--sys-text-secondary)] font-bold">{{ imp.date }}</td>
                    <td class="px-6 py-4">
                       <span class="text-[var(--sys-text-disabled)] italic">Chưa gán ca</span>
                    </td>
                    <td class="px-6 py-4">
                       <div class="flex items-center gap-2">
                          <span class="px-2 py-1 rounded-lg bg-[var(--sys-brand-soft)] text-[var(--sys-brand-solid)] font-black uppercase tracking-widest border border-[var(--sys-brand-border)]">
                             {{ imp.suggested_shift }}
                          </span>
                          <span class="material-symbols-rounded text-xs text-[var(--sys-success-text)] font-black">check_circle</span>
                       </div>
                    </td>
                 </tr>
              </tbody>
           </table>
        </div>
      </div>

      <div class="p-8 border-t border-[var(--sys-border-subtle)] bg-[var(--sys-bg-page)]/50 rounded-b-[40px] flex items-center justify-end gap-4">
        <button @click="close" class="px-8 py-4 rounded-2xl text-xs font-bold text-[var(--sys-text-secondary)] hover:bg-[var(--sys-bg-hover)] transition-all">Quay lại</button>
        <button 
          @click="handleApply"
          :disabled="store.submitting"
          class="px-10 py-4 rounded-2xl bg-[var(--sys-brand-solid)] text-white text-xs font-black uppercase tracking-widest hover:brightness-110 active:scale-95 transition-all shadow-xl shadow-[var(--sys-brand-solid)]/30"
        >
          {{ store.submitting ? 'ĐANG ÁP DỤNG...' : 'XÁC NHẬN ÁP DỤNG' }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { useScheduleStore } from '@/stores/useScheduleStore';

const store = useScheduleStore();
const data = computed(() => store.modals.suggestionPreview.data);

const impactedEmployees = computed(() => {
  return data.value?.impacted_employees || [
    { id: 1, full_name: 'Nguyễn Văn A', date: 'Thứ 2 (18/04)', suggested_shift: 'S' },
    { id: 2, full_name: 'Trần Thị B', date: 'Thứ 3 (19/04)', suggested_shift: 'C' },
    { id: 3, full_name: 'Phạm Văn C', date: 'Thứ 2 (18/04)', suggested_shift: 'A' },
  ];
});

const close = () => store.closeModal('suggestionPreview');

const handleApply = async () => {
  await store.applySuggestion(data.value.id);
  alert('Đã áp dụng gợi ý thành công!');
  close();
};
</script>

<style scoped>
@keyframes pop-in {
  from { opacity: 0; transform: scale(0.95); }
  to { opacity: 1; transform: scale(1); }
}
.animation-pop-in {
  animation: pop-in 0.3s cubic-bezier(0.2, 0.8, 0.3, 1);
}
.custom-scrollbar::-webkit-scrollbar {
  width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
  background: var(--sys-border-subtle);
  border-radius: 10px;
}
</style>
