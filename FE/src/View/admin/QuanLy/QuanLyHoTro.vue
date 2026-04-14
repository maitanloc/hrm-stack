<template>
  <div class="space-y-5 pb-10 min-h-screen bg-[var(--sys-bg-page)] text-[var(--sys-text-primary)]">

    <!-- ── Header ─────────────────────────────────────────── -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 bg-transparent text-left px-1">
      <div class="bg-transparent text-left">
        <h1 class="text-xl font-bold uppercase tracking-tight text-[var(--sys-text-primary)] mb-1">Quản lý Hỗ trợ Nội bộ</h1>
        <p class="text-[13px] text-[var(--sys-text-secondary)] m-0">Tiếp nhận, phân công và xử lý ticket hỗ trợ từ nhân viên toàn hệ thống.</p>
      </div>
      <div class="hidden xl:block shrink-0">
        <div class="inline-flex items-center gap-2 px-3 h-8 bg-[var(--sys-brand-soft)] rounded border border-[var(--sys-brand-border)] font-black text-[10px] text-[var(--sys-brand-solid)] uppercase tracking-widest shadow-sm">
          <span class="w-1.5 h-1.5 rounded-full bg-[var(--sys-brand-solid)] animate-pulse"></span>
          Internal Support Hub
        </div>
      </div>
    </div>

    <!-- ── Metrics Row ─────────────────────────────────────── -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 px-1">
      <div
        v-for="stat in stats"
        :key="stat.label"
        class="bg-[var(--sys-bg-surface)] rounded-lg border border-[var(--sys-border-subtle)] shadow-sm p-4 flex items-center gap-4 group hover:border-[var(--sys-brand-solid)] transition-all"
      >
        <div
          :class="stat.iconBg"
          class="w-10 h-10 rounded-md flex items-center justify-center shrink-0 border transition-all group-hover:scale-110"
        >
          <span class="material-symbols-outlined text-xl">{{ stat.icon }}</span>
        </div>
        <div class="bg-transparent flex flex-col overflow-hidden">
          <p class="text-[11px] font-bold text-[var(--sys-text-secondary)] mb-0.5 uppercase tracking-widest opacity-60 truncate">{{ stat.label }}</p>
          <p class="text-xl font-bold text-[var(--sys-text-primary)] m-0 leading-tight tracking-tight">{{ stat.value }}</p>
        </div>
      </div>
    </div>

    <!-- ── Toolbar ─────────────────────────────────────────── -->
    <div class="flex flex-col md:flex-row items-center gap-3 px-1 bg-transparent justify-between">
      <div class="flex flex-1 items-center gap-3 w-full md:w-auto">
        <!-- Search -->
        <div class="relative w-full md:w-80 group shrink-0 h-11">
          <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-[18px] text-[var(--sys-brand-solid)] opacity-60">search</span>
          <input
            v-model="searchQuery"
            type="text"
            placeholder="Mã ticket, nhân viên, tiêu đề..."
            class="w-full h-full pl-10 pr-4 rounded-md border border-[var(--sys-border-strong)] bg-[var(--sys-bg-surface)] text-[13px] font-bold text-[var(--sys-text-primary)] focus:outline-none focus:border-[var(--sys-brand-solid)] transition-all shadow-sm placeholder:font-normal placeholder:opacity-50"
          >
        </div>

        <!-- Category filter -->
        <Dropdown
          v-model="filterCategory"
          :options="categoryOptions"
          class="w-full md:w-60 h-11 shrink-0 shadow-sm"
        />
      </div>

      <!-- Tab switcher -->
      <div class="flex items-center gap-1 bg-[var(--sys-bg-page)] p-1 rounded-md border border-[var(--sys-border-subtle)] shadow-sm h-11 shrink-0">
        <button
          v-for="tab in tabs"
          :key="tab.id"
          @click="activeTab = tab.id"
          :class="[
            'px-4 h-full rounded-md text-[11px] font-bold tracking-wider uppercase transition-all flex items-center gap-2 whitespace-nowrap active:scale-95',
            activeTab === tab.id
              ? 'bg-[var(--sys-brand-soft)] text-[var(--sys-brand-solid)]'
              : 'text-[var(--sys-text-secondary)] hover:bg-[var(--sys-bg-hover)]'
          ]"
        >
          {{ tab.label }}
          <span
            v-if="getTabCount(tab.id) > 0"
            :class="[
              'px-1.5 py-0.5 rounded-md text-[10px] font-bold min-w-[18px] flex items-center justify-center',
              activeTab === tab.id ? 'bg-[var(--sys-brand-solid)] text-white' : 'bg-[var(--sys-danger-solid)] text-white'
            ]"
          >{{ getTabCount(tab.id) }}</span>
        </button>
      </div>
    </div>

    <!-- ── Ticket Table ────────────────────────────────────── -->
    <div class="bg-[var(--sys-bg-surface)] rounded-lg border border-[var(--sys-border-subtle)] shadow-sm overflow-hidden flex flex-col">
      <div class="overflow-x-auto custom-scrollbar bg-transparent">
        <table class="w-full text-[13px] text-left border-collapse">
          <thead class="bg-[var(--sys-bg-page)]">
            <tr>
              <th class="px-4 py-2.5 font-bold text-[11px] text-[var(--sys-text-secondary)] uppercase tracking-widest border-b border-[var(--sys-border-subtle)] w-[1%] whitespace-nowrap">Mã Ticket</th>
              <th class="px-4 py-2.5 font-bold text-[11px] text-[var(--sys-text-secondary)] uppercase tracking-widest border-b border-[var(--sys-border-subtle)] w-[15%] whitespace-nowrap">Nhân viên</th>
              <th class="px-4 py-2.5 font-bold text-[11px] text-[var(--sys-text-secondary)] uppercase tracking-widest border-b border-[var(--sys-border-subtle)] w-[1%] whitespace-nowrap">Phòng ban</th>
              <th class="px-4 py-2.5 font-bold text-[11px] text-[var(--sys-text-secondary)] uppercase tracking-widest border-b border-[var(--sys-border-subtle)] w-auto">Tiêu đề yêu cầu</th>
              <th class="px-4 py-2.5 font-bold text-[11px] text-[var(--sys-text-secondary)] uppercase tracking-widest border-b border-[var(--sys-border-subtle)] w-[1%] whitespace-nowrap">Loại dịch vụ</th>
              <th class="px-4 py-2.5 font-bold text-[11px] text-[var(--sys-text-secondary)] uppercase tracking-widest border-b border-[var(--sys-border-subtle)] w-[1%] whitespace-nowrap">Mức độ</th>
              <th class="px-4 py-2.5 font-bold text-[11px] text-[var(--sys-text-secondary)] uppercase tracking-widest border-b border-[var(--sys-border-subtle)] w-[1%] whitespace-nowrap">Ngày tạo</th>
              <th class="px-4 py-2.5 font-bold text-[11px] text-[var(--sys-text-secondary)] uppercase tracking-widest border-b border-[var(--sys-border-subtle)] w-[1%] whitespace-nowrap">Trạng thái</th>
              <th class="px-4 py-2.5 font-bold text-[11px] text-[var(--sys-text-secondary)] uppercase tracking-widest border-b border-[var(--sys-border-subtle)] w-[1%] whitespace-nowrap text-right">Thao tác</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-[var(--sys-border-subtle)]">
            <tr
              v-for="ticket in filteredTickets"
              :key="ticket.id"
              @click="openDetail(ticket)"
              class="hover:bg-[var(--sys-bg-hover)] transition-colors cursor-pointer group"
            >
              <td class="px-4 py-3 align-middle w-[1%] whitespace-nowrap">
                <span class="font-mono text-[11px] font-bold text-[var(--sys-brand-solid)]">{{ ticket.id }}</span>
              </td>
              <td class="px-4 py-3 align-middle w-[15%] whitespace-nowrap">
                <div class="flex items-center gap-2.5 h-full">
                  <div class="w-7 h-7 shrink-0 rounded-full bg-[var(--sys-brand-soft)] text-[var(--sys-brand-solid)] flex items-center justify-center font-bold text-[10px] uppercase border border-[var(--sys-brand-border)]">
                    {{ ticket.employeeName?.charAt(0) || '?' }}
                  </div>
                  <span class="text-[12px] font-bold text-[var(--sys-text-primary)] leading-none truncate max-w-[150px]">{{ ticket.employeeName }}</span>
                </div>
              </td>
              <td class="px-4 py-3 align-middle w-[1%] whitespace-nowrap">
                <div class="flex items-center h-full">
                  <span class="px-2 py-0.5 bg-[var(--sys-brand-soft)] text-[var(--sys-brand-solid)] border border-[var(--sys-brand-border)] rounded text-[10px] font-bold uppercase tracking-tight shadow-sm leading-none">{{ ticket.department }}</span>
                </div>
              </td>
              <td class="px-4 py-3 align-middle w-auto">
                <div class="flex items-center h-full">
                  <span class="text-[12px] font-medium text-[var(--sys-text-primary)] group-hover:text-[var(--sys-brand-solid)] transition-colors line-clamp-1 leading-none" title="Nhấn để xem chi tiết">{{ ticket.title }}</span>
                </div>
              </td>
              <td class="px-4 py-3 align-middle text-[12px] text-[var(--sys-text-secondary)] w-[1%] whitespace-nowrap">
                <div class="flex items-center h-full leading-none">{{ ticket.category }}</div>
              </td>
              <td class="px-4 py-3 align-middle w-[1%] whitespace-nowrap">
                <div class="flex items-center h-full">
                  <span :class="getPriorityClass(ticket.priority)" class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide leading-none whitespace-nowrap">
                    {{ ticket.priority }}
                  </span>
                </div>
              </td>
              <td class="px-4 py-3 align-middle text-[12px] text-[var(--sys-text-secondary)] w-[1%] whitespace-nowrap">
                <div class="flex items-center h-full leading-none">{{ ticket.date }}</div>
              </td>
              <td class="px-4 py-3 align-middle w-[1%] whitespace-nowrap">
                <div class="flex items-center h-full">
                  <span :class="getStatusClass(ticket.status)" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wide leading-none whitespace-nowrap">
                    <span class="w-1.5 h-1.5 rounded-full" :class="getStatusDotClass(ticket.status)"></span>
                    {{ ticket.status }}
                  </span>
                </div>
              </td>
              <td class="px-4 py-3 text-right bg-transparent align-middle w-[1%] whitespace-nowrap">
                <div class="flex items-center justify-end gap-1.5" @click.stop>
                  <button
                    @click="openDetail(ticket)"
                    class="w-8 h-8 rounded-md bg-[var(--sys-bg-page)] border border-[var(--sys-border-subtle)] text-[var(--sys-text-secondary)] hover:bg-[var(--sys-brand-soft)] hover:text-[var(--sys-brand-solid)] flex items-center justify-center transition-colors shadow-sm active:scale-95"
                    title="Xem chi tiết"
                  >
                    <span class="material-symbols-outlined text-[16px]">visibility</span>
                  </button>
                  <template v-if="ticket.status === 'Chờ xử lý' || ticket.status === 'Đang xử lý'">
                    <button
                      @click="handleComplete(ticket)"
                      class="w-8 h-8 rounded-md bg-[var(--sys-success-soft)] text-[var(--sys-success-text)] border border-[var(--sys-success-border)] flex items-center justify-center hover:bg-[var(--sys-success-solid)] hover:text-white transition-all shadow-sm active:scale-95"
                      title="Hoàn thành"
                    >
                      <span class="material-symbols-outlined text-[16px]">task_alt</span>
                    </button>
                    <button
                      @click="openReject(ticket)"
                      class="w-8 h-8 rounded-md bg-[var(--sys-danger-soft)] text-[var(--sys-danger-text)] border border-[var(--sys-danger-border)] flex items-center justify-center hover:bg-[var(--sys-danger-solid)] hover:text-white transition-all shadow-sm active:scale-95"
                      title="Từ chối"
                    >
                      <span class="material-symbols-outlined text-[16px]">cancel</span>
                    </button>
                  </template>
                  <span v-else class="text-[10px] font-bold text-[var(--sys-text-disabled)] uppercase tracking-wide opacity-50 px-2 py-1">Đã xử lý</span>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Empty State -->
      <div v-if="filteredTickets.length === 0" class="flex flex-col items-center justify-center py-20">
        <div class="w-16 h-16 rounded-full bg-white flex items-center justify-center mb-4 border border-[var(--sys-border-subtle)] shadow-xl">
          <span class="material-symbols-outlined text-[var(--sys-brand-solid)] text-3xl">inbox</span>
        </div>
        <p class="text-[12px] font-black text-[var(--sys-text-disabled)] uppercase tracking-[0.3em] opacity-40">Không có ticket nào</p>
        <p class="text-[10px] font-bold text-[var(--sys-text-disabled)] uppercase tracking-widest mt-2 opacity-30 italic">Tất cả yêu cầu hỗ trợ sẽ hiển thị tại đây.</p>
      </div>

      <!-- Table Footer -->
      <div v-if="filteredTickets.length > 0" class="px-4 py-3 border-t border-[var(--sys-border-subtle)] bg-[var(--sys-bg-page)]/50 flex items-center justify-between h-12">
        <p class="text-[11px] font-bold text-[var(--sys-text-secondary)] uppercase tracking-widest opacity-60">
          Hiển thị <strong>{{ filteredTickets.length }}</strong> / {{ tickets.length }} yêu cầu
        </p>
        <p class="text-[10px] font-bold text-[var(--sys-text-secondary)] uppercase tracking-widest opacity-50">
          Cập nhật lần cuối: {{ lastUpdated }}
        </p>
      </div>
    </div>

    <!-- ══════════════════════════════════════════
         DETAIL MODAL
    ══════════════════════════════════════════ -->
    <Teleport to="body">
      <Transition name="modal-fade">
        <div v-if="showDetailModal" class="fixed inset-0 z-[10001] flex items-center justify-center p-4" @click.self="showDetailModal = false">
          <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="showDetailModal = false"></div>
          <div class="relative bg-[var(--sys-bg-surface)] border border-[var(--sys-border-subtle)] w-full max-w-3xl rounded-xl shadow-2xl overflow-hidden flex flex-col animate-zoomIn">
            <!-- Header -->
            <div class="px-6 py-4 border-b border-[var(--sys-border-subtle)] bg-[var(--sys-bg-page)] flex items-center justify-between">
              <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-md bg-[var(--sys-brand-soft)] border border-[var(--sys-brand-border)] flex items-center justify-center">
                  <span class="material-symbols-outlined text-[20px] text-[var(--sys-brand-solid)]">support_agent</span>
                </div>
                <div>
                  <p class="text-[10px] font-black text-[var(--sys-text-secondary)] uppercase tracking-widest opacity-60 leading-none mb-0.5">Chi tiết Ticket</p>
                  <h3 class="text-[14px] font-bold text-[var(--sys-text-primary)] m-0 leading-tight font-mono">{{ selectedTicket?.id }}</h3>
                </div>
              </div>
              <button @click="showDetailModal = false" class="w-8 h-8 rounded-md hover:bg-[var(--sys-bg-hover)] text-[var(--sys-text-secondary)] flex items-center justify-center transition-all border border-[var(--sys-border-subtle)]">
                <span class="material-symbols-outlined text-[18px]">close</span>
              </button>
            </div>

            <!-- Body -->
            <div class="p-6 space-y-5 overflow-y-auto">
              <!-- Employee info -->
              <div class="flex items-center gap-4 p-4 bg-[var(--sys-bg-page)] rounded-lg border border-[var(--sys-border-subtle)]">
                <div class="w-12 h-12 rounded-full flex items-center justify-center text-white text-base font-bold shrink-0" :style="`background: ${selectedTicket?.avatarColor}`">
                  {{ selectedTicket?.employeeName?.charAt(0) }}
                </div>
                <div>
                  <p class="text-[14px] font-bold text-[var(--sys-text-primary)] leading-tight">{{ selectedTicket?.employeeName }}</p>
                  <p class="text-[11px] text-[var(--sys-text-secondary)] opacity-70">{{ selectedTicket?.department }} • Tạo lúc {{ selectedTicket?.date }}</p>
                </div>
                <div class="ml-auto">
                  <span :class="getPriorityClass(selectedTicket?.priority)" class="inline-flex items-center px-2.5 py-1 rounded text-[11px] font-bold uppercase tracking-wide">
                    {{ selectedTicket?.priority }}
                  </span>
                </div>
              </div>

              <!-- Fields grid -->
              <div class="grid grid-cols-2 gap-4">
                <div>
                  <p class="text-[10px] font-black text-[var(--sys-text-secondary)] uppercase tracking-widest opacity-60 mb-1">Tiêu đề</p>
                  <p class="text-[13px] font-semibold text-[var(--sys-text-primary)]">{{ selectedTicket?.title }}</p>
                </div>
                <div>
                  <p class="text-[10px] font-black text-[var(--sys-text-secondary)] uppercase tracking-widest opacity-60 mb-1">Loại dịch vụ</p>
                  <p class="text-[13px] font-semibold text-[var(--sys-text-primary)]">{{ selectedTicket?.category }}</p>
                </div>
                <div>
                  <p class="text-[10px] font-black text-[var(--sys-text-secondary)] uppercase tracking-widest opacity-60 mb-1">Thiết bị liên quan</p>
                  <p class="text-[13px] font-semibold text-[var(--sys-text-primary)]">{{ selectedTicket?.asset || '—' }}</p>
                </div>
                <div>
                  <p class="text-[10px] font-black text-[var(--sys-text-secondary)] uppercase tracking-widest opacity-60 mb-1">Hạn xử lý</p>
                  <p class="text-[13px] font-semibold text-[var(--sys-text-primary)]">{{ selectedTicket?.deadline || 'Không yêu cầu' }}</p>
                </div>
              </div>

              <!-- Description -->
              <div>
                <p class="text-[10px] font-black text-[var(--sys-text-secondary)] uppercase tracking-widest opacity-60 mb-2">Mô tả chi tiết</p>
                <div class="bg-[var(--sys-bg-page)] rounded-lg border border-[var(--sys-border-subtle)] p-4">
                  <p class="text-[13px] text-[var(--sys-text-secondary)] leading-relaxed italic font-medium">"{{ selectedTicket?.description }}"</p>
                </div>
              </div>

              <!-- Status + Note -->
              <div v-if="selectedTicket?.note" class="p-3.5 rounded-lg bg-[var(--sys-danger-soft)] border border-[var(--sys-danger-border)]">
                <p class="text-[10px] font-black text-[var(--sys-danger-text)] uppercase tracking-widest mb-1">Lý do từ chối</p>
                <p class="text-[12px] text-[var(--sys-danger-text)] font-medium">{{ selectedTicket?.note }}</p>
              </div>
            </div>

            <!-- Footer actions -->
            <div class="px-6 py-4 border-t border-[var(--sys-border-subtle)] bg-[var(--sys-bg-page)] flex justify-end gap-3">
              <button v-if="selectedTicket?.status === 'Chờ xử lý'" @click="handleProcess(selectedTicket)" class="h-9 px-5 bg-[var(--sys-warning-soft)] text-[var(--sys-warning-text)] border border-[var(--sys-warning-border)] rounded-md text-[11px] font-bold uppercase tracking-wide hover:bg-[var(--sys-warning-solid)] hover:text-white transition-all flex items-center gap-1.5 active:scale-95 whitespace-nowrap">
                <span class="material-symbols-outlined text-[16px]">engineering</span>
                Tiếp nhận & Xử lý
              </button>
              <button v-if="selectedTicket?.status === 'Chờ xử lý' || selectedTicket?.status === 'Đang xử lý'" @click="handleComplete(selectedTicket); showDetailModal = false" class="h-9 px-5 bg-[var(--sys-brand-solid)] text-white rounded-md text-[11px] font-bold uppercase tracking-wide hover:brightness-95 transition-all flex items-center gap-1.5 shadow-sm active:scale-95 whitespace-nowrap">
                <span class="material-symbols-outlined text-[16px]">task_alt</span>
                Đánh dấu Hoàn thành
              </button>
              <button v-if="selectedTicket?.status === 'Chờ xử lý' || selectedTicket?.status === 'Đang xử lý'" @click="showDetailModal = false; openReject(selectedTicket)" class="h-9 px-4 border border-[var(--sys-danger-border)] text-[var(--sys-danger-text)] rounded-md text-[11px] font-bold uppercase tracking-wide hover:bg-[var(--sys-danger-soft)] transition-all flex items-center gap-1.5 active:scale-95 whitespace-nowrap">
                <span class="material-symbols-outlined text-[16px]">cancel</span>
                Từ chối
              </button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>

    <!-- ══════════════════════════════════════════
         REJECT MODAL
    ══════════════════════════════════════════ -->
    <Teleport to="body">
      <Transition name="modal-fade">
        <div v-if="showRejectModal" class="fixed inset-0 z-[10002] flex items-center justify-center p-4">
          <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" @click="closeReject"></div>
          <div class="relative bg-[var(--sys-bg-surface)] border border-[var(--sys-border-subtle)] w-full max-w-lg rounded-xl shadow-2xl overflow-hidden flex flex-col animate-zoomIn">
            <!-- Header -->
            <div class="px-6 py-4 border-b border-[var(--sys-border-subtle)] bg-[var(--sys-bg-surface)] flex items-center justify-between">
              <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-md bg-[var(--sys-danger-soft)] text-[var(--sys-danger-text)] flex items-center justify-center border border-[var(--sys-danger-border)]">
                  <span class="material-symbols-outlined text-[20px]">rule</span>
                </div>
                <div>
                  <h3 class="text-[14px] font-bold text-[var(--sys-text-primary)] m-0 uppercase tracking-tight">Từ chối ticket hỗ trợ</h3>
                  <p class="text-[11px] text-[var(--sys-text-secondary)] font-bold uppercase tracking-widest opacity-60 m-0 mt-0.5">Mã: <span class="text-[var(--sys-brand-solid)] font-black">#{{ selectedTicket?.id }}</span></p>
                </div>
              </div>
              <button @click="closeReject" class="w-8 h-8 rounded flex items-center justify-center hover:bg-[var(--sys-bg-hover)] text-[var(--sys-text-secondary)] transition-all">
                <span class="material-symbols-outlined text-[18px]">close</span>
              </button>
            </div>

            <!-- Body -->
            <div class="p-6 space-y-5">
              <div class="grid grid-cols-2 gap-5">
                <div class="space-y-1.5">
                  <label class="text-[11px] font-bold text-[var(--sys-text-secondary)] uppercase tracking-widest block ml-1">Mã ticket</label>
                  <input type="text" :value="selectedTicket?.id" readonly class="w-full h-11 px-4 bg-[var(--sys-bg-page)] border border-[var(--sys-border-strong)] rounded-md text-[13px] font-bold text-[var(--sys-text-disabled)] outline-none uppercase font-mono shadow-sm">
                </div>
                <div class="space-y-1.5">
                  <label class="text-[11px] font-bold text-[var(--sys-text-secondary)] uppercase tracking-widest block ml-1">Nhân viên</label>
                  <input type="text" :value="selectedTicket?.employeeName" readonly class="w-full h-11 px-4 bg-[var(--sys-bg-page)] border border-[var(--sys-border-strong)] rounded-md text-[13px] font-bold text-[var(--sys-text-disabled)] outline-none shadow-sm">
                </div>
              </div>
              <div class="space-y-1.5">
                <label class="text-[11px] font-bold text-[var(--sys-text-primary)] uppercase tracking-widest block ml-1">Lý do từ chối <span class="text-[var(--sys-danger-solid)]">*</span></label>
                <textarea
                  v-model="rejectNote"
                  rows="4"
                  placeholder="Nêu rõ lý do từ chối để thông báo cho nhân viên..."
                  class="w-full px-4 py-3 bg-[var(--sys-bg-page)] border border-[var(--sys-border-strong)] rounded-md text-[13px] font-bold text-[var(--sys-text-primary)] focus:border-[var(--sys-danger-solid)] outline-none transition-all resize-none shadow-sm placeholder:font-normal placeholder:opacity-50"
                ></textarea>
                <p class="text-[10px] font-bold text-[var(--sys-danger-text)] uppercase tracking-widest opacity-70 italic mt-1 ml-1">* Lý do này sẽ được hiển thị cho nhân viên.</p>
              </div>
            </div>

            <!-- Footer -->
            <div class="px-6 py-4 border-t border-[var(--sys-border-subtle)] bg-[var(--sys-bg-surface)] flex justify-end items-center gap-3 h-16 shrink-0">
              <button @click="closeReject" class="h-9 px-6 text-[12px] font-bold text-[var(--sys-text-secondary)] hover:bg-[var(--sys-bg-hover)] rounded-md transition-all uppercase tracking-wide">Hủy</button>
              <button @click="confirmReject" class="h-9 px-6 bg-[var(--sys-danger-solid)] text-white rounded-md font-bold text-[12px] hover:brightness-110 shadow-lg transition-all uppercase tracking-widest flex items-center gap-2 active:scale-95">
                <span class="material-symbols-outlined text-[18px]">verified_user</span>
                Xác nhận từ chối
              </button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useConfirm } from '@/composables/useConfirm'
import { useSupportStore } from '@/composables/useSupportStore'
import Dropdown from '@/components/Dropdown.vue'

const { showAlert } = useConfirm()
const store = useSupportStore()
const tickets = store.tickets

// ── State ──────────────────────────────────────────────
const activeTab = ref('pending')
const searchQuery = ref('')
const intervalId = ref(null)

const categoryOptions = [
  { label: 'TẤT CẢ LOẠI DỊCH VỤ', value: 'TẤT CẢ LOẠI DỊCH VỤ' },
  { label: 'Hỗ trợ IT & Thiết bị', value: 'Hỗ trợ IT & Thiết bị' },
  { label: 'Hành chính & Văn phòng', value: 'Hành chính & Văn phòng' },
  { label: 'Phần mềm & Tài khoản', value: 'Phần mềm & Tài khoản' },
  { label: 'Cơ sở hạ tầng', value: 'Cơ sở hạ tầng' },
  { label: 'Nhân sự & Phúc lợi', value: 'Nhân sự & Phúc lợi' },
  { label: 'Khác', value: 'Khác' }
]
const filterCategory = ref('TẤT CẢ LOẠI DỊCH VỤ')
const showDetailModal = ref(false)
const showRejectModal = ref(false)
const selectedTicket = ref(null)
const rejectNote = ref('')

const lastUpdated = ref(new Date().toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' }))

// ── Lifecycle ──────────────────────────────────────────
onMounted(async () => {
  await store.fetchTickets()
  intervalId.value = setInterval(async () => {
    await store.fetchTickets()
    lastUpdated.value = new Date().toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' })
  }, 10000) // 10s auto-refresh
})

onUnmounted(() => {
  if (intervalId.value) clearInterval(intervalId.value)
})

// ── Tabs ───────────────────────────────────────────────
const tabs = [
  { id: 'pending',    label: 'Chờ xử lý' },
  { id: 'processing', label: 'Đang xử lý' },
  { id: 'done',       label: 'Hoàn thành' },
  { id: 'rejected',   label: 'Từ chối' },
]

const tabStatusMap = {
  pending:    'Chờ xử lý',
  processing: 'Đang xử lý',
  done:       'Hoàn thành',
  rejected:   'Từ chối',
}

// ── Computed ───────────────────────────────────────────
const filteredTickets = computed(() => {
  const statusFilter = tabStatusMap[activeTab.value]
  let list = store.tickets.value.filter(t => t.status === statusFilter)
  if (filterCategory.value !== 'TẤT CẢ LOẠI DỊCH VỤ') {
    list = list.filter(t => t.category === filterCategory.value)
  }
  if (searchQuery.value.trim()) {
    const q = searchQuery.value.toLowerCase()
    list = list.filter(t =>
      t.id.toLowerCase().includes(q) ||
      t.employeeName.toLowerCase().includes(q) ||
      t.title.toLowerCase().includes(q)
    )
  }
  return list
})

const stats = computed(() => [
  {
    label: 'Tổng Ticket',
    value: store.tickets.value.length,
    icon: 'confirmation_number',
    iconBg: 'bg-[var(--sys-brand-soft)] text-[var(--sys-brand-solid)] border-[var(--sys-brand-border)]',
  },
  {
    label: 'Chờ xử lý',
    value: store.tickets.value.filter(t => t.status === 'Chờ xử lý').length,
    icon: 'hourglass_empty',
    iconBg: 'bg-[var(--sys-warning-soft)] text-[var(--sys-warning-text)] border-[var(--sys-warning-border)]',
  },
  {
    label: 'Đang xử lý',
    value: store.tickets.value.filter(t => t.status === 'Đang xử lý').length,
    icon: 'engineering',
    iconBg: 'bg-[var(--sys-brand-soft)] text-[var(--sys-brand-solid)] border-[var(--sys-brand-border)]',
  },
  {
    label: 'Hoàn thành',
    value: store.tickets.value.filter(t => t.status === 'Hoàn thành').length,
    icon: 'check_circle',
    iconBg: 'bg-[var(--sys-success-soft)] text-[var(--sys-success-text)] border-[var(--sys-success-border)]',
  },
])

// ── Tab Count ──────────────────────────────────────────
function getTabCount(tabId) {
  return store.tickets.value.filter(t => t.status === tabStatusMap[tabId]).length
}

// ── Badge Helpers ──────────────────────────────────────
function getPriorityClass(p) {
  switch (p) {
    case 'Khẩn cấp': return 'bg-[var(--sys-danger-soft)] text-[var(--sys-danger-text)] border border-[var(--sys-danger-border)]'
    case 'Cao':       return 'bg-[var(--sys-warning-soft)] text-[var(--sys-warning-text)] border border-[var(--sys-warning-border)]'
    case 'Trung bình':return 'bg-[var(--sys-brand-soft)] text-[var(--sys-brand-soft-text)] border border-[var(--sys-brand-border)]'
    default:          return 'bg-[var(--sys-bg-active)] text-[var(--sys-text-secondary)] border border-[var(--sys-border-strong)]'
  }
}

function getStatusClass(s) {
  switch (s) {
    case 'Hoàn thành':  return 'bg-[var(--sys-success-soft)] text-[var(--sys-success-text)]'
    case 'Đang xử lý':  return 'bg-[var(--sys-warning-soft)] text-[var(--sys-warning-text)]'
    case 'Chờ xử lý':   return 'bg-[var(--sys-brand-soft)] text-[var(--sys-brand-soft-text)]'
    case 'Từ chối':     return 'bg-[var(--sys-danger-soft)] text-[var(--sys-danger-text)]'
    default:             return 'bg-[var(--sys-bg-active)] text-[var(--sys-text-secondary)]'
  }
}

function getStatusDotClass(s) {
  switch (s) {
    case 'Hoàn thành':  return 'bg-[var(--sys-success-solid)]'
    case 'Đang xử lý':  return 'bg-[var(--sys-warning-solid)]'
    case 'Chờ xử lý':   return 'bg-[var(--sys-brand-solid)]'
    case 'Từ chối':     return 'bg-[var(--sys-danger-solid)]'
    default:             return 'bg-[var(--sys-text-disabled)]'
  }
}

// ── Actions ────────────────────────────────────────────
function openDetail(ticket) {
  selectedTicket.value = ticket
  showDetailModal.value = true
}

function handleProcess(ticket) {
  store.updateStatus(ticket.id, 'Đang xử lý')
  lastUpdated.value = new Date().toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' })
}

function handleComplete(ticket) {
  store.updateStatus(ticket.id, 'Hoàn thành')
  lastUpdated.value = new Date().toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' })
}

function openReject(ticket) {
  selectedTicket.value = ticket
  rejectNote.value = ''
  showRejectModal.value = true
}

function closeReject() {
  showRejectModal.value = false
  rejectNote.value = ''
}

async function confirmReject() {
  if (!rejectNote.value.trim()) {
    await showAlert('THIẾU THÔNG TIN', 'Vui lòng nhập lý do từ chối!')
    return
  }
  if (selectedTicket.value) {
    store.updateStatus(selectedTicket.value.id, 'Từ chối', rejectNote.value)
    closeReject()
    lastUpdated.value = new Date().toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' })
  }
}
</script>

<style scoped>
@keyframes zoomIn {
  from { opacity: 0; transform: scale(0.96); }
  to   { opacity: 1; transform: scale(1); }
}
.animate-zoomIn { animation: zoomIn 0.25s cubic-bezier(0.16, 1, 0.3, 1) forwards; }

.modal-fade-enter-active, .modal-fade-leave-active { transition: opacity 0.2s ease; }
.modal-fade-enter-from, .modal-fade-leave-to { opacity: 0; }

/* Dark mode */
:global(html.dark) .bg-white {
  background-color: oklch(0.2 0.018 265) !important;
}
</style>
