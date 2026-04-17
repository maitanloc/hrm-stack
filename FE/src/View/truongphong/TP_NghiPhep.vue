<template>
  <div class="space-y-6 pb-8">
    <!-- Header Area: SaaS Enterprise Style -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 bg-transparent text-left px-1">
      <div class="bg-transparent text-left">
        <h1 class="text-xl font-bold text-[var(--sys-text-primary)] mb-0.5 tracking-tight uppercase">Phê duyệt Nghỉ phép Phòng ban</h1>
        <p class="text-[13px] text-[var(--sys-text-secondary)] font-medium flex items-center gap-3">
          Xử lý các yêu cầu vắng mặt và điều phối nguồn lực nhân sự phòng ban.
          <span class="px-2 py-0.5 bg-[var(--sys-brand-soft)] text-[var(--sys-brand-solid)] rounded-md border border-[var(--sys-brand-border)] text-[10px] font-bold uppercase tracking-widest shadow-sm animate-pulse">PENDING: {{ pendingLeaves.length }}</span>
        </p>
      </div>
    </div>

    <!-- Tabs/Filters: Refined and Spacious -->
    <div class="flex border-b border-[var(--sys-border-subtle)] bg-transparent gap-8 px-1">
      <button 
        v-for="tab in ['Chờ duyệt', 'Đã duyệt', 'Đã từ chối']" :key="tab"
        :class="['pb-3 text-[11px] font-bold uppercase tracking-widest transition-all relative h-10 flex items-center', activeTab === tab ? 'text-[var(--sys-brand-solid)]' : 'text-[var(--sys-text-secondary)] opacity-40 hover:opacity-100']"
        @click="activeTab = tab"
      >
        {{ tab }} ({{ tab === 'Chờ duyệt' ? pendingLeaves.length : (tab === 'Đã duyệt' ? approvedLeaves.length : rejectedLeaves.length) }})
        <div v-if="activeTab === tab" class="absolute bottom-0 left-0 w-full h-[3px] bg-[var(--sys-brand-solid)] rounded-full shadow-sm"></div>
      </button>
    </div>

    <!-- Leave Request Grid: Premium Card Style -->
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5 items-stretch">
      <div v-for="leave in currentList" :key="leave.id" 
        class="bg-[var(--sys-bg-surface)] rounded-lg border border-[var(--sys-border-subtle)] shadow-sm overflow-hidden flex flex-col group hover:border-[var(--sys-brand-solid)] transition-all hover:shadow-lg">
        <div class="p-5 flex-grow space-y-4">
          <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-md bg-[var(--sys-bg-page)] border border-[var(--sys-border-subtle)] flex items-center justify-center font-bold text-lg text-[var(--sys-brand-solid)] uppercase shadow-inner">
              {{ leave.name.charAt(0) }}
            </div>
            <div class="flex flex-col">
              <h4 class="text-[14px] font-bold text-[var(--sys-text-primary)] m-0 uppercase tracking-tight group-hover:text-[var(--sys-brand-solid)] transition-colors">{{ leave.name }}</h4>
              <p class="text-[11px] font-bold text-[var(--sys-brand-solid)] uppercase tracking-widest opacity-80 leading-none">{{ leave.position }}</p>
            </div>
          </div>

          <div class="space-y-3 py-4 border-t border-b border-[var(--sys-border-subtle)] border-dashed border-t-2 border-b-2">
            <div class="flex justify-between items-center text-[12.5px] font-bold text-[var(--sys-text-primary)]">
              <span class="text-[10px] font-bold uppercase tracking-widest text-[var(--sys-text-secondary)] opacity-60">Loại hình:</span>
              <span class="text-[var(--sys-brand-solid)]">{{ leave.typeName }}</span>
            </div>
            <div class="flex justify-between items-center text-[12.5px] font-bold text-[var(--sys-text-primary)]">
              <span class="text-[10px] font-bold uppercase tracking-widest text-[var(--sys-text-secondary)] opacity-60">Thời gian thực tế:</span>
              <span class="text-[var(--sys-text-primary)]">{{ leave.range.split('(')[0] }}</span>
            </div>
            <div class="flex justify-between items-center text-[12.5px] font-bold text-[var(--sys-text-primary)]">
              <span class="text-[10px] font-bold uppercase tracking-widest text-[var(--sys-text-secondary)] opacity-60">Lý do xin nghỉ:</span>
              <span class="text-[var(--sys-text-primary)]">{{ leave.reason }}</span>
            </div>
            <div class="flex justify-between items-center text-[12.5px] font-bold text-[var(--sys-text-primary)]">
              <span class="text-[10px] font-bold uppercase tracking-widest text-[var(--sys-text-secondary)] opacity-60">Định mức trừ:</span>
              <span class="px-2 py-0.5 bg-[var(--sys-brand-soft)] text-[var(--sys-brand-solid)] rounded-md font-bold text-[11px]">{{ leave.days }} ngày công</span>
            </div>
          </div>
        </div>

        <div class="p-3 bg-[var(--sys-bg-page)]/30 px-4 pb-4 flex gap-2">
          <button @click="approve(leave.id)" class="flex-1 h-10 bg-[var(--sys-brand-solid)] text-white rounded-md font-bold text-[10px] uppercase tracking-widest hover:brightness-110 transition-all flex items-center justify-center gap-2 shadow-sm">
            <template v-if="leave.days > 7">
              <span class="material-symbols-rounded text-[18px] font-bold">forward</span> GỬI GIÁM ĐỐC
            </template>
            <template v-else>
              <span class="material-symbols-rounded text-[18px] font-bold">check_circle</span> DUYỆT ĐƠN
            </template>
          </button>
          <button @click="reject(leave.id)" class="flex-1 h-10 bg-[var(--sys-danger-soft)] text-[var(--sys-danger-text)] border border-[var(--sys-danger-border)] rounded-md font-bold text-[10px] uppercase tracking-widest hover:bg-[var(--sys-danger-solid)] hover:text-white transition-all flex items-center justify-center gap-2">
            <span class="material-symbols-rounded text-[18px] font-bold">cancel</span> TỪ CHỐI
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { apiRequest } from '@/services/beApi.js'

const activeTab = ref('Chờ duyệt')
const deptLeaveReqs = ref([])
const employeeDetailMap = ref(new Map())

const toDisplayDate = (value) => {
  const date = new Date(String(value || ''))
  if (Number.isNaN(date.getTime())) return value || 'N/A'
  return `${String(date.getDate()).padStart(2, '0')}/${String(date.getMonth() + 1).padStart(2, '0')}/${date.getFullYear()}`
}

const formatRange = (fromDate, toDate) => {
  const start = toDisplayDate(fromDate)
  const end = toDisplayDate(toDate)
  return start === end ? start : `${start} - ${end}`
}

const fetchEmployeeDetail = async (employeeId) => {
  if (!employeeId) return null
  const cached = employeeDetailMap.value.get(employeeId)
  if (cached) return cached
  try {
    const payload = await apiRequest(`/employees/${employeeId}`)
    const detail = payload?.data ?? null
    if (detail) employeeDetailMap.value.set(employeeId, detail)
    return detail
  } catch (error) {
    console.warn(`Không tải được chi tiết nhân viên #${employeeId}:`, error)
    return null
  }
}

const fetchData = async () => {
  try {
    const payload = await apiRequest('/leave-requests', {
      query: {
        page: 1,
        per_page: 300,
      },
    })
    deptLeaveReqs.value = Array.isArray(payload?.data) ? payload.data : []
    const ids = Array.from(new Set(deptLeaveReqs.value.map((item) => Number(item.employee_id)).filter((id) => Number.isFinite(id) && id > 0)))
    await Promise.all(ids.map((id) => fetchEmployeeDetail(id)))
  } catch (error) {
    console.error('Lỗi khi tải dữ liệu nghỉ phép TP:', error);
  }
}

const mapReq = (r) => {
  const employeeId = r.employee_id || r.employeeId || r.requester_id
  const employeeDetail = employeeDetailMap.value.get(employeeId) || null
  const requestStatus = String(r.request_status || r.status || '').toUpperCase()
  return {
    id: r.leave_request_id || r.id || r.requestId,
    employeeId,
    name: employeeDetail?.full_name || r.employee_name || 'Nhân viên N/A',
    position: String(employeeDetail?.position_name || 'Nhân viên').toUpperCase(),
    range: formatRange(r.from_date, r.to_date),
    typeName: r.leave_type_name || 'Nghỉ phép',
    days: Number(r.number_of_days || r.days || 0),
    reason: r.request_reason || r.request_notes || r.reason || 'Không ghi rõ',
    statusRaw: requestStatus,
  }
}

const pendingLeaves = computed(() => deptLeaveReqs.value.filter(r => String(r.request_status || '').toUpperCase() === 'CHỜ_DUYỆT').map(mapReq))
const approvedLeaves = computed(() => deptLeaveReqs.value.filter(r => ['ĐÃ_DUYỆT', 'CHỜ_XÁC_NHẬN_HR', 'CHỜ_GIÁM_ĐỐC_DUYỆT'].includes(String(r.request_status || '').toUpperCase())).map(mapReq))
const rejectedLeaves = computed(() => deptLeaveReqs.value.filter(r => String(r.request_status || '').toUpperCase() === 'TỪ_CHỐI').map(mapReq))

const currentList = computed(() => {
  if (activeTab.value === 'Đã duyệt') return approvedLeaves.value
  if (activeTab.value === 'Đã từ chối') return rejectedLeaves.value
  return pendingLeaves.value
})

const approve = async (id) => {
  try {
    const req = deptLeaveReqs.value.find(r => Number(r.leave_request_id || r.id || r.requestId) === Number(id));
    if (!req) return;
    await apiRequest(`/leave-requests/${id}`, {
      method: 'PATCH',
      body: { status: 'APPROVED' },
    });
    await fetchData();
  } catch (err) { console.error('Lỗi khi phê duyệt:', err); }
}

const reject = async (id) => {
  try {
    await apiRequest(`/leave-requests/${id}`, {
      method: 'PATCH',
      body: {
        status: 'REJECTED',
        rejectionReason: 'Đơn nghỉ phép chưa đáp ứng điều kiện phê duyệt của trưởng phòng.',
      },
    });
    await fetchData();
  } catch (err) { console.error('Lỗi khi từ chối:', err); }
}

onMounted(fetchData)

</script>

<style scoped>
* { -webkit-font-smoothing: antialiased; -moz-osx-font-smoothing: grayscale; }
</style>
