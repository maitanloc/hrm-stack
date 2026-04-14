<template>
  <div class="interview-schedule-page space-y-6 pb-10 min-h-screen bg-[var(--sys-bg-page)] text-[var(--sys-text-primary)] p-4 md:p-6 lg:p-8">
    <!-- Header -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 bg-transparent text-left">
      <div class="bg-transparent text-left">
        <h1 class="text-xl font-semibold text-[var(--sys-text-primary)] tracking-tight m-0 uppercase">Điều phối Lịch phỏng vấn</h1>
        <p class="text-[13px] text-[var(--sys-text-secondary)] mt-0.5">Thẩm định năng lực và quản trị hội đồng tuyển dụng toàn cầu.</p>
      </div>
      <div class="bg-transparent text-right hidden xl:block shrink-0">
        <div class="inline-flex items-center gap-2 bg-white px-3 py-1.5 rounded-md border border-[var(--sys-border-subtle)] font-bold text-[10px] text-[var(--sys-brand-solid)] shadow-sm uppercase tracking-wider">
          <span class="w-1.5 h-1.5 rounded-full bg-[var(--sys-brand-solid)] animate-pulse"></span>
          Recruitment SaaS v3.0
        </div>
      </div>
    </div>

    <!-- Toolbar -->
    <div class="flex flex-col lg:flex-row items-center gap-4 bg-transparent border-b border-[var(--sys-border-subtle)] pb-4">
      <div class="flex items-center gap-1 bg-[var(--sys-bg-page)] p-1 rounded-md border border-[var(--sys-border-subtle)] h-11 shadow-inner shrink-0">
        <button @click="currentView = 'calendar'" 
          :class="['px-5 h-full text-[12px] font-bold rounded-md transition-all flex items-center gap-2 border uppercase', 
          currentView === 'calendar' ? 'bg-white text-[var(--sys-brand-solid)] border-[var(--sys-border-strong)] shadow-sm' : 'text-[var(--sys-text-secondary)] border-transparent hover:text-[var(--sys-text-primary)]']">
          <span class="material-symbols-outlined text-[18px]">calendar_month</span>
          Lịch tóm lược
        </button>
        <button @click="currentView = 'list'" 
          :class="['px-5 h-full text-[12px] font-bold rounded-md transition-all flex items-center gap-2 border uppercase', 
          currentView === 'list' ? 'bg-white text-[var(--sys-brand-solid)] border-[var(--sys-border-strong)] shadow-sm' : 'text-[var(--sys-text-secondary)] border-transparent hover:text-[var(--sys-text-primary)]']">
          <span class="material-symbols-outlined text-[18px]">view_list</span>
          Danh sách chi tiết
        </button>
      </div>
      
      <div class="flex items-center gap-3 w-full lg:w-auto shrink-0 flex-wrap lg:flex-nowrap bg-transparent justify-end">
        <Dropdown v-model="filterStatus" :options="statusOptions" class="min-w-[160px] h-11" />
        
        <button @click="openCreateModal" class="h-11 px-6 bg-[var(--sys-brand-solid)] rounded-md font-bold text-white hover:brightness-110 shadow-lg transition-all flex items-center gap-2 text-[12px] uppercase tracking-wider shrink-0 active:scale-95">
          <span class="material-symbols-outlined text-[20px]">add_circle</span>
          Thiết lập lịch mới
        </button>
      </div>
    </div>

    <!-- Main Content -->
    <div class="space-y-6">
      <!-- Calendar View (Expandable Architecture) -->
      <div v-show="currentView === 'calendar'" 
        :class="['bg-white rounded-lg border border-[var(--sys-border-subtle)] shadow-sm overflow-hidden relative motion-safe:animate-fadeIn transition-all duration-300', 
        isFullscreen ? 'fixed inset-0 z-[99999] p-6 bg-white overflow-y-auto' : '']"
      >
        <!-- Navigation Header -->
        <div class="p-4 border-b border-[var(--sys-border-subtle)] bg-[var(--sys-bg-page)]/30 flex justify-between items-center h-16">
          <div class="flex items-center gap-3">
            <!-- Icon Wrapper (Directive 1) -->
            <div class="w-10 h-10 rounded-md bg-[var(--sys-brand-soft)] text-[var(--sys-brand-solid)] flex items-center justify-center shrink-0 border border-[var(--sys-brand-border)]">
              <span class="material-symbols-outlined text-xl">event_available</span>
            </div>
            <div>
              <h3 class="text-[15px] font-bold text-[var(--sys-text-primary)] m-0 leading-tight uppercase">Tháng {{ currentMonth + 1 }}, {{ currentYear }}</h3>
              <p class="text-[10px] font-bold text-[var(--sys-text-secondary)] uppercase tracking-widest mt-0.5 opacity-60">Toàn cảnh lịch trình phỏng vấn</p>
            </div>
          </div>
          <div class="flex gap-2 items-center">
            <div class="flex gap-1 border border-[var(--sys-border-subtle)] bg-white rounded-md p-1">
              <button @click="prevMonth" class="w-8 h-8 flex items-center justify-center rounded-md text-[var(--sys-text-secondary)] hover:bg-[var(--sys-bg-hover)] transition-all active:scale-90"><span class="material-symbols-outlined text-lg">chevron_left</span></button>
              <button @click="nextMonth" class="w-8 h-8 flex items-center justify-center rounded-md text-[var(--sys-text-secondary)] hover:bg-[var(--sys-bg-hover)] transition-all active:scale-90"><span class="material-symbols-outlined text-lg">chevron_right</span></button>
            </div>
            <!-- Fullscreen Button (Directive 3) -->
            <button @click="toggleFullscreen" class="w-10 h-10 flex items-center justify-center rounded-md bg-white text-[var(--sys-text-secondary)] border border-[var(--sys-border-strong)] hover:text-[var(--sys-brand-solid)] hover:border-[var(--sys-brand-solid)] shadow-sm transition-all active:scale-90 tooltip" :title="isFullscreen ? 'Thu nhỏ' : 'Toàn màn hình'">
              <span class="material-symbols-outlined text-[20px]">{{ isFullscreen ? 'fullscreen_exit' : 'fullscreen' }}</span>
            </button>
          </div>
        </div>

        <!-- Scrollable Grid Container (Directive 3) -->
        <div :class="['p-4 overflow-x-auto custom-scrollbar', isFullscreen ? 'h-[calc(100vh-120px)]' : 'h-[600px]']">
          <div class="grid grid-cols-7 border-t border-l border-[var(--sys-border-subtle)] rounded-md overflow-hidden min-w-[900px]">
            <!-- Day labels -->
            <div v-for="dayName in ['Thứ 2', 'Thứ 3', 'Thứ 4', 'Thứ 5', 'Thứ 6', 'Thứ 7', 'CN']" :key="dayName" 
              class="text-center py-2 text-[10px] font-bold text-[var(--sys-text-secondary)] bg-[var(--sys-bg-page)] border-r border-b border-[var(--sys-border-subtle)] uppercase tracking-widest">
              {{ dayName }}
            </div>

            <!-- Blank cells -->
            <div v-for="blank in emptyDays" :key="'blank'+blank" class="min-h-[120px] bg-[var(--sys-bg-page)]/20 border-r border-b border-[var(--sys-border-subtle)]/30"></div>

            <!-- Real cells -->
            <div 
              v-for="day in daysInMonth" :key="day" 
              class="min-h-[120px] p-2 bg-white border-r border-b border-[var(--sys-border-subtle)] hover:bg-[var(--sys-bg-hover)] transition-colors relative group/cell"
              @dragover.prevent @dragenter.prevent @drop="onDrop($event, day)"
            >
              <div class="flex justify-between items-center mb-1.5">
                <span class="text-[11px] font-bold text-[var(--sys-text-disabled)] group-hover/cell:opacity-100 group-hover/cell:text-[var(--sys-brand-solid)] transition-all">{{ day }}</span>
              </div>
              
              <div class="space-y-1 custom-scrollbar max-h-[85px] overflow-y-auto pr-0.5 pb-1">
                <div 
                  v-for="interview in getInterviewsByDay(day)" :key="interview.id"
                  draggable="true" @dragstart="onDragStart($event, interview)"
                  @click.stop="openEditModal(interview)"
                  class="p-1.5 rounded-md cursor-pointer transition-all shadow-sm flex flex-col gap-0.5 border border-white/20 hover:brightness-110 active:scale-95"
                  :class="getCalendarEventClass(interview.computedStatus)"
                >
                  <p class="text-[9px] font-bold uppercase tracking-tight opacity-80">{{ interview.time }} • {{ interview.candidate }}</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- List View -->
      <div v-show="currentView === 'list'" class="bg-white rounded-lg border border-[var(--sys-border-subtle)] shadow-sm overflow-hidden motion-safe:animate-fadeIn">
        <div class="px-4 py-3 border-b border-[var(--sys-border-subtle)] flex justify-between items-center bg-[var(--sys-bg-page)]/50 h-14">
          <h4 class="text-[13px] font-bold text-[var(--sys-text-primary)] flex items-center gap-2 m-0 uppercase tracking-widest leading-none">
            <span class="material-symbols-outlined text-[var(--sys-brand-solid)] text-[20px]">overview_key</span> 
            Điều phối & Thẩm định năng lực
          </h4>
          <span class="px-3 py-1 bg-white border border-[var(--sys-border-subtle)] rounded-full text-[10px] font-bold text-[var(--sys-text-secondary)] uppercase tracking-widest">{{ filteredInterviews.length }} Kết quả</span>
        </div>

        <div class="overflow-x-auto custom-scrollbar">
          <table class="w-full text-left border-collapse">
            <thead>
              <tr class="bg-[var(--sys-bg-page)]">
                <th class="px-4 py-2.5 text-[11px] font-bold text-[var(--sys-text-secondary)] border-b border-[var(--sys-border-subtle)] uppercase tracking-widest">Định danh ứng viên</th>
                <th class="px-4 py-2.5 text-[11px] font-bold text-[var(--sys-text-secondary)] border-b border-[var(--sys-border-subtle)] uppercase tracking-widest text-center">Lịch trình</th>
                <th class="px-4 py-2.5 text-[11px] font-bold text-[var(--sys-text-secondary)] border-b border-[var(--sys-border-subtle)] uppercase tracking-widest text-center">Hội đồng phụ trách</th>
                <th class="px-4 py-2.5 text-[11px] font-bold text-[var(--sys-text-secondary)] border-b border-[var(--sys-border-subtle)] uppercase tracking-widest text-center">Trạng thái</th>
                <th class="px-4 py-2.5 text-[11px] font-bold text-[var(--sys-text-secondary)] border-b border-[var(--sys-border-subtle)] uppercase tracking-widest text-right whitespace-nowrap">Thao tác</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-[var(--sys-border-subtle)]">
              <tr v-for="interview in filteredInterviews" :key="interview.id" class="group hover:bg-[var(--sys-bg-hover)] transition-all">
                <td class="px-4 py-3 whitespace-nowrap">
                  <div class="flex flex-col max-w-[200px]">
                    <span class="text-[13px] font-bold text-[var(--sys-text-primary)] group-hover:text-[var(--sys-brand-solid)] transition-colors" :class="{'opacity-40 line-through': interview.status === 'Đã hủy'}">{{ interview.candidate }}</span>
                    <span class="text-[10px] font-bold text-[var(--sys-brand-solid)] mt-0.5 opacity-60 uppercase tracking-tight">CAND-{{ interview.id }}</span>
                  </div>
                </td>
                <td class="px-4 py-3 text-center whitespace-nowrap">
                  <div class="text-[13px] font-semibold text-[var(--sys-text-primary)] opacity-80">
                    {{ interview.date }} <span class="text-[var(--sys-text-disabled)] mx-1">/</span> {{ interview.time }}
                  </div>
                </td>
                <td class="px-4 py-3 text-center whitespace-nowrap">
                  <div class="flex items-center justify-center gap-2">
                    <div class="w-8 h-8 rounded-md bg-[var(--sys-brand-soft)] text-[var(--sys-brand-solid)] flex items-center justify-center text-[10px] font-bold border border-[var(--sys-brand-border)]">
                      {{ getInterviewerInitials(interview.interviewerId) }}
                    </div>
                    <span class="text-[12px] font-bold text-[var(--sys-text-secondary)] hidden xl:block uppercase truncate max-w-[120px]">{{ getInterviewerName(interview.interviewerId) }}</span>
                  </div>
                </td>
                <td class="px-4 py-3 text-center whitespace-nowrap">
                  <span class="px-2 py-0.5 rounded-md text-[10px] font-bold border inline-flex items-center gap-1.5 uppercase tracking-widest transition-all" :class="getStatusBadgeClass(interview.computedStatus)">
                    {{ interview.computedStatus }}
                  </span>
                </td>
                <td class="px-4 py-3 text-right whitespace-nowrap">
                  <div class="flex justify-end gap-1 transition-opacity">
                    <button @click="openEditModal(interview)" class="w-8 h-8 flex items-center justify-center rounded-md text-[var(--sys-text-secondary)] hover:bg-[var(--sys-brand-soft)] hover:text-[var(--sys-brand-solid)] transition-all" title="Chỉnh sửa">
                      <span class="material-symbols-outlined text-[18px]">edit_square</span>
                    </button>
                    <button @click="openEvaluateModal(interview.candidate)" class="w-8 h-8 flex items-center justify-center rounded-md text-[var(--sys-text-secondary)] hover:bg-[var(--sys-success-soft)] hover:text-[var(--sys-success-solid)] transition-all" title="Đánh giá">
                      <span class="material-symbols-outlined text-[18px]">check_circle</span>
                    </button>
                    <button v-if="interview.status !== 'Đã hủy'" @click="handleCancelInterview(interview)" class="w-8 h-8 flex items-center justify-center rounded-md text-[var(--sys-text-secondary)] hover:bg-[var(--sys-danger-soft)] hover:text-[var(--sys-danger-solid)] transition-all" title="Hủy">
                      <span class="material-symbols-outlined text-[18px]">delete</span>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Modals System -->
    <Teleport to="body">
      <!-- Create/Edit Modal (Directive 4: Widen & Grid Form) -->
      <Transition name="modal-fade">
        <div v-if="showModal" class="fixed inset-0 z-[10000] flex items-center justify-center p-4">
          <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" @click="closeModal"></div>
          <div class="relative bg-white w-full max-w-2xl rounded-lg shadow-2xl border border-[var(--sys-border-subtle)] overflow-visible text-left flex flex-col motion-safe:animate-zoomIn">
            <div class="px-6 py-4 border-b border-[var(--sys-border-subtle)] flex items-center justify-between bg-[var(--sys-bg-surface)]">
              <div class="flex items-center gap-4">
                <div class="w-10 h-10 rounded-md bg-[var(--sys-brand-soft)] text-[var(--sys-brand-solid)] flex items-center justify-center border border-[var(--sys-brand-border)]">
                  <span class="material-symbols-outlined">post_add</span>
                </div>
                <div class="bg-transparent text-left">
                  <h3 class="text-[17px] font-bold text-[var(--sys-text-primary)] uppercase tracking-tight m-0">{{ isEditMode ? 'Cập nhật lịch trình' : 'Khởi tạo phỏng vấn mới' }}</h3>
                  <p class="text-[11px] text-[var(--sys-text-secondary)] font-medium mt-0.5">Xác lập thời gian và nhân sự phụ trách thẩm định.</p>
                </div>
              </div>
              <button @click="closeModal" class="w-8 h-8 flex items-center justify-center rounded-md hover:bg-[var(--sys-bg-hover)] transition-all text-[var(--sys-text-secondary)]">
                <span class="material-symbols-outlined text-xl">close</span>
              </button>
            </div>
            
            <div class="p-6 space-y-6">
              <!-- Grid Form (Directive 4) -->
              <div class="grid grid-cols-2 gap-5">
                <div class="col-span-2 space-y-1.5 bg-transparent">
                  <label class="text-[11px] font-bold text-[var(--sys-text-primary)] uppercase tracking-wider ml-1">Định danh ứng viên *</label>
                  <input v-model="form.candidate" @input="handleCandidateTyping" list="candidate-suggestions" type="text" placeholder="Họ và tên hoặc Mã hồ sơ..." class="w-full h-11 px-4 bg-[var(--sys-bg-page)] border border-[var(--sys-border-strong)] rounded-md text-[13px] font-bold text-[var(--sys-text-primary)] outline-none focus:border-[var(--sys-brand-solid)] shadow-sm transition-all">
                  <datalist id="candidate-suggestions">
                    <option v-for="opt in candidateSuggestions" :key="opt.candidate_id" :value="opt.full_name">
                      {{ opt.candidate_code || opt.candidate_id }}
                    </option>
                  </datalist>
                </div>
                
                <div class="space-y-1.5 bg-transparent">
                  <label class="text-[11px] font-bold text-[var(--sys-text-primary)] uppercase tracking-wider ml-1">Ngày phỏng vấn</label>
                  <input v-model="form.date" :min="todayIsoDate" type="date" class="w-full h-11 px-4 bg-[var(--sys-bg-page)] border border-[var(--sys-border-strong)] rounded-md text-[13px] font-bold text-[var(--sys-text-primary)] outline-none focus:border-[var(--sys-brand-solid)] shadow-sm" placeholder="DD/MM/YYYY">
                </div>
                
                <div class="space-y-1.5 bg-transparent">
                  <label class="text-[11px] font-bold text-[var(--sys-text-primary)] uppercase tracking-wider ml-1">Thời điểm khởi động</label>
                  <input v-model="form.time" type="time" class="w-full h-11 px-4 bg-[var(--sys-bg-page)] border border-[var(--sys-border-strong)] rounded-md text-[13px] font-bold text-[var(--sys-text-primary)] outline-none focus:border-[var(--sys-brand-solid)] shadow-sm" placeholder="HH:mm">
                </div>
              </div>

              <div class="space-y-1.5 bg-transparent">
                <label class="text-[11px] font-bold text-[var(--sys-text-primary)] uppercase tracking-wider ml-1">Hội đồng/Chuyên viên phụ trách</label>
                <Dropdown v-model="form.interviewerId" :options="interviewerFormOptions" class="w-full h-11 shadow-sm" />
              </div>

              <!-- Footer Actions (Directive 4: Slim and Right-aligned) -->
              <div class="pt-4 flex justify-end gap-3 border-t border-[var(--sys-border-subtle)] border-dashed">
                <button @click="closeModal" class="h-9 px-6 bg-white border border-[var(--sys-border-strong)] text-[var(--sys-text-secondary)] rounded-md font-bold text-[12px] uppercase tracking-wide hover:bg-[var(--sys-bg-hover)] transition-all">Hủy bỏ</button>
                <button @click="saveSchedule" class="h-9 px-8 bg-[var(--sys-brand-solid)] text-white rounded-md font-bold text-[12px] hover:brightness-110 shadow-lg transition-all uppercase tracking-widest flex items-center justify-center gap-2 active:scale-95">
                  <span class="material-symbols-outlined text-[18px]">verified</span>
                  Xác nhận lưu
                </button>
              </div>
            </div>
          </div>
        </div>
      </Transition>

      <!-- Evaluation Modal (Widen) -->
      <Transition name="modal-fade">
        <div v-if="showEvaluateModal" class="fixed inset-0 z-[10000] flex items-center justify-center p-4">
          <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" @click="showEvaluateModal = false"></div>
          <div class="relative bg-white w-full max-w-2xl rounded-lg shadow-2xl border border-[var(--sys-border-subtle)] overflow-hidden text-left flex flex-col motion-safe:animate-zoomIn">
            <div class="p-6 space-y-6 bg-transparent">
              <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-lg bg-[var(--sys-brand-soft)] text-[var(--sys-brand-solid)] flex items-center justify-center border border-[var(--sys-brand-border)]">
                  <span class="material-symbols-outlined text-2xl">rate_review</span>
                </div>
                <div class="bg-transparent text-left">
                  <h3 class="text-lg font-bold text-[var(--sys-text-primary)] leading-tight uppercase tracking-tight m-0">{{ selectedCandidateToEvaluate }}</h3>
                  <p class="text-[11px] text-[var(--sys-brand-solid)] font-bold uppercase tracking-widest mt-1">Hồ sơ thẩm định năng lực phỏng vấn</p>
                </div>
              </div>
              
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6 bg-transparent pt-2 border-t border-[var(--sys-border-subtle)] border-dashed">
                <div class="space-y-1.5 bg-transparent">
                  <label class="text-[11px] font-bold text-[var(--sys-text-secondary)] uppercase tracking-wider ml-1">Kiến thức chuyên môn (H1)</label>
                  <textarea class="w-full px-4 py-3 bg-[var(--sys-bg-page)] border border-[var(--sys-border-strong)] rounded-md text-[13px] font-medium min-h-[140px] outline-none focus:border-[var(--sys-brand-solid)] shadow-inner resize-none" placeholder="Đánh giá kỹ năng cứng..."></textarea>
                </div>
                <div class="space-y-1.5 bg-transparent">
                  <label class="text-[11px] font-bold text-[var(--sys-text-secondary)] uppercase tracking-wider ml-1">Kỹ năng mềm (H2)</label>
                  <textarea class="w-full px-4 py-3 bg-[var(--sys-bg-page)] border border-[var(--sys-border-strong)] rounded-md text-[13px] font-medium min-h-[140px] outline-none focus:border-[var(--sys-brand-solid)] shadow-inner resize-none" placeholder="Giao tiếp, làm việc nhóm..."></textarea>
                </div>
              </div>
              
              <div class="flex justify-end gap-3 pt-2 bg-transparent">
                <button @click="finishEvaluation" class="h-9 px-6 bg-[var(--sys-danger-solid)] text-white rounded-md font-bold text-[11px] hover:brightness-110 shadow-md transition-all uppercase tracking-widest">Fail / Loại</button>
                <button @click="finishEvaluation" class="h-9 px-8 bg-[var(--sys-success-solid)] text-white rounded-md font-bold text-[11px] hover:brightness-110 shadow-md transition-all uppercase tracking-widest">Pass / Duyệt</button>
              </div>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>

    <!-- Success Toast -->
    <Transition name="toast-slide">
      <div v-if="showToast" class="fixed bottom-8 right-8 z-[11000] flex items-center gap-4 px-6 py-4 bg-white border border-[var(--sys-border-subtle)] rounded-lg shadow-2xl">
        <div class="w-10 h-10 rounded-full bg-[var(--sys-brand-solid)] text-white flex items-center justify-center shrink-0">
          <span class="material-symbols-outlined text-xl">done_all</span>
        </div>
        <div>
          <span class="text-[10px] font-bold text-[var(--sys-brand-solid)] uppercase tracking-widest block mb-0.5">Hệ thống ghi nhận</span>
          <span class="text-[13px] font-bold text-[var(--sys-text-primary)]">{{ toastMessage }}</span>
        </div>
      </div>
    </Transition>
  </div>
</template>

<script setup>
/**
 * HỆ THỐNG LỊCH PHỎNG VẤN (ADMIN) - ENTERPRISE REFINEMENT
 * Tuân thủ 5 Chỉ thị UX/UI SaaS Final:
 * 1. Icon Decor chuẩn w-10 h-10 rounded-md.
 * 2. Lưới dữ liệu mật độ cao.
 * 3. Expandable Calendar (Fullscreen toggle & h-600px grid).
 * 4. Modal mở rộng max-w-2xl & Grid Form 2 cột.
 * 5. Extreme Density: p-4 card padding, py-2.5 table cells.
 */
import { ref, computed, onMounted, onUnmounted } from 'vue';
import Dropdown from '@/components/Dropdown.vue';
import { useConfirm } from '@/composables/useConfirm';
import { BE_API_BASE, getAccessToken } from '@/services/runtimeConfig.js';

const { showAlert, showConfirm } = useConfirm();

// Fullscreen State (Directive 3)
const isFullscreen = ref(false);
const toggleFullscreen = () => { isFullscreen.value = !isFullscreen.value; };

// Helpers
const formatDate = (date) => {
 const d = date.getDate().toString().padStart(2, '0');
 const m = (date.getMonth() + 1).toString().padStart(2, '0');
 const y = date.getFullYear();
 return `${d}/${m}/${y}`;
};

const formatTime = (date) => {
 const h = date.getHours().toString().padStart(2, '0');
 const min = date.getMinutes().toString().padStart(2, '0');
 return `${h}:${min}`;
};

const todayIsoDate = computed(() => {
  const now = new Date()
  const yyyy = now.getFullYear()
  const mm = String(now.getMonth() + 1).padStart(2, '0')
  const dd = String(now.getDate()).padStart(2, '0')
  return `${yyyy}-${mm}-${dd}`
})

const isoDateToDisplay = (value) => {
  const raw = String(value || '').trim()
  if (!raw) return ''
  if (/^\d{2}\/\d{2}\/\d{4}$/.test(raw)) return raw
  const parsed = new Date(raw)
  if (Number.isNaN(parsed.getTime())) return ''
  return formatDate(parsed)
}

const displayDateToIso = (value) => {
  const raw = String(value || '').trim()
  if (!raw) return null
  if (/^\d{4}-\d{2}-\d{2}$/.test(raw)) return raw
  const parts = raw.split('/')
  if (parts.length === 3) {
    const [dd, mm, yyyy] = parts
    if (dd && mm && yyyy) {
      return `${yyyy.padStart(4, '0')}-${mm.padStart(2, '0')}-${dd.padStart(2, '0')}`
    }
  }
  const parsed = new Date(raw)
  if (Number.isNaN(parsed.getTime())) return null
  const yyyy = parsed.getFullYear()
  const mm = String(parsed.getMonth() + 1).padStart(2, '0')
  const dd = String(parsed.getDate()).padStart(2, '0')
  return `${yyyy}-${mm}-${dd}`
}

const normalizeTime = (value) => {
  const raw = String(value || '').trim()
  if (!raw) return '00:00:00'
  if (/^\d{2}:\d{2}:\d{2}$/.test(raw)) return raw
  if (/^\d{2}:\d{2}$/.test(raw)) return `${raw}:00`
  return '00:00:00'
}

const parseDateTime = (dateStr, timeStr) => {
 const normalizedDate = displayDateToIso(dateStr)
 const normalizedTime = normalizeTime(timeStr).slice(0, 8)
 if (!normalizedDate) return new Date()
 const parsed = new Date(`${normalizedDate}T${normalizedTime}`)
 return Number.isNaN(parsed.getTime()) ? new Date() : parsed
};

// State
const currentTime = ref(new Date());
let ticker = null;
onMounted(() => { ticker = setInterval(() => { currentTime.value = new Date(); }, 30000); });
onUnmounted(() => { if (ticker) clearInterval(ticker); });

const currentDate = ref(new Date()); 
const currentMonth = computed(() => currentDate.value.getMonth());
const currentYear = computed(() => currentDate.value.getFullYear());
const daysInMonth = computed(() => new Date(currentYear.value, currentMonth.value + 1, 0).getDate());
const emptyDays = computed(() => {
 let f = new Date(currentYear.value, currentMonth.value, 1).getDay();
 return f === 0 ? 6 : f - 1;
});

const prevMonth = () => { currentDate.value = new Date(currentYear.value, currentMonth.value - 1, 1); };
const nextMonth = () => { currentDate.value = new Date(currentYear.value, currentMonth.value + 1, 1); };

const currentView = ref('calendar');
const filterStatus = ref('');
const showModal = ref(false);
const showEvaluateModal = ref(false);
const showToast = ref(false);
const toastMessage = ref('');
const isEditMode = ref(false);
const selectedCandidateToEvaluate = ref('');
const editingId = ref(null);

const form = ref({ candidate: '', date: '', time: '', interviewerId: '', status: 'Sắp diễn ra' });
const candidateSuggestions = ref([])

const getToken = () => getAccessToken()
const isAuthenticated = () => Boolean(getToken())

const apiRequest = async (path, { method = 'GET', body } = {}) => {
  const token = getToken()
  if (!token) throw new Error('Missing auth token')
  const response = await fetch(`${BE_API_BASE}${path}`, {
    method,
    headers: {
      Authorization: `Bearer ${token}`,
      'Content-Type': 'application/json',
    },
    body: body ? JSON.stringify(body) : undefined,
  })
  const payload = await response.json().catch(() => ({}))
  if (!response.ok || payload?.success === false) {
    throw new Error(payload?.message || `Request failed: ${response.status}`)
  }
  return payload?.data
}

const backendStatusToUi = (status) => {
  const key = String(status || '').toUpperCase()
  if (key === 'COMPLETED') return 'Đã xong'
  if (key === 'CANCELED') return 'Đã hủy'
  return 'Sắp diễn ra'
}

const uiStatusToBackend = (status) => {
  const key = String(status || '').trim()
  if (key === 'Đã xong') return 'COMPLETED'
  if (key === 'Đã hủy') return 'CANCELED'
  return 'SCHEDULED'
}

const mapInterviewFromBackend = (item) => ({
  id: Number(item.interview_id),
  backendInterviewId: Number(item.interview_id),
  candidate: item.candidate_name || 'Ứng viên',
  candidateId: Number(item.candidate_id) || null,
  date: isoDateToDisplay(item.interview_date),
  time: String(item.interview_time || '00:00').slice(0, 5),
  interviewerId: String(item.interviewer_id || item.department_manager_id || ''),
  status: backendStatusToUi(item.status),
  position: item.position_name || '',
})

const interviewList = ref([])

const refreshInterviews = async () => {
  if (!isAuthenticated()) {
    interviewList.value = []
    return
  }
  const data = await apiRequest('/interviews?page=1&per_page=500')
  const items = Array.isArray(data) ? data : []
  interviewList.value = items.map(mapInterviewFromBackend)
}

const danhSachNhanSu = ref([]);
const FALLBACK_TRUONG_PHONG = [
 { id: 'NV008', name: 'Trần Thanh Tâm', role: 'Trưởng phòng Kỹ thuật Cloud' },
 { id: 'NV003', name: 'Lê Thị Thu', role: 'Trưởng phòng Khối Kinh doanh' },
 { id: 'NV004', name: 'Trần Lan Anh', role: 'Trưởng phòng Marketing & PR' },
 { id: 'NV001', name: 'Lê Quản Trị', role: 'Trưởng phòng Quản trị Nhân sự' },
 { id: 'NV006', name: 'Nguyễn Bích Diệp', role: 'Trưởng phòng Tài chính - Kế toán' }
];

const normalizeManagerName = (value = '') => (
 value
  .toLowerCase()
  .replace(/\s*\(.*?\)\s*/g, ' ')
  .replace(/\s+/g, ' ')
  .trim()
);

const loadDepartmentManagers = async () => {
 try {
  if (!isAuthenticated()) {
   danhSachNhanSu.value = FALLBACK_TRUONG_PHONG;
   return;
  }
  const employeesData = await apiRequest('/employees?page=1&per_page=500');
  const employees = Array.isArray(employeesData) ? employeesData : [];
  const mappedManagers = employees
   .filter((emp) => normalizeManagerName(emp?.position_name || '').includes('truong phong'))
   .map((emp) => ({
    id: String(emp?.employee_id || ''),
    name: emp?.full_name || 'Trưởng phòng',
    role: emp?.position_name || 'Trưởng phòng'
   }))
   .filter((item) => item.id);

  const seen = new Set();
  const uniqueManagers = mappedManagers.filter((item) => {
   if (seen.has(item.id)) return false;
   seen.add(item.id);
   return true;
  });

  danhSachNhanSu.value = uniqueManagers.length ? uniqueManagers : FALLBACK_TRUONG_PHONG;
 } catch (error) {
  danhSachNhanSu.value = FALLBACK_TRUONG_PHONG;
 }
};

onMounted(async () => {
  await loadDepartmentManagers();
  await refreshInterviews();
});

const statusOptions = [
 { label: 'Toàn bộ trạng thái', value: '' },
 { label: 'Sắp diễn ra', value: 'Sắp diễn ra' },
 { label: 'Đã hoàn tất', value: 'Đã xong' },
 { label: 'Đã hủy bỏ', value: 'Đã hủy' }
];

const interviewerFormOptions = computed(() => [
 { label: 'Chọn nhân sự phụ trách phỏng vấn...', value: '' },
 ...danhSachNhanSu.value.map(ns => ({ label: `${ns.name} (${ns.role})`, value: ns.id }))
]);

// Computed Logic
const liveInterviews = computed(() => {
 return interviewList.value.map(i => {
 let s = i.status;
 if (s === 'Sắp diễn ra' && parseDateTime(i.date, i.time) < currentTime.value) s = 'Quá hạn';
 return { ...i, computedStatus: s };
 });
});

const filteredInterviews = computed(() => {
 return liveInterviews.value.filter(item => filterStatus.value ? item.status === filterStatus.value : true);
});

// Handlers
const getInterviewsByDay = (day) => {
 const dStr = `${day.toString().padStart(2, '0')}/${(currentMonth.value + 1).toString().padStart(2, '0')}/${currentYear.value}`;
 return liveInterviews.value.filter(i => i.date === dStr);
};

const getInterviewerName = (id) => danhSachNhanSu.value.find(p => p.id === id)?.name || 'N/A';
const getInterviewerInitials = (id) => getInterviewerName(id).split(' ').map(n => n[0]).join('').toUpperCase();

const isPastInterviewDateTime = (interviewDate, interviewTime) => {
  const normalizedDate = displayDateToIso(interviewDate)
  if (!normalizedDate) return true
  const timeRaw = normalizeTime(interviewTime).slice(0, 8)
  const interviewAt = new Date(`${normalizedDate}T${timeRaw}`)
  if (Number.isNaN(interviewAt.getTime())) return true
  return interviewAt.getTime() < Date.now() - 60000
}

const getStatusBadgeClass = (s) => {
 switch(s) {
 case 'Quá hạn': return 'bg-[var(--sys-danger-soft)] text-[var(--sys-danger-text)] border-[var(--sys-danger-border)]';
 case 'Sắp diễn ra': return 'bg-[var(--sys-brand-soft)] text-[var(--sys-brand-solid)] border-[var(--sys-brand-border)]';
 case 'Đã xong': return 'bg-[var(--sys-success-soft)] text-[var(--sys-success-text)] border-[var(--sys-success-border)]';
 default: return 'bg-[var(--sys-bg-page)] text-[var(--sys-text-secondary)] border-[var(--sys-border-subtle)]';
 }
};

const getCalendarEventClass = (s) => {
 switch(s) {
 case 'Quá hạn': return 'bg-[var(--sys-danger-solid)] text-white';
 case 'Sắp diễn ra': return 'bg-[var(--sys-brand-solid)] text-white shadow-brand';
 case 'Đã xong': return 'bg-[var(--sys-success-solid)] text-white';
 default: return 'bg-[var(--sys-bg-hover)] text-[var(--sys-text-secondary)] border border-[var(--sys-border-subtle)]';
 }
};

const onDragStart = (e, i) => e.dataTransfer.setData('id', i.id);
const onDrop = async (e, day) => {
 const id = parseInt(e.dataTransfer.getData('id'));
 const target = interviewList.value.find(item => item.id === id);
 if (target) {
 const nDay = `${day.toString().padStart(2, '0')}/${(currentMonth.value + 1).toString().padStart(2, '0')}/${currentYear.value}`;
 const nDayIso = displayDateToIso(nDay)
 if (!nDayIso || new Date(`${nDayIso}T00:00:00`).getTime() < new Date(`${todayIsoDate.value}T00:00:00`).getTime()) {
   await showAlert('Ngày không hợp lệ', 'Không thể dời lịch phỏng vấn về ngày trong quá khứ.')
   return
 }
 target.date = nDay;
 if (isAuthenticated() && target.backendInterviewId) {
   try {
     await apiRequest(`/interviews/${target.backendInterviewId}`, {
       method: 'PATCH',
       body: { interview_date: nDayIso },
     });
   } catch (error) {
     await showAlert('LỖI', error?.message || 'Không thể dời lịch trên backend.');
   }
 }
 triggerToast(`ĐÃ DỜI LỊCH PHỎNG VẤN SANG NGÀY ${day}`);
 }
};

const openCreateModal = () => {
  isEditMode.value = false
  form.value = { candidate: '', date: todayIsoDate.value, time: '09:00', interviewerId: '', status: 'Sắp diễn ra' }
  candidateSuggestions.value = []
  showModal.value = true
};
const openEditModal = (i) => {
  isEditMode.value = true
  editingId.value = i.id
  form.value = {
    ...i,
    date: displayDateToIso(i.date) || todayIsoDate.value,
    time: String(i.time || '09:00').slice(0, 5),
  }
  candidateSuggestions.value = []
  showModal.value = true
};
const closeModal = () => showModal.value = false;

const searchCandidates = async (keyword) => {
  const q = String(keyword || '').trim()
  if (!q) return []
  const items = await apiRequest(`/recruitment-candidates?q=${encodeURIComponent(q)}&page=1&per_page=20`)
  return Array.isArray(items) ? items : []
}

const handleCandidateTyping = async () => {
  try {
    candidateSuggestions.value = await searchCandidates(form.value.candidate)
  } catch {
    candidateSuggestions.value = []
  }
}

const resolveCandidateIdByName = async (name) => {
  const keyword = String(name || '').trim()
  if (!keyword) return null
  const list = await searchCandidates(keyword)
  if (!list.length) return null
  const exact = list.find((item) => String(item?.full_name || '').trim().toLowerCase() === keyword.toLowerCase())
  const picked = exact || list[0]
  return picked?.candidate_id ? Number(picked.candidate_id) : null
}

const saveSchedule = async () => {
 if (!isAuthenticated()) {
  await showAlert('THIẾU PHIÊN ĐĂNG NHẬP', 'Vui lòng đăng nhập lại để thao tác lịch phỏng vấn.');
  return;
 }
 const interviewDate = displayDateToIso(form.value.date);
 if (!interviewDate) {
  await showAlert('THIẾU DỮ LIỆU', 'Ngày phỏng vấn không hợp lệ.');
  return;
 }
 const interviewTime = normalizeTime(form.value.time).slice(0, 8);
 if (isPastInterviewDateTime(interviewDate, interviewTime)) {
  await showAlert('LỖI THỜI GIAN', 'Không thể đặt lịch phỏng vấn ở quá khứ.');
  return;
 }
 const interviewerId = Number(form.value.interviewerId) || null;

 try {
  if (isEditMode.value) {
   const target = interviewList.value.find(i => i.id === editingId.value);
   if (!target?.backendInterviewId) throw new Error('Không tìm thấy lịch backend để cập nhật.');
   await apiRequest(`/interviews/${target.backendInterviewId}`, {
    method: 'PATCH',
    body: {
      interview_date: interviewDate,
      interview_time: interviewTime,
      interviewer_id: interviewerId,
      status: uiStatusToBackend(form.value.status),
    },
   });
   triggerToast('ĐÃ CẬP NHẬT LỊCH TRÌNH');
  } else {
   const candidateId = await resolveCandidateIdByName(form.value.candidate);
   if (!candidateId) throw new Error('Không tìm thấy ứng viên theo tên đã nhập.');
   await apiRequest('/interviews', {
    method: 'POST',
    body: {
      candidate_id: candidateId,
      interview_date: interviewDate,
      interview_time: interviewTime,
      interviewer_id: interviewerId,
      status: uiStatusToBackend(form.value.status),
      interview_mode: 'ONLINE',
    },
   });
   triggerToast('ĐÃ KHỞI TẠO LỊCH MỚI');
  }
  await refreshInterviews();
  closeModal();
 } catch (error) {
  await showAlert('LỖI', error?.message || 'Không thể lưu lịch phỏng vấn.');
 }
};

const openEvaluateModal = (name) => { selectedCandidateToEvaluate.value = name; showEvaluateModal.value = true; };
const finishEvaluation = () => { showEvaluateModal.value = false; triggerToast('ĐÃ LƯU KẾT QUẢ THẨM ĐỊNH'); };
const handleCancelInterview = async (i) => {
 if (!(await showConfirm('Xác thực', 'Hủy bỏ lịch trình này?'))) return;
 i.status = 'Đã hủy';
 if (isAuthenticated() && i.backendInterviewId) {
  try {
   await apiRequest(`/interviews/${i.backendInterviewId}`, {
    method: 'PATCH',
    body: { status: 'CANCELED' },
   });
  } catch (error) {
   await showAlert('LỖI', error?.message || 'Không thể cập nhật trạng thái lịch phỏng vấn.');
  }
 }
 triggerToast('ĐÃ ĐÌNH CHỈ LỊCH PHỎNG VẤN');
};

const triggerToast = (m) => { toastMessage.value = m; showToast.value = true; setTimeout(() => showToast.value = false, 3000); };
</script>

<style scoped>
.shadow-brand { box-shadow: 0 4px 12px var(--sys-brand-soft); }

.custom-scrollbar::-webkit-scrollbar { width: 5px; height: 5px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: var(--sys-border-subtle); border-radius: 10px; }

@keyframes zoomIn { from { opacity: 0; transform: scale(0.95); } to { opacity: 1; transform: scale(1); } }
.animate-zoomIn { animation: zoomIn 0.3s cubic-bezier(0.16, 1, 0.3, 1) forwards; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
.animate-fadeIn { animation: fadeIn 0.4s ease forwards; }

.modal-fade-enter-active, .modal-fade-leave-active { transition: opacity 0.3s ease; }
.modal-fade-enter-from, .modal-fade-leave-to { opacity: 0; }

.toast-slide-enter-active, .toast-slide-leave-active { transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1); }
.toast-slide-enter-from, .toast-slide-leave-to { opacity: 0; transform: translateX(100%); }

.tooltip:hover::after {
  content: attr(title);
  position: absolute;
  top: -35px;
  right: 0;
  background: var(--sys-text-primary);
  color: white;
  padding: 4px 10px;
  border-radius: 4px;
  font-size: 11px;
  white-space: nowrap;
  z-index: 100;
}
</style>
