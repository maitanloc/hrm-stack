<template>
  <section class="module-page">
    <div class="module-card">
      <h1 class="module-title">Cai dat he thong</h1>
      <p class="module-subtitle">Thong tin chung cong ty va cau hinh thong bao</p>
    </div>

    <!-- Messages -->
    <div class="module-card" v-if="errorMessage">
      <div class="alert alert-danger mb-0">{{ errorMessage }}</div>
    </div>
    <div class="module-card" v-if="successMessage">
      <div class="alert alert-success mb-0">{{ successMessage }}</div>
    </div>

    <!-- General Settings -->
    <div class="module-card">
      <h2 class="h5 mb-3">Thong tin chung</h2>
      <div v-if="loadingGeneral" class="module-empty">Dang tai...</div>
      <div v-else class="module-form-grid">
        <div>
          <label class="module-label">Ten cong ty</label>
          <input class="module-input" v-model="general.company_name" />
        </div>
        <div>
          <label class="module-label">Ma so thue</label>
          <input class="module-input" v-model="general.company_tax_code" />
        </div>
        <div class="full">
          <label class="module-label">Dia chi</label>
          <input class="module-input" v-model="general.company_address" />
        </div>
        <div>
          <label class="module-label">Ngon ngu</label>
          <select class="module-select" v-model="general.system_language">
            <option value="vi">Tieng Viet</option>
            <option value="en">English</option>
          </select>
        </div>
        <div>
          <label class="module-label">Mui gio</label>
          <select class="module-select" v-model="general.system_timezone">
            <option value="Asia/Ho_Chi_Minh">Asia/Ho_Chi_Minh (UTC+7)</option>
            <option value="UTC">UTC</option>
          </select>
        </div>
        <div>
          <label class="module-label">Yeu cau 2FA</label>
          <select class="module-select" v-model="general.security_require_2fa">
            <option :value="true">Co</option>
            <option :value="false">Khong</option>
          </select>
        </div>
        <div>
          <label class="module-label">Session timeout (phut)</label>
          <input class="module-input" type="number" v-model="general.security_session_timeout" />
        </div>
      </div>
      <div class="d-flex gap-2 mt-3">
        <button class="btn btn-primary btn-lg" :disabled="savingGeneral" @click="saveGeneral">
          {{ savingGeneral ? 'Dang luu...' : 'Luu cai dat chung' }}
        </button>
      </div>
    </div>

    <!-- Notification Config -->
    <div class="module-card">
      <h2 class="h5 mb-3">Cau hinh thong bao</h2>
      <div v-if="loadingNotif" class="module-empty">Dang tai...</div>
      <div v-else-if="!notifItems.length" class="module-empty">Chua co cau hinh thong bao.</div>
      <div v-else class="table-responsive">
        <table class="module-table">
          <thead>
            <tr>
              <th>Loai thong bao</th>
              <th>Bat</th>
              <th>Email</th>
              <th>In-app</th>
              <th>Ngay truoc</th>
              <th>Nguoi nhan</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="item in notifItems" :key="item.notification_type">
              <td>{{ item.notification_type }}</td>
              <td>
                <input type="checkbox" :checked="item.is_enabled == 1" @change="item.is_enabled = $event.target.checked ? 1 : 0" />
              </td>
              <td>
                <input type="checkbox" :checked="item.send_email == 1" @change="item.send_email = $event.target.checked ? 1 : 0" />
              </td>
              <td>
                <input type="checkbox" :checked="item.send_in_app == 1" @change="item.send_in_app = $event.target.checked ? 1 : 0" />
              </td>
              <td>
                <input class="module-input" type="number" style="max-width:80px" v-model.number="item.days_before_trigger" />
              </td>
              <td>
                <input class="module-input" style="max-width:140px" v-model="item.recipients" />
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <div class="d-flex gap-2 mt-3" v-if="notifItems.length">
        <button class="btn btn-primary btn-lg" :disabled="savingNotif" @click="saveNotif">
          {{ savingNotif ? 'Dang luu...' : 'Luu cau hinh thong bao' }}
        </button>
      </div>
    </div>
  </section>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { apiRequest } from '@/services/beApi.js';

const loadingGeneral = ref(false);
const savingGeneral = ref(false);
const loadingNotif = ref(false);
const savingNotif = ref(false);
const errorMessage = ref('');
const successMessage = ref('');

const general = ref({
  company_name: '',
  company_tax_code: '',
  company_address: '',
  system_language: 'vi',
  system_timezone: 'Asia/Ho_Chi_Minh',
  security_require_2fa: false,
  security_session_timeout: '60',
  backup_last_run_at: '',
});

const notifItems = ref([]);

const clearMessages = () => {
  errorMessage.value = '';
  successMessage.value = '';
};

const fetchGeneral = async () => {
  loadingGeneral.value = true;
  try {
    const payload = await apiRequest('/settings/general');
    if (payload?.data) {
      general.value = { ...general.value, ...payload.data };
    }
  } catch (error) {
    errorMessage.value = error?.message || 'Khong tai duoc cai dat chung';
  } finally {
    loadingGeneral.value = false;
  }
};

const saveGeneral = async () => {
  clearMessages();
  savingGeneral.value = true;
  try {
    await apiRequest('/settings/general', {
      method: 'PUT',
      body: general.value,
    });
    successMessage.value = 'Da luu cai dat chung thanh cong';
  } catch (error) {
    errorMessage.value = error?.message || 'Khong luu duoc cai dat';
  } finally {
    savingGeneral.value = false;
  }
};

const fetchNotif = async () => {
  loadingNotif.value = true;
  try {
    const payload = await apiRequest('/settings/notifications');
    notifItems.value = Array.isArray(payload?.data) ? payload.data : [];
  } catch (error) {
    errorMessage.value = error?.message || 'Khong tai duoc cau hinh thong bao';
  } finally {
    loadingNotif.value = false;
  }
};

const saveNotif = async () => {
  clearMessages();
  savingNotif.value = true;
  try {
    await apiRequest('/settings/notifications', {
      method: 'PUT',
      body: { items: notifItems.value },
    });
    successMessage.value = 'Da luu cau hinh thong bao thanh cong';
  } catch (error) {
    errorMessage.value = error?.message || 'Khong luu duoc cau hinh thong bao';
  } finally {
    savingNotif.value = false;
  }
};

onMounted(async () => {
  await Promise.all([fetchGeneral(), fetchNotif()]);
});
</script>
