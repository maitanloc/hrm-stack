<template>
  <ApiCrudModule
    title="Truyen thong - Danh muc tin tuc"
    subtitle="Quan ly nhom / loai tin tuc noi bo"
    entity-name="danh muc"
    id-key="category_id"
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
  listPath: '/news-categories',
  detailPath: (id) => `/news-categories/${id}`,
  createPath: '/news-categories',
  updatePath: (id) => `/news-categories/${id}`,
  deletePath: (id) => `/news-categories/${id}`,
  getItemId: (item) => item?.category_id,
  mapItem: (item) => ({ ...item, id: item.category_id }),
  initialForm: () => ({
    category_code: '',
    category_name: '',
    description: '',
    status: true,
  }),
  toCreatePayload: (form) => ({
    category_code: form.category_code,
    category_name: form.category_name,
    description: form.description || undefined,
    status: form.status,
  }),
  toUpdatePayload: (form) => ({
    category_name: form.category_name || undefined,
    description: form.description || undefined,
    status: form.status,
  }),
});

const statusBadge = toBadgeMap([
  ['1', 'success'],
  ['TRUE', 'success'],
  ['0', 'danger'],
  ['FALSE', 'danger'],
]);

const columns = [
  { key: 'category_code', label: 'Ma danh muc' },
  { key: 'category_name', label: 'Ten danh muc' },
  { key: 'description', label: 'Mo ta' },
  { key: 'status', label: 'Trang thai', badge: true, badgeMap: statusBadge, formatter: (v) => v ? 'Active' : 'Inactive' },
];

const detailFields = [
  { key: 'category_code', label: 'Ma danh muc' },
  { key: 'category_name', label: 'Ten danh muc' },
  { key: 'description', label: 'Mo ta' },
  { key: 'status', label: 'Trang thai', formatter: (v) => v ? 'Active' : 'Inactive' },
];

const formFields = [
  { key: 'category_code', label: 'Ma danh muc', required: true },
  { key: 'category_name', label: 'Ten danh muc', required: true },
  { key: 'description', label: 'Mo ta', type: 'textarea', full: true },
];

onMounted(() => crud.fetchList());
</script>
