<template>
  <div class="resignation-page min-h-screen bg-[var(--sys-bg-page)] text-[var(--sys-text-primary)] p-4 md:p-6 lg:p-8">
    <div class="max-w-5xl mx-auto space-y-6 bg-transparent">
      
      <!-- Top Header -->
      <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 bg-transparent text-left">
        <div class="bg-transparent text-left">
          <div class="flex items-center gap-3 mb-1">
            <button @click="$router.push(profileRoute)" class="w-8 h-8 flex items-center justify-center rounded-md hover:bg-[var(--sys-bg-hover)] text-[var(--sys-text-secondary)] transition-all">
              <span class="material-symbols-outlined text-[20px]">arrow_back</span>
            </button>
            <h1 class="text-xl font-semibold text-[var(--sys-text-primary)] tracking-tight m-0">Đơn xin thôi việc & Bàn giao</h1>
          </div>
          <p class="text-[13px] text-[var(--sys-text-secondary)] ml-11">Chúng tôi rất tiếc khi thấy bạn rời đi. Vui lòng hoàn thành các thông tin bên dưới để bắt đầu quy trình bàn giao.</p>
        </div>
      </div>

      <!-- User Context Info -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="bg-white p-4 rounded-lg border border-[var(--sys-border-subtle)] flex items-center gap-4 shadow-sm">
          <div class="w-10 h-10 rounded-md bg-[var(--sys-brand-soft)] text-[var(--sys-brand-solid)] flex items-center justify-center border border-[var(--sys-brand-border)]">
            <span class="material-symbols-outlined text-[20px]">badge</span>
          </div>
          <div>
            <p class="text-[10px] font-bold text-[var(--sys-text-secondary)] uppercase tracking-wide opacity-60">Chức danh hiện tại</p>
            <p class="text-[13px] font-bold text-[var(--sys-text-primary)] mt-0.5">{{ positionName || 'Chưa cập nhật' }}</p>
          </div>
        </div>

        <div class="bg-white p-4 rounded-lg border border-[var(--sys-border-subtle)] flex items-center gap-4 shadow-sm">
          <div class="w-10 h-10 rounded-md bg-[var(--sys-brand-soft)] text-[var(--sys-brand-solid)] flex items-center justify-center border border-[var(--sys-brand-border)]">
            <span class="material-symbols-outlined text-[20px]">domain</span>
          </div>
          <div>
            <p class="text-[10px] font-bold text-[var(--sys-text-secondary)] uppercase tracking-wide opacity-60">Phòng ban quản lý</p>
            <p class="text-[13px] font-bold text-[var(--sys-text-primary)] mt-0.5">{{ deptName || 'Chưa cập nhật' }}</p>
          </div>
        </div>
      </div>

      <!-- Status Banner -->
      <div class="p-3 rounded-md border flex justify-between items-center shadow-sm" :class="statusBannerClass">
        <div class="flex items-center gap-3">
          <span class="material-symbols-outlined text-[20px]">{{ statusBannerIcon }}</span>
          <div>
            <p class="text-[12px] font-bold m-0">Trạng thái hồ sơ thôi việc hiện tại</p>
            <p class="text-[11px] opacity-70 m-0">{{ statusBannerDesc }}</p>
          </div>
        </div>
        <span class="px-2.5 py-0.5 rounded-md text-white text-[10px] font-bold uppercase tracking-wider" :class="statusBadgeClass">
          {{ statusBannerLabel }}
        </span>
      </div>

      <!-- Main Form Content -->
      <div class="bg-white rounded-lg border border-[var(--sys-border-subtle)] shadow-sm overflow-hidden flex flex-col lg:flex-row items-stretch">
        
        <!-- Form Column -->
        <div class="flex-1 p-6 md:p-8 space-y-6 border-b lg:border-b-0 lg:border-r border-[var(--sys-border-subtle)]">
          <h2 class="text-[14px] font-bold text-[var(--sys-text-primary)] uppercase tracking-wide flex items-center gap-2 mb-6">
            <span class="w-1.5 h-4 bg-[var(--sys-brand-solid)] rounded-full"></span>
            Thông tin đề xuất thôi việc
          </h2>

          <div class="space-y-5">
            <div>
              <label class="block text-[11px] font-bold text-[var(--sys-text-primary)] uppercase tracking-wider mb-2">Ngày làm việc cuối cùng <span class="text-[var(--sys-danger-solid)]">*</span></label>
              <CalendarCustom v-model="finalDate" placeholder="Chọn ngày cuối công tác" />
              <p class="text-[11px] text-[var(--sys-text-disabled)] mt-2 italic font-medium">Báo trước tối thiểu 30 ngày (Dựa trên HĐLĐ hiện tại).</p>
            </div>

            <div>
              <label class="block text-[11px] font-bold text-[var(--sys-text-primary)] uppercase tracking-wider mb-2">Lý do chính <span class="text-[var(--sys-danger-solid)]">*</span></label>
              <select v-model="reasonType" class="w-full h-10 px-3 bg-[var(--sys-bg-page)] border border-[var(--sys-border-strong)] rounded-md text-[13px] font-semibold text-[var(--sys-text-primary)] outline-none focus:border-[var(--sys-brand-solid)] shadow-sm">
                <option value="" disabled>-- Vui lòng chọn lý do --</option>
                <option
                  v-for="item in reasonOptions"
                  :key="`reason-${item.value}`"
                  :value="item.value"
                >
                  {{ item.label }}
                </option>
              </select>
            </div>

            <div>
              <label class="block text-[11px] font-bold text-[var(--sys-text-primary)] uppercase tracking-wider mb-2">Ghi chú & Chia sẻ (không bắt buộc)</label>
              <textarea v-model="detailNote" class="w-full h-32 p-3 bg-[var(--sys-bg-page)] border border-[var(--sys-border-strong)] rounded-md text-[13px] font-medium text-[var(--sys-text-primary)] outline-none focus:border-[var(--sys-brand-solid)] shadow-sm resize-none" placeholder="Cảm nhận của bạn về môi trường làm việc hoặc lý do chi tiết hơn..."></textarea>
            </div>

            <div class="p-4 bg-[var(--sys-bg-page)] rounded-md border border-[var(--sys-border-subtle)] flex items-start gap-3">
              <input v-model="commitment" type="checkbox" id="commitCheck" class="mt-1 w-4 h-4 rounded border-[var(--sys-border-strong)] text-[var(--sys-brand-solid)] focus:ring-[var(--sys-brand-solid)]">
              <label for="commitCheck" class="text-[12px] font-medium text-[var(--sys-text-secondary)] leading-relaxed">
                Tôi cam kết sẽ thực hiện đầy đủ các nhiệm vụ được giao và bàn giao toàn bộ tài sản, tài liệu trước ngày rời đi chính thức.
              </label>
            </div>
          </div>
        </div>

        <!-- Policy Column -->
        <div class="w-full lg:w-[320px] bg-[var(--sys-bg-page)]/50 p-6 md:p-8 flex flex-col">
          <h3 class="text-[12px] font-bold text-[var(--sys-text-primary)] uppercase tracking-wider flex items-center gap-2 mb-6">
            <span class="material-symbols-outlined text-[var(--sys-brand-solid)] text-[20px]">policy</span>
            Quy trình & Chính sách
          </h3>

          <div class="space-y-6 flex-grow">
            <div class="relative pl-6 pb-6">
              <div class="absolute left-0 top-1.5 w-3 h-3 rounded-full bg-[var(--sys-brand-solid)] shadow-sm"></div>
              <div class="absolute left-1.5 top-4 w-[1px] h-full bg-[var(--sys-border-strong)]"></div>
              <h4 class="text-[12px] font-bold text-[var(--sys-text-primary)] mb-1">Thời hạn báo trước</h4>
              <p class="text-[11px] text-[var(--sys-text-secondary)] font-medium leading-relaxed opacity-80">
                Ít nhất 30 ngày đối với HĐ xác định thời hạn hoặc 45 ngày đối với HĐ không xác định thời hạn.
              </p>
            </div>

            <div class="relative pl-6 pb-6">
              <div class="absolute left-0 top-1.5 w-3 h-3 rounded-full bg-[var(--sys-brand-solid)] shadow-sm"></div>
              <div class="absolute left-1.5 top-4 w-[1px] h-full bg-[var(--sys-border-strong)]"></div>
              <h4 class="text-[12px] font-bold text-[var(--sys-text-primary)] mb-1">Bàn giao tài sản</h4>
              <p class="text-[11px] text-[var(--sys-text-secondary)] font-medium leading-relaxed opacity-80">
                Hoàn tất bàn giao thiết bị (laptop, thẻ), tài khoản và hồ sơ công việc cho quản lý trực tiếp.
              </p>
            </div>

            <div class="relative pl-6 pb-0">
              <div class="absolute left-0 top-1.5 w-3 h-3 rounded-full bg-[var(--sys-brand-solid)] shadow-sm"></div>
              <h4 class="text-[12px] font-bold text-[var(--sys-text-primary)] mb-1">Quyết toán nhân sự</h4>
              <p class="text-[11px] text-[var(--sys-text-secondary)] font-medium leading-relaxed opacity-80">
                Chi trả lương, trợ cấp và chốt sổ BHXH trong vòng 7-14 ngày kể từ ngày chấm dứt HĐLĐ.
              </p>
            </div>
          </div>

          <div class="mt-10 pt-6 border-t border-[var(--sys-border-subtle)] text-center">
            <p class="text-[11px] font-bold text-[var(--sys-text-disabled)] uppercase tracking-tight mb-2">Hỗ trợ nhanh</p>
            <a href="mailto:hr_congty@anhsinhvienfpoly.click" class="text-[var(--sys-brand-solid)] font-bold text-[11px] hover:opacity-80 transition-opacity uppercase tracking-wide">BP. Hành chính Nhân sự</a>
          </div>
        </div>
      </div>

      <!-- Action Footer -->
      <div class="flex flex-col sm:flex-row justify-between items-center gap-4 pt-4">
        <button class="flex items-center gap-2 text-[12px] font-bold text-[var(--sys-brand-solid)] hover:opacity-80 transition-all uppercase tracking-wide">
          <span class="material-symbols-outlined text-[20px]">attach_file</span>
          Đính kèm tài liệu liên quan
        </button>
        
        <div class="flex gap-3 w-full sm:w-auto">
          <button @click="$router.push(profileRoute)" class="h-11 px-6 bg-white text-[var(--sys-text-secondary)] border border-[var(--sys-border-strong)] rounded-md font-bold text-[12px] uppercase tracking-wide hover:bg-[var(--sys-bg-page)] shadow-sm transition-all flex-grow sm:flex-grow-0">
            Hủy bỏ
          </button>
          <button
            @click="submitResignationRequest"
            :disabled="isSubmitting"
            class="h-11 px-8 bg-[var(--sys-brand-solid)] text-white rounded-md font-bold text-[12px] uppercase tracking-wide hover:brightness-95 shadow-md flex items-center justify-center gap-2 transition-all flex-grow sm:flex-grow-0 disabled:opacity-60 disabled:cursor-not-allowed"
          >
            {{ isSubmitting ? 'Đang gửi...' : 'Gửi yêu cầu' }} <span class="material-symbols-outlined text-[18px]">send</span>
          </button>
        </div>
      </div>

    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import CalendarCustom from '@/components/CalendarCustom.vue';
import { BE_API_BASE, getAccessToken } from '@/services/runtimeConfig.js';
import { useCurrentUser } from '@/composables/useCurrentUser.js';
import { useConfirm } from '@/composables/useConfirm.js';
import { getCurrentUserRole } from '@/services/session.js';
import { parseJsonResponseSafely } from '@/utils/textEncodingFixed.js';

const finalDate = ref('');
const reasonType = ref('');
const detailNote = ref('');
const commitment = ref(false);
const isSubmitting = ref(false);
const reasonOptions = ref([
  { value: 'career_change', label: 'Thay đổi định hướng cá nhân' },
  { value: 'family_health', label: 'Việc gia đình / Sức khỏe' },
  { value: 'relocation', label: 'Chuyển nơi cư trú xa' },
  { value: 'other_confidential', label: 'Lý do bảo mật khác' },
]);
const resignationRequestTypeId = ref(null);
const latestRequest = ref(null);

const { showAlert } = useConfirm();
const { employeeId, deptName, positionName } = useCurrentUser();
const currentRole = getCurrentUserRole();
const profileRoute = computed(() => (['admin', 'hr'].includes(currentRole) ? '/admin/hoso' : '/nhanvien/hoso'));

const statusBannerLabel = computed(() => {
  const status = String(latestRequest.value?.status || '').toUpperCase();
  if (status === 'ĐÃ_DUYỆT') return 'Đã duyệt';
  if (status === 'TỪ_CHỐI') return 'Từ chối';
  if (status === 'CHỜ_DUYỆT') return 'Chờ duyệt';
  return 'Đang chờ tạo mới';
});

const statusBannerDesc = computed(() => {
  if (!latestRequest.value) return 'Hệ thống chưa ghi nhận đơn đang được xử lý';
  const rawDate = String(latestRequest.value?.request_date || '').trim();
  if (!rawDate) return `Đơn gần nhất đang ở trạng thái ${statusBannerLabel.value.toLowerCase()}.`;
  return `Đơn gần nhất tạo ngày ${rawDate} — trạng thái ${statusBannerLabel.value.toLowerCase()}.`;
});

const statusBannerClass = computed(() => {
  const status = String(latestRequest.value?.status || '').toUpperCase();
  if (status === 'ĐÃ_DUYỆT') return 'bg-[var(--sys-success-soft)] border-[var(--sys-success-border)] text-[var(--sys-success-text)]';
  if (status === 'TỪ_CHỐI') return 'bg-[var(--sys-danger-soft)] border-[var(--sys-danger-border)] text-[var(--sys-danger-text)]';
  return 'bg-[var(--sys-warning-soft)] border-[var(--sys-warning-border)] text-[var(--sys-warning-text)]';
});

const statusBadgeClass = computed(() => {
  const status = String(latestRequest.value?.status || '').toUpperCase();
  if (status === 'ĐÃ_DUYỆT') return 'bg-[var(--sys-success-text)]';
  if (status === 'TỪ_CHỐI') return 'bg-[var(--sys-danger-text)]';
  return 'bg-[var(--sys-warning-text)]';
});

const statusBannerIcon = computed(() => {
  const status = String(latestRequest.value?.status || '').toUpperCase();
  if (status === 'ĐÃ_DUYỆT') return 'verified';
  if (status === 'TỪ_CHỐI') return 'cancel';
  return 'history_edu';
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
  const payload = await parseJsonResponseSafely(response);
  if (!response.ok || payload?.success === false) {
    throw new Error(payload?.message || 'Không thể xử lý yêu cầu.');
  }
  return payload?.data;
};

const toReasonLabel = (value) => {
  const found = reasonOptions.value.find((item) => item.value === value);
  return found?.label || 'Lý do khác';
};

const loadRequestTypes = async () => {
  const data = await apiJson('/request-types?page=1&per_page=300');
  const items = Array.isArray(data) ? data : [];
  const resignationTypes = items.filter((item) => {
    const name = String(item.request_type_name || item.requestTypeName || '').toLowerCase();
    return name.includes('thôi việc') || name.includes('nghỉ việc') || name.includes('nghi viec');
  });
  if (resignationTypes.length > 0) {
    const typeId = Number(resignationTypes[0].request_type_id || resignationTypes[0].requestTypeId || 0);
    resignationRequestTypeId.value = Number.isFinite(typeId) && typeId > 0 ? typeId : null;
  }
};

const loadLatestResignationRequest = async () => {
  if (!employeeId.value) return;
  const data = await apiJson(`/requests?page=1&per_page=100&requester_id=${employeeId.value}`);
  const items = Array.isArray(data) ? data : [];
  const filtered = items.filter((item) => {
    const reason = String(item.reason || '').toLowerCase();
    const notes = String(item.notes || '').toLowerCase();
    if (resignationRequestTypeId.value) {
      return Number(item.request_type_id || 0) === Number(resignationRequestTypeId.value);
    }
    return reason.includes('thôi việc') || reason.includes('nghỉ việc') || notes.includes('thôi việc') || notes.includes('nghỉ việc');
  });
  filtered.sort((a, b) => new Date(b.request_date || 0) - new Date(a.request_date || 0));
  latestRequest.value = filtered[0] || null;
};

const submitResignationRequest = async () => {
  if (!employeeId.value) {
    await showAlert('Thiếu thông tin', 'Không xác định được nhân viên hiện tại. Vui lòng đăng nhập lại.');
    return;
  }
  if (!finalDate.value) {
    await showAlert('Thiếu thông tin', 'Vui lòng chọn ngày làm việc cuối cùng.');
    return;
  }
  if (!reasonType.value) {
    await showAlert('Thiếu thông tin', 'Vui lòng chọn lý do chính.');
    return;
  }
  if (!commitment.value) {
    await showAlert('Thiếu cam kết', 'Vui lòng xác nhận cam kết bàn giao trước khi gửi.');
    return;
  }

  isSubmitting.value = true;
  try {
    const today = new Date().toISOString().slice(0, 10);
    const reasonLabel = toReasonLabel(reasonType.value);
    const notes = [detailNote.value?.trim(), `Phòng ban: ${deptName.value || 'N/A'}`, `Chức danh: ${positionName.value || 'N/A'}`]
      .filter((entry) => String(entry || '').trim() !== '')
      .join(' | ');

    await apiJson('/requests', {
      method: 'POST',
      body: JSON.stringify({
        request_type_id: resignationRequestTypeId.value || 9,
        requester_id: Number(employeeId.value),
        request_date: today,
        from_date: finalDate.value,
        to_date: finalDate.value,
        duration: 1,
        reason: `Đơn thôi việc - ${reasonLabel}`,
        notes,
        status: 'CHỜ_DUYỆT',
      }),
    });

    await showAlert('Đã gửi thành công', 'Đơn thôi việc đã được gửi tới HR để xét duyệt.');
    detailNote.value = '';
    reasonType.value = '';
    commitment.value = false;
    await loadLatestResignationRequest();
  } catch (error) {
    await showAlert('Gửi thất bại', error?.message || 'Không thể gửi đơn thôi việc. Vui lòng thử lại.');
  } finally {
    isSubmitting.value = false;
  }
};

onMounted(async () => {
  try {
    await loadRequestTypes();
    await loadLatestResignationRequest();
  } catch (error) {
    console.warn('[don-nghi-viec] init failed:', error?.message || error);
  }
});
/**
 * TRANG ĐƠN XIN THÔI VIỆC (PORTAL) - PHIÊN BẢN ENTERPRISE SaaS
 * Tuân thủ 7 Golden Rules:
 * - Font Inter 14px (text-sm)
 * - Bo góc chuẩn B2B: 6px (MD), 8px (LG)
 * - Hệ màu Semantic đồng bộ, xóa bỏ font-black/italic rườm rà
 */
</script>

<style scoped>
.resignation-page {
  background-color: var(--sys-bg-page);
}
.material-symbols-outlined {
  font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
}
</style>
