<template>
  <div class="flex flex-col gap-8 animation-fade-in">
    <div class="p-8 md:p-10 rounded-[40px] bg-white border border-[var(--sys-border-subtle)] shadow-sm">
      <div class="flex flex-col lg:flex-row lg:items-end justify-between gap-8 mb-12">
        <div class="space-y-2">
          <div class="flex items-center gap-3">
             <div class="w-10 h-10 rounded-xl bg-[var(--sys-warning-soft)] text-[var(--sys-warning-text)] flex items-center justify-center">
                <span class="material-symbols-rounded">verified_user</span>
             </div>
             <h3 class="text-2xl font-black tracking-tight text-[var(--sys-text-primary)]">Rà soát & Kiểm tra</h3>
          </div>
          <p class="text-[13px] text-[var(--sys-text-secondary)] max-w-xl">Hệ thống phát hiện các điểm bất thường và vi phạm chính sách dựa trên quy định hiện hành.</p>
        </div>

        <div class="flex items-center gap-1.5 bg-[var(--sys-bg-page)] p-1.5 rounded-2xl border border-[var(--sys-border-subtle)] shadow-inner">
           <button 
             v-for="f in ['ALL', 'ERROR', 'WARNING']" 
             :key="f"
             @click="filterType = f"
             :class="['px-6 py-2.5 rounded-xl text-[11px] font-bold uppercase tracking-widest transition-all duration-500', filterType === f ? 'bg-white text-[var(--sys-brand-solid)] shadow-lg' : 'text-[var(--sys-text-disabled)] hover:text-[var(--sys-text-secondary)]']"
           >
             {{ f === 'ALL' ? 'Tất cả' : (f === 'ERROR' ? 'Lỗi' : 'Cảnh báo') }}
           </button>
        </div>
      </div>

      <div class="grid grid-cols-1 gap-4">
        <div 
          v-for="warn in filteredWarnings" 
          :key="warn.id"
          class="flex flex-col rounded-[32px] border border-[var(--sys-border-subtle)] bg-white hover:border-[var(--sys-brand-border)] hover:shadow-xl hover:shadow-[var(--sys-brand-solid)]/5 transition-all duration-500 group overflow-hidden"
        >
          <div class="flex flex-col md:flex-row md:items-center gap-6 p-6 md:p-8">
            <div :class="[
              'w-16 h-16 rounded-[22px] flex items-center justify-center shrink-0 shadow-lg transition-all duration-700 group-hover:scale-110 group-hover:rotate-3',
              warn.severity === 'ERROR' ? 'bg-rose-50 text-rose-500 border border-rose-100 shadow-rose-500/10' : 'bg-amber-50 text-amber-500 border border-amber-100 shadow-amber-500/10'
            ]">
              <span class="material-symbols-rounded text-[32px]">
                {{ warn.severity === 'ERROR' ? 'report_gmailerrorred' : 'warning_amber' }}
              </span>
            </div>
            
            <div class="flex-1 min-w-0 space-y-2">
              <div class="flex flex-wrap items-center gap-3">
                <span class="text-[15px] font-black text-[var(--sys-text-primary)] group-hover:text-[var(--sys-brand-solid)] transition-colors">
                  {{ getEmployee(warn.employee_id)?.full_name }}
                </span>
                <span class="px-2.5 py-1 rounded-lg bg-[var(--sys-bg-page)] text-[10px] font-black text-[var(--sys-text-secondary)] uppercase tracking-widest border border-[var(--sys-border-subtle)]">
                  #{{ getEmployee(warn.employee_id)?.employee_code }}
                </span>
                <div class="w-1 h-1 rounded-full bg-slate-300"></div>
                <span class="text-[11px] font-black text-[var(--sys-brand-solid)] uppercase tracking-widest">
                  {{ formatDate(warn.work_date) }}
                </span>
              </div>
              <h4 class="text-[16px] font-bold text-[var(--sys-text-primary)] opacity-90 leading-tight">{{ warn.title }}</h4>
              <p class="text-[13px] text-[var(--sys-text-secondary)] leading-relaxed line-clamp-1 group-hover:line-clamp-none transition-all">{{ warn.message }}</p>
            </div>

            <div class="flex items-center gap-3 shrink-0">
               <button 
                 @click="handleResolve(warn)"
                 class="px-6 py-3 rounded-2xl text-[11px] font-black uppercase tracking-widest bg-[var(--sys-brand-soft)] text-[var(--sys-brand-solid)] border border-[var(--sys-brand-border)] hover:bg-[var(--sys-brand-solid)] hover:text-white hover:shadow-lg hover:shadow-[var(--sys-brand-solid)]/20 transition-all"
               >
                 Điều chỉnh
               </button>
               <button 
                 @click="toggleDetail(warn.id)"
                 class="w-12 h-12 rounded-2xl flex items-center justify-center text-[var(--sys-text-disabled)] bg-[var(--sys-bg-page)] hover:bg-[var(--sys-brand-soft)] hover:text-[var(--sys-brand-solid)] transition-all"
               >
                 <span class="material-symbols-rounded transition-transform duration-500" :class="{ 'rotate-180': expandedId === warn.id }">expand_more</span>
               </button>
            </div>
          </div>

          <transition name="expand">
            <div v-if="expandedId === warn.id" class="px-8 pb-8 pt-0">
               <div class="p-6 rounded-3xl bg-[var(--sys-bg-page)]/50 border border-[var(--sys-border-subtle)] space-y-5">
                  <div class="flex items-start gap-4">
                    <div class="w-10 h-10 rounded-2xl bg-white flex items-center justify-center shrink-0 shadow-sm">
                      <span class="material-symbols-rounded text-[22px] text-[var(--sys-brand-solid)] animate-pulse">lightbulb</span>
                    </div>
                    <div class="space-y-1">
                      <p class="text-[10px] font-black uppercase tracking-[0.2em] text-[var(--sys-text-disabled)]">Phân tích & Giải pháp</p>
                      <p class="text-[13px] text-[var(--sys-text-secondary)] font-bold leading-relaxed">{{ warn.message }}</p>
                    </div>
                  </div>
                  <div class="flex items-center gap-10 pt-5 border-t border-[var(--sys-border-subtle)]">
                     <div class="flex flex-col">
                        <span class="text-[9px] font-black uppercase tracking-[0.2em] text-[var(--sys-text-disabled)] mb-1">Mức độ rủi ro</span>
                        <div class="flex items-center gap-2">
                          <span :class="['w-2.5 h-2.5 rounded-full shadow-sm', warn.severity === 'ERROR' ? 'bg-rose-500 animate-pulse' : 'bg-amber-500']"></span>
                          <span :class="['text-[11px] font-black uppercase tracking-widest', warn.severity === 'ERROR' ? 'text-rose-600' : 'text-amber-600']">{{ warn.severity }}</span>
                        </div>
                     </div>
                     <div class="flex flex-col">
                        <span class="text-[9px] font-black uppercase tracking-[0.2em] text-[var(--sys-text-disabled)] mb-1">Thời điểm ghi nhận</span>
                        <span class="text-[11px] font-black text-[var(--sys-text-primary)] uppercase tracking-widest">{{ formatDate(warn.work_date) }}</span>
                     </div>
                  </div>
               </div>
            </div>
          </transition>
        </div>

        <div v-if="filteredWarnings.length === 0" class="py-24 flex flex-col items-center justify-center text-center">
           <div class="relative mb-10">
              <div class="absolute inset-0 bg-emerald-500/20 blur-3xl rounded-full scale-150"></div>
              <div class="relative w-28 h-28 rounded-[40px] bg-white border-4 border-emerald-100 flex items-center justify-center shadow-2xl">
                <span class="material-symbols-rounded text-[56px] text-emerald-500">task_alt</span>
              </div>
           </div>
           <div class="space-y-3">
             <h4 class="text-2xl font-black text-[var(--sys-text-primary)] uppercase tracking-tight">Hệ thống an toàn</h4>
             <p class="text-[14px] text-[var(--sys-text-secondary)] max-w-sm mx-auto leading-relaxed">Không phát hiện bất kỳ vi phạm chính sách nào trong dữ liệu phân ca hiện tại. Bạn có thể yên tâm tiến hành công bố lịch.</p>
           </div>
        </div>
      </div>

      <div class="mt-16 pt-12 border-t border-dashed border-[var(--sys-border-subtle)]">
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
  const dayNames = ['Chủ Nhật', 'Thứ Hai', 'Thứ Ba', 'Thứ Tư', 'Thứ Năm', 'Thứ Sáu', 'Thứ Bảy'];
  return `${dayNames[d.getDay()]}, ${d.getDate()}/${d.getMonth() + 1}`;
};

const toggleDetail = (id) => {
  expandedId.value = expandedId.value === id ? null : id;
};

const handleResolve = (warn) => {
  store.setActiveTab(1); // Jump back to matrix
};
</script>

<style scoped>
.expand-enter-active {
  transition: all 0.5s cubic-bezier(0.16, 1, 0.3, 1);
  max-height: 400px;
}
.expand-leave-active {
  transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
  max-height: 400px;
}
.expand-enter-from, .expand-leave-to {
  max-height: 0;
  opacity: 0;
  transform: translateY(-10px);
}
</style>
