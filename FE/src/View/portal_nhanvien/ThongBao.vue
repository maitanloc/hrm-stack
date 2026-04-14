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
            <!-- Unread Item 1 -->
            <div class="group relative bg-white rounded-lg border-l-4 border-l-[var(--sys-brand-solid)] border-y border-r border-[var(--sys-border-subtle)] shadow-sm cursor-pointer hover:bg-[var(--sys-bg-hover)] transition-all">
              <div class="p-5 flex gap-5 items-start">
                <div class="w-11 h-11 rounded-md bg-[var(--sys-brand-soft)] text-[var(--sys-brand-solid)] flex items-center justify-center shrink-0 border border-[var(--sys-brand-border)] group-hover:bg-[var(--sys-brand-solid)] group-hover:text-white transition-all">
                  <span class="material-symbols-outlined text-[24px]">event_available</span>
                </div>
                <div class="flex-grow">
                  <div class="flex justify-between items-start gap-4 mb-1">
                    <h3 class="text-sm font-bold text-[var(--sys-text-primary)] group-hover:text-[var(--sys-brand-solid)] transition-colors">Đơn nghỉ phép đã được phê duyệt</h3>
                    <div class="flex items-center gap-2 shrink-0">
                      <span class="text-[10px] font-bold text-[var(--sys-text-disabled)] uppercase tracking-tight">10 phút trước</span>
                      <span class="w-2 h-2 rounded-full bg-[var(--sys-brand-solid)] animate-pulse"></span>
                    </div>
                  </div>
                  <p class="text-[13px] font-medium text-[var(--sys-text-secondary)] leading-relaxed mb-3 opacity-80">
                    Đơn nghỉ phép (20/10 - 22/10) đã được phê duyệt bởi BP. Hành chính Nhân sự.
                  </p>
                  <div class="flex items-center gap-2">
                    <span class="px-2 py-0.5 rounded-md bg-[var(--sys-brand-soft)] text-[var(--sys-brand-solid)] text-[10px] font-bold uppercase border border-[var(--sys-brand-border)]">Hành chính</span>
                  </div>
                </div>
              </div>
            </div>

            <!-- Unread Item 2 -->
            <div class="group relative bg-white rounded-lg border-l-4 border-l-[var(--sys-brand-solid)] border-y border-r border-[var(--sys-border-subtle)] shadow-sm cursor-pointer hover:bg-[var(--sys-bg-hover)] transition-all">
              <div class="p-5 flex gap-5 items-start">
                <div class="w-11 h-11 rounded-md bg-[var(--sys-success-soft)] text-[var(--sys-success-text)] flex items-center justify-center shrink-0 border border-[var(--sys-success-border)] group-hover:bg-[var(--sys-success-solid)] group-hover:text-white transition-all">
                  <span class="material-symbols-outlined text-[24px]">payments</span>
                </div>
                <div class="flex-grow">
                  <div class="flex justify-between items-start gap-4 mb-1">
                    <h3 class="text-sm font-bold text-[var(--sys-text-primary)] group-hover:text-[var(--sys-brand-solid)] transition-colors">Phiếu lương tháng 10/2023</h3>
                    <div class="flex items-center gap-2 shrink-0">
                      <span class="text-[10px] font-bold text-[var(--sys-text-disabled)] uppercase tracking-tight">2 giờ trước</span>
                      <span class="w-2 h-2 rounded-full bg-[var(--sys-brand-solid)] animate-pulse"></span>
                    </div>
                  </div>
                  <p class="text-[13px] font-medium text-[var(--sys-text-secondary)] leading-relaxed mb-3 opacity-80">
                    Cổng thông tin lương đã cập nhật phiếu lương tháng 10. Vui lòng xác nhận trước ngày 08/11.
                  </p>
                  <div class="flex items-center gap-2">
                    <span class="px-2 py-0.5 rounded-md bg-[var(--sys-success-soft)] text-[var(--sys-success-text)] text-[10px] font-bold uppercase border border-[var(--sys-success-border)]">Tài chính</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Older Group -->
        <div class="space-y-3">
          <h2 class="text-[11px] font-bold text-[var(--sys-text-disabled)] uppercase tracking-[0.2em] ml-1">Trước đó / Đã xem</h2>
          
          <div class="flex flex-col gap-2">
            <div v-for="(item, idx) in notifications" :key="idx" 
              class="group bg-white rounded-lg border border-[var(--sys-border-subtle)] shadow-sm cursor-pointer hover:bg-[var(--sys-bg-hover)] transition-all">
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
import { ref } from 'vue';

const searchQuery = ref('');
const activeFilter = ref('Tất cả');

const notifications = [
  { title: 'Thông báo họp tổng kết quý 4', desc: 'Tất cả nhân viên tham gia buổi họp tổng kết quý 3 và định hướng quý 4 vào 09:00 sáng Thứ Hai.', icon: 'campaign', time: 'Hôm qua, 14:30', tag: 'Sự kiện', color: 'brand' },
  { title: 'Yêu cầu cập nhật mật khẩu Portal', desc: 'Mật khẩu của bạn sắp hết hạn. Vui lòng thay đổi để đảm bảo tính bảo mật của tài khoản.', icon: 'security', time: '2 ngày trước', tag: 'Hệ thống', color: 'warning' },
  { title: 'Chúc mừng sinh nhật thành viên mới', desc: 'Chúc mừng sinh nhật các thành viên tháng 11. Bữa tiệc trà chiều nay lúc 16:00.', icon: 'cake', time: '1 tuần trước', tag: 'Văn hóa', color: 'danger' }
];
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
