<template>
  <div class="space-y-6 pb-8">
    <!-- Header Area -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 bg-transparent text-left">
      <div class="bg-transparent text-left">
        <h1 class="text-xl font-semibold text-[var(--sys-text-primary)] mb-1">Điều phối Nghỉ phép & Vắng mặt</h1>
        <p class="text-sm text-[var(--sys-text-secondary)]">Quản lý định mức, phê duyệt yêu cầu vắng mặt và theo dõi mật độ nguồn lực.</p>
      </div>
      <div class="bg-transparent text-right hidden xl:block">
        <div class="inline-flex items-center gap-2 bg-[var(--sys-bg-page)] px-4 py-2 rounded-md border border-[var(--sys-border-subtle)] font-semibold text-[13px] text-[var(--sys-brand-solid)] shadow-sm">
          <span class="w-2 h-2 rounded-full bg-[var(--sys-brand-solid)] animate-pulse"></span>
          Hệ thống Quản trị Nhân sự
        </div>
      </div>
    </div>

    <!-- Stats Row -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
      <div v-for="(stat, idx) in leaveStats" :key="idx" 
        class="bg-[var(--sys-bg-surface)] p-4 rounded-lg border border-[var(--sys-border-subtle)] shadow-sm hover:border-[var(--sys-brand-solid)] transition-all flex items-center gap-4">
        <div class="w-12 h-12 rounded-md flex items-center justify-center border border-white/10" :class="stat.bgClass">
          <span class="material-symbols-outlined text-[24px]">{{ stat.icon }}</span>
        </div>
        <div class="bg-transparent flex flex-col flex-1">
          <p class="text-[12px] font-medium text-[var(--sys-text-secondary)] uppercase tracking-wide mb-0.5">{{ stat.label }}</p>
          <div class="flex items-baseline gap-1 bg-transparent">
            <h4 class="text-xl font-bold text-[var(--sys-text-primary)] m-0 leading-tight">{{ stat.value }}</h4>
            <span class="text-[11px] font-semibold text-[var(--sys-text-disabled)] uppercase">{{ stat.unit.split(' ')[0] }}</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Main Content Layout -->
    <div class="flex flex-col xl:flex-row gap-6 bg-transparent">
      <!-- Left: Requests List -->
      <div :class="activeRequestId ? 'xl:w-2/3' : 'w-full'" class="transition-all duration-300 bg-transparent text-left flex flex-col gap-4">
        <div class="bg-[var(--sys-bg-surface)] rounded-lg border border-[var(--sys-border-subtle)] shadow-sm overflow-hidden flex flex-col min-h-[600px]">
          <!-- Filters & Tabs Toolbar -->
          <div class="px-4 py-3 border-b border-[var(--sys-border-subtle)] bg-[var(--sys-bg-page)]/50 space-y-4">
            <!-- First Row: Ultra-Compact Tabs + Search -->
            <div class="flex flex-row items-center justify-between gap-3 bg-transparent w-full">
              <!-- Tabs Container - Ultra Compact -->
              <div class="flex items-center gap-0.5 bg-white p-0.5 rounded-md border border-[var(--sys-border-subtle)] shadow-sm overflow-x-auto no-scrollbar shrink-0 max-w-[60%]">
                <button 
                  v-for="tab in tabOptions" :key="tab.value"
                  @click="activeTab = tab.value"
                  :class="[
                    'px-3.5 py-1.5 rounded-md text-[13px] font-semibold transition-all whitespace-nowrap flex items-center gap-1.5',
                    activeTab === tab.value ? 'bg-[var(--sys-brand-soft)] text-[var(--sys-brand-solid)] shadow-sm' : 'text-[var(--sys-text-secondary)] hover:bg-[var(--sys-bg-hover)]'
                  ]"
                >
                  {{ tab.label }}
                  <span class="px-1.5 py-0.5 rounded-md bg-[var(--sys-danger-solid)] text-white text-[10px] font-bold" v-if="tab.count > 0">{{ tab.count }}</span>
                </button>
              </div>

              <!-- Search Bar - Maximum Visibility -->
              <div class="relative flex-1 max-w-sm group">
                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-[18px] text-[var(--sys-brand-solid)]">search</span>
                <input v-model="searchQuery" type="text" placeholder="Tìm kiếm..." class="w-full h-10 pl-10 pr-4 bg-white border border-[var(--sys-border-strong)] rounded-md text-[13px] font-medium text-[var(--sys-text-primary)] focus:border-[var(--sys-brand-solid)] outline-none transition-all placeholder:text-[var(--sys-text-disabled)] shadow-sm">
              </div>
            </div>

            <!-- Second Row: Balanced Dropdowns -->
            <div class="flex flex-col md:flex-row items-center gap-4">
              <div class="flex-1 w-full">
                <Dropdown v-model="filterDept" :options="deptOptions" class="w-full h-10" />
              </div>
              <div class="flex-1 w-full">
                <Dropdown v-model="filterRange" :options="rangeOptions" class="w-full h-10" />
              </div>
            </div>
          </div>

          <!-- Table Content -->
          <div class="overflow-x-auto flex-grow bg-transparent custom-scrollbar">
            <table class="w-full text-left border-collapse">
              <thead class="bg-[var(--sys-bg-page)]">
                <tr>
                  <th class="px-4 py-3 text-[12px] font-semibold text-[var(--sys-text-secondary)] border-b border-[var(--sys-border-subtle)] uppercase tracking-wider">Nhân sự thụ hưởng</th>
                  <th class="px-4 py-3 text-[12px] font-semibold text-[var(--sys-text-secondary)] border-b border-[var(--sys-border-subtle)] uppercase tracking-wider">Đơn vị</th>
                  <th class="px-4 py-3 text-[12px] font-semibold text-[var(--sys-text-secondary)] border-b border-[var(--sys-border-subtle)] uppercase tracking-wider">Loại hình vắng</th>
                  <th class="px-4 py-3 text-[12px] font-semibold text-[var(--sys-text-secondary)] border-b border-[var(--sys-border-subtle)] uppercase tracking-wider text-center">Thời gian</th>
                  <th class="px-4 py-3 text-[12px] font-semibold text-[var(--sys-text-secondary)] border-b border-[var(--sys-border-subtle)] uppercase tracking-wider text-center">Số ngày</th>
                  <th class="px-4 py-3 text-[12px] font-semibold text-[var(--sys-text-secondary)] border-b border-[var(--sys-border-subtle)] uppercase tracking-wider text-right">Trạng thái</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-[var(--sys-border-subtle)]">
                <tr v-for="req in paginatedRequests" :key="req.id" 
                  @click="activeRequestId = req.id" 
                  @dblclick="handleDeleteRequest(req)"
                  class="group cursor-pointer hover:bg-[var(--sys-bg-hover)] transition-all" 
                  :class="activeRequestId === req.id ? 'bg-[var(--sys-brand-soft)]/30' : ''"
                  title="Nhấn đúp để xóa đơn (chỉ đơn chưa duyệt)"
                >
                  <td class="px-4 py-3 whitespace-nowrap bg-transparent">
                    <div class="flex flex-col bg-transparent">
                      <span class="text-[13px] font-semibold text-[var(--sys-text-primary)] transition-colors line-clamp-1 max-w-[200px]">{{ req.name }}</span>
                      <span class="text-[11px] text-[var(--sys-brand-solid)] font-bold opacity-60">#{{ req.msnv }}</span>
                    </div>
                  </td>
                  <td class="px-4 py-3 whitespace-nowrap bg-transparent">
                    <span class="text-[13px] text-[var(--sys-text-secondary)]">{{ req.department }}</span>
                  </td>
                  <td class="px-4 py-3 whitespace-nowrap bg-transparent">
                    <span :class="['px-2 py-0.5 rounded-md text-[11px] font-semibold border transition-all uppercase tracking-wide', getLeaveTypeClass(req.type)]">{{ req.type }}</span>
                  </td>
                  <td class="px-4 py-3 whitespace-nowrap bg-transparent text-center">
                    <span class="text-[13px] text-[var(--sys-text-secondary)]">{{ req.dateRange }}</span>
                  </td>
                  <td class="px-4 py-3 whitespace-nowrap bg-transparent text-center">
                    <span class="text-[13px] font-bold text-[var(--sys-brand-solid)]">{{ req.days }}</span>
                  </td>
                  <td class="px-4 py-3 whitespace-nowrap bg-transparent text-right">
                    <span :class="[
                      'px-2 py-0.5 rounded-md text-[11px] font-semibold inline-flex items-center gap-1.5 border transition-all uppercase tracking-wide',
                      getStatusBadgeClass(req.status)
                    ]">
                      <span class="w-1.5 h-1.5 rounded-full shrink-0" :class="getStatusDotClass(req.status)"></span>
                      {{ req.statusText }}
                    </span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Footer -->
          <div class="px-4 py-3 bg-[var(--sys-bg-page)] border-t border-[var(--sys-border-subtle)] flex justify-between items-center text-[12px] font-medium text-[var(--sys-text-secondary)]">
            <span>Hiển thị {{ paginatedRequests.length }} / {{ filteredRequests.length }} hồ sơ biến động</span>
            <div class="flex items-center gap-1.5">
              <button 
                @click="currentPage > 1 ? currentPage-- : null"
                :disabled="currentPage === 1"
                :class="['w-8 h-8 flex items-center justify-center rounded-md border border-[var(--sys-border-subtle)] transition-all', currentPage === 1 ? 'opacity-30 cursor-not-allowed' : 'bg-white hover:text-[var(--sys-brand-solid)]']"
              >
                <span class="material-symbols-outlined text-[18px]">chevron_left</span>
              </button>
              
              <div class="flex items-center gap-1 px-2">
                <span class="text-[var(--sys-text-primary)] font-bold">{{ currentPage }}</span>
                <span class="opacity-40">/</span>
                <span class="opacity-60">{{ totalPages }}</span>
              </div>

              <button 
                @click="currentPage < totalPages ? currentPage++ : null"
                :disabled="currentPage === totalPages"
                :class="['w-8 h-8 flex items-center justify-center rounded-md border border-[var(--sys-border-subtle)] transition-all', currentPage === totalPages ? 'opacity-30 cursor-not-allowed' : 'bg-white hover:text-[var(--sys-brand-solid)]']"
              >
                <span class="material-symbols-outlined text-[18px]">chevron_right</span>
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Right: Detail Panel -->
      <div v-show="activeRequestId" class="xl:w-1/3 transition-all duration-300 bg-transparent text-left">
        <div class="bg-[var(--sys-bg-surface-elevated)] border border-[var(--sys-border-subtle)] shadow-lg rounded-lg overflow-hidden flex flex-col h-full sticky top-4" v-if="activeRequest">
          <!-- Panel Header -->
          <div class="px-6 py-4 border-b border-[var(--sys-border-subtle)] flex justify-between items-center bg-[var(--sys-bg-surface)]">
            <div class="bg-transparent text-left flex flex-col">
              <h3 class="text-sm font-semibold text-[var(--sys-text-primary)] m-0 uppercase tracking-tight">Hồ sơ</h3>
              <p class="text-[12px] text-[var(--sys-text-secondary)] mt-0.5">Mã nghiệp vụ: #LV-{{ activeRequest.id }}</p>
            </div>
            <button @click="activeRequestId = null" class="w-8 h-8 flex items-center justify-center rounded-md hover:bg-[var(--sys-bg-hover)] transition-all text-[var(--sys-text-secondary)]">
              <span class="material-symbols-outlined text-xl">close</span>
            </button>
          </div>
          
          <!-- Panel Body -->
          <div class="p-6 flex-grow flex flex-col gap-6 custom-scrollbar overflow-y-auto bg-transparent">
            <!-- Profile Info -->
            <div class="flex items-center gap-4 p-4 bg-[var(--sys-bg-page)] rounded-lg border border-[var(--sys-border-subtle)]">
              <div class="w-12 h-12 rounded-lg bg-[var(--sys-brand-solid)] text-white flex items-center justify-center shadow-sm">
                <span class="material-symbols-outlined text-[28px]">person_search</span>
              </div>
              <div class="flex flex-col bg-transparent text-left flex-1">
                <h4 class="text-base font-bold text-[var(--sys-text-primary)] m-0">{{ activeRequest.name }}</h4>
                <p class="text-[12px] text-[var(--sys-text-secondary)] font-medium mt-0.5">{{ activeRequest.role }} • #{{ activeRequest.msnv }}</p>
              </div>
            </div>

            <!-- Meta Data Grid -->
            <div class="grid grid-cols-2 gap-4 bg-transparent border-none">
              <div class="p-3 bg-[var(--sys-bg-page)] rounded-md border border-[var(--sys-border-subtle)]">
                <p class="text-[11px] font-semibold text-[var(--sys-text-secondary)] uppercase tracking-wider mb-1">Loại vắng mặt</p>
                <p class="text-[13px] font-bold text-[var(--sys-text-primary)] m-0">{{ activeRequest.typeDetail }}</p>
              </div>
              <div class="p-3 bg-[var(--sys-bg-page)] rounded-md border border-[var(--sys-border-subtle)]">
                <p class="text-[11px] font-semibold text-[var(--sys-text-secondary)] uppercase tracking-wider mb-1">Số ngày</p>
                <p class="text-[13px] font-bold text-[var(--sys-brand-solid)] m-0">{{ activeRequest.days }} NGÀY</p>
              </div>
            </div>

            <div class="space-y-1.5 bg-transparent border-none">
              <label class="text-[12px] font-semibold text-[var(--sys-text-secondary)] uppercase tracking-wider block ml-1">Thời gian hiệu lực</label>
              <div class="flex items-center gap-3 bg-[var(--sys-bg-page)] p-3 rounded-md border border-[var(--sys-border-subtle)] text-[13px] font-bold text-[var(--sys-text-primary)]">
                <span class="material-symbols-outlined text-[20px] text-[var(--sys-brand-solid)]">calendar_clock</span>
                {{ activeRequest.fullDateRange }}
              </div>
            </div>

            <div class="space-y-1.5 bg-transparent border-none">
              <label class="text-[12px] font-semibold text-[var(--sys-text-secondary)] uppercase tracking-wider block ml-1">Lý do & Thuyết minh</label>
              <div class="bg-[var(--sys-bg-page)] p-4 rounded-md border-l-4 border-l-[var(--sys-brand-solid)] text-[13px] text-[var(--sys-text-primary)] font-medium leading-relaxed italic">
                "{{ activeRequest.reason }}"
              </div>
            </div>

            <div class="p-4 bg-[var(--sys-brand-soft)] rounded-lg border border-[var(--sys-brand-border)] flex items-center justify-between">
              <div class="flex items-center gap-2 text-[var(--sys-brand-solid)]">
                <span class="material-symbols-outlined text-[20px]">account_balance_wallet</span>
                <span class="text-[12px] font-bold uppercase tracking-wide">Quỹ phép tồn</span>
              </div>
              <span class="text-sm font-bold text-[var(--sys-brand-solid)] bg-white/50 px-2 py-0.5 rounded border border-[var(--sys-brand-border)]">{{ activeRequest.balance }} NGÀY</span>
            </div>

            <!-- Approver Info -->
            <div v-if="activeRequest.status === 'approved'" class="space-y-3 p-4 bg-[var(--sys-success-soft)]/50 border border-[var(--sys-success-border)] rounded-lg">
              <div class="flex items-center gap-2 text-[var(--sys-success-text)] font-bold uppercase text-[11px]">
                <span class="material-symbols-outlined text-[18px]">verified_user</span>
                Lịch sử phê chuẩn
              </div>
              <div class="space-y-2">
                <div v-if="activeRequest.approver_manager" class="flex flex-col gap-1 p-2 bg-white/60 rounded border border-[var(--sys-success-border)]/20 shadow-sm">
                  <span class="text-[10px] uppercase font-bold text-[var(--sys-text-secondary)] opacity-60">Trưởng phòng xét duyệt:</span>
                  <span class="text-[13px] font-bold text-[var(--sys-text-primary)] transition-colors">{{ activeRequest.approver_manager }}</span>
                </div>
                <div v-if="activeRequest.approver_director" class="flex flex-col gap-1 p-2 bg-white/60 rounded border border-[var(--sys-success-border)]/20 shadow-sm">
                  <span class="text-[10px] uppercase font-bold text-[var(--sys-text-secondary)] opacity-60">Giám đốc phê chuẩn:</span>
                  <span class="text-[14px] font-bold text-[var(--sys-brand-solid)] transition-colors">{{ activeRequest.approver_director }}</span>
                </div>
              </div>
            </div>

            <!-- Warnings -->
            <div v-if="activeRequest.warnings && activeRequest.warnings.length" class="space-y-3 p-4 bg-[var(--sys-danger-soft)]/50 border border-[var(--sys-danger-border)] rounded-lg">
              <div class="flex items-center gap-2 text-[var(--sys-danger-text)] font-bold uppercase text-[11px]">
                <span class="material-symbols-outlined text-[18px]">warning</span>
                Xung đột hệ thống
              </div>
              <ul class="space-y-2 m-0 p-0 list-none">
                <li v-for="(warn, idx) in activeRequest.warnings" :key="idx" class="text-[11px] font-medium text-[var(--sys-danger-text)] flex items-start gap-2 bg-white/60 p-2 rounded-md border border-[var(--sys-danger-border)]/20 shadow-sm leading-relaxed">
                  <span class="w-1 h-1 rounded-full bg-[var(--sys-danger-solid)] mt-1.5 shrink-0"></span>
                  {{ warn }}
                </li>
              </ul>
            </div>

            <div v-if="activeRequest.status === 'pending'" class="space-y-1.5 bg-transparent border-none">
              <div class="bg-[var(--sys-warning-soft)] border border-[var(--sys-warning-border)] rounded-md px-3 py-2 text-[13px] font-semibold text-[var(--sys-warning-text)]">
                Đơn nghỉ phép đang chờ Trưởng phòng phê duyệt. HR theo dõi và chưa thể xác nhận ở bước này.
              </div>
            </div>

            <div v-if="activeRequest.status === 'waiting_director'" class="space-y-1.5 bg-transparent border-none">
              <div class="bg-[var(--sys-warning-soft)] border border-[var(--sys-warning-border)] rounded-md px-3 py-2 text-[13px] font-semibold text-[var(--sys-warning-text)]">
                Đơn nghỉ phép đang chờ Giám đốc phê duyệt. HR vui lòng theo dõi trạng thái.
              </div>
            </div>
          </div>

          <!-- Sticky Footer -->
          <div v-if="activeRequest.status === 'pending' || activeRequest.status === 'waiting_director'" class="p-6 border-t border-[var(--sys-border-subtle)] flex gap-3 bg-[var(--sys-bg-surface)] shadow-lg mt-auto">
            <button
              type="button"
              class="w-full h-11 px-6 bg-[var(--sys-bg-page)] text-[var(--sys-text-secondary)] rounded-md text-sm font-semibold border border-[var(--sys-border-subtle)] cursor-not-allowed"
              disabled
            >
              Chờ phê duyệt theo quy trình
            </button>
          </div>

          <div v-if="activeRequest.status === 'waiting_hr'" class="p-6 border-t border-[var(--sys-border-subtle)] flex gap-3 bg-[var(--sys-bg-surface)] shadow-lg mt-auto">
            <button 
              @click="handleConfirmHR(activeRequest)"
              class="w-full h-11 px-6 bg-[var(--sys-brand-solid)] text-white rounded-md text-sm font-bold shadow-sm hover:brightness-90 transition-all flex items-center justify-center gap-2 uppercase tracking-wide"
            >
              <span class="material-symbols-outlined text-[20px]">verified</span> 
              Xác nhận & Chấm công
            </button>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Attendance Confirmation Modal -->
    <Teleport to="body">
      <div v-if="showAttendanceModal" class="fixed inset-0 z-[1100] flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
        <div class="bg-white w-full max-w-md rounded-lg shadow-2xl overflow-hidden border border-[var(--sys-border-subtle)]">
          <div class="px-6 py-4 border-b border-[var(--sys-border-subtle)] bg-[var(--sys-bg-surface)] flex justify-between items-center">
            <h3 class="text-sm font-bold text-[var(--sys-text-primary)] uppercase">Xác nhận chấm công tự động</h3>
            <button @click="showAttendanceModal = false" class="text-[var(--sys-text-secondary)] hover:text-[var(--sys-text-primary)]">
               <span class="material-symbols-outlined">close</span>
            </button>
          </div>
          <div class="p-6 space-y-4">
            <div class="flex items-center gap-4 p-3 bg-[var(--sys-bg-page)] rounded-md border border-[var(--sys-border-subtle)]">
              <div class="w-10 h-10 rounded-full bg-[var(--sys-brand-soft)] text-[var(--sys-brand-solid)] flex items-center justify-center">
                <span class="material-symbols-outlined">person</span>
              </div>
              <div>
                <p class="text-[11px] font-bold text-[var(--sys-text-secondary)] uppercase tracking-widest">Nhân viên nghỉ phép</p>
                <p class="text-[14px] font-bold text-[var(--sys-text-primary)]">{{ activeRequest?.name }}</p>
              </div>
            </div>
            <div class="grid grid-cols-2 gap-3">
              <div class="p-3 bg-[var(--sys-bg-page)] rounded-md border border-[var(--sys-border-subtle)]">
                <p class="text-[10px] font-bold text-[var(--sys-text-secondary)] uppercase">Từ ngày</p>
                <p class="text-[13px] font-bold text-[var(--sys-brand-solid)]">{{ activeRequest?.fullDateRange.split(' - ')[0] }}</p>
              </div>
              <div class="p-3 bg-[var(--sys-bg-page)] rounded-md border border-[var(--sys-border-subtle)]">
                <p class="text-[10px] font-bold text-[var(--sys-text-secondary)] uppercase">Đến ngày</p>
                <p class="text-[13px] font-bold text-[var(--sys-brand-solid)]">{{ activeRequest?.fullDateRange.split(' - ')[1] || activeRequest?.fullDateRange.split(' - ')[0] }}</p>
              </div>
            </div>
            <p class="text-[12px] text-[var(--sys-text-secondary)] italic leading-relaxed">
              Hệ thống sẽ tự động cập nhật dữ liệu chấm công "On-time" cho nhân sự này trong suốt khoảng thời gian nghỉ phép đã nêu trên.
            </p>
          </div>
          <div class="p-6 bg-[var(--sys-bg-page)]/50 border-t border-[var(--sys-border-subtle)] flex gap-3">
            <button @click="showAttendanceModal = false" class="flex-1 h-10 rounded-md text-[11px] font-bold uppercase text-[var(--sys-text-secondary)] hover:bg-white transition-all border border-[var(--sys-border-strong)]">Hủy</button>
            <button 
              @click="markAttendance" 
              :disabled="isProcessingAttendance"
              class="flex-1 h-10 bg-[var(--sys-brand-solid)] text-white rounded-md font-bold uppercase text-[11px] hover:brightness-90 transition-all flex items-center justify-center gap-2"
            >
              <span v-if="isProcessingAttendance" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
              <span v-else class="material-symbols-outlined text-[18px]">rule</span>
              {{ isProcessingAttendance ? 'Đang xử lý...' : 'Bấm Chấm công' }}
            </button>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<script setup>
/**
 * TRANG ĐIỀU PHỐI NGHỈ PHÉP - PHIÊN BẢN ENTERPRISE SaaS
 * Tuân thủ 7 Golden Rules:
 * - Font Inter 14px (text-sm), Tỉ lệ thẻ/bảng cao (text-13px)
 * - Bo góc chuẩn B2B: 6px (MD) cho Input/Button/Dropdown, 8px (LG) cho Card/Thẻ/Modal
 * - Hệ màu Blue/White đồng nhất cho Action Icons (Approve)
 */
import { ref, computed, onMounted, watch } from 'vue';
import Dropdown from '@/components/Dropdown.vue';
import { apiRequest } from '@/services/beApi.js';
import { useConfirm } from '@/composables/useConfirm';
import { fixMojibake } from '@/utils/textEncodingFixed.js';

const { showAlert } = useConfirm();

const filterDept = ref('ALL');
const filterRange = ref('month');
const activeTab = ref('all');
const searchQuery = ref('');
const currentPage = ref(1);
const pageSize = 10;
const showAttendanceModal = ref(false);
const isProcessingAttendance = ref(false);
const employees = ref([]);
const departments = ref([]);
const requestTypes = ref([]);

const LEAVE_TYPE_LABEL_BY_CODE = {
  PHEP_NAM: 'Nghỉ phép năm',
  OM_DAU: 'Nghỉ ốm',
  KHONG_LUONG: 'Nghỉ không lương',
  CONG_TAC: 'Công tác',
  REMOTE: 'Làm việc từ xa',
};

const normalizeText = (value) => fixMojibake(String(value ?? '')).replace(/\s+/g, ' ').trim();

const normalizeStatusToken = (value) =>
  normalizeText(value)
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .replace(/[^A-Za-z0-9]+/g, '_')
    .replace(/^_+|_+$/g, '')
    .toUpperCase();

const mapLeaveStatus = (rawStatus) => {
  const token = normalizeStatusToken(rawStatus);
  const compact = token.replace(/_/g, '');

  if (token.includes('TU_CHOI') || token.includes('REJECTED') || compact.includes('TUCHOI')) {
    return { status: 'rejected', text: 'Từ chối' };
  }
  if (token.includes('CHO_XAC_NHAN_HR') || compact.includes('CHOXACNHANHR') || (token.includes('HR') && token.includes('CHO'))) {
    return { status: 'waiting_hr', text: 'Chờ HR xác nhận' };
  }
  if (token.includes('CHO_GIAM_DOC_DUYET') || compact.includes('CHOGIAMDOCDUYET') || (token.includes('GIAM_DOC') && token.includes('CHO'))) {
    return { status: 'waiting_director', text: 'Chờ Giám đốc duyệt' };
  }
  if (token.includes('DA_DUYET') || token.includes('APPROVED') || compact.includes('DADUYET')) {
    return { status: 'approved', text: 'Đã duyệt' };
  }
  return { status: 'pending', text: 'Chờ Trưởng phòng duyệt' };
};

const resolveLeaveTypeLabel = (req, requestTypeMap) => {
  const leaveCode = normalizeText(req.leave_type_code || req.leaveTypeCode).toUpperCase();
  if (leaveCode && LEAVE_TYPE_LABEL_BY_CODE[leaveCode]) {
    return LEAVE_TYPE_LABEL_BY_CODE[leaveCode];
  }

  const leaveTypeName = normalizeText(req.leave_type_name || req.leaveTypeName);
  if (leaveTypeName && !/\?/.test(leaveTypeName)) {
    return leaveTypeName;
  }

  const requestTypeId = req.request_type_id ?? req.requestTypeId;
  const requestType = requestTypeMap.get(String(requestTypeId));
  const requestTypeName = normalizeText(requestType?.request_type_name ?? requestType?.requestTypeName ?? '');
  if (requestTypeName && !/\?/.test(requestTypeName)) {
    return requestTypeName;
  }

  return 'Nghỉ phép';
};

const leaveStats = ref([
  { label: 'Yêu cầu chờ duyệt', value: '12', unit: 'HỒ SƠ', icon: 'pending_actions', bgClass: 'bg-[var(--sys-warning-soft)] text-[var(--sys-warning-text)]' },
  { label: 'Đã phê chuẩn', value: '25', unit: 'HỒ SƠ', icon: 'task_alt', bgClass: 'bg-[var(--sys-success-soft)] text-[var(--sys-success-text)]' },
  { label: 'Đang vắng mặt', value: '08', unit: 'NHÂN SỰ', icon: 'person_off', bgClass: 'bg-[var(--sys-brand-solid)] text-white' },
  { label: 'Tỷ trọng vắng mặt', value: '4.2', unit: '% TỔNG', icon: 'query_stats', bgClass: 'bg-[var(--sys-danger-soft)] text-[var(--sys-danger-text)]' }
]);

const tabOptions = [
  { label: 'Tất cả', value: 'all', count: 0 },
  { label: 'Chờ duyệt', value: 'pending', count: 0 },
  { label: 'Chờ xác nhận', value: 'waiting_hr', count: 0 },
  { label: 'Đã duyệt', value: 'approved', count: 0 },
  { label: 'Từ chối', value: 'rejected', count: 0 },
];

const deptOptions = computed(() => {
  const depts = departments.value.map((d) => ({
    departmentName: d.department_name ?? d.departmentName ?? '',
  }));
  return [
    { label: 'Phòng ban: Tất cả', value: 'ALL' },
    ...depts.map(d => ({
      label: `Phòng ban: ${d.departmentName}`,
      value: d.departmentName
    }))
  ];
});

const rangeOptions = [
  { label: 'Chu kỳ: Trong tháng', value: 'month' },
  { label: 'Chu kỳ: Trong quý', value: 'quarter' }
];

const requests = ref([]);

const fetchData = async () => {
  try {
    const [requestsRes, empRes, deptRes, typeRes] = await Promise.all([
      apiRequest('/leave-requests', { query: { page: 1, per_page: 5000 }, noGetCache: true }),
      apiRequest('/employees', { query: { page: 1, per_page: 5000 }, noGetCache: true }),
      apiRequest('/departments', { query: { page: 1, per_page: 1000 }, noGetCache: true }),
      apiRequest('/request-types', { query: { page: 1, per_page: 1000 }, noGetCache: true }),
    ]);
    employees.value = empRes?.data || [];
    departments.value = deptRes?.data || [];
    requestTypes.value = typeRes?.data || [];

    const employeeById = new Map(
      employees.value.map((employee) => [String(employee.employee_id ?? employee.employeeId ?? employee.id), employee]),
    );
    const deptById = new Map(
      departments.value.map((department) => [String(department.department_id ?? department.departmentId ?? department.id), department]),
    );
    const requestTypeMap = new Map(
      requestTypes.value.map((type) => [String(type.request_type_id ?? type.requestTypeId), type]),
    );

    requests.value = (requestsRes?.data || []).map((req) => {
      const employeeId = req.employee_id ?? req.employeeId ?? req.requester_id ?? req.requesterId ?? req.user_id ?? req.userId ?? null;
      const employee = employeeId !== null ? employeeById.get(String(employeeId)) : null;
      const fallbackName = normalizeText(req.employee_name || req.requester_name || req.requesterName || req.full_name || req.name);

      const employeeCode = normalizeText(
        req.employee_code
        || req.employeeCode
        || employee?.employee_code
        || employee?.employeeCode,
      ) || (employeeId ? `NV${String(employeeId).padStart(4, '0')}` : 'NV-NA');
      const name = normalizeText(employee?.full_name ?? employee?.fullName ?? fallbackName) || `Nhân sự ${employeeCode}`;

      const departmentId = req.department_id ?? req.departmentId ?? employee?.department_id ?? employee?.departmentId ?? null;
      const department = deptById.get(String(departmentId || ''));
      const departmentName = normalizeText(
        req.department_name
        || req.departmentName
        || department?.department_name
        || department?.departmentName
        || employee?.department_name
        || employee?.departmentName,
      ) || 'Chưa rõ phòng ban';

      const roleName = normalizeText(
        req.position_name
        || req.positionName
        || employee?.position_name
        || employee?.positionName
        || employee?.position?.position_name
        || employee?.position?.positionName,
      ) || 'Nhân viên';

      const startDate = String(req.from_date || req.fromDate || req.start_date || req.startDate || '').slice(0, 10);
      const endDate = String(req.to_date || req.toDate || req.end_date || req.endDate || startDate).slice(0, 10);
      const leaveTypeName = resolveLeaveTypeLabel(req, requestTypeMap);
      const mappedStatus = mapLeaveStatus(req.request_status ?? req.status);

      return {
        id: req.leave_request_id || req.leaveRequestId || req.request_id || req.requestId || req.id,
        employeeId: employeeId ? Number(employeeId) : null,
        name,
        msnv: employeeCode,
        department: departmentName,
        role: roleName,
        type: leaveTypeName.toUpperCase(),
        typeDetail: leaveTypeName,
        dateRange: startDate === endDate ? startDate : `${startDate} - ${endDate}`,
        fullDateRange: startDate === endDate ? startDate : `${startDate} - ${endDate}`,
        days: Number(req.number_of_days ?? req.days ?? req.duration ?? 1) || 1,
        status: mappedStatus.status,
        statusText: mappedStatus.text,
        reason: normalizeText(req.request_reason || req.reason || req.request_notes || req.notes) || 'Không có lý do',
        balance: Number(employee?.base_leave_days ?? employee?.baseLeaveDays ?? 12) || 12,
        warnings: req.urgent || req.is_urgent ? ['Yêu cầu khẩn cấp'] : [],
        approver_manager: normalizeText(req.approver_manager),
        approver_director: normalizeText(req.approver_director),
      };
    });
    
    // Đồng bộ số lượng tab & thống kê
    tabOptions[0].count = requests.value.length; 
    tabOptions[1].count = requests.value.filter(r => r.status === 'pending' || r.status === 'waiting_director').length;
    tabOptions[2].count = requests.value.filter(r => r.status === 'waiting_hr').length;
    tabOptions[3].count = requests.value.filter(r => r.status === 'approved').length;
    tabOptions[4].count = requests.value.filter(r => r.status === 'rejected').length;
    
    // Cập nhật leaveStats
    leaveStats.value[0].value = String(tabOptions[1].count);
    leaveStats.value[1].value = String(tabOptions[2].count);
    leaveStats.value[2].value = String(requests.value.filter(r => r.status === 'approved').length); // Giả định
    
    rejectComment.value = ''; // Reset nội dung ý kiến
  } catch (error) {
    console.error('Lỗi khi tải dữ liệu nghỉ phép Admin:', error);
  }
};

onMounted(() => {
  fetchData();
});

const activeRequestId = ref(null);

const activeRequest = computed(() => {
  return requests.value.find(r => r.id === activeRequestId.value) || null;
});

const filteredRequests = computed(() => {
  let list = requests.value;
  if (activeTab.value !== 'all') {
    if (activeTab.value === 'pending') {
      list = list.filter(r => r.status === 'pending' || r.status === 'waiting_director');
    } else {
      list = list.filter(r => r.status === activeTab.value);
    }
  }
  if (filterDept.value !== 'ALL') {
    list = list.filter(r => r.department === filterDept.value);
  }
  if (searchQuery.value) {
    const q = searchQuery.value.toLowerCase();
    list = list.filter(r => r.name.toLowerCase().includes(q) || String(r.msnv).toLowerCase().includes(q));
  }
  return list;
});

const totalPages = computed(() => {
  return Math.ceil(filteredRequests.value.length / pageSize) || 1;
});

const paginatedRequests = computed(() => {
  const start = (currentPage.value - 1) * pageSize;
  const end = start + pageSize;
  return filteredRequests.value.slice(start, end);
});

watch([activeTab, filterDept, searchQuery], () => {
  currentPage.value = 1;
});

const rejectComment = ref('');

const notifyUserAction = async (employeeId, status) => {
  if (!employeeId) return;
  try {
    await apiRequest('/notifications', {
      method: 'POST',
      body: {
        receiver_id: Number(employeeId),
        title: 'Thông báo xét duyệt Nghỉ phép',
        content: `Ban lãnh đạo đã ${status === 'ĐÃ_DUYỆT' ? 'PHÊ DUYỆT' : 'TỪ CHỐI'} đơn nghỉ phép của bạn.`,
        notification_type: 'SYSTEM',
      },
    }).catch(() => null);
  } catch (e) { console.error('Notify Error:', e); }
};

const handleApprove = async (req) => {
  try {
    await apiRequest(`/leave-requests/${req.id}`, {
      method: 'PATCH',
      body: { 
        status: 'approved', // Using 'approved' consistently with mappings
        statusText: 'Đã duyệt',
        approver_director: 'Ban Giám Đốc'
      }
    });
    await notifyUserAction(req.employeeId, 'ĐÃ_DUYỆT');
    rejectComment.value = '';
    await fetchData();
    activeRequestId.value = null;
  } catch (err) {
    console.error('Lỗi khi phê duyệt:', err);
  }
};

const handleConfirmHR = async (req) => {
  try {
    await apiRequest(`/leave-requests/${req.id}`, {
      method: 'PATCH',
      body: { 
        status: 'approved',
        statusText: 'Đã duyệt',
        approver_hr: 'Phòng Nhân Sự'
      }
    });
    await notifyUserAction(req.employeeId, 'ĐÃ_DUYỆT');
    await fetchData();
    showAttendanceModal.value = true;
  } catch (err) {
    console.error('Lỗi khi HR xác nhận:', err);
  }
};

const markAttendance = async () => {
  if (!activeRequest.value) return;
  isProcessingAttendance.value = true;
  try {
    const start = new Date(activeRequest.value.fullDateRange.split(' - ')[0]);
    const end = new Date(activeRequest.value.fullDateRange.split(' - ')[1] || activeRequest.value.fullDateRange.split(' - ')[0]);
    
    const dates = [];
    let current = new Date(start);
    while (current <= end) {
      dates.push(new Date(current).toISOString().split('T')[0]);
      current.setDate(current.getDate() + 1);
    }

    const employeeId = Number(
      activeRequest.value.employeeId
      ?? employees.value.find((employee) => String(employee.employee_code ?? employee.employeeCode) === String(activeRequest.value.msnv))?.employee_id
      ?? 0,
    );
    if (!employeeId) {
      await showAlert('THIẾU DỮ LIỆU', 'Không xác định được nhân sự để chấm công tự động.');
      return;
    }

    for (const date of dates) {
      await apiRequest('/attendances', {
        method: 'POST',
        body: {
          employee_id: employeeId,
          attendance_date: date,
          status: 'ĐÃ_DUYỆT',
          notes: `Chấm công tự động từ đơn nghỉ phép #${activeRequest.value.id}`,
        }
      }).catch(() => null);
    }
    
    await showAlert('THÀNH CÔNG', `Đã chấm công tự động cho ${activeRequest.value.name} từ ${dates[0]} đến ${dates[dates.length-1]}`);
    showAttendanceModal.value = false;
    activeRequestId.value = null;
  } catch (err) {
    console.error('Lỗi khi chấm công:', err);
    await showAlert('LỖI HỆ THỐNG', 'Có lỗi xảy ra khi chấm công tự động.');
  } finally {
    isProcessingAttendance.value = false;
  }
};

const confirmRejectAction = async (req) => {
  if (!rejectComment.value) {
    await showAlert('THIẾU DỮ LIỆU', 'Vui lòng nhập lý do từ chối');
    return;
  }
  try {
    await apiRequest(`/leave-requests/${req.id}`, {
      method: 'PATCH',
      body: { 
        status: 'TỪ_CHỐI',
        rejectionReason: rejectComment.value 
      }
    });
    await notifyUserAction(req.employeeId, 'TỪ_CHỐI');
    rejectComment.value = '';
    await fetchData();
    activeRequestId.value = null;
  } catch (err) {
    console.error('Lỗi khi từ chối:', err);
  }
};


const getLeaveTypeClass = (type) => {
  if (type.includes('Phép')) return 'bg-[var(--sys-brand-soft)] text-[var(--sys-brand-solid)] border-[var(--sys-brand-border)]';
  if (type.includes('ốm')) return 'bg-[var(--sys-success-soft)] text-[var(--sys-success-text)] border-[var(--sys-success-border)]';
  return 'bg-[var(--sys-warning-soft)] text-[var(--sys-warning-text)] border-[var(--sys-warning-border)]';
};

const getStatusBadgeClass = (status) => {
  switch (status) {
    case 'approved': return 'bg-[var(--sys-success-soft)] text-[var(--sys-success-text)] border-[var(--sys-success-border)]';
    case 'waiting_hr': return 'bg-[var(--sys-brand-soft)] text-[var(--sys-brand-solid)] border-[var(--sys-brand-border)]';
    case 'waiting_director': return 'bg-[var(--sys-warning-soft)] text-[var(--sys-warning-text)] border-[var(--sys-warning-border)]';
    case 'pending': return 'bg-[var(--sys-warning-soft)] text-[var(--sys-warning-text)] border-[var(--sys-border-subtle)]';
    case 'rejected': return 'bg-[var(--sys-danger-soft)] text-[var(--sys-danger-text)] border-[var(--sys-danger-border)]';
    default: return 'bg-[var(--sys-bg-hover)] text-[var(--sys-text-disabled)] border-[var(--sys-border-subtle)] opacity-50';
  }
};

const getStatusDotClass = (status) => {
  switch (status) {
    case 'approved': return 'bg-[var(--sys-success-solid)]';
    case 'waiting_hr': return 'bg-[var(--sys-brand-solid)]';
    case 'waiting_director': return 'bg-[var(--sys-warning-solid)]';
    case 'pending': return 'bg-[var(--sys-warning-solid)]';
    case 'rejected': return 'bg-[var(--sys-danger-solid)]';
    default: return 'bg-[var(--sys-icon-default)] opacity-40';
  }
};
</script>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
  width: 5px;
  height: 5px;
}
.custom-scrollbar::-webkit-scrollbar-track {
  background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
  background: var(--sys-border-subtle);
  border-radius: 5px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
  background: var(--sys-brand-solid);
}
.no-scrollbar::-webkit-scrollbar {
  display: none;
}
</style>
