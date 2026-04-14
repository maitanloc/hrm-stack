<template>
  <div class="space-y-6 pb-8 text-left bg-[var(--sys-bg-page)] min-h-screen p-4 md:p-6 lg:p-8">
    
    <!-- Header Area -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 bg-transparent text-left">
      <div class="bg-transparent text-left">
        <h1 class="text-xl font-bold text-[var(--sys-text-primary)] mb-1 uppercase tracking-tight flex items-center gap-3">
          <span class="material-symbols-rounded text-[var(--sys-brand-solid)] text-[24px]">event_available</span>
          Quản lý Nghỉ phép & Vắng mặt
        </h1>
        <p class="text-[13px] text-[var(--sys-text-secondary)] font-medium">Theo dõi quỹ phép năm và đăng ký các yêu cầu nghỉ phép cá nhân.</p>
      </div>
      
      <div class="flex items-center gap-3 bg-transparent">
        <button 
          @click="showModal = true; isSuccess = false" 
          class="h-11 px-6 bg-[var(--sys-brand-solid)] text-white rounded-md font-bold text-[12px] uppercase tracking-wider flex items-center gap-2 hover:brightness-110 active:scale-95 transition-all shadow-lg shadow-[var(--sys-brand-solid)]/10"
        >
          <span class="material-symbols-rounded text-[20px]">add</span>
          Tạo đơn nghỉ phép
        </button>
      </div>
    </div>

    <!-- Stats Cards Grid (Matching Admin/Director style) -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
      <!-- Total Leave Days -->
      <div class="bg-[var(--sys-bg-surface)] p-4 rounded-lg border border-[var(--sys-border-subtle)] shadow-sm hover:border-[var(--sys-brand-solid)] transition-all group flex items-center gap-4">
        <div class="w-10 h-10 rounded-md bg-[var(--sys-brand-soft)] text-[var(--sys-brand-solid)] border border-[var(--sys-brand-border)] flex items-center justify-center shrink-0">
          <span class="material-symbols-rounded text-xl">account_balance_wallet</span>
        </div>
        <div class="bg-transparent flex flex-col overflow-hidden">
          <p class="text-[11px] font-bold text-[var(--sys-text-secondary)] mb-0.5 uppercase tracking-widest opacity-60 truncate">Tổng quỹ phép</p>
          <p class="text-xl font-bold text-[var(--sys-text-primary)] m-0 leading-tight tracking-tight">14 <small class="text-[10px] opacity-40">ngày</small></p>
        </div>
      </div>
      
      <!-- Used Days -->
      <div class="bg-[var(--sys-bg-surface)] p-4 rounded-lg border border-[var(--sys-border-subtle)] shadow-sm hover:border-[var(--sys-warning-border)] transition-all group flex items-center gap-4">
        <div class="w-10 h-10 rounded-md bg-[var(--sys-warning-soft)] text-[var(--sys-warning-text)] border border-[var(--sys-warning-border)] flex items-center justify-center shrink-0">
          <span class="material-symbols-rounded text-xl">event_available</span>
        </div>
        <div class="bg-transparent flex flex-col overflow-hidden">
          <p class="text-[11px] font-bold text-[var(--sys-text-secondary)] mb-0.5 uppercase tracking-widest opacity-60 truncate">Đã sử dụng</p>
          <p class="text-xl font-bold text-[var(--sys-warning-text)] m-0 leading-tight tracking-tight">02 <small class="text-[10px] opacity-40 text-[var(--sys-text-disabled)]">ngày</small></p>
        </div>
      </div>

      <!-- Pending Requests -->
      <div class="bg-[var(--sys-bg-surface)] p-4 rounded-lg border border-[var(--sys-border-subtle)] shadow-sm hover:border-[var(--sys-brand-solid)] transition-all group flex items-center gap-4">
        <div class="w-10 h-10 rounded-md bg-[var(--sys-brand-soft)] text-[var(--sys-brand-solid)] border border-[var(--sys-brand-border)] flex items-center justify-center shrink-0">
          <span class="material-symbols-rounded text-xl">pending_actions</span>
        </div>
        <div class="bg-transparent flex flex-col overflow-hidden">
          <p class="text-[11px] font-bold text-[var(--sys-text-secondary)] mb-0.5 uppercase tracking-widest opacity-60 truncate">Đang chờ duyệt</p>
          <p class="text-xl font-bold text-[var(--sys-brand-solid)] m-0 leading-tight tracking-tight">{{ pendingCount }} <small class="text-[10px] opacity-40 text-[var(--sys-text-disabled)]">đơn</small></p>
        </div>
      </div>

      <!-- Remaining Balance -->
      <div class="bg-[var(--sys-bg-surface)] p-4 rounded-lg border border-[var(--sys-border-subtle)] shadow-sm hover:border-[var(--sys-success-border)] transition-all group flex items-center gap-4 border-l-4 border-l-[var(--sys-success-solid)]">
        <div class="w-10 h-10 rounded-md bg-[var(--sys-success-soft)] text-[var(--sys-success-text)] border border-[var(--sys-success-border)] flex items-center justify-center shrink-0">
          <span class="material-symbols-rounded text-xl">payments</span>
        </div>
        <div class="bg-transparent flex flex-col overflow-hidden">
          <p class="text-[11px] font-bold text-[var(--sys-text-secondary)] mb-0.5 uppercase tracking-widest opacity-60 truncate">Còn lại</p>
          <p class="text-xl font-bold text-[var(--sys-success-text)] m-0 leading-tight tracking-tight">12 <small class="text-[10px] opacity-40 text-[var(--sys-text-disabled)]">ngày</small></p>
        </div>
      </div>
    </div>

    <!-- History Table (Enterprise Layout) -->
    <div class="bg-[var(--sys-bg-surface)] rounded-lg border border-[var(--sys-border-subtle)] shadow-sm overflow-hidden flex flex-col">
      <div class="px-5 py-3 border-b border-[var(--sys-border-subtle)] bg-[var(--sys-bg-page)]/50 flex justify-between items-center h-14">
        <h3 class="text-[13px] font-bold text-[var(--sys-text-primary)] uppercase tracking-widest m-0 flex items-center gap-2 leading-none">
          <span class="material-symbols-rounded text-[var(--sys-brand-solid)] text-[20px]">history</span>
          Lịch sử yêu cầu vắng mặt
        </h3>
        <span class="text-[11px] font-bold text-[var(--sys-text-secondary)] uppercase opacity-50 tracking-tighter">Click đơn CHỜ DUYỆT để xóa</span>
      </div>
      
      <div class="overflow-x-auto custom-scrollbar">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="bg-[var(--sys-bg-page)]">
              <th class="px-6 py-3 text-[11px] font-bold text-[var(--sys-text-secondary)] uppercase tracking-widest border-b border-[var(--sys-border-subtle)]">Loại hình</th>
              <th class="px-6 py-3 text-[11px] font-bold text-[var(--sys-text-secondary)] uppercase tracking-widest border-b border-[var(--sys-border-subtle)]">Thời gian</th>
              <th class="px-6 py-3 text-[11px] font-bold text-[var(--sys-text-secondary)] uppercase tracking-widest border-b border-[var(--sys-border-subtle)] text-center">Tổng ngày</th>
              <th class="px-6 py-3 text-[11px] font-bold text-[var(--sys-text-secondary)] uppercase tracking-widest border-b border-[var(--sys-border-subtle)] text-right">Trạng thái</th>
              <th class="px-6 py-3 text-[11px] font-bold text-[var(--sys-text-secondary)] uppercase tracking-widest border-b border-[var(--sys-border-subtle)] text-right">Tác vụ</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-[var(--sys-border-subtle)]">
            <tr v-for="item in leaveHistory" :key="item.id" class="group hover:bg-[var(--sys-bg-hover)] transition-all">
              <td class="px-6 py-4 whitespace-nowrap bg-transparent">
                <span class="text-[13px] font-bold text-[var(--sys-text-primary)] uppercase tracking-tight group-hover:text-[var(--sys-brand-solid)]">{{ item.type }}</span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap bg-transparent">
                <span class="text-[13px] font-medium text-[var(--sys-text-secondary)] uppercase tracking-tighter">{{ item.duration }}</span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap bg-transparent text-center">
                <span class="text-[13px] font-bold text-[var(--sys-text-primary)]">{{ item.total }}</span>
              </td>
              <td class="px-6 py-4 text-right whitespace-nowrap bg-transparent">
                <span :class="[
                  'px-3 py-1 rounded-md text-[10px] font-bold inline-flex items-center gap-2 border transition-all uppercase tracking-widest shadow-sm',
                  getStatusBadgeClass(item.statusRaw)
                ]">
                  <span class="w-1.5 h-1.5 rounded-full" :class="getStatusDotClass(item.statusRaw)"></span>
                  {{ item.status }}
                </span>
              </td>
              <td class="px-6 py-4 text-right whitespace-nowrap bg-transparent">
                <button 
                  v-if="item.statusRaw === 'CHỜ_DUYỆT'" 
                  @click="handleDeleteRequest(item)"
                  class="w-8 h-8 flex items-center justify-center rounded-md text-[var(--sys-text-secondary)] hover:text-[var(--sys-danger-solid)] hover:bg-[var(--sys-danger-soft)] transition-all border border-transparent hover:border-[var(--sys-danger-border)]"
                >
                  <span class="material-symbols-rounded text-[20px]">delete</span>
                </button>
                <span v-else class="material-symbols-rounded text-[var(--sys-text-disabled)] opacity-20">lock</span>
              </td>
            </tr>
            <tr v-if="leaveHistory.length === 0">
               <td colspan="5" class="px-6 py-20 text-center opacity-40">
                  <span class="material-symbols-rounded text-4xl block mb-2">inbox</span>
                  <p class="text-[12px] font-bold uppercase tracking-widest">Chưa có lịch sử yêu cầu</p>
               </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Modal: Create Request (Matching Admin Form style) -->
    <Teleport to="body">
      <Transition name="modal">
        <div v-if="showModal" class="fixed inset-0 z-[1050] flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm transition-all duration-300">
          <div class="bg-white w-full max-w-xl rounded-lg border border-[var(--sys-border-subtle)] flex flex-col min-h-[600px] max-h-[95vh] shadow-2xl transform transition-all">
            
            <!-- Success State -->
            <div v-if="isSuccess" class="p-12 flex flex-col items-center justify-center text-center space-y-6">
              <div class="w-16 h-16 rounded-full bg-[var(--sys-success-soft)] flex items-center justify-center border-2 border-[var(--sys-success-border)] relative">
                <span class="material-symbols-rounded text-[32px] text-[var(--sys-success-solid)]">check</span>
              </div>
              <div class="bg-transparent text-center">
                <h3 class="text-lg font-bold text-[var(--sys-text-primary)] tracking-tight uppercase">Gửi yêu cầu thành công</h3>
                <p class="text-[13px] text-[var(--sys-text-secondary)] mt-1 font-medium max-w-xs mx-auto">
                  Đơn nghỉ phép của bạn đã được chuyển đến quản lý trực tiếp phê duyệt.
                </p>
              </div>
              <button 
                @click="closeSuccessModal" 
                class="h-10 px-8 bg-[var(--sys-brand-solid)] text-white rounded-md font-bold uppercase tracking-widest text-[11px] hover:brightness-95 transition-all shadow-md shadow-[var(--sys-brand-solid)]/10"
              >
                Đồng ý
              </button>
            </div>

            <!-- Validation Error State -->
            <div v-else-if="isValidationError" class="p-12 flex flex-col items-center justify-center text-center space-y-6">
              <div class="w-16 h-16 rounded-full bg-[var(--sys-danger-soft)] flex items-center justify-center border-2 border-[var(--sys-danger-border)] relative">
                <span class="material-symbols-rounded text-[32px] text-[var(--sys-danger-solid)]">error</span>
              </div>
              <div class="bg-transparent text-center">
                <h3 class="text-lg font-bold text-[var(--sys-text-primary)] tracking-tight uppercase">Thông tin chưa hợp lệ</h3>
                <p class="text-[13px] text-[var(--sys-text-secondary)] mt-1 font-bold max-w-xs mx-auto">
                  {{ validationMessage }}
                </p>
              </div>
              <button 
                @click="isValidationError = false" 
                class="h-10 px-8 bg-[var(--sys-danger-solid)] text-white rounded-md font-bold uppercase tracking-widest text-[11px] hover:brightness-95 transition-all shadow-md shadow-[var(--sys-danger-solid)]/10"
              >
                Quay lại chỉnh sửa
              </button>
            </div>

            <!-- Form State -->
            <template v-else>
              <!-- Modal Header -->
              <div class="px-6 py-4 border-b border-[var(--sys-border-subtle)] flex justify-between items-center bg-[var(--sys-bg-surface)]">
                <div class="flex items-center gap-3 bg-transparent text-left">
                  <div class="w-10 h-10 rounded-md bg-[var(--sys-brand-soft)] text-[var(--sys-brand-solid)] flex items-center justify-center border border-[var(--sys-brand-border)]">
                    <span class="material-symbols-rounded text-[22px]">post_add</span>
                  </div>
                  <div class="bg-transparent text-left">
                    <h3 class="text-[14px] font-bold text-[var(--sys-text-primary)] uppercase tracking-tight m-0 leading-none">Thiết lập đơn nghỉ phép</h3>
                    <p class="text-[10px] text-[var(--sys-text-secondary)] mt-1 font-bold opacity-60 uppercase tracking-tighter">Vui lòng điền đầy đủ thông tin định danh</p>
                  </div>
                </div>
                <button @click="showModal = false" class="w-8 h-8 flex items-center justify-center rounded-md hover:bg-[var(--sys-bg-hover)] text-[var(--sys-text-secondary)] transition-all">
                  <span class="material-symbols-rounded text-[22px]">close</span>
                </button>
              </div>
              
              <!-- Modal Body (Grid 2 columns where needed) -->
              <div class="p-6 space-y-6 overflow-y-auto custom-scrollbar bg-white flex-1 text-left">
                <!-- Row 1: Type -->
                <div class="space-y-1.5 bg-transparent">
                   <label class="block text-[11px] font-bold text-[var(--sys-text-primary)] uppercase tracking-widest ml-1">Loại hình nghỉ phép *</label>
                   <Dropdown v-model="leaveType" :options="leaveTypeOptions" placeholder="-- Chọn loại hình nghỉ --" class="h-11 shadow-sm" />
                </div>

                <!-- Other Leave Type Specification -->
                <div v-if="leaveType === 99" class="space-y-2 animate-in slide-in-from-top-2 duration-300">
                  <label class="block text-[11px] font-bold text-[var(--sys-text-primary)] uppercase tracking-widest ml-1">Ghi rõ loại nghỉ khác *</label>
                  <div class="relative group">
                    <span class="material-symbols-rounded absolute left-3 top-1/2 -translate-y-1/2 text-[18px] text-[var(--sys-brand-solid)]">edit_note</span>
                    <input 
                      v-model="otherReason" 
                      type="text" 
                      placeholder="Nhập tên loại nghỉ của bạn..."
                      class="w-full h-11 pl-10 pr-4 bg-white border border-[var(--sys-border-strong)] rounded-md text-[13px] font-bold text-[var(--sys-text-primary)] focus:border-[var(--sys-brand-solid)] focus:ring-1 focus:ring-[var(--sys-brand-solid)] outline-none transition-all shadow-sm"
                    >
                  </div>
                </div>

                <!-- Row 2: Dates -->
                <div class="grid grid-cols-2 gap-4 bg-transparent">
                  <div class="space-y-1.5 bg-transparent">
                    <label class="block text-[11px] font-bold text-[var(--sys-text-primary)] uppercase tracking-widest ml-1">Từ ngày *</label>
                    <CalendarCustom v-model="startDate" placeholder="Bắt đầu từ..." disable-past />
                  </div>
                  <div class="space-y-1.5 bg-transparent">
                    <label class="block text-[11px] font-bold text-[var(--sys-text-primary)] uppercase tracking-widest ml-1">Đến ngày *</label>
                    <CalendarCustom v-model="endDate" placeholder="Kết thúc vào..." disable-past :min-date="startDate" />
                  </div>
                </div>

                <!-- Row 3: Reason -->
                <div class="space-y-1.5 bg-transparent text-left">
                  <label class="block text-[11px] font-bold text-[var(--sys-text-primary)] uppercase tracking-widest ml-1">Lý do chi tiết *</label>
                  <textarea 
                    v-model="reason" 
                    placeholder="Ghi chú thêm về lý do nghỉ phép..."
                    class="w-full h-24 px-4 py-3 bg-[var(--sys-bg-page)] border border-[var(--sys-border-strong)] rounded-md text-[13px] font-bold text-[var(--sys-text-primary)] outline-none focus:border-[var(--sys-brand-solid)] shadow-inner transition-all resize-none"
                  ></textarea>
                </div>

                <!-- Info summary -->
                <div class="p-4 bg-[var(--sys-bg-page)] rounded-md border border-[var(--sys-border-subtle)] border-dashed flex items-center justify-between">
                   <div class="flex items-center gap-3">
                      <span class="text-[11px] font-bold text-[var(--sys-text-secondary)] uppercase tracking-widest">Tổng thời gian:</span>
                      <span class="text-sm font-black text-[var(--sys-brand-solid)]">{{ calculateDays }} NGÀY</span>
                   </div>
                   <span class="text-[10px] font-bold text-[var(--sys-text-disabled)] uppercase italic" v-if="calculateDays > 0">Khả dụng: 12 ngày</span>
                </div>
              </div>

              <!-- Modal Footer -->
              <div class="px-6 py-4 border-t border-[var(--sys-border-subtle)] bg-[var(--sys-bg-surface)] flex gap-3">
                <button @click="showModal = false" class="flex-1 h-10 rounded-md text-[11px] font-bold uppercase tracking-widest text-[var(--sys-text-secondary)] hover:bg-[var(--sys-bg-hover)] transition-all">Hủy bỏ</button>
                <button 
                  @click="submitRequest" 
                  class="flex-[1.5] h-10 bg-[var(--sys-brand-solid)] text-white rounded-md font-bold uppercase tracking-widest text-[11px] hover:brightness-95 active:scale-[0.98] transition-all flex items-center justify-center gap-2 shadow-lg shadow-[var(--sys-brand-solid)]/10"
                >
                  <span class="material-symbols-rounded text-[18px]">send</span>
                  Xác nhận gửi đơn
                </button>
              </div>
            </template>
          </div>
        </div>
      </Transition>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import CalendarCustom from '@/components/CalendarCustom.vue';
import Dropdown from '@/components/Dropdown.vue';
import { useConfirm } from '@/composables/useConfirm';
import { mockLeaveRequests, mockRequestTypes, mockEmployees, mockDepartments } from '@/mock-data/index.js';
import { useCurrentUser } from '@/composables/useCurrentUser.js';

const { showAlert, showConfirm } = useConfirm();

const showModal = ref(false);
const isSuccess = ref(false);
const isValidationError = ref(false);
const validationMessage = ref('');
const leaveType = ref(null);
const otherReason = ref('');
const startDate = ref('');
const endDate = ref('');
const reason = ref('');

const requestTypes = ref([]);
const leaveHistory = ref([]);

// Ràng buộc: Ngày kết thúc phải sau hoặc bằng ngày bắt đầu
watch(startDate, (newStart) => {
  if (newStart && endDate.value && new Date(newStart) > new Date(endDate.value)) {
    endDate.value = '';
  }
});

const closeSuccessModal = () => {
  isSuccess.value = false;
  showModal.value = false;
};

const leaveTypeOptions = computed(() => {
  return requestTypes.value.map(t => {
    let icon = 'event_available';
    // Mapping icons dựa trên ID hoặc tên
    switch (t.requestTypeId) {
      case 1: icon = 'event_busy'; break;     // Nghỉ phép năm
      case 6: icon = 'sick'; break;           // Nghỉ ốm
      case 7: icon = 'child_care'; break;     // Thai sản
      case 8: icon = 'money_off'; break;      // Không lương
      case 9: icon = 'family_restroom'; break;// Việc riêng
      case 10: icon = 'history'; break;       // Nghỉ bù
      case 99: icon = 'help'; break;          // Khác
      default: icon = 'article';
    }
    return {
      label: t.requestTypeName,
      value: t.requestTypeId,
      icon: icon
    };
  });
});

// Current user từ localStorage
const { employeeId: currentEmpId, deptId: currentDeptId, baseLeaveDays: currentBaseLeaveDays } = useCurrentUser();
const CURRENT_EMP_ID = computed(() => currentEmpId.value);

const calculateDays = computed(() => {
  if (!startDate.value || !endDate.value) return 0;
  const start = new Date(startDate.value);
  const end = new Date(endDate.value);
  if (isNaN(start.getTime()) || isNaN(end.getTime())) return 0;
  const diffTime = Math.abs(end - start);
  return Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;
});

const pendingCount = computed(() => {
  return leaveHistory.value.filter(h => h.statusRaw === 'CHỜ_DUYỆT').length;
});

const fetchData = async () => {
  // 1. Khởi tạo danh sách loại hình nghỉ phép
  const allTypes = mockRequestTypes || [];
  const leaveCategories = ['NGHỈ_PHÉP', 'NGHỈ PHÉP', 'NGHI_PHEP', 'NGHI PHÉP'];
  const leaveTypeIds = [1, 6, 7, 8, 9, 10, 99];

  requestTypes.value = allTypes.filter(t => {
    const typeId = Number(t.requestTypeId);
    const cat = String(t.category || '').trim().toUpperCase();
    return leaveTypeIds.includes(typeId) || leaveCategories.includes(cat);
  });

  if (requestTypes.value.length === 0) {
    requestTypes.value = allTypes.filter(t => [1, 6, 7, 8, 9, 10, 99].includes(Number(t.requestTypeId)));
  }

  // 2. Tải lịch sử nghỉ phép từ Backend
  try {
    const res = await fetch(`http://localhost:3000/leaveRequests?requesterId=${CURRENT_EMP_ID.value}`);
    const data = await res.json();
    
    leaveHistory.value = data.map(item => {
      const typeObj = mockRequestTypes.getById(item.requestTypeId);
      const typeName = item.requestTypeId === 99 ? item.other_reason_name : (typeObj?.requestTypeName || 'Nghỉ phép');
      return {
        id: item.id || item.requestId,
        type: typeName,
        duration: `${item.startDate || 'N/A'} - ${item.endDate || 'N/A'}`,
        total: `${item.days || 0} ngày`,
        statusRaw: item.status,
        status: item.status === 'CHỜ_DUYỆT' ? 'Chờ duyệt' : (item.status === 'ĐÃ_DUYỆT' ? 'Đã duyệt' : 'Từ chối')
      };
    });
  } catch (error) {
    console.error('Lỗi khi tải dữ liệu nghỉ phép:', error);
  }
};

const submitRequest = async () => {
  if (!leaveType.value || !startDate.value || !endDate.value || !reason.value) {
    validationMessage.value = 'Vui lòng hoàn tất tất cả các trường thông tin có dấu (*)';
    isValidationError.value = true;
    return;
  }

  if (leaveType.value === 99 && !otherReason.value) {
    validationMessage.value = 'Vui lòng ghi rõ loại hình nghỉ khác của bạn';
    isValidationError.value = true;
    return;
  }

  const days = calculateDays.value;
  if (days <= 0) {
    validationMessage.value = 'Khoảng thời gian nghỉ phép không hợp lệ (Ngày bắt đầu phải trước ngày kết thúc)';
    isValidationError.value = true;
    return;
  }

  const employee = mockEmployees.getById(CURRENT_EMP_ID.value);

  const newRequest = {
    requesterId: CURRENT_EMP_ID.value,
    requestTypeId: leaveType.value,
    notes: reason.value,
    startDate: startDate.value,
    endDate: endDate.value,
    days: days,
    other_reason_name: leaveType.value === 99 ? otherReason.value : null,
    status: 'CHỜ_DUYỆT',
    is_urgent: days >= 3,
    request_date: new Date().toISOString().split('T')[0],
    department_id: currentDeptId.value || employee?.department?.departmentId || 2
  };

  try {
    await fetch('http://localhost:3000/leaveRequests', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(newRequest)
    });
    isSuccess.value = true;
    startDate.value = '';
    endDate.value = '';
    reason.value = '';
    fetchData();
  } catch (error) {
    console.error('Lỗi khi gửi đơn nghỉ phép:', error);
  }
};

const handleDeleteRequest = async (item) => {
  if (item.statusRaw !== 'CHỜ_DUYỆT') {
    await showAlert('Không khả dụng', 'Không thể xóa đơn đã được duyệt hoặc từ chối!');
    return;
  }
  
  const ok = await showConfirm('Xác nhận xóa', 'Bạn có chắc chắn muốn hủy bỏ và xóa đơn nghỉ phép này không?');
  if (ok) {
    try {
      await fetch(`http://localhost:3000/leaveRequests/${item.id}`, {
        method: 'DELETE'
      });
      fetchData();
    } catch (err) {
      console.error('Lỗi khi xóa đơn:', err);
    }
  }
};

onMounted(() => {
  fetchData();
});

const getStatusBadgeClass = (status) => {
  switch (status) {
    case 'ĐÃ_DUYỆT': return 'bg-[var(--sys-success-soft)] text-[var(--sys-success-text)] border-[var(--sys-success-border)]';
    case 'CHỜ_DUYỆT': return 'bg-[var(--sys-brand-soft)] text-[var(--sys-brand-solid)] border-[var(--sys-brand-border)]';
    case 'TỪ_CHỐI': return 'bg-[var(--sys-danger-soft)] text-[var(--sys-danger-text)] border-[var(--sys-danger-border)]';
    default: return 'bg-[var(--sys-bg-page)] text-[var(--sys-text-secondary)] border-[var(--sys-border-subtle)]';
  }
};

const getStatusDotClass = (status) => {
  switch (status) {
    case 'ĐÃ_DUYỆT': return 'bg-[var(--sys-success-solid)]';
    case 'CHỜ_DUYỆT': return 'bg-[var(--sys-brand-solid)]';
    case 'TỪ_CHỐI': return 'bg-[var(--sys-danger-solid)]';
    default: return 'bg-[var(--sys-text-disabled)] opacity-40';
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

.modal-enter-active, .modal-leave-active {
  transition: opacity 0.2s ease, transform 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}
.modal-enter-from, .modal-leave-to {
  opacity: 0;
  transform: scale(0.98) translateY(10px);
}
</style>
