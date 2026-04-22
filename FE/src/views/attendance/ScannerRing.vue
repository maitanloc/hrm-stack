<template>
  <div class="scanner-ring-container">
    <div class="scanner-ring" :class="{ 'scanning': isScanning, 'success': isSuccess, 'error': isError }">
      <div class="scanner-line"></div>
      <div class="corner top-left"></div>
      <div class="corner top-right"></div>
      <div class="corner bottom-left"></div>
      <div class="corner bottom-right"></div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  isScanning: Boolean,
  isSuccess: Boolean,
  isError: Boolean
})
</script>

<style scoped>
.scanner-ring-container {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  width: 280px;
  height: 280px;
  z-index: 10;
  pointer-events: none;
}

.scanner-ring {
  width: 100%;
  height: 100%;
  border: 2px solid rgba(255, 255, 255, 0.3);
  border-radius: 40px;
  position: relative;
  transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}

.scanner-ring.scanning {
  border-color: rgba(37, 99, 235, 0.5);
  background: rgba(37, 99, 235, 0.04);
  box-shadow:
    0 0 0 1px rgba(96, 165, 250, 0.16),
    0 0 22px rgba(37, 99, 235, 0.16),
    inset 0 0 24px rgba(37, 99, 235, 0.06);
}

.scanner-ring.success {
  border-color: rgba(16, 185, 129, 0.5);
  background: rgba(16, 185, 129, 0.05);
  box-shadow:
    0 0 0 1px rgba(110, 231, 183, 0.18),
    0 0 30px rgba(16, 185, 129, 0.18),
    inset 0 0 24px rgba(16, 185, 129, 0.08);
}

.scanner-ring.error {
  border-color: rgba(239, 68, 68, 0.52);
  background: rgba(239, 68, 68, 0.05);
  box-shadow:
    0 0 0 1px rgba(252, 165, 165, 0.18),
    0 0 30px rgba(239, 68, 68, 0.18),
    inset 0 0 24px rgba(239, 68, 68, 0.08);
}

.scanner-line {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 2px;
  background: linear-gradient(to right, transparent, var(--sys-brand-solid, #2563eb), transparent);
  box-shadow: 0 0 15px rgba(37, 99, 235, 0.45);
  opacity: 0;
  transition: opacity 0.3s;
}

.scanning .scanner-line {
  opacity: 1;
  animation: scanMove 2s infinite ease-in-out;
}

@keyframes scanMove {
  0% { top: 10%; }
  50% { top: 90%; }
  100% { top: 10%; }
}

.corner {
  position: absolute;
  width: 30px;
  height: 30px;
  border-color: rgba(255, 255, 255, 0.8);
  border-style: solid;
  transition: border-color 0.3s;
}

.scanning .corner {
  border-color: rgba(96, 165, 250, 0.8);
}

.success .corner {
  border-color: rgba(110, 231, 183, 0.78);
}

.error .corner {
  border-color: rgba(252, 165, 165, 0.78);
}

.top-left {
  top: -2px;
  left: -2px;
  border-width: 4px 0 0 4px;
  border-top-left-radius: 40px;
}

.top-right {
  top: -2px;
  right: -2px;
  border-width: 4px 4px 0 0;
  border-top-right-radius: 40px;
}

.bottom-left {
  bottom: -2px;
  left: -2px;
  border-width: 0 0 4px 4px;
  border-bottom-left-radius: 40px;
}

.bottom-right {
  bottom: -2px;
  right: -2px;
  border-width: 0 4px 4px 0;
  border-bottom-right-radius: 40px;
}

@media (max-width: 480px) {
  .scanner-ring-container {
    width: 220px;
    height: 220px;
  }
}
</style>
