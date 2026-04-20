<template>
  <section class="module-page">
    <ApiCrudModule
      title="Payroll - Ky luong"
      subtitle="Quan ly ky luong theo thang va trang thai khoa ky"
      entity-name="ky luong"
      :crud="crud"
      :columns="columns"
      :form-fields="formFields"
      :filter-fields="filterFields"
      :detail-fields="detailFields"
      :allow-delete="false"
    />

    <div class="module-card" v-if="crud.selectedDetail.value">
      <h2 class="h5 mb-2">Dong ky luong</h2>
      <p class="text-secondary mb-3">Sau khi dong ky, he thong se ghi nhan phu cap/dieu chinh vao bang luong.</p>
      <button class="btn btn-warning btn-lg" :disabled="closing" @click="closePeriod">Dong ky luong dang chon</button>
      <div class="alert alert-info mt-3 mb-0" v-if="closeMessage">{{ closeMessage }}</div>
    </div>
  </section>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import ApiCrudModule from '@/components/api/ApiCrudModule.vue';
import { useCrudModule } from '@/composables/useCrudModule.js';
import { apiRequest } from '@/services/beApi.js';
import { formatDate, toBadgeMap } from '@/views/api/formatters.js';

const closing = ref(false);
const closeMessage = ref('');

const crud = useCrudModule({
  listPath: '/salary-periods',
  defaultFilters: {
    year: '',
    status: '',
  },
  mapItem: (item) => ({
    ...item,
    id: item.period_id,
  }),
  initialForm: () => ({
    period_code: '',
    period_name: '',
    period_type: 'MONTHLY',
    year: new Date().getFullYear(),
    month: new Date().getMonth() + 1,
    start_date: '',
    end_date: '',
    payment_date: '',
    standard_working_days: 22,
    status: 'OPEN',
    notes: '',
  }),
  toCreatePayload: (form) => ({
    period_code: form.period_code,
    period_name: form.period_name,
    period_type: form.period_type,
    year: Number(form.year),
    month: form.month !== '' ? Number(form.month) : undefined,
    start_date: form.start_date,
    end_date: form.end_date,
    payment_date: form.payment_date || undefined,
    standard_working_days: form.standard_working_days !== '' ? Number(form.standard_working_days) : undefined,
    status: form.status || undefined,
    notes: form.notes || undefined,
  }),
  toUpdatePayload: (form) => ({
    period_name: form.period_name || undefined,
    period_type: form.period_type || undefined,
    year: form.year !== '' ? Number(form.year) : undefined,
    month: form.month !== '' ? Number(form.month) : undefined,
    start_date: form.start_date || undefined,
    end_date: form.end_date || undefined,
    payment_date: form.payment_date || undefined,
    standard_working_days: form.standard_working_days !== '' ? Number(form.standard_working_days) : undefined,
    status: form.status || undefined,
    notes: form.notes || undefined,
  }),
});

const statusBadge = toBadgeMap([
  ['OPEN', 'info'],
  ['CLOSED', 'success'],
  ['PAID', 'success'],
]);

const columns = [
  { key: 'period_code', label: 'Ma ky' },
  { key: 'period_name', label: 'Ten ky' },
  { key: 'year', label: 'Nam' },
  { key: 'month', label: 'Thang' },
  { key: 'start_date', label: 'Tu ngay', formatter: formatDate },
  { key: 'end_date', label: 'Den ngay', formatter: formatDate },
  { key: 'status', label: 'Trang thai', badge: true, badgeMap: statusBadge },
];

const detailFields = [
  { key: 'period_code', label: 'Ma ky luong' },
  { key: 'period_name', label: 'Ten ky' },
  { key: 'period_type', label: 'Loai ky' },
  { key: 'year', label: 'Nam' },
  { key: 'month', label: 'Thang' },
  { key: 'start_date', label: 'Ngay bat dau', formatter: formatDate },
  { key: 'end_date', label: 'Ngay ket thuc', formatter: formatDate },
  { key: 'payment_date', label: 'Ngay tra luong', formatter: formatDate },
  { key: 'standard_working_days', label: 'Cong chuan' },
  { key: 'status', label: 'Trang thai' },
  { key: 'notes', label: 'Ghi chu' },
];

const filterFields = [
  { key: 'year', type: 'number', placeholder: 'Nam' },
  {
    key: 'status',
    type: 'select',
    placeholder: 'Trang thai',
    options: [
      { value: 'OPEN', label: 'Open' },
      { value: 'CLOSED', label: 'Closed' },
      { value: 'PAID', label: 'Paid' },
    ],
  },
];

const formFields = [
  { key: 'period_code', label: 'Ma ky', required: true },
  { key: 'period_name', label: 'Ten ky', required: true },
  {
    key: 'period_type',
    label: 'Loai ky',
    type: 'select',
    options: [
      { value: 'MONTHLY', label: 'Monthly' },
      { value: 'WEEKLY', label: 'Weekly' },
      { value: 'CUSTOM', label: 'Custom' },
    ],
  },
  { key: 'year', label: 'Nam', type: 'number', required: true },
  { key: 'month', label: 'Thang', type: 'number' },
  { key: 'start_date', label: 'Tu ngay', type: 'date', required: true },
  { key: 'end_date', label: 'Den ngay', type: 'date', required: true },
  { key: 'payment_date', label: 'Ngay tra luong', type: 'date' },
  { key: 'standard_working_days', label: 'Cong chuan', type: 'number' },
  {
    key: 'status',
    label: 'Trang thai',
    type: 'select',
    options: [
      { value: 'OPEN', label: 'Open' },
      { value: 'CLOSED', label: 'Closed' },
      { value: 'PAID', label: 'Paid' },
    ],
  },
  { key: 'notes', label: 'Ghi chu', type: 'textarea', full: true },
];

const closePeriod = async () => {
  if (!crud.selectedDetail.value?.period_id) return;
  const ok = window.confirm('Ban chac chan muon dong ky luong nay?');
  if (!ok) return;
  closing.value = true;
  closeMessage.value = '';
  try {
    const payload = await apiRequest(`/salary-periods/${crud.selectedDetail.value.period_id}/close`, { method: 'POST' });
    closeMessage.value = payload?.message || 'Dong ky luong thanh cong';
    await crud.fetchList();
    await crud.fetchDetail(crud.selectedDetail.value.period_id);
  } catch (error) {
    closeMessage.value = error?.message || 'Khong dong duoc ky luong';
  } finally {
    closing.value = false;
  }
};

onMounted(async () => {
  await crud.fetchList();
});
</script>
