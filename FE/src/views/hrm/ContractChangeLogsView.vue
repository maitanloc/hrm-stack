<template>
  <ApiCrudModule
    title="HRM - Lich su thay doi hop dong"
    subtitle="Ghi nhan cac thay doi / su kien lien quan hop dong lao dong"
    entity-name="ban ghi"
    id-key="id"
    :crud="crud"
    :columns="columns"
    :form-fields="formFields"
    :detail-fields="detailFields"
    :allow-delete="false"
  />
</template>

<script setup>
import { onMounted } from 'vue';
import ApiCrudModule from '@/components/api/ApiCrudModule.vue';
import { useCrudModule } from '@/composables/useCrudModule.js';
import { formatDateTime, toBadgeMap } from '@/views/api/formatters.js';

const crud = useCrudModule({
  listPath: '/contract-change-logs',
  createPath: '/contract-change-logs',
  mapItem: (item) => ({
    ...item,
    id: item.log_id || item.id,
  }),
  initialForm: () => ({
    contract_id: '',
    contract_no: '',
    employee_name: '',
    action_type: 'UPDATE',
    content: '',
    notes: '',
  }),
  toCreatePayload: (form) => ({
    contract_id: form.contract_id ? Number(form.contract_id) : undefined,
    contract_no: form.contract_no,
    employee_name: form.employee_name,
    action_type: form.action_type,
    content: form.content || undefined,
    notes: form.notes || undefined,
  }),
});

const actionBadge = toBadgeMap([
  ['CREATE', 'info'],
  ['SIGN', 'info'],
  ['EXTEND', 'success'],
  ['UPDATE', 'warning'],
  ['TERMINATE', 'danger'],
]);

const columns = [
  { key: 'contract_no', label: 'So hop dong' },
  { key: 'employee_name', label: 'Nhan vien' },
  { key: 'action_type', label: 'Hanh dong', badge: true, badgeMap: actionBadge },
  { key: 'content', label: 'Noi dung' },
  { key: 'created_at', label: 'Ngay ghi', formatter: formatDateTime },
];

const detailFields = [
  { key: 'contract_no', label: 'So hop dong' },
  { key: 'employee_name', label: 'Nhan vien' },
  { key: 'action_type', label: 'Loai hanh dong' },
  { key: 'content', label: 'Noi dung' },
  { key: 'notes', label: 'Ghi chu' },
  { key: 'created_by', label: 'Nguoi tao' },
  { key: 'created_at', label: 'Ngay tao', formatter: formatDateTime },
];

const formFields = [
  { key: 'contract_no', label: 'So hop dong', required: true },
  { key: 'employee_name', label: 'Ten nhan vien', required: true },
  {
    key: 'action_type',
    label: 'Loai hanh dong',
    type: 'select',
    required: true,
    options: [
      { value: 'CREATE', label: 'Tao moi' },
      { value: 'SIGN', label: 'Ky moi' },
      { value: 'EXTEND', label: 'Gia han' },
      { value: 'UPDATE', label: 'Cap nhat' },
      { value: 'TERMINATE', label: 'Thanh ly' },
    ],
  },
  { key: 'contract_id', label: 'Hop dong ID', type: 'number' },
  { key: 'content', label: 'Noi dung', type: 'textarea', full: true },
  { key: 'notes', label: 'Ghi chu', type: 'textarea', full: true },
];

onMounted(() => crud.fetchList());
</script>
