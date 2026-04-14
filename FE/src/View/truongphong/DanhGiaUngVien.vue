<template>
  <div class="space-y-6 pb-8">
    <!-- Header Area: SaaS Enterprise Style -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 bg-transparent text-left px-1">
      <div class="bg-transparent text-left">
        <h1 class="text-xl font-bold text-[var(--sys-text-primary)] mb-0.5 tracking-tight uppercase">Đánh giá Ứng viên Phỏng vấn</h1>
        <p class="text-[13px] text-[var(--sys-text-secondary)] font-medium flex items-center gap-3">
          Trưởng phòng thẩm định năng lực và phản hồi kết quả phỏng vấn cho HR.
          <span class="px-2 py-0.5 bg-[var(--sys-brand-soft)] text-[var(--sys-brand-solid)] rounded-md border border-[var(--sys-brand-border)] text-[10px] font-bold uppercase tracking-widest shadow-sm">RECRUITMENT EVALUATION</span>
        </p>
      </div>
      <div class="bg-transparent hidden lg:block">
        <div class="px-4 py-3 bg-[var(--sys-bg-surface)] rounded-lg border border-[var(--sys-border-subtle)] shadow-sm flex items-center gap-3.5">
          <div class="w-10 h-10 rounded-md bg-[var(--sys-brand-solid)] text-white flex items-center justify-center font-bold text-sm shadow-md">IT</div>
          <div>
            <p class="text-[10px] font-bold text-[var(--sys-text-secondary)] uppercase m-0 tracking-widest opacity-60 shadow-none">PHÒNG QUẢN LÝ</p>
            <p class="text-[13px] font-bold text-[var(--sys-text-primary)] m-0 uppercase tracking-tight">{{ deptName }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Candidate List: Premium Form Style -->
    <div class="bg-[var(--sys-bg-surface)] rounded-lg border border-[var(--sys-border-subtle)] shadow-sm overflow-hidden flex flex-col">
      <div class="px-5 py-3.5 border-b border-[var(--sys-border-subtle)] bg-[var(--sys-bg-page)]/30">
        <h3 class="text-[11px] font-bold text-[var(--sys-text-primary)] uppercase tracking-widest m-0 flex items-center gap-2.5">
          <div class="w-8 h-8 rounded-md bg-[var(--sys-brand-soft)] text-[var(--sys-brand-solid)] border border-[var(--sys-brand-border)] flex items-center justify-center shrink-0">
            <span class="material-symbols-rounded text-[var(--sys-brand-solid)] text-[20px] font-bold">how_to_reg</span>
          </div>
          DANH SÁCH CHỜ THẨM ĐỊNH NĂNG LỰC
        </h3>
      </div>

      <div v-if="pendingEval.length === 0" class="p-20 text-center bg-[var(--sys-bg-page)]/10">
        <span class="material-symbols-rounded text-6xl text-[var(--sys-text-disabled)] opacity-10 mb-4 font-bold">person_search</span>
        <p class="text-[13px] font-bold text-[var(--sys-text-disabled)] uppercase opacity-40">Hiện không có ứng viên nào đang chờ đánh giá chuyên môn</p>
      </div>

      <div v-else class="divide-y divide-[var(--sys-border-subtle)]">
        <div v-for="candidate in pendingEval" :key="candidate.id" class="p-6 hover:bg-[var(--sys-bg-hover)] transition-all duration-300 group">
          <div class="flex flex-col lg:flex-row gap-8">
            <!-- Information Column -->
            <div class="lg:w-1/3 space-y-4">
              <div class="flex items-center gap-4">
                <div class="w-14 h-14 rounded-md border-2 border-[var(--sys-brand-solid)] flex items-center justify-center font-bold text-xl text-[var(--sys-brand-solid)] shadow-inner bg-[var(--sys-bg-page)]">
                  {{ candidate.name.charAt(0) }}
                </div>
                <div class="flex flex-col">
                  <h4 class="text-[14px] font-bold text-[var(--sys-text-primary)] m-0 uppercase tracking-tight group-hover:text-[var(--sys-brand-solid)] transition-colors">{{ candidate.name }}</h4>
                  <p class="text-[11px] font-bold text-[var(--sys-brand-solid)] uppercase tracking-widest opacity-80 leading-none">{{ candidate.position }}</p>
                </div>
              </div>
              
              <div class="space-y-2 py-4 border-t border-b border-[var(--sys-border-subtle)] border-dashed border-t-2 border-b-2">
                <div class="flex items-center justify-between text-[12.5px] font-bold text-[var(--sys-text-primary)]">
                  <span class="text-[10px] font-bold uppercase tracking-widest text-[var(--sys-text-secondary)] opacity-60">TRẠNG THÁI:</span>
                  <span :class="['', candidate.status === 'pending_mgr' ? 'text-[var(--sys-warning-text)]' : 'text-purple-600']">{{ candidate.status === 'pending_mgr' ? 'CHỜ THẨM ĐỊNH CV' : 'ĐÃ PHỎNG VẤN' }}</span>
                </div>
                <div class="flex items-center justify-between text-[12.5px] font-bold text-[var(--sys-text-primary)]">
                  <span class="text-[10px] font-bold uppercase tracking-widest text-[var(--sys-text-secondary)] opacity-60">LỊCH HẸN PHỎNG VẤN:</span>
                  <span class="text-[var(--sys-brand-solid)]">{{ candidate.interviewDate || 'Chưa lên lịch' }}</span>
                </div>
                <div class="flex items-center justify-between text-[12.5px] font-bold text-[var(--sys-text-primary)]">
                  <span class="text-[10px] font-bold uppercase tracking-widest text-[var(--sys-text-secondary)] opacity-60">RANKING AI SCORE:</span>
                  <span class="text-[var(--sys-success-text)]">{{ candidate.aiScore }}% MATCH</span>
                </div>
              </div>

              <button @click="openCv(candidate.cvUrl)" class="w-full h-10 px-4 border-2 border-dashed border-[var(--sys-brand-border)] text-[var(--sys-brand-solid)] rounded-md text-[10px] font-bold uppercase tracking-widest hover:bg-[var(--sys-brand-soft)] transition-all flex items-center justify-center gap-2.5">
                <span class="material-symbols-rounded text-[18px] font-bold">attachment</span>
                TRUY XUẤT HỒ SƠ CV / LINKEDIN
              </button>
            </div>

            <!-- Review Column -->
            <div class="flex-1 space-y-4 lg:pl-8 lg:border-l-2 lg:border-dashed lg:border-[var(--sys-border-subtle)] flex flex-col">
              <!-- Score Slider -->
              <div class="space-y-1.5">
                <label class="text-[11px] font-bold text-[var(--sys-text-secondary)] uppercase tracking-widest block">SỐ ĐIỂM ĐÁNH GIÁ CHUYÊN MÔN (1–10)</label>
                <div class="flex items-center gap-3">
                  <input 
                    v-model.number="scores[candidate.id]"
                    type="range" min="1" max="10" step="1"
                    class="flex-1 accent-[var(--sys-brand-solid)] h-2"
                  />
                  <span class="w-10 h-10 rounded-md bg-[var(--sys-brand-soft)] border border-[var(--sys-brand-border)] text-[var(--sys-brand-solid)] flex items-center justify-center font-bold text-sm shrink-0">
                    {{ scores[candidate.id] || '—' }}
                  </span>
                </div>
              </div>
              <!-- Notes -->
              <div class="flex-grow space-y-2">
                <label class="text-[11px] font-bold text-[var(--sys-brand-solid)] uppercase tracking-widest block mb-2 shadow-none">QUYẾT ĐỊNH & ĐÁNH GIÁ CHUYÊN MÔN TỔNG QUAN *</label>
                <textarea 
                  v-model="reviews[candidate.id]"
                  class="w-full h-32 p-4 bg-[var(--sys-bg-page)]/50 border border-[var(--sys-border-strong)] rounded-md text-[13px] font-bold text-[var(--sys-text-primary)] outline-none focus:border-[var(--sys-brand-solid)] focus:bg-white shadow-sm transition-all placeholder:opacity-30"
                  placeholder="Nhập nhận định kỹ thuẫt, thái độ chuyên nghiệp và mức độ hài lòng thực tế..."
                ></textarea>
              </div>
              <div class="flex justify-end gap-3 pt-2">
                <button 
                  @click="submitEval(candidate.id, 'reject')"
                  :disabled="!reviews[candidate.id]"
                  class="h-11 px-6 border border-[var(--sys-danger-border)] text-[var(--sys-danger-text)] rounded-md text-[11px] font-bold uppercase tracking-widest hover:bg-[var(--sys-danger-soft)] transition-all active:scale-95 disabled:opacity-30 disabled:pointer-events-none"
                >
                  Từ chối
                </button>
                <button 
                  @click="submitEval(candidate.id, 'approve')"
                  :disabled="!reviews[candidate.id]"
                  class="h-11 px-10 bg-[var(--sys-brand-solid)] text-white rounded-md text-[11px] font-bold uppercase tracking-widest shadow-md hover:brightness-110 transition-all flex items-center gap-3 active:scale-95 disabled:opacity-30 disabled:pointer-events-none"
                >
                  <span class="material-symbols-rounded text-[20px] font-bold">send</span>
                  DUYỆT & GỬi HR
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import {
  useManagerApplications,
  submitManagerEvaluation,
  refreshRecruitmentCandidates,
} from '@/composables/useRecruitmentStore';
import { useConfirm } from '@/composables/useConfirm';
import { AUTH_USER_KEY } from '@/services/runtimeConfig';
import { getSessionItem } from '@/services/session.js';

const { showAlert } = useConfirm();

const authUser = (() => {
  try {
    const raw = getSessionItem(AUTH_USER_KEY);
    return raw ? JSON.parse(raw) : null;
  } catch {
    return null;
  }
})();

const managedDeptIds = Array.isArray(authUser?.managed_department_ids)
  ? authUser.managed_department_ids
    .map((value) => Number(value))
    .filter((value) => Number.isFinite(value) && value > 0)
  : [];
const deptScope = managedDeptIds.length ? managedDeptIds : null;

const managerEmployeeId = Number(authUser?.employee_id || 0);
const { pendingEval, deptName } = useManagerApplications(
  deptScope,
  managerEmployeeId > 0 ? managerEmployeeId : null
);

const reviews = ref({});
const scores  = ref({});
let refreshTimer = null;

const openCv = (cvUrl) => {
  if (!cvUrl) return;
  if (cvUrl.startsWith('data:')) {
    fetch(cvUrl)
      .then(res => res.blob())
      .then(blob => {
        const objectUrl = URL.createObjectURL(blob);
        window.open(objectUrl, '_blank');
      });
  } else {
    window.open(cvUrl, '_blank');
  }
};

async function submitEval(candidateId, decision) {
  const notes = reviews.value[candidateId];
  const score = scores.value[candidateId] || null;
  if (!notes) return;

  try {
    await submitManagerEvaluation(candidateId, { notes, score }, decision);
  } catch (error) {
    await showAlert('GỬI THẤT BẠI', error?.message || 'Không thể gửi đánh giá về HR. Vui lòng thử lại.');
    return;
  }

  const msg = decision === 'approve'
    ? 'Đề xuất ĐẬU đã gửi về HR. HR sẽ xác nhận kết quả cuối cùng và gửi mail cho ứng viên.'
    : 'Đề xuất TỪ CHỐI đã gửi về HR. HR sẽ quyết định cuối cùng và gửi mail cho ứng viên.';

  await showAlert(
    decision === 'approve' ? 'TIẾP NHẬN ĐÁNH GIÁ' : 'TỪ CHỐI',
    msg
  );
  delete reviews.value[candidateId];
  delete scores.value[candidateId];
}

onMounted(async () => {
  try {
    await refreshRecruitmentCandidates();
  } catch (error) {
    console.warn('[tp-danhgia] refresh failed:', error?.message || error);
  }
  refreshTimer = setInterval(async () => {
    try {
      await refreshRecruitmentCandidates();
    } catch {
      // best effort
    }
  }, 10000);
});

onUnmounted(() => {
  if (refreshTimer) {
    clearInterval(refreshTimer);
    refreshTimer = null;
  }
});
</script>

<style scoped>
* { -webkit-font-smoothing: antialiased; -moz-osx-font-smoothing: grayscale; }
textarea::placeholder { font-weight: 700; opacity: 0.3 !important; }
</style>
