<template>
  <ApiCrudModule
    title="He thong - Thong bao"
    subtitle="Quan ly thong bao gui nhan trong he thong"
    entity-name="thong bao"
    id-key="notification_id"
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
import { formatDateTime, toBadgeMap } from '@/views/api/formatters.js';

const lookup = useLookupOptions();

const crud = useCrudModule({
  listPath: '/notifications',
  createPath: '/notifications',
  updatePath: (id) => `/notifications/${id}`,
  getItemId: (item) => item?.notification_id,
  defaultFilters: { is_read: '' },
  mapItem: (item) => ({
    ...item,
    id: item.notification_id,
    receiver_name: item.receiver_name || `#${item.receiver_id}`,
  }),
  initialForm: () => ({
    title: '',
    content: '',
    receiver_id: '',
    notification_type: 'SYSTEM',
    priority: 'TRUNG_BÌNH',
  }),
  toCreatePayload: (form) => ({
    title: form.title,
    content: form.content || undefined,
    receiver_id: Number(form.receiver_id),
    notification_type: form.notification_type || 'SYSTEM',
    priority: form.priority || 'TRUNG_BÌNH',
  }),
  toUpdatePayload: (form) => ({
    is_read: form.is_read,
    title: form.title || undefined,
    content: form.content || undefined,
    priority: form.priority || undefined,
  }),
});

const readBadge = toBadgeMap([
  ['1', 'success'],
  ['TRUE', 'success'],
  ['0', 'warning'],
  ['FALSE', 'warning'],
]);

const priorityBadge = toBadgeMap([
  ['CAO', 'danger'],
  ['TRUNG_BÌNH', 'info'],
  ['THẤP', 'success'],
]);

const columns = [
  { key: 'title', label: 'Tieu de' },
  { key: 'notification_type', label: 'Loai' },
  { key: 'receiver_name', label: 'Nguoi nhan' },
  { key: 'priority', label: 'Uu tien', badge: true, badgeMap: priorityBadge },
  { key: 'is_read', label: 'Da doc', badge: true, badgeMap: readBadge, formatter: (v) => v ? 'Da doc' : 'Chua doc' },
  { key: 'created_at', label: 'Ngay tao', formatter: formatDateTime },
];

const detailFields = [
  { key: 'title', label: 'Tieu de' },
  { key: 'content', label: 'Noi dung' },
  { key: 'notification_type', label: 'Loai' },
  { key: 'receiver_name', label: 'Nguoi nhan' },
  { key: 'priority', label: 'Muc uu tien' },
  { key: 'is_read', label: 'Trang thai', formatter: (v) => v ? 'Da doc' : 'Chua doc' },
  { key: 'read_date', label: 'Ngay doc', formatter: formatDateTime },
  { key: 'action_url', label: 'Link hanh dong' },
  { key: 'created_at', label: 'Ngay tao', formatter: formatDateTime },
  { key: 'expires_at', label: 'Han', formatter: formatDateTime },
];

const filterFields = [
  {
    key: 'is_read',
    type: 'select',
    placeholder: 'Trang thai doc',
    options: [
      { value: '0', label: 'Chua doc' },
      { value: '1', label: 'Da doc' },
    ],
  },
];

const formFields = [
  { key: 'title', label: 'Tieu de', required: true },
  { key: 'receiver_id', label: 'Nguoi nhan', type: 'select', required: true, options: () => lookup.employees.value },
  {
    key: 'notification_type',
    label: 'Loai thong bao',
    type: 'select',
    options: [
      { value: 'SYSTEM', label: 'He thong' },
      { value: 'LEAVE', label: 'Nghi phep' },
      { value: 'ATTENDANCE', label: 'Cham cong' },
      { value: 'PAYROLL', label: 'Luong' },
      { value: 'CONTRACT', label: 'Hop dong' },
    ],
  },
  {
    key: 'priority',
    label: 'Uu tien',
    type: 'select',
    options: [
      { value: 'THẤP', label: 'Thap' },
      { value: 'TRUNG_BÌNH', label: 'Trung binh' },
      { value: 'CAO', label: 'Cao' },
    ],
  },
  { key: 'content', label: 'Noi dung', type: 'textarea', full: true },
];

onMounted(async () => {
  await lookup.bootstrap(['employees']);
  await crud.fetchList();
});
</script>
