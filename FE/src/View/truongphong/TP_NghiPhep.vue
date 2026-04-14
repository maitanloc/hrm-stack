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
            <template v-if="leave.days > 3">
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
import { mockEmployees, mockRequestTypes } from '@/mock-data/index.js'

const activeTab = ref('Chờ duyệt')
const deptLeaveReqs = ref([])

const fetchData = async () => {
  try {
    const userDeptId = Number(localStorage.getItem('userDeptId')) || 2;
    const res = await fetch('http://localhost:3000/leaveRequests');
    const allLeaves = await res.json();
    
    // Lấy ID nhân viên cùng phòng
    const deptEmpIds = mockEmployees
      .filter(e => {
         const dId = e.department?.departmentId || e.departmentId || e.deptId;
         return Number(dId) === Number(userDeptId);
      })
      .map(e => e.employeeId);

    // Lọc các đơn của nhân viên trong phòng ban này
    deptLeaveReqs.value = allLeaves.filter(r => 
      deptEmpIds.includes(r.requesterId)
    );
  } catch (error) {
    console.error('Lỗi khi tải dữ liệu nghỉ phép TP:', error);
  }
}

onMounted(fetchData)

const mapReq = (r) => {
  // 1. Xác định định danh nhân sự đa kênh
  const empIdForLookup = r.requesterId || r.employeeId || r.userId;
  const lookupName = r.name || r.requesterName || r.requester_name;

  // 2. Tìm kiếm thông tin từ Mock Data
  const emp = mockEmployees.find(e => 
    (empIdForLookup && (String(e.employeeId) === String(empIdForLookup) || String(e.id) === String(empIdForLookup) || String(e.employeeCode) === String(empIdForLookup))) ||
    (lookupName && String(e.fullName).toLowerCase() === String(lookupName).toLowerCase())
  ) || {};
  
  const typeObj = mockRequestTypes.getById(r.requestTypeId);
  const typeName = r.requestTypeId === 99 ? (r.other_reason_name || 'Khác') : (typeObj?.requestTypeName || 'Nghỉ phép');
  
  const startD = r.startDate || r.start_date || 'N/A';
  const endD = r.endDate || r.end_date || startD;

  return {
    id: r.id || r.requestId,
    name: emp.fullName || lookupName || 'Nhân viên N/A',
    position: (emp?.position?.positionName || emp?.positionName || r.position || 'Nhân viên').toUpperCase(),
    range: startD === endD ? startD : `${startD} - ${endD}`,
    typeName: typeName,
    days: Number(r.days) || 0,
    reason: r.reason || r.notes || 'Không ghi rõ',
    statusRaw: r.status
  }
}

const pendingLeaves = computed(() => deptLeaveReqs.value.filter(r => r.status === 'CHỜ_DUYỆT').map(mapReq))
const approvedLeaves = computed(() => deptLeaveReqs.value.filter(r => r.status === 'ĐÃ_DUYỆT' || r.status === 'CHỜ_XÁC_NHẬN_HR' || r.status === 'CHỜ_GIÁM_ĐỐC_DUYỆT').map(mapReq))
const rejectedLeaves = computed(() => deptLeaveReqs.value.filter(r => r.status === 'TỪ_CHỐI').map(mapReq))

const currentList = computed(() => {
  if (activeTab.value === 'Đã duyệt') return approvedLeaves.value
  if (activeTab.value === 'Đã từ chối') return rejectedLeaves.value
  return pendingLeaves.value
})

const notifyUser = async (reqId, status) => {
  try {
    const req = deptLeaveReqs.value.find(r => (r.id || r.requestId) === reqId);
    if (!req) return;
    // 1. Thông báo cho nhân viên gửi đơn
    await fetch('http://localhost:3000/notifications', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        userId: req.requesterId,
        type: status === 'TỪ_CHỐI' ? 'danger' : 'info',
        title: 'Cập nhật đơn nghỉ phép',
        desc: status === 'CHỜ_GIÁM_ĐỐC_DUYỆT' ? 'Đơn của bạn đã được Trưởng phòng chuyển đến Ban Giám Đốc.' : (status === 'CHỜ_XÁC_NHẬN_HR' ? 'Đơn của bạn đang chờ HR xác nhận.' : 'Đơn của bạn đã được xử lý.'),
        time: 'Vừa xong',
        isRead: false,
        icon: 'notifications'
      })
    });

    // 2. Thông báo cho Giám đốc nếu cần duyệt (> 7 ngày)
    if (status === 'CHỜ_GIÁM_ĐỐC_DUYỆT') {
      const directors = mockEmployees.filter(e => e.role === 'DIRECTOR');
      for (const director of directors) {
        await fetch('http://localhost:3000/notifications', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            userId: director.employeeId || director.id,
            type: 'warning',
            title: 'Yêu cầu phê duyệt mới',
            desc: `Nhân viên ${req.name || req.requesterName} gửi đơn nghỉ phép ${req.days} ngày cần bạn phê duyệt.`,
            time: 'Vừa xong',
            isRead: false,
            icon: 'pending_actions'
          })
        });
      }
    }

    // 3. Thông báo cho HR nếu cần xác nhận chấm công
    if (status === 'CHỜ_XÁC_NHẬN_HR') {
      const hrs = mockEmployees.filter(e => e.role === 'HR');
      for (const hr of hrs) {
        await fetch('http://localhost:3000/notifications', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            userId: hr.employeeId || hr.id,
            type: 'info',
            title: 'Đơn nghỉ phép chờ xác nhận',
            desc: `Nhân viên ${req.name || req.requesterName} nghỉ ${req.days} ngày cần bạn xác nhận & chấm công.`,
            time: 'Vừa xong',
            isRead: false,
            icon: 'pending'
          })
        });
      }
    }
  } catch (e) { console.error('Notify Error:', e); }
};

const approve = async (id) => {
  try {
    const req = deptLeaveReqs.value.find(r => (r.id || r.requestId) === id);
    if (!req) return;
    
    const isNeedDirectorApproval = Number(req.days) > 7;
    const newStatus = isNeedDirectorApproval ? 'CHỜ_GIÁM_ĐỐC_DUYỆT' : 'CHỜ_XÁC_NHẬN_HR';
    const newStatusText = isNeedDirectorApproval ? 'Chờ Giám đốc duyệt' : 'Chờ HR xác nhận';

    await fetch(`http://localhost:3000/leaveRequests/${id}`, {
      method: 'PATCH',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ 
        status: newStatus,
        statusText: newStatusText,
        approver_manager: 'Trưởng phòng'
      })
    });
    
    await notifyUser(id, isNeedDirectorApproval ? 'CHỜ_GIÁM_ĐỐC_DUYỆT' : 'CHỜ_XÁC_NHẬN_HR');
    fetchData();
  } catch (err) { console.error('Lỗi khi phê duyệt:', err); }
}

const reject = async (id) => {
  try {
    await fetch(`http://localhost:3000/leaveRequests/${id}`, {
      method: 'PATCH',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ status: 'TỪ_CHỐI' })
    });
    await notifyUser(id, 'TỪ_CHỐI');
    fetchData();
  } catch (err) { console.error('Lỗi khi từ chối:', err); }
}
</script>

<style scoped>
* { -webkit-font-smoothing: antialiased; -moz-osx-font-smoothing: grayscale; }
</style>
