<template>
  <ApiCrudModule
    title="Payroll - Chi tiet khoan luong"
    subtitle="Cac khoan phu cap / khau tru chi tiet theo phieu luong"
    entity-name="khoan luong"
    id-key="breakdown_id"
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
import { formatCurrency, toBadgeMap } from '@/views/api/formatters.js';

const crud = useCrudModule({
  listPath: '/salary-breakdowns',
  detailPath: (id) => `/salary-breakdowns/${id}`,
  createPath: '/salary-breakdowns',
  updatePath: (id) => `/salary-breakdowns/${id}`,
  deletePath: (id) => `/salary-breakdowns/${id}`,
  getItemId: (item) => item?.breakdown_id,
  defaultFilters: { salary_detail_id: '', item_type: '' },
  mapItem: (item) => ({ ...item, id: item.breakdown_id }),
  initialForm: () => ({
    salary_detail_id: '',
    item_type: 'ALLOWANCE',
    item_name: '',
    amount: 0,
    is_taxable: false,
    is_insurable: false,
    description: '',
  }),
  toCreatePayload: (form) => ({
    salary_detail_id: Number(form.salary_detail_id),
    item_type: form.item_type,
    item_name: form.item_name,
    amount: Number(form.amount || 0),
    is_taxable: form.is_taxable || false,
    is_insurable: form.is_insurable || false,
    description: form.description || undefined,
  }),
  toUpdatePayload: (form) => ({
    item_type: form.item_type || undefined,
    item_name: form.item_name || undefined,
    amount: form.amount !== '' ? Number(form.amount) : undefined,
    is_taxable: form.is_taxable,
    is_insurable: form.is_insurable,
    description: form.description || undefined,
  }),
});

const typeBadge = toBadgeMap([
  ['ALLOWANCE', 'success'],
  ['DEDUCTION', 'danger'],
  ['BONUS', 'info'],
  ['TAX', 'warning'],
  ['INSURANCE', 'warning'],
]);

const columns = [
  { key: 'salary_detail_id', label: 'Phieu luong #' },
  { key: 'item_type', label: 'Loai', badge: true, badgeMap: typeBadge },
  { key: 'item_name', label: 'Ten khoan' },
  { key: 'amount', label: 'So tien', formatter: formatCurrency },
  { key: 'is_taxable', label: 'Tinh thue', formatter: (v) => v ? 'Co' : 'Khong' },
];

const detailFields = [
  { key: 'salary_detail_id', label: 'Phieu luong #' },
  { key: 'item_type', label: 'Loai' },
  { key: 'item_name', label: 'Ten khoan' },
  { key: 'amount', label: 'So tien', formatter: formatCurrency },
  { key: 'is_taxable', label: 'Tinh thue', formatter: (v) => v ? 'Co' : 'Khong' },
  { key: 'is_insurable', label: 'Tinh bao hiem', formatter: (v) => v ? 'Co' : 'Khong' },
  { key: 'description', label: 'Mo ta' },
];

const filterFields = [
  { key: 'salary_detail_id', type: 'number', placeholder: 'Phieu luong ID' },
  {
    key: 'item_type',
    type: 'select',
    placeholder: 'Loai khoan',
    options: [
      { value: 'ALLOWANCE', label: 'Phu cap' },
      { value: 'DEDUCTION', label: 'Khau tru' },
      { value: 'BONUS', label: 'Thuong' },
      { value: 'TAX', label: 'Thue' },
      { value: 'INSURANCE', label: 'Bao hiem' },
    ],
  },
];

const formFields = [
  { key: 'salary_detail_id', label: 'Phieu luong ID', type: 'number', required: true },
  {
    key: 'item_type',
    label: 'Loai',
    type: 'select',
    required: true,
    options: [
      { value: 'ALLOWANCE', label: 'Phu cap' },
      { value: 'DEDUCTION', label: 'Khau tru' },
      { value: 'BONUS', label: 'Thuong' },
      { value: 'TAX', label: 'Thue' },
      { value: 'INSURANCE', label: 'Bao hiem' },
    ],
  },
  { key: 'item_name', label: 'Ten khoan', required: true },
  { key: 'amount', label: 'So tien', type: 'number', required: true },
  { key: 'description', label: 'Mo ta', type: 'textarea', full: true },
];

onMounted(() => crud.fetchList());
</script>
