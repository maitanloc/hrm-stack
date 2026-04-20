<template>
  <div class="flex flex-col gap-6">
    <div class="p-8 rounded-[40px] bg-[var(--sys-bg-surface)] border border-[var(--sys-border-subtle)] shadow-sm">
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-10">
        <div>
          <h3 class="text-xl font-bold tracking-tight text-[var(--sys-text-primary)]">Rà soát & Kiểm tra Lỗi</h3>
          <p class="text-[13px] text-[var(--sys-text-secondary)] mt-1">Hệ thống phát hiện các điểm bất thường và vi phạm chính sách phân ca dựa trên quy định hiện hành.</p>
        </div>
        <div class="flex items-center gap-1.5 bg-[var(--sys-bg-page)] p-1.5 rounded-2xl border border-[var(--sys-border-subtle)] shadow-inner">
           <button 
             v-for="f in ['ALL', 'ERROR', 'WARNING']" 
             :key="f"
             @click="filterType = f"
             :class="['px-5 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all duration-300', filterType === f ? 'bg-white text-[var(--sys-brand-solid)] shadow-md' : 'text-[var(--sys-text-disabled)] hover:text-[var(--sys-text-secondary)]']"
           >
             {{ f === 'ALL' ? 'Tất cả' : (f === 'ERROR' ? 'Lỗi (Error)' : 'Cảnh báo') }}
           </button>
        </div>
      </div>

      <div class="space-y-4">
        <div 
          v-for="warn in filteredWarnings" 
          :key="warn.id"
          class="flex flex-col rounded-[32px] border border-[var(--sys-border-subtle)] bg-[var(--sys-bg-page)]/30 hover:border-[var(--sys-brand-border)] hover:bg-[var(--sys-bg-page)]/60 transition-all duration-500 group overflow-hidden"
        >
          <div class="flex items-start gap-5 p-6">
            <div :class="[
              'w-14 h-14 rounded-2xl flex items-center justify-center shrink-0 shadow-lg transition-all duration-500 group-hover:scale-110 group-hover:rotate-3',
              warn.severity === 'ERROR' ? 'bg-rose-50 text-rose-500 border border-rose-100' : 'bg-amber-50 text-amber-500 border border-amber-100'
            ]">
              <span class="material-symbols-rounded text-[28px]">
                {{ warn.severity === 'ERROR' ? 'report' : 'warning' }}
              </span>
            </div>
            
            <div class="flex-1 min-w-0 space-y-1">
              <div class="flex flex-wrap items-center gap-2.5">
                <span class="text-[14px] font-black text-[var(--sys-text-primary)] transition-colors group-hover:text-[var(--sys-brand-solid)]">
                  {{ getEmployee(warn.employee_id)?.full_name }}
                </span>
                <span class="px-2 py-0.5 rounded-md bg-[var(--sys-bg-hover)] text-[9px] font-black text-[var(--sys-text-secondary)] uppercase tracking-widest border border-[var(--sys-border-subtle)]">
                  #{{ getEmployee(warn.employee_id)?.employee_code }}
                </span>
                <span class="w-1 h-1 rounded-full bg-slate-300"></span>
                <span class="text-[10px] font-bold text-[var(--sys-brand-solid)] uppercase tracking-widest">
                  {{ formatDate(warn.work_date) }}
                </span>
              </div>
              <p class="text-[14px] font-bold text-[var(--sys-text-primary)] opacity-90 leading-tight">{{ warn.title }}</p>
              <p class="text-[11px] text-[var(--sys-text-secondary)] font-medium leading-relaxed line-clamp-1 group-hover:line-clamp-none transition-all">{{ warn.message }}</p>
            </div>

            <div class="flex items-center gap-3 shrink-0 ml-4">
               <button 
                 @click="handleResolve(warn)"
                 class="px-5 py-2.5 rounded-xl text-[10px] font-black uppercase tracking-widest bg-[var(--sys-brand-soft)] text-[var(--sys-brand-solid)] border border-[var(--sys-brand-border)] hover:bg-[var(--sys-brand-solid)] hover:text-white transition-all shadow-sm"
               >
                 Điều chỉnh
               </button>
               <button 
                 @click="toggleDetail(warn.id)"
                 class="w-11 h-11 rounded-xl flex items-center justify-center text-[var(--sys-text-disabled)] bg-white border border-[var(--sys-border-subtle)] hover:bg-[var(--sys-bg-hover)] hover:text-[var(--sys-text-primary)] transition-all shadow-sm"
               >
                 <span class="material-symbols-rounded transition-transform duration-500" :class="{ 'rotate-180': expandedId === warn.id }">keyboard_arrow_down</span>
               </button>
            </div>
          </div>

          <transition name="expand">
            <div v-if="expandedId === warn.id" class="px-6 pb-6 pt-2 bg-gradient-to-b from-transparent to-[var(--sys-bg-page)]/50">
               <div class="p-5 rounded-2xl bg-white border border-[var(--sys-border-subtle)] shadow-inner space-y-4">
                  <div class="flex items-start gap-4">
                    <div class="w-8 h-8 rounded-full bg-slate-50 flex items-center justify-center shrink-0">
                      <span class="material-symbols-rounded text-[18px] text-slate-400">lightbulb</span>
                    </div>
                    <div class="space-y-1">
                      <p class="text-[10px] font-black uppercase tracking-widest text-slate-400">Chi tiết lỗi & Gợi ý</p>
                      <p class="text-[12px] text-[var(--sys-text-secondary)] font-medium leading-relaxed">{{ warn.message }}</p>
                    </div>
                  </div>
                  <div class="flex items-center gap-6 pt-4 border-t border-slate-100">
                     <div class="flex flex-col">
                        <span class="text-[9px] font-black uppercase tracking-widest text-slate-400 mb-0.5">Mức độ rủi ro</span>
                        <div class="flex items-center gap-1.5">
                          <span :class="['w-2 h-2 rounded-full', warn.severity === 'ERROR' ? 'bg-rose-500 animate-pulse' : 'bg-amber-500']"></span>
                          <span :class="['text-[11px] font-black uppercase', warn.severity === 'ERROR' ? 'text-rose-600' : 'text-amber-600']">{{ warn.severity }}</span>
                        </div>
                     </div>
                     <div class="flex flex-col">
                        <span class="text-[9px] font-black uppercase tracking-widest text-slate-400 mb-0.5">Ngày vi phạm</span>
                        <span class="text-[11px] font-black text-slate-700 uppercase tracking-tighter">{{ formatDate(warn.work_date) }}</span>
                     </div>
                  </div>
               </div>
            </div>
          </transition>
        </div>

        <div v-if="filteredWarnings.length === 0" class="py-24 flex flex-col items-center justify-center text-center animate-in fade-in zoom-in duration-700">
           <div class="w-24 h-24 rounded-[40px] bg-emerald-50 text-emerald-500 flex items-center justify-center mb-8 border-2 border-emerald-100 shadow-xl shadow-emerald-500/10">
              <span class="material-symbols-rounded text-[48px]">verified_user</span>
           </div>
           <div class="space-y-2">
             <h4 class="text-xl font-black text-slate-800 uppercase tracking-tight">Hệ thống an toàn</h4>
             <p class="text-[14px] text-slate-500 font-medium max-w-xs mx-auto">Không phát hiện bất kỳ vi phạm chính sách nào trong dữ liệu phân ca hiện tại.</p>
           </div>
        </div>
      </div>

      <div class="mt-12 pt-12 border-t border-dashed border-[var(--sys-border-subtle)]">
        <SuggestionList />
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useScheduleStore } from '@/stores/useScheduleStore';
import SuggestionList from './SuggestionList.vue';

const store = useScheduleStore();
const filterType = ref('ALL');
const expandedId = ref(null);

onMounted(() => {
  if (store.selectedDepartmentId && store.selectedDateRange.from) {
    store.fetchSuggestions(store.selectedDepartmentId, store.selectedDateRange.from, store.selectedDateRange.to);
  }
});

const filteredWarnings = computed(() => {
  if (filterType.value === 'ALL') return store.allWarnings;
  return store.allWarnings.filter(w => w.severity === filterType.value);
});

const getEmployee = (id) => store.employees.find(e => e.employee_id === id);

const formatDate = (dateStr) => {
  if (!dateStr) return '';
  const d = new Date(dateStr);
  return `${d.getDate()}/${d.getMonth() + 1}`;
};

const toggleDetail = (id) => {
  expandedId.value = expandedId.value === id ? null : id;
};

const handleResolve = (warn) => {
  store.setActiveTab(1); // Jump back to matrix
};
</script>

<style scoped>
.expand-enter-active, .expand-leave-active {
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  max-height: 200px;
}
.expand-enter-from, .expand-leave-to {
  max-height: 0;
  opacity: 0;
}
</style>
