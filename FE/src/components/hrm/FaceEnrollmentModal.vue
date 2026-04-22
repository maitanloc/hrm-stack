<template>
  <Teleport to="body">
    <Transition name="modal-fade">
      <div v-if="show" class="fixed inset-0 z-[20000] flex items-center justify-center p-4">
        <div class="fixed inset-0 w-screen h-screen bg-black/80 backdrop-blur-md" @click="handleClose"></div>
        
        <div class="relative w-full max-w-2xl bg-[var(--sys-bg-surface)] border border-[var(--sys-border-subtle)] rounded-2xl shadow-2xl overflow-hidden flex flex-col text-left motion-safe:animate-zoomIn">
          <!-- Header -->
          <div class="px-6 py-4 border-b border-[var(--sys-border-subtle)] flex items-center justify-between">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-lg bg-[var(--sys-brand-soft)] text-[var(--sys-brand-solid)] flex items-center justify-center border border-[var(--sys-brand-border)]">
                <span class="material-symbols-outlined">face_recognition</span>
              </div>
              <div class="bg-transparent text-left">
                <h3 class="text-base font-bold text-[var(--sys-text-primary)] m-0 uppercase tracking-tight">Xác thực khuôn mặt 3D</h3>
                <p class="text-[11px] text-[var(--sys-text-secondary)] font-medium mt-0.5">Nhân viên: <span class="text-[var(--sys-brand-solid)]">{{ employeeName }}</span></p>
              </div>
            </div>
            <button @click="handleClose" class="w-8 h-8 flex items-center justify-center rounded-md hover:bg-[var(--sys-bg-hover)] transition-all text-[var(--sys-text-secondary)]">
              <span class="material-symbols-outlined text-xl">close</span>
            </button>
          </div>

          <!-- Body -->
          <div class="p-6 space-y-6 bg-[var(--sys-bg-surface)] relative z-10">
            <!-- Progress Steps -->
            <div class="flex justify-between items-center px-8 relative">
              <div class="absolute top-1/2 left-8 right-8 h-0.5 bg-[var(--sys-border-strong)] -translate-y-1/2 z-0"></div>
              <div 
                class="absolute top-1/2 left-8 h-0.5 bg-[var(--sys-brand-solid)] -translate-y-1/2 z-0 transition-all duration-500"
                :style="{ width: `${(currentStep - 1) * 50}%` }"
              ></div>
              
              <div v-for="step in 3" :key="step" class="relative z-10 flex flex-col items-center gap-2">
                <div 
                  :class="[
                    'w-8 h-8 rounded-full border-2 flex items-center justify-center text-[12px] font-bold transition-all duration-300',
                    currentStep >= step ? 'bg-[var(--sys-brand-solid)] border-[var(--sys-brand-solid)] text-white' : 'bg-[var(--sys-bg-surface)] border-[var(--sys-border-strong)] text-[var(--sys-text-disabled)]'
                  ]"
                >
                  <span v-if="captures[step-1]" class="material-symbols-outlined text-[16px]">check</span>
                  <span v-else>{{ step }}</span>
                </div>
                <span class="text-[10px] font-bold uppercase tracking-widest text-[var(--sys-text-secondary)]">
                  {{ steps[step-1].label }}
                </span>
              </div>
            </div>

            <!-- Visualization Area -->
            <div class="relative aspect-video bg-black rounded-xl overflow-hidden border border-[var(--sys-border-strong)] flex items-center justify-center group">
              <video ref="videoRef" autoplay playsinline muted class="w-full h-full object-cover transform scale-x-[-1]"></video>
              
              <!-- Face Guide Overlay -->
              <div class="absolute inset-0 pointer-events-none flex flex-col items-center justify-center">
                 <div class="w-[45%] h-[65%] border-2 border-white/30 border-dashed rounded-[50%/40%] shadow-[0_0_0_1000px_rgba(0,0,0,0.4)]"></div>
                 <div class="mt-8 px-4 py-2 bg-black/60 backdrop-blur-md rounded-full border border-white/20">
                    <p class="text-white text-[12px] font-bold uppercase tracking-widest mb-0">{{ steps[currentStep-1].instruction }}</p>
                 </div>
              </div>

              <!-- Snapshot Overlay -->
              <transition name="fade">
                <div v-if="lastSnapshot" class="absolute inset-0 z-20 bg-black/40 flex items-center justify-center">
                  <img :src="lastSnapshot" class="h-full object-contain transform scale-x-[-1]" />
                </div>
              </transition>
            </div>

            <!-- Previews -->
            <div class="grid grid-cols-3 gap-4">
              <div v-for="(cap, index) in captures" :key="index" 
                class="aspect-square rounded-lg border border-[var(--sys-border-strong)] bg-[var(--sys-bg-page)] overflow-hidden relative group">
                <img v-if="cap" :src="cap" class="w-full h-full object-cover transform scale-x-[-1]" />
                <div v-else class="w-full h-full flex flex-col items-center justify-center opacity-40">
                  <span class="material-symbols-outlined text-2xl mb-1">no_photography</span>
                  <span class="text-[8px] font-bold uppercase tracking-tighter">{{ steps[index].short }}</span>
                </div>
                <div v-if="cap" class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 flex items-center justify-center transition-opacity">
                  <button @click="resetStep(index)" class="w-8 h-8 rounded-full bg-red-500 text-white flex items-center justify-center shadow-lg hover:scale-110 transition-all">
                    <span class="material-symbols-outlined text-[18px]">delete</span>
                  </button>
                </div>
              </div>
            </div>
          </div>

          <!-- Footer -->
          <div class="px-6 py-4 border-t border-[var(--sys-border-subtle)] bg-[var(--sys-bg-page)]/50 flex justify-between items-center">
            <p v-if="errorMessage" class="text-[11px] font-bold text-red-500 uppercase tracking-tight">{{ errorMessage }}</p>
            <div v-else class="flex-1"></div>
            
            <!-- Loading Overlay -->
            <transition name="fade">
              <div v-if="isProcessing" class="absolute inset-0 z-[100] bg-white/60 backdrop-blur-sm flex flex-col items-center justify-center">
                 <div class="w-12 h-12 border-4 border-[var(--sys-brand-solid)] border-t-transparent rounded-full animate-spin mb-4"></div>
                 <p class="text-[14px] font-bold text-[var(--sys-brand-solid)] uppercase tracking-widest animate-pulse">Đang phân tích khuôn mặt...</p>
                 <p class="text-[11px] text-[var(--sys-text-secondary)] mt-2">Vui lòng không đóng cửa sổ này</p>
              </div>
            </transition>

            <div class="flex gap-2">
              <button 
                @click="capture" 
                v-if="currentStep <= 3 && !allCaptured"
                class="h-10 px-6 bg-[var(--sys-brand-solid)] text-white rounded-lg font-bold text-[12px] hover:brightness-110 shadow-lg transition-all uppercase tracking-widest active:scale-95 flex items-center gap-2 group"
                :disabled="isProcessing"
              >
                <span class="material-symbols-outlined text-[20px] group-hover:rotate-12 transition-transform">photo_camera</span>
                Chụp {{ steps[currentStep-1].short }}
              </button>

              <button 
                @click="submitEnrollment" 
                v-if="allCaptured"
                class="h-10 px-8 bg-[var(--sys-success-text)] text-white rounded-lg font-bold text-[12px] hover:brightness-110 shadow-lg transition-all uppercase tracking-widest active:scale-95 flex items-center gap-2"
                :disabled="isProcessing"
                :class="{ 'opacity-50 pointer-events-none': isProcessing }"
              >
                <span class="material-symbols-outlined text-[20px]">{{ isProcessing ? 'sync' : 'verified' }}</span>
                {{ isProcessing ? 'Đang gửi...' : 'Hoàn tất đăng ký' }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { ref, onUnmounted, computed, watch } from 'vue';
import { apiRequest } from '@/services/beApi.js';
import { captureVideoFrame, waitForVideoReady } from '@/utils/videoCapture.js';

const props = defineProps({
  show: Boolean,
  employeeId: [Number, String],
  employeeName: String
});

const emit = defineEmits(['close', 'success']);

const videoRef = ref(null);
const currentStep = ref(1);
const isProcessing = ref(false);
const errorMessage = ref('');
const lastSnapshot = ref(null);
const captures = ref([null, null, null]);

const steps = [
  { label: 'Chính diện', short: 'Trực diện', instruction: 'Nhìn thẳng vào camera' },
  { label: 'Nghiêng trái', short: 'Phía trái', instruction: 'Quay đầu chậm sang phía trái' },
  { label: 'Nghiêng phải', short: 'Phía phải', instruction: 'Quay đầu chậm sang phía phải' }
];

const allCaptured = computed(() => captures.value.every(c => c !== null));

const mapEnrollmentError = (err) => {
  const rawMessage = String(err?.payload?.message || err?.message || '').trim();

  if (rawMessage === 'blank_frame') {
    return 'Khung hình vừa chụp khong hop le. Vui long doi camera on dinh va chup lai.';
  }
  if (rawMessage === 'camera_frame_timeout' || rawMessage === 'camera_frame_unavailable') {
    return 'Camera chua san sang. Vui long doi 1 chut roi chup lai.';
  }
  if (err?.status === 404) {
    return 'Loi cau hinh he thong: API dang ky khuon mat chua duoc khai bao dung.';
  }
  if (err?.status === 422) {
    return rawMessage || 'Du lieu khuon mat khong hop le. Vui long chup lai du 3 goc mat.';
  }
  if (!rawMessage || rawMessage.includes('<!DOCTYPE html>') || rawMessage.length > 200) {
    return 'Khong the dang ky khuon mat luc nay. Vui long thu lai sau.';
  }

  return rawMessage;
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
    errorMessage.value = 'Loi camera: khong the truy cap ong kinh.';
  }
};

const stopCamera = () => {
  if (videoRef.value && videoRef.value.srcObject) {
    videoRef.value.srcObject.getTracks().forEach(t => t.stop());
    videoRef.value.srcObject = null;
  }
};

const capture = () => {
  void (async () => {
    const video = videoRef.value;
    if (!video || isProcessing.value) return;

    errorMessage.value = '';

    try {
      const { dataUrl } = await captureVideoFrame(video, {
        targetWidth: 640,
        targetHeight: 480,
        mirrored: true,
        quality: 0.9,
      });

      lastSnapshot.value = dataUrl;
      window.setTimeout(() => {
        lastSnapshot.value = null;
        captures.value[currentStep.value - 1] = dataUrl;
        if (currentStep.value < 3) {
          currentStep.value += 1;
        }
      }, 400);
    } catch (err) {
      errorMessage.value = mapEnrollmentError(err);
    }
  })();
};

const resetStep = (index) => {
  captures.value[index] = null;
  currentStep.value = index + 1;
};

const submitEnrollment = async () => {
  console.log('SubmitEnrollment: Clicked', { isProcessing: isProcessing.value, employeeId: props.employeeId });
  if (isProcessing.value) {
    console.warn('SubmitEnrollment: Already processing, ignoring click');
    return;
  }
  
  if (!props.employeeId) {
    errorMessage.value = 'He thong thieu ma nhan vien de dinh danh.';
    console.error('SubmitEnrollment: Missing employeeId');
    return;
  }

  if (!allCaptured.value) {
    errorMessage.value = 'Can chup du 3 anh khuon mat hop le truoc khi gui dang ky.';
    return;
  }

  isProcessing.value = true;
  errorMessage.value = '';

  try {
    console.log('SubmitEnrollment: Calling API...', `/employees/${props.employeeId}/enroll-face`);
    const res = await apiRequest(`/employees/${props.employeeId}/enroll-face`, {
      method: 'POST',
      body: { images: captures.value }
    });

    // If we reach here, apiRequest did not throw, so it's a success
    console.log('Face Enrollment Success Payload:', res);
    emit('success');
    handleClose();
  } catch (err) {
    errorMessage.value = mapEnrollmentError(err);
  } finally {
    isProcessing.value = false;
  }
};

const handleClose = () => {
  stopCamera();
  emit('close');
};

const resetModal = () => {
  currentStep.value = 1;
  captures.value = [null, null, null];
  errorMessage.value = '';
  lastSnapshot.value = null;
};

watch(() => props.show, (newVal) => {
  if (newVal) {
    resetModal();
    // Delay slightly to ensure video element is rendered
    setTimeout(startCamera, 100);
  } else {
    stopCamera();
  }
});

onUnmounted(stopCamera);
</script>

<style scoped>
.modal-fade-enter-active, .modal-fade-leave-active { transition: opacity 0.3s ease; }
.modal-fade-enter-from, .modal-fade-leave-to { opacity: 0; }

@keyframes zoomIn {
  from { opacity: 0; transform: scale(0.95) translateY(10px); }
  to { opacity: 1; transform: scale(1) translateY(0); }
}
.animate-zoomIn { animation: zoomIn 0.4s cubic-bezier(0.16, 1, 0.3, 1) forwards; }

.fade-enter-active, .fade-leave-active { transition: opacity 0.2s; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
