<template>
  <section class="module-page">
    <div class="module-card">
      <h1 class="module-title">{{ title }}</h1>
      <p class="module-subtitle">{{ subtitle }}</p>
    </div>

    <div class="module-card">
      <div class="module-actions">
        <div class="d-flex gap-2 align-items-center">
          <input
            :value="searchBuffer"
            class="module-input"
            style="max-width: 320px"
            type="text"
            placeholder="Tim nhanh theo tu khoa"
            @input="onSearchInput"
            @keyup.enter="applySearch"
          />
          <button class="btn btn-outline-primary btn-lg" @click="applySearch">Tim</button>
        </div>

        <select class="module-select" style="max-width: 120px" v-model.number="crud.perPage.value">
          <option :value="10">10</option>
          <option :value="20">20</option>
          <option :value="50">50</option>
        </select>

        <button class="btn btn-primary btn-lg" @click="crud.openCreate">Them {{ entityName }}</button>

        <template v-for="filter in filterFields" :key="filter.key">
          <select
            v-if="filter.type === 'select'"
            class="module-select"
            style="max-width: 220px"
            :value="crud.filters.value[filter.key] || ''"
            @change="onFilterChange(filter.key, $event.target.value)"
          >
            <option value="">{{ filter.placeholder || 'Tat ca' }}</option>
            <option v-for="option in resolveOptions(filter)" :key="option.value" :value="option.value">{{ option.label }}</option>
          </select>

          <input
            v-else
            class="module-input"
            style="max-width: 220px"
            :type="filter.type || 'text'"
            :value="crud.filters.value[filter.key] || ''"
            :placeholder="filter.placeholder || ''"
            @change="onFilterChange(filter.key, $event.target.value)"
          />
        </template>
      </div>

      <div class="mt-3" v-if="crud.errorMessage.value">
        <div class="alert alert-danger mb-0">{{ crud.errorMessage.value }}</div>
      </div>
      <div class="mt-3" v-if="crud.successMessage.value">
        <div class="alert alert-success mb-0">{{ crud.successMessage.value }}</div>
      </div>
    </div>

    <div class="module-grid">
      <div class="module-card">
        <div v-if="crud.loading.value" class="module-empty">Dang tai du lieu...</div>
        <div v-else-if="!crud.rows.value.length" class="module-empty">Chua co du lieu.</div>
        <div v-else class="table-responsive">
          <table class="module-table">
            <thead>
              <tr>
                <th v-for="col in columns" :key="col.key">{{ col.label }}</th>
                <th style="width: 210px">Thao tac</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="row in crud.rows.value"
                :key="row[idKey]"
                @click="crud.selectRow(row)"
                style="cursor: pointer"
              >
                <td v-for="col in columns" :key="col.key">
                  <span
                    v-if="col.badge"
                    class="module-pill"
                    :class="badgeClass(row[col.key], col.badgeMap || {})"
                  >
                    {{ displayValue(row, col) }}
                  </span>
                  <span v-else>{{ displayValue(row, col) }}</span>
                </td>
                <td>
                  <div class="d-flex gap-2">
                    <button class="btn btn-outline-primary btn-sm" @click.stop="crud.openEdit(row)">Sua</button>
                    <button class="btn btn-outline-secondary btn-sm" @click.stop="crud.selectRow(row)">Xem</button>
                    <button
                      v-if="allowDelete"
                      class="btn btn-outline-danger btn-sm"
                      :disabled="crud.deletingId.value === row[idKey]"
                      @click.stop="removeRow(row)"
                    >
                      Xoa
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="d-flex justify-content-between align-items-center mt-3">
          <div class="text-secondary">Tong: {{ crud.total.value }} ban ghi</div>
          <div class="d-flex align-items-center gap-2">
            <button class="btn btn-outline-secondary" :disabled="!crud.canPrev.value" @click="crud.goPrev">Truoc</button>
            <span>Trang {{ crud.page.value }} / {{ crud.lastPage.value }}</span>
            <button class="btn btn-outline-secondary" :disabled="!crud.canNext.value" @click="crud.goNext">Sau</button>
          </div>
        </div>
      </div>

      <div class="module-card">
        <h2 class="h5 mb-3">Chi tiet {{ entityName }}</h2>
        <div v-if="crud.detailLoading.value" class="module-empty">Dang tai chi tiet...</div>
        <div v-else-if="!crud.selectedDetail.value" class="module-empty">Chon 1 dong de xem chi tiet.</div>
        <div v-else class="module-detail-list">
          <div class="module-detail-item" v-for="field in resolvedDetailFields" :key="field.key">
            <div class="module-detail-key">{{ field.label }}</div>
            <div class="module-detail-value">{{ fieldValue(crud.selectedDetail.value, field) }}</div>
          </div>
        </div>
      </div>
    </div>

    <div class="module-card">
      <h2 class="h5 mb-3">{{ crud.formMode.value === 'create' ? `Them ${entityName}` : `Cap nhat ${entityName}` }}</h2>

      <div class="module-form-grid">
        <div :class="field.full ? 'full' : ''" v-for="field in formFields" :key="field.key">
          <label class="module-label">{{ field.label }}<span v-if="field.required"> *</span></label>

          <select
            v-if="field.type === 'select'"
            class="module-select"
            v-model="crud.formData.value[field.key]"
          >
            <option value="">{{ field.placeholder || 'Vui long chon' }}</option>
            <option v-for="option in resolveOptions(field)" :key="option.value" :value="option.value">{{ option.label }}</option>
          </select>

          <textarea
            v-else-if="field.type === 'textarea'"
            class="module-textarea"
            rows="3"
            :placeholder="field.placeholder || ''"
            v-model="crud.formData.value[field.key]"
          />

          <input
            v-else
            class="module-input"
            :type="field.type || 'text'"
            :placeholder="field.placeholder || ''"
            v-model="crud.formData.value[field.key]"
          />
        </div>
      </div>

      <div class="d-flex gap-2 mt-3">
        <button class="btn btn-primary btn-lg" :disabled="crud.submitting.value" @click="crud.submitForm">
          {{ crud.submitting.value ? 'Dang luu...' : 'Luu' }}
        </button>
        <button class="btn btn-outline-secondary btn-lg" @click="crud.openCreate">Nhap moi</button>
      </div>
    </div>
  </section>
</template>

<script setup>
import { computed, ref } from 'vue';

const props = defineProps({
  title: { type: String, default: '' },
  subtitle: { type: String, default: '' },
  entityName: { type: String, default: 'du lieu' },
  idKey: { type: String, default: 'id' },
  columns: { type: Array, default: () => [] },
  formFields: { type: Array, default: () => [] },
  detailFields: { type: Array, default: () => [] },
  filterFields: { type: Array, default: () => [] },
  crud: { type: Object, required: true },
  allowDelete: { type: Boolean, default: true },
});

const searchBuffer = ref(props.crud.search.value || '');

const resolvedDetailFields = computed(() => {
  if (props.detailFields.length > 0) return props.detailFields;
  return props.columns.map((item) => ({ key: item.key, label: item.label }));
});

const resolveOptions = (field) => {
  if (typeof field.options === 'function') return field.options();
  return Array.isArray(field.options) ? field.options : [];
};

const displayValue = (row, col) => {
  if (typeof col.formatter === 'function') return col.formatter(row[col.key], row);
  const value = row[col.key];
  if (value === null || value === undefined || value === '') return '-';
  return String(value);
};

const fieldValue = (row, field) => {
  const value = row?.[field.key];
  if (typeof field.formatter === 'function') return field.formatter(value, row);
  if (value === null || value === undefined || value === '') return '-';
  return String(value);
};

const badgeClass = (value, badgeMap) => {
  const key = String(value || '').toUpperCase();
  return badgeMap[key] || 'info';
};

const onSearchInput = (event) => {
  searchBuffer.value = event.target.value;
};

const applySearch = async () => {
  await props.crud.applySearch(searchBuffer.value.trim());
};

const onFilterChange = async (key, value) => {
  await props.crud.setFilter(key, value);
};

const removeRow = async (row) => {
  const ok = window.confirm('Ban chac chan muon xoa ban ghi nay?');
  if (!ok) return;
  await props.crud.removeRow(row);
};
</script>
