<template>
  <div class="space-y-6 pb-8">
    <!-- Header Area -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 bg-transparent text-left">
      <div class="bg-transparent text-left">
        <h1 class="text-xl font-semibold text-[var(--sys-text-primary)] mb-1">Cấu trúc Lương & Phúc lợi</h1>
        <p class="text-sm text-[var(--sys-text-secondary)]">Quản lý bảng kê thu nhập, phụ cấp và các khoản khấu trừ định kỳ.</p>
      </div>
      <div class="flex items-center gap-3 bg-transparent shrink-0">
        <div class="hidden md:flex items-center gap-1 bg-[var(--sys-bg-page)] p-1 rounded-md border border-[var(--sys-border-subtle)]">
          <button 
            v-for="view in ['grid', 'list']" 
            :key="view"
            @click="viewMode = view"
            :class="[
              'w-8 h-8 rounded-md flex items-center justify-center transition-all',
              viewMode === view ? 'bg-white text-[var(--sys-brand-solid)] shadow-sm' : 'text-[var(--sys-text-secondary)] hover:bg-[var(--sys-bg-hover)]'
            ]"
          >
            <span class="material-symbols-outlined text-[20px]">{{ view === 'grid' ? 'grid_view' : 'format_list_bulleted' }}</span>
          </button>
        </div>
        <button 
          @click="createCurrentPeriod"
          class="h-10 px-4 bg-[var(--sys-brand-solid)] rounded-md font-semibold text-white hover:brightness-90 transition-all flex items-center gap-2 text-sm shadow-sm"
        >
          <span class="material-symbols-outlined text-[20px]">payments</span>
          Tạo kỳ lương {{ currentMonth }}/{{ new Date().getFullYear() }}
        </button>
      </div>
    </div>

    <!-- Active Periods Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
      <div v-for="period in periods" :key="period.id" 
        class="bg-[var(--sys-bg-surface)] rounded-lg border border-[var(--sys-border-subtle)] shadow-sm overflow-hidden flex flex-col hover:border-[var(--sys-brand-solid)] transition-all">
        <div class="p-4 flex flex-col flex-1">
          <div class="flex items-center justify-between mb-4">
            <span :class="[
              'px-2 py-0.5 rounded-md text-[11px] font-semibold border transition-all uppercase tracking-wide',
              getStatusBadgeClass(period.status)
            ]">
              {{ period.status.replace('_', ' ') }}
            </span>
            <button class="w-8 h-8 rounded-md hover:bg-[var(--sys-bg-hover)] flex items-center justify-center text-[var(--sys-text-secondary)]">
              <span class="material-symbols-outlined text-[20px]">more_horiz</span>
            </button>
          </div>
          <h3 class="text-xl font-bold text-[var(--sys-text-primary)] mb-1">Tháng {{ period.month }}/{{ period.year }}</h3>
          <p class="text-[13px] text-[var(--sys-text-secondary)] mb-4">Chu kỳ: {{ period.range }}</p>
          
          <div class="grid grid-cols-2 gap-3 mb-4">
            <div class="p-3 bg-[var(--sys-bg-page)] rounded-md border border-[var(--sys-border-subtle)]">
              <p class="text-[11px] font-semibold text-[var(--sys-text-secondary)] uppercase tracking-wider mb-1">Quỹ lương</p>
              <p class="text-[13px] font-bold text-[var(--sys-text-primary)]">{{ formatCurrency(period.total_fund) }}</p>
            </div>
            <div class="p-3 bg-[var(--sys-bg-page)] rounded-md border border-[var(--sys-border-subtle)]">
              <p class="text-[11px] font-semibold text-[var(--sys-text-secondary)] uppercase tracking-wider mb-1">Hồ sơ</p>
              <p class="text-[13px] font-bold text-[var(--sys-text-primary)]">{{ period.employee_count }} NV</p>
            </div>
          </div>
        </div>
        <div class="px-4 py-3 bg-[var(--sys-bg-page)] border-t border-[var(--sys-border-subtle)] flex items-center justify-between transition-all">
          <button @click="viewDetails(period)" class="text-[13px] font-semibold text-[var(--sys-brand-solid)] hover:opacity-80 transition-opacity flex items-center gap-1">
            Chi tiết bảng lương
            <span class="material-symbols-outlined text-[16px]">arrow_forward</span>
          </button>
          <span class="text-[11px] text-[var(--sys-text-disabled)] italic">{{ period.updated_at }}</span>
        </div>
      </div>
    </div>

    <!-- Details Table -->
    <div v-if="selectedPeriod" class="bg-[var(--sys-bg-surface)] rounded-lg border border-[var(--sys-border-subtle)] shadow-sm overflow-hidden flex flex-col">
      <div class="px-4 py-3 border-b border-[var(--sys-border-subtle)] flex flex-col lg:flex-row items-center justify-between gap-4 bg-[var(--sys-bg-page)]/50">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-md bg-[var(--sys-brand-soft)] text-[var(--sys-brand-solid)] flex items-center justify-center border border-[var(--sys-brand-border)]">
            <span class="material-symbols-outlined text-[24px]">payments</span>
          </div>
          <div class="bg-transparent text-left">
            <h3 class="text-sm font-semibold text-[var(--sys-text-primary)] uppercase tracking-wide">Chi tiết quyết toán {{ selectedPeriod.month }}/{{ selectedPeriod.year }}</h3>
            <p class="text-[12px] text-[var(--sys-text-secondary)]">Dữ liệu tổng hợp từ chấm công và hợp đồng lao động.</p>
          </div>
        </div>
        <div class="flex items-center gap-2 w-full lg:w-auto bg-transparent">
          <button class="h-9 px-4 bg-white text-[var(--sys-text-secondary)] border border-[var(--sys-border-subtle)] rounded-md text-[13px] font-semibold hover:border-[var(--sys-brand-solid)] transition-all flex items-center gap-2">
            <span class="material-symbols-outlined text-[18px]">download</span>
            Xuất báo cáo
          </button>
          <button v-if="selectedPeriod.status === 'ĐANG_XỬ_LÝ'" @click="approvePeriod" class="h-9 px-6 bg-[var(--sys-brand-solid)] text-white rounded-md text-[13px] font-semibold shadow-sm hover:brightness-90 transition-all flex items-center gap-2">
            <span class="material-symbols-outlined text-[18px]">check_circle</span>
            Phê chuẩn kết toán
          </button>
        </div>
      </div>

      <div class="overflow-x-auto custom-scrollbar">
        <table class="w-full text-left border-collapse">
          <thead class="bg-[var(--sys-bg-page)]">
            <tr>
              <th class="px-4 py-3 text-[12px] font-semibold text-[var(--sys-text-secondary)] border-b border-[var(--sys-border-subtle)] uppercase tracking-wider whitespace-nowrap">Nhân sự thụ hưởng</th>
              <th class="px-4 py-3 text-[12px] font-semibold text-[var(--sys-text-secondary)] border-b border-[var(--sys-border-subtle)] uppercase tracking-wider text-right whitespace-nowrap">Lương căn bản</th>
              <th class="px-4 py-3 text-[12px] font-semibold text-[var(--sys-text-secondary)] border-b border-[var(--sys-border-subtle)] uppercase tracking-wider text-right whitespace-nowrap">Phụ cấp (+)</th>
              <th class="px-4 py-3 text-[12px] font-semibold text-[var(--sys-text-secondary)] border-b border-[var(--sys-border-subtle)] uppercase tracking-wider text-right whitespace-nowrap">Khấu trừ (-)</th>
              <th class="px-4 py-3 text-[12px] font-semibold text-[var(--sys-brand-solid)] border-b border-[var(--sys-border-subtle)] uppercase tracking-wider text-right bg-[var(--sys-brand-soft)]/20 whitespace-nowrap">Thực lĩnh</th>
              <th class="px-4 py-3 text-[12px] font-semibold text-[var(--sys-text-secondary)] border-b border-[var(--sys-border-subtle)] uppercase tracking-wider text-right whitespace-nowrap">Thao tác</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-[var(--sys-border-subtle)]">
            <tr v-for="item in salaryItems" :key="item.id" class="group hover:bg-[var(--sys-bg-hover)] transition-colors">
              <td class="px-4 py-3 whitespace-nowrap bg-transparent">
                <div class="flex flex-col bg-transparent">
                  <span class="text-[13px] font-semibold text-[var(--sys-text-primary)] line-clamp-1 max-w-[200px]">{{ item.name }}</span>
                  <span class="text-[11px] text-[var(--sys-text-secondary)] uppercase tracking-wider font-bold opacity-60">#{{ item.code }}</span>
                </div>
              </td>
              <td class="px-4 py-3 text-right whitespace-nowrap bg-transparent">
                <span class="text-[13px] font-medium text-[var(--sys-text-secondary)]">{{ formatCurrency(item.base) }}</span>
              </td>
              <td class="px-4 py-3 text-right whitespace-nowrap bg-transparent">
                <span class="text-[13px] font-semibold text-[var(--sys-success-text)]">+{{ formatCurrency(item.bonus) }}</span>
              </td>
              <td class="px-4 py-3 text-right whitespace-nowrap bg-transparent">
                <span class="text-[13px] font-semibold text-[var(--sys-danger-text)]">-{{ formatCurrency(item.deduction) }}</span>
              </td>
              <td class="px-4 py-3 text-right whitespace-nowrap bg-[var(--sys-brand-soft)]/5">
                <span class="text-[14px] font-bold text-[var(--sys-brand-solid)]">{{ formatCurrency(item.base + item.bonus - item.deduction) }}</span>
              </td>
              <td class="px-4 py-3 text-right whitespace-nowrap bg-transparent">
                <div class="flex items-center justify-end gap-1">
                  <button @click="viewPayslip(item)" class="w-8 h-8 flex items-center justify-center rounded-md text-[var(--sys-text-secondary)] hover:bg-[var(--sys-brand-soft)] hover:text-[var(--sys-brand-solid)] transition-all" title="Xem phiếu lương">
                    <span class="material-symbols-outlined text-[18px]">visibility</span>
                  </button>
                  <button @click="editSalary(item)" :disabled="selectedPeriod.status !== 'ĐANG_XỬ_LÝ'" class="w-8 h-8 flex items-center justify-center rounded-md text-[var(--sys-text-secondary)] hover:bg-[var(--sys-brand-soft)] hover:text-[var(--sys-brand-solid)] transition-all disabled:opacity-30 disabled:cursor-not-allowed" title="Điều chỉnh">
                    <span class="material-symbols-outlined text-[18px]">edit_square</span>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Edit Modal -->
    <Teleport to="body">
      <Transition name="modal-fade">
        <div v-if="showEditModal" class="fixed inset-0 z-[10000] flex items-center justify-center p-4">
          <div class="fixed inset-0 w-screen h-screen bg-black/50 z-[9999]" @click="showEditModal = false"></div>
          <div class="relative z-[10000] bg-[var(--sys-bg-surface-elevated)] border border-[var(--sys-border-subtle)] w-full max-w-xl rounded-lg shadow-xl overflow-hidden flex flex-col text-left">
            <!-- Modal Header -->
            <div class="px-6 py-4 border-b border-[var(--sys-border-subtle)] flex items-center justify-between bg-[var(--sys-bg-surface)]">
              <div class="bg-transparent text-left flex flex-col">
                <h3 class="text-lg font-semibold text-[var(--sys-text-primary)] m-0 uppercase tracking-tight">Điều chỉnh thu nhập nhân sự</h3>
                <p class="text-sm text-[var(--sys-text-secondary)] mt-1">Cập nhật các tham số cho bảng lương {{ selectedPeriod.month }}/{{ selectedPeriod.year }}</p>
              </div>
              <button @click="showEditModal = false" class="w-8 h-8 flex items-center justify-center rounded-md hover:bg-[var(--sys-bg-hover)] transition-all text-[var(--sys-text-secondary)]">
                <span class="material-symbols-outlined text-xl">close</span>
              </button>
            </div>

            <!-- Modal Body -->
            <div class="p-6 space-y-6 bg-transparent custom-scrollbar overflow-y-auto">
              <!-- Employee Info Header -->
              <div class="flex items-center gap-4 p-4 bg-[var(--sys-bg-page)] rounded-lg border border-[var(--sys-border-subtle)]">
                <div class="w-12 h-12 rounded-lg bg-[var(--sys-brand-solid)] flex items-center justify-center text-white font-bold text-xl">{{ editForm.name.charAt(0) }}</div>
                <div class="bg-transparent text-left flex-1">
                  <p class="text-base font-bold text-[var(--sys-text-primary)] m-0">{{ editForm.name }}</p>
                  <p class="text-[12px] text-[var(--sys-text-secondary)] font-semibold uppercase tracking-wider opacity-60">Mã NV: {{ editForm.code }}</p>
                </div>
              </div>

              <!-- Form Inputs -->
              <div class="space-y-4 bg-transparent">
                <div class="space-y-1.5 bg-transparent">
                  <label class="text-[13px] font-medium text-[var(--sys-text-primary)] block">Lương cơ bản (Cố định)</label>
                  <input v-model="editForm.base" type="number" disabled class="w-full h-10 px-3 bg-[var(--sys-bg-hover)] border border-[var(--sys-border-strong)] rounded-md text-sm text-[var(--sys-text-disabled)] cursor-not-allowed text-right font-semibold">
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 bg-transparent border-none">
                  <div class="space-y-1.5 bg-transparent">
                    <label class="text-[13px] font-medium text-[var(--sys-text-primary)] block">Phụ cấp (+)</label>
                    <input v-model="editForm.bonus" type="number" class="w-full h-10 px-3 bg-white border border-[var(--sys-border-strong)] rounded-md text-sm text-[var(--sys-success-text)] focus:border-[var(--sys-success-solid)] outline-none text-right font-semibold">
                  </div>
                  <div class="space-y-1.5 bg-transparent">
                    <label class="text-[13px] font-medium text-[var(--sys-text-primary)] block">Khấu trừ (-)</label>
                    <input v-model="editForm.deduction" type="number" class="w-full h-10 px-3 bg-white border border-[var(--sys-border-strong)] rounded-md text-sm text-[var(--sys-danger-text)] focus:border-[var(--sys-danger-solid)] outline-none text-right font-semibold">
                  </div>
                </div>

                <!-- Live Preview -->
                <div class="p-5 bg-[var(--sys-brand-soft)] rounded-lg border border-[var(--sys-brand-border)] flex items-center justify-between">
                  <div class="bg-transparent text-left">
                    <p class="text-[12px] font-semibold text-[var(--sys-brand-solid)] uppercase tracking-wider mb-1">Dự tính thực nhận</p>
                    <p class="text-3xl font-bold text-[var(--sys-brand-solid)] m-0 leading-none">{{ formatCurrency(editForm.base + editForm.bonus - editForm.deduction) }}</p>
                  </div>
                  <span class="material-symbols-outlined text-[32px] text-[var(--sys-brand-solid)]">account_balance_wallet</span>
                </div>
              </div>
            </div>

            <!-- Modal Footer -->
            <div class="px-6 py-4 border-t border-[var(--sys-border-subtle)] bg-[var(--sys-bg-surface)] flex justify-end gap-3">
              <button @click="showEditModal = false" class="px-4 py-2 text-sm font-medium text-[var(--sys-text-secondary)] hover:bg-[var(--sys-bg-hover)] rounded-md transition-all uppercase tracking-wide">Hủy</button>
              <button @click="saveSalaryAdjustment" class="px-6 py-2 bg-[var(--sys-brand-solid)] text-white rounded-md font-semibold text-sm hover:brightness-90 transition-all uppercase tracking-wide">
                Xác nhận thay đổi
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
 * TRANG CẤU TRÚC LƯƠNG & PHÚC LỢI - PHIÊN BẢN ENTERPRISE SaaS
 * Tuân thủ 7 Golden Rules:
 * - Font Inter 14px (text-sm), Tỉ lệ thẻ/bảng cao (text-13px)
 * - Bo góc chuẩn B2B: 6px (MD) cho Input/Button, 8px (LG) cho Card/Thẻ/Modal
 * - Hệ màu Blue/White đồng nhất cho Action Icons
 */
import { ref, computed, onMounted } from 'vue';
import { useConfirm } from '@/composables/useConfirm';
import { apiRequest } from '@/services/beApi.js';

const { showAlert, showConfirm } = useConfirm();
const periodsRaw = ref([]);
const salaryDetailsRaw = ref([]);
const employeesRaw = ref([]);
const isLoading = ref(false);

const currentMonth = new Date().getMonth() + 1;
const currentYear = new Date().getFullYear();

const viewMode = ref('grid');
const selectedPeriodId = ref(null);
const showEditModal = ref(false);

const toNumber = (value, fallback = 0) => {
  const parsed = Number(value);
  return Number.isFinite(parsed) ? parsed : fallback;
};

const pad2 = (value) => String(value).padStart(2, '0');

const formatIsoDate = (value) => {
  const text = String(value ?? '').trim();
  if (!text) return '';
  return text.slice(0, 10);
};

const formatRange = (startDate, endDate) => {
  const start = formatIsoDate(startDate);
  const end = formatIsoDate(endDate);
  if (!start || !end) return '--/-- - --/--';
  return `${start.slice(8, 10)}/${start.slice(5, 7)} - ${end.slice(8, 10)}/${end.slice(5, 7)}`;
};

const mapPeriodStatusToUi = (rawStatus) => {
  const key = String(rawStatus ?? '').trim().toUpperCase();
  if (['PAID', 'CLOSED'].includes(key)) return 'ĐÃ_THANH_TOÁN';
  if (['DRAFT', 'PENDING'].includes(key)) return 'CHỜ_DUYỆT';
  return 'ĐANG_XỬ_LÝ';
};

const loadData = async () => {
  isLoading.value = true;
  try {
    const [periods, salaryDetails, employees] = await Promise.all([
      apiRequest('/salary-periods', { query: { page: 1, per_page: 500 } }),
      apiRequest('/salary-details', { query: { page: 1, per_page: 5000 } }),
      apiRequest('/employees', { query: { page: 1, per_page: 2000 } }),
    ]);
    periodsRaw.value = Array.isArray(periods?.data) ? periods.data : [];
    salaryDetailsRaw.value = Array.isArray(salaryDetails?.data) ? salaryDetails.data : [];
    employeesRaw.value = Array.isArray(employees?.data) ? employees.data : [];
  } catch (error) {
    await showAlert('Không tải được dữ liệu lương', error?.message || 'Không thể tải dữ liệu kỳ lương.');
  } finally {
    isLoading.value = false;
  }
};

const periods = computed(() => {
  const detailsByPeriod = new Map();
  salaryDetailsRaw.value.forEach((detail) => {
    const transferStatus = String(detail.transferStatus ?? detail.transfer_status ?? '').trim().toUpperCase();
    if (transferStatus === 'DELETED') return;
    const periodId = Number(detail.periodId ?? detail.period_id);
    if (!Number.isFinite(periodId) || periodId <= 0) return;
    const existing = detailsByPeriod.get(periodId) || [];
    existing.push(detail);
    detailsByPeriod.set(periodId, existing);
  });

  const mapped = periodsRaw.value.map((period) => {
    const id = Number(period.periodId ?? period.period_id ?? period.id);
    const month = toNumber(period.month, null);
    const year = toNumber(period.year, null);
    const rawStatus = String(period.status ?? 'OPEN').toUpperCase();
    const details = detailsByPeriod.get(id) || [];
    const totalFund = details.reduce((sum, detail) => {
      return sum + toNumber(detail.netSalary ?? detail.net_salary ?? detail.grossSalary ?? detail.gross_salary);
    }, 0);
    const employeeCount = new Set(
      details.map((detail) => String(detail.employeeId ?? detail.employee_id)).filter(Boolean),
    ).size;

    return {
      id,
      month,
      year,
      status: mapPeriodStatusToUi(rawStatus),
      rawStatus,
      range: formatRange(period.startDate ?? period.start_date, period.endDate ?? period.end_date),
      total_fund: totalFund,
      employee_count: employeeCount,
      updated_at: formatIsoDate(period.updated_at ?? period.created_at ?? period.startDate ?? period.start_date),
    };
  });

  return mapped.sort((a, b) => {
    const left = `${a.year ?? 0}-${pad2(a.month ?? 0)}`;
    const right = `${b.year ?? 0}-${pad2(b.month ?? 0)}`;
    return left < right ? 1 : -1;
  });
});

const selectedPeriod = computed(() =>
  periods.value.find((period) => String(period.id) === String(selectedPeriodId.value)) || null,
);

const salaryItems = computed(() => {
  if (!selectedPeriod.value) return [];
  const employeeMap = new Map(
    employeesRaw.value.map((emp) => [
      String(emp.employeeId ?? emp.employee_id ?? emp.id),
      emp,
    ]),
  );
  const periodId = Number(selectedPeriod.value.id);
  return salaryDetailsRaw.value
    .filter((item) => Number(item.periodId ?? item.period_id) === periodId)
    .filter((item) => String(item.transferStatus ?? item.transfer_status ?? '').trim().toUpperCase() !== 'DELETED')
    .map((item) => {
      const employeeId = item.employeeId ?? item.employee_id;
      const emp = employeeMap.get(String(employeeId)) || {};
      const base = toNumber(item.basicSalary ?? item.basic_salary);
      const gross = toNumber(item.grossSalary ?? item.gross_salary, base);
      const bonus = toNumber(item.totalAllowances ?? item.total_allowances, Math.max(gross - base, 0));
      const deduction = toNumber(
        item.totalDeductions
          ?? item.total_deductions
          ?? (toNumber(item.personalIncomeTax ?? item.personal_income_tax) + toNumber(item.penalty)),
      );
      return {
        id: Number(item.salaryId ?? item.salary_id ?? item.salaryDetailId ?? item.salary_detail_id ?? item.id),
        name: emp.fullName ?? emp.full_name ?? item.employeeName ?? `NV #${employeeId}`,
        code: emp.employeeCode ?? emp.employee_code ?? `EMP-${employeeId}`,
        base,
        bonus,
        deduction,
        net: toNumber(item.netSalary ?? item.net_salary, gross - deduction),
      };
    });
});

const editForm = ref({ id: null, name: '', code: '', base: 0, bonus: 0, deduction: 0 });

const formatCurrency = (val) => {
  return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(val);
};

const getStatusBadgeClass = (status) => {
  switch (status) {
    case 'ĐÃ_THANH_TOÁN': return 'bg-[var(--sys-success-soft)] text-[var(--sys-success-text)] border-[var(--sys-success-border)]';
    case 'ĐANG_XỬ_LÝ': return 'bg-[var(--sys-brand-soft)] text-[var(--sys-brand-solid)] border-[var(--sys-brand-border)]';
    case 'CHỜ_DUYỆT': return 'bg-[var(--sys-warning-soft)] text-[var(--sys-warning-text)] border-[var(--sys-warning-border)]';
    default: return 'bg-[var(--sys-bg-hover)] text-[var(--sys-text-secondary)] border-[var(--sys-border-subtle)]';
  }
};

const viewDetails = (period) => {
  selectedPeriodId.value = period.id;
};

const editSalary = (item) => {
  editForm.value = { ...item };
  showEditModal.value = true;
};

const saveSalaryAdjustment = async () => {
  const gross = toNumber(editForm.value.base) + toNumber(editForm.value.bonus);
  const deduction = toNumber(editForm.value.deduction);
  const payload = {
    total_allowances: toNumber(editForm.value.bonus),
    total_deductions: deduction,
    gross_salary: gross,
    net_salary: gross - deduction,
  };
  try {
    await apiRequest(`/salary-details/${editForm.value.id}`, {
      method: 'PATCH',
      body: payload,
    });
    await loadData();
    showEditModal.value = false;
  } catch (error) {
    await showAlert('Cập nhật thất bại', error?.message || 'Không thể cập nhật bảng lương.');
  }
};

const viewPayslip = (item) => {
  console.log('Viewing payslip for:', item.name);
};

const approvePeriod = async () => {
  const ok = await showConfirm('Phê chuẩn quyết toán', `Bạn có chắc muốn chốt bảng lương tháng ${selectedPeriod.value.month}/${selectedPeriod.value.year}? Dữ liệu sau khi chốt sẽ không thể chỉnh sửa.`);
  if (ok) {
    try {
      await apiRequest(`/salary-periods/${selectedPeriod.value.id}/close`, {
        method: 'POST',
        body: {},
      });
      await loadData();
    } catch (error) {
      await showAlert('Chốt kỳ thất bại', error?.message || 'Không thể chốt kỳ lương.');
    }
  }
};

const createCurrentPeriod = async () => {
  const existing = periods.value.find((period) => period.month === currentMonth && period.year === currentYear);
  if (existing) {
    selectedPeriodId.value = existing.id;
    await showAlert('Kỳ lương đã tồn tại', `Kỳ ${currentMonth}/${currentYear} đã có trong hệ thống.`);
    return;
  }

  const month = currentMonth;
  const year = currentYear;
  const startDate = `${year}-${pad2(month)}-01`;
  const endDate = `${year}-${pad2(month)}-${new Date(year, month, 0).getDate()}`;
  const paymentDateObj = new Date(year, month, 5);
  const paymentDate = `${paymentDateObj.getFullYear()}-${pad2(paymentDateObj.getMonth() + 1)}-${pad2(paymentDateObj.getDate())}`;

  try {
    const created = await apiRequest('/salary-periods', {
      method: 'POST',
      body: {
        period_code: `SP-${year}-${pad2(month)}`,
        period_name: `Salary Period ${pad2(month)}/${year}`,
        period_type: 'MONTHLY',
        year,
        month,
        start_date: startDate,
        end_date: endDate,
        payment_date: paymentDate,
        standard_working_days: 26,
        status: 'OPEN',
      },
    });
    await loadData();
    const createdData = created?.data || created;
    selectedPeriodId.value = Number(createdData.periodId ?? createdData.period_id ?? createdData.id ?? selectedPeriodId.value);
  } catch (error) {
    await showAlert('Tạo kỳ lương thất bại', error?.message || 'Không thể tạo kỳ lương mới.');
  }
};

onMounted(async () => {
  await loadData();
  if (!selectedPeriodId.value && periods.value.length > 0) {
    const openPeriod = periods.value.find((period) => period.status === 'ĐANG_XỬ_LÝ');
    selectedPeriodId.value = openPeriod?.id ?? periods.value[0].id;
  }
});
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
