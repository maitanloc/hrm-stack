<template>
  <section class="module-page">
    <div class="module-card">
      <h1 class="module-title">Giám sát GPS chấm công</h1>
      <p class="module-subtitle">Dữ liệu vi phạm được lấy trực tiếp từ backend geofence và chỉ hiển thị cảnh báo thực sự cần theo dõi.</p>
    </div>

    <div class="module-card">
      <div class="module-actions">
        <select class="module-select" style="max-width: 200px" v-model="filters.risk_level" @change="loadRiskAlerts">
          <option value="">Tất cả mức độ</option>
          <option value="YELLOW">Vàng (YELLOW)</option>
          <option value="RED">Đỏ (RED)</option>
        </select>

        <select class="module-select" style="max-width: 200px" v-model="filters.status" @change="loadRiskAlerts">
          <option value="">Tất cả trạng thái</option>
          <option value="OPEN">Đang mở</option>
          <option value="RESOLVED">Đã xử lý</option>
        </select>

        <input class="module-input" style="max-width: 180px" type="date" v-model="filters.date_from" @change="loadRiskAlerts" />
        <input class="module-input" style="max-width: 180px" type="date" v-model="filters.date_to" @change="loadRiskAlerts" />
      </div>

      <div class="mt-3" v-if="errorMessage">
        <div class="alert alert-danger mb-0">{{ errorMessage }}</div>
      </div>
    </div>

    <div class="module-card">
      <div v-if="loading" class="module-empty">Đang tải cảnh báo…</div>
      <div v-else-if="!rows.length" class="module-empty">Không có cảnh báo nào.</div>
      <div v-else class="table-responsive">
        <table class="module-table">
          <thead>
            <tr>
              <th>Thời điểm</th>
              <th>Nhân viên</th>
              <th>Loại sự kiện</th>
              <th>Mức độ</th>
              <th>Diễn giải</th>
              <th>Mã</th>
              <th>Khoảng cách thực tế</th>
              <th>Trạng thái</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="item in rows" :key="item.id">
              <td class="whitespace-nowrap">{{ formatDateTime(item.happened_at) }}</td>
              <td>{{ item.employee_name || `#${item.employee_id}` }}</td>
              <td>{{ labelAttendanceType(item.attendance_type) }}</td>
              <td>
                <span class="module-pill" :class="riskPillClass(item.risk_level)">{{ riskLabel(item.risk_level) }}</span>
              </td>
              <td>{{ labelReasonCode(item.reason_code) }}</td>
              <td class="text-[var(--sys-text-secondary)] text-xs whitespace-nowrap">{{ item.reason_code || '—' }}</td>
              <td>{{ distanceLabel(item) }}</td>
              <td>
                <span class="module-pill" :class="item.status === 'RESOLVED' ? 'success' : 'info'">{{ statusLabel(item.status) }}</span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div class="d-flex justify-content-between align-items-center mt-3">
        <div class="text-secondary">Tổng: {{ total }}</div>
        <div class="d-flex align-items-center gap-2">
          <button class="btn btn-outline-secondary" type="button" :disabled="page <= 1" @click="prevPage">Trước</button>
          <span>Trang {{ page }} / {{ lastPage }}</span>
          <button class="btn btn-outline-secondary" type="button" :disabled="page >= lastPage" @click="nextPage">Sau</button>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { apiRequest } from '@/services/beApi.js';
import { formatDateTime } from '@/views/api/formatters.js';
import { labelAttendanceType, labelReasonCode } from '@/utils/attendanceRiskLabels.js';

const loading = ref(false);
const errorMessage = ref('');
const rows = ref([]);
const page = ref(1);
const perPage = ref(20);
const total = ref(0);
const lastPage = ref(1);

const filters = ref({
  risk_level: '',
  status: '',
  date_from: '',
  date_to: '',
});

function riskPillClass(level) {
  const u = String(level || '').toUpperCase();
  if (u === 'RED') return 'danger';
  if (u === 'YELLOW') return 'warning';
  return 'info';
}

function riskLabel(level) {
  const u = String(level || '').toUpperCase();
  if (u === 'RED') return 'RED';
  if (u === 'YELLOW') return 'YELLOW';
  if (u === 'GREEN') return 'GREEN';
  return u || '—';
}

function distanceLabel(item) {
  const reason = String(item?.reason_code || '').toUpperCase();
  const distance = Number(item?.distance_m);
  if (['DEVICE_NOT_TRUSTED', 'MULTI_DEVICE_SHORT_TIME'].includes(reason)) {
    return 'Không áp dụng';
  }
  if (!Number.isFinite(distance) || distance < 0) {
    return '—';
  }
  return `${Math.round(distance)}m`;
}

function statusLabel(status) {
  if (status === 'RESOLVED') return 'Đã xử lý';
  if (status === 'OPEN') return 'Đang mở';
  return status || '—';
}

const loadRiskAlerts = async () => {
  loading.value = true;
  errorMessage.value = '';
  try {
    const payload = await apiRequest('/risk-alerts', {
      query: {
        page: page.value,
        per_page: perPage.value,
        risk_level: filters.value.risk_level || undefined,
        status: filters.value.status || undefined,
        date_from: filters.value.date_from || undefined,
        date_to: filters.value.date_to || undefined,
      },
    });

    rows.value = Array.isArray(payload?.data) ? payload.data : [];
    total.value = Number(payload?.meta?.total || rows.value.length || 0);
    lastPage.value = Number(payload?.meta?.last_page || 1);
    if (lastPage.value < 1) lastPage.value = 1;
  } catch (error) {
    errorMessage.value = error?.message || 'Không tải được cảnh báo rủi ro.';
    rows.value = [];
    total.value = 0;
    lastPage.value = 1;
  } finally {
    loading.value = false;
  }
};

const prevPage = async () => {
  if (page.value <= 1) return;
  page.value -= 1;
  await loadRiskAlerts();
};

const nextPage = async () => {
  if (page.value >= lastPage.value) return;
  page.value += 1;
  await loadRiskAlerts();
};

onMounted(loadRiskAlerts);
</script>
