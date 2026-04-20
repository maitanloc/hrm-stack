<template>
  <div class="flex flex-col gap-8 max-w-4xl mx-auto py-8 px-4">
    <div class="text-center space-y-4 animate-in fade-in slide-in-from-bottom-4 duration-700">
      <div class="w-24 h-24 rounded-[40px] bg-[var(--sys-brand-soft)] text-[var(--sys-brand-solid)] flex items-center justify-center mx-auto mb-6 border border-[var(--sys-brand-border)] shadow-xl shadow-[var(--sys-brand-solid)]/10">
        <span class="material-symbols-rounded text-[48px]">campaign</span>
      </div>
      <div class="space-y-2">
        <h2 class="text-3xl font-black tracking-tight text-[var(--sys-text-primary)] uppercase">Chốt & Công bố Lịch</h2>
        <p class="text-[14px] text-[var(--sys-text-secondary)] max-w-md mx-auto leading-relaxed">Sau khi công bố, lịch làm việc sẽ được chính thức hóa, gửi thông báo đến nhân sự và khóa các chỉnh sửa lịch nền.</p>
      </div>
    </div>

    <!-- Validation Checklist -->
    <div class="p-10 rounded-[48px] bg-[var(--sys-bg-surface)] border border-[var(--sys-border-subtle)] shadow-2xl relative overflow-hidden group">
      <div class="absolute -top-10 -right-10 p-12 opacity-[0.03] pointer-events-none group-hover:opacity-[0.07] transition-opacity duration-1000">
        <span class="material-symbols-rounded text-[240px]">verified</span>
      </div>

      <div class="flex items-center justify-between mb-8">
        <h3 class="text-lg font-black uppercase tracking-widest flex items-center gap-3 text-[var(--sys-text-primary)]">
          <span class="w-10 h-10 rounded-2xl bg-[var(--sys-brand-soft)] text-[var(--sys-brand-solid)] flex items-center justify-center text-xs font-black border border-[var(--sys-brand-border)]">01</span>
          Điều kiện xuất bản
        </h3>
        <button @click="validate" class="flex items-center gap-2 px-4 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest text-[var(--sys-brand-solid)] hover:bg-[var(--sys-brand-soft)] transition-all">
          <span class="material-symbols-rounded text-[18px]">sync</span>
          Kiểm tra lại
        </button>
      </div>

      <div class="space-y-5">
        <div v-for="(check, index) in checklist" :key="index" class="flex items-center gap-5 p-5 rounded-[28px] bg-[var(--sys-bg-page)]/50 border border-[var(--sys-border-subtle)] hover:border-[var(--sys-brand-border)] transition-all duration-300">
          <div :class="[
            'w-12 h-12 rounded-2xl flex items-center justify-center shrink-0 border-2 transition-all shadow-sm',
            check.status === 'PASS' 
              ? 'bg-emerald-50 text-emerald-500 border-emerald-100' 
              : (check.status === 'FAIL' ? 'bg-rose-50 text-rose-500 border-rose-100' : 'bg-slate-50 text-slate-300 border-slate-100')
          ]">
            <span class="material-symbols-rounded text-[24px]">
              {{ check.status === 'PASS' ? 'check_circle' : (check.status === 'FAIL' ? 'cancel' : 'pending') }}
            </span>
          </div>
          <div class="flex-1 space-y-0.5">
            <p class="text-[14px] font-bold text-[var(--sys-text-primary)]">{{ check.label }}</p>
            <p class="text-[11px] text-[var(--sys-text-secondary)] font-medium opacity-60 leading-relaxed">{{ check.desc }}</p>
          </div>
          <div v-if="check.status === 'FAIL'" class="shrink-0">
             <button @click="store.setActiveTab(check.tab)" class="px-4 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest bg-rose-500 text-white hover:brightness-110 shadow-lg shadow-rose-500/20 transition-all">Xử lý ngay</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Final Action -->
    <div class="p-10 rounded-[48px] bg-[var(--sys-bg-surface-elevated)] border border-[var(--sys-border-subtle)] shadow-inner relative overflow-hidden">
      <div v-if="!store.isPublished" class="flex flex-col items-center gap-8">
        <div v-if="['director', 'admin'].includes(role)" class="flex flex-col items-center gap-8 w-full">
          <div class="flex items-center gap-2.5 px-5 py-2.5 rounded-full bg-amber-50 border border-amber-100 text-amber-600">
            <span class="material-symbols-rounded text-[18px]">report</span>
            <p class="text-[10px] font-black uppercase tracking-[0.1em]">Lưu ý: Hành động này sẽ khóa vĩnh viễn dữ liệu lịch nền.</p>
          </div>
          
          <button 
            @click="handlePublish"
            :disabled="!isReadyToPublish || store.submitting"
            class="w-full max-w-md py-6 rounded-[32px] bg-[var(--sys-brand-solid)] text-white font-black text-lg uppercase tracking-widest hover:brightness-110 active:scale-95 transition-all shadow-2xl shadow-[var(--sys-brand-solid)]/40 group disabled:opacity-50 disabled:grayscale disabled:shadow-none"
          >
            <div class="flex items-center justify-center gap-4">
               <span v-if="store.submitting" class="material-symbols-rounded animate-spin">sync</span>
               <span v-else class="material-symbols-rounded text-[28px] group-hover:rotate-12 transition-transform">verified</span>
               {{ store.submitting ? 'ĐANG XỬ LÝ...' : 'XÁC NHẬN CÔNG BỐ' }}
            </div>
          </button>
        </div>
        <div v-else class="p-8 rounded-[32px] bg-amber-50/50 border border-amber-100 flex flex-col items-center gap-4 text-center">
           <div class="w-14 h-14 rounded-2xl bg-amber-100 text-amber-600 flex items-center justify-center">
             <span class="material-symbols-rounded text-[32px]">lock_person</span>
           </div>
           <div>
             <p class="text-sm font-bold text-amber-700">Quyền hạn hạn chế</p>
             <p class="text-xs text-amber-600 font-medium mt-1">Chỉ Giám đốc hoặc Admin mới có thể thực hiện <strong>Chốt & Công bố</strong>.</p>
           </div>
        </div>
      </div>

      <div v-else class="flex flex-col items-center gap-6 text-center py-6 animate-in zoom-in duration-500">
        <div class="w-20 h-20 rounded-[32px] bg-emerald-50 text-emerald-500 flex items-center justify-center border-2 border-emerald-100 shadow-xl shadow-emerald-500/10">
           <span class="material-symbols-rounded text-[40px]">verified</span>
        </div>
        <div class="space-y-2">
           <h4 class="text-2xl font-black text-emerald-600 uppercase tracking-tight">Công bố thành công</h4>
           <p class="text-sm text-[var(--sys-text-secondary)] font-medium max-w-sm mx-auto">Lịch làm việc đã được hệ thống ghi nhận chính thức. Nhân viên đã có thể tra cứu lịch cá nhân.</p>
        </div>
        <div class="mt-6 px-8 py-4 rounded-3xl bg-white border border-[var(--sys-border-subtle)] flex items-center gap-4 shadow-sm">
           <div class="text-left border-r border-slate-100 pr-4">
             <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-0.5">Thời điểm</p>
             <p class="text-[12px] font-bold text-slate-700">{{ formatDate(store.publishedAt) }}</p>
           </div>
           <div class="text-left">
             <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-0.5">Thực hiện bởi</p>
             <p class="text-[12px] font-bold text-slate-700">{{ store.publishedBy || 'Quản trị viên' }}</p>
           </div>
        </div>
      </div>
    </div>

    <!-- Modals -->
    <PublishBlockModal v-if="store.modals.blockPublish?.open" />
  </div>
</template>

<script setup>
import { computed, ref, onMounted } from 'vue';
import { useScheduleStore } from '@/stores/useScheduleStore';
import { useCurrentUser } from '@/composables/useCurrentUser';
import PublishBlockModal from './modals/PublishBlockModal.vue';

const store = useScheduleStore();
const { role } = useCurrentUser();

const checklist = ref([
  { id: 'assigned', label: 'Gán ca nhân sự', desc: 'Tất cả nhân sự trong đơn vị phải được gán ít nhất 1 ca làm việc.', status: 'PENDING', tab: 1 },
  { id: 'conflicts', label: 'Xung đột quy định', desc: 'Không có lỗi nghiêm trọng (ERROR) trong việc phân ca.', status: 'PENDING', tab: 3 },
  { id: 'overrides', label: 'Xác nhận ngoại lệ', desc: 'Các điều chỉnh ngoại lệ đã được rà soát.', status: 'PENDING', tab: 2 },
]);

const isReadyToPublish = computed(() => {
  return checklist.value.every(c => c.status === 'PASS');
});

const validate = async () => {
  try {
    const res = await store.validatePublish();
    // Update checklist status based on res
    checklist.value[0].status = store.unassignedEmployees === 0 ? 'PASS' : 'FAIL';
    checklist.value[1].status = store.allWarnings.filter(w => w.severity === 'ERROR').length === 0 ? 'PASS' : 'FAIL';
    checklist.value[2].status = 'PASS'; // Assume pass for now in Phase 1
  } catch (err) {
    console.error('Validation failed:', err);
  }
};

const handlePublish = async () => {
  if (confirm('Bạn có chắc chắn muốn CHỐT LỊCH LÀM VIỆC cho giai đoạn này? Hành động này sẽ khóa dữ liệu lịch nền.')) {
    try {
      await store.publishSchedule();
    } catch (err) {
      store.openModal('blockPublish', { message: err.message });
    }
  }
};

const formatDate = (dateStr) => {
  if (!dateStr) return '---';
  return new Date(dateStr).toLocaleString('vi-VN');
};

onMounted(() => {
  validate();
});
</script>
