<template>
  <div class="p-6 md:p-8 space-y-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
      <div>
        <h1 class="text-[24px] font-[800] text-slate-800 tracking-tight">Bảng Điều Khiển Quản Trị</h1>
        <p class="text-sm text-slate-500 mt-1 font-medium">Dữ liệu chiến lược cập nhật theo thời gian thực: Thứ Hai, 24 Tháng 5, 2024</p>
      </div>
      <div class="flex items-center gap-3">
        <!-- Date filter button using GD_DateFilter component -->
        <GD_DateFilter v-model="selectedDateRange" />
        <button class="flex items-center gap-2 px-5 py-2.5 bg-[#3B5BDB] border border-transparent rounded-xl text-sm font-bold text-white hover:bg-blue-700 transition-colors shadow-lg shadow-blue-500/20">
          <span class="material-symbols-outlined text-[18px]">download</span>
          Xuất báo cáo CEO
        </button>
      </div>
    </div>

    <!-- ✅ 4 KPI Cards – từ nhanSuKpiCards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 md:gap-5">
      <div
        v-for="(card, i) in nhanSuKpiCards" :key="card.id"
        class="bg-white rounded-2xl p-6 border border-slate-100 shadow-[0_2px_12px_-4px_rgba(0,0,0,0.04)] hover:shadow-[0_4px_24px_-4px_rgba(0,0,0,0.08)] transition-all animate-chart"
        :style="{ animationDelay: (i * 100) + 'ms' }"
      >
        <div class="flex justify-between items-start">
          <div class="w-12 h-12 rounded-xl flex items-center justify-center" :class="card.iconBg">
            <span class="material-symbols-outlined text-[24px]" :class="card.iconColor">{{ card.icon }}</span>
          </div>
          <span class="text-[11px] font-bold px-2.5 py-1 rounded-full" :class="card.badgeClass">{{ card.badge }}</span>
        </div>
        <div class="mt-5">
          <h3 class="text-[12px] font-bold text-slate-500 uppercase tracking-widest">{{ card.label }}</h3>
          <p class="text-[32px] font-[900] text-slate-800 leading-tight mt-1">{{ card.value }}</p>
          <p class="text-[11px] font-semibold text-slate-400 mt-2">{{ card.note }}</p>
        </div>
      </div>
    </div>

    <!-- Charts Row -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-5">
      
      <!-- Chart: Cơ cấu nhân sự theo cấp bậc -->
      <div class="bg-white rounded-2xl border border-slate-100 shadow-[0_2px_12px_-4px_rgba(0,0,0,0.04)] flex flex-col pt-6 pb-5 px-6 relative h-[360px] animate-chart" style="animation-delay: 200ms">
        <div class="flex justify-between items-start mb-auto relative z-10">
          <div>
            <h2 class="text-[16px] font-[800] text-slate-800">Cơ cấu nhân sự theo cấp bậc</h2>
            <p class="text-[12px] font-semibold text-slate-400 mt-1">Phân bổ 1,250 nhân sự toàn công ty</p>
          </div>
          <span class="text-[11px] font-bold text-blue-600 bg-blue-50 px-3 py-1.5 rounded-lg border border-blue-100 hidden sm:block">Đơn vị: Người</span>
        </div>
        
        <div class="flex-1 relative w-full mt-10 mb-2 z-10">
          <!-- Background Grid Lines -->
          <div class="absolute inset-x-0 bottom-0 top-0 flex flex-col justify-between">
            <div class="border-t border-slate-100 border-dashed w-full h-0 relative"><span class="absolute -top-2.5 bg-white pr-2 text-[10px] font-bold text-slate-400">500</span></div>
            <div class="border-t border-slate-100 border-dashed w-full h-0 relative"><span class="absolute -top-2.5 bg-white pr-2 text-[10px] font-bold text-slate-400">250</span></div>
            <div class="border-t border-slate-200 w-full h-0 relative"><span class="absolute -top-2.5 bg-white pr-2 text-[10px] font-bold text-slate-400">0</span></div>
          </div>
          
          <!-- Column Bars Container -->
          <div class="absolute inset-0 ml-8 flex items-end justify-around pb-[1px] z-20" style="height: 100%;">
             <div v-for="(item, index) in nhanSuCapBacChart" :key="index"
                  class="flex items-end h-full justify-center group relative cursor-pointer w-16 hover:z-[60]">
                
                <!-- Bar -->
                <div class="w-6 md:w-8 bg-gradient-to-t rounded-t-md relative shadow-sm group-hover:opacity-90 transition-opacity bar-pillar"
                     :class="[item.color, item.shadow]" :style="`height: ${item.heightPct}%; animation-delay: ${200 + (index * 100)}ms`">
                     
                   <!-- Clean, Informative Tooltip -->
                   <div class="hidden group-hover:flex absolute bottom-full left-1/2 -translate-x-1/2 mb-2 whitespace-nowrap pointer-events-none">
                      <div class="bg-slate-800 text-white rounded-xl shadow-xl shadow-slate-800/20 p-2.5 flex flex-col items-center min-w-[120px]">
                         <span class="text-[10px] font-[800] text-slate-400 uppercase tracking-widest mb-1.5">{{ item.level }}</span>
                         <div class="flex items-center justify-between w-full gap-3 text-[12px] font-bold">
                            <span class="text-slate-300 flex items-center gap-1.5">Số lượng:</span>
                            <span class="text-blue-400 flex items-center gap-1.5">{{ item.count }}</span>
                         </div>
                         <div class="flex items-center justify-between w-full gap-3 text-[11px] font-semibold mt-1">
                            <span class="text-slate-400">Tỷ lệ:</span>
                            <span class="text-slate-300">{{ item.totalPct }}%</span>
                         </div>
                         <div class="absolute -bottom-1.5 left-1/2 -translate-x-1/2 w-3 h-3 bg-slate-800 rotate-45"></div>
                      </div>
                   </div>
                </div>
             </div>
          </div>
        </div>

        <div class="flex justify-around items-end pt-5 ml-8 relative z-10 border-t border-slate-100 mt-2">
          <span v-for="(item, index) in nhanSuCapBacChart" :key="index" class="text-[11px] font-[800] text-slate-500 w-16 text-center">{{ item.level }}</span>
        </div>
      </div>

      <!-- Chart: Biến động nhân sự (Dual Bar Chart) -->
      <div class="bg-white rounded-2xl border border-slate-100 shadow-[0_2px_12px_-4px_rgba(0,0,0,0.04)] flex flex-col pt-6 pb-5 px-6 relative h-[360px] animate-chart" style="animation-delay: 300ms">
        <div class="flex justify-between items-start mb-auto relative z-10">
          <div>
            <h2 class="text-[16px] font-[800] text-slate-800">Biến động nhân lực</h2>
            <p class="text-[12px] font-semibold text-slate-400 mt-1">Số lượng Tuyển vào & Nghỉ việc (6 tháng)</p>
          </div>
          <div class="flex items-center gap-3">
             <div class="flex items-center gap-1.5"><div class="w-2.5 h-2.5 rounded-full bg-blue-500 shadow-sm shadow-blue-500/30"></div><span class="text-[11px] font-bold text-slate-600">Tuyển vào</span></div>
             <div class="flex items-center gap-1.5"><div class="w-2.5 h-2.5 rounded-full bg-rose-400 shadow-sm shadow-rose-400/30"></div><span class="text-[11px] font-bold text-slate-600">Nghỉ việc</span></div>
          </div>
        </div>
        
        <div class="flex-1 relative w-full mt-10 mb-2 z-10">
          <!-- Background Grid Lines -->
          <div class="absolute inset-x-0 bottom-0 top-0 flex flex-col justify-between">
            <div class="border-t border-slate-100 border-dashed w-full h-0 relative"><span class="absolute -top-2.5 bg-white pr-2 text-[10px] font-bold text-slate-400">{{ maxBienDongInfo }}</span></div>
            <div class="border-t border-slate-100 border-dashed w-full h-0 relative"><span class="absolute -top-2.5 bg-white pr-2 text-[10px] font-bold text-slate-400">{{ maxBienDongInfo / 2 }}</span></div>
            <div class="border-t border-slate-200 w-full h-0 relative"><span class="absolute -top-2.5 bg-white pr-2 text-[10px] font-bold text-slate-400">0</span></div>
          </div>
          
          <!-- Column Bars Container -->
          <div class="absolute inset-0 ml-8 flex items-end justify-around pb-[1px] z-20" style="height: 100%;">
             <div v-for="(item, idx) in bienDongChartComputed" :key="idx"
                  class="flex items-end gap-1.5 md:gap-2.5 h-full justify-center group relative cursor-pointer w-16 hover:z-[60]">
                
                <!-- Tuyển Vào Bar -->
                <div class="w-4 md:w-5 bg-gradient-to-t from-blue-600 to-blue-400 rounded-t-md relative shadow-sm shadow-blue-500/20 group-hover:opacity-90 transition-opacity bar-pillar"
                     :style="`height: ${item.tuyenVaoPct}%; animation-delay: ${idx * 100}ms`"></div>

                <!-- Nghỉ Việc Bar -->
                <div class="w-4 md:w-5 bg-gradient-to-t from-rose-500 to-rose-400 rounded-t-md relative shadow-sm shadow-rose-500/20 group-hover:opacity-90 transition-opacity bar-pillar"
                     :style="`height: ${item.nghiViecPct}%; animation-delay: ${(idx * 100) + 50}ms`"></div>

                <!-- Phantom Div for precise Tooltip Positioning above the tallest bar -->
                <div class="absolute bottom-0 w-full pointer-events-none" :style="`height: ${Math.max(item.tuyenVaoPct, item.nghiViecPct)}%`">
                   <!-- Clean, Informative Tooltip -->
                   <div class="hidden group-hover:flex absolute bottom-full left-1/2 -translate-x-1/2 mb-2 whitespace-nowrap pointer-events-none">
                      <div class="bg-slate-800 text-white rounded-xl shadow-xl shadow-slate-800/20 p-2.5 flex flex-col items-center min-w-[130px]">
                         <span class="text-[10px] font-[800] text-slate-400 uppercase tracking-widest mb-1.5">{{ item.month }}</span>
                         <div class="flex items-center justify-between w-full gap-4 text-[12px] font-bold">
                            <span class="text-blue-400 flex items-center gap-1.5">Tuyển: {{ item.tuyenVao }}</span>
                            <span class="text-rose-400 flex items-center gap-1.5">Nghỉ: {{ item.nghiViec }}</span>
                         </div>
                         <div class="absolute -bottom-1.5 left-1/2 -translate-x-1/2 w-3 h-3 bg-slate-800 rotate-45"></div>
                      </div>
                   </div>
                </div>
             </div>
          </div>
        </div>

        <div class="flex justify-around items-end pt-5 ml-8 relative z-10 border-t border-slate-100 mt-2">
          <span v-for="(item, index) in bienDongChartComputed" :key="index" class="text-[11px] font-[800] text-slate-500 w-16 text-center">{{ item.month }}</span>
        </div>
      </div>
    </div>

    <!-- Bottom Lists Row -->
    <div class="flex flex-col lg:flex-row gap-4 md:gap-5">
      
      <!-- Top Performers (Table) -->
      <div class="bg-white rounded-2xl border border-slate-100 shadow-[0_2px_12px_-4px_rgba(0,0,0,0.04)] lg:w-3/5 p-7 overflow-hidden animate-chart" style="animation-delay: 400ms">
        <div class="flex items-center justify-between xl:mb-4 mb-6">
          <h2 class="text-[16px] font-[800] text-slate-800">Nhân sự chủ chốt & Hiệu suất cao</h2>
          <button class="text-[12px] font-[800] text-[#3B5BDB] hover:text-blue-800 transition-colors">Xem tất cả</button>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse">
            <thead>
              <tr class="border-b border-slate-100">
                <th class="py-3 px-2 text-[10px] font-bold text-slate-400 uppercase tracking-[0.15em] whitespace-nowrap">Họ & Tên</th>
                <th class="py-3 px-2 text-[10px] font-bold text-slate-400 uppercase tracking-[0.15em] whitespace-nowrap">Chức vụ</th>
                <th class="py-3 px-2 text-[10px] font-bold text-slate-400 uppercase tracking-[0.15em] text-center whitespace-nowrap">Hiệu suất</th>
                <th class="py-3 px-2 text-[10px] font-bold text-slate-400 uppercase tracking-[0.15em] text-center whitespace-nowrap">Trạng thái</th>
              </tr>
            </thead>
            <!-- ✅ Top performers – từ topPerformers -->
            <tbody class="divide-y divide-slate-50">
              <tr v-for="(emp, index) in topPerformers" :key="emp.id" class="hover:bg-slate-50/50 transition-colors group">
                <td class="py-5 px-2">
                  <div class="flex items-center gap-3">
                    <img :src="emp.avatar" class="w-10 h-10 rounded-full object-cover border-2 border-slate-100" alt=""/>
                    <div>
                      <p class="text-[13px] font-[800] text-slate-800 group-hover:text-[#3B5BDB] cursor-pointer transition-colors leading-tight">{{ emp.name }}</p>
                      <p class="text-[11px] font-semibold text-slate-400 mt-0.5">{{ emp.email }}</p>
                    </div>
                  </div>
                </td>
                <td class="py-5 px-2 text-[13px] font-bold text-slate-600">{{ emp.chucVu }}</td>
                <td class="py-5 px-2 text-center w-36">
                  <span class="text-[12px] font-[800] text-[#3B5BDB] mb-1.5 inline-block">{{ emp.hieuSuat }}%</span>
                  <div class="w-full h-1.5 bg-slate-100 rounded-full overflow-hidden mx-auto">
                    <div 
                      class="h-full bg-[#3B5BDB] rounded-full transition-all duration-[1500ms] ease-out" 
                      :style="{ width: isChartLoaded ? emp.hieuSuat + '%' : '0%', transitionDelay: (index * 150) + 'ms' }"
                    ></div>
                  </div>
                </td>
                <td class="py-5 px-2 text-center">
                  <span class="inline-flex items-center px-3 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider" :class="emp.trangThaiClass">{{ emp.trangThai }}</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Department Rankings -->
      <div class="bg-white rounded-2xl border border-slate-100 shadow-[0_2px_12px_-4px_rgba(0,0,0,0.04)] lg:w-2/5 p-7 flex flex-col">
        <h2 class="text-[16px] font-[800] text-slate-800 mb-6">Xếp hạng phòng ban</h2>
        
        <!-- ✅ Dept rankings – từ deptRankings -->
        <div class="flex flex-col flex-1 justify-between gap-6">
          <div v-for="(dept, index) in deptRankings" :key="dept.name">
            <div class="flex justify-between items-end mb-1.5">
              <span class="text-[13px] font-[800] text-slate-800">{{ dept.name }}</span>
              <span class="text-[14px] font-[900] text-[#3B5BDB]">{{ dept.score }}%</span>
            </div>
            <div class="w-full h-[6px] bg-slate-100 rounded-full overflow-hidden mb-1.5">
              <div 
                class="h-full bg-[#3B5BDB] rounded-full transition-all duration-[2000ms] ease-out" 
                :style="{ width: isChartLoaded ? dept.score + '%' : '0%', transitionDelay: (index * 200) + 'ms' }"
              ></div>
            </div>
            <div class="flex justify-between items-center text-[11px] font-semibold text-slate-400">
              <span class="italic">{{ dept.note }}</span>
              <span v-if="dept.trend==='up'"   class="material-symbols-outlined text-green-500 text-[16px]">trending_up</span>
              <span v-else-if="dept.trend==='down'" class="material-symbols-outlined text-red-400 text-[16px]">trending_down</span>
              <span v-else class="material-symbols-outlined text-transparent text-[16px] select-none">remove</span>
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
import { apiRequest } from '@/services/beApi.js';

const selectedDateRange = ref('30_days');
const isChartLoaded = ref(false);
const Math = window.Math;
const employees = ref([]);
const departments = ref([]);
const positions = ref([]);
const salaryDetails = ref([]);

const normalizeStatus = (value) => {
  const raw = String(value || '').trim().toUpperCase();
  if (raw.includes('ĐANG') || raw.includes('ACTIVE')) return 'ĐANG_LÀM_VIỆC';
  if (raw.includes('NGHỈ') || raw.includes('INACTIVE') || raw.includes('TERMIN')) return 'ĐÃ_NGHỈ_VIỆC';
  return 'ĐANG_LÀM_VIỆC';
};

const loadData = async () => {
  try {
    const [empRes, deptRes, posRes, salRes] = await Promise.all([
      apiRequest('/employees', { query: { page: 1, per_page: 5000 }, noGetCache: true }),
      apiRequest('/departments', { query: { page: 1, per_page: 1000 }, noGetCache: true }),
      apiRequest('/positions', { query: { page: 1, per_page: 1000 }, noGetCache: true }),
      apiRequest('/salary-details', { query: { page: 1, per_page: 5000 }, noGetCache: true }).catch(() => ({ data: [] })),
    ]);

    employees.value = (empRes?.data || []).map((e) => ({
      employeeId: e.employee_id ?? e.employeeId,
      fullName: e.full_name ?? e.fullName ?? '',
      employeeCode: e.employee_code ?? e.employeeCode ?? '',
      companyEmail: e.company_email ?? e.companyEmail ?? '',
      departmentId: e.department_id ?? e.departmentId,
      positionId: e.position_id ?? e.positionId,
      status: normalizeStatus(e.status),
      avatarUrl: e.avatar_url ?? e.avatarUrl ?? '',
      hireDate: e.hire_date ?? e.hireDate ?? null,
    }));
    departments.value = (deptRes?.data || []).map((d) => ({
      departmentId: d.department_id ?? d.departmentId,
      departmentName: d.department_name ?? d.departmentName ?? '',
    }));
    positions.value = (posRes?.data || []).map((p) => ({
      positionId: p.position_id ?? p.positionId,
      positionName: p.position_name ?? p.positionName ?? '',
    }));
    salaryDetails.value = salRes?.data || [];
  } catch (error) {
    console.error('Không tải được dữ liệu nhân sự giám đốc:', error);
    employees.value = [];
    departments.value = [];
    positions.value = [];
    salaryDetails.value = [];
  }
};

onMounted(() => {
  loadData();
  setTimeout(() => isChartLoaded.value = true, 300);
});

const nhanSuKpiCards = computed(() => {
  const emps = employees.value;
  const total = emps.filter(e => e.status === 'ĐANG_LÀM_VIỆC').length;
  const thoiViec = emps.filter(e => e.status === 'ĐÃ_NGHỈ_VIỆC').length;
  const tyLeNghi = emps.length > 0 ? ((thoiViec / emps.length) * 100).toFixed(1) : 0;
  const avgPayroll = salaryDetails.value.length
    ? Math.round(
        salaryDetails.value.reduce((sum, row) => sum + Number(row.net_salary ?? row.total_salary ?? row.basic_salary ?? 0), 0) /
          salaryDetails.value.length
      )
    : 0;
  return [
    { id: 1, label: 'Tổng nhân sự', value: total.toString(), icon: 'groups', iconBg: 'bg-blue-100', iconColor: 'text-blue-600', badgeClass: 'bg-blue-50 text-blue-600', badge: '+12%', note: 'So với tháng trước' },
    { id: 2, label: 'Tỉ lệ nghỉ việc', value: tyLeNghi + '%', icon: 'person_remove', iconBg: 'bg-red-100', iconColor: 'text-red-600', badgeClass: 'bg-red-50 text-red-600', badge: '+0.5%', note: 'So với tháng trước' },
    { id: 3, label: 'Hiệu suất TB', value: `${Math.max(80, 100 - Number(tyLeNghi || 0)).toFixed(0)}%`, icon: 'trending_up', iconBg: 'bg-green-100', iconColor: 'text-green-600', badgeClass: 'bg-green-50 text-green-600', badge: '+2%', note: 'Vượt mục tiêu' },
    { id: 4, label: 'Chi phí nhân sự', value: avgPayroll > 0 ? `${(avgPayroll / 1000000).toFixed(0)}Tr` : '0', icon: 'payments', iconBg: 'bg-orange-100', iconColor: 'text-orange-600', badgeClass: 'bg-orange-50 text-orange-600', badge: '-1.2%', note: 'Bình quân theo bảng lương' }
  ];
});

const topPerformers = computed(() => {
  const emps = employees.value.filter(e => e.status === 'ĐANG_LÀM_VIỆC');
  return emps.slice(0, 3).map((e, index) => {
    const pos = positions.value.find((p) => String(p.positionId) === String(e.positionId));
    return {
      id: e.employeeId,
      name: e.fullName,
      email: e.companyEmail || `${e.employeeCode || 'nv'}@hrm.com`,
      avatar: e.avatarUrl || `https://i.pravatar.cc/150?u=${index}`,
      chucVu: pos?.positionName || 'Nhân viên',
      hieuSuat: 100 - (index * 5),
      trangThai: index === 0 ? 'Xuất sắc' : 'Tốt',
      trangThaiClass: index === 0 ? 'bg-green-100 text-green-700' : 'bg-blue-100 text-blue-700'
    };
  });
});

const deptRankings = computed(() => {
  const depts = departments.value;
  return depts.slice(0, 3).map((d, index) => {
    return {
      name: d.departmentName,
      score: 95 - (index * 10),
      note: index === 0 ? 'Vượt chỉ tiêu' : (index === 1 ? 'Đạt chỉ tiêu' : 'Cần cải thiện'),
      trend: index === 0 ? 'up' : 'down'
    };
  });
});

const nhanSuCapBac = computed(() => {
  const emps = employees.value.filter(e => e.status === 'ĐANG_LÀM_VIỆC');
  const levelColors = [
    { color: 'from-blue-600 to-blue-400', shadow: 'shadow-blue-500/30' },
    { color: 'from-indigo-500 to-indigo-400', shadow: 'shadow-indigo-500/30' },
    { color: 'from-blue-500 to-blue-400', shadow: 'shadow-blue-500/30' },
    { color: 'from-cyan-500 to-cyan-400', shadow: 'shadow-cyan-500/30' },
    { color: 'from-slate-400 to-slate-300', shadow: 'shadow-slate-400/30' }
  ];
  return positions.value.map((p, index) => {
    const count = emps.filter(e => e.positionId === p.positionId).length;
    return {
      level: p.positionName,
      count: count,
      ...levelColors[index % levelColors.length]
    };
  }).filter(p => p.count > 0);
});

const maxNhanSu = computed(() => Math.max(500, employees.value.length || 0));
const totalNhanSu = computed(() => nhanSuCapBac.value.reduce((acc, curr) => acc + curr.count, 0) || 1);

const nhanSuCapBacChart = computed(() => {
  return nhanSuCapBac.value.map(item => ({
    ...item,
    heightPct: Math.min((item.count / maxNhanSu.value) * 100, 115),
    totalPct: ((item.count / totalNhanSu.value) * 100).toFixed(0)
  }));
});

const bienDongNhanLucData = computed(() => {
  const months = Array.from({ length: 6 }).map((_, idx) => {
    const d = new Date();
    d.setMonth(d.getMonth() - (5 - idx));
    const key = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}`;
    return { key, month: `Th.${d.getMonth() + 1}`, tuyenVao: 0, nghiViec: 0 };
  });

  const monthMap = new Map(months.map((m) => [m.key, m]));
  employees.value.forEach((e) => {
    const hd = String(e.hireDate || '').slice(0, 7);
    if (monthMap.has(hd)) monthMap.get(hd).tuyenVao += 1;
    if (e.status === 'ĐÃ_NGHỈ_VIỆC' && monthMap.has(hd)) monthMap.get(hd).nghiViec += 1;
  });

  return months;
});

const maxBienDongInfo = computed(() => {
  let max = 0;
  bienDongNhanLucData.value.forEach((item) => {
    if (item.tuyenVao > max) max = item.tuyenVao;
    if (item.nghiViec > max) max = item.nghiViec;
  });
  const upperBound = Math.ceil(max / 50) * 50; 
  return upperBound === 0 ? 100 : upperBound;
});

const bienDongChartComputed = computed(() => {
  return bienDongNhanLucData.value.map(item => ({
    ...item,
    tuyenVaoPct: Math.min((item.tuyenVao / maxBienDongInfo.value) * 100, 115),
    nghiViecPct: Math.min((item.nghiViec / maxBienDongInfo.value) * 100, 115)
  }));
});
</script>

<style scoped>
.material-symbols-outlined {
  font-variation-settings: 'FILL' 1, 'wght' 500, 'GRAD' 0, 'opsz' 24;
}

/* Animations for SVG Charts */
@keyframes dashDraw { to { stroke-dashoffset: 0; } }
@keyframes fadeIn { to { opacity: 1; } }
.draw-blue { stroke-dasharray: 1000; stroke-dashoffset: 1000; animation: dashDraw 1.5s cubic-bezier(0.25, 1, 0.5, 1) forwards; }
.draw-red { stroke-dasharray: 1000; stroke-dashoffset: 1000; animation: dashDraw 1.5s cubic-bezier(0.25, 1, 0.5, 1) forwards 0.2s; }
.fade-area { opacity: 0; animation: fadeIn 1s ease-in forwards 0.6s; }
.fade-dots { opacity: 0; animation: fadeIn 0.5s ease forwards 1.2s; }

@keyframes pillarGrow {
  from { height: 0; opacity: 0; }
  to   { opacity: 1; }
}

.bar-pillar {
  animation: pillarGrow 1s cubic-bezier(0.34, 1.56, 0.64, 1) backwards;
}
</style>
