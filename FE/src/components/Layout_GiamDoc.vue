<template>
  <div class="min-h-screen font-[Roboto,Inter,sans-serif] bg-slate-50 dark:bg-[#0d1117] text-slate-800 dark:text-slate-200 transition-colors duration-300">
    <!-- Topbar -->
    <header class="h-16 bg-[#161c2d] flex items-center justify-between px-6 shadow-md fixed top-0 left-0 right-0 z-50">

      <!-- Logo Left -->
      <router-link to="/giamdoc" class="flex items-center gap-2.5 ml-1 cursor-pointer hover:opacity-90 transition-opacity">
        <div
          class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0 shadow-md"
          style="background:linear-gradient(135deg,oklch(0.52 0.22 265),oklch(0.45 0.19 295));box-shadow:0 2px 8px oklch(0.48 0.195 265 / 0.35)"
        >
          <span class="material-symbols-rounded text-white" style="font-size:24px;font-variation-settings:'FILL' 1,'wght' 600,'GRAD' 0,'opsz' 20">corporate_fare</span>
        </div>
        <div class="flex flex-col ml-1">
          <span class="text-white font-bold text-lg tracking-wide leading-tight">HRM Portal</span>
          <span class="text-white/70 text-[10px] uppercase tracking-[0.2em] font-semibold leading-none">Portal Cao Cấp</span>
        </div>
      </router-link>

      <!-- Right Actions -->
      <div class="flex items-center gap-5">
        <!-- Search input -->
        <div class="relative hidden mx-4 md:block">
          <span class="material-symbols-rounded absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-[18px]">search</span>
          <input
            type="text"
            placeholder="Tìm kiếm báo cáo, nhân sự..."
            class="pl-10 pr-4 py-2 w-72 rounded-full bg-[#1e2536] text-sm text-white placeholder-gray-400 border-none focus:outline-none focus:ring-1 focus:ring-blue-500/50 transition-all"
          />
        </div>

        <!-- ── Dark Mode M3 Switch ── -->
        <div class="mx-2 flex items-center gap-1.5 hidden sm:flex">
          <span
            class="material-symbols-rounded transition-all duration-300"
            style="font-size:18px;font-variation-settings:'FILL' 1,'wght' 400,'GRAD' 0,'opsz' 20"
            :class="isDark
              ? 'text-white/40 opacity-40 scale-90'
              : 'text-yellow-400 opacity-100 scale-100'"
          >light_mode</span>

          <button
            role="switch"
            :aria-checked="isDark"
            @click="isDark = !isDark"
            class="relative w-[48px] h-7 rounded-full transition-all duration-300 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 bg-[#1e2536] border border-white/20 shadow-inner"
            :class="isDark ? 'bg-[#0f172a] ring-2 ring-white/10' : ''"
          >
            <span
              class="absolute top-1/2 -translate-y-1/2 rounded-full flex items-center justify-center shadow-lg transition-all duration-300 ease-[cubic-bezier(0.2,0,0,1)]"
              :class="isDark
                ? 'left-[calc(100%-24px)] w-5 h-5 bg-white border border-blue-500 shadow-[0_0_8px_rgba(59,130,246,0.5)]'
                : 'left-1 w-4 h-4 bg-white/70'"
            >
              <span
                class="material-symbols-rounded transition-all duration-200"
                style="font-size:11px;font-variation-settings:'FILL' 1"
                :class="isDark ? 'text-blue-500' : 'text-[#1e2536]'"
              >{{ isDark ? 'dark_mode' : 'light_mode' }}</span>
            </span>
          </button>

          <span
            class="material-symbols-rounded transition-all duration-300"
            style="font-size:18px;font-variation-settings:'FILL' 1,'wght' 400,'GRAD' 0,'opsz' 20"
            :class="isDark
              ? 'text-blue-500 opacity-100 scale-100'
              : 'text-white/40 opacity-40 scale-90'"
          >dark_mode</span>
        </div>

        <!-- ── Notification Bell with Popup ── -->
        <div class="notif-trigger" @mouseenter="showPopup = true" @mouseleave="startHideTimer">
          <!-- Bell Button -->
          <button
            class="w-9 h-9 flex items-center justify-center rounded-full bg-[#1e2536] text-white/80 relative transition-all duration-200"
            :class="showPopup ? 'bg-[#283144] text-white ring-2 ring-blue-500/40' : 'hover:bg-[#283144] hover:text-white'"
            @click="goToNotifications"
            title="Trung tâm Thông báo & Phê duyệt"
          >
            <span
              class="material-symbols-rounded text-[20px] transition-all duration-200"
              :style="showPopup ? 'font-variation-settings:\'FILL\' 1' : ''"
            >notifications</span>
            <!-- Unread dot -->
            <span
              v-if="approvalCount > 0 || recentNotifications.some(n => !n.isRead)"
              class="absolute top-1.5 right-2 w-2.5 h-2.5 rounded-full bg-orange-500 ring-2 ring-[#161c2d] flex items-center justify-center"
            >
              <span class="notif-pulse"></span>
            </span>
          </button>

          <!-- Popup Dropdown -->
          <Transition name="notif-drop">
            <div
              v-if="showPopup"
              class="notif-popup"
              @mouseenter="cancelHideTimer"
              @mouseleave="startHideTimer"
            >
              <!-- Arrow -->
              <div class="notif-arrow"></div>

              <!-- Header -->
              <div class="notif-popup-header">
                <div class="notif-popup-title-row">
                  <span class="material-symbols-rounded notif-popup-icon" style="font-variation-settings:'FILL' 1">notifications_active</span>
                  <span class="notif-popup-title">Thông báo</span>
                </div>
                <span class="notif-popup-badge">{{ recentNotifications.length }} mới</span>
              </div>

              <!-- List of 3 latest notifications -->
              <div class="notif-popup-list">
                <div
                  v-for="(item, idx) in recentNotifications"
                  :key="item.id"
                  class="notif-popup-item"
                  :style="{ animationDelay: (idx * 50) + 'ms' }"
                  @click="handleNotifClick(item)"
                >
                  <div class="notif-popup-dot" :class="item.dotColor"></div>
                  <div class="notif-popup-content">
                    <div class="notif-popup-level-row">
                      <span class="notif-popup-level" :class="[item.levelBg, item.levelColor]">{{ item.levelLabel }}</span>
                      <span class="notif-popup-time">{{ item.time }}</span>
                    </div>
                    <p class="notif-popup-text">{{ item.title }}</p>
                    <p class="notif-popup-desc">{{ item.desc }}</p>
                  </div>
                </div>
              </div>

              <!-- Approval Requests Section -->
              <div class="notif-popup-approval-header">
                <span class="material-symbols-rounded" style="font-size:14px;font-variation-settings:'FILL' 1;color:#f59e0b">approval</span>
                <span>Chờ phê duyệt</span>
                <span class="notif-popup-approval-count">{{ approvalCount }} yêu cầu</span>
              </div>
              <div class="notif-popup-approval-mini">
                <div
                  v-for="req in recentApprovals"
                  :key="req.id"
                  class="notif-popup-approval-item"
                  @click="goToNotifications"
                >
                  <div class="notif-popup-avatar" :class="[req.avatarBg, req.avatarColor]">{{ req.initials }}</div>
                  <div class="notif-popup-approval-info">
                    <span class="notif-popup-approval-name">{{ req.name }}</span>
                    <span class="notif-popup-approval-title">{{ req.title }}</span>
                  </div>
                  <span v-if="req.urgent" class="notif-popup-urgent">Khẩn</span>
                </div>
              </div>

              <!-- Footer -->
              <div class="notif-popup-footer">
                <button class="notif-popup-view-all" @click="goToNotifications">
                  <span class="material-symbols-rounded" style="font-size:15px;font-variation-settings:'FILL' 1">open_in_new</span>
                  Xem tất cả thông báo & yêu cầu
                </button>
              </div>
            </div>
          </Transition>
        </div>

        <div class="w-px h-8 bg-white/10 hidden md:block"></div>

        <!-- User Profile -->
        <div class="flex items-center gap-3 cursor-pointer hover:bg-white/5 p-1.5 -mr-1.5 rounded-xl transition-colors" @click="router.push('/giamdoc/hoso')">
          <div class="flex flex-col text-right hidden sm:flex">
            <span class="text-white font-semibold text-sm leading-tight">Nguyễn Minh Triết</span>
            <span class="text-white/60 text-xs font-medium mt-0.5">Tổng Giám Đốc</span>
          </div>
          <div class="w-9 h-9 flex items-center justify-center rounded-full bg-blue-500/20 border border-blue-500/30 overflow-hidden shrink-0 relative">
            <span class="text-blue-400 font-bold text-sm">T</span>
          </div>
        </div>
      </div>
    </header>

    <!-- Main Content -->
    <main class="pt-16 min-h-screen overflow-hidden px-6 lg:px-8">
      <router-view v-slot="{ Component }">
        <transition :name="transitionName" mode="out-in">
          <component :is="Component" />
        </transition>
      </router-view>
    </main>

  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import {
  importantNotifications as staticNotifs
} from '@/mock-data/sampleData_GiamDoc.js';
import { mockLeaveRequests, mockEmployees, mockRequestTypes } from '@/mock-data/index.js';
import { getInitials, getAvatarColors } from '@/utils/uiMapper.js';
import { useCurrentUser } from '@/composables/useCurrentUser';
import { getCurrentUserRole } from '@/services/session.js';

const { employeeId } = useCurrentUser();

const route  = useRoute();
const router = useRouter();
const isDark = ref(false);

// ── Notification Popup ──────────────────────────────────────────────────────
const showPopup = ref(false);
let hideTimer = null;

const startHideTimer = () => {
  hideTimer = setTimeout(() => {
    showPopup.value = false;
  }, 150);
};

const cancelHideTimer = () => {
  clearTimeout(hideTimer);
};

const goToNotifications = () => {
  showPopup.value = false;
  router.push('/giamdoc/thongbao');
};

const liveNotifications = ref([]);
const liveLeaveRequests = ref([]);

const fetchNotifications = async () => {
  try {
    const [resNotif, resReq] = await Promise.all([
      fetch(`http://localhost:3000/notifications?userId=${employeeId.value}&_sort=id&_order=desc&_limit=20`),
      fetch(`http://localhost:3000/leaveRequests`)
    ]);

    if (resNotif.ok) liveNotifications.value = await resNotif.json();
    if (resReq.ok) liveLeaveRequests.value = await resReq.json();

  } catch (error) {
    console.error('Lỗi khi tải thông báo hoặc yêu cầu:', error);
  }
};

onMounted(() => {
  fetchNotifications();
  const interval = setInterval(fetchNotifications, 10000); // Polling every 10s
  onUnmounted(() => clearInterval(interval));
});

const handleNotifClick = async (item) => {
  showPopup.value = false;
  // Mark as read (optional, depends on backend capability)
  if (!item.isRead) {
    try {
      await fetch(`http://localhost:3000/notifications/${item.id}`, {
        method: 'PATCH',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ isRead: true })
      });
      fetchNotifications();
    } catch (e) {}
  }
  
  if (item.actionRoute) {
    router.push(item.actionRoute);
  } else if (item.type === 'warning' || item.desc.includes('cần bạn phê duyệt')) {
    router.push('/giamdoc/thongbao');
  }
};

// Lấy yêu cầu phê duyệt REAL từ API (chỉ CHỜ_GIÁM_ĐỐC_DUYỆT)
const realApprovals = computed(() => {
  const allReqs = liveLeaveRequests.value.length > 0 ? liveLeaveRequests.value : mockLeaveRequests;
  return allReqs.filter(r => {
    return r.status === 'CHỜ_GIÁM_ĐỐC_DUYỆT';
  }).map(r => {
    const emp = mockEmployees.getById(r.requesterId) || {};
    const type = mockRequestTypes.getById(r.requestTypeId) || {};
    const avatarUI = getAvatarColors(emp.employeeId || 1);
    
    return {
      id: r.id || r.requestId,
      name: emp.fullName || 'Nhân viên',
      title: r.title || type.requestTypeName || 'Yêu cầu',
      initials: getInitials(emp.fullName || '?'),
      avatarBg: avatarUI.bg,
      avatarColor: avatarUI.text,
      urgent: !!r.is_urgent
    };
  });
});

const recentNotifications = computed(() => {
  if (liveNotifications.value.length > 0) {
    return liveNotifications.value.slice(0, 5).map(n => ({
      id: n.id,
      level: n.type === 'warning' ? 'canh_bao' : (n.type === 'info' ? 'thong_tin' : 'thanh_cong'),
      levelLabel: n.type === 'warning' ? 'CẦN DUYỆT' : (n.type === 'info' ? 'THÔNG TIN' : 'HOÀN TẤT'),
      levelColor: n.type === 'warning' ? 'text-orange-700' : 'text-blue-700',
      levelBg: n.type === 'warning' ? 'bg-orange-50' : 'bg-blue-50',
      dotColor: n.type === 'warning' ? 'bg-orange-500' : 'bg-blue-500',
      title: n.title,
      desc: n.desc,
      time: n.time || 'Vừa xong',
      actionRoute: n.actionRoute,
      isRead: n.isRead
    }));
  }
  
  const dynamicNotifs = realApprovals.value.filter(r => r.urgent).slice(0, 2).map(r => ({
    id: `urgent-${r.id}`,
    level: 'canh_bao',
    levelLabel: 'KHẨN CẤP',
    levelColor: 'text-orange-700',
    levelBg: 'bg-orange-50',
    dotColor: 'bg-orange-500',
    icon: 'warning',
    iconColor: 'text-orange-500',
    title: `Yêu cầu phê duyệt khẩn: ${r.name}`,
    desc: `${r.title} cần được xử lý ngay trong ngày.`,
    actionRoute: '/giamdoc/thongbao',
    time: 'Vừa xong',
    isRead: false
  }));
  return [...dynamicNotifs, ...staticNotifs.slice(0, 2)];
});

const recentApprovals = computed(() => {
  return realApprovals.value.slice(0, 3);
});

const approvalCount = computed(() => realApprovals.value.length);

// ── Transition Logic ────────────────────────────────────────────────────────
const transitionName = ref('fade');
watch(() => route.meta.index, (toIndex, fromIndex) => {
  if (toIndex === undefined || fromIndex === undefined) {
    transitionName.value = 'fade';
    return;
  }
  transitionName.value = toIndex > fromIndex ? 'slide-up' : 'slide-down';
});

watch(isDark, (val) => {
  document.documentElement.classList.toggle('dark', val);
}, { immediate: true });

// ── Authentication Check ───────────────────────────────────────────────────
onMounted(() => {
  const userRole = getCurrentUserRole();
  if (!userRole || userRole !== 'director') {
    router.push('/login');
  }
});
</script>

<style scoped>
/* ════════════════════════════════════════
   Page Transitions
/* ════════════════════════════════════════ */
.slide-up-enter-active,
.slide-up-leave-active,
.slide-down-enter-active,
.slide-down-leave-active,
.fade-enter-active,
.fade-leave-active {
  transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
}

.slide-up-enter-from  { opacity: 0; transform: translateY(20px); }
.slide-up-leave-to    { opacity: 0; transform: translateY(-20px); }
.slide-down-enter-from{ opacity: 0; transform: translateY(-20px); }
.slide-down-leave-to  { opacity: 0; transform: translateY(20px); }
.fade-enter-from,
.fade-leave-to        { opacity: 0; }

/* ════════════════════════════════════════
   Notification Bell
/* ════════════════════════════════════════ */
.notif-trigger {
  position: relative;
  display: flex;
  align-items: center;
}

/* Pulse animation on unread dot */
.notif-pulse {
  position: absolute;
  inset: 0;
  border-radius: 50%;
  background: #f97316;
  animation: pulse-ring 1.8s ease-out infinite;
}

@keyframes pulse-ring {
  0%   { opacity: 0.7; transform: scale(1); }
  70%  { opacity: 0;   transform: scale(2.2); }
  100% { opacity: 0;   transform: scale(2.2); }
}

/* ════════════════════════════════════════
   Popup Dropdown
/* ════════════════════════════════════════ */
.notif-popup {
  position: absolute;
  top: calc(100% + 14px);
  right: -8px;
  width: 360px;
  background: #1a2235;
  border: 1px solid rgba(255,255,255,0.1);
  border-radius: 18px;
  box-shadow:
    0 20px 60px rgba(0,0,0,0.5),
    0 0 0 1px rgba(255,255,255,0.05),
    inset 0 1px 0 rgba(255,255,255,0.08);
  overflow: hidden;
  z-index: 200;
}

/* Arrow caret pointing up */
.notif-arrow {
  position: absolute;
  top: -7px;
  right: 20px;
  width: 14px;
  height: 14px;
  background: #1a2235;
  border-top: 1px solid rgba(255,255,255,0.1);
  border-left: 1px solid rgba(255,255,255,0.1);
  transform: rotate(45deg);
  border-radius: 3px 0 0 0;
}

/* ── Popup Header ── */
.notif-popup-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 16px 16px 12px;
  border-bottom: 1px solid rgba(255,255,255,0.07);
}

.notif-popup-title-row {
  display: flex;
  align-items: center;
  gap: 7px;
}

.notif-popup-icon {
  font-size: 18px;
  color: #60a5fa;
  background: rgba(96,165,250,0.15);
  width: 32px;
  height: 32px;
  border-radius: 9px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.notif-popup-title {
  font-size: 14px;
  font-weight: 800;
  color: #ffffff;
  letter-spacing: -0.02em;
}

.notif-popup-badge {
  font-size: 10.5px;
  font-weight: 800;
  padding: 3px 10px;
  border-radius: 99px;
  background: rgba(239,68,68,0.2);
  color: #fca5a5;
  border: 1px solid rgba(239,68,68,0.3);
  letter-spacing: 0.02em;
}

/* ── Notification List ── */
.notif-popup-list {
  padding: 8px 8px 4px;
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.notif-popup-item {
  display: flex;
  align-items: flex-start;
  gap: 10px;
  padding: 10px 10px;
  border-radius: 12px;
  cursor: pointer;
  transition: background 0.15s;
  animation: popItemIn 0.2s ease both;
}

.notif-popup-item:hover {
  background: rgba(255,255,255,0.06);
}

@keyframes popItemIn {
  from { opacity: 0; transform: translateY(-6px); }
  to   { opacity: 1; transform: translateY(0); }
}

.notif-popup-dot {
  width: 9px;
  height: 9px;
  border-radius: 50%;
  flex-shrink: 0;
  margin-top: 5px;
  box-shadow: 0 0 6px currentColor;
}

.notif-popup-content { flex: 1; min-width: 0; }

.notif-popup-level-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 6px;
  margin-bottom: 4px;
}

.notif-popup-level {
  font-size: 9px;
  font-weight: 800;
  padding: 1px 7px;
  border-radius: 99px;
  letter-spacing: 0.07em;
  text-transform: uppercase;
}

/* Override bg/color classes for dark popup context */
.notif-popup-level.bg-blue-50   { background: rgba(59,130,246,0.2) !important; }
.notif-popup-level.text-blue-700{ color: #93c5fd !important; }
.notif-popup-level.bg-orange-50 { background: rgba(249,115,22,0.2) !important; }
.notif-popup-level.text-orange-700 { color: #fdba74 !important; }
.notif-popup-level.bg-green-50  { background: rgba(34,197,94,0.2) !important; }
.notif-popup-level.text-green-700 { color: #86efac !important; }
.notif-popup-level.bg-purple-50 { background: rgba(168,85,247,0.2) !important; }
.notif-popup-level.text-purple-700 { color: #d8b4fe !important; }

.notif-popup-time {
  font-size: 10px;
  color: rgba(255,255,255,0.35);
  font-weight: 500;
  white-space: nowrap;
  flex-shrink: 0;
}

.notif-popup-text {
  font-size: 12.5px;
  font-weight: 600;
  color: rgba(255,255,255,0.88);
  margin: 0 0 2px;
  line-height: 1.45;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.notif-popup-desc {
  font-size: 11.5px;
  color: rgba(255,255,255,0.45);
  margin: 0;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

/* ── Approval Mini Section ── */
.notif-popup-approval-header {
  display: flex;
  align-items: center;
  gap: 5px;
  padding: 10px 18px 6px;
  font-size: 10.5px;
  font-weight: 800;
  color: rgba(255,255,255,0.4);
  text-transform: uppercase;
  letter-spacing: 0.08em;
  border-top: 1px solid rgba(255,255,255,0.07);
}

.notif-popup-approval-count {
  margin-left: auto;
  background: rgba(245,158,11,0.2);
  color: #fcd34d;
  font-size: 10px;
  font-weight: 800;
  padding: 2px 8px;
  border-radius: 99px;
  border: 1px solid rgba(245,158,11,0.3);
}

.notif-popup-approval-mini {
  padding: 4px 8px 8px;
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.notif-popup-approval-item {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 8px 10px;
  border-radius: 10px;
  cursor: pointer;
  transition: background 0.15s;
}
.notif-popup-approval-item:hover { background: rgba(255,255,255,0.05); }

.notif-popup-avatar {
  width: 30px;
  height: 30px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 11px;
  font-weight: 800;
  flex-shrink: 0;
}

/* Avatar bg/color override for dark popup */
.notif-popup-avatar.bg-pink-100  { background: rgba(236,72,153,0.2) !important; }
.notif-popup-avatar.text-pink-600 { color: #f9a8d4 !important; }
.notif-popup-avatar.bg-indigo-100 { background: rgba(99,102,241,0.2) !important; }
.notif-popup-avatar.text-indigo-600 { color: #a5b4fc !important; }
.notif-popup-avatar.bg-green-100  { background: rgba(34,197,94,0.2) !important; }
.notif-popup-avatar.text-green-600 { color: #86efac !important; }
.notif-popup-avatar.bg-amber-100  { background: rgba(245,158,11,0.2) !important; }
.notif-popup-avatar.text-amber-600 { color: #fcd34d !important; }
.notif-popup-avatar.bg-teal-100   { background: rgba(20,184,166,0.2) !important; }
.notif-popup-avatar.text-teal-600  { color: #5eead4 !important; }
.notif-popup-avatar.bg-purple-100 { background: rgba(168,85,247,0.2) !important; }
.notif-popup-avatar.text-purple-600{ color: #d8b4fe !important; }

.notif-popup-approval-info {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 1px;
}

.notif-popup-approval-name {
  font-size: 12px;
  font-weight: 700;
  color: rgba(255,255,255,0.85);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.notif-popup-approval-title {
  font-size: 11px;
  color: rgba(255,255,255,0.4);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.notif-popup-urgent {
  font-size: 9px;
  font-weight: 800;
  padding: 2px 7px;
  border-radius: 99px;
  background: rgba(239,68,68,0.2);
  color: #fca5a5;
  border: 1px solid rgba(239,68,68,0.3);
  flex-shrink: 0;
  letter-spacing: 0.04em;
}

/* ── Footer ── */
.notif-popup-footer {
  padding: 10px 12px 12px;
  border-top: 1px solid rgba(255,255,255,0.07);
}

.notif-popup-view-all {
  width: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 7px;
  padding: 10px 16px;
  border-radius: 11px;
  border: none;
  background: linear-gradient(135deg, #2563eb, #1d4ed8);
  color: #fff;
  font-size: 12.5px;
  font-weight: 800;
  cursor: pointer;
  font-family: inherit;
  letter-spacing: 0.01em;
  transition: all 0.18s;
  box-shadow: 0 4px 14px rgba(37,99,235,0.4);
}
.notif-popup-view-all:hover {
  background: linear-gradient(135deg, #1d4ed8, #1e40af);
  transform: translateY(-1px);
  box-shadow: 0 6px 20px rgba(37,99,235,0.5);
}

/* ── Dropdown Transition ── */
.notif-drop-enter-active {
  transition: all 0.22s cubic-bezier(0.34, 1.56, 0.64, 1);
}
.notif-drop-leave-active {
  transition: all 0.15s ease-in;
}
.notif-drop-enter-from {
  opacity: 0;
  transform: translateY(-10px) scale(0.96);
  transform-origin: top right;
}
.notif-drop-leave-to {
  opacity: 0;
  transform: translateY(-6px) scale(0.97);
  transform-origin: top right;
}
</style>
