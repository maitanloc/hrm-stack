<template>
  <div
    class="min-h-screen font-[Roboto,Inter,sans-serif] transition-colors duration-300 bg-[var(--sys-bg-page)] text-[var(--sys-text-primary)]"
  >

    <!-- ═══════════════════════════════════════════════
         M3 TOP APP BAR
    ═══════════════════════════════════════════════ -->
    <header
      class="fixed top-0 left-0 right-0 z-40 flex items-center h-16 px-2 gap-1 transition-colors duration-300 bg-[var(--sys-bg-surface)] border-b border-[var(--sys-border-subtle)]"
      :class="isDark
        ? 'shadow-[0_1px_3px_oklch(0_0_0/0.4)]'
        : 'shadow-[0_1px_2px_oklch(0_0_0/0.07),0_2px_4px_oklch(0_0_0/0.05)]'"
    >
      <!-- Menu / Hamburger -->
      <button
        @click="handleMenuToggle"
        aria-label="Toggle sidebar"
        class="flex items-center justify-center w-10 h-10 rounded-full transition-colors duration-150 focus-visible:outline-none text-[var(--sys-text-secondary)] hover:bg-[var(--sys-bg-hover)]"
      >
        <span class="material-symbols-rounded" style="font-size:24px;font-variation-settings:'FILL' 0,'wght' 400,'GRAD' 0,'opsz' 24">menu</span>
      </button>

      <!-- Brand -->
      <div class="flex items-center gap-2.5 ml-1">
        <div
          class="w-8 h-8 rounded-xl flex items-center justify-center shrink-0 shadow-md"
          style="background:linear-gradient(135deg,oklch(0.52 0.22 265),oklch(0.45 0.19 295));box-shadow:0 2px 8px oklch(0.48 0.195 265 / 0.35)"
        >
          <span class="material-symbols-rounded text-white" style="font-size:18px;font-variation-settings:'FILL' 1,'wght' 600,'GRAD' 0,'opsz' 20">corporate_fare</span>
        </div>
        <span
          class="hidden sm:block whitespace-nowrap font-medium text-[1.08rem] transition-colors duration-300 text-[var(--sys-text-primary)]"
        >HRM Portal</span>
      </div>

      <!-- Breadcrumb (center-left) -->
      <nav class="hidden md:flex items-center gap-1 ml-4 text-sm">
        <span
          class="cursor-pointer transition-colors text-[var(--sys-text-secondary)] hover:text-[var(--sys-accent)]"
        >Home</span>
        <span
          class="material-symbols-rounded text-[var(--sys-text-secondary)] opacity-50"
          style="font-size:16px;font-variation-settings:'FILL' 0,'wght' 300,'GRAD' 0,'opsz' 20"
        >chevron_right</span>
        <span
          class="font-medium text-[var(--sys-text-primary)]"
        >{{ currentPageLabel }}</span>
      </nav>

      <div class="flex-1" />

      <!-- Right actions -->
      <div class="flex items-center gap-0.5">

        <!-- Search -->
        <div class="relative group hidden sm:flex items-center mr-1">
          <span
            class="material-symbols-rounded absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none text-[18px] transition-colors text-[var(--sys-text-secondary)]"
            style="font-variation-settings:'FILL' 0,'wght' 400,'GRAD' 0,'opsz' 20"
          >search</span>
          <input
            type="text"
            placeholder="Tìm kiếm..."
            class="pl-9 pr-4 py-1.5 w-48 rounded-full text-sm transition-all duration-200 focus:outline-none focus:w-64 bg-[var(--sys-bg-page)] border border-[var(--sys-border)] text-[var(--sys-text-primary)] focus:border-[var(--sys-accent)] focus:ring-2 focus:ring-[var(--sys-accent)]/20"
          />
        </div>

        <!-- Notification -->
        <div class="relative" ref="notificationDropdownRef">
          <button
            @click="isNotificationOpen = !isNotificationOpen"
            aria-label="Notifications"
            class="relative flex items-center justify-center w-10 h-10 rounded-full transition-colors duration-150 text-[var(--sys-text-secondary)] hover:bg-[var(--sys-bg-hover)]"
          >
            <span class="material-symbols-rounded" style="font-size:24px;font-variation-settings:'FILL' 0,'wght' 400,'GRAD' 0,'opsz' 24">notifications</span>
            <!-- M3 Badge dot -->
            <span
              class="absolute top-1.5 right-1.5 flex h-2.5 w-2.5 rounded-full bg-[oklch(0.55_0.22_25)] items-center justify-center ring-2"
              :class="isDark ? 'ring-[oklch(0.165_0.015_265)]' : 'ring-white'"
            ></span>
          </button>

          <!-- Notification Dropdown -->
          <transition name="m3-dropdown">
            <div
              v-if="isNotificationOpen"
              class="absolute right-0 mt-3 w-80 rounded-3xl overflow-hidden z-50 shadow-[0_8px_32px_oklch(0_0_0/0.18)]"
              :class="isDark
                ? 'bg-[var(--sys-bg-surface-elevated)] border border-[var(--sys-border-subtle)]'
                : 'bg-[var(--sys-bg-surface-elevated)] border border-[var(--sys-border-subtle)]'"
            >
              <div
                class="flex justify-between items-center px-5 py-4 border-b"
                :class="isDark ? 'border-[var(--sys-border-subtle)]' : 'border-[var(--sys-border-subtle)]'"
              >
                <h6
                  class="text-sm font-bold mb-0 text-[var(--sys-text-primary)]"
                >Thông báo</h6>
                <span class="text-[10px] font-bold text-[var(--sys-accent)] uppercase tracking-widest">3 Mới</span>
              </div>
              <div class="max-h-[300px] overflow-y-auto">
                <div
                  class="p-4 flex gap-3 transition-colors cursor-default border-b border-[var(--sys-border)] hover:bg-[var(--sys-bg-page)]"
                >
                  <div
                    class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0 bg-[var(--sys-accent)]/10 text-[var(--sys-accent)]"
                  >
                    <span class="material-symbols-rounded text-base" style="font-variation-settings:'FILL' 1,'wght' 400,'GRAD' 0,'opsz' 20">payments</span>
                  </div>
                  <div>
                    <p
                      class="text-xs font-semibold mb-0.5 text-[var(--sys-text-primary)]"
                    >Phiếu lương tháng 10/2023 đã cập nhật</p>
                    <p
                      class="text-[10px] text-[var(--sys-text-secondary)]"
                    >Tài chính · 10 phút trước</p>
                  </div>
                </div>
                <div
                  class="p-4 flex gap-3 transition-colors cursor-default hover:bg-[var(--sys-bg-page)]"
                >
                  <div
                    class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0 bg-[var(--sys-success-bg)] text-[var(--sys-success-text)]"
                  >
                    <span class="material-symbols-rounded text-base" style="font-variation-settings:'FILL' 1,'wght' 400,'GRAD' 0,'opsz' 20">task_alt</span>
                  </div>
                  <div>
                    <p
                      class="text-xs font-semibold mb-0.5 text-[var(--sys-text-primary)]"
                    >Đơn xin nghỉ phép đã được duyệt</p>
                    <p
                      class="text-[10px] text-[var(--sys-text-secondary)]"
                    >Nhân sự · 1 giờ trước</p>
                  </div>
                </div>
              </div>
              <!-- Custom Xem Tất Cả Thông Báo Link -->
              <div
                class="border-t p-2 text-center"
                :class="isDark ? 'border-[var(--sys-border-subtle)]' : 'border-[var(--sys-border-subtle)]'"
              >
                <router-link
                  to="/nhanvien/thongbao"
                  @click="isNotificationOpen = false"
                  class="text-xs font-semibold transition-colors duration-200 text-[var(--sys-accent)] hover:text-[oklch(0.4_0.15_265)] inline-block py-1 px-4 rounded-full hover:bg-[var(--sys-accent)]/10"
                >
                  Xem tất cả thông báo
                </router-link>
              </div>
            </div>
          </transition>
        </div>

        <!-- ── Dark Mode M3 Switch ── -->
        <div class="mx-2 flex items-center gap-1.5">
          <!-- Sun -->
          <span
            class="material-symbols-rounded transition-all duration-300"
            style="font-size:18px;font-variation-settings:'FILL' 1,'wght' 400,'GRAD' 0,'opsz' 20"
            :class="isDark
              ? 'text-[var(--sys-text-secondary)] opacity-40 scale-90'
              : 'text-[oklch(0.62_0.14_75)] opacity-100 scale-100'"
          >light_mode</span>

          <!-- Switch track -->
          <button
            role="switch"
            :aria-checked="isDark"
            :aria-label="isDark ? 'Chuyển sang sáng' : 'Chuyển sang tối'"
            @click="isDark = !isDark"
            class="relative w-[52px] h-8 rounded-full transition-all duration-300 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-[var(--sys-accent)] border border-[var(--sys-border-subtle)]"
            :class="isDark
              ? 'bg-[#0f172a] ring-2 ring-white/10'
              : 'bg-[var(--sys-bg-hover)]'"
          >
            <span
              class="absolute top-1/2 -translate-y-1/2 rounded-full flex items-center justify-center transition-all duration-300 ease-[cubic-bezier(0.2,0,0,1)] shadow-md"
              :class="isDark
                ? 'left-[calc(100%-28px)] w-6 h-6 bg-white shadow-[0_0_12px_var(--sys-accent)]'
                : 'left-1 w-5 h-5 bg-[var(--sys-text-secondary)]'"
            >
              <span
                class="material-symbols-rounded transition-all duration-200"
                style="font-size:12px;font-variation-settings:'FILL' 1,'wght' 400,'GRAD' 0,'opsz' 20"
                :class="isDark ? 'text-[var(--sys-accent)]' : 'text-white'"
              >{{ isDark ? 'dark_mode' : 'light_mode' }}</span>
            </span>
          </button>

          <!-- Moon -->
          <span
            class="material-symbols-rounded transition-all duration-300"
            style="font-size:18px;font-variation-settings:'FILL' 1,'wght' 400,'GRAD' 0,'opsz' 20"
            :class="isDark
              ? 'text-[var(--sys-accent)] opacity-100 scale-100'
              : 'text-[var(--sys-text-secondary)] opacity-40 scale-90'"
          >dark_mode</span>
        </div>

        <div
          class="h-6 w-px hidden md:block mx-1 bg-[var(--sys-border)]"
        ></div>

        <!-- Profile Dropdown -->
        <div class="relative" ref="profileDropdownRef">
          <button
            @click="isProfileOpen = !isProfileOpen"
            class="flex items-center gap-2.5 p-1 pr-3 rounded-full transition-all duration-200 select-none hover:bg-[var(--sys-bg-hover)] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--sys-brand-solid)]"
          >
            <div
              class="w-8 h-8 rounded-full flex items-center justify-center bg-[var(--sys-accent)]/10 text-[var(--sys-accent)] border border-[var(--sys-accent)]/20 text-sm font-bold shrink-0 overflow-hidden"
            >
              <img v-if="avatar" :src="avatar" class="w-full h-full object-cover" />
              <span v-else>{{ fullName.charAt(0) }}</span>
            </div>
            <div class="hidden lg:flex flex-col justify-center text-left">
              <span
                class="text-sm font-bold leading-tight"
                :class="isDark ? 'text-[oklch(0.91_0.01_265)]' : 'text-[var(--sys-text-primary)]'"
              >{{ fullName }}</span>
              <span
                class="text-[10px] uppercase tracking-widest font-bold mt-0.5 leading-none"
                :class="isDark ? 'text-[oklch(0.65_0.1_265)]' : 'text-[var(--sys-text-secondary)]'"
              >{{ positionName || 'Nhân viên' }}</span>
            </div>
            <span
              class="material-symbols-rounded text-sm transition-transform duration-200"
              :class="[
                isProfileOpen ? 'rotate-180' : '',
                'text-[var(--sys-text-secondary)]'
              ]"
              style="font-size:18px;font-variation-settings:'FILL' 0,'wght' 400,'GRAD' 0,'opsz' 20"
            >expand_more</span>
          </button>

          <transition name="m3-dropdown">
            <div
              v-if="isProfileOpen"
              class="absolute right-0 mt-3 w-60 rounded-lg overflow-hidden z-50 shadow-sm border border-[var(--sys-border-subtle)] bg-[var(--sys-bg-surface-elevated)] py-1"
            >
              <div
                class="px-4 py-3 border-b border-[var(--sys-border-subtle)] mb-1"
              >
                <p
                  class="text-[10px] font-semibold uppercase tracking-wider mb-1 text-[var(--sys-text-secondary)]"
                >Cổng thông tin nhân sự</p>
                <p
                  class="text-xs font-medium truncate mb-0 text-[var(--sys-text-primary)]"
                >{{ email }}</p>
              </div>
              <router-link
                to="/nhanvien/hoso"
                class="flex items-center gap-3 px-4 py-2 text-sm font-medium transition-colors text-[var(--sys-text-primary)] hover:bg-[var(--sys-bg-hover)]"
                @click="isProfileOpen = false"
              >
                <span class="material-symbols-rounded text-[18px]" style="font-variation-settings:'FILL' 0,'wght' 400,'GRAD' 0,'opsz' 20">person</span>
                Thông tin cá nhân
              </router-link>
              <button
                @click="logout"
                class="w-full flex items-center gap-3 px-4 py-2 text-sm font-medium text-left transition-colors text-[var(--sys-danger-text)] hover:bg-[var(--sys-danger-soft)]"
              >
                <span class="material-symbols-rounded text-[18px]" style="font-variation-settings:'FILL' 0,'wght' 400,'GRAD' 0,'opsz' 20">logout</span>
                Đăng xuất
              </button>
            </div>
          </transition>
        </div>
      </div>
    </header>

    <!-- ═══════════════════════════════════════════════
         MOBILE SCRIM OVERLAY
    ═══════════════════════════════════════════════ -->
    <div
      @click="isMobileSidebarOpen = false"
      class="fixed inset-0 z-20 lg:hidden bg-black/40 backdrop-blur-sm transition-opacity duration-300"
      :class="isMobileSidebarOpen ? 'opacity-100 pointer-events-auto' : 'opacity-0 pointer-events-none'"
    ></div>

    <!-- ═══════════════════════════════════════════════
         M3 SIDEBAR — DRAWER ↔ RAIL
    ═══════════════════════════════════════════════ -->
    <aside
      class="fixed top-16 left-0 bottom-0 z-30 flex flex-col overflow-hidden transition-[width,transform] duration-300 ease-[cubic-bezier(0.2,0,0,1)]"
      :class="[
        sidebarExpanded ? 'w-[280px]' : 'w-20',
        isMobileSidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0',
        'bg-[var(--sys-bg-surface)] border-r border-[var(--sys-border-subtle)]'
      ]"
    >
      <!-- Sidebar header with collapse toggle (desktop) -->
      <div
        class="shrink-0 h-12 flex items-center px-3 border-b border-[var(--sys-border-subtle)]"
      >
        <!-- Icon logo (always visible) -->
        <span
          class="material-symbols-rounded shrink-0 transition-colors duration-200 text-[var(--sys-brand-solid)]"
          style="font-size:22px;font-variation-settings:'FILL' 1,'wght' 400,'GRAD' 0,'opsz' 24"
        >apps</span>

        <!-- Label text: visible only when expanded -->
        <span
          class="overflow-hidden whitespace-nowrap text-xs font-bold uppercase tracking-[0.12em] transition-all duration-300 ml-2 text-[var(--sys-text-secondary)]"
          :class="[
            sidebarExpanded ? 'opacity-100 max-w-[150px]' : 'opacity-0 max-w-0 ml-0 pointer-events-none'
          ]"
        >Menu</span>

        <!-- Chevron collapse toggle -->
        <button
          @click="sidebarExpanded = !sidebarExpanded"
          class="hidden lg:flex ml-auto w-8 h-8 rounded-full items-center justify-center transition-colors duration-150 text-[var(--sys-text-secondary)] hover:bg-[var(--sys-bg-hover)] hover:text-[var(--sys-text-primary)]"
          :aria-label="sidebarExpanded ? 'Thu gọn sidebar' : 'Mở rộng sidebar'"
        >
          <span
            class="material-symbols-rounded transition-transform duration-300"
            style="font-size:20px;font-variation-settings:'FILL' 0,'wght' 400,'GRAD' 0,'opsz' 24"
            :class="sidebarExpanded ? 'rotate-0' : 'rotate-180'"
          >chevron_left</span>
        </button>
      </div>


      <!-- Nav scrollable area -->
      <nav
        class="flex-1 overflow-y-auto overflow-x-hidden py-2 custom-scrollbar"
      >

        <div :class="sidebarExpanded ? 'px-3 space-y-0.5' : 'px-2 flex flex-col items-center gap-0.5'">
          <SidebarItem
            :expanded="sidebarExpanded"
            :is-active="isExactActive('/nhanvien')"
            icon="home"
            label="Trang chủ"
            :is-dark="isDark"
            to="/nhanvien"
            @click="handleNavClick"
          />
          
          <SidebarItem 
            :expanded="sidebarExpanded" 
            :is-active="isActive('/nhanvien/chamcong')" 
            icon="schedule" 
            label="Chấm công" 
            :is-dark="isDark" 
            to="/nhanvien/chamcong" 
            @click="handleNavClick" 
          />

          <SidebarItem 
            :expanded="sidebarExpanded" 
            :is-active="isActive('/nhanvien/nghiphep')" 
            icon="event_busy" 
            label="Nghỉ phép" 
            :is-dark="isDark" 
            to="/nhanvien/nghiphep" 
            @click="handleNavClick" 
            :badge="0" 
          />

          <SidebarItem 
            :expanded="sidebarExpanded" 
            :is-active="isActive('/nhanvien/luong')" 
            icon="payments" 
            label="Tiền lương" 
            :is-dark="isDark" 
            to="/nhanvien/luong" 
            @click="handleNavClick" 
          />

          <NavSection label="Tiện ích" :expanded="sidebarExpanded" :is-dark="isDark" />

          <SidebarItem 
            :expanded="sidebarExpanded" 
            :is-active="isActive('/nhanvien/dichvu')" 
            icon="support_agent" 
            label="Dịch vụ nội bộ" 
            :is-dark="isDark" 
            to="/nhanvien/dichvu" 
            @click="handleNavClick" 
          />
        </div>
      </nav>

    </aside>

    <!-- ═══════════════════════════════════════════════
         MAIN CONTENT
    ═══════════════════════════════════════════════ -->
    <main
      class="pt-16 min-h-screen transition-[margin] duration-300 ease-[cubic-bezier(0.2,0,0,1)]"
      :class="sidebarExpanded ? 'lg:ml-[280px]' : 'lg:ml-20'"
    >
      <div class="p-6 lg:p-8 max-w-[1600px] mx-auto text-[var(--sys-text-primary)] overflow-hidden">
        <router-view v-slot="{ Component }">
          <transition :name="transitionName" mode="out-in">
            <component :is="Component" />
          </transition>
        </router-view>
      </div>
    </main>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted, defineComponent, h, resolveComponent } from 'vue';
import { useRoute, useRouter, RouterLink } from 'vue-router';
import { useConfirm } from '@/composables/useConfirm';
import { useCurrentUser } from '@/composables/useCurrentUser';
import { clearAuthSession, getCurrentUserRole } from '@/services/session.js';

const { fullName, email, avatar, positionName } = useCurrentUser();
const { showConfirm } = useConfirm();

const route = useRoute();
const router = useRouter();

// ── Transition Logic ───────────────────────────────────────────────────────
const transitionName = ref('fade');
watch(() => route.meta.index, (toIndex, fromIndex) => {
  if (toIndex === undefined || fromIndex === undefined) {
    transitionName.value = 'fade';
    return;
  }
  transitionName.value = toIndex > fromIndex ? 'slide-up' : 'slide-down';
});

// ── State ──────────────────────────────────────────────────────────────────
const sidebarExpanded = ref(true);
const isMobileSidebarOpen = ref(false);
const isDark = ref(false);
const isNotificationOpen = ref(false);
const isProfileOpen = ref(false);

const notificationDropdownRef = ref(null);
const profileDropdownRef = ref(null);

// ── Dark mode effect ───────────────────────────────────────────────────────
watch(isDark, (val) => {
  document.documentElement.classList.toggle('dark', val);
}, { immediate: true });

// ── Router helpers ─────────────────────────────────────────────────────────
const isActive = (path) => route.path.startsWith(path) && path !== '/';
const isExactActive = (path) => route.path === path;

const currentPageLabel = computed(() => {
  const path = route.path;
  if (path === '/nhanvien' || path === '/nhanvien/') return 'Trang chủ';
  if (path.startsWith('/nhanvien/chamcong')) return 'Chấm công';
  if (path.startsWith('/nhanvien/nghiphep')) return 'Nghỉ phép';
  if (path.startsWith('/nhanvien/luong')) return 'Tiền lương';
  if (path.startsWith('/nhanvien/dichvu')) return 'Dịch vụ nội bộ';
  if (path.startsWith('/nhanvien/hoso')) return 'Hồ sơ cá nhân';
  return 'Trang chủ';
});

// ── Sidebar / Menu toggle ──────────────────────────────────────────────────
const handleMenuToggle = () => {
  if (window.innerWidth < 1024) {
    isMobileSidebarOpen.value = !isMobileSidebarOpen.value;
  } else {
    sidebarExpanded.value = !sidebarExpanded.value;
  }
};

const handleNavClick = () => {
  isMobileSidebarOpen.value = false;
};

// ── Click outside to close dropdowns ──────────────────────────────────────
const handleClickOutside = (event) => {
  if (profileDropdownRef.value && !profileDropdownRef.value.contains(event.target)) {
    isProfileOpen.value = false;
  }
  if (notificationDropdownRef.value && !notificationDropdownRef.value.contains(event.target)) {
    isNotificationOpen.value = false;
  }
};

onMounted(() => {
  document.addEventListener('click', handleClickOutside);
  
  // Check authentication
  const userRole = getCurrentUserRole();
  if (!userRole || userRole !== 'employee') {
    router.push('/login');
  }
});
onUnmounted(() => document.removeEventListener('click', handleClickOutside));

// ── Logout ─────────────────────────────────────────────────────────────────
const logout = async () => {
  const ok = await showConfirm('Xác nhận đăng xuất', 'Bạn có chắc chắn muốn thoát khỏi hệ thống không?');
  if (ok) {
    clearAuthSession();
    // Forcefully remove dark mode class
    document.documentElement.classList.remove('dark');
    router.push('/login');
  }
};
</script>

<!-- ─── Sub-components (defineComponent inline) ─────────────────────────── -->
<script>
import { defineComponent, h, resolveComponent } from 'vue';

// ── NavSection: section label / divider ──────────────────────────────────
export const NavSection = defineComponent({
  name: 'NavSection',
  props: {
    label: String,
    expanded: Boolean,
    isDark: Boolean,
  },
  setup(props) {
    return () => {
      if (props.expanded) {
        return h('p', {
          class: [
            'px-5 pt-4 pb-1 text-[10px] font-bold uppercase tracking-[0.1em] whitespace-nowrap text-[var(--sys-text-secondary)]',
          ].join(' '),
        }, props.label);
      } else {
        return h('div', {
          class: [
            'mx-auto w-8 h-px my-3 bg-[var(--sys-border)]',
          ].join(' '),
        });
      }
    };
  },
});

// ── SidebarItem ───────────────────────────────────────────────────────────
export const SidebarItem = defineComponent({
  name: 'SidebarItem',
  props: {
    expanded: Boolean,
    isActive: Boolean,
    icon: String,
    label: String,
    isDark: Boolean,
    to: String,
    badge: { type: Number, default: undefined },
  },
  emits: ['click'],
  setup(props, { emit }) {
    return () => {
      const RouterLink = resolveComponent('RouterLink');

      // Active pill bg
      const activeBg = 'bg-[var(--sys-brand-soft)]';
      const hoverBg  = 'hover:bg-[var(--sys-bg-hover)]';
      const activeText = 'text-[var(--sys-brand-soft-text)]';
      const activeIcon = 'text-[var(--sys-brand-solid)]';
      const inactiveText = 'text-[var(--sys-text-secondary)]';
      const inactiveIcon = 'text-[var(--sys-icon-default)]';
      const labelText = 'text-[var(--sys-text-primary)]';

      // ── DRAWER (expanded) ──
      if (props.expanded) {
        return h(RouterLink, {
          to: props.to,
          class: [
            'group relative flex items-center gap-3 w-full h-[52px] px-4 rounded-[26px]',
            'transition-colors duration-150 outline-none text-left no-underline',
            'focus-visible:ring-2 focus-visible:ring-[var(--sys-accent)]',
            props.isActive ? activeBg : hoverBg,
          ].join(' '),
          onClick: () => emit('click'),
        }, () => [
          // State ripple
          h('span', { class: 'absolute inset-0 rounded-[26px] opacity-0 group-active:opacity-10 bg-[oklch(0.22_0.12_265)] dark:bg-[oklch(0.9_0.09_265)] transition-opacity pointer-events-none' }),

          // Icon (morphing)
          h('span', { class: 'relative shrink-0 w-6 h-6 flex items-center justify-center' }, [
            // Outline icon (inactive)
            h('span', {
              class: [
                'material-symbols-rounded absolute transition-all duration-300',
                props.isActive ? `opacity-0 scale-50 ${activeIcon}` : `opacity-100 scale-100 ${inactiveIcon}`,
              ].join(' '),
              style: "font-size:22px;font-variation-settings:'FILL' 0,'wght' 400,'GRAD' 0,'opsz' 24",
            }, props.icon),
            // Filled icon (active)
            h('span', {
              class: [
                'material-symbols-rounded absolute transition-all duration-300',
                props.isActive ? `opacity-100 scale-100 ${activeIcon}` : `opacity-0 scale-150 ${inactiveIcon}`,
              ].join(' '),
              style: "font-size:22px;font-variation-settings:'FILL' 1,'wght' 500,'GRAD' 0,'opsz' 24",
            }, props.icon),
          ]),

          // Label
          h('span', {
            class: ['flex-1 text-sm whitespace-nowrap', props.isActive ? `${activeText} font-semibold` : labelText].join(' '),
          }, props.label),

          // Badge chip
          props.badge !== undefined ? h('span', {
            class: [
              'shrink-0 min-w-[22px] h-[22px] px-1.5 rounded-full flex items-center justify-center',
              props.isActive
                ? 'bg-[var(--sys-bg-surface)] text-[var(--sys-brand-solid)]'
                : 'bg-[var(--sys-brand-soft)] text-[var(--sys-brand-soft-text)]',
            ].join(' '),
            style: 'font-size:11px;font-weight:600',
          }, props.badge > 99 ? '99+' : String(props.badge)) : null,
        ]);
      }

      // ── RAIL (collapsed) ──
      return h(RouterLink, {
        to: props.to,
        title: props.label,
        class: [
          'group relative flex flex-col items-center gap-1 w-full py-1 cursor-pointer outline-none',
          'focus-visible:ring-2 focus-visible:ring-[var(--sys-accent)] rounded-xl no-underline',
        ].join(' '),
        onClick: () => emit('click'),
      }, () => [
        // Pill
        h('div', {
          class: [
            'relative flex items-center justify-center w-16 h-8 rounded-full transition-colors duration-150',
            props.isActive ? activeBg : hoverBg,
          ].join(' '),
        }, [
          // Badge for Rail
          props.badge !== undefined ? h('span', {
            class: [
              'absolute -top-1 right-2 min-w-[14px] h-[14px] px-1 rounded-full flex items-center justify-center bg-[var(--sys-brand-soft)] text-[var(--sys-brand-soft-text)] shadow-sm',
            ].join(' '),
            style: 'font-size:8px;font-weight:700',
          }, props.badge > 9 ? '9+' : String(props.badge)) : null,

          // Active dot for Rail
          h('div', {
            class: [
              'absolute -right-1 top-1/2 -translate-y-1/2 w-1 h-3 rounded-l-full bg-[var(--sys-brand-solid)] transition-all duration-300',
              props.isActive ? 'opacity-100 scale-y-100' : 'opacity-0 scale-y-0',
            ].join(' '),
          }),

          // Icon (morphing)
          h('span', { class: 'relative w-5 h-5 flex items-center justify-center' }, [
            h('span', {
              class: [
                'material-symbols-rounded absolute transition-all duration-300',
                props.isActive ? `opacity-0 scale-50 ${activeIcon}` : `opacity-100 scale-100 ${inactiveIcon}`,
              ].join(' '),
              style: "font-size:21px;font-variation-settings:'FILL' 0,'wght' 400,'GRAD' 0,'opsz' 24",
            }, props.icon),
            h('span', {
              class: [
                'material-symbols-rounded absolute transition-all duration-300',
                props.isActive ? `opacity-100 scale-100 ${activeIcon}` : `opacity-0 scale-150 ${inactiveIcon}`,
              ].join(' '),
              style: "font-size:21px;font-variation-settings:'FILL' 1,'wght' 500,'GRAD' 0,'opsz' 24",
            }, props.icon),
          ]),
        ]),

        // Label below pill
        h('span', {
          class: [
            'text-[10px] leading-tight px-1 text-center transition-colors',
            props.isActive ? activeText : inactiveText,
          ].join(' '),
          style: `font-weight:${props.isActive ? 600 : 400}`,
        }, props.label.length > 10 ? props.label.slice(0, 9) + '…' : props.label),
      ]);
    };
  },
});
</script>

<style scoped>
/* M3 Dropdown transition */
.m3-dropdown-enter-active,
.m3-dropdown-leave-active {
  transition: all 0.22s cubic-bezier(0.2, 0, 0, 1);
}
.m3-dropdown-enter-from,
.m3-dropdown-leave-to {
  opacity: 0;
  transform: translateY(8px) scale(0.96);
}

/* Page Transitions */
.slide-up-enter-active,
.slide-up-leave-active,
.slide-down-enter-active,
.slide-down-leave-active,
.fade-enter-active,
.fade-leave-active {
  transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
}

.slide-up-enter-from {
  opacity: 0;
  transform: translateY(20px);
}
.slide-up-leave-to {
  opacity: 0;
  transform: translateY(-20px);
}

.slide-down-enter-from {
  opacity: 0;
  transform: translateY(-20px);
}
.slide-down-leave-to {
  opacity: 0;
  transform: translateY(20px);
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

/* Remove link underline */
a,
.router-link-active,
.router-link-exact-active {
  text-decoration: none !important;
}

/* Scrollbar */
nav::-webkit-scrollbar { width: 4px; }
nav::-webkit-scrollbar-track { background: transparent; }
nav::-webkit-scrollbar-thumb { border-radius: 8px; }

/* Global Table Fix: Prevent text wrapping in columns */
:deep(table) th,
:deep(table) td {
  white-space: nowrap !important;
}
</style>
