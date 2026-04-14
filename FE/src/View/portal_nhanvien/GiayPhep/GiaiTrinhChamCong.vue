<template>
  <div class="explanation-page min-h-screen bg-[var(--sys-bg-page)] text-[var(--sys-text-primary)] p-4 md:p-6 lg:p-8">
    <div class="max-w-6xl mx-auto space-y-6 bg-transparent">
      
      <!-- Top Header -->
      <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 bg-transparent text-left">
        <div class="bg-transparent text-left">
          <div class="flex items-center gap-3 mb-1">
            <button @click="$router.push('/nhanvien/chamcong')" class="w-8 h-8 flex items-center justify-center rounded-md hover:bg-[var(--sys-bg-hover)] text-[var(--sys-text-secondary)] transition-all">
              <span class="material-symbols-outlined text-[20px]">arrow_back</span>
            </button>
            <h1 class="text-xl font-semibold text-[var(--sys-text-primary)] tracking-tight m-0">Đơn giải trình sai lệch chấm công</h1>
          </div>
          <p class="text-[13px] text-[var(--sys-text-secondary)] ml-11">Vui lòng điền đầy đủ các thông tin bên dưới để gửi yêu cầu điều chỉnh dữ liệu chấm công.</p>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
        
        <!-- Left Column: Main Form -->
        <div class="lg:col-span-8">
          <div class="bg-white p-6 md:p-10 rounded-lg border border-[var(--sys-border-subtle)] shadow-sm">
            <form @submit.prevent="submitExplanationRequest" class="space-y-6 text-left">
              
              <h2 class="text-[14px] font-bold text-[var(--sys-text-primary)] uppercase tracking-wide flex items-center gap-2 mb-8">
                <span class="w-1.5 h-4 bg-[var(--sys-brand-solid)] rounded-full"></span>
                Nội dung đề xuất giải trình
              </h2>

              <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                <div>
                  <label class="block text-[11px] font-bold text-[var(--sys-text-primary)] uppercase tracking-wider mb-2 flex items-center gap-2">
                    <span class="material-symbols-outlined text-[var(--sys-text-disabled)] text-[18px]">calendar_today</span>
                    Ngày giải trình <span class="text-[var(--sys-danger-solid)]">*</span>
                  </label>
                  <input v-model="form.explainDate" type="date" class="w-full h-10 px-3 bg-[var(--sys-bg-page)] border border-[var(--sys-border-strong)] rounded-md text-[13px] font-semibold text-[var(--sys-text-primary)] outline-none focus:border-[var(--sys-brand-solid)] shadow-sm">
                </div>

                <div>
                  <label class="block text-[11px] font-bold text-[var(--sys-text-primary)] uppercase tracking-wider mb-2 flex items-center gap-2">
                    <span class="material-symbols-outlined text-[var(--sys-text-disabled)] text-[18px]">login</span>
                    Giờ vào thực tế
                  </label>
                  <input v-model="form.checkInReal" type="time" class="w-full h-10 px-3 bg-[var(--sys-bg-page)] border border-[var(--sys-border-strong)] rounded-md text-[13px] font-semibold text-[var(--sys-text-primary)] outline-none focus:border-[var(--sys-brand-solid)] shadow-sm">
                </div>

                <div>
                  <label class="block text-[11px] font-bold text-[var(--sys-text-primary)] uppercase tracking-wider mb-2 flex items-center gap-2">
                    <span class="material-symbols-outlined text-[var(--sys-text-disabled)] text-[18px]">logout</span>
                    Giờ ra thực tế
                  </label>
                  <input v-model="form.checkOutReal" type="time" class="w-full h-10 px-3 bg-[var(--sys-bg-page)] border border-[var(--sys-border-strong)] rounded-md text-[13px] font-semibold text-[var(--sys-text-primary)] outline-none focus:border-[var(--sys-brand-solid)] shadow-sm">
                </div>
              </div>

              <div>
                <label class="block text-[11px] font-bold text-[var(--sys-text-primary)] uppercase tracking-wider mb-2 flex items-center gap-2">
                  <span class="material-symbols-outlined text-[var(--sys-text-disabled)] text-[18px]">category</span>
                  Loại giải trình <span class="text-[var(--sys-danger-solid)]">*</span>
                </label>
                <select v-model="form.explainType" class="w-full h-10 px-3 bg-[var(--sys-bg-page)] border border-[var(--sys-border-strong)] rounded-md text-[13px] font-semibold text-[var(--sys-text-primary)] outline-none focus:border-[var(--sys-brand-solid)] shadow-sm">
                  <option value="" disabled>-- Vui lòng chọn loại giải trình --</option>
                  <option value="Quên chấm công (Vào/Ra)">Quên chấm công (Vào/Ra)</option>
                  <option value="Lỗi kết nối từ phía hệ thống">Lỗi kết nối từ phía hệ thống</option>
                  <option value="Đi công tác / Ra ngoài công tác">Đi công tác / Ra ngoài công tác</option>
                  <option value="Hỏng hóc thiết bị đầu cuối">Hỏng hóc thiết bị đầu cuối</option>
                </select>
              </div>

              <div>
                <label class="block text-[11px] font-bold text-[var(--sys-text-primary)] uppercase tracking-wider mb-2 flex items-center gap-2">
                  <span class="material-symbols-outlined text-[var(--sys-text-disabled)] text-[18px]">description</span>
                  Lý do chi tiết <span class="text-[var(--sys-danger-solid)]">*</span>
                </label>
                <textarea v-model="form.reason" class="w-full h-32 p-3 bg-[var(--sys-bg-page)] border border-[var(--sys-border-strong)] rounded-md text-[13px] font-medium text-[var(--sys-text-primary)] outline-none focus:border-[var(--sys-brand-solid)] shadow-sm resize-none" placeholder="Vui lòng mô tả chi tiết nguyên nhân dẫn đến sai lệch dữ liệu..."></textarea>
              </div>

              <div>
                <label class="block text-[11px] font-bold text-[var(--sys-text-primary)] uppercase tracking-wider mb-2 flex items-center gap-2">
                  <span class="material-symbols-outlined text-[var(--sys-text-disabled)] text-[18px]">attach_file</span>
                  Minh chứng đính kèm
                </label>
                <div class="border-2 border-dashed border-[var(--sys-border-subtle)] rounded-lg p-8 flex flex-col items-center justify-center text-center bg-[var(--sys-bg-page)] hover:border-[var(--sys-brand-solid)] hover:bg-[var(--sys-brand-soft)]/20 cursor-pointer transition-all group">
                  <div class="w-12 h-12 bg-[var(--sys-brand-soft)] text-[var(--sys-brand-solid)] rounded-full flex items-center justify-center mb-4 transition-all group-hover:bg-[var(--sys-brand-solid)] group-hover:text-white">
                    <span class="material-symbols-outlined text-[28px]">cloud_upload</span>
                  </div>
                  <p class="text-[13px] font-bold text-[var(--sys-text-primary)] mb-1">
                    Nhấp để tải lên hoặc kéo thả tệp tin vào đây
                  </p>
                  <p class="text-[11px] font-medium text-[var(--sys-text-disabled)] uppercase tracking-wide">
                    JPG, PNG, PDF (Tối đa 5MB)
                  </p>
                </div>
              </div>

              <div class="flex justify-end gap-3 pt-6 border-t border-[var(--sys-border-subtle)]">
                <button type="button" @click="$router.push('/nhanvien/chamcong')" class="h-11 px-6 bg-white text-[var(--sys-text-secondary)] border border-[var(--sys-border-strong)] rounded-md font-bold text-[12px] uppercase tracking-wide hover:bg-[var(--sys-bg-page)] shadow-sm transition-all focus:outline-none">
                  Hủy bỏ
                </button>
                <button type="submit" class="h-11 px-8 bg-[var(--sys-brand-solid)] text-white rounded-md font-bold text-[12px] uppercase tracking-wide hover:brightness-95 shadow-md flex items-center justify-center gap-2 transition-all focus:outline-none">
                  Gửi yêu cầu <span class="material-symbols-outlined text-[18px]">send</span>
                </button>
              </div>
            </form>
          </div>
        </div>

        <!-- Right Column: Context Sidebar -->
        <div class="lg:col-span-4 space-y-4">
          <!-- Recent History Sidebar Card -->
          <div class="bg-white rounded-lg border border-[var(--sys-border-subtle)] shadow-sm overflow-hidden flex flex-col">
            <div class="px-5 py-3 border-b border-[var(--sys-border-subtle)] bg-[var(--sys-bg-page)]/50 flex justify-between items-center">
              <div>
                <h3 class="text-[12px] font-bold text-[var(--sys-text-primary)] uppercase tracking-wide flex items-center gap-2">
                  <span class="material-symbols-outlined text-[var(--sys-brand-solid)] text-[18px]">history</span>
                  Chấm công gần đây
                </h3>
              </div>
              <button type="button" @click="$router.push('/nhanvien/chamcong')" class="text-[var(--sys-brand-solid)] font-bold text-[11px] hover:opacity-80 transition-opacity uppercase tracking-tight">TẤT CẢ</button>
            </div>
            
            <div class="divide-y divide-[var(--sys-border-subtle)]">
              <!-- Unvalid Item -->
              <div
                v-for="row in recentAttendanceRows"
                :key="`attn-row-${row.id}`"
                class="p-4 hover:bg-[var(--sys-bg-hover)] transition-all border-l-4 cursor-pointer"
                :class="row.badgeClass"
              >
                <div class="flex justify-between items-start">
                  <div>
                    <p class="text-[11px] font-bold text-[var(--sys-text-disabled)] uppercase tracking-tight mb-1">{{ row.dateLabel }}</p>
                    <p class="text-[14px] font-bold text-[var(--sys-text-primary)]">{{ row.rangeLabel }}</p>
                  </div>
                  <div class="text-right">
                    <span :class="row.chipClass" class="px-2 py-0.5 rounded-md text-[9px] font-black uppercase tracking-widest border">{{ row.statusLabel }}</span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Important Banner -->
          <div class="p-5 rounded-lg border border-[var(--sys-brand-border)] bg-[var(--sys-bg-page)]">
            <h4 class="text-[12px] font-bold text-[var(--sys-brand-solid)] flex items-center gap-2 mb-3 uppercase tracking-wide">
              <span class="material-symbols-outlined text-[18px]">info</span>
              Nguyên tắc giải trình
            </h4>
            <ul class="space-y-3 m-0 p-0 list-none">
              <li class="flex gap-2 items-start">
                <span class="text-[var(--sys-brand-solid)] mt-0.5 text-[14px]">•</span>
                <span class="text-[12px] font-medium text-[var(--sys-text-secondary)] leading-tight">Yêu cầu cần được gửi trước ngày 25 hàng tháng để tổng hợp lương.</span>
              </li>
              <li class="flex gap-2 items-start">
                <span class="text-[var(--sys-brand-solid)] mt-0.5 text-[14px]">•</span>
                <span class="text-[12px] font-medium text-[var(--sys-text-secondary)] leading-tight">Phải đính kèm minh chứng đối với các trường hợp đi công tác hoặc lỗi máy móc.</span>
              </li>
              <li class="flex gap-2 items-start">
                <span class="text-[var(--sys-danger-solid)] mt-0.5 text-[14px]">•</span>
                <span class="text-[12px] font-bold text-[var(--sys-danger-text)] leading-tight">Mọi giải trình sai lệch cố ý sẽ bị xử lý theo nội quy lao động của công ty.</span>
              </li>
            </ul>
          </div>
        </div>

      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { useConfirm } from '@/composables/useConfirm';
import { useCurrentUser } from '@/composables/useCurrentUser';
import { BE_API_BASE, getAccessToken } from '@/services/runtimeConfig';

const { showAlert } = useConfirm();
const { employeeId } = useCurrentUser();

const form = ref({
  explainDate: new Date().toISOString().slice(0, 10),
  checkInReal: '',
  checkOutReal: '',
  explainType: '',
  reason: '',
});

const attendanceHistory = ref([]);
const recentAttendanceRows = computed(() => {
  const rows = [...attendanceHistory.value]
    .sort((a, b) => new Date(b.attendance_date || b.date || 0) - new Date(a.attendance_date || a.date || 0))
    .slice(0, 3)
    .map((item) => {
      const dateRaw = String(item.attendance_date || item.date || '').slice(0, 10);
      const checkIn = String(item.check_in_time || item.checkIn1 || '').slice(11, 16) || '--:--';
      const checkOut = String(item.check_out_time_2 || item.check_out_time || item.checkOut2 || item.checkOut1 || '').slice(11, 16) || '--:--';
      const status = String(item.status || '').toUpperCase();
      let statusLabel = 'HỢP LỆ';
      let chipClass = 'bg-[var(--sys-success-soft)] text-[var(--sys-success-text)] border-[var(--sys-success-border)]';
      let badgeClass = 'border-l-[var(--sys-success-solid)]';
      if (status.includes('CHỜ')) {
        statusLabel = 'CHỜ DUYỆT';
        chipClass = 'bg-[var(--sys-warning-soft)] text-[var(--sys-warning-text)] border-[var(--sys-warning-border)]';
        badgeClass = 'border-l-[var(--sys-warning-solid)]';
      } else if (status.includes('TỪ_CHỐI')) {
        statusLabel = 'TỪ CHỐI';
        chipClass = 'bg-[var(--sys-danger-soft)] text-[var(--sys-danger-text)] border-[var(--sys-danger-border)]';
        badgeClass = 'border-l-[var(--sys-danger-solid)]';
      }
      return {
        id: item.attendance_id || `${dateRaw}-${checkIn}`,
        dateLabel: dateRaw || '--/--/----',
        rangeLabel: `${checkIn} - ${checkOut}`,
        statusLabel,
        chipClass,
        badgeClass,
      };
    });
  return rows;
});

const apiJson = async (path, options = {}) => {
  const token = getAccessToken();
  if (!token) throw new Error('Phiên đăng nhập đã hết hạn. Vui lòng đăng nhập lại.');
  const response = await fetch(`${BE_API_BASE}${path}`, {
    ...options,
    headers: {
      Authorization: `Bearer ${token}`,
      'Content-Type': 'application/json',
      ...(options.headers || {}),
    },
  });
  const payload = await response.json().catch(() => ({}));
  if (!response.ok || payload?.success === false) {
    throw new Error(payload?.message || 'Không thể xử lý yêu cầu.');
  }
  return payload?.data;
};

const submitExplanationRequest = async () => {
  if (!employeeId.value) {
    await showAlert('Thiếu thông tin', 'Không xác định được nhân viên hiện tại.');
    return;
  }
  if (!form.value.explainDate || !form.value.explainType || !form.value.reason.trim()) {
    await showAlert('Thiếu thông tin', 'Vui lòng nhập đầy đủ ngày, loại giải trình và lý do chi tiết.');
    return;
  }
  try {
    const requestDate = new Date().toISOString().slice(0, 10);
    const noteSegments = [
      `Giờ vào thực tế: ${form.value.checkInReal || '--:--'}`,
      `Giờ ra thực tế: ${form.value.checkOutReal || '--:--'}`,
    ];
    await apiJson('/requests', {
      method: 'POST',
      body: JSON.stringify({
        request_type_id: 11,
        requester_id: Number(employeeId.value),
        request_date: requestDate,
        from_date: form.value.explainDate,
        to_date: form.value.explainDate,
        duration: 1,
        reason: `Giải trình chấm công - ${form.value.explainType}`,
        notes: `${form.value.reason.trim()} | ${noteSegments.join(' | ')}`,
        status: 'CHỜ_DUYỆT',
      }),
    });
    await showAlert('Đã gửi thành công', 'Yêu cầu giải trình đã được gửi tới HR để xét duyệt.');
    form.value.explainType = '';
    form.value.reason = '';
    form.value.checkInReal = '';
    form.value.checkOutReal = '';
  } catch (error) {
    await showAlert('Gửi thất bại', error?.message || 'Không thể gửi yêu cầu giải trình.');
  }
};

const loadAttendanceHistory = async () => {
  if (!employeeId.value) return;
  const data = await apiJson(`/attendances?page=1&per_page=30&employee_id=${employeeId.value}`);
  attendanceHistory.value = Array.isArray(data) ? data : [];
};

onMounted(async () => {
  try {
    await loadAttendanceHistory();
  } catch (error) {
    console.warn('[giai-trinh] init failed:', error?.message || error);
  }
});
/**
 * TRANG GIẢI TRÌNH CHẤM CÔNG (PORTAL) - PHIÊN BẢN ENTERPRISE SaaS
 * Tuân thủ 7 Golden Rules:
 * - Font Inter 14px (text-sm)
 * - Bo góc chuẩn B2B: 6px (MD), 8px (LG)
 * - Hệ màu Semantic đồng bộ, xóa bỏ font-black/italic
 */
</script>

<style scoped>
.explanation-page {
  background-color: var(--sys-bg-page);
}
.material-symbols-outlined {
  font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
}
</style>
