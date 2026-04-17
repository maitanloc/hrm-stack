<template>
  <ApiCrudModule
    title="HRM Core - Nhan vien"
    subtitle="Danh sach, chi tiet, tao moi va cap nhat ho so nhan vien"
    entity-name="nhan vien"
    :crud="crud"
    :columns="columns"
    :form-fields="formFields"
    :filter-fields="filterFields"
    :detail-fields="detailFields"
  />
</template>

<script setup>
import { onMounted } from 'vue';
import ApiCrudModule from '@/components/api/ApiCrudModule.vue';
import { useCrudModule } from '@/composables/useCrudModule.js';
import { useLookupOptions } from '@/composables/useLookupOptions.js';
import { formatDate, toBadgeMap } from '@/views/api/formatters.js';

const lookup = useLookupOptions();

const crud = useCrudModule({
  listPath: '/employees',
  defaultFilters: {
    status: '',
    department_id: '',
  },
  mapItem: (item) => ({
    ...item,
    id: item.employee_id,
    department_name: item.department_name || '-',
  }),
  mapDetail: (item) => ({
    ...item,
    id: item.employee_id,
  }),
  initialForm: () => ({
    employee_code: '',
    full_name: '',
    company_email: '',
    password: '',
    hire_date: '',
    date_of_birth: '',
    gender: 'NAM',
    phone_number: '',
    status: 'THỬ_VIỆC',
    department_id: '',
    position_id: '',
    nationality_id: 1,
  }),
  toCreatePayload: (form) => ({
    employee_code: form.employee_code,
    full_name: form.full_name,
    company_email: form.company_email,
    password: form.password,
    hire_date: form.hire_date,
    date_of_birth: form.date_of_birth || undefined,
    gender: form.gender || undefined,
    phone_number: form.phone_number || undefined,
    status: form.status || undefined,
    department_id: form.department_id ? Number(form.department_id) : undefined,
    position_id: form.position_id ? Number(form.position_id) : undefined,
    nationality_id: form.nationality_id ? Number(form.nationality_id) : undefined,
  }),
  toUpdatePayload: (form) => ({
    full_name: form.full_name,
    company_email: form.company_email,
    password: form.password || undefined,
    hire_date: form.hire_date || undefined,
    date_of_birth: form.date_of_birth || undefined,
    gender: form.gender || undefined,
    phone_number: form.phone_number || undefined,
    status: form.status || undefined,
    department_id: form.department_id ? Number(form.department_id) : undefined,
    position_id: form.position_id ? Number(form.position_id) : undefined,
    nationality_id: form.nationality_id ? Number(form.nationality_id) : undefined,
  }),
});

const statusBadge = toBadgeMap([
  ['ĐANG_LÀM_VIỆC', 'success'],
  ['THỬ_VIỆC', 'warning'],
  ['ĐÃ_NGHỈ_VIỆC', 'danger'],
]);

const columns = [
  { key: 'employee_code', label: 'Ma NV' },
  { key: 'full_name', label: 'Ho ten' },
  { key: 'department_name', label: 'Phong ban' },
  { key: 'company_email', label: 'Email cong ty' },
  { key: 'hire_date', label: 'Ngay vao lam', formatter: formatDate },
  { key: 'status', label: 'Trang thai', badge: true, badgeMap: statusBadge },
];

const detailFields = [
  { key: 'employee_code', label: 'Ma nhan vien' },
  { key: 'full_name', label: 'Ho ten' },
  { key: 'company_email', label: 'Email cong ty' },
  { key: 'phone_number', label: 'So dien thoai' },
  { key: 'gender', label: 'Gioi tinh' },
  { key: 'date_of_birth', label: 'Ngay sinh', formatter: formatDate },
  { key: 'hire_date', label: 'Ngay vao lam', formatter: formatDate },
  { key: 'department_name', label: 'Phong ban' },
  { key: 'position_name', label: 'Chuc danh' },
  { key: 'status', label: 'Trang thai' },
];

const filterFields = [
  {
    key: 'status',
    type: 'select',
    placeholder: 'Trang thai',
    options: [
      { value: 'ĐANG_LÀM_VIỆC', label: 'Dang lam viec' },
      { value: 'THỬ_VIỆC', label: 'Thu viec' },
      { value: 'ĐÃ_NGHỈ_VIỆC', label: 'Da nghi viec' },
    ],
  },
  {
    key: 'department_id',
    type: 'select',
    placeholder: 'Phong ban',
    options: () => lookup.departments.value,
  },
];

const formFields = [
  { key: 'employee_code', label: 'Ma nhan vien', required: true, placeholder: 'VD: EMP001' },
  { key: 'full_name', label: 'Ho ten', required: true, placeholder: 'Nguyen Van A' },
  { key: 'company_email', label: 'Email cong ty', type: 'email', required: true, placeholder: 'a@company.com' },
  { key: 'password', label: 'Mat khau', type: 'password', placeholder: 'Bo trong de dung ma NV (khi tao moi)' },
  { key: 'hire_date', label: 'Ngay vao lam', type: 'date', required: true },
  { key: 'date_of_birth', label: 'Ngay sinh', type: 'date' },
  {
    key: 'gender',
    label: 'Gioi tinh',
    type: 'select',
    options: [
      { value: 'NAM', label: 'Nam' },
      { value: 'NỮ', label: 'Nu' },
      { value: 'KHÁC', label: 'Khac' },
    ],
  },
  { key: 'phone_number', label: 'So dien thoai' },
  {
    key: 'status',
    label: 'Trang thai',
    type: 'select',
    options: [
      { value: 'ĐANG_LÀM_VIỆC', label: 'Dang lam viec' },
      { value: 'THỬ_VIỆC', label: 'Thu viec' },
      { value: 'ĐÃ_NGHỈ_VIỆC', label: 'Da nghi viec' },
    ],
  },
  {
    key: 'department_id',
    label: 'Phong ban',
    type: 'select',
    options: () => lookup.departments.value,
  },
  {
    key: 'position_id',
    label: 'Chuc danh',
    type: 'select',
    options: () => lookup.positions.value,
  },
  { key: 'nationality_id', label: 'Quoc tich ID', type: 'number' },
];

onMounted(async () => {
  await lookup.bootstrap(['departments', 'positions']);
  await crud.fetchList();
});
</script>
