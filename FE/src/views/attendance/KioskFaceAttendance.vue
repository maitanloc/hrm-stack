<template>
  <div class="kiosk-container">
    <!-- Full-screen Camera Background -->
    <div class="camera-stage">
      <video ref="videoRef" autoplay playsinline muted class="video-feed"></video>
      
      <!-- Visual Scanning Ring -->
      <ScannerRing 
        :is-scanning="isProcessing" 
        :is-success="lastResult?.success" 
        :is-error="lastResult && !lastResult.success"
      />

      <!-- Instruction Overlay -->
      <div v-if="!showResult" class="ui-guide">
        <div class="glass-box guide-top anim-down">
          <div class="time-display">{{ formatTime(now) }}</div>
          <div class="date-display">{{ formatDate(now) }}</div>
        </div>

        <div class="guide-center">
          <div v-if="gpsLocked" class="status-badge gps-ok">
            <span class="material-symbols-outlined">location_on</span>
            <span>Vị trí sẵn sàng</span>
          </div>
          <div v-else class="status-badge gps-warn">
            <span class="material-symbols-outlined">location_off</span>
            <span>Đang lấy GPS...</span>
          </div>
        </div>

        <div class="glass-box guide-bottom anim-up">
          <p class="instruction-text">{{ instruction }}</p>
          <div class="loading-bar-container" v-if="isProcessing">
             <div class="loading-bar"></div>
          </div>
        </div>
      </div>
    </div>

    <!-- Result Success/Error Overlay -->
    <KioskResultOverlay 
      :visible="showResult" 
      :data="resultData" 
      @close="closeResult"
    />

    <!-- Manual Fallback/Controls -->
    <div class="kiosk-footer" v-if="!showResult">
       <button class="footer-action" @click="triggerManualCapture" :disabled="isProcessing">
          <span class="material-symbols-outlined">photo_camera</span>
          <span>Chụp ảnh thủ công</span>
       </button>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { apiRequest } from '@/services/beApi.js';
import ScannerRing from './ScannerRing.vue';
import KioskResultOverlay from './KioskResultOverlay.vue';
import { captureVideoFrame, waitForVideoReady } from '@/utils/videoCapture.js';

const videoRef = ref(null);
const now = ref(new Date());
const isProcessing = ref(false);
const showResult = ref(false);
const lastResult = ref(null);
const gpsLocked = ref(false);
const currentCoords = ref(null);
const captureTimer = ref(null);
const clockTimer = ref(null);
const lastCaptureAt = ref(0);
const instruction = ref('Vui lòng đưa gương mặt vào khung hình');
const geoWatchId = ref(null);

const resultData = ref({
  success: true,
  title: '',
  message: '',
  employee: null,
  event: null,
  calc: null,
  risk: null
});

// Settings
const AUTO_CAPTURE_INTERVAL = 3000; // 3s
const formatTime = (d) => d.toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
const formatDate = (d) => d.toLocaleDateString('vi-VN', { weekday: 'long', day: '2-digit', month: '2-digit', year: 'numeric' });

const mapCaptureError = (err) => {
  const message = String(err?.message || '').trim();
  if (message === 'blank_frame') {
    return 'Khung hình chưa hợp lệ. Vui lòng nhìn rõ vào camera.';
  }
  if (message === 'camera_frame_timeout' || message === 'camera_frame_unavailable') {
    return 'Camera chưa sẵn sàng. Vui lòng thử lại.';
  }
  return 'Không thể chụp ảnh từ camera.';
};

const formatShiftTimeLabel = (shiftInfo) => {
  const start = String(shiftInfo?.start_time || '').slice(0, 5);
  const end = String(shiftInfo?.end_time || '').slice(0, 5);
  if (!start && !end) return '';
  return `Ca làm: ${start || '--:--'} - ${end || '--:--'}`;
};

const buildCalcPayload = (data = {}) => ({
  result_code: data.result_code || '',
  severity: data.severity || '',
  late_minutes: Number(data.late_minutes || 0),
  early_checkout_minutes: Number(data.early_checkout_minutes || 0),
  repeat_scan_blocked: Boolean(data.repeat_scan_blocked),
  retry_after_seconds: Number(data.retry_after_seconds || 0),
  geofence_status: data.geofence_status || '',
  shift_label: data.shift_info?.shift_name
    ? `${data.shift_info.shift_name}${data.shift_info.shift_code ? ` (${data.shift_info.shift_code})` : ''}`
    : '',
  shift_time_label: formatShiftTimeLabel(data.shift_info),
});

const buildSuccessTitle = (data = {}) => {
  if (data.result_code === 'CHECKIN_LATE') return 'ĐI TRỄ';
  if (data.event_effect === 'check_out') return 'HẸN GẶP LẠI!';
  return 'CHẤM CÔNG THÀNH CÔNG';
};

const buildBlockedTitle = (data = {}, status = 0) => {
  if (data.result_code === 'REPEAT_SCAN_BLOCKED') return 'BẠN VỪA QUÉT XONG';
  if (data.result_code === 'NOT_READY_FOR_CHECKOUT') return 'CHƯA THỂ CHECK-OUT';
  if (data.result_code === 'NO_SHIFT') return 'KHÔNG CÓ CA';
  if (data.result_code === 'ATTENDANCE_COMPLETED') return 'ĐÃ HOÀN TẤT';
  return status === 403 ? 'LƯU Ý' : 'THẤT BẠI';
};

const openResultOverlay = ({
  success,
  title,
  message,
  employee = null,
  event = null,
  calc = null,
  risk = null,
}) => {
  resultData.value = {
    success,
    title,
    message,
    employee,
    event,
    calc,
    risk,
  };
  showResult.value = true;
};

const startCamera = async () => {
  try {
    const stream = await navigator.mediaDevices.getUserMedia({ 
      video: { 
        facingMode: 'user',
        width: { ideal: 1280 },
        height: { ideal: 720 }
      } 
    });
    if (videoRef.value) {
      videoRef.value.srcObject = stream;
      await waitForVideoReady(videoRef.value, 5000);
    }
  } catch (err) {
    console.error('Camera Error:', err);
    instruction.value = 'Không thể truy cập camera. Vui lòng cấp quyền.';
  }
};

const stopCamera = () => {
  if (videoRef.value && videoRef.value.srcObject) {
    videoRef.value.srcObject.getTracks().forEach(track => track.stop());
  }
};

const startGPS = () => {
  if (!navigator.geolocation) return;
  geoWatchId.value = navigator.geolocation.watchPosition(
    (pos) => {
      currentCoords.value = {
        lat: pos.coords.latitude,
        lng: pos.coords.longitude,
        accuracy: pos.coords.accuracy
      };
      gpsLocked.value = true;
    },
    (err) => {
      console.warn('GPS Error:', err);
      gpsLocked.value = false;
    },
    { enableHighAccuracy: true }
  );
};

const captureAndIdentify = async () => {
  if (isProcessing.value || showResult.value || !gpsLocked.value) return;
  
  const nowTs = Date.now();
  if (nowTs - lastCaptureAt.value < AUTO_CAPTURE_INTERVAL) return;
  lastCaptureAt.value = nowTs;

  const video = videoRef.value;
  if (!video) return;

  let imageData = '';
  try {
    ({ dataUrl: imageData } = await captureVideoFrame(video, {
      mirrored: true,
      quality: 0.8,
    }));
  } catch (err) {
    instruction.value = mapCaptureError(err);
    lastResult.value = { success: false };
    setTimeout(() => { lastResult.value = null; }, 1500);
    return;
  }

  isProcessing.value = true;
  instruction.value = 'Đang nhận diện gương mặt...';

  try {
    const response = await apiRequest('/kiosk/face-attendance', {
      method: 'POST',
      body: {
        image: imageData,
        lat: currentCoords.value.lat,
        lng: currentCoords.value.lng,
        accuracy_m: currentCoords.value.accuracy,
        kiosk_secret: 'demo_secret_change_me',
        client_time: new Date().toISOString()
      }
    });

    console.log('Kiosk Response:', response);

    if (response.success === true) {
      handleSuccess(response.data);
      return;
    }
    handleBusinessResult(response);
  } catch (err) {
    console.error('Kiosk Error:', err);
    handleError(err);
  } finally {
    isProcessing.value = false;
  }
};

const handleSuccess = (data) => {
  console.log('Mapping success data:', data);
  lastResult.value = { success: true };
  openResultOverlay({
    success: true,
    title: buildSuccessTitle(data),
    message: data.message || 'Chấm công thành công.',
    employee: {
      employee_id: data.employee?.employee_id || null,
      full_name: data.employee?.full_name || 'Nhân viên',
      employee_code: data.employee?.employee_code || '',
      department_name: data.employee?.department_name || '',
      avatar_url: data.employee?.avatar_url || ''
    },
    event: {
      attendance_id: data.event?.attendance_id || null,
      time: data.event?.time || new Date().toLocaleTimeString('vi-VN'),
      date: data.event?.date || new Date().toLocaleDateString('vi-VN')
    },
    calc: buildCalcPayload(data),
    risk: data.risk || null,
  });
  instruction.value = data.message || 'Chấm công thành công!';
};

const handleBusinessResult = (response) => {
  const data = response?.data || {};
  lastResult.value = { success: false };
  openResultOverlay({
    success: false,
    title: buildBlockedTitle(data, Number(response?.status || 0)),
    message: data.message || response?.message || 'Không thể chấm công lúc này.',
    employee: data.employee || null,
    event: data.last_success_event
      ? {
          attendance_id: null,
          time: String(data.last_success_event.event_time || '').slice(11, 19),
          date: String(data.last_success_event.event_time || '').slice(0, 10),
        }
      : null,
    calc: buildCalcPayload(data),
    risk: null,
  });
};

const handleError = (err) => {
  const payload = err.payload || {};
  const status = err.status;
  const data = payload.data || {};

  if (status === 404) {
    lastResult.value = { success: false };
    openResultOverlay({
      success: false,
      title: 'LỖI CẤU HÌNH',
      message: 'API chấm công kiosk chưa được cấu hình đúng trên máy chủ.',
    });
    return;
  }

  if (status === 422 && data.result_code !== 'NO_SHIFT') {
    instruction.value = payload.message || 'Ảnh khuôn mặt chưa hợp lệ. Vui lòng thử lại.';
    lastResult.value = { success: false };
    setTimeout(() => { lastResult.value = null; }, 1500);
    return;
  }

  // Face not recognized is handled with simple text instruction
  if (status === 403 && payload.error === 'face_not_recognized') {
    instruction.value = payload.message || 'Không nhận diện được khuôn mặt. Vui lòng thử lại.';
    lastResult.value = { success: false };
    setTimeout(() => { lastResult.value = null; }, 1500);
    return;
  }

  if (status === 409 || status === 422 || status === 403) {
    handleBusinessResult(payload);
    return;
  }

  lastResult.value = { success: false };
  openResultOverlay({
    success: false,
    title: 'THẤT BẠI',
    message: payload.message || 'Có lỗi xảy ra khi chấm công.',
    employee: data.employee || null,
    calc: buildCalcPayload(data),
  });
};

const closeResult = () => {
  showResult.value = false;
  lastResult.value = null;
  instruction.value = 'Vui lòng đưa gương mặt vào khung hình';
  lastCaptureAt.value = Date.now() + 1000; // Small buffer before resuming
};

const triggerManualCapture = () => {
  lastCaptureAt.value = 0; // Force immediate capture
};

onMounted(() => {
  void startCamera();
  startGPS();
  
  // Capture loop
  captureTimer.value = setInterval(captureAndIdentify, 1000);
  
  // Clock update
  clockTimer.value = setInterval(() => { now.value = new Date(); }, 1000);
});

onUnmounted(() => {
  stopCamera();
  clearInterval(captureTimer.value);
  clearInterval(clockTimer.value);
  if (geoWatchId.value !== null && navigator.geolocation) {
    navigator.geolocation.clearWatch(geoWatchId.value);
  }
});
</script>

<style scoped>
.kiosk-container {
  position: fixed;
  inset: 0;
  background: black;
  color: white;
  font-family: 'Inter', system-ui, -apple-system, sans-serif;
  overflow: hidden;
}

.camera-stage {
  width: 100%;
  height: 100%;
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
}

.video-feed {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transform: scaleX(-1); /* Mirror mode */
}

.ui-guide {
  position: absolute;
  inset: 0;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  padding: 40px;
  pointer-events: none;
}

.glass-box {
  background: rgba(0, 0, 0, 0.4);
  backdrop-filter: blur(20px);
  padding: 24px 40px;
  border-radius: 30px;
  border: 1px solid rgba(255, 255, 255, 0.1);
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.5);
}

.guide-top {
  align-self: flex-start;
  text-align: left;
}

.time-display {
  font-size: 3.5rem;
  font-weight: 800;
  letter-spacing: -2px;
  line-height: 1;
}

.date-display {
  font-size: 1.2rem;
  opacity: 0.8;
  margin-top: 8px;
  text-transform: capitalize;
}

.guide-center {
  display: flex;
  justify-content: center;
}

.status-badge {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 10px 20px;
  border-radius: 99px;
  font-weight: 700;
  font-size: 0.9rem;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  backdrop-filter: blur(10px);
}

.gps-ok {
  background: rgba(16, 185, 129, 0.2);
  color: #10b981;
  border: 1px solid rgba(16, 185, 129, 0.3);
}

.gps-warn {
  background: rgba(245, 158, 11, 0.2);
  color: #f59e0b;
  border: 1px solid rgba(245, 158, 11, 0.3);
}

.guide-bottom {
  align-self: center;
  width: 100%;
  max-width: 500px;
  text-align: center;
}

.instruction-text {
  font-size: 1.4rem;
  font-weight: 700;
  margin: 0;
  letter-spacing: -0.5px;
}

.loading-bar-container {
  height: 4px;
  background: rgba(255, 255, 255, 0.1);
  border-radius: 2px;
  margin-top: 16px;
  overflow: hidden;
}

.loading-bar {
  height: 100%;
  background: #2563eb;
  width: 100%;
  animation: loadingAnim 1.5s infinite ease-in-out;
}

.kiosk-footer {
  position: absolute;
  bottom: 40px;
  right: 40px;
  z-index: 20;
}

.footer-action {
  background: rgba(255, 255, 255, 0.1);
  backdrop-filter: blur(10px);
  border: 1px solid rgba(255, 255, 255, 0.2);
  color: white;
  padding: 12px 24px;
  border-radius: 16px;
  display: flex;
  align-items: center;
  gap: 10px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}

.footer-action:hover {
  background: rgba(255, 255, 255, 0.2);
  transform: translateY(-2px);
}

.footer-action:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

@keyframes loadingAnim {
  0% { transform: translateX(-100%); }
  100% { transform: translateX(100%); }
}

.anim-down { animation: slideDown 0.8s cubic-bezier(0.23, 1, 0.32, 1); }
.anim-up { animation: slideUp 0.8s cubic-bezier(0.23, 1, 0.32, 1); }

@keyframes slideDown {
  0% { transform: translateY(-40px); opacity: 0; }
  100% { transform: translateY(0); opacity: 1; }
}

@keyframes slideUp {
  0% { transform: translateY(40px); opacity: 0; }
  100% { transform: translateY(0); opacity: 1; }
}

@media (max-width: 768px) {
  .ui-guide { padding: 20px; }
  .time-display { font-size: 2.5rem; }
  .instruction-text { font-size: 1.1rem; }
}
</style>
