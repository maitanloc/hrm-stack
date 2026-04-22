<template>
  <div class="p-6 bg-[#F8FAFC] min-h-screen">
    <!-- Header & Filters -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
      <div>
        <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Quản lý phân ca làm việc</h1>
        <p class="text-slate-500 text-sm mt-1">Lập lịch, điều chỉnh và chốt phân ca tuần cho đội ngũ.</p>
      </div>

      <div class="flex items-center gap-3 bg-white p-2 rounded-xl shadow-sm border border-slate-200">
        <!-- Dept Selector -->
        <select 
          v-model="store.selectedDepartmentId"
          class="bg-slate-50 border-none rounded-lg text-sm font-medium px-3 py-2 focus:ring-2 focus:ring-blue-500 outline-none"
          @change="handleRefresh"
        >
          <option v-for="dept in departments" :key="dept.id" :value="dept.id">
            {{ dept.name }}
          </option>
        </select>

        <!-- Date Navigator -->
        <div class="flex items-center gap-1 border-l border-slate-200 pl-3">
          <button @click="changeWeek(-1)" class="p-1.5 hover:bg-slate-100 rounded-lg transition-colors">
            <span class="material-symbols-outlined text-[20px] text-slate-600">chevron_left</span>
          </button>
          <div class="px-2 text-sm font-bold text-slate-700 whitespace-nowrap">
            {{ formatRange(store.selectedDateRange.from, store.selectedDateRange.to) }}
          </div>
          <button @click="changeWeek(1)" class="p-1.5 hover:bg-slate-100 rounded-lg transition-colors">
            <span class="material-symbols-outlined text-[20px] text-slate-600">chevron_right</span>
          </button>
        </div>

        <button 
          @click="handleRefresh"
          class="ml-2 p-2 bg-blue-50 text-blue-600 hover:bg-blue-100 rounded-lg transition-colors flex items-center gap-2"
          :class="{ 'animate-spin': store.loading }"
        >
          <span class="material-symbols-outlined text-[20px]">refresh</span>
        </button>
      </div>
    </div>

    <!-- Summary Cards -->
    <div v-if="loadError" class="mb-6 p-6 bg-rose-50 border border-rose-200 rounded-2xl flex items-center gap-4 text-rose-700 shadow-sm animate-in fade-in slide-in-from-top-4 duration-300">
       <span class="material-symbols-outlined text-3xl">error</span>
       <div>
         <p class="font-bold text-lg">Hệ thống đang gặp sự cố kết nối</p>
         <p class="text-sm opacity-90">{{ loadError }}. Vui lòng kiểm tra Docker/Backend và thử lại.</p>
         <p class="text-[10px] mt-1 font-mono bg-rose-100 p-1 rounded">Debug: DeptID={{ store.selectedDepartmentId || 'None' }} | Port=8080</p>
       </div>
       <button @click="onMounted" class="ml-auto px-4 py-2 bg-rose-600 text-white rounded-xl font-bold hover:bg-rose-700 transition-all">Thử lại</button>
    </div>

    <div v-else class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
      <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm flex items-center gap-4">
        <div class="w-12 h-12 rounded-xl bg-blue-50 flex items-center justify-center">
          <span class="material-symbols-outlined text-blue-600">group</span>
        </div>
        <div>
          <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Nhân sự</p>
          <p class="text-xl font-bold text-slate-900">{{ store.totalEmployees }}</p>
        </div>
      </div>

      <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm flex items-center gap-4">
        <div class="w-12 h-12 rounded-xl bg-amber-50 flex items-center justify-center">
          <span class="material-symbols-outlined text-amber-600">event_busy</span>
        </div>
        <div>
          <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Chưa phân ca</p>
          <p class="text-xl font-bold text-slate-900">{{ store.unassignedEmployees }}</p>
        </div>
      </div>

      <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm flex items-center gap-4">
        <div class="w-12 h-12 rounded-xl bg-rose-50 flex items-center justify-center">
          <span class="material-symbols-outlined text-rose-600">warning</span>
        </div>
        <div>
          <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Cảnh báo</p>
          <p class="text-xl font-bold text-slate-900">{{ store.warningCount }}</p>
        </div>
      </div>

      <div class="p-4 rounded-2xl border shadow-sm flex items-center gap-4 transition-all duration-300"
           :class="store.isPublished ? 'bg-emerald-50 border-emerald-200' : 'bg-slate-50 border-slate-200'">
        <div class="w-12 h-12 rounded-xl flex items-center justify-center"
             :class="store.isPublished ? 'bg-emerald-100' : 'bg-slate-200'">
          <span class="material-symbols-outlined" 
                :class="store.isPublished ? 'text-emerald-600' : 'text-slate-600'">
            {{ store.isPublished ? 'verified' : 'draft' }}
          </span>
        </div>
        <div>
          <p class="text-xs font-medium uppercase tracking-wider"
             :class="store.isPublished ? 'text-emerald-700' : 'text-slate-500'">Trạng thái</p>
          <p class="text-xl font-bold"
             :class="store.isPublished ? 'text-emerald-900' : 'text-slate-900'">
            {{ store.statusLabel }}
          </p>
        </div>
      </div>
    </div>

    <!-- Workflow Tabs -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden mb-6">
      <div class="flex border-b border-slate-100 bg-slate-50/50">
        <button 
          v-for="tab in tabs" 
          :key="tab.id"
          @click="handleTabChange(tab.id)"
          class="px-6 py-4 text-sm font-bold transition-all border-b-2 flex items-center gap-2"
          :class="store.activeTab === tab.id 
            ? 'border-blue-600 text-blue-600 bg-white' 
            : 'border-transparent text-slate-500 hover:text-slate-700 hover:bg-slate-100/50'"
        >
          <span class="material-symbols-outlined text-[18px]">{{ tab.icon }}</span>
          {{ tab.name }}
          <span v-if="tab.id === 3 && store.warningCount > 0" 
                class="bg-rose-500 text-white text-[10px] px-1.5 py-0.5 rounded-full ml-1 animate-pulse">
            {{ store.warningCount }}
          </span>
          <span v-if="tab.id === 5 && store.suggestions.length > 0" 
                class="bg-blue-500 text-white text-[10px] px-1.5 py-0.5 rounded-full ml-1">
            {{ store.suggestions.length }}
          </span>
        </button>
      </div>

      <div class="p-6">
        <!-- Tab Content -->
        <div v-show="store.activeTab === 1">
          <div class="flex flex-col lg:flex-row gap-6">
             <div class="lg:w-2/3">
               <WeeklyMatrixGrid />
             </div>
             <div class="lg:w-1/3 space-y-4">
                <BaseSchedulePanel />
             </div>
          </div>
        </div>

        <div v-show="store.activeTab === 2">
          <OverridePanel />
        </div>

        <div v-show="store.activeTab === 3">
          <WarningPanel />
        </div>

        <div v-show="store.activeTab === 5">
          <SmartAssistancePanel />
        </div>

        <div v-show="store.activeTab === 6">
          <GovernancePanel />
        </div>

        <div v-show="store.activeTab === 4">
          <PublishPanel />
        </div>
      </div>
    </div>

    <!-- Shared Modals -->
    <ScheduleModals />
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { useScheduleStore } from '@/stores/useScheduleStore';
import { apiRequest } from '@/services/beApi.js';
import WeeklyMatrixGrid from '@/components/schedule/WeeklyMatrixGrid.vue';
import BaseSchedulePanel from '@/components/schedule/BaseSchedulePanel.vue';
import OverridePanel from '@/components/schedule/OverridePanel.vue';
import WarningPanel from '@/components/schedule/WarningPanel.vue';
import PublishPanel from '@/components/schedule/PublishPanel.vue';
import SmartAssistancePanel from '@/components/schedule/SmartAssistancePanel.vue';
import GovernancePanel from '@/components/schedule/GovernancePanel.vue';
import ScheduleModals from '@/components/schedule/ScheduleModals.vue';

const store = useScheduleStore();

const departments = ref([]);

const tabs = [
  { id: 1, name: '1. Tạo lịch nền', icon: 'calendar_month' },
  { id: 2, name: '2. Ngoại lệ / Ghi đè', icon: 'edit_calendar' },
  { id: 3, name: '3. Cảnh báo', icon: 'warning' },
  { id: 5, name: '4. Smart Assistance', icon: 'auto_awesome' },
  { id: 6, name: '5. Quản trị & Báo cáo', icon: 'admin_panel_settings' }, // New Tab
  { id: 4, name: '6. Chốt & Publish', icon: 'task_alt' },
];

const handleTabChange = (id) => {
  store.setActiveTab(id);
  if (id === 5) {
    store.fetchSuggestions();
  } else if (id === 6) {
    store.fetchAuditLogs();
    store.fetchPublishHistory();
    store.fetchGovernanceOverview();
  }
};

const formatRange = (from, to) => {
  if (!from || !to) return 'Chưa chọn ngày';
  const f = new Date(from);
  const t = new Date(to);
  return `${f.toLocaleDateString('vi-VN')} - ${t.toLocaleDateString('vi-VN')}`;
};

const changeWeek = (offset) => {
  const current = new Date(store.selectedDateRange.from || new Date());
  const newFrom = new Date(current.setDate(current.getDate() + (offset * 7)));
  
  // Set to Monday
  const day = newFrom.getDay() || 7;
  if (day !== 1) newFrom.setHours(-24 * (day - 1));
  
  const newTo = new Date(newFrom);
  newTo.setDate(newTo.getDate() + 6);

  store.selectedDateRange = {
    from: newFrom.toISOString().split('T')[0],
    to: newTo.toISOString().split('T')[0]
  };
  handleRefresh();
};

const handleRefresh = async () => {
  try {
    loadError.value = null;
    await store.fetchScheduleData(
      store.selectedDepartmentId,
      store.selectedDateRange.from,
      store.selectedDateRange.to
    );
  } catch (err) {
    loadError.value = err?.message || 'Không tải được dữ liệu phân ca';
  }
};

const loadError = ref(null);

const loadDepartments = async () => {
  try {
    const res = await apiRequest('/departments', { query: { page: 1, per_page: 500 } });
    if (res?.success && res.data) {
      departments.value = [
        { id: null, name: 'Tất cả phòng ban' },
        ...res.data.map(d => ({
        id: Number(d.departmentId || d.department_id || d.id),
        name: d.departmentName || d.department_name || d.name
      }))
      ];
    } else {
      throw new Error(res?.message || 'Không thể tải danh sách phòng ban');
    }
  } catch (err) {
    console.error('Failed to load departments', err);
    loadError.value = err.message;
  }
};

onMounted(async () => {
  store.loading = true;
  loadError.value = null;
  try {
    // 1. Load basic options first
    await Promise.all([
      loadDepartments(),
      store.fetchShiftTypes()
    ]);
    
    if (loadError.value) return;

    // 2. Set default date range...
    // (giữ nguyên logic date range)
    if (!store.selectedDateRange.from) {
       // ... logic Monday-Sunday ...
    }

    // 3. Trigger data load
    await handleRefresh();
  } catch (err) {
    loadError.value = "Lỗi khởi tạo hệ thống. Vui lòng kiểm tra Backend.";
  } finally {
    store.loading = false;
  }
});
</script>
