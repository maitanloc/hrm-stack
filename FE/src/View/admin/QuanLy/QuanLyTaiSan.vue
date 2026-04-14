<template>
  <div class="space-y-6 pb-8">
    <!-- Header Area -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 bg-transparent text-left">
      <div class="bg-transparent text-left">
        <h1 class="text-xl font-semibold text-[var(--sys-text-primary)] mb-1">Quản lý Tài sản & Thiết bị</h1>
        <p class="text-sm text-[var(--sys-text-secondary)]">Theo dõi vòng đời, cấp phát và bảo trì tài sản cố định của doanh nghiệp.</p>
      </div>
      <div class="flex flex-col md:flex-row items-center gap-3 flex-1 w-full max-w-3xl bg-transparent">
        <!-- Search Bar -->
        <div class="relative flex-1 w-full group">
          <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-[20px] text-[var(--sys-brand-solid)]">search</span>
          <input 
            v-model="searchQuery"
            type="text" 
            placeholder="Tìm kiếm mã tài sản, tên thiết bị..." 
            class="w-full h-10 pl-10 pr-4 rounded-md bg-[var(--sys-bg-surface)] border border-[var(--sys-border-strong)] text-sm text-[var(--sys-text-primary)] focus:outline-none focus:border-[var(--sys-brand-solid)] focus:ring-1 focus:ring-[var(--sys-brand-solid)] transition-all placeholder:text-[var(--sys-text-disabled)]"
          >
        </div>
        
        <div class="flex items-center gap-3 w-full md:w-auto bg-transparent shrink-0">
          <Dropdown v-model="filterCategory" :options="categoryOptions" class="min-w-[160px] h-10" />
          <button @click="openAddModal" class="h-10 px-4 bg-[var(--sys-brand-solid)] rounded-md font-semibold text-white hover:brightness-90 active:opacity-90 transition-all flex items-center gap-2 text-sm whitespace-nowrap shadow-sm">
            <span class="material-symbols-outlined text-[20px]">devices_other</span>
            Thêm tài sản
          </button>
        </div>
      </div>
    </div>

    <!-- Stats Section -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
      <div v-for="stat in stats" :key="stat.label" 
        class="bg-[var(--sys-bg-surface)] p-4 rounded-lg border border-[var(--sys-border-subtle)] shadow-sm hover:border-[var(--sys-brand-solid)] transition-all group flex items-center gap-4">
        <div :class="`w-12 h-12 rounded-md flex items-center justify-center transition-all ${
          stat.semantic === 'brand' ? 'bg-[var(--sys-brand-soft)] text-[var(--sys-brand-solid)]' :
          stat.semantic === 'success' ? 'bg-[var(--sys-success-soft)] text-[var(--sys-success-text)]' :
          stat.semantic === 'danger' ? 'bg-[var(--sys-danger-soft)] text-[var(--sys-danger-text)]' :
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
              <th class="px-4 py-3 text-[12px] font-semibold text-[var(--sys-text-secondary)] border-b border-[var(--sys-border-subtle)] uppercase tracking-wider">Định danh thiết bị</th>
              <th class="px-4 py-3 text-[12px] font-semibold text-[var(--sys-text-secondary)] border-b border-[var(--sys-border-subtle)] uppercase tracking-wider">Danh mục TSCĐ</th>
              <th class="px-4 py-3 text-[12px] font-semibold text-[var(--sys-text-secondary)] border-b border-[var(--sys-border-subtle)] uppercase tracking-wider">Đối tượng thụ hưởng</th>
              <th class="px-4 py-3 text-[12px] font-semibold text-[var(--sys-text-secondary)] border-b border-[var(--sys-border-subtle)] uppercase tracking-wider text-center">Trạng thái</th>
              <th class="px-4 py-3 text-[12px] font-semibold text-[var(--sys-text-secondary)] border-b border-[var(--sys-border-subtle)] uppercase tracking-wider text-right">Thao tác</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-[var(--sys-border-subtle)]">
            <tr v-for="asset in filteredAssets" :key="asset.id" 
              class="group hover:bg-[var(--sys-bg-hover)] transition-colors cursor-default">
              <td class="px-4 py-3 whitespace-nowrap bg-transparent">
                <div class="flex flex-col bg-transparent">
                  <span class="text-[13px] font-semibold text-[var(--sys-text-primary)] transition-colors line-clamp-1 max-w-[280px]">{{ asset.name }}</span>
                  <span class="text-[12px] text-[var(--sys-text-secondary)]">Mã: {{ asset.code }}</span>
                </div>
              </td>
              <td class="px-4 py-3 whitespace-nowrap bg-transparent">
                <span class="text-[13px] font-medium text-[var(--sys-text-secondary)]">{{ asset.category }}</span>
              </td>
              <td class="px-4 py-3 whitespace-nowrap bg-transparent">
                <div v-if="asset.user" class="flex items-center gap-2">
                  <div class="w-6 h-6 rounded-md bg-[var(--sys-brand-soft)] text-[var(--sys-brand-solid)] flex items-center justify-center text-[10px] font-bold border border-[var(--sys-brand-border)]">
                    {{ asset.user[0] }}
                  </div>
                  <span class="text-[13px] font-medium text-[var(--sys-text-primary)]">{{ asset.user }}</span>
                </div>
                <span v-else class="text-[12px] text-[var(--sys-text-disabled)] italic">Đang dự trữ tại kho</span>
              </td>
              <td class="px-4 py-3 text-center whitespace-nowrap bg-transparent">
                <span :class="[
                  'px-2 py-0.5 rounded-md text-[11px] font-semibold inline-flex items-center gap-1.5 border transition-all uppercase tracking-wide',
                  getStatusBadgeClass(asset.status)
                ]">
                  <span class="w-1.5 h-1.5 rounded-full shrink-0" :class="getStatusDotClass(asset.status)"></span>
                  {{ asset.status.split('_').join(' ') }}
                </span>
              </td>
              <td class="px-4 py-3 text-right whitespace-nowrap bg-transparent">
                  <div class="flex items-center justify-end gap-1">
                    <button @click="editAsset(asset)" class="w-8 h-8 flex items-center justify-center rounded-md text-[var(--sys-text-secondary)] hover:bg-[var(--sys-brand-soft)] hover:text-[var(--sys-brand-solid)] transition-all" title="Chỉnh sửa">
                      <span class="material-symbols-outlined text-[18px]">edit_square</span>
                    </button>
                    <button v-if="!asset.user" @click="assignAsset(asset)" class="w-8 h-8 flex items-center justify-center rounded-md text-[var(--sys-text-secondary)] hover:bg-[var(--sys-brand-soft)] hover:text-[var(--sys-brand-solid)] transition-all" title="Bàn giao">
                      <span class="material-symbols-outlined text-[18px]">person_add</span>
                    </button>
                    <button v-else @click="recoverAsset(asset)" class="w-8 h-8 flex items-center justify-center rounded-md text-[var(--sys-text-secondary)] hover:bg-[var(--sys-warning-soft)] hover:text-[var(--sys-warning-solid)] transition-all" title="Thu hồi">
                      <span class="material-symbols-outlined text-[18px]">assignment_return</span>
                    </button>
                    <button @click="confirmLiquidate(asset)" class="w-8 h-8 flex items-center justify-center rounded-md text-[var(--sys-text-secondary)] hover:bg-[var(--sys-danger-soft)] hover:text-[var(--sys-danger-solid)] transition-all" title="Thanh lý">
                      <span class="material-symbols-outlined text-[18px]">delete</span>
                    </button>
                  </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <div class="px-4 py-3 bg-[var(--sys-bg-page)] border-t border-[var(--sys-border-subtle)] flex justify-between items-center text-[12px] font-medium text-[var(--sys-text-secondary)]">
        <span>Ghi nhận {{ assets.length }} thực thể tài sản hợp lệ</span>
      </div>
    </div>

    <!-- Modal System -->
    <Teleport to="body">
      <Transition name="modal-fade">
        <div v-if="showModal" class="fixed inset-0 z-[10000] flex items-center justify-center p-4">
          <div class="fixed inset-0 w-screen h-screen bg-black/50 z-[9999]" @click="closeModal"></div>
          <div class="relative z-[10000] bg-[var(--sys-bg-surface-elevated)] border border-[var(--sys-border-subtle)] w-full max-w-2xl max-h-[90vh] rounded-lg shadow-xl overflow-hidden flex flex-col text-left">
            <!-- Modal Header -->
            <div class="px-6 py-4 border-b border-[var(--sys-border-subtle)] flex items-center justify-between bg-[var(--sys-bg-surface)]">
              <div class="bg-transparent text-left flex flex-col">
                <h3 class="text-lg font-semibold text-[var(--sys-text-primary)] m-0 uppercase tracking-tight">
                  {{ !editMode ? 'Khai báo tài sản mới' : (form.assigned_to ? 'Cấu hình thông tin tài sản' : 'Bàn giao tài sản cho nhân viên') }}
                </h3>
                <p class="text-sm text-[var(--sys-text-secondary)] mt-1">
                  {{ !editMode ? 'Khởi tạo bản ghi tài sản mới vào hệ thống kho.' : (form.assigned_to ? 'Cập nhật thông số kỹ thuật hoặc thông tin bảo trì.' : 'Thực hiện quy trình bàn giao thiết bị cho nhân sự thụ hưởng.') }}
                </p>
              </div>
              <button @click="closeModal" class="w-8 h-8 flex items-center justify-center rounded-md hover:bg-[var(--sys-bg-hover)] transition-all text-[var(--sys-text-secondary)]">
                <span class="material-symbols-outlined text-xl">close</span>
              </button>
            </div>

            <!-- Modal Body -->
            <div class="flex-1 overflow-y-auto p-6 custom-scrollbar bg-transparent">
              <div class="space-y-6 bg-transparent border-none">
                <div class="space-y-1.5 bg-transparent border-none">
                  <label class="text-[13px] font-medium text-[var(--sys-text-primary)] block">Tên thiết bị / Tài sản chính thức *</label>
                  <input v-model="form.name" type="text" placeholder="VD: MacBook Pro 14 M3..." class="w-full h-10 px-3 bg-[var(--sys-bg-page)] border border-[var(--sys-border-strong)] rounded-md text-sm text-[var(--sys-text-primary)] focus:border-[var(--sys-brand-solid)] outline-none transition-all">
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 bg-transparent border-none">
                  <div class="space-y-1.5 bg-transparent border-none">
                    <label class="text-[13px] font-medium text-[var(--sys-text-primary)] block">Mã Asset Tag *</label>
                    <input v-model="form.code" type="text" placeholder="VD: TS-2024-0001" class="w-full h-10 px-3 bg-[var(--sys-bg-page)] border border-[var(--sys-border-strong)] rounded-md text-sm text-[var(--sys-text-primary)] focus:border-[var(--sys-brand-solid)] outline-none transition-all">
                  </div>
                  <div class="space-y-1.5 bg-transparent border-none text-left">
                    <label class="text-[13px] font-medium text-[var(--sys-text-primary)] block">Danh mục tài sản *</label>
                    <Dropdown v-model="form.category" :options="categoryOptionsForm" class="w-full h-10" />
                  </div>
                </div>

                <div class="space-y-1.5 bg-transparent border-none text-left">
                  <label class="text-[13px] font-medium text-[var(--sys-text-primary)] block">Trạng thái vận hành</label>
                  <Dropdown v-model="form.status" :options="statusOptionsForm" class="w-full h-10" />
                </div>

                <div v-if="form.status === 'ĐANG_SỬ_DỤNG'" class="p-4 bg-[var(--sys-bg-page)] rounded-md border border-[var(--sys-border-subtle)] space-y-3">
                  <label class="text-[13px] font-semibold text-[var(--sys-brand-solid)] block uppercase tracking-wide">Chủ thể thụ hưởng</label>
                  <input v-model="form.user" type="text" placeholder="Nhập tên nhân sự hoặc mã NV..." class="w-full h-10 px-3 bg-white border border-[var(--sys-border-strong)] rounded-md text-sm text-[var(--sys-text-primary)] focus:border-[var(--sys-brand-solid)] outline-none transition-all">
                </div>
              </div>
            </div>

            <!-- Modal Footer -->
            <div class="px-6 py-4 border-t border-[var(--sys-border-subtle)] bg-[var(--sys-bg-surface)] flex justify-end gap-3">
              <button @click="closeModal" class="px-4 py-2 text-sm font-medium text-[var(--sys-text-secondary)] hover:bg-[var(--sys-bg-hover)] rounded-md transition-all uppercase tracking-wide">Hủy bỏ</button>
              <button @click="handleSave" class="px-6 py-2 bg-[var(--sys-brand-solid)] text-white rounded-md font-semibold text-sm hover:brightness-90 transition-all uppercase tracking-wide">
                {{ !editMode ? 'Xác nhận khởi tạo' : (form.assigned_to ? 'Cập nhật tài sản' : 'Xác nhận bàn giao') }}
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
 * TRANG QUẢN TRỊ TÀI SẢN - PHIÊN BẢN ENTERPRISE SaaS
 * Tuân thủ 7 Golden Rules:
 * - Font Inter 14px (text-sm), Tỉ lệ Table cao (text-13px)
 * - Bo góc chuẩn B2B: 6px (MD) cho Input/Button, 8px (LG) cho Card/Modal/Table
 * - Hệ màu Blue/White đồng nhất cho Action Icons
 */
import { ref, computed, onMounted } from 'vue';
import Dropdown from '@/components/Dropdown.vue';
import { useConfirm } from '@/composables/useConfirm';
import { BE_API_BASE, getAccessToken } from '@/services/runtimeConfig.js';
import { handleUnauthorized } from '@/services/session.js';

const { showAlert, showConfirm } = useConfirm();

const searchQuery = ref('');
const filterCategory = ref('ALL');
const filterStatus = ref('ALL');
const showModal = ref(false);
const editMode = ref(false);
const assetsRaw = ref([]);
const employeesRaw = ref([]);
const assignmentsRaw = ref([]);

const categories = ['Laptop', 'Màn hình', 'Bàn phím', 'Chuột', 'Máy in', 'Server'];
const categoryOptions = computed(() => [
  { label: 'Tất cả danh mục', value: 'ALL' },
  ...categories.map(cat => ({ label: cat, value: cat }))
]);

const statusOptions = [
  { label: 'Mọi trạng thái', value: 'ALL' },
  { label: 'Sẵn sàng / Trong kho', value: 'TRONG_KHO' },
  { label: 'Đang sử dụng', value: 'ĐANG_SỬ_DỤNG' },
  { label: 'Đang bảo trì / Hỏng', value: 'HỎNG' },
  { label: 'Đã thanh lý', value: 'ĐÃ_THANH_LÝ' }
];

const categoryOptionsForm = categories.map(cat => ({ label: cat, value: cat }));
const statusOptionsForm = [
  { label: 'Sẵn sàng (Trong kho)', value: 'TRONG_KHO' },
  { label: 'Đang bàn giao sử dụng', value: 'ĐANG_SỬ_DỤNG' },
  { label: 'Đang bảo trì / Hỏng', value: 'HỎNG' },
  { label: 'Đã thanh lý định kỳ', value: 'ĐÃ_THANH_LÝ' }
];

const authHeaders = () => {
  const token = getAccessToken();
  if (!token) throw new Error('Thiếu access token');
  return {
    Authorization: `Bearer ${token}`,
    'Content-Type': 'application/json',
  };
};

const apiRequest = async (path, { method = 'GET', body } = {}) => {
  const response = await fetch(`${BE_API_BASE}${path}`, {
    method,
    headers: authHeaders(),
    body: body ? JSON.stringify(body) : undefined,
  });
  if (response.status === 401) {
    handleUnauthorized();
    throw new Error('Phiên đăng nhập đã hết hạn');
  }
  const payload = await response.json().catch(() => ({}));
  if (!response.ok || payload?.success === false) {
    throw new Error(payload?.message || `Request failed (${response.status})`);
  }
  return payload?.data;
};

const fetchList = async (path, perPage = 1000) => {
  const data = await apiRequest(`${path}?page=1&per_page=${perPage}`);
  return Array.isArray(data) ? data : [];
};

const loadData = async () => {
  try {
    const [assets, employees, assignments] = await Promise.all([
      fetchList('/assets', 1000),
      fetchList('/employees', 2000),
      fetchList('/asset-assignments', 3000),
    ]);
    assetsRaw.value = assets;
    employeesRaw.value = employees;
    assignmentsRaw.value = assignments;
  } catch (error) {
    await showAlert('Không tải được dữ liệu', error?.message || 'Không thể tải dữ liệu tài sản.');
  }
};

onMounted(() => {
  void loadData();
});

const latestAssignmentMap = computed(() => {
  const map = new Map();
  assignmentsRaw.value.forEach((assignment) => {
    const assetId = Number(assignment.assetId ?? assignment.asset_id);
    const current = map.get(assetId);
    const currentId = Number(current?.assignmentId ?? current?.assignment_id ?? 0);
    const nextId = Number(assignment.assignmentId ?? assignment.assignment_id ?? 0);
    if (!current || nextId >= currentId) {
      map.set(assetId, assignment);
    }
  });
  return map;
});

const isAssignmentInUse = (assignment) => {
  const status = String(assignment?.status || '').trim().toUpperCase()
  return ['ĐANG_SỬ_DỤNG', 'ASSIGNED', 'ACTIVE', 'IN_USE'].includes(status)
}

const assets = computed(() => {
  return assetsRaw.value.map(a => {
    const assetId = Number(a.assetId ?? a.asset_id ?? a.id);
    const latest = latestAssignmentMap.value.get(assetId);
    const using = isAssignmentInUse(latest);
    const employeeId = using ? Number(latest.employeeId ?? latest.employee_id) : null;
    const emp = using
      ? employeesRaw.value.find((e) => Number(e.employeeId ?? e.employee_id ?? e.id) === employeeId)
      : null;
    return {
      id: assetId,
      name: a.assetName ?? a.asset_name ?? a.name ?? '',
      code: a.assetCode ?? a.asset_code ?? a.code ?? '',
      category: a.category ?? a.category_name ?? 'Khác',
      user: emp ? (emp.fullName ?? emp.full_name ?? '') : null,
      assigned_to: employeeId,
      status: a.status ?? 'TRONG_KHO'
    };
  });
});

const stats = computed(() => [
  { label: 'Tổng số thiết bị', value: assets.value.length.toString(), icon: 'devices_other', semantic: 'brand' },
  { label: 'Đang bàn giao', value: assets.value.filter(a => a.status === 'ĐANG_SỬ_DỤNG').length.toString(), icon: 'assignment_ind', semantic: 'brand' },
  { label: 'Trong kho dự phòng', value: assets.value.filter(a => a.status === 'TRONG_KHO').length.toString(), icon: 'warehouse', semantic: 'success' },
  { label: 'Bảo trì hệ thống', value: assets.value.filter(a => a.status === 'HỎNG').length.toString(), icon: 'settings_suggest', semantic: 'danger' }
]);

const emptyForm = {
  id: null,
  name: '',
  code: '',
  category: 'Laptop',
  status: 'TRONG_KHO',
  user: ''
};

const form = ref({ ...emptyForm });

const filteredAssets = computed(() => {
  let list = assets.value;
  if (filterCategory.value !== 'ALL') {
    list = list.filter(a => a.category === filterCategory.value);
  }
  if (filterStatus.value !== 'ALL') {
    list = list.filter(a => a.status === filterStatus.value);
  }
  if (searchQuery.value) {
    const q = searchQuery.value.toLowerCase();
    list = list.filter(a => 
      a.name.toLowerCase().includes(q) || 
      a.code.toLowerCase().includes(q) || 
      (a.user && a.user.toLowerCase().includes(q))
    );
  }
  return list;
});

const getStatusBadgeClass = (status) => {
  switch (status) {
    case 'ĐANG_SỬ_DỤNG': return 'bg-[var(--sys-brand-soft)] text-[var(--sys-brand-solid)] border-[var(--sys-brand-border)]';
    case 'TRONG_KHO': return 'bg-[var(--sys-success-soft)] text-[var(--sys-success-text)] border-[var(--sys-success-border)]';
    case 'HỎNG': return 'bg-[var(--sys-warning-soft)] text-[var(--sys-warning-text)] border-[var(--sys-warning-border)]';
    case 'ĐÃ_THANH_LÝ': return 'bg-[var(--sys-danger-soft)] text-[var(--sys-danger-text)] border-[var(--sys-danger-border)]';
    default: return 'bg-[var(--sys-bg-hover)] text-[var(--sys-text-secondary)] border-[var(--sys-border-subtle)]';
  }
};

const getStatusDotClass = (status) => {
  switch (status) {
    case 'ĐANG_SỬ_DỤNG': return 'bg-[var(--sys-brand-solid)]';
    case 'TRONG_KHO': return 'bg-[var(--sys-success-solid)]';
    case 'HỎNG': return 'bg-[var(--sys-warning-solid)]';
    case 'ĐÃ_THANH_LÝ': return 'bg-[var(--sys-danger-solid)]';
    default: return 'bg-[var(--sys-icon-default)] opacity-40';
  }
};

const openAddModal = () => {
  editMode.value = false;
  form.value = { ...emptyForm };
  form.value.code = `AST-${new Date().getFullYear()}-${Math.floor(1000 + Math.random() * 9000)}`;
  showModal.value = true;
};

const editAsset = (asset) => {
  editMode.value = true;
  form.value = { ...asset };
  showModal.value = true;
};

const closeModal = () => { showModal.value = false; };

const findEmployeeByKeyword = (keyword) => {
  const q = String(keyword || '').trim().toLowerCase();
  if (!q) return null;
  return employeesRaw.value.find((e) =>
    String(e.fullName || e.full_name || '').toLowerCase().includes(q) ||
    String(e.employeeCode || e.employee_code || '').toLowerCase().includes(q)
  ) || null;
};

const createAssignment = async ({ assetId, employeeId, status }) => {
  await apiRequest('/asset-assignments', {
    method: 'POST',
    body: {
      asset_id: Number(assetId),
      employee_id: Number(employeeId),
      status,
      assigned_date: new Date().toISOString().slice(0, 10),
    },
  })
};

const handleSave = async () => {
  if (!form.value.name || !form.value.code) {
    await showAlert('Thiếu thông tin', 'Vui lòng xác định tên gọi và mã tài sản!');
    return;
  }
  
  let assigned_id = form.value.assigned_to;
  if (form.value.status === 'ĐANG_SỬ_DỤNG') {
    if (!form.value.user || !form.value.user.trim()) {
      await showAlert('Thiếu nhân sự', 'Vui lòng nhập tên hoặc mã nhân sự để bàn giao!');
      return;
    }

    const emp = findEmployeeByKeyword(form.value.user);

    if (!emp) {
      await showAlert('Không tìm thấy', `Không tìm thấy nhân sự khớp với "${form.value.user}". Vui lòng kiểm tra lại!`);
      return;
    }
    assigned_id = Number(emp.employeeId ?? emp.employee_id ?? emp.id);
  } else {
    // Nếu trạng thái khác ĐANG_SỬ_DỤNG, xóa người dùng được gán
    assigned_id = null;
  }

  const createDto = {
    asset_code: form.value.code,
    asset_name: form.value.name,
    status: form.value.status,
  };

  const updateDto = {
    asset_name: form.value.name,
    status: form.value.status,
  };

  try {
    let assetId = Number(form.value.id || 0);
    if (editMode.value && assetId > 0) {
      await apiRequest(`/assets/${assetId}`, {
        method: 'PATCH',
        body: updateDto,
      });
    } else {
      const created = await apiRequest('/assets', {
        method: 'POST',
        body: createDto,
      });
      assetId = Number(created?.asset_id ?? created?.assetId ?? created?.id ?? 0);
    }

    if (assetId > 0 && assigned_id) {
      await createAssignment({ assetId, employeeId: assigned_id, status: 'ĐANG_SỬ_DỤNG' });
      await apiRequest(`/assets/${assetId}`, {
        method: 'PATCH',
        body: { status: 'ĐANG_SỬ_DỤNG' },
      });
    }
    if (assetId > 0 && !assigned_id && form.value.assigned_to) {
      await createAssignment({
        assetId,
        employeeId: Number(form.value.assigned_to),
        status: 'ĐÃ_TRẢ',
      });
      await apiRequest(`/assets/${assetId}`, {
        method: 'PATCH',
        body: { status: 'TRONG_KHO' },
      });
    }

    await loadData();
    closeModal();
  } catch (error) {
    await showAlert('Lưu thất bại', error?.message || 'Không thể lưu tài sản.');
  }
};

const assignAsset = (asset) => {
  editAsset(asset);
  form.value.status = 'ĐANG_SỬ_DỤNG';
  form.value.user = ''; // Reset user input for new handover
};

const recoverAsset = async (asset) => {
  const ok = await showConfirm('Xác nhận thu hồi', `Bạn có chắc muốn thu hồi thiết bị ${asset.name} từ nhân sự ${asset.user}?`);
  if (ok) {
    try {
      if (asset.assigned_to) {
        await createAssignment({
          assetId: Number(asset.id),
          employeeId: Number(asset.assigned_to),
          status: 'ĐÃ_TRẢ',
        });
      }
      await apiRequest(`/assets/${asset.id}`, {
        method: 'PATCH',
        body: { status: 'TRONG_KHO' },
      });
      await loadData();
    } catch (error) {
      await showAlert('Thu hồi thất bại', error?.message || 'Không thể thu hồi tài sản.');
    }
  }
};

const confirmLiquidate = async (asset) => {
  const ok = await showConfirm('Xác nhận thanh lý', `Bạn có chắc muốn thực hiện quy trình thanh lý cho thiết bị ${asset.name}?`);
  if (ok) {
    try {
      if (asset.assigned_to) {
        await createAssignment({
          assetId: Number(asset.id),
          employeeId: Number(asset.assigned_to),
          status: 'ĐÃ_TRẢ',
        });
      }
      await apiRequest(`/assets/${asset.id}`, {
        method: 'PATCH',
        body: { status: 'ĐÃ_THANH_LÝ' },
      });
      await loadData();
    } catch (error) {
      await showAlert('Thanh lý thất bại', error?.message || 'Không thể cập nhật trạng thái thanh lý.');
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
