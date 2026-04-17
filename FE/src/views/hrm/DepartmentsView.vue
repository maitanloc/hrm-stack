<template>
  <ApiCrudModule
    title="HRM Core - Phong ban"
    subtitle="Quan ly danh muc phong ban va nguoi quan ly"
    entity-name="phong ban"
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
import { toBadgeMap } from '@/views/api/formatters.js';

const lookup = useLookupOptions();

const crud = useCrudModule({
  listPath: '/departments',
  defaultFilters: {
    manager_id: '',
  },
  mapItem: (item) => ({
    ...item,
    id: item.department_id,
    manager_name: item.manager_name || '-',
    status_text: Number(item.status) === 0 ? 'TAM DUNG' : 'HOAT DONG',
  }),
  initialForm: () => ({
    department_code: '',
    department_name: '',
    parent_department_id: '',
    manager_id: '',
    description: '',
    status: 1,
  }),
  toCreatePayload: (form) => ({
    department_code: form.department_code,
    department_name: form.department_name,
    parent_department_id: form.parent_department_id ? Number(form.parent_department_id) : undefined,
    manager_id: form.manager_id ? Number(form.manager_id) : undefined,
    description: form.description || undefined,
    status: Number(form.status) === 0 ? 0 : 1,
  }),
  toUpdatePayload: (form) => ({
    department_name: form.department_name,
    parent_department_id: form.parent_department_id ? Number(form.parent_department_id) : undefined,
    manager_id: form.manager_id ? Number(form.manager_id) : undefined,
    description: form.description || undefined,
    status: Number(form.status) === 0 ? 0 : 1,
  }),
});

const statusBadge = toBadgeMap([
  ['HOAT DONG', 'success'],
  ['TAM DUNG', 'warning'],
]);

const columns = [
  { key: 'department_code', label: 'Ma PB' },
  { key: 'department_name', label: 'Ten phong ban' },
  { key: 'manager_name', label: 'Quan ly' },
  { key: 'status_text', label: 'Trang thai', badge: true, badgeMap: statusBadge },
];

const detailFields = [
  { key: 'department_code', label: 'Ma phong ban' },
  { key: 'department_name', label: 'Ten phong ban' },
  { key: 'manager_name', label: 'Nguoi quan ly' },
  { key: 'parent_department_id', label: 'Phong ban cha' },
  { key: 'description', label: 'Mo ta' },
  { key: 'status_text', label: 'Trang thai' },
];

const filterFields = [
  {
    key: 'manager_id',
    type: 'select',
    placeholder: 'Loc theo quan ly',
    options: () => lookup.employees.value,
  },
];

const formFields = [
  { key: 'department_code', label: 'Ma phong ban', required: true, placeholder: 'VD: HCNS' },
  { key: 'department_name', label: 'Ten phong ban', required: true, placeholder: 'Hanh chinh Nhan su' },
  {
    key: 'parent_department_id',
    label: 'Phong ban cha',
    type: 'select',
    options: () => lookup.departments.value,
  },
  {
    key: 'manager_id',
    label: 'Nguoi quan ly',
    type: 'select',
    options: () => lookup.employees.value,
  },
  { key: 'description', label: 'Mo ta', type: 'textarea', full: true },
  {
    key: 'status',
    label: 'Trang thai',
    type: 'select',
    options: [
      { value: 1, label: 'Hoat dong' },
      { value: 0, label: 'Tam dung' },
    ],
  },
];

onMounted(async () => {
  await lookup.bootstrap(['employees', 'departments']);
  await crud.fetchList();
});
</script>
