<template>
  <div class="dashboard-wrapper min-h-screen bg-[var(--sys-bg-page)] text-[var(--sys-text-primary)] p-4 md:p-6 lg:p-8">
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
          <h1 class="text-xl font-semibold text-[var(--sys-text-primary)] mb-1">Chào buổi sáng, {{ fullName }}.</h1>
          <p class="text-sm text-[var(--sys-text-secondary)]">{{ currentDateStr }} Chúc bạn một ngày làm việc hiệu quả.</p>
        </div>
        <div class="hidden md:block shrink-0">
          <span class="bg-[var(--sys-brand-soft)] text-[var(--sys-brand-solid)] px-3 py-1.5 rounded-md font-bold text-[11px] uppercase tracking-wide border border-[var(--sys-brand-border)]">Portal Nhân Viên v2.0</span>
        </div>
      </div>

      <!-- Hero Section Grid -->
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 bg-transparent">
        
        <!-- Left Column: Clock & Summary -->
        <div class="lg:col-span-8 flex flex-col gap-6 bg-transparent">
          
          <!-- Check-in Hero Card -->
          <div class="bg-[var(--sys-bg-surface)] rounded-lg shadow-sm border border-[var(--sys-border-subtle)] p-6 md:p-8 relative overflow-hidden group">
            <div class="absolute top-0 right-0 w-48 h-48 bg-[var(--sys-brand-soft)] rounded-full -mr-24 -mt-24 opacity-30 blur-2xl"></div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 items-center relative z-10 bg-transparent">
              <!-- Digital Clock Display -->
              <div class="flex flex-col items-center md:items-start text-center md:text-left border-b md:border-b-0 md:border-r border-[var(--sys-border-subtle)] pb-6 md:pb-0 md:pr-8 bg-transparent">
                <p class="text-[11px] font-bold text-[var(--sys-text-secondary)] uppercase tracking-wider mb-4 opacity-60">Thời gian hệ thống</p>
                <div class="flex items-baseline gap-2 bg-transparent">
                  <span class="text-6xl font-bold text-[var(--sys-brand-solid)] tracking-tight">{{ currentHours }}:{{ currentMinutes }}</span>
                  <span class="text-xl font-semibold text-[var(--sys-text-secondary)] opacity-40">{{ currentSeconds }}s</span>
                </div>
                <div class="mt-6 flex flex-col gap-3 bg-transparent">
                  <div class="flex items-center gap-2 text-[12px] font-semibold text-[var(--sys-text-secondary)] bg-transparent">
                    <span class="material-symbols-outlined text-[20px] text-[var(--sys-brand-solid)]">location_on</span>
                    {{ geoPrimaryLine }}
                  </div>
                  <div class="flex items-center gap-2 text-[11px] font-bold uppercase tracking-wide bg-transparent" :class="geoAccentClass">
                    <span class="w-2 h-2 rounded-full animate-pulse shadow-sm" :class="geoDotClass"></span>
                    {{ geoSecondaryLine }}
                  </div>
                  <div class="rounded-md border px-3 py-2 text-left" :class="geoBannerClass">
                    <p class="m-0 text-[11px] font-bold uppercase tracking-wide">{{ geoHeadline }}</p>
                    <p class="m-0 mt-1 text-[12px] font-medium leading-5">{{ geoBody }}</p>
                  </div>
                  <div v-if="geoNeedsAttention" class="flex flex-wrap gap-2 bg-transparent">
                    <button
                      type="button"
                      class="h-9 px-4 rounded-md bg-[var(--sys-brand-solid)] text-white text-[11px] font-bold uppercase tracking-wide hover:brightness-95 transition-all"
                      @click="handleGeoAction"
                    >
                      {{ geoActionLabel }}
                    </button>
                  </div>
                  <div class="rounded-md border border-[var(--sys-border-subtle)] bg-[var(--sys-bg-page)] px-4 py-3 text-left shadow-sm">
                    <div class="flex items-start justify-between gap-3 bg-transparent">
                      <div class="bg-transparent">
                        <p class="m-0 text-[10px] font-bold uppercase tracking-[0.2em] text-[var(--sys-text-secondary)] opacity-60">Ca hôm nay</p>
                        <p class="m-0 mt-1 text-[15px] font-bold text-[var(--sys-text-primary)]">{{ todayShiftCard.title }}</p>
                      </div>
                      <span class="rounded-md border px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide" :class="todayShiftCard.badgeClass">
                        {{ todayShiftCard.badge }}
                      </span>
                    </div>
                    <p class="m-0 mt-3 text-[12px] font-semibold text-[var(--sys-text-primary)]">{{ todayShiftCard.time }}</p>
                    <p class="m-0 mt-1 text-[11px] font-medium leading-5 text-[var(--sys-text-secondary)]">{{ todayShiftCard.meta }}</p>
                  </div>
                </div>
              </div>

              <!-- Action Area -->
              <div class="flex flex-col gap-4 bg-transparent">
                <div class="grid grid-cols-2 gap-4 bg-transparent">
                  <button 
                    @click="handleCheckIn" 
                    class="font-bold py-5 px-4 rounded-md flex flex-col items-center justify-center gap-2 shadow-sm transition-all group bg-[var(--sys-brand-solid)] hover:brightness-90 text-white active:scale-95"
                  >
                    <span class="material-symbols-outlined text-3xl group-hover:scale-105 transition-transform">login</span>
                    <span class="text-[12px] uppercase tracking-wide">Đi làm</span>
                  </button>
                  <button 
                    @click="handleCheckOut" 
                    class="font-bold py-5 px-4 rounded-md flex flex-col items-center justify-center gap-2 shadow-sm transition-all group bg-white hover:bg-[var(--sys-bg-page)] text-[var(--sys-text-primary)] border border-[var(--sys-border-strong)] active:scale-95"
                  >
                    <span class="material-symbols-outlined text-3xl group-hover:scale-105 transition-transform text-[var(--sys-text-secondary)]">logout</span>
                    <span class="text-[12px] uppercase tracking-wide opacity-80">Ra về</span>
                  </button>
                </div>
                <div class="py-3 px-4 bg-[var(--sys-bg-page)] rounded-md border border-[var(--sys-border-subtle)] text-center">
                  <p class="text-[11px] font-bold text-[var(--sys-text-secondary)] uppercase tracking-wide opacity-60">
                    Ghi nhận gần nhất: <span class="text-[var(--sys-text-primary)] font-bold">{{ lastLogTime }}</span>
                  </p>
                </div>
              </div>
            </div>
          </div>

          <!-- Secondary Metric Cards Row -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6 bg-transparent">
            <!-- Leave Remaining -->
            <div
              class="bg-[var(--sys-bg-surface)] rounded-lg shadow-sm border border-[var(--sys-border-subtle)] p-6 flex flex-col justify-between group hover:border-[var(--sys-brand-solid)] transition-all cursor-pointer"
              @click="router.push('/nhanvien/nghiphep')"
            >
              <div class="flex justify-between items-start mb-6 bg-transparent">
                <div class="w-11 h-11 rounded-md bg-[var(--sys-brand-soft)] text-[var(--sys-brand-solid)] flex items-center justify-center border border-[var(--sys-brand-border)] group-hover:bg-[var(--sys-brand-solid)] group-hover:text-white transition-all shadow-sm">
                  <span class="material-symbols-outlined text-[24px]">calendar_month</span>
                </div>
                <span class="text-[10px] font-bold text-[var(--sys-text-disabled)] bg-[var(--sys-bg-page)] px-3 py-1 rounded-md border border-[var(--sys-border-subtle)] uppercase tracking-wide">Kỳ phép 2023/10</span>
              </div>
              <div class="bg-transparent text-left">
                <p class="text-[11px] font-bold text-[var(--sys-text-secondary)] uppercase tracking-wider mb-2 opacity-60">Quỹ phép năm còn lại</p>
                <div class="flex items-baseline gap-2 bg-transparent">
                  <h3 class="text-5xl font-bold text-[var(--sys-text-primary)] tracking-tight">{{ baseLeaveDays }}</h3>
                  <span class="text-[12px] font-bold text-[var(--sys-text-secondary)] uppercase tracking-widest opacity-40">ngày</span>
                </div>
              </div>
              <div class="mt-6 pt-4 border-t border-[var(--sys-border-subtle)] bg-transparent">
                <div class="flex justify-between items-center text-[11px] font-bold text-[var(--sys-text-secondary)] mb-2 uppercase tracking-wide opacity-60">
                  <span>Dùng: 0.0</span>
                  <span>Quỹ: {{ baseLeaveDays }}.0</span>
                </div>
                <div class="h-2 w-full bg-[var(--sys-bg-page)] rounded-full overflow-hidden border border-[var(--sys-border-subtle)] shadow-inner">
                  <div class="h-full bg-[var(--sys-brand-solid)] transition-all" style="width: 33%"></div>
                </div>
              </div>
            </div>

            <!-- Stats Summary -->
            <div class="bg-[var(--sys-bg-surface)] rounded-lg shadow-sm border border-[var(--sys-border-subtle)] p-6 flex flex-col bg-transparent">
              <h3 class="text-[11px] font-bold text-[var(--sys-text-secondary)] uppercase tracking-wider mb-6 opacity-60 text-left">Tóm tắt chuyên cần tháng</h3>
              <div class="grid grid-cols-3 gap-2 flex-grow bg-transparent">
                <div class="flex flex-col items-center justify-center p-3 rounded-md bg-[var(--sys-success-soft)] border border-[var(--sys-success-border)] group hover:brightness-95 transition-all shadow-sm">
                  <span class="material-symbols-outlined text-[var(--sys-success-text)] mb-2 text-[24px]">done_all</span>
                  <span class="text-[9px] font-bold text-[var(--sys-success-text)] uppercase tracking-tighter mb-1 opacity-80">Ngày công</span>
                  <span class="text-xl font-bold text-[var(--sys-text-primary)]">{{ monthlySummary.worked }}</span>
                </div>
                <div class="flex flex-col items-center justify-center p-3 rounded-md bg-[var(--sys-danger-soft)] border border-[var(--sys-danger-border)] group hover:brightness-95 transition-all shadow-sm">
                  <span class="material-symbols-outlined text-[var(--sys-danger-text)] mb-2 text-[24px]">event_busy</span>
                  <span class="text-[9px] font-bold text-[var(--sys-danger-text)] uppercase tracking-tighter mb-1 opacity-80">Vắng mặt</span>
                  <span class="text-xl font-bold text-[var(--sys-text-primary)]">{{ monthlySummary.absent }}</span>
                </div>
                <div class="flex flex-col items-center justify-center p-3 rounded-md bg-[var(--sys-warning-soft)] border border-[var(--sys-warning-border)] group hover:brightness-95 transition-all shadow-sm">
                  <span class="material-symbols-outlined text-[var(--sys-warning-text)] mb-2 text-[24px]">history</span>
                  <span class="text-[9px] font-bold text-[var(--sys-warning-text)] uppercase tracking-tighter mb-1 opacity-80">Đi muộn</span>
                  <span class="text-xl font-bold text-[var(--sys-text-primary)]">{{ monthlySummary.late }}</span>
                </div>
              </div>
              <div class="mt-6 pt-4 flex justify-center bg-transparent border-t border-[var(--sys-border-subtle)]">
                <button type="button" @click.stop="router.push('/nhanvien/chamcong')" class="text-[var(--sys-brand-solid)] text-[11px] font-bold uppercase tracking-wide hover:opacity-80 transition-opacity flex items-center gap-1">
                  Bảng chi tiết <span class="material-symbols-outlined text-[18px]">chevron_right</span>
                </button>
              </div>
            </div>
          </div>

          <div class="bg-[var(--sys-bg-surface)] rounded-lg shadow-sm border border-[var(--sys-border-subtle)] overflow-hidden">
            <div class="px-6 py-4 border-b border-[var(--sys-border-subtle)] flex justify-between items-center bg-[var(--sys-bg-page)]/50">
              <h3 class="text-sm font-semibold text-[var(--sys-text-primary)] uppercase tracking-wide flex items-center gap-2 m-0">
                <span class="material-symbols-outlined text-[var(--sys-brand-solid)] text-[22px]">calendar_month</span>
                Lịch làm việc của tôi
              </h3>
              <button type="button" @click="router.push('/nhanvien/chamcong')" class="text-[var(--sys-brand-solid)] text-[11px] font-bold uppercase tracking-wide hover:opacity-80 transition-opacity flex items-center gap-1">
                Xem chấm công <span class="material-symbols-outlined text-[18px]">chevron_right</span>
              </button>
            </div>

            <div class="p-4 md:p-5 bg-transparent">
              <div v-if="!upcomingScheduleRows.length" class="rounded-md border border-[var(--sys-border-subtle)] bg-[var(--sys-bg-page)] px-4 py-5 text-[12px] font-medium text-[var(--sys-text-secondary)] text-center">
                Chưa có lịch làm việc hiển thị trong 7 ngày tới. Nếu đây là tuần làm việc, bạn nên kiểm tra lại với trưởng phòng hoặc HR.
              </div>

              <div v-else class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-3 bg-transparent">
                <div v-for="item in upcomingScheduleRows" :key="item.workDate" class="rounded-md border border-[var(--sys-border-subtle)] bg-white px-4 py-3 shadow-sm">
                  <div class="flex items-start justify-between gap-3 bg-transparent">
                    <div class="bg-transparent">
                      <p class="m-0 text-[10px] font-bold uppercase tracking-[0.16em] text-[var(--sys-text-secondary)] opacity-60">{{ item.dayLabel }}</p>
                      <p class="m-0 mt-1 text-[13px] font-bold text-[var(--sys-text-primary)]">{{ item.title }}</p>
                    </div>
                    <span class="rounded-md border px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide" :class="item.badgeClass">
                      {{ item.badge }}
                    </span>
                  </div>
                  <p class="m-0 mt-3 text-[12px] font-semibold text-[var(--sys-text-primary)]">{{ item.timeLabel }}</p>
                  <p class="m-0 mt-1 text-[11px] font-medium leading-5 text-[var(--sys-text-secondary)]">{{ item.meta }}</p>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Right Column: Notifications -->
        <div class="lg:col-span-4 flex flex-col bg-transparent">
          <div class="bg-[var(--sys-bg-surface)] rounded-lg shadow-sm border border-[var(--sys-border-subtle)] h-full flex flex-col overflow-hidden">
            <div class="px-5 py-4 border-b border-[var(--sys-border-subtle)] bg-[var(--sys-bg-page)]/50 flex justify-between items-center text-left">
              <h3 class="text-sm font-semibold text-[var(--sys-text-primary)] uppercase tracking-wide flex items-center gap-2 m-0">
                <span class="material-symbols-outlined text-[var(--sys-brand-solid)] text-[22px]">notifications_active</span>
                Thông báo mới
              </h3>
              <span v-if="unreadNotificationsCount > 0" class="bg-[var(--sys-danger-solid)] text-white text-[10px] font-bold px-2 py-0.5 rounded-md shadow-sm">{{ unreadNotificationsCount }}</span>
            </div>
            
            <div class="flex-grow overflow-y-auto custom-scrollbar bg-transparent divide-y divide-[var(--sys-border-subtle)]">
              <button v-for="notif in notifications" :key="notif.id" type="button" class="w-full block px-5 py-4 hover:bg-[var(--sys-bg-hover)] transition-all cursor-pointer group no-underline text-left bg-transparent border-0" @click="handleNotificationClick(notif)">
                <div class="flex gap-4 bg-transparent text-left">
                  <div :class="['w-10 h-10 rounded-md flex items-center justify-center shrink-0 border transition-all', 
                    notif.type === 'success' ? 'bg-[var(--sys-success-soft)] text-[var(--sys-success-text)] border-[var(--sys-success-border)] group-hover:bg-[var(--sys-success-solid)] group-hover:text-white' : 
                    notif.type === 'info' ? 'bg-[var(--sys-info-soft)] text-[var(--sys-info-text)] border-[var(--sys-info-border)] group-hover:bg-[var(--sys-info-solid)] group-hover:text-white' : 
                    'bg-[var(--sys-warning-soft)] text-[var(--sys-warning-text)] border-[var(--sys-warning-border)] group-hover:bg-[var(--sys-warning-solid)] group-hover:text-white']">
                    <span class="material-symbols-outlined text-[20px]">{{ notif.icon }}</span>
                  </div>
                  <div class="bg-transparent text-left">
                    <h4 class="text-[13px] font-semibold text-[var(--sys-text-primary)] mb-1 group-hover:text-[var(--sys-brand-solid)] transition-colors uppercase tracking-tight">{{ notif.title }}</h4>
                    <p class="text-[12px] font-medium text-[var(--sys-text-secondary)] leading-snug mb-2 opacity-80">{{ notif.desc }}</p>
                    <span class="text-[10px] font-bold text-[var(--sys-text-disabled)] uppercase tracking-widest opacity-60">{{ notif.time }}</span>
                  </div>
                </div>
              </button>
              <div v-if="notifications.length === 0" class="px-5 py-10 text-center text-[12px] font-medium text-[var(--sys-text-secondary)]">
                Chưa có thông báo mới cho tài khoản của bạn.
              </div>
            </div>

            <div class="px-5 py-3 bg-[var(--sys-bg-page)]/50 border-t border-[var(--sys-border-subtle)] text-center">
              <router-link :to="{ name: 'thong-bao' }" class="text-[var(--sys-brand-solid)] text-[11px] font-bold uppercase tracking-wider hover:opacity-80 transition-opacity inline-flex items-center gap-1 active:scale-95 no-underline">
                Tất cả thông báo <span class="material-symbols-outlined text-[18px]">chevron_right</span>
              </router-link>
            </div>
          </div>
        </div>
      </div>

      <!-- Recent Activity Table Section -->
      <div class="bg-[var(--sys-bg-surface)] rounded-lg shadow-sm border border-[var(--sys-border-subtle)] overflow-hidden">
        <div class="px-6 py-4 border-b border-[var(--sys-border-subtle)] flex justify-between items-center bg-[var(--sys-bg-page)]/50">
          <h3 class="text-sm font-semibold text-[var(--sys-text-primary)] uppercase tracking-wide flex items-center gap-2 m-0">
            <span class="material-symbols-outlined text-[var(--sys-brand-solid)] text-[22px]">history</span>
            Hoạt động gần đây
          </h3>
          <button @click="router.push('/nhanvien/thongbao')" class="w-8 h-8 flex items-center justify-center rounded-md bg-white border border-[var(--sys-border-strong)] text-[var(--sys-text-secondary)] hover:border-[var(--sys-brand-solid)] transition-all shadow-sm">
            <span class="material-symbols-outlined text-[18px]">tune</span>
          </button>
        </div>
        
        <div class="overflow-x-auto custom-scrollbar">
          <table class="w-full text-left border-collapse">
            <thead class="bg-[var(--sys-bg-page)]">
              <tr>
                <th class="px-6 py-3 text-[12px] font-semibold text-[var(--sys-text-secondary)] uppercase border-b border-[var(--sys-border-subtle)]">Ngày thực hiện</th>
                <th class="px-6 py-3 text-[12px] font-semibold text-[var(--sys-text-secondary)] uppercase border-b border-[var(--sys-border-subtle)]">Loại hoạt động</th>
                <th class="px-6 py-3 text-[12px] font-semibold text-[var(--sys-text-secondary)] uppercase border-b border-[var(--sys-border-subtle)]">Thời gian chi tiết</th>
                <th class="px-6 py-3 text-[12px] font-semibold text-[var(--sys-text-secondary)] uppercase border-b border-[var(--sys-border-subtle)] text-right">Trạng thái</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-[var(--sys-border-subtle)]">
              <tr v-for="(act, idx) in activities" :key="idx" class="hover:bg-[var(--sys-bg-hover)] transition-colors">
                <td class="px-6 py-3 text-[13px] font-semibold text-[var(--sys-text-primary)] whitespace-nowrap bg-transparent">{{ act.date }}</td>
                <td class="px-6 py-3 bg-transparent">
                  <div class="flex items-center gap-2">
                    <span :class="['w-2 h-2 rounded-full shadow-sm', 'bg-[var(--sys-' + act.color + '-solid)]']"></span>
                    <span class="text-[13px] font-semibold text-[var(--sys-text-primary)]">{{ act.type }}</span>
                  </div>
                </td>
                <td class="px-6 py-3 text-[13px] font-medium text-[var(--sys-text-secondary)] whitespace-nowrap bg-transparent">{{ act.time }}</td>
                <td class="px-6 py-3 text-right bg-transparent">
                  <span :class="['px-2.5 py-0.5 rounded-md text-[11px] font-bold border uppercase tracking-wide', 
                    act.status === 'Đã duyệt' ? 'bg-[var(--sys-brand-soft)] text-[var(--sys-brand-solid)] border-[var(--sys-brand-border)]' : 'bg-[var(--sys-success-soft)] text-[var(--sys-success-text)] border-[var(--sys-success-border)]']">
                    {{ act.status }}
                  </span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <div class="px-6 py-3 bg-[var(--sys-bg-page)]/50 border-t border-[var(--sys-border-subtle)] flex justify-end">
          <button type="button" @click="router.push('/nhanvien/chamcong')" class="text-[var(--sys-brand-solid)] text-[11px] font-bold uppercase tracking-wide hover:opacity-80 transition-opacity flex items-center gap-1 active:scale-95 no-underline">
            Toàn bộ lịch sử <span class="material-symbols-outlined text-[18px]">chevron_right</span>
          </button>
        </div>
      </div>

    </div>
  </div>
</template>
<script setup>
import { computed, ref, onMounted, onUnmounted } from 'vue';
import { useRouter } from 'vue-router';
import { useCurrentUser } from '@/composables/useCurrentUser';
import { loadGeoState, requestAttendancePrecheck } from '@/services/attendanceGeo.js';
import { fetchAttendanceResults, fetchMySchedule, fetchMyShiftToday } from '@/services/workforceApi.js';
import { fetchNotifications as fetchNotificationsService, markNotificationRead } from '@/services/notificationsApi.js';

const router = useRouter();
const { employeeId, fullName, baseLeaveDays } = useCurrentUser();

const currentHours = ref('00');
const currentMinutes = ref('00');
const currentSeconds = ref('00');
const currentDateStr = ref('');
let timerInterval = null;

const activities = ref([]);
const notifications = ref([]);
const showToast = ref(false);
const toastMsg = ref('');
const toastType = ref('success');
const lastLogTime = ref(localStorage.getItem('attendance_last_event_label') || '--:-- --/--/----');
const geoState = ref(loadGeoState());
const geoBusy = ref(false);
const lastGeoToastKey = ref('');
const todayShift = ref(null);
const mySchedule = ref([]);
const monthlyResults = ref([]);
let notificationInterval = null;

const geoRiskLevel = computed(() => String(geoState.value?.riskLevel || '').toUpperCase());
const geoReasonCode = computed(() => String(geoState.value?.reasonCode || '').toUpperCase());
const geoPrimaryLine = computed(() => {
  const location = geoState.value?.location;
  if (location?.lat && location?.lng) {
    return `GPS thật: ${Number(location.lat).toFixed(6)}, ${Number(location.lng).toFixed(6)}`;
  }
  return 'Chưa có GPS thật từ thiết bị';
});
const geoSecondaryLine = computed(() => {
  const anchor = String(geoState.value?.companyAnchorLabel || '').trim();
  const distance = Number(geoState.value?.companyAnchorDistanceM);
  if (anchor && Number.isFinite(distance) && distance >= 0) {
    return `${anchor} · cách ${Math.round(distance)}m`;
  }
  if (Number.isFinite(distance) && distance >= 0) {
    return `Khoảng cách đến mốc công ty: ${Math.round(distance)}m`;
  }
  return 'GPS được đọc trực tiếp từ trình duyệt, không dùng nhãn IP tĩnh';
});
const geoHeadline = computed(() => {
  if (geoReasonCode.value === 'INSECURE_CONTEXT') return 'Cần mở bản HTTPS';
  if (geoReasonCode.value === 'PERMISSION_DENIED') return 'Cần bật GPS';
  if (geoRiskLevel.value === 'GREEN') return 'Trong vùng cho phép';
  if (geoRiskLevel.value === 'YELLOW') return 'Gần vùng công ty';
  if (geoRiskLevel.value === 'RED') return 'Ngoài vùng cho phép';
  return 'Đang chờ GPS';
});
const geoBody = computed(() => {
  if (geoState.value?.userMessage) return geoState.value.userMessage;
  return 'Khi vào hệ thống, trình duyệt sẽ xin quyền GPS để kiểm tra vị trí trước khi chấm công.';
});
const geoBannerClass = computed(() => {
  if (geoRiskLevel.value === 'GREEN') return 'bg-[var(--sys-success-soft)] border-[var(--sys-success-border)] text-[var(--sys-success-text)]';
  if (geoRiskLevel.value === 'YELLOW') return 'bg-[var(--sys-warning-soft)] border-[var(--sys-warning-border)] text-[var(--sys-warning-text)]';
  if (geoRiskLevel.value === 'RED' || geoReasonCode.value === 'INSECURE_CONTEXT' || geoReasonCode.value === 'PERMISSION_DENIED') {
    return 'bg-[var(--sys-danger-soft)] border-[var(--sys-danger-border)] text-[var(--sys-danger-text)]';
  }
  return 'bg-[var(--sys-info-soft)] border-[var(--sys-info-border)] text-[var(--sys-info-text)]';
});
const geoAccentClass = computed(() => {
  if (geoRiskLevel.value === 'GREEN') return 'text-[var(--sys-success-text)]';
  if (geoRiskLevel.value === 'YELLOW') return 'text-[var(--sys-warning-text)]';
  if (geoRiskLevel.value === 'RED') return 'text-[var(--sys-danger-text)]';
  return 'text-[var(--sys-text-secondary)]';
});
const geoDotClass = computed(() => {
  if (geoRiskLevel.value === 'GREEN') return 'bg-[var(--sys-success-solid)]';
  if (geoRiskLevel.value === 'YELLOW') return 'bg-[var(--sys-warning-solid)]';
  if (geoRiskLevel.value === 'RED') return 'bg-[var(--sys-danger-solid)]';
  return 'bg-[var(--sys-brand-solid)]';
});
const geoNeedsAttention = computed(() => geoRiskLevel.value !== 'GREEN');
const geoActionLabel = computed(() => {
  if (geoReasonCode.value === 'INSECURE_CONTEXT') return 'Mở bản HTTPS';
  if (geoReasonCode.value === 'PERMISSION_DENIED') return 'Bật GPS';
  return 'Mở chấm công';
});
const todayShiftCard = computed(() => {
  const payload = todayShift.value || {};
  const shift = payload.shift || null;
  const holiday = payload.holiday || null;
  const leave = payload.leave || null;
  const remote = payload.remote || null;
  const businessTrip = payload.business_trip || null;

  if (holiday) {
    return {
      title: holiday.holiday_name || 'Ngày nghỉ hệ thống',
      time: 'Không yêu cầu chấm công theo lịch chuẩn',
      meta: holiday.description || 'Hệ thống đang áp lịch nghỉ lễ cho ngày hôm nay.',
      badge: 'Holiday',
      badgeClass: 'bg-[var(--sys-info-soft)] text-[var(--sys-info-text)] border-[var(--sys-info-border)]',
    };
  }

  if (leave) {
    return {
      title: leave.leave_type_name || 'Nghỉ phép đã duyệt',
      time: 'Ngày làm việc được thay bằng đơn nghỉ',
      meta: 'Bạn đã có đơn nghỉ hợp lệ trong ngày, hệ thống sẽ đối chiếu theo trạng thái này.',
      badge: 'Leave',
      badgeClass: 'bg-[var(--sys-warning-soft)] text-[var(--sys-warning-text)] border-[var(--sys-warning-border)]',
    };
  }

  if (businessTrip) {
    return {
      title: 'Lịch công tác',
      time: shift?.shift_name ? `${shift.shift_name} · ${shift.start_time || '--:--'} - ${shift.end_time || '--:--'}` : 'Theo lịch công tác đã duyệt',
      meta: 'Ngày hôm nay đang được gắn trạng thái công tác. Manager và HR sẽ thấy lịch này trên bảng phòng ban.',
      badge: 'CT',
      badgeClass: 'bg-[var(--sys-brand-soft)] text-[var(--sys-brand-solid)] border-[var(--sys-brand-border)]',
    };
  }

  if (remote) {
    return {
      title: shift?.shift_name || 'Làm việc từ xa',
      time: shift?.start_time ? `${shift.start_time} - ${shift.end_time || '--:--'}` : 'Theo khung giờ remote đã duyệt',
      meta: 'Ngày hôm nay hệ thống đang ghi nhận bạn làm việc từ xa. Vẫn nên check-in đúng giờ nếu policy yêu cầu.',
      badge: 'Remote',
      badgeClass: 'bg-[var(--sys-success-soft)] text-[var(--sys-success-text)] border-[var(--sys-success-border)]',
    };
  }

  if (shift) {
    const sourceMap = {
      override: 'Ca được chỉnh riêng cho hôm nay.',
      assignment: 'Ca mặc định cá nhân đang có hiệu lực.',
      department_schedule: 'Ca lấy từ lịch phòng ban đã phân.',
    };
    return {
      title: shift.shift_name || 'Ca làm việc',
      time: shift.start_time ? `${shift.start_time} - ${shift.end_time || '--:--'}` : 'Đang chờ khung giờ ca',
      meta: sourceMap[shift.source] || 'Hệ thống đang lấy ca theo lịch làm việc hiện hành.',
      badge: 'Shift',
      badgeClass: 'bg-[var(--sys-brand-soft)] text-[var(--sys-brand-solid)] border-[var(--sys-brand-border)]',
    };
  }

  return {
    title: 'Chưa được phân ca',
    time: 'Hôm nay chưa có lịch làm việc chính thức',
    meta: 'Nếu đây là ngày làm việc, bạn nên liên hệ trưởng phòng hoặc HR để cập nhật ca trước khi chấm công.',
    badge: 'Pending',
    badgeClass: 'bg-[var(--sys-danger-soft)] text-[var(--sys-danger-text)] border-[var(--sys-danger-border)]',
  };
});
const monthlySummary = computed(() => {
  const summary = {
    worked: 0,
    absent: 0,
    lateMinutes: 0,
  };

  for (const item of monthlyResults.value) {
    const status = String(item?.primary_status_code || '').toUpperCase();
    if (['P', 'OT', 'NS', 'REMOTE', 'CT', 'H'].includes(status)) {
      summary.worked += 1;
    } else if (['AL', 'SL', 'UNP'].includes(status)) {
      summary.worked += 0.5;
    } else if (status === 'AB') {
      summary.absent += 1;
    }
    summary.lateMinutes += Number(item?.late_minutes || 0);
  }

  return {
    worked: summary.worked.toFixed(1),
    absent: summary.absent.toFixed(1),
    late: summary.lateMinutes > 0 ? `${summary.lateMinutes}p` : '0p',
  };
});
const unreadNotificationsCount = computed(() => notifications.value.filter((item) => !item.isRead).length || 0);

const upcomingScheduleRows = computed(() => {
  const scheduleRows = Array.isArray(mySchedule.value) ? mySchedule.value : [];
  return scheduleRows
    .slice()
    .sort((left, right) => String(left?.work_date || '').localeCompare(String(right?.work_date || '')))
    .slice(0, 7)
    .map((item) => {
      const shift = item?.shift || null;
      const holiday = item?.holiday || null;
      const leave = item?.leave || null;
      const remote = item?.remote || null;
      const businessTrip = item?.business_trip || null;
      const workDate = String(item?.work_date || '');
      const date = new Date(`${workDate}T00:00:00`);
      const dayLabel = Number.isNaN(date.getTime())
        ? workDate
        : date.toLocaleDateString('vi-VN', { weekday: 'long', day: '2-digit', month: '2-digit' });

      if (holiday) {
        return {
          workDate,
          dayLabel,
          title: holiday.holiday_name || 'Ngày nghỉ hệ thống',
          timeLabel: 'Không cần chấm công theo lịch chuẩn',
          meta: holiday.description || 'Hệ thống đang áp dụng lịch nghỉ lễ cho ngày này.',
          badge: 'Holiday',
          badgeClass: 'bg-[var(--sys-info-soft)] text-[var(--sys-info-text)] border-[var(--sys-info-border)]',
        };
      }

      if (leave) {
        return {
          workDate,
          dayLabel,
          title: leave.leave_type_name || 'Nghỉ phép đã duyệt',
          timeLabel: 'Ngày làm việc được thay bằng đơn nghỉ',
          meta: 'Lịch làm việc đã được thay thế bằng trạng thái nghỉ phép hợp lệ.',
          badge: 'Leave',
          badgeClass: 'bg-[var(--sys-warning-soft)] text-[var(--sys-warning-text)] border-[var(--sys-warning-border)]',
        };
      }

      if (businessTrip) {
        return {
          workDate,
          dayLabel,
          title: 'Công tác',
          timeLabel: shift?.start_time ? `${shift.start_time} - ${shift.end_time || '--:--'}` : 'Theo lịch công tác đã duyệt',
          meta: 'Ngày này đang được đánh dấu công tác trên planning layer.',
          badge: 'CT',
          badgeClass: 'bg-[var(--sys-brand-soft)] text-[var(--sys-brand-solid)] border-[var(--sys-brand-border)]',
        };
      }

      if (remote) {
        return {
          workDate,
          dayLabel,
          title: shift?.shift_name || 'Làm việc từ xa',
          timeLabel: shift?.start_time ? `${shift.start_time} - ${shift.end_time || '--:--'}` : 'Theo lịch remote đã duyệt',
          meta: 'Ngày này được ghi nhận làm việc từ xa theo workflow đã phê duyệt.',
          badge: 'Remote',
          badgeClass: 'bg-[var(--sys-success-soft)] text-[var(--sys-success-text)] border-[var(--sys-success-border)]',
        };
      }

      if (shift) {
        const sourceMap = {
          override: 'Ca chỉnh riêng theo ngày đang được ưu tiên áp dụng.',
          assignment: 'Ca mặc định cá nhân đang có hiệu lực.',
          department_schedule: 'Ca lấy từ lịch phòng ban đã publish.',
        };
        return {
          workDate,
          dayLabel,
          title: shift.shift_name || 'Ca làm việc',
          timeLabel: shift.start_time ? `${shift.start_time} - ${shift.end_time || '--:--'}` : 'Đang chờ khung giờ ca',
          meta: sourceMap[shift.source] || 'Hệ thống đang đọc ca từ lịch làm việc hiện hành.',
          badge: 'Shift',
          badgeClass: 'bg-[var(--sys-brand-soft)] text-[var(--sys-brand-solid)] border-[var(--sys-brand-border)]',
        };
      }

      return {
        workDate,
        dayLabel,
        title: 'Chưa phân ca',
        timeLabel: 'Không có lịch làm việc chính thức',
        meta: 'Bạn nên liên hệ trưởng phòng hoặc HR để bổ sung lịch nếu đây là ngày làm việc.',
        badge: 'Pending',
        badgeClass: 'bg-[var(--sys-danger-soft)] text-[var(--sys-danger-text)] border-[var(--sys-danger-border)]',
      };
    });
});

const triggerToast = (msg, type = 'success') => {
  toastMsg.value = msg;
  toastType.value = type;
  showToast.value = true;
  setTimeout(() => { showToast.value = false; }, 3000);
};

const handleCheckIn = () => {
  router.push('/nhanvien/chamcong');
};

const handleCheckOut = () => {
  router.push('/nhanvien/chamcong');
};

const handleGeoAction = () => {
  if (geoReasonCode.value === 'INSECURE_CONTEXT') {
    window.location.href = `https://anhsinhvienfpoly.click${window.location.pathname}`;
    return;
  }
  router.push('/nhanvien/chamcong');
};

const refreshGeoState = async () => {
  if (!employeeId.value || geoBusy.value) return;
  geoBusy.value = true;
  try {
    const { state } = await requestAttendancePrecheck({
      employeeId: employeeId.value,
      attendanceType: 'CHECKIN',
    });
    geoState.value = state;
    const distance = Number(state?.companyAnchorDistanceM);
    const distanceLabel = Number.isFinite(distance) && distance >= 0 ? ` · cách ${Math.round(distance)}m` : '';
    const toastKey = `${state?.riskLevel || ''}:${state?.reasonCode || ''}:${Math.round(distance || -1)}`;
    if (toastKey !== lastGeoToastKey.value) {
      lastGeoToastKey.value = toastKey;
      if (state?.riskLevel === 'RED') {
        triggerToast(`${state?.userMessage || 'Bạn đang ngoài khu vực làm việc.'}${distanceLabel}`, 'danger');
      } else if (state?.riskLevel === 'YELLOW') {
        triggerToast(`${state?.userMessage || 'Bạn đang gần khu vực làm việc.'}${distanceLabel}`, 'warning');
      }
    }
  } catch (error) {
    geoState.value = {
      ...loadGeoState(),
      riskLevel: 'RED',
      reasonCode: String(error?.reason || '').toUpperCase(),
      userMessage: error?.message || 'Không lấy được GPS thật từ thiết bị.',
    };
    const toastKey = `ERROR:${geoState.value.reasonCode}:${geoState.value.userMessage}`;
    if (toastKey !== lastGeoToastKey.value) {
      lastGeoToastKey.value = toastKey;
      triggerToast(geoState.value.userMessage, 'danger');
    }
  } finally {
    geoBusy.value = false;
  }
};

const refreshWorkforcePanel = async () => {
  if (!employeeId.value) return;
  const today = new Date();
  const attendanceFromDate = `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}-01`;
  const attendanceToDate = `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}-${String(today.getDate()).padStart(2, '0')}`;
  const scheduleToDate = new Date(today);
  scheduleToDate.setDate(today.getDate() + 6);
  const scheduleFromDate = `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}-${String(today.getDate()).padStart(2, '0')}`;
  const scheduleUntilDate = `${scheduleToDate.getFullYear()}-${String(scheduleToDate.getMonth() + 1).padStart(2, '0')}-${String(scheduleToDate.getDate()).padStart(2, '0')}`;

  try {
    const [todayPayload, schedulePayload, attendancePayload] = await Promise.all([
      fetchMyShiftToday(),
      fetchMySchedule({ fromDate: scheduleFromDate, toDate: scheduleUntilDate }),
      fetchAttendanceResults({ fromDate: attendanceFromDate, toDate: attendanceToDate, perPage: 120 }),
    ]);

    todayShift.value = todayPayload;
    mySchedule.value = Array.isArray(schedulePayload) ? schedulePayload : [];
    monthlyResults.value = Array.isArray(attendancePayload) ? attendancePayload.filter((item) => Number(item?.employee_id) === Number(employeeId.value)) : [];
  } catch (error) {
    console.warn('Không tải được dữ liệu workforce cho dashboard nhân viên:', error);
  }
};

const refreshNotifications = async () => {
  try {
    notifications.value = await fetchNotificationsService({ perPage: 6 });
  } catch (error) {
    console.warn('Không tải được thông báo dashboard nhân viên:', error);
  }
};

const handleNotificationClick = async (notification) => {
  try {
    if (!notification?.isRead) {
      await markNotificationRead(notification.id);
      await refreshNotifications();
    }
  } catch (error) {
    console.warn('Không cập nhật được trạng thái thông báo:', error);
  } finally {
    router.push({ name: 'thong-bao' });
  }
};

const updateTime = () => {
  const now = new Date();
  currentHours.value = String(now.getHours()).padStart(2, '0');
  currentMinutes.value = String(now.getMinutes()).padStart(2, '0');
  currentSeconds.value = String(now.getSeconds()).padStart(2, '0');
  
  const days = ['Chủ Nhật', 'Thứ Hai', 'Thứ Ba', 'Thứ Tư', 'Thứ Năm', 'Thứ Sáu', 'Thứ Bảy'];
  currentDateStr.value = `Hôm nay là ${days[now.getDay()]}, ${now.getDate()}/${now.getMonth() + 1}/${now.getFullYear()}.`;
};

onMounted(() => {
  updateTime();
  timerInterval = setInterval(updateTime, 1000);
  void refreshGeoState();
  void refreshWorkforcePanel();
  void refreshNotifications();
  notificationInterval = setInterval(() => {
    if (typeof document !== 'undefined' && document.hidden) return;
    void refreshNotifications();
  }, 45000);
});

onUnmounted(() => {
  if (timerInterval) clearInterval(timerInterval);
  if (notificationInterval) clearInterval(notificationInterval);
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
.toast-enter-active, .toast-leave-active {
  transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}
.toast-enter-from, .toast-leave-to {
  opacity: 0;
  transform: translate(-50%, -20px) scale(0.9);
}
</style>
