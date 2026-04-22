<template>
  <div class="enrollment-container">
    <div class="header">
      <button class="back-btn" @click="$router.back()">
        <span class="material-symbols-outlined">arrow_back</span>
      </button>
      <div class="title-group">
        <h1>Đăng ký khuôn mặt</h1>
        <p v-if="employee">Nhân viên: {{ employee.full_name }} ({{ employee.employee_code }})</p>
      </div>
    </div>

    <div class="camera-section">
      <div class="video-wrapper">
        <video ref="videoRef" autoplay playsinline muted class="video-feed"></video>
        
        <!-- Face Alignment Guide -->
        <div class="face-guide">
          <div class="guide-oval"></div>
        </div>

        <!-- Status Overlay -->
        <div v-if="statusMessage" class="status-overlay" :class="statusType">
          {{ statusMessage }}
        </div>
      </div>

      <div class="controls">
        <button 
          class="capture-btn" 
          @click="captureImage" 
          :disabled="isProcessing"
        >
          <div class="inner-circle"></div>
          <span v-if="isProcessing" class="spinner"></span>
        </button>
        <p class="hint">Căn chỉnh khuôn mặt vào giữa khung và nhấn nút chụp</p>
      </div>
    </div>

    <!-- Success Feedback -->
    <Teleport to="body">
       <div v-if="showSuccess" class="success-pop">
          <div class="success-card">
             <span class="material-symbols-outlined check-icon">check_circle</span>
             <h2>Đăng ký thành công!</h2>
             <p>Khuôn mặt đã được cập nhật vào hệ thống.</p>
             <button @click="$router.back()" class="primary-btn">Quay lại danh sách</button>
          </div>
       </div>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { apiRequest } from '@/services/beApi.js';
import { captureVideoFrame, waitForVideoReady } from '@/utils/videoCapture.js';

const route = useRoute();
const router = useRouter();
const employeeId = route.params.id;

const videoRef = ref(null);
const employee = ref(null);
const isProcessing = ref(false);
const showSuccess = ref(false);
const statusMessage = ref('');
const statusType = ref('info'); // info, error

const mapEnrollmentError = (err) => {
  const rawMessage = String(err?.payload?.message || err?.message || '').trim();

  if (rawMessage === 'blank_frame') {
    return 'Khung hình vừa chụp không hợp lệ. Vui lòng chụp lại.';
  }
  if (rawMessage === 'camera_frame_timeout' || rawMessage === 'camera_frame_unavailable') {
    return 'Camera chưa sẵn sàng. Vui lòng thử lại sau vài giây.';
  }
  if (err?.status === 404) {
    return 'API đăng ký khuôn mặt chưa được cấu hình đúng.';
  }
  if (err?.status === 422) {
    return rawMessage || 'Dữ liệu khuôn mặt không hợp lệ.';
  }
  return rawMessage || 'Không thể đăng ký khuôn mặt. Thử lại.';
};

const fetchEmployee = async () => {
  try {
    const res = await apiRequest(`/employees/${employeeId}`, { method: 'GET' });
    if (res.status === 200) {
      employee.value = res.data;
    }
  } catch (err) {
    console.error('Failed to fetch employee', err);
  }
};

const startCamera = async () => {
  try {
    const stream = await navigator.mediaDevices.getUserMedia({ 
      video: { 
        width: { ideal: 1280 },
        height: { ideal: 720 },
        facingMode: 'user'
      } 
    });
    if (videoRef.value) {
      videoRef.value.srcObject = stream;
      await waitForVideoReady(videoRef.value, 5000);
    }
  } catch (err) {
    statusMessage.value = 'Không thể truy cập camera. Vui lòng cấp quyền.';
    statusType.value = 'error';
  }
};

const stopCamera = () => {
  if (videoRef.value && videoRef.value.srcObject) {
    videoRef.value.srcObject.getTracks().forEach(t => t.stop());
  }
};

const captureImage = async () => {
  if (isProcessing.value) return;

  const video = videoRef.value;
  if (!video) return;

  isProcessing.value = true;
  statusMessage.value = 'Đang xử lý khuôn mặt...';
  statusType.value = 'info';

  try {
    const { dataUrl } = await captureVideoFrame(video, {
      mirrored: true,
      quality: 0.9,
    });

    const res = await apiRequest(`/employees/${employeeId}/enroll-face`, {
      method: 'POST',
      body: { images: [dataUrl, dataUrl, dataUrl] }
    });

    if (res.status === 200) {
      showSuccess.value = true;
      statusMessage.value = '';
    }
  } catch (err) {
    statusMessage.value = mapEnrollmentError(err);
    statusType.value = 'error';
  } finally {
    isProcessing.value = false;
  }
};

onMounted(() => {
  fetchEmployee();
  startCamera();
});

onUnmounted(() => {
  stopCamera();
});
</script>

<style scoped>
.enrollment-container {
  min-height: 100vh;
  background: #f8fafc;
  display: flex;
  flex-direction: column;
  padding: 24px;
}

.header {
  display: flex;
  align-items: center;
  gap: 16px;
  margin-bottom: 32px;
}

.back-btn {
  width: 40px;
  height: 40px;
  border-radius: 12px;
  border: 1px solid #e2e8f0;
  background: white;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.2s;
}

.back-btn:hover {
  background: #f1f5f9;
}

.title-group h1 {
  font-size: 1.5rem;
  font-weight: 700;
  color: #1e293b;
  margin: 0;
}

.title-group p {
  font-size: 0.9rem;
  color: #64748b;
  margin: 4px 0 0;
}

.camera-section {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 32px;
}

.video-wrapper {
  position: relative;
  width: 100%;
  max-width: 640px;
  aspect-ratio: 4/3;
  background: #000;
  border-radius: 24px;
  overflow: hidden;
  box-shadow: 0 20px 50px rgba(0, 0, 0, 0.1);
}

.video-feed {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transform: scaleX(-1);
}

.face-guide {
  position: absolute;
  inset: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  pointer-events: none;
}

.guide-oval {
  width: 50%;
  height: 60%;
  border: 2px dashed rgba(255, 255, 255, 0.5);
  border-radius: 50% / 40%;
  box-shadow: 0 0 0 1000px rgba(0, 0, 0, 0.3);
}

.status-overlay {
  position: absolute;
  bottom: 24px;
  left: 50%;
  transform: translateX(-50%);
  padding: 12px 24px;
  border-radius: 12px;
  font-weight: 600;
  font-size: 0.9rem;
  white-space: nowrap;
  backdrop-filter: blur(8px);
}

.status-overlay.info { background: rgba(37, 99, 235, 0.9); color: white; }
.status-overlay.error { background: rgba(220, 38, 38, 0.9); color: white; }

.controls {
  text-align: center;
}

.capture-btn {
  width: 80px;
  height: 80px;
  border-radius: 50%;
  border: 4px solid #fff;
  background: #2563eb;
  padding: 6px;
  cursor: pointer;
  transition: all 0.2s;
  box-shadow: 0 10px 20px rgba(37, 99, 235, 0.3);
  position: relative;
}

.capture-btn:hover { transform: scale(1.05); }
.capture-btn:active { transform: scale(0.95); }
.capture-btn:disabled { opacity: 0.5; filter: grayscale(1); }

.inner-circle {
  width: 100%;
  height: 100%;
  border-radius: 50%;
  background: white;
  transition: all 0.2s;
}

.capture-btn:active .inner-circle { background: #f8fafc; }

.hint {
  margin-top: 16px;
  font-size: 0.9rem;
  color: #64748b;
}

.success-pop {
  position: fixed;
  inset: 0;
  background: rgba(15, 23, 42, 0.8);
  backdrop-filter: blur(8px);
  z-index: 1000;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 24px;
  animation: fadeIn 0.3s ease;
}

.success-card {
  background: white;
  padding: 48px;
  border-radius: 32px;
  text-align: center;
  max-width: 400px;
  width: 100%;
  box-shadow: 0 30px 60px rgba(0, 0, 0, 0.2);
  transform-origin: center;
  animation: PopIn 0.5s cubic-bezier(0.34, 1.56, 0.64, 1);
}

.check-icon {
  font-size: 64px;
  color: #10b981;
  margin-bottom: 24px;
}

.success-card h2 {
  font-size: 1.8rem;
  color: #1e293b;
  margin: 0 0 12px;
}

.success-card p {
  color: #64748b;
  margin-bottom: 32px;
}

.primary-btn {
  background: #2563eb;
  color: white;
  border: none;
  padding: 14px 28px;
  border-radius: 12px;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.2s;
}

.primary-btn:hover { background: #1d4ed8; }

@keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
@keyframes PopIn { from { transform: scale(0.8); opacity: 0; } to { transform: scale(1); opacity: 1; } }

.spinner {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  width: 40px;
  height: 40px;
  border: 4px solid rgba(255, 255, 255, 0.3);
  border-top-color: white;
  border-radius: 50%;
  animation: spin 1s infinite linear;
}

@keyframes spin { to { transform: translate(-50%, -50%) rotate(360deg); } }
</style>
