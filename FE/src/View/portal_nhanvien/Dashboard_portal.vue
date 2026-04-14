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
                    Văn phòng TP. Hồ Chí Minh
                  </div>
                  <div class="flex items-center gap-2 text-[11px] font-bold text-[var(--sys-success-text)] uppercase tracking-wide bg-transparent">
                    <span class="w-2 h-2 rounded-full bg-[var(--sys-success-solid)] animate-pulse shadow-sm"></span>
                    IP: 113.161.x.x (Hợp lệ)
                  </div>
                </div>
              </div>

              <!-- Action Area -->
              <div class="flex flex-col gap-4 bg-transparent">
                <div class="grid grid-cols-2 gap-4 bg-transparent">
                  <button 
                    @click="handleCheckIn" 
                    :disabled="attendanceToday?.checkOut2"
                    :class="[
                      'font-bold py-5 px-4 rounded-md flex flex-col items-center justify-center gap-2 shadow-sm transition-all group',
                      attendanceToday?.checkOut2 
                        ? 'bg-gray-200 text-gray-400 cursor-not-allowed opacity-60' 
                        : 'bg-[var(--sys-brand-solid)] hover:brightness-90 text-white active:scale-95'
                    ]"
                  >
                    <span class="material-symbols-outlined text-3xl group-hover:scale-105 transition-transform">login</span>
                    <span class="text-[12px] uppercase tracking-wide">Vào {{ !attendanceToday ? 'lần 1' : (!attendanceToday.checkIn2 ? 'lần 2' : '') }}</span>
                  </button>
                  <button 
                    @click="handleCheckOut" 
                    :disabled="!attendanceToday?.checkIn2 || attendanceToday?.checkOut2"
                    :class="[
                      'font-bold py-5 px-4 rounded-md flex flex-col items-center justify-center gap-2 shadow-sm transition-all group',
                      (!attendanceToday?.checkIn2 || attendanceToday?.checkOut2)
                        ? 'bg-gray-50 text-gray-400 cursor-not-allowed border-gray-100'
                        : 'bg-white hover:bg-[var(--sys-bg-page)] text-[var(--sys-text-primary)] border border-[var(--sys-border-strong)] active:scale-95'
                    ]"
                  >
                    <span class="material-symbols-outlined text-3xl group-hover:scale-105 transition-transform" :class="(!attendanceToday?.checkIn2 || attendanceToday?.checkOut2) ? 'text-gray-300' : 'text-[var(--sys-text-secondary)]'">logout</span>
                    <span class="text-[12px] uppercase tracking-wide opacity-80">Ra {{ attendanceToday?.checkIn2 && !attendanceToday?.checkOut1 ? 'lần 1' : (attendanceToday?.checkOut1 ? 'lần 2' : '') }}</span>
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
                  <span class="text-xl font-bold text-[var(--sys-text-primary)]">18.5</span>
                </div>
                <div class="flex flex-col items-center justify-center p-3 rounded-md bg-[var(--sys-danger-soft)] border border-[var(--sys-danger-border)] group hover:brightness-95 transition-all shadow-sm">
                  <span class="material-symbols-outlined text-[var(--sys-danger-text)] mb-2 text-[24px]">event_busy</span>
                  <span class="text-[9px] font-bold text-[var(--sys-danger-text)] uppercase tracking-tighter mb-1 opacity-80">Vắng mặt</span>
                  <span class="text-xl font-bold text-[var(--sys-text-primary)]">0.0</span>
                </div>
                <div class="flex flex-col items-center justify-center p-3 rounded-md bg-[var(--sys-warning-soft)] border border-[var(--sys-warning-border)] group hover:brightness-95 transition-all shadow-sm">
                  <span class="material-symbols-outlined text-[var(--sys-warning-text)] mb-2 text-[24px]">history</span>
                  <span class="text-[9px] font-bold text-[var(--sys-warning-text)] uppercase tracking-tighter mb-1 opacity-80">Đi muộn</span>
                  <span class="text-xl font-bold text-[var(--sys-text-primary)]">15p</span>
                </div>
              </div>
              <div class="mt-6 pt-4 flex justify-center bg-transparent border-t border-[var(--sys-border-subtle)]">
                <button type="button" @click.stop="router.push('/nhanvien/chamcong')" class="text-[var(--sys-brand-solid)] text-[11px] font-bold uppercase tracking-wide hover:opacity-80 transition-opacity flex items-center gap-1">
                  Bảng chi tiết <span class="material-symbols-outlined text-[18px]">chevron_right</span>
                </button>
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
              <span class="bg-[var(--sys-danger-solid)] text-white text-[10px] font-bold px-2 py-0.5 rounded-md shadow-sm">3</span>
            </div>
            
            <div class="flex-grow overflow-y-auto custom-scrollbar bg-transparent divide-y divide-[var(--sys-border-subtle)]">
              <router-link v-for="notif in notifications" :key="notif.id" :to="{ name: 'thong-bao' }" class="block px-5 py-4 hover:bg-[var(--sys-bg-hover)] transition-all cursor-pointer group no-underline text-left">
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
              </router-link>
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
import { ref, onMounted, onUnmounted, computed } from 'vue';
import { useRouter } from 'vue-router';
import { useCurrentUser } from '@/composables/useCurrentUser';

const router = useRouter();
const { fullName, baseLeaveDays, employeeId: currentEmpId } = useCurrentUser();
const userId = computed(() => currentEmpId.value);

const currentHours = ref('00');
const currentMinutes = ref('00');
const currentSeconds = ref('00');
const currentDateStr = ref('');
let timerInterval = null;

const activities = ref([]);
const notifications = ref([]);
const attendanceToday = ref(null);
const showToast = ref(false);
const toastMsg = ref('');
const toastType = ref('success');

const lastLogTime = computed(() => {
  if (!attendanceToday.value) return '--:--:--';
  return attendanceToday.value.checkOut2 || attendanceToday.value.checkOut1 || attendanceToday.value.checkIn2 || attendanceToday.value.checkIn1 || '--:--:--';
});

const triggerToast = (msg, type = 'success') => {
  toastMsg.value = msg;
  toastType.value = type;
  showToast.value = true;
  setTimeout(() => { showToast.value = false; }, 3000);
};

const notifyManager = async (msg) => {
  try {
    const userRes = await fetch(`http://localhost:3000/employees/${userId.value}`);
    const user = await userRes.json();
    if (user && user.managerId) {
      await fetch('http://localhost:3000/notifications', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          userId: user.managerId,
          type: 'info',
          title: 'Thông báo Chấm công',
          desc: `${user.name} ${msg}`,
          time: 'Vừa xong',
          isRead: false,
          icon: 'history'
        })
      });
    }
  } catch (e) { console.error('Notify Error:', e); }
};

const fetchData = async () => {
  try {
    const res = await fetch(`http://localhost:3000/attendances?employeeId=${userId.value}`);
    const data = await res.json();
    const todayStr = new Date().toISOString().split('T')[0];
    attendanceToday.value = data.find(item => item.date === todayStr);

    activities.value = data.slice(0, 4).map(item => ({
      date: item.date,
      type: 'Chấm công',
      time: `${item.checkIn1}${item.checkIn2 ? ' | ' + item.checkIn2 : ''} Vào - ${item.checkOut1 || '--'}${item.checkOut2 ? ' | ' + item.checkOut2 : ''} Ra`,
      color: 'success',
      status: item.status === 'ontime' ? 'Hợp lệ' : 'Hợp lệ'
    }));

    notifications.value = [
        { id: 1, type: 'warning', icon: 'campaign', title: 'Thông báo Nội bộ', desc: 'Cuộc họp toàn công ty vào Thứ 6 lúc 15:00.', time: '10 phút trước' },
        { id: 2, type: 'success', icon: 'done_all', title: 'Nghỉ phép', desc: 'Đơn nghỉ phép ngày 15/10 đã được duyệt.', time: '2 giờ trước' },
        { id: 3, type: 'success', icon: 'payments', title: 'Lương & Thưởng', desc: 'Bảng lương tháng 9 đã được cập nhật.', time: '1 ngày trước' }
    ];
  } catch (error) {
    console.error('Lỗi khi tải dữ liệu nhân viên:', error);
  }
};

const handleCheckIn = async () => {
  const now = new Date();
  const timeStr = now.toTimeString().split(' ')[0];
  const dateStr = now.toISOString().split('T')[0];

  // Nếu đã chấm ra lần 2 rồi thì không cho làm gì nữa
  if (attendanceToday.value?.checkOut2) {
    triggerToast('Bạn đã hoàn tất tất cả lượt chấm công cho hôm nay (Check-out lần 2).', 'warning');
    return;
  }

  if (!attendanceToday.value) {
    const newEntry = {
      employeeId: userId.value,
      date: dateStr,
      checkIn1: timeStr,
      checkIn2: null,
      checkOut1: null,
      checkOut2: null,
      status: 'ontime',
      location: 'Văn phòng HCM'
    };
    await fetch('http://localhost:3000/attendances', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(newEntry)
    });
    triggerToast('Khởi tạo chấm công thành công!');
    notifyManager(`đã chấm công vào lúc ${timeStr}`);
  } else if (!attendanceToday.value.checkIn2) {
    await fetch(`http://localhost:3000/attendances/${attendanceToday.value.id}`, {
      method: 'PATCH',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ checkIn2: timeStr })
    });
    triggerToast('Ghi nhận vào lần 2 thành công!');
    notifyManager(`đã chấm công vào (lần 2) lúc ${timeStr}`);
  } else {
    triggerToast('Bạn đã ghi nhận vào đủ 2 lần cho hôm nay.', 'warning');
    return;
  }
  fetchData();
};

const handleCheckOut = async () => {
  const now = new Date();
  const timeStr = now.toTimeString().split(' ')[0];

  if (!attendanceToday.value || !attendanceToday.value.checkIn1) {
    triggerToast('Bạn chưa ghi nhận vào lần 1 hôm nay!', 'danger');
    return;
  }

  // Ràng buộc: Chỉ cho chấm ra nếu đã chấm vào đủ 2 lần
  if (!attendanceToday.value.checkIn2) {
    triggerToast('Bạn phải hoàn tất ghi nhận VÀO lần 2 trước khi ghi nhận RA.', 'warning');
    return;
  }

  // Nếu đã chấm ra lần 2 rồi thì không cho làm gì nữa
  if (attendanceToday.value.checkOut2) {
    triggerToast('Bạn đã hoàn tất tất cả lượt chấm công cho hôm nay.', 'warning');
    return;
  }

  if (!attendanceToday.value.checkOut1) {
    await fetch(`http://localhost:3000/attendances/${attendanceToday.value.id}`, {
      method: 'PATCH',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ checkOut1: timeStr })
    });
    triggerToast('Ghi nhận ra lần 1 thành công!');
    notifyManager(`đã chấm công ra lúc ${timeStr}`);
  } else {
    await fetch(`http://localhost:3000/attendances/${attendanceToday.value.id}`, {
      method: 'PATCH',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ checkOut2: timeStr })
    });
    triggerToast('Ghi nhận ra lần 2 (Kết thúc ngày) thành công!');
    notifyManager(`đã chấm công ra (lần 2) lúc ${timeStr}`);
  }
  fetchData();
};

const updateTime = () => {
  const now = new Date();
  currentHours.value = String(now.getHours()).padStart(2, '0');
  currentMinutes.value = String(now.getMinutes()).padStart(2, '0');
  currentSeconds.value = String(now.getSeconds()).padStart(2, '0');
  
  const days = ['Chủ Nhật', 'Thứ Hai', 'Thứ Ba', 'Thứ Tư', 'Thứ Năm', 'Thứ Sáu', 'Thứ Bảy'];
  currentDateStr.value = `Hôm nay là ${days[now.getDay()]}, ${now.getDate()}/${now.getMonth() + 1}/${now.getFullYear()}.`;
};

let pollInterval = null;

onMounted(() => {
  updateTime();
  timerInterval = setInterval(updateTime, 1000);
  fetchData();
  pollInterval = setInterval(fetchData, 10000); // Live sync data every 10s
});

onUnmounted(() => {
  if (timerInterval) clearInterval(timerInterval);
  if (pollInterval) clearInterval(pollInterval);
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
