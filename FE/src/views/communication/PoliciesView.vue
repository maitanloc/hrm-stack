<template>
  <ApiCrudModule
    title="Truyen thong - Chinh sach"
    subtitle="Quan ly chinh sach / quy dinh noi bo"
    entity-name="chinh sach"
    id-key="policy_id"
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
  listPath: '/policies',
  detailPath: (id) => `/policies/${id}`,
  createPath: '/policies',
  updatePath: (id) => `/policies/${id}`,
  deletePath: (id) => `/policies/${id}`,
  getItemId: (item) => item?.policy_id,
  defaultFilters: { status: '' },
  mapItem: (item) => ({ ...item, id: item.policy_id }),
  initialForm: () => ({
    policy_name: '',
    policy_type: 'HR',
    content: '',
    version: '1.0',
    effective_date: '',
    expiry_date: '',
    department_id: '',
    is_required_acknowledgment: false,
    status: 'DRAFT',
  }),
  toCreatePayload: (form) => ({
    policy_name: form.policy_name,
    policy_type: form.policy_type,
    content: form.content || undefined,
    version: form.version || undefined,
    effective_date: form.effective_date || undefined,
    expiry_date: form.expiry_date || undefined,
    department_id: form.department_id ? Number(form.department_id) : undefined,
    is_required_acknowledgment: form.is_required_acknowledgment || false,
    status: form.status || 'DRAFT',
  }),
  toUpdatePayload: (form) => ({
    policy_name: form.policy_name || undefined,
    policy_type: form.policy_type || undefined,
    content: form.content || undefined,
    version: form.version || undefined,
    effective_date: form.effective_date || undefined,
    expiry_date: form.expiry_date || undefined,
    department_id: form.department_id ? Number(form.department_id) : undefined,
    is_required_acknowledgment: form.is_required_acknowledgment,
    status: form.status || undefined,
  }),
});

const statusBadge = toBadgeMap([
  ['DRAFT', 'warning'],
  ['ACTIVE', 'success'],
  ['ARCHIVED', 'info'],
  ['EXPIRED', 'danger'],
]);

const columns = [
  { key: 'policy_code', label: 'Ma CS' },
  { key: 'policy_name', label: 'Ten chinh sach' },
  { key: 'policy_type', label: 'Loai' },
  { key: 'version', label: 'Phien ban' },
  { key: 'effective_date', label: 'Hieu luc', formatter: formatDate },
  { key: 'status', label: 'Trang thai', badge: true, badgeMap: statusBadge },
];

const detailFields = [
  { key: 'policy_code', label: 'Ma chinh sach' },
  { key: 'policy_name', label: 'Ten chinh sach' },
  { key: 'policy_type', label: 'Loai' },
  { key: 'version', label: 'Phien ban' },
  { key: 'content', label: 'Noi dung' },
  { key: 'effective_date', label: 'Ngay hieu luc', formatter: formatDate },
  { key: 'expiry_date', label: 'Ngay het han', formatter: formatDate },
  { key: 'is_required_acknowledgment', label: 'Can xac nhan', formatter: (v) => v ? 'Co' : 'Khong' },
  { key: 'status', label: 'Trang thai' },
];

const filterFields = [
  {
    key: 'status',
    type: 'select',
    placeholder: 'Trang thai',
    options: [
      { value: 'DRAFT', label: 'Nhap' },
      { value: 'ACTIVE', label: 'Hieu luc' },
      { value: 'ARCHIVED', label: 'Luu tru' },
    ],
  },
];

const formFields = [
  { key: 'policy_name', label: 'Ten chinh sach', required: true },
  {
    key: 'policy_type',
    label: 'Loai',
    type: 'select',
    required: true,
    options: [
      { value: 'HR', label: 'Nhan su' },
      { value: 'FINANCE', label: 'Tai chinh' },
      { value: 'IT', label: 'CNTT' },
      { value: 'GENERAL', label: 'Chung' },
    ],
  },
  { key: 'version', label: 'Phien ban' },
  {
    key: 'status',
    label: 'Trang thai',
    type: 'select',
    options: [
      { value: 'DRAFT', label: 'Nhap' },
      { value: 'ACTIVE', label: 'Hieu luc' },
      { value: 'ARCHIVED', label: 'Luu tru' },
    ],
  },
  { key: 'effective_date', label: 'Ngay hieu luc', type: 'date' },
  { key: 'expiry_date', label: 'Ngay het han', type: 'date' },
  { key: 'department_id', label: 'Phong ban', type: 'select', options: () => lookup.departments.value },
  { key: 'content', label: 'Noi dung', type: 'textarea', full: true },
];

onMounted(async () => {
  await lookup.bootstrap(['departments']);
  await crud.fetchList();
});
</script>
