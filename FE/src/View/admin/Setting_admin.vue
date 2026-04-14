<template>
  <div class="space-y-6 pb-8 max-w-6xl mx-auto">
    <!-- Header Area -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 bg-transparent text-left">
      <div class="bg-transparent text-left">
        <h1 class="text-xl font-semibold text-[var(--sys-text-primary)] mb-1">Cấu hình Hệ thống</h1>
        <p class="text-sm text-[var(--sys-text-secondary)]">Quản lý tham số vận hành, phân quyền và thiết lập bảo mật toàn hệ thống.</p>
      </div>
      <div class="flex items-center gap-3 bg-transparent shrink-0">
        <button 
          @click="saveAllSettings"
          class="h-10 px-6 bg-[var(--sys-brand-solid)] text-white rounded-md font-semibold text-sm hover:brightness-90 transition-all flex items-center gap-2 shadow-sm"
        >
          <span class="material-symbols-outlined text-[20px]">save</span>
          Lưu cấu hình
        </button>
      </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
      <!-- Sidebar Navigation -->
      <div class="lg:col-span-3 space-y-1 bg-transparent">
        <button 
          v-for="tab in tabs" 
          :key="tab.id"
          @click="activeTab = tab.id"
          :class="[
            'w-full flex items-center gap-3 px-4 py-2.5 rounded-md font-semibold text-[13px] transition-all',
            activeTab === tab.id ? 'bg-[var(--sys-brand-soft)] text-[var(--sys-brand-solid)] shadow-sm' : 'text-[var(--sys-text-secondary)] hover:bg-[var(--sys-bg-hover)]'
          ]"
        >
          <span class="material-symbols-outlined text-[20px]">{{ tab.icon }}</span>
          {{ tab.label }}
        </button>
      </div>

      <!-- Main Content Area -->
      <div class="lg:col-span-9 bg-transparent">
        <div class="bg-[var(--sys-bg-surface)] rounded-lg border border-[var(--sys-border-subtle)] shadow-sm overflow-hidden p-6 md:p-8">
          
          <!-- General Tab -->
          <div v-if="activeTab === 'general'" class="space-y-8 animate-fadeIn">
            <div class="bg-transparent text-left">
              <h3 class="text-base font-semibold text-[var(--sys-text-primary)] flex items-center gap-2 mb-6 uppercase tracking-wide">
                <span class="w-1.5 h-6 bg-[var(--sys-brand-solid)] rounded-full"></span>
                Định danh Tổ chức
              </h3>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6 bg-transparent">
                <div class="space-y-1.5 bg-transparent text-left">
                  <label class="text-[13px] font-medium text-[var(--sys-text-primary)] block">Tên doanh nghiệp chính thức</label>
                  <input v-model="settings.companyName" type="text" class="w-full h-10 px-3 bg-[var(--sys-bg-page)] border border-[var(--sys-border-strong)] rounded-md text-sm text-[var(--sys-text-primary)] focus:border-[var(--sys-brand-solid)] outline-none transition-all">
                </div>
                <div class="space-y-1.5 bg-transparent text-left">
                  <label class="text-[13px] font-medium text-[var(--sys-text-primary)] block">Mã số thuế</label>
                  <input v-model="settings.taxCode" type="text" class="w-full h-10 px-3 bg-[var(--sys-bg-page)] border border-[var(--sys-border-strong)] rounded-md text-sm text-[var(--sys-text-primary)] focus:border-[var(--sys-brand-solid)] outline-none transition-all">
                </div>
                <div class="md:col-span-2 space-y-1.5 bg-transparent text-left">
                  <label class="text-[13px] font-medium text-[var(--sys-text-primary)] block">Địa chỉ trụ sở điều hành</label>
                  <input v-model="settings.address" type="text" class="w-full h-10 px-3 bg-[var(--sys-bg-page)] border border-[var(--sys-border-strong)] rounded-md text-sm text-[var(--sys-text-primary)] focus:border-[var(--sys-brand-solid)] outline-none transition-all">
                </div>
              </div>
            </div>

            <div class="h-px bg-[var(--sys-border-subtle)]"></div>

            <div class="bg-transparent text-left">
              <h3 class="text-base font-semibold text-[var(--sys-text-primary)] flex items-center gap-2 mb-6 uppercase tracking-wide">
                <span class="w-1.5 h-6 bg-[var(--sys-success-solid)] rounded-full"></span>
                Ngôn ngữ & Vùng miền
              </h3>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6 bg-transparent">
                <div class="space-y-1.5 bg-transparent text-left">
                  <label class="text-[13px] font-medium text-[var(--sys-text-primary)] block">Ngôn ngữ mặc định</label>
                  <select v-model="settings.language" class="w-full h-10 px-3 bg-[var(--sys-bg-page)] border border-[var(--sys-border-strong)] rounded-md text-sm text-[var(--sys-text-primary)] focus:border-[var(--sys-brand-solid)] outline-none transition-all">
                    <option value="vi">Tiếng Việt (Vietnam)</option>
                    <option value="en">English (United States)</option>
                  </select>
                </div>
                <div class="space-y-1.5 bg-transparent text-left">
                  <label class="text-[13px] font-medium text-[var(--sys-text-primary)] block">Múi giờ vận hành</label>
                  <select v-model="settings.timezone" class="w-full h-10 px-3 bg-[var(--sys-bg-page)] border border-[var(--sys-border-strong)] rounded-md text-sm text-[var(--sys-text-primary)] focus:border-[var(--sys-brand-solid)] outline-none transition-all">
                    <option value="Asia/Ho_Chi_Minh">(GMT+07:00) Hanoi</option>
                    <option value="UTC">(GMT+00:00) UTC</option>
                  </select>
                </div>
              </div>
            </div>
          </div>

          <!-- Notification Tab -->
          <div v-if="activeTab === 'notifications'" class="space-y-6 animate-fadeIn">
            <h3 class="text-base font-semibold text-[var(--sys-text-primary)] mb-6 text-left uppercase tracking-wide">Tùy chọn Kênh thông tin</h3>
            
            <div class="space-y-4">
              <div v-for="notify in notificationOptions" :key="notify.id" class="flex items-center justify-between p-4 rounded-lg border border-[var(--sys-border-subtle)] bg-[var(--sys-bg-page)]/50 hover:bg-white transition-all group">
                <div class="flex items-center gap-4 bg-transparent text-left">
                  <div :class="['w-10 h-10 rounded-md flex items-center justify-center border shadow-sm', notify.bg, notify.color]">
                    <span class="material-symbols-outlined text-[20px]">{{ notify.icon }}</span>
                  </div>
                  <div class="bg-transparent text-left">
                    <p class="text-[14px] font-semibold text-[var(--sys-text-primary)] mb-0.5">{{ notify.label }}</p>
                    <p class="text-[12px] text-[var(--sys-text-secondary)]">{{ notify.desc }}</p>
                  </div>
                </div>
                <label class="relative inline-flex items-center cursor-pointer">
                  <input type="checkbox" v-model="notify.enabled" class="sr-only peer">
                  <div class="w-11 h-6 bg-[var(--sys-bg-page)] border border-[var(--sys-border-subtle)] rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border border-gray-300 after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[var(--sys-brand-solid)]"></div>
                </label>
              </div>
            </div>
          </div>

          <!-- Security Tab -->
          <div v-if="activeTab === 'security'" class="space-y-8 animate-fadeIn">
            <div class="p-6 bg-[var(--sys-warning-soft)] rounded-lg border border-[var(--sys-warning-border)] flex items-start gap-4 shadow-sm">
              <div class="w-10 h-10 bg-[var(--sys-warning-solid)] text-white rounded-md flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-[24px]">verified_user</span>
              </div>
              <div class="bg-transparent text-left">
                <h4 class="text-[14px] font-bold text-[var(--sys-warning-text)] mb-1">Giao thức Bảo mật Phê chuẩn</h4>
                <p class="text-[12px] text-[var(--sys-warning-text)] leading-relaxed font-medium">Thiết lập các quy trình xác thực, kiến trúc mã hóa và định danh an toàn cho toàn bộ hệ thống dữ liệu nhân sự.</p>
              </div>
            </div>

            <div class="space-y-6">
              <div class="flex items-center justify-between p-4 bg-[var(--sys-bg-page)] border border-[var(--sys-border-subtle)] rounded-lg hover:bg-white transition-all shadow-sm">
                <div class="bg-transparent text-left">
                  <p class="text-[14px] font-semibold text-[var(--sys-text-primary)] mb-1">Xác thực 2 yếu tố (2FA)</p>
                  <p class="text-[12px] text-[var(--sys-text-secondary)]">Yêu cầu bắt buộc đối với cấp độ quản trị viên</p>
                </div>
                <label class="relative inline-flex items-center cursor-pointer">
                  <input type="checkbox" v-model="settings.require2FA" class="sr-only peer">
                  <div class="w-11 h-6 bg-[var(--sys-bg-page)] border border-[var(--sys-border-subtle)] rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[var(--sys-brand-solid)]"></div>
                </label>
              </div>

              <div class="space-y-1.5 bg-transparent text-left">
                <label class="text-[13px] font-medium text-[var(--sys-text-primary)] block ml-1">Thời gian tự động khóa phiên (Session Timeout)</label>
                <select v-model="settings.sessionTimeout" class="w-full h-10 px-3 bg-[var(--sys-bg-page)] border border-[var(--sys-border-subtle)] rounded-md text-[13px] font-semibold text-[var(--sys-text-primary)] focus:border-[var(--sys-brand-solid)] outline-none">
                  <option value="30">Ngưng hoạt động sau 30 phút (Đề xuất)</option>
                  <option value="60">Ngưng hoạt động sau 60 phút</option>
                  <option value="120">Ngưng hoạt động sau 120 phút</option>
                  <option value="0">Duy trì kết nối vĩnh viễn</option>
                </select>
              </div>
            </div>
          </div>

          <!-- Backup Tab -->
          <div v-if="activeTab === 'backup'" class="space-y-8 animate-fadeIn py-4">
            <div class="flex flex-col items-center justify-center text-center space-y-4">
              <div class="w-16 h-16 bg-[var(--sys-brand-soft)] text-[var(--sys-brand-solid)] rounded-lg flex items-center justify-center shadow-sm border border-[var(--sys-brand-border)]">
                <span class="material-symbols-outlined text-[32px]">cloud_sync</span>
              </div>
              <div class="bg-transparent text-center max-w-lg space-y-2">
                <h3 class="text-xl font-bold text-[var(--sys-text-primary)]">Lưu ký & Phục hồi dữ liệu</h3>
                <p class="text-sm text-[var(--sys-text-secondary)]">Đảm bảo an toàn tuyệt đối cho cơ sở dữ liệu nhân sự bằng hệ thống sao lưu định kỳ.</p>
              </div>
            </div>

            <div class="flex flex-col sm:flex-row items-center justify-center gap-3 bg-transparent">
              <button class="h-10 px-8 bg-[var(--sys-brand-solid)] text-white rounded-md font-bold text-sm hover:brightness-90 shadow-sm transition-all flex items-center gap-2 uppercase tracking-wide">
                <span class="material-symbols-outlined text-[20px]">backup</span>
                Tạo Snapshot
              </button>
              <button class="h-10 px-8 bg-white border border-[var(--sys-border-strong)] text-[var(--sys-text-primary)] rounded-md font-bold text-sm hover:border-[var(--sys-brand-solid)] transition-all flex items-center gap-2 uppercase tracking-wide shadow-sm">
                <span class="material-symbols-outlined text-[20px]">history</span>
                Chọn điểm khôi phục
              </button>
            </div>

            <div class="flex flex-col items-center gap-2 bg-transparent pt-4">
              <div class="inline-flex items-center gap-2 px-3 py-1.5 bg-[var(--sys-bg-page)] rounded-md border border-[var(--sys-border-subtle)] shadow-inner">
                <span class="w-2 h-2 rounded-full bg-[var(--sys-success-solid)] animate-pulse"></span>
                <p class="text-[12px] text-[var(--sys-text-secondary)] font-medium">Lần lưu ký cuối: 14/03/2026 • 02:30 AM</p>
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
 * TRANG CẤU HÌNH HỆ THỐNG - PHIÊN BẢN ENTERPRISE SaaS
 * Tuân thủ 7 Golden Rules:
 * - Font Inter 14px (text-sm), Tỉ lệ Sidebar/Form cao (text-13px)
 * - Bo góc chuẩn B2B: 6px (MD) cho Input/Button, 8px (LG) cho Card/Thẻ
 * - Hệ màu Blue/White đồng nhất cho Action Icons
 */
import { ref } from 'vue';
import { useConfirm } from '@/composables/useConfirm';

const { showAlert } = useConfirm();

const activeTab = ref('general');

const tabs = [
  { id: 'general', label: 'Cấu hình tổng quan', icon: 'settings' },
  { id: 'notifications', label: 'Luồng thông tin', icon: 'notifications_active' },
  { id: 'security', label: 'Tường lửa & Bảo mật', icon: 'security' },
  { id: 'backup', label: 'Lưu ký dữ liệu', icon: 'cloud_sync' },
];

const settings = ref({
  companyName: 'Tập đoàn Công nghệ & Giải pháp Nhân sự HRM Global',
  taxCode: '01099887766',
  address: 'Complex Building, 456 Trần Duy Hưng, Cầu Giấy, Hà Nội',
  language: 'vi',
  timezone: 'Asia/Ho_Chi_Minh',
  require2FA: true,
  sessionTimeout: '60'
});

const notificationOptions = ref([
  { id: 1, label: 'Email thông báo', desc: 'Phê duyệt lương, thông báo nội bộ qua hệ thống Mailing', icon: 'mail', bg: 'bg-[var(--sys-brand-soft)]', color: 'text-[var(--sys-brand-solid)]', enabled: true },
  { id: 2, label: 'Thông báo Real-time', desc: 'Xử lý đơn từ và nhắc lịch làm việc tức thời qua Web Push', icon: 'bolt', bg: 'bg-[var(--sys-success-soft)]', color: 'text-[var(--sys-success-text)]', enabled: true },
  { id: 3, label: 'SMS Gateway', desc: 'Xác thực OTP giao dịch và cảnh báo xâm nhập an ninh', icon: 'sms', bg: 'bg-[var(--sys-warning-soft)]', color: 'text-[var(--sys-warning-text)]', enabled: false },
]);

const saveAllSettings = async () => {
  await showAlert('Phê chuẩn Cấu hình', 'Toàn bộ tham số vận hành hệ thống đã được cập nhật thành công!');
};
</script>

<style scoped>
@keyframes fadeIn {
  from { opacity: 0; transform: translateY(10px); }
  to { opacity: 1; transform: translateY(0); }
}
.animate-fadeIn {
  animation: fadeIn 0.4s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}

.no-scrollbar::-webkit-scrollbar {
  display: none;
}
</style>
