<template>
  <div class="space-y-6 pb-8">
    <!-- Header Area: SaaS Enterprise Style -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 bg-transparent text-left px-1">
      <div class="bg-transparent text-left">
        <h1 class="text-xl font-bold text-[var(--sys-text-primary)] mb-0.5 tracking-tight uppercase">Quản lý Tài sản Phòng ban</h1>
        <p class="text-[13px] text-[var(--sys-text-secondary)] font-medium flex items-center gap-3">
          Kiểm kê danh mục thiết bị và cơ sở vật chất thuộc quyền quản lý {{ deptName }}.
          <span class="px-2 py-0.5 bg-[var(--sys-brand-soft)] text-[var(--sys-brand-solid)] rounded-md border border-[var(--sys-brand-border)] text-[10px] font-bold uppercase tracking-widest shadow-sm">ASSET TRACKING MODE</span>
        </p>
      </div>
    </div>

    <!-- Assets Inventory Grid: Premium Card Style -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
      <div v-for="asset in assets" :key="asset.id" 
        class="bg-[var(--sys-bg-surface)] rounded-lg border border-[var(--sys-border-subtle)] shadow-sm overflow-hidden flex flex-col group hover:border-[var(--sys-brand-solid)] transition-all hover:shadow-lg">
        <div class="p-6 flex-grow space-y-5">
          <div class="flex items-center gap-5">
            <div class="w-16 h-16 rounded-md bg-[var(--sys-bg-page)] border border-[var(--sys-border-subtle)] flex items-center justify-center text-[var(--sys-brand-solid)] group-hover:scale-105 transition-transform shadow-inner">
              <span class="material-symbols-rounded text-[36px] font-bold">{{ asset.icon }}</span>
            </div>
            <div class="flex flex-col gap-1">
              <h4 class="text-[14px] font-bold text-[var(--sys-text-primary)] m-0 uppercase tracking-tight group-hover:text-[var(--sys-brand-solid)] transition-colors leading-tight">{{ asset.name }}</h4>
              <p class="text-[11px] font-bold text-[var(--sys-brand-solid)] uppercase tracking-widest opacity-80 leading-none">{{ asset.code }} <span class="mx-1 opacity-30 text-slate-400">|</span> {{ asset.category }}</p>
            </div>
          </div>

          <div class="space-y-3 py-4 border-t border-b border-[var(--sys-border-subtle)] border-dashed border-t-2 border-b-2">
             <div class="flex justify-between items-center text-[12.5px] font-bold text-[var(--sys-text-primary)]">
              <span class="text-[10px] font-bold uppercase tracking-widest text-[var(--sys-text-secondary)] opacity-60">Người chịu trách nhiệm:</span>
              <span class="text-[var(--sys-brand-solid)]">{{ asset.user }}</span>
            </div>
            <div class="flex justify-between items-center text-[12.5px] font-bold text-[var(--sys-text-primary)]">
              <span class="text-[10px] font-bold uppercase tracking-widest text-[var(--sys-text-secondary)] opacity-60">Hiện trạng vận hành:</span>
              <span :class="['px-2.5 py-0.5 rounded-md font-bold text-[10px] uppercase border shadow-sm tracking-widest', asset.status === 'Tốt' ? 'bg-[var(--sys-success-soft)] text-[var(--sys-success-text)] border-[var(--sys-success-border)]' : 'bg-[var(--sys-warning-soft)] text-[var(--sys-warning-text)] border-[var(--sys-warning-border)]']">{{ asset.status }}</span>
            </div>
          </div>
        </div>

        <div class="px-5 py-3.5 bg-[var(--sys-bg-page)]/30 border-t border-[var(--sys-border-subtle)] flex justify-between items-center transition-all duration-300">
          <span class="text-[10px] font-bold text-[var(--sys-text-disabled)] uppercase tracking-widest leading-none">Bàn giao: {{ asset.date }}</span>
          <button class="text-[10px] font-bold text-[var(--sys-brand-solid)] uppercase tracking-widest flex items-center gap-2 hover:opacity-80 transition-opacity">
            LỊCH SỬ BẢO TRÌ
            <span class="material-symbols-rounded text-[16px] font-bold">history_edu</span>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { apiRequest } from '@/services/beApi.js'
import { getSessionItem } from '@/services/session.js'

const userDeptId = Number(getSessionItem('userDeptId')) || 0
const assets = ref([])
const deptName = ref('Phòng ban')

const CATEGORY_ICON_MAP = {
  'Laptop': 'laptop_mac',
  'Màn hình': 'monitor',
  'Bàn phím': 'keyboard',
  'Chuột': 'mouse',
  'Máy in': 'print',
  'Server': 'dns',
}

const normalizeCategoryName = (asset = {}) => {
  const raw = String(asset.category_name || asset.category || asset.asset_name || '').trim()
  if (!raw) return 'Thiết bị'
  if (/laptop/i.test(raw)) return 'Laptop'
  if (/m[aà]n|monitor/i.test(raw)) return 'Màn hình'
  if (/chu[oộ]t|mouse/i.test(raw)) return 'Chuột'
  if (/b[aà]n ph[ií]m|keyboard/i.test(raw)) return 'Bàn phím'
  if (/m[aá]y in|printer/i.test(raw)) return 'Máy in'
  if (/server/i.test(raw)) return 'Server'
  return raw
}

const normalizeStatus = (status) => {
  const code = String(status || '').toUpperCase()
  if (['ASSIGNED', 'IN_USE', 'ĐANG_SỬ_DỤNG', 'ACTIVE'].includes(code)) return 'Tốt'
  if (['AVAILABLE', 'SẴN_SÀNG', 'SAN_SANG'].includes(code)) return 'Tốt'
  return 'Cần bảo trì'
}

const loadData = async () => {
  try {
    const [employeeRes, assetRes, assignmentRes] = await Promise.all([
      apiRequest('/employees', { query: { page: 1, per_page: 1000, department_id: userDeptId || undefined } }),
      apiRequest('/assets', { query: { page: 1, per_page: 1000 } }),
      apiRequest('/asset-assignments', { query: { page: 1, per_page: 1000, status: 'ASSIGNED' } }),
    ])

    const employees = Array.isArray(employeeRes?.data) ? employeeRes.data : []
    const allAssets = Array.isArray(assetRes?.data) ? assetRes.data : []
    const allAssignments = Array.isArray(assignmentRes?.data) ? assignmentRes.data : []

    if (employees.length > 0) {
      deptName.value = employees[0]?.department_name || getSessionItem('userDeptName') || 'Phòng ban'
    } else {
      deptName.value = getSessionItem('userDeptName') || 'Phòng ban'
    }

    const employeeMap = new Map(employees.map((emp) => [Number(emp.employee_id || 0), emp]))
    const scopeEmployeeIds = new Set(Array.from(employeeMap.keys()))
    const scopedAssignments = allAssignments.filter((assignment) => scopeEmployeeIds.has(Number(assignment.employee_id || 0)))
    const assetMap = new Map(allAssets.map((asset) => [Number(asset.asset_id || 0), asset]))

    assets.value = scopedAssignments.map((assignment) => {
      const asset = assetMap.get(Number(assignment.asset_id || 0)) || {}
      const emp = employeeMap.get(Number(assignment.employee_id || 0))
      const category = normalizeCategoryName(asset)
      return {
        id: Number(assignment.assignment_id || 0),
        name: String(asset.asset_name || 'THIẾT BỊ').toUpperCase(),
        code: asset.asset_code || 'N/A',
        category,
        user: emp?.full_name || assignment.employee_name || 'N/A',
        status: normalizeStatus(assignment.status || asset.status),
        date: String(assignment.assigned_date || asset.purchase_date || '').slice(0, 10) || 'N/A',
        icon: CATEGORY_ICON_MAP[category] || 'inventory_2',
      }
    })
  } catch (error) {
    console.error('Lỗi tải dữ liệu tài sản:', error);
    assets.value = []
  }
}

onMounted(loadData)
</script>

<style scoped>
* { -webkit-font-smoothing: antialiased; -moz-osx-font-smoothing: grayscale; }
</style>
