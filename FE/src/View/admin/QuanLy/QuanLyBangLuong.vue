<template>
  <div class="space-y-6 pb-8">
    <!-- Header Area -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 bg-transparent text-left">
      <div class="bg-transparent text-left">
        <h1 class="text-xl font-semibold text-[var(--sys-text-primary)] mb-1">Quản lý Quyết toán & Bảng lương</h1>
        <p class="text-sm text-[var(--sys-text-secondary)]">Quản lý chu kỳ thu nhập, phê chuẩn thanh quyết toán và đối soát ngân quỹ nhân sự.</p>
      </div>
      <div class="flex items-center gap-3 text-left bg-transparent shrink-0">
        <button
          class="h-10 px-4 bg-[var(--sys-bg-surface)] border border-[var(--sys-border-strong)] text-[var(--sys-text-secondary)] rounded-md font-semibold text-sm hover:text-[var(--sys-brand-solid)] transition-all flex items-center gap-2 shadow-sm"
          @click="exportReport"
        >
          <span class="material-symbols-outlined text-[20px]">ios_share</span>
          Xuất báo cáo
        </button>
        <button @click="openAddModal" class="h-10 px-6 bg-[var(--sys-brand-solid)] rounded-md font-semibold text-white hover:brightness-90 transition-all flex items-center gap-2 text-sm shadow-sm">
          <span class="material-symbols-outlined text-[20px]">add_card</span>
          Tạo kỳ quyết toán
        </button>
      </div>
    </div>

    <!-- Stats Section -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
      <div v-for="(stat, idx) in stats" :key="idx" 
        class="bg-[var(--sys-bg-surface)] p-4 rounded-lg border border-[var(--sys-border-subtle)] shadow-sm hover:border-[var(--sys-brand-solid)] transition-all group flex items-center gap-4">
        <div class="w-12 h-12 rounded-md flex items-center justify-center bg-[var(--sys-brand-soft)] text-[var(--sys-brand-solid)] transition-all">
          <span class="material-symbols-outlined text-[24px]">{{ stat.icon }}</span>
        </div>
        <div class="bg-transparent flex flex-col flex-1">
          <div class="flex items-center justify-between mb-0.5">
            <p class="text-[12px] font-medium text-[var(--sys-text-secondary)] uppercase tracking-wide">{{ stat.label }}</p>
            <span :class="[
              'text-[10px] font-bold',
              stat.trend.startsWith('+') ? 'text-[var(--sys-success-text)]' : 'text-[var(--sys-danger-text)]'
            ]">
              {{ stat.trend }}
            </span>
          </div>
          <p class="text-xl font-bold text-[var(--sys-text-primary)] m-0 leading-tight">{{ stat.value }}</p>
        </div>
      </div>
    </div>

    <!-- Main Data Table Container -->
    <div class="bg-[var(--sys-bg-surface)] rounded-lg border border-[var(--sys-border-subtle)] shadow-sm overflow-hidden flex flex-col min-h-[500px]">
      <!-- Toolbar -->
      <div class="px-4 py-3 border-b border-[var(--sys-border-subtle)] flex flex-col xl:flex-row justify-between items-start xl:items-center gap-4 bg-[var(--sys-bg-page)]/50">
        <div class="flex items-center gap-1 bg-white p-1 rounded-md border border-[var(--sys-border-subtle)] shadow-sm overflow-x-auto max-w-full">
          <button
            v-for="period in visiblePeriods"
            :key="period.id"
            class="px-4 py-1.5 rounded-md text-[13px] font-semibold whitespace-nowrap transition-all"
            :class="selectedPeriodId === period.id
              ? 'bg-[var(--sys-brand-solid)] text-white shadow-sm'
              : 'text-[var(--sys-text-secondary)] hover:bg-[var(--sys-bg-hover)]'"
            @click="selectPeriod(period.id)"
          >
            {{ period.label }}
          </button>
        </div>

        <div class="flex items-center gap-3 w-full xl:w-auto bg-transparent">
          <div class="relative flex-1 xl:w-80 group">
            <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-[20px] text-[var(--sys-brand-solid)]">search</span>
            <input 
              v-model="searchQuery"
              type="text" 
              placeholder="Tìm kiếm nhân viên..." 
              class="w-full h-10 pl-10 pr-4 bg-white border border-[var(--sys-border-strong)] rounded-md text-sm text-[var(--sys-text-primary)] focus:outline-none focus:border-[var(--sys-brand-solid)] focus:ring-1 focus:ring-[var(--sys-brand-solid)] transition-all placeholder:text-[var(--sys-text-disabled)]"
            >
          </div>
          <button
            class="shrink-0 h-10 px-4 bg-[var(--sys-success-soft)] text-[var(--sys-success-text)] rounded-md text-sm font-semibold hover:bg-[var(--sys-success-solid)] hover:text-white transition-all border border-[var(--sys-success-border)] flex items-center gap-2 shadow-sm disabled:opacity-50 disabled:cursor-not-allowed"
            :disabled="!selectedPeriod || isPeriodClosed"
            @click="closeSelectedPeriod"
          >
            <span class="material-symbols-outlined text-[20px]">verified_user</span>
            Chốt kỳ lương
          </button>
        </div>
      </div>

      <!-- Table Section -->
      <div class="overflow-x-auto bg-transparent custom-scrollbar flex-grow">
        <table class="w-full text-left border-collapse">
          <thead class="bg-[var(--sys-bg-page)]">
            <tr>
              <th class="px-4 py-3 text-[12px] font-semibold text-[var(--sys-text-secondary)] border-b border-[var(--sys-border-subtle)] uppercase tracking-wider whitespace-nowrap">Hồ sơ thụ hưởng</th>
              <th class="px-4 py-3 text-[12px] font-semibold text-[var(--sys-text-secondary)] border-b border-[var(--sys-border-subtle)] uppercase tracking-wider text-right whitespace-nowrap">Mức lương cơ bản</th>
              <th class="px-4 py-3 text-[12px] font-semibold text-[var(--sys-text-secondary)] border-b border-[var(--sys-border-subtle)] uppercase tracking-wider text-right whitespace-nowrap">Thu nhập gộp</th>
              <th class="px-4 py-3 text-[12px] font-semibold text-[var(--sys-text-secondary)] border-b border-[var(--sys-border-subtle)] uppercase tracking-wider text-right whitespace-nowrap">Khấu trừ trích nộp</th>
              <th class="px-4 py-3 text-[12px] font-semibold text-[var(--sys-brand-solid)] border-b border-[var(--sys-border-subtle)] uppercase tracking-wider text-right bg-[var(--sys-brand-soft)]/20 whitespace-nowrap">Thực lĩnh (NET)</th>
              <th class="px-4 py-3 text-[12px] font-semibold text-[var(--sys-text-secondary)] border-b border-[var(--sys-border-subtle)] uppercase tracking-wider text-center whitespace-nowrap">Trạng thái</th>
              <th class="px-4 py-3 text-[12px] font-semibold text-[var(--sys-text-secondary)] border-b border-[var(--sys-border-subtle)] uppercase tracking-wider text-right whitespace-nowrap">Thao tác</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-[var(--sys-border-subtle)]">
            <tr v-for="(item, index) in pagedEmployees" :key="item.id || index" 
              class="group hover:bg-[var(--sys-bg-hover)] transition-all">
              <td class="px-4 py-3 whitespace-nowrap bg-transparent">
                <div class="flex flex-col bg-transparent">
                  <span class="text-[13px] font-semibold text-[var(--sys-text-primary)] transition-colors line-clamp-1 max-w-[200px]">{{ item.name }}</span>
                  <span class="text-[12px] text-[var(--sys-text-secondary)]">{{ item.role }}</span>
                </div>
              </td>
              <td class="px-4 py-3 whitespace-nowrap text-right text-[13px] text-[var(--sys-text-secondary)] font-medium">{{ formatCurrency(item.baseSalary) }}</td>
              <td class="px-4 py-3 whitespace-nowrap text-right text-[13px] text-[var(--sys-brand-solid)] font-semibold">+{{ formatCurrency(item.totalIncome) }}</td>
              <td class="px-4 py-3 whitespace-nowrap text-right text-[13px] text-[var(--sys-danger-text)] font-semibold">-{{ formatCurrency(item.deduction) }}</td>
              <td class="px-4 py-3 whitespace-nowrap text-right bg-[var(--sys-brand-soft)]/5">
                <span class="text-[14px] font-bold text-[var(--sys-brand-solid)]">{{ formatCurrency(item.netSalary) }}</span>
              </td>
              <td class="px-4 py-3 whitespace-nowrap text-center bg-transparent">
                <span 
                  class="px-2 py-0.5 rounded-md text-[11px] font-semibold border transition-all uppercase tracking-wide"
                  :class="getStatusClasses(item.status)"
                >
                  {{ item.status }}
                </span>
              </td>
              <td class="px-4 py-3 whitespace-nowrap text-right bg-transparent">
                <div class="flex items-center justify-end gap-1">
                  <button @click="openViewModal(item)" class="w-8 h-8 flex items-center justify-center rounded-md text-[var(--sys-text-secondary)] hover:bg-[var(--sys-brand-soft)] hover:text-[var(--sys-brand-solid)] transition-all" title="Xem chi tiết">
                    <span class="material-symbols-outlined text-[18px]">visibility</span>
                  </button>
                  <button @click="openEditModal(item, index)" class="w-8 h-8 flex items-center justify-center rounded-md text-[var(--sys-text-secondary)] hover:bg-[var(--sys-brand-soft)] hover:text-[var(--sys-brand-solid)] transition-all" title="Hiệu chỉnh">
                    <span class="material-symbols-outlined text-[18px]">edit_square</span>
                  </button>
                  <button @click="openDeleteModal(item, index)" class="w-8 h-8 flex items-center justify-center rounded-md text-[var(--sys-text-secondary)] hover:bg-[var(--sys-danger-soft)] hover:text-[var(--sys-danger-solid)] transition-all" title="Xóa">
                    <span class="material-symbols-outlined text-[18px]">delete</span>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div class="px-4 py-3 bg-[var(--sys-bg-page)] border-t border-[var(--sys-border-subtle)] flex flex-col sm:flex-row justify-between items-center gap-4 text-[12px] font-medium text-[var(--sys-text-secondary)]">
        <span>Hiển thị {{ pagedEmployees.length }} trên tổng số {{ filteredEmployees.length }} hồ sơ</span>
        <div class="flex items-center gap-1.5">
          <button
            class="w-8 h-8 flex items-center justify-center rounded-md bg-white border border-[var(--sys-border-subtle)] hover:text-[var(--sys-brand-solid)] transition-all disabled:opacity-50 disabled:cursor-not-allowed"
            :disabled="currentPage <= 1"
            @click="goPrevPage"
          >
            <span class="material-symbols-outlined text-[18px]">chevron_left</span>
          </button>
          <button
            v-for="page in visiblePageNumbers"
            :key="page"
            class="w-8 h-8 flex items-center justify-center rounded-md border border-[var(--sys-border-subtle)] transition-all font-bold"
            :class="currentPage === page ? 'bg-[var(--sys-brand-solid)] text-white' : 'bg-white hover:text-[var(--sys-brand-solid)]'"
            @click="currentPage = page"
          >
            {{ page }}
          </button>
          <button
            class="w-8 h-8 flex items-center justify-center rounded-md bg-white border border-[var(--sys-border-subtle)] hover:text-[var(--sys-brand-solid)] transition-all disabled:opacity-50 disabled:cursor-not-allowed"
            :disabled="currentPage >= totalPages"
            @click="goNextPage"
          >
            <span class="material-symbols-outlined text-[18px]">chevron_right</span>
          </button>
        </div>
      </div>
    </div>

    <!-- Modal System -->
    <Teleport to="body">
      <Transition name="modal-fade">
        <div v-if="isAddEditModalOpen" class="fixed inset-0 z-[10000] flex items-center justify-center p-4">
          <div class="fixed inset-0 w-screen h-screen bg-black/50 z-[9999]" @click="closeModal"></div>
          <div class="relative z-[10000] bg-[var(--sys-bg-surface-elevated)] border border-[var(--sys-border-subtle)] w-full max-w-2xl max-h-[90vh] rounded-lg shadow-xl overflow-hidden flex flex-col text-left">
            <!-- Modal Header -->
            <div class="px-6 py-4 border-b border-[var(--sys-border-subtle)] flex justify-between items-center bg-[var(--sys-bg-surface)]">
              <div class="bg-transparent text-left flex flex-col">
                <h3 class="text-lg font-semibold text-[var(--sys-text-primary)] m-0 uppercase tracking-tight">{{ modalTitle }}</h3>
                <p class="text-sm text-[var(--sys-text-secondary)] mt-1">Cấu hình tham số thu nhấp và quyết toán chi trả.</p>
              </div>
              <button @click="closeModal" class="w-8 h-8 flex items-center justify-center rounded-md hover:bg-[var(--sys-bg-hover)] transition-all text-[var(--sys-text-secondary)]">
                <span class="material-symbols-outlined text-xl">close</span>
              </button>
            </div>

            <!-- Modal Body -->
            <div class="flex-1 overflow-y-auto p-6 custom-scrollbar bg-transparent">
              <div class="space-y-6 bg-transparent border-none">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 bg-transparent border-none">
                  <div class="space-y-1.5 bg-transparent border-none">
                    <label class="text-[13px] font-medium text-[var(--sys-text-primary)] block">Hồ sơ thụ hưởng *</label>
                    <input v-model="formData.name" :disabled="modalMode === 'view'" class="w-full h-10 px-3 bg-[var(--sys-bg-page)] border border-[var(--sys-border-strong)] rounded-md text-sm text-[var(--sys-text-primary)] focus:border-[var(--sys-brand-solid)] outline-none transition-all">
                  </div>
                  <div class="space-y-1.5 bg-transparent border-none">
                    <label class="text-[13px] font-medium text-[var(--sys-text-primary)] block">Chu kỳ quyết toán</label>
                    <input v-model="formData.period" :disabled="modalMode === 'view'" class="w-full h-10 px-3 bg-[var(--sys-bg-page)] border border-[var(--sys-border-strong)] rounded-md text-sm text-[var(--sys-text-primary)] focus:border-[var(--sys-brand-solid)] outline-none transition-all">
                  </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 bg-transparent border-none">
                  <div class="space-y-1.5 bg-transparent border-none">
                    <label class="text-[13px] font-medium text-[var(--sys-text-primary)] block">Lương định biên (Gross)</label>
                    <input v-model="formData.baseSalary" type="number" :disabled="modalMode === 'view'" class="w-full h-10 px-3 bg-[var(--sys-bg-page)] border border-[var(--sys-border-strong)] rounded-md text-sm text-[var(--sys-text-primary)] focus:border-[var(--sys-brand-solid)] outline-none transition-all text-right">
                  </div>
                  <div class="space-y-1.5 bg-transparent border-none">
                    <label class="text-[13px] font-medium text-[var(--sys-text-primary)] block">Tổng thu nhập gộp</label>
                    <input v-model="formData.totalIncome" type="number" :disabled="modalMode === 'view'" class="w-full h-10 px-3 bg-[var(--sys-bg-page)] border border-[var(--sys-border-strong)] rounded-md text-sm text-[var(--sys-text-primary)] focus:border-[var(--sys-brand-solid)] outline-none transition-all text-right">
                  </div>
                </div>

                <div class="space-y-1.5 bg-transparent border-none">
                  <label class="text-[13px] font-medium text-[var(--sys-text-primary)] block">Các khoản trích trừ (Thuế, BH...)</label>
                  <input v-model="formData.deduction" type="number" :disabled="modalMode === 'view'" class="w-full h-10 px-3 bg-[var(--sys-bg-page)] border border-[var(--sys-border-strong)] rounded-md text-sm text-[var(--sys-danger-text)] focus:border-[var(--sys-danger-solid)] outline-none transition-all text-right">
                </div>

                <div class="p-6 bg-[var(--sys-brand-soft)] rounded-lg border border-[var(--sys-brand-border)] flex items-center justify-between">
                  <div class="bg-transparent text-left">
                    <p class="text-[12px] font-semibold text-[var(--sys-brand-solid)] mb-1 uppercase tracking-wide">Thực nhận (NET Salary)</p>
                    <p class="text-3xl font-bold text-[var(--sys-brand-solid)] m-0 leading-none">{{ formatCurrency(formData.netSalary || (formData.totalIncome - formData.deduction)) }}</p>
                  </div>
                  <div class="w-12 h-12 rounded-md bg-white flex items-center justify-center border border-[var(--sys-brand-border)] shadow-sm">
                    <span class="material-symbols-outlined text-[32px] text-[var(--sys-brand-solid)]">payments</span>
                  </div>
                </div>
              </div>
            </div>

            <!-- Modal Footer -->
            <div class="px-6 py-4 border-t border-[var(--sys-border-subtle)] bg-[var(--sys-bg-surface)] flex justify-end gap-3">
              <button @click="closeModal" class="px-4 py-2 text-sm font-medium text-[var(--sys-text-secondary)] hover:bg-[var(--sys-bg-hover)] rounded-md transition-all uppercase tracking-wide">Hủy</button>
              <button v-if="modalMode !== 'view'" @click="saveData" class="px-6 py-2 bg-[var(--sys-brand-solid)] text-white rounded-md font-semibold text-sm hover:brightness-90 transition-all uppercase tracking-wide">
                Lưu hồ sơ tài chính
              </button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { useConfirm } from '@/composables/useConfirm';
import { BE_API_BASE, getAccessToken } from '@/services/runtimeConfig.js';
import { handleUnauthorized } from '@/services/session.js';
import { fixMojibake, parseJsonResponseSafely } from '@/utils/textEncodingFixed.js';

const { showAlert, showConfirm } = useConfirm();

const salaryDetailsRaw = ref([]);
const employeesRaw = ref([]);
const periodsRaw = ref([]);
const isLoading = ref(false);

const searchQuery = ref('');
const currentPage = ref(1);
const pageSize = 12;
const selectedPeriodId = ref(null);
const currentYear = new Date().getFullYear();
const currentMonth = new Date().getMonth() + 1;

const toNumber = (value, fallback = 0) => {
  const parsed = Number(value);
  return Number.isFinite(parsed) ? parsed : fallback;
};

const normalizeText = (value) => String(value ?? '').trim().toLowerCase();
const displayText = (value) => fixMojibake(String(value ?? '')).replace(/\s+/g, ' ').trim();

const formatCurrency = (val) => new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(toNumber(val));

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
  const payload = await parseJsonResponseSafely(response);
  if (!response.ok || payload?.success === false) {
    throw new Error(payload?.message || `Request failed (${response.status})`);
  }
  return {
    data: Array.isArray(payload?.data) ? payload.data : payload?.data,
    meta: payload?.meta || {},
  };
};

const formatPeriodLabel = (period) => {
  const month = toNumber(period.month ?? period.month_number ?? null, null);
  const year = toNumber(period.year ?? null, null);
  if (month && year) return `Tháng ${String(month).padStart(2, '0')} / ${year}`;
  return period.period_name ?? period.periodName ?? period.period_code ?? 'Kỳ lương';
};

const sortedPeriods = computed(() =>
  [...periodsRaw.value].sort((a, b) => {
    const ay = toNumber(a.year, 0);
    const by = toNumber(b.year, 0);
    if (ay !== by) return by - ay;
    return toNumber(b.month, 0) - toNumber(a.month, 0);
  }),
);

const visiblePeriods = computed(() =>
  sortedPeriods.value.map((period) => ({
    ...period,
    id: Number(period.period_id || period.periodId || period.id),
    label: formatPeriodLabel(period),
  })),
);

const selectedPeriod = computed(() => {
  const selectedId = Number(selectedPeriodId.value || 0);
  return sortedPeriods.value.find((period) => Number(period.period_id || period.periodId || period.id) === selectedId) || null;
});

const isPeriodClosed = computed(() => {
  const status = String(selectedPeriod.value?.status || '').toUpperCase();
  return ['CLOSED', 'PAID'].includes(status);
});

const employeeMap = computed(() =>
  new Map(
    employeesRaw.value.map((emp) => [String(emp.employee_id || emp.employeeId || emp.id), emp]),
  ),
);

const periodMap = computed(() =>
  new Map(
    periodsRaw.value.map((period) => [String(period.period_id || period.periodId || period.id), period]),
  ),
);

const employees = computed(() => {
  const selectedId = Number(selectedPeriodId.value || 0);
  return salaryDetailsRaw.value
    .filter((item) => Number(item.period_id || item.periodId) === selectedId)
    .map((item) => {
      const salaryId = Number(item.salary_detail_id || item.salaryDetailId || item.salary_id || item.id || 0);
      const employeeId = Number(item.employee_id || item.employeeId || 0);
      const periodId = Number(item.period_id || item.periodId || 0);
      const emp = employeeMap.value.get(String(employeeId)) || {};
      const period = periodMap.value.get(String(periodId)) || {};
      const base = toNumber(item.basic_salary ?? item.basicSalary);
      const gross = toNumber(item.gross_salary ?? item.grossSalary, base);
      const deductions = toNumber(
        item.total_deductions
          ?? item.totalDeductions
          ?? (toNumber(item.personal_income_tax ?? item.personalIncomeTax) + toNumber(item.penalty)),
      );
      const net = toNumber(item.net_salary ?? item.netSalary, gross - deductions);
      const statusRaw = String(item.transfer_status ?? item.transferStatus ?? '').toUpperCase();
      const status = statusRaw === 'TRANSFERRED' ? 'Đã thanh toán' : 'Chờ thanh toán';
      const employeeCode = displayText(item.employee_code ?? item.employeeCode ?? emp.employee_code ?? emp.employeeCode);
      const employeeName = displayText(item.full_name ?? item.fullName ?? emp.full_name ?? emp.fullName);
      const departmentName = displayText(item.department_name ?? item.departmentName ?? emp.department_name ?? emp.departmentName);
      const positionName = displayText(item.position_name ?? item.positionName ?? emp.position_name ?? emp.positionName);
      const displayRole = departmentName || positionName || 'Chưa gán phòng ban';
      return {
        id: salaryId,
        salaryId,
        period_id: periodId,
        employee_id: employeeId,
        empId: employeeCode || `NV${String(employeeId).padStart(4, '0')}`,
        name: employeeName || (employeeCode ? `Nhân sự ${employeeCode}` : 'Nhân sự chưa cập nhật'),
        role: displayRole,
        baseSalary: base,
        totalIncome: gross,
        deduction: deductions,
        netSalary: net,
        status,
        period: formatPeriodLabel(period),
      };
    });
});

const filteredEmployees = computed(() => {
  const q = normalizeText(searchQuery.value);
  if (!q) return employees.value;
  return employees.value.filter((item) => normalizeText(item.name).includes(q) || normalizeText(item.empId).includes(q));
});

const totalPages = computed(() => Math.max(1, Math.ceil(filteredEmployees.value.length / pageSize)));

const pagedEmployees = computed(() => {
  const start = (currentPage.value - 1) * pageSize;
  return filteredEmployees.value.slice(start, start + pageSize);
});

const visiblePageNumbers = computed(() => {
  const total = totalPages.value;
  const current = currentPage.value;
  const start = Math.max(1, current - 2);
  const end = Math.min(total, start + 4);
  const pages = [];
  for (let i = start; i <= end; i += 1) pages.push(i);
  return pages;
});

const goPrevPage = () => {
  if (currentPage.value > 1) currentPage.value -= 1;
};

const goNextPage = () => {
  if (currentPage.value < totalPages.value) currentPage.value += 1;
};

watch([searchQuery, selectedPeriodId], () => {
  currentPage.value = 1;
});

watch(totalPages, (nextTotal) => {
  if (currentPage.value > nextTotal) currentPage.value = nextTotal;
  if (currentPage.value < 1) currentPage.value = 1;
});

const selectPeriod = (periodId) => {
  selectedPeriodId.value = Number(periodId);
};

const loadData = async () => {
  isLoading.value = true;
  try {
    const [salaryPayload, employeePayload, periodPayload] = await Promise.all([
      apiRequest('/salary-details?page=1&per_page=3000'),
      apiRequest('/employees?page=1&per_page=2000'),
      apiRequest('/salary-periods?page=1&per_page=500'),
    ]);
    salaryDetailsRaw.value = Array.isArray(salaryPayload.data) ? salaryPayload.data : [];
    employeesRaw.value = Array.isArray(employeePayload.data) ? employeePayload.data : [];
    periodsRaw.value = Array.isArray(periodPayload.data) ? periodPayload.data : [];

    if (!selectedPeriodId.value && periodsRaw.value.length > 0) {
      selectedPeriodId.value = Number(periodsRaw.value[0].period_id || periodsRaw.value[0].periodId || periodsRaw.value[0].id || 0);
    } else if (
      selectedPeriodId.value
      && !periodsRaw.value.some((period) => Number(period.period_id || period.periodId || period.id) === Number(selectedPeriodId.value))
    ) {
      selectedPeriodId.value = periodsRaw.value.length > 0
        ? Number(periodsRaw.value[0].period_id || periodsRaw.value[0].periodId || periodsRaw.value[0].id || 0)
        : null;
    }
  } catch (error) {
    await showAlert('Không tải được bảng lương', error?.message || 'Không thể tải dữ liệu bảng lương.');
  } finally {
    isLoading.value = false;
  }
};

onMounted(() => {
  void loadData();
});

const stats = computed(() => {
  const list = employees.value;
  const totalBudget = list.reduce((acc, item) => acc + toNumber(item.totalIncome), 0);
  const totalTax = list.reduce((acc, item) => acc + toNumber(item.deduction), 0);
  const avgIncome = list.length > 0 ? Math.floor(totalBudget / list.length) : 0;
  return [
    { label: 'Tổng ngân quỹ lương', value: formatCurrency(totalBudget), icon: 'account_balance', trend: 'LIVE' },
    { label: 'Nhân sự thụ hưởng', value: String(list.length), icon: 'badge', trend: 'LIVE' },
    { label: 'Thu nhập bình quân', value: formatCurrency(avgIncome), icon: 'query_stats', trend: 'LIVE' },
    { label: 'Tổng khấu trừ thuế', value: formatCurrency(totalTax), icon: 'savings', trend: 'LIVE' },
  ];
});

const getStatusClasses = (status) => {
  if (status === 'Đã thanh toán') return 'bg-[var(--sys-success-soft)] text-[var(--sys-success-text)] border-[var(--sys-success-border)]';
  if (status === 'Chờ thanh toán') return 'bg-[var(--sys-warning-soft)] text-[var(--sys-warning-text)] border-[var(--sys-warning-border)]';
  return 'bg-[var(--sys-bg-page)] text-[var(--sys-text-disabled)] border-[var(--sys-border-subtle)] opacity-50';
};

const isAddEditModalOpen = ref(false);
const modalMode = ref('add');
const formData = ref({});

const modalTitle = computed(() => {
  if (modalMode.value === 'add') return 'Tạo hồ sơ quyết toán mới';
  if (modalMode.value === 'edit') return 'Điều chỉnh thông tin tài chính';
  return 'Chi tiết bảng lương nhân sự';
});

const openAddModal = () => {
  const defaultEmployee = employeesRaw.value[0] || {};
  const defaultPeriod = selectedPeriod.value || periodsRaw.value[0] || {};
  modalMode.value = 'add';
  formData.value = {
    employee_id: Number(defaultEmployee.employee_id || defaultEmployee.employeeId || defaultEmployee.id || 0),
    period_id: Number(defaultPeriod.period_id || defaultPeriod.periodId || defaultPeriod.id || 0),
    name: defaultEmployee.full_name || defaultEmployee.fullName || 'Nhân viên mới',
    period: formatPeriodLabel(defaultPeriod),
    baseSalary: 0,
    totalIncome: 0,
    deduction: 0,
    netSalary: 0,
    status: 'Chờ thanh toán',
  };
  isAddEditModalOpen.value = true;
};

const openEditModal = (item) => {
  modalMode.value = 'edit';
  formData.value = { ...item };
  isAddEditModalOpen.value = true;
};

const openViewModal = (item) => {
  modalMode.value = 'view';
  formData.value = { ...item };
  isAddEditModalOpen.value = true;
};

const closeModal = () => {
  isAddEditModalOpen.value = false;
};

const resolveEmployeeId = () => {
  if (formData.value.employee_id) return Number(formData.value.employee_id);
  const search = normalizeText(formData.value.name);
  const found = employeesRaw.value.find((emp) => {
    const fullName = normalizeText(emp.full_name || emp.fullName);
    const code = normalizeText(emp.employee_code || emp.employeeCode);
    return fullName === search || code === search;
  });
  return Number(found?.employee_id || found?.employeeId || found?.id || 0);
};

const resolvePeriodId = () => {
  if (formData.value.period_id) return Number(formData.value.period_id);
  const search = normalizeText(formData.value.period);
  const found = periodsRaw.value.find((period) =>
    normalizeText(formatPeriodLabel(period)) === search
    || normalizeText(period.period_code || period.periodCode) === search,
  );
  return Number(found?.period_id || found?.periodId || found?.id || 0);
};

const saveData = async () => {
  const employeeId = resolveEmployeeId();
  const periodId = resolvePeriodId();
  const baseSalary = toNumber(formData.value.baseSalary);
  const totalIncome = toNumber(formData.value.totalIncome);
  const deduction = toNumber(formData.value.deduction);
  const net = toNumber(formData.value.netSalary, totalIncome - deduction);

  if (!employeeId || !periodId) {
    await showAlert('Thiếu thông tin', 'Cần chọn nhân sự và kỳ lương hợp lệ trước khi lưu.');
    return;
  }

  const payload = {
    employee_id: employeeId,
    period_id: periodId,
    basic_salary: baseSalary,
    gross_salary: totalIncome,
    total_allowances: Math.max(totalIncome - baseSalary, 0),
    total_deductions: Math.max(deduction, 0),
    net_salary: net,
    transfer_status: formData.value.status === 'Đã thanh toán' ? 'TRANSFERRED' : 'PENDING',
  };

  try {
    if (modalMode.value === 'add') {
      await apiRequest('/salary-details', { method: 'POST', body: payload });
    } else {
      await apiRequest(`/salary-details/${formData.value.id}`, { method: 'PATCH', body: payload });
    }
    await loadData();
    closeModal();
    await showAlert('Thành công', 'Đã lưu hồ sơ bảng lương.');
  } catch (error) {
    await showAlert('Lưu thất bại', error?.message || 'Không thể lưu hồ sơ bảng lương.');
  }
};

const openDeleteModal = async (item) => {
  const ok = await showConfirm('Thông báo', `Backend hiện chưa hỗ trợ xóa cứng bản ghi bảng lương cho ${item.name}. Bạn có muốn chuyển trạng thái về "Chờ thanh toán" không?`);
  if (!ok) return;
  try {
    await apiRequest(`/salary-details/${item.id}`, {
      method: 'PATCH',
      body: { transfer_status: 'PENDING' },
    });
    await loadData();
  } catch (error) {
    await showAlert('Không thể cập nhật', error?.message || 'Không thể cập nhật bản ghi lương.');
  }
};

const closeSelectedPeriod = async () => {
  if (!selectedPeriod.value) return;
  const periodId = Number(selectedPeriod.value.period_id || selectedPeriod.value.periodId || selectedPeriod.value.id || 0);
  if (!periodId) return;
  const ok = await showConfirm('Xác nhận chốt kỳ lương', `Bạn chắc chắn muốn chốt ${formatPeriodLabel(selectedPeriod.value)}?`);
  if (!ok) return;
  try {
    await apiRequest(`/salary-periods/${periodId}/close`, { method: 'POST' });
    await loadData();
    await showAlert('Thành công', 'Đã chốt kỳ lương.');
  } catch (error) {
    await showAlert('Chốt kỳ thất bại', error?.message || 'Không thể chốt kỳ lương.');
  }
};

const exportReport = () => {
  const rows = filteredEmployees.value;
  if (rows.length === 0) {
    void showAlert('Không có dữ liệu', 'Không có dữ liệu để xuất báo cáo.');
    return;
  }
  const header = ['Mã NV', 'Họ tên', 'Phòng ban', 'Lương cơ bản', 'Thu nhập gộp', 'Khấu trừ', 'Thực lĩnh', 'Trạng thái'];
  const body = rows.map((row) => [
    row.empId,
    row.name,
    row.role,
    String(toNumber(row.baseSalary)),
    String(toNumber(row.totalIncome)),
    String(toNumber(row.deduction)),
    String(toNumber(row.netSalary)),
    row.status,
  ]);
  const csv = [header, ...body]
    .map((line) => line.map((cell) => `"${String(cell).replace(/"/g, '""')}"`).join(','))
    .join('\n');
  const blob = new Blob([`\uFEFF${csv}`], { type: 'text/csv;charset=utf-8;' });
  const periodLabel = selectedPeriod.value ? formatPeriodLabel(selectedPeriod.value).replace(/\s+/g, '_') : `thang_${currentMonth}_${currentYear}`;
  const link = document.createElement('a');
  link.href = URL.createObjectURL(blob);
  link.download = `bang-luong-${periodLabel}.csv`;
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
  URL.revokeObjectURL(link.href);
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
