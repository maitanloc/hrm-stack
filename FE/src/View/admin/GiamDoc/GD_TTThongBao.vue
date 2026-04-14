<template>
  <div class="tt-container">

    <!-- ══════════════════════════════════════
         PAGE HEADER
    ══════════════════════════════════════════ -->
    <div class="tt-header">
      <div class="tt-header-left">
        <div class="tt-header-icon-wrap">
          <span class="material-symbols-rounded tt-header-icon">notifications_active</span>
        </div>
        <div>
          <h1 class="tt-page-title">Trung tâm Phê duyệt</h1>
          <p class="tt-page-sub">
            Chào mừng Giám đốc, bạn có
            <strong class="tt-count-highlight">{{ pendingList.length }} yêu cầu mới</strong>
            cần xử lý trong hôm nay.
          </p>
        </div>
      </div>
      <div class="tt-header-right">
        <div class="tt-stat-chip tt-stat-chip--blue">
          <span class="material-symbols-rounded" style="font-size:16px;font-variation-settings:'FILL' 1">pending_actions</span>
          <span>{{ pendingList.length }} Chờ duyệt</span>
        </div>
        <div class="tt-stat-chip tt-stat-chip--green">
          <span class="material-symbols-rounded" style="font-size:16px;font-variation-settings:'FILL' 1">task_alt</span>
          <span>{{ approvalStats.todayApproved }} Đã duyệt</span>
        </div>
        <div class="tt-stat-chip tt-stat-chip--red">
          <span class="material-symbols-rounded" style="font-size:16px;font-variation-settings:'FILL' 1">cancel</span>
          <span>{{ approvalStats.todayRejected }} Từ chối</span>
        </div>
      </div>
    </div>

    <!-- ══════════════════════════════════════
         FILTER TABS
    ══════════════════════════════════════════ -->
    <div class="tt-filter-bar">
      <div class="tt-tabs">
        <button
          v-for="tab in tabs"
          :key="tab.key"
          class="tt-tab"
          :class="{ 'tt-tab--active': activeTab === tab.key }"
          @click="activeTab = tab.key"
        >
          <span class="material-symbols-rounded" style="font-size:16px;font-variation-settings:'FILL' 1">{{ tab.icon }}</span>
          {{ tab.label }}
          <span v-if="tab.count > 0" class="tt-tab-badge">{{ tab.count }}</span>
        </button>
      </div>
    </div>

    <!-- ══════════════════════════════════════
         MAIN TWO-COLUMN LAYOUT
    ══════════════════════════════════════════ -->
    <div class="tt-main-grid">

      <!-- ────────────────────────────────
           LEFT: YÊU CẦU CHỜ PHÊ DUYỆT
      ──────────────────────────────────── -->
      <div class="tt-col-left animate-in">
        <div class="tt-card">
          <div class="tt-card-header">
            <div class="tt-card-header-left">
              <span class="material-symbols-rounded tt-section-icon" style="font-variation-settings:'FILL' 1">approval</span>
              <h2 class="tt-section-title">Yêu cầu Chờ Phê duyệt</h2>
            </div>
            <button class="tt-view-history-link" @click="showHistory = true">
              Xem tất cả lịch sử
              <span class="material-symbols-rounded" style="font-size:14px">arrow_forward</span>
            </button>
          </div>

          <!-- Empty State -->
          <div v-if="filteredRequests.length === 0" class="tt-empty">
            <span class="material-symbols-rounded tt-empty-icon" style="font-variation-settings:'FILL' 1">inbox</span>
            <p class="tt-empty-title">Không có yêu cầu nào</p>
            <p class="tt-empty-sub">Tất cả yêu cầu đã được xử lý.</p>
          </div>

          <!-- Approval Items -->
          <transition-group name="list-fade" tag="div" class="tt-approval-list space-y-3">
            <div
              v-for="(item, index) in filteredRequests"
              :key="item.id"
              class="tt-approval-item group"
              :class="{ 'tt-approval-item--urgent': item.urgent }"
              :style="{ animationDelay: (index * 60) + 'ms' }"
              @click="openMainModal(item, 'detail')"
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
                    {{ item.time ? item.time.split('T')[0] : 'N/A' }}
                  </span>
                </div>
                <p class="tt-approval-title line-clamp-1 font-bold text-[13px] text-slate-600 dark:text-slate-300">
                  {{ item.title }}
                </p>
              </div>

              <!-- Part 3: Actions -->
              <div class="tt-approval-actions">
                <button class="tt-btn-reject" title="Từ chối yêu cầu" @click.stop="openMainModal(item, 'reject')">
                  BÁC BỎ
                </button>
                <button class="tt-btn-approve" title="Phê duyệt ngay" @click.stop="openMainModal(item, 'approve')">
                  <span class="material-symbols-rounded text-[14px] font-fill">verified</span>
                  PHÊ DUYỆT
                </button>
              </div>
            </div>
          </transition-group>

          <!-- History Section -->
          <transition name="slide-fade">
            <div v-if="showHistory && processedList.length > 0" class="tt-history-section">
              <div class="tt-history-header">
                <span class="material-symbols-rounded" style="font-size:16px;font-variation-settings:'FILL' 1">history</span>
                Lịch sử xử lý hôm nay
              </div>
              <div
                v-for="item in processedList"
                :key="'h-' + item.id"
                class="tt-history-item"
              >
                <div class="tt-avatar tt-avatar--sm" :class="[item.avatarBg, item.avatarColor]">{{ item.initials }}</div>
                <div class="tt-history-info">
                  <span class="tt-history-name">{{ item.name }}</span>
                  <span class="tt-history-title">{{ item.title }}</span>
                </div>
                <span class="tt-history-status" :class="item.status === 'approved' ? 'tt-status--approved' : 'tt-status--rejected'">
                  <span class="material-symbols-rounded" style="font-size:13px;font-variation-settings:'FILL' 1">{{ item.status === 'approved' ? 'task_alt' : 'cancel' }}</span>
                  {{ item.status === 'approved' ? 'Đã duyệt' : 'Đã từ chối' }}
                </span>
              </div>
            </div>
          </transition>
        </div>
      </div>

      <!-- ────────────────────────────────
           RIGHT: THÔNG BÁO QUAN TRỌNG
      ──────────────────────────────────── -->
      <div class="tt-col-right">

        <!-- Notifications Card -->
        <div class="tt-card animate-in" style="animation-delay:100ms">
          <div class="tt-card-header">
            <div class="tt-card-header-left">
              <span class="material-symbols-rounded tt-section-icon tt-section-icon--amber" style="font-variation-settings:'FILL' 1">notifications</span>
              <h2 class="tt-section-title">Thông báo Quan trọng</h2>
            </div>
            <span class="tt-notif-count-badge">{{ importantNotifications.length }}</span>
          </div>

          <div class="tt-notif-list">
            <div
              v-for="(notif, index) in importantNotifications"
              :key="notif.id"
              class="tt-notif-item"
              :style="{ animationDelay: (index * 80 + 150) + 'ms' }"
            >
              <div class="tt-notif-dot-wrap">
                <span class="tt-notif-dot" :class="notif.dotColor"></span>
                <div v-if="index < importantNotifications.length - 1" class="tt-notif-line"></div>
              </div>
              <div class="tt-notif-body">
                <span class="tt-notif-level" :class="[notif.levelBg, notif.levelColor]">{{ notif.levelLabel }}</span>
                <p class="tt-notif-title">{{ notif.title }}</p>
                <p class="tt-notif-desc">{{ notif.desc }}</p>
                <div class="tt-notif-footer">
                  <button
                    v-if="notif.action"
                    class="tt-notif-action"
                    :class="notif.levelColor"
                    @click="notif.actionRoute ? router.push(notif.actionRoute) : null"
                  >
                    {{ notif.action }}
                  </button>
                  <span class="tt-notif-time">{{ notif.time }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>

      </div>
    </div>

    <!-- Unified Approval Modal -->
    <Teleport to="body">
      <Transition name="modal-fade">
        <div v-if="showMainModal && selectedItem" class="tt-modal-overlay" @click.self="closeMainModal">
          <div class="tt-modal animate-in fade-in zoom-in duration-300">
            <div class="tt-modal-header">
              <div 
                class="tt-modal-icon transition-colors duration-300"
                :class="{
                  'tt-modal-icon--approve': modalMode === 'approve',
                  'tt-modal-icon--reject': modalMode === 'reject',
                  'bg-slate-100 text-slate-500': modalMode === 'detail'
                }"
              >
                <span v-if="modalMode === 'approve'" class="material-symbols-rounded" style="font-size:26px;font-variation-settings:'FILL' 1">task_alt</span>
                <span v-else-if="modalMode === 'reject'" class="material-symbols-rounded" style="font-size:26px;font-variation-settings:'FILL' 1">cancel</span>
                <span v-else class="material-symbols-rounded" style="font-size:26px;">visibility</span>
              </div>
              <div class="tt-modal-title-block">
                <h3 class="tt-modal-title">
                  <span v-if="modalMode === 'approve'">Xác nhận Phê duyệt</span>
                  <span v-else-if="modalMode === 'reject'">Xác nhận Từ chối</span>
                  <span v-else>Chi tiết yêu cầu</span>
                </h3>
                <p class="tt-modal-subtitle">{{ selectedItem.name }} — {{ selectedItem.title }}</p>
              </div>
              <button class="tt-modal-close" @click="closeMainModal">
                <span class="material-symbols-rounded">close</span>
              </button>
            </div>
            <div class="tt-modal-body">
              <div class="tt-modal-info-box" style="display: grid; gap: 10px;">
                <div class="tt-modal-info-row">
                  <span class="tt-modal-info-label">Mã đơn</span>
                  <span class="tt-modal-info-val" style="font-family: monospace; font-weight: 800; color: #2563eb;">{{ selectedItem.requestCode }}</span>
                </div>
                <div class="tt-modal-info-row">
                  <span class="tt-modal-info-label">Loại yêu cầu</span>
                  <span class="tt-modal-info-val">{{ selectedItem.type }}</span>
                </div>
                <div class="tt-modal-info-row">
                  <span class="tt-modal-info-label">Thời gian nghỉ</span>
                  <span class="tt-modal-info-val">
                    {{ selectedItem.startDate }} <span style="opacity: 0.5; margin: 0 4px;">→</span> {{ selectedItem.endDate }}
                  </span>
                </div>
                <div class="tt-modal-info-row">
                  <span class="tt-modal-info-label">Tổng thời gian</span>
                  <span class="tt-modal-info-val" style="color: #ea580c; font-weight: 800;">{{ selectedItem.totalDays }} ngày</span>
                </div>
                <div class="tt-modal-info-row">
                  <span class="tt-modal-info-label">Người yêu cầu</span>
                  <span class="tt-modal-info-val">{{ selectedItem.dept }} ({{ selectedItem.name }})</span>
                </div>
                <div class="tt-modal-info-row" style="border-bottom: none;">
                  <span class="tt-modal-info-label">Ngày gửi</span>
                  <span class="tt-modal-info-val">{{ selectedItem.requestDate }}</span>
                </div>
              </div>

              <!-- Detailed Reason Box (Always visible) -->
              <div class="mt-4 p-4 bg-[var(--bg-hover)] border border-[var(--border)] rounded-xl text-left">
                 <p class="text-[10px] font-black text-[var(--text-muted)] uppercase tracking-widest mb-1 opacity-60">Lý do chi tiết</p>
                 <p class="italic text-[13px] text-[var(--text-body)] font-bold">
                    "{{ selectedItem.reasonText || 'Không có ghi chú thêm.' }}"
                 </p>
              </div>

              <!-- Conditional Section: Reject -->
              <div v-if="modalMode === 'reject'" class="mt-4">
                <label class="tt-modal-label">Lý do từ chối <span style="color:#ef4444">*</span></label>
                <textarea
                  v-model="rejectReason"
                  rows="3"
                  class="tt-modal-textarea"
                  placeholder="Nhập lý do từ chối chi tiết để gửi tới nhân sự..."
                ></textarea>
                <p class="tt-modal-hint">* Thông tin này sẽ được gửi trực tiếp đến hộp thư của nhân sự.</p>
              </div>

              <!-- Conditional Section: Approve -->
              <p v-if="modalMode === 'approve'" class="tt-modal-confirm-text">
                Bạn có chắc chắn muốn <strong style="color: var(--brand, #2563EB)">phê duyệt</strong> yêu cầu này không?
              </p>
            </div>
            <div class="tt-modal-footer">
              <!-- Detail Mode Actions -->
              <template v-if="modalMode === 'detail'">
                <button class="tt-modal-btn-cancel" @click="closeMainModal">Đóng</button>
                <div style="display: flex; gap: 8px;">
                  <button class="tt-modal-btn-confirm tt-modal-btn-confirm--reject" style="background:#ef4444" @click="modalMode = 'reject'">Từ chối</button>
                  <button class="tt-modal-btn-confirm tt-modal-btn-confirm--approve" @click="modalMode = 'approve'">Phê duyệt</button>
                </div>
              </template>

              <!-- Approve Mode Actions -->
              <template v-else-if="modalMode === 'approve'">
                <button class="tt-modal-btn-cancel" @click="modalMode = 'detail'">Quay lại</button>
                <button class="tt-modal-btn-confirm tt-modal-btn-confirm--approve" @click="confirmApprove">
                  <span class="material-symbols-rounded" style="font-size:16px;font-variation-settings:'FILL' 1">check_circle</span>
                  Phê duyệt
                </button>
              </template>

              <!-- Reject Mode Actions -->
              <template v-else-if="modalMode === 'reject'">
                <button class="tt-modal-btn-cancel" @click="modalMode = 'detail'">Quay lại</button>
                <button class="tt-modal-btn-confirm tt-modal-btn-confirm--reject" @click="confirmReject">
                  <span class="material-symbols-rounded" style="font-size:16px;font-variation-settings:'FILL' 1">cancel</span>
                  Xác nhận Từ chối
                </button>
              </template>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>

    <!-- Toast -->
    <Teleport to="body">
      <Transition name="toast-slide">
        <div v-if="toast.show" class="tt-toast" :class="toast.type === 'approve' ? 'tt-toast--approve' : 'tt-toast--reject'">
          <span class="material-symbols-rounded" style="font-size:18px;font-variation-settings:'FILL' 1">
            {{ toast.type === 'approve' ? 'task_alt' : 'cancel' }}
          </span>
          {{ toast.msg }}
        </div>
      </Transition>
    </Teleport>

  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useRouter } from 'vue-router';
// Import Functional MockDB
import { mockLeaveRequests, mockEmployees, mockDepartments, mockRequestTypes } from '@/mock-data/index.js';
import { getInitials, getAvatarColors, getRequestTypeUI } from '@/utils/uiMapper.js';
import { importantNotifications as staticNotifications } from '@/mock-data/sampleData_GiamDoc.js';

const router = useRouter();

// ── State ──────────────────────────────────────────────
const activeTab   = ref('all');
const showHistory = ref(false);
const showMainModal = ref(false);
const modalMode = ref('detail'); // detail, approve, reject
const selectedItem = ref(null);
const rejectReason = ref('');
const toast = ref({ show: false, type: '', msg: '' });
const rawRequests = ref([]);

const fetchData = async () => {
  try {
    const [resReqs, resEmp] = await Promise.all([
      fetch('http://localhost:3000/leaveRequests').then(r => r.ok ? r.json() : null).catch(() => null),
      fetch('http://localhost:3000/employees').then(r => r.ok ? r.json() : null).catch(() => null)
    ]);

    if (resReqs) rawRequests.value = resReqs;
    // Employees are handled by mockEmployees helpers for now, but we use the API data if available
    if (resEmp) {
       // Update global mock for components relying on it
       resEmp.forEach(e => mockEmployees.update(e.employeeId || e.id, e));
    }
  } catch (error) {
    console.error('Lỗi khi tải dữ liệu Trung tâm phê duyệt:', error);
  }
};

import { onMounted, onUnmounted } from 'vue';
onMounted(() => {
  fetchData();
  const interval = setInterval(fetchData, 10000); // Đồng bộ 10s
  onUnmounted(() => clearInterval(interval));
});

const openMainModal = (item, mode = 'detail') => {
  selectedItem.value = item;
  modalMode.value = mode;
  showMainModal.value = true;
};

const closeMainModal = () => {
  showMainModal.value = false;
  selectedItem.value = null;
  rejectReason.value = '';
};

// Compatibility wrappers
const openApprove = (item) => openMainModal(item, 'approve');
const openReject = (item) => openMainModal(item, 'reject');
const closeApprove = closeMainModal;
const closeReject = closeMainModal;

// ── Mock Notifications ──────────────────────────────────
const importantNotifications = computed(() => {
  // Kết hợp thông báo tĩnh và thông báo từ yêu cầu khẩn cấp
  const dynamicNotifs = pendingList.value.filter(r => r.urgent).slice(0, 2).map(r => ({
    id: `urgent-${r.id}`,
    level: 'canh_bao',
    levelLabel: 'KHẨN CẤP',
    levelColor: 'text-orange-700',
    levelBg: 'bg-orange-50',
    dotColor: 'bg-orange-500',
    icon: 'warning',
    iconColor: 'text-orange-500',
    title: `Yêu cầu phê duyệt khẩn: ${r.name}`,
    desc: `${r.title} cần được xử lý ngay trong ngày.`,
    action: 'Xử lý ngay',
    actionRoute: null,
    time: 'Vừa xong'
  }));

  return [...dynamicNotifs, ...staticNotifications.slice(0, 2)];
});

const approvalStats = computed(() => {
  const all = mappedRequests.value;
  const today = all.length;
  const approved = all.filter(r => r.status === 'approved').length;
  const rejected = all.filter(r => r.status === 'rejected').length;
  return {
    todayApproved: approved,
    todayRejected: rejected,
    total: today
  };
});

// ── Mapped Reactive Requests ────────────────────────────
const mappedRequests = computed(() => {
  // Lấy toàn bộ yêu cầu và sắp xếp mới nhất lên đầu
  const allRequests = [...rawRequests.value].sort((a, b) => {
    const dateA = new Date(a.requestDate || 0);
    const dateB = new Date(b.requestDate || 0);
    return dateB - dateA;
  });

  return allRequests.map(req => {
    const emp = mockEmployees.getById(req.requesterId) || {};
    const dept = mockDepartments.getById(emp.departmentId) || {};
    const reqTypeObj = mockRequestTypes.getById(req.requestTypeId) || {};
    // Dùng category string để lấy cấu hình UI, fallback 'KHÁC' tránh crash
    const ui = getRequestTypeUI(reqTypeObj.category || 'KHÁC') || {
      icon: 'help', color: 'text-gray-600', bg: 'bg-gray-50', catKey: 'khac'
    };
    const avatarUI = getAvatarColors(emp.employeeId || 1);

    // Trạng thái 'pending' cụ thể cho Giám đốc giải quyết là CHỜ_GIÁM_ĐỐC_DUYỆT
    // Nếu muốn hiển thị cả CHỜ_DUYỆT của TP thì thêm vào, nhưng user nói Icon Nav (chỉ lọc CHỜ_GD) là đúng
    // Vậy ta thống nhất: Giám đốc chỉ thấy những gì CẦN GIÁM ĐỐC DUYỆT
    // ĐỒNG BỘ LOGIC: Giám đốc chỉ xử lý đơn nghỉ phép ở trạng thái CHỜ_GIÁM_ĐỐC_DUYỆT
    const isPendingForDirector = req.status === 'CHỜ_GIÁM_ĐỐC_DUYỆT';

    return {
      id: req.requestId,
      name: emp.fullName || 'Khuyết danh',
      dept: dept.departmentName ? `Phòng ${dept.departmentName}` : '',
      initials: getInitials(emp.fullName || '?'),
      avatarBg: avatarUI.bg,
      avatarColor: avatarUI.text,
      type: reqTypeObj.requestTypeName || 'Khác',
      typeIcon: ui.icon,
      typeColor: ui.color,
      typeBg: ui.bg,
      title: req.title || 'Không có nội dung',
      time: req.requestDate || new Date().toISOString(),
      urgent: !!req.is_urgent || req.days >= 3,
      category: ui.catKey,
      reasonText: req.reason || req.notes || req.title,
      
      // Detailed info
      requestCode: req.requestCode || `REQ-${req.requestId}`,
      startDate: req.startDate,
      endDate: req.endDate,
      totalDays: req.days || 0,
      requestDate: req.requestDate,

      status: isPendingForDirector ? 'pending' : (req.status === 'ĐÃ_DUYỆT' ? 'approved' : 'rejected')
    };
  });
});

// ── Computed ────────────────────────────────────────────
const pendingList = computed(() => mappedRequests.value.filter(r => r.status === 'pending'));
const processedList = computed(() => mappedRequests.value.filter(r => r.status !== 'pending'));

const filteredRequests = computed(() => {
  if (activeTab.value === 'all') return pendingList.value;
  return pendingList.value.filter(r => r.category === activeTab.value);
});

// ── Tabs ────────────────────────────────────────────────
const tabs = computed(() => [
  { key: 'all',       label: 'Tất cả',          icon: 'apps',          count: pendingList.value.length },
  { key: 'nghi_phep', label: 'Nghỉ phép',       icon: 'event_busy',    count: pendingList.value.filter(r => r.category === 'nghi_phep').length },
  { key: 'tuyen_dung',label: 'Tuyển dụng',      icon: 'person_add',    count: pendingList.value.filter(r => r.category === 'tuyen_dung').length },
  { key: 'luong',     label: 'Điều chỉnh',      icon: 'payments',      count: pendingList.value.filter(r => r.category === 'luong').length },
  { key: 'hop_dong',  label: 'Hợp đồng',        icon: 'badge',         count: pendingList.value.filter(r => r.category === 'hop_dong').length },
  { key: 'khac',      label: 'Khác',            icon: 'more_horiz',    count: pendingList.value.filter(r => r.category === 'khac').length },
]);

// ── Actions ─────────────────────────────────────────────
const confirmApprove = async () => {
  if (!selectedItem.value) return;
  const requestId = selectedItem.value.id;
  
  try {
    const isLeaveRequest = selectedItem.value.type === 'Nghỉ phép' || (selectedItem.value.id && selectedItem.value.id.toString().startsWith('REQ'));
    const newStatus = isLeaveRequest ? 'CHỜ_XÁC_NHẬN_HR' : 'ĐÃ_DUYỆT';
    const newStatusText = isLeaveRequest ? 'Chờ HR xác nhận' : 'Đã duyệt';

    await fetch(`http://localhost:3000/leaveRequests/${requestId}`, {
      method: 'PATCH',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        status: newStatus,
        statusText: newStatusText,
        approver_director: 'Ban Giám Đốc'
      })
    });
    
    // Sync mock for internal helpers
    mockLeaveRequests.approve(requestId);
    showToast('approve', `Đã phê duyệt yêu cầu của ${selectedItem.value.name}`);
    await fetchData(); // Refresh data immediately
  } catch (error) {
    console.error('Lỗi phê duyệt:', error);
  }
  closeMainModal();
};

const confirmReject = async () => {
  if (!selectedItem.value) return;
  if (!rejectReason.value.trim()) {
    const ta = document.querySelector('.tt-modal-textarea');
    if (ta) { ta.focus(); ta.classList.add('tt-input-error'); setTimeout(() => ta.classList.remove('tt-input-error'), 800); }
    return;
  }
  
  const requestId = selectedItem.value.id;
  try {
    await fetch(`http://localhost:3000/leaveRequests/${requestId}`, {
      method: 'PATCH',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        status: 'TỪ_CHỐI',
        statusText: 'Đã từ chối',
        notes: rejectReason.value,
        rejectionReason: rejectReason.value
      })
    });
    
    mockLeaveRequests.reject(requestId, rejectReason.value);
    showToast('reject', `Đã từ chối yêu cầu của ${selectedItem.value.name}`);
    await fetchData();
  } catch (error) {
    console.error('Lỗi từ chối:', error);
  }
  closeMainModal();
};
const showToast = (type, msg) => {
  toast.value = { show: true, type, msg };
  setTimeout(() => { toast.value.show = false; }, 3500);
};
</script>

<style>
/* ══════════════════════════════════════════════════════
   UNSCOPED — Dark mode overrides for dynamic Tailwind classes
   (avatar backgrounds, type badge colors, notif level colors)
════════════════════════════════════════════════════════ */

/* ─── Avatar bg colors ─── */
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

/* ─── Type badge bg colors ─── */
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

/* ─── Notification level badge colors ─── */
.dark .tt-notif-level.bg-blue-50      { background-color: rgba(37,99,235,0.22) !important; }
.dark .tt-notif-level.text-blue-700   { color: #93c5fd !important; }
.dark .tt-notif-level.bg-orange-50    { background-color: rgba(249,115,22,0.22) !important; }
.dark .tt-notif-level.text-orange-700 { color: #fdba74 !important; }
.dark .tt-notif-level.bg-green-50     { background-color: rgba(34,197,94,0.22) !important; }
.dark .tt-notif-level.text-green-700  { color: #86efac !important; }
.dark .tt-notif-level.bg-purple-50    { background-color: rgba(168,85,247,0.22) !important; }
.dark .tt-notif-level.text-purple-700 { color: #d8b4fe !important; }

/* ─── Notification action link colors ─── */
.dark .tt-notif-action.text-blue-700  { color: #60a5fa !important; }
.dark .tt-notif-action.text-orange-700{ color: #fb923c !important; }
.dark .tt-notif-action.text-green-700 { color: #4ade80 !important; }
.dark .tt-notif-action.text-purple-700{ color: #c084fc !important; }

/* ─── Text Colors Force Override ─── */
.dark .tt-approval-name { color: #f1f5f9 !important; }
.dark .tt-approval-dept { color: rgba(255,255,255,0.7) !important; }
.dark .tt-approval-title { color: #e2e8f0 !important; }
.dark .tt-approval-time { color: rgba(255,255,255,0.5) !important; }
.dark .tt-section-title { color: #f8fafc !important; }
.dark .tt-urgent-badge { background-color: rgba(239,68,68,0.2) !important; color: #fca5a5 !important; }
</style>

<style scoped>
/* ════════════════════════════════════════════
   DARK MODE — CSS Variable Overrides
   (Tương tự GD_TrangChu.vue — cần có ở mỗi
   component vì Vue scoped CSS chỉ inject khi
   component được mount)
════════════════════════════════════════════ */
/* Light mode — CSS variables */
:root {
  --urgent-item-bg:     linear-gradient(135deg, #fff5f5 0%, #ffffff 100%);
  --urgent-item-border: #fca5a5;
}

/* Dark mode — CSS variables (auto-switch khi .dark on <html>) */
:global(.dark) {
  --bg-card:     #121827;
  --border:      rgba(255, 255, 255, 0.07);
  --text-title:  #f1f5f9;
  --text-body:   #94a3b8;
  --text-muted:  #64748b;
  --bg-hover:    rgba(255, 255, 255, 0.04);
  --bg-page:     #0d1117;
  --shadow-card: 0 10px 40px -10px rgba(0, 0, 0, 0.5);
  --shadow-hover: 0 15px 50px -15px rgba(0, 0, 0, 0.6), 0 0 0 1px rgba(255, 255, 255, 0.1);
  --brand-light: rgba(59, 130, 246, 0.15);
  --brand:       #60a5fa;
  --danger-light: rgba(239, 68, 68, 0.15);
  --danger:      #f87171;
  --danger-text: #fca5a5;
  --success-light: rgba(34, 197, 94, 0.15);
  --success:     #4ade80;
  --success-text: #86efac;
  --warning-light: rgba(245, 158, 11, 0.15);
  --warning:     #fbbf24;
  /* Urgent item màu tối */
  --urgent-item-bg:     linear-gradient(135deg, #2a0d0d 0%, #1e2535 100%);
  --urgent-item-border: rgba(239,68,68,0.45);
}

/* ════════════════════════════════════════════
   LAYOUT
════════════════════════════════════════════ */
.tt-container {
  max-width: 1400px;
  margin: 0 auto;
  padding: 24px;
  display: flex;
  flex-direction: column;
  gap: 20px;
}

/* ── Page Header ── */
.tt-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  flex-wrap: wrap;
}

.tt-header-left {
  display: flex;
  align-items: center;
  gap: 14px;
}

.tt-header-icon-wrap {
  width: 48px;
  height: 48px;
  border-radius: 14px;
  background: linear-gradient(135deg, #1d4ed8, #2563eb);
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 4px 14px rgba(37,99,235,0.35);
  flex-shrink: 0;
}

.tt-header-icon {
  font-size: 24px;
  color: #fff;
  font-variation-settings: 'FILL' 1, 'wght' 600;
}

.tt-page-title {
  font-size: 22px;
  font-weight: 800;
  color: var(--text-title, #111827);
  letter-spacing: -0.03em;
  margin: 0 0 2px;
}

.tt-page-sub {
  font-size: 13px;
  color: var(--text-muted, #6b7280);
  margin: 0;
}

.tt-count-highlight {
  color: #2563eb;
  font-weight: 800;
}

:global(.dark) .tt-count-highlight { color: #60a5fa; }

.tt-header-right {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-wrap: wrap;
}

.tt-stat-chip {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  padding: 6px 14px;
  border-radius: 99px;
  font-size: 12.5px;
  font-weight: 700;
  border: 1px solid transparent;
}

.tt-stat-chip--blue  { background: #eff6ff; color: #1d4ed8; border-color: #bfdbfe; }
.tt-stat-chip--green { background: #f0fdf4; color: #15803d; border-color: #bbf7d0; }
.tt-stat-chip--red   { background: #fff1f2; color: #be123c; border-color: #fecdd3; }

:global(.dark) .tt-stat-chip--blue  { background: rgba(37,99,235,0.15); color: #93c5fd; border-color: rgba(37,99,235,0.3); }
:global(.dark) .tt-stat-chip--green { background: rgba(22,163,74,0.15); color: #86efac; border-color: rgba(22,163,74,0.3); }
:global(.dark) .tt-stat-chip--red   { background: rgba(239,68,68,0.15); color: #fca5a5; border-color: rgba(239,68,68,0.3); }

/* ── Filter Bar ── */
.tt-filter-bar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  flex-wrap: wrap;
}

.tt-tabs {
  display: flex;
  align-items: center;
  gap: 4px;
  background: var(--bg-card, #fff);
  border: 1px solid var(--border, #e5e7eb);
  border-radius: 12px;
  padding: 4px;
  flex-wrap: wrap;
}

:global(.dark) .tt-tabs {
  background: #1e2536;
  border-color: rgba(255,255,255,0.08);
}

.tt-tab {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  padding: 6px 13px;
  border-radius: 9px;
  border: none;
  background: transparent;
  font-size: 12.5px;
  font-weight: 600;
  color: var(--text-body, #4b5563);
  cursor: pointer;
  transition: all 0.18s;
  font-family: inherit;
  white-space: nowrap;
}

.tt-tab:hover { background: var(--bg-hover, #f3f4f6); }

.tt-tab--active {
  background: #2563eb;
  color: #fff;
  box-shadow: 0 2px 8px rgba(37,99,235,0.35);
}

.tt-tab--active:hover { background: #1d4ed8; }

.tt-tab-badge {
  background: rgba(255,255,255,0.3);
  color: inherit;
  font-size: 10px;
  font-weight: 800;
  padding: 1px 6px;
  border-radius: 99px;
  min-width: 18px;
  text-align: center;
}

.tt-tab:not(.tt-tab--active) .tt-tab-badge {
  background: #ef4444;
  color: #fff;
}

.tt-filter-right { display: flex; align-items: center; gap: 6px; }
.tt-filter-label { font-size: 12px; color: var(--text-muted, #9ca3af); font-weight: 600; }

.tt-history-btn {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  padding: 6px 14px;
  border-radius: 8px;
  border: 1px solid var(--border, #e5e7eb);
  background: var(--bg-card, #fff);
  font-size: 12.5px;
  font-weight: 700;
  color: #2563eb;
  cursor: pointer;
  transition: all 0.15s;
  font-family: inherit;
}
.tt-history-btn:hover { border-color: #2563eb; background: #eff6ff; }
:global(.dark) .tt-history-btn { background: #1e2536; border-color: rgba(255,255,255,0.1); color: #60a5fa; }

/* ── Two-Column Grid ── */
.tt-main-grid {
  display: grid;
  grid-template-columns: 1fr 380px;
  gap: 20px;
  align-items: start;
}

@media (max-width: 1050px) {
  .tt-main-grid { grid-template-columns: 1fr; }
}

/* ── Card Base ── */
.tt-card {
  background: var(--bg-card, #fff);
  border: 1px solid var(--border, #e5e7eb);
  border-radius: 18px;
  padding: 20px;
  box-shadow: 0 2px 16px -4px rgba(0,0,0,0.06);
  transition: box-shadow 0.2s;
}

:global(.dark) .tt-card {
  background: #121827 !important;
  border-color: rgba(255,255,255,0.07) !important;
  box-shadow: 0 4px 24px rgba(0,0,0,0.3) !important;
}

.tt-col-right { display: flex; flex-direction: column; gap: 16px; }

/* ── Card Header ── */
.tt-card-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 18px;
  gap: 8px;
}

.tt-card-header-left { display: flex; align-items: center; gap: 10px; }

.tt-section-icon {
  font-size: 20px;
  color: #2563eb;
  background: #eff6ff;
  width: 36px;
  height: 36px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.tt-section-icon--amber {
  color: #d97706;
  background: #fffbeb;
}

:global(.dark) .tt-section-icon { background: rgba(37,99,235,0.15); color: #60a5fa; }
:global(.dark) .tt-section-icon--amber { background: rgba(217,119,6,0.15); color: #fbbf24; }

.tt-section-title {
  font-size: 15px;
  font-weight: 800;
  color: var(--text-title, #111827);
  margin: 0;
  letter-spacing: -0.02em;
}

.tt-view-history-link {
  display: inline-flex;
  align-items: center;
  gap: 3px;
  font-size: 12px;
  font-weight: 700;
  color: #2563eb;
  background: none;
  border: none;
  cursor: pointer;
  white-space: nowrap;
  padding: 4px 0;
  font-family: inherit;
  transition: opacity 0.15s;
}
.tt-view-history-link:hover { opacity: 0.75; }

.tt-notif-count-badge {
  background: #ef4444;
  color: #fff;
  font-size: 11px;
  font-weight: 800;
  padding: 2px 8px;
  border-radius: 99px;
  min-width: 22px;
  text-align: center;
}

/* ── Approval Items ── */
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

:global(.dark) .tt-approval-item:hover {
  background: #243045 !important;
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

.tt-avatar--sm {
  width: 32px;
  height: 32px;
  border-radius: 8px;
  font-size: 11px;
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

/* ── Empty State ── */
.tt-empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 48px 24px;
  gap: 8px;
}
.tt-empty-icon { font-size: 48px; color: #d1d5db; }
.tt-empty-title { font-size: 15px; font-weight: 700; color: var(--text-body, #6b7280); margin: 0; }
.tt-empty-sub   { font-size: 13px; color: var(--text-muted, #9ca3af); margin: 0; }

/* ── History Section ── */
.tt-history-section {
  margin-top: 16px;
  padding-top: 16px;
  border-top: 1px dashed var(--border, #e5e7eb);
}

.tt-history-header {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 12px;
  font-weight: 800;
  color: var(--text-muted, #9ca3af);
  text-transform: uppercase;
  letter-spacing: 0.08em;
  margin-bottom: 10px;
}

.tt-history-item {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 8px 0;
  border-bottom: 1px solid var(--border, #f3f4f6);
}
.tt-history-item:last-child { border-bottom: none; }

.tt-history-info {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
}
.tt-history-name  { font-size: 12.5px; font-weight: 700; color: var(--text-title, #374151); }
.tt-history-title { font-size: 11.5px; color: var(--text-muted, #9ca3af); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

.tt-history-status {
  display: inline-flex;
  align-items: center;
  gap: 3px;
  padding: 3px 10px;
  border-radius: 99px;
  font-size: 11px;
  font-weight: 700;
  white-space: nowrap;
}
.tt-status--approved { background: #f0fdf4; color: #15803d; }
.tt-status--rejected { background: #fff1f2; color: #be123c; }

/* ── Notification Items ── */
.tt-notif-list { display: flex; flex-direction: column; }

.tt-notif-item {
  display: flex;
  gap: 12px;
  padding: 14px 0;
  border-bottom: 1px solid var(--border, #f3f4f6);
  animation: fadeInUp 0.35s ease both;
}
.tt-notif-item:last-child { border-bottom: none; padding-bottom: 0; }

.tt-notif-dot-wrap {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0;
  flex-shrink: 0;
  padding-top: 6px;
}

.tt-notif-dot {
  width: 10px;
  height: 10px;
  border-radius: 50%;
  flex-shrink: 0;
}

.tt-notif-line {
  width: 2px;
  flex: 1;
  min-height: 20px;
  background: var(--border, #e5e7eb);
  margin-top: 4px;
}

.tt-notif-body { flex: 1; min-width: 0; }

.tt-notif-level {
  display: inline-block;
  padding: 2px 8px;
  border-radius: 99px;
  font-size: 9.5px;
  font-weight: 800;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  margin-bottom: 5px;
}

.tt-notif-title {
  font-size: 13px;
  font-weight: 700;
  color: var(--text-title, #111827);
  margin: 0 0 4px;
  line-height: 1.45;
}

.tt-notif-desc {
  font-size: 12px;
  color: var(--text-muted, #6b7280);
  margin: 0 0 8px;
  line-height: 1.5;
}

.tt-notif-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
}

.tt-notif-action {
  font-size: 11.5px;
  font-weight: 700;
  background: none;
  border: none;
  cursor: pointer;
  padding: 0;
  font-family: inherit;
  transition: opacity 0.15s;
  text-decoration: underline;
  text-underline-offset: 2px;
}
.tt-notif-action:hover { opacity: 0.7; }

.tt-notif-time {
  font-size: 11px;
  color: var(--text-muted, #9ca3af);
  font-weight: 500;
  white-space: nowrap;
}

/* ── Performance Card ── */
.tt-performance-card {
  border-radius: 18px;
  padding: 22px;
  background: linear-gradient(135deg, #1e3a8a 0%, #1d4ed8 50%, #2563eb 100%);
  color: #fff;
  position: relative;
  overflow: hidden;
  box-shadow: 0 8px 28px rgba(37,99,235,0.4);
}

.tt-perf-bg-glow {
  position: absolute;
  top: -30px;
  right: -30px;
  width: 140px;
  height: 140px;
  border-radius: 50%;
  background: radial-gradient(circle, rgba(255,255,255,0.15), transparent 70%);
  pointer-events: none;
}

.tt-perf-content { position: relative; z-index: 1; }

.tt-perf-label {
  font-size: 10px;
  font-weight: 800;
  letter-spacing: 0.12em;
  text-transform: uppercase;
  color: rgba(255,255,255,0.65);
  margin: 0 0 10px;
}

.tt-perf-rate-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 12px;
}

.tt-perf-rate {
  font-size: 52px;
  font-weight: 900;
  color: #fff;
  line-height: 1;
  letter-spacing: -0.04em;
}

.tt-perf-ring {
  position: relative;
  width: 60px;
  height: 60px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.tt-perf-svg { width: 60px; height: 60px; }

.tt-perf-ring-icon {
  position: absolute;
  font-size: 20px;
  color: rgba(255,255,255,0.9);
  font-variation-settings: 'FILL' 1;
}

.tt-perf-desc {
  font-size: 12px;
  color: rgba(255,255,255,0.75);
  margin: 0;
  line-height: 1.6;
}

/* ── Animations ── */
.animate-in {
  animation: fadeInUp 0.4s ease both;
}

@keyframes fadeInUp {
  from { opacity: 0; transform: translateY(16px); }
  to   { opacity: 1; transform: translateY(0); }
}

@keyframes pulse-red {
  0%,100% { transform: scale(1); opacity: 1; }
  50%      { transform: scale(1.4); opacity: 0.7; }
}

/* ── Modal ── */
.tt-modal-overlay {
  position: fixed;
  inset: 0;
  z-index: 10001;
  background: rgba(0,0,0,0.45);
  backdrop-filter: blur(4px);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 16px;
}

.tt-modal {
  background: var(--bg-card, #fff);
  border-radius: 20px;
  width: 100%;
  max-width: 480px;
  box-shadow: 0 24px 64px rgba(0,0,0,0.25);
  overflow: hidden;
  display: flex;
  flex-direction: column;
}

:global(.dark) .tt-modal {
  background: #1a2235;
  border: 1px solid rgba(255,255,255,0.1);
}

.tt-modal-header {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 20px 20px 16px;
  border-bottom: 1px solid var(--border, #e5e7eb);
}

.tt-modal-icon {
  width: 48px;
  height: 48px;
  border-radius: 14px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}
.tt-modal-icon--approve { background: #eff6ff; color: #2563eb; }
.tt-modal-icon--reject  { background: #fff1f2; color: #ef4444; }

.tt-modal-title-block { flex: 1; min-width: 0; }
.tt-modal-title    { font-size: 15px; font-weight: 800; color: var(--text-title, #111827); margin: 0 0 2px; }
.tt-modal-subtitle { font-size: 12px; color: var(--text-muted, #9ca3af); margin: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

.tt-modal-close {
  width: 34px; height: 34px;
  border-radius: 10px;
  border: none;
  background: var(--bg-hover, #f3f4f6);
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--text-muted, #6b7280);
  transition: all 0.15s;
  flex-shrink: 0;
}
.tt-modal-close:hover { background: #fee2e2; color: #ef4444; }

.tt-modal-body { padding: 20px; display: flex; flex-direction: column; gap: 14px; }

.tt-modal-info-box {
  background: var(--bg-hover, #f8f9fa);
  border-radius: 12px;
  padding: 14px;
  display: flex;
  flex-direction: column;
  gap: 10px;
  border: 1px solid var(--border, #e5e7eb);
}

.tt-modal-info-row {
  display: flex;
  align-items: baseline;
  gap: 10px;
}
.tt-modal-info-label {
  font-size: 11px;
  font-weight: 800;
  color: var(--text-muted, #9ca3af);
  text-transform: uppercase;
  letter-spacing: 0.06em;
  white-space: nowrap;
  min-width: 90px;
}
.tt-modal-info-val {
  font-size: 13px;
  font-weight: 700;
  color: var(--text-title, #374151);
}

.tt-modal-confirm-text {
  font-size: 13.5px;
  color: var(--text-body, #374151);
  margin: 0;
  line-height: 1.6;
}

.tt-modal-label {
  font-size: 12px;
  font-weight: 800;
  color: var(--text-body, #374151);
  text-transform: uppercase;
  letter-spacing: 0.06em;
}

.tt-modal-textarea {
  width: 100%;
  padding: 12px 14px;
  border-radius: 10px;
  border: 1.5px solid var(--border, #e5e7eb);
  background: var(--bg-hover, #f8f9fa);
  font-size: 13px;
  color: var(--text-title, #374151);
  font-family: inherit;
  resize: none;
  outline: none;
  transition: border-color 0.15s;
  box-sizing: border-box;
}
.tt-modal-textarea:focus    { border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37,99,235,0.1); }
.tt-modal-textarea.tt-input-error { border-color: #ef4444; animation: shake 0.5s; }

.tt-modal-hint { font-size: 11px; color: var(--text-muted, #9ca3af); font-style: italic; margin: 0; }

.tt-modal-footer {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: 10px;
  padding: 16px 20px;
  border-top: 1px solid var(--border, #e5e7eb);
}

.tt-modal-btn-cancel {
  padding: 9px 20px;
  border-radius: 10px;
  border: 1.5px solid var(--border, #e5e7eb);
  background: transparent;
  font-size: 13px;
  font-weight: 700;
  color: var(--text-body, #6b7280);
  cursor: pointer;
  font-family: inherit;
  transition: all 0.15s;
}
.tt-modal-btn-cancel:hover { background: var(--bg-hover, #f3f4f6); }

.tt-modal-btn-confirm {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 9px 22px;
  border-radius: 10px;
  border: none;
  font-size: 13px;
  font-weight: 800;
  cursor: pointer;
  font-family: inherit;
  transition: all 0.15s;
  color: #fff;
}
.tt-modal-btn-confirm--approve {
  background: linear-gradient(135deg, #2563eb, #1d4ed8);
  box-shadow: 0 4px 12px rgba(37,99,235,0.35);
}
.tt-modal-btn-confirm--approve:hover { transform: translateY(-1px); box-shadow: 0 6px 18px rgba(37,99,235,0.45); }
.tt-modal-btn-confirm--reject {
  background: linear-gradient(135deg, #ef4444, #dc2626);
  box-shadow: 0 4px 12px rgba(239,68,68,0.35);
}
.tt-modal-btn-confirm--reject:hover { transform: translateY(-1px); box-shadow: 0 6px 18px rgba(239,68,68,0.45); }

/* ── Toast ── */
.tt-toast {
  position: fixed;
  bottom: 32px;
  left: 50%;
  transform: translateX(-50%);
  z-index: 99999;
  display: inline-flex;
  align-items: center;
  gap: 10px;
  padding: 13px 24px;
  border-radius: 99px;
  font-size: 13.5px;
  font-weight: 700;
  color: #fff;
  box-shadow: 0 8px 28px rgba(0,0,0,0.2);
  white-space: nowrap;
}
.tt-toast--approve { background: linear-gradient(135deg,#16a34a,#15803d); }
.tt-toast--reject  { background: linear-gradient(135deg,#ef4444,#dc2626); }

/* ── Transition: List ── */
.list-fade-enter-active, .list-fade-leave-active { transition: all 0.3s ease; }
.list-fade-enter-from { opacity: 0; transform: translateY(10px); }
.list-fade-leave-to   { opacity: 0; transform: translateX(30px); }

/* ── Transition: Modal ── */
.modal-fade-enter-active, .modal-fade-leave-active { transition: all 0.22s ease; }
.modal-fade-enter-from, .modal-fade-leave-to { opacity: 0; }
.modal-fade-enter-from .tt-modal, .modal-fade-leave-to .tt-modal { transform: scale(0.95) translateY(10px); }

/* ── Transition: Slide Fade ── */
.slide-fade-enter-active, .slide-fade-leave-active { transition: all 0.25s ease; }
.slide-fade-enter-from, .slide-fade-leave-to { opacity: 0; transform: translateY(-8px); }

/* ── Transition: Toast ── */
.toast-slide-enter-active, .toast-slide-leave-active { transition: all 0.3s cubic-bezier(0.34,1.56,0.64,1); }
.toast-slide-enter-from  { opacity: 0; transform: translateX(-50%) translateY(20px); }
.toast-slide-leave-to    { opacity: 0; transform: translateX(-50%) translateY(20px); }

@keyframes shake {
  0%,100% { transform: translateX(0); }
  20%,60% { transform: translateX(-4px); }
  40%,80% { transform: translateX(4px); }
}

/* ════════════════════════════════════════════════
   DARK MODE — Override Tailwind Color Utilities
   (avatar bg, type badge bg, notify level bg)
════════════════════════════════════════════════ */

/* ── Page background & container ── */
:global(.dark) .tt-container {
  background: transparent;
}

/* ── Avatar backgrounds ── */
:global(.dark) .tt-avatar.bg-pink-100   { background: rgba(236,72,153,0.18) !important; }
:global(.dark) .tt-avatar.text-pink-600 { color: #f9a8d4 !important; }
:global(.dark) .tt-avatar.bg-indigo-100  { background: rgba(99,102,241,0.18) !important; }
:global(.dark) .tt-avatar.text-indigo-600{ color: #a5b4fc !important; }
:global(.dark) .tt-avatar.bg-green-100   { background: rgba(34,197,94,0.18) !important; }
:global(.dark) .tt-avatar.text-green-600 { color: #86efac !important; }
:global(.dark) .tt-avatar.bg-amber-100   { background: rgba(245,158,11,0.18) !important; }
:global(.dark) .tt-avatar.text-amber-600 { color: #fcd34d !important; }
:global(.dark) .tt-avatar.bg-teal-100    { background: rgba(20,184,166,0.18) !important; }
:global(.dark) .tt-avatar.text-teal-600  { color: #5eead4 !important; }
:global(.dark) .tt-avatar.bg-purple-100  { background: rgba(168,85,247,0.18) !important; }
:global(.dark) .tt-avatar.text-purple-600{ color: #d8b4fe !important; }
:global(.dark) .tt-avatar.bg-rose-100    { background: rgba(244,63,94,0.18) !important; }
:global(.dark) .tt-avatar.text-rose-600  { color: #fda4af !important; }
:global(.dark) .tt-avatar.bg-sky-100     { background: rgba(14,165,233,0.18) !important; }
:global(.dark) .tt-avatar.text-sky-600   { color: #7dd3fc !important; }

/* ── Type badge backgrounds (tt-approval-type) ── */
:global(.dark) .tt-approval-type.bg-blue-50    { background: rgba(37,99,235,0.18) !important; }
:global(.dark) .tt-approval-type.text-blue-600 { color: #93c5fd !important; }
:global(.dark) .tt-approval-type.bg-indigo-50   { background: rgba(99,102,241,0.18) !important; }
:global(.dark) .tt-approval-type.text-indigo-600{ color: #a5b4fc !important; }
:global(.dark) .tt-approval-type.bg-green-50    { background: rgba(34,197,94,0.18) !important; }
:global(.dark) .tt-approval-type.text-green-600 { color: #86efac !important; }
:global(.dark) .tt-approval-type.bg-amber-50    { background: rgba(245,158,11,0.18) !important; }
:global(.dark) .tt-approval-type.text-amber-600 { color: #fcd34d !important; }
:global(.dark) .tt-approval-type.bg-teal-50     { background: rgba(20,184,166,0.18) !important; }
:global(.dark) .tt-approval-type.text-teal-600  { color: #5eead4 !important; }
:global(.dark) .tt-approval-type.bg-purple-50   { background: rgba(168,85,247,0.18) !important; }
:global(.dark) .tt-approval-type.text-purple-600{ color: #d8b4fe !important; }
:global(.dark) .tt-approval-type.bg-red-50      { background: rgba(239,68,68,0.18) !important; }
:global(.dark) .tt-approval-type.text-red-600   { color: #fca5a5 !important; }

/* ── Notification level backgrounds (tt-notif-level) ── */
:global(.dark) .tt-notif-level.bg-blue-50    { background: rgba(37,99,235,0.2) !important; }
:global(.dark) .tt-notif-level.text-blue-700 { color: #93c5fd !important; }
:global(.dark) .tt-notif-level.bg-orange-50  { background: rgba(249,115,22,0.2) !important; }
:global(.dark) .tt-notif-level.text-orange-700{ color: #fdba74 !important; }
:global(.dark) .tt-notif-level.bg-green-50   { background: rgba(34,197,94,0.2) !important; }
:global(.dark) .tt-notif-level.text-green-700{ color: #86efac !important; }
:global(.dark) .tt-notif-level.bg-purple-50  { background: rgba(168,85,247,0.2) !important; }
:global(.dark) .tt-notif-level.text-purple-700{ color: #d8b4fe !important; }

/* ── Notification action links ── */
:global(.dark) .tt-notif-action.text-blue-700  { color: #60a5fa !important; }
:global(.dark) .tt-notif-action.text-orange-700{ color: #fb923c !important; }
:global(.dark) .tt-notif-action.text-green-700 { color: #4ade80 !important; }
:global(.dark) .tt-notif-action.text-purple-700{ color: #c084fc !important; }

/* ── Urgent badge in dark mode ── */
:global(.dark) .tt-urgent-badge {
  background: rgba(239,68,68,0.2);
  color: #fca5a5;
}

/* ── Urgent dot border fix ── */
:global(.dark) .tt-urgent-dot {
  border-color: #1a2235;
}

/* ── History status badges ── */
:global(.dark) .tt-status--approved { background: rgba(22,163,74,0.2); color: #86efac; }
:global(.dark) .tt-status--rejected { background: rgba(239,68,68,0.2); color: #fca5a5; }

/* ── History divider ── */
:global(.dark) .tt-history-item { border-bottom-color: rgba(255,255,255,0.06); }
:global(.dark) .tt-history-section { border-top-color: rgba(255,255,255,0.08); }
:global(.dark) .tt-history-name  { color: rgba(255,255,255,0.85); }
:global(.dark) .tt-history-title { color: rgba(255,255,255,0.4); }

/* ── Empty state ── */
:global(.dark) .tt-empty-icon  { color: rgba(255,255,255,0.15); }
:global(.dark) .tt-empty-title { color: rgba(255,255,255,0.5); }
:global(.dark) .tt-empty-sub   { color: rgba(255,255,255,0.3); }

/* ── Notification item dividers ── */
:global(.dark) .tt-notif-item  { border-bottom-color: rgba(255,255,255,0.07); }
:global(.dark) .tt-notif-line  { background: rgba(255,255,255,0.08); }
:global(.dark) .tt-notif-title { color: rgba(255,255,255,0.9); }
:global(.dark) .tt-notif-desc  { color: rgba(255,255,255,0.45); }
:global(.dark) .tt-notif-time  { color: rgba(255,255,255,0.35); }

/* ── Section title/icon ── */
:global(.dark) .tt-section-title       { color: rgba(255,255,255,0.92); }
:global(.dark) .tt-view-history-link   { color: #60a5fa; }
:global(.dark) .tt-approval-name       { color: rgba(255,255,255,0.92); }
:global(.dark) .tt-approval-dept       { color: rgba(255,255,255,0.45); }
:global(.dark) .tt-approval-title      { color: rgba(255,255,255,0.85); }
:global(.dark) .tt-approval-time       { color: rgba(255,255,255,0.35); }

/* ── Modal dark mode ── */
:global(.dark) .tt-modal-title    { color: rgba(255,255,255,0.92); }
:global(.dark) .tt-modal-subtitle { color: rgba(255,255,255,0.45); }
:global(.dark) .tt-modal-info-box {
  background: rgba(255,255,255,0.04);
  border-color: rgba(255,255,255,0.08);
}
:global(.dark) .tt-modal-info-label { color: rgba(255,255,255,0.4); }
:global(.dark) .tt-modal-info-val   { color: rgba(255,255,255,0.85); }
:global(.dark) .tt-modal-confirm-text { color: rgba(255,255,255,0.75); }
:global(.dark) .tt-modal-label      { color: rgba(255,255,255,0.75); }
:global(.dark) .tt-modal-hint       { color: rgba(255,255,255,0.35); }
:global(.dark) .tt-modal-textarea {
  background: rgba(255,255,255,0.04);
  border-color: rgba(255,255,255,0.1);
  color: rgba(255,255,255,0.9);
}
:global(.dark) .tt-modal-textarea:focus { border-color: #3b82f6; }
:global(.dark) .tt-modal-icon--approve { background: rgba(37,99,235,0.2); color: #60a5fa; }
:global(.dark) .tt-modal-icon--reject  { background: rgba(239,68,68,0.2); color: #f87171; }
:global(.dark) .tt-modal-close { background: rgba(255,255,255,0.06); color: rgba(255,255,255,0.5); }
:global(.dark) .tt-modal-close:hover { background: rgba(239,68,68,0.2); color: #f87171; }
:global(.dark) .tt-modal-btn-cancel {
  border-color: rgba(255,255,255,0.1);
  color: rgba(255,255,255,0.6);
}
:global(.dark) .tt-modal-btn-cancel:hover { background: rgba(255,255,255,0.06); }
:global(.dark) .tt-modal-footer,
:global(.dark) .tt-modal-header { border-color: rgba(255,255,255,0.08); }

/* ── Filter tabs dark hover ── */
:global(.dark) .tt-tab { color: rgba(255,255,255,0.6); }
:global(.dark) .tt-tab:hover { background: rgba(255,255,255,0.06); }

/* ── Page title/subtitle dark ── */
:global(.dark) .tt-page-title { color: rgba(255,255,255,0.95); }
:global(.dark) .tt-page-sub   { color: rgba(255,255,255,0.45); }
</style>
