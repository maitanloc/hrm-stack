<template>
  <div class="space-y-6 pb-8">
    <!-- Header Area -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 bg-transparent text-left">
      <div class="bg-transparent text-left">
        <h1 class="text-xl font-semibold text-[var(--sys-text-primary)] mb-1 uppercase tracking-tight">Tổng quan Hệ thống Quản trị</h1>
        <p class="text-[13px] text-[var(--sys-text-secondary)]">Theo dõi biến động nhân sự, hiệu suất vận hành và tiến độ công việc trong thời gian thực.</p>
      </div>
      <div class="flex items-center gap-3 bg-transparent shrink-0">
        <div class="hidden md:flex items-center gap-2 px-3 py-1.5 bg-[var(--sys-bg-page)] border border-[var(--sys-border-subtle)] rounded-md text-[var(--sys-text-primary)] text-[11px] font-bold shadow-sm uppercase tracking-wider">
          <span class="w-1.5 h-1.5 rounded-full bg-[var(--sys-success-solid)] animate-pulse"></span>
          Hệ thống trực tuyến
        </div>
        <div class="hidden md:block bg-[var(--sys-brand-soft)] text-[var(--sys-brand-solid)] px-4 py-1.5 rounded-md font-bold text-[11px] border border-[var(--sys-brand-border)] shadow-sm uppercase tracking-wider">
          Phiên bản 3.0
        </div>
      </div>
    </div>

    <!-- Stats Section (Directive 1 & 5) -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
      <div v-for="(stat, idx) in stats" :key="idx" 
        class="bg-[var(--sys-bg-surface)] p-4 rounded-lg border border-[var(--sys-border-subtle)] shadow-sm hover:border-[var(--sys-brand-solid)] transition-all flex items-center gap-4 group">
        <!-- Universal Icon Wrapper (Directive 1) -->
        <div :class="[
          'w-10 h-10 rounded-md flex items-center justify-center transition-all border shrink-0',
          getStatIconClass(stat.color)
        ]">
          <span class="material-symbols-outlined text-xl">{{ stat.icon }}</span>
        </div>
        <div class="bg-transparent flex flex-col flex-1 overflow-hidden">
          <div class="flex items-center justify-between mb-0.5">
            <p class="text-[11px] font-bold text-[var(--sys-text-secondary)] uppercase tracking-widest opacity-60 truncate">{{ stat.label }}</p>
            <span :class="[
              'text-[10px] font-bold px-1.5 py-0.5 rounded-md border uppercase tracking-tighter',
              getStatIconClass(stat.color)
            ]">
              {{ stat.change }}
            </span>
          </div>
          <h2 class="text-xl font-bold text-[var(--sys-text-primary)] leading-tight m-0 tracking-tight">{{ stat.value }}</h2>
        </div>
      </div>
    </div>

    <!-- Charts Section (Directive 1 & 5) -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-stretch">
      <!-- Personnel Trends -->
      <div class="bg-[var(--sys-bg-surface)] p-4 rounded-lg border border-[var(--sys-border-subtle)] shadow-sm flex flex-col hover:border-[var(--sys-brand-solid)] transition-all">
        <div class="flex justify-between items-center mb-6">
          <h5 class="text-[13px] font-bold text-[var(--sys-text-primary)] flex items-center gap-2 uppercase tracking-widest leading-none">
            <div class="w-8 h-8 rounded-md bg-[var(--sys-brand-soft)] text-[var(--sys-brand-solid)] flex items-center justify-center border border-[var(--sys-brand-border)]">
              <span class="material-symbols-outlined text-[18px]">analytics</span>
            </div>
            Biến động nhân sự
          </h5>
          <Dropdown v-model="timeRange" :options="timeRangeOptions" class="min-w-[150px] h-9" />
        </div>
        
        <div class="flex items-end justify-around flex-grow border-b border-[var(--sys-border-subtle)] pb-4 min-h-[200px]">
          <div v-for="i in 6" :key="i" class="flex gap-1 items-end justify-center w-full bg-transparent group/bar max-w-[40px]">
            <div class="w-3 bg-[var(--sys-brand-solid)] rounded-t-sm shadow-sm transition-all h-32 hover:brightness-110" :style="{ height: (Math.random() * 80 + 40) + 'px' }"></div>
            <div class="w-3 bg-[var(--sys-brand-soft)] rounded-t-sm border border-[var(--sys-brand-border)] transition-all h-20 hover:brightness-105" :style="{ height: (Math.random() * 40 + 20) + 'px' }"></div>
          </div>
        </div>
        <div class="flex justify-between pt-3 text-[10px] font-bold text-[var(--sys-text-secondary)] uppercase tracking-widest opacity-60 px-4">
          <span>TH 01</span><span>TH 02</span><span>TH 03</span><span>TH 04</span><span>TH 05</span><span>TH 06</span>
        </div>
        
        <div class="flex justify-center gap-6 mt-6">
          <div class="flex items-center gap-2">
            <div class="w-2.5 h-2.5 rounded-sm bg-[var(--sys-brand-solid)]"></div>
            <span class="text-[10px] font-bold text-[var(--sys-text-secondary)] uppercase tracking-widest opacity-80">Tuyển mới</span>
          </div>
          <div class="flex items-center gap-2">
            <div class="w-2.5 h-2.5 rounded-sm bg-[var(--sys-brand-soft)] border border-[var(--sys-brand-border)]"></div>
            <span class="text-[10px] font-bold text-[var(--sys-text-secondary)] uppercase tracking-widest opacity-80">Nghỉ việc</span>
          </div>
        </div>
      </div>

      <!-- Resource Allocation (Directive 5) -->
      <div class="bg-[var(--sys-bg-surface)] p-4 rounded-lg border border-[var(--sys-border-subtle)] shadow-sm flex flex-col hover:border-[var(--sys-brand-solid)] transition-all">
        <div class="flex justify-between items-center mb-6">
          <h5 class="text-[13px] font-bold text-[var(--sys-text-primary)] flex items-center gap-2 uppercase tracking-widest leading-none">
            <div class="w-8 h-8 rounded-md bg-[var(--sys-success-soft)] text-[var(--sys-success-text)] flex items-center justify-center border border-[var(--sys-success-border)]">
              <span class="material-symbols-outlined text-[18px]">pie_chart</span>
            </div>
            Phân bổ nguồn lực
          </h5>
          <button
            class="text-[11px] font-bold text-[var(--sys-brand-solid)] hover:opacity-80 transition-opacity flex items-center gap-1 uppercase tracking-widest"
            @click="handleOpenDetailReport"
          >
            Báo cáo chi tiết
            <span class="material-symbols-outlined text-[16px]">arrow_right_alt</span>
          </button>
        </div>

        <div class="space-y-5 flex-grow px-2">
          <div v-for="(dept, idx) in departmentData" :key="idx" class="space-y-2">
            <div class="flex justify-between items-end">
              <span class="text-[13px] font-bold text-[var(--sys-text-primary)]">{{ dept.name }}</span>
              <div class="flex items-center gap-2 text-[11px] font-bold uppercase tracking-tight">
                <span class="text-[var(--sys-text-disabled)] opacity-60">{{ dept.count }} NV</span>
                <span class="text-[var(--sys-brand-solid)]">{{ dept.percent }}%</span>
              </div>
            </div>
            <div class="h-1.5 bg-[var(--sys-bg-page)] rounded-full overflow-hidden border border-[var(--sys-border-subtle)] shadow-inner">
              <div class="h-full rounded-full transition-all duration-1000" 
                :class="dept.color"
                :style="{ width: dept.percent + '%' }"></div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Tasks & Events Table (Directive 5) -->
    <div class="bg-[var(--sys-bg-surface)] rounded-lg border border-[var(--sys-border-subtle)] shadow-sm overflow-hidden flex flex-col">
      <div class="px-4 py-3 border-b border-[var(--sys-border-subtle)] flex flex-col sm:flex-row justify-between items-center gap-4 bg-[var(--sys-bg-page)]/50">
        <div class="text-left flex items-center gap-3">
          <!-- Universal Icon Wrapper -->
          <div class="w-10 h-10 rounded-md bg-[var(--sys-danger-soft)] text-[var(--sys-danger-text)] flex items-center justify-center border border-[var(--sys-danger-border)]">
            <span class="material-symbols-outlined text-xl">assignment_late</span>
          </div>
          <div class="bg-transparent text-left">
            <h5 class="text-[13px] font-bold text-[var(--sys-text-primary)] m-0 uppercase tracking-widest leading-none">Nhiệm vụ & Sự kiện ưu tiên</h5>
            <p class="text-[11px] text-[var(--sys-text-secondary)] font-medium mt-1">Các tiến trình cần phê chuẩn hoặc can thiệp khẩn cấp.</p>
          </div>
        </div>
        <div class="flex bg-[var(--sys-bg-hover)] p-1 rounded-md border border-[var(--sys-border-subtle)] h-10">
          <button
            class="px-5 h-full rounded-md text-[11px] font-bold transition-all uppercase tracking-widest border"
            :class="taskView === 'all'
              ? 'bg-white shadow-sm text-[var(--sys-brand-solid)] border-[var(--sys-border-strong)]'
              : 'text-[var(--sys-text-secondary)] border-transparent hover:text-[var(--sys-brand-solid)] opacity-70'"
            @click="taskView = 'all'"
          >
            Tất cả luồng việc
          </button>
          <button
            class="px-5 h-full rounded-md text-[11px] font-bold transition-all uppercase tracking-widest border"
            :class="taskView === 'mine'
              ? 'bg-white shadow-sm text-[var(--sys-brand-solid)] border-[var(--sys-border-strong)]'
              : 'text-[var(--sys-text-secondary)] border-transparent hover:text-[var(--sys-brand-solid)] opacity-70'"
            @click="taskView = 'mine'"
          >
            Việc của tôi
          </button>
        </div>
      </div>
      
      <div class="overflow-x-auto custom-scrollbar">
        <table class="w-full text-left border-collapse">
          <thead class="bg-[var(--sys-bg-page)]">
            <tr>
              <th class="px-4 py-2.5 text-[11px] font-bold text-[var(--sys-text-secondary)] border-b border-[var(--sys-border-subtle)] uppercase tracking-widest">Đối tượng thụ hưởng</th>
              <th class="px-4 py-2.5 text-[11px] font-bold text-[var(--sys-text-secondary)] border-b border-[var(--sys-border-subtle)] uppercase tracking-widest">Nội dung đề xuất</th>
              <th class="px-4 py-2.5 text-[11px] font-bold text-[var(--sys-text-secondary)] border-b border-[var(--sys-border-subtle)] uppercase tracking-widest">Thời hiệu</th>
              <th class="px-4 py-2.5 text-[11px] font-bold text-[var(--sys-text-secondary)] border-b border-[var(--sys-border-subtle)] uppercase tracking-widest text-center">Trạng thái</th>
              <th class="px-4 py-2.5 text-[11px] font-bold text-[var(--sys-text-secondary)] border-b border-[var(--sys-border-subtle)] uppercase tracking-widest text-right">Quản trị</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-[var(--sys-border-subtle)]">
            <tr v-for="(item, idx) in visibleTaskItems" :key="idx" class="group hover:bg-[var(--sys-bg-hover)] transition-colors">
              <td class="px-4 py-3 whitespace-nowrap bg-transparent">
                <div class="flex flex-col bg-transparent max-w-[200px]">
                  <span class="text-[13px] font-bold text-[var(--sys-text-primary)] truncate transition-colors group-hover:text-[var(--sys-brand-solid)]">{{ item.name }}</span>
                  <span class="text-[10px] font-bold text-[var(--sys-brand-solid)] opacity-60 uppercase tracking-tight transition-opacity group-hover:opacity-100">{{ item.role }}</span>
                </div>
              </td>
              <td class="px-4 py-3 whitespace-nowrap bg-transparent">
                <div class="flex items-center gap-2">
                  <span class="w-1.5 h-1.5 rounded-full bg-[var(--sys-brand-solid)] opacity-40"></span>
                  <span class="text-[12px] font-semibold text-[var(--sys-text-primary)] truncate max-w-[250px]">{{ item.action }}</span>
                </div>
              </td>
              <td class="px-4 py-3 whitespace-nowrap text-[12px] font-bold text-[var(--sys-text-secondary)] bg-transparent uppercase tracking-tighter">{{ item.time }}</td>
              <td class="px-4 py-3 whitespace-nowrap bg-transparent text-center">
                <span :class="[
                  'px-2.5 py-0.5 rounded-md text-[10px] font-bold border inline-flex items-center gap-1.5 transition-all shadow-sm uppercase tracking-widest',
                  item.statusClass
                ]">
                  <span class="w-1.5 h-1.5 rounded-full animate-pulse" :class="item.dotClass"></span>
                  {{ item.status }}
                </span>
              </td>
              <td class="px-4 py-3 whitespace-nowrap text-right bg-transparent">
                <button
                  class="h-8 px-4 bg-[var(--sys-brand-solid)] text-white text-[11px] font-bold rounded-md shadow-sm hover:brightness-110 transition-all flex items-center gap-2 uppercase tracking-widest active:scale-95"
                  @click="handleProcessNow(item)"
                >
                  <span class="material-symbols-outlined text-[16px]">edit_square</span>
                  Xử lý ngay
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div class="px-4 py-2.5 bg-[var(--sys-bg-page)] border-t border-[var(--sys-border-subtle)] flex justify-end">
        <button
          class="text-[11px] font-bold text-[var(--sys-brand-solid)] hover:opacity-80 transition-opacity flex items-center gap-1 uppercase tracking-widest"
          @click="handleViewAllTasks"
        >
          Truy xuất toàn bộ danh mục đề xuất
          <span class="material-symbols-outlined text-[18px]">arrow_right_alt</span>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import Dropdown from '@/components/Dropdown.vue';
import { AUTH_USER_KEY, BE_API_BASE, getAccessToken } from '@/services/runtimeConfig.js';
import { getSessionItem, handleUnauthorized } from '@/services/session.js';
import { useConfirm } from '@/composables/useConfirm';

const router = useRouter();
const { showAlert } = useConfirm();
const timeRange = ref('6months');
const timeRangeOptions = ref([
  { label: '06 tháng gần nhất', value: '6months' },
  { label: '01 năm tài chính', value: '1year' },
  { label: 'Toàn bộ lịch sử', value: 'all' }
]);

const stats = ref([]);
const departmentData = ref([]);
const taskItems = ref([]);
const taskView = ref('all');
const resolveCurrentUserId = () => {
  const direct = Number(getSessionItem('userId') || 0);
  if (direct > 0) return direct;
  try {
    const raw = getSessionItem(AUTH_USER_KEY);
    const user = raw ? JSON.parse(raw) : null;
    return Number(user?.employee_id || user?.employeeId || 0);
  } catch {
    return 0;
  }
};
const currentUserId = resolveCurrentUserId();

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

const isEmployeeActive = (statusRaw) => {
  const status = String(statusRaw || '').toUpperCase();
  return !['ĐÃ_THÔI_VIỆC', 'ĐÃ_NGHỈ_VIỆC', 'NGHỈ_VIỆC', 'INACTIVE', 'TERMINATED'].includes(status);
};

const isPendingRequest = (statusRaw) => {
  const status = String(statusRaw || '').toUpperCase();
  return ['CHỜ_DUYỆT', 'CHỜ_GIÁM_ĐỐC_DUYỆT', 'CHỜ_XÁC_NHẬN_HR', 'ĐANG_XỬ_LÝ', 'PENDING', 'IN_PROGRESS'].includes(status);
};

const mapRequestStatusUi = (statusRaw) => {
  if (isPendingRequest(statusRaw)) {
    return {
      text: 'Chờ thẩm định',
      statusClass: 'bg-[var(--sys-warning-soft)] text-[var(--sys-warning-text)] border-[var(--sys-warning-border)]',
      dotClass: 'bg-[var(--sys-warning-solid)]',
    };
  }
  return {
    text: 'Đã xử lý',
    statusClass: 'bg-[var(--sys-success-soft)] text-[var(--sys-success-text)] border-[var(--sys-success-border)]',
    dotClass: 'bg-[var(--sys-success-solid)]',
  };
};

const formatDateYmd = (date) => {
  const y = date.getFullYear();
  const m = String(date.getMonth() + 1).padStart(2, '0');
  const d = String(date.getDate()).padStart(2, '0');
  return `${y}-${m}-${d}`;
};

const formatRequestTime = (raw) => {
  const text = String(raw || '').trim();
  if (!text) return '--';
  const parsed = new Date(text);
  if (Number.isNaN(parsed.getTime())) {
    return text.slice(0, 10);
  }
  const dd = String(parsed.getDate()).padStart(2, '0');
  const mm = String(parsed.getMonth() + 1).padStart(2, '0');
  const yyyy = parsed.getFullYear();
  return `${dd}/${mm}/${yyyy}`;
};

const syncDashboardData = async () => {
  try {
    const today = new Date();
    const todayYmd = formatDateYmd(today);
    const monthAgo = new Date(today.getFullYear(), today.getMonth() - 1, today.getDate());
    const monthAgoYmd = formatDateYmd(monthAgo);

    const [employees, departments, requests, attendances] = await Promise.all([
      apiRequest('/employees?page=1&per_page=1000'),
      apiRequest('/departments?page=1&per_page=300'),
      apiRequest('/requests?page=1&per_page=300'),
      apiRequest(`/attendances?page=1&per_page=500&date_from=${todayYmd}&date_to=${todayYmd}`),
    ]);

    const employeeList = Array.isArray(employees) ? employees : [];
    const departmentList = Array.isArray(departments) ? departments : [];
    const requestList = Array.isArray(requests) ? requests : [];
    const attendanceList = Array.isArray(attendances) ? attendances : [];

    const activeEmps = employeeList.filter((emp) => isEmployeeActive(emp.status));
    const activeEmpsCount = activeEmps.length;
    const pendingReqsCount = requestList.filter((item) => isPendingRequest(item.status)).length;
    const newJoinersCount = activeEmps.filter((emp) => {
      const hireDate = String(emp.hire_date || '').slice(0, 10);
      return hireDate && hireDate >= monthAgoYmd && hireDate <= todayYmd;
    }).length;
    const resignedCount = employeeList.length - activeEmpsCount;
    const turnover = employeeList.length > 0 ? ((resignedCount / employeeList.length) * 100).toFixed(1) : '0.0';

    const lateCount = attendanceList.filter((item) => {
      const status = String(item.status || '').toUpperCase();
      return ['MUON', 'ĐI_MUỘN', 'LATE'].includes(status);
    }).length;

    stats.value = [
      { label: 'TỔNG NHÂN SỰ', value: activeEmpsCount, change: 'LIVE', color: 'brand', icon: 'groups' },
      { label: 'YÊU CẦU DUYỆT', value: pendingReqsCount, change: 'MỚI', color: 'warning', icon: 'pending_actions' },
      { label: 'NHÂN SỰ MỚI', value: newJoinersCount, change: '30D', color: 'success', icon: 'person_add' },
      { label: 'TỶ LỆ NGHỈ VIỆC', value: `${turnover}%`, change: 'LIVE', color: 'danger', icon: 'trending_down' },
      { label: 'ĐI MUỘN HÔM NAY', value: String(lateCount), change: 'LIVE', color: 'danger', icon: 'schedule' },
    ];

    const colors = [
      'bg-[var(--sys-brand-solid)]',
      'bg-[var(--sys-success-solid)]',
      'bg-[var(--sys-warning-solid)]',
      'bg-[var(--sys-danger-solid)]',
      'bg-teal-500',
      'bg-cyan-500',
    ];
    departmentData.value = departmentList.map((department, index) => {
      const deptId = Number(department.department_id || department.departmentId || 0);
      const count = activeEmps.filter((emp) => Number(emp.department_id) === deptId).length;
      return {
        name: department.department_name || department.departmentName || `Phòng ban #${deptId}`,
        count,
        percent: Math.round((count / (activeEmpsCount || 1)) * 100),
        color: colors[index % colors.length],
      };
    });

    const employeeMap = new Map(
      employeeList.map((emp) => [Number(emp.employee_id || emp.employeeId || 0), emp]),
    );
    const departmentMap = new Map(
      departmentList.map((dept) => [Number(dept.department_id || dept.departmentId || 0), dept]),
    );

    taskItems.value = requestList
      .map((req) => {
        const requesterId = Number(req.requester_id || req.requesterId || 0);
        const requester = employeeMap.get(requesterId) || null;
        const requesterDeptId = Number(
          requester?.department_id || req.department_id || req.departmentId || 0,
        );
        const requesterDept = departmentMap.get(requesterDeptId) || null;
        const statusMeta = mapRequestStatusUi(req.status);
        const typeName = req.request_type_name || req.requestTypeName || 'Yêu cầu nội bộ';
        const reason = String(req.reason || '').trim();
        return {
          requestId: Number(req.request_id || req.requestId || req.id || 0),
          name: req.requester_name || requester?.full_name || 'N/A',
          role: requester?.position_name || requester?.positionName || 'Nhân viên',
          action: reason ? `${typeName} — ${reason.slice(0, 60)}` : typeName,
          time: formatRequestTime(req.request_date || req.requestDate),
          status: statusMeta.text,
          statusClass: statusMeta.statusClass,
          dotClass: statusMeta.dotClass,
          requesterId,
        };
      })
      .sort((a, b) => b.requestId - a.requestId)
      .slice(0, 12);
  } catch (error) {
    console.error('Dashboard load failed:', error);
    await showAlert('Không tải được Dashboard', error?.message || 'Lỗi tải dữ liệu dashboard.');
  }
};

const getStatIconClass = (color) => {
  switch(color) {
    case 'brand': return 'bg-[var(--sys-brand-soft)] text-[var(--sys-brand-solid)] border-[var(--sys-brand-border)]';
    case 'success': return 'bg-[var(--sys-success-soft)] text-[var(--sys-success-text)] border-[var(--sys-success-border)]';
    case 'danger': return 'bg-[var(--sys-danger-soft)] text-[var(--sys-danger-text)] border-[var(--sys-danger-border)]';
    case 'warning': return 'bg-[var(--sys-warning-soft)] text-[var(--sys-warning-text)] border-[var(--sys-warning-border)]';
    default: return 'bg-[var(--sys-bg-page)] text-[var(--sys-text-secondary)] border-[var(--sys-border-subtle)]';
  }
};

const visibleTaskItems = computed(() => {
  if (taskView.value === 'mine') {
    const mine = taskItems.value.filter((item) => item.requesterId === currentUserId);
    if (mine.length > 0) return mine;
    return taskItems.value.filter((item) => item.status === 'Chờ thẩm định');
  }
  return taskItems.value;
});

const handleProcessNow = (item) => {
  const requestId = Number(item?.requestId || 0);
  if (requestId > 0) {
    router.push({ path: '/admin/pheduyet', query: { request_id: String(requestId) } });
    return;
  }
  router.push('/admin/pheduyet');
};

const handleViewAllTasks = () => {
  router.push('/admin/pheduyet');
};

const handleOpenDetailReport = () => {
  router.push('/admin/nhansu');
};

onMounted(() => {
  void syncDashboardData();
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
  border-radius: 10px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
  background: var(--sys-brand-solid);
}
</style>
