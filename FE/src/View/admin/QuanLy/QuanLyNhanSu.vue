<template>
  <div class="space-y-6 pb-8">
    <!-- Header Area -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 bg-transparent text-left">
      <div class="bg-transparent text-left">
        <h1 class="text-xl font-bold text-[var(--sys-text-primary)] mb-1 uppercase tracking-tight">Hệ thống Quản lý Hồ sơ Nhân sự</h1>
        <p class="text-[13px] text-[var(--sys-text-secondary)]">Quản trị tập trung: Hồ sơ nhân viên, cơ cấu phòng ban và lộ trình thăng tiến.</p>
      </div>
      <div class="flex flex-col md:flex-row items-center gap-3 flex-1 w-full max-w-2xl bg-transparent">
        <div class="relative flex-1 w-full group">
          <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-[18px] text-[var(--sys-brand-solid)] opacity-60">search</span>
          <input 
            v-model="searchQuery"
            type="text" 
            placeholder="Mã NV, Họ tên hoặc Số CCCD..." 
            class="w-full h-11 pl-10 pr-4 rounded-md bg-[var(--sys-bg-surface)] border border-[var(--sys-border-strong)] text-[13px] text-[var(--sys-text-primary)] focus:outline-none focus:border-[var(--sys-brand-solid)] transition-all placeholder:text-[var(--sys-text-disabled)] shadow-sm"
          >
        </div>
        
        <div class="flex items-center gap-2 w-full md:w-auto bg-transparent shrink-0">
          <Dropdown v-model="filterDepartment" :options="departmentOptions" class="min-w-[150px] h-11" />
          <button @click="openAddModal" class="h-11 px-6 bg-[var(--sys-brand-solid)] rounded-md font-bold text-white hover:brightness-110 active:scale-95 transition-all flex items-center gap-2 text-[12px] uppercase tracking-wider shadow-lg shrink-0">
            <span class="material-symbols-outlined text-[20px]">person_add</span>
            Gia nhập mới
          </button>
        </div>
      </div>
    </div>

    <!-- Stats Section (Directive 1 & 5) -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
      <div v-for="stat in stats" :key="stat.label" 
        class="bg-[var(--sys-bg-surface)] p-4 rounded-lg border border-[var(--sys-border-subtle)] shadow-sm hover:border-[var(--sys-brand-solid)] transition-all group flex items-center gap-4">
        <!-- Universal Icon Wrapper (Directive 1) -->
        <div :class="`w-10 h-10 rounded-md flex items-center justify-center transition-all border shrink-0 ${
          stat.semantic === 'brand' ? 'bg-[var(--sys-brand-soft)] text-[var(--sys-brand-solid)] border-[var(--sys-brand-border)]' :
          stat.semantic === 'warning' ? 'bg-[var(--sys-warning-soft)] text-[var(--sys-warning-text)] border-[var(--sys-warning-border)]' :
          stat.semantic === 'danger' ? 'bg-[var(--sys-danger-soft)] text-[var(--sys-danger-text)] border-[var(--sys-danger-border)]' :
          'bg-[var(--sys-success-soft)] text-[var(--sys-success-text)] border-[var(--sys-success-border)]'
        }`">
          <span class="material-symbols-outlined text-xl">{{ stat.icon }}</span>
        </div>
        <div class="bg-transparent flex flex-col overflow-hidden">
          <p class="text-[11px] font-bold text-[var(--sys-text-secondary)] mb-0.5 uppercase tracking-widest opacity-60 truncate">{{ stat.label }}</p>
          <p class="text-xl font-bold text-[var(--sys-text-primary)] m-0 leading-tight tracking-tight">{{ stat.value }}</p>
        </div>
      </div>
    </div>

    <!-- Main Data Table Container (Directive 5: px-4 py-2.5) -->
    <div class="bg-[var(--sys-bg-surface)] rounded-lg border border-[var(--sys-border-subtle)] shadow-sm overflow-hidden flex flex-col">
      <div class="px-4 py-3 border-b border-[var(--sys-border-subtle)] bg-[var(--sys-bg-page)]/50 flex justify-between items-center h-14">
        <h4 class="text-[13px] font-bold text-[var(--sys-text-primary)] flex items-center gap-2 m-0 uppercase tracking-widest leading-none">
          <span class="material-symbols-outlined text-[var(--sys-brand-solid)] text-[20px]">badge</span> 
          Danh mục định danh nhân sự
        </h4>
        <Dropdown v-model="filterStatus" :options="statusOptions" class="min-w-[150px] h-9" />
      </div>

      <div class="overflow-x-auto custom-scrollbar bg-transparent">
        <table class="w-full text-left border-collapse">
          <thead class="bg-[var(--sys-bg-page)]">
            <tr>
              <th class="px-4 py-2.5 text-[11px] font-bold text-[var(--sys-text-secondary)] border-b border-[var(--sys-border-subtle)] uppercase tracking-widest whitespace-nowrap">Định danh nhân sự</th>
              <th class="px-4 py-2.5 text-[11px] font-bold text-[var(--sys-text-secondary)] border-b border-[var(--sys-border-subtle)] uppercase tracking-widest whitespace-nowrap">Cơ cấu & Chức vụ</th>
              <th class="px-4 py-2.5 text-[11px] font-bold text-[var(--sys-text-secondary)] border-b border-[var(--sys-border-subtle)] uppercase tracking-widest text-center whitespace-nowrap">Ngày gia nhập</th>
              <th class="px-4 py-2.5 text-[11px] font-bold text-[var(--sys-text-secondary)] border-b border-[var(--sys-border-subtle)] uppercase tracking-widest text-center whitespace-nowrap">Trạng thái</th>
              <th class="px-4 py-2.5 text-[11px] font-bold text-[var(--sys-text-secondary)] border-b border-[var(--sys-border-subtle)] uppercase tracking-widest text-right whitespace-nowrap">Quản trị</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-[var(--sys-border-subtle)]">
            <tr v-for="emp in filteredEmployees" :key="emp.id" 
              class="group hover:bg-[var(--sys-bg-hover)] transition-all cursor-default relative overflow-hidden">
              <td class="px-4 py-3 whitespace-nowrap bg-transparent">
                <div class="flex items-center gap-3">
                  <div class="w-9 h-9 rounded-md bg-[var(--sys-brand-soft)] text-[var(--sys-brand-solid)] flex items-center justify-center text-[13px] font-bold border border-[var(--sys-brand-border)] shadow-sm transition-transform group-hover:scale-105">
                    {{ emp.fullName ? emp.fullName[0] : '' }}
                  </div>
                  <div class="flex flex-col bg-transparent max-w-[180px]">
                    <span class="text-[13px] font-bold text-[var(--sys-text-primary)] transition-colors group-hover:text-[var(--sys-brand-solid)] truncate">{{ emp.fullName }}</span>
                    <span class="text-[10px] font-bold text-[var(--sys-brand-solid)] mt-0.5 opacity-60 uppercase tracking-tight transition-opacity group-hover:opacity-100">#{{ emp.employeeCode }}</span>
                  </div>
                </div>
              </td>
              <td class="px-4 py-3 whitespace-nowrap bg-transparent">
                <div class="flex flex-col bg-transparent">
                  <span class="text-[12px] font-bold text-[var(--sys-text-primary)] opacity-90 uppercase tracking-tight">{{ emp.department }}</span>
                  <span class="text-[11px] font-bold text-[var(--sys-text-secondary)] uppercase tracking-widest mt-0.5 opacity-60">{{ emp.position }}</span>
                </div>
              </td>
              <td class="px-4 py-3 text-center whitespace-nowrap bg-transparent">
                <span class="text-[12px] font-bold text-[var(--sys-text-secondary)] uppercase tracking-tighter">{{ emp.hireDate }}</span>
              </td>
              <td class="px-4 py-3 text-center whitespace-nowrap bg-transparent">
                <span :class="[
                  'px-2 py-0.5 rounded-md text-[10px] font-bold inline-flex items-center gap-1.5 border transition-all uppercase tracking-widest shadow-sm',
                  getStatusBadgeClass(emp.status)
                ]">
                  <span class="w-1.5 h-1.5 rounded-full shrink-0" :class="getStatusDotClass(emp.status)"></span>
                  {{ emp.status.split('_').join(' ') }}
                </span>
              </td>
              <td class="px-4 py-3 text-right whitespace-nowrap bg-transparent">
                <div class="flex items-center justify-end gap-1 px-1">
                  <button @click="openFaceEnrollment(emp)" class="w-8 h-8 flex items-center justify-center rounded-md text-[var(--sys-text-secondary)] hover:text-[var(--sys-success-text)] hover:bg-[var(--sys-success-soft)] transition-all" title="Xác thực khuôn mặt">
                    <span class="material-symbols-outlined text-[18px]">face_6</span>
                  </button>
                  <button @click="editEmployee(emp)" class="w-8 h-8 flex items-center justify-center rounded-md text-[var(--sys-text-secondary)] hover:text-[var(--sys-brand-solid)] hover:bg-[var(--sys-brand-soft)] transition-all" title="Chỉnh sửa">
                    <span class="material-symbols-outlined text-[18px]">edit_square</span>
                  </button>
                  <button @click="confirmResign(emp)" class="w-8 h-8 flex items-center justify-center rounded-md text-[var(--sys-text-secondary)] hover:text-[var(--sys-danger-solid)] hover:bg-[var(--sys-danger-soft)] transition-all" title="Chấm dứt">
                    <span class="material-symbols-outlined text-[18px]">delete</span>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <div class="px-4 py-3 bg-[var(--sys-bg-page)]/50 border-t border-[var(--sys-border-subtle)] flex justify-between items-center h-12">
        <span class="text-[11px] font-bold text-[var(--sys-text-secondary)] uppercase tracking-widest opacity-60">Hiển thị {{ filteredEmployees.length }} hồ sơ khả dụng</span>
        <button class="text-[var(--sys-brand-solid)] text-[11px] font-bold hover:opacity-80 flex items-center gap-1 group uppercase tracking-widest transition-opacity">
          Truy xuất toàn bộ danh mục
          <span class="material-symbols-outlined text-[16px] group-hover:translate-x-0.5 transition-transform">arrow_right_alt</span>
        </button>
      </div>
    </div>

    <!-- Add/Edit Modal (Directive 4: Widen & Grid Form) -->
    <Teleport to="body">
      <Transition name="modal-fade">
        <div v-if="showModal" class="fixed inset-0 z-[10000] flex items-center justify-center p-4">
          <div class="fixed inset-0 w-screen h-screen bg-black/60 backdrop-blur-sm z-[9999]" @click="showModal = false"></div>
          <div class="relative z-[10000] bg-white border border-[var(--sys-border-subtle)] w-full max-w-3xl max-h-[90vh] rounded-lg shadow-2xl overflow-hidden flex flex-col text-left motion-safe:animate-zoomIn">
            <!-- Modal Header -->
            <div class="px-6 py-4 border-b border-[var(--sys-border-subtle)] flex items-center justify-between bg-[var(--sys-bg-surface)]">
              <div class="bg-transparent text-left flex items-center gap-4">
                <div class="w-10 h-10 rounded-md bg-[var(--sys-brand-soft)] text-[var(--sys-brand-solid)] flex items-center justify-center border border-[var(--sys-brand-border)]">
                  <span class="material-symbols-outlined">{{ editMode ? 'edit_document' : 'person_add' }}</span>
                </div>
                <div>
                  <h3 class="text-lg font-bold text-[var(--sys-text-primary)] m-0 uppercase tracking-tight">{{ editMode ? 'Cập nhật hồ sơ nhân sự' : 'Thiết lập gia nhập mới' }}</h3>
                  <p class="text-[11px] text-[var(--sys-text-secondary)] font-medium mt-0.5">Xác thực thông tin định danh và cơ cấu tổ chức.</p>
                </div>
              </div>
              <button @click="showModal = false" class="w-8 h-8 flex items-center justify-center rounded-md hover:bg-[var(--sys-bg-hover)] transition-all text-[var(--sys-text-secondary)]">
                <span class="material-symbols-outlined text-xl">close</span>
              </button>
            </div>

            <!-- Modal Body (Directive 4: Grid Form) -->
            <div class="flex-1 overflow-y-auto p-6 custom-scrollbar space-y-8">
              <!-- Personal Info Group -->
              <div class="space-y-5">
                <h4 class="text-[11px] font-bold text-[var(--sys-brand-solid)] uppercase tracking-widest flex items-center gap-2">
                  <span class="material-symbols-outlined text-[16px]">person</span> 
                  I. Thông tin định danh cá nhân
                </h4>
                
                <div class="grid grid-cols-2 gap-5">
                  <div class="col-span-2 space-y-1.5">
                    <label class="text-[11px] font-bold text-[var(--sys-text-primary)] uppercase tracking-wider ml-1">Họ và tên đầy đủ *</label>
                    <input v-model="form.fullName" type="text" placeholder="Nhập họ tên như trên CCCD..." class="w-full h-11 px-4 bg-[var(--sys-bg-page)] border border-[var(--sys-border-strong)] rounded-md text-[13px] font-bold text-[var(--sys-text-primary)] outline-none focus:border-[var(--sys-brand-solid)] shadow-sm transition-all">
                  </div>
                  
                  <div class="space-y-1.5">
                    <label class="text-[11px] font-bold text-[var(--sys-text-primary)] uppercase tracking-wider ml-1">Ngày tháng năm sinh</label>
                    <CalendarCustom v-model="form.dateOfBirth" placeholder="Chọn ngày sinh" />
                  </div>
                  <div class="space-y-1.5">
                    <label class="text-[11px] font-bold text-[var(--sys-text-primary)] uppercase tracking-wider ml-1">Giới tính định danh</label>
                    <Dropdown v-model="form.gender" :options="genderOptions" class="w-full h-11 shadow-sm" />
                  </div>

                  <div class="space-y-1.5">
                    <label class="text-[11px] font-bold text-[var(--sys-text-primary)] uppercase tracking-wider ml-1">Số điện thoại liên lạc *</label>
                    <input v-model="form.phoneNumber" type="tel" placeholder="091 xxx xxxx" class="w-full h-11 px-4 bg-[var(--sys-bg-page)] border border-[var(--sys-border-strong)] rounded-md text-[13px] font-bold text-[var(--sys-text-primary)] outline-none focus:border-[var(--sys-brand-solid)] shadow-sm transition-all">
                  </div>
                  <div class="space-y-1.5">
                    <label class="text-[11px] font-bold text-[var(--sys-text-primary)] uppercase tracking-wider ml-1">Số thẻ CCCD/Hộ chiếu *</label>
                    <input v-model="form.id_card" type="text" placeholder="Nhập số định danh..." class="w-full h-11 px-4 bg-[var(--sys-bg-page)] border border-[var(--sys-border-strong)] rounded-md text-[13px] font-bold text-[var(--sys-text-primary)] outline-none focus:border-[var(--sys-brand-solid)] shadow-sm transition-all">
                  </div>
                </div>
              </div>

              <!-- Work Info Group -->
              <div class="space-y-5 pt-2 border-t border-[var(--sys-border-subtle)] border-dashed">
                <h4 class="text-[11px] font-bold text-[var(--sys-success-text)] uppercase tracking-widest flex items-center gap-2">
                  <span class="material-symbols-outlined text-[16px]">business_center</span> 
                  II. Cơ cấu tổ chức & Nhân sự
                </h4>
                
                <div class="grid grid-cols-2 gap-5">
                  <div class="space-y-1.5">
                    <label class="text-[11px] font-bold text-[var(--sys-text-primary)] uppercase tracking-wider ml-1">Phòng ban chỉ định *</label>
                    <Dropdown v-model="form.department" :options="departmentFormOptions" class="w-full h-11 shadow-sm" />
                  </div>
                  <div class="space-y-1.5">
                    <label class="text-[11px] font-bold text-[var(--sys-text-primary)] uppercase tracking-wider ml-1">Vị trí chuyên môn *</label>
                    <Dropdown v-model="form.position" :options="positionOptionsList" class="w-full h-11 shadow-sm" />
                  </div>

                  <div class="col-span-2 space-y-1.5">
                    <label class="text-[11px] font-bold text-[var(--sys-text-primary)] uppercase tracking-wider ml-1">Email công vụ (@company.com) *</label>
                    <input v-model="form.companyEmail" type="email" placeholder="official.alias@company.com" class="w-full h-11 px-4 bg-[var(--sys-bg-page)] border border-[var(--sys-border-strong)] rounded-md text-[13px] font-bold text-[var(--sys-text-primary)] outline-none focus:border-[var(--sys-brand-solid)] shadow-sm transition-all">
                  </div>

                  <div class="space-y-1.5">
                    <label class="text-[11px] font-bold text-[var(--sys-text-primary)] uppercase tracking-wider ml-1">Ngày gia nhập chính thức *</label>
                    <CalendarCustom v-model="form.hireDate" placeholder="Chọn ngày gia nhập" />
                  </div>
                  <div class="space-y-1.5">
                    <label class="text-[11px] font-bold text-[var(--sys-text-primary)] uppercase tracking-wider ml-1">Phân loại nhân sự</label>
                    <Dropdown v-model="form.status" :options="statusOptionsForm" class="w-full h-11 shadow-sm" />
                  </div>
                </div>

                <!-- ID Card Summary (Directive 1) -->
                <div class="mt-4 p-4 bg-[var(--sys-bg-page)] rounded-lg border border-[var(--sys-border-subtle)] flex items-center gap-5 shadow-inner">
                  <div class="w-10 h-10 rounded-md bg-[var(--sys-brand-soft)] text-[var(--sys-brand-solid)] flex items-center justify-center border border-[var(--sys-brand-border)]">
                    <span class="material-symbols-outlined">qr_code_Scanner</span>
                  </div>
                  <div class="bg-transparent flex flex-col">
                    <p class="text-[10px] font-bold text-[var(--sys-brand-solid)] uppercase tracking-widest m-0 leading-none">Mã định danh hệ thống (ID)</p>
                    <p class="text-xl font-bold text-[var(--sys-text-primary)] m-0 mt-1 uppercase tracking-tighter">{{ form.employeeCode || 'EMP-XXXX' }}</p>
                  </div>
                </div>
              </div>
            </div>

            <!-- Modal Footer (Directive 4: Slim and Right) -->
            <div class="px-6 py-4 border-t border-[var(--sys-border-subtle)] bg-[var(--sys-bg-surface)] flex justify-end gap-3 h-16 shrink-0">
              <button @click="showModal = false" class="h-9 px-6 text-[12px] font-bold text-[var(--sys-text-secondary)] hover:bg-[var(--sys-bg-hover)] rounded-md transition-all uppercase tracking-wide">Hủy tác vụ</button>
              <button @click="saveEmployee" class="h-9 px-8 bg-[var(--sys-brand-solid)] text-white rounded-md font-bold text-[12px] hover:brightness-110 shadow-lg transition-all uppercase tracking-widest active:scale-95 flex items-center gap-2">
                <span class="material-symbols-outlined text-[18px]">verified</span>
                {{ editMode ? 'Lưu cập nhật' : 'Xác nhận gia nhập' }}
              </button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>

    <!-- Face Enrollment Modal -->
    <FaceEnrollmentModal 
      :show="showFaceModal"
      :employee-id="selectedEmployee?.id"
      :employee-name="selectedEmployee?.fullName"
      @close="showFaceModal = false"
      @success="onFaceSuccess"
    />
  </div>
</template>

<script setup>
/**
 * QUẢN LÝ NHÂN SỰ - PHIÊN BẢN ENTERPRISE REFINEMENT
 * Đã kết nối Mock Database mới từ mock-data
 */
import { ref, computed, onMounted } from 'vue';
import Dropdown from '@/components/Dropdown.vue';
import CalendarCustom from '@/components/CalendarCustom.vue';
import FaceEnrollmentModal from '@/components/hrm/FaceEnrollmentModal.vue';
import { useConfirm } from '@/composables/useConfirm';
import { apiRequest } from '@/services/beApi.js';

const { showAlert, showConfirm } = useConfirm();
const employeesList = ref([]);
const departmentsList = ref([]);
const positionsList = ref([]);
const isLoading = ref(false);

const loadData = async () => {
  isLoading.value = true;
  try {
    const [employees, departments, positions] = await Promise.all([
      apiRequest('/employees', { query: { page: 1, per_page: 2000 } }),
      apiRequest('/departments', { query: { page: 1, per_page: 500 } }),
      apiRequest('/positions', { query: { page: 1, per_page: 500 } }),
    ]);
    employeesList.value = Array.isArray(employees?.data) ? employees.data : [];
    departmentsList.value = Array.isArray(departments?.data) ? departments.data : [];
    positionsList.value = Array.isArray(positions?.data) ? positions.data : [];
  } catch (error) {
    await showAlert('Không tải được dữ liệu', error?.message || 'Không thể tải danh sách nhân sự.');
  } finally {
    isLoading.value = false;
  }
};

onMounted(() => {
  void loadData();
});

// Mapping Global Employees -> Component Table Format
const employees = computed(() => {
  return employeesList.value.map(e => ({
    id: Number(e.employeeId ?? e.employee_id ?? e.id),
    employeeCode: e.employeeCode ?? e.employee_code ?? '',
    fullName: e.fullName ?? e.full_name ?? '',
    department: e.department?.departmentName ?? e.department_name ?? '',
    department_id: Number(e.department?.departmentId ?? e.departmentId ?? e.department_id ?? e.deptId ?? 0),
    position: e.position?.positionName ?? e.position_name ?? '',
    position_id: Number(e.position?.positionId ?? e.positionId ?? e.position_id ?? 0),
    status: e.status || 'THỬ_VIỆC',
    hireDate: e.hireDate || e.hire_date || '',
    gender: e.gender || 'NAM',
    phoneNumber: e.phoneNumber || e.phone_number || '',
    id_card: e.id_card || '',
    companyEmail: e.companyEmail || e.company_email || '',
    dateOfBirth: e.dateOfBirth || e.date_of_birth || ''
  }));
});

const searchQuery = ref('');
const filterDepartment = ref('ALL');
const filterStatus = ref('ALL');
const showModal = ref(false);
const editMode = ref(false);

const stats = computed(() => [
  { label: 'Tổng nhân lực', value: employees.value.length.toString(), icon: 'groups', semantic: 'brand' },
  { label: 'Đang thử việc', value: employees.value.filter(e => e.status === 'THỬ_VIỆC').length.toString(), icon: 'explore', semantic: 'warning' },
  { label: 'Đã Thôi việc', value: employees.value.filter(e => e.status === 'ĐÃ_NGHỈ_VIỆC').length.toString(), icon: 'person_off', semantic: 'danger' },
  { label: 'Chính thức', value: employees.value.filter(e => e.status === 'ĐANG_LÀM_VIỆC').length.toString(), icon: 'verified', semantic: 'success' }
]);

const departmentOptions = computed(() => {
  const depts = new Set(employees.value.map(e => e.department).filter(Boolean));
  return [
    { label: 'Toàn bộ phòng ban', value: 'ALL' },
    ...Array.from(depts).map(d => ({ label: d, value: d }))
  ];
});

const statusOptions = [
  { label: 'Tất cả trạng thái', value: 'ALL' },
  { label: 'Đang làm việc', value: 'ĐANG_LÀM_VIỆC' },
  { label: 'Đang thử việc', value: 'THỬ_VIỆC' },
  { label: 'Đã thôi việc', value: 'ĐÃ_NGHỈ_VIỆC' }
];

const genderOptions = [
  { label: 'Nam giới', value: 'NAM' },
  { label: 'Nữ giới', value: 'NỮ' },
  { label: 'Định danh khác', value: 'KHÁC' }
];

// Mapping Forms
const departmentFormOptions = computed(() => {
  return departmentsList.value
    .map((d) => ({
      label: d.departmentName ?? d.department_name ?? '',
      value: Number(d.departmentId ?? d.department_id ?? d.id),
    }))
    .filter((d) => d.value > 0 && d.label);
});
const positionOptionsList = computed(() => {
  return positionsList.value
    .map((p) => ({
      label: p.positionName ?? p.position_name ?? '',
      value: Number(p.positionId ?? p.position_id ?? p.id),
    }))
    .filter((p) => p.value > 0 && p.label);
});

const statusOptionsForm = [
  { label: 'Chính thức (Đang làm việc)', value: 'ĐANG_LÀM_VIỆC' },
  { label: 'Hợp đồng thử việc', value: 'THỬ_VIỆC' },
  { label: 'Đã thôi việc / Chấm dứt', value: 'ĐÃ_NGHỈ_VIỆC' }
];

const emptyForm = {
  fullName: '',
  dateOfBirth: '',
  gender: 'NAM',
  phoneNumber: '',
  id_card: '',
  department: 1, // departmentId
  position: 1, // positionId
  companyEmail: '',
  hireDate: new Date().toISOString().substr(0, 10),
  status: 'THỬ_VIỆC',
  employeeCode: ''
};

const form = ref({ ...emptyForm });

const filteredEmployees = computed(() => {
  let list = employees.value;
  if (filterDepartment.value !== 'ALL') {
    list = list.filter(e => e.department === filterDepartment.value);
  }
  if (filterStatus.value !== 'ALL') {
    list = list.filter(e => e.status === filterStatus.value);
  }
  if (searchQuery.value) {
    const q = searchQuery.value.toLowerCase();
    list = list.filter(e => e.fullName.toLowerCase().includes(q) || e.employeeCode.toLowerCase().includes(q));
  }
  return list;
});

const getStatusBadgeClass = (status) => {
  switch (status) {
    case 'ĐANG_LÀM_VIỆC': return 'bg-[var(--sys-success-soft)] text-[var(--sys-success-text)] border-[var(--sys-success-border)]';
    case 'THỬ_VIỆC': return 'bg-[var(--sys-brand-soft)] text-[var(--sys-brand-solid)] border-[var(--sys-brand-border)]';
    case 'ĐÃ_NGHỈ_VIỆC': return 'bg-[var(--sys-danger-soft)] text-[var(--sys-danger-text)] border-[var(--sys-danger-border)]';
    default: return 'bg-[var(--sys-bg-page)] text-[var(--sys-text-secondary)] border-[var(--sys-border-subtle)]';
  }
};

const getStatusDotClass = (status) => {
  switch (status) {
    case 'ĐANG_LÀM_VIỆC': return 'bg-[var(--sys-success-solid)]';
    case 'THỬ_VIỆC': return 'bg-[var(--sys-brand-solid)]';
    case 'ĐÃ_NGHỈ_VIỆC': return 'bg-[var(--sys-danger-solid)]';
    default: return 'bg-[var(--sys-text-disabled)] opacity-40';
  }
};

const openAddModal = () => {
  editMode.value = false;
  form.value = { ...emptyForm };
  form.value.employeeCode = `EMP${(employees.value.length + 1).toString().padStart(3,'0')}`;
  form.value.department = departmentFormOptions.value[0]?.value || 1;
  form.value.position = positionOptionsList.value[0]?.value || 1;
  showModal.value = true;
};

const editEmployee = (emp) => {
  editMode.value = true;
  form.value = { ...emp, department: emp.department_id, position: emp.position_id };
  showModal.value = true;
};

const saveEmployee = async () => {
  if (!form.value.fullName || !form.value.companyEmail || !form.value.hireDate) {
    await showAlert('Thiếu dữ liệu', 'Họ tên, email công vụ và ngày gia nhập là bắt buộc.');
    return;
  }

  try {
    if (editMode.value) {
      await apiRequest(`/employees/${form.value.id}`, {
        method: 'PATCH',
        body: {
          full_name: form.value.fullName,
          company_email: form.value.companyEmail,
          hire_date: form.value.hireDate,
          date_of_birth: form.value.dateOfBirth || undefined,
          gender: form.value.gender,
          phone_number: form.value.phoneNumber || undefined,
          status: form.value.status,
          department_id: Number(form.value.department),
          position_id: Number(form.value.position),
        },
      });
    } else {
      await apiRequest('/employees', {
        method: 'POST',
        body: {
          employee_code: form.value.employeeCode,
          full_name: form.value.fullName,
          company_email: form.value.companyEmail,
          hire_date: form.value.hireDate,
          date_of_birth: form.value.dateOfBirth || undefined,
          gender: form.value.gender,
          phone_number: form.value.phoneNumber || undefined,
          status: form.value.status,
          department_id: Number(form.value.department),
          position_id: Number(form.value.position),
          password: form.value.employeeCode,
        },
      });
    }
    await loadData();
    showModal.value = false;
  } catch (error) {
    await showAlert('Lưu thất bại', error?.message || 'Không thể lưu hồ sơ nhân sự.');
  }
};

const confirmResign = async (emp) => {
  const ok = await showConfirm('Chấm dứt hồ sơ', `Xác nhận thực hiện quy trình thôi việc cho nhân sự ${emp.fullName}?`);
  if (ok) {
    try {
      await apiRequest(`/employees/${emp.id}`, {
        method: 'PATCH',
        body: { status: 'ĐÃ_NGHỈ_VIỆC' },
      });
      await loadData();
    } catch (error) {
      await showAlert('Cập nhật thất bại', error?.message || 'Không thể cập nhật trạng thái nhân sự.');
    }
  }
};

const showFaceModal = ref(false);
const selectedEmployee = ref(null);

const openFaceEnrollment = (emp) => {
  selectedEmployee.value = emp;
  showFaceModal.value = true;
};

const onFaceSuccess = async () => {
  const name = selectedEmployee.value?.fullName || 'nhân viên';
  showAlert('Đăng ký thành công', `Khuôn mặt của ${name} đã được cập nhật.`);
  loadData(); // Chạy ngầm
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
  border-radius: 10px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
  background: var(--sys-brand-solid);
}

@keyframes zoomIn {
  from { opacity: 0; transform: scale(0.95); }
  to { opacity: 1; transform: scale(1); }
}

.animate-zoomIn {
  animation: zoomIn 0.3s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}

.modal-fade-enter-active,
.modal-fade-leave-active {
  transition: opacity 0.2s ease;
}

.modal-fade-enter-from,
.modal-fade-leave-to {
  opacity: 0;
}
</style>
