<template>
  <div class="internal-services-page min-h-screen bg-[var(--sys-bg-page)] text-[var(--sys-text-primary)] p-4 md:p-6 lg:p-8">
    <div class="max-w-7xl mx-auto space-y-6 bg-transparent">
      
      <!-- Header Area -->
      <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 bg-transparent text-left">
        <div class="bg-transparent text-left">
          <h1 class="text-xl font-bold text-[var(--sys-text-primary)] mb-1 uppercase tracking-tight">Dịch vụ nội bộ & Hỗ trợ kỹ thuật</h1>
          <p class="text-[13px] text-[var(--sys-text-secondary)] m-0">Gửi yêu cầu hỗ trợ thiết bị, văn phòng và các dịch vụ hành chính trực tuyến.</p>
        </div>
        <button
          @click="openModal"
          class="h-11 px-6 bg-[var(--sys-brand-solid)] text-white rounded-md font-bold text-[12px] uppercase tracking-wider hover:brightness-110 active:scale-95 transition-all flex items-center gap-2 shadow-lg shrink-0"
        >
          <span class="material-symbols-outlined text-[20px]">add_circle</span>
          Tạo Ticket hỗ trợ
        </button>
      </div>

      <!-- Metrics Grid -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4 bg-transparent">
        <!-- Ticket Statistics -->
        <div class="bg-[var(--sys-bg-surface)] p-4 rounded-lg border border-[var(--sys-border-subtle)] shadow-sm hover:border-[var(--sys-brand-solid)] transition-all group flex items-center gap-4">
          <div class="w-10 h-10 rounded-md bg-[var(--sys-brand-soft)] text-[var(--sys-brand-solid)] flex items-center justify-center shrink-0 border border-[var(--sys-brand-border)] transition-all">
            <span class="material-symbols-outlined text-xl">confirmation_number</span>
          </div>
          <div class="bg-transparent flex flex-col overflow-hidden">
            <p class="text-[11px] font-bold text-[var(--sys-text-secondary)] mb-0.5 uppercase tracking-widest opacity-60 truncate">Tổng Ticket đã tạo</p>
            <p class="text-xl font-bold text-[var(--sys-text-primary)] m-0 leading-tight tracking-tight">{{ tickets.length }}</p>
          </div>
        </div>

        <div class="bg-[var(--sys-bg-surface)] p-4 rounded-lg border border-[var(--sys-border-subtle)] shadow-sm hover:border-[var(--sys-warning-border)] transition-all group flex items-center gap-4">
          <div class="w-10 h-10 rounded-md bg-[var(--sys-warning-soft)] text-[var(--sys-warning-text)] flex items-center justify-center shrink-0 border border-[var(--sys-warning-border)] transition-all">
            <span class="material-symbols-outlined text-xl">pending</span>
          </div>
          <div class="bg-transparent flex flex-col overflow-hidden">
            <p class="text-[11px] font-bold text-[var(--sys-text-secondary)] mb-0.5 uppercase tracking-widest opacity-60 truncate">Yêu cầu đang xử lý</p>
            <p class="text-xl font-bold text-[var(--sys-text-primary)] m-0 leading-tight tracking-tight">{{ tickets.filter(t => t.status === 'Đang xử lý').length }}</p>
          </div>
        </div>

        <div class="bg-[var(--sys-bg-surface)] p-4 rounded-lg border border-[var(--sys-border-subtle)] shadow-sm hover:border-[var(--sys-success-border)] transition-all group flex items-center gap-4">
          <div class="w-10 h-10 rounded-md bg-[var(--sys-success-soft)] text-[var(--sys-success-text)] flex items-center justify-center shrink-0 border border-[var(--sys-success-border)] transition-all">
            <span class="material-symbols-outlined text-xl">check_circle</span>
          </div>
          <div class="bg-transparent flex flex-col overflow-hidden">
            <p class="text-[11px] font-bold text-[var(--sys-text-secondary)] mb-0.5 uppercase tracking-widest opacity-60 truncate">Đã hoàn thành bàn giao</p>
            <p class="text-xl font-bold text-[var(--sys-text-primary)] m-0 leading-tight tracking-tight">{{ tickets.filter(t => t.status === 'Hoàn thành').length }}</p>
          </div>
        </div>
      </div>

      <!-- Ticket List Section -->
      <div class="bg-[var(--sys-bg-surface)] rounded-lg border border-[var(--sys-border-subtle)] shadow-sm overflow-hidden flex flex-col">
        <div class="px-4 py-3 border-b border-[var(--sys-border-subtle)] bg-[var(--sys-bg-page)]/50 flex justify-between items-center h-14">
          <h4 class="text-[13px] font-bold text-[var(--sys-text-primary)] flex items-center gap-2 m-0 uppercase tracking-widest leading-none">
            <span class="material-symbols-outlined text-[var(--sys-brand-solid)] text-[20px]">list_alt</span>
            Tiến độ xử lý & Lịch sử hỗ trợ
          </h4>
          <div class="relative group hidden sm:block">
            <span class="absolute inset-y-0 left-3 flex items-center text-[var(--sys-text-secondary)] opacity-50 group-focus-within:text-[var(--sys-brand-solid)] transition-colors">
              <span class="material-symbols-outlined text-[18px]">search</span>
            </span>
            <input
              v-model="searchQuery"
              type="text"
              placeholder="Tìm kiếm mã Ticket..."
              class="w-64 h-11 pl-10 pr-4 rounded-md border border-[var(--sys-border-strong)] text-[13px] font-medium outline-none focus:border-[var(--sys-brand-solid)] shadow-sm transition-all placeholder:text-[var(--sys-text-secondary)]/40 bg-[var(--sys-bg-surface)]"
            >
          </div>
        </div>

        <!-- Empty State -->
        <div v-if="filteredTickets.length === 0" class="py-20 flex flex-col items-center justify-center text-center bg-transparent">
          <div class="w-20 h-20 rounded-lg bg-[var(--sys-bg-page)] flex items-center justify-center mb-6 border border-[var(--sys-border-subtle)] shadow-inner">
            <span class="material-symbols-outlined text-[40px] text-[var(--sys-text-disabled)] opacity-30">confirmation_number</span>
          </div>
          <h3 class="text-lg font-bold text-[var(--sys-text-primary)] mb-2 uppercase tracking-tight">Chưa có yêu cầu nào được khởi tạo</h3>
          <p class="text-[13px] font-medium text-[var(--sys-text-secondary)] max-w-sm mx-auto mb-8 opacity-70 leading-relaxed">Mọi yêu cầu hỗ trợ hoặc dịch vụ nội bộ của bạn sẽ được hiển thị và cập nhật tiến độ tự động tại đây.</p>
          <button
            @click="openModal"
            class="h-10 px-6 bg-[var(--sys-brand-soft)] text-[var(--sys-brand-solid)] rounded-md font-bold text-[12px] uppercase tracking-wider hover:bg-[var(--sys-brand-solid)] hover:text-white transition-all border border-[var(--sys-brand-border)] flex items-center gap-2 active:scale-95"
          >
            Khởi tạo Ticket đầu tiên
            <span class="material-symbols-outlined text-[18px]">arrow_forward</span>
          </button>
        </div>

        <!-- Ticket Table -->
        <div v-else class="overflow-x-auto custom-scrollbar bg-transparent">
          <table class="w-full text-left border-collapse">
            <thead class="bg-[var(--sys-bg-page)]">
              <tr>
                <th class="px-4 py-2.5 text-[11px] font-bold text-[var(--sys-text-secondary)] border-b border-[var(--sys-border-subtle)] uppercase tracking-widest whitespace-nowrap">Mã Ticket</th>
                <th class="px-4 py-2.5 text-[11px] font-bold text-[var(--sys-text-secondary)] border-b border-[var(--sys-border-subtle)] uppercase tracking-widest whitespace-nowrap">Tiêu đề</th>
                <th class="px-4 py-2.5 text-[11px] font-bold text-[var(--sys-text-secondary)] border-b border-[var(--sys-border-subtle)] uppercase tracking-widest whitespace-nowrap text-center">Loại dịch vụ</th>
                <th class="px-4 py-2.5 text-[11px] font-bold text-[var(--sys-text-secondary)] border-b border-[var(--sys-border-subtle)] uppercase tracking-widest whitespace-nowrap text-center">Mức độ</th>
                <th class="px-4 py-2.5 text-[11px] font-bold text-[var(--sys-text-secondary)] border-b border-[var(--sys-border-subtle)] uppercase tracking-widest whitespace-nowrap text-center">Ngày tạo</th>
                <th class="px-4 py-2.5 text-[11px] font-bold text-[var(--sys-text-secondary)] border-b border-[var(--sys-border-subtle)] uppercase tracking-widest whitespace-nowrap text-right">Trạng thái</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-[var(--sys-border-subtle)]">
              <tr
                v-for="ticket in filteredTickets"
                :key="ticket.id"
                class="group hover:bg-[var(--sys-bg-hover)] transition-all cursor-default"
              >
                <td class="px-4 py-3 whitespace-nowrap bg-transparent">
                  <span class="font-mono text-[12px] text-[var(--sys-brand-solid)] font-bold uppercase tracking-tight">#{{ ticket.id }}</span>
                </td>
                <td class="px-4 py-3 bg-transparent">
                  <span class="text-[13px] font-bold text-[var(--sys-text-primary)] transition-colors group-hover:text-[var(--sys-brand-solid)] line-clamp-1 max-w-[250px]" :title="ticket.title">{{ ticket.title }}</span>
                </td>
                <td class="px-4 py-3 whitespace-nowrap text-center bg-transparent text-[12px] font-bold text-[var(--sys-text-secondary)] tracking-tight">
                  {{ ticket.category }}
                </td>
                <td class="px-4 py-3 whitespace-nowrap text-center bg-transparent">
                  <span :class="getPriorityClass(ticket.priority)" class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-widest border">
                    {{ ticket.priority }}
                  </span>
                </td>
                <td class="px-4 py-3 whitespace-nowrap text-center bg-transparent text-[12px] font-bold text-[var(--sys-text-secondary)] tracking-tighter uppercase">
                  {{ ticket.date }}
                </td>
                <td class="px-4 py-3 whitespace-nowrap text-right bg-transparent">
                  <span :class="getStatusClass(ticket.status)" class="inline-flex items-center justify-end gap-1.5 px-2 py-0.5 rounded-md text-[10px] font-bold uppercase tracking-widest border transition-all shadow-sm">
                    <span class="w-1.5 h-1.5 rounded-full" :class="getStatusDotClass(ticket.status)"></span>
                    {{ ticket.status }}
                  </span>
                </td>
              </tr>
            </tbody>
          </table>
          <div class="px-4 py-3 bg-[var(--sys-bg-page)]/50 border-t border-[var(--sys-border-subtle)] flex justify-between items-center h-12">
            <span class="text-[11px] font-bold text-[var(--sys-text-secondary)] uppercase tracking-widest opacity-60">Hiển thị {{ filteredTickets.length }} yêu cầu</span>
          </div>
        </div>
      </div>
    </div>

  <!-- ══════════════════════════════════════════════════
       POPUP / MODAL: TẠO TICKET HỖ TRỢ
  ══════════════════════════════════════════════════ -->
  <Teleport to="body">
    <Transition name="modal-fade">
      <div v-if="showModal" class="fixed inset-0 z-[10000] flex items-center justify-center p-4">
        <div class="fixed inset-0 w-screen h-screen bg-black/60 backdrop-blur-sm z-[9999]" @click.self="closeModal"></div>
        <div class="relative z-[10000] bg-white border border-[var(--sys-border-subtle)] w-full max-w-3xl max-h-[90vh] rounded-lg shadow-2xl overflow-hidden flex flex-col text-left motion-safe:animate-zoomIn">
          
          <!-- Modal Header -->
          <div class="px-6 py-4 border-b border-[var(--sys-border-subtle)] flex items-center justify-between bg-[var(--sys-bg-surface)]">
            <div class="bg-transparent text-left flex items-center gap-4">
              <div class="w-10 h-10 rounded-md bg-[var(--sys-brand-soft)] border border-[var(--sys-brand-border)] flex items-center justify-center">
                <span class="material-symbols-outlined text-[20px] text-[var(--sys-brand-solid)]">support_agent</span>
              </div>
              <div>
                <h3 class="text-lg font-bold text-[var(--sys-text-primary)] m-0 uppercase tracking-tight">Tạo Ticket hỗ trợ</h3>
                <p class="text-[11px] text-[var(--sys-text-secondary)] font-medium mt-0.5">Điền thông tin yêu cầu bên dưới</p>
              </div>
            </div>
            <button @click="closeModal" class="w-8 h-8 flex items-center justify-center rounded-md hover:bg-[var(--sys-bg-hover)] transition-all text-[var(--sys-text-secondary)]">
              <span class="material-symbols-outlined text-xl">close</span>
            </button>
          </div>

          <!-- Modal Body (Directive 4: Grid Form) -->
          <div class="flex-1 overflow-y-auto p-6 custom-scrollbar space-y-8">
            <!-- Row 1: Tiêu đề yêu cầu -->
            <div class="space-y-1.5">
              <label class="text-[11px] font-bold text-[var(--sys-text-primary)] uppercase tracking-wider ml-1" for="ticket-title">
                Tiêu đề yêu cầu <span class="text-[var(--sys-danger-solid)]">*</span>
              </label>
              <input
                id="ticket-title"
                v-model="form.title"
                type="text"
                placeholder="Nhập tiêu đề ngắn gọn mô tả vấn đề..."
                class="w-full h-11 px-4 bg-[var(--sys-bg-page)] border border-[var(--sys-border-strong)] rounded-md text-[13px] font-bold text-[var(--sys-text-primary)] outline-none focus:border-[var(--sys-brand-solid)] shadow-sm transition-all"
                :class="{ 'border-[var(--sys-danger-solid)] focus:border-[var(--sys-danger-solid)]': errors.title }"
              />
              <p v-if="errors.title" class="text-[11px] font-bold text-[var(--sys-danger-solid)] mt-1 m-0">{{ errors.title }}</p>
            </div>

            <!-- Row 2: Loại dịch vụ + Mức độ ưu tiên -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
              <div class="space-y-1.5">
                <label class="text-[11px] font-bold text-[var(--sys-text-primary)] uppercase tracking-wider ml-1" for="ticket-category">
                  Loại dịch vụ <span class="text-[var(--sys-danger-solid)]">*</span>
                </label>
                <div class="relative">
                  <select
                    id="ticket-category"
                    v-model="form.category"
                    class="w-full h-11 px-4 bg-[var(--sys-bg-page)] border border-[var(--sys-border-strong)] rounded-md text-[13px] font-bold text-[var(--sys-text-primary)] outline-none focus:border-[var(--sys-brand-solid)] shadow-sm transition-all form-select-custom appearance-none"
                    :class="{ 'border-[var(--sys-danger-solid)]': errors.category }"
                  >
                    <option value="" disabled>Chọn loại dịch vụ...</option>
                    <option value="Hỗ trợ IT & Thiết bị">Hỗ trợ IT & Thiết bị</option>
                    <option value="Hành chính & Văn phòng">Hành chính & Văn phòng</option>
                    <option value="Phần mềm & Tài khoản">Phần mềm & Tài khoản</option>
                    <option value="Cơ sở hạ tầng">Cơ sở hạ tầng</option>
                    <option value="Nhân sự & Phúc lợi">Nhân sự & Phúc lợi</option>
                    <option value="Khác">Khác</option>
                  </select>
                  <span class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-[var(--sys-text-secondary)] opacity-60">
                    <span class="material-symbols-outlined text-[18px]">expand_more</span>
                  </span>
                </div>
                <p v-if="errors.category" class="text-[11px] font-bold text-[var(--sys-danger-solid)] mt-1 m-0">{{ errors.category }}</p>
              </div>

              <div class="space-y-1.5">
                <label class="text-[11px] font-bold text-[var(--sys-text-primary)] uppercase tracking-wider ml-1" for="ticket-priority">
                  Mức độ ưu tiên <span class="text-[var(--sys-danger-solid)]">*</span>
                </label>
                <div class="relative">
                  <select
                    id="ticket-priority"
                    v-model="form.priority"
                    class="w-full h-11 px-4 bg-[var(--sys-bg-page)] border border-[var(--sys-border-strong)] rounded-md text-[13px] font-bold text-[var(--sys-text-primary)] outline-none focus:border-[var(--sys-brand-solid)] shadow-sm transition-all form-select-custom appearance-none"
                    :class="{ 'border-[var(--sys-danger-solid)]': errors.priority }"
                  >
                    <option value="" disabled>Chọn mức độ...</option>
                    <option value="Thấp">Thấp</option>
                    <option value="Trung bình">Trung bình</option>
                    <option value="Cao">Cao</option>
                    <option value="Khẩn cấp">Khẩn cấp</option>
                  </select>
                  <span class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-[var(--sys-text-secondary)] opacity-60">
                    <span class="material-symbols-outlined text-[18px]">expand_more</span>
                  </span>
                </div>
                <p v-if="errors.priority" class="text-[11px] font-bold text-[var(--sys-danger-solid)] mt-1 m-0">{{ errors.priority }}</p>
              </div>
            </div>

            <!-- Row 3: Phòng ban + Thiết bị liên quan -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
              <div class="space-y-1.5">
                <label class="text-[11px] font-bold text-[var(--sys-text-primary)] uppercase tracking-wider ml-1" for="ticket-department">Phòng ban</label>
                <div class="relative">
                  <select id="ticket-department" v-model="form.department" class="w-full h-11 px-4 bg-[var(--sys-bg-page)] border border-[var(--sys-border-strong)] rounded-md text-[13px] font-bold text-[var(--sys-text-primary)] outline-none focus:border-[var(--sys-brand-solid)] shadow-sm transition-all form-select-custom appearance-none">
                    <option value="" disabled>Chọn phòng ban...</option>
                    <option value="Kế toán">Kế toán</option>
                    <option value="Nhân sự">Nhân sự</option>
                    <option value="IT">IT</option>
                    <option value="Kinh doanh">Kinh doanh</option>
                    <option value="Vận hành">Vận hành</option>
                    <option value="Marketing">Marketing</option>
                  </select>
                  <span class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-[var(--sys-text-secondary)] opacity-60">
                    <span class="material-symbols-outlined text-[18px]">expand_more</span>
                  </span>
                </div>
              </div>

              <div class="space-y-1.5">
                <label class="text-[11px] font-bold text-[var(--sys-text-primary)] uppercase tracking-wider ml-1" for="ticket-asset">Thiết bị / Tài sản liên quan</label>
                <input
                  id="ticket-asset"
                  v-model="form.asset"
                  type="text"
                  placeholder="VD: Máy tính PC-NV-042, Máy in..."
                  class="w-full h-11 px-4 bg-[var(--sys-bg-page)] border border-[var(--sys-border-strong)] rounded-md text-[13px] font-bold text-[var(--sys-text-primary)] outline-none focus:border-[var(--sys-brand-solid)] shadow-sm transition-all"
                />
              </div>
            </div>

            <!-- Row 4: Mô tả chi tiết -->
            <div class="space-y-1.5">
              <label class="text-[11px] font-bold text-[var(--sys-text-primary)] uppercase tracking-wider ml-1" for="ticket-description">
                Mô tả chi tiết vấn đề <span class="text-[var(--sys-danger-solid)]">*</span>
              </label>
              <textarea
                id="ticket-description"
                v-model="form.description"
                rows="4"
                placeholder="Mô tả rõ ràng vấn đề gặp phải, thời điểm xảy ra, và các bước đã thực hiện (nếu có)..."
                class="w-full py-3 px-4 bg-[var(--sys-bg-page)] border border-[var(--sys-border-strong)] rounded-md text-[13px] font-bold text-[var(--sys-text-primary)] outline-none focus:border-[var(--sys-brand-solid)] shadow-sm transition-all resize-none"
                :class="{ 'border-[var(--sys-danger-solid)]': errors.description }"
              ></textarea>
              <div class="flex justify-between items-center mt-1">
                <p v-if="errors.description" class="text-[11px] font-bold text-[var(--sys-danger-solid)] mt-1 m-0">{{ errors.description }}</p>
                <span v-else class="text-[11px] text-[var(--sys-text-secondary)] opacity-50">{{ form.description.length }}/500 ký tự</span>
              </div>
            </div>

            <!-- Row 5: Thời gian mong muốn xử lý -->
            <div class="space-y-1.5">
              <label class="text-[11px] font-bold text-[var(--sys-text-primary)] uppercase tracking-wider ml-1" for="ticket-deadline">Thời gian cần xử lý trước</label>
              <input
                id="ticket-deadline"
                v-model="form.deadline"
                type="date"
                class="w-full h-11 px-4 bg-[var(--sys-bg-page)] border border-[var(--sys-border-strong)] rounded-md text-[13px] font-bold text-[var(--sys-text-primary)] outline-none focus:border-[var(--sys-brand-solid)] shadow-sm transition-all"
                :min="todayStr"
              />
            </div>

            <!-- Priority indicator banner -->
            <Transition name="fade-priority">
              <div v-if="form.priority === 'Khẩn cấp'" class="flex items-start gap-3 p-4 rounded-lg bg-[var(--sys-danger-soft)] border border-[var(--sys-danger-border)]">
                <span class="material-symbols-outlined text-[20px] text-[var(--sys-danger-solid)] shrink-0 mt-0.5">warning</span>
                <p class="text-[12px] text-[var(--sys-danger-text)] font-medium m-0 leading-relaxed">
                  Ticket khẩn cấp sẽ được chuyển ngay đến bộ phận phụ trách và xử lý trong vòng <strong>2 giờ làm việc</strong>. Vui lòng đảm bảo thông tin chính xác.
                </p>
              </div>
            </Transition>
          </div>

          <!-- Modal Footer -->
          <div class="px-6 py-4 border-t border-[var(--sys-border-subtle)] bg-[var(--sys-bg-surface)] flex justify-end gap-3 h-16 shrink-0">
            <button @click="closeModal" class="h-9 px-6 text-[12px] font-bold text-[var(--sys-text-secondary)] hover:bg-[var(--sys-bg-hover)] rounded-md transition-all uppercase tracking-wide">
              Hủy bỏ
            </button>
            <button @click="submitTicket" :disabled="isSubmitting" class="h-9 px-8 bg-[var(--sys-brand-solid)] text-white rounded-md font-bold text-[12px] hover:brightness-110 shadow-lg transition-all uppercase tracking-widest active:scale-95 flex items-center gap-2">
              <span v-if="isSubmitting" class="material-symbols-outlined text-[18px] animate-spin">progress_activity</span>
              <span v-else class="material-symbols-outlined text-[18px]">verified</span>
              {{ isSubmitting ? 'Đang gửi...' : 'Gửi yêu cầu' }}
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>

  <!-- Success Toast -->
  <Transition name="toast-slide">
    <div v-if="showToast" class="success-toast">
      <span class="material-symbols-outlined text-[20px] text-[var(--sys-success-solid)]">check_circle</span>
      <div>
        <p class="text-[13px] font-bold text-[var(--sys-text-primary)] m-0">Ticket đã được tạo thành công!</p>
        <p class="text-[12px] text-[var(--sys-text-secondary)] m-0 opacity-80">Mã: <span class="font-mono font-bold text-[var(--sys-brand-solid)]">{{ lastCreatedId }}</span></p>
      </div>
    </div>
  </Transition>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useSupportStore } from '@/composables/useSupportStore'

// ── Shared Store ───────────────────────────────────────
const store = useSupportStore()
const tickets = store.tickets

// ── State ──────────────────────────────────────────────
const showModal = ref(false)
const showToast = ref(false)
const isSubmitting = ref(false)
const lastCreatedId = ref('')
const searchQuery = ref('')
const intervalId = ref(null)

// ── Lifecycle ──────────────────────────────────────────
onMounted(async () => {
  await store.fetchTickets()
  intervalId.value = setInterval(store.fetchTickets, 10000)
})

onUnmounted(() => {
  if (intervalId.value) clearInterval(intervalId.value)
})

// ── Form State ─────────────────────────────────────────
const defaultForm = () => ({
  title: '',
  category: '',
  priority: '',
  department: '',
  asset: '',
  description: '',
  deadline: '',
})

const form = ref(defaultForm())
const errors = ref({})

// ── Helpers ────────────────────────────────────────────
const todayStr = new Date().toISOString().split('T')[0]

const filteredTickets = computed(() => {
  if (!searchQuery.value.trim()) return store.tickets.value
  const q = searchQuery.value.toLowerCase()
  return store.tickets.value.filter(t =>
    t.id.toLowerCase().includes(q) ||
    t.title.toLowerCase().includes(q) ||
    t.category.toLowerCase().includes(q)
  )
})

// ── Modal Controls ─────────────────────────────────────
function openModal() {
  form.value = defaultForm()
  errors.value = {}
  showModal.value = true
  document.body.style.overflow = 'hidden'
}

function closeModal() {
  showModal.value = false
  document.body.style.overflow = ''
}

// ── Validation ─────────────────────────────────────────
function validate() {
  const e = {}
  if (!form.value.title.trim()) e.title = 'Vui lòng nhập tiêu đề yêu cầu.'
  if (!form.value.category) e.category = 'Vui lòng chọn loại dịch vụ.'
  if (!form.value.priority) e.priority = 'Vui lòng chọn mức độ ưu tiên.'
  if (!form.value.description.trim()) e.description = 'Vui lòng mô tả chi tiết vấn đề.'
  else if (form.value.description.trim().length < 20) e.description = 'Mô tả cần ít nhất 20 ký tự.'
  errors.value = e
  return Object.keys(e).length === 0
}

// ── Submit ─────────────────────────────────────────────
async function submitTicket() {
  if (!validate()) return

  isSubmitting.value = true
  // Simulate API delay
  await new Promise(r => setTimeout(r, 900))

  const newId = await store.addTicket({
    title: form.value.title,
    category: form.value.category,
    priority: form.value.priority,
    description: form.value.description,
    deadline: form.value.deadline,
    asset: form.value.asset,
  })

  lastCreatedId.value = newId
  isSubmitting.value = false

  closeModal()
  showToast.value = true
  setTimeout(() => { showToast.value = false }, 4000)
}

// ── Badge Helpers ──────────────────────────────────────
function getPriorityClass(p) {
  switch (p) {
    case 'Khẩn cấp': return 'bg-[var(--sys-danger-soft)] text-[var(--sys-danger-text)] border border-[var(--sys-danger-border)]'
    case 'Cao': return 'bg-[var(--sys-warning-soft)] text-[var(--sys-warning-text)] border border-[var(--sys-warning-border)]'
    case 'Trung bình': return 'bg-[var(--sys-brand-soft)] text-[var(--sys-brand-soft-text)] border border-[var(--sys-brand-border)]'
    default: return 'bg-[var(--sys-bg-active)] text-[var(--sys-text-secondary)] border border-[var(--sys-border-strong)]'
  }
}

function getStatusClass(s) {
  switch (s) {
    case 'Hoàn thành': return 'bg-[var(--sys-success-soft)] text-[var(--sys-success-text)]'
    case 'Đang xử lý': return 'bg-[var(--sys-warning-soft)] text-[var(--sys-warning-text)]'
    case 'Mới': return 'bg-[var(--sys-brand-soft)] text-[var(--sys-brand-soft-text)]'
    default: return 'bg-[var(--sys-bg-active)] text-[var(--sys-text-secondary)]'
  }
}

function getStatusDotClass(s) {
  switch (s) {
    case 'Hoàn thành': return 'bg-[var(--sys-success-solid)]'
    case 'Đang xử lý': return 'bg-[var(--sys-warning-solid)]'
    case 'Mới': return 'bg-[var(--sys-brand-solid)]'
    default: return 'bg-[var(--sys-text-disabled)]'
  }
}
</script>

<style scoped>
/* ─── Form Select Custom ────────────────────────────── */
.form-select-custom {
  -webkit-appearance: none;
  -moz-appearance: none;
  appearance: none;
  background-image: none !important;
}

/* ─── Page ─────────────────────────────────────────── */
.internal-services-page {
  background-color: var(--sys-bg-page);
}

.custom-scrollbar::-webkit-scrollbar {
  width: 5px;
  height: 5px;
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

/* ─── Success Toast ──────────────────────────────────── */
.success-toast {
  position: fixed;
  bottom: 1.5rem;
  right: 1.5rem;
  z-index: 1100;
  background: var(--sys-bg-surface, #ffffff);
  border: 1px solid var(--sys-success-border);
  border-left: 4px solid var(--sys-success-solid);
  border-radius: 8px;
  padding: 0.875rem 1.25rem;
  display: flex;
  align-items: flex-start;
  gap: 0.75rem;
  box-shadow: 0 8px 24px rgba(15, 23, 42, 0.15);
  max-width: 320px;
}

/* ─── Transitions ────────────────────────────────────── */

@keyframes zoomIn {
  from { opacity: 0; transform: scale(0.95); }
  to { opacity: 1; transform: scale(1); }
}

.animate-zoomIn {
  animation: zoomIn 0.3s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}

/* Overlay fade */
.modal-fade-enter-active,
.modal-fade-leave-active {
  transition: opacity 0.2s ease;
}
.modal-fade-enter-from,
.modal-fade-leave-to {
  opacity: 0;
}

/* Priority banner */
.fade-priority-enter-active,
.fade-priority-leave-active {
  transition: all 0.2s ease;
}
.fade-priority-enter-from,
.fade-priority-leave-to {
  opacity: 0;
  transform: translateY(-6px);
}

/* Toast */
.toast-slide-enter-active {
  transition: all 0.35s cubic-bezier(0.34, 1.56, 0.64, 1);
}
.toast-slide-leave-active {
  transition: all 0.25s ease-in;
}
.toast-slide-enter-from,
.toast-slide-leave-to {
  opacity: 0;
  transform: translateX(100%);
}

/* Spin animation for loading */
@keyframes spin {
  to { transform: rotate(360deg); }
}
.animate-spin {
  animation: spin 0.8s linear infinite;
}

/* ─── Dark mode extras ───────────────────────────────── */
:global(html.dark) .success-toast {
  background: oklch(0.22 0.015 265) !important;
}
</style>
