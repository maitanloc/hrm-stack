<template>
  <ApiCrudModule
    title="Asset - Tai san"
    subtitle="Quan ly danh muc tai san cong ty"
    entity-name="tai san"
    id-key="asset_id"
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
import { formatCurrency, formatDate, toBadgeMap } from '@/views/api/formatters.js';

const crud = useCrudModule({
  listPath: '/assets',
  detailPath: (id) => `/assets/${id}`,
  createPath: '/assets',
  updatePath: (id) => `/assets/${id}`,
  getItemId: (item) => item?.asset_id,
  defaultFilters: { status: '' },
  mapItem: (item) => ({ ...item, id: item.asset_id }),
  initialForm: () => ({
    asset_code: '',
    asset_name: '',
    serial_number: '',
    inventory_number: '',
    purchase_date: '',
    purchase_price: '',
    status: 'AVAILABLE',
    condition_note: '',
    notes: '',
  }),
  toCreatePayload: (form) => ({
    asset_code: form.asset_code,
    asset_name: form.asset_name,
    serial_number: form.serial_number || undefined,
    inventory_number: form.inventory_number || undefined,
    purchase_date: form.purchase_date || undefined,
    purchase_price: form.purchase_price !== '' ? Number(form.purchase_price) : undefined,
    status: form.status || undefined,
  }),
  toUpdatePayload: (form) => ({
    asset_name: form.asset_name || undefined,
    serial_number: form.serial_number || undefined,
    inventory_number: form.inventory_number || undefined,
    purchase_date: form.purchase_date || undefined,
    purchase_price: form.purchase_price !== '' ? Number(form.purchase_price) : undefined,
    status: form.status || undefined,
    condition_note: form.condition_note || undefined,
    notes: form.notes || undefined,
  }),
});

const statusBadge = toBadgeMap([
  ['AVAILABLE', 'success'],
  ['IN_USE', 'info'],
  ['MAINTENANCE', 'warning'],
  ['DISPOSED', 'danger'],
  ['LOST', 'danger'],
]);

const columns = [
  { key: 'asset_code', label: 'Ma tai san' },
  { key: 'asset_name', label: 'Ten' },
  { key: 'serial_number', label: 'Serial' },
  { key: 'purchase_date', label: 'Ngay mua', formatter: formatDate },
  { key: 'purchase_price', label: 'Gia mua', formatter: formatCurrency },
  { key: 'status', label: 'Trang thai', badge: true, badgeMap: statusBadge },
];

const detailFields = [
  { key: 'asset_code', label: 'Ma tai san' },
  { key: 'asset_name', label: 'Ten tai san' },
  { key: 'serial_number', label: 'So serial' },
  { key: 'inventory_number', label: 'So kiem ke' },
  { key: 'purchase_date', label: 'Ngay mua', formatter: formatDate },
  { key: 'purchase_price', label: 'Gia mua', formatter: formatCurrency },
  { key: 'status', label: 'Trang thai' },
  { key: 'condition_note', label: 'Tinh trang' },
  { key: 'notes', label: 'Ghi chu' },
];

const filterFields = [
  {
    key: 'status',
    type: 'select',
    placeholder: 'Trang thai',
    options: [
      { value: 'AVAILABLE', label: 'San sang' },
      { value: 'IN_USE', label: 'Dang dung' },
      { value: 'MAINTENANCE', label: 'Bao tri' },
      { value: 'DISPOSED', label: 'Da thanh ly' },
    ],
  },
];

const formFields = [
  { key: 'asset_code', label: 'Ma tai san', required: true },
  { key: 'asset_name', label: 'Ten tai san', required: true },
  { key: 'serial_number', label: 'So serial' },
  { key: 'inventory_number', label: 'So kiem ke' },
  { key: 'purchase_date', label: 'Ngay mua', type: 'date' },
  { key: 'purchase_price', label: 'Gia mua', type: 'number' },
  {
    key: 'status',
    label: 'Trang thai',
    type: 'select',
    options: [
      { value: 'AVAILABLE', label: 'San sang' },
      { value: 'IN_USE', label: 'Dang dung' },
      { value: 'MAINTENANCE', label: 'Bao tri' },
      { value: 'DISPOSED', label: 'Da thanh ly' },
    ],
  },
  { key: 'condition_note', label: 'Tinh trang', type: 'textarea', full: true },
  { key: 'notes', label: 'Ghi chu', type: 'textarea', full: true },
];

onMounted(() => crud.fetchList());
</script>
