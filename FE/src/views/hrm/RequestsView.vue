<template>
  <ApiCrudModule
    title="HRM - Don tu / Yeu cau"
    subtitle="Quan ly don tu nghi phep, tang ca, cong tac, v.v."
    entity-name="don tu"
    id-key="request_id"
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
import { formatDate, formatDateTime, toBadgeMap } from '@/views/api/formatters.js';

const lookup = useLookupOptions();

const crud = useCrudModule({
  listPath: '/requests',
  detailPath: (id) => `/requests/${id}`,
  createPath: '/requests',
  updatePath: (id) => `/requests/${id}`,
  deletePath: (id) => `/requests/${id}`,
  getItemId: (item) => item?.request_id,
  defaultFilters: { status: '', request_type_id: '' },
  mapItem: (item) => ({
    ...item,
    id: item.request_id,
    requester_name: item.full_name || item.requester_name || `#${item.requester_id}`,
  }),
  initialForm: () => ({
    request_type_id: '',
    requester_id: '',
    request_date: new Date().toISOString().slice(0, 10),
    from_date: '',
    to_date: '',
    duration: '',
    reason: '',
    is_urgent: false,
    notes: '',
  }),
  toCreatePayload: (form) => ({
    request_type_id: Number(form.request_type_id),
    requester_id: form.requester_id ? Number(form.requester_id) : undefined,
    request_date: form.request_date,
    from_date: form.from_date || undefined,
    to_date: form.to_date || undefined,
    duration: form.duration !== '' ? Number(form.duration) : undefined,
    reason: form.reason || undefined,
    is_urgent: form.is_urgent || false,
    notes: form.notes || undefined,
  }),
  toUpdatePayload: (form) => ({
    from_date: form.from_date || undefined,
    to_date: form.to_date || undefined,
    duration: form.duration !== '' ? Number(form.duration) : undefined,
    reason: form.reason || undefined,
    status: form.status || undefined,
    is_urgent: form.is_urgent,
    notes: form.notes || undefined,
  }),
});

const statusBadge = toBadgeMap([
  ['CHỜ_DUYỆT', 'warning'],
  ['PENDING', 'warning'],
  ['NHÁP', 'info'],
  ['ĐANG_XỬ_LÝ', 'info'],
  ['ĐÃ_DUYỆT', 'success'],
  ['APPROVED', 'success'],
  ['HOÀN_THÀNH', 'success'],
  ['TỪ_CHỐI', 'danger'],
  ['REJECTED', 'danger'],
  ['ĐÃ_HỦY', 'danger'],
]);

const columns = [
  { key: 'request_code', label: 'Ma don' },
  { key: 'requester_name', label: 'Nguoi gui' },
  { key: 'request_type_name', label: 'Loai don' },
  { key: 'request_date', label: 'Ngay gui', formatter: formatDate },
  { key: 'status', label: 'Trang thai', badge: true, badgeMap: statusBadge },
];

const detailFields = [
  { key: 'request_code', label: 'Ma don' },
  { key: 'requester_name', label: 'Nguoi gui' },
  { key: 'request_type_name', label: 'Loai don' },
  { key: 'request_date', label: 'Ngay gui', formatter: formatDate },
  { key: 'from_date', label: 'Tu ngay', formatter: formatDateTime },
  { key: 'to_date', label: 'Den ngay', formatter: formatDateTime },
  { key: 'duration', label: 'Thoi luong' },
  { key: 'reason', label: 'Ly do' },
  { key: 'status', label: 'Trang thai' },
  { key: 'is_urgent', label: 'Khan cap', formatter: (v) => v ? 'Co' : 'Khong' },
  { key: 'notes', label: 'Ghi chu' },
  { key: 'created_at', label: 'Ngay tao', formatter: formatDateTime },
];

const filterFields = [
  { key: 'request_type_id', type: 'select', placeholder: 'Loai don', options: () => lookup.leaveTypes.value },
  {
    key: 'status',
    type: 'select',
    placeholder: 'Trang thai',
    options: [
      { value: 'PENDING', label: 'Cho duyet' },
      { value: 'APPROVED', label: 'Da duyet' },
      { value: 'REJECTED', label: 'Tu choi' },
      { value: 'IN_PROGRESS', label: 'Dang xu ly' },
      { value: 'COMPLETED', label: 'Hoan thanh' },
    ],
  },
];

const formFields = [
  { key: 'request_type_id', label: 'Loai don', type: 'select', required: true, options: () => lookup.leaveTypes.value },
  { key: 'requester_id', label: 'Nguoi gui', type: 'select', options: () => lookup.employees.value },
  { key: 'request_date', label: 'Ngay gui', type: 'date', required: true },
  { key: 'from_date', label: 'Tu ngay', type: 'datetime-local' },
  { key: 'to_date', label: 'Den ngay', type: 'datetime-local' },
  { key: 'duration', label: 'Thoi luong (ngay)', type: 'number' },
  { key: 'reason', label: 'Ly do', type: 'textarea', full: true },
  { key: 'notes', label: 'Ghi chu', type: 'textarea', full: true },
];

onMounted(async () => {
  await lookup.bootstrap(['employees', 'leaveTypes']);
  await crud.fetchList();
});
</script>
