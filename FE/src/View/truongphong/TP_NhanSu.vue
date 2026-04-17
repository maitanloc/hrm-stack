<template>
  <div class="space-y-6 pb-8">
    <!-- Header Area: SaaS Enterprise Style -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 bg-transparent text-left px-1">
      <div class="bg-transparent text-left">
        <h1 class="text-xl font-bold text-[var(--sys-text-primary)] mb-0.5 tracking-tight uppercase">Quản lý Nhân sự Phòng ban</h1>
        <p class="text-[13px] text-[var(--sys-text-secondary)] font-medium flex items-center gap-3">
          Danh sách thành viên chính thức thuộc {{ deptName }}. 
          <span class="px-2 py-0.5 bg-[var(--sys-brand-soft)] text-[var(--sys-brand-solid)] rounded-md border border-[var(--sys-brand-border)] text-[10px] font-bold uppercase tracking-widest shadow-sm">VIEW ONLY MODE</span>
        </p>
      </div>
    </div>

    <!-- Filters: Compact and Professional -->
    <div class="bg-[var(--sys-bg-surface)] p-3.5 rounded-lg border border-[var(--sys-border-subtle)] shadow-sm flex flex-col md:flex-row gap-4 items-center">
      <div class="flex-1 relative w-full group">
        <div class="absolute left-3 top-1/2 -translate-y-1/2 w-8 h-8 rounded-md bg-[var(--sys-brand-soft)] text-[var(--sys-brand-solid)] flex items-center justify-center shrink-0 border border-[var(--sys-brand-border)] transition-all duration-300">
          <span class="material-symbols-rounded text-[18px] font-bold">search</span>
        </div>
        <input 
          v-model="searchQuery"
          type="text" 
          placeholder="Tìm kiếm danh tính, chức danh nghiệp vụ..." 
          class="w-full h-11 pl-12 pr-4 bg-[var(--sys-bg-page)]/50 border border-[var(--sys-border-strong)] rounded-md text-[13px] font-bold text-[var(--sys-text-primary)] outline-none focus:border-[var(--sys-brand-solid)] shadow-sm transition-all placeholder:text-slate-400"
        >
      </div>
      <div class="flex items-center gap-2 shrink-0">
        <Dropdown v-model="filterStatus" :options="statusOptions" class="min-w-[160px] h-11 shadow-sm" />
      </div>
    </div>

    <!-- Employee Grid: Premium Card Style -->
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-5">
      <div v-for="staff in filteredStaff" :key="staff.id" 
        class="bg-[var(--sys-bg-surface)] rounded-lg border border-[var(--sys-border-subtle)] shadow-sm overflow-hidden flex flex-col group hover:border-[var(--sys-brand-solid)] transition-all hover:shadow-lg">
        <!-- Card Indicator -->
        <div class="h-1.5 bg-gradient-to-r from-[var(--sys-brand-solid)] to-indigo-500"></div>
        
        <div class="p-5 flex flex-col items-center text-center space-y-4">
          <div class="relative">
            <div class="w-16 h-16 rounded-md bg-[var(--sys-bg-page)] border-2 border-[var(--sys-border-subtle)] flex items-center justify-center font-bold text-xl text-[var(--sys-brand-solid)] uppercase group-hover:scale-105 transition-transform shadow-inner">
              {{ staff.name.charAt(0) }}
            </div>
            <span :class="['absolute -bottom-1 -right-1 w-4 h-4 border-2 border-white rounded-full shadow-sm', staff.status === 'ĐANG_LÀM_VIỆC' ? 'bg-[var(--sys-success-solid)]' : 'bg-[var(--sys-warning-solid)]']"></span>
          </div>
          
          <div class="space-y-1">
            <h4 class="text-[13px] font-bold text-[var(--sys-text-primary)] m-0 uppercase tracking-tight transition-colors group-hover:text-[var(--sys-brand-solid)]">{{ staff.name }}</h4>
            <p class="text-[11px] font-bold text-[var(--sys-brand-solid)] uppercase tracking-widest opacity-80 leading-none">{{ staff.position }}</p>
          </div>
          
          <div class="w-full grid grid-cols-2 gap-3 pt-4 border-t border-[var(--sys-border-subtle)] border-dashed border-t-2">
            <div class="text-left space-y-0.5">
              <p class="text-[10px] font-bold text-[var(--sys-text-secondary)] uppercase tracking-widest opacity-60 shadow-none">ID CODE</p>
              <p class="text-[12px] font-bold text-[var(--sys-text-primary)]">#{{ staff.id }}</p>
            </div>
            <div class="text-left space-y-0.5">
              <p class="text-[10px] font-bold text-[var(--sys-text-secondary)] uppercase tracking-widest opacity-60 shadow-none">JOIN DATE</p>
              <p class="text-[12px] font-bold text-[var(--sys-text-primary)]">{{ staff.joinDate }}</p>
            </div>
          </div>
        </div>

        <!-- Action Footer -->
        <div class="px-4 py-2.5 bg-[var(--sys-bg-page)]/30 border-t border-[var(--sys-border-subtle)] flex justify-between items-center transition-all duration-300">
          <span class="text-[10px] font-bold text-[var(--sys-text-secondary)] uppercase tracking-widest">Status: {{ getStatusLabel(staff.status) }}</span>
          <button @click="viewDetails(staff)" class="text-[10px] font-bold text-[var(--sys-brand-solid)] uppercase tracking-widest flex items-center gap-1.5 transition-all hover:opacity-80 active:scale-95">
            Chi tiết hồ sơ
            <span class="material-symbols-rounded text-[14px] font-bold">arrow_forward</span>
          </button>
        </div>
      </div>
    </div>

    <!-- Modal Chi tiết hồ sơ -->
    <Teleport to="body">
      <Transition name="modal-fade">
        <div v-if="showModal" class="fixed inset-0 z-[10000] flex items-center justify-center p-4">
          <div class="fixed inset-0 w-screen h-screen bg-black/50 z-[9999]" @click="closeDetails"></div>
          <div class="relative z-[10000] bg-[var(--sys-bg-surface-elevated)] border border-[var(--sys-border-subtle)] w-full max-w-3xl max-h-[90vh] rounded-lg shadow-2xl overflow-hidden flex flex-col text-left">
            <!-- Modal Header -->
            <div class="px-6 py-4 border-b border-[var(--sys-border-subtle)] flex items-center justify-between bg-[var(--sys-bg-page)]/50">
              <div class="bg-transparent text-left flex items-center gap-3">
                <span class="material-symbols-rounded text-[var(--sys-brand-solid)] text-[24px]">contact_page</span>
                <div>
                  <h3 class="text-sm font-bold text-[var(--sys-text-primary)] m-0 uppercase tracking-wide">Hồ sơ nhân sự chi tiết</h3>
                  <p class="text-[11px] text-[var(--sys-text-secondary)] mt-0.5 font-medium uppercase tracking-widest opacity-80">CHỈ ĐỌC (VIEW ONLY)</p>
                </div>
              </div>
              <button @click="closeDetails" class="w-8 h-8 flex items-center justify-center rounded-md hover:bg-[var(--sys-bg-hover)] transition-all text-[var(--sys-text-secondary)] shadow-sm border border-transparent hover:border-[var(--sys-border-strong)]">
                <span class="material-symbols-rounded text-xl">close</span>
              </button>
            </div>

            <!-- Modal Body -->
            <div v-if="selectedStaff" class="flex-1 overflow-y-auto p-6 custom-scrollbar bg-[var(--sys-bg-surface)] space-y-8">
              <!-- Basic Info -->
              <div class="flex flex-col md:flex-row gap-6 items-start">
                <div class="w-24 h-24 rounded-lg bg-[var(--sys-bg-page)] border border-[var(--sys-border-subtle)] flex items-center justify-center font-bold text-3xl text-[var(--sys-brand-solid)] uppercase shadow-inner shrink-0 relative">
                  {{ selectedStaff.name.charAt(0) }}
                  <span :class="['absolute -bottom-1 -right-1 w-4 h-4 border-2 border-white rounded-full shadow-sm', selectedStaff.status === 'ĐANG_LÀM_VIỆC' ? 'bg-[var(--sys-success-solid)]' : 'bg-[var(--sys-warning-solid)]']"></span>
                </div>
                <div class="flex-1 space-y-4 w-full">
                  <div>
                    <h2 class="text-xl font-bold text-[var(--sys-text-primary)] mb-1 uppercase tracking-tight">{{ selectedStaff.name }}</h2>
                    <p class="text-[12px] font-bold text-[var(--sys-brand-solid)] uppercase tracking-widest opacity-90">{{ selectedStaff.position }}</p>
                  </div>
                  
                  <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                    <div class="space-y-1">
                      <p class="text-[10px] font-bold text-[var(--sys-text-secondary)] uppercase tracking-widest opacity-60">Mã nhân viên</p>
                      <p class="text-[13px] font-bold text-[var(--sys-text-primary)]">#{{ selectedStaff.id }}</p>
                    </div>
                    <div class="space-y-1">
                      <p class="text-[10px] font-bold text-[var(--sys-text-secondary)] uppercase tracking-widest opacity-60">Ngày gia nhập</p>
                      <p class="text-[13px] font-bold text-[var(--sys-text-primary)]">{{ selectedStaff.joinDate }}</p>
                    </div>
                    <div class="space-y-1">
                      <p class="text-[10px] font-bold text-[var(--sys-text-secondary)] uppercase tracking-widest opacity-60">Trạng thái</p>
                      <span :class="['px-2.5 py-0.5 rounded-md text-[10px] font-bold border inline-flex items-center uppercase tracking-wide', selectedStaff.status === 'ĐANG_LÀM_VIỆC' ? 'bg-[var(--sys-success-soft)] text-[var(--sys-success-text)] border-[var(--sys-success-border)]' : 'bg-[var(--sys-warning-soft)] text-[var(--sys-warning-text)] border-[var(--sys-warning-border)]']">
                        {{ getStatusLabel(selectedStaff.status) }}
                      </span>
                    </div>
                  </div>
                </div>
              </div>

              <!-- General Info -->
              <div>
                <h4 class="text-[12px] font-bold text-[var(--sys-text-primary)] border-b border-[var(--sys-border-subtle)] pb-2 mb-4 uppercase tracking-widest flex items-center gap-2">
                  <span class="material-symbols-rounded text-[18px] text-[var(--sys-brand-solid)]">info</span>
                  Thông tin phân bổ
                </h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4">
                  <div class="flex flex-col border border-[var(--sys-border-subtle)] p-3 rounded-md bg-[var(--sys-bg-page)]/50 shadow-sm border-l-2 border-l-[var(--sys-brand-solid)]">
                    <span class="text-[10px] font-bold text-[var(--sys-text-secondary)] uppercase tracking-widest opacity-70 mb-1">Cơ cấu tổ chức</span>
                    <span class="text-[13px] font-bold text-[var(--sys-text-primary)]">{{ selectedStaff.department || deptName }}</span>
                  </div>
                  <div class="flex flex-col border border-[var(--sys-border-subtle)] p-3 rounded-md bg-[var(--sys-bg-page)]/50 shadow-sm border-l-2 border-l-[var(--sys-brand-solid)]">
                    <span class="text-[10px] font-bold text-[var(--sys-text-secondary)] uppercase tracking-widest opacity-70 mb-1">Email công ty</span>
                    <span class="text-[13px] font-bold text-[var(--sys-text-primary)]">{{ selectedStaff.email || 'Chưa cập nhật' }}</span>
                  </div>
                  <div class="flex flex-col border border-[var(--sys-border-subtle)] p-3 rounded-md bg-[var(--sys-bg-page)]/50 shadow-sm border-l-2 border-l-[var(--sys-brand-solid)]">
                    <span class="text-[10px] font-bold text-[var(--sys-text-secondary)] uppercase tracking-widest opacity-70 mb-1">Số điện thoại</span>
                    <span class="text-[13px] font-bold text-[var(--sys-text-primary)]">{{ selectedStaff.phone || 'Chưa cập nhật' }}</span>
                  </div>
                  <div class="flex flex-col border border-[var(--sys-border-subtle)] p-3 rounded-md bg-[var(--sys-bg-page)]/50 shadow-sm border-l-2 border-l-[var(--sys-brand-solid)]">
                    <span class="text-[10px] font-bold text-[var(--sys-text-secondary)] uppercase tracking-widest opacity-70 mb-1">Báo cáo cho (Line Manager)</span>
                    <span class="text-[13px] font-bold text-[var(--sys-text-primary)]">{{ selectedStaff.managerName || 'Chưa cập nhật' }}</span>
                  </div>
                </div>
              </div>

            </div>

            <!-- Modal Footer -->
            <div class="px-6 py-4 border-t border-[var(--sys-border-subtle)] bg-[var(--sys-bg-page)] flex justify-end gap-3">
              <button @click="closeDetails" class="px-6 py-2 bg-white text-[var(--sys-text-secondary)] border border-[var(--sys-border-strong)] rounded-md font-bold text-[12px] hover:bg-[var(--sys-bg-hover)] shadow-sm uppercase tracking-wide transition-all active:scale-95">Đóng</button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import Dropdown from '@/components/Dropdown.vue'
import { apiRequest } from '@/services/beApi.js'

const searchQuery = ref('')
const filterStatus = ref('')
const showModal = ref(false)
const selectedStaff = ref(null)
const detailCache = ref(new Map())

const staffList = ref([])
const deptName = ref('Đang tải...')
const userDeptId = localStorage.getItem('userDeptId') || '1';

const statusOptions = [
  { label: 'Tất cả trạng thái', value: '' },
  { label: 'Đang làm việc', value: 'ĐANG_LÀM_VIỆC' },
  { label: 'Đã nghỉ việc', value: 'ĐÃ_NGHỈ_VIỆC' },
]

const toDisplayDate = (value) => {
  const date = new Date(String(value || ''))
  if (Number.isNaN(date.getTime())) return value || 'Chưa cập nhật'
  return `${String(date.getDate()).padStart(2, '0')}/${String(date.getMonth() + 1).padStart(2, '0')}/${date.getFullYear()}`
}

const normalizeStatus = (value) => {
  const raw = String(value || '').trim().toUpperCase()
  if (['ACTIVE', 'ĐANG_LÀM_VIỆC', 'DANG_LAM_VIEC'].includes(raw)) return 'ĐANG_LÀM_VIỆC'
  if (['INACTIVE', 'ĐÃ_NGHỈ_VIỆC', 'DA_NGHI_VIEC'].includes(raw)) return 'ĐÃ_NGHỈ_VIỆC'
  return raw || 'ĐANG_LÀM_VIỆC'
}

const buildStaffSummary = (employee, detail = null) => {
  const merged = { ...(employee || {}), ...(detail || {}) }
  return {
    id: merged.employee_id || merged.employeeId,
    name: merged.full_name || merged.fullName || 'Nhân viên',
    position: String(merged.position_name || merged.position?.positionName || 'Nhân viên').toUpperCase(),
    status: normalizeStatus(merged.status),
    joinDate: toDisplayDate(merged.hire_date || merged.hireDate),
    department: merged.department_name || merged.department?.departmentName || deptName.value,
    email: merged.company_email || '',
    phone: merged.phone_number || '',
    managerName: merged.manager_name || 'Trưởng phòng',
    avatarUrl: merged.avatarUrl || merged.avatar || null,
  }
}

const fetchEmployeeDetail = async (employeeId) => {
  if (!employeeId) return null
  const cache = detailCache.value.get(employeeId)
  if (cache) return cache
  try {
    const detailPayload = await apiRequest(`/employees/${employeeId}`)
    const detail = detailPayload?.data ?? null
    if (detail) detailCache.value.set(employeeId, detail)
    return detail
  } catch (error) {
    console.warn(`Không tải được chi tiết nhân viên #${employeeId}:`, error)
    return null
  }
}

const loadData = async () => {
  try {
    const [departmentPayload, employeePayload] = await Promise.all([
      apiRequest(`/departments/${userDeptId}`),
      apiRequest('/employees', {
        query: {
          department_id: userDeptId,
          per_page: 200,
        },
      }),
    ])

    deptName.value = departmentPayload?.data?.department_name || 'Phòng ban'
    const employeeItems = Array.isArray(employeePayload?.data) ? employeePayload.data : []
    const detailedEmployees = await Promise.all(
      employeeItems.map(async (employee) => buildStaffSummary(employee, await fetchEmployeeDetail(employee.employee_id || employee.employeeId)))
    )
    staffList.value = detailedEmployees
  } catch (error) {
    console.error('Lỗi khi tải dữ liệu nhân sự:', error);
  }
}

const viewDetails = async (staff) => {
  const detail = await fetchEmployeeDetail(staff.id)
  selectedStaff.value = buildStaffSummary(staff, detail)
  showModal.value = true
}

const closeDetails = () => {
  showModal.value = false
  setTimeout(() => { selectedStaff.value = null }, 200)
}

const filteredStaff = computed(() => {
  return staffList.value.filter(s => {
    const q = searchQuery.value.toLowerCase()
    const matchesQuery = s.name.toLowerCase().includes(q) || s.position.toLowerCase().includes(q)
    const matchesStatus = !filterStatus.value || s.status === filterStatus.value
    return matchesQuery && matchesStatus
  })
})

const getStatusLabel = (status) => {
  if (status === 'ĐANG_LÀM_VIỆC') return 'Active'
  if (status === 'ĐÃ_NGHỈ_VIỆC') return 'Nghỉ việc'
  return status || 'Active'
}

onMounted(loadData)
</script>

<style scoped>
/* Sub-pixel text rendering for premium feel */
* {
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
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

.modal-fade-enter-active,
.modal-fade-leave-active {
  transition: opacity 0.2s ease;
}

.modal-fade-enter-from,
.modal-fade-leave-to {
  opacity: 0;
}
</style>
