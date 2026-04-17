<template>
  <ApiCrudModule
    title="Recruitment - Vi tri tuyen dung"
    subtitle="Danh sach vi tri dang mo tuyen va cap nhat nhu cau"
    entity-name="vi tri"
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
  listPath: '/recruitment-positions',
  defaultFilters: {
    status: '',
  },
  mapItem: (item) => ({
    ...item,
    id: item.recruitment_position_id,
  }),
  initialForm: () => ({
    position_code: '',
    position_name: '',
    department_id: '',
    employment_type: 'FULLTIME',
    vacancy_count: 1,
    description: '',
    status: 'OPEN',
    opened_at: '',
    closed_at: '',
  }),
  toCreatePayload: (form) => ({
    position_code: form.position_code || undefined,
    position_name: form.position_name,
    department_id: form.department_id ? Number(form.department_id) : undefined,
    employment_type: form.employment_type || undefined,
    vacancy_count: Number(form.vacancy_count || 1),
    description: form.description || undefined,
    status: form.status || undefined,
    opened_at: form.opened_at || undefined,
    closed_at: form.closed_at || undefined,
  }),
  toUpdatePayload: (form) => ({
    position_name: form.position_name || undefined,
    department_id: form.department_id ? Number(form.department_id) : undefined,
    employment_type: form.employment_type || undefined,
    vacancy_count: form.vacancy_count !== '' ? Number(form.vacancy_count) : undefined,
    description: form.description || undefined,
    status: form.status || undefined,
    opened_at: form.opened_at || undefined,
    closed_at: form.closed_at || undefined,
  }),
  deletePath: (id) => `/recruitment-positions/${id}`,
  updatePath: (id) => `/recruitment-positions/${id}`,
});

const statusBadge = toBadgeMap([
  ['OPEN', 'success'],
  ['PENDING', 'warning'],
  ['CLOSED', 'danger'],
]);

const columns = [
  { key: 'position_code', label: 'Ma vi tri' },
  { key: 'position_name', label: 'Ten vi tri' },
  { key: 'department_name', label: 'Phong ban' },
  { key: 'vacancy_count', label: 'So luong can' },
  { key: 'opened_at', label: 'Ngay mo', formatter: formatDate },
  { key: 'status', label: 'Trang thai', badge: true, badgeMap: statusBadge },
];

const detailFields = [
  { key: 'position_code', label: 'Ma vi tri' },
  { key: 'position_name', label: 'Ten vi tri' },
  { key: 'department_name', label: 'Phong ban' },
  { key: 'employment_type', label: 'Loai hinh' },
  { key: 'vacancy_count', label: 'So luong can' },
  { key: 'description', label: 'Mo ta' },
  { key: 'opened_at', label: 'Ngay mo', formatter: formatDate },
  { key: 'closed_at', label: 'Ngay dong', formatter: formatDate },
  { key: 'status', label: 'Trang thai' },
];

const filterFields = [
  {
    key: 'status',
    type: 'select',
    placeholder: 'Trang thai',
    options: [
      { value: 'OPEN', label: 'Open' },
      { value: 'PENDING', label: 'Pending' },
      { value: 'CLOSED', label: 'Closed' },
    ],
  },
];

const formFields = [
  { key: 'position_code', label: 'Ma vi tri', placeholder: 'Bo trong de tu sinh' },
  { key: 'position_name', label: 'Ten vi tri', required: true },
  { key: 'department_id', label: 'Phong ban', type: 'select', options: () => lookup.departments.value },
  {
    key: 'employment_type',
    label: 'Loai hinh',
    type: 'select',
    options: [
      { value: 'FULLTIME', label: 'Full-time' },
      { value: 'PARTTIME', label: 'Part-time' },
      { value: 'INTERN', label: 'Intern' },
      { value: 'CONTRACT', label: 'Contract' },
    ],
  },
  { key: 'vacancy_count', label: 'So luong can', type: 'number' },
  { key: 'opened_at', label: 'Ngay mo', type: 'date' },
  { key: 'closed_at', label: 'Ngay dong', type: 'date' },
  {
    key: 'status',
    label: 'Trang thai',
    type: 'select',
    options: [
      { value: 'OPEN', label: 'Open' },
      { value: 'PENDING', label: 'Pending' },
      { value: 'CLOSED', label: 'Closed' },
    ],
  },
  { key: 'description', label: 'Mo ta', type: 'textarea', full: true },
];

onMounted(async () => {
  await lookup.bootstrap(['departments']);
  await crud.fetchList();
});
</script>
