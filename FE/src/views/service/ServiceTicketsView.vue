<template>
  <ApiCrudModule
    title="Dich vu noi bo - Ticket"
    subtitle="Quan ly phieu yeu cau ho tro noi bo"
    entity-name="ticket"
    id-key="ticket_id"
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
  listPath: '/service-tickets',
  createPath: '/service-tickets',
  updatePath: (id) => `/service-tickets/${id}`,
  getItemId: (item) => item?.ticket_id,
  defaultFilters: { status: '' },
  mapItem: (item) => ({
    ...item,
    id: item.ticket_id,
    requester_name: item.requester_name || item.full_name || `#${item.requester_id}`,
  }),
  initialForm: () => ({
    category_id: '',
    title: '',
    description: '',
    priority: 'MEDIUM',
  }),
  toCreatePayload: (form) => ({
    category_id: Number(form.category_id),
    title: form.title,
    description: form.description || undefined,
    priority: form.priority || 'MEDIUM',
  }),
  toUpdatePayload: (form) => ({
    title: form.title || undefined,
    description: form.description || undefined,
    priority: form.priority || undefined,
    status: form.status || undefined,
    assigned_to: form.assigned_to ? Number(form.assigned_to) : undefined,
  }),
});

const statusBadge = toBadgeMap([
  ['OPEN', 'warning'],
  ['IN_PROGRESS', 'info'],
  ['RESOLVED', 'success'],
  ['CLOSED', 'success'],
]);

const priorityBadge = toBadgeMap([
  ['LOW', 'success'],
  ['MEDIUM', 'info'],
  ['HIGH', 'warning'],
  ['URGENT', 'danger'],
]);

const columns = [
  { key: 'ticket_code', label: 'Ma ticket' },
  { key: 'title', label: 'Tieu de' },
  { key: 'requester_name', label: 'Nguoi gui' },
  { key: 'priority', label: 'Uu tien', badge: true, badgeMap: priorityBadge },
  { key: 'status', label: 'Trang thai', badge: true, badgeMap: statusBadge },
  { key: 'created_at', label: 'Ngay tao', formatter: formatDateTime },
];

const detailFields = [
  { key: 'ticket_code', label: 'Ma ticket' },
  { key: 'title', label: 'Tieu de' },
  { key: 'description', label: 'Mo ta' },
  { key: 'requester_name', label: 'Nguoi gui' },
  { key: 'category_name', label: 'Danh muc' },
  { key: 'priority', label: 'Muc uu tien' },
  { key: 'status', label: 'Trang thai' },
  { key: 'assigned_to', label: 'Nguoi xu ly' },
  { key: 'resolved_at', label: 'Ngay xu ly xong', formatter: formatDateTime },
  { key: 'created_at', label: 'Ngay tao', formatter: formatDateTime },
];

const filterFields = [
  {
    key: 'status',
    type: 'select',
    placeholder: 'Trang thai',
    options: [
      { value: 'OPEN', label: 'Mo' },
      { value: 'IN_PROGRESS', label: 'Dang xu ly' },
      { value: 'RESOLVED', label: 'Da xu ly' },
      { value: 'CLOSED', label: 'Dong' },
    ],
  },
];

const formFields = [
  { key: 'category_id', label: 'Danh muc', type: 'select', required: true, options: () => lookup.serviceCategories.value },
  { key: 'title', label: 'Tieu de', required: true },
  {
    key: 'priority',
    label: 'Muc uu tien',
    type: 'select',
    options: [
      { value: 'LOW', label: 'Thap' },
      { value: 'MEDIUM', label: 'Trung binh' },
      { value: 'HIGH', label: 'Cao' },
      { value: 'URGENT', label: 'Khan cap' },
    ],
  },
  {
    key: 'status',
    label: 'Trang thai',
    type: 'select',
    options: [
      { value: 'OPEN', label: 'Mo' },
      { value: 'IN_PROGRESS', label: 'Dang xu ly' },
      { value: 'RESOLVED', label: 'Da xu ly' },
      { value: 'CLOSED', label: 'Dong' },
    ],
  },
  { key: 'description', label: 'Mo ta', type: 'textarea', full: true },
];

onMounted(async () => {
  await lookup.bootstrap(['serviceCategories']);
  await crud.fetchList();
});
</script>
