<template>
  <ApiCrudModule
    title="Asset - Cap phat tai san"
    subtitle="Quan ly viec cap phat / thu hoi tai san cho nhan vien"
    entity-name="cap phat"
    id-key="assignment_id"
    :crud="crud"
    :columns="columns"
    :form-fields="formFields"
    :detail-fields="detailFields"
    :filter-fields="filterFields"
    :allow-delete="false"
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
  listPath: '/asset-assignments',
  createPath: '/asset-assignments',
  getItemId: (item) => item?.assignment_id,
  defaultFilters: { status: '' },
  mapItem: (item) => ({
    ...item,
    id: item.assignment_id,
    employee_name: item.full_name || item.employee_name || `#${item.employee_id}`,
  }),
  initialForm: () => ({
    asset_id: '',
    employee_id: '',
    assigned_date: new Date().toISOString().slice(0, 10),
    expected_return_date: '',
    condition_before: '',
    assignment_notes: '',
  }),
  toCreatePayload: (form) => ({
    asset_id: Number(form.asset_id),
    employee_id: Number(form.employee_id),
    assigned_date: form.assigned_date,
    expected_return_date: form.expected_return_date || undefined,
    condition_before: form.condition_before || undefined,
    assignment_notes: form.assignment_notes || undefined,
  }),
});

const statusBadge = toBadgeMap([
  ['ASSIGNED', 'info'],
  ['RETURNED', 'success'],
  ['LOST', 'danger'],
  ['DAMAGED', 'warning'],
]);

const columns = [
  { key: 'asset_id', label: 'Tai san #' },
  { key: 'employee_name', label: 'Nhan vien' },
  { key: 'assigned_date', label: 'Ngay cap', formatter: formatDate },
  { key: 'expected_return_date', label: 'Ngay tra du kien', formatter: formatDate },
  { key: 'status', label: 'Trang thai', badge: true, badgeMap: statusBadge },
];

const detailFields = [
  { key: 'asset_id', label: 'Tai san ID' },
  { key: 'employee_name', label: 'Nhan vien' },
  { key: 'assigned_date', label: 'Ngay cap', formatter: formatDate },
  { key: 'expected_return_date', label: 'Ngay tra du kien', formatter: formatDate },
  { key: 'actual_return_date', label: 'Ngay tra thuc te', formatter: formatDate },
  { key: 'condition_before', label: 'Tinh trang khi cap' },
  { key: 'condition_after', label: 'Tinh trang khi tra' },
  { key: 'assignment_notes', label: 'Ghi chu' },
  { key: 'status', label: 'Trang thai' },
];

const filterFields = [
  {
    key: 'status',
    type: 'select',
    placeholder: 'Trang thai',
    options: [
      { value: 'ASSIGNED', label: 'Dang cap' },
      { value: 'RETURNED', label: 'Da tra' },
      { value: 'LOST', label: 'Mat' },
      { value: 'DAMAGED', label: 'Hong' },
    ],
  },
];

const formFields = [
  { key: 'asset_id', label: 'Tai san ID', type: 'number', required: true },
  { key: 'employee_id', label: 'Nhan vien', type: 'select', required: true, options: () => lookup.employees.value },
  { key: 'assigned_date', label: 'Ngay cap', type: 'date', required: true },
  { key: 'expected_return_date', label: 'Ngay tra du kien', type: 'date' },
  { key: 'condition_before', label: 'Tinh trang khi cap', type: 'textarea', full: true },
  { key: 'assignment_notes', label: 'Ghi chu', type: 'textarea', full: true },
];

onMounted(async () => {
  await lookup.bootstrap(['employees']);
  await crud.fetchList();
});
</script>
