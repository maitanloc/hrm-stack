<template>
  <div class="dashboard">

    <!-- ══════════════════════════════════════════
         PAGE HEADER
    ══════════════════════════════════════════════ -->
    <div class="page-header">
      <div class="page-header-left">
        <h1 class="page-title">Bảng Điều Khiển Chiến Lược</h1>
        <p class="page-subtitle">
          <span class="material-symbols-rounded" style="font-size:15px;vertical-align:-3px;margin-right:4px">wb_sunny</span>
          Chào buổi sáng, Dữ liệu được cập nhật mới nhất. 
        </p>
      </div>
      <div class="page-header-actions">
        <!-- Date filter button using GD_DateFilter component -->
        <GD_DateFilter v-model="selectedDateRange" />
        <button class="btn-export">
          <span class="material-symbols-rounded">download</span>
          Xuất báo cáo
        </button>
      </div>
    </div>

    <!-- ══════════════════════════════════════════
         KPI CARDS
    ══════════════════════════════════════════════ -->
    <div class="kpi-grid">
      <div
        v-for="(card, index) in kpiCards"
        :key="card.id"
        class="kpi-card animate-chart"
        :style="{ animationDelay: (index * 100) + 'ms' }"
        @click="router.push(card.route)"
      >
        <!-- Header: Icon + Badge -->
        <div class="kpi-card-header">
          <div class="kpi-icon" :class="card.iconClass">
            <span class="material-symbols-rounded">{{ card.icon }}</span>
          </div>
          <span class="kpi-badge" :class="card.badgeClass">
            <span class="material-symbols-rounded" style="font-size:11px">{{ card.badgeTrend === 'up' ? 'trending_up' : 'trending_down' }}</span>
            {{ card.badge }}
          </span>
        </div>

        <!-- Label -->
        <p class="kpi-label">{{ card.label }}</p>

        <!-- Value -->
        <h3 class="kpi-value">{{ card.value }}</h3>

        <!-- Footer -->
        <div class="kpi-footer">
          <!-- Sparkline bars -->
          <template v-if="card.footerType === 'sparkline'">
            <div class="kpi-sparkline">
              <div
                v-for="(h, i) in card.sparkline"
                :key="i"
                class="bar"
                :class="[
                  card.sparklineDanger ? 'bar--danger' : '',
                  i === card.sparkline.length - 1
                    ? (card.sparklineDanger ? 'bar--active-danger' : 'bar--active')
                    : ''
                ]"
                :style="`height:${h}%`"
              ></div>
            </div>
          </template>
          <!-- Progress bar -->
          <template v-else-if="card.footerType === 'progress'">
            <div class="kpi-progress-bar">
              <div
                class="kpi-progress-fill"
                :class="card.progressClass"
                :style="`width:${card.progress}%`"
              ></div>
            </div>
          </template>
          <span class="kpi-meta">{{ card.meta }}</span>
        </div>
      </div>
    </div>

    <!-- ══════════════════════════════════════════
         CHARTS ROW
    ══════════════════════════════════════════════ -->
    <div class="charts-grid">

      <!-- Bar Chart -->
      <div class="chart-card chart-card--wide animate-chart cursor-pointer hover:border-blue-400/50 transition-all" style="animation-delay: 200ms;" @click="router.push('/giamdoc/biendong')">
        <div class="chart-card-header">
          <div>
            <h4 class="chart-title">Xu hướng tăng trưởng nhân sự</h4>
            <p class="chart-subtitle">6 tháng gần nhất</p>
          </div>
          <div class="chart-legend">
            <span class="legend-item">
              <span class="legend-dot legend-dot--brand"></span>Hiện tại
            </span>
            <span class="legend-item">
              <span class="legend-dot legend-dot--amber"></span>Mục tiêu
            </span>
          </div>
        </div>

        <div class="bar-chart">
          <!-- Background Grid Lines -->
          <div class="absolute inset-0 flex flex-col justify-between pb-[28px] pr-[44px] pointer-events-none z-0">
            <div v-for="(lab, i) in barChartYLabels" :key="'grid-'+i" class="w-full border-t border-dashed border-slate-200/80" :class="i === barChartYLabels.length - 1 ? 'border-solid border-slate-300' : ''"></div>
          </div>

          <div class="bar-chart-cols relative z-10">
            <div class="relative flex items-end justify-center h-full w-full group hover:z-[60] z-20 gap-1.5 sm:gap-2.5" v-for="(col, i) in dynamicBarChart" :key="i">
              
              <!-- Phantom Tooltip Top Alignment -->
              <div class="absolute hidden group-hover:flex flex-col items-center z-50 pointer-events-none w-max"
                   :style="`bottom: calc(${Math.max(col.currentH, col.targetH)}% + 5px);`">
                  <div class="bg-slate-800 text-white rounded-md text-[11px] px-3 py-2 shadow-xl whitespace-nowrap flex flex-col items-center gap-0.5 relative">
                   <span class="font-bold text-amber-400">Mục tiêu: {{ col.target }}</span>
                   <span class="font-bold text-blue-300 flex items-center gap-1.5">
                     Hiện tại: {{ col.current }}
                     <span v-if="col.current >= col.target" class="w-1.5 h-1.5 rounded-full bg-green-400 animate-pulse drop-shadow-[0_0_3px_#4ade80]"></span>
                   </span>
                 </div>
                 <div class="w-2.5 h-2.5 bg-slate-800 rotate-45 -mt-1.5"></div>
              </div>

              <!-- Target Col (Amber) -->
              <div class="w-5 sm:w-8 md:w-10 rounded-t-md transition-all duration-300 group-hover:opacity-90 bg-gradient-to-t from-amber-500 to-amber-300 shadow-sm shadow-amber-500/20 group-hover:-translate-y-1 bar-pillar-modern"
                   :style="`height: ${col.targetH}%; animation-delay: ${200 + (i * 100)}ms`"
              >
              </div>

              <!-- Current Col (Blue) -->
              <div class="w-5 sm:w-8 md:w-10 rounded-t-md transition-all duration-300 group-hover:opacity-90 bg-gradient-to-t from-blue-600 to-blue-400 shadow-sm shadow-blue-500/20 relative group-hover:-translate-y-1 bar-pillar-modern"
                   :class="col.active ? 'opacity-100' : 'opacity-85'"
                   :style="`height: ${col.currentH}%; animation-delay: ${300 + (i * 100)}ms`">
                   <!-- SPECIAL FEATURE: Vượt mục tiêu hiển thị ngôi sao và hiệu ứng pulse viền sáng cực đẹp đặc quyền của Trang chủ Giám đốc -->
                   <div v-if="col.current >= col.target" class="absolute -top-6 left-1/2 -translate-x-1/2 text-[14px] text-green-500 font-extrabold opacity-0 group-hover:opacity-100 transition-all duration-[400ms] drop-shadow-[0_0_5px_rgba(74,222,128,0.8)] group-hover:-translate-y-1">★</div>
                   <div v-if="col.current >= col.target" class="absolute inset-0 bg-gradient-to-t from-transparent to-white/30 rounded-t-md animate-pulse pointer-events-none"></div>
              </div>

              <span class="bar-label" :class="col.active ? 'bar-label--active' : ''">{{ col.label }}</span>
            </div>
          </div>
          <!-- Y-axis lines -->
          <div class="y-axis-lines">
            <span class="y-label" v-for="(lab, i) in barChartYLabels" :key="i">{{ lab }}</span>
          </div>
        </div>
      </div>
    </div>

    <!-- ══════════════════════════════════════════
         BOTTOM ROW
    ══════════════════════════════════════════════ -->
    <div class="bottom-grid">

      <!-- Approval List – dữ liệu từ pendingApprovals -->
      <div class="list-card list-card--wide animate-chart" style="animation-delay: 400ms;">
        <div class="list-card-header">
          <h4 class="chart-title">Yêu cầu chờ phê duyệt</h4>
          <span class="badge-urgent">
            <span class="material-symbols-rounded" style="font-size:12px">warning</span>
            {{ urgentPendingCount < 10 ? '0' + urgentPendingCount : urgentPendingCount }} khẩn cấp
          </span>
        </div>

        <div class="tt-approval-list px-5 mb-4 space-y-3">
          <div
            v-for="(item, index) in pendingApprovals"
            :key="item.id"
            class="tt-approval-item group"
            :class="{ 'tt-approval-item--urgent': item.urgent }"
            :style="{ animationDelay: (index * 60) + 'ms' }"
            @click="openDetailModal(item)"
          >
            <!-- Part 1: Persona -->
            <div class="flex items-center gap-4 flex-1 min-w-0">
              <div class="tt-avatar shadow-sm group-hover:scale-105 transition-transform" :class="[item.avatarBg, item.avatarColor]">
                {{ item.initials }}
                <span v-if="item.urgent" class="tt-urgent-dot"></span>
              </div>
              <div class="flex flex-col min-w-0">
                <div class="flex items-center gap-2 mb-0.5">
                  <span class="tt-approval-name truncate">{{ item.name }}</span>
                  <span v-if="item.urgent" class="tt-urgent-badge">KHẨN</span>
                </div>
                <span class="tt-approval-dept truncate opacity-80">{{ item.dept }}</span>
              </div>
            </div>

            <!-- Part 2: Request Detail -->
            <div class="hidden md:flex flex-col flex-[1.5] px-4 border-l border-r border-slate-100 dark:border-white/5 mx-2">
              <div class="flex items-center gap-2 mb-1">
                <span class="tt-approval-type shrink-0" :class="[item.typeBg, item.typeColor]">
                  <span class="material-symbols-rounded text-[12px] font-fill">{{ item.typeIcon }}</span>
                  {{ item.type }}
                </span>
                <span class="tt-approval-time shrink-0">
                  <span class="material-symbols-rounded text-[12px]">calendar_month</span>
                  {{ item.time.split('T')[0] }}
                </span>
              </div>
              <p class="tt-approval-title line-clamp-1 font-bold text-[13px] text-slate-600 dark:text-slate-300">
                {{ item.title }}
              </p>
            </div>

            <!-- Part 3: Actions -->
            <div class="tt-approval-actions">
              <button class="tt-btn-reject" title="Từ chối yêu cầu" @click.stop="openRejectModal(item)">
                BÁC BỎ
              </button>
              <button class="tt-btn-approve" title="Phê duyệt ngay" @click.stop="openApproveModal(item)">
                <span class="material-symbols-rounded text-[14px] font-fill">verified</span>
                PHÊ DUYỆT
              </button>
            </div>
          </div>
        </div>

        <div class="list-card-footer-container">
          <button class="btn-view-all" @click="router.push('/giamdoc/thongbao')">
            <span>Xem toàn bộ {{ pendingApprovals.length }} yêu cầu</span>
            <span class="material-symbols-rounded">arrow_forward</span>
          </button>
        </div>
      </div>

      <!-- Events Timeline -->
      <div class="events-card">
        <div class="events-glow"></div>
        <div class="events-header">
          <span class="material-symbols-rounded events-icon">calendar_month</span>
          <h4 class="events-title">Sự kiện quan trọng</h4>
        </div>

        <!-- Timeline events – dữ liệu từ timelineEvents -->
        <div class="timeline">
          <div class="timeline-line"></div>
          <div
            v-for="ev in timelineEvents"
            :key="ev.id"
            class="timeline-item"
            :class="ev.active ? 'timeline-item--active' : ''"
          >
            <div class="timeline-dot" :class="ev.active ? 'timeline-dot--active' : ''"></div>
            <div class="timeline-content">
              <p class="timeline-time" :class="ev.active ? 'active-time' : ''">{{ ev.time }}</p>
              <h5 class="timeline-title">{{ ev.title }}</h5>
              <p class="timeline-place">
                <span class="material-symbols-rounded" style="font-size:13px">{{ ev.placeIcon }}</span>
                {{ ev.place }}
              </p>
            </div>
          </div>
        </div>

        <!-- Reminder -->
        <div class="reminder-card">
          <p class="reminder-label">
            <span class="material-symbols-rounded" style="font-size:14px;font-variation-settings:'FILL' 1">sticky_note_2</span>
            Lời nhắc của bạn
          </p>
          <p class="reminder-text">{{ reminderText }}</p>
          <button class="reminder-edit">
            <span class="material-symbols-rounded" style="font-size:14px">edit</span>
            Chỉnh sửa
          </button>
        </div>
      </div>

    </div>

    <!-- Detail Modal (Yêu cầu) -->
    <!-- Unified Approval Modal -->
    <Teleport to="body">
      <Transition name="modal-fade">
        <div
          v-if="showMainModal && selectedApproval"
          class="fixed inset-0 z-[10001] flex items-center justify-center p-4"
        >
          <div class="fixed inset-0 bg-black/40 backdrop-blur-sm" @click="closeMainModal"></div>

          <div class="relative max-w-xl w-full bg-[var(--sys-bg-surface)] rounded-xl shadow-2xl p-6 overflow-hidden flex flex-col text-left animate-in fade-in zoom-in duration-300">
            <!-- Header -->
            <div class="flex items-center gap-4 mb-6">
              <div 
                class="w-12 h-12 rounded-lg flex items-center justify-center border transition-colors duration-300"
                :class="{
                  'bg-[var(--sys-brand-soft)] border-[var(--sys-brand-border)]': modalMode === 'approve',
                  'bg-[var(--sys-danger-soft)] border-[var(--sys-danger-border)]': modalMode === 'reject',
                  'bg-[var(--sys-bg-page)] border-[var(--sys-border-subtle)]': modalMode === 'detail'
                }"
              >
                <span v-if="modalMode === 'approve'" class="material-symbols-outlined text-[26px] font-bold text-[var(--sys-brand-solid)]">task_alt</span>
                <span v-else-if="modalMode === 'reject'" class="material-symbols-outlined text-[26px] font-bold text-[var(--sys-danger-solid)]">rule</span>
                <span v-else class="material-symbols-outlined text-[26px] font-bold text-[var(--sys-text-secondary)]">visibility</span>
              </div>
              <div class="flex-1">
                <h3 class="text-[17px] font-extrabold text-[var(--sys-text-primary)] m-0 tracking-tight leading-none uppercase">
                  <span v-if="modalMode === 'approve'">Xác nhận Phê duyệt</span>
                  <span v-else-if="modalMode === 'reject'">Thẩm định bác bỏ</span>
                  <span v-else>Chi tiết đơn yêu cầu</span>
                </h3>
                <p class="text-[12px] text-[var(--sys-text-secondary)] font-bold mt-1 tracking-wide opacity-80">
                  {{ selectedApproval.name }} — {{ selectedApproval.title }}
                </p>
              </div>
              <button
                @click="closeMainModal"
                class="w-8 h-8 flex items-center justify-center rounded-full hover:bg-[var(--sys-bg-hover)] text-[var(--sys-text-secondary)] transition-all bg-[var(--sys-bg-page)] border border-[var(--sys-border-subtle)]"
              >
                <span class="material-symbols-outlined text-[18px]">close</span>
              </button>
            </div>

            <!-- Content Grid (Always shown) -->
            <div class="grid grid-cols-1 gap-2.5 bg-[var(--sys-bg-surface)] p-5 rounded-xl border border-[var(--sys-border-strong)] mb-4 shadow-sm">
              <div class="grid grid-cols-[130px_1fr] items-center gap-2">
                <span class="text-[10px] font-extrabold text-[var(--sys-text-disabled)] uppercase tracking-widest leading-none">MÃ ĐƠN</span>
                <span class="text-[13px] font-mono font-bold text-[var(--sys-brand-solid)] leading-tight">{{ selectedApproval.requestCode }}</span>
              </div>
              <div class="grid grid-cols-[130px_1fr] items-center gap-2">
                <span class="text-[10px] font-extrabold text-[var(--sys-text-disabled)] uppercase tracking-widest leading-none">LOẠI YÊU CẦU</span>
                <span class="text-[13px] font-bold text-[var(--sys-text-primary)] leading-tight">{{ selectedApproval.type || 'Khác' }}</span>
              </div>
              <div class="grid grid-cols-[130px_1fr] items-center gap-2">
                <span class="text-[10px] font-extrabold text-[var(--sys-text-disabled)] uppercase tracking-widest leading-none">THỜI GIAN NGHỈ</span>
                <span class="text-[13px] font-bold text-[var(--sys-text-primary)] leading-tight">
                   {{ selectedApproval.startDate }} <span class="mx-1 opacity-40">→</span> {{ selectedApproval.endDate }}
                </span>
              </div>
              <div class="grid grid-cols-[130px_1fr] items-center gap-2">
                <span class="text-[10px] font-extrabold text-[var(--sys-text-disabled)] uppercase tracking-widest leading-none">TỔNG THỜI GIAN</span>
                <span class="text-[13px] font-bold text-orange-600 leading-tight">{{ selectedApproval.totalDays }} ngày</span>
              </div>
              <div class="grid grid-cols-[130px_1fr] items-center gap-2">
                <span class="text-[10px] font-extrabold text-[var(--sys-text-disabled)] uppercase tracking-widest leading-none">NGƯỜI YÊU CẦU</span>
                <span class="text-[13px] font-bold text-[var(--sys-text-primary)] leading-tight">{{ selectedApproval.dept }} ({{ selectedApproval.name }})</span>
              </div>
              <div class="grid grid-cols-[130px_1fr] items-center gap-2">
                <span class="text-[10px] font-extrabold text-[var(--sys-text-disabled)] uppercase tracking-widest leading-none">NGÀY GỬI</span>
                <span class="text-[13px] font-bold text-[var(--sys-text-primary)] leading-tight">{{ selectedApproval.requestDate }}</span>
              </div>
            </div>

            <!-- Reason Box (Always shown) -->
            <div class="bg-[var(--sys-bg-page)] p-5 rounded-xl border border-[var(--sys-border-subtle)] mb-5 shadow-inner text-left">
              <p class="text-[10px] font-black text-[var(--sys-text-disabled)] uppercase tracking-widest leading-none mb-2.5">LÝ DO CHI TIẾT</p>
              <p class="text-[13px] text-[var(--sys-text-primary)] leading-relaxed font-bold italic opacity-90">
                "{{ selectedApproval.reasonText || 'Không có ghi chú thêm.' }}"
              </p>
            </div>

            <!-- Rejection Input Section -->
            <div v-if="modalMode === 'reject'" class="space-y-1.5 mb-6">
              <label class="text-[10px] font-black text-[var(--sys-text-primary)] uppercase tracking-widest ml-1 opacity-60">NỘI DUNG PHẢN HỒI THẨM ĐỊNH *</label>
              <textarea
                v-model="rejectReason"
                rows="3"
                placeholder="Xác định nguyên nhân bác bỏ hồ sơ chi tiết..."
                class="w-full px-4 py-3 bg-[var(--sys-bg-page)] border border-[var(--sys-border-strong)] rounded text-[13px] font-bold text-[var(--sys-text-primary)] outline-none transition-all resize-none shadow-inner placeholder:font-normal placeholder:italic placeholder:opacity-40 focus:ring-2 focus:ring-[var(--sys-danger-soft)]"
              ></textarea>
              <p class="text-[9px] font-bold text-[var(--sys-danger-text)] uppercase tracking-widest opacity-60 italic">
                * Thông tin này sẽ được gửi trực tiếp đến hộp thư của nhân sự.
              </p>
            </div>

            <!-- Approval Confirm Text -->
            <p v-if="modalMode === 'approve'" class="text-[13px] text-[var(--sys-text-secondary)] border-l-4 border-[var(--sys-brand-solid)] pl-3 py-1 mb-6 font-medium text-left">
              Bạn có chắc chắn muốn <span class="font-extrabold text-[var(--sys-brand-solid)]">phê duyệt</span> yêu cầu này không?
            </p>

            <!-- Modal Footer Actions -->
            <div class="flex justify-end gap-3 mt-auto pt-4 border-t border-[var(--sys-border-subtle)] font-bold">
              <!-- Mode: DETAIL -->
              <template v-if="modalMode === 'detail'">
                <button 
                  class="h-10 px-6 bg-white text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-50 focus:ring-2 focus:ring-gray-100 transition-all dark:bg-[#1e2536] dark:text-gray-300 dark:border-gray-600 dark:hover:bg-[#28324f]"
                  @click="closeMainModal"
                >
                  Đóng
                </button>
                <div class="flex gap-2">
                  <button class="btn-reject h-10 px-6 rounded-lg" @click="modalMode = 'reject'">Từ chối</button>
                  <button class="btn-approve h-10 px-6 rounded-lg" @click="modalMode = 'approve'">Phê duyệt</button>
                </div>
              </template>

              <!-- Mode: APPROVE -->
              <template v-else-if="modalMode === 'approve'">
                <button 
                  class="h-10 px-6 bg-white text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-50 focus:ring-2 focus:ring-gray-100 transition-all dark:bg-[#1e2536] dark:text-gray-300 dark:border-gray-600 dark:hover:bg-[#28324f]"
                  @click="modalMode = 'detail'"
                >
                  Quay lại
                </button>
                <button 
                  class="h-10 px-6 bg-[var(--sys-brand-solid)] hover:bg-[#1d4ed8] text-white shadow-md hover:shadow-lg transition-all rounded-lg flex items-center justify-center gap-2 border-0"
                  @click="confirmApprove"
                >
                  <span class="material-symbols-rounded text-[18px]">check_circle</span>
                  Xác nhận Phê duyệt
                </button>
              </template>

              <!-- Mode: REJECT -->
              <template v-else-if="modalMode === 'reject'">
                <button 
                  class="h-10 px-6 bg-white text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-50 focus:ring-2 focus:ring-gray-100 transition-all dark:bg-[#1e2536] dark:text-gray-300 dark:border-gray-600 dark:hover:bg-[#28324f]"
                  @click="modalMode = 'detail'"
                >
                  Quay lại
                </button>
                <button 
                  class="h-10 px-6 bg-[var(--sys-danger-solid)] hover:bg-red-700 text-white rounded-lg shadow-md hover:shadow-lg transition-all border-0" 
                  @click="confirmReject"
                >
                  XÁC NHẬN BÁC BỎ
                </button>
              </template>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>

    <!-- Redundant modals removed in favor of Unified Modal -->
  </div>
</template>

<script setup>
import { computed, ref, onMounted, onUnmounted } from 'vue';
import { useRouter } from 'vue-router';
import GD_DateFilter from '@/components/GD_DateFilter.vue';
import { apiRequest } from '@/services/beApi.js';
import { getInitials, getAvatarColors, getRequestTypeUI } from '@/utils/uiMapper.js';
import { useConfirm } from '@/composables/useConfirm';

const { showAlert } = useConfirm();
const router = useRouter();

const selectedDateRange = ref('30_days');
const Math = window.Math;

const approvals = ref([]);
const kpiCards = ref([]);
const barChartData = ref([]);
const barChartYLabels = ref([]);
const reminderText = ref('');
const timelineEvents = ref([]);
const employees = ref([]);
const departments = ref([]);
const leaveRequests = ref([]);
const attendances = ref([]);
const salaryDetails = ref([]);
const requestTypes = ref([]);

const normalizeStatus = (value) => {
  const raw = String(value || '').trim().toUpperCase();
  if (raw.includes('ĐANG') || raw.includes('ACTIVE')) return 'ĐANG_LÀM_VIỆC';
  if (raw.includes('NGHỈ') || raw.includes('INACTIVE') || raw.includes('TERMIN')) return 'ĐÃ_NGHỈ_VIỆC';
  return raw || 'ĐANG_LÀM_VIỆC';
};

const fetchData = async () => {
  try {
    const [resReqs, resEmp, resAtt, resDept, resSalary, resReqTypes] = await Promise.all([
      apiRequest('/leave-requests', { query: { page: 1, per_page: 3000 }, noGetCache: true }).catch(() => ({ data: [] })),
      apiRequest('/employees', { query: { page: 1, per_page: 5000 }, noGetCache: true }).catch(() => ({ data: [] })),
      apiRequest('/attendances', { query: { page: 1, per_page: 5000 }, noGetCache: true }).catch(() => ({ data: [] })),
      apiRequest('/departments', { query: { page: 1, per_page: 1000 }, noGetCache: true }).catch(() => ({ data: [] })),
      apiRequest('/salary-details', { query: { page: 1, per_page: 5000 }, noGetCache: true }).catch(() => ({ data: [] })),
      apiRequest('/request-types', { query: { page: 1, per_page: 1000 }, noGetCache: true }).catch(() => ({ data: [] })),
    ]);

    leaveRequests.value = resReqs?.data || [];
    employees.value = (resEmp?.data || []).map((e) => ({
      ...e,
      employeeId: e.employee_id ?? e.employeeId,
      fullName: e.full_name ?? e.fullName ?? '',
      departmentId: e.department_id ?? e.departmentId,
      status: normalizeStatus(e.status),
      hireDate: e.hire_date ?? e.hireDate ?? '',
      dateOfBirth: e.date_of_birth ?? e.dateOfBirth ?? '',
      role: e.role ?? '',
    }));
    attendances.value = resAtt?.data || [];
    departments.value = (resDept?.data || []).map((d) => ({
      ...d,
      departmentId: d.department_id ?? d.departmentId,
      departmentName: d.department_name ?? d.departmentName ?? '',
    }));
    salaryDetails.value = resSalary?.data || [];
    requestTypes.value = (resReqTypes?.data || []).map((r) => ({
      ...r,
      requestTypeId: r.request_type_id ?? r.requestTypeId,
      requestTypeName: r.request_type_name ?? r.requestTypeName,
      category: r.category ?? 'KHÁC',
    }));

    const allReqs = leaveRequests.value;
    const allEmps = employees.value;
    const allAtts = attendances.value;
    const allDepts = departments.value;

    const activeEmps = allEmps.filter(e => e.status !== 'ĐÃ_NGHỈ_VIỆC');
    const totalHeadcount = activeEmps.length;
    const totalAll = allEmps.length;

    // Tỷ lệ biến động: (Nghỉ việc / Tổng)
    const soNghiViec = allEmps.filter(e => e.status === 'ĐÃ_NGHỈ_VIỆC').length;
    const bienDongRate = totalAll > 0 ? ((soNghiViec / totalAll) * 100).toFixed(1) : '0.0';

    // Tổng quỹ lương từ salaryDetails
    const tongLuong = salaryDetails.value.reduce((sum, s) => sum + Number(s.net_salary ?? s.total_salary ?? s.basic_salary ?? 0), 0);
    const NGAN_SACH_LUONG = 5000000000; // 5 tỷ ngân sách dự tính cho quy mô hiện tại
    const nganSachPct = Math.min(Math.round((tongLuong / NGAN_SACH_LUONG) * 100), 100);
    let tongLuongFormatted;
    if (tongLuong >= 1_000_000_000) {
      tongLuongFormatted = (tongLuong / 1_000_000_000).toFixed(1) + ' tỷ';
    } else {
      tongLuongFormatted = (tongLuong / 1_000_000).toFixed(0) + ' Tr';
    }

    // Chuyên cần
    const totalAttRec = allAtts.length;
    const onTimeRec = allAtts.filter((a) => {
      const st = String(a.status || '').toUpperCase();
      return st.includes('ĐÚNG_GIỜ') || st.includes('ON_TIME') || st.includes('P') || Boolean(a.check_in_time || a.checkIn);
    }).length;
    const chuyenCanRate = totalAttRec > 0 ? Math.min(((onTimeRec / totalAttRec) * 100).toFixed(1), 100) : 98.2;

    kpiCards.value = [
      {
        id: 1, label: 'TỔNG NHÂN SỰ', value: totalHeadcount.toLocaleString('vi-VN'),
        icon: 'groups', iconClass: 'kpi-icon--blue',
        badge: '+5.2%', badgeTrend: 'up', badgeClass: 'kpi-badge--success',
        route: '/giamdoc/nhansu', footerType: 'sparkline',
        sparkline: [40, 55, 48, 62, 58, 70, 80, 100], sparklineDanger: false, meta: 'so với tháng trước'
      },
      {
        id: 2, label: 'TỶ LỆ BIẾN ĐỘNG', value: bienDongRate + '%',
        icon: 'sync_alt', iconClass: 'kpi-icon--rose',
        badge: '-0.5%', badgeTrend: 'down', badgeClass: 'kpi-badge--danger',
        route: '/giamdoc/biendong', footerType: 'sparkline',
        sparkline: [55, 48, 60, 44, 52, 40, 38, 60], sparklineDanger: true, meta: 'cải thiện 0.5%'
      },
      {
        id: 3, label: 'TỔNG QUỸ LƯƠNG THÁNG', value: tongLuongFormatted,
        icon: 'payments', iconClass: 'kpi-icon--green',
        badge: '+1.2%', badgeTrend: 'up', badgeClass: 'kpi-badge--success',
        route: '/giamdoc/bangluong', footerType: 'progress',
        progress: nganSachPct, progressClass: 'kpi-progress-fill--brand', meta: nganSachPct + '% ngân sách'
      },
      {
        id: 4, label: 'CHỈ SỐ CHUYÊN CẦN', value: chuyenCanRate + '%',
        icon: 'verified_user', iconClass: 'kpi-icon--amber',
        badge: '+0.8%', badgeTrend: 'up', badgeClass: 'kpi-badge--success',
        route: '/giamdoc/chuyencan', footerType: 'progress',
        progress: parseFloat(chuyenCanRate), progressClass: 'kpi-progress-fill--amber', meta: 'Xuất sắc'
      }
    ];

    // 2. Bar Chart: Headcount Growth (Last 6 Months)
    const months = ['T10/25', 'T11/25', 'T12/25', 'T1/26', 'T2/26', 'T3/26'];
    const cutoffDates = ['2025-10-31', '2025-11-30', '2025-12-31', '2026-01-31', '2026-02-28', '2026-03-31'];
    
    barChartData.value = months.map((m, idx) => {
        const count = allEmps.filter(e => e.hireDate && e.hireDate <= cutoffDates[idx] && (e.status !== 'ĐÃ_NGHỈ_VIỆC')).length;
        // Target giả lập tăng dần
        const target = 50 + (idx * 5); 
        return {
            label: m,
            current: count,
            target: target,
            active: idx === 5
        };
    });
    
    // Auto-adjust Y-axis labels based on current count
    const maxVal = Math.max(...barChartData.value.map(d => Math.max(d.current, d.target))) + 10;
    const step = Math.ceil(maxVal / 5);
    barChartYLabels.value = Array.from({length: 6}, (_, i) => (step * (5 - i)).toLocaleString());

    // 4. Dynamic Timeline: Today Birthdays and New Hires
    const todayStr = '03-25'; // Giả lập hôm nay 25/03 theo context
    const birthdays = allEmps.filter(e => e.dateOfBirth && e.dateOfBirth.includes(todayStr));
    const newHires = allEmps.filter(e => e.hireDate && e.hireDate.startsWith('2026-03'));
    
    let events = [];
    if (birthdays.length > 0) {
      events.push({
        id: 'bday',
        time: 'Cả ngày hôm nay',
        title: `Sinh nhật: ${birthdays.map(b => b.fullName).join(', ')}`,
        place: 'Văn phòng Công ty',
        placeIcon: 'cake',
        active: true
      });
    }
    events.push({
      id: 'meeting',
      time: '14:00 Chiều nay',
      title: 'Họp Review Chỉ số Quý I',
      place: 'Phòng Meeting 1',
      placeIcon: 'groups',
      active: birthdays.length === 0
    });
    if (newHires.length > 0) {
      events.push({
        id: 'new',
        time: 'Tháng này',
        title: `Chào mừng ${newHires.length} nhân sự mới`,
        place: 'Ký Hợp đồng thử việc',
        placeIcon: 'person_add',
        active: false
      });
    }
    timelineEvents.value = events.slice(0, 3);

    // 5. Pending Approvals
    approvals.value = allReqs.filter(r => {
        const isDirectorQueue = r.status === 'CHỜ_GIÁM_ĐỐC_DUYỆT';
        const isVisible = r.visible_to ? r.visible_to.includes('Director') : true;
        return isDirectorQueue && isVisible;
    }).map(r => {
        const requesterId = r.requester_id ?? r.requesterId ?? r.employee_id ?? r.employeeId;
        const emp = allEmps.find(e => String(e.employeeId) === String(requesterId)) || {};
        const dept = allDepts.find(d => String(d.departmentId) === String(emp.departmentId)) || {};
        const reqTypeObj = requestTypes.value.find((t) => String(t.requestTypeId) === String(r.request_type_id ?? r.requestTypeId)) || {};
        const ui = getRequestTypeUI(reqTypeObj.category || 'KHÁC') || {
          icon: 'help', color: 'text-gray-600', bg: 'bg-gray-50', catKey: 'khac'
        };
        const avatarUI = getAvatarColors(emp.employeeId || 1);
        return {
            id: r.request_id ?? r.id ?? r.requestId,
            requestId: r.request_id ?? r.requestId,
            isReal: true,
            statusRaw: r.status,
            title: r.title,
            meta: `${emp.fullName || 'Khuyết danh'} • Lý do: ${r.notes || 'Không có'}`,
            icon: r.is_urgent ? 'warning' : 'event_note',
            iconClass: r.is_urgent ? 'kpi-icon--amber' : 'kpi-icon--blue',
            urgent: r.is_urgent,
            actions: ['Từ chối', 'Phê duyệt'],
            status: 'pending',
            rejectReason: '',
            
            // New fields for unified Approval Modal (from Notification Center)
            name: emp.fullName || 'Khuyết danh',
            dept: dept.departmentName ? `Phòng ${dept.departmentName}` : '',
            type: reqTypeObj.requestTypeName || 'Khác',
            reasonText: r.reason || r.notes || r.title,
            
            // Detailed leave info
            requestCode: r.request_code || r.requestCode || `REQ-${r.request_id ?? r.requestId}`,
            startDate: r.start_date || r.startDate,
            endDate: r.end_date || r.endDate,
            totalDays: (r.total_days ?? r.days) || 0,
            requestDate: r.request_date || r.requestDate,
            
            // New UI fields from TTThongBao
            initials: getInitials(emp.fullName || '?'),
            avatarBg: avatarUI.bg,
            avatarColor: avatarUI.text,
            typeIcon: ui.icon,
            typeColor: ui.color,
            typeBg: ui.bg,
            time: r.request_date || r.requestDate || new Date().toISOString()
        };
    }).slice(0, 5);

    // Cập nhật Lời nhắc
    const pendingTotal = approvals.value.length;
    if (pendingTotal > 0) {
      reminderText.value = `Anh đang có ${pendingTotal} đơn phê duyệt đang chờ xử lý. Vui lòng kiểm tra và duyệt sớm để đảm bảo tiến độ công việc của nhân viên.`;
    } else {
      reminderText.value = 'Hiện tại không có thông báo hay lời nhắc mới. Chúc anh một ngày làm việc hiệu quả!';
    }

  } catch (error) {
    console.error('Lỗi khi tải dữ liệu Giám đốc:', error);
  }
};

onMounted(() => {
  fetchData();
  const interval = setInterval(() => {
    if (typeof document !== 'undefined' && document.hidden) return;
    fetchData();
  }, 45000);
  onUnmounted(() => clearInterval(interval));
});

const pendingApprovals = computed(() => approvals.value.filter((a) => a.status === 'pending'));

const urgentPendingCount = computed(() => {
  return pendingApprovals.value.filter((a) => a.urgent).length;
});

const selectedApproval = ref(null);
const showMainModal = ref(false);
const modalMode = ref('detail'); // 'detail', 'approve', 'reject'
const rejectReason = ref('');

const openMainModal = (item, mode = 'detail') => {
  selectedApproval.value = item;
  modalMode.value = mode;
  showMainModal.value = true;
};

const closeMainModal = () => {
  showMainModal.value = false;
  selectedApproval.value = null;
  rejectReason.value = '';
};

// Re-map existing open functions for compatibility with list items
const openDetailModal = (item) => openMainModal(item, 'detail');
const openApproveModal = (item) => openMainModal(item, 'approve');
const openRejectModal = (item) => openMainModal(item, 'reject');
const closeDetailModal = closeMainModal;
const closeApproveModal = closeMainModal;
const closeRejectModal = closeMainModal;

const confirmApprove = async () => {
  if (!selectedApproval.value) return;

  const requestId = selectedApproval.value.id;
  const isLeaveRequest = selectedApproval.value.type === 'Nghỉ phép' || selectedApproval.value.statusRaw === 'CHỜ_GIÁM_ĐỐC_DUYỆT';

  try {
    const newStatus = isLeaveRequest ? 'CHỜ_XÁC_NHẬN_HR' : 'ĐÃ_DUYỆT';
    const newStatusText = isLeaveRequest ? 'Chờ HR xác nhận' : 'Đã duyệt';

    await apiRequest(`/leave-requests/${requestId}`, {
      method: 'PATCH',
      body: {
        status: newStatus,
        statusText: newStatusText,
        approver_director: 'Ban Giám Đốc'
      },
    });
    const hrs = employees.value.filter((e) => {
      const role = String(e.role || '').toUpperCase();
      return role === 'HR' || role === 'ADMIN';
    });
    for (const hr of hrs) {
      await apiRequest('/notifications', {
        method: 'POST',
        body: {
          user_id: hr.employeeId,
          title: 'Đơn nghỉ phép chờ xác nhận',
          content: `Nhân viên ${selectedApproval.value.name} nghỉ đã được Giám đốc duyệt nhanh, cần bạn xác nhận & chấm công.`,
          notification_type: 'SYSTEM',
        },
      }).catch(() => null);
    }
    
    await fetchData();
  } catch (error) {
    console.error('Lỗi khi duyệt đơn:', error);
  }

  closeMainModal();
};

const confirmReject = async () => {
  if (!selectedApproval.value) return;
  const reason = rejectReason.value.trim();
  if (!reason) {
    await showAlert('THIẾU DỮ LIỆU', 'Vui lòng nhập lý do từ chối!');
    return;
  }

  const requestId = selectedApproval.value.id; // Đây là r.id nhờ vào mapping mới

  try {
    await apiRequest(`/leave-requests/${requestId}`, {
      method: 'PATCH',
      body: {
        status: 'TỪ_CHỐI',
        statusText: 'Đã từ chối',
        notes: reason,
        rejectionReason: reason
      },
    });
    await fetchData();
  } catch (error) {
    console.error('Lỗi khi từ chối đơn:', error);
  }

  closeMainModal();
};

// Tính toán Scale động cho Bar Chart từ Data
const dynamicBarChart = computed(() => {
  // Lấy giá trị nhỏ nhất và lớn nhất trực tiếp từ mảng nhãn trục Y
  const yLabels = barChartYLabels.value || [];
  if (yLabels.length === 0) return [];

  const maxLabel = parseFloat(yLabels[0].replace(/,/g, ''));
  const minLabel = parseFloat(yLabels[yLabels.length - 1].replace(/,/g, ''));
  const range = maxLabel - minLabel;

  return barChartData.value.map(col => {
    // Trực tiếp dùng dữ liệu raw integer theo dữ liệu API hiện tại
    const valCurrentNum = col.current || 0;
    const targetNum = col.target || 0;

    let currentH = range > 0 ? ((valCurrentNum - minLabel) / range) * 100 : (valCurrentNum > 100 ? 100 : valCurrentNum);
    let targetH = range > 0 ? ((targetNum - minLabel) / range) * 100 : (targetNum > 100 ? 100 : targetNum);

    // Kẹp trong khoảng 0 - 100% để giao diện không bể khi vượt chuẩn
    return {
      ...col,
      currentH: Math.max(0, Math.min(currentH, 115)),
      targetH: Math.max(0, Math.min(targetH, 115))
    };
  });
});
</script>

<style scoped>
/* ════════════════════════════════════════════════
   Dark Mode Variables Override
════════════════════════════════════════════════ */
:global(.dark) {
  --bg-card: #121827;           /* Executive Deep Navy Blue (Cùng tone với GD_HoSoCaNhan) */
  --border: rgba(255, 255, 255, 0.05); /* Viền mờ lấp lánh nhẹ (glassmorphism) */
  --text-title: #ffffff;        /* Trắng tuyệt đối cho tiêu đề */
  --text-body: #94a3b8;         /* slate-400 cho nội dung */
  --text-muted: #64748b;        /* slate-500 */
  --bg-hover: rgba(255, 255, 255, 0.03); /* Lót nền hover */
  --shadow-card: 0 10px 40px -10px rgba(0, 0, 0, 0.5); /* Bóng đổ gradient tối sang trọng */
  --shadow-hover: 0 15px 50px -15px rgba(0, 0, 0, 0.6), 0 0 0 1px rgba(255, 255, 255, 0.1);

  /* Fix lỗi hiển thị màu "mờ" của các con số / icon trong Dark Mode */
  --brand-light: rgba(59, 130, 246, 0.15);
  --brand: #60a5fa;

  --danger-light: rgba(239, 68, 68, 0.15);
  --danger: #f87171;
  --danger-text: #fca5a5;

  --success-light: rgba(34, 197, 94, 0.15);
  --success: #4ade80;
  --success-text: #86efac;

  --warning-light: rgba(245, 158, 11, 0.15);
  --warning: #fbbf24;
}

/* ════════════════════════════════════════════════
   Layout Styles
════════════════════════════════════════════════ */
.dashboard {
  max-width: 1600px;
  margin: 0 auto;
  padding: 24px;
  display: flex;
  flex-direction: column;
  gap: 24px;
}

/* ── Page Header ── */
.page-header {
  display: flex;
  align-items: flex-end;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 16px;
}

.page-title {
  font-size: 22px;
  font-weight: 800;
  color: var(--text-title, #111827);
  letter-spacing: -0.03em;
  margin: 0;
}

.page-subtitle {
  font-size: 13px;
  color: var(--text-muted, #6B7280);
  margin: 4px 0 0;
  display: flex;
  align-items: center;
}

.page-header-actions {
  display: flex;
  align-items: center;
  gap: 10px;
  flex-wrap: wrap;
}

.date-filter {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 8px 14px;
  border-radius: 8px;
  border: 1px solid var(--border, #E5E7EB);
  background: var(--bg-card, #fff);
  font-size: 13px;
  font-weight: 600;
  color: var(--text-body, #374151);
  cursor: pointer;
  box-shadow: var(--shadow-sm);
  transition: border-color 0.15s, box-shadow 0.15s;
}

.date-filter:hover {
  border-color: var(--brand, #3B82F6);
  box-shadow: 0 0 0 3px rgba(59,130,246,0.1);
}

.date-filter .material-symbols-rounded {
  font-size: 16px;
  color: var(--text-muted, #6B7280);
}

.btn-export {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 8px 16px;
  border-radius: 8px;
  border: none;
  background: var(--brand, #3B82F6);
  color: #fff;
  font-size: 13px;
  font-weight: 700;
  cursor: pointer;
  box-shadow: 0 2px 8px rgba(59,130,246,0.35);
  transition: background 0.15s, box-shadow 0.15s, transform 0.1s;
  font-family: inherit;
}

.btn-export:hover {
  background: var(--brand-hover, #2563EB);
  box-shadow: 0 4px 12px rgba(59,130,246,0.45);
  transform: translateY(-1px);
}

.btn-export .material-symbols-rounded { font-size: 17px; }

/* ── KPI Grid ── */
.kpi-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 16px;
}

@media (max-width: 1100px) { .kpi-grid { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 640px)  { .kpi-grid { grid-template-columns: 1fr; } }

.kpi-card {
  background: var(--bg-card, #fff);
  border: 1px solid var(--border, #E5E7EB);
  border-radius: 16px;
  padding: 20px;
  box-shadow: var(--shadow-card);
  transition: box-shadow 0.2s, transform 0.2s, background-color 0.3s, border-color 0.3s;
  cursor: pointer;
}

.kpi-card:hover {
  box-shadow: var(--shadow-hover);
  transform: translateY(-2px);
}

.kpi-card-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  margin-bottom: 14px;
}

.kpi-icon {
  width: 42px;
  height: 42px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.kpi-icon .material-symbols-rounded {
  font-size: 20px;
  font-variation-settings: 'FILL' 1, 'wght' 500, 'GRAD' 0, 'opsz' 24;
}

.kpi-icon--blue   { background: var(--brand-light, #DBEAFE); }
.kpi-icon--blue .material-symbols-rounded { color: var(--brand, #3B82F6); }

.kpi-icon--rose   { background: var(--danger-light, #FEE2E2); }
.kpi-icon--rose .material-symbols-rounded { color: var(--danger, #EF4444); }

.kpi-icon--green  { background: var(--success-light, #DCFCE7); }
.kpi-icon--green .material-symbols-rounded { color: var(--success, #22C55E); }

.kpi-icon--amber  { background: var(--warning-light, #FEF3C7); }
.kpi-icon--amber .material-symbols-rounded { color: var(--warning, #F59E0B); }

.kpi-badge {
  display: inline-flex;
  align-items: center;
  gap: 2px;
  font-size: 11px;
  font-weight: 700;
  padding: 3px 8px;
  border-radius: 999px;
}

.kpi-badge--up      { background: var(--success-light, #DCFCE7); color: var(--success-text, #15803D); }
.kpi-badge--down    { background: var(--danger-light, #FEE2E2); color: var(--danger-text, #B91C1C); }
.kpi-badge--success { background: var(--success-light, #DCFCE7); color: var(--success-text, #15803D); }
.kpi-badge--danger  { background: var(--danger-light, #FEE2E2); color: var(--danger-text, #B91C1C); }

.kpi-label {
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.07em;
  color: var(--text-muted, #6B7280);
  margin: 0 0 4px;
}

.kpi-value {
  font-size: 30px;
  font-weight: 800;
  color: var(--text-title, #111827);
  letter-spacing: -0.04em;
  margin: 0 0 12px;
  line-height: 1;
}

.kpi-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
}

.kpi-meta {
  font-size: 11px;
  color: var(--text-muted, #6B7280);
  text-align: right;
  white-space: nowrap;
}

/* Sparkline */
.kpi-sparkline {
  display: flex;
  align-items: flex-end;
  gap: 3px;
  height: 28px;
  flex: 1;
}

.kpi-sparkline .bar {
  flex: 1;
  border-radius: 3px 3px 0 0;
  background: var(--border, #E5E7EB);
  transition: background 0.2s;
}

.kpi-sparkline .bar--active    { background: var(--brand, #3B82F6); }
.kpi-sparkline .bar--danger    { background: var(--danger-light, #FEE2E2); }
.kpi-sparkline .bar--active-danger { background: var(--danger, #EF4444); }

/* Progress bar */
.kpi-progress-bar {
  flex: 1;
  height: 6px;
  background: var(--bg-hover, #F1F5F9);
  border-radius: 999px;
  overflow: hidden;
}

.kpi-progress-fill {
  height: 100%;
  border-radius: 999px;
  background: var(--brand, #3B82F6);
  transition: width 0.6s cubic-bezier(0.4,0,0.2,1);
}

.kpi-progress-fill--amber { background: var(--warning, #F59E0B); }
.kpi-progress-fill--brand { background: var(--brand, #3B82F6); }
.kpi-progress-fill--green { background: var(--success, #22C55E); }

/* ── Charts Grid ── */
.charts-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: 16px;
}

@media (max-width: 1100px) { .charts-grid { grid-template-columns: 1fr; } }

.chart-card {
  background: var(--bg-card, #fff);
  border: 1px solid var(--border, #E5E7EB);
  border-radius: 16px;
  padding: 22px;
  box-shadow: var(--shadow-card);
  transition: background-color 0.3s, border-color 0.3s;
}

.chart-card-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  margin-bottom: 20px;
}

.chart-title {
  font-size: 15px;
  font-weight: 700;
  color: var(--text-title, #111827);
  margin: 0;
}

.chart-subtitle {
  font-size: 12px;
  color: var(--text-muted, #6B7280);
  margin: 3px 0 0;
}

.chart-legend {
  display: flex;
  gap: 16px;
}

.legend-item {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 12px;
  font-weight: 600;
  color: var(--text-muted, #6B7280);
}

.legend-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
}

.legend-dot--brand { background: var(--brand, #3B82F6); }
.legend-dot--amber { background: var(--warning, #F59E0B); }

/* Bar Chart */
.bar-chart {
  position: relative;
  height: 220px;
  display: flex;
}

.bar-chart-cols {
  flex: 1;
  display: flex;
  align-items: flex-end;
  gap: 8px;
  padding-bottom: 28px;
  position: relative;
}



.bar-label {
  position: absolute;
  bottom: -24px;
  font-size: 10px;
  font-weight: 600;
  color: var(--text-muted, #6B7280);
  text-align: center;
  white-space: nowrap;
}

.bar-label--active { color: var(--brand, #3B82F6); font-weight: 700; }

.y-axis-lines {
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  padding: 0 0 28px 8px;
  width: 44px;
  flex-shrink: 0;
}

.y-label {
  font-size: 9px;
  color: var(--text-muted, #6B7280);
  text-align: right;
}

/* Donut */
.donut-wrapper {
  display: flex;
  justify-content: center;
  margin: 8px 0 20px;
}

.donut-chart-ring {
  width: 160px;
  height: 160px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: var(--shadow-card);
}

.donut-center {
  width: 110px;
  height: 110px;
  border-radius: 50%;
  background: var(--bg-card, #fff);
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  box-shadow: inset 0 2px 8px rgba(0,0,0,0.06);
  transition: background-color 0.3s;
}

.donut-number {
  font-size: 20px;
  font-weight: 800;
  color: var(--text-title, #111827);
  letter-spacing: -0.04em;
}

.donut-unit {
  font-size: 10px;
  font-weight: 600;
  color: var(--text-muted, #6B7280);
  text-transform: uppercase;
  letter-spacing: 0.06em;
}

.donut-legend {
  display: flex;
  flex-direction: column;
  gap: 10px;
  padding-top: 4px;
}

.donut-legend-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.flex { display: flex; }
.items-center { align-items: center; }
.gap-2 { gap: 8px; }

.donut-dot {
  width: 10px;
  height: 10px;
  border-radius: 50%;
  flex-shrink: 0;
}

.donut-label {
  font-size: 13px;
  font-weight: 500;
  color: var(--text-body, #374151);
}

.donut-stat {
  display: flex;
  align-items: center;
  gap: 8px;
}

.donut-pct {
  font-size: 13px;
  font-weight: 700;
  color: var(--text-title, #111827);
  min-width: 32px;
  text-align: right;
}

.donut-mini-bar {
  width: 60px;
  height: 5px;
  border-radius: 999px;
  background: var(--border, #E5E7EB);
  overflow: hidden;
}

.donut-mini-bar > div {
  height: 100%;
  border-radius: 999px;
  transition: width 0.6s cubic-bezier(0.4,0,0.2,1);
}

/* ── Bottom Grid ── */
.bottom-grid {
  display: grid;
  grid-template-columns: 1fr 340px;
  gap: 16px;
}

@media (max-width: 1100px) { .bottom-grid { grid-template-columns: 1fr; } }

.list-card {
  background: var(--bg-card, #fff);
  border: 1px solid var(--border, #E5E7EB);
  border-radius: 16px;
  box-shadow: var(--shadow-card);
  display: flex;
  flex-direction: column;
  overflow: hidden;
  transition: background-color 0.3s, border-color 0.3s;
}

.list-card-header {
  padding: 18px 20px 16px;
  border-bottom: 1px solid var(--border-light, #F3F4F6);
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.badge-urgent {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  background: var(--danger-light, #FEE2E2);
  color: var(--danger-text, #B91C1C);
  font-size: 11px;
  font-weight: 700;
  padding: 4px 10px;
  border-radius: 999px;
}

/* ── TT Approval Items (Synced from TTThongBao) ── */
.tt-approval-list { 
  display: flex; 
  flex-direction: column; 
  gap: 12px; 
  margin-top: 16px; /* Khoảng cách với thanh ngang tiêu đề */
}

.tt-approval-item {
  display: flex;
  align-items: center;
  padding: 12px 18px;
  background: var(--bg-card, #ffffff);
  border-radius: 12px;
  border: 1px solid var(--border, #f1f5f9);
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
  position: relative;
  overflow: hidden;
  cursor: pointer;
}

.tt-approval-item:hover {
  border-color: var(--brand, #3b82f6);
  box-shadow: var(--shadow-hover);
  background: var(--bg-hover, #f8faff);
}

.tt-approval-item--urgent {
  background: linear-gradient(135deg, var(--danger-light, #fffcfc) 0%, var(--bg-card, #ffffff) 100%) !important;
  border-color: var(--danger, #fca5a5) !important;
  border-left: 5px solid #ef4444 !important;
}

:global(.dark) .tt-approval-item {
  background: #1e293b !important; /* Force dark background */
  border-color: rgba(255,255,255,0.06) !important;
}

:global(.dark) .tt-approval-item--urgent {
  background: linear-gradient(135deg, #321c1c 0%, #1e293b 100%) !important;
  border-color: rgba(239, 68, 68, 0.3) !important;
}

/* ── Avatar ── */
.tt-avatar {
  width: 44px;
  height: 44px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 14px;
  font-weight: 800;
  flex-shrink: 0;
  position: relative;
  letter-spacing: -0.02em;
}

.tt-urgent-dot {
  position: absolute;
  top: -3px;
  right: -3px;
  width: 10px;
  height: 10px;
  background: #ef4444;
  border-radius: 50%;
  border: 2px solid white;
  animation: pulse-red 1.5s ease infinite;
}
:global(.dark) .tt-urgent-dot { border-color: #121827; }

/* ── Info block ── */
.tt-approval-info { display: flex; flex-direction: column; gap: 3px; min-width: 0; }

.tt-approval-name-row {
  display: flex;
  align-items: center;
  gap: 6px;
  flex-wrap: wrap;
}

.tt-approval-name {
  font-size: 13.5px;
  font-weight: 700;
  color: var(--text-title, #111827);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.tt-urgent-badge {
  display: inline-flex;
  align-items: center;
  gap: 2px;
  padding: 1px 7px;
  border-radius: 99px;
  background: #fee2e2;
  color: #dc2626;
  font-size: 10px;
  font-weight: 800;
  letter-spacing: 0.02em;
}

.tt-approval-dept {
  font-size: 11.5px;
  color: var(--text-muted, #9ca3af);
  font-weight: 500;
}

.tt-approval-title-row {
  display: flex;
  align-items: center;
  gap: 7px;
  flex-wrap: wrap;
  margin-top: 2px;
}

.tt-approval-type {
  display: inline-flex;
  align-items: center;
  gap: 3px;
  padding: 2px 8px;
  border-radius: 99px;
  font-size: 10.5px;
  font-weight: 700;
  white-space: nowrap;
}

.tt-approval-title {
  font-size: 13px;
  font-weight: 700;
  color: var(--text-title, #374151);
}

.tt-approval-time {
  display: inline-flex;
  align-items: center;
  gap: 3px;
  font-size: 11px;
  color: var(--text-muted, #9ca3af);
  margin-top: 2px;
}

/* ── Action Buttons ── */
.tt-approval-actions {
  display: flex;
  flex-direction: column;
  gap: 6px;
  flex-shrink: 0;
}

.tt-btn-reject {
  padding: 6px 14px;
  border-radius: 8px;
  border: 1.5px solid var(--border, #e5e7eb);
  background: transparent;
  font-size: 11px;
  font-weight: 800;
  color: var(--text-body, #6b7280);
  cursor: pointer;
  transition: all 0.15s;
  font-family: inherit;
  letter-spacing: 0.04em;
  white-space: nowrap;
}
.tt-btn-reject:hover { border-color: #ef4444; color: #ef4444; background: #fff1f2; }

.tt-btn-approve {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  padding: 7px 14px;
  border-radius: 8px;
  border: none;
  background: linear-gradient(135deg, #2563eb, #1d4ed8);
  color: #fff;
  font-size: 11px;
  font-weight: 800;
  cursor: pointer;
  transition: all 0.15s;
  font-family: inherit;
  letter-spacing: 0.04em;
  white-space: nowrap;
  box-shadow: 0 3px 10px rgba(37,99,235,0.3);
}
.tt-btn-approve:hover {
  background: linear-gradient(135deg, #1d4ed8, #1e40af);
  transform: translateY(-1px);
  box-shadow: 0 5px 14px rgba(37,99,235,0.4);
}

@keyframes pulse-red {
  0%,100% { transform: scale(1); opacity: 1; }
  50%      { transform: scale(1.4); opacity: 0.7; }
}

@keyframes fadeInUp {
  from { opacity: 0; transform: translateY(16px); }
  to   { opacity: 1; transform: translateY(0); }
}

@keyframes pillarGrowModern {
  from { 
    transform: scaleY(0); 
    opacity: 0; 
    filter: blur(8px);
  }
  to { 
    transform: scaleY(1); 
    opacity: 1; 
    filter: blur(0);
  }
}

.bar-pillar-modern {
  transform-origin: bottom;
  animation: pillarGrowModern 1.2s cubic-bezier(0.34, 1.56, 0.64, 1) backwards;
}

.btn-schedule {
  background: #1E293B;
  border: 1px solid #334155;
  color: #F1F5F9;
  box-shadow: 0 1px 4px rgba(0,0,0,0.3);
}
.btn-schedule:hover { background: #334155 !important; }

.list-card-footer-container {
  margin-top: auto;
  padding: 16px;
  display: flex;
  justify-content: center;
  border-top: 1px solid var(--border-light, #F3F4F6);
  background: linear-gradient(to bottom, transparent, var(--bg-hover, #F8FAFC)/30);
}

.btn-view-all {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 8px 24px;
  border-radius: 999px;
  background: var(--brand-light, #DBEAFE);
  color: var(--brand, #2563eb);
  font-size: 12px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  border: 1.5px solid transparent;
  cursor: pointer;
  font-family: inherit;
  transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
  box-shadow: 0 2px 4px rgba(37, 99, 235, 0.05);
}

.btn-view-all:hover {
  background: var(--brand, #2563eb);
  color: white;
  transform: translateY(-2px);
  box-shadow: 0 6px 15px rgba(37, 99, 235, 0.2);
}

.btn-view-all .material-symbols-rounded {
  font-size: 16px;
  transition: transform 0.25s cubic-bezier(0.4, 0, 0.2, 1);
}

.btn-view-all:hover .material-symbols-rounded {
  transform: translateX(4px);
}

:global(.dark) .btn-view-all {
  background: rgba(37, 99, 235, 0.15);
  color: #93c5fd;
  border-color: rgba(37, 99, 235, 0.2);
}

:global(.dark) .btn-view-all:hover {
  background: var(--brand, #2563eb);
  color: white;
}

:global(.dark) .list-card-footer-container {
  border-top-color: rgba(255,255,255,0.05);
}

/* Events Card — always dark themed regardless of light/dark toggle */
.events-card {
  background: #0F172A;
  border: 1px solid #1E3A5F;
  border-radius: 16px;
  padding: 22px;
  box-shadow: 0 8px 32px rgba(0,0,0,0.5), 0 4px 16px rgba(59,130,246,0.1);
  display: flex;
  flex-direction: column;
  position: relative;
  overflow: hidden;
}

.events-glow {
  position: absolute;
  top: -60px;
  right: -60px;
  width: 200px;
  height: 200px;
  background: radial-gradient(circle, rgba(59,130,246,0.15), transparent 70%);
  pointer-events: none;
}

.events-header {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 20px;
}

.events-icon {
  color: var(--warning, #F59E0B);
  font-variation-settings: 'FILL' 1, 'wght' 500, 'GRAD' 0, 'opsz' 24;
  font-size: 20px;
}

.events-title {
  font-size: 15px;
  font-weight: 700;
  color: #F1F5F9;
  margin: 0;
}

/* Timeline */
.timeline {
  flex: 1;
  position: relative;
  padding-left: 20px;
}

.timeline-line {
  position: absolute;
  left: 6px;
  top: 6px;
  bottom: 0;
  width: 1px;
  background: linear-gradient(to bottom, rgba(255,255,255,0.15), transparent);
}

.timeline-item {
  position: relative;
  margin-bottom: 20px;
  opacity: 0.7;
  transition: opacity 0.2s;
}

.timeline-item--active { opacity: 1; }

.timeline-dot {
  position: absolute;
  left: -17px;
  top: 4px;
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: #334155;
  border: 2px solid #475569;
}

.timeline-dot--active {
  background: var(--warning, #F59E0B);
  border-color: var(--warning, #F59E0B);
  box-shadow: 0 0 10px rgba(245,158,11,0.5);
  width: 10px;
  height: 10px;
  left: -18px;
}

.timeline-time {
  font-size: 10px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.1em;
  color: rgba(255,255,255,0.35);
  margin: 0 0 3px;
}

.active-time { color: var(--warning, #F59E0B); }

.timeline-title {
  font-size: 13px;
  font-weight: 600;
  color: #F1F5F9;
  margin: 0 0 3px;
}

.timeline-place {
  font-size: 11px;
  color: rgba(255,255,255,0.4);
  margin: 0;
  display: flex;
  align-items: center;
  gap: 2px;
}

.timeline-place .material-symbols-rounded { color: rgba(255,255,255,0.3); }

/* Reminder */
.reminder-card {
  margin-top: 18px;
  background: rgba(255,255,255,0.05);
  border: 1px solid rgba(255,255,255,0.08);
  border-radius: 12px;
  padding: 14px 16px;
  backdrop-filter: blur(8px);
}

.reminder-label {
  display: flex;
  align-items: center;
  gap: 5px;
  font-size: 10px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.1em;
  color: var(--warning, #F59E0B);
  margin: 0 0 6px;
}

.reminder-label .material-symbols-rounded { color: var(--warning, #F59E0B); }

.reminder-text {
  font-size: 12.5px;
  color: rgba(255,255,255,0.7);
  font-style: italic;
  line-height: 1.55;
  margin: 0 0 10px;
}

.reminder-edit {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.07em;
  color: rgba(255,255,255,0.5);
  background: none;
  border: none;
  cursor: pointer;
  font-family: inherit;
  padding: 0;
  transition: color 0.15s;
}

.reminder-edit:hover { color: #F59E0B; }

/* Modal fades */
.modal-fade-enter-active,
.modal-fade-leave-active {
  transition: opacity 0.2s ease;
}
.modal-fade-enter-from,
.modal-fade-enter-from,
.modal-fade-leave-to {
  opacity: 0;
}
</style>

<style>
/* ─── Avatar & Type badge dynamic bg colors (Unscoped for Tailwind overrides) ─── */
.dark .tt-avatar.bg-pink-100    { background-color: rgba(236,72,153,0.18) !important; }
.dark .tt-avatar.text-pink-600  { color: #f9a8d4 !important; }
.dark .tt-avatar.bg-indigo-100  { background-color: rgba(99,102,241,0.18) !important; }
.dark .tt-avatar.text-indigo-600{ color: #a5b4fc !important; }
.dark .tt-avatar.bg-green-100   { background-color: rgba(34,197,94,0.18) !important; }
.dark .tt-avatar.text-green-600 { color: #86efac !important; }
.dark .tt-avatar.bg-amber-100   { background-color: rgba(245,158,11,0.18) !important; }
.dark .tt-avatar.text-amber-600 { color: #fcd34d !important; }
.dark .tt-avatar.bg-teal-100    { background-color: rgba(20,184,166,0.18) !important; }
.dark .tt-avatar.text-teal-600  { color: #5eead4 !important; }
.dark .tt-avatar.bg-purple-100  { background-color: rgba(168,85,247,0.18) !important; }
.dark .tt-avatar.text-purple-600{ color: #d8b4fe !important; }
.dark .tt-avatar.bg-rose-100    { background-color: rgba(244,63,94,0.18) !important; }
.dark .tt-avatar.text-rose-600  { color: #fda4af !important; }
.dark .tt-avatar.bg-sky-100     { background-color: rgba(14,165,233,0.18) !important; }
.dark .tt-avatar.text-sky-600   { color: #7dd3fc !important; }

.dark .tt-approval-type.bg-blue-50     { background-color: rgba(37,99,235,0.18) !important; }
.dark .tt-approval-type.text-blue-600  { color: #93c5fd !important; }
.dark .tt-approval-type.bg-indigo-50   { background-color: rgba(99,102,241,0.18) !important; }
.dark .tt-approval-type.text-indigo-600{ color: #a5b4fc !important; }
.dark .tt-approval-type.bg-green-50    { background-color: rgba(34,197,94,0.18) !important; }
.dark .tt-approval-type.text-green-600 { color: #86efac !important; }
.dark .tt-approval-type.bg-amber-50    { background-color: rgba(245,158,11,0.18) !important; }
.dark .tt-approval-type.text-amber-600 { color: #fcd34d !important; }
.dark .tt-approval-type.bg-teal-50     { background-color: rgba(20,184,166,0.18) !important; }
.dark .tt-approval-type.text-teal-600  { color: #5eead4 !important; }
.dark .tt-approval-type.bg-purple-50   { background-color: rgba(168,85,247,0.18) !important; }
.dark .tt-approval-type.text-purple-600{ color: #d8b4fe !important; }
.dark .tt-approval-type.bg-red-50      { background-color: rgba(239,68,68,0.18) !important; }
.dark .tt-approval-type.text-red-600   { color: #fca5a5 !important; }

.dark .tt-approval-name { color: #f1f5f9 !important; }
.dark .tt-approval-dept { color: rgba(255,255,255,0.7) !important; }
.dark .tt-approval-title { color: #e2e8f0 !important; }
.dark .tt-approval-time { color: rgba(255,255,255,0.5) !important; }
.dark .tt-urgent-badge { background-color: rgba(239,68,68,0.2) !important; color: #fca5a5 !important; }
</style>
