<template>
  <div class="space-y-6 pb-8">
    <!-- Header Area: SaaS Enterprise Style -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 bg-transparent text-left px-1">
      <div class="bg-transparent text-left">
        <h1 class="text-xl font-bold text-[var(--sys-text-primary)] mb-0.5 tracking-tight uppercase">Theo dõi Tuyển dụng Phòng ban</h1>
        <p class="text-[13px] text-[var(--sys-text-secondary)] font-medium flex items-center gap-3">
          Giám sát tiến độ các chiến dịch tuyển mộ nhân tài khối IT.
          <span class="px-2 py-0.5 bg-[var(--sys-brand-soft)] text-[var(--sys-brand-solid)] rounded-md border border-[var(--sys-brand-border)] text-[10px] font-bold uppercase tracking-widest shadow-sm">MONITORING MODE</span>
        </p>
      </div>
    </div>

    <!-- Active Job Postings: Premium Card Style -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
      <div v-for="job in jobs" :key="job.jobId" 
        class="bg-[var(--sys-bg-surface)] rounded-lg border border-[var(--sys-border-subtle)] shadow-sm overflow-hidden flex flex-col group hover:border-[var(--sys-brand-solid)] transition-all hover:shadow-lg">
        <div class="p-6 space-y-5">
          <div class="flex justify-between items-start">
            <div class="space-y-1">
              <h4 class="text-[14px] font-bold text-[var(--sys-text-primary)] m-0 uppercase tracking-tight group-hover:text-[var(--sys-brand-solid)] transition-colors">{{ job.title }}</h4>
              <p class="text-[11px] font-bold text-[var(--sys-brand-solid)] uppercase tracking-widest opacity-80 leading-none">JOB-{{ job.jobId }} <span class="mx-2 opacity-30 text-slate-400">|</span> Mức lương: {{ job.salaryMin/1000000 }}M - {{ job.salaryMax/1000000 }}M</p>
            </div>
            <div :class="['px-3 py-1.5 rounded-md text-[10px] font-bold uppercase border shadow-sm tracking-widest transition-all', job.status === 'ĐANG_MỞ' ? 'bg-[var(--sys-success-soft)] text-[var(--sys-success-text)] border-[var(--sys-success-border)]' : 'bg-[var(--sys-bg-page)] text-[var(--sys-text-disabled)] border-[var(--sys-border-subtle)] opacity-60']">
              {{ job.status }}
            </div>
          </div>

          <!-- Progress Visualization: Executive Layout -->
          <div class="grid grid-cols-4 gap-4 py-5 border-t border-b border-[var(--sys-border-subtle)] border-dashed border-t-2 border-b-2">
            <div class="text-center space-y-1.5 border-r border-[var(--sys-border-subtle)] border-dashed last:border-0">
              <p class="text-[10px] font-bold text-[var(--sys-text-secondary)] uppercase opacity-60 tracking-widest">ỨNG TUYỂN</p>
              <p class="text-[18px] font-bold leading-none">{{ job.applied }}</p>
            </div>
            <div class="text-center space-y-1.5 border-r border-[var(--sys-border-subtle)] border-dashed last:border-0">
              <p class="text-[10px] font-bold text-[var(--sys-text-secondary)] uppercase opacity-60 tracking-widest">SÀNG LỌC</p>
              <p class="text-[18px] font-bold text-indigo-600 leading-none">{{ job.screening }}</p>
            </div>
            <div class="text-center space-y-1.5 border-r border-[var(--sys-border-subtle)] border-dashed last:border-0">
              <p class="text-[10px] font-bold text-[var(--sys-text-secondary)] uppercase opacity-60 tracking-widest">PHỎNG VẤN</p>
              <p class="text-[18px] font-bold text-violet-600 leading-none">{{ job.interviewing }}</p>
            </div>
            <div class="text-center space-y-1.5">
              <p class="text-[10px] font-bold text-[var(--sys-text-secondary)] uppercase opacity-60 tracking-widest">THÀNH CÔNG</p>
              <p class="text-[18px] font-bold text-[var(--sys-success-text)] leading-none">{{ job.hired }}</p>
            </div>
          </div>

          <div class="flex items-center gap-4 pt-1">
            <div class="flex gap-2">
               <div v-for="i in Math.min(3, job.applied)" :key="i" class="inline-block h-8 w-8 rounded-md bg-gradient-to-br from-[var(--sys-bg-page)] to-[var(--sys-bg-hover)] flex items-center justify-center font-bold text-[10px] uppercase text-[var(--sys-brand-solid)] shadow-sm border border-[var(--sys-brand-border)]">UV</div>
            </div>
            <span class="text-[11px] font-bold text-[var(--sys-text-secondary)] opacity-60" v-if="job.applied > 3">Và {{ job.applied - 3 }} ứng viên khác...</span>
          </div>
        </div>
        
        <div class="px-5 py-3.5 bg-[var(--sys-bg-page)]/30 border-t border-[var(--sys-border-subtle)] flex justify-between items-center transition-all duration-300">
          <span class="text-[10px] font-bold text-[var(--sys-text-disabled)] uppercase tracking-widest leading-none">Cập nhật: Mới nhất</span>
          <button @click="filterCandidatesByJob(job.title)" class="text-[10px] font-bold text-[var(--sys-brand-solid)] uppercase tracking-widest flex items-center gap-2 hover:opacity-80 transition-opacity">
            CHI TIẾT ỨNG VIÊN
            <span class="material-symbols-rounded text-[16px] font-bold">trending_flat</span>
          </button>
        </div>
      </div>
    </div>

    <!-- Bottom Lists Section -->
    <div class="bg-[var(--sys-bg-surface)] rounded-lg border border-[var(--sys-border-subtle)] shadow-sm overflow-hidden mt-6" id="candidates-list-section">
      <!-- Tabs Area -->
      <div class="flex items-center justify-between gap-2 px-5 py-3.5 bg-[var(--sys-bg-page)]/50 border-b border-[var(--sys-border-subtle)]">
        <h3 class="text-[12px] font-bold text-[var(--sys-text-primary)] uppercase tracking-widest flex items-center gap-2 m-0 transition-all">
          <span class="material-symbols-rounded text-[18px] text-[var(--sys-brand-solid)]">folder_shared</span>
          {{ selectedJobFilter ? `HỒ SƠ ỨNG TUYỂN: ${selectedJobFilter}` : 'Hồ sơ ứng tuyển mới nhất' }}
        </h3>
        <button v-if="selectedJobFilter" @click="clearJobFilter" class="h-8 px-3 rounded-md text-[10px] font-bold text-[var(--sys-danger-text)] bg-[var(--sys-danger-soft)] hover:bg-[var(--sys-danger-solid)] hover:text-white transition-all uppercase tracking-wide flex items-center gap-1 border border-[var(--sys-danger-border)]">
          <span class="material-symbols-rounded text-[14px]">close</span> Bỏ lọc
        </button>
      </div>

      <!-- View Content -->
      <div class="p-5">
        <!-- Grid View (Mới nhất) -->
        <div v-if="!showFullList" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 animate-fade-in">
          <div v-for="c in recentCandidates" :key="c.id" 
            class="p-3.5 rounded-lg border border-[var(--sys-border-subtle)] hover:border-[var(--sys-brand-solid)] transition-all flex items-center justify-between group bg-[var(--sys-bg-page)]/20 shadow-sm cursor-pointer"
            @click="viewCandidateDetails(c)">
            <div class="flex flex-col bg-transparent">
              <h6 class="text-[13px] font-semibold text-[var(--sys-text-primary)] mb-0.5 group-hover:text-[var(--sys-brand-solid)] transition-colors">{{ c.name }}</h6>
              <span class="text-[10px] font-bold text-[var(--sys-brand-solid)] uppercase tracking-tight opacity-70">
                {{ c.position }}
              </span>
            </div>
            <button class="w-8 h-8 flex items-center justify-center rounded-md text-[var(--sys-text-secondary)] hover:bg-[var(--sys-brand-soft)] hover:text-[var(--sys-brand-solid)] transition-all" title="Xem chi tiết">
              <span class="material-symbols-rounded text-[20px]">visibility</span>
            </button>
          </div>
        </div>

        <!-- List View (Sớm nhất đến muộn nhất) -->
        <div v-else class="flex flex-col gap-3 animate-fade-in">
          <div v-for="(c, idx) in sortedCandidates" :key="c.id" 
            class="flex items-center justify-between p-4 rounded-lg border border-[var(--sys-border-subtle)] hover:border-[var(--sys-brand-solid)] transition-all bg-[var(--sys-bg-page)]/20 shadow-sm cursor-pointer group"
            @click="viewCandidateDetails(c)">
            <div class="flex items-center gap-4">
              <div class="w-10 h-10 rounded-md bg-[var(--sys-brand-soft)] text-[var(--sys-brand-solid)] flex items-center justify-center font-bold text-sm uppercase shadow-inner border border-[var(--sys-brand-border)]">
                {{ c.name.charAt(0) }}
              </div>
              <div class="space-y-0.5">
                <h6 class="text-[14px] font-bold text-[var(--sys-text-primary)] m-0 group-hover:text-[var(--sys-brand-solid)] transition-colors">{{ c.name }}</h6>
                <div class="flex items-center gap-3">
                  <span class="text-[11px] font-bold text-[var(--sys-brand-solid)] uppercase tracking-widest opacity-80">{{ c.positionName }}</span>
                  <span class="w-1 h-1 rounded-full bg-[var(--sys-border-strong)] hidden md:block"></span>
                  <span class="text-[11px] font-medium text-[var(--sys-text-secondary)] hidden md:block">Nộp HS: {{ c.date }}</span>
                  <span :class="['text-[10px] font-bold px-1.5 py-0.5 border rounded uppercase', c.status === 'pending_hr' ? 'bg-[var(--sys-warning-soft)] text-[var(--sys-warning-text)] border-[var(--sys-warning-border)]' : (c.status === 'pending_mgr' ? 'bg-[var(--sys-brand-soft)] text-[var(--sys-brand-solid)] border-[var(--sys-brand-border)]' : 'bg-purple-50 text-purple-700 border-purple-200')]">{{ c.statusLabel }}</span>
                </div>
              </div>
            </div>
            <div class="flex items-center gap-4">
              <span class="text-[11px] font-bold px-2.5 py-1 bg-[var(--sys-success-soft)] text-[var(--sys-success-text)] rounded border border-[var(--sys-success-border)] flex items-center gap-1">
                <span class="material-symbols-rounded text-[14px]">psychology</span> {{ c.aiScore }}%
              </span>
              <button class="w-8 h-8 flex items-center justify-center rounded-md text-[var(--sys-text-secondary)] hover:bg-[var(--sys-bg-hover)] transition-all">
                <span class="material-symbols-rounded text-[20px]">chevron_right</span>
              </button>
            </div>
          </div>
        </div>

        <div class="text-center pt-6 mt-6 border-t border-[var(--sys-border-subtle)] border-dashed">
          <button @click="showFullList = !showFullList" class="text-[12px] font-bold text-[var(--sys-brand-solid)] hover:opacity-80 transition-opacity flex items-center justify-center gap-1 mx-auto uppercase tracking-wide">
            {{ showFullList ? 'Thu gọn danh mục' : 'Truy xuất toàn bộ danh mục hồ sơ' }}
            <span class="material-symbols-rounded text-[18px]">{{ showFullList ? 'expand_less' : 'keyboard_double_arrow_right' }}</span>
          </button>
        </div>
      </div>
    </div>

    <!-- Modal Chi tiết Hồ sơ --->
    <Teleport to="body">
      <Transition name="modal-fade">
        <div v-if="showModal" class="fixed inset-0 z-[10000] flex items-center justify-center p-4">
          <div class="fixed inset-0 w-screen h-screen bg-black/50 z-[9999]" @click="closeModal"></div>
          <div class="relative z-[10000] bg-[var(--sys-bg-surface-elevated)] border border-[var(--sys-border-subtle)] w-full max-w-2xl max-h-[90vh] rounded-lg shadow-2xl overflow-hidden flex flex-col text-left">
            <!-- Modal Header -->
            <div class="px-6 py-4 border-b border-[var(--sys-border-subtle)] flex items-center justify-between bg-[var(--sys-bg-page)]/50">
              <div class="bg-transparent text-left flex items-center gap-3">
                <span class="material-symbols-rounded text-[var(--sys-brand-solid)] text-[24px]">assignment_ind</span>
                <div>
                  <h3 class="text-sm font-bold text-[var(--sys-text-primary)] m-0 uppercase tracking-wide">Chi tiết ứng viên</h3>
                  <p v-if="canReviewSelectedCandidate" class="text-[11px] text-[var(--sys-brand-solid)] mt-0.5 font-bold uppercase tracking-widest">YÊU CẦU ĐÁNH GIÁ</p>
                  <p v-else class="text-[11px] text-[var(--sys-text-secondary)] mt-0.5 font-medium uppercase tracking-widest opacity-80">CHỈ ĐỌC (VIEW ONLY)</p>
                </div>
              </div>
              <button @click="closeModal" class="w-8 h-8 flex items-center justify-center rounded-md hover:bg-[var(--sys-bg-hover)] transition-all text-[var(--sys-text-secondary)] shadow-sm border border-transparent hover:border-[var(--sys-border-strong)]">
                <span class="material-symbols-rounded text-xl">close</span>
              </button>
            </div>

            <!-- Modal Body -->
            <div v-if="selectedCandidate" class="flex-1 overflow-y-auto p-6 custom-scrollbar bg-[var(--sys-bg-surface)] space-y-6">
              
              <!-- Profile Header -->
              <div class="flex items-center gap-5">
                <div class="w-20 h-20 rounded-lg bg-[var(--sys-bg-page)] border border-[var(--sys-border-subtle)] flex items-center justify-center font-bold text-2xl text-[var(--sys-brand-solid)] uppercase shadow-inner shrink-0 relative">
                  {{ selectedCandidate.name.charAt(0) }}
                </div>
                <div>
                  <h2 class="text-xl font-bold text-[var(--sys-text-primary)] mb-1 uppercase tracking-tight">{{ selectedCandidate.name }}</h2>
                  <p class="text-[12px] font-bold text-[var(--sys-brand-solid)] uppercase tracking-widest">{{ selectedCandidate.position }}</p>
                  <p class="text-[11px] font-medium text-[var(--sys-text-secondary)] mt-1.5 flex items-center gap-1.5 opacity-80">
                    <span class="material-symbols-rounded text-[14px]">event</span>
                    Ngày nộp hồ sơ: {{ selectedCandidate.date || 'Chưa có' }}
                  </p>
                  <p v-if="selectedCandidate.interviewDate" class="text-[11px] font-bold text-[var(--sys-brand-solid)] mt-1 flex items-center gap-1.5">
                    <span class="material-symbols-rounded text-[14px]">schedule</span>
                    Lịch phỏng vấn: {{ new Date(selectedCandidate.interviewDate).toLocaleString('vi-VN') }}
                  </p>
                </div>
              </div>

              <!-- General Info Grid -->
              <div class="grid grid-cols-2 gap-x-6 gap-y-4 pt-6 border-t border-[var(--sys-border-subtle)] border-dashed border-t-2">
                <div class="flex flex-col border border-[var(--sys-border-subtle)] p-3 rounded-md bg-[var(--sys-bg-page)]/50 shadow-sm">
                  <span class="text-[10px] font-bold text-[var(--sys-text-secondary)] uppercase tracking-widest opacity-70 mb-1 flex items-center gap-1"><span class="material-symbols-rounded text-[14px]">work</span> Kinh nghiệm làm việc</span>
                  <span class="text-[13px] font-bold text-[var(--sys-text-primary)]">{{ selectedCandidate.workExperience?.length ? selectedCandidate.workExperience.length + ' Công ty/Dự án' : 'Chưa cập nhật' }}</span>
                </div>
                <div class="flex flex-col border border-[var(--sys-border-subtle)] p-3 rounded-md bg-[var(--sys-bg-page)]/50 shadow-sm">
                  <span class="text-[10px] font-bold text-[var(--sys-text-secondary)] uppercase tracking-widest opacity-70 mb-1 flex items-center gap-1"><span class="material-symbols-rounded text-[14px]">payments</span> Lương kỳ vọng</span>
                  <span class="text-[13px] font-bold text-[var(--sys-text-primary)]">{{ selectedCandidate.notes ? selectedCandidate.notes.replace('Mức lương kỳ vọng:', '').trim() || 'Thỏa thuận' : 'Thỏa thuận' }}</span>
                </div>
                <div class="flex flex-col border border-[var(--sys-border-subtle)] p-3 rounded-md bg-[var(--sys-bg-page)]/50 shadow-sm">
                  <span class="text-[10px] font-bold text-[var(--sys-text-secondary)] uppercase tracking-widest opacity-70 mb-1 flex items-center gap-1"><span class="material-symbols-rounded text-[14px]">psychology</span> Điểm AI (Match Score)</span>
                  <span class="text-[13px] font-bold text-[var(--sys-success-text)]">{{ selectedCandidate.aiScore }}% Phù hợp</span>
                </div>
                <div class="flex flex-col border border-[var(--sys-border-subtle)] p-3 rounded-md bg-[var(--sys-bg-page)]/50 shadow-sm">
                  <span class="text-[10px] font-bold text-[var(--sys-text-secondary)] uppercase tracking-widest opacity-70 mb-1 flex items-center gap-1"><span class="material-symbols-rounded text-[14px]">school</span> Học vấn</span>
                  <span class="text-[13px] font-bold text-[var(--sys-text-primary)]">{{ selectedCandidate.education?.school || 'Chưa cập nhật' }}</span>
                </div>
                <div class="flex flex-col border border-[var(--sys-border-subtle)] p-3 rounded-md bg-[var(--sys-bg-page)]/50 shadow-sm">
                  <span class="text-[10px] font-bold text-[var(--sys-text-secondary)] uppercase tracking-widest opacity-70 mb-1 flex items-center gap-1"><span class="material-symbols-rounded text-[14px]">checklist</span> Kỹ năng chính</span>
                  <span class="text-[13px] font-bold text-[var(--sys-text-primary)]">{{ selectedCandidate.skills ? selectedCandidate.skills.join(', ') : 'Chưa cập nhật' }}</span>
                </div>
              </div>

              <!-- Cover Letter -->
              <div v-if="selectedCandidate.coverLetter" class="p-3 bg-[var(--sys-bg-page)] rounded-md border border-[var(--sys-border-subtle)] shadow-sm">
                 <span class="text-[10px] font-bold text-[var(--sys-text-secondary)] uppercase tracking-widest opacity-70 mb-2 flex items-center gap-1"><span class="material-symbols-rounded text-[14px]">format_quote</span> Lời giới thiệu</span>
                 <p class="text-[13px] text-[var(--sys-text-primary)] italic">"{{ selectedCandidate.coverLetter }}"</p>
              </div>

              <!-- CV Link Info / Action -->
              <div class="px-4 py-3 bg-[var(--sys-bg-page)] border border-[var(--sys-border-subtle)] rounded-md flex items-center justify-between" v-if="selectedCandidate.cvUrl">
                <div class="flex items-center gap-3">
                  <span class="material-symbols-outlined text-[var(--sys-text-secondary)] text-[18px]">link</span>
                  <button @click="openCv(selectedCandidate.cvUrl)" class="text-[13px] font-medium text-[var(--sys-brand-solid)] hover:underline flex items-center gap-1">
                    Xem chi tiết CV / Portfolio (LinkedIn)
                    <span class="material-symbols-outlined text-[14px]">open_in_new</span>
                  </button>
                </div>
              </div>

              <!-- Manager Review Action -->
              <div v-if="canReviewSelectedCandidate" class="pt-6 border-t border-[var(--sys-border-subtle)] border-dashed border-t-2">
                <h4 class="text-[12px] font-bold text-[var(--sys-brand-solid)] mb-2 uppercase tracking-wide flex items-center gap-2">
                  <span class="material-symbols-rounded text-[18px]">rate_review</span> Nhận xét & Đề xuất
                </h4>
                <div class="mb-3">
                  <label class="text-[10px] font-bold text-[var(--sys-text-secondary)] uppercase tracking-widest block mb-1.5">Điểm đánh giá (1-10)</label>
                  <div class="flex items-center gap-3">
                    <input
                      v-model.number="managerScore"
                      type="range"
                      min="1"
                      max="10"
                      step="1"
                      class="flex-1 accent-[var(--sys-brand-solid)] h-2"
                    />
                    <span class="w-9 h-9 rounded-md bg-[var(--sys-brand-soft)] border border-[var(--sys-brand-border)] text-[var(--sys-brand-solid)] flex items-center justify-center font-bold text-xs shrink-0">
                      {{ managerScore }}
                    </span>
                  </div>
                </div>
                <textarea v-model="managerReviewText" 
                  class="w-full border border-[var(--sys-border-strong)] rounded-md p-3 text-[13px] font-medium bg-[var(--sys-bg-page)] focus:outline-none focus:border-[var(--sys-brand-solid)] resize-y shadow-inner" 
                  rows="3" 
                  placeholder="Nhập nhận xét về chuyên môn của ứng viên và duyệt phỏng vấn..."></textarea>
              </div>

            </div>

            <!-- Modal Footer -->
            <div class="px-6 py-4 border-t border-[var(--sys-border-subtle)] bg-[var(--sys-bg-page)] flex justify-end gap-3">
              <template v-if="canReviewSelectedCandidate">
                <button
                  @click="handleManagerDecision('reject')"
                  :disabled="isSubmittingDecision || !canSubmitDecision"
                  class="px-4 py-2 border border-[var(--sys-danger-border)] text-[var(--sys-danger-text)] rounded-md font-bold text-[11px] uppercase tracking-wide hover:bg-[var(--sys-danger-soft)] transition-all disabled:opacity-40 disabled:pointer-events-none"
                >
                  Đề xuất rớt
                </button>
                <button
                  @click="handleManagerDecision('approve')"
                  :disabled="isSubmittingDecision || !canSubmitDecision"
                  class="px-4 py-2 bg-[var(--sys-brand-solid)] text-white border border-transparent rounded-md font-bold text-[11px] uppercase tracking-wide hover:brightness-95 transition-all disabled:opacity-40 disabled:pointer-events-none"
                >
                  Đề xuất đậu
                </button>
              </template>
              <button @click="closeModal" class="px-6 py-2 bg-[var(--sys-brand-solid)] text-white border border-transparent rounded-md font-bold text-[12px] hover:brightness-90 shadow-sm uppercase tracking-wide transition-all active:scale-95">Đóng</button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { useRecruitmentStore, submitManagerEvaluation, refreshRecruitmentCandidates } from '@/composables/useRecruitmentStore';
import { useConfirm } from '@/composables/useConfirm';
import { AUTH_USER_KEY } from '@/services/runtimeConfig.js';
import { getSessionItem } from '@/services/session.js';

const showModal = ref(false);
const showFullList = ref(false);
const selectedCandidate = ref(null);
const selectedJobFilter = ref('');
const managerReviewText = ref('');
const managerScore = ref(7);
const isSubmittingDecision = ref(false);

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

const authUser = (() => {
  try {
    const raw = getSessionItem(AUTH_USER_KEY);
    return raw ? JSON.parse(raw) : null;
  } catch {
    return null;
  }
})();

const managedDeptIds = Array.isArray(authUser?.managed_department_ids)
  ? authUser.managed_department_ids.map((v) => Number(v)).filter((v) => Number.isFinite(v) && v > 0)
  : [];

// If account has no explicit managed departments, do not hard-filter by a stale localStorage department.
// This prevents "blank manager recruitment board" when backend manager mapping is incomplete.
const deptScope = managedDeptIds.length ? managedDeptIds : null;

const managerEmployeeId = Number(authUser?.employee_id || 0);
const store = useRecruitmentStore(
  deptScope,
  { managerId: managerEmployeeId > 0 ? managerEmployeeId : null }
);
const { showAlert } = useConfirm();
let refreshTimer = null;

// Dynamic Jobs loading
const jobs = computed(() => {
  const candidates = store.candidates.value || [];
  const grouped = new Map();

  candidates.forEach((candidate) => {
    const key = String(candidate.jobId || candidate.positionName || candidate.position || candidate.id);
    if (!grouped.has(key)) {
      grouped.set(key, {
        jobId: candidate.jobId || key,
        title: candidate.positionName || candidate.position || 'Vị trí tuyển dụng',
        salaryMin: 0,
        salaryMax: 0,
        status: 'ĐANG_MỞ',
        applied: 0,
        screening: 0,
        interviewing: 0,
        hired: 0,
      });
    }
    const job = grouped.get(key);
    job.applied += 1;
    if (['pending_hr', 'pending_mgr', 'mgr_approved'].includes(candidate.status)) job.screening += 1;
    if (candidate.status === 'interviewing') job.interviewing += 1;
    if (candidate.status === 'pass') job.hired += 1;
  });

  return Array.from(grouped.values()).sort((a, b) => b.applied - a.applied);
});

const baseFilteredCandidates = computed(() => {
  let list = store.candidates.value.filter(c => c.status !== 'pass' && c.status !== 'fail');
  if (selectedJobFilter.value) {
    list = list.filter(c => c.position === selectedJobFilter.value);
  }
  return list;
});

const recentCandidates = computed(() => {
  return [...baseFilteredCandidates.value].slice(0, 4);
});

const sortedCandidates = computed(() => {
  return [...baseFilteredCandidates.value];
});

const filterCandidatesByJob = (jobTitle) => {
  selectedJobFilter.value = jobTitle;
  showFullList.value = true;
  document.getElementById('candidates-list-section')?.scrollIntoView({ behavior: 'smooth' });
};

const clearJobFilter = () => {
  selectedJobFilter.value = '';
};

const viewCandidateDetails = (candidate) => {
  selectedCandidate.value = candidate;
  managerReviewText.value = candidate?.managerReview || '';
  managerScore.value = Number(candidate?.managerScore || 7) || 7;
  showModal.value = true;
};

const closeModal = () => {
  showModal.value = false;
  managerReviewText.value = '';
  managerScore.value = 7;
  isSubmittingDecision.value = false;
  setTimeout(() => { selectedCandidate.value = null; }, 200);
};

const canSubmitDecision = computed(() => String(managerReviewText.value || '').trim().length > 0);
const canReviewSelectedCandidate = computed(() => {
  const status = String(selectedCandidate.value?.status || '').trim();
  if (!selectedCandidate.value) return false;
  return status !== 'pass' && status !== 'fail';
});

const handleManagerDecision = async (decision) => {
  if (!selectedCandidate.value || isSubmittingDecision.value) return;
  const notes = String(managerReviewText.value || '').trim();
  if (!notes) {
    await showAlert('THIẾU NHẬN XÉT', 'Vui lòng nhập nhận xét trước khi gửi đề xuất về HR.');
    return;
  }

  isSubmittingDecision.value = true;
  try {
    await submitManagerEvaluation(
      selectedCandidate.value.id,
      { notes, score: Number(managerScore.value || 0) || null },
      decision
    );
    await refreshRecruitmentCandidates();

    selectedCandidate.value.managerReview = notes;
    selectedCandidate.value.managerScore = Number(managerScore.value || 0) || null;
    selectedCandidate.value.managerDecisionProposal = decision === 'approve' ? 'PASS' : 'FAIL';
    selectedCandidate.value.status = decision === 'approve' ? 'mgr_approved' : 'pending_hr';

    await showAlert(
      'ĐÃ GỬI VỀ HR',
      decision === 'approve'
        ? 'Đề xuất ĐẬU đã gửi về HR. HR sẽ quyết định cuối cùng và gửi mail cho ứng viên.'
        : 'Đề xuất TỪ CHỐI đã gửi về HR. HR sẽ quyết định cuối cùng và gửi mail cho ứng viên.'
    );
    closeModal();
  } catch (error) {
    await showAlert('GỬI THẤT BẠI', error?.message || 'Không thể gửi đánh giá về HR. Vui lòng thử lại.');
  } finally {
    isSubmittingDecision.value = false;
  }
};

onMounted(async () => {
  try {
    await refreshRecruitmentCandidates();
  } catch (error) {
    console.warn('[tp-tuyendung] refresh failed:', error?.message || error);
  }
  refreshTimer = setInterval(async () => {
    if (typeof document !== 'undefined' && document.hidden) return;
    try {
      await refreshRecruitmentCandidates();
    } catch {
      // best effort background refresh
    }
  }, 45000);
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

.custom-scrollbar::-webkit-scrollbar {
  width: 5px;
  height: 5px;
}
.custom-scrollbar::-webkit-scrollbar-track {
  background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
  background: var(--sys-border-subtle);
  border-radius: 5px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
  background: var(--sys-brand-solid);
}

.modal-fade-enter-active,
.modal-fade-leave-active {
  transition: opacity 0.2s ease;
}

.modal-fade-enter-from,
.modal-fade-leave-to {
  opacity: 0;
}

@keyframes fadeInOpacity {
  from { opacity: 0; transform: translateY(4px); }
  to { opacity: 1; transform: translateY(0); }
}
.animate-fade-in {
  animation: fadeInOpacity 0.3s ease-out forwards;
}
</style>
