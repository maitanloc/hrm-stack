<template>
  <ApiCrudModule
    title="HRM Core - Hop dong"
    subtitle="Quan ly hop dong lao dong, gia han va trang thai"
    entity-name="hop dong"
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
import { formatCurrency, formatDate, toBadgeMap } from '@/views/api/formatters.js';

const lookup = useLookupOptions();

const crud = useCrudModule({
  listPath: '/contracts',
  defaultFilters: {
    status: '',
    employee_id: '',
  },
  mapItem: (item) => ({
    ...item,
    id: item.contract_id,
  }),
  initialForm: () => ({
    contract_code: '',
    employee_id: '',
    contract_type_id: '',
    contract_number: '',
    sign_date: '',
    effective_date: '',
    expiry_date: '',
    position_id: '',
    department_id: '',
    basic_salary: 0,
    gross_salary: 0,
    net_salary: 0,
    work_location: '',
    job_title: '',
    status: 'CÓ_HIỆU_LỰC',
  }),
  toCreatePayload: (form) => ({
    contract_code: form.contract_code || undefined,
    employee_id: Number(form.employee_id),
    contract_type_id: Number(form.contract_type_id),
    contract_number: form.contract_number || undefined,
    sign_date: form.sign_date,
    effective_date: form.effective_date,
    expiry_date: form.expiry_date || undefined,
    position_id: form.position_id ? Number(form.position_id) : undefined,
    department_id: form.department_id ? Number(form.department_id) : undefined,
    basic_salary: Number(form.basic_salary || 0),
    gross_salary: Number(form.gross_salary || 0),
    net_salary: form.net_salary ? Number(form.net_salary) : undefined,
    work_location: form.work_location || undefined,
    job_title: form.job_title || undefined,
    status: form.status || undefined,
  }),
  toUpdatePayload: (form) => ({
    employee_id: form.employee_id ? Number(form.employee_id) : undefined,
    contract_type_id: form.contract_type_id ? Number(form.contract_type_id) : undefined,
    contract_number: form.contract_number || undefined,
    sign_date: form.sign_date || undefined,
    effective_date: form.effective_date || undefined,
    expiry_date: form.expiry_date || undefined,
    position_id: form.position_id ? Number(form.position_id) : undefined,
    department_id: form.department_id ? Number(form.department_id) : undefined,
    basic_salary: form.basic_salary !== '' ? Number(form.basic_salary) : undefined,
    gross_salary: form.gross_salary !== '' ? Number(form.gross_salary) : undefined,
    net_salary: form.net_salary !== '' ? Number(form.net_salary) : undefined,
    work_location: form.work_location || undefined,
    job_title: form.job_title || undefined,
    status: form.status || undefined,
  }),
});

const statusBadge = toBadgeMap([
  ['CÓ_HIỆU_LỰC', 'success'],
  ['CHỜ_HIỆU_LỰC', 'info'],
  ['HẾT_HẠN', 'warning'],
  ['ĐÃ_CHẤM_DỨT', 'danger'],
]);

const columns = [
  { key: 'contract_code', label: 'Ma HD' },
  { key: 'employee_name', label: 'Nhan vien' },
  { key: 'contract_type_name', label: 'Loai HD' },
  { key: 'effective_date', label: 'Ngay hieu luc', formatter: formatDate },
  { key: 'expiry_date', label: 'Ngay het han', formatter: formatDate },
  { key: 'status', label: 'Trang thai', badge: true, badgeMap: statusBadge },
];

const detailFields = [
  { key: 'contract_code', label: 'Ma hop dong' },
  { key: 'contract_number', label: 'So hop dong' },
  { key: 'employee_name', label: 'Nhan vien' },
  { key: 'contract_type_name', label: 'Loai hop dong' },
  { key: 'sign_date', label: 'Ngay ky', formatter: formatDate },
  { key: 'effective_date', label: 'Ngay hieu luc', formatter: formatDate },
  { key: 'expiry_date', label: 'Ngay het han', formatter: formatDate },
  { key: 'basic_salary', label: 'Luong co ban', formatter: formatCurrency },
  { key: 'gross_salary', label: 'Luong gross', formatter: formatCurrency },
  { key: 'net_salary', label: 'Luong net', formatter: formatCurrency },
  { key: 'status', label: 'Trang thai' },
];

const filterFields = [
  {
    key: 'status',
    type: 'select',
    placeholder: 'Trang thai',
    options: [
      { value: 'CÓ_HIỆU_LỰC', label: 'Co hieu luc' },
      { value: 'CHỜ_HIỆU_LỰC', label: 'Cho hieu luc' },
      { value: 'HẾT_HẠN', label: 'Het han' },
      { value: 'ĐÃ_CHẤM_DỨT', label: 'Da cham dut' },
    ],
  },
  {
    key: 'employee_id',
    type: 'select',
    placeholder: 'Nhan vien',
    options: () => lookup.employees.value,
  },
];

const formFields = [
  { key: 'contract_code', label: 'Ma hop dong', placeholder: 'Bo trong de sinh tu dong' },
  {
    key: 'employee_id',
    label: 'Nhan vien',
    type: 'select',
    required: true,
    options: () => lookup.employees.value,
  },
  {
    key: 'contract_type_id',
    label: 'Loai hop dong',
    type: 'select',
    required: true,
    options: () => lookup.contractTypes.value,
  },
  { key: 'contract_number', label: 'So hop dong' },
  { key: 'sign_date', label: 'Ngay ky', type: 'date', required: true },
  { key: 'effective_date', label: 'Ngay hieu luc', type: 'date', required: true },
  { key: 'expiry_date', label: 'Ngay het han', type: 'date' },
  { key: 'position_id', label: 'Chuc danh', type: 'select', options: () => lookup.positions.value },
  { key: 'department_id', label: 'Phong ban', type: 'select', options: () => lookup.departments.value },
  { key: 'basic_salary', label: 'Luong co ban', type: 'number' },
  { key: 'gross_salary', label: 'Luong gross', type: 'number' },
  { key: 'net_salary', label: 'Luong net', type: 'number' },
  { key: 'work_location', label: 'Noi lam viec' },
  { key: 'job_title', label: 'Chuc danh ghi tren HD' },
  {
    key: 'status',
    label: 'Trang thai',
    type: 'select',
    options: [
      { value: 'CÓ_HIỆU_LỰC', label: 'Co hieu luc' },
      { value: 'CHỜ_HIỆU_LỰC', label: 'Cho hieu luc' },
      { value: 'HẾT_HẠN', label: 'Het han' },
      { value: 'ĐÃ_CHẤM_DỨT', label: 'Da cham dut' },
    ],
  },
];

onMounted(async () => {
  await lookup.bootstrap(['employees', 'departments', 'positions', 'contractTypes']);
  await crud.fetchList();
});
</script>
