<template>
  <ApiCrudModule
    title="Attendance - So du phep"
    subtitle="Xem so du phep nam cua nhan vien"
    entity-name="so du phep"
    id-key="balance_id"
    :crud="crud"
    :columns="columns"
    :form-fields="[]"
    :detail-fields="detailFields"
    :allow-delete="false"
  />
</template>

<script setup>
import { onMounted } from 'vue';
import ApiCrudModule from '@/components/api/ApiCrudModule.vue';
import { useCrudModule } from '@/composables/useCrudModule.js';

const crud = useCrudModule({
  listPath: '/leave-balances',
  detailPath: (id) => `/leave-balances/${id}`,
  getItemId: (item) => item?.balance_id,
  mapItem: (item) => ({
    ...item,
    id: item.balance_id,
    employee_name: item.full_name || item.employee_name || `#${item.employee_id}`,
  }),
  initialForm: () => ({}),
});

const columns = [
  { key: 'employee_name', label: 'Nhan vien' },
  { key: 'leave_type', label: 'Loai phep' },
  { key: 'year', label: 'Nam' },
  { key: 'total_days', label: 'Tong phep' },
  { key: 'used_days', label: 'Da dung' },
  { key: 'remaining_days', label: 'Con lai' },
];

const detailFields = [
  { key: 'employee_name', label: 'Nhan vien' },
  { key: 'leave_type', label: 'Loai phep' },
  { key: 'year', label: 'Nam' },
  { key: 'total_days', label: 'Tong so ngay phep' },
  { key: 'used_days', label: 'So ngay da dung' },
  { key: 'remaining_days', label: 'So ngay con lai' },
  { key: 'carried_over', label: 'Phep chuyen tu nam truoc' },
  { key: 'expires_at', label: 'Han su dung' },
];

onMounted(() => crud.fetchList());
</script>
