<template>
  <div class="space-y-5 pb-10 min-h-screen bg-[var(--sys-bg-page)] text-[var(--sys-text-primary)]">
    <!-- Header Area -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 bg-transparent text-left px-1">
      <div class="bg-transparent text-left">
        <h1 class="text-xl font-semibold text-[var(--sys-text-primary)] mb-1">Phê duyệt Tập trung</h1>
        <p class="text-sm text-[var(--sys-text-secondary)]">Thẩm định đa kênh: Nghỉ phép, Tăng ca (OT), Công tác và các Đề xuất nội bộ.</p>
      </div>
      <div class="bg-transparent hidden xl:block shrink-0">
        <div class="inline-flex items-center gap-2 px-3 h-8 bg-[var(--sys-brand-soft)] rounded border border-[var(--sys-brand-border)] font-black text-[10px] text-[var(--sys-brand-solid)] uppercase tracking-widest shadow-sm">
          <span class="w-1.5 h-1.5 rounded-full bg-[var(--sys-brand-solid)] animate-pulse shadow-sm"></span>
          Centralized Decision Hub
        </div>
      </div>
    </div>

    <!-- Toolbar Section (Directive 1: h-9, mb-5) -->
    <div class="flex flex-col md:flex-row items-center gap-3 mb-5 px-1 bg-transparent justify-between">
      <div class="flex flex-1 items-center gap-3 w-full md:w-auto bg-transparent">
        <!-- Search Bar (w-72) -->
        <div class="relative w-full md:w-80 group shrink-0 h-9">
          <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-[18px] text-[var(--sys-brand-solid)] font-bold">search</span>
          <input 
            v-model="searchQuery"
            type="text" 
            placeholder="Tên nhân viên, mã định danh..." 
            class="w-full h-full pl-9 pr-4 rounded border border-[var(--sys-border-strong)] bg-white text-[12px] font-bold text-[var(--sys-text-primary)] focus:outline-none focus:border-[var(--sys-brand-solid)] transition-all placeholder:font-normal placeholder:opacity-50"
          >
        </div>
        <div class="w-48 shrink-0 bg-transparent h-9">
          <Dropdown v-model="filterType" :options="typeOptions" class="h-full text-[11px] font-bold" />
        </div>
      </div>
      
      <!-- Tabs Navigation (Compact & Professional) -->
      <div class="flex items-center gap-1 bg-white p-1 rounded-md border border-[var(--sys-border-subtle)] shadow-sm h-10 shrink-0">
        <button 
          v-for="tab in tabs" 
          :key="tab.id"
          @click="activeTab = tab.id"
          :class="[
            'px-4 h-full rounded-md text-[12px] font-semibold transition-all flex items-center gap-2 whitespace-nowrap active:scale-95',
            activeTab === tab.id ? 'bg-[var(--sys-brand-soft)] text-[var(--sys-brand-solid)] shadow-sm' : 'text-[var(--sys-text-secondary)] hover:bg-[var(--sys-bg-hover)]'
          ]"
        >
          {{ tab.label }}
          <span v-if="getTabCount(tab.id)" :class="[
            'px-1.5 py-0.5 rounded-md text-[10px] font-bold flex items-center justify-center min-w-[18px]',
            activeTab === tab.id ? 'bg-[var(--sys-brand-solid)] text-white' : 'bg-[var(--sys-danger-solid)] text-white'
          ]">
            {{ getTabCount(tab.id) }}
          </span>
        </button>
      </div>
    </div>

    <!-- Requests Grid Layout (Directive 3 & 5: Card Data Density) -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5 px-1 bg-transparent">
      <transition-group name="grid-anim">
        <div v-for="request in filteredRequests" :key="request.id" 
          @click="openDetailModal(request)"
          class="bg-white p-4 rounded-lg border border-[var(--sys-border-subtle)] shadow-sm hover:border-[var(--sys-brand-solid)] hover:shadow-md transition-all flex flex-col group relative overflow-hidden cursor-pointer group/card hover:-translate-y-1">
          
          <!-- Top Row: Icon/Avatar + Basic Info -->
          <div class="flex items-start gap-4 mb-4">
            <div :class="[
              'w-10 h-10 rounded-md flex items-center justify-center shrink-0 border border-white/20 shadow-sm transition-all group-hover/card:scale-110',
              getIconWrapperClass(request)
            ]">
              <span class="material-symbols-outlined text-xl font-bold">{{ request.icon }}</span>
            </div>
            <div class="flex-1 bg-transparent text-left overflow-hidden">
              <div class="flex items-center justify-between gap-1 mb-0.5">
                <span class="text-[13px] font-semibold text-[var(--sys-text-primary)] group-hover/card:text-[var(--sys-brand-solid)] transition-colors whitespace-nowrap">{{ request.employeeName }}</span>
                <span class="text-[10px] font-bold text-[var(--sys-text-disabled)] opacity-40">#{{ request.employeeId.split('-')[1] }}</span>
              </div>
              <div class="flex items-center gap-2 mb-1">
                <span class="px-1.5 py-0.5 bg-[var(--sys-brand-soft)] text-[var(--sys-brand-solid)] border border-[var(--sys-brand-border)] rounded text-[9px] font-bold uppercase tracking-tight">{{ request.department }}</span>
                <p class="text-[11px] font-medium text-[var(--sys-text-secondary)] opacity-80 leading-tight truncate">{{ request.title }}</p>
              </div>
            </div>
          </div>

          <!-- Middle Content: Reason & Metadata -->
          <div class="flex-1 space-y-4 mb-5">
            <div class="p-3 bg-[var(--sys-bg-page)] rounded border border-[var(--sys-border-subtle)] shadow-inner min-h-[64px] relative">
               <span class="absolute -top-2 left-2 px-2 bg-white text-[9px] font-black text-[var(--sys-text-disabled)] uppercase tracking-widest border rounded">Lý do</span>
              <p class="text-[12px] text-[var(--sys-text-secondary)] leading-[1.4] font-bold italic line-clamp-2 opacity-80 pt-1">"{{ request.reason }}"</p>
            </div>
            
            <div class="flex flex-col gap-2.5 bg-transparent text-left">
              <div class="flex items-center gap-2.5 text-[11px] font-extrabold text-[var(--sys-text-secondary)] opacity-60 uppercase tracking-tight">
                <span class="material-symbols-outlined text-[18px] text-[var(--sys-brand-solid)] font-bold">calendar_today</span>
                {{ request.dateRange }}
              </div>
              <div class="flex items-center gap-2.5 text-[11px] font-black text-[var(--sys-brand-solid)] uppercase tracking-[0.05em]">
                <span class="material-symbols-outlined text-[18px] font-bold">timer</span>
                Thời lượng: <span class="bg-[var(--sys-brand-soft)] px-1.5 py-0.2 rounded border border-[var(--sys-brand-border)] ml-1">{{ request.duration }}</span>
              </div>
            </div>
          </div>

          <!-- Bottom Row: Decisions / Status (Directive 2: No-wrap Fix) -->
          <div class="pt-4 border-t border-[var(--sys-border-subtle)] border-dashed mt-auto">
            <template v-if="request.status === 'pending'">
              <div class="flex gap-1 w-full bg-transparent">
                <button @click.stop="handleReject(request)" class="flex-1 h-9 px-1 text-[10px] font-bold text-[var(--sys-text-secondary)] hover:text-[var(--sys-danger-text)] hover:bg-[var(--sys-danger-soft)] rounded-md border border-[var(--sys-border-subtle)] bg-white uppercase transition-all whitespace-nowrap active:scale-95 flex items-center justify-center gap-1">
                   <span class="material-symbols-outlined text-[16px]">do_not_disturb_on</span>
                   TỪ CHỐI
                </button>
                <button @click.stop="handleApprove(request)" class="flex-[1.6] h-9 px-1 bg-[var(--sys-brand-solid)] text-white rounded-md text-[10px] font-bold hover:brightness-110 shadow-lg flex items-center justify-center gap-1 uppercase transition-all whitespace-nowrap">
                   <span class="material-symbols-outlined text-[16px] font-bold text-white">task_alt</span>
                   PHÊ DUYỆT
                </button>
              </div>
            </template>
            <div v-else :class="[
              'w-full h-9 rounded-md text-[10px] font-bold border flex items-center justify-center gap-2 transition-all uppercase tracking-wider shadow-sm',
              request.status === 'approved' ? 'bg-[var(--sys-success-soft)] text-[var(--sys-success-text)] border-[var(--sys-success-border)]' : 'bg-[var(--sys-danger-soft)] text-[var(--sys-danger-text)] border-[var(--sys-danger-border)]'
            ]">
              <span class="material-symbols-outlined text-[18px] font-bold">{{ request.status === 'approved' ? 'check_circle' : 'cancel' }}</span>
              {{ request.status === 'approved' ? 'CHÁP THUẬN' : 'BÁC BỎ' }}
            </div>
          </div>
        </div>
      </transition-group>
    </div>

    <!-- Detail Modal (Progressive Disclosure) -->
    <Teleport to="body">
      <Transition name="slide-up">
        <div v-if="showDetailModal" class="fixed inset-0 z-[10001] flex items-center justify-center p-4">
          <div class="fixed inset-0 bg-black/40 backdrop-blur-sm" @click="closeDetailModal"></div>
          <div class="relative max-w-2xl w-full bg-[var(--sys-bg-surface)] rounded-xl shadow-2xl p-6 overflow-hidden flex flex-col text-left">
            <!-- Modal Header -->
            <div class="flex items-center gap-4 mb-6">
              <div :class="[
                'w-14 h-14 rounded-lg flex items-center justify-center shadow-sm border border-[var(--sys-border-subtle)]',
                getIconWrapperClass(selectedRequest)
              ]">
                <span class="material-symbols-outlined text-2xl font-bold">{{ selectedRequest?.icon }}</span>
              </div>
              <div class="flex-1">
                <h3 class="text-lg font-semibold text-[var(--sys-text-primary)] leading-tight mb-0.5">{{ selectedRequest?.employeeName }}</h3>
                <p class="text-xs font-bold text-[var(--sys-brand-solid)] uppercase tracking-tight opacity-70">{{ selectedRequest?.employeeId }} • {{ selectedRequest?.title }}</p>
              </div>
              <button @click="closeDetailModal" class="w-10 h-10 rounded-full hover:bg-[var(--sys-bg-hover)] text-[var(--sys-text-secondary)] flex items-center justify-center transition-all">
                <span class="material-symbols-outlined">close</span>
              </button>
            </div>

            <!-- Modal Body (Grid) -->
            <div class="grid grid-cols-2 gap-6 mb-6">
              <div class="space-y-1">
                <span class="text-[10px] font-black text-[var(--sys-text-disabled)] uppercase tracking-widest leading-none">Phòng ban đơn vị</span>
                <p class="text-sm font-bold text-[var(--sys-brand-solid)] uppercase tracking-tight">{{ selectedRequest?.department }}</p>
              </div>
              <div class="space-y-1">
                <span class="text-[10px] font-black text-[var(--sys-text-disabled)] uppercase tracking-widest leading-none">Loại đề xuất</span>
                <p class="text-sm font-bold text-[var(--sys-text-primary)]">{{ selectedRequest?.title }}</p>
              </div>
              <div class="space-y-1">
                <span class="text-[10px] font-black text-[var(--sys-text-disabled)] uppercase tracking-widest leading-none">Mã nhân sự</span>
                <p class="text-sm font-bold text-[var(--sys-text-primary)]">{{ selectedRequest?.employeeId }}</p>
              </div>
              <div class="space-y-1">
                <span class="text-[10px] font-black text-[var(--sys-text-disabled)] uppercase tracking-widest leading-none">Khoảng thời gian</span>
                <p class="text-sm font-bold text-[var(--sys-text-primary)]">{{ selectedRequest?.dateRange }}</p>
              </div>
              <div class="space-y-1">
                <span class="text-[10px] font-black text-[var(--sys-text-disabled)] uppercase tracking-widest leading-none">Tổng thời lượng</span>
                <p class="text-sm font-black text-[var(--sys-brand-solid)]">{{ selectedRequest?.duration }}</p>
              </div>
            </div>

            <!-- Detailed Reason -->
            <div class="space-y-2 mb-6">
              <span class="text-[10px] font-black text-[var(--sys-text-disabled)] uppercase tracking-widest">Nội dung giải trình</span>
              <div class="bg-[var(--sys-bg-hover)] p-4 rounded-lg border border-[var(--sys-border-subtle)]">
                <p class="text-sm text-[var(--sys-text-secondary)] leading-relaxed font-medium italic">
                  "{{ selectedRequest?.reason }}"
                </p>
              </div>
            </div>

            <!-- Actions -->
            <div v-if="selectedRequest?.status === 'pending'" class="flex justify-end gap-3 mt-auto pt-6 border-t border-[var(--sys-border-subtle)] border-dashed">
              <button @click="handleRejectFromDetail" class="h-10 px-5 text-[11px] font-bold text-[var(--sys-danger-text)] hover:bg-[var(--sys-danger-soft)] rounded-md transition-all uppercase tracking-widest border border-[var(--sys-danger-border)]">Từ chối</button>
              <button @click="handleApproveFromDetail" class="h-10 px-6 bg-[var(--sys-brand-solid)] text-white rounded-md font-extrabold text-[11px] hover:brightness-110 shadow-lg transition-all uppercase tracking-widest flex items-center gap-2">
                <span class="material-symbols-outlined text-[18px]">task_alt</span>
                Chấp thuận ngay
              </button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>

    <!-- Empty State -->
    <div v-if="filteredRequests.length === 0" class="flex flex-col items-center justify-center py-24 bg-[var(--sys-bg-page)]/20 rounded-lg border-2 border-[var(--sys-border-subtle)] border-dashed">
      <div class="w-16 h-16 rounded-full bg-white flex items-center justify-center mb-4 border border-[var(--sys-border-subtle)] shadow-xl transform animate-bounce">
        <span class="material-symbols-outlined text-[var(--sys-brand-solid)] text-3xl font-black">inbox</span>
      </div>
      <p class="text-[12px] font-black text-[var(--sys-text-disabled)] uppercase tracking-[0.3em] opacity-40 leading-none">Inbox Empty</p>
      <p class="text-[10px] font-bold text-[var(--sys-text-disabled)] uppercase tracking-widest mt-2 opacity-30 italic leading-none">Không có yêu cầu chờ thẩm định.</p>
    </div>

    <!-- Reject Modal (Improved Widen & Grid Form) -->
    <Teleport to="body">
      <Transition name="modal-fade">
        <div v-if="showRejectModal" class="fixed inset-0 z-[10000] flex items-center justify-center p-4">
          <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" @click="closeRejectModal"></div>
          <div class="relative bg-[var(--sys-bg-surface-elevated)] border border-[var(--sys-border-subtle)] w-full max-w-2xl rounded-lg shadow-2xl overflow-hidden flex flex-col text-left motion-safe:animate-zoomIn">
            <!-- Modal Header -->
            <div class="px-6 h-14 border-b border-[var(--sys-border-subtle)] flex items-center justify-between bg-[var(--sys-bg-surface)]">
              <div class="bg-transparent text-left flex items-center gap-4">
                <div class="w-10 h-10 rounded bg-[var(--sys-danger-soft)] text-[var(--sys-danger-text)] flex items-center justify-center border border-[var(--sys-danger-border)] shrink-0">
                  <span class="material-symbols-outlined text-xl">rule</span>
                </div>
                <div>
                  <h3 class="text-sm font-extrabold text-[var(--sys-text-primary)] m-0 uppercase tracking-tight leading-none">Thẩm định bác bỏ hồ sơ</h3>
                  <p class="text-[10px] text-[var(--sys-text-secondary)] font-bold mt-1 uppercase tracking-widest opacity-60">Nhân sự: <span class="text-[var(--sys-brand-solid)] font-black">{{ selectedRequest?.employeeName }}</span></p>
                </div>
              </div>
              <button @click="closeRejectModal" class="w-8 h-8 flex items-center justify-center rounded hover:bg-[var(--sys-bg-hover)] text-[var(--sys-text-secondary)] transition-all">
                <span class="material-symbols-outlined text-xl">close</span>
              </button>
            </div>

            <!-- Modal Body (Grid Form) -->
            <div class="p-6 space-y-6 bg-transparent">
              <div class="grid grid-cols-2 gap-5">
                <div class="space-y-1.5">
                  <label class="text-[10px] font-black text-[var(--sys-text-primary)] uppercase tracking-widest ml-1 opacity-60">Mã tham chiếu</label>
                  <input type="text" :value="selectedRequest?.employeeId" readonly class="w-full h-10 px-3 bg-[var(--sys-bg-page)] border border-[var(--sys-border-strong)] rounded text-[13px] font-black text-[var(--sys-text-disabled)] outline-none uppercase tracking-widest shadow-inner">
                </div>
                <div class="space-y-1.5">
                  <label class="text-[10px] font-black text-[var(--sys-text-primary)] uppercase tracking-widest ml-1 opacity-60">Phân loại nghiệp vụ</label>
                  <input type="text" :value="selectedRequest?.title" readonly class="w-full h-10 px-3 bg-[var(--sys-bg-page)] border border-[var(--sys-border-strong)] rounded text-[13px] font-black text-[var(--sys-text-disabled)] outline-none uppercase tracking-widest shadow-inner">
                </div>
              </div>
              
              <div class="space-y-1.5">
                <label class="text-[10px] font-black text-[var(--sys-text-primary)] uppercase tracking-widest ml-1">Nội dung phản hồi thẩm định *</label>
                <textarea 
                  v-model="rejectReason" 
                  rows="4" 
                  placeholder="Xác định nguyên nhân bác bỏ hồ sơ chi tiết..." 
                  class="w-full px-4 py-3 bg-[var(--sys-bg-page)] border border-[var(--sys-border-strong)] rounded text-[13px] font-bold text-[var(--sys-text-primary)] focus:border-[var(--sys-danger-solid)] outline-none transition-all resize-none shadow-inner placeholder:font-normal placeholder:italic placeholder:opacity-40"
                ></textarea>
                <p class="text-[9px] font-bold text-[var(--sys-danger-text)] uppercase tracking-widest opacity-60 italic">* Thông tin này sẽ được gửi trực tiếp đến hộp thư của nhân sự.</p>
              </div>
            </div>

            <!-- Modal Footer -->
            <div class="px-6 h-16 border-t border-[var(--sys-border-subtle)] bg-[var(--sys-bg-surface)] flex justify-end items-center gap-3">
              <button @click="closeRejectModal" class="h-10 px-6 text-[11px] font-bold text-[var(--sys-text-secondary)] hover:bg-[var(--sys-bg-hover)] rounded-md transition-all uppercase tracking-widest">Hủy bỏ</button>
              <button @click="confirmReject" class="h-10 px-8 bg-[var(--sys-danger-solid)] text-white rounded-md font-extrabold text-[11px] hover:brightness-110 shadow-lg transition-all uppercase tracking-widest flex items-center gap-2 active:scale-95">
                <span class="material-symbols-outlined text-[18px] font-bold">verified_user</span>
                XÁC NHẬN BÁC BỎ
              </button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>
  </div>
</template>

<script setup>
/**
 * HỆ THỐNG PHÊ DUYỆT TẬP TRUNG (ADMIN) - ENTERPRISE REFINEMENT V3
 * Tuân thủ 7 Chỉ thị UI/UX SaaS Final (Directive 1, 2, 3, 4, 6)
 * Fix: "Không cho rớt chữ" - Áp dụng whitespace-nowrap & Flex dynamic layout.
 */
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { useRoute } from 'vue-router';
import Dropdown from '@/components/Dropdown.vue';
import { useConfirm } from '@/composables/useConfirm';
import { BE_API_BASE, getAccessToken } from '@/services/runtimeConfig.js';
import { handleUnauthorized } from '@/services/session.js';

const { showAlert } = useConfirm();
const route = useRoute();

const activeTab = ref('pending');
const searchQuery = ref('');
const filterType = ref('ALL');
const showRejectModal = ref(false);
const showDetailModal = ref(false);
const rejectReason = ref('');
const selectedRequest = ref(null);
const liveRequests = ref([]);
const employeeRows = ref([]);
const departmentRows = ref([]);
const requestTypeRows = ref([]);

const authHeaders = () => {
  const token = getAccessToken();
  if (!token) throw new Error('Thiếu access token');
  return {
    Authorization: `Bearer ${token}`,
    'Content-Type': 'application/json',
  };
};

const apiRequest = async (path, { method = 'GET', body } = {}) => {
  const response = await fetch(`${BE_API_BASE}${path}`, {
    method,
    headers: authHeaders(),
    body: body ? JSON.stringify(body) : undefined,
  });
  if (response.status === 401) {
    handleUnauthorized();
    throw new Error('Phiên đăng nhập đã hết hạn');
  }
  const payload = await response.json().catch(() => ({}));
  if (!response.ok || payload?.success === false) {
    throw new Error(payload?.message || `Request failed (${response.status})`);
  }
  return payload?.data ?? [];
};

const loadData = async () => {
  try {
    const [requests, employees, departments, requestTypes] = await Promise.all([
      apiRequest('/requests?page=1&per_page=500'),
      apiRequest('/employees?page=1&per_page=1000'),
      apiRequest('/departments?page=1&per_page=300'),
      apiRequest('/request-types?page=1&per_page=200'),
    ]);
    liveRequests.value = Array.isArray(requests) ? requests : [];
    employeeRows.value = Array.isArray(employees) ? employees : [];
    departmentRows.value = Array.isArray(departments) ? departments : [];
    requestTypeRows.value = Array.isArray(requestTypes) ? requestTypes : [];
  } catch (error) {
    console.error('Lỗi khi tải dữ liệu phê duyệt:', error);
    await showAlert('Không tải được dữ liệu phê duyệt', error?.message || 'Có lỗi xảy ra khi tải danh sách yêu cầu.');
    liveRequests.value = [];
  }
};

let pollInterval = null;
onMounted(() => {
  void loadData().then(() => {
    const focusId = Number(route.query.request_id || 0);
    if (!focusId) return;
    const found = requests.value.find((item) => Number(item.id) === focusId);
    if (found) {
      selectedRequest.value = found;
      showDetailModal.value = true;
    }
  });
  pollInterval = setInterval(loadData, 15000);
});

onUnmounted(() => {
  if (pollInterval) clearInterval(pollInterval);
});

const requests = computed(() => {
  const requestTypeMap = new Map(
    requestTypeRows.value.map((item) => [Number(item.request_type_id || item.requestTypeId || 0), item]),
  );
  const employeeMap = new Map(
    employeeRows.value.map((item) => [Number(item.employee_id || item.employeeId || 0), item]),
  );
  const departmentMap = new Map(
    departmentRows.value.map((item) => [Number(item.department_id || item.departmentId || 0), item]),
  );

  const rawList = [...liveRequests.value].sort((a, b) => {
    const da = String(a.request_date || a.requestDate || '');
    const db = String(b.request_date || b.requestDate || '');
    return db.localeCompare(da);
  });

  return rawList.map((req) => {
    const requesterId = Number(req.requester_id || req.requesterId || req.employee_id || req.employeeId || 0);
    const employee = employeeMap.get(requesterId) || null;
    const departmentId = Number(
      employee?.department_id || req.department_id || req.departmentId || 0,
    );
    const department = departmentMap.get(departmentId) || null;
    const requestTypeId = Number(req.request_type_id || req.requestTypeId || 0);
    const typeObj = requestTypeMap.get(requestTypeId) || null;

    const statusRaw = String(req.status || '').toUpperCase();
    let mappedStatus = 'pending';
    if (['ĐÃ_DUYỆT', 'HOÀN_THÀNH', 'COMPLETED', 'APPROVED'].includes(statusRaw)) mappedStatus = 'approved';
    if (['TỪ_CHỐI', 'REJECTED', 'ĐÃ_HỦY', 'CANCELED'].includes(statusRaw)) mappedStatus = 'rejected';

    const reqTypeCode = String(typeObj?.request_type_code || '').toUpperCase();
    const reqTypeName = String(typeObj?.request_type_name || req.request_type_name || 'Yêu cầu');
    const reqTitle = reqTypeName || 'Yêu cầu';

    let icon = 'event_note'; 
    if (reqTypeCode.includes('LEAVE') || reqTitle.toUpperCase().includes('NGHỈ')) icon = 'event_busy';
    if (reqTypeCode.includes('OT') || reqTitle.toUpperCase().includes('TĂNG CA')) icon = 'schedule';
    if (reqTypeCode.includes('ATTEND') || reqTitle.toUpperCase().includes('CHẤM CÔNG') || reqTitle.toUpperCase().includes('CHAM CONG')) icon = 'edit_calendar';
    if (reqTypeCode.includes('TRIP') || reqTitle.toUpperCase().includes('CÔNG TÁC')) icon = 'flight';
    if (reqTypeCode.includes('PAY') || reqTitle.toUpperCase().includes('TẠM ỨNG')) icon = 'payments';

    const requestDate = String(req.request_date || '').slice(0, 10);
    const fromDate = String(req.from_date || '').slice(0, 10);
    const toDate = String(req.to_date || '').slice(0, 10);
    const dateRange = fromDate && toDate ? `${fromDate} - ${toDate}` : (requestDate || 'Hôm nay');
    const duration = Number(req.duration || 0) > 0 ? `${req.duration} ngày` : 'N/A';

    return {
      id: Number(req.request_id || req.requestId || req.id || 0),
      employeeName: req.requester_name || employee?.full_name || 'N/A',
      employeeId: employee?.employee_code || 'N/A',
      department: (department?.department_name || employee?.department_name || 'N/A').toUpperCase(),
      title: reqTitle,
      icon: icon,
      dateRange,
      duration,
      reason: req.reason || req.notes || 'Không có ghi chú',
      status: mappedStatus,
      raw: req,
    };
  });
});

const openDetailModal = (r) => {
  selectedRequest.value = r;
  showDetailModal.value = true;
};

const closeDetailModal = () => {
  showDetailModal.value = false;
};

const handleApproveFromDetail = () => {
  if (selectedRequest.value) {
    handleApprove(selectedRequest.value);
    closeDetailModal();
  }
};

const handleRejectFromDetail = () => {
  if (selectedRequest.value) {
    const r = selectedRequest.value;
    closeDetailModal();
    handleReject(r);
  }
};

const typeOptions = [
  { label: 'TẤT CẢ LOẠI HÌNH', value: 'ALL' },
  { label: 'NGHỈ PHÉP (LEAVE)', value: 'event_busy' },
  { label: 'TĂNG CA (OT)', value: 'schedule' },
  { label: 'CÔNG TÁC (WORK TRIP)', value: 'flight' },
  { label: 'ĐIỀU CHỈNH CÔNG', value: 'edit_calendar' }
];

const tabs = ref([
  { id: 'pending', label: 'Chờ phê duyệt' },
  { id: 'approved', label: 'Đã duyệt' },
  { id: 'rejected', label: 'Đã bác bỏ' },
]);

const handleReject = (r) => { selectedRequest.value = r; showRejectModal.value = true; };
const closeRejectModal = () => { showRejectModal.value = false; rejectReason.value = ''; selectedRequest.value = null; };

const tryPatchRequestStatus = async (id, payloadCandidates) => {
  let lastError = null;
  for (const payload of payloadCandidates) {
    try {
      await apiRequest(`/requests/${id}`, {
        method: 'PATCH',
        body: payload,
      });
      return true;
    } catch (error) {
      lastError = error;
    }
  }
  throw lastError || new Error('Không thể cập nhật trạng thái yêu cầu.');
};

const handleApprove = async (r) => {
  try {
    const id = Number(r.id || r.raw?.request_id || r.raw?.requestId || r.raw?.id || 0);
    if (!id) {
      await showAlert('Thiếu dữ liệu', 'Không xác định được mã yêu cầu để phê duyệt.');
      return;
    }
    await tryPatchRequestStatus(id, [
      { status: 'ĐÃ_DUYỆT' },
      { status: 'APPROVED' },
      { status: 'COMPLETED' },
    ]);
    await loadData();
    closeDetailModal();
    await showAlert('Thành công', 'Đã phê duyệt yêu cầu.');
  } catch (error) {
    console.error('Lỗi khi duyệt đơn:', error);
    await showAlert('Duyệt thất bại', error?.message || 'Không thể phê duyệt yêu cầu.');
  }
};

const confirmReject = async () => {
  if (!rejectReason.value) { 
    await showAlert('THIẾU DỮ LIỆU', 'Vui lòng xác định nội dung bác bỏ hồ sơ!'); 
    return; 
  }
  if (selectedRequest.value) {
    try {
      const id = Number(
        selectedRequest.value.id
        || selectedRequest.value.raw?.request_id
        || selectedRequest.value.raw?.requestId
        || selectedRequest.value.raw?.id
        || 0
      );
      if (!id) {
        await showAlert('Thiếu dữ liệu', 'Không xác định được request_id.');
        return;
      }
      await tryPatchRequestStatus(id, [
        { status: 'TỪ_CHỐI', notes: rejectReason.value },
        { status: 'REJECTED', notes: rejectReason.value },
        { status: 'CANCELED', notes: rejectReason.value },
      ]);
      await loadData();
      closeRejectModal();
      closeDetailModal();
      await showAlert('Đã từ chối', 'Yêu cầu đã được từ chối và lưu lý do.');
    } catch (error) {
      console.error('Lỗi khi bác bỏ đơn:', error);
      await showAlert('Từ chối thất bại', error?.message || 'Không thể từ chối yêu cầu.');
    }
  }
};

const getIconWrapperClass = (request) => {
  if (request.status === 'approved') return 'bg-[var(--sys-success-soft)] text-[var(--sys-success-text)] border-[var(--sys-success-border)]';
  if (request.status === 'rejected') return 'bg-[var(--sys-danger-soft)] text-[var(--sys-danger-text)] border-[var(--sys-danger-border)]';
  if (request.icon === 'event_busy') return 'bg-[var(--sys-warning-soft)] text-[var(--sys-warning-text)] border-[var(--sys-warning-border)]';
  return 'bg-[var(--sys-brand-soft)] text-[var(--sys-brand-solid)] border-[var(--sys-brand-border)]';
};

const filteredRequests = computed(() => {
  let list = requests.value.filter(r => r.status === activeTab.value);
  if (filterType.value !== 'ALL') { list = list.filter(r => r.icon === filterType.value); }
  if (searchQuery.value) {
    const q = searchQuery.value.toLowerCase();
    list = list.filter(r => r.employeeName.toLowerCase().includes(q) || r.employeeId.toLowerCase().includes(q));
  }
  return list;
});

const getTabCount = (id) => requests.value.filter(r => r.status === id).length;
</script>

<style scoped>
.grid-anim-enter-active, .grid-anim-leave-active { transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1); }
.grid-anim-enter-from, .grid-anim-leave-to { opacity: 0; transform: scale(0.9); }
@keyframes zoomIn { from { opacity: 0; transform: scale(0.97); } to { opacity: 1; transform: scale(1); } }
.animate-zoomIn { animation: zoomIn 0.3s cubic-bezier(0.16, 1, 0.3, 1) forwards; }
.modal-fade-enter-active, .modal-fade-leave-active { transition: opacity 0.2s ease; }
.modal-fade-enter-from, .modal-fade-leave-to { opacity: 0; }

/* Slide-up Transition for Detail Modal */
.slide-up-enter-active, .slide-up-leave-active {
  transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
}
.slide-up-enter-from, .slide-up-leave-to {
  opacity: 0;
  transform: translateY(40px);
}
</style>
