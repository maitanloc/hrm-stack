<template>
  <ApiCrudModule
    title="Recruitment - Phong van"
    subtitle="Lap lich phong van va cap nhat ket qua"
    entity-name="lich phong van"
    :crud="crud"
    :columns="columns"
    :form-fields="formFields"
    :filter-fields="filterFields"
    :detail-fields="detailFields"
    :allow-delete="false"
  />
</template>

<script setup>
import { onMounted, ref } from 'vue';
import ApiCrudModule from '@/components/api/ApiCrudModule.vue';
import { useCrudModule } from '@/composables/useCrudModule.js';
import { useLookupOptions } from '@/composables/useLookupOptions.js';
import { apiRequest } from '@/services/beApi.js';
import { formatDate, toBadgeMap } from '@/views/api/formatters.js';

const lookup = useLookupOptions();
const candidateOptions = ref([]);

const loadCandidates = async () => {
  const payload = await apiRequest('/recruitment-candidates', { query: { page: 1, per_page: 300 } });
  candidateOptions.value = (Array.isArray(payload?.data) ? payload.data : []).map((item) => ({
    value: item.candidate_id,
    label: `${item.candidate_code || item.candidate_id} - ${item.full_name || ''}`,
  }));
};

const crud = useCrudModule({
  listPath: '/interviews',
  defaultFilters: {
    status: '',
    candidate_id: '',
    interviewer_id: '',
  },
  mapItem: (item) => ({
    ...item,
    id: item.interview_id,
  }),
  initialForm: () => ({
    candidate_id: '',
    interviewer_id: '',
    interview_date: '',
    interview_time: '',
    interview_mode: 'OFFLINE',
    meeting_link: '',
    location: '',
    status: 'SCHEDULED',
    result: 'PENDING',
    evaluation_notes: '',
  }),
  toCreatePayload: (form) => ({
    candidate_id: Number(form.candidate_id),
    interviewer_id: form.interviewer_id ? Number(form.interviewer_id) : undefined,
    interview_date: form.interview_date,
    interview_time: form.interview_time,
    interview_mode: form.interview_mode || undefined,
    meeting_link: form.meeting_link || undefined,
    location: form.location || undefined,
    status: form.status || undefined,
    result: form.result || undefined,
    evaluation_notes: form.evaluation_notes || undefined,
  }),
  toUpdatePayload: (form) => ({
    interviewer_id: form.interviewer_id ? Number(form.interviewer_id) : undefined,
    interview_date: form.interview_date || undefined,
    interview_time: form.interview_time || undefined,
    interview_mode: form.interview_mode || undefined,
    meeting_link: form.meeting_link || undefined,
    location: form.location || undefined,
    status: form.status || undefined,
    result: form.result || undefined,
    evaluation_notes: form.evaluation_notes || undefined,
  }),
});

const statusBadge = toBadgeMap([
  ['SCHEDULED', 'info'],
  ['DONE', 'success'],
  ['CANCELLED', 'danger'],
]);

const resultBadge = toBadgeMap([
  ['PENDING', 'warning'],
  ['PASS', 'success'],
  ['FAIL', 'danger'],
]);

const columns = [
  { key: 'candidate_name', label: 'Ung vien' },
  { key: 'interview_date', label: 'Ngay PV', formatter: formatDate },
  { key: 'interview_time', label: 'Gio PV' },
  { key: 'interviewer_name', label: 'Nguoi phong van' },
  { key: 'status', label: 'Trang thai', badge: true, badgeMap: statusBadge },
  { key: 'result', label: 'Ket qua', badge: true, badgeMap: resultBadge },
];

const detailFields = [
  { key: 'candidate_name', label: 'Ung vien' },
  { key: 'position_name', label: 'Vi tri' },
  { key: 'interview_date', label: 'Ngay phong van', formatter: formatDate },
  { key: 'interview_time', label: 'Gio phong van' },
  { key: 'interview_mode', label: 'Hinh thuc' },
  { key: 'location', label: 'Dia diem' },
  { key: 'meeting_link', label: 'Link hop' },
  { key: 'interviewer_name', label: 'Nguoi phong van' },
  { key: 'department_manager_name', label: 'Quan ly phong' },
  { key: 'status', label: 'Trang thai' },
  { key: 'result', label: 'Ket qua' },
  { key: 'evaluation_notes', label: 'Nhan xet' },
];

const filterFields = [
  {
    key: 'status',
    type: 'select',
    placeholder: 'Trang thai',
    options: [
      { value: 'SCHEDULED', label: 'Scheduled' },
      { value: 'DONE', label: 'Done' },
      { value: 'CANCELLED', label: 'Cancelled' },
    ],
  },
  { key: 'candidate_id', type: 'select', placeholder: 'Ung vien', options: () => candidateOptions.value },
  { key: 'interviewer_id', type: 'select', placeholder: 'Nguoi PV', options: () => lookup.employees.value },
];

const formFields = [
  { key: 'candidate_id', label: 'Ung vien', type: 'select', required: true, options: () => candidateOptions.value },
  { key: 'interviewer_id', label: 'Nguoi phong van', type: 'select', options: () => lookup.employees.value },
  { key: 'interview_date', label: 'Ngay phong van', type: 'date', required: true },
  { key: 'interview_time', label: 'Gio phong van', type: 'time', required: true },
  {
    key: 'interview_mode',
    label: 'Hinh thuc',
    type: 'select',
    options: [
      { value: 'OFFLINE', label: 'Offline' },
      { value: 'ONLINE', label: 'Online' },
    ],
  },
  { key: 'meeting_link', label: 'Link hop (neu online)' },
  { key: 'location', label: 'Dia diem (neu offline)' },
  {
    key: 'status',
    label: 'Trang thai',
    type: 'select',
    options: [
      { value: 'SCHEDULED', label: 'Scheduled' },
      { value: 'DONE', label: 'Done' },
      { value: 'CANCELLED', label: 'Cancelled' },
    ],
  },
  {
    key: 'result',
    label: 'Ket qua',
    type: 'select',
    options: [
      { value: 'PENDING', label: 'Pending' },
      { value: 'PASS', label: 'Pass' },
      { value: 'FAIL', label: 'Fail' },
    ],
  },
  { key: 'evaluation_notes', label: 'Nhan xet', type: 'textarea', full: true },
];

onMounted(async () => {
  await Promise.all([
    lookup.bootstrap(['employees']),
    loadCandidates(),
  ]);
  await crud.fetchList();
});
</script>
