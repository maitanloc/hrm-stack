<template>
  <ApiCrudModule
    title="Time & Attendance - Cham cong"
    subtitle="Theo doi bang cham cong ngay cong va cap nhat thu cong"
    entity-name="bang cham cong"
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
import { toApiDateTime } from '@/services/beApi.js';
import { formatDate, formatDateTime, toBadgeMap } from '@/views/api/formatters.js';

const lookup = useLookupOptions();

const crud = useCrudModule({
  listPath: '/attendances',
  defaultFilters: {
    status: '',
    date_from: '',
    date_to: '',
  },
  mapItem: (item) => ({
    ...item,
    id: item.attendance_id,
    employee_name: item.full_name || item.employee_name || `#${item.employee_id}`,
  }),
  initialForm: () => ({
    employee_id: '',
    attendance_date: '',
    check_in_time: '',
    check_out_time: '',
    check_in_time_2: '',
    check_out_time_2: '',
    check_in_method: 'MOBILE',
    check_out_method: 'MOBILE',
    work_type: 'WORK',
    actual_working_hours: '',
    overtime_hours: '',
    late_minutes: '',
    early_leave_minutes: '',
    status: 'CHỜ_DUYỆT',
    notes: '',
  }),
  toCreatePayload: (form) => ({
    employee_id: form.employee_id ? Number(form.employee_id) : undefined,
    attendance_date: form.attendance_date,
    check_in_time: toApiDateTime(form.check_in_time) || undefined,
    check_out_time: toApiDateTime(form.check_out_time) || undefined,
    check_in_time_2: toApiDateTime(form.check_in_time_2) || undefined,
    check_out_time_2: toApiDateTime(form.check_out_time_2) || undefined,
    check_in_method: form.check_in_method || undefined,
    check_out_method: form.check_out_method || undefined,
    work_type: form.work_type || undefined,
    actual_working_hours: form.actual_working_hours !== '' ? Number(form.actual_working_hours) : undefined,
    overtime_hours: form.overtime_hours !== '' ? Number(form.overtime_hours) : undefined,
    late_minutes: form.late_minutes !== '' ? Number(form.late_minutes) : undefined,
    early_leave_minutes: form.early_leave_minutes !== '' ? Number(form.early_leave_minutes) : undefined,
    status: form.status || undefined,
    notes: form.notes || undefined,
  }),
  toUpdatePayload: (form) => ({
    check_in_time: toApiDateTime(form.check_in_time) || undefined,
    check_out_time: toApiDateTime(form.check_out_time) || undefined,
    check_in_time_2: toApiDateTime(form.check_in_time_2) || undefined,
    check_out_time_2: toApiDateTime(form.check_out_time_2) || undefined,
    check_in_method: form.check_in_method || undefined,
    check_out_method: form.check_out_method || undefined,
    work_type: form.work_type || undefined,
    actual_working_hours: form.actual_working_hours !== '' ? Number(form.actual_working_hours) : undefined,
    overtime_hours: form.overtime_hours !== '' ? Number(form.overtime_hours) : undefined,
    late_minutes: form.late_minutes !== '' ? Number(form.late_minutes) : undefined,
    early_leave_minutes: form.early_leave_minutes !== '' ? Number(form.early_leave_minutes) : undefined,
    status: form.status || undefined,
    notes: form.notes || undefined,
  }),
});

const statusBadge = toBadgeMap([
  ['CHỜ_DUYỆT', 'warning'],
  ['ĐÃ_DUYỆT', 'success'],
  ['TỪ_CHỐI', 'danger'],
  ['NHẬP_THỦ_CÔNG', 'info'],
]);

const columns = [
  { key: 'attendance_date', label: 'Ngay cong', formatter: formatDate },
  { key: 'employee_name', label: 'Nhan vien' },
  { key: 'check_in_time', label: 'Check-in', formatter: formatDateTime },
  { key: 'check_out_time', label: 'Check-out', formatter: formatDateTime },
  { key: 'late_minutes', label: 'Di muon (phut)' },
  { key: 'status', label: 'Trang thai', badge: true, badgeMap: statusBadge },
];

const detailFields = [
  { key: 'employee_name', label: 'Nhan vien' },
  { key: 'attendance_date', label: 'Ngay cong', formatter: formatDate },
  { key: 'check_in_time', label: 'Check-in 1', formatter: formatDateTime },
  { key: 'check_out_time', label: 'Check-out 1', formatter: formatDateTime },
  { key: 'check_in_time_2', label: 'Check-in 2', formatter: formatDateTime },
  { key: 'check_out_time_2', label: 'Check-out 2', formatter: formatDateTime },
  { key: 'actual_working_hours', label: 'Gio cong thuc te' },
  { key: 'overtime_hours', label: 'Gio tang ca' },
  { key: 'status', label: 'Trang thai' },
  { key: 'notes', label: 'Ghi chu' },
];

const filterFields = [
  {
    key: 'status',
    type: 'select',
    placeholder: 'Trang thai',
    options: [
      { value: 'CHỜ_DUYỆT', label: 'Cho duyet' },
      { value: 'ĐÃ_DUYỆT', label: 'Da duyet' },
      { value: 'TỪ_CHỐI', label: 'Tu choi' },
      { value: 'NHẬP_THỦ_CÔNG', label: 'Nhap thu cong' },
    ],
  },
  { key: 'date_from', type: 'date', placeholder: 'Tu ngay' },
  { key: 'date_to', type: 'date', placeholder: 'Den ngay' },
];

const formFields = [
  { key: 'employee_id', label: 'Nhan vien', type: 'select', required: true, options: () => lookup.employees.value },
  { key: 'attendance_date', label: 'Ngay cong', type: 'date', required: true },
  { key: 'check_in_time', label: 'Gio vao 1', type: 'datetime-local' },
  { key: 'check_out_time', label: 'Gio ra 1', type: 'datetime-local' },
  { key: 'check_in_time_2', label: 'Gio vao 2', type: 'datetime-local' },
  { key: 'check_out_time_2', label: 'Gio ra 2', type: 'datetime-local' },
  { key: 'check_in_method', label: 'Phuong thuc vao' },
  { key: 'check_out_method', label: 'Phuong thuc ra' },
  { key: 'work_type', label: 'Loai cong viec' },
  { key: 'actual_working_hours', label: 'Gio cong', type: 'number' },
  { key: 'overtime_hours', label: 'Tang ca', type: 'number' },
  { key: 'late_minutes', label: 'Di muon (phut)', type: 'number' },
  { key: 'early_leave_minutes', label: 'Ve som (phut)', type: 'number' },
  {
    key: 'status',
    label: 'Trang thai',
    type: 'select',
    options: [
      { value: 'CHỜ_DUYỆT', label: 'Cho duyet' },
      { value: 'ĐÃ_DUYỆT', label: 'Da duyet' },
      { value: 'TỪ_CHỐI', label: 'Tu choi' },
      { value: 'NHẬP_THỦ_CÔNG', label: 'Nhap thu cong' },
    ],
  },
  { key: 'notes', label: 'Ghi chu', type: 'textarea', full: true },
];

onMounted(async () => {
  await lookup.bootstrap(['employees']);
  await crud.fetchList();
});
</script>
