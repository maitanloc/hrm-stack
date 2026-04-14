<template>
  <div class="p-6 md:p-8 space-y-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
      <div>
        <h1 class="text-[24px] font-[800] text-slate-800 tracking-tight">Tổng Quan Quỹ Lương</h1>
        <p class="text-sm text-slate-500 mt-1 font-medium">Báo cáo chi tiết chi phí nhân sự tháng hiện tại dành cho Ban Giám Đốc</p>
      </div>
      <div class="flex items-center gap-3">
        <!-- Date filter button using GD_DateFilter component -->
        <GD_DateFilter v-model="selectedDateRange" />
        <button class="flex items-center gap-2 px-5 py-2.5 bg-[#3B5BDB] border border-transparent rounded-xl text-sm font-bold text-white hover:bg-blue-700 transition-colors shadow-lg shadow-blue-500/20">
          <span class="material-symbols-outlined text-[18px]">download</span>
          Xuất báo cáo
        </button>
      </div>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 md:gap-5">
      <div
        v-for="(card, index) in bangLuongKpiCards" :key="card.id"
        class="bg-white rounded-2xl p-6 border border-slate-100 shadow-[0_2px_12px_-4px_rgba(0,0,0,0.04)] hover:shadow-[0_4px_24px_-4px_rgba(0,0,0,0.08)] transition-all animate-chart"
        :style="{ animationDelay: (index * 100) + 'ms' }"
      >
        <div class="flex justify-between items-start">
          <h3 class="text-[12px] font-bold text-slate-500">{{ card.label }}</h3>
          <div class="w-10 h-10 rounded-xl flex items-center justify-center" :class="card.iconBg">
            <span class="material-symbols-outlined text-[20px]" :class="card.iconColor">{{ card.icon }}</span>
          </div>
        </div>
        <div class="mt-2">
          <p class="text-[24px] font-[900] text-slate-800 leading-tight">{{ card.value }}</p>
          <div class="flex items-center gap-1.5 mt-3">
            <span
              class="flex items-center text-[11px] font-bold"
              :class="card.badgeTrend === 'up' ? 'text-emerald-600' : 'text-rose-600'"
            >
              <span class="material-symbols-outlined text-[14px]">{{ card.badgeTrend === 'up' ? 'trending_up' : 'trending_down' }}</span>
              {{ card.badgeText }}
            </span>
            <span class="text-[11px] font-semibold text-slate-400">vs tháng trước</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Charts Row -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 md:gap-5">
      
      <!-- Left Chart Placeholder -->
      <div class="bg-white rounded-2xl border border-slate-100 shadow-[0_2px_12px_-4px_rgba(0,0,0,0.04)] flex flex-col pt-6 pb-5 px-6 relative h-[360px] animate-chart" style="animation-delay: 200ms">
        <div class="flex justify-between items-start mb-auto relative z-10">
          <h2 class="text-[16px] font-[800] text-slate-800">Quỹ lương theo Phòng ban</h2>
          <span class="text-[11px] font-bold text-slate-500 bg-slate-100 px-3 py-1.5 rounded-lg border border-slate-200">Đơn vị: % Tổng quỹ</span>
        </div>
        
        <div class="flex-1 relative w-full mt-10 mb-2 z-10">
          <!-- Background Grid Lines -->
          <div class="absolute inset-x-0 bottom-0 top-0 flex flex-col justify-between">
            <div class="border-t border-slate-100 border-dashed w-full h-0 relative"><span class="absolute -top-2.5 bg-white pr-2 text-[10px] font-bold text-slate-400">100%</span></div>
            <div class="border-t border-slate-100 border-dashed w-full h-0 relative"><span class="absolute -top-2.5 bg-white pr-2 text-[10px] font-bold text-slate-400">50%</span></div>
            <div class="border-t border-slate-200 w-full h-0 relative"><span class="absolute -top-2.5 bg-white pr-2 text-[10px] font-bold text-slate-400">0</span></div>
          </div>
          
          <!-- Column Bars Container -->
          <div class="absolute inset-0 ml-8 flex items-end justify-around pb-[1px] z-20" style="height: 100%;">
             <div v-for="(item, i) in bangLuongPhongBanChart" :key="i"
                  class="flex items-end h-full justify-center group relative cursor-pointer w-16 hover:z-[60]">
                
                <!-- Bar -->
                <div class="w-6 md:w-8 bg-gradient-to-t from-indigo-500 to-indigo-400 rounded-t-md relative shadow-sm shadow-indigo-500/30 group-hover:opacity-90 transition-opacity bar-pillar"
                     :style="`height: ${Math.min(item.percent, 115)}%; animation-delay: ${i * 100}ms`">
                     
                   <!-- Clean, Informative Tooltip -->
                   <div class="hidden group-hover:flex absolute bottom-full left-1/2 -translate-x-1/2 mb-2 whitespace-nowrap pointer-events-none">
                      <div class="bg-slate-800 text-white rounded-xl shadow-xl shadow-slate-800/20 p-2.5 flex flex-col items-center min-w-[110px]">
                         <span class="text-[10px] font-[800] text-slate-400 uppercase tracking-widest mb-1.5">{{ item.name }}</span>
                         <div class="flex items-center justify-between w-full gap-3 text-[12px] font-bold">
                            <span class="text-slate-300 flex items-center gap-1.5">Tỷ trọng:</span>
                            <span class="text-indigo-400 flex items-center gap-1.5">{{ item.percent }}%</span>
                         </div>
                         <div class="absolute -bottom-1.5 left-1/2 -translate-x-1/2 w-3 h-3 bg-slate-800 rotate-45"></div>
                      </div>
                   </div>
                </div>
             </div>
          </div>
        </div>

        <div class="flex justify-around items-end pt-5 ml-8 relative z-10 border-t border-slate-100 mt-2">
          <span v-for="(item, i) in bangLuongPhongBanChart" :key="i" class="text-[11px] font-[800] text-slate-500 w-16 text-center leading-tight">{{ item.name }}</span>
        </div>
      </div>

      <!-- Right Chart: Xu hướng 6 tháng (Area Chart Hỗ Trợ Tương Tác) -->
      <div class="bg-white rounded-2xl border border-slate-100 shadow-[0_2px_12px_-4px_rgba(0,0,0,0.04)] flex flex-col pt-6 pb-6 px-6 relative h-[360px] animate-chart" style="animation-delay: 300ms">
        <div class="flex justify-between items-start mb-auto relative z-10">
          <div>
            <h2 class="text-[16px] font-[800] text-slate-800">Xu hướng 6 tháng</h2>
            <p class="text-[11px] font-semibold text-slate-400 mt-1">Tăng trưởng quỹ lương</p>
          </div>
          <span class="text-[11px] font-bold text-slate-500 bg-slate-100 px-3 py-1.5 rounded-lg border border-slate-200">Đơn vị: Tỷ VNĐ</span>
        </div>
        
        <div class="flex-1 relative w-full mt-10 mb-8 z-10 px-2 lg:px-4">
          <!-- Background Grid Lines (Y-Axis) -->
          <div class="absolute inset-x-0 bottom-0 top-0 flex flex-col justify-between pointer-events-none">
            <div class="border-t border-slate-100 border-dashed w-full h-0 relative"><span class="absolute -top-2.5 bg-white pr-2 text-[10px] font-bold text-slate-400">{{ bangLuongLineChartMax.toFixed(1) }}</span></div>
            <div class="border-t border-slate-100 border-dashed w-full h-0 relative"><span class="absolute -top-2.5 bg-white pr-2 text-[10px] font-bold text-slate-400">{{ (bangLuongLineChartMax / 2).toFixed(1) }}</span></div>
            <div class="border-t border-slate-200 w-full h-0 relative"><span class="absolute -top-2.5 bg-white pr-2 text-[10px] font-bold text-slate-400">0.0</span></div>
          </div>
          
          <!-- The SVG Area Chart Wrapper with Clip-Path Scan -->
          <div class="absolute left-8 right-3 lg:left-10 lg:right-4 top-0 bottom-0 z-10 pointer-events-none overflow-hidden" 
               :style="`clip-path: inset(0 ${isChartLoaded ? 0 : 100}% 0 0); transition: clip-path 2s cubic-bezier(0.4, 0, 0.2, 1);`"
          >
             <svg class="w-full h-full overflow-visible" viewBox="0 0 100 100" preserveAspectRatio="none">
                <defs>
                   <linearGradient id="curveGradient" x1="0" y1="0" x2="0" y2="1">
                      <stop offset="0%" stop-color="#3B5BDB" stop-opacity="0.25" />
                      <stop offset="100%" stop-color="#3B5BDB" stop-opacity="0" />
                   </linearGradient>
                </defs>
                <path :d="bangLuongAreaPath" fill="url(#curveGradient)" />
                <path :d="bangLuongLinePath" fill="none" stroke="#3B5BDB" stroke-width="4" stroke-linecap="round" vector-effect="non-scaling-stroke" />
             </svg>
          </div>

          <!-- HTML Interactive Hover Zones & Dots -->
          <div class="absolute left-8 right-3 lg:left-10 lg:right-4 top-0 bottom-0 z-20 overflow-hidden"
               :style="`clip-path: inset(0 ${isChartLoaded ? 0 : 100}% 0 0); transition: clip-path 2s cubic-bezier(0.4, 0, 0.2, 1);`"
          >
             <!-- Each Month Column explicitly becomes a strictly defined Vertical Axis at {p.x}% -->
             <div v-for="(p, idx) in bangLuongLinePoints" :key="idx"
                  class="absolute top-0 bottom-0 w-px group cursor-pointer hover:z-[60]"
                  :style="`left: ${p.x}%`">
                
                <!-- Hit target & Phantom Column for mouse hover -->
                <div class="absolute inset-y-0 left-1/2 -translate-x-1/2 w-[40px] md:w-[60px] bg-transparent group-hover:bg-slate-100/40 rounded-lg transition-colors z-10"></div>
                
                <!-- Helper Vertical Line from bottom to the dot to emphasize column connection -->
                <div class="absolute bottom-0 left-1/2 -translate-x-1/2 w-px bg-blue-400 border-l border-dashed border-blue-400 opacity-0 group-hover:opacity-70 transition-opacity z-20 pointer-events-none"
                     :style="`height: ${p.yPct}%`"></div>
                
                <!-- PERMANENTLY VISIBLE Data Dot -->
                <div class="absolute left-1/2 w-3 h-3 md:w-4 md:h-4 rounded-full bg-white border-[2.5px] border-[#3B5BDB] shadow shadow-[#3B5BDB]/20 z-30 transition-all duration-300 pointer-events-none group-hover:scale-[1.4] group-hover:border-[3px] group-hover:shadow-blue-500/50 -translate-x-1/2 translate-y-1/2"
                     :style="`bottom: ${p.yPct}%`"
                ></div>

                <!-- Clean, Informative Tooltip (Decoupled & perfectly anchored above the dot) -->
                <div class="hidden group-hover:flex absolute left-1/2 -translate-x-1/2 whitespace-nowrap pointer-events-none z-50 animate-fade-in-up"
                     :style="`bottom: calc(${p.yPct}% + 22px)`">
                   <div class="bg-slate-800 text-white rounded-xl shadow-xl shadow-slate-800/20 p-2.5 flex flex-col items-center min-w-[110px]">
                      <span class="text-[10px] font-[800] text-slate-400 uppercase tracking-widest mb-1.5">{{ p.month }}</span>
                      <div class="flex items-center justify-between w-full gap-3 text-[12px] font-bold">
                         <span class="text-slate-300 flex items-center gap-1.5">Quỹ lương:</span>
                         <span class="text-blue-400 flex items-center gap-1.5">{{ p.val }} Tỷ</span>
                      </div>
                      <div class="absolute -bottom-1.5 left-1/2 -translate-x-1/2 w-3 h-3 bg-slate-800 rotate-45"></div>
                   </div>
                </div>

                <!-- X-Axis Label -->
                <span class="absolute -bottom-8 left-1/2 -translate-x-1/2 text-[11px] font-[800] text-slate-500 transition-colors group-hover:text-[#3B5BDB]">{{ p.month }}</span>
             </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Bottom Lists Row -->
    <div class="bg-white rounded-2xl border border-slate-100 shadow-[0_2px_12px_-4px_rgba(0,0,0,0.04)] p-7 flex flex-col animate-chart" style="animation-delay: 500ms">
      <div class="flex items-center justify-between mb-6">
        <h2 class="text-[16px] font-[800] text-slate-800">Chi tiết chi phí theo bộ phận</h2>
        
        <div class="relative w-64 hidden sm:block">
          <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-[18px]">search</span>
          <input type="text" placeholder="Tìm bộ phận..." class="w-full pl-9 pr-4 py-2 bg-slate-50 border border-slate-100 rounded-lg text-[13px] focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all font-medium text-slate-700">
        </div>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="border-b border-slate-200">
              <th class="py-4 px-2 text-[12px] font-[800] text-slate-700 whitespace-nowrap">Phòng ban</th>
              <th class="py-4 px-2 text-[12px] font-[800] text-slate-700 whitespace-nowrap text-center">Số nhân viên</th>
              <th class="py-4 px-2 text-[12px] font-[800] text-slate-700 whitespace-nowrap text-right">Tổng lương cơ bản</th>
              <th class="py-4 px-2 text-[12px] font-[800] text-slate-700 whitespace-nowrap text-right">Thưởng & Phụ cấp</th>
              <th class="py-4 px-2 text-[12px] font-[800] text-slate-700 whitespace-nowrap text-right">Tổng chi phí</th>
            </tr>
          </thead>
          <!-- ✅ Bảng chi phí phòng ban – từ bangLuongBoPhans -->
          <tbody class="divide-y divide-slate-100">
            <tr
              v-for="bp in bangLuongBoPhans" :key="bp.tenPhong"
              class="hover:bg-slate-50/50 transition-colors group"
            >
              <td class="py-4 px-2 text-[13px] font-[800] text-slate-800">{{ bp.tenPhong }}</td>
              <td class="py-4 px-2 text-[13px] font-medium text-slate-600 text-center">{{ bp.soNhanVien }}</td>
              <td class="py-4 px-2 text-[13px] font-medium text-slate-600 text-right">{{ bp.luongCoBan }}</td>
              <td class="py-4 px-2 text-[13px] font-medium text-slate-600 text-right">{{ bp.thuongPhuCap }}</td>
              <td class="py-4 px-2 text-[13px] font-[800] text-[#3B5BDB] text-right">{{ bp.tongChiPhi }}</td>
            </tr>
            <!-- Dòng tổng cộng – từ bangLuongTongCong -->
            <tr class="bg-slate-50/50">
              <td class="py-5 px-2 text-[14px] font-[900] text-slate-900">Tổng cộng</td>
              <td class="py-5 px-2 text-[14px] font-[900] text-slate-900 text-center">{{ bangLuongTongCong.soNhanVien }}</td>
              <td class="py-5 px-2 text-[14px] font-[900] text-slate-900 text-right">{{ bangLuongTongCong.luongCoBan }}</td>
              <td class="py-5 px-2 text-[14px] font-[900] text-slate-900 text-right">{{ bangLuongTongCong.thuongPhuCap }}</td>
              <td class="py-5 px-2 text-[14px] font-[900] text-[#3B5BDB] text-right">{{ bangLuongTongCong.tongChiPhi }}</td>
            </tr>
          </tbody>
        </table>
      </div>

      <div class="mt-4 flex items-center justify-between text-[12px] text-slate-500">
        <span>Hiển thị {{ bangLuongBoPhans.length }} trên {{ bangLuongBoPhans.length }} bộ phận</span>
        <button class="w-8 h-8 rounded border border-slate-200 flex items-center justify-center hover:bg-slate-50 transition-colors font-semibold">1</button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, ref, onMounted } from 'vue';
import GD_DateFilter from '@/components/GD_DateFilter.vue';
import { mockEmployees, mockSalaryDetails, mockDepartments } from '@/mock-data/index.js';

const selectedDateRange = ref('30_days');
const isChartLoaded = ref(false);
const Math = window.Math;

onMounted(() => {
  setTimeout(() => {
    isChartLoaded.value = true;
  }, 100);
});

const bangLuongKpiCards = computed(() => {
  const allSalaries = mockSalaryDetails;
  const totalSalary = allSalaries.reduce((sum, s) => sum + s.netSalary, 0) || 0;
  const totalAllowance = allSalaries.reduce((sum, s) => sum + s.allowance, 0) || 0;
  return [
    { id: 1, label: 'Tổng ngân sách', value: '5.2 Tỷ', icon: 'account_balance', iconBg: 'bg-blue-100', iconColor: 'text-blue-600', badgeTrend: 'up', badgeText: '2.5%' },
    { id: 2, label: 'Đã chi', value: (totalSalary / 1000000000).toFixed(2) + ' Tỷ', icon: 'payments', iconBg: 'bg-green-100', iconColor: 'text-green-600', badgeTrend: 'up', badgeText: '1.2%' },
    { id: 3, label: 'Tiết kiệm', value: '400 Tr', icon: 'savings', iconBg: 'bg-yellow-100', iconColor: 'text-yellow-600', badgeTrend: 'down', badgeText: '0.5%' },
    { id: 4, label: 'Thưởng & Phụ cấp', value: (totalAllowance / 1000000).toFixed(0) + ' Tr', icon: 'card_giftcard', iconBg: 'bg-purple-100', iconColor: 'text-purple-600', badgeTrend: 'up', badgeText: '15%' }
  ];
});

const bangLuongBoPhans = computed(() => {
  const depts = mockDepartments;
  const emps = mockEmployees;
  const sals = mockSalaryDetails;
  
  return depts.map(d => {
    const deptEmps = emps.filter(e => e.departmentId === d.departmentId);
    const deptSals = sals.filter(s => deptEmps.find(e => e.employeeId === s.employeeId));
    const luongCb = deptSals.reduce((sum, s) => sum + (s.basicSalary || 0), 0);
    const thuong = deptSals.reduce((sum, s) => sum + (s.allowance || 0), 0);
    return {
      tenPhong: d.departmentName,
      soNhanVien: deptEmps.length,
      luongCoBan: (luongCb / 1000000000).toFixed(2) + ' Tỷ',
      thuongPhuCap: (thuong / 1000000).toFixed(0) + ' Tr',
      tongChiPhi: ((luongCb + thuong) / 1000000000).toFixed(2) + ' Tỷ',
      rawTongChiPhi: luongCb + thuong
    };
  }).filter(d => d.soNhanVien > 0);
});

const bangLuongTongCong = computed(() => {
  const sumEmp = bangLuongBoPhans.value.reduce((s, b) => s + b.soNhanVien, 0);
  const sumCb = mockSalaryDetails.reduce((sum, s) => sum + (s.basicSalary || 0), 0);
  const sumThuong = mockSalaryDetails.reduce((sum, s) => sum + (s.allowance || 0), 0);
  return {
    soNhanVien: sumEmp,
    luongCoBan: (sumCb / 1000000000).toFixed(2) + ' Tỷ',
    thuongPhuCap: (sumThuong / 1000000000).toFixed(2) + ' Tỷ',
    tongChiPhi: ((sumCb + sumThuong) / 1000000000).toFixed(2) + ' Tỷ'
  };
});

const bangLuongPhongBanChart = computed(() => {
  const total = bangLuongBoPhans.value.reduce((s, b) => s + b.rawTongChiPhi, 0);
  if (total === 0) return [];
  return bangLuongBoPhans.value.map(b => ({
    name: b.tenPhong,
    percent: Math.round((b.rawTongChiPhi / total) * 100)
  })).sort((a,b) => b.percent - a.percent).slice(0, 5);
});

const bangLuongLineChart = ref([
  { month: 'T1', val: 4.2 },
  { month: 'T2', val: 4.5 },
  { month: 'T3', val: 1.1 },
  { month: 'T4', val: 4.8 },
  { month: 'T5', val: 5.0 },
  { month: 'T6', val: 4.7 }
]);
const bangLuongLineChartMax = 6.0;

const bangLuongLinePoints = computed(() => {
  const max = bangLuongLineChartMax;
  const numPoints = bangLuongLineChart.value.length;
  return bangLuongLineChart.value.map((p, i) => {
    return {
      month: p.month,
      val: p.val,
      x: (i / (numPoints - 1)) * 100,
      y: 100 - (p.val / max) * 100,
      yPct: Math.min((p.val / max) * 100, 115) // Hỗ trợ overshoot
    }
  });
});

const bangLuongLinePath = computed(() => {
  if (!bangLuongLinePoints.value.length) return '';
  let d = `M ${bangLuongLinePoints.value[0].x} ${bangLuongLinePoints.value[0].y}`;
  for(let i=1; i<bangLuongLinePoints.value.length; i++) {
    const prev = bangLuongLinePoints.value[i-1];
    const curr = bangLuongLinePoints.value[i];
    const cpX = (prev.x + curr.x) / 2;
    d += ` C ${cpX} ${prev.y} ${cpX} ${curr.y} ${curr.x} ${curr.y}`;
  }
  return d;
});

const bangLuongAreaPath = computed(() => {
  if (!bangLuongLinePath.value) return '';
  return `${bangLuongLinePath.value} L 100 100 L 0 100 Z`;
});


</script>

<style scoped>
.material-symbols-outlined {
  font-variation-settings: 'FILL' 1, 'wght' 500, 'GRAD' 0, 'opsz' 24;
}

@keyframes pillarGrow {
  from { height: 0; opacity: 0; }
  to   { opacity: 1; }
}

.bar-pillar {
  animation: pillarGrow 1s cubic-bezier(0.34, 1.56, 0.64, 1) backwards;
}
</style>