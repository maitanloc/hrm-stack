<template>
  <div class="space-y-6 pb-8">
    <!-- Header Area -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 bg-transparent text-left px-1">
      <div class="bg-transparent text-left">
        <h1 class="text-xl font-bold text-[var(--sys-text-primary)] mb-0.5 tracking-tight uppercase">Quyết toán Tiền lương Phòng ban</h1>
        <p class="text-[13px] text-[var(--sys-text-secondary)] font-medium flex items-center gap-3">
          Thống kê chi phí nhân sự và quỹ lương tháng của đơn vị {{ deptName }}.
          <span class="px-2 py-0.5 bg-[var(--sys-brand-soft)] text-[var(--sys-brand-solid)] rounded-md border border-[var(--sys-brand-border)] text-[10px] font-bold uppercase tracking-widest shadow-sm">CONFIDENTIAL VIEW</span>
        </p>
      </div>
      <div class="flex items-center gap-3">
        <Dropdown v-model="selectedPeriod" :options="periodOptions" class="min-w-[170px] h-11 shadow-sm" />
        <button
          @click="exportPDF"
          class="h-11 px-5 bg-[var(--sys-brand-solid)] text-white rounded-md text-[11px] font-bold uppercase tracking-wide hover:brightness-95 transition-all flex items-center gap-2 shadow-sm"
        >
          <span class="material-symbols-rounded text-[18px]">download</span>
          Tải PDF
        </button>
      </div>
    </div>

    <!-- Summary Stats -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
      <div v-for="stat in quickStats" :key="stat.label"
        class="bg-[var(--sys-bg-surface)] p-6 rounded-lg border border-[var(--sys-border-subtle)] shadow-sm flex items-center justify-between group hover:border-[var(--sys-brand-solid)] transition-all hover:shadow-lg">
        <div>
          <p class="text-[11px] font-bold text-[var(--sys-text-secondary)] uppercase tracking-widest mb-1 opacity-60">{{ stat.label }}</p>
          <h3 :class="['text-2xl font-bold leading-none tracking-tight', stat.textClass]">{{ stat.value }}</h3>
        </div>
        <div :class="['w-12 h-12 rounded-md flex items-center justify-center border shadow-sm group-hover:scale-105 transition-transform duration-300', stat.bgClass, stat.colorClass, stat.borderClass]">
          <span class="material-symbols-rounded text-2xl font-bold">{{ stat.icon }}</span>
        </div>
      </div>
    </div>

    <!-- Payroll Table -->
    <div class="bg-[var(--sys-bg-surface)] rounded-lg border border-[var(--sys-border-subtle)] shadow-sm overflow-hidden">
      <div class="px-5 py-3.5 border-b border-[var(--sys-border-subtle)] flex justify-between items-center bg-[var(--sys-bg-page)]/30">
        <h3 class="text-[11px] font-bold text-[var(--sys-text-primary)] uppercase tracking-widest m-0 flex items-center gap-2.5">
          <div class="w-8 h-8 rounded-md bg-[var(--sys-brand-soft)] text-[var(--sys-brand-solid)] border border-[var(--sys-brand-border)] flex items-center justify-center shrink-0">
            <span class="material-symbols-rounded text-[20px] font-bold">assignment_turned_in</span>
          </div>
          BẢNG KÊ CHI TIẾT THU NHẬP — KỲ {{ selectedPeriodLabel }}
        </h3>
        <span class="text-[11px] font-bold text-[var(--sys-text-secondary)] opacity-60">{{ payrollList.length }} nhân sự</span>
      </div>
      <div class="overflow-x-auto custom-scrollbar">
        <table class="w-full text-left border-collapse min-w-[1000px]">
          <thead class="bg-[var(--sys-bg-page)]/40">
            <tr>
              <th class="px-6 py-4 text-[11px] font-bold text-[var(--sys-text-secondary)] border-b border-[var(--sys-border-subtle)] uppercase tracking-widest">STT</th>
              <th class="px-6 py-4 text-[11px] font-bold text-[var(--sys-text-secondary)] border-b border-[var(--sys-border-subtle)] uppercase tracking-widest">Nhân sự thực hiện</th>
              <th class="px-6 py-4 text-[11px] font-bold text-[var(--sys-text-secondary)] border-b border-[var(--sys-border-subtle)] uppercase tracking-widest">Lương cơ bản</th>
              <th class="px-6 py-4 text-[11px] font-bold text-[var(--sys-text-secondary)] border-b border-[var(--sys-border-subtle)] uppercase tracking-widest">Phụ cấp & Thưởng</th>
              <th class="px-6 py-4 text-[11px] font-bold text-[var(--sys-text-secondary)] border-b border-[var(--sys-border-subtle)] uppercase tracking-widest">Các khoản trừ</th>
              <th class="px-6 py-4 text-[11px] font-bold text-[var(--sys-text-secondary)] border-b border-[var(--sys-border-subtle)] uppercase tracking-widest text-right">Tổng thực lĩnh</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-[var(--sys-border-subtle)]">
            <tr v-for="(staff, idx) in payrollList" :key="staff.id" class="hover:bg-[var(--sys-bg-hover)] transition-all duration-200 group">
              <td class="px-6 py-4 text-[13px] font-bold text-[var(--sys-text-secondary)] opacity-60">{{ idx + 1 }}</td>
              <td class="px-6 py-4">
                <div class="flex items-center gap-4">
                  <div class="w-10 h-10 rounded-md bg-[var(--sys-bg-page)] border border-[var(--sys-border-subtle)] flex items-center justify-center font-bold text-xs text-[var(--sys-brand-solid)] shadow-sm group-hover:bg-[var(--sys-brand-solid)] group-hover:text-white transition-all">
                    {{ staff.name.charAt(0) }}
                  </div>
                  <div class="flex flex-col">
                    <h4 class="text-[13px] font-bold text-[var(--sys-text-primary)] m-0 uppercase tracking-tight group-hover:text-[var(--sys-brand-solid)] transition-colors">{{ staff.name }}</h4>
                    <p class="text-[11px] font-bold text-[var(--sys-text-secondary)] m-0 uppercase tracking-widest opacity-60 leading-none">{{ staff.position }}</p>
                  </div>
                </div>
              </td>
              <td class="px-6 py-4 text-[13px] font-bold text-[var(--sys-text-primary)] opacity-80">{{ staff.base }} đ</td>
              <td class="px-6 py-4 text-[13px] font-bold text-[var(--sys-success-text)]">+ {{ staff.bonus }} đ</td>
              <td class="px-6 py-4 text-[13px] font-bold text-[var(--sys-danger-text)]">- {{ staff.deduct }} đ</td>
              <td class="px-6 py-4 text-right">
                <span class="text-[14px] font-bold text-[var(--sys-brand-solid)] tracking-tight">{{ staff.total }} đ</span>
              </td>
            </tr>
          </tbody>
          <tfoot class="bg-[var(--sys-bg-page)]/60 border-t-2 border-[var(--sys-border-subtle)]">
            <tr>
              <td colspan="2" class="px-6 py-4 text-[12px] font-bold text-[var(--sys-text-primary)] uppercase tracking-widest">TỔNG CỘNG</td>
              <td class="px-6 py-4 text-[13px] font-bold text-[var(--sys-text-primary)]">{{ totalBase }} đ</td>
              <td class="px-6 py-4 text-[13px] font-bold text-[var(--sys-success-text)]">+ {{ totalBonus }} đ</td>
              <td class="px-6 py-4 text-[13px] font-bold text-[var(--sys-danger-text)]">- {{ totalDeduct }} đ</td>
              <td class="px-6 py-4 text-right text-[15px] font-bold text-[var(--sys-brand-solid)]">{{ totalNet }} đ</td>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, watch, computed } from 'vue'
import Dropdown from '@/components/Dropdown.vue'
import { exportManagerPayrollPDF } from '@/utils/pdfExport.js'
import { apiRequest } from '@/services/beApi.js'
import { getSessionItem } from '@/services/session.js'

const selectedPeriod = ref(null)
const userDeptId = Number(getSessionItem('userDeptId')) || 0
const deptName = ref('Phòng ban')

const periodOptions = ref([])

const payrollList = ref([])
const quickStats = ref([])
const totalBase = ref('0')
const totalBonus = ref('0')
const totalDeduct = ref('0')
const totalNet = ref('0')
const employeeDetailCache = new Map()

const fmt = (num) => new Intl.NumberFormat('vi-VN').format(Math.round(num))
const selectedPeriodLabel = computed(
  () => periodOptions.value.find((item) => item.value === selectedPeriod.value)?.label || '--'
)

const buildPeriodLabel = (period) => {
  const month = Number(period.month || 0)
  const year = Number(period.year || 0)
  if (month >= 1 && month <= 12 && year > 0) {
    return `Tháng ${String(month).padStart(2, '0')}/${year}`
  }
  return period.period_name || period.period_code || `Kỳ ${period.period_id}`
}

const getEmployeePosition = async (employeeId) => {
  if (!employeeId) return 'Nhân viên'
  if (employeeDetailCache.has(employeeId)) return employeeDetailCache.get(employeeId)
  try {
    const res = await apiRequest(`/employees/${employeeId}`)
    const detail = res?.data || {}
    const positionName = String(detail.position_name || detail.positionName || 'Nhân viên')
    employeeDetailCache.set(employeeId, positionName)
    return positionName
  } catch {
    employeeDetailCache.set(employeeId, 'Nhân viên')
    return 'Nhân viên'
  }
}

const bootstrapPeriodOptions = async () => {
  const periodRes = await apiRequest('/salary-periods', { query: { page: 1, per_page: 24 } })
  const periods = Array.isArray(periodRes?.data) ? periodRes.data : []
  const sorted = [...periods].sort((a, b) => Number(b.period_id || 0) - Number(a.period_id || 0))
  periodOptions.value = sorted.map((period) => ({
    label: buildPeriodLabel(period),
    value: Number(period.period_id),
    monthKey:
      (Number(period.year || 0) > 0 && Number(period.month || 0) > 0)
        ? `${period.year}-${String(period.month).padStart(2, '0')}`
        : String(period.start_date || '').slice(0, 7),
  }))
  if (!selectedPeriod.value && periodOptions.value.length > 0) {
    selectedPeriod.value = periodOptions.value[0].value
  }
}

const loadData = async () => {
  try {
    if (!selectedPeriod.value) {
      payrollList.value = []
      quickStats.value = []
      totalBase.value = '0'
      totalBonus.value = '0'
      totalDeduct.value = '0'
      totalNet.value = '0'
      return
    }

    const [employeeRes, detailRes] = await Promise.all([
      apiRequest('/employees', { query: { page: 1, per_page: 1000, department_id: userDeptId || undefined } }),
      apiRequest('/salary-details', { query: { page: 1, per_page: 1000, period_id: selectedPeriod.value } }),
    ])

    const scopedEmployees = Array.isArray(employeeRes?.data) ? employeeRes.data : []
    const details = Array.isArray(detailRes?.data) ? detailRes.data : []
    const scopeEmployeeIds = new Set(scopedEmployees.map((emp) => Number(emp.employee_id || 0)))
    const scopedDetails = details.filter((detail) => scopeEmployeeIds.has(Number(detail.employee_id || 0)))

    if (scopedEmployees.length > 0) {
      deptName.value = scopedEmployees[0]?.department_name || getSessionItem('userDeptName') || 'Phòng ban'
    } else {
      deptName.value = getSessionItem('userDeptName') || 'Phòng ban'
    }

    const monthKey = periodOptions.value.find((item) => item.value === selectedPeriod.value)?.monthKey || ''
    const adjustmentRes = monthKey
      ? await apiRequest('/payroll-adjustments', { query: { page: 1, per_page: 1000, apply_month: monthKey } })
      : { data: [] }
    const adjustments = Array.isArray(adjustmentRes?.data) ? adjustmentRes.data : []
    const scopeAdjustments = adjustments.filter((adj) => scopeEmployeeIds.has(Number(adj.employee_id || 0)))
    const adjustmentMap = new Map()
    scopeAdjustments.forEach((adj) => {
      const empId = Number(adj.employee_id || 0)
      adjustmentMap.set(empId, (adjustmentMap.get(empId) || 0) + Number(adj.amount || 0))
    })

    let sumBase = 0
    let sumBonus = 0
    let sumDeduct = 0
    let sumNet = 0

    const rows = []
    for (const detail of scopedDetails) {
      const empId = Number(detail.employee_id || 0)
      const pendingAdjustment = Number(adjustmentMap.get(empId) || 0)
      const baseSalary = Number(detail.basic_salary || 0)
      const bonusPart =
        Number(detail.total_allowances || 0) +
        Number(detail.overtime_pay || 0) +
        Number(detail.bonus || 0) +
        Math.max(0, pendingAdjustment)
      const deductPart =
        Number(detail.total_deductions || 0) +
        Number(detail.penalty || 0) +
        Number(detail.personal_income_tax || 0) +
        Number(detail.advance_payment || 0) +
        Math.abs(Math.min(0, pendingAdjustment))
      const netSalary = Number(detail.net_salary || 0) + pendingAdjustment

      sumBase += baseSalary
      sumBonus += bonusPart
      sumDeduct += deductPart
      sumNet += netSalary

      const positionName = await getEmployeePosition(empId)
      rows.push({
        id: Number(detail.salary_detail_id || 0),
        name: detail.full_name || `Nhân sự #${empId}`,
        position: String(positionName).toUpperCase(),
        base: fmt(baseSalary),
        bonus: fmt(bonusPart),
        deduct: fmt(deductPart),
        total: fmt(netSalary),
      })
    }

    payrollList.value = rows
    totalBase.value = fmt(sumBase);
    totalBonus.value = fmt(sumBonus);
    totalDeduct.value = fmt(sumDeduct);
    totalNet.value = fmt(sumNet);

    quickStats.value = [
      { label: 'QUỸ LƯƠNG TỔNG', value: fmt(sumNet) + ' đ', icon: 'account_balance_wallet', bgClass: 'bg-[var(--sys-brand-soft)]', colorClass: 'text-[var(--sys-brand-solid)]', borderClass: 'border-[var(--sys-brand-border)]', textClass: 'text-[var(--sys-text-primary)]' },
      { label: 'THƯỞNG & PHỤ CẤP', value: fmt(sumBonus) + ' đ', icon: 'stars', bgClass: 'bg-[var(--sys-success-soft)]', colorClass: 'text-[var(--sys-success-text)]', borderClass: 'border-[var(--sys-success-border)]', textClass: 'text-[var(--sys-success-text)]' },
      { label: 'TỔNG KHẤU TRỪ', value: '- ' + fmt(sumDeduct) + ' đ', icon: 'money_off', bgClass: 'bg-[var(--sys-danger-soft)]', colorClass: 'text-[var(--sys-danger-text)]', borderClass: 'border-[var(--sys-danger-border)]', textClass: 'text-[var(--sys-danger-text)]' },
    ];
  } catch (error) {
    console.error('Lỗi khi tải dữ liệu lương:', error);
    payrollList.value = []
  }
}

onMounted(async () => {
  await bootstrapPeriodOptions()
  await loadData()
})
watch(selectedPeriod, loadData)

// ─── PDF Export ──────────────────────────────────────────────────────────────
const exportPDF = async () => {
  const periodLabel = periodOptions.value.find(p => p.value === selectedPeriod.value)?.label || selectedPeriod.value

  exportManagerPayrollPDF({
    periodLabel,
    deptName: deptName.value,
    payrollList: payrollList.value,
    totalBase: totalBase.value,
    totalBonus: totalBonus.value,
    totalDeduct: totalDeduct.value,
    totalNet: totalNet.value,
  })
}
</script>

<style scoped>
.custom-scrollbar::-webkit-scrollbar { height: 5px; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: var(--sys-border-subtle); border-radius: 5px; }
* { -webkit-font-smoothing: antialiased; -moz-osx-font-smoothing: grayscale; }
</style>
