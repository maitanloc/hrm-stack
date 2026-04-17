<template>
  <ApiCrudModule
    title="Payroll - Chi tiet luong"
    subtitle="Danh sach phieu luong cua nhan vien theo ky"
    entity-name="chi tiet luong"
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
  listPath: '/salary-details',
  defaultFilters: {
    period_id: '',
    transfer_status: '',
  },
  mapItem: (item) => ({
    ...item,
    id: item.salary_detail_id,
    employee_name: item.full_name || item.employee_name || `#${item.employee_id}`,
  }),
  initialForm: () => ({
    period_id: '',
    employee_id: '',
    contract_id: '',
    basic_salary: 0,
    gross_salary: 0,
    net_salary: 0,
    total_allowances: 0,
    total_deductions: 0,
    overtime_pay: 0,
    leave_pay: 0,
    bonus: 0,
    penalty: 0,
    personal_income_tax: 0,
    advance_payment: 0,
    bank_account: '',
    bank_name: '',
    transfer_status: 'PENDING',
    notes: '',
  }),
  toCreatePayload: (form) => ({
    period_id: Number(form.period_id),
    employee_id: Number(form.employee_id),
    contract_id: form.contract_id ? Number(form.contract_id) : undefined,
    basic_salary: Number(form.basic_salary || 0),
    gross_salary: Number(form.gross_salary || 0),
    net_salary: Number(form.net_salary || 0),
    total_allowances: Number(form.total_allowances || 0),
    total_deductions: Number(form.total_deductions || 0),
    overtime_pay: Number(form.overtime_pay || 0),
    leave_pay: Number(form.leave_pay || 0),
    bonus: Number(form.bonus || 0),
    penalty: Number(form.penalty || 0),
    personal_income_tax: Number(form.personal_income_tax || 0),
    advance_payment: Number(form.advance_payment || 0),
    bank_account: form.bank_account || undefined,
    bank_name: form.bank_name || undefined,
    transfer_status: form.transfer_status || undefined,
    notes: form.notes || undefined,
  }),
  toUpdatePayload: (form) => ({
    contract_id: form.contract_id ? Number(form.contract_id) : undefined,
    basic_salary: form.basic_salary !== '' ? Number(form.basic_salary) : undefined,
    gross_salary: form.gross_salary !== '' ? Number(form.gross_salary) : undefined,
    net_salary: form.net_salary !== '' ? Number(form.net_salary) : undefined,
    total_allowances: form.total_allowances !== '' ? Number(form.total_allowances) : undefined,
    total_deductions: form.total_deductions !== '' ? Number(form.total_deductions) : undefined,
    overtime_pay: form.overtime_pay !== '' ? Number(form.overtime_pay) : undefined,
    leave_pay: form.leave_pay !== '' ? Number(form.leave_pay) : undefined,
    bonus: form.bonus !== '' ? Number(form.bonus) : undefined,
    penalty: form.penalty !== '' ? Number(form.penalty) : undefined,
    personal_income_tax: form.personal_income_tax !== '' ? Number(form.personal_income_tax) : undefined,
    advance_payment: form.advance_payment !== '' ? Number(form.advance_payment) : undefined,
    bank_account: form.bank_account || undefined,
    bank_name: form.bank_name || undefined,
    transfer_status: form.transfer_status || undefined,
    notes: form.notes || undefined,
  }),
});

const transferBadge = toBadgeMap([
  ['PENDING', 'warning'],
  ['TRANSFERRED', 'success'],
  ['DELETED', 'danger'],
]);

const columns = [
  { key: 'period_code', label: 'Ky luong' },
  { key: 'employee_name', label: 'Nhan vien' },
  { key: 'gross_salary', label: 'Luong gross', formatter: formatCurrency },
  { key: 'net_salary', label: 'Luong net', formatter: formatCurrency },
  { key: 'transfer_status', label: 'Chuyen khoan', badge: true, badgeMap: transferBadge },
];

const detailFields = [
  { key: 'period_code', label: 'Ky luong' },
  { key: 'employee_name', label: 'Nhan vien' },
  { key: 'basic_salary', label: 'Luong co ban', formatter: formatCurrency },
  { key: 'gross_salary', label: 'Luong gross', formatter: formatCurrency },
  { key: 'net_salary', label: 'Luong net', formatter: formatCurrency },
  { key: 'total_allowances', label: 'Tong phu cap', formatter: formatCurrency },
  { key: 'total_deductions', label: 'Tong khau tru', formatter: formatCurrency },
  { key: 'transfer_status', label: 'Trang thai chuyen khoan' },
  { key: 'bank_account', label: 'Tai khoan ngan hang' },
  { key: 'bank_name', label: 'Ten ngan hang' },
  { key: 'notes', label: 'Ghi chu' },
];

const filterFields = [
  { key: 'period_id', type: 'select', placeholder: 'Ky luong', options: () => lookup.salaryPeriods.value },
  {
    key: 'transfer_status',
    type: 'select',
    placeholder: 'Trang thai CK',
    options: [
      { value: 'PENDING', label: 'Pending' },
      { value: 'TRANSFERRED', label: 'Transferred' },
      { value: 'DELETED', label: 'Deleted' },
    ],
  },
];

const formFields = [
  { key: 'period_id', label: 'Ky luong', type: 'select', required: true, options: () => lookup.salaryPeriods.value },
  { key: 'employee_id', label: 'Nhan vien', type: 'select', required: true, options: () => lookup.employees.value },
  { key: 'contract_id', label: 'Hop dong ID', type: 'number' },
  { key: 'basic_salary', label: 'Luong co ban', type: 'number', required: true },
  { key: 'gross_salary', label: 'Luong gross', type: 'number', required: true },
  { key: 'net_salary', label: 'Luong net', type: 'number', required: true },
  { key: 'total_allowances', label: 'Tong phu cap', type: 'number' },
  { key: 'total_deductions', label: 'Tong khau tru', type: 'number' },
  { key: 'overtime_pay', label: 'Tien tang ca', type: 'number' },
  { key: 'leave_pay', label: 'Tien nghi phep', type: 'number' },
  { key: 'bonus', label: 'Thuong', type: 'number' },
  { key: 'penalty', label: 'Phat', type: 'number' },
  { key: 'personal_income_tax', label: 'Thue TNCN', type: 'number' },
  { key: 'advance_payment', label: 'Tam ung', type: 'number' },
  { key: 'bank_account', label: 'So tai khoan' },
  { key: 'bank_name', label: 'Ngan hang' },
  {
    key: 'transfer_status',
    label: 'Trang thai CK',
    type: 'select',
    options: [
      { value: 'PENDING', label: 'Pending' },
      { value: 'TRANSFERRED', label: 'Transferred' },
      { value: 'DELETED', label: 'Deleted' },
    ],
  },
  { key: 'notes', label: 'Ghi chu', type: 'textarea', full: true },
];

onMounted(async () => {
  await lookup.bootstrap(['employees', 'salaryPeriods']);
  await crud.fetchList();
});
</script>
