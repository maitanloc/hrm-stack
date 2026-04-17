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
              <span class="text-[11px] font-bold text-[var(--sys-text-secondary)] uppercase tracking-widest">Nghỉ / Vắng</span>
            </div>
          </div>
        </div>
      </div>

      <div class="px-4 py-3 border-b border-[var(--sys-border-subtle)] bg-[var(--sys-bg-surface)]">
        <div class="grid grid-cols-2 xl:grid-cols-5 gap-3">
          <div v-for="metric in overviewCards" :key="metric.label" class="rounded-md border px-4 py-3 shadow-sm" :class="metric.cardClass">
            <p class="m-0 text-[10px] font-bold uppercase tracking-[0.14em] opacity-70">{{ metric.label }}</p>
            <p class="m-0 mt-2 text-[20px] font-bold tracking-tight">{{ metric.value }}</p>
            <p class="m-0 mt-1 text-[11px] font-medium opacity-80">{{ metric.note }}</p>
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
                   <div v-if="staff.data[d]?.kind === 'on'" class="w-7 h-7 rounded-md flex items-center justify-center font-bold text-[10px] bg-[var(--sys-success-soft)] text-[var(--sys-success-text)] border border-[var(--sys-success-border)] shadow-sm scale-95 hover:scale-105 transition-transform" :title="staff.data[d]?.title || 'Đi làm đúng ca'">✔</div>
                   <div v-if="staff.data[d]?.kind === 'late'" class="w-7 h-7 rounded-md flex items-center justify-center font-bold text-[10px] bg-[var(--sys-warning-soft)] text-[var(--sys-warning-text)] border border-[var(--sys-warning-border)] shadow-sm scale-95 hover:scale-105 transition-transform" :title="staff.data[d]?.title || 'Đi muộn / về sớm'">M</div>
                   <div v-if="staff.data[d]?.kind === 'off'" class="w-10 h-8 rounded-md flex flex-col items-center justify-center font-bold bg-[var(--sys-danger-soft)] text-[var(--sys-danger-text)] border border-[var(--sys-danger-border)] shadow-sm scale-95 hover:scale-105 transition-transform leading-none" :title="staff.data[d]?.title || 'Off'">
                     <span class="text-[8px] tracking-[0.12em]">OFF</span>
                     <span class="text-[7px] font-black uppercase opacity-80 mt-[1px]">{{ staff.data[d]?.reasonShort || 'OFF' }}</span>
                   </div>
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
            <span class="flex items-center gap-1.5"><span class="w-2 h-2 rounded bg-[var(--sys-danger-solid)]"></span> OFF NGHỈ / VẮNG</span>
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
import { useCurrentUser } from '@/composables/useCurrentUser'
import { fetchTeamSchedule } from '@/services/workforceApi.js'

const showHistoryModal = ref(false)
const now = new Date()
const selectedMonth = ref(String(now.getMonth() + 1).padStart(2, '0'))
const selectedYear = ref(String(now.getFullYear()))
const deptName = ref('Phòng ban')
const todayStr = ref('')
const sevenDaysAgoStr = ref('')

const showExportPreview = ref(false)
const exportData = ref([])
const fullAttendances = ref([])
const workforceRows = ref([])
const { deptId, deptName: currentDeptName } = useCurrentUser()

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

const attendanceList = ref([])
const historyLogs = ref([])

const overviewCards = computed(() => {
  const summary = {
    totalEmployees: new Set(),
    onTime: 0,
    attention: 0,
    absent: 0,
    leaveLike: 0,
    unassigned: 0,
  }

  workforceRows.value.forEach((row) => {
    const employeeId = Number(row?.employee?.employee_id || 0)
    if (employeeId) summary.totalEmployees.add(employeeId)

    const status = String(row?.attendance_result?.primary_status_code || '').toUpperCase()
    if (['P', 'OT', 'NS'].includes(status)) {
      summary.onTime += 1
    } else if (['L', 'EO'].includes(status)) {
      summary.attention += 1
    } else if (status === 'AB') {
      summary.absent += 1
    } else if (['AL', 'SL', 'UNP', 'H', 'REMOTE', 'CT'].includes(status)) {
      summary.leaveLike += 1
    } else if (status === 'UNASSIGNED') {
      summary.unassigned += 1
    }
  })

  return [
    {
      label: 'Nhân sự theo dõi',
      value: summary.totalEmployees.size,
      note: 'Tổng nhân sự trong phạm vi phòng ban đang xem',
      cardClass: 'bg-[var(--sys-brand-soft)] text-[var(--sys-brand-solid)] border-[var(--sys-brand-border)]',
    },
    {
      label: 'Đúng ca / làm việc',
      value: summary.onTime,
      note: 'Bao gồm đúng giờ, OT, ca đêm',
      cardClass: 'bg-[var(--sys-success-soft)] text-[var(--sys-success-text)] border-[var(--sys-success-border)]',
    },
    {
      label: 'Cần chú ý',
      value: summary.attention,
      note: 'Đi muộn hoặc về sớm theo attendance result',
      cardClass: 'bg-[var(--sys-warning-soft)] text-[var(--sys-warning-text)] border-[var(--sys-warning-border)]',
    },
    {
      label: 'Nghỉ / công tác',
      value: summary.leaveLike,
      note: 'Leave, holiday, remote hoặc công tác đã được áp lịch',
      cardClass: 'bg-[var(--sys-info-soft)] text-[var(--sys-info-text)] border-[var(--sys-info-border)]',
    },
    {
      label: 'Vắng / chưa phân ca',
      value: summary.absent + summary.unassigned,
      note: `AB: ${summary.absent} · Chưa phân ca: ${summary.unassigned}`,
      cardClass: 'bg-[var(--sys-danger-soft)] text-[var(--sys-danger-text)] border-[var(--sys-danger-border)]',
    },
  ]
})

const toIsoDate = (date) => {
  const year = date.getFullYear()
  const month = String(date.getMonth() + 1).padStart(2, '0')
  const day = String(date.getDate()).padStart(2, '0')
  return `${year}-${month}-${day}`
}

const formatDateVi = (value) => {
  if (!value) return '--/--/----'
  const date = new Date(value)
  if (Number.isNaN(date.getTime())) return value
  return date.toLocaleDateString('vi-VN')
}

const getDayKey = (dateString) => {
  const date = new Date(dateString)
  if (Number.isNaN(date.getTime())) return null
  return date.getDate()
}

const buildGridCell = (row) => {
  const attendanceResult = row?.attendance_result || {}
  const status = String(attendanceResult?.primary_status_code || '').toUpperCase()
  const holidayName = String(row?.holiday?.holiday_name || '').trim()
  const leaveName = String(row?.leave?.leave_type_name || '').trim()

  if (['L', 'EO'].includes(status)) {
    return {
      kind: 'late',
      title: `${getStatusLabel(attendanceResult)}${attendanceResult?.late_minutes ? ` · Muộn ${attendanceResult.late_minutes} phút` : ''}${attendanceResult?.early_out_minutes ? ` · Sớm ${attendanceResult.early_out_minutes} phút` : ''}`,
    }
  }

  if (status === 'H') {
    return {
      kind: 'off',
      reasonShort: 'LỄ',
      title: `OFF · ${holidayName || 'Ngày nghỉ lễ hệ thống'}`,
    }
  }

  if (['AL', 'SL', 'UNP'].includes(status)) {
    return {
      kind: 'off',
      reasonShort: status === 'AL' ? 'PHÉP' : status === 'SL' ? 'ỐM' : 'K LƯƠNG',
      title: `OFF · ${leaveName || getStatusLabel(attendanceResult)}`,
    }
  }

  if (status === 'AB') {
    return {
      kind: 'off',
      reasonShort: 'VẮNG',
      title: 'OFF · Vắng mặt chưa có đơn hợp lệ',
    }
  }

  if (status === 'UNASSIGNED') {
    return {
      kind: 'off',
      reasonShort: 'CHƯA CA',
      title: 'OFF · Chưa được phân ca làm việc',
    }
  }

  if (['P', 'OT', 'NS', 'REMOTE', 'CT'].includes(status)) {
    return {
      kind: 'on',
      title: getStatusLabel(attendanceResult),
    }
  }

  return null
}

const getStatusLabel = (attendanceResult) => {
  const status = String(attendanceResult?.primary_status_code || '').toUpperCase()
  const labels = {
    P: 'Đúng giờ',
    L: 'Đi muộn',
    EO: 'Về sớm',
    OT: 'Làm thêm giờ',
    NS: 'Ca đêm',
    AL: 'Nghỉ phép năm',
    SL: 'Nghỉ ốm',
    UNP: 'Nghỉ không lương',
    AB: 'Vắng mặt',
    H: 'Ngày lễ',
    CT: 'Công tác',
    REMOTE: 'Làm việc từ xa',
    UNASSIGNED: 'Chưa phân ca',
  }
  return labels[status] || 'Đang cập nhật'
}

const getSourceLabel = (shift) => {
  const source = String(shift?.source || '').toLowerCase()
  if (source === 'override') return 'Ca chỉnh riêng'
  if (source === 'assignment') return 'Ca mặc định cá nhân'
  if (source === 'department_schedule') return 'Lịch phòng ban'
  return 'Ngữ cảnh hệ thống'
}

const loadData = async () => {
  try {
    const resolvedDeptId = Number(deptId.value || 0)
    const fromDate = `${selectedYear.value}-${selectedMonth.value}-01`
    const toDate = `${selectedYear.value}-${selectedMonth.value}-${String(daysInMonth.value).padStart(2, '0')}`

    deptName.value = currentDeptName.value || 'Phòng ban'

    const payload = await fetchTeamSchedule({
      fromDate,
      toDate,
      departmentId: resolvedDeptId || undefined,
    })

    const rows = Array.isArray(payload) ? payload : []
    workforceRows.value = rows

    const employeeMap = new Map()
    rows.forEach((row) => {
      const employee = row?.employee || {}
      const employeeIdValue = Number(employee.employee_id || 0)
      if (!employeeIdValue) return

      const existing = employeeMap.get(employeeIdValue) || {
        id: employeeIdValue,
        name: employee.full_name || `Nhân sự #${employeeIdValue}`,
        dept: employee.department_name || deptName.value,
        total: 0,
        data: {},
      }

      const dayKey = getDayKey(row?.work_date)
      const gridCell = buildGridCell(row)
      if (dayKey) {
        existing.data[dayKey] = gridCell
      }

      if (gridCell?.kind === 'on') existing.total += 1
      if (gridCell?.kind === 'late') existing.total += 0.5

      employeeMap.set(employeeIdValue, existing)
    })

    attendanceList.value = Array.from(employeeMap.values()).map((item) => ({
      ...item,
      total: item.total.toFixed(1),
    }))

    fullAttendances.value = rows

    todayStr.value = formatDateVi(now)
    const sevenDaysAgo = new Date(now)
    sevenDaysAgo.setDate(now.getDate() - 6)
    sevenDaysAgoStr.value = formatDateVi(sevenDaysAgo)

    historyLogs.value = rows
      .filter((row) => {
        const date = new Date(row?.work_date || '')
        if (Number.isNaN(date.getTime())) return false
        date.setHours(0, 0, 0, 0)
        return date >= new Date(sevenDaysAgo.getFullYear(), sevenDaysAgo.getMonth(), sevenDaysAgo.getDate())
      })
      .map((row) => {
        const employee = row?.employee || {}
        const shift = row?.shift || {}
        const attendanceResult = row?.attendance_result || {}
        const gridCell = buildGridCell(row)
        return {
          date: formatDateVi(row?.work_date || ''),
          name: employee.full_name || `Nhân sự #${employee.employee_id || '--'}`,
          role: employee.department_name || deptName.value,
          in: shift?.start_time || '--:--',
          out: shift?.end_time || '--:--',
          in2: row?.leave?.leave_type_name || (row?.holiday?.holiday_name ? 'Ngày lễ' : row?.remote ? 'Remote' : row?.business_trip ? 'Công tác' : getSourceLabel(shift)),
          out2: attendanceResult?.overtime_minutes
            ? `${attendanceResult.overtime_minutes}p OT`
            : attendanceResult?.late_minutes
              ? `${attendanceResult.late_minutes}p muộn`
              : attendanceResult?.early_out_minutes
                ? `${attendanceResult.early_out_minutes}p sớm`
                : (attendanceResult?.primary_status_code === 'UNASSIGNED' ? 'Chưa phân ca' : '--:--'),
          status: gridCell?.kind || '',
          statusLabel: getStatusLabel(attendanceResult),
        }
      })
      .sort((left, right) => String(right.date).localeCompare(String(left.date)))
  } catch (error) {
    console.error('Lỗi khi tải dữ liệu chuyên cần:', error);
    attendanceList.value = []
    historyLogs.value = []
    fullAttendances.value = []
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
    for (let d = 1; d <= daysInMonth.value; d++) {
      if (isWeekend(d)) continue;

      const dateStr = `${selectedYear.value}-${selectedMonth.value}-${String(d).padStart(2, '0')}`;
      const att = fullAttendances.value.find((row) => Number(row?.employee?.employee_id) === Number(emp.id) && row?.work_date === dateStr);
      
      if (att) {
         const shift = att.shift || {}
         data.push({
           name: emp.name,
           dept: emp.dept,
           date: dateStr,
           in1: getFTime(shift.start_time || ''),
           out1: getFTime(shift.end_time || ''),
           in2: '--',
           out2: att?.attendance_result?.overtime_minutes ? `${att.attendance_result.overtime_minutes}p OT` : '--'
         });
      } else if (emp.data[d]?.kind && emp.data[d]?.kind !== 'off') {
         data.push({
           name: emp.name,
           dept: emp.dept,
           date: dateStr,
           in1: '--',
           out1: '--',
           in2: '--',
           out2: '--'
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
  if (status === 'ontime' || status === 'on') return 'text-[var(--sys-success-text)] bg-[var(--sys-success-soft)] px-2 py-0.5 rounded border border-[var(--sys-success-border)]';
  if (status === 'late') return 'text-[var(--sys-warning-text)] bg-[var(--sys-warning-soft)] px-2 py-0.5 rounded border border-[var(--sys-warning-border)]';
  if (status === 'off') return 'text-[var(--sys-danger-text)] bg-[var(--sys-danger-soft)] px-2 py-0.5 rounded border border-[var(--sys-danger-border)]';
  return 'text-[var(--sys-text-secondary)] bg-[var(--sys-bg-hover)] px-2 py-0.5 rounded border border-[var(--sys-border-strong)]';
}

let pollInterval = null;
const TEAM_ATTENDANCE_POLL_INTERVAL_MS = 60000;

watch([selectedMonth, selectedYear], () => {
  loadData();
});

onMounted(() => {
  loadData();
  pollInterval = setInterval(() => {
    if (typeof document !== 'undefined' && document.hidden) return;
    loadData();
  }, TEAM_ATTENDANCE_POLL_INTERVAL_MS);
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
