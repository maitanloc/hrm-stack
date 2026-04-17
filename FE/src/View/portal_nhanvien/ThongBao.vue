<template>
  <div class="notifications-page min-h-screen bg-[var(--sys-bg-page)] text-[var(--sys-text-primary)] p-4 md:p-6 lg:p-8">
    <div class="max-w-4xl mx-auto space-y-6 bg-transparent">
      
      <!-- Top Header -->
      <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 bg-transparent text-left">
        <div class="bg-transparent text-left">
          <h1 class="text-xl font-semibold text-[var(--sys-text-primary)] tracking-tight flex items-center gap-2 m-0">
            <span class="material-symbols-outlined text-[var(--sys-brand-solid)] text-[24px]">notifications_active</span>
            Trung tâm thông báo & Sự kiện
          </h1>
          <p class="text-[13px] text-[var(--sys-text-secondary)] mt-0.5">Cập nhật tin tức quan trọng và hoạt động hệ thống cá nhân.</p>
        </div>
        
        <div class="flex items-center gap-2 w-full md:w-auto shrink-0">
          <div class="relative flex-grow md:w-64">
            <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-[var(--sys-text-secondary)]/50 text-[18px]">search</span>
            <input 
              v-model="searchQuery"
              type="text" 
              class="w-full h-10 pl-10 pr-4 bg-white border border-[var(--sys-border-strong)] rounded-md text-[13px] font-medium focus:border-[var(--sys-brand-solid)] outline-none shadow-sm transition-all placeholder:text-[var(--sys-text-secondary)]/40" 
              placeholder="Tìm kiếm nội dung..."
            >
          </div>
          <button class="w-10 h-10 flex items-center justify-center rounded-md bg-white border border-[var(--sys-border-strong)] text-[var(--sys-text-secondary)] hover:border-[var(--sys-brand-solid)] transition-all shadow-sm">
            <span class="material-symbols-outlined text-[18px]">tune</span>
          </button>
        </div>
      </div>

      <!-- Filters Row -->
      <div class="flex flex-wrap items-center justify-between gap-4 p-1">
        <div class="flex items-center gap-1 bg-[var(--sys-bg-page)] p-1 rounded-md border border-[var(--sys-border-subtle)] shadow-inner">
          <button 
            v-for="f in ['Tất cả', 'Chưa đọc', 'Quan trọng']" 
            :key="f"
            @click="activeFilter = f"
            :class="[
              'px-4 py-1.5 rounded-md text-[11px] font-bold uppercase tracking-wide transition-all border outline-none',
              activeFilter === f 
                ? 'bg-white text-[var(--sys-brand-solid)] border-[var(--sys-border-subtle)] shadow-sm' 
                : 'text-[var(--sys-text-secondary)] border-transparent hover:text-[var(--sys-text-primary)]'
            ]"
          >
            {{ f }}
          </button>
        </div>
        
        <div class="flex items-center gap-2 text-[11px] font-bold text-[var(--sys-text-disabled)] uppercase tracking-wider">
          Sắp xếp:
          <button class="text-[var(--sys-brand-solid)] hover:opacity-80 transition-opacity flex items-center gap-1">
            Gần nhất <span class="material-symbols-outlined text-[16px]">expand_more</span>
          </button>
        </div>
      </div>

      <!-- Notifications List -->
      <div class="space-y-6">
        <!-- New Group -->
        <div class="space-y-3">
          <h2 class="text-[11px] font-bold text-[var(--sys-text-disabled)] uppercase tracking-[0.2em] ml-1">Đang chờ xử lý / Mới</h2>
          
          <div class="flex flex-col gap-3">
            <button
              v-for="item in unreadNotifications"
              :key="item.id"
              type="button"
              class="group relative bg-white rounded-lg border-l-4 border-l-[var(--sys-brand-solid)] border-y border-r border-[var(--sys-border-subtle)] shadow-sm cursor-pointer hover:bg-[var(--sys-bg-hover)] transition-all text-left"
              @click="handleNotificationClick(item)"
            >
              <div class="p-5 flex gap-5 items-start">
                <div :class="['w-11 h-11 rounded-md flex items-center justify-center shrink-0 border group-hover:text-white transition-all',
                  item.type === 'success'
                    ? 'bg-[var(--sys-success-soft)] text-[var(--sys-success-text)] border-[var(--sys-success-border)] group-hover:bg-[var(--sys-success-solid)]'
                    : item.type === 'warning'
                      ? 'bg-[var(--sys-warning-soft)] text-[var(--sys-warning-text)] border-[var(--sys-warning-border)] group-hover:bg-[var(--sys-warning-solid)]'
                      : 'bg-[var(--sys-brand-soft)] text-[var(--sys-brand-solid)] border-[var(--sys-brand-border)] group-hover:bg-[var(--sys-brand-solid)]'
                ]">
                  <span class="material-symbols-outlined text-[24px]">{{ item.icon }}</span>
                </div>
                <div class="flex-grow">
                  <div class="flex justify-between items-start gap-4 mb-1">
                    <h3 class="text-sm font-bold text-[var(--sys-text-primary)] group-hover:text-[var(--sys-brand-solid)] transition-colors">{{ item.title }}</h3>
                    <div class="flex items-center gap-2 shrink-0">
                      <span class="text-[10px] font-bold text-[var(--sys-text-disabled)] uppercase tracking-tight">{{ item.time }}</span>
                      <span class="w-2 h-2 rounded-full bg-[var(--sys-brand-solid)] animate-pulse"></span>
                    </div>
                  </div>
                  <p class="text-[13px] font-medium text-[var(--sys-text-secondary)] leading-relaxed mb-3 opacity-80">
                    {{ item.desc }}
                  </p>
                  <div class="flex items-center gap-2">
                    <span class="px-2 py-0.5 rounded-md text-[10px] font-bold uppercase border"
                      :class="item.type === 'success'
                        ? 'bg-[var(--sys-success-soft)] text-[var(--sys-success-text)] border-[var(--sys-success-border)]'
                        : item.type === 'warning'
                          ? 'bg-[var(--sys-warning-soft)] text-[var(--sys-warning-text)] border-[var(--sys-warning-border)]'
                          : 'bg-[var(--sys-brand-soft)] text-[var(--sys-brand-solid)] border-[var(--sys-brand-border)]'"
                    >
                      {{ item.priorityLabel }}
                    </span>
                  </div>
                </div>
              </div>
            </button>
            <div v-if="unreadNotifications.length === 0" class="rounded-lg border border-dashed border-[var(--sys-border-subtle)] bg-white px-5 py-8 text-center text-[12px] font-medium text-[var(--sys-text-secondary)]">
              Không còn thông báo chưa đọc phù hợp với bộ lọc hiện tại.
            </div>
          </div>
        </div>

        <!-- Older Group -->
        <div class="space-y-3">
          <h2 class="text-[11px] font-bold text-[var(--sys-text-disabled)] uppercase tracking-[0.2em] ml-1">Trước đó / Đã xem</h2>
          
          <div class="flex flex-col gap-2">
            <button v-for="item in readNotifications" :key="item.id" type="button"
              class="group bg-white rounded-lg border border-[var(--sys-border-subtle)] shadow-sm cursor-pointer hover:bg-[var(--sys-bg-hover)] transition-all text-left"
              @click="handleNotificationClick(item)">
              <div class="p-4 flex gap-4 items-start">
                <div :class="`w-10 h-10 rounded-md flex items-center justify-center shrink-0 border transition-all bg-[var(--sys-${item.color}-soft)] text-[var(--sys-${item.color}-text)] border-[var(--sys-${item.color}-border)] group-hover:bg-[var(--sys-${item.color}-solid)] group-hover:text-white`">
                  <span class="material-symbols-outlined text-[20px]">{{ item.icon }}</span>
                </div>
                <div class="flex-grow min-w-0">
                  <div class="flex justify-between items-start gap-4 mb-0.5">
                    <h3 class="text-[13px] font-bold text-[var(--sys-text-primary)] group-hover:text-[var(--sys-brand-solid)] transition-colors truncate">{{ item.title }}</h3>
                    <span class="text-[10px] font-bold text-[var(--sys-text-disabled)] uppercase tracking-tight shrink-0">{{ item.time }}</span>
                  </div>
                  <p class="text-[12px] font-medium text-[var(--sys-text-secondary)] opacity-70 truncate">{{ item.desc }}</p>
                </div>
              </div>
            </button>
            <div v-if="readNotifications.length === 0" class="rounded-lg border border-dashed border-[var(--sys-border-subtle)] bg-white px-5 py-8 text-center text-[12px] font-medium text-[var(--sys-text-secondary)]">
              Chưa có thông báo đã xem phù hợp với bộ lọc hiện tại.
            </div>
          </div>
        </div>
      </div>

    </div>
  </div>
</template>

<script setup>
/**
 * TRUNG TÂM THÔNG BÁO (PORTAL) - PHIÊN BẢN ENTERPRISE SaaS
 * Tuân thủ 7 Golden Rules:
 * - Font Inter 14px (text-sm)
 * - Bo góc chuẩn B2B: 6px (MD), 8px (LG)
 * - Hệ màu Semantic đồng bộ, xóa bỏ font-black/italic rườm rà
 */
import { computed, onMounted, ref } from 'vue';
import { fetchNotifications, markNotificationRead } from '@/services/notificationsApi.js';

const searchQuery = ref('');
const activeFilter = ref('Tất cả');
const notifications = ref([]);

const decorateNotification = (item) => ({
  ...item,
  color: item.type === 'success' ? 'success' : item.type === 'warning' ? 'warning' : 'brand',
  priorityLabel: item.priority === 'CAO' ? 'Ưu tiên cao' : item.priority === 'THẤP' ? 'Thông tin' : 'Cập nhật',
});

const filteredNotifications = computed(() => {
  const query = String(searchQuery.value || '').trim().toLowerCase();
  return notifications.value.filter((item) => {
    const matchesQuery = !query
      || item.title.toLowerCase().includes(query)
      || item.desc.toLowerCase().includes(query);
    if (!matchesQuery) return false;

    if (activeFilter.value === 'Chưa đọc') return !item.isRead;
    if (activeFilter.value === 'Quan trọng') return item.priority === 'CAO' || item.type === 'warning';
    return true;
  });
});

const unreadNotifications = computed(() => filteredNotifications.value.filter((item) => !item.isRead));
const readNotifications = computed(() => filteredNotifications.value.filter((item) => item.isRead));

const loadNotifications = async () => {
  try {
    notifications.value = (await fetchNotifications({ perPage: 100 })).map(decorateNotification);
  } catch (error) {
    console.error('Không tải được thông báo nhân viên:', error);
    notifications.value = [];
  }
};

const handleNotificationClick = async (item) => {
  try {
    if (!item?.isRead) {
      await markNotificationRead(item.id);
      await loadNotifications();
    }
  } catch (error) {
    console.error('Không đánh dấu đã đọc được:', error);
  }
};

onMounted(() => {
  void loadNotifications();
});
</script>

<style scoped>
.notifications-page {
  background-color: var(--sys-bg-page);
}
.material-symbols-outlined {
  font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
}
.group:hover .material-symbols-outlined {
  font-variation-settings: 'FILL' 1, 'wght' 400, 'GRAD' 0, 'opsz' 24;
}
</style>
