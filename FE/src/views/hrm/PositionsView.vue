<template>
  <ApiCrudModule
    title="HRM - Chuc danh"
    subtitle="Quan ly danh muc chuc danh / vi tri cong viec"
    entity-name="chuc danh"
    id-key="position_id"
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
import { formatCurrency } from '@/views/api/formatters.js';

const crud = useCrudModule({
  listPath: '/positions',
  detailPath: (id) => `/positions/${id}`,
  createPath: '/positions',
  updatePath: (id) => `/positions/${id}`,
  deletePath: (id) => `/positions/${id}`,
  getItemId: (item) => item?.position_id,
  mapItem: (item) => ({ ...item, id: item.position_id }),
  initialForm: () => ({
    position_code: '',
    position_name: '',
    position_group: '',
    position_level: '',
    job_description: '',
    requirements: '',
    salary_range_min: '',
    salary_range_max: '',
    status: true,
  }),
  toCreatePayload: (form) => ({
    position_code: form.position_code,
    position_name: form.position_name,
    position_group: form.position_group || undefined,
    position_level: form.position_level || undefined,
    job_description: form.job_description || undefined,
    requirements: form.requirements || undefined,
    salary_range_min: form.salary_range_min !== '' ? Number(form.salary_range_min) : undefined,
    salary_range_max: form.salary_range_max !== '' ? Number(form.salary_range_max) : undefined,
    status: form.status,
  }),
  toUpdatePayload: (form) => ({
    position_name: form.position_name || undefined,
    position_group: form.position_group || undefined,
    position_level: form.position_level || undefined,
    job_description: form.job_description || undefined,
    requirements: form.requirements || undefined,
    salary_range_min: form.salary_range_min !== '' ? Number(form.salary_range_min) : undefined,
    salary_range_max: form.salary_range_max !== '' ? Number(form.salary_range_max) : undefined,
    status: form.status,
  }),
});

const columns = [
  { key: 'position_code', label: 'Ma chuc danh' },
  { key: 'position_name', label: 'Ten chuc danh' },
  { key: 'position_group', label: 'Nhom' },
  { key: 'position_level', label: 'Cap bac' },
  { key: 'salary_range_min', label: 'Luong min', formatter: formatCurrency },
  { key: 'salary_range_max', label: 'Luong max', formatter: formatCurrency },
];

const detailFields = [
  { key: 'position_code', label: 'Ma chuc danh' },
  { key: 'position_name', label: 'Ten chuc danh' },
  { key: 'position_group', label: 'Nhom' },
  { key: 'position_level', label: 'Cap bac' },
  { key: 'job_description', label: 'Mo ta cong viec' },
  { key: 'requirements', label: 'Yeu cau' },
  { key: 'salary_range_min', label: 'Luong toi thieu', formatter: formatCurrency },
  { key: 'salary_range_max', label: 'Luong toi da', formatter: formatCurrency },
  { key: 'status', label: 'Trang thai', formatter: (v) => v ? 'Hoat dong' : 'Ngung' },
];

const formFields = [
  { key: 'position_code', label: 'Ma chuc danh', required: true },
  { key: 'position_name', label: 'Ten chuc danh', required: true },
  { key: 'position_group', label: 'Nhom' },
  { key: 'position_level', label: 'Cap bac' },
  { key: 'salary_range_min', label: 'Luong toi thieu', type: 'number' },
  { key: 'salary_range_max', label: 'Luong toi da', type: 'number' },
  { key: 'job_description', label: 'Mo ta cong viec', type: 'textarea', full: true },
  { key: 'requirements', label: 'Yeu cau', type: 'textarea', full: true },
];

onMounted(() => crud.fetchList());
</script>
