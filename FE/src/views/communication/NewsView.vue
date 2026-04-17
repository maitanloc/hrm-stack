<template>
  <ApiCrudModule
    title="Truyen thong - Tin tuc"
    subtitle="Quan ly tin tuc / thong bao noi bo cong ty"
    entity-name="tin tuc"
    id-key="news_id"
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
import { useLookupOptions } from '@/composables/useLookupOptions.js';
import { formatDate, toBadgeMap } from '@/views/api/formatters.js';

const lookup = useLookupOptions();

const crud = useCrudModule({
  listPath: '/news',
  detailPath: (id) => `/news/${id}`,
  createPath: '/news',
  updatePath: (id) => `/news/${id}`,
  deletePath: (id) => `/news/${id}`,
  getItemId: (item) => item?.news_id,
  defaultFilters: { status: '' },
  mapItem: (item) => ({ ...item, id: item.news_id }),
  initialForm: () => ({
    title: '',
    summary: '',
    content: '',
    category_id: '',
    priority: 'NORMAL',
    is_important: false,
    is_pinned: false,
    published_date: new Date().toISOString().slice(0, 10),
    expiry_date: '',
    status: 'DRAFT',
  }),
  toCreatePayload: (form) => ({
    title: form.title,
    summary: form.summary || undefined,
    content: form.content || undefined,
    category_id: form.category_id ? Number(form.category_id) : undefined,
    priority: form.priority || undefined,
    is_important: form.is_important || false,
    is_pinned: form.is_pinned || false,
    published_date: form.published_date || undefined,
    expiry_date: form.expiry_date || undefined,
    status: form.status || 'DRAFT',
  }),
  toUpdatePayload: (form) => ({
    title: form.title || undefined,
    summary: form.summary || undefined,
    content: form.content || undefined,
    category_id: form.category_id ? Number(form.category_id) : undefined,
    priority: form.priority || undefined,
    is_important: form.is_important,
    is_pinned: form.is_pinned,
    published_date: form.published_date || undefined,
    expiry_date: form.expiry_date || undefined,
    status: form.status || undefined,
  }),
});

const statusBadge = toBadgeMap([
  ['DRAFT', 'warning'],
  ['PUBLISHED', 'success'],
  ['ARCHIVED', 'info'],
]);

const priorityBadge = toBadgeMap([
  ['HIGH', 'danger'],
  ['NORMAL', 'info'],
  ['LOW', 'success'],
]);

const columns = [
  { key: 'title', label: 'Tieu de' },
  { key: 'category_name', label: 'Danh muc' },
  { key: 'priority', label: 'Uu tien', badge: true, badgeMap: priorityBadge },
  { key: 'published_date', label: 'Ngay dang', formatter: formatDate },
  { key: 'status', label: 'Trang thai', badge: true, badgeMap: statusBadge },
  { key: 'view_count', label: 'Luot xem' },
];

const detailFields = [
  { key: 'news_code', label: 'Ma tin' },
  { key: 'title', label: 'Tieu de' },
  { key: 'summary', label: 'Tom tat' },
  { key: 'content', label: 'Noi dung' },
  { key: 'category_name', label: 'Danh muc' },
  { key: 'priority', label: 'Uu tien' },
  { key: 'is_important', label: 'Quan trong', formatter: (v) => v ? 'Co' : 'Khong' },
  { key: 'is_pinned', label: 'Ghim len dau', formatter: (v) => v ? 'Co' : 'Khong' },
  { key: 'published_date', label: 'Ngay dang', formatter: formatDate },
  { key: 'expiry_date', label: 'Ngay het han', formatter: formatDate },
  { key: 'status', label: 'Trang thai' },
  { key: 'view_count', label: 'Luot xem' },
];

const filterFields = [
  {
    key: 'status',
    type: 'select',
    placeholder: 'Trang thai',
    options: [
      { value: 'DRAFT', label: 'Nhap' },
      { value: 'PUBLISHED', label: 'Da dang' },
      { value: 'ARCHIVED', label: 'Luu tru' },
    ],
  },
];

const formFields = [
  { key: 'title', label: 'Tieu de', required: true },
  { key: 'category_id', label: 'Danh muc', type: 'select', options: () => lookup.newsCategories.value },
  {
    key: 'priority',
    label: 'Uu tien',
    type: 'select',
    options: [
      { value: 'LOW', label: 'Thap' },
      { value: 'NORMAL', label: 'Binh thuong' },
      { value: 'HIGH', label: 'Cao' },
    ],
  },
  {
    key: 'status',
    label: 'Trang thai',
    type: 'select',
    options: [
      { value: 'DRAFT', label: 'Nhap' },
      { value: 'PUBLISHED', label: 'Da dang' },
      { value: 'ARCHIVED', label: 'Luu tru' },
    ],
  },
  { key: 'published_date', label: 'Ngay dang', type: 'date' },
  { key: 'expiry_date', label: 'Ngay het han', type: 'date' },
  { key: 'summary', label: 'Tom tat', type: 'textarea', full: true },
  { key: 'content', label: 'Noi dung', type: 'textarea', full: true },
];

onMounted(async () => {
  await lookup.bootstrap(['newsCategories']);
  await crud.fetchList();
});
</script>
