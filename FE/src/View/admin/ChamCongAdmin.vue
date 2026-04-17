<template>
  <div class="space-y-6 pb-8">
    <!-- Header Area -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 bg-transparent text-left">
      <div class="bg-transparent text-left">
        <h1 class="text-xl font-semibold text-[var(--sys-text-primary)] mb-1">Quản lý Chấm công & Chuyên cần</h1>
        <p class="text-sm text-[var(--sys-text-secondary)]">Kiểm soát dữ liệu chấm công, phê duyệt định mức và theo dõi nguồn lực thời gian thực.</p>
      </div>
      <div class="flex items-center gap-3 bg-transparent shrink-0">
        <div class="hidden md:flex items-center gap-2.5 bg-[var(--sys-bg-page)] px-4 h-11 rounded-md border border-[var(--sys-border-subtle)] shadow-sm">
          <span class="text-[var(--sys-text-secondary)] text-[13px] font-medium">Kỳ lương 10/2023</span>
          <span class="flex items-center gap-1.5 text-[var(--sys-success-text)] font-semibold text-[13px]">
            <span class="w-1.5 h-1.5 rounded-full bg-[var(--sys-success-solid)]"></span>
            Đang mở
          </span>
        </div>
        <button class="h-11 px-6 bg-[var(--sys-warning-solid)] rounded-md font-semibold text-sm text-white hover:brightness-90 transition-all flex items-center gap-2 shadow-sm">
          <span class="material-symbols-outlined text-[20px]">lock_open</span> 
          Khóa bảng công
        </button>
      </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
      <div v-for="(stat, idx) in adminStats" :key="idx" 
      class="bg-[var(--sys-bg-surface)] p-4 rounded-lg border border-[var(--sys-border-subtle)] shadow-sm hover:border-[var(--sys-brand-solid)] transition-all flex items-center gap-4">
        <div :class="`w-12 h-12 rounded-md flex items-center justify-center border transition-all ${
          stat.color === 'brand' ? 'bg-[var(--sys-brand-soft)] text-[var(--sys-brand-solid)] border-[var(--sys-brand-border)]' :
          stat.color === 'success' ? 'bg-[var(--sys-success-soft)] text-[var(--sys-success-text)] border-[var(--sys-success-border)]' :
          'bg-[var(--sys-danger-soft)] text-[var(--sys-danger-text)] border-[var(--sys-danger-border)]'
        }`">
          <span class="material-symbols-outlined text-[24px]">{{ stat.icon }}</span>
        </div>
        <div class="bg-transparent flex flex-col flex-1">
          <div class="flex items-center justify-between mb-0.5">
            <p class="text-[12px] font-medium text-[var(--sys-text-secondary)] uppercase tracking-wide">{{ stat.label }}</p>
              <span class="text-[10px] font-bold text-[var(--sys-brand-solid)]">
              {{ stat.sub }}
            </span>
          </div>
          <div class="flex items-baseline gap-1">
            <h2 class="text-xl font-bold text-[var(--sys-text-primary)] leading-tight">{{ stat.value }}</h2>
            <span class="text-[11px] font-semibold text-[var(--sys-text-disabled)] uppercase">{{ stat.unit }}</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Charts Row -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-stretch">
      <div class="lg:col-span-7">
        <div class="bg-[var(--sys-bg-surface)] border border-[var(--sys-border-subtle)] shadow-sm rounded-lg p-5 h-full flex flex-col hover:border-[var(--sys-brand-solid)] transition-all">
          <h5 class="text-sm font-semibold text-[var(--sys-text-primary)] flex items-center gap-2 mb-6 uppercase tracking-wide">
            <span class="material-symbols-outlined text-[var(--sys-brand-solid)] text-[20px]">analytics</span>
            Phân bổ OT theo bộ phận
          </h5>
          <div class="flex items-end justify-around flex-grow pt-4 pb-4 px-4 bg-transparent border-b border-[var(--sys-border-subtle)]" style="min-height: 200px;">
            <div v-for="dept in ['IT', 'HR', 'Sales', 'MKT', 'Ops']" :key="dept" class="flex flex-col items-center gap-3 w-full bg-transparent group/bar">
              <div class="bg-[var(--sys-bg-page)] rounded-t-md w-8 relative overflow-hidden flex items-end shadow-inner border border-[var(--sys-border-subtle)] h-40">
                <div class="bg-[var(--sys-brand-solid)] w-full transition-all duration-500 hover:brightness-110" 
                  :style="{ height: (Math.random() * 60 + 20) + '%' }"></div>
              </div>
              <span class="text-[11px] font-semibold text-[var(--sys-text-secondary)] uppercase">{{ dept }}</span>
            </div>
          </div>
        </div>
      </div>
      <div class="lg:col-span-5">
        <div class="bg-[var(--sys-bg-surface)] border border-[var(--sys-border-subtle)] shadow-sm rounded-lg p-5 h-full flex flex-col hover:border-[var(--sys-brand-solid)] transition-all">
          <h5 class="text-sm font-semibold text-[var(--sys-text-primary)] flex items-center gap-2 mb-6 uppercase tracking-wide">
            <span class="material-symbols-outlined text-[var(--sys-success-text)] text-[20px]">military_tech</span>
            Nhân sự gương mẫu (Sớm nhất)
          </h5>
          <div class="flex flex-col gap-4 bg-transparent">
            <div v-for="user in topEarlyUsers" :key="user.name" class="bg-transparent space-y-2">
              <div class="flex justify-between items-center text-[13px] font-semibold">
                <span class="text-[var(--sys-text-primary)]">{{ user.name }}</span>
                <span class="text-[var(--sys-success-text)]">{{ user.earlyMinutes }}p sớm</span>
              </div>
              <div class="h-1.5 bg-[var(--sys-bg-page)] rounded-full overflow-hidden border border-[var(--sys-border-subtle)] shadow-inner">
                <div class="bg-[var(--sys-success-solid)] h-full transition-all duration-700" :style="{ width: user.percent + '%' }"></div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Main Table Container -->
    <div class="bg-[var(--sys-bg-surface)] rounded-lg border border-[var(--sys-border-subtle)] shadow-sm overflow-hidden flex flex-col">
      <!-- Toolbar -->
      <div class="px-4 py-3 border-b border-[var(--sys-border-subtle)] flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4 bg-[var(--sys-bg-page)]/50">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-md bg-[var(--sys-brand-soft)] text-[var(--sys-brand-solid)] flex items-center justify-center border border-[var(--sys-brand-border)]">
            <span class="material-symbols-outlined text-[24px]">event_available</span>
          </div>
          <div class="bg-transparent text-left">
            <h5 class="text-sm font-semibold text-[var(--sys-text-primary)] m-0 uppercase tracking-wide">Nhật trình chấm công</h5>
            <p class="text-[12px] text-[var(--sys-text-secondary)]">Dữ liệu sinh trắc học thời gian thực.</p>
          </div>
        </div>
        <div class="flex flex-wrap items-center gap-3 w-full lg:w-auto">
          <CalendarCustom v-model="filterDate" placeholder="Chọn ngày" class="flex-1" />
          <Dropdown v-model="filterDept" :options="deptOptions" class="min-w-[180px] h-11" />
          <Dropdown v-model="filterStatus" :options="statusOptions" class="min-w-[180px] h-11" />
        </div>
      </div>

      <!-- Table -->
      <div class="overflow-x-auto custom-scrollbar flex-grow">
        <table class="w-full text-left border-collapse">
          <thead class="bg-[var(--sys-bg-page)]">
            <tr>
              <th class="px-4 py-3 text-[12px] font-semibold text-[var(--sys-text-secondary)] border-b border-[var(--sys-border-subtle)] uppercase tracking-wider">Nhân sự</th>
              <th class="px-4 py-3 text-[12px] font-semibold text-[var(--sys-text-secondary)] border-b border-[var(--sys-border-subtle)] uppercase tracking-wider text-center">Giờ vào</th>
              <th class="px-4 py-3 text-[12px] font-semibold text-[var(--sys-text-secondary)] border-b border-[var(--sys-border-subtle)] uppercase tracking-wider text-center">Giờ ra</th>
              <th class="px-4 py-3 text-[12px] font-semibold text-[var(--sys-text-secondary)] border-b border-[var(--sys-border-subtle)] uppercase tracking-wider text-right">Muộn</th>
              <th class="px-4 py-3 text-[12px] font-semibold text-[var(--sys-text-secondary)] border-b border-[var(--sys-border-subtle)] uppercase tracking-wider text-right">Sớm</th>
              <th class="px-4 py-3 text-[12px] font-semibold text-[var(--sys-text-secondary)] border-b border-[var(--sys-border-subtle)] uppercase tracking-wider text-right">OT</th>
              <th class="px-4 py-3 text-[12px] font-semibold text-[var(--sys-text-secondary)] border-b border-[var(--sys-border-subtle)] uppercase tracking-wider text-center">Trạng thái</th>
              <th class="px-4 py-3 text-[12px] font-semibold text-[var(--sys-text-secondary)] border-b border-[var(--sys-border-subtle)] uppercase tracking-wider text-right">Thao tác</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-[var(--sys-border-subtle)]">
            <tr v-for="record in timeRecords" :key="record.id" class="group hover:bg-[var(--sys-bg-hover)] transition-colors">
              <td class="px-4 py-3 whitespace-nowrap bg-transparent">
                <div class="flex flex-col bg-transparent">
                  <span class="text-[13px] font-semibold text-[var(--sys-text-primary)]">{{ record.name }}</span>
                  <span class="text-[11px] text-[var(--sys-text-secondary)]">{{ record.shift }}</span>
                </div>
              </td>
              <td class="px-4 py-3 text-center whitespace-nowrap bg-transparent">
                <div class="flex flex-col gap-0.5">
                  <span class="text-[13px] font-medium text-[var(--sys-text-primary)]">{{ record.checkIn }}</span>
                  <span v-if="record.checkIn2" class="text-[10px] font-bold text-[var(--sys-brand-solid)] bg-[var(--sys-brand-soft)] px-1 rounded shadow-sm">{{ record.checkIn2 }} (L2)</span>
                </div>
              </td>
              <td class="px-4 py-3 text-center whitespace-nowrap bg-transparent">
                <div class="flex flex-col gap-0.5">
                  <span class="text-[13px] font-medium text-[var(--sys-text-primary)]">{{ record.checkOut }}</span>
                  <span v-if="record.checkOut2" class="text-[10px] font-bold text-[var(--sys-brand-solid)] bg-[var(--sys-brand-soft)] px-1 rounded shadow-sm">{{ record.checkOut2 }} (L2)</span>
                </div>
              </td>
              <td class="px-4 py-3 text-right whitespace-nowrap bg-transparent">
                <span :class="['text-[13px] font-semibold', record.late > 0 ? 'text-[var(--sys-danger-text)]' : 'text-[var(--sys-text-disabled)] opacity-50']">
                  {{ record.late > 0 ? record.late + ' ph' : '-' }}
                </span>
              </td>
              <td class="px-4 py-3 text-right whitespace-nowrap bg-transparent">
                <span :class="['text-[13px] font-semibold', record.early > 0 ? 'text-[var(--sys-warning-text)]' : 'text-[var(--sys-text-disabled)] opacity-50']">
                  {{ record.early > 0 ? record.early + ' ph' : '-' }}
                </span>
              </td>
              <td class="px-4 py-3 text-right whitespace-nowrap bg-transparent">
                <span :class="['text-[13px] font-bold', record.ot > 0 ? 'text-[var(--sys-brand-solid)]' : 'text-[var(--sys-text-disabled)] opacity-50']">
                  {{ record.ot > 0 ? record.ot + ' h' : '-' }}
                </span>
              </td>
              <td class="px-4 py-3 text-center whitespace-nowrap bg-transparent">
                <span :class="[
                  'px-2 py-0.5 rounded-md text-[11px] font-semibold border transition-all inline-flex items-center gap-1.5 uppercase tracking-wide',
                  getStatusBadgeClass(record.statusText)
                ]">
                  <span class="w-1.5 h-1.5 rounded-full" :class="getStatusDotClass(record.statusText)"></span>
                  {{ record.statusText }}
                </span>
              </td>
              <td class="px-4 py-3 text-right whitespace-nowrap bg-transparent">
                <div class="flex items-center justify-end">
                  <button class="w-8 h-8 flex items-center justify-center rounded-md text-[var(--sys-text-secondary)] hover:text-[var(--sys-brand-solid)] hover:bg-[var(--sys-brand-soft)] transition-all" title="Chỉnh sửa" @click="openEditAttendance(record)">
                    <span class="material-symbols-outlined text-[18px]">edit_square</span>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Footer -->
      <div class="px-4 py-3 bg-[var(--sys-bg-page)] border-t border-[var(--sys-border-subtle)] flex justify-end">
        <button class="text-[13px] font-semibold text-[var(--sys-brand-solid)] hover:opacity-80 transition-opacity flex items-center gap-1" @click="resetAttendanceFilters">
          Xem toàn bộ nhật trình
          <span class="material-symbols-outlined text-[18px]">arrow_forward</span>
        </button>
      </div>
    </div>

    <Teleport to="body">
      <div v-if="showEditModal" class="fixed inset-0 z-[120] bg-black/45 flex items-center justify-center p-4" @click.self="closeEditModal">
        <div class="w-full max-w-2xl bg-white rounded-lg border border-[var(--sys-border-subtle)] shadow-2xl overflow-hidden">
          <div class="px-5 py-4 border-b border-[var(--sys-border-subtle)] flex items-center justify-between">
            <h3 class="text-sm font-bold uppercase tracking-wide text-[var(--sys-text-primary)]">Chỉnh sửa nhật trình chấm công</h3>
            <button type="button" class="text-[var(--sys-text-secondary)]" @click="closeEditModal">
              <span class="material-symbols-outlined">close</span>
            </button>
          </div>

          <div class="p-5 grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="text-[11px] font-bold uppercase tracking-wide text-[var(--sys-text-secondary)]">Giờ vào 1</label>
              <input v-model="editForm.check_in_time" type="datetime-local" class="mt-1 w-full h-10 px-3 bg-[var(--sys-bg-page)] border border-[var(--sys-border-strong)] rounded-md text-sm" />
            </div>
            <div>
              <label class="text-[11px] font-bold uppercase tracking-wide text-[var(--sys-text-secondary)]">Giờ ra 1</label>
              <input v-model="editForm.check_out_time" type="datetime-local" class="mt-1 w-full h-10 px-3 bg-[var(--sys-bg-page)] border border-[var(--sys-border-strong)] rounded-md text-sm" />
            </div>
            <div>
              <label class="text-[11px] font-bold uppercase tracking-wide text-[var(--sys-text-secondary)]">Giờ vào 2</label>
              <input v-model="editForm.check_in_time_2" type="datetime-local" class="mt-1 w-full h-10 px-3 bg-[var(--sys-bg-page)] border border-[var(--sys-border-strong)] rounded-md text-sm" />
            </div>
            <div>
              <label class="text-[11px] font-bold uppercase tracking-wide text-[var(--sys-text-secondary)]">Giờ ra 2</label>
              <input v-model="editForm.check_out_time_2" type="datetime-local" class="mt-1 w-full h-10 px-3 bg-[var(--sys-bg-page)] border border-[var(--sys-border-strong)] rounded-md text-sm" />
            </div>
            <div>
              <label class="text-[11px] font-bold uppercase tracking-wide text-[var(--sys-text-secondary)]">Trạng thái</label>
              <select v-model="editForm.status" class="mt-1 w-full h-10 px-3 bg-[var(--sys-bg-page)] border border-[var(--sys-border-strong)] rounded-md text-sm">
                <option value="">-- Không đổi --</option>
                <option value="ĐỦ_CÔNG">Đủ công</option>
                <option value="CHỜ_DUYỆT">Chờ duyệt</option>
                <option value="VẮNG_MẶT">Vắng mặt</option>
                <option value="ĐI_MUỘN">Đi muộn</option>
              </select>
            </div>
            <div class="md:col-span-2">
              <label class="text-[11px] font-bold uppercase tracking-wide text-[var(--sys-text-secondary)]">Ghi chú</label>
              <textarea v-model="editForm.notes" rows="3" class="mt-1 w-full px-3 py-2 bg-[var(--sys-bg-page)] border border-[var(--sys-border-strong)] rounded-md text-sm"></textarea>
            </div>
          </div>

          <div class="px-5 py-4 border-t border-[var(--sys-border-subtle)] flex justify-end gap-2">
            <button type="button" class="h-9 px-4 rounded-md border border-[var(--sys-border-strong)] text-[12px] font-bold uppercase tracking-wide" @click="closeEditModal">Hủy</button>
            <button type="button" class="h-9 px-4 rounded-md bg-[var(--sys-brand-solid)] text-white text-[12px] font-bold uppercase tracking-wide disabled:opacity-60" :disabled="savingEditAttendance" @click="saveEditAttendance">
              {{ savingEditAttendance ? 'Đang lưu...' : 'Lưu cập nhật' }}
            </button>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<script setup>
/**
 * TRANG QUẢN LÝ CHẤM CÔNG - PHIÊN BẢN ENTERPRISE SaaS
 * Tuân thủ 7 Golden Rules:
 * - Font Inter 14px (text-sm), Tỉ lệ Table cao (text-13px)
 * - Bo góc chuẩn B2B: 6px (MD) cho Input/Button, 8px (LG) cho Card/Table
 * - Hệ màu Blue/White đồng nhất cho Action Icons
 */
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import Dropdown from '@/components/Dropdown.vue';
import CalendarCustom from '@/components/CalendarCustom.vue';
import { useConfirm } from '@/composables/useConfirm';
import { BE_API_BASE, getAccessToken } from '@/services/runtimeConfig.js';
import { handleUnauthorized } from '@/services/session.js';

const { showAlert } = useConfirm();
const filterDate = ref(new Date().toISOString().slice(0, 10));
const filterDept = ref('ALL');
const filterStatus = ref('ALL');

const deptOptions = ref([{ label: 'Tất cả phòng ban', value: 'ALL' }]);
const statusOptions = [
  { label: 'Mọi trạng thái', value: 'ALL' },
  { label: 'Đủ công', value: 'ON_TIME' },
  { label: 'Đi muộn', value: 'LATE' },
  { label: 'Vắng mặt', value: 'ABSENT' },
];

const timeRecords = ref([]);
const topEarlyUsers = ref([]);
const showEditModal = ref(false);
const savingEditAttendance = ref(false);
const editTargetAttendanceId = ref(null);
const editForm = ref({
  check_in_time: '',
  check_out_time: '',
  check_in_time_2: '',
  check_out_time_2: '',
  status: '',
  notes: '',
});

const authHeaders = () => {
  const token = getAccessToken();
  if (!token) throw new Error('Thiếu access token');
  return {
    Authorization: `Bearer ${token}`,
    'Content-Type': 'application/json',
  };
};

const apiRequest = async (path) => {
  const response = await fetch(`${BE_API_BASE}${path}`, { headers: authHeaders() });
  if (response.status === 401) {
    handleUnauthorized();
    throw new Error('Phiên đăng nhập đã hết hạn');
  }
  const payload = await response.json().catch(() => ({}));
  if (!response.ok || payload?.success === false) {
    throw new Error(payload?.message || `Request failed (${response.status})`);
  }
  return Array.isArray(payload?.data) ? payload.data : [];
};

const toTimeHHMM = (value) => String(value || '').slice(11, 16) || '--:--';

const resolveStatusCode = (attendance) => {
  if (!attendance) return 'ABSENT';
  const statusText = String(attendance.status || '').toUpperCase();
  if (statusText.includes('VẮNG') || statusText.includes('VANG') || statusText.includes('ABSENT')) return 'ABSENT';
  const late = Number(attendance.late_minutes || attendance.lateMinutes || 0);
  if (late > 0 || statusText.includes('MUỘN') || statusText.includes('MUON') || statusText.includes('LATE')) return 'LATE';
  if (!attendance.check_in_time && !attendance.check_out_time) return 'ABSENT';
  return 'ON_TIME';
};

const statusLabelMap = {
  ON_TIME: 'ĐỦ CÔNG',
  LATE: 'ĐI MUỘN',
  ABSENT: 'VẮNG MẶT',
};

const totalOtHours = computed(() =>
  timeRecords.value.reduce((acc, item) => acc + Number(item.ot || 0), 0),
);
const attendanceRate = computed(() => {
  if (timeRecords.value.length === 0) return 0;
  const working = timeRecords.value.filter((item) => item.statusCode !== 'ABSENT').length;
  return Math.round((working / timeRecords.value.length) * 1000) / 10;
});
const absentCount = computed(() => timeRecords.value.filter((item) => item.statusCode === 'ABSENT').length);

const adminStats = computed(() => [
  { label: 'Tổng giờ OT ngày', value: `${totalOtHours.value.toFixed(1)}`, sub: 'LIVE', unit: 'giờ', icon: 'timer', color: 'brand' },
  { label: 'Tỷ lệ chuyên cần', value: `${attendanceRate.value}`, sub: 'LIVE', unit: '%', icon: 'verified_user', color: 'success' },
  { label: 'Vắng mặt hôm nay', value: `${absentCount.value}`, sub: 'LIVE', unit: 'nhân sự', icon: 'person_off', color: 'danger' },
]);

const loadData = async () => {
  try {
    const date = filterDate.value || new Date().toISOString().slice(0, 10);
    const [employees, departments, attendances] = await Promise.all([
      apiRequest('/employees?page=1&per_page=2000'),
      apiRequest('/departments?page=1&per_page=500'),
      apiRequest(`/attendances?page=1&per_page=3000&date_from=${date}&date_to=${date}`),
    ]);

    deptOptions.value = [
      { label: 'Tất cả phòng ban', value: 'ALL' },
      ...departments.map((d) => ({
        label: d.department_name || d.departmentName || d.name || `Phòng #${d.department_id || d.id}`,
        value: String(d.department_id || d.departmentId || d.id),
      })),
    ];

    const attendanceMap = new Map();
    attendances.forEach((att) => {
      attendanceMap.set(Number(att.employee_id || att.employeeId || 0), att);
    });

    const mapped = employees.map((emp) => {
      const employeeId = Number(emp.employee_id || emp.employeeId || emp.id || 0);
      const att = attendanceMap.get(employeeId) || null;
      const statusCode = resolveStatusCode(att);
      return {
        id: employeeId,
        attendanceId: Number(att?.attendance_id || att?.attendanceId || 0) || null,
        name: emp.full_name || emp.fullName || `Nhân viên #${employeeId}`,
        departmentId: Number(emp.department_id || emp.departmentId || 0),
        shift: String(emp.position_name || emp.positionName || 'Nhân viên').toUpperCase(),
        checkIn: toTimeHHMM(att?.check_in_time),
        checkOut: toTimeHHMM(att?.check_out_time),
        checkInRaw: att?.check_in_time || '',
        checkOutRaw: att?.check_out_time || '',
        checkIn2: att?.check_in_time_2 ? toTimeHHMM(att.check_in_time_2) : null,
        checkOut2: att?.check_out_time_2 ? toTimeHHMM(att.check_out_time_2) : null,
        checkInRaw2: att?.check_in_time_2 || '',
        checkOutRaw2: att?.check_out_time_2 || '',
        late: Number(att?.late_minutes || 0),
        early: Number(att?.early_leave_minutes || 0),
        ot: Number(att?.overtime_hours || 0),
        statusCode,
        statusText: statusLabelMap[statusCode] || 'VẮNG MẶT',
        statusRaw: String(att?.status || ''),
        notesRaw: String(att?.notes || ''),
      };
    });

    const deptFiltered = filterDept.value === 'ALL'
      ? mapped
      : mapped.filter((item) => String(item.departmentId) === String(filterDept.value));
    const statusFiltered = filterStatus.value === 'ALL'
      ? deptFiltered
      : deptFiltered.filter((item) => item.statusCode === filterStatus.value);

    timeRecords.value = statusFiltered;

    const earlyRows = mapped
      .filter((item) => item.statusCode !== 'ABSENT' && item.checkIn !== '--:--')
      .map((item) => {
        const [h, m] = item.checkIn.split(':').map((v) => Number(v || 0));
        const minute = h * 60 + m;
        const early = Math.max((8 * 60) - minute, 0);
        return { name: item.name, earlyMinutes: early };
      })
      .filter((item) => item.earlyMinutes > 0)
      .sort((a, b) => b.earlyMinutes - a.earlyMinutes)
      .slice(0, 4);

    topEarlyUsers.value = earlyRows.map((item) => ({
      ...item,
      percent: Math.min(Math.round((item.earlyMinutes / 60) * 100), 100),
    }));
  } catch (error) {
    console.error('Lỗi tải dữ liệu Admin:', error);
  }
};

const toDateTimeLocal = (value) => {
  const raw = String(value || '').trim();
  if (!raw) return '';
  return raw.replace(' ', 'T').slice(0, 16);
};

const toMysqlDateTime = (value) => {
  const raw = String(value || '').trim();
  if (!raw) return '';
  return `${raw.replace('T', ' ')}:00`;
};

const openEditAttendance = async (record) => {
  if (!record?.attendanceId) {
    await showAlert('Không thể chỉnh sửa', 'Dòng này chưa có bản ghi chấm công để cập nhật.');
    return;
  }
  editTargetAttendanceId.value = Number(record.attendanceId);
  editForm.value = {
    check_in_time: toDateTimeLocal(record.checkInRaw),
    check_out_time: toDateTimeLocal(record.checkOutRaw),
    check_in_time_2: toDateTimeLocal(record.checkInRaw2),
    check_out_time_2: toDateTimeLocal(record.checkOutRaw2),
    status: String(record.statusRaw || ''),
    notes: String(record.notesRaw || ''),
  };
  showEditModal.value = true;
};

const closeEditModal = () => {
  showEditModal.value = false;
  editTargetAttendanceId.value = null;
};

const saveEditAttendance = async () => {
  if (!editTargetAttendanceId.value) return;

  const payload = {};
  if (editForm.value.check_in_time) payload.check_in_time = toMysqlDateTime(editForm.value.check_in_time);
  if (editForm.value.check_out_time) payload.check_out_time = toMysqlDateTime(editForm.value.check_out_time);
  if (editForm.value.check_in_time_2) payload.check_in_time_2 = toMysqlDateTime(editForm.value.check_in_time_2);
  if (editForm.value.check_out_time_2) payload.check_out_time_2 = toMysqlDateTime(editForm.value.check_out_time_2);
  if (editForm.value.status) payload.status = editForm.value.status;
  if (String(editForm.value.notes || '').trim()) payload.notes = String(editForm.value.notes || '').trim();

  if (Object.keys(payload).length === 0) {
    await showAlert('Thiếu dữ liệu', 'Bạn cần nhập ít nhất một trường để lưu cập nhật.');
    return;
  }

  savingEditAttendance.value = true;
  try {
    const response = await fetch(`${BE_API_BASE}/attendances/${editTargetAttendanceId.value}`, {
      method: 'PATCH',
      headers: authHeaders(),
      body: JSON.stringify(payload),
    });
    if (response.status === 401) {
      handleUnauthorized();
      throw new Error('Phiên đăng nhập đã hết hạn.');
    }
    const body = await response.json().catch(() => ({}));
    if (!response.ok || body?.success === false) {
      throw new Error(body?.message || `Request failed (${response.status})`);
    }
    closeEditModal();
    await loadData();
  } catch (error) {
    await showAlert('Lưu thất bại', error?.message || 'Không thể cập nhật bản ghi chấm công.');
  } finally {
    savingEditAttendance.value = false;
  }
};

const resetAttendanceFilters = () => {
  filterDept.value = 'ALL';
  filterStatus.value = 'ALL';
  void loadData();
};

let pollInterval = null;
const ATTENDANCE_POLL_INTERVAL_MS = 60000;
onMounted(() => {
  void loadData();
  pollInterval = setInterval(() => {
    if (typeof document !== 'undefined' && document.hidden) return;
    void loadData();
  }, ATTENDANCE_POLL_INTERVAL_MS);
});

onUnmounted(() => {
  if (pollInterval) clearInterval(pollInterval);
});

watch([filterDate, filterDept, filterStatus], () => {
  void loadData();
});

const getStatusBadgeClass = (status) => {
  switch (status) {
    case 'ĐỦ CÔNG': return 'bg-[var(--sys-success-soft)] text-[var(--sys-success-text)] border-[var(--sys-success-border)]';
    case 'ĐI MUỘN': return 'bg-[var(--sys-warning-soft)] text-[var(--sys-warning-text)] border-[var(--sys-warning-border)]';
    case 'VẮNG MẶT': return 'bg-[var(--sys-danger-soft)] text-[var(--sys-danger-text)] border-[var(--sys-danger-border)]';
    default: return 'bg-[var(--sys-bg-hover)] text-[var(--sys-text-disabled)] border-[var(--sys-border-subtle)] opacity-50';
  }
};

const getStatusDotClass = (status) => {
  switch (status) {
    case 'ĐỦ CÔNG': return 'bg-[var(--sys-success-solid)]';
    case 'ĐI MUỘN': return 'bg-[var(--sys-warning-solid)]';
    case 'VẮNG MẶT': return 'bg-[var(--sys-danger-solid)]';
    default: return 'bg-[var(--sys-icon-default)] opacity-40';
  }
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
</style>
