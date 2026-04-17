<template>
  <div class="attendance-wrapper min-h-screen bg-[var(--sys-bg-page)] text-[var(--sys-text-primary)] p-4 md:p-6 lg:p-8">
    <!-- Toast Notification -->
    <Transition name="toast">
      <div v-if="showToast" :class="['fixed top-6 left-1/2 -translate-x-1/2 z-[100] px-6 py-3 rounded-lg shadow-2xl flex items-center gap-3 border backdrop-blur-md transition-all', 
        toastType === 'success' ? 'bg-green-50/90 text-green-700 border-green-200' : 
        toastType === 'danger' ? 'bg-red-50/90 text-red-700 border-red-200' : 
        'bg-amber-50/90 text-amber-700 border-amber-200']">
        <span class="material-symbols-outlined text-[20px]">{{ toastType === 'success' ? 'check_circle' : toastType === 'danger' ? 'error' : 'warning' }}</span>
        <span class="text-sm font-bold tracking-tight">{{ toastMsg }}</span>
      </div>
    </Transition>

    <div class="max-w-7xl mx-auto space-y-6 bg-transparent">
      
      <!-- Header Area -->
      <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 bg-transparent text-left">
        <div class="bg-transparent text-left">
          <h1 class="text-xl font-semibold text-[var(--sys-text-primary)] mb-1">Dữ liệu Chấm công & Thời gian</h1>
          <p class="text-sm text-[var(--sys-text-secondary)]">Ghi nhận giờ bắt đầu/kết thúc ca làm việc và tra cứu lịch sử chuyên cần.</p>
        </div>
        <div class="hidden md:block shrink-0">
          <p class="text-[13px] font-bold text-[var(--sys-brand-solid)] bg-[var(--sys-brand-soft)] px-3 py-1.5 rounded-md border border-[var(--sys-brand-border)] shadow-sm">{{ currentDateStr }}</p>
        </div>
      </div>

      <!-- Clock-in/out Section -->
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 bg-transparent">
        <div class="lg:col-span-8">
          <div class="bg-[var(--sys-bg-surface)] rounded-lg border border-[var(--sys-border-subtle)] p-6 md:p-10 flex flex-col items-center justify-center text-center shadow-sm relative overflow-hidden group">
            <div class="absolute top-0 right-0 w-32 h-32 bg-[var(--sys-brand-soft)] rounded-full -mr-16 -mt-16 opacity-30 blur-2xl transition-transform group-hover:scale-110"></div>
            
            <!-- Real-time Clock Style -->
            <div class="flex gap-4 mb-8 items-center">
              <div class="flex flex-col items-center gap-1.5">
                <div class="w-20 h-24 bg-[var(--sys-bg-page)] rounded-md border border-[var(--sys-border-strong)] flex items-center justify-center shadow-inner">
                  <span class="text-4xl font-bold text-[var(--sys-brand-solid)]">{{ currentHours }}</span>
                </div>
                <span class="text-[10px] font-bold text-[var(--sys-text-secondary)] uppercase tracking-widest opacity-60">Giờ</span>
              </div>
              <div class="text-3xl font-bold text-[var(--sys-text-disabled)]">:</div>
              <div class="flex flex-col items-center gap-1.5">
                <div class="w-20 h-24 bg-[var(--sys-bg-page)] rounded-md border border-[var(--sys-border-strong)] flex items-center justify-center shadow-inner">
                  <span class="text-4xl font-bold text-[var(--sys-brand-solid)]">{{ currentMinutes }}</span>
                </div>
                <span class="text-[10px] font-bold text-[var(--sys-text-secondary)] uppercase tracking-widest opacity-60">Phút</span>
              </div>
              <div class="text-3xl font-bold text-[var(--sys-text-disabled)] opacity-30">:</div>
              <div class="flex flex-col items-center gap-1.5">
                <div class="w-20 h-24 bg-[var(--sys-bg-page)] rounded-md border border-[var(--sys-border-strong)] flex items-center justify-center shadow-inner">
                  <span class="text-4xl font-bold text-[var(--sys-brand-solid)] opacity-60">{{ currentSeconds }}</span>
                </div>
                <span class="text-[10px] font-bold text-[var(--sys-text-secondary)] uppercase tracking-widest opacity-60">Giây</span>
              </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex gap-4 w-full max-w-lg">
              <button 
                @click="handleCheckIn" 
                :disabled="!canCheckIn"
                :class="[
                  'flex-1 h-12 rounded-md font-bold text-[14px] uppercase tracking-wide shadow-md transition-all flex items-center justify-center gap-2',
                  !canCheckIn ? 'bg-gray-400 cursor-not-allowed opacity-50' : 'bg-[var(--sys-brand-solid)] hover:brightness-95 active:scale-95 text-white'
                ]"
              >
                <span class="material-symbols-outlined text-[24px]">login</span>
                {{ checkInActionText }}
              </button>
              <button 
                @click="handleCheckOut" 
                :disabled="!canCheckOut"
                :class="[
                  'flex-1 h-12 rounded-md font-bold text-[14px] uppercase tracking-wide transition-all flex items-center justify-center gap-2 shadow-sm',
                  (!canCheckOut) 
                    ? 'bg-gray-100 text-gray-400 cursor-not-allowed border-gray-200' 
                    : 'bg-white hover:bg-[var(--sys-bg-page)] text-[var(--sys-text-primary)] border border-[var(--sys-border-strong)] active:scale-95'
                ]"
              >
                <span class="material-symbols-outlined text-[24px]">logout</span>
                {{ checkOutActionText }}
              </button>
            </div>
            <p class="text-xs text-[var(--sys-text-secondary)] mt-6 flex items-center gap-2 opacity-80">
              <span class="material-symbols-outlined text-[16px]">history</span>
              Ghi nhận gần nhất: <span class="font-bold text-[var(--sys-text-primary)]">{{ lastLogTime }}</span>
            </p>
          </div>
        </div>

        <!-- Today's Summary -->
        <div class="lg:col-span-4">
          <div class="bg-[var(--sys-bg-surface)] rounded-lg border border-[var(--sys-border-subtle)] p-6 h-full flex flex-col shadow-sm">
            <h3 class="text-xs font-bold text-[var(--sys-text-secondary)] uppercase tracking-wider mb-6 flex items-center gap-2">
              <span class="material-symbols-outlined text-[var(--sys-brand-solid)] text-[20px]">analytics</span>
              Thông số hôm nay
            </h3>
            <div class="space-y-3 flex-grow">
              <div class="flex justify-between items-center p-3 rounded-md bg-[var(--sys-bg-page)] border border-[var(--sys-border-subtle)]">
                <span class="text-[12px] font-medium text-[var(--sys-text-secondary)]">Ca áp dụng:</span>
                <span class="text-[13px] font-bold text-[var(--sys-text-primary)] text-right">{{ todayShiftTitle }}</span>
              </div>
              <div class="flex justify-between items-center p-3 rounded-md bg-[var(--sys-bg-page)] border border-[var(--sys-border-subtle)]">
                <span class="text-[12px] font-medium text-[var(--sys-text-secondary)]">Trạng thái lịch:</span>
                <span class="text-[13px] font-bold text-[var(--sys-text-primary)] text-right">{{ todayShiftMeta }}</span>
              </div>
              <div class="flex justify-between items-center p-3 rounded-md bg-[var(--sys-bg-page)] border border-[var(--sys-border-subtle)]">
                <span class="text-[12px] font-medium text-[var(--sys-text-secondary)]">Thời điểm Vào 1:</span>
                <span class="text-[13px] font-bold text-[var(--sys-text-primary)] text-right">{{ formatAttendancePoint(attendanceToday?.checkIn1, attendanceTodayDate) }}</span>
              </div>
              <div class="flex justify-between items-center p-3 rounded-md bg-[var(--sys-bg-page)] border border-[var(--sys-border-subtle)]">
                <span class="text-[12px] font-medium text-[var(--sys-text-secondary)]">Thời điểm Vào 2:</span>
                <span class="text-[13px] font-bold text-[var(--sys-text-primary)] text-right">{{ formatAttendancePoint(attendanceToday?.checkIn2, attendanceTodayDate) }}</span>
              </div>
              <div class="flex justify-between items-center p-3 rounded-md bg-[var(--sys-bg-page)] border border-[var(--sys-border-subtle)]">
                <span class="text-[12px] font-medium text-[var(--sys-text-secondary)]">Thời điểm Ra 1:</span>
                <span class="text-[13px] font-bold text-[var(--sys-text-primary)] text-right">{{ formatAttendancePoint(attendanceToday?.checkOut1, attendanceTodayDate) }}</span>
              </div>
              <div class="flex justify-between items-center p-3 rounded-md bg-[var(--sys-bg-page)] border border-[var(--sys-border-subtle)]">
                <span class="text-[12px] font-medium text-[var(--sys-text-secondary)]">Thời điểm Ra 2:</span>
                <span class="text-[13px] font-bold text-[var(--sys-text-primary)] text-right">{{ formatAttendancePoint(attendanceToday?.checkOut2, attendanceTodayDate) }}</span>
              </div>
              <div class="flex justify-between items-center p-3 rounded-md bg-[var(--sys-brand-soft)] border border-[var(--sys-brand-border)]">
                <span class="text-[12px] font-bold text-[var(--sys-brand-solid)]">Tổng tích lũy hiện tại:</span>
                <span class="text-[13px] font-bold text-[var(--sys-brand-solid)]">{{ calculateTotal(attendanceToday || {}) }}</span>
              </div>
            </div>
            <div class="mt-8 pt-6 border-t border-[var(--sys-border-subtle)] text-center">
              <div class="h-1.5 w-full bg-[var(--sys-bg-page)] rounded-full overflow-hidden mb-3 border border-[var(--sys-border-subtle)] shadow-inner">
                <div class="h-full bg-[var(--sys-brand-solid)] transition-all" style="width: 56.25%;"></div>
              </div>
              <span class="text-[11px] font-bold text-[var(--sys-text-disabled)] uppercase tracking-wide">Bạn đã hoàn thành 56% chỉ tiêu ngày</span>
            </div>
          </div>
        </div>
      </div>

      <!-- History Table Section -->
      <div class="bg-[var(--sys-bg-surface)] rounded-lg border border-[var(--sys-border-subtle)] shadow-sm overflow-hidden flex flex-col">
        <div class="px-5 py-4 border-b border-[var(--sys-border-subtle)] flex flex-wrap gap-4 items-center justify-between bg-[var(--sys-bg-page)]/50">
          <h3 class="text-sm font-semibold text-[var(--sys-text-primary)] uppercase tracking-wide m-0">Tra cứu lịch sử công ca</h3>
          <div class="flex flex-wrap gap-3 items-center">
            <button @click="showRequestModal = true" class="h-11 px-5 bg-[var(--sys-brand-solid)] text-white rounded-md font-bold text-[11px] uppercase tracking-wide hover:brightness-90 transition-all flex items-center gap-2 shadow-sm">
              <span class="material-symbols-outlined text-[20px]">add_circle</span>
              Yêu cầu bổ sung
            </button>
            
            <Dropdown 
              v-model="selectedMonth"
              :options="monthOptions"
              class="h-11 w-36"
            />
            
            <Dropdown 
              v-model="selectedYear"
              :options="yearOptions"
              class="h-11 w-32"
            />

            <button @click="loadAttendance" class="w-11 h-11 flex items-center justify-center rounded-md bg-white border border-[var(--sys-border-subtle)] text-[var(--sys-text-secondary)] hover:border-[var(--sys-brand-solid)] transition-all shadow-sm">
              <span class="material-symbols-outlined text-[18px]">filter_list</span>
            </button>
          </div>
        </div>
        
        <div class="overflow-x-auto custom-scrollbar">
          <table class="w-full text-left border-collapse">
            <thead class="bg-[var(--sys-bg-page)]">
              <tr>
                <th class="px-5 py-3 text-[12px] font-semibold text-[var(--sys-text-secondary)] border-b border-[var(--sys-border-subtle)] uppercase">Ngày / Thứ</th>
                <th class="px-5 py-3 text-[12px] font-semibold text-[var(--sys-text-secondary)] border-b border-[var(--sys-border-subtle)] uppercase">Thời điểm Vào</th>
                <th class="px-5 py-3 text-[12px] font-semibold text-[var(--sys-text-secondary)] border-b border-[var(--sys-border-subtle)] uppercase">Thời điểm Ra</th>
                <th class="px-5 py-3 text-[12px] font-semibold text-[var(--sys-text-secondary)] border-b border-[var(--sys-border-subtle)] uppercase text-center">Tổng giờ</th>
                <th class="px-5 py-3 text-[12px] font-semibold text-[var(--sys-text-secondary)] border-b border-[var(--sys-border-subtle)] uppercase text-right">Trạng thái</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-[var(--sys-border-subtle)]">
              <tr v-for="(item, index) in pagedHistoryItems" :key="index" class="hover:bg-[var(--sys-bg-hover)] transition-colors">
                <td class="px-5 py-3 text-[13px] font-bold text-[var(--sys-text-primary)] bg-transparent">{{ item.day }}</td>
                <td class="px-5 py-3 text-[13px] font-medium text-[var(--sys-text-secondary)] bg-transparent">{{ item.checkIn }}</td>
                <td class="px-5 py-3 text-[13px] font-medium text-[var(--sys-text-secondary)] bg-transparent">{{ item.checkOut }}</td>
                <td class="px-5 py-3 text-center bg-transparent">
                  <span class="text-[13px] font-bold text-[var(--sys-text-primary)]">{{ item.total }}</span>
                </td>
                <td class="px-5 py-3 text-right bg-transparent">
                  <span :class="getStatusClass(item.status)" class="px-2 py-0.5 rounded-md text-[10px] font-bold uppercase tracking-wider border">
                    {{ item.status }}
                  </span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        
        <div class="px-5 py-3 border-t border-[var(--sys-border-subtle)] bg-[var(--sys-bg-page)]/50 flex justify-between items-center">
          <p class="text-[11px] font-bold text-[var(--sys-text-disabled)] uppercase">Hiển thị {{ pagedHistoryItems.length }}/{{ filteredHistoryItems.length }} dữ liệu</p>
          <div class="flex items-center gap-1.5">
            <button :disabled="currentPage <= 1" @click="goPrevPage" class="px-3 py-1 bg-white border border-[var(--sys-border-subtle)] rounded-md text-[11px] font-bold text-[var(--sys-text-secondary)] hover:bg-[var(--sys-bg-hover)] transition-all disabled:opacity-50 disabled:cursor-not-allowed">Trước</button>
            <button class="w-8 h-8 flex items-center justify-center bg-[var(--sys-brand-solid)] text-white text-[11px] font-bold rounded-md shadow-sm">{{ currentPage }}</button>
            <button :disabled="currentPage >= totalPages" @click="goNextPage" class="px-3 py-1 bg-white border border-[var(--sys-border-subtle)] rounded-md text-[11px] font-bold text-[var(--sys-text-secondary)] hover:bg-[var(--sys-bg-hover)] transition-all disabled:opacity-50 disabled:cursor-not-allowed">Tiếp</button>
          </div>
        </div>
      </div>

      <!-- Action Footer -->
      <div class="flex justify-end pt-4">
        <router-link to="/nhanvien/giaitrinhchamcong" class="h-10 px-6 bg-[var(--sys-warning-soft)] text-[var(--sys-warning-text)] border border-[var(--sys-warning-border)] rounded-md font-bold text-[11px] uppercase tracking-wide hover:brightness-95 transition-all flex items-center gap-2 shadow-sm">
          <span class="material-symbols-outlined text-[20px]">report_problem</span>
          Giải trình sai lệch
        </router-link>
      </div>

      <!-- Attendance Addition Request Modal -->
      <Teleport to="body">
        <Transition name="toast">
          <div v-if="showRequestModal" class="fixed inset-0 z-[10000] flex items-center justify-center p-4">
            <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="showRequestModal = false"></div>
            <div class="relative bg-white border border-[var(--sys-border-subtle)] w-full max-w-xl rounded-xl shadow-2xl overflow-hidden flex flex-col text-left">
              <!-- Modal Header -->
              <div class="px-6 py-4 border-b border-[var(--sys-border-subtle)] flex items-center justify-between bg-[var(--sys-bg-page)]/50">
                <div class="flex items-center gap-3">
                  <span class="material-symbols-outlined text-[var(--sys-brand-solid)]">edit_calendar</span>
                  <div>
                    <h3 class="text-sm font-bold text-[var(--sys-text-primary)] m-0 uppercase tracking-wide">Yêu cầu bổ sung chấm công</h3>
                    <p class="text-[11px] text-[var(--sys-text-secondary)] mt-0.5 font-medium uppercase tracking-widest opacity-80">Gửi admin phê duyệt điều chỉnh dữ liệu</p>
                  </div>
                </div>
                <button @click="showRequestModal = false" class="text-[var(--sys-text-secondary)] hover:text-[var(--sys-text-primary)] transition-colors">
                  <span class="material-symbols-outlined">close</span>
                </button>
              </div>

              <!-- Modal Body -->
              <div class="p-6 space-y-4">
                <div class="grid grid-cols-2 gap-4">
                  <div class="space-y-1.5 flex flex-col">
                    <label class="text-[11px] font-bold text-[var(--sys-text-secondary)] uppercase tracking-wider">Ngày cần bổ sung *</label>
                    <input v-model="requestForm.date" type="date" class="h-10 px-3 bg-[var(--sys-bg-page)] border border-[var(--sys-border-strong)] rounded text-sm font-medium outline-none focus:border-[var(--sys-brand-solid)] transition-all">
                  </div>
                  <div class="space-y-1.5 flex flex-col">
                    <label class="text-[11px] font-bold text-[var(--sys-text-secondary)] uppercase tracking-wider">Thời lượng dự kiến</label>
                    <input v-model="requestForm.duration" type="text" placeholder="Ví dụ: 8h" class="h-10 px-3 bg-[var(--sys-bg-page)] border border-[var(--sys-border-strong)] rounded text-sm font-medium outline-none focus:border-[var(--sys-brand-solid)] transition-all">
                  </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                  <div class="space-y-1.5 flex flex-col">
                    <label class="text-[11px] font-bold text-[var(--sys-text-secondary)] uppercase tracking-wider">Giờ vào lần 1</label>
                    <input v-model="requestForm.checkIn1" type="time" class="h-10 px-3 bg-[var(--sys-bg-page)] border border-[var(--sys-border-strong)] rounded text-sm font-medium outline-none focus:border-[var(--sys-brand-solid)] transition-all">
                  </div>
                  <div class="space-y-1.5 flex flex-col">
                    <label class="text-[11px] font-bold text-[var(--sys-text-secondary)] uppercase tracking-wider">Giờ ra lần 1</label>
                    <input v-model="requestForm.checkOut1" type="time" class="h-10 px-3 bg-[var(--sys-bg-page)] border border-[var(--sys-border-strong)] rounded text-sm font-medium outline-none focus:border-[var(--sys-brand-solid)] transition-all">
                  </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                  <div class="space-y-1.5 flex flex-col">
                    <label class="text-[11px] font-bold text-[var(--sys-text-secondary)] uppercase tracking-wider">Giờ vào lần 2</label>
                    <input v-model="requestForm.checkIn2" type="time" class="h-10 px-3 bg-[var(--sys-bg-page)] border border-[var(--sys-border-strong)] rounded text-sm font-medium outline-none focus:border-[var(--sys-brand-solid)] transition-all">
                  </div>
                  <div class="space-y-1.5 flex flex-col">
                    <label class="text-[11px] font-bold text-[var(--sys-text-secondary)] uppercase tracking-wider">Giờ ra lần 2</label>
                    <input v-model="requestForm.checkOut2" type="time" class="h-10 px-3 bg-[var(--sys-bg-page)] border border-[var(--sys-border-strong)] rounded text-sm font-medium outline-none focus:border-[var(--sys-brand-solid)] transition-all">
                  </div>
                </div>

                <div class="space-y-1.5 flex flex-col">
                  <label class="text-[11px] font-bold text-[var(--sys-text-secondary)] uppercase tracking-wider">Lý do điều chỉnh *</label>
                  <textarea v-model="requestForm.reason" rows="3" placeholder="Ví dụ: Quên quẹt thẻ, đi công tác bên ngoài..." class="p-3 bg-[var(--sys-bg-page)] border border-[var(--sys-border-strong)] rounded text-sm font-medium outline-none focus:border-[var(--sys-brand-solid)] transition-all resize-none"></textarea>
                </div>
              </div>

              <!-- Modal Footer -->
              <div class="px-6 py-4 border-t border-[var(--sys-border-subtle)] bg-[var(--sys-bg-page)]/30 flex justify-end gap-3">
                <button @click="showRequestModal = false" class="px-6 py-2 text-[11px] font-bold text-[var(--sys-text-secondary)] uppercase tracking-widest hover:bg-[var(--sys-bg-hover)] rounded-md transition-all border border-transparent hover:border-[var(--sys-border-strong)]">Hủy bỏ</button>
                <button @click="submitAdditionRequest" class="px-8 py-2 bg-[var(--sys-brand-solid)] text-white rounded-md font-bold text-[11px] uppercase tracking-widest hover:brightness-110 shadow-lg active:scale-95 transition-all flex items-center gap-2">
                  <span class="material-symbols-outlined text-[18px]">send</span>
                  Gửi yêu cầu
                </button>
              </div>
            </div>
          </div>
        </Transition>
      </Teleport>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, computed, watch } from 'vue';
import Dropdown from '@/components/Dropdown.vue';
import { useCurrentUser } from '@/composables/useCurrentUser.js';
import { BE_API_BASE, getAccessToken } from '@/services/runtimeConfig.js';
import { handleUnauthorized } from '@/services/session.js';
import { fetchMyShiftToday } from '@/services/workforceApi.js';
import { loadGeoState, nowApiTime, requestAttendancePrecheck } from '@/services/attendanceGeo.js';

const currentHours = ref('00');
const currentMinutes = ref('00');
const currentSeconds = ref('00');
const currentDateStr = ref('');
let timerInterval = null;
let geoInterval = null;

const selectedMonth = ref(new Date().getMonth() + 1);
const selectedYear = ref(new Date().getFullYear());
const currentPage = ref(1);
const pageSize = 10;

const currentYear = new Date().getFullYear();
const currentMonth = new Date().getMonth() + 1;
const yearOptions = computed(() => [
  { label: `Năm ${currentYear}`, value: currentYear },
  { label: `Năm ${currentYear - 1}`, value: currentYear - 1 },
  { label: `Năm ${currentYear - 2}`, value: currentYear - 2 },
]);
const monthOptions = computed(() => {
  const maxMonth = selectedYear.value === currentYear ? currentMonth : 12;
  return Array.from({ length: maxMonth }, (_, i) => ({ label: `Tháng ${i + 1}`, value: i + 1 }));
});

const { employeeId: currentEmpId } = useCurrentUser();
const userId = computed(() => currentEmpId.value);
const attendanceToday = ref(null);
const attendanceRows = ref([]);
const todayShiftContext = ref(null);
const geoState = ref(loadGeoState() || null);

const showToast = ref(false);
const toastMsg = ref('');
const toastType = ref('success');
const showRequestModal = ref(false);
const requestTypeId = ref(null);

const getLocalDateStr = (date = new Date()) => {
  const y = date.getFullYear();
  const m = String(date.getMonth() + 1).padStart(2, '0');
  const d = String(date.getDate()).padStart(2, '0');
  return `${y}-${m}-${d}`;
};

const requestForm = ref({
  date: getLocalDateStr(),
  checkIn1: '',
  checkOut1: '',
  checkIn2: '',
  checkOut2: '',
  duration: '',
  reason: '',
});

const formatDateToDDMMYYYY = (value) => {
  const raw = String(value || '').trim();
  if (!raw) return '';
  const datePart = raw.includes('T') ? raw.slice(0, 10) : raw.slice(0, 10);
  const [y, m, d] = datePart.split('-');
  if (!y || !m || !d) return '';
  return `${d}/${m}/${y}`;
};

const extractTimeHHMM = (value) => {
  const raw = String(value || '').trim();
  if (!raw) return '';
  if (raw.includes('T')) return raw.slice(11, 16);
  if (raw.includes(' ')) {
    const parts = raw.split(' ');
    return (parts[1] || '').slice(0, 5);
  }
  if (/^\d{2}:\d{2}(:\d{2})?$/.test(raw)) return raw.slice(0, 5);
  return '';
};

const formatAttendancePoint = (value, attendanceDate) => {
  if (!value) return '--:-- --/--/----';
  const hhmm = extractTimeHHMM(value);
  const ddmmyyyy = formatDateToDDMMYYYY(attendanceDate);
  if (!hhmm || !ddmmyyyy) return '--:-- --/--/----';
  return `${hhmm} ${ddmmyyyy}`;
};

const attendanceTodayDate = computed(() => attendanceToday.value?.attendance_date || '');
const todayShiftTitle = computed(() => {
  const payload = todayShiftContext.value || {};
  const shift = payload.shift || null;
  const holiday = payload.holiday || null;
  const leave = payload.leave || null;
  const remote = payload.remote || null;
  const businessTrip = payload.business_trip || null;

  if (holiday?.holiday_name) return holiday.holiday_name;
  if (leave?.leave_type_name) return leave.leave_type_name;
  if (businessTrip) return 'Công tác';
  if (remote) return shift?.shift_name ? `${shift.shift_name} · Remote` : 'Làm việc từ xa';
  if (shift?.shift_name) {
    const window = [shift.start_time, shift.end_time].filter(Boolean).join(' - ');
    return window ? `${shift.shift_name} · ${window}` : shift.shift_name;
  }
  return 'Chưa phân ca';
});
const todayShiftMeta = computed(() => {
  const payload = todayShiftContext.value || {};
  const shift = payload.shift || null;
  const holiday = payload.holiday || null;
  const leave = payload.leave || null;
  const remote = payload.remote || null;
  const businessTrip = payload.business_trip || null;

  if (holiday?.description) return holiday.description;
  if (holiday) return 'Hệ thống đang áp ngày nghỉ lễ cho hôm nay.';
  if (leave) return 'Bạn đã có đơn nghỉ được duyệt trong ngày.';
  if (businessTrip) return 'Ngày làm việc được chuyển sang lịch công tác.';
  if (remote) return 'Ngày làm việc được xử lý theo lịch remote đã duyệt.';

  const sourceMap = {
    OVERRIDE: 'Ca được chỉnh riêng cho hôm nay.',
    EMPLOYEE_DEFAULT: 'Ca mặc định cá nhân đang có hiệu lực.',
    DEPARTMENT_SCHEDULE: 'Ca lấy từ lịch phân ca phòng ban.',
  };
  return sourceMap[String(shift?.source || '').toUpperCase()] || 'Hệ thống đang dùng lịch làm việc hiện tại.';
});

const lastLogTime = computed(() => {
  if (!attendanceToday.value) return '--:-- --/--/----';
  const latest =
    attendanceToday.value.check_out_time_2 ||
    attendanceToday.value.check_out_time ||
    attendanceToday.value.check_in_time_2 ||
    attendanceToday.value.check_in_time;
  return formatAttendancePoint(latest, attendanceTodayDate.value);
});

const canCheckIn = computed(() => {
  if (!isGeoClockAllowed.value) return false;
  const row = attendanceToday.value;
  if (!row) return true; // check-in 1
  if (row.check_out_time_2) return false; // done
  if (!row.check_out_time) return false; // must checkout 1 first
  return !row.check_in_time_2; // check-in 2
});

const canCheckOut = computed(() => {
  if (!isGeoClockAllowed.value) return false;
  const row = attendanceToday.value;
  if (!row) return false;
  if (row.check_out_time_2) return false;
  if (!row.check_out_time) return Boolean(row.check_in_time); // checkout 1
  return Boolean(row.check_in_time_2 && !row.check_out_time_2); // checkout 2
});

const checkInActionText = computed(() => {
  if (!isGeoClockAllowed.value) return geoBlockedLabel.value;
  const row = attendanceToday.value;
  if (!row) return 'Vào lần 1';
  if (!row.check_out_time) return 'Chờ ra lần 1';
  if (!row.check_in_time_2) return 'Vào lần 2';
  return 'Đã vào đủ';
});

const checkOutActionText = computed(() => {
  if (!isGeoClockAllowed.value) return geoBlockedLabel.value;
  const row = attendanceToday.value;
  if (!row) return 'Ra lần 1';
  if (!row.check_out_time) return 'Ra lần 1';
  if (!row.check_in_time_2) return 'Chờ vào lần 2';
  if (!row.check_out_time_2) return 'Ra lần 2';
  return 'Đã ra đủ';
});

const triggerToast = (msg, type = 'success') => {
  toastMsg.value = msg;
  toastType.value = type;
  showToast.value = true;
  setTimeout(() => { showToast.value = false; }, 3000);
};

const authHeaders = () => {
  const token = getAccessToken();
  if (!token) throw new Error('Thiếu access token');
  return {
    Authorization: `Bearer ${token}`,
    'Content-Type': 'application/json',
  };
};

const apiRequest = async (path, options = {}) => {
  const res = await fetch(`${BE_API_BASE}${path}`, {
    ...options,
    headers: {
      ...authHeaders(),
      ...(options.headers || {}),
    },
  });
  if (res.status === 401) {
    handleUnauthorized();
    throw new Error('Phiên đăng nhập đã hết hạn');
  }
  const payload = await res.json().catch(() => ({}));
  if (!res.ok || payload?.success === false) {
    const error = new Error(payload?.message || `Request failed (${res.status})`);
    error.status = res.status;
    error.payload = payload;
    throw error;
  }
  return payload;
};

const apiRequestWithFallback = async (primaryPath, fallbackPath, options = {}) => {
  try {
    return await apiRequest(primaryPath, options);
  } catch (error) {
    if (Number(error?.status) !== 404 || !fallbackPath) {
      throw error;
    }
    return apiRequest(fallbackPath, options);
  }
};

const geoRiskLevel = computed(() => {
  const raw = geoState.value?.riskLevel ?? geoState.value?.raw?.risk_level ?? '';
  return String(raw || '').toUpperCase();
});

const geoReasonCode = computed(() => {
  const raw = geoState.value?.reasonCode ?? geoState.value?.raw?.reason_code ?? '';
  return String(raw || '').toUpperCase();
});

const isGeoClockAllowed = computed(() => {
  const risk = geoRiskLevel.value;
  const allowClockIn = Boolean(
    geoState.value?.allowClockIn
      ?? geoState.value?.raw?.allow_clock_in
      ?? geoState.value?.raw?.precheck_token
  );
  return allowClockIn && (risk === 'GREEN' || risk === 'YELLOW');
});

const geoBlockedLabel = computed(() => {
  const reason = geoReasonCode.value;
  if (reason === 'DEVICE_NOT_TRUSTED') return 'Thiết bị mới';
  if (reason === 'PERMISSION_DENIED') return 'Bật GPS';
  if (reason === 'INSECURE_CONTEXT') return 'Cần HTTPS';
  if (geoRiskLevel.value === 'RED') return 'Ngoài vùng';
  return 'Đang kiểm tra GPS';
});

const mapAttendanceStatusToUi = (statusRaw) => {
  const key = String(statusRaw || '').toUpperCase();
  if (key === 'TỪ_CHỐI') return 'Vắng mặt';
  if (key === 'CHỜ_DUYỆT') return 'Hợp lệ';
  if (key === 'ĐÃ_DUYỆT') return 'Hợp lệ';
  if (key === 'NHẬP_THỦ_CÔNG') return 'Hợp lệ';
  return 'Hợp lệ';
};

const calculateTotal = (item) => {
  if (!item.check_in_time || !item.check_out_time) return '0h';
  const startTime = extractTimeHHMM(item.check_in_time);
  const endTime = extractTimeHHMM(item.check_out_time_2 || item.check_out_time);
  if (!startTime || !endTime) return '0h';
  const start = new Date(`2000-01-01T${startTime}:00`);
  const end = new Date(`2000-01-01T${endTime}:00`);
  const diff = (end - start) / (1000 * 60 * 60);
  return diff > 0 ? `${diff.toFixed(1)}h` : '0h';
};

const filteredHistoryItems = computed(() => {
  return attendanceRows.value
    .filter((item) => {
      const dateRaw = String(item.attendance_date || '');
      const month = Number(dateRaw.slice(5, 7));
      const year = Number(dateRaw.slice(0, 4));
      return month === Number(selectedMonth.value) && year === Number(selectedYear.value);
    })
    .map((item) => {
      const dateRaw = String(item.attendance_date || '');
      const d = new Date(`${dateRaw}T00:00:00`);
      const dayStr = ['Chủ Nhật', 'Thứ Hai', 'Thứ Ba', 'Thứ Tư', 'Thứ Năm', 'Thứ Sáu', 'Thứ Bảy'][d.getDay()];
      const checkInSegments = [
        formatAttendancePoint(item.check_in_time, dateRaw),
        formatAttendancePoint(item.check_in_time_2, dateRaw),
      ].filter((entry) => !entry.startsWith('--:--'));
      const checkOutSegments = [
        formatAttendancePoint(item.check_out_time, dateRaw),
        formatAttendancePoint(item.check_out_time_2, dateRaw),
      ].filter((entry) => !entry.startsWith('--:--'));

      return {
        day: `${dayStr}, ${d.getDate()}/${d.getMonth() + 1}`,
        checkIn: checkInSegments.length > 0 ? checkInSegments.join(' | ') : '--:-- --/--/----',
        checkOut: checkOutSegments.length > 0 ? checkOutSegments.join(' | ') : '--:-- --/--/----',
        total: calculateTotal(item),
        status: mapAttendanceStatusToUi(item.status),
      };
    });
});

const totalPages = computed(() => Math.max(1, Math.ceil(filteredHistoryItems.value.length / pageSize)));
const pagedHistoryItems = computed(() => {
  const start = (currentPage.value - 1) * pageSize;
  return filteredHistoryItems.value.slice(start, start + pageSize);
});

watch([selectedMonth, selectedYear], () => {
  if (selectedYear.value === currentYear && selectedMonth.value > currentMonth) {
    selectedMonth.value = currentMonth;
  }
  currentPage.value = 1;
});

const goPrevPage = () => {
  if (currentPage.value > 1) currentPage.value -= 1;
};

const goNextPage = () => {
  if (currentPage.value < totalPages.value) currentPage.value += 1;
};

const resolveAttendanceRequestTypeId = async () => {
  if (requestTypeId.value) return requestTypeId.value;
  const payload = await apiRequest('/request-types?page=1&per_page=200');
  const items = Array.isArray(payload?.data) ? payload.data : [];
  const matched = items.find((it) => {
    const code = String(it.request_type_code || '').toUpperCase();
    const name = String(it.request_type_name || '').toUpperCase();
    return code.includes('CHAM_CONG') || name.includes('CHẤM CÔNG') || name.includes('CHAM CONG');
  });
  const fallback = items[0] || null;
  requestTypeId.value = Number(matched?.request_type_id || fallback?.request_type_id || 0) || null;
  return requestTypeId.value;
};

const loadAttendance = async () => {
  try {
    if (!userId.value) return;
    const from = new Date();
    from.setDate(from.getDate() - 400);
    const dateFrom = getLocalDateStr(from);
    const dateTo = getLocalDateStr(new Date());
    const payload = await apiRequest(`/attendances?page=1&per_page=500&date_from=${dateFrom}&date_to=${dateTo}`);
    const rows = Array.isArray(payload?.data) ? payload.data : [];
    attendanceRows.value = rows.sort((a, b) => {
      const da = `${a.attendance_date || ''} ${a.check_in_time || ''}`;
      const db = `${b.attendance_date || ''} ${b.check_in_time || ''}`;
      return db.localeCompare(da);
    });
    const today = getLocalDateStr();
    attendanceToday.value = attendanceRows.value.find((it) => String(it.attendance_date || '') === today) || null;
    try {
      todayShiftContext.value = await fetchMyShiftToday(today);
    } catch (shiftError) {
      console.warn('Không tải được ca hôm nay cho màn chấm công:', shiftError);
      todayShiftContext.value = null;
    }
  } catch (error) {
    console.error('Load attendance error:', error);
    triggerToast(`Không tải được dữ liệu chấm công: ${error.message}`, 'danger');
  }
};

const handleCheckIn = async () => {
  try {
    if (!isGeoClockAllowed.value) {
      triggerToast('Bạn đang ngoài khu vực làm việc. Chỉ GREEN/YELLOW mới được chấm công.', 'danger');
      return;
    }
    if (attendanceToday.value && !attendanceToday.value.check_out_time) {
      triggerToast('Hãy chấm ra lần 1 trước khi vào lần 2.', 'warning');
      return;
    }
    if (attendanceToday.value && attendanceToday.value.check_in_time_2) {
      triggerToast('Bạn đã hoàn tất các lượt vào hôm nay.', 'warning');
      return;
    }

    const precheck = await refreshGeoPrecheck('CHECKIN');
    if (!precheck?.precheck_token) {
      triggerToast(precheck?.user_message || 'Ngoài vùng cho phép. Không thể chấm công.', 'danger');
      return;
    }

    const payload = await apiRequestWithFallback('/attendance/checkin', '/attendance/clock-in', {
      method: 'POST',
      body: JSON.stringify({
        employee_id: userId.value,
        precheck_token: precheck.precheck_token,
        client_time: nowApiTime(),
      }),
    });
    triggerToast(payload?.data?.user_message || 'Chấm vào thành công!');
    await loadAttendance();
  } catch (error) {
    console.error('Check-in error:', error);
    triggerToast(`Không thể chấm vào: ${error.message}`, 'danger');
  }
};

const handleCheckOut = async () => {
  try {
    if (!isGeoClockAllowed.value) {
      triggerToast('Bạn đang ngoài khu vực làm việc. Chỉ GREEN/YELLOW mới được chấm công.', 'danger');
      return;
    }
    if (!attendanceToday.value || !attendanceToday.value.check_in_time) {
      triggerToast('Bạn chưa vào ca hôm nay.', 'danger');
      return;
    }
    if (!attendanceToday.value.check_out_time) {
      const precheck = await refreshGeoPrecheck('CHECKOUT');
      if (!precheck?.precheck_token) {
        triggerToast(precheck?.user_message || 'Ngoài vùng cho phép. Không thể chấm công.', 'danger');
        return;
      }
      const payload = await apiRequestWithFallback('/attendance/checkout', '/attendance/clock-out', {
        method: 'POST',
        body: JSON.stringify({
          employee_id: userId.value,
          precheck_token: precheck.precheck_token,
          client_time: nowApiTime(),
        }),
      });
      triggerToast(payload?.data?.user_message || 'Chấm ra thành công!');
      await loadAttendance();
      return;
    }
    if (!attendanceToday.value.check_in_time_2) {
      triggerToast('Hãy chấm vào lần 2 trước khi ra lần 2.', 'warning');
      return;
    }
    if (!attendanceToday.value.check_out_time_2) {
      const precheck = await refreshGeoPrecheck('CHECKOUT');
      if (!precheck?.precheck_token) {
        triggerToast(precheck?.user_message || 'Ngoài vùng cho phép. Không thể chấm công.', 'danger');
        return;
      }
      const payload = await apiRequestWithFallback('/attendance/checkout', '/attendance/clock-out', {
        method: 'POST',
        body: JSON.stringify({
          employee_id: userId.value,
          precheck_token: precheck.precheck_token,
          client_time: nowApiTime(),
        }),
      });
      triggerToast(payload?.data?.user_message || 'Chấm ra thành công!');
      await loadAttendance();
      return;
    }
    triggerToast('Bạn đã hoàn tất chấm công hôm nay.', 'warning');
  } catch (error) {
    console.error('Check-out error:', error);
    triggerToast(`Không thể chấm ra: ${error.message}`, 'danger');
  }
};

const refreshGeoPrecheck = async (attendanceType = 'CHECKIN') => {
  if (!userId.value) return null;
  try {
    const result = await requestAttendancePrecheck({
      employeeId: userId.value,
      attendanceType,
    });
    geoState.value = result?.state || null;
    const precheck = result?.precheck || null;
    const riskLevel = String(precheck?.risk_level || '').toUpperCase();
    const allow = Boolean(precheck?.precheck_token) && (riskLevel === 'GREEN' || riskLevel === 'YELLOW');
    if (!allow) {
      triggerToast(precheck?.user_message || 'Bạn đang ngoài khu vực làm việc. Vui lòng đến đúng vị trí rồi thử lại.', 'danger');
    }
    return precheck;
  } catch (error) {
    const message = String(error?.message || 'Không lấy được GPS thật từ thiết bị. Vui lòng bật GPS rồi thử lại.');
    triggerToast(message, 'danger');
    return null;
  }
};

const submitAdditionRequest = async () => {
  if (!requestForm.value.date || !requestForm.value.reason.trim()) {
    triggerToast('Vui lòng điền đầy đủ các thông tin bắt buộc (*)', 'danger');
    return;
  }
  try {
    const typeId = await resolveAttendanceRequestTypeId();
    if (!typeId) throw new Error('Chưa cấu hình loại yêu cầu chấm công trong hệ thống.');
    const fromDate = `${requestForm.value.date} 00:00:00`;
    const toDate = `${requestForm.value.date} 23:59:59`;
    const note = [
      `Bổ sung công ngày ${requestForm.value.date}`,
      `Vào 1: ${requestForm.value.checkIn1 || '--'}`,
      `Ra 1: ${requestForm.value.checkOut1 || '--'}`,
      `Vào 2: ${requestForm.value.checkIn2 || '--'}`,
      `Ra 2: ${requestForm.value.checkOut2 || '--'}`,
    ].join(' | ');

    await apiRequest('/requests', {
      method: 'POST',
      body: JSON.stringify({
        request_type_id: typeId,
        requester_id: userId.value,
        request_date: getLocalDateStr(),
        from_date: fromDate,
        to_date: toDate,
        duration: requestForm.value.duration || null,
        reason: requestForm.value.reason.trim(),
        status: 'CHỜ_DUYỆT',
        notes: note,
      }),
    });

    triggerToast('Đã gửi yêu cầu bổ sung chấm công.');
    showRequestModal.value = false;
    requestForm.value = {
      date: getLocalDateStr(),
      checkIn1: '',
      checkOut1: '',
      checkIn2: '',
      checkOut2: '',
      duration: '',
      reason: '',
    };
  } catch (error) {
    console.error('Submit addition request error:', error);
    triggerToast(error?.message || 'Gửi yêu cầu thất bại.', 'danger');
  }
};

const updateTime = () => {
  const now = new Date();
  currentHours.value = String(now.getHours()).padStart(2, '0');
  currentMinutes.value = String(now.getMinutes()).padStart(2, '0');
  currentSeconds.value = String(now.getSeconds()).padStart(2, '0');
  const days = ['Chủ Nhật', 'Thứ Hai', 'Thứ Ba', 'Thứ Tư', 'Thứ Năm', 'Thứ Sáu', 'Thứ Bảy'];
  currentDateStr.value = `${days[now.getDay()]}, ${now.getDate()}/${now.getMonth() + 1}/${now.getFullYear()}`;
};

onMounted(() => {
  updateTime();
  timerInterval = setInterval(updateTime, 1000);
  void loadAttendance();
  void refreshGeoPrecheck('CHECKIN');
  geoInterval = setInterval(() => {
    void refreshGeoPrecheck('CHECKIN');
  }, 45000);
});

onUnmounted(() => {
  if (timerInterval) clearInterval(timerInterval);
  if (geoInterval) clearInterval(geoInterval);
});

const getStatusClass = (status) => {
  switch (status) {
    case 'Hợp lệ': return 'bg-[var(--sys-success-soft)] text-[var(--sys-success-text)] border-[var(--sys-success-border)]';
    case 'Đi muộn': return 'bg-[var(--sys-warning-soft)] text-[var(--sys-warning-text)] border-[var(--sys-warning-border)]';
    case 'Vắng mặt': return 'bg-[var(--sys-danger-soft)] text-[var(--sys-danger-text)] border-[var(--sys-danger-border)]';
    default: return 'bg-[var(--sys-bg-page)] text-[var(--sys-text-secondary)] border-[var(--sys-border-subtle)]';
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
.toast-enter-active, .toast-leave-active {
  transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}
.toast-enter-from, .toast-leave-to {
  opacity: 0;
  transform: translate(-50%, -20px) scale(0.9);
}
</style>
