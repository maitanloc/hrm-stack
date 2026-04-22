<template>
  <Transition name="fade">
    <div v-if="visible" class="result-overlay" :class="data.success ? 'bg-success' : 'bg-error'">
      <div class="result-content">
        <div class="icon-wrapper">
          <span v-if="data.success" class="material-symbols-outlined icon-main anim-pop">check_circle</span>
          <span v-else class="material-symbols-outlined icon-main anim-shake">error</span>
        </div>

        <h2 class="status-title">{{ data.title }}</h2>
        <p class="status-msg">{{ data.message }}</p>

        <div v-if="data.employee" class="employee-info anim-up">
          <div class="avatar-ring">
             <img v-if="data.employee.avatar_url" :src="data.employee.avatar_url" class="emp-avatar" alt="avatar" />
             <div v-else class="emp-initials">{{ getInitials(data.employee.full_name) }}</div>
          </div>
          <div class="emp-details">
            <div class="emp-name">{{ data.employee.full_name }}</div>
            <div class="emp-code">Mã: {{ data.employee.employee_code }}</div>
            <div class="emp-id" v-if="data.employee.employee_id">ID: {{ data.employee.employee_id }}</div>
            <div class="emp-dept">{{ data.employee.department_name }}</div>
          </div>
        </div>

        <div v-if="data.event" class="event-meta">
          <div class="meta-item">
            <span class="material-symbols-outlined">schedule</span>
            <span>{{ data.event.time }}</span>
          </div>
          <div class="meta-item">
            <span class="material-symbols-outlined">calendar_today</span>
            <span>{{ data.event.date }}</span>
          </div>
          <div v-if="data.event.attendance_id" class="meta-item">
             <span class="material-symbols-outlined">fingerprint</span>
             <span>ID: {{ data.event.attendance_id }}</span>
          </div>
        </div>

        <div v-if="hasCalcDetails" class="calc-panel">
          <div v-if="data.calc?.shift_label" class="calc-line">
            <span class="material-symbols-outlined">badge</span>
            <span>{{ data.calc.shift_label }}</span>
          </div>
          <div v-if="data.calc?.shift_time_label" class="calc-line">
            <span class="material-symbols-outlined">schedule</span>
            <span>{{ data.calc.shift_time_label }}</span>
          </div>
          <div class="calc-chips">
            <span v-if="Number(data.calc?.late_minutes || 0) > 0" class="calc-chip chip-warning">
              Đi trễ {{ data.calc.late_minutes }} phút
            </span>
            <span v-if="Number(data.calc?.early_checkout_minutes || 0) > 0" class="calc-chip chip-warning">
              Về sớm {{ data.calc.early_checkout_minutes }} phút
            </span>
            <span v-if="Number(data.calc?.retry_after_seconds || 0) > 0" class="calc-chip chip-neutral">
              Quét lại sau {{ formatRetryAfter(data.calc.retry_after_seconds) }}
            </span>
            <span v-if="data.calc?.geofence_status" class="calc-chip chip-neutral">
              GPS {{ data.calc.geofence_status }}
            </span>
          </div>
        </div>

        <div class="auto-close-hint">Tự động đóng sau {{ countdown }}s...</div>
      </div>
    </div>
  </Transition>
</template>

<script setup>
import { computed, ref, onMounted, onUnmounted, watch } from 'vue';

const props = defineProps({
  visible: Boolean,
  data: {
    type: Object,
    default: () => ({
      success: true,
      title: 'Thành công',
      message: 'Đã ghi nhận chấm công',
      employee: null,
      event: null
    })
  },
  duration: {
    type: Number,
    default: 5000
  }
});

const emit = defineEmits(['close']);
const countdown = ref(props.duration / 1000);
let timer = null;
let countdownTimer = null;

const getInitials = (name) => {
  if (!name) return '??';
  return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(-2);
};

const hasCalcDetails = computed(() => {
  const calc = props.data?.calc || {};
  return Boolean(
    calc.shift_label
    || calc.shift_time_label
    || Number(calc.late_minutes || 0) > 0
    || Number(calc.early_checkout_minutes || 0) > 0
    || Number(calc.retry_after_seconds || 0) > 0
    || calc.geofence_status
  );
});

const formatRetryAfter = (seconds) => {
  const total = Math.max(0, Number(seconds || 0));
  const minutes = Math.floor(total / 60);
  const remain = total % 60;
  if (minutes > 0) {
    return `${minutes}p ${String(remain).padStart(2, '0')}s`;
  }
  return `${remain}s`;
};

const clearTimers = () => {
  if (timer) clearTimeout(timer);
  if (countdownTimer) clearInterval(countdownTimer);
};

const startTimers = () => {
  clearTimers();
  countdown.value = props.duration / 1000;
  timer = setTimeout(() => {
    emit('close');
  }, props.duration);

  countdownTimer = setInterval(() => {
    if (countdown.value > 0) countdown.value--;
  }, 1000);
};

watch(() => props.visible, (newVal) => {
  if (newVal) {
    startTimers();
  } else {
    clearTimers();
  }
});

onMounted(() => {
  if (props.visible) {
    startTimers();
  }
});

onUnmounted(() => {
  clearTimers();
});
</script>

<style scoped>
.result-overlay {
  position: fixed;
  inset: 0;
  z-index: 9999;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  padding: 20px;
  backdrop-filter: blur(12px);
}

.bg-success {
  background: rgba(16, 185, 129, 0.95);
}

.bg-error {
  background: rgba(239, 68, 68, 0.95);
}

.result-content {
  width: 100%;
  max-width: 400px;
  text-align: center;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 16px;
}

.icon-wrapper .icon-main {
  font-size: 80px;
  filter: drop-shadow(0 4px 10px rgba(0,0,0,0.1));
}

.status-title {
  font-size: 2rem;
  font-weight: 800;
  margin: 0;
}

.status-msg {
  font-size: 1.1rem;
  opacity: 0.9;
  margin: 0;
}

.employee-info {
  background: rgba(255, 255, 255, 0.15);
  padding: 20px;
  border-radius: 24px;
  width: 100%;
  display: flex;
  align-items: center;
  gap: 16px;
  backdrop-filter: blur(10px);
  border: 1px solid rgba(255, 255, 255, 0.2);
}

.avatar-ring {
  width: 64px;
  height: 64px;
  border-radius: 50%;
  border: 3px solid white;
  overflow: hidden;
  background: rgba(255,255,255,0.2);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.emp-avatar {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.emp-initials {
  font-size: 1.4rem;
  font-weight: 700;
}

.emp-details {
  text-align: left;
}

.emp-name {
  font-size: 1.2rem;
  font-weight: 700;
}

.emp-code, .emp-id {
  font-size: 0.9rem;
  opacity: 0.8;
}

.emp-dept {
  font-size: 0.85rem;
  margin-top: 4px;
  font-weight: 600;
  background: rgba(0,0,0,0.1);
  padding: 2px 8px;
  border-radius: 6px;
  display: inline-block;
}

.event-meta {
  display: flex;
  gap: 20px;
  flex-wrap: wrap;
  justify-content: center;
}

.calc-panel {
  width: 100%;
  max-width: 420px;
  background: rgba(255, 255, 255, 0.14);
  border: 1px solid rgba(255, 255, 255, 0.22);
  border-radius: 20px;
  padding: 14px 16px;
  backdrop-filter: blur(10px);
}

.calc-line {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  font-size: 0.95rem;
  font-weight: 600;
  opacity: 0.95;
}

.calc-line + .calc-line {
  margin-top: 8px;
}

.calc-chips {
  display: flex;
  flex-wrap: wrap;
  justify-content: center;
  gap: 10px;
  margin-top: 12px;
}

.calc-chip {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-height: 32px;
  padding: 6px 12px;
  border-radius: 999px;
  font-size: 0.88rem;
  font-weight: 700;
  letter-spacing: 0.01em;
}

.chip-warning {
  background: rgba(251, 191, 36, 0.18);
  border: 1px solid rgba(251, 191, 36, 0.45);
}

.chip-neutral {
  background: rgba(255, 255, 255, 0.12);
  border: 1px solid rgba(255, 255, 255, 0.2);
}

.meta-item {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 0.95rem;
  font-weight: 600;
}

.auto-close-hint {
  font-size: 0.85rem;
  opacity: 0.7;
  margin-top: 10px;
}

/* Animations */
.anim-pop { animation: pop 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275); }
.anim-shake { animation: shake 0.5s ease-in-out; }
.anim-up { animation: slideUp 0.6s cubic-bezier(0.23, 1, 0.32, 1); }

@keyframes pop {
  0% { transform: scale(0.5); opacity: 0; }
  100% { transform: scale(1); opacity: 1; }
}

@keyframes shake {
  0%, 100% { transform: translateX(0); }
  25% { transform: translateX(-10px); }
  75% { transform: translateX(10px); }
}

@keyframes slideUp {
  0% { transform: translateY(30px); opacity: 0; }
  100% { transform: translateY(0); opacity: 1; }
}

.fade-enter-active, .fade-leave-active { transition: opacity 0.5s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
