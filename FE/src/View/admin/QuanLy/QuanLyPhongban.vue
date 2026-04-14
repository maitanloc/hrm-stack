<template>
  <div class="space-y-6 pb-8">
    <!-- Header Area -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 bg-transparent text-left">
      <div class="bg-transparent text-left">
        <h1 class="text-xl font-semibold text-[var(--sys-text-primary)] mb-1">Quản Trị Cơ Cấu Tổ Chức</h1>
        <p class="text-sm text-[var(--sys-text-secondary)]">Quản lý sơ đồ tổ chức, phân cấp đơn vị và định biên nhân sự.</p>
      </div>
      <div class="flex flex-col md:flex-row items-center gap-3 flex-1 w-full max-w-3xl bg-transparent">
        <!-- Search Bar -->
        <div class="relative flex-1 w-full group">
          <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-[20px] text-[var(--sys-brand-solid)]">search</span>
          <input 
            v-model="searchQuery"
            type="text" 
            placeholder="Tìm kiếm mã đơn vị, tên phòng ban..." 
            class="w-full h-10 pl-10 pr-4 rounded-md bg-[var(--sys-bg-surface)] border border-[var(--sys-border-strong)] text-sm text-[var(--sys-text-primary)] focus:outline-none focus:border-[var(--sys-brand-solid)] focus:ring-1 focus:ring-[var(--sys-brand-solid)] transition-all placeholder:text-[var(--sys-text-disabled)]"
          >
        </div>
        
        <div class="flex items-center gap-3 w-full md:w-auto bg-transparent shrink-0">
          <Dropdown v-model="filterActive" :options="activeOptions" class="min-w-[160px] h-10" />
          <button @click="openModal('add')" class="h-10 px-4 bg-[var(--sys-brand-solid)] rounded-md font-semibold text-white hover:brightness-90 active:opacity-90 transition-all flex items-center gap-2 text-sm whitespace-nowrap shadow-sm">
            <span class="material-symbols-outlined text-[20px]">add_business</span>
            Thêm đơn vị
          </button>
        </div>
      </div>
    </div>

    <!-- Stats Section -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
      <div v-for="stat in stats" :key="stat.label" 
        class="bg-[var(--sys-bg-surface)] p-4 rounded-lg border border-[var(--sys-border-subtle)] shadow-sm hover:border-[var(--sys-brand-solid)] transition-all group flex items-center gap-4">
        <div :class="`w-12 h-12 rounded-md flex items-center justify-center transition-all ${
          stat.semantic === 'brand' ? 'bg-[var(--sys-brand-soft)] text-[var(--sys-brand-solid)]' :
          stat.semantic === 'success' ? 'bg-[var(--sys-success-soft)] text-[var(--sys-success-text)]' :
          'bg-[var(--sys-warning-soft)] text-[var(--sys-warning-text)]'
        }`">
          <span class="material-symbols-outlined text-[24px]">{{ stat.icon }}</span>
        </div>
        <div class="bg-transparent flex flex-col">
          <p class="text-[12px] font-medium text-[var(--sys-text-secondary)] mb-0.5 uppercase tracking-wide">{{ stat.label }}</p>
          <p class="text-xl font-bold text-[var(--sys-text-primary)] m-0 leading-tight">{{ stat.value }}</p>
        </div>
      </div>
    </div>

    <!-- Main Data Table Container -->
    <div class="bg-[var(--sys-bg-surface)] rounded-lg border border-[var(--sys-border-subtle)] shadow-sm overflow-hidden flex flex-col">
      <div class="overflow-x-auto custom-scrollbar bg-transparent">
        <table class="w-full text-left border-collapse">
          <thead class="bg-[var(--sys-bg-page)]">
            <tr>
              <th class="px-4 py-3 text-[12px] font-semibold text-[var(--sys-text-secondary)] border-b border-[var(--sys-border-subtle)] uppercase tracking-wider">Cơ cấu tổ chức</th>
              <th class="px-4 py-3 text-[12px] font-semibold text-[var(--sys-text-secondary)] border-b border-[var(--sys-border-subtle)] uppercase tracking-wider">Quản lý trực tiếp</th>
              <th class="px-4 py-3 text-[12px] font-semibold text-[var(--sys-text-secondary)] border-b border-[var(--sys-border-subtle)] uppercase tracking-wider text-center">SL Nhân Viên</th>
              <th class="px-4 py-3 text-[12px] font-semibold text-[var(--sys-text-secondary)] border-b border-[var(--sys-border-subtle)] uppercase tracking-wider text-center">Trạng thái</th>
              <th class="px-4 py-3 text-[12px] font-semibold text-[var(--sys-text-secondary)] border-b border-[var(--sys-border-subtle)] uppercase tracking-wider text-right">Thao tác</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-[var(--sys-border-subtle)]">
            <tr v-for="dept in filteredDepartments" :key="dept.id" 
              class="group hover:bg-[var(--sys-bg-hover)] transition-colors cursor-default">
              <td class="px-4 py-3 whitespace-nowrap bg-transparent">
                <div class="flex items-center gap-3">
                  <div class="w-8 h-8 rounded-md bg-[var(--sys-brand-soft)] text-[var(--sys-brand-solid)] flex items-center justify-center border border-[var(--sys-brand-border)]">
                    <span class="material-symbols-outlined text-[18px]">{{ dept.icon || 'hub' }}</span>
                  </div>
                  <div class="flex flex-col bg-transparent">
                    <span class="text-[13px] font-semibold text-[var(--sys-text-primary)] transition-colors">{{ dept.name }}</span>
                    <span class="text-[12px] text-[var(--sys-text-secondary)]">Mã: #{{ dept.code }}</span>
                  </div>
                </div>
              </td>
              <td class="px-4 py-3 whitespace-nowrap bg-transparent">
                <div v-if="dept.manager" class="flex items-center gap-2">
                  <span class="text-[13px] font-medium text-[var(--sys-text-primary)]">{{ dept.manager }}</span>
                </div>
                <span v-else class="text-[12px] text-[var(--sys-text-disabled)] italic">Chưa bổ nhiệm</span>
              </td>
              <td class="px-4 py-3 text-center whitespace-nowrap bg-transparent">
                <span class="text-[13px] text-[var(--sys-text-primary)]">{{ dept.employee_count }} nhân sự</span>
              </td>
              <td class="px-4 py-3 text-center whitespace-nowrap bg-transparent">
                <span :class="[
                  'px-2 py-0.5 rounded-md text-[11px] font-semibold inline-flex items-center gap-1.5 border transition-all uppercase tracking-wide',
                  dept.active ? 'bg-[var(--sys-success-soft)] text-[var(--sys-success-text)] border-[var(--sys-success-border)]' : 'bg-[var(--sys-danger-soft)] text-[var(--sys-danger-text)] border-[var(--sys-danger-border)]'
                ]">
                  <span class="w-1.5 h-1.5 rounded-full shrink-0" :class="dept.active ? 'bg-[var(--sys-success-solid)]' : 'bg-[var(--sys-danger-solid)]'"></span>
                  {{ dept.active ? 'Vận hành' : 'Tạm dừng' }}
                </span>
              </td>
              <td class="px-4 py-3 text-right whitespace-nowrap bg-transparent">
                <div class="flex items-center justify-end gap-1">
                  <button @click="openModal('edit', dept)" class="w-8 h-8 flex items-center justify-center rounded-md text-[var(--sys-text-secondary)] hover:bg-[var(--sys-brand-soft)] hover:text-[var(--sys-brand-solid)] transition-all" title="Chỉnh sửa">
                    <span class="material-symbols-outlined text-[18px]">edit_square</span>
                  </button>
                  <button @click="confirmDissolve(dept)" class="w-8 h-8 flex items-center justify-center rounded-md text-[var(--sys-text-secondary)] hover:bg-[var(--sys-danger-soft)] hover:text-[var(--sys-danger-solid)] transition-all" title="Xóa">
                    <span class="material-symbols-outlined text-[18px]">delete</span>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <div class="px-4 py-3 bg-[var(--sys-bg-page)] border-t border-[var(--sys-border-subtle)] flex justify-between items-center">
        <span class="text-[12px] font-medium text-[var(--sys-text-secondary)]">Hiển thị {{ filteredDepartments.length }} phòng ban</span>
      </div>
    </div>

    <!-- Modal System -->
    <Teleport to="body">
      <Transition name="modal-fade">
        <div v-if="showModal" class="fixed inset-0 z-[10000] flex items-center justify-center p-4">
          <div class="fixed inset-0 w-screen h-screen bg-black/50 z-[9999]" @click="closeModal"></div>
          <div class="relative z-[10000] bg-[var(--sys-bg-surface-elevated)] border border-[var(--sys-border-subtle)] w-full max-w-4xl max-h-[90vh] rounded-lg shadow-xl overflow-hidden flex flex-col text-left">
            <!-- Modal Header -->
            <div class="px-6 py-4 border-b border-[var(--sys-border-subtle)] flex items-center justify-between bg-[var(--sys-bg-surface)]">
              <div class="bg-transparent text-left flex flex-col">
                <h3 class="text-lg font-semibold text-[var(--sys-text-primary)] m-0 uppercase tracking-tight">{{ isEdit ? 'Cập nhật cấu trúc đơn vị' : 'Thêm đơn vị tổ chức mới' }}</h3>
                <p class="text-sm text-[var(--sys-text-secondary)] mt-1">Cung cấp thông tin chi tiết để cập nhật sơ đồ tổ chức doanh nghiệp.</p>
              </div>
              <button @click="closeModal" class="w-8 h-8 flex items-center justify-center rounded-md hover:bg-[var(--sys-bg-hover)] transition-all text-[var(--sys-text-secondary)]">
                <span class="material-symbols-outlined text-xl">close</span>
              </button>
            </div>

            <!-- Modal Body -->
            <div class="flex-1 overflow-y-auto p-6 custom-scrollbar bg-transparent">
              <div class="grid grid-cols-1 md:grid-cols-2 gap-8 bg-transparent text-left border-none">
                <!-- Identification side -->
                <div class="space-y-6 bg-transparent border-none">
                  <h4 class="text-sm font-semibold text-[var(--sys-brand-solid)] uppercase tracking-wider">Thông tin định danh</h4>
                  
                  <div class="space-y-4 bg-transparent border-none">
                    <div class="grid grid-cols-2 gap-4 bg-transparent border-none">
                      <div class="space-y-1.5 bg-transparent border-none">
                        <label class="text-[13px] font-medium text-[var(--sys-text-primary)] block">Mã đơn vị *</label>
                        <input v-model="form.code" type="text" placeholder="VD: ACC-DEPT" class="w-full h-10 px-3 bg-[var(--sys-bg-page)] border border-[var(--sys-border-strong)] rounded-md text-sm text-[var(--sys-text-primary)] focus:border-[var(--sys-brand-solid)] outline-none transition-all">
                      </div>
                      <div class="space-y-1.5 bg-transparent border-none">
                        <label class="text-[13px] font-medium text-[var(--sys-text-primary)] block">Biểu tượng</label>
                        <Dropdown v-model="form.icon" :options="iconOptionsList" class="w-full h-10" />
                      </div>
                    </div>

                    <div class="space-y-1.5 bg-transparent border-none">
                      <label class="text-[13px] font-medium text-[var(--sys-text-primary)] block">Tên đơn vị tổ chức *</label>
                      <input v-model="form.name" type="text" placeholder="Nhập tên phòng ban..." class="w-full h-10 px-3 bg-[var(--sys-bg-page)] border border-[var(--sys-border-strong)] rounded-md text-sm text-[var(--sys-text-primary)] focus:border-[var(--sys-brand-solid)] outline-none transition-all">
                    </div>

                    <!-- Preview Card -->
                    <div class="p-5 bg-[var(--sys-bg-page)] rounded-md border border-[var(--sys-border-subtle)] flex items-center gap-4">
                      <div class="w-12 h-12 rounded-md bg-white flex items-center justify-center text-[var(--sys-brand-solid)] border border-[var(--sys-border-subtle)] shrink-0 shadow-sm">
                        <span class="material-symbols-outlined text-2xl">{{ form.icon || 'corporate_fare' }}</span>
                      </div>
                      <div class="bg-transparent flex flex-col overflow-hidden">
                        <p class="text-sm font-semibold text-[var(--sys-text-primary)] truncate m-0">{{ form.name || 'Tên đơn vị' }}</p>
                        <p class="text-[11px] font-medium text-[var(--sys-brand-solid)] uppercase tracking-wide m-0">Mã: {{ form.code || 'DEPT-CODE' }}</p>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Management side -->
                <div class="space-y-6 bg-transparent border-none">
                  <h4 class="text-sm font-semibold text-[var(--sys-success-text)] uppercase tracking-wider">Quản lý & Phân cấp</h4>
                  
                  <div class="space-y-4 bg-transparent border-none">
                    <div class="space-y-1.5 bg-transparent border-none text-left">
                      <label class="text-[13px] font-medium text-[var(--sys-text-primary)] block">Đơn vị quản lý cấp trên</label>
                      <Dropdown v-model="form.parent_id" :options="parentDeptOptions" class="w-full h-10" />
                    </div>

                    <div class="space-y-1.5 bg-transparent border-none text-left relative">
                      <label class="text-[13px] font-medium text-[var(--sys-text-primary)] block">Trưởng đơn vị / Người phụ trách</label>
                      <input
                        v-model="form.manager"
                        type="text"
                        placeholder="Gõ tên hoặc mã NV để tìm quản lý..."
                        class="w-full h-10 px-3 bg-[var(--sys-bg-page)] border border-[var(--sys-border-strong)] rounded-md text-sm text-[var(--sys-text-primary)] focus:border-[var(--sys-brand-solid)] outline-none transition-all"
                        @focus="managerSuggestionOpen = true"
                        @input="onManagerKeywordInput"
                        @blur="onManagerInputBlur"
                      >
                      <div
                        v-if="managerSuggestionOpen && managerSuggestions.length > 0"
                        class="absolute z-20 mt-1 w-full max-h-52 overflow-auto rounded-md border border-[var(--sys-border-subtle)] bg-white shadow-lg"
                      >
                        <button
                          v-for="employee in managerSuggestions"
                          :key="employee.value"
                          type="button"
                          class="w-full text-left px-3 py-2 text-sm hover:bg-[var(--sys-bg-hover)] transition-colors"
                          @mousedown.prevent="selectManager(employee)"
                        >
                          <p class="font-semibold text-[var(--sys-text-primary)]">{{ employee.label }}</p>
                          <p class="text-[11px] text-[var(--sys-text-secondary)]">{{ employee.meta }}</p>
                        </button>
                      </div>
                    </div>

                    <!-- Toggle Status -->
                    <div class="flex items-center justify-between p-4 bg-[var(--sys-bg-page)] rounded-md border border-[var(--sys-border-subtle)] shadow-sm">
                      <div class="bg-transparent text-left">
                        <p class="text-[12px] font-medium text-[var(--sys-text-primary)] m-0">Kích hoạt vận hành</p>
                        <p class="text-[11px] text-[var(--sys-text-secondary)] m-0">{{ form.active ? 'Đang hoạt động trong sơ đồ' : 'Đang tạm dừng hoạt động' }}</p>
                      </div>
                      <button 
                        @click="form.active = !form.active"
                        type="button"
                        class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 outline-none"
                        :class="form.active ? 'bg-[var(--sys-brand-solid)]' : 'bg-[var(--sys-border-subtle)]'"
                      >
                        <span class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200"
                          :class="form.active ? 'translate-x-5' : 'translate-x-0'"></span>
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Modal Footer -->
            <div class="px-6 py-4 border-t border-[var(--sys-border-subtle)] bg-[var(--sys-bg-surface)] flex justify-end gap-3">
              <button @click="closeModal" class="px-4 py-2 text-sm font-medium text-[var(--sys-text-secondary)] hover:bg-[var(--sys-bg-hover)] rounded-md transition-all uppercase tracking-wide">Hủy bỏ</button>
              <button @click="handleSave" class="px-6 py-2 bg-[var(--sys-brand-solid)] text-white rounded-md font-semibold text-sm hover:brightness-90 transition-all uppercase tracking-wide">
                {{ isEdit ? 'Cập nhật cấu trúc' : 'Xác nhận khởi tạo' }}
              </button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>
  </div>
</template>

<script setup>
/**
 * TRANG QUẢN TRỊ CƠ CẤU TỔ CHỨC - PHIÊN BẢN ENTERPRISE SaaS
 * Tuân thủ 7 Golden Rules:
 * - Font Inter 14px (text-sm), Tỉ lệ Table cao (text-13px)
 * - Bo góc chuẩn B2B: 6px (MD) cho Input/Button, 8px (LG) cho Card/Modal/Table
 * - Hệ màu Blue/White đồng nhất cho Action Icons (Search, Edit, Delete)
 * - Chiều cao Toolbar chuẩn hóa h-10 (40px)
 */
import { computed, onMounted, ref } from 'vue';
import Dropdown from '@/components/Dropdown.vue';
import { useConfirm } from '@/composables/useConfirm';
import { BE_API_BASE, getAccessToken } from '@/services/runtimeConfig.js';
import { handleUnauthorized } from '@/services/session.js';

const { showAlert, showConfirm } = useConfirm();

const showModal = ref(false);
const isEdit = ref(false);
const searchQuery = ref('');
const filterActive = ref('ALL');
const departmentsRaw = ref([]);
const employeesRaw = ref([]);
const isLoading = ref(false);
const managerSuggestionOpen = ref(false);

const activeOptions = [
  { label: 'Mọi trạng thái', value: 'ALL' },
  { label: 'Đang hoạt động', value: true },
  { label: 'Đã tạm dừng', value: false }
];

const authHeaders = () => {
  const token = getAccessToken();
  if (!token) throw new Error('Thiếu access token. Vui lòng đăng nhập lại.');
  return {
    Authorization: `Bearer ${token}`,
    'Content-Type': 'application/json',
  };
};

const apiRequest = async (path, options = {}) => {
  const response = await fetch(`${BE_API_BASE}${path}`, {
    ...options,
    headers: {
      ...authHeaders(),
      ...(options.headers || {}),
    },
  });
  if (response.status === 401) {
    handleUnauthorized();
    throw new Error('Phiên đăng nhập đã hết hạn.');
  }
  const payload = await response.json().catch(() => ({}));
  if (!response.ok || payload?.success === false) {
    throw new Error(payload?.message || `HTTP ${response.status}`);
  }
  return payload?.data;
};

const loadData = async () => {
  isLoading.value = true;
  try {
    const [departmentRows, employeeRows] = await Promise.all([
      apiRequest('/departments?page=1&per_page=500'),
      apiRequest('/employees?page=1&per_page=2000'),
    ]);
    departmentsRaw.value = Array.isArray(departmentRows) ? departmentRows : [];
    employeesRaw.value = Array.isArray(employeeRows) ? employeeRows : [];
  } catch (error) {
    await showAlert('Không tải được dữ liệu', error?.message || 'Không thể tải danh sách phòng ban.');
  } finally {
    isLoading.value = false;
  }
};

onMounted(() => {
  void loadData();
});

const toEmployeeId = (employee) => Number(employee?.employee_id ?? employee?.employeeId ?? employee?.id ?? 0);
const toEmployeeCode = (employee) => String(employee?.employee_code ?? employee?.employeeCode ?? '').trim();
const toEmployeeName = (employee) => String(employee?.full_name ?? employee?.fullName ?? employee?.name ?? '').trim();
const toEmployeeDepartmentId = (employee) => Number(employee?.department_id ?? employee?.departmentId ?? 0);

const employeeLookup = computed(() => {
  const map = new Map();
  employeesRaw.value.forEach((employee) => {
    const employeeId = toEmployeeId(employee);
    if (employeeId > 0) map.set(employeeId, employee);
  });
  return map;
});

const normalizeDepartmentStatus = (statusValue) => {
  if (typeof statusValue === 'boolean') return statusValue;
  if (typeof statusValue === 'number') return statusValue > 0;
  const text = String(statusValue ?? '').trim().toUpperCase();
  if (text === '' || text === '1' || text === 'TRUE' || text === 'ACTIVE') return true;
  if (text === '0' || text === 'FALSE' || text === 'INACTIVE') return false;
  return true;
};

const departments = computed(() => {
  return departmentsRaw.value.map((d) => {
    const departmentId = Number(d.departmentId ?? d.department_id ?? d.id ?? 0);
    const empCount = employeesRaw.value.filter(e => {
      const deptId = toEmployeeDepartmentId(e);
      return deptId === departmentId && e.status !== 'ĐÃ_NGHỈ_VIỆC';
    }).length;
    const managerId = Number(d.manager_id ?? d.managerId ?? 0) || null;
    const managerFromDepartment = String(d.manager || d.manager_name || d.managerName || '').trim();
    const managerEmployee = managerId ? employeeLookup.value.get(managerId) : null;
    const managerName = managerFromDepartment || toEmployeeName(managerEmployee) || null;

    return {
      id: departmentId,
      name: d.departmentName || d.department_name || '',
      code: d.departmentCode || d.department_code || `DEPT-${departmentId}`,
      manager: managerName,
      manager_id: managerId,
      active: normalizeDepartmentStatus(d.status),
      employee_count: empCount,
      icon: d.icon || 'corporate_fare',
      parent_id: d.parent_id || d.parent_department_id || null
    };
  });
});

const filteredDepartments = computed(() => {
  let list = departments.value;
  if (filterActive.value !== 'ALL') {
    list = list.filter(d => d.active === filterActive.value);
  }
  if (searchQuery.value) {
    const q = searchQuery.value.toLowerCase();
    list = list.filter(d => 
      d.name.toLowerCase().includes(q) || 
      d.code.toLowerCase().includes(q)
    );
  }
  return list;
});

const stats = computed(() => [
  { label: 'Tổng số đơn vị', value: departments.value.length.toString(), icon: 'account_tree', semantic: 'brand' },
  { label: 'Đang vận hành', value: departments.value.filter(d => d.active).length.toString(), icon: 'check_circle', semantic: 'success' },
  { label: 'Tạm dừng / Hủy bỏ', value: departments.value.filter(d => !d.active).length.toString(), icon: 'schema', semantic: 'warning' }
]);

const iconOptions = ['corporate_fare', 'engineering', 'groups', 'web', 'payments', 'hub', 'apartment', 'meeting_room', 'account_balance', 'rocket_launch'];
const iconOptionsList = iconOptions.map(icon => ({ label: icon.split('_').join(' ').toUpperCase(), value: icon }));

const parentDeptOptions = computed(() => [
  { label: '-- Đơn vị cấp cao nhất --', value: null },
  ...departments.value.map(d => ({ label: d.name, value: d.id }))
]);

const emptyForm = {
  id: null, name: '', code: '', manager: '', manager_id: null,
  active: true, icon: 'corporate_fare', parent_id: null
};

const form = ref({ ...emptyForm });

const openModal = (type, dept = null) => {
  isEdit.value = type === 'edit';
  if (isEdit.value && dept) {
    form.value = {
      ...emptyForm,
      ...dept,
      manager: dept.manager || '',
      manager_id: dept.manager_id || null,
    };
  } else {
    form.value = { ...emptyForm };
  }
  if (!isEdit.value) {
    form.value.code = `DEPT-${(departments.value.length + 1).toString().padStart(3,'0')}`;
  }
  managerSuggestionOpen.value = false;
  showModal.value = true;
};

const closeModal = () => {
  managerSuggestionOpen.value = false;
  showModal.value = false;
};

const managerCandidates = computed(() => {
  return employeesRaw.value
    .map((employee) => {
      const id = toEmployeeId(employee);
      const code = toEmployeeCode(employee);
      const name = toEmployeeName(employee);
      if (id <= 0 || !name) return null;
      return {
        value: id,
        label: name,
        meta: code ? `Mã NV: ${code}` : `ID: ${id}`,
        keywords: `${name} ${code}`.toLowerCase(),
      };
    })
    .filter(Boolean);
});

const managerSuggestions = computed(() => {
  if (!managerSuggestionOpen.value) return [];
  const keyword = String(form.value.manager || '').trim().toLowerCase();
  const list = managerCandidates.value;
  if (!keyword) return list.slice(0, 8);
  return list.filter((item) => item.keywords.includes(keyword)).slice(0, 8);
});

const onManagerKeywordInput = () => {
  form.value.manager_id = null;
  managerSuggestionOpen.value = true;
};

const selectManager = (employee) => {
  form.value.manager = employee.label;
  form.value.manager_id = employee.value;
  managerSuggestionOpen.value = false;
};

const onManagerInputBlur = () => {
  window.setTimeout(() => {
    managerSuggestionOpen.value = false;
  }, 120);
};

const resolveManagerId = (keyword) => {
  if (form.value.manager_id) return Number(form.value.manager_id);
  const q = String(keyword || '').trim().toLowerCase();
  if (!q) return null;
  const found = managerCandidates.value.find((employee) =>
    employee.keywords.includes(q)
  );
  return found ? Number(found.value) : null;
};

const handleSave = async () => {
  if (!form.value.name || !form.value.code) {
    await showAlert('Thiếu thông tin', 'Vui lòng cung cấp đầy đủ tên và mã định danh phòng ban!');
    return;
  }

  const managerKeyword = String(form.value.manager || '').trim();
  const managerId = resolveManagerId(form.value.manager);
  if (managerKeyword && !managerId) {
    await showAlert(
      'Thiếu dữ liệu hợp lệ',
      'Vui lòng chọn Trưởng đơn vị từ danh sách gợi ý để đảm bảo liên kết đúng nhân sự.',
    );
    managerSuggestionOpen.value = true;
    return;
  }

  const payload = {
    department_name: String(form.value.name || '').trim(),
    parent_department_id: form.value.parent_id ? Number(form.value.parent_id) : null,
    manager_id: managerId,
    status: Boolean(form.value.active),
  };

  const finalPayload = Object.fromEntries(
    Object.entries(payload).filter(([, value]) => value !== null && value !== '')
  );

  if (!finalPayload.department_name) {
    await showAlert('Thiếu thông tin', 'Tên đơn vị tổ chức không được để trống.');
    return;
  }

  try {
    if (isEdit.value) {
      await apiRequest(`/departments/${form.value.id}`, {
        method: 'PATCH',
        body: JSON.stringify(finalPayload),
      });
    } else {
      await apiRequest('/departments', {
        method: 'POST',
        body: JSON.stringify({
          ...finalPayload,
          department_code: String(form.value.code || '').trim(),
        }),
      });
    }
    await loadData();
    closeModal();
  } catch (error) {
    await showAlert('Lưu thất bại', error?.message || 'Không thể lưu phòng ban.');
  }
};

const confirmDissolve = async (dept) => {
  if (dept.employee_count > 0) {
    await showAlert('Ràng buộc dữ liệu', `Phòng ${dept.name} đang quản lý ${dept.employee_count} nhân viên. Vui lòng điều chuyển nhân sự trước khi thực hiện!`);
    return;
  }
  const ok = await showConfirm('Cấu trúc tổ chức', `Xác nhận thay đổi trạng thái hoạt động của đơn vị ${dept.name}?`);
  if (ok) {
    try {
      await apiRequest(`/departments/${dept.id}`, {
        method: 'PATCH',
        body: JSON.stringify({ status: !dept.active }),
      });
      await loadData();
    } catch (error) {
      await showAlert('Cập nhật thất bại', error?.message || 'Không thể cập nhật trạng thái phòng ban.');
    }
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

.modal-fade-enter-active,
.modal-fade-leave-active {
  transition: opacity 0.2s ease;
}

.modal-fade-enter-from,
.modal-fade-leave-to {
  opacity: 0;
}
</style>
