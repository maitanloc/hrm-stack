<template>
  <Teleport to="body">
    <Transition
      enter-active-class="transition ease-out duration-200"
      enter-from-class="opacity-0 scale-95"
      enter-to-class="opacity-100 scale-100"
      leave-active-class="transition ease-in duration-150"
      leave-from-class="opacity-100 scale-100"
      leave-to-class="opacity-0 scale-95"
    >
      <div v-if="state.show" class="fixed inset-0 z-[100000] flex items-center justify-center p-4">
        <!-- Backdrop -->
        <div 
          class="fixed inset-0 bg-black/40 backdrop-blur-[1px]" 
          @click="confirmAction(false)"
        ></div>
        
        <!-- Modal Content: Enterprise B2B Refined -->
        <div class="relative w-full max-w-sm rounded-xl shadow-2xl overflow-hidden border bg-[var(--sys-bg-surface-elevated)] border-[var(--sys-border-subtle)] transition-all duration-300 flex flex-col p-6 text-left">
          
          <div class="flex items-start gap-4 mb-6">
            <!-- Icon Area -->
            <div :class="[
              'w-12 h-12 rounded-lg flex items-center justify-center shrink-0 shadow-sm border',
              state.type === 'confirm' 
                ? 'bg-[var(--sys-warning-soft)] text-[var(--sys-warning-text)] border-[var(--sys-warning-border)]' 
                : 'bg-[var(--sys-brand-soft)] text-[var(--sys-brand-solid)] border-[var(--sys-brand-border)]'
            ]">
              <span class="material-symbols-outlined text-[28px] font-bold">
                {{ state.type === 'confirm' ? 'priority_high' : 'info' }}
              </span>
            </div>

            <!-- Text Area -->
            <div class="flex-1 flex flex-col pt-0.5">
              <h3 class="text-base font-extrabold text-[var(--sys-text-primary)] uppercase tracking-tight mb-1">
                {{ state.title }}
              </h3>
              <p class="text-[13px] font-medium leading-relaxed text-[var(--sys-text-secondary)] opacity-80">
                {{ state.message }}
              </p>
            </div>
          </div>

          <div class="flex gap-2 justify-end w-full">
            <!-- Secondary Button (Cancel) -->
            <button 
              v-if="state.type === 'confirm'"
              @click="confirmAction(false)" 
              class="h-10 px-6 rounded-md text-[11px] font-bold transition-all uppercase tracking-widest border border-[var(--sys-border-strong)] text-[var(--sys-text-secondary)] hover:bg-[var(--sys-bg-hover)] active:scale-95"
            >
              Hủy bỏ
            </button>
            
            <!-- Primary Button (Confirm/OK) -->
            <button 
              @click="confirmAction(true)" 
              class="h-10 px-8 rounded-md text-[11px] font-bold transition-all focus:outline-none uppercase tracking-widest active:scale-95 shadow-lg flex items-center justify-center"
              :class="[
                state.type === 'confirm' 
                  ? 'bg-[var(--sys-warning-solid)] text-white hover:brightness-110' 
                  : 'bg-[var(--sys-brand-solid)] text-white hover:brightness-110'
              ]"
            >
              {{ state.type === 'confirm' ? 'Xác nhận' : 'Đóng' }}
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { useConfirm } from '@/composables/useConfirm';

const { state, confirmAction } = useConfirm();
</script>

<style scoped>
/* Modal transition refined for SaaS feel */
</style>
