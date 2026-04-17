<template>
  <ApiCrudModule
    title="Attendance - Don tang ca"
    subtitle="Quan ly don dang ky lam them gio"
    entity-name="don tang ca"
    id-key="overtime_id"
    :crud="crud"
    :columns="columns"
    :form-fields="formFields"
    :detail-fields="detailFields"
    :filter-fields="filterFields"
    :allow-delete="true"
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
  listPath: '/overtime-requests',
  detailPath: (id) => `/overtime-requests/${id}`,
  createPath: '/overtime-requests',
  updatePath: (id) => `/overtime-requests/${id}`,
  deletePath: (id) => `/overtime-requests/${id}`,
  getItemId: (item) => item?.overtime_id,
  defaultFilters: { status: '' },
  mapItem: (item) => ({
    ...item,
    id: item.overtime_id,
    employee_name: item.full_name || item.employee_name || `#${item.employee_id}`,
  }),
  initialForm: () => ({
    employee_id: '',
    overtime_date: new Date().toISOString().slice(0, 10),
    start_time: '17:30',
    end_time: '20:00',
    break_time: 0,
    reason: '',
  }),
  toCreatePayload: (form) => ({
    employee_id: form.employee_id ? Number(form.employee_id) : undefined,
    overtime_date: form.overtime_date,
    start_time: form.start_time,
    end_time: form.end_time,
    break_time: Number(form.break_time || 0),
    reason: form.reason || undefined,
  }),
  toUpdatePayload: (form) => ({
    overtime_date: form.overtime_date || undefined,
    start_time: form.start_time || undefined,
    end_time: form.end_time || undefined,
    break_time: form.break_time !== '' ? Number(form.break_time) : undefined,
    reason: form.reason || undefined,
    status: form.status || undefined,
  }),
});

const statusBadge = toBadgeMap([
  ['CHỜ_DUYỆT', 'warning'],
  ['PENDING', 'warning'],
  ['ĐÃ_DUYỆT', 'success'],
  ['APPROVED', 'success'],
  ['TỪ_CHỐI', 'danger'],
  ['REJECTED', 'danger'],
]);

const columns = [
  { key: 'employee_name', label: 'Nhan vien' },
  { key: 'overtime_date', label: 'Ngay tang ca', formatter: formatDate },
  { key: 'start_time', label: 'Bat dau' },
  { key: 'end_time', label: 'Ket thuc' },
  { key: 'break_time', label: 'Nghi (ph)', formatter: (v) => `${v || 0}` },
  { key: 'status', label: 'Trang thai', badge: true, badgeMap: statusBadge },
];

const detailFields = [
  { key: 'employee_name', label: 'Nhan vien' },
  { key: 'overtime_date', label: 'Ngay tang ca', formatter: formatDate },
  { key: 'start_time', label: 'Gio bat dau' },
  { key: 'end_time', label: 'Gio ket thuc' },
  { key: 'break_time', label: 'Thoi gian nghi (phut)' },
  { key: 'reason', label: 'Ly do' },
  { key: 'status', label: 'Trang thai' },
  { key: 'approved_by', label: 'Nguoi duyet' },
  { key: 'approved_date', label: 'Ngay duyet', formatter: formatDate },
];

const filterFields = [
  {
    key: 'status',
    type: 'select',
    placeholder: 'Trang thai',
    options: [
      { value: 'CHỜ_DUYỆT', label: 'Cho duyet' },
      { value: 'ĐÃ_DUYỆT', label: 'Da duyet' },
      { value: 'TỪ_CHỐI', label: 'Tu choi' },
    ],
  },
  { key: 'date_from', type: 'date', placeholder: 'Tu ngay' },
  { key: 'date_to', type: 'date', placeholder: 'Den ngay' },
];

const formFields = [
  { key: 'employee_id', label: 'Nhan vien', type: 'select', options: () => lookup.employees.value },
  { key: 'overtime_date', label: 'Ngay tang ca', type: 'date', required: true },
  { key: 'start_time', label: 'Gio bat dau', required: true },
  { key: 'end_time', label: 'Gio ket thuc', required: true },
  { key: 'break_time', label: 'Nghi (phut)', type: 'number' },
  { key: 'reason', label: 'Ly do', type: 'textarea', full: true },
];

onMounted(async () => {
  await lookup.bootstrap(['employees']);
  await crud.fetchList();
});
</script>
