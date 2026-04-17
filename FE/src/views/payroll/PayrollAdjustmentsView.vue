<template>
  <ApiCrudModule
    title="Payroll - Dieu chinh luong"
    subtitle="Theo doi cac khoan cong/tru phat sinh theo thang"
    entity-name="dieu chinh luong"
    :crud="crud"
    :columns="columns"
    :form-fields="formFields"
    :filter-fields="filterFields"
    :detail-fields="detailFields"
    :allow-delete="false"
  />
</template>

<script setup>
import { onMounted } from 'vue';
import ApiCrudModule from '@/components/api/ApiCrudModule.vue';
import { useCrudModule } from '@/composables/useCrudModule.js';
import { useLookupOptions } from '@/composables/useLookupOptions.js';
import { formatCurrency, toBadgeMap } from '@/views/api/formatters.js';

const lookup = useLookupOptions();

const crud = useCrudModule({
  listPath: '/payroll-adjustments',
  defaultFilters: {
    employee_id: '',
    apply_month: '',
    status: '',
  },
  mapItem: (item) => ({
    ...item,
    id: item.adjustment_id,
    employee_name: item.full_name || `#${item.employee_id}`,
    status_text: Number(item.status) === 1 ? 'DA CHI TRA' : 'CHUA CHI TRA',
  }),
  initialForm: () => ({
    employee_id: '',
    amount: 0,
    description: '',
    apply_month: '',
  }),
  toCreatePayload: (form) => ({
    employee_id: Number(form.employee_id),
    amount: Number(form.amount || 0),
    description: form.description,
    apply_month: form.apply_month,
  }),
  toUpdatePayload: (form) => ({
    amount: form.amount !== '' ? Number(form.amount) : undefined,
    description: form.description || undefined,
    apply_month: form.apply_month || undefined,
  }),
});

const statusBadge = toBadgeMap([
  ['CHUA CHI TRA', 'warning'],
  ['DA CHI TRA', 'success'],
]);

const columns = [
  { key: 'employee_name', label: 'Nhan vien' },
  { key: 'apply_month', label: 'Ap dung thang' },
  { key: 'amount', label: 'So tien', formatter: formatCurrency },
  { key: 'description', label: 'Noi dung' },
  { key: 'status_text', label: 'Trang thai', badge: true, badgeMap: statusBadge },
];

const detailFields = [
  { key: 'employee_name', label: 'Nhan vien' },
  { key: 'apply_month', label: 'Thang ap dung' },
  { key: 'amount', label: 'So tien', formatter: formatCurrency },
  { key: 'description', label: 'Noi dung dieu chinh' },
  { key: 'status_text', label: 'Trang thai' },
  { key: 'paid_period_code', label: 'Ky da thanh toan' },
  { key: 'paid_at', label: 'Thoi diem thanh toan' },
];

const filterFields = [
  { key: 'employee_id', type: 'select', placeholder: 'Nhan vien', options: () => lookup.employees.value },
  { key: 'apply_month', type: 'month', placeholder: 'Thang ap dung' },
  {
    key: 'status',
    type: 'select',
    placeholder: 'Trang thai',
    options: [
      { value: 0, label: 'Chua chi tra' },
      { value: 1, label: 'Da chi tra' },
    ],
  },
];

const formFields = [
  { key: 'employee_id', label: 'Nhan vien', type: 'select', required: true, options: () => lookup.employees.value },
  { key: 'apply_month', label: 'Ap dung thang', type: 'month', required: true },
  { key: 'amount', label: 'So tien (+/-)', type: 'number', required: true },
  { key: 'description', label: 'Noi dung', type: 'textarea', required: true, full: true },
];

onMounted(async () => {
  await lookup.bootstrap(['employees']);
  await crud.fetchList();
});
</script>
