<template>
  <ApiCrudModule
    title="Time & Attendance - Don nghi"
    subtitle="Quan ly don nghi phep va luong phe duyet"
    entity-name="don nghi"
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
  listPath: '/leave-requests',
  defaultFilters: {
    employee_id: '',
    status: '',
  },
  mapItem: (item) => ({
    ...item,
    id: item.leave_request_id,
    employee_name: item.employee_name || `#${item.employee_id}`,
    status: item.request_status || item.status,
  }),
  initialForm: () => ({
    requester_id: '',
    request_date: '',
    leave_type_id: '',
    employee_id: '',
    from_date: '',
    to_date: '',
    number_of_days: 1,
    reason: '',
    is_urgent: 0,
    status: 'CHỜ_DUYỆT',
  }),
  toCreatePayload: (form) => ({
    requester_id: Number(form.requester_id || form.employee_id),
    request_date: form.request_date,
    leave_type_id: Number(form.leave_type_id),
    employee_id: Number(form.employee_id),
    from_date: form.from_date,
    to_date: form.to_date,
    number_of_days: Number(form.number_of_days || 0),
    reason: form.reason || undefined,
    is_urgent: Number(form.is_urgent) === 1 ? 1 : 0,
    status: form.status || undefined,
  }),
  toUpdatePayload: (form) => ({
    from_date: form.from_date || undefined,
    to_date: form.to_date || undefined,
    number_of_days: form.number_of_days !== '' ? Number(form.number_of_days) : undefined,
    reason: form.reason || undefined,
    status: form.status || undefined,
  }),
});

const statusBadge = toBadgeMap([
  ['CHỜ_DUYỆT', 'warning'],
  ['CHỜ_GIÁM_ĐỐC_DUYỆT', 'info'],
  ['CHỜ_XÁC_NHẬN_HR', 'info'],
  ['ĐÃ_DUYỆT', 'success'],
  ['TỪ_CHỐI', 'danger'],
]);

const columns = [
  { key: 'employee_name', label: 'Nhan vien' },
  { key: 'from_date', label: 'Tu ngay', formatter: formatDate },
  { key: 'to_date', label: 'Den ngay', formatter: formatDate },
  { key: 'number_of_days', label: 'So ngay' },
  { key: 'status', label: 'Trang thai', badge: true, badgeMap: statusBadge },
];

const detailFields = [
  { key: 'employee_name', label: 'Nhan vien' },
  { key: 'from_date', label: 'Tu ngay', formatter: formatDate },
  { key: 'to_date', label: 'Den ngay', formatter: formatDate },
  { key: 'number_of_days', label: 'So ngay nghi' },
  { key: 'leave_type_name', label: 'Loai nghi' },
  { key: 'reason', label: 'Ly do' },
  { key: 'status', label: 'Trang thai' },
];

const filterFields = [
  { key: 'employee_id', type: 'select', placeholder: 'Nhan vien', options: () => lookup.employees.value },
  {
    key: 'status',
    type: 'select',
    placeholder: 'Trang thai',
    options: [
      { value: 'CHỜ_DUYỆT', label: 'Cho duyet' },
      { value: 'CHỜ_GIÁM_ĐỐC_DUYỆT', label: 'Cho giam doc duyet' },
      { value: 'CHỜ_XÁC_NHẬN_HR', label: 'Cho HR xac nhan' },
      { value: 'ĐÃ_DUYỆT', label: 'Da duyet' },
      { value: 'TỪ_CHỐI', label: 'Tu choi' },
    ],
  },
];

const formFields = [
  { key: 'employee_id', label: 'Nhan vien', type: 'select', required: true, options: () => lookup.employees.value },
  { key: 'requester_id', label: 'Nguoi tao don', type: 'select', required: true, options: () => lookup.employees.value },
  { key: 'request_date', label: 'Ngay tao don', type: 'date', required: true },
  { key: 'leave_type_id', label: 'Loai nghi', type: 'select', required: true, options: () => lookup.leaveTypes.value },
  { key: 'from_date', label: 'Tu ngay', type: 'date', required: true },
  { key: 'to_date', label: 'Den ngay', type: 'date', required: true },
  { key: 'number_of_days', label: 'So ngay nghi', type: 'number', required: true },
  { key: 'is_urgent', label: 'Khan cap', type: 'select', options: [{ value: 0, label: 'Khong' }, { value: 1, label: 'Co' }] },
  {
    key: 'status',
    label: 'Trang thai',
    type: 'select',
    options: [
      { value: 'CHỜ_DUYỆT', label: 'Cho duyet' },
      { value: 'CHỜ_GIÁM_ĐỐC_DUYỆT', label: 'Cho giam doc duyet' },
      { value: 'CHỜ_XÁC_NHẬN_HR', label: 'Cho HR xac nhan' },
      { value: 'ĐÃ_DUYỆT', label: 'Da duyet' },
      { value: 'TỪ_CHỐI', label: 'Tu choi' },
    ],
  },
  { key: 'reason', label: 'Ly do', type: 'textarea', full: true },
];

onMounted(async () => {
  await lookup.bootstrap(['employees', 'leaveTypes']);
  await crud.fetchList();
});
</script>
