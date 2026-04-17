<template>
  <ApiCrudModule
    title="HRM - Loai don tu"
    subtitle="Quan ly danh muc loai don tu / yeu cau"
    entity-name="loai don"
    id-key="request_type_id"
    :crud="crud"
    :columns="columns"
    :form-fields="formFields"
    :detail-fields="detailFields"
    :allow-delete="true"
  />
</template>

<script setup>
import { onMounted } from 'vue';
import ApiCrudModule from '@/components/api/ApiCrudModule.vue';
import { useCrudModule } from '@/composables/useCrudModule.js';
import { toBadgeMap } from '@/views/api/formatters.js';

const crud = useCrudModule({
  listPath: '/request-types',
  detailPath: (id) => `/request-types/${id}`,
  createPath: '/request-types',
  updatePath: (id) => `/request-types/${id}`,
  deletePath: (id) => `/request-types/${id}`,
  getItemId: (item) => item?.request_type_id,
  mapItem: (item) => ({ ...item, id: item.request_type_id }),
  initialForm: () => ({
    request_type_code: '',
    request_type_name: '',
    category: 'LEAVE',
    requires_approval: true,
    description: '',
    is_active: true,
  }),
  toCreatePayload: (form) => ({
    request_type_code: form.request_type_code,
    request_type_name: form.request_type_name,
    category: form.category,
    requires_approval: form.requires_approval,
    description: form.description || undefined,
    is_active: form.is_active,
  }),
  toUpdatePayload: (form) => ({
    request_type_name: form.request_type_name || undefined,
    category: form.category || undefined,
    requires_approval: form.requires_approval,
    description: form.description || undefined,
    is_active: form.is_active,
  }),
});

const activeBadge = toBadgeMap([
  ['1', 'success'],
  ['TRUE', 'success'],
  ['0', 'danger'],
  ['FALSE', 'danger'],
]);

const columns = [
  { key: 'request_type_code', label: 'Ma loai' },
  { key: 'request_type_name', label: 'Ten loai' },
  { key: 'category', label: 'Nhom', badge: true },
  { key: 'requires_approval', label: 'Can phe duyet', formatter: (v) => v ? 'Co' : 'Khong' },
  { key: 'is_active', label: 'Trang thai', badge: true, badgeMap: activeBadge, formatter: (v) => v ? 'Active' : 'Inactive' },
];

const detailFields = [
  { key: 'request_type_code', label: 'Ma loai' },
  { key: 'request_type_name', label: 'Ten loai don' },
  { key: 'category', label: 'Nhom' },
  { key: 'requires_approval', label: 'Can phe duyet', formatter: (v) => v ? 'Co' : 'Khong' },
  { key: 'description', label: 'Mo ta' },
  { key: 'is_active', label: 'Trang thai', formatter: (v) => v ? 'Active' : 'Inactive' },
];

const formFields = [
  { key: 'request_type_code', label: 'Ma loai don', required: true },
  { key: 'request_type_name', label: 'Ten loai don', required: true },
  {
    key: 'category',
    label: 'Nhom',
    type: 'select',
    required: true,
    options: [
      { value: 'LEAVE', label: 'Nghi phep' },
      { value: 'OVERTIME', label: 'Tang ca' },
      { value: 'BUSINESS_TRIP', label: 'Cong tac' },
      { value: 'OTHER', label: 'Khac' },
    ],
  },
  { key: 'description', label: 'Mo ta', type: 'textarea', full: true },
];

onMounted(() => crud.fetchList());
</script>
