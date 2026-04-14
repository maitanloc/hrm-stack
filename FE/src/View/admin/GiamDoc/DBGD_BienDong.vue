<template>
  <div class="p-6 md:p-8 space-y-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
      <div>
        <h1 class="text-[24px] font-[800] text-slate-800 tracking-tight">Thống Kê Biến Động</h1>
        <p class="text-sm text-slate-500 mt-1 font-medium">Báo cáo phân tích nghỉ việc và mức độ gắn kết nhân sự: <span class="text-blue-600 font-semibold cursor-pointer">Tháng 5, 2024</span></p>
      </div>
      <div class="flex items-center gap-3">
        <!-- Date filter button using GD_DateFilter component -->
        <GD_DateFilter v-model="selectedDateRange" />
        <button class="flex items-center gap-2 px-5 py-2.5 bg-[#3B5BDB] border border-transparent rounded-xl text-sm font-bold text-white hover:bg-blue-700 transition-colors shadow-lg shadow-blue-500/20">
          <span class="material-symbols-outlined text-[18px]">download</span>
          Xuất dữ liệu
        </button>
      </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 md:gap-5">
      <div
        v-for="(card, i) in bienDongKpiCards" :key="card.id"
        class="bg-white rounded-2xl p-6 border border-slate-100 shadow-[0_2px_12px_-4px_rgba(0,0,0,0.04)] relative flex flex-col justify-between hover:shadow-[0_4px_24px_-4px_rgba(0,0,0,0.08)] transition-all animate-chart"
        :style="`animation-delay: ${i*100}ms`"
      >
        <div class="flex justify-between items-start mb-4">
          <div class="w-12 h-12 rounded-xl flex items-center justify-center shadow-sm" :class="[card.iconBg, card.iconColor]">
            <span class="material-symbols-outlined text-[24px]">{{ card.icon }}</span>
          </div>
          <div v-if="card.badge" class="flex items-center gap-1 text-[11px] font-bold px-2 py-1 rounded-full border" :class="card.badge.cls">
            <span class="material-symbols-outlined text-[14px]">{{ card.badge.icon }}</span> {{ card.badge.text }}
          </div>
        </div>
        <div>
          <h3 class="text-[12px] font-bold text-slate-500 uppercase tracking-widest">{{ card.label }}</h3>
          <p class="text-[32px] font-[900] text-slate-800 mt-1">{{ card.value }} <span :class="card.suffixCls">{{ card.suffix }}</span></p>
          <p class="text-[11px] font-medium text-slate-400 mt-2">{{ card.note }}</p>
        </div>
      </div>
    </div>

    <!-- Charts Row -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 md:gap-5">
      <!-- Line Chart -->
      <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-100 shadow-[0_2px_12px_-4px_rgba(0,0,0,0.04)] p-6 lg:p-7 flex flex-col relative min-h-[400px] animate-chart" style="animation-delay: 200ms">
        <div class="flex justify-between items-center mb-6 relative z-10 w-full pr-1">
          <div>
             <h2 class="text-[16px] font-[800] text-slate-800 tracking-tight">Xu hướng Biến động (12 tháng)</h2>
             <p class="text-[12px] font-semibold text-slate-400 mt-1">Sự thay đổi tỷ lệ nghỉ việc theo thời gian</p>
          </div>
          <button class="px-3 md:px-4 py-1.5 md:py-2 border border-slate-200 rounded-lg text-[12px] font-bold text-slate-600 hover:bg-slate-50 transition-colors shadow-sm bg-white whitespace-nowrap">Tất cả bộ phận</button>
        </div>

        <!-- SVG Line Chart Wrapper exactly matching Gold Standard Matrix -->
        <div class="flex-1 relative w-full mt-10 mb-8 z-10 px-2 lg:px-4">
          <!-- Background Grid Lines (Y-Axis) -->
          <div class="absolute inset-x-0 bottom-0 top-0 flex flex-col justify-between pointer-events-none">
            <div class="border-t border-slate-100 border-dashed w-full h-0 relative"><span class="absolute -top-2.5 bg-white pr-2 text-[10px] font-bold text-slate-400">{{ bienDongLineChartMax.toFixed(1) }}%</span></div>
            <div class="border-t border-slate-100 border-dashed w-full h-0 relative"><span class="absolute -top-2.5 bg-white pr-2 text-[10px] font-bold text-slate-400">{{ (bienDongLineChartMax / 2).toFixed(1) }}%</span></div>
            <div class="border-t border-slate-200 w-full h-0 relative"><span class="absolute -top-2.5 bg-white pr-2 text-[10px] font-bold text-slate-400">0.0%</span></div>
          </div>
          
          <!-- The SVG Area Chart Wrapper with Scan Effect -->
          <div class="absolute left-8 right-3 lg:left-10 lg:right-4 top-0 bottom-0 z-10 pointer-events-none overflow-hidden"
               :style="`clip-path: inset(0 ${isChartLoaded ? 0 : 100}% 0 0); transition: clip-path 2s cubic-bezier(0.4, 0, 0.2, 1);`"
          >
             <svg class="w-full h-full overflow-visible" viewBox="0 0 100 100" preserveAspectRatio="none">
                <defs>
                   <linearGradient id="chartBlue2" x1="0" y1="0" x2="0" y2="1">
                      <stop offset="0%" stop-color="#3B82F6" stop-opacity="0.25" />
                      <stop offset="100%" stop-color="#3B82F6" stop-opacity="0" />
                   </linearGradient>
                </defs>
                <path :d="bienDongAreaPath" fill="url(#chartBlue2)" />
                <path :d="bienDongLinePath" fill="none" stroke="#3B82F6" stroke-width="4" stroke-linecap="round" vector-effect="non-scaling-stroke" />
             </svg>
          </div>

          <!-- HTML Interactive Hover Zones & Dots -->
          <div class="absolute left-8 right-3 lg:left-10 lg:right-4 top-0 bottom-0 z-20 overflow-hidden"
               :style="`clip-path: inset(0 ${isChartLoaded ? 0 : 100}% 0 0); transition: clip-path 2s cubic-bezier(0.4, 0, 0.2, 1);`"
          >
             <div v-for="(p, idx) in bienDongLinePoints" :key="idx"
                  class="absolute top-0 bottom-0 w-px group cursor-pointer hover:z-[60]"
                  :style="`left: ${p.x}%`">
                
                <!-- Hit target & Phantom Column for mouse hover -->
                <div class="absolute inset-y-0 left-1/2 -translate-x-1/2 w-[40px] md:w-[60px] bg-transparent group-hover:bg-slate-100/40 rounded-lg transition-colors z-10"></div>
                
                <!-- Helper Vertical Line from bottom to the dot to emphasize column connection -->
                <div class="absolute bottom-0 left-1/2 -translate-x-1/2 w-px bg-blue-400 border-l border-dashed border-blue-400 opacity-0 group-hover:opacity-70 transition-opacity z-20 pointer-events-none"
                     :style="`height: ${p.yPct}%`"></div>
                
                <!-- PERMANENTLY VISIBLE Data Dot -->
                <div class="absolute left-1/2 w-3 h-3 md:w-4 md:h-4 rounded-full bg-white border-[2.5px] border-[#3B82F6] shadow shadow-[#3B82F6]/20 z-30 transition-all duration-300 pointer-events-none group-hover:scale-[1.4] group-hover:border-[3px] group-hover:shadow-blue-500/50 -translate-x-1/2 translate-y-1/2"
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
                <span class="absolute -bottom-8 left-1/2 -translate-x-1/2 text-[11px] font-[800] text-slate-500 transition-colors group-hover:text-blue-600 whitespace-nowrap">{{ p.month }}</span>
             </div>
          </div>
        </div>
      </div>

      <!-- Donut Chart -->
      <div class="lg:col-span-1 bg-white rounded-2xl border border-slate-100 shadow-[0_2px_12px_-4px_rgba(0,0,0,0.04)] p-6 flex flex-col items-center animate-chart" style="animation-delay: 300ms">
        <h2 class="text-[16px] font-[800] text-slate-800 tracking-tight w-full mb-8">Biến động theo Thâm niên</h2>
        
        <div class="relative w-[200px] h-[200px] mt-2 mb-4 hover:scale-105 transition-transform duration-500 cursor-pointer">
          <!-- Pure SVG Donut mimicking the image -->
          <svg viewBox="0 0 100 100" class="w-full h-full transform -rotate-90">
             <circle
               v-for="(seg, idx) in bienDongDonutSegments" :key="idx"
               cx="50" cy="50" r="35" fill="none" :stroke="seg.color" stroke-width="25"
               stroke-dasharray="219.9" :stroke-dashoffset="seg.dashoffset" stroke-linecap="butt" :transform="seg.transform"
             ></circle>
          </svg>
          <!-- Inner circle cutout for cleaner ring -->
          <div class="absolute inset-[25px] bg-white rounded-full shadow-inner border border-slate-50"></div>
        </div>

        <div class="w-full mt-auto space-y-4 pt-4">
          <div
             v-for="(seg, idx) in bienDongDonutSegments" :key="idx"
             class="flex items-center justify-between text-[12px] font-bold group hover:bg-slate-50 px-2 py-1.5 -mx-2 rounded transition-colors cursor-pointer"
          >
             <div class="flex items-center gap-2">
               <span class="w-3 h-3 rounded-full" :style="{ backgroundColor: seg.color }"></span>
               <span class="text-slate-500 group-hover:text-slate-800 transition-colors">{{ seg.label }}</span>
             </div>
             <span class="text-slate-800 text-[13px] font-[900]">{{ seg.pct }}%</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Table: Tỷ lệ theo Bộ phận -->
    <div class="bg-white rounded-2xl border border-slate-100 shadow-[0_2px_12px_-4px_rgba(0,0,0,0.04)] overflow-hidden flex flex-col animate-chart" style="animation-delay: 400ms">
      <div class="px-7 py-5 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
         <h2 class="text-[16px] font-[800] text-slate-800 tracking-tight">Tỷ lệ nghỉ việc theo Bộ phận</h2>
         <span class="text-[11px] italic text-slate-400 font-semibold">* Highlight các bộ phận > 5%</span>
      </div>
      <div class="overflow-x-auto w-full">
         <table class="w-full text-left border-collapse">
           <thead>
             <tr class="border-b border-slate-100 text-[11px] uppercase font-bold text-slate-400 tracking-widest bg-white">
               <th class="py-4 px-7 whitespace-nowrap">Phòng ban</th>
               <th class="py-4 px-7 whitespace-nowrap">Nhân sự</th>
               <th class="py-4 px-7 whitespace-nowrap">Nghỉ việc</th>
               <th class="py-4 px-7 whitespace-nowrap">Tỷ lệ (%)</th>
             </tr>
           </thead>
           <tbody class="divide-y divide-slate-100">
             <tr v-for="(row, idx) in nghiViecBoPhan" :key="idx" class="hover:bg-slate-50/70 transition-colors">
               <td class="py-4 px-7 text-[13px] font-[800] text-slate-700">{{ row.tenPhong }}</td>
               <td class="py-4 px-7 text-[13px] font-semibold text-slate-500">{{ row.soNhanSu }}</td>
               <td class="py-4 px-7 text-[13px] font-semibold text-slate-500">{{ row.soNghiViec }}</td>
               <td class="py-4 px-7">
                 <span v-if="row.alert" class="px-2.5 py-1 bg-red-50 text-red-600 font-[900] text-[13px] rounded tracking-wide border border-red-100 shadow-sm">{{ row.tyLe }}%</span>
                 <span v-else class="text-[13px] font-[800] text-slate-800">{{ row.tyLe }}%</span>
               </td>
             </tr>
           </tbody>
         </table>
      </div>
    </div>

    <!-- Table: Danh sách nghỉ việc -->
    <div class="bg-white rounded-2xl border border-slate-100 shadow-[0_2px_12px_-4px_rgba(0,0,0,0.04)] overflow-hidden flex flex-col animate-chart" style="animation-delay: 500ms">
      <div class="px-7 py-5 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
         <h2 class="text-[16px] font-[800] text-slate-800 tracking-tight">Danh sách Nhân sự nghỉ việc gần đây</h2>
         <a href="#" class="text-[12px] font-[800] text-[#3B5BDB] hover:text-blue-800 transition-colors bg-white px-3 py-1.5 rounded-lg border border-slate-200 shadow-sm">Xem tất cả</a>
      </div>
      <div class="overflow-x-auto w-full">
         <table class="w-full text-left border-collapse">
           <thead>
             <tr class="border-b border-slate-100 text-[11px] uppercase font-bold text-slate-400 tracking-widest bg-white">
               <th class="py-4 px-7 whitespace-nowrap">Nhân viên</th>
               <th class="py-4 px-7 whitespace-nowrap">Chức vụ / Phòng ban</th>
               <th class="py-4 px-7 whitespace-nowrap">Ngày nghỉ</th>
               <th class="py-4 px-7 whitespace-nowrap">Lý do chính</th>
               <th class="py-4 px-7 whitespace-nowrap text-right">Hình thức</th>
             </tr>
           </thead>
           <tbody class="divide-y divide-slate-100">
             <tr v-for="nv in danhSachNghiViec" :key="nv.id" class="hover:bg-slate-50/70 transition-colors group cursor-pointer">
               <td class="py-4 px-7">
                 <div class="flex items-center justify-start gap-3">
                    <div class="w-9 h-9 rounded-full bg-slate-200 text-slate-600 flex items-center justify-center text-[12px] font-[800] border-2 border-slate-100 shadow-sm">{{ nv.initials }}</div>
                    <span class="text-[14px] font-[800] text-slate-800 group-hover:text-blue-600 transition-colors">{{ nv.name }}</span>
                 </div>
               </td>
               <td class="py-4 px-7">
                  <div class="flex flex-col">
                     <span class="text-[13px] font-[800] text-slate-700">{{ nv.chucVu }}</span>
                     <span class="text-[12px] font-medium text-slate-400">{{ nv.phongBan }}</span>
                  </div>
               </td>
               <td class="py-4 px-7 text-[13px] font-bold text-slate-500 italic">{{ nv.ngayNghi }}</td>
               <td class="py-4 px-7 text-[13px] font-semibold text-slate-600">{{ nv.lyDo }}</td>
               <td class="py-4 px-7 text-right">
                 <span class="px-3 py-1 font-[800] text-[10px] rounded-full uppercase tracking-wider border shadow-sm" :class="nv.hinhThucCls">{{ nv.hinhThuc }}</span>
               </td>
             </tr>
           </tbody>
         </table>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, ref, onMounted } from 'vue';
import GD_DateFilter from '@/components/GD_DateFilter.vue';
import { mockEmployees, mockDepartments, mockPositions } from '@/mock-data/index.js';

const selectedDateRange = ref('30_days');
const isChartLoaded = ref(false);
const Math = window.Math;

onMounted(() => {
  setTimeout(() => isChartLoaded.value = true, 200);
});

const bienDongKpiCards = computed(() => {
  const emps = mockEmployees;
  const thoiViec = emps.filter(e => e.status === 'ĐÃ_NGHỈ_VIỆC').length;
  const tyLeNghi = emps.length > 0 ? ((thoiViec / emps.length) * 100).toFixed(1) : 0;
  return [
    {
      id: 1, label: 'Tổng nghỉ việc', value: thoiViec.toString(), suffix: 'nhân sự', suffixCls: 'text-lg text-slate-500 font-bold',
      icon: 'group_remove', iconBg: 'bg-red-50', iconColor: 'text-red-500',
      badge: { icon: 'trending_up', text: '+5%', cls: 'bg-red-50 text-red-600 border-red-100' },
      note: 'So với cùng kỳ năm trước'
    },
    {
      id: 2, label: 'Tỷ lệ biến động', value: tyLeNghi.toString(), suffix: '%', suffixCls: 'text-lg text-slate-500 font-bold',
      icon: 'show_chart', iconBg: 'bg-orange-50', iconColor: 'text-orange-500',
      badge: { icon: 'warning', text: 'Cao', cls: 'bg-orange-50 text-orange-600 border-orange-100' },
      note: 'Vượt mục tiêu 0.6%'
    },
    {
      id: 3, label: 'Chi phí thay thế', value: '1.2', suffix: 'Tỷ', suffixCls: 'text-lg text-slate-500 font-bold',
      icon: 'currency_exchange', iconBg: 'bg-blue-50', iconColor: 'text-blue-500',
      badge: null,
      note: 'Ước tính phí tuyển dụng & đào tạo'
    },
    {
      id: 4, label: 'Tỷ lệ giữ chân', value: (100 - tyLeNghi).toFixed(1), suffix: '%', suffixCls: 'text-lg text-slate-500 font-bold',
      icon: 'loyalty', iconBg: 'bg-green-50', iconColor: 'text-green-500',
      badge: { icon: 'task_alt', text: 'Tốt', cls: 'bg-green-50 text-green-600 border-green-100' },
      note: 'Nhân sự thâm niên > 1 năm'
    }
  ];
});

const nghiViecBoPhan = computed(() => {
  const depts = mockDepartments;
  const emps = mockEmployees;
  return depts.map(d => {
    const deptEmps = emps.filter(e => e.departmentId === d.departmentId);
    const deptThoiViec = deptEmps.filter(e => e.status === 'ĐÃ_NGHỈ_VIỆC').length;
    const tyLe = deptEmps.length > 0 ? ((deptThoiViec / deptEmps.length) * 100).toFixed(1) : 0;
    return {
      tenPhong: d.departmentName,
      soNhanSu: deptEmps.length,
      soNghiViec: deptThoiViec,
      tyLe: tyLe,
      alert: tyLe > 5
    };
  }).filter(d => d.soNhanSu > 0).sort((a,b) => b.soNghiViec - a.soNghiViec);
});

const danhSachNghiViec = computed(() => {
  const emps = mockEmployees.filter(e => e.status === 'ĐÃ_NGHỈ_VIỆC');
  return emps.slice(0, 5).map(e => {
    const deptName = mockDepartments.getById(e.departmentId)?.departmentName || 'Không rõ';
    const posName = mockPositions.getById(e.positionId)?.positionName || 'Nhân viên';
    return {
      id: e.employeeId,
      initials: e.fullName ? e.fullName.split(' ').map(n=>n[0]).join('').substring(0,2).toUpperCase() : 'NV',
      name: e.fullName,
      chucVu: posName,
      phongBan: deptName,
      ngayNghi: new Date().toLocaleDateString('vi-VN'),
      lyDo: 'Lý do cá nhân',
      hinhThucCls: 'bg-orange-50 text-orange-600 border-orange-100',
      hinhThuc: 'Tự nguyện'
    };
  });
});

const bienDongLineChart = ref([
  { month: 'Tháng 1', val: 2.5 },
  { month: 'Tháng 2', val: 3.0 },
  { month: 'Tháng 3', val: 2.8 },
  { month: 'Tháng 4', val: 3.5 },
  { month: 'Tháng 5', val: 4.0 },
  { month: 'Tháng 6', val: 3.6 }
]);
const bienDongLineChartMax = 5.0;

const bienDongDonut = ref([
  { label: 'Dưới 1 năm', pct: 45, color: '#FB7185' },
  { label: '1 - 3 năm', pct: 35, color: '#FCD34D' },
  { label: 'Trên 3 năm', pct: 20, color: '#34D399' }
]);

// --- BIỂU ĐỒ ĐƯỜNG ĐỘNG (Line Chart) ---
const bienDongLinePoints = computed(() => {
  const max = bienDongLineChartMax;
  const numPoints = bienDongLineChart.value.length;
  return bienDongLineChart.value.map((item, idx) => ({ 
    month: item.month,
    val: item.val,
    x: (idx / (numPoints - 1)) * 100, 
    y: 100 - (item.val / max) * 100,
    yPct: Math.min((item.val / max) * 100, 115)
  }));
});

const bienDongLinePath = computed(() => {
  if (!bienDongLinePoints.value.length) return '';
  let d = `M ${bienDongLinePoints.value[0].x} ${bienDongLinePoints.value[0].y}`;
  for(let i=1; i<bienDongLinePoints.value.length; i++){
    const prev = bienDongLinePoints.value[i-1];
    const curr = bienDongLinePoints.value[i];
    const cpX = (prev.x + curr.x) / 2;
    d += ` C ${cpX} ${prev.y} ${cpX} ${curr.y} ${curr.x} ${curr.y}`;
  }
  return d;
});

const bienDongAreaPath = computed(() => {
  if(!bienDongLinePath.value) return '';
  return `${bienDongLinePath.value} L 100 100 L 0 100 Z`;
});

// --- BIỂU ĐỒ TRÒN ĐỘNG (Donut Chart) ---
const bienDongDonutSegments = computed(() => {
  let segments = [];
  let currentAngle = 0;
  const circumference = 219.9;
  
  bienDongDonut.value.forEach((item) => {
    const dashoffset = circumference - (item.pct / 100) * circumference;
    segments.push({
      ...item,
      dashoffset,
      transform: currentAngle > 0 ? `rotate(${currentAngle} 50 50)` : ''
    });
    currentAngle += (item.pct / 100) * 360;
  });
  return segments;
});

</script>

<style scoped>
.material-symbols-outlined {
  font-variation-settings: 'FILL' 1, 'wght' 500, 'GRAD' 0, 'opsz' 24;
}
</style>