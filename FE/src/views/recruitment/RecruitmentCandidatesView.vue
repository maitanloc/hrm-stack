<template>
  <ApiCrudModule
    title="Recruitment - Ung vien"
    subtitle="Theo doi ung vien, trang thai va ket qua danh gia"
    entity-name="ung vien"
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
import { formatDate, toBadgeMap } from '@/views/api/formatters.js';

const lookup = useLookupOptions();

const crud = useCrudModule({
  listPath: '/recruitment-candidates',
  defaultFilters: {
    status: '',
    recruitment_position_id: '',
  },
  mapItem: (item) => ({
    ...item,
    id: item.candidate_id,
    ai_score: item.ai_score ?? '-',
  }),
  initialForm: () => ({
    candidate_code: '',
    full_name: '',
    email: '',
    phone_number: '',
    recruitment_position_id: '',
    source_channel: '',
    ai_score: '',
    application_status: 'NEW',
    applied_at: '',
    notes: '',
    cv_url: '',
  }),
  toCreatePayload: (form) => ({
    candidate_code: form.candidate_code || undefined,
    full_name: form.full_name,
    email: form.email || undefined,
    phone_number: form.phone_number || undefined,
    recruitment_position_id: Number(form.recruitment_position_id),
    source_channel: form.source_channel || undefined,
    ai_score: form.ai_score !== '' ? Number(form.ai_score) : undefined,
    application_status: form.application_status || undefined,
    applied_at: form.applied_at || undefined,
    notes: form.notes || undefined,
    cv_url: form.cv_url || undefined,
  }),
  toUpdatePayload: (form) => ({
    full_name: form.full_name || undefined,
    email: form.email || undefined,
    phone_number: form.phone_number || undefined,
    recruitment_position_id: form.recruitment_position_id ? Number(form.recruitment_position_id) : undefined,
    source_channel: form.source_channel || undefined,
    ai_score: form.ai_score !== '' ? Number(form.ai_score) : undefined,
    application_status: form.application_status || undefined,
    applied_at: form.applied_at || undefined,
    notes: form.notes || undefined,
    cv_url: form.cv_url || undefined,
  }),
});

const statusBadge = toBadgeMap([
  ['NEW', 'info'],
  ['SCREENING', 'warning'],
  ['INTERVIEW', 'warning'],
  ['PASSED', 'success'],
  ['REJECTED', 'danger'],
]);

const columns = [
  { key: 'candidate_code', label: 'Ma UV' },
  { key: 'full_name', label: 'Ho ten' },
  { key: 'position_name', label: 'Vi tri' },
  { key: 'ai_score', label: 'AI score' },
  { key: 'application_status', label: 'Trang thai', badge: true, badgeMap: statusBadge },
  { key: 'applied_at', label: 'Ngay nop', formatter: formatDate },
];

const detailFields = [
  { key: 'candidate_code', label: 'Ma ung vien' },
  { key: 'full_name', label: 'Ho ten' },
  { key: 'email', label: 'Email' },
  { key: 'phone_number', label: 'So dien thoai' },
  { key: 'position_name', label: 'Vi tri ung tuyen' },
  { key: 'source_channel', label: 'Kenh tuyen dung' },
  { key: 'ai_score', label: 'AI score' },
  { key: 'application_status', label: 'Trang thai' },
  { key: 'notes', label: 'Ghi chu' },
];

const filterFields = [
  {
    key: 'status',
    type: 'select',
    placeholder: 'Trang thai',
    options: [
      { value: 'NEW', label: 'New' },
      { value: 'SCREENING', label: 'Screening' },
      { value: 'INTERVIEW', label: 'Interview' },
      { value: 'PASSED', label: 'Passed' },
      { value: 'REJECTED', label: 'Rejected' },
    ],
  },
  {
    key: 'recruitment_position_id',
    type: 'select',
    placeholder: 'Vi tri',
    options: () => lookup.recruitmentPositions.value,
  },
];

const formFields = [
  { key: 'candidate_code', label: 'Ma ung vien', placeholder: 'Bo trong de tu sinh' },
  { key: 'full_name', label: 'Ho ten', required: true },
  { key: 'email', label: 'Email', type: 'email' },
  { key: 'phone_number', label: 'So dien thoai' },
  { key: 'recruitment_position_id', label: 'Vi tri', type: 'select', required: true, options: () => lookup.recruitmentPositions.value },
  { key: 'source_channel', label: 'Nguon ung vien' },
  { key: 'ai_score', label: 'AI score', type: 'number' },
  {
    key: 'application_status',
    label: 'Trang thai',
    type: 'select',
    options: [
      { value: 'NEW', label: 'New' },
      { value: 'SCREENING', label: 'Screening' },
      { value: 'INTERVIEW', label: 'Interview' },
      { value: 'PASSED', label: 'Passed' },
      { value: 'REJECTED', label: 'Rejected' },
    ],
  },
  { key: 'applied_at', label: 'Ngay nop', type: 'date' },
  { key: 'cv_url', label: 'Link CV' },
  { key: 'notes', label: 'Ghi chu', type: 'textarea', full: true },
];

onMounted(async () => {
  await lookup.bootstrap(['recruitmentPositions']);
  await crud.fetchList();
});
</script>
