<template>
  <div class="suggestion-section mt-12 pt-12 border-t border-dashed border-[var(--sys-border-subtle)]">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-10">
      <div>
        <div class="flex items-center gap-3 mb-1.5">
          <div class="w-10 h-10 rounded-2xl bg-[var(--sys-brand-soft)] text-[var(--sys-brand-solid)] flex items-center justify-center shadow-lg shadow-[var(--sys-brand-solid)]/10">
            <span class="material-symbols-rounded text-[24px]">auto_awesome</span>
          </div>
          <h3 class="text-xl font-black tracking-tight text-[var(--sys-text-primary)] uppercase">Gợi ý Thông minh</h3>
        </div>
        <p class="text-[13px] text-[var(--sys-text-secondary)] font-medium">Hệ thống phân tích lịch sử và quy luật để đưa ra các đề xuất tối ưu.</p>
      </div>
      <div class="px-5 py-2 rounded-xl bg-[var(--sys-brand-soft)] border border-[var(--sys-brand-border)]">
        <span class="text-[10px] font-black text-[var(--sys-brand-solid)] uppercase tracking-[0.15em]">
          {{ suggestions.length }} đề xuất mới
        </span>
      </div>
    </div>

    <div v-if="suggestions.length > 0" class="grid grid-cols-1 xl:grid-cols-2 gap-6">
      <div 
        v-for="s in suggestions" 
        :key="s.id"
        class="group p-8 rounded-[40px] bg-[var(--sys-bg-surface)] border border-[var(--sys-border-subtle)] hover:border-[var(--sys-brand-border)] hover:shadow-2xl hover:shadow-[var(--sys-brand-solid)]/5 transition-all duration-500 flex flex-col justify-between relative overflow-hidden"
      >
        <div class="absolute -right-4 -top-4 opacity-[0.02] group-hover:scale-110 transition-transform duration-1000">
           <span class="material-symbols-rounded text-8xl">{{ s.icon || 'auto_fix' }}</span>
        </div>

        <div class="relative">
          <div class="flex items-start justify-between mb-6">
            <div class="p-4 rounded-2xl bg-[var(--sys-bg-page)] text-[var(--sys-brand-solid)] border border-[var(--sys-border-subtle)] group-hover:bg-[var(--sys-brand-soft)] transition-colors duration-500">
              <span class="material-symbols-rounded text-[28px]">{{ s.icon || 'auto_fix' }}</span>
            </div>
            <span class="text-[9px] font-black uppercase tracking-[0.2em] px-3 py-1.5 rounded-xl bg-[var(--sys-bg-page)] text-[var(--sys-text-disabled)] border border-[var(--sys-border-subtle)]">
              AI ENGINE: {{ s.type }}
            </span>
          </div>
          
          <h4 class="text-sm font-black text-[var(--sys-text-primary)] mb-2 uppercase tracking-tight">{{ s.title }}</h4>
          <p class="text-[13px] text-[var(--sys-text-secondary)] font-medium leading-relaxed mb-6 opacity-80">{{ s.description }}</p>
          
          <div class="flex items-start gap-3 mb-8 p-4 rounded-2xl bg-[var(--sys-bg-page)]/50 border border-[var(--sys-border-subtle)] group-hover:bg-white transition-colors duration-500 shadow-inner">
            <span class="material-symbols-rounded text-[18px] text-[var(--sys-brand-solid)] mt-0.5">lightbulb</span>
            <span class="text-[11px] font-bold text-[var(--sys-text-secondary)] italic leading-relaxed">{{ s.explanation }}</span>
          </div>
        </div>

        <div class="flex items-center gap-4 relative">
          <button 
            @click="store.openModal('suggestionPreview', s)"
            class="flex-1 py-3.5 rounded-2xl bg-[var(--sys-bg-page)] border border-[var(--sys-border-subtle)] text-[var(--sys-text-primary)] text-[11px] font-black uppercase tracking-widest hover:bg-[var(--sys-bg-hover)] transition-all active:scale-95"
          >
            Xem kết quả
          </button>
          <button 
            @click="store.applySuggestion(s.id)"
            class="flex-[1.5] py-3.5 rounded-2xl bg-[var(--sys-brand-solid)] text-white text-[11px] font-black uppercase tracking-[0.15em] hover:brightness-110 shadow-xl shadow-[var(--sys-brand-solid)]/20 transition-all active:scale-95"
          >
            Chấp nhận đề xuất
          </button>
          <button 
            @click="store.skipSuggestion(s.id)"
            class="w-12 h-12 rounded-2xl flex items-center justify-center text-slate-300 bg-[var(--sys-bg-page)] border border-[var(--sys-border-subtle)] hover:bg-rose-50 hover:text-rose-500 hover:border-rose-100 transition-all active:scale-90"
            title="Bỏ qua"
          >
            <span class="material-symbols-rounded text-[20px]">close</span>
          </button>
        </div>
      </div>
    </div>

    <div v-else class="py-16 px-6 rounded-[48px] border-2 border-dashed border-[var(--sys-border-subtle)] flex flex-col items-center justify-center opacity-40 bg-[var(--sys-bg-page)]/30">
       <div class="w-16 h-16 rounded-[24px] bg-slate-100 flex items-center justify-center mb-6">
         <span class="material-symbols-rounded text-4xl text-slate-400">auto_awesome</span>
       </div>
       <div class="text-center space-y-1">
         <p class="text-xs font-black uppercase tracking-[0.2em] text-slate-500">Hệ thống đang phân tích...</p>
         <p class="text-[11px] font-medium text-slate-400">Chưa có đề xuất tối ưu mới cho giai đoạn này.</p>
       </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { useScheduleStore } from '@/stores/useScheduleStore';

const store = useScheduleStore();
const suggestions = computed(() => store.suggestions);
</script>
