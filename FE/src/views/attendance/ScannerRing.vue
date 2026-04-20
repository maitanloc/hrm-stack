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
  border-color: var(--sys-brand-solid, #2563eb);
  box-shadow: 0 0 20px rgba(37, 99, 235, 0.2);
}

.scanner-ring.success {
  border-color: #10b981;
  box-shadow: 0 0 30px rgba(16, 185, 129, 0.4);
}

.scanner-ring.error {
  border-color: #ef4444;
  box-shadow: 0 0 30px rgba(239, 68, 68, 0.4);
}

.scanner-line {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 2px;
  background: linear-gradient(to right, transparent, var(--sys-brand-solid, #2563eb), transparent);
  box-shadow: 0 0 15px var(--sys-brand-solid, #2563eb);
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
  border-color: var(--sys-brand-solid, #2563eb);
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
