<template>
  <div class="space-y-6 pb-8">
    <!-- Header Area -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 bg-transparent text-left">
      <div class="bg-transparent text-left">
        <h1 class="text-xl font-semibold text-[var(--sys-text-primary)] mb-1">Quản lý Hợp đồng Lao động</h1>
        <p class="text-sm text-[var(--sys-text-secondary)]">Quản lý hồ sơ pháp lý, điều khoản lao động và theo dõi thời hạn hợp đồng.</p>
      </div>
      <div class="flex flex-col md:flex-row items-center gap-3 flex-1 w-full max-w-3xl bg-transparent">
        <!-- Search Bar -->
        <div class="relative flex-1 w-full group">
          <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-[20px] text-[var(--sys-brand-solid)]">search</span>
          <input 
            v-model="searchQuery"
            type="text" 
            placeholder="Tìm kiếm số hợp đồng, nhân viên..." 
            class="w-full h-10 pl-10 pr-4 rounded-md bg-[var(--sys-bg-surface)] border border-[var(--sys-border-strong)] text-sm text-[var(--sys-text-primary)] focus:outline-none focus:border-[var(--sys-brand-solid)] focus:ring-1 focus:ring-[var(--sys-brand-solid)] transition-all placeholder:text-[var(--sys-text-disabled)]"
          >
        </div>
        
        <div class="flex items-center gap-3 w-full md:w-auto bg-transparent shrink-0">
          <Dropdown v-model="filterStatus" :options="statusOptions" class="min-w-[160px] h-10" />
          <button @click="openAddModal" class="h-10 px-4 bg-[var(--sys-brand-solid)] rounded-md font-semibold text-white hover:brightness-90 active:opacity-90 transition-all flex items-center gap-2 text-sm whitespace-nowrap shadow-sm">
            <span class="material-symbols-outlined text-[20px]">contract_edit</span>
            Khởi tạo hợp đồng
          </button>
        </div>
      </div>
    </div>

    <!-- Alert Box -->
    <div v-if="expiringCount > 0" class="bg-[var(--sys-warning-soft)] border border-[var(--sys-warning-border)] rounded-lg p-4 flex items-center gap-4 shadow-sm">
      <div class="w-10 h-10 bg-[var(--sys-warning-solid)] text-white rounded-md flex items-center justify-center shrink-0">
        <span class="material-symbols-outlined text-[24px]">priority_high</span>
      </div>
      <div class="flex-1 text-left bg-transparent">
        <p class="text-[14px] font-semibold text-[var(--sys-warning-text)] m-0">Thông báo đáo hạn hợp đồng</p>
        <p class="text-[13px] text-[var(--sys-warning-text)] m-0 opacity-80">Có {{ expiringCount }} hợp đồng chuẩn bị đáo hạn trong vòng 30 ngày tới. Vui lòng kiểm tra và thực hiện gia hạn.</p>
      </div>
      <button
        class="h-9 px-4 bg-[var(--sys-warning-solid)] text-white rounded-md text-[13px] font-semibold hover:brightness-90 transition-all"
        @click="focusExpiringContracts"
      >
        Xử lý ngay
      </button>
    </div>

    <!-- Content Layout -->
    <div class="grid grid-cols-1 xl:grid-cols-12 gap-6 items-stretch">
      <!-- Left Sidebar: Stats & Timeline -->
      <div class="xl:col-span-3 space-y-6">
        <!-- Stats Card -->
        <div class="bg-[var(--sys-bg-surface)] rounded-lg border border-[var(--sys-border-subtle)] shadow-sm overflow-hidden divide-y divide-[var(--sys-border-subtle)]">
          <div class="px-4 py-3 bg-[var(--sys-bg-page)]">
            <h3 class="text-[12px] font-semibold text-[var(--sys-text-secondary)] uppercase tracking-wider">Thống kê pháp lý</h3>
          </div>
          <div class="p-4 space-y-3">
            <div v-for="stat in contractStats" :key="stat.label"
              class="flex items-center justify-between p-3 bg-[var(--sys-bg-page)] rounded-md border border-[var(--sys-border-subtle)]">
              <span class="text-[12px] font-medium text-[var(--sys-text-secondary)]">{{ stat.label }}</span>
              <span class="text-base font-bold text-[var(--sys-text-primary)]">{{ stat.value }}</span>
            </div>
          </div>
        </div>

        <!-- History Timeline -->
        <div class="bg-[var(--sys-bg-surface)] rounded-lg border border-[var(--sys-border-subtle)] shadow-sm overflow-hidden flex flex-col">
          <div class="px-4 py-3 bg-[var(--sys-bg-page)]">
            <h3 class="text-[12px] font-semibold text-[var(--sys-text-secondary)] uppercase tracking-wider">Nhật ký vận hành</h3>
          </div>
          <div class="p-4 space-y-6 relative ml-2 border-l border-[var(--sys-border-subtle)] border-dashed">
            <div v-for="log in historyLogs" :key="log.id" class="relative group/log pl-6">
              <div :class="`absolute -left-[14px] top-0.5 w-7 h-7 rounded-md border-2 border-white shadow-sm flex items-center justify-center z-10 ${log.bg}`">
                <span class="material-symbols-outlined text-[14px] text-white">{{ log.icon }}</span>
              </div>
              <div class="bg-transparent text-left">
                <p class="text-[13px] font-semibold text-[var(--sys-text-primary)] mb-1">{{ log.content }}</p>
                <p class="text-[11px] text-[var(--sys-text-secondary)]">{{ log.time }} • {{ log.user }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Main Data Table -->
      <div class="xl:col-span-9 bg-[var(--sys-bg-surface)] rounded-lg border border-[var(--sys-border-subtle)] shadow-sm overflow-hidden flex flex-col">
        <div class="overflow-x-auto custom-scrollbar flex-1">
          <table class="w-full text-left border-collapse">
            <thead class="bg-[var(--sys-bg-page)]">
              <tr>
                <th class="px-4 py-3 text-[12px] font-semibold text-[var(--sys-text-secondary)] border-b border-[var(--sys-border-subtle)] uppercase tracking-wider">Số hiệu</th>
                <th class="px-4 py-3 text-[12px] font-semibold text-[var(--sys-text-secondary)] border-b border-[var(--sys-border-subtle)] uppercase tracking-wider">Nhân sự thụ hưởng</th>
                <th class="px-4 py-3 text-[12px] font-semibold text-[var(--sys-text-secondary)] border-b border-[var(--sys-border-subtle)] uppercase tracking-wider">Thời hạn hiệu lực</th>
                <th class="px-4 py-3 text-[12px] font-semibold text-[var(--sys-text-secondary)] border-b border-[var(--sys-border-subtle)] uppercase tracking-wider text-center">Trạng thái</th>
                <th class="px-4 py-3 text-[12px] font-semibold text-[var(--sys-text-secondary)] border-b border-[var(--sys-border-subtle)] uppercase tracking-wider text-right">Quản trị</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-[var(--sys-border-subtle)]">
              <tr v-for="item in filteredContracts" :key="item.id" 
                class="group hover:bg-[var(--sys-bg-hover)] transition-colors cursor-default">
                <td class="px-4 py-3 whitespace-nowrap bg-transparent">
                  <span class="text-[13px] font-semibold text-[var(--sys-brand-solid)] tracking-wide">#{{ item.contract_no }}</span>
                </td>
                <td class="px-4 py-3 whitespace-nowrap bg-transparent">
                  <div class="flex flex-col bg-transparent">
                    <span class="text-[13px] font-semibold text-[var(--sys-text-primary)] transition-colors line-clamp-1 max-w-[200px]">{{ item.employee_name }}</span>
                    <span class="text-[12px] text-[var(--sys-text-secondary)]">{{ item.contract_type.split('_').join(' ') }}</span>
                  </div>
                </td>
                <td class="px-4 py-3 whitespace-nowrap bg-transparent">
                  <div class="flex items-center gap-2 text-[13px] font-medium text-[var(--sys-text-primary)]">
                    <span>{{ item.start_date }}</span>
                    <span class="material-symbols-outlined text-[16px] text-[var(--sys-text-disabled)] shadow-none">arrow_forward</span>
                    <span>{{ item.end_date || 'Vô thời hạn' }}</span>
                  </div>
                </td>
                <td class="px-4 py-3 text-center whitespace-nowrap bg-transparent">
                  <span :class="[
                    'px-2 py-0.5 rounded-md text-[11px] font-semibold inline-flex items-center gap-1.5 border transition-all uppercase tracking-wide',
                    getStatusBadgeClass(item.ui_status)
                  ]">
                    <span class="w-1.5 h-1.5 rounded-full shrink-0" :class="getStatusDotClass(item.ui_status)"></span>
                    {{ item.status.split('_').join(' ') }}
                  </span>
                </td>
                <td class="px-4 py-3 text-right whitespace-nowrap bg-transparent">
                  <div class="flex items-center justify-end gap-1">
                    <button @click="editContract(item)" class="w-8 h-8 flex items-center justify-center rounded-md text-[var(--sys-text-secondary)] hover:bg-[var(--sys-brand-soft)] hover:text-[var(--sys-brand-solid)] transition-all" title="Chỉnh sửa">
                      <span class="material-symbols-outlined text-[18px]">edit_square</span>
                    </button>
                    <button @click="extendContract(item)" class="w-8 h-8 flex items-center justify-center rounded-md text-[var(--sys-text-secondary)] hover:bg-[var(--sys-brand-soft)] hover:text-[var(--sys-brand-solid)] transition-all" title="Gia hạn">
                      <span class="material-symbols-outlined text-[18px]">history_edu</span>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <div class="px-4 py-3 bg-[var(--sys-bg-page)] border-t border-[var(--sys-border-subtle)] flex justify-end">
          <button
            class="text-[12px] font-semibold text-[var(--sys-brand-solid)] hover:opacity-80 transition-opacity flex items-center gap-1"
            @click="resetContractFilters"
          >
            Xem tất cả hợp đồng
            <span class="material-symbols-outlined text-[16px]">arrow_forward</span>
          </button>
        </div>
      </div>
    </div>

    <!-- Modal System -->
    <Teleport to="body">
      <Transition name="modal-fade">
        <div v-if="showModal" class="fixed inset-0 z-[10000] flex items-center justify-center p-4">
          <div class="fixed inset-0 w-screen h-screen bg-black/50 z-[9999]" @click="closeModal"></div>
          <div class="relative z-[10000] bg-[var(--sys-bg-surface-elevated)] border border-[var(--sys-border-subtle)] w-full max-w-2xl max-h-[90vh] rounded-lg shadow-xl overflow-hidden flex flex-col text-left">
            <!-- Modal Header -->
            <div class="px-6 py-4 border-b border-[var(--sys-border-subtle)] flex items-center justify-between bg-[var(--sys-bg-surface)]">
              <div class="bg-transparent text-left flex flex-col">
                <h3 class="text-lg font-semibold text-[var(--sys-text-primary)] m-0 uppercase tracking-tight">{{ editMode ? 'Cập nhật hợp đồng lao động' : 'Tạo mới hợp đồng' }}</h3>
                <p class="text-sm text-[var(--sys-text-secondary)] mt-1">Đảm bảo các thông số tuân thủ Luật Lao động và Quy chế công ty.</p>
              </div>
              <button @click="closeModal" class="w-8 h-8 flex items-center justify-center rounded-md hover:bg-[var(--sys-bg-hover)] transition-all text-[var(--sys-text-secondary)]">
                <span class="material-symbols-outlined text-xl">close</span>
              </button>
            </div>

            <!-- Modal Body -->
            <div class="flex-1 overflow-y-auto p-6 custom-scrollbar bg-transparent">
              <div class="space-y-6 bg-transparent border-none">
                <div class="space-y-1.5 bg-transparent border-none">
                  <label class="text-[13px] font-medium text-[var(--sys-text-primary)] block">Nhân viên thụ hưởng *</label>
                  <input v-model="form.employee_name" type="text" placeholder="Tìm tên hoặc mã nhân viên..." class="w-full h-10 px-3 bg-[var(--sys-bg-page)] border border-[var(--sys-border-strong)] rounded-md text-sm text-[var(--sys-text-primary)] focus:border-[var(--sys-brand-solid)] outline-none transition-all">
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 bg-transparent border-none">
                  <div class="space-y-1.5 bg-transparent border-none">
                    <label class="text-[13px] font-medium text-[var(--sys-text-primary)] block">Loại hợp đồng *</label>
                    <Dropdown v-model="form.contract_type" :options="contractTypeOptions" class="w-full h-10" />
                  </div>
                  <div class="space-y-1.5 bg-transparent border-none">
                    <label class="text-[13px] font-medium text-[var(--sys-text-primary)] block">Số hiệu hợp đồng *</label>
                    <input v-model="form.contract_no" type="text" placeholder="VD: HD-2024-001" class="w-full h-10 px-3 bg-[var(--sys-bg-page)] border border-[var(--sys-border-strong)] rounded-md text-sm text-[var(--sys-text-primary)] focus:border-[var(--sys-brand-solid)] outline-none transition-all">
                  </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 bg-transparent border-none">
                  <div class="space-y-1.5 bg-transparent border-none">
                    <label class="text-[13px] font-medium text-[var(--sys-text-primary)] block">Ngày ký *</label>
                    <CalendarCustom v-model="form.sign_date" placeholder="Chọn ngày ký" />
                  </div>
                  <div class="space-y-1.5 bg-transparent border-none">
                    <label class="text-[13px] font-medium text-[var(--sys-text-primary)] block">Ngày hiệu lực *</label>
                    <CalendarCustom v-model="form.start_date" placeholder="Chọn ngày hiệu lực" />
                  </div>
                </div>

                <div class="p-4 bg-[var(--sys-bg-page)] rounded-md border border-[var(--sys-border-subtle)] flex items-center justify-between">
                  <p class="text-[13px] font-medium text-[var(--sys-text-primary)] m-0">Ghi nhận ngày đáo hạn dự kiến:</p>
                  <p class="text-base font-bold text-[var(--sys-brand-solid)] m-0">{{ calculateEndDate() || 'Vô thời hạn' }}</p>
                </div>

                <div class="space-y-1.5 bg-transparent border-none">
                  <label class="text-[13px] font-medium text-[var(--sys-text-primary)] block">Thỏa thuận lương cơ bản (VNĐ)</label>
                  <input v-model="form.salary" type="number" placeholder="Nhập mức lương..." class="w-full h-10 px-3 bg-[var(--sys-bg-page)] border border-[var(--sys-border-strong)] rounded-md text-sm text-[var(--sys-text-primary)] focus:border-[var(--sys-brand-solid)] outline-none transition-all text-right">
                </div>
              </div>
            </div>

            <!-- Modal Footer -->
            <div class="px-6 py-4 border-t border-[var(--sys-border-subtle)] bg-[var(--sys-bg-surface)] flex justify-end gap-3">
              <button @click="closeModal" class="px-4 py-2 text-sm font-medium text-[var(--sys-text-secondary)] hover:bg-[var(--sys-bg-hover)] rounded-md transition-all uppercase tracking-wide">Hủy bỏ</button>
              <button @click="handleSave" class="px-6 py-2 bg-[var(--sys-brand-solid)] text-white rounded-md font-semibold text-sm hover:brightness-90 transition-all uppercase tracking-wide">
                {{ editMode ? 'Cập nhật hợp đồng' : 'Xác nhận khởi tạo' }}
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
 * TRANG QUẢN TRỊ HỢP ĐỒNG - PHIÊN BẢN ENTERPRISE SaaS
 * Tuân thủ 7 Golden Rules:
 * - Font Inter 14px (text-sm), Tỉ lệ Table cao (text-13px)
 * - Bo góc chuẩn B2B: 6px (MD) cho Input/Button, 8px (LG) cho Card/Modal/Table
 * - Hệ màu Blue/White đồng nhất cho Action Icons
 */
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import Dropdown from '@/components/Dropdown.vue';
import CalendarCustom from '@/components/CalendarCustom.vue';
import { useConfirm } from '@/composables/useConfirm.js';
import { BE_API_BASE, getAccessToken } from '@/services/runtimeConfig.js';
import { handleUnauthorized } from '@/services/session.js';

const { showAlert, showConfirm } = useConfirm();
const router = useRouter();

const showModal = ref(false);
const editMode = ref(false);
const searchQuery = ref('');
const filterStatus = ref('ALL');
const contractsRaw = ref([]);
const employeesRaw = ref([]);
const contractTypesRaw = ref([]);

const statusOptions = [
  { label: 'Mọi trạng thái', value: 'ALL' },
  { label: 'Đang hiệu lực', value: 'DANG_HIEU_LUC' },
  { label: 'Sắp hết hạn', value: 'SAP_HET_HAN' },
  { label: 'Đã thanh lý/Chấm dứt', value: 'DA_THANH_LY' },
  { label: 'Chờ hiệu lực', value: 'CHO_HIEU_LUC' },
];

const fallbackContractTypeOptions = [
  { label: 'Thử việc (02 tháng)', value: 'THỬ_VIỆC' },
  { label: 'Xác định thời hạn (01 năm)', value: 'CHÍNH_THỨC_1_NĂM' },
  { label: 'Xác định thời hạn (03 năm)', value: 'CHÍNH_THỨC_3_NĂM' },
  { label: 'Không xác định thời hạn', value: 'VÔ_THỜI_HẠN' },
];

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
    body: body !== undefined ? JSON.stringify(body) : undefined,
  });
  if (response.status === 401) {
    handleUnauthorized();
    throw new Error('Phiên đăng nhập đã hết hạn');
  }
  const payload = await response.json().catch(() => ({}));
  if (!response.ok || payload?.success === false) {
    throw new Error(payload?.message || `HTTP ${response.status}`);
  }
  return payload;
};

const toUiContractType = (item = {}) => {
  const code = String(item.contractTypeCode || item.contract_type_code || '').toUpperCase();
  const maxMonths = Number(item.max_duration_months ?? item.maxDurationMonths ?? 0);
  const isProbation = Boolean(item.is_probation ?? item.isProbation);
  if (isProbation || code.includes('HDTV')) return 'THỬ_VIỆC';
  if (maxMonths >= 24 || code.includes('HDLD03')) return 'CHÍNH_THỨC_3_NĂM';
  if (maxMonths === 12 || code.includes('HDLD02')) return 'CHÍNH_THỨC_1_NĂM';
  return 'VÔ_THỜI_HẠN';
};

const loadData = async () => {
  try {
    const [contractsPayload, employeesPayload, contractTypesPayload] = await Promise.all([
      apiRequest('/contracts?page=1&per_page=1200'),
      apiRequest('/employees?page=1&per_page=2000'),
      apiRequest('/contract-types?page=1&per_page=200'),
    ]);
    contractsRaw.value = Array.isArray(contractsPayload?.data) ? contractsPayload.data : [];
    employeesRaw.value = Array.isArray(employeesPayload?.data) ? employeesPayload.data : [];
    contractTypesRaw.value = Array.isArray(contractTypesPayload?.data) ? contractTypesPayload.data : [];
  } catch (error) {
    await showAlert('Không tải được dữ liệu', error?.message || 'Không thể tải danh sách hợp đồng.');
  }
};

onMounted(() => {
  void loadData();
});

const contractTypeOptions = computed(() => {
  if (!contractTypesRaw.value.length) return fallbackContractTypeOptions;
  return contractTypesRaw.value.map((item) => ({
    label: item.contractTypeName || item.contract_type_name || item.contractTypeCode || 'Loại hợp đồng',
    value: toUiContractType(item),
    rawId: Number(item.contractTypeId ?? item.contract_type_id ?? item.id),
  }));
});

const resolveContractTypeId = (uiType) => {
  const picked = contractTypeOptions.value.find((item) => item.value === uiType && item.rawId);
  if (picked?.rawId) return picked.rawId;
  const fallback = contractTypesRaw.value[0];
  return fallback ? Number(fallback.contractTypeId ?? fallback.contract_type_id ?? fallback.id) : null;
};

const contracts = computed(() => {
  return contractsRaw.value.map(c => {
    const empId = Number(c.employee_id ?? c.employeeId ?? 0);
    const emp = employeesRaw.value.find(e => Number(e.employee_id ?? e.employeeId ?? e.id) === empId);
    const uiStatus = String(c.ui_status || '').toUpperCase() || 'DANG_HIEU_LUC';
    const endDate = c.expiry_date || c.end_date || c.endDate || null;
    const startDate = c.effective_date || c.start_date || c.startDate || '';
    const salary = Number(c.basic_salary ?? c.salary ?? 0);

    return {
      id: Number(c.contract_id ?? c.contractId ?? c.id),
      contract_no: c.contract_number || c.contract_no || c.contract_code || c.contractCode,
      employee_id: empId,
      employee_name: c.employee_name || (emp ? (emp.full_name || emp.fullName) : `NV #${empId}`),
      contract_type: toUiContractType(c),
      contract_type_id: Number(c.contract_type_id ?? c.contractTypeId ?? 0),
      sign_date: c.sign_date || startDate || '',
      start_date: startDate,
      end_date: endDate,
      status: String(c.status || 'CÓ_HIỆU_LỰC'),
      ui_status: uiStatus,
      salary,
    }
  });
});

const filteredContracts = computed(() => {
  let list = contracts.value;
  if (filterStatus.value !== 'ALL') {
    list = list.filter(c => c.ui_status === filterStatus.value);
  }
  if (searchQuery.value) {
    const q = searchQuery.value.toLowerCase();
    list = list.filter(c => 
      String(c.employee_name || '').toLowerCase().includes(q) || 
      String(c.contract_no || '').toLowerCase().includes(q)
    );
  }
  return list;
});

const contractStats = computed(() => [
  { label: 'Hợp đồng hiệu lực', value: contracts.value.filter(c => c.ui_status === 'DANG_HIEU_LUC' || c.ui_status === 'SAP_HET_HAN').length.toString() },
  { label: 'Đang thử việc', value: contracts.value.filter(c => c.contract_type === 'THỬ_VIỆC').length.toString() },
  { label: 'Hợp đồng vô thời hạn', value: contracts.value.filter(c => c.contract_type === 'VÔ_THỜI_HẠN').length.toString() },
  { label: 'Đã chấm dứt', value: contracts.value.filter(c => c.ui_status === 'DA_THANH_LY').length.toString() }
]);

const historyLogs = [
  { id: 1, content: 'Dữ liệu hợp đồng đồng bộ từ BE API', user: 'System', time: 'Realtime', icon: 'description', bg: 'bg-[var(--sys-brand-solid)]' }
];

const expiringCount = computed(() => contracts.value.filter(c => c.ui_status === 'SAP_HET_HAN').length);

const emptyForm = {
  id: null,
  employee_id: '',
  employee_name: '',
  contract_no: '',
  contract_type: 'THỬ_VIỆC',
  sign_date: new Date().toISOString().substr(0, 10),
  start_date: new Date().toISOString().substr(0, 10),
  salary: 0,
  status: 'CÓ_HIỆU_LỰC'
};

const form = ref({ ...emptyForm });

const openAddModal = () => {
  editMode.value = false;
  form.value = { ...emptyForm };
  form.value.contract_no = `HDLD-${new Date().getFullYear()}-${Math.floor(1000 + Math.random() * 9000)}`;
  showModal.value = true;
};

const editContract = (item) => {
  editMode.value = true;
  form.value = { ...item, salary: item.salary, sign_date: item.sign_date || item.start_date };
  showModal.value = true;
};

const closeModal = () => { showModal.value = false; };

const getStatusBadgeClass = (status) => {
  switch (status) {
    case 'DANG_HIEU_LUC': return 'bg-[var(--sys-success-soft)] text-[var(--sys-success-text)] border-[var(--sys-success-border)]';
    case 'SAP_HET_HAN': return 'bg-[var(--sys-warning-soft)] text-[var(--sys-warning-text)] border-[var(--sys-warning-border)]';
    case 'DA_THANH_LY': return 'bg-[var(--sys-danger-soft)] text-[var(--sys-danger-text)] border-[var(--sys-danger-border)]';
    case 'CHO_HIEU_LUC': return 'bg-[var(--sys-brand-soft)] text-[var(--sys-brand-solid)] border-[var(--sys-brand-border)]';
    default: return 'bg-[var(--sys-bg-hover)] text-[var(--sys-text-secondary)] border-[var(--sys-border-subtle)]';
  }
};

const getStatusDotClass = (status) => {
  switch (status) {
    case 'DANG_HIEU_LUC': return 'bg-[var(--sys-success-solid)]';
    case 'SAP_HET_HAN': return 'bg-[var(--sys-warning-solid)]';
    case 'DA_THANH_LY': return 'bg-[var(--sys-danger-solid)]';
    case 'CHO_HIEU_LUC': return 'bg-[var(--sys-brand-solid)]';
    default: return 'bg-[var(--sys-icon-default)] opacity-40';
  }
};

const calculateEndDate = () => {
  if (!form.value.start_date || form.value.contract_type === 'VÔ_THỜI_HẠN') return null;
  const start = new Date(form.value.start_date);
  let monthsToAdd = 0;
  if (form.value.contract_type === 'THỬ_VIỆC' || form.value.contract_type === 'THỰC_TẬP') monthsToAdd = 2;
  if (form.value.contract_type === 'CHÍNH_THỨC_1_NĂM') monthsToAdd = 12;
  if (form.value.contract_type === 'CHÍNH_THỨC_3_NĂM') monthsToAdd = 36;
  
  start.setMonth(start.getMonth() + monthsToAdd);
  return start.toISOString().substr(0, 10);
};

const handleSave = async () => {
  if (!form.value.employee_name || !form.value.contract_no) {
    await showAlert('Thiếu thông tin', 'Vui lòng xác định nhân viên và số hiệu hợp đồng!');
    return;
  }

  let emp_id = form.value.employee_id;
  if (!emp_id) {
    const q = String(form.value.employee_name || '').toLowerCase();
    const emp = employeesRaw.value.find(e =>
      String(e.fullName || e.full_name || '').toLowerCase().includes(q) ||
      String(e.employeeCode || e.employee_code || '').toLowerCase().includes(q)
    );
    emp_id = emp ? Number(emp.employeeId ?? emp.employee_id ?? emp.id) : null;
  }

  if (!emp_id) {
    await showAlert('Không tìm thấy nhân sự', 'Vui lòng nhập đúng tên hoặc mã nhân viên.');
    return;
  }

  const contractTypeId = resolveContractTypeId(form.value.contract_type);
  if (!contractTypeId) {
    await showAlert('Thiếu loại hợp đồng', 'Không tìm thấy danh mục loại hợp đồng hợp lệ.');
    return;
  }

  const payload = {
    contract_number: form.value.contract_no,
    employee_id: emp_id,
    contract_type_id: contractTypeId,
    sign_date: form.value.sign_date || form.value.start_date,
    effective_date: form.value.start_date,
    expiry_date: calculateEndDate() || null,
    basic_salary: Number(form.value.salary || 0),
    gross_salary: Number(form.value.salary || 0),
    net_salary: Number(form.value.salary || 0),
    status: form.value.status,
  };

  try {
    if (editMode.value) {
      await apiRequest(`/contracts/${form.value.id}`, { method: 'PATCH', body: payload });
    } else {
      await apiRequest('/contracts', { method: 'POST', body: payload });
    }
    await loadData();
    closeModal();
  } catch (error) {
    await showAlert('Lưu thất bại', error?.message || 'Không thể lưu hợp đồng.');
  }
};

const extendContract = async (item) => {
  const ok = await showConfirm('Xác nhận gia hạn', `Khởi tạo quy trình gia hạn cho hợp đồng ${item.contract_no}?`);
  if (ok) {
    openAddModal();
    form.value.employee_name = item.employee_name;
    form.value.employee_id = item.employee_id;
    form.value.contract_type = 'CHÍNH_THỨC_1_NĂM';
  }
};

const focusExpiringContracts = () => {
  filterStatus.value = 'SAP_HET_HAN';
  searchQuery.value = '';
};

const resetContractFilters = () => {
  filterStatus.value = 'ALL';
  searchQuery.value = '';
  void loadData();
  router.replace('/admin/hopdong?view=all');
};
</script>

<style scoped>
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
</style>
