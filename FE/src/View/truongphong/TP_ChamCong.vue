<template>
  <div class="space-y-6 pb-8">
    <!-- Header Area: SaaS Enterprise Style -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 bg-transparent text-left px-1">
      <div class="bg-transparent text-left">
        <h1 class="text-xl font-bold text-[var(--sys-text-primary)] mb-0.5 tracking-tight uppercase">Quản lý Chấm công & Chuyên cần</h1>
        <p class="text-[13px] text-[var(--sys-text-secondary)] font-medium">Kiểm soát và phê duyệt dữ liệu chuyên cần nhân sự {{ deptName }}.</p>
      </div>
      <div class="flex items-center gap-3 shrink-0">
        <button @click="openExportPreview" class="h-11 px-6 bg-[var(--sys-brand-soft)] text-[var(--sys-brand-solid)] rounded-md font-bold text-[13px] uppercase tracking-widest border border-[var(--sys-brand-border)] hover:bg-[var(--sys-brand-solid)] hover:text-white transition-all flex items-center gap-2.5 shadow-sm active:scale-95">
          <span class="material-symbols-rounded text-[20px] font-bold">cloud_download</span> 
          Xuất dữ liệu công
        </button>
      </div>
    </div>

    <!-- Main Content Container: Refined Harmony -->
    <div class="bg-[var(--sys-bg-surface)] rounded-lg border border-[var(--sys-border-subtle)] shadow-sm overflow-hidden flex flex-col">
      <!-- Toolbar: Consistent and Compact -->
      <div class="px-3.5 py-3 border-b border-[var(--sys-border-subtle)] flex flex-col xl:flex-row justify-between items-start xl:items-center gap-3.5 bg-[var(--sys-bg-page)]/20">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-md bg-[var(--sys-brand-soft)] text-[var(--sys-brand-solid)] flex items-center justify-center border border-[var(--sys-brand-border)] shadow-sm">
            <span class="material-symbols-rounded text-[24px] font-bold">date_range</span>
          </div>
          <div class="bg-transparent text-left">
            <h5 class="text-[11px] font-bold text-[var(--sys-text-primary)] uppercase tracking-widest m-0 leading-none mb-1 shadow-none">BẢNG CÔNG CHI TIẾT</h5>
            <p class="text-[10px] font-bold text-[var(--sys-text-secondary)] m-0 uppercase tracking-widest opacity-60">Tháng {{ selectedMonth }}/{{ selectedYear }}</p>
          </div>
        </div>

        <div class="flex flex-wrap items-center gap-4 w-full xl:w-auto">
          <div class="flex items-center gap-2">
            <Dropdown v-model="selectedMonth" :options="monthOptions" class="min-w-[150px] h-10 shadow-sm" />
            <Dropdown v-model="selectedYear" :options="yearOptions" class="min-w-[100px] h-10 shadow-sm" />
          </div>
          
          <!-- Legend: Balanced and Clear -->
          <div class="h-10 px-5 bg-[var(--sys-bg-surface)] rounded-md border border-[var(--sys-border-subtle)] flex items-center gap-6 shadow-sm overflow-hidden">
            <div class="flex items-center gap-2.5">
              <span class="w-2.5 h-2.5 rounded-full bg-[var(--sys-success-solid)]"></span>
              <span class="text-[11px] font-bold text-[var(--sys-text-secondary)] uppercase tracking-widest">Đúng giờ</span>
            </div>
            <div class="flex items-center gap-2.5">
              <span class="w-2.5 h-2.5 rounded-full bg-[var(--sys-warning-solid)]"></span>
              <span class="text-[11px] font-bold text-[var(--sys-text-secondary)] uppercase tracking-widest">Đi muộn</span>
            </div>
            <div class="flex items-center gap-2.5">
              <span class="w-2.5 h-2.5 rounded-full bg-[var(--sys-danger-solid)]"></span>
              <span class="text-[11px] font-bold text-[var(--sys-text-secondary)] uppercase tracking-widest">Vắng mặt</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Attendance Grid: Optimized Cells for Visibility -->
      <div class="overflow-x-auto custom-scrollbar flex-grow bg-white">
        <table class="w-full text-left border-collapse min-w-[1200px]">
          <thead class="bg-[var(--sys-bg-page)]/40">
            <tr>
              <th class="px-6 py-4 text-[11px] font-bold text-[var(--sys-text-secondary)] border-b border-[var(--sys-border-subtle)] uppercase tracking-widest sticky left-0 z-20 bg-[var(--sys-bg-page)] shadow-sm">NHÂN SỰ PHÒNG</th>
              <th v-for="d in daysInMonth" :key="d" 
                class="px-1 py-4 text-[11px] font-bold text-center border-b border-[var(--sys-border-subtle)] transition-colors"
                :class="isWeekend(d) ? 'bg-[var(--sys-bg-page)] text-[var(--sys-text-disabled)] opacity-60' : 'text-[var(--sys-text-secondary)]'">
                {{ d < 10 ? '0' + d : d }}
              </th>
              <th class="px-6 py-4 text-[11px] font-bold text-right border-b border-[var(--sys-border-subtle)] uppercase tracking-widest sticky right-0 z-20 bg-[var(--sys-bg-page)] shadow-sm">TỔNG CÔNG</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-[var(--sys-border-subtle)]">
            <tr v-for="staff in attendanceList" :key="staff.id" class="group hover:bg-[var(--sys-bg-hover)] transition-all duration-200">
              <!-- Sticky Name Cell -->
              <td class="px-6 py-4 sticky left-0 z-10 bg-[var(--sys-bg-surface)] border-r border-[var(--sys-border-subtle)] group-hover:bg-[var(--sys-bg-hover)] shadow-sm">
                <div class="flex items-center gap-4">
                  <div class="w-9 h-9 rounded-md bg-[var(--sys-brand-soft)] text-[var(--sys-brand-solid)] flex items-center justify-center font-bold text-xs uppercase border border-[var(--sys-brand-border)]">
                    {{ staff.name.charAt(0) }}
                  </div>
                  <div class="flex flex-col">
                    <span class="text-[13px] font-bold text-[var(--sys-text-primary)] leading-none mb-1 group-hover:text-[var(--sys-brand-solid)] transition-colors">{{ staff.name }}</span>
                    <span class="text-[10px] font-bold text-[var(--sys-text-secondary)] items-center flex gap-1.5 uppercase tracking-widest opacity-60">
                      {{ staff.id }} <span class="w-1 h-1 rounded-full bg-[var(--sys-border-strong)]"></span> {{ staff.dept }}
                    </span>
                  </div>
                </div>
              </td>

              <!-- Status Cells -->
              <td v-for="d in daysInMonth" :key="d" 
                class="px-1 py-3 border-r border-[var(--sys-border-subtle)]/50 text-center transition-colors"
                :class="isWeekend(d) ? 'bg-[var(--sys-bg-page)] opacity-40' : ''">
                <div v-if="!isWeekend(d)" class="flex justify-center h-7 items-center">
                   <div v-if="staff.data[d] === 'on'" class="w-7 h-7 rounded-md flex items-center justify-center font-bold text-[10px] bg-[var(--sys-success-soft)] text-[var(--sys-success-text)] border border-[var(--sys-success-border)] shadow-sm scale-95 hover:scale-105 transition-transform" title="Đúng giờ">✔</div>
                   <div v-if="staff.data[d] === 'late'" class="w-7 h-7 rounded-md flex items-center justify-center font-bold text-[10px] bg-[var(--sys-warning-soft)] text-[var(--sys-warning-text)] border border-[var(--sys-warning-border)] shadow-sm scale-95 hover:scale-105 transition-transform" title="Đi muộn">M</div>
                   <div v-if="staff.data[d] === 'off'" class="w-7 h-7 rounded-md flex items-center justify-center font-bold text-[10px] bg-[var(--sys-danger-soft)] text-[var(--sys-danger-text)] border border-[var(--sys-danger-border)] shadow-sm scale-95 hover:scale-105 transition-transform" title="Vắng mặt">V</div>
                   <div v-if="!staff.data[d]" class="w-1.5 h-1.5 rounded-full bg-[var(--sys-border-strong)] opacity-20"></div>
                </div>
                <div v-else class="text-[9px] font-bold text-[var(--sys-text-secondary)] uppercase tracking-tighter opacity-60">OFF</div>
              </td>

              <!-- Sticky Total Cell -->
              <td class="px-6 py-4 text-right whitespace-nowrap sticky right-0 z-10 bg-[var(--sys-bg-surface)] border-l border-[var(--sys-border-subtle)] group-hover:bg-[var(--sys-bg-hover)] shadow-sm">
                <div class="flex items-center justify-end gap-2">
                  <span class="text-sm font-bold text-[var(--sys-text-primary)]">{{ staff.total }}</span>
                  <span class="text-[10px] font-bold text-[var(--sys-text-disabled)] uppercase tracking-widest opacity-60">/ 22</span>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Footer: Refined Compact Spacing -->
      <div class="px-4 py-2.5 bg-[var(--sys-bg-page)]/30 border-t border-[var(--sys-border-subtle)] flex flex-col sm:flex-row justify-between items-center gap-3">
        <div class="flex items-center gap-5">
          <p class="text-[11px] text-[var(--sys-text-secondary)] font-bold flex items-center gap-4 uppercase tracking-widest">
            <span class="flex items-center gap-1.5"><span class="w-2 h-2 rounded bg-[var(--sys-success-solid)]"></span> ✔ ĐỦ CÔNG</span>
            <span class="flex items-center gap-1.5"><span class="w-2 h-2 rounded bg-[var(--sys-warning-solid)]"></span> M ĐI MUỘN</span>
            <span class="flex items-center gap-1.5"><span class="w-2 h-2 rounded bg-[var(--sys-danger-solid)]"></span> V VẮNG MẶT</span>
          </p>
        </div>
        <button @click="showHistoryModal = true" class="text-[11px] font-bold uppercase tracking-widest text-[var(--sys-brand-solid)] hover:opacity-80 transition-all flex items-center gap-2 group">
          Toàn bộ lịch sử chấm công
          <span class="material-symbols-rounded text-[18px] font-bold group-hover:translate-x-1 transition-transform">trending_flat</span>
        </button>
      </div>
    </div>

    <!-- History Modal -->
    <Teleport to="body">
      <Transition name="modal-fade">
        <div v-if="showHistoryModal" class="fixed inset-0 z-[10000] flex items-center justify-center p-4">
          <div class="fixed inset-0 w-screen h-screen bg-black/50 z-[9999]" @click="showHistoryModal = false"></div>
          <div class="relative z-[10000] bg-[var(--sys-bg-surface-elevated)] border border-[var(--sys-border-subtle)] w-full max-w-4xl max-h-[90vh] rounded-lg shadow-2xl overflow-hidden flex flex-col text-left">
            <!-- Modal Header -->
            <div class="px-6 py-4 border-b border-[var(--sys-border-subtle)] flex items-center justify-between bg-[var(--sys-bg-page)]/50">
              <div class="bg-transparent text-left flex items-center gap-3">
                <span class="material-symbols-rounded text-[var(--sys-brand-solid)] text-[24px]">history</span>
                <div>
                  <h3 class="text-sm font-bold text-[var(--sys-text-primary)] m-0 uppercase tracking-wide">Nhật ký chấm công (7 ngày gần nhất)</h3>
                  <p class="text-[11px] text-[var(--sys-text-secondary)] mt-0.5 font-medium uppercase tracking-widest opacity-80">Dữ liệu từ {{ sevenDaysAgoStr }} đến {{ todayStr }}</p>
                </div>
              </div>
              <button @click="showHistoryModal = false" class="w-8 h-8 flex items-center justify-center rounded-md hover:bg-[var(--sys-bg-hover)] transition-all text-[var(--sys-text-secondary)] shadow-sm border border-transparent hover:border-[var(--sys-border-strong)]">
                <span class="material-symbols-rounded text-xl">close</span>
              </button>
            </div>

            <!-- Modal Body -->
            <div class="flex-1 overflow-y-auto p-0 custom-scrollbar bg-[var(--sys-bg-surface)]">
              <table class="w-full text-left border-collapse">
                <thead class="bg-[var(--sys-bg-page)]/90 sticky top-0 backdrop-blur-sm z-10 shadow-sm border-b border-[var(--sys-border-subtle)]">
                  <tr>
                    <th class="px-6 py-3 text-[11px] font-bold text-[var(--sys-text-secondary)] uppercase tracking-widest">Thời gian</th>
                    <th class="px-6 py-3 text-[11px] font-bold text-[var(--sys-text-secondary)] uppercase tracking-widest">Họ Tên Nhân Sự</th>
                    <th class="px-6 py-3 text-[11px] font-bold text-[var(--sys-text-secondary)] uppercase tracking-widest">In / Out</th>
                    <th class="px-6 py-3 text-[11px] font-bold text-[var(--sys-text-secondary)] uppercase tracking-widest">Trạng thái</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-[var(--sys-border-subtle)]">
                  <tr v-for="(log, idx) in historyLogs" :key="idx" class="hover:bg-[var(--sys-bg-hover)] transition-colors">
                    <td class="px-6 py-3 text-[12px] font-bold text-[var(--sys-text-primary)]">{{ log.date }}</td>
                    <td class="px-6 py-3">
                      <p class="text-[13px] font-bold text-[var(--sys-text-primary)] leading-none m-0 mb-0.5">{{ log.name }}</p>
                      <p class="text-[10px] font-semibold text-[var(--sys-text-secondary)] uppercase tracking-wider m-0 opacity-70">{{ log.role }}</p>
                    </td>
                    <td class="px-6 py-3">
                      <div class="flex flex-col gap-1.5">
                        <div class="flex items-center gap-2">
                          <span class="text-[9px] uppercase font-black text-[var(--sys-text-disabled)] w-10">Lần 1:</span>
                          <span class="px-1.5 py-0.5 rounded text-[11px] font-bold border border-[var(--sys-border-strong)] text-[var(--sys-text-secondary)] bg-[var(--sys-bg-page)]">{{ log.in }}</span>
                          <span class="material-symbols-rounded text-[14px] text-[var(--sys-text-disabled)]">arrow_forward</span>
                          <span class="px-1.5 py-0.5 rounded text-[11px] font-bold border border-[var(--sys-border-strong)] text-[var(--sys-text-secondary)] bg-[var(--sys-bg-page)]">{{ log.out || '--:--' }}</span>
                        </div>
                        <div class="flex items-center gap-2">
                          <span class="text-[9px] uppercase font-black text-[var(--sys-text-disabled)] w-10">Lần 2:</span>
                          <span class="px-1.5 py-0.5 rounded text-[11px] font-bold border border-[var(--sys-border-strong)] text-[var(--sys-brand-solid)] bg-[var(--sys-brand-soft)] shadow-sm">{{ log.in2 || '--:--' }}</span>
                          <span class="material-symbols-rounded text-[14px] text-[var(--sys-text-disabled)]">arrow_forward</span>
                          <span class="px-1.5 py-0.5 rounded text-[11px] font-bold border border-[var(--sys-border-strong)] text-[var(--sys-brand-solid)] bg-[var(--sys-brand-soft)] shadow-sm">{{ log.out2 || '--:--' }}</span>
                        </div>
                      </div>
                    </td>
                    <td class="px-6 py-3 text-[10px] font-bold uppercase tracking-wide">
                      <span :class="getStatusClass(log.status)">
                        {{ log.statusLabel }}
                      </span>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- Modal Footer -->
            <div class="px-6 py-4 border-t border-[var(--sys-border-subtle)] bg-[var(--sys-bg-page)]/50 flex justify-end gap-3">
              <button @click="handleExportHistoryExcel" class="px-6 py-2 bg-[var(--sys-brand-soft)] text-[var(--sys-brand-solid)] border border-[var(--sys-brand-border)] rounded-md font-bold text-[11px] uppercase tracking-widest hover:bg-[var(--sys-brand-solid)] hover:text-white transition-all shadow-sm">
                Xuất Excel Tổng Hợp
              </button>
              <button @click="showHistoryModal = false" class="px-6 py-2 bg-white text-[var(--sys-text-secondary)] border border-[var(--sys-border-strong)] rounded-md font-bold text-[11px] hover:bg-[var(--sys-bg-hover)] shadow-sm uppercase tracking-widest transition-all">Đóng</button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>

    <!-- Export Preview Modal -->
    <Teleport to="body">
      <Transition name="modal-fade">
        <div v-if="showExportPreview" class="fixed inset-0 z-[10000] flex items-center justify-center p-4">
          <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" @click="showExportPreview = false"></div>
          <div class="relative bg-white border border-[var(--sys-border-subtle)] w-full max-w-6xl max-h-[90vh] rounded-xl shadow-2xl overflow-hidden flex flex-col text-left">
            <!-- Modal Header -->
            <div class="px-6 py-4 border-b border-[var(--sys-border-subtle)] flex items-center justify-between bg-[var(--sys-bg-page)]/50">
              <div class="flex items-center gap-3">
                <span class="material-symbols-rounded text-[var(--sys-brand-solid)]">table_view</span>
                <div>
                  <h3 class="text-sm font-bold text-[var(--sys-text-primary)] m-0 uppercase tracking-wide">Xem trước bản xuất Excel (Tháng {{ selectedMonth }})</h3>
                  <p class="text-[11px] text-[var(--sys-text-secondary)] mt-0.5 font-medium uppercase tracking-widest opacity-80">Kiểm tra thông tin chi tiết trước khi tải về</p>
                </div>
              </div>
              <button @click="showExportPreview = false" class="text-[var(--sys-text-secondary)] hover:text-[var(--sys-text-primary)] transition-colors">
                <span class="material-symbols-outlined">close</span>
              </button>
            </div>

            <!-- Modal Body -->
            <div class="flex-1 overflow-y-auto custom-scrollbar p-0">
              <table class="w-full text-left border-collapse border-spacing-0">
                <thead class="bg-[var(--sys-bg-page)] sticky top-0 z-10 shadow-sm border-b border-[var(--sys-border-subtle)]">
                  <tr class="h-10">
                    <th class="px-4 text-[10px] font-bold text-[var(--sys-text-secondary)] uppercase border-r border-[var(--sys-border-subtle)] w-12 text-center">STT</th>
                    <th class="px-4 text-[10px] font-bold text-[var(--sys-text-secondary)] uppercase border-r border-[var(--sys-border-subtle)]">Họ và Tên</th>
                    <th class="px-4 text-[10px] font-bold text-[var(--sys-text-secondary)] uppercase border-r border-[var(--sys-border-subtle)]">Phòng ban</th>
                    <th class="px-4 text-[10px] font-bold text-[var(--sys-text-secondary)] uppercase border-r border-[var(--sys-border-subtle)]">Ngày</th>
                    <th class="px-3 text-[10px] font-bold text-[var(--sys-text-secondary)] uppercase border-r border-[var(--sys-border-subtle)] text-center">Vào 1</th>
                    <th class="px-3 text-[10px] font-bold text-[var(--sys-text-secondary)] uppercase border-r border-[var(--sys-border-subtle)] text-center">Ra 1</th>
                    <th class="px-3 text-[10px] font-bold text-[var(--sys-text-secondary)] uppercase border-r border-[var(--sys-border-subtle)] text-center">Vào 2</th>
                    <th class="px-3 text-[10px] font-bold text-[var(--sys-text-secondary)] uppercase text-center">Ra 2</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-[var(--sys-border-subtle)]">
                  <tr v-for="(row, idx) in exportData" :key="idx" class="hover:bg-[var(--sys-bg-hover)] transition-colors h-10">
                    <td class="px-4 text-[11px] font-bold text-center border-r border-[var(--sys-border-subtle)]/50">{{ idx + 1 }}</td>
                    <td class="px-4 text-[12px] font-bold text-[var(--sys-text-primary)] border-r border-[var(--sys-border-subtle)]/50">{{ row.name }}</td>
                    <td class="px-4 text-[11px] font-medium text-[var(--sys-text-secondary)] border-r border-[var(--sys-border-subtle)]/50 whitespace-nowrap">{{ row.dept }}</td>
                    <td class="px-4 text-[11px] font-bold text-[var(--sys-brand-solid)] border-r border-[var(--sys-border-subtle)]/50">{{ row.date }}</td>
                    <td class="px-3 text-[11px] font-medium text-center border-r border-[var(--sys-border-subtle)]/50">{{ row.in1 }}</td>
                    <td class="px-3 text-[11px] font-medium text-center border-r border-[var(--sys-border-subtle)]/50">{{ row.out1 }}</td>
                    <td class="px-3 text-[11px] font-medium text-center border-r border-[var(--sys-border-subtle)]/50">{{ row.in2 }}</td>
                    <td class="px-3 text-[11px] font-medium text-center">{{ row.out2 }}</td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- Modal Footer -->
            <div class="px-6 py-4 border-t border-[var(--sys-border-subtle)] bg-[var(--sys-bg-page)]/30 flex justify-end gap-3">
              <button @click="showExportPreview = false" class="px-6 py-2 text-[11px] font-bold text-[var(--sys-text-secondary)] uppercase tracking-widest hover:bg-[var(--sys-bg-hover)] rounded-md transition-all border border-transparent hover:border-[var(--sys-border-strong)]">Đóng</button>
              <button @click="handleExportExcel" class="px-8 py-2 bg-[var(--sys-brand-solid)] text-white rounded-md font-bold text-[11px] uppercase tracking-widest hover:brightness-110 shadow-lg active:scale-95 transition-all flex items-center gap-2">
                <span class="material-symbols-outlined text-[18px]">download</span>
                Xác nhận Tải Excel (.csv)
              </button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import Dropdown from '@/components/Dropdown.vue'
import { mockEmployees, mockDB, mockDepartments } from '@/mock-data/index.js'

const showHistoryModal = ref(false)
const selectedMonth = ref('03')
const selectedYear = ref('2026')
const deptName = ref('Phòng ban')
const todayStr = ref('')
const sevenDaysAgoStr = ref('')

const showExportPreview = ref(false)
const exportData = ref([])
const fullAttendances = ref([])

const monthOptions = [
  { label: 'Tháng 01', value: '01' },
  { label: 'Tháng 02', value: '02' },
  { label: 'Tháng 03', value: '03' },
  { label: 'Tháng 04', value: '04' },
  { label: 'Tháng 05', value: '05' },
  { label: 'Tháng 06', value: '06' },
  { label: 'Tháng 07', value: '07' },
  { label: 'Tháng 08', value: '08' },
  { label: 'Tháng 09', value: '09' },
  { label: 'Tháng 10', value: '10' },
  { label: 'Tháng 11', value: '11' },
  { label: 'Tháng 12', value: '12' },
]

const yearOptions = [
  { label: '2026', value: '2026' },
  { label: '2025', value: '2025' },
]

const daysInMonth = computed(() => {
  return new Date(Number(selectedYear.value), Number(selectedMonth.value), 0).getDate();
});

const isWeekend = (day) => {
  const date = new Date(Number(selectedYear.value), Number(selectedMonth.value) - 1, day);
  const d = date.getDay();
  return d === 0 || d === 6;
};

// Trạng thái chấm công giả lập dựa trên attendance_id
const getAttStatus = (seed) => {
  const r = seed % 10
  if (r < 7) return 'on'
  if (r === 7) return 'late'
  return 'off'
}

const attendanceList = ref([])
const historyLogs = ref([])
const userDeptId = localStorage.getItem('userDeptId') || '1';

const loadData = async () => {
  try {
    const departmentResult = mockDepartments.find(d => Number(d.departmentId) === Number(userDeptId) || d.id === userDeptId);
    if (departmentResult) {
      deptName.value = departmentResult.departmentName || departmentResult.name || 'Phòng ban';
    }

    const employeesResult = mockEmployees.filter(e => {
      const dId = e.department?.departmentId || e.departmentId || e.deptId;
      return Number(dId) === Number(userDeptId);
    });
    let allAtts = [];
    try {
      const attRes = await fetch('http://localhost:3000/attendances');
      if (attRes.ok) allAtts = await attRes.json();
      else allAtts = mockDB.attendances || [];
    } catch (e) {
      allAtts = mockDB.attendances || [];
    }
    fullAttendances.value = allAtts;

    // Build the grid
    attendanceList.value = employeesResult.map(emp => {
      const empId = emp.employeeId || emp.id;
      const empAtts = allAtts.filter(a => a.employeeId === empId || a.employeeId === empId);
      const data = {};
      let totalDays = 0;

      empAtts.forEach(att => {
        const d = new Date(att.attendanceDate || att.date);
        if (!isNaN(d)) {
          const day = d.getDate();
          const status = att.status === 'ĐÃ_DUYỆT' ? 'on' : (att.status === 'ontime' ? 'on' : (att.status === 'late' ? 'late' : 'off'));
          data[day] = status;
          if (status !== 'off') totalDays += status === 'late' ? 0.5 : 1;
        }
      });

      // Fill mock data logic
      const now = new Date();
      const isCurrentMonth = now.getFullYear() === Number(selectedYear.value) && (now.getMonth() + 1) === Number(selectedMonth.value);
      const isPastMonth = Number(selectedYear.value) < now.getFullYear() || (Number(selectedYear.value) === now.getFullYear() && Number(selectedMonth.value) < (now.getMonth() + 1));
      const limitDay = isCurrentMonth ? now.getDate() : (isPastMonth ? (daysInMonth.value + 1) : 0);

      for (let d = 1; d < limitDay; d++) {
        if (!data[d] && !isWeekend(d)) {
          const seed = (parseInt(String(empId).replace(/\D/g, '')) * 31 + d) % 10;
          data[d] = seed < 8 ? 'on' : (seed === 8 ? 'late' : 'off');
          totalDays += data[d] === 'on' ? 1 : (data[d] === 'late' ? 0.5 : 0);
        }
      }

      return {
        id: empId,
        name: emp.fullName || emp.name,
        dept: deptName.value,
        total: totalDays.toFixed(1),
        data
      }
    });

    // Build history logs - Show last 7 days from current date
    const now = new Date();
    todayStr.value = now.toLocaleDateString('vi-VN');
    
    // Normalize to midnight for comparison
    const todayLimit = new Date(now);
    todayLimit.setHours(23, 59, 59, 999);
    
    const sevenDaysAgoMatch = new Date(now);
    sevenDaysAgoMatch.setDate(now.getDate() - 6); // 6 days before today = 7 days total inclusive
    sevenDaysAgoMatch.setHours(0, 0, 0, 0);
    
    sevenDaysAgoStr.value = sevenDaysAgoMatch.toLocaleDateString('vi-VN');

    // Build history logs for ALL department members (Last 7 Days)
    const history = [];
    // Iterate through last 7 days from today back
    for (let i = 0; i < 7; i++) {
      const d = new Date(now);
      d.setDate(now.getDate() - i);
      d.setHours(0, 0, 0, 0);
      
      const dateStr = d.toISOString().split('T')[0];
      const showDay = d.toLocaleDateString('vi-VN');

      employeesResult.forEach(emp => {
        const empId = emp.employeeId || emp.id;
        const att = allAtts.find(a => (a.employeeId === empId) && (a.attendanceDate === dateStr || a.date === dateStr));
        
        const formatTime = (time) => {
          if (!time) return '--:--';
          if (time.includes('T')) return time.split('T')[1].substring(0, 5);
          if (time.includes(' ')) return time.split(' ')[1].substring(0, 5);
          return time.substring(0, 5);
        };

        if (att) {
          const statusConverted = att.status === 'ĐÃ_DUYỆT' ? 'ontime' : (att.status || 'ontime');
          history.push({
            date: dateStr,
            name: emp.fullName || emp.name,
            role: deptName.value,
            in: formatTime(att.checkInTime || att.checkIn1),
            out: formatTime(att.checkOutTime || att.checkOut1),
            in2: formatTime(att.checkIn2),
            out2: formatTime(att.checkOut2),
            status: statusConverted,
            statusLabel: statusConverted === 'ontime' ? 'Đúng giờ' : (statusConverted === 'late' ? 'Đi muộn' : 'Vắng mặt')
          });
        } else if (!isWeekend(d.getDate())) {
          // If no live data, use the mock logic to show a complete 7-day view
          const dayNum = d.getDate();
          const seed = (parseInt(String(empId).replace(/\D/g, '')) * 31 + dayNum) % 10;
          const status = seed < 8 ? 'ontime' : (seed === 8 ? 'late' : 'off');
          
          if (status !== 'off') {
            history.push({
              date: dateStr,
              name: emp.fullName || emp.name,
              role: deptName.value,
              in: '08:00',
              out: '12:00',
              in2: '13:00',
              out2: '17:00',
              status: status,
              statusLabel: status === 'ontime' ? 'Đúng giờ' : 'Đi muộn'
            });
          }
        }
      });
    }
    historyLogs.value = history;
  } catch (error) {
    console.error('Lỗi khi tải dữ liệu chuyên cần:', error);
  }
}

const openExportPreview = () => {
  // Build export list for all employees in current month
  const data = [];
  const employees = attendanceList.value; // List of team members
  
  // Format helpers
  const getFTime = (time) => {
    if (!time) return '--';
    if (time.includes('T')) return time.split('T')[1].substring(0, 5);
    if (time.includes(' ')) return time.split(' ')[1].substring(0, 5);
    return time.substring(0, 5);
  };

  employees.forEach(emp => {
    // We iterate through all days of the selected month
    for (let d = 1; d <= daysInMonth; d++) {
      if (isWeekend(d)) continue;

      const dateStr = `${selectedYear.value}-${selectedMonth.value}-${String(d).padStart(2, '0')}`;
      const att = fullAttendances.value.find(a => (a.employeeId === emp.id) && (a.attendanceDate === dateStr || a.date === dateStr));
      
      if (att) {
         data.push({
           name: emp.name,
           dept: emp.dept,
           date: dateStr,
           in1: getFTime(att.checkIn1 || att.checkInTime),
           out1: getFTime(att.checkOut1 || att.checkOutTime),
           in2: getFTime(att.checkIn2),
           out2: getFTime(att.checkOut2)
         });
      } else if (emp.data[d] && emp.data[d] !== 'off') {
         // Mock row for complete table
         data.push({
           name: emp.name,
           dept: emp.dept,
           date: dateStr,
           in1: '08:00',
           out1: '12:00',
           in2: '13:00',
           out2: '17:00'
         });
      }
    }
  });

  exportData.value = data;
  showExportPreview.value = true;
};

const handleExportExcel = () => {
  // Simple CSV generation that Excel likes (with BOM for UTF-8)
  const headers = ['STT', 'Họ và Tên', 'Phòng Ban', 'Ngày', 'Vào 1', 'Ra 1', 'Vào 2', 'Ra 2'];
  let csvContent = '\uFEFF' + headers.join(',') + '\n';

  exportData.value.forEach((row, idx) => {
    const rowValues = [
      idx + 1,
      `"${row.name}"`,
      `"${row.dept}"`,
      row.date,
      row.in1,
      row.out1,
      row.in2,
      row.out2
    ];
    csvContent += rowValues.join(',') + '\n';
  });

  const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
  const link = document.createElement('a');
  const url = URL.createObjectURL(blob);
  link.setAttribute('href', url);
  const filename = exportData.value.length < 50 
    ? `Lich_Su_7_Ngay_${deptName.value}.csv` 
    : `Bang_Cong_${deptName.value}_Thang_${selectedMonth.value}.csv`;

  link.setAttribute('download', filename);
  link.style.visibility = 'hidden';
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
  
  showExportPreview.value = false;
};

const handleExportHistoryExcel = () => {
  // Instead of immediate download, we show PREVIEW first
  const data = historyLogs.value.map(row => ({
    name: row.name,
    dept: row.role, // role is dept name here
    date: row.date,
    in1: row.in,
    out1: row.out,
    in2: row.in2 || '--',
    out2: row.out2 || '--'
  }));
  
  exportData.value = data;
  showExportPreview.value = true;
  // We don't need to change much else as handleExportExcel uses exportData
};

const getStatusClass = (status) => {
  if (status === 'ontime') return 'text-[var(--sys-success-text)] bg-[var(--sys-success-soft)] px-2 py-0.5 rounded border border-[var(--sys-success-border)]';
  if (status === 'late') return 'text-[var(--sys-warning-text)] bg-[var(--sys-warning-soft)] px-2 py-0.5 rounded border border-[var(--sys-warning-border)]';
  if (status === 'off') return 'text-[var(--sys-danger-text)] bg-[var(--sys-danger-soft)] px-2 py-0.5 rounded border border-[var(--sys-danger-border)]';
  return 'text-[var(--sys-text-secondary)] bg-[var(--sys-bg-hover)] px-2 py-0.5 rounded border border-[var(--sys-border-strong)]';
}

let pollInterval = null;

watch([selectedMonth, selectedYear], () => {
  loadData();
});

onMounted(() => {
  loadData();
  pollInterval = setInterval(loadData, 10000); // Poll every 10 seconds
});

onUnmounted(() => {
  if (pollInterval) clearInterval(pollInterval);
});
</script>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
  width: 5px;
  height: 6px;
}
.custom-scrollbar::-webkit-scrollbar-track {
  background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
  background: var(--sys-border-subtle);
  border-radius: 10px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
  background: var(--sys-brand-solid);
}

/* Enhancing sticky shadows for harmony */
.sticky {
  transition: background-color 0.2s ease;
}

/* Sub-pixel text rendering for premium feel */
* {
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
}

.modal-fade-enter-active,
.modal-fade-leave-active {
  transition: opacity 0.2s ease;
}

.modal-fade-enter-from,
.modal-fade-leave-to {
  opacity: 0;
}
</style>
