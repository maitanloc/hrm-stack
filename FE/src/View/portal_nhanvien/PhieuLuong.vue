<template>
  <div class="salary-wrapper min-h-screen bg-[var(--sys-bg-page)] text-[var(--sys-text-primary)] p-4 md:p-6 lg:p-8">
    <div class="max-w-7xl mx-auto space-y-6 bg-transparent">
      
      <!-- Header Area -->
      <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 bg-transparent text-left">
        <div class="bg-transparent text-left">
          <h1 class="text-xl font-semibold text-[var(--sys-text-primary)] mb-1">Thu nhập & Phiếu lương cá nhân</h1>
          <p class="text-sm text-[var(--sys-text-secondary)]">Theo dõi chi tiết thu nhập hàng tháng và các khoản trích đóng bảo hiểm.</p>
        </div>
        <div class="flex items-center gap-2 shrink-0 bg-transparent">
           <div class="px-3 py-1.5 bg-white rounded-md border border-[var(--sys-border-strong)] shadow-sm">
              <span class="text-[11px] font-bold text-[var(--sys-text-secondary)] uppercase tracking-wide mr-2">Năm tài chính</span>
              <span class="text-[13px] font-bold text-[var(--sys-brand-solid)]">{{ financialYear }}</span>
           </div>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 bg-transparent">
        
        <!-- Left Column: Period List -->
        <div class="lg:col-span-4 space-y-4 bg-transparent">
          <div class="bg-[var(--sys-bg-surface)] p-6 rounded-lg border border-[var(--sys-border-subtle)] shadow-sm h-full flex flex-col min-h-[500px]">
            <h2 class="text-xs font-bold text-[var(--sys-text-secondary)] uppercase tracking-wider mb-6 flex items-center gap-2">
              <span class="material-symbols-outlined text-[var(--sys-brand-solid)] text-[20px]">history</span>
              Danh sách kỳ lương
            </h2>
            
            <div class="flex flex-col gap-3 overflow-y-auto pr-1 custom-scrollbar flex-grow">
              <div 
                v-for="period in salaryPeriods" 
                :key="period.id"
                @click="selectedPeriodId = period.id"
                :class="[
                  'group p-4 rounded-md border transition-all cursor-pointer relative active:scale-[0.98]',
                  selectedPeriodId === period.id 
                    ? 'bg-[var(--sys-brand-soft)] border-[var(--sys-brand-border)] shadow-sm' 
                    : 'bg-white border-[var(--sys-border-strong)] hover:border-[var(--sys-brand-solid)]/30 hover:bg-[var(--sys-bg-page)]'
                ]"
              >
                <div v-if="selectedPeriodId === period.id" class="absolute left-0 top-0 bottom-0 w-1 bg-[var(--sys-brand-solid)] rounded-l-md"></div>
                
                <div class="flex justify-between items-start mb-3 bg-transparent">
                  <div class="bg-transparent text-left">
                    <h3 :class="['text-[13px] font-bold uppercase transition-colors', selectedPeriodId === period.id ? 'text-[var(--sys-brand-solid)]' : 'text-[var(--sys-text-primary)]']">Tháng {{ period.month }}/{{ period.year }}</h3>
                    <p class="text-[11px] text-[var(--sys-text-disabled)] font-medium m-0">Kỳ thanh toán: {{ period.payDate }}</p>
                  </div>
                  <span :class="['px-2 py-0.5 rounded-md text-[10px] font-bold uppercase border', period.status === 'Đã thanh toán' ? 'bg-[var(--sys-success-soft)] text-[var(--sys-success-text)] border-[var(--sys-success-border)]' : 'bg-[var(--sys-warning-soft)] text-[var(--sys-warning-text)] border-[var(--sys-warning-border)]']">
                    {{ period.status }}
                  </span>
                </div>

                <div class="flex justify-between items-end bg-transparent">
                  <div class="bg-transparent text-left">
                    <p class="text-[10px] font-bold text-[var(--sys-text-secondary)] uppercase tracking-wide mb-0.5 opacity-60">Thực nhận (Net)</p>
                    <p :class="['text-lg font-bold transition-colors m-0', selectedPeriodId === period.id ? 'text-[var(--sys-brand-solid)]' : 'text-[var(--sys-text-primary)]']">
                      {{ formatCurrency(period.net) }}
                    </p>
                  </div>
                  <div :class="['w-8 h-8 rounded-md flex items-center justify-center transition-all', selectedPeriodId === period.id ? 'bg-[var(--sys-brand-solid)] text-white' : 'bg-[var(--sys-bg-page)] text-[var(--sys-text-secondary)] border border-[var(--sys-border-subtle)]']">
                    <span class="material-symbols-outlined text-[18px]">{{ selectedPeriodId === period.id ? 'check' : 'chevron_right' }}</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Right Column: Detail View -->
        <div class="lg:col-span-8 bg-transparent">
          <div class="bg-[var(--sys-bg-surface)] rounded-lg border border-[var(--sys-border-subtle)] shadow-sm overflow-hidden flex flex-col h-full min-h-[600px]">
            
            <!-- Standardized Tabs -->
            <div class="px-6 py-4 border-b border-[var(--sys-border-subtle)] flex flex-wrap items-center justify-between gap-4 bg-[var(--sys-bg-page)]/50">
              <div class="flex items-center gap-1 bg-[var(--sys-bg-page)] p-1 rounded-md border border-[var(--sys-border-strong)] shadow-inner w-full md:w-auto">
                <button 
                  @click="activeTab = 'salary'" 
                  :class="[
                    'flex-1 md:flex-none px-6 py-1.5 rounded-md text-[11px] font-bold uppercase tracking-wide transition-all',
                    activeTab === 'salary' ? 'bg-white text-[var(--sys-brand-solid)] shadow-sm border border-[var(--sys-border-subtle)]' : 'text-[var(--sys-text-secondary)] hover:text-[var(--sys-text-primary)] opacity-70'
                  ]"
                >
                  Phiếu lương
                </button>
                <button 
                  @click="activeTab = 'insurance'" 
                  :class="[
                    'flex-1 md:flex-none px-6 py-1.5 rounded-md text-[11px] font-bold uppercase tracking-wide transition-all',
                    activeTab === 'insurance' ? 'bg-white text-[var(--sys-brand-solid)] shadow-sm border border-[var(--sys-border-subtle)]' : 'text-[var(--sys-text-secondary)] hover:text-[var(--sys-text-primary)] opacity-70'
                  ]"
                >
                  Bảo hiểm
                </button>
              </div>

              <div class="flex items-center gap-2 bg-transparent w-full md:w-auto justify-end" v-if="activeTab === 'salary'">
                <button @click="exportPDF" class="h-9 px-4 bg-[var(--sys-brand-solid)] text-white rounded-md text-[11px] font-bold uppercase tracking-wide hover:brightness-95 transition-all flex items-center gap-2 shadow-sm">
                  <span class="material-symbols-rounded text-[18px]">download</span>
                  Tải PDF
                </button>
              </div>
            </div>

            <!-- SALARY TAB CONTENT -->
            <div v-if="activeTab === 'salary'" class="flex-1 flex flex-col bg-transparent overflow-y-auto custom-scrollbar">
              <div class="p-8 md:p-10 space-y-10 bg-transparent">
                
                <!-- Document Header -->
                <div class="flex flex-col md:flex-row justify-between items-start gap-6 pb-8 border-b border-[var(--sys-border-subtle)]">
                  <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-md bg-[var(--sys-brand-soft)] text-[var(--sys-brand-solid)] flex items-center justify-center border border-[var(--sys-brand-border)]">
                      <span class="material-symbols-outlined text-[28px]">payments</span>
                    </div>
                    <div>
                      <h4 class="text-lg font-bold text-[var(--sys-text-primary)] uppercase tracking-tight mb-0.5">Chi tiết Phiếu lương</h4>
                      <p class="text-[11px] font-bold text-[var(--sys-brand-solid)] uppercase tracking-wider">Kỳ quyết toán: {{ currentPeriod.month }}/{{ currentPeriod.year }}</p>
                    </div>
                  </div>
                  
                  <div class="grid grid-cols-2 gap-x-6 gap-y-2 text-left bg-transparent w-full md:w-auto">
                    <div>
                      <p class="text-[10px] font-bold text-[var(--sys-text-secondary)] uppercase opacity-60 mb-0.5">Nhân viên</p>
                      <p class="text-[13px] font-bold text-[var(--sys-text-primary)]">{{ currentUserName }}</p>
                    </div>
                    <div>
                      <p class="text-[10px] font-bold text-[var(--sys-text-secondary)] uppercase opacity-60 mb-0.5">Mã số</p>
                      <p class="text-[13px] font-bold text-[var(--sys-text-primary)]">{{ currentUserCode }}</p>
                    </div>
                  </div>
                </div>

                <!-- Net Summary Banner -->
                <div class="bg-[var(--sys-brand-solid)] p-8 rounded-lg shadow-sm overflow-hidden relative group">
                  <div class="absolute top-0 right-0 w-48 h-48 bg-white/10 rounded-full -mr-24 -mt-24 blur-3xl"></div>
                  <div class="relative z-10 flex flex-col md:flex-row justify-between items-center gap-6">
                    <div class="text-center md:text-left">
                      <p class="text-[11px] font-bold text-white/80 uppercase tracking-widest mb-2">Thực lĩnh cuối cùng (NET)</p>
                      <h3 class="text-4xl font-bold text-white tracking-tight m-0">{{ formatCurrency(currentPeriod.net) }}</h3>
                    </div>
                    <div class="px-5 py-3 bg-white/20 rounded-md border border-white/20 text-center md:text-right backdrop-blur-sm">
                       <p class="text-[10px] font-bold text-white/60 uppercase tracking-wide mb-1">Trạng thái thanh toán</p>
                       <p class="text-[13px] font-bold text-white uppercase tracking-wider m-0 flex items-center justify-center md:justify-end gap-2">
                         <span class="w-1.5 h-1.5 rounded-full bg-white animate-pulse"></span>
                         {{ currentPeriod.status }}
                       </p>
                    </div>
                  </div>
                </div>

                <!-- Breakdown Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-10 bg-transparent">
                  <!-- Incomes -->
                  <div class="space-y-4">
                    <h5 class="text-[12px] font-bold text-[var(--sys-text-primary)] uppercase tracking-wider pb-3 border-b-2 border-[var(--sys-success-solid)] flex items-center gap-2">
                      Thu nhập (+)
                    </h5>
                    <div class="space-y-3">
                      <div v-for="inc in incomes" :key="inc.label" class="flex justify-between items-center group">
                        <span class="text-[13px] font-medium text-[var(--sys-text-secondary)] group-hover:text-[var(--sys-text-primary)] transition-colors">{{ inc.label }}</span>
                        <span class="text-[13px] font-bold text-[var(--sys-text-primary)]">{{ formatCurrency(inc.value) }}</span>
                      </div>
                      <div class="pt-4 mt-4 border-t border-[var(--sys-border-subtle)] flex justify-between items-center">
                        <span class="text-[12px] font-bold text-[var(--sys-text-primary)] uppercase">Tổng thu nhập</span>
                        <span class="text-base font-bold text-[var(--sys-success-text)]">{{ formatCurrency(totalIncome) }}</span>
                      </div>
                    </div>
                  </div>

                  <!-- Deductions -->
                  <div class="space-y-4">
                    <h5 class="text-[12px] font-bold text-[var(--sys-text-primary)] uppercase tracking-wider pb-3 border-b-2 border-[var(--sys-danger-solid)] flex items-center gap-2">
                      Khấu trừ (-)
                    </h5>
                    <div class="space-y-3">
                      <div v-for="ded in deductions" :key="ded.label" class="flex justify-between items-center group">
                        <span class="text-[13px] font-medium text-[var(--sys-text-secondary)] group-hover:text-[var(--sys-text-primary)] transition-colors">{{ ded.label }}</span>
                        <span class="text-[13px] font-bold text-[var(--sys-danger-text)]">-{{ formatCurrency(ded.value) }}</span>
                      </div>
                      <div class="pt-4 mt-4 border-t border-[var(--sys-border-subtle)] flex justify-between items-center">
                        <span class="text-[12px] font-bold text-[var(--sys-text-primary)] uppercase">Tổng khấu trừ</span>
                        <span class="text-base font-bold text-[var(--sys-danger-text)]">{{ formatCurrency(totalDeduction) }}</span>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Footer Disclaimer -->
                <div class="p-6 bg-[var(--sys-bg-page)] rounded-md border border-[var(--sys-border-subtle)] border-dashed">
                  <div class="flex gap-4 items-start">
                    <span class="material-symbols-outlined text-[var(--sys-brand-solid)] text-[24px]">verified_user</span>
                    <div class="bg-transparent text-left">
                      <p class="text-[11px] font-bold text-[var(--sys-text-primary)] uppercase tracking-wide mb-1.5">Thông tin xác thực hệ thống</p>
                      <p class="text-[12px] text-[var(--sys-text-secondary)] font-medium leading-relaxed m-0">
                        Chứng từ điện tử trích xuất từ FoxMobile HRM. Mọi thắc mắc về sai sót vui lòng phản hồi phòng <strong>C&B</strong> trước ngày {{ currentPeriod.feedbackDeadline }}.
                      </p>
                    </div>
                  </div>
                </div>

              </div>
            </div>

            <!-- INSURANCE TAB CONTENT -->
            <div v-if="activeTab === 'insurance'" class="flex-1 flex flex-col bg-transparent overflow-y-auto custom-scrollbar">
              <div class="p-8 md:p-10 space-y-10 bg-transparent">
                <div class="flex items-center gap-4 text-left">
                  <div class="w-12 h-12 rounded-md bg-[var(--sys-success-soft)] text-[var(--sys-success-text)] flex items-center justify-center border border-[var(--sys-success-border)]">
                    <span class="material-symbols-outlined text-[28px]">health_and_safety</span>
                  </div>
                  <div>
                    <h4 class="text-lg font-bold text-[var(--sys-text-primary)] uppercase mb-0.5">Quá trình tham gia Bảo hiểm</h4>
                    <p class="text-[11px] font-bold text-[var(--sys-success-text)] uppercase tracking-wider">Trạng thái: Đang tham gia (Active)</p>
                  </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 bg-transparent">
                   <div v-for="ins in insuranceStats" :key="ins.label" class="p-4 bg-white rounded-md border border-[var(--sys-border-strong)] shadow-sm group transition-all hover:border-[var(--sys-brand-solid)]">
                      <p class="text-[10px] font-bold text-[var(--sys-text-secondary)] uppercase tracking-wide mb-2 opacity-60 group-hover:text-[var(--sys-brand-solid)] transition-colors">{{ ins.label }}</p>
                      <p class="text-[14px] font-bold text-[var(--sys-text-primary)] m-0 leading-tight">{{ ins.value }}</p>
                   </div>
                </div>

                <div class="bg-white rounded-md border border-[var(--sys-border-subtle)] overflow-hidden shadow-sm">
                   <div class="px-5 py-3 border-b border-[var(--sys-border-subtle)] bg-[var(--sys-bg-page)]/50">
                      <h5 class="text-[11px] font-bold text-[var(--sys-text-secondary)] uppercase tracking-wide m-0">Định mức trích đóng hiện tại</h5>
                   </div>
                   <div class="overflow-x-auto">
                      <table class="w-full text-left border-collapse">
                         <thead class="bg-[var(--sys-bg-page)]">
                            <tr>
                               <th class="px-5 py-3 text-[12px] font-semibold text-[var(--sys-text-secondary)] uppercase border-b border-[var(--sys-border-subtle)]">Loại quỹ</th>
                               <th class="px-5 py-3 text-[12px] font-semibold text-[var(--sys-text-secondary)] uppercase border-b border-[var(--sys-border-subtle)] text-center">NLĐ (%)</th>
                               <th class="px-5 py-3 text-[12px] font-semibold text-[var(--sys-text-secondary)] uppercase border-b border-[var(--sys-border-subtle)] text-center">CTY (%)</th>
                               <th class="px-5 py-3 text-[12px] font-semibold text-[var(--sys-text-secondary)] uppercase border-b border-[var(--sys-border-subtle)] text-right">Mức đóng/Tháng</th>
                            </tr>
                         </thead>
                         <tbody class="divide-y divide-[var(--sys-border-subtle)]">
                            <tr v-for="row in insuranceRows" :key="row.label" class="group hover:bg-[var(--sys-bg-hover)] transition-colors">
                               <td class="px-5 py-4 text-[13px] font-bold text-[var(--sys-text-primary)] bg-transparent">{{ row.label }}</td>
                               <td class="px-5 py-4 text-[13px] font-bold text-[var(--sys-danger-text)] text-center bg-transparent">{{ row.employeeRate }}%</td>
                               <td class="px-5 py-4 text-[13px] font-bold text-[var(--sys-brand-solid)] text-center bg-transparent">{{ row.companyRate }}%</td>
                               <td class="px-5 py-4 text-[13px] font-bold text-[var(--sys-text-primary)] text-right bg-transparent">{{ formatCurrency(row.value) }}</td>
                            </tr>
                         </tbody>
                      </table>
                   </div>
                </div>

                <div class="p-6 bg-[var(--sys-info-soft)] rounded-md border border-[var(--sys-info-border)] flex items-start gap-4">
                   <div class="w-10 h-10 rounded-md bg-[var(--sys-info-solid)] text-white flex items-center justify-center shadow-md shrink-0">
                      <span class="material-symbols-outlined text-[20px]">info</span>
                   </div>
                   <div class="bg-transparent text-left">
                      <h4 class="text-[13px] font-bold text-[var(--sys-info-text)] mb-1 uppercase tracking-tight">Cổng thông tin Bảo hiểm xã hội</h4>
                      <p class="text-[12px] text-[var(--sys-info-text)] font-medium leading-relaxed m-0 opacity-90">
                        Số liệu được cập nhật tự động dựa trên mức lương đóng bảo hiểm đăng ký với cơ quan chức năng.
                      </p>
                   </div>
                </div>
              </div>
            </div>

          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
/**
 * TRANG PHIẾU LƯƠNG (PORTAL) - PHIÊN BẢN ENTERPRISE SaaS
 * Dùng data thực tế từ mock-data/BE và lấy theo user đang đăng nhập.
 */
import { ref, computed, onMounted, watch } from 'vue';
import { exportEmployeePayrollPDF } from '@/utils/pdfExport.js';
import { useCurrentUser } from '@/composables/useCurrentUser';
import { BE_API_BASE, getAccessToken } from '@/services/runtimeConfig.js';
import { handleUnauthorized } from '@/services/session.js';
import { parseJsonResponseSafely } from '@/utils/textEncodingFixed.js';

const { employeeId: currentEmpId, fullName, employeeCode, deptName, positionName } = useCurrentUser();
const mySalaries = ref([]);
const periodRows = ref([]);

const toNum = (value, fallback = 0) => {
  const parsed = Number(value);
  return Number.isFinite(parsed) ? parsed : fallback;
};

const pickMonthYear = (item) => {
  const periodMonthKey = String(item.period_month_key || '').trim();
  if (/^\d{4}-\d{2}$/.test(periodMonthKey)) {
    const [yearText, monthText] = periodMonthKey.split('-');
    return { month: toNum(monthText, 1), year: toNum(yearText, new Date().getFullYear()) };
  }

  const month = toNum(item.month, null);
  const year = toNum(item.year, null);
  if (month && year) return { month, year };

  const periodName = String(item.periodName ?? item.period_name ?? item.period_code ?? '').trim();
  const match = periodName.match(/(\d{1,2})[\/\-](\d{4})/);
  if (match) return { month: toNum(match[1], 1), year: toNum(match[2], new Date().getFullYear()) };

  const now = new Date();
  return { month: now.getMonth() + 1, year: now.getFullYear() };
};

const salaryPeriods = computed(() =>
  mySalaries.value.map((s) => {
    const periodId = Number(s.period_id ?? s.periodId ?? 0);
    const period = periodRows.value.find((item) => Number(item.period_id ?? item.periodId) === periodId) || null;
    const merged = period ? { ...s, ...period } : s;
    const { month, year } = pickMonthYear(merged);
    const mm = String(month).padStart(2, '0');
    const statusKey = String(s.transferStatus ?? s.transfer_status ?? '').toUpperCase();
    const payDateIso = String(period?.payment_date || period?.paymentDate || s.transfer_date || '').slice(0, 10);
    const payDate = /^\d{4}-\d{2}-\d{2}$/.test(payDateIso)
      ? `${payDateIso.slice(8, 10)}/${payDateIso.slice(5, 7)}/${payDateIso.slice(0, 4)}`
      : `05/${mm}/${year}`;
    return {
      id: Number(s.salaryDetailId ?? s.salaryId ?? s.salary_id ?? s.id),
      month: mm,
      year: String(year),
      payDate,
      status: statusKey === 'TRANSFERRED' ? 'Đã thanh toán' : 'Chờ thanh toán',
      net: toNum(s.netSalary ?? s.net_salary),
      feedbackDeadline: `10/${mm}/${year}`,
    };
  })
);

const activeTab = ref('salary');
const selectedPeriodId = ref(null);

const fallbackPeriod = {
  id: 0,
  month: '--',
  year: '----',
  payDate: '--/--/----',
  status: 'Chờ thanh toán',
  net: 0,
  feedbackDeadline: '--/--/----',
};

const currentPeriod = computed(() => {
  return salaryPeriods.value.find((p) => p.id === selectedPeriodId.value)
    || salaryPeriods.value[0]
    || fallbackPeriod;
});

const currentSalaryData = computed(() => {
  return mySalaries.value.find((s) => Number(s.salaryDetailId ?? s.salaryId ?? s.salary_id ?? s.id) === selectedPeriodId.value)
    || mySalaries.value[0]
    || {};
});

const incomes = computed(() => {
  const data = currentSalaryData.value;
  const basic = toNum(data.basicSalary ?? data.basic_salary);
  const overtime = toNum(data.overtimePay ?? data.overtime_pay);
  const allowances = toNum(data.totalAllowances ?? data.total_allowances ?? data.allowance);
  const bonus = toNum(data.bonus);
  const gross = toNum(data.grossSalary ?? data.gross_salary, basic + overtime + allowances + bonus);
  const shiftPay = Math.max(0, gross - basic - overtime - allowances - bonus);

  return [
    { label: 'Lương cơ bản (Gross)', value: basic },
    { label: 'Lương theo ca/ngày công', value: shiftPay },
    { label: 'Lương làm thêm giờ (OT)', value: overtime },
    { label: 'Phụ cấp / Trợ cấp', value: allowances },
    { label: 'Thưởng hiệu suất / Lễ Tết', value: bonus }
  ];
});

const totalIncome = computed(() => {
  const data = currentSalaryData.value;
  return toNum(data.grossSalary ?? data.gross_salary, incomes.value.reduce((sum, item) => sum + toNum(item.value), 0));
});

const deductions = computed(() => {
  const data = currentSalaryData.value;
  return [
    { label: 'BHXH', value: toNum(data.socialInsuranceEmployee ?? data.social_insurance_employee) },
    { label: 'BHYT', value: toNum(data.healthInsuranceEmployee ?? data.health_insurance_employee) },
    { label: 'BHTN', value: toNum(data.unemploymentInsuranceEmployee ?? data.unemployment_insurance_employee) },
    { label: 'Thuế TNCN tạm tính', value: toNum(data.personalIncomeTax ?? data.personal_income_tax ?? data.tax) },
    { label: 'Phạt / Khấu trừ đi muộn', value: toNum(data.penalty) }
  ];
});

const totalDeduction = computed(() => {
  const data = currentSalaryData.value;
  return toNum(data.totalDeductions ?? data.total_deductions, deductions.value.reduce((sum, item) => sum + toNum(item.value), 0));
});

const insuranceStats = ref([
  { label: 'Số sổ BHXH', value: 'BH2026-0987654' },
  { label: 'Nơi KCB Ban đầu', value: 'BV Đa khoa Xanh Pôn' },
  { label: 'Tổng quá trình', value: '03 Năm 04 Tháng' },
]);

const insuranceRows = ref([
  { label: 'BH Xã hội', employeeRate: 8, companyRate: 17.5, value: 2240000 },
  { label: 'BH Y tế', employeeRate: 1.5, companyRate: 3, value: 420000 },
  { label: 'BH Thất nghiệp', employeeRate: 1, companyRate: 1, value: 280000 },
]);

const formatCurrency = (val) => {
  return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(val);
};

const financialYear = computed(() => {
  const year = Number(currentPeriod.value?.year || 0);
  return Number.isFinite(year) && year > 0 ? String(year) : String(new Date().getFullYear());
});

const currentUserName = computed(() => fullName.value || localStorage.getItem('userName') || 'Nhân viên');
const currentUserCode = computed(() => employeeCode.value || localStorage.getItem('userCode') || 'N/A');

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
  return Array.isArray(payload?.data) ? payload.data : [];
};

const loadSalary = async () => {
  const employeeId = Number(currentEmpId.value);
  if (!Number.isFinite(employeeId) || employeeId <= 0) {
    mySalaries.value = [];
    selectedPeriodId.value = null;
    return;
  }

  let items = [];
  let periods = [];
  try {
    [items, periods] = await Promise.all([
      apiRequest('/salary-details?page=1&per_page=500'),
      apiRequest('/salary-periods?page=1&per_page=200'),
    ]);
  } catch (error) {
    console.error('Load salary error:', error);
    items = [];
    periods = [];
  }

  const normalized = Array.isArray(items) ? [...items] : [];
  periodRows.value = Array.isArray(periods) ? periods : [];
  normalized.sort((a, b) => {
    const am = toNum(a.month, 0);
    const ay = toNum(a.year, 0);
    const bm = toNum(b.month, 0);
    const by = toNum(b.year, 0);
    if (ay !== by) return by - ay;
    return bm - am;
  });
  mySalaries.value = normalized;
  selectedPeriodId.value = normalized[0]
    ? Number(normalized[0].salaryDetailId ?? normalized[0].salaryId ?? normalized[0].salary_id ?? normalized[0].id)
    : null;
};

onMounted(() => {
  void loadSalary();
});

watch(() => currentEmpId.value, () => {
  void loadSalary();
});

const exportPDF = () => {
  const period = currentPeriod.value
  const periodLabel = `Tháng ${period.month}/${period.year}`
  exportEmployeePayrollPDF({
    periodLabel,
    employeeName: currentUserName.value,
    employeeCode: currentUserCode.value,
    department: deptName.value || 'Phòng ban chưa cập nhật',
    position: positionName.value || 'Nhân viên',
    status: period.status,
    incomes: incomes.value,
    deductions: deductions.value,
    totalIncome: totalIncome.value,
    totalDeduction: totalDeduction.value,
    netAmount: period.net,
    insuranceRows: insuranceRows.value,
  })
};
</script>

<style scoped>
.salary-wrapper {
  background-color: var(--sys-bg-page);
}
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
</style>
