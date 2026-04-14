<template>
  <div class="dashboard">
    <!-- Header -->
    <div class="mb-8 flex flex-wrap justify-between items-end gap-6 bg-transparent text-left">
        <div>
            <h1 class="text-2xl font-semibold text-[var(--sys-text-primary)]">Chi tiết Chỉ số Chuyên cần</h1>
            <p class="text-[var(--sys-text-secondary)] text-sm font-medium mt-1">Cập nhật lúc: 08:30, 24/05/2024</p>
        </div>
        <div class="flex items-center gap-4">
            <!-- Thoi gian filters -->
            <!-- Date filter button using GD_DateFilter component -->
            <GD_DateFilter v-model="selectedDateRange" />
            <!-- Phong ban dropdown -->
            <div class="flex items-center gap-2 px-5 py-2.5 bg-[var(--sys-bg-surface)] border border-[var(--sys-border-subtle)] rounded-xl text-[var(--sys-text-primary)] text-xs font-semibold shadow-sm cursor-pointer hover:border-[var(--sys-brand-solid)] transition-all">
                 <span class="material-symbols-rounded text-lg text-[var(--sys-icon-default)]">filter_list</span>
                 <span>Tất cả phòng ban</span>
                 <span class="material-symbols-rounded text-lg text-[var(--sys-icon-default)]">expand_more</span>
            </div>
            <!-- Xuat bao cao -->
            <button class="px-6 py-2.5 bg-[var(--sys-brand-solid)] text-white rounded-xl text-xs font-semibold hover:bg-[var(--sys-brand-hover)] shadow-xl shadow-[var(--sys-brand-solid-lch-30)] transition-all flex items-center gap-2">
                 <span class="material-symbols-rounded text-lg">download</span>
                 Xuất báo cáo
            </button>
        </div>
    </div>

    <!-- ✅ 3 KPI Cards – dữ liệu từ chuyenCanCards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div
          v-for="(card, i) in chuyenCanCards"
          :key="card.id"
          class="bg-[var(--sys-bg-surface)] border border-[var(--sys-border-subtle)] rounded-2xl p-6 shadow-[0_2px_12px_-4px_rgba(0,0,0,0.04)] hover:shadow-xl hover:translate-y-[-4px] transition-all cursor-pointer group text-left animate-chart"
          :style="{ animationDelay: (i * 100) + 'ms' }"
        >
            <div class="flex justify-between items-center mb-6">
                <p class="text-[13px] font-semibold text-[var(--sys-text-secondary)]">{{ card.label }}</p>
                <div
                  class="w-10 h-10 rounded-xl flex items-center justify-center shadow-inner"
                  :style="`background:${card.iconBg}; color:${card.iconColor}`"
                >
                    <span class="material-symbols-rounded text-xl">{{ card.icon }}</span>
                </div>
            </div>

            <!-- Card 1: chỉ hiện giá trị đơn + progress bar -->
            <template v-if="card.id === 'ty-le'">
                <div class="flex items-baseline gap-3 mb-6">
                    <h3 class="text-[40px] font-bold text-[var(--sys-text-primary)] leading-none">{{ card.value }}</h3>
                    <span
                      class="flex items-center text-sm font-semibold gap-1"
                      :class="card.badgeGood ? 'text-[var(--sys-success-text)]' : 'text-[var(--sys-danger-text)]'"
                    >
                        <span class="material-symbols-rounded text-[18px]">
                            {{ card.badgeTrend === 'up' ? 'trending_up' : 'trending_down' }}
                        </span>
                        {{ card.badge }}
                    </span>
                </div>
                <div class="h-1.5 w-full bg-[var(--sys-bg-hover)] rounded-full overflow-hidden">
                    <div class="h-full bg-[var(--sys-brand-solid)] rounded-full shadow-lg transition-all duration-[1500ms] ease-out" :style="`width:${isChartLoaded ? card.progress : 0}%`"></div>
                </div>
            </template>

            <!-- Card 2 & 3: giá trị kèm đơn vị + ghi chú -->
            <template v-else>
                <div class="flex items-baseline gap-3 mb-3">
                    <h3 class="text-[32px] font-bold text-[var(--sys-text-primary)] leading-none">
                        {{ card.value }}
                        <span v-if="card.valueSub" class="text-[24px] text-[var(--sys-text-secondary)]/50 font-semibold"> {{ card.valueSub }}</span>
                    </h3>
                    <span
                      class="flex items-center text-sm font-semibold gap-1"
                      :class="card.badgeGood ? 'text-[var(--sys-success-text)]' : 'text-[var(--sys-danger-text)]'"
                    >
                        <span class="material-symbols-rounded text-[18px]">
                            {{ card.badgeTrend === 'up' ? 'trending_up' : 'trending_down' }}
                        </span>
                        {{ card.badge }}
                    </span>
                </div>
                <p class="text-[12px] font-semibold text-[var(--sys-text-secondary)]/60">{{ card.note }}</p>
            </template>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 md:gap-5 mb-8">
        <!-- Line Chart Xu Hướng -->
        <div class="bg-[var(--sys-bg-surface)] border border-[var(--sys-border-subtle)] rounded-2xl p-6 lg:p-7 shadow-[0_2px_12px_-4px_rgba(0,0,0,0.04)] flex flex-col justify-between text-left min-h-[400px]">
            <div class="flex justify-between items-start mb-6">
                <div>
                   <h4 class="text-[18px] font-bold text-[var(--sys-text-primary)]">Xu hướng chuyên cần</h4>
                   <p class="text-[13px] font-semibold text-[var(--sys-text-secondary)] mt-4">
                       <span class="text-[24px] font-bold text-[var(--sys-text-primary)] mr-2">{{ defaultAvg }}%</span> 
                       Trung bình 6 tháng qua
                   </p>
                </div>
                <!-- Theo thang dropdown -->
                <div class="flex items-center gap-2 px-4 py-2 bg-transparent text-[var(--sys-text-secondary)] text-xs font-semibold cursor-pointer hover:text-[var(--sys-text-primary)] transition-all">
                     <span>Theo tháng</span>
                     <span class="material-symbols-rounded text-lg">expand_more</span>
                </div>
            </div>
            
            <!-- SVG Line Chart Wrapper exactly matching Gold Standard Matrix -->
            <div class="flex-1 relative w-full mt-10 mb-8 z-10 px-2 lg:px-4">
              <!-- Background Grid Lines (Y-Axis) -->
              <div class="absolute inset-x-0 bottom-0 top-0 flex flex-col justify-between pointer-events-none">
                <div class="border-t border-[var(--sys-border-subtle)] border-dashed w-full h-0 relative"><span class="absolute -top-2.5 bg-[var(--sys-bg-surface)] pr-2 text-[10px] font-bold text-[var(--sys-text-secondary)]/60">100.0%</span></div>
                <div class="border-t border-[var(--sys-border-subtle)] border-dashed w-full h-0 relative"><span class="absolute -top-2.5 bg-[var(--sys-bg-surface)] pr-2 text-[10px] font-bold text-[var(--sys-text-secondary)]/60">98.5%</span></div>
                <div class="border-t border-[var(--sys-border-subtle)] border-dashed w-full h-0 relative"><span class="absolute -top-2.5 bg-[var(--sys-bg-surface)] pr-2 text-[10px] font-bold text-[var(--sys-text-secondary)]/60">97.0%</span></div>
              </div>
              
              <!-- The SVG Area Chart Wrapper with Scan Effect -->
              <div class="absolute left-8 right-3 lg:left-10 lg:right-4 top-0 bottom-0 z-10 pointer-events-none overflow-hidden"
                   :style="`clip-path: inset(0 ${isChartLoaded ? 0 : 100}% 0 0); transition: clip-path 2s cubic-bezier(0.4, 0, 0.2, 1);`"
              >
                 <svg class="w-full h-full overflow-visible" viewBox="0 0 100 100" preserveAspectRatio="none">
                    <defs>
                       <linearGradient id="chartBlueCC" x1="0" y1="0" x2="0" y2="1">
                          <stop offset="0%" stop-color="#3B82F6" stop-opacity="0.25" />
                          <stop offset="100%" stop-color="#3B82F6" stop-opacity="0" />
                       </linearGradient>
                    </defs>
                    <path :d="areaPathData" fill="url(#chartBlueCC)" />
                    <path :d="linePathData" fill="none" stroke="var(--sys-brand-solid)" stroke-width="4" stroke-linecap="round" vector-effect="non-scaling-stroke" />
                 </svg>
              </div>

              <!-- HTML Interactive Hover Zones & Dots -->
              <div class="absolute left-8 right-3 lg:left-10 lg:right-4 top-0 bottom-0 z-20 overflow-hidden"
                   :style="`clip-path: inset(0 ${isChartLoaded ? 0 : 100}% 0 0); transition: clip-path 2s cubic-bezier(0.4, 0, 0.2, 1);`"
              >
                 <div v-for="(p, idx) in points" :key="idx"
                      class="absolute top-0 bottom-0 w-px group cursor-pointer hover:z-[60]"
                      :style="`left: ${p.x}%`">
                    
                    <!-- Hit target & Phantom Column for mouse hover -->
                    <div class="absolute inset-y-0 left-1/2 -translate-x-1/2 w-[40px] md:w-[60px] bg-transparent group-hover:bg-[var(--sys-bg-hover)] rounded-lg transition-colors z-10"></div>
                    
                    <!-- Helper Vertical Line from bottom to the dot to emphasize column connection -->
                    <div class="absolute bottom-0 left-1/2 -translate-x-1/2 w-px bg-[var(--sys-brand-solid)] border-l border-dashed border-[var(--sys-brand-solid)] opacity-0 group-hover:opacity-70 transition-opacity z-20 pointer-events-none"
                         :style="`height: ${p.yPct}%`"></div>
                    
                    <!-- PERMANENTLY VISIBLE Data Dot -->
                    <div class="absolute left-1/2 w-3 h-3 md:w-4 md:h-4 rounded-full bg-[var(--sys-bg-surface)] border-[2.5px] border-[var(--sys-brand-solid)] shadow shadow-[var(--sys-brand-solid-lch-30)] z-30 transition-all duration-300 pointer-events-none group-hover:scale-[1.4] group-hover:border-[3px] -translate-x-1/2 translate-y-1/2"
                         :style="`bottom: ${p.yPct}%`"
                    ></div>

                    <!-- Clean, Informative Tooltip (Decoupled & perfectly anchored above the dot) -->
                    <div class="hidden group-hover:flex absolute left-1/2 -translate-x-1/2 whitespace-nowrap pointer-events-none z-50 animate-fade-in-up"
                         :style="`bottom: calc(${p.yPct}% + 22px)`">
                       <div class="bg-slate-800 text-white rounded-xl shadow-xl shadow-slate-800/20 p-2.5 flex flex-col items-center min-w-[110px]">
                          <span class="text-[10px] font-[800] text-slate-400 uppercase tracking-widest mb-1.5">{{ p.month }}</span>
                          <div class="flex items-center justify-between w-full gap-3 text-[12px] font-bold">
                             <span class="text-slate-300 flex items-center gap-1.5">Tỷ lệ:</span>
                             <span class="text-blue-400 flex items-center gap-1.5">{{ p.val }}%</span>
                          </div>
                          <div class="absolute -bottom-1.5 left-1/2 -translate-x-1/2 w-3 h-3 bg-slate-800 rotate-45"></div>
                       </div>
                    </div>

                    <!-- X-Axis Label -->
                    <span class="absolute -bottom-8 left-1/2 -translate-x-1/2 text-[11px] font-[800] text-[var(--sys-text-secondary)]/80 transition-colors group-hover:text-[var(--sys-brand-solid)] whitespace-nowrap">{{ p.month }}</span>
                 </div>
              </div>
            </div>
        </div>

        <!-- Phân Tích Phòng Ban -->
        <div class="bg-[var(--sys-bg-surface)] border border-[var(--sys-border-subtle)] rounded-2xl p-6 lg:p-8 shadow-[0_2px_12px_-4px_rgba(0,0,0,0.04)] text-left">
             <div class="flex justify-between items-center mb-8">
                 <h4 class="text-[18px] font-bold text-[var(--sys-text-primary)]">Phân tích theo Phòng ban</h4>
                 <span class="material-symbols-rounded text-[var(--sys-text-secondary)] cursor-pointer">more_horiz</span>
             </div>
             
             <div class="space-y-6">
                  <div class="group" v-for="(dept, i) in depts" :key="dept.name">
                       <div class="flex justify-between items-center mb-3">
                            <h5 class="text-xs font-semibold text-[var(--sys-text-primary)]">{{ dept.name }}</h5>
                            <span class="text-xs font-bold text-[var(--sys-brand-solid)]">{{ dept.val }}%</span>
                       </div>
                       <div class="h-2 w-full bg-[var(--sys-bg-hover)] rounded-full overflow-hidden">
                            <div 
                              class="h-full bg-[var(--sys-brand-solid)] rounded-full shadow-lg shadow-[var(--sys-brand-solid-lch-30)] transition-all duration-[1500ms] ease-out" 
                              :style="{ width: isChartLoaded ? dept.val + '%' : '0%', transitionDelay: (i * 150) + 'ms' }"
                            ></div>
                       </div>
                  </div>
             </div>
        </div>
    </div>

    <!-- Bottom Lists -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 md:gap-5 text-left">
        <!-- Top Xuat Sac -->
        <div class="bg-[var(--sys-bg-surface)] border border-[var(--sys-border-subtle)] rounded-2xl p-6 lg:p-8 shadow-[0_2px_12px_-4px_rgba(0,0,0,0.04)]">
             <div class="flex justify-between items-center mb-6">
                 <h4 class="text-[16px] font-bold text-[var(--sys-text-primary)] flex items-center gap-2">
                     <span class="material-symbols-rounded text-[var(--sys-success-text)] text-xl" style="font-variation-settings: 'FILL' 1;">verified</span> 
                     Top chuyên cần xuất sắc
                 </h4>
                 <button class="text-xs font-semibold text-[var(--sys-brand-solid)] hover:opacity-80 transition-opacity">Xem tất cả</button>
             </div>
             <div class="space-y-6 mt-8">
                  <div class="flex items-center gap-4 border-b border-[var(--sys-border-subtle)] pb-4 last:border-0 last:pb-0" v-for="user in topUsers" :key="user.name">
                       <div class="w-12 h-12 rounded-full overflow-hidden border border-[var(--sys-border-subtle)] bg-[var(--sys-bg-hover)]">
                            <img :src="user.avatar" class="w-full h-full object-cover"/>
                       </div>
                       <div class="flex-grow">
                            <h5 class="text-sm font-bold text-[var(--sys-text-primary)]">{{ user.name }}</h5>
                            <p class="text-xs font-semibold text-[var(--sys-text-secondary)] mt-1">{{ user.dept }}</p>
                       </div>
                       <div class="text-right">
                            <h5 class="text-sm font-bold text-[var(--sys-success-text)]">{{ user.val }}</h5>
                            <p class="text-[10px] font-bold text-[var(--sys-text-secondary)]/40 uppercase tracking-wide mt-1">{{ user.subval }}</p>
                       </div>
                  </div>
             </div>
        </div>
        
        <!-- Can Luu Y -->
        <div class="bg-[var(--sys-bg-surface)] border border-[var(--sys-border-subtle)] rounded-2xl p-6 lg:p-8 shadow-[0_2px_12px_-4px_rgba(0,0,0,0.04)]">
             <div class="flex justify-between items-center mb-6">
                 <h4 class="text-[16px] font-bold text-[var(--sys-text-primary)] flex items-center gap-2">
                     <span class="material-symbols-rounded text-[var(--sys-danger-text)] text-xl" style="font-variation-settings: 'FILL' 1;">warning</span>
                     Cần lưu ý (Vi phạm nhiều)
                 </h4>
                 <button class="text-xs font-semibold text-[var(--sys-brand-solid)] hover:opacity-80 transition-opacity">Xem tất cả</button>
             </div>
             <div class="space-y-6 mt-8">
                  <div class="flex items-center gap-4 border-b border-[var(--sys-border-subtle)] pb-4 last:border-0 last:pb-0" v-for="user in badUsers" :key="user.name">
                       <div class="w-12 h-12 rounded-full overflow-hidden border border-[var(--sys-border-subtle)] bg-[var(--sys-bg-hover)]">
                            <img :src="user.avatar" class="w-full h-full object-cover"/>
                       </div>
                       <div class="flex-grow">
                            <h5 class="text-sm font-bold text-[var(--sys-text-primary)]">{{ user.name }}</h5>
                            <p class="text-xs font-semibold text-[var(--sys-text-secondary)] mt-1">{{ user.dept }}</p>
                       </div>
                       <div class="text-right">
                            <h5 class="text-sm font-bold text-[var(--sys-danger-text)]">{{ user.val }}</h5>
                            <p class="text-[10px] font-bold text-[var(--sys-text-secondary)]/40 uppercase tracking-wide mt-1">{{ user.subval }}</p>
                       </div>
                  </div>
             </div>
        </div>
    </div>
  </div>
</template>

<script setup>
import { computed, ref, onMounted } from 'vue';
import GD_DateFilter from '@/components/GD_DateFilter.vue';
import { mockEmployees, mockDepartments, mockLeaveRequests } from '@/mock-data/index.js';

const selectedDateRange = ref('30_days');
const isChartLoaded = ref(false);

onMounted(() => {
  setTimeout(() => isChartLoaded.value = true, 150);
});

const chuyenCanCards = computed(() => {
  const reqs = mockLeaveRequests.filter(r => r.status === 'ĐÃ_DUYỆT');
  return [
    { id: 'ty-le', label: 'Tỷ lệ chuyên cần', icon: 'timelapse', iconBg: 'var(--sys-brand-solid-lch-90)', iconColor: 'var(--sys-brand-solid)', value: '98.5%', badgeGood: true, badgeTrend: 'up', badge: '1.2%', progress: 98.5 },
    { id: 'di-tre', label: 'Đi trễ / Về sớm', icon: 'schedule', iconBg: 'var(--sys-warning-light)', iconColor: 'var(--sys-warning)', value: '45', valueSub: 'lần', badgeGood: false, badgeTrend: 'up', badge: '5%', note: 'Vs trung bình tháng trước' },
    { id: 'nghi-phep', label: 'Đơn nghỉ phép/vắng mặt', icon: 'event_busy', iconBg: 'var(--sys-danger-light)', iconColor: 'var(--sys-danger)', value: reqs.length.toString(), valueSub: 'đơn', badgeGood: true, badgeTrend: 'down', badge: '10%', note: 'Đã duyệt toàn hệ thống' }
  ];
});

const depts = computed(() => {
  return mockDepartments.slice(0, 4).map((d, index) => {
    return { name: d.departmentName, val: (99.5 - (index * 0.8)).toFixed(1) };
  });
});

const topUsers = computed(() => {
  return mockEmployees.filter(e => e.status === 'ĐANG_LÀM_VIỆC').slice(0, 2).map((e, index) => {
    const d = mockDepartments.getById(e.departmentId);
    return { name: e.fullName, dept: d ? d.departmentName : '', avatar: e.avatarUrl || `https://i.pravatar.cc/150?u=${index}`, val: '100%', subval: 'Chuyên cần' };
  });
});

const badUsers = computed(() => {
  return mockEmployees.filter(e => e.status === 'ĐANG_LÀM_VIỆC').slice(2, 4).map((e, index) => {
    const d = mockDepartments.getById(e.departmentId);
    return { name: e.fullName, dept: d ? d.departmentName : '', avatar: e.avatarUrl || `https://i.pravatar.cc/150?u=${index+10}`, val: (8 - index).toString(), subval: 'Lần đi trễ' };
  });
});

const chuyenCanLineChart = ref([98.5, 98.2, 98.6, 99.0, 98.8, 98.5]);

const defaultAvg = chuyenCanLineChart.value.length ? (chuyenCanLineChart.value.reduce((a,b) => a+b, 0) / chuyenCanLineChart.value.length).toFixed(1) : '98.5';

const points = computed(() => {
  const minVal = 97.0;
  const maxVal = 100.0;
  const maxDiff = maxVal - minVal;
  const numPoints = chuyenCanLineChart.value.length;

  return chuyenCanLineChart.value.map((val, idx) => {
    // 0-100 coordinates
    const x = (idx / (numPoints - 1)) * 100;
    // Y percentage mapped to 100% chart bounds
    let yPct = ((val - minVal) / maxDiff) * 100;
    yPct = Math.min(Math.max(yPct, 0), 115);
    
    return { 
      month: 'T' + (idx + 1), 
      val: val, 
      x: x.toFixed(2), 
      y: (100 - yPct).toFixed(2), 
      yPct: yPct.toFixed(2) 
    };
  });
});

const linePathData = computed(() => {
  if (!points.value.length) return '';
  let d = `M ${points.value[0].x} ${points.value[0].y}`;
  for(let i=1; i<points.value.length; i++){
    const prev = points.value[i-1];
    const curr = points.value[i];
    const cpX = (parseFloat(prev.x) + parseFloat(curr.x)) / 2;
    d += ` C ${cpX} ${prev.y} ${cpX} ${curr.y} ${curr.x} ${curr.y}`;
  }
  return d;
});

const areaPathData = computed(() => {
  if(!linePathData.value) return '';
  return `${linePathData.value} L 100 100 L 0 100 Z`;
});
</script>

<style scoped>
.dashboard {
  max-width: 1600px;
  margin: 0 auto;
  padding: 24px;
}
</style>
