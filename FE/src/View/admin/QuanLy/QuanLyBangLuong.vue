<template>
  <div class="space-y-6 pb-8">
    <!-- Header Area -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 bg-transparent text-left">
      <div class="bg-transparent text-left">
        <h1 class="text-xl font-semibold text-[var(--sys-text-primary)] mb-1">Quản lý Quyết toán & Bảng lương</h1>
        <p class="text-sm text-[var(--sys-text-secondary)]">Quản lý chu kỳ thu nhập, phê chuẩn thanh quyết toán và đối soát ngân quỹ nhân sự.</p>
      </div>
      <div class="flex items-center gap-3 text-left bg-transparent shrink-0">
        <button
          class="h-10 px-4 bg-[var(--sys-bg-surface)] border border-[var(--sys-border-strong)] text-[var(--sys-text-secondary)] rounded-md font-semibold text-sm hover:text-[var(--sys-brand-solid)] transition-all flex items-center gap-2 shadow-sm"
          @click="exportReport"
        >
          <span class="material-symbols-outlined text-[20px]">ios_share</span>
          Xuất báo cáo
        </button>
        <button @click="openAddModal" class="h-10 px-6 bg-[var(--sys-brand-solid)] rounded-md font-semibold text-white hover:brightness-90 transition-all flex items-center gap-2 text-sm shadow-sm">
          <span class="material-symbols-outlined text-[20px]">add_card</span>
          Tạo kỳ quyết toán
        </button>
      </div>
    </div>

    <!-- Stats Section -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
      <div v-for="(stat, idx) in stats" :key="idx" 
        class="bg-[var(--sys-bg-surface)] p-4 rounded-lg border border-[var(--sys-border-subtle)] shadow-sm hover:border-[var(--sys-brand-solid)] transition-all group flex items-center gap-4">
        <div class="w-12 h-12 rounded-md flex items-center justify-center bg-[var(--sys-brand-soft)] text-[var(--sys-brand-solid)] transition-all">
          <span class="material-symbols-outlined text-[24px]">{{ stat.icon }}</span>
        </div>
        <div class="bg-transparent flex flex-col flex-1">
          <div class="flex items-center justify-between mb-0.5">
            <p class="text-[12px] font-medium text-[var(--sys-text-secondary)] uppercase tracking-wide">{{ stat.label }}</p>
            <span :class="[
              'text-[10px] font-bold',
              stat.trend.startsWith('+') ? 'text-[var(--sys-success-text)]' : 'text-[var(--sys-danger-text)]'
            ]">
              {{ stat.trend }}
            </span>
          </div>
          <p class="text-xl font-bold text-[var(--sys-text-primary)] m-0 leading-tight">{{ stat.value }}</p>
        </div>
      </div>
    </div>

    <!-- Main Data Table Container -->
    <div class="bg-[var(--sys-bg-surface)] rounded-lg border border-[var(--sys-border-subtle)] shadow-sm overflow-hidden flex flex-col min-h-[500px]">
      <!-- Toolbar -->
      <div class="px-4 py-3 border-b border-[var(--sys-border-subtle)] flex flex-col xl:flex-row justify-between items-start xl:items-center gap-4 bg-[var(--sys-bg-page)]/50">
        <div class="flex items-center gap-1 bg-white p-1 rounded-md border border-[var(--sys-border-subtle)] shadow-sm overflow-x-auto max-w-full">
          <div
            v-for="period in visiblePeriods"
            :key="period.id"
            class="flex items-center rounded-md transition-all group/period"
            :class="selectedPeriodId === period.id
              ? 'bg-[var(--sys-brand-solid)] text-white shadow-sm'
              : 'text-[var(--sys-text-secondary)] hover:bg-[var(--sys-bg-hover)]'"
          >
            <button
              class="px-4 py-1.5 text-[13px] font-semibold whitespace-nowrap"
              @click="selectPeriod(period.id)"
            >
              {{ period.label }}
            </button>
            <button
              v-if="selectedPeriodId === period.id && !isPeriodClosed && employees.length === 0"
              @click.stop="openDeletePeriodModal(period)"
              class="w-6 h-6 mr-1 ml-[-4px] rounded-full flex flex-col items-center justify-center hover:bg-white/20 transition-all opacity-70 hover:opacity-100"
              title="Xóa kỳ lương trống"
            >
              <span class="material-symbols-outlined text-[16px] leading-[1]">close</span>
            </button>
          </div>
          <button 
            @click="createNewPeriod"
            class="w-8 h-8 shrink-0 flex items-center justify-center rounded-md text-[var(--sys-brand-solid)] hover:bg-[var(--sys-brand-soft)] transition-all ml-1 border border-dashed border-[var(--sys-brand-border)]"
            title="Thêm kỳ lương mới"
          >
            <span class="material-symbols-outlined text-[20px]">add</span>
          </button>
        </div>

        <div class="flex items-center gap-3 w-full xl:w-auto bg-transparent">
          <div class="relative flex-1 xl:w-80 group">
            <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-[20px] text-[var(--sys-brand-solid)]">search</span>
            <input 
              v-model="searchQuery"
              type="text" 
              placeholder="Tìm kiếm nhân viên..." 
              class="w-full h-10 pl-10 pr-4 bg-white border border-[var(--sys-border-strong)] rounded-md text-sm text-[var(--sys-text-primary)] focus:outline-none focus:border-[var(--sys-brand-solid)] focus:ring-1 focus:ring-[var(--sys-brand-solid)] transition-all placeholder:text-[var(--sys-text-disabled)]"
            >
          </div>
          <button
            class="shrink-0 h-10 px-4 bg-[var(--sys-brand-soft)] text-[var(--sys-brand-solid)] rounded-md text-sm font-semibold hover:bg-[var(--sys-brand-solid)] hover:text-white transition-all border border-[var(--sys-brand-border)] flex items-center gap-2 shadow-sm disabled:opacity-50 disabled:cursor-not-allowed"
            :disabled="!selectedPeriod || isPeriodClosed || isCalcLoading"
            @click="performBulkCalculation"
            title="Tự động tính và tạo phiếu lương cho tất cả nhân viên chưa có trong kỳ này"
          >
            <span class="material-symbols-outlined text-[20px]" :class="{'animate-spin': isCalcLoading}">auto_mode</span>
            Tính nhanh toàn bộ
          </button>
          <button 
            @click="handleDeleteCurrentPeriod" 
            :disabled="isLoading || isPeriodClosed"
            class="h-10 px-4 bg-red-50 text-red-600 border border-red-200 rounded-md flex items-center justify-center gap-2 hover:bg-red-600 hover:text-white transition-all disabled:opacity-30 disabled:cursor-not-allowed shadow-sm"
            title="Xóa nhanh toàn bộ kỳ lương và kết quả tính toán"
          >
            <span class="material-symbols-outlined text-[20px]">delete_forever</span>
            Xóa kỳ này
          </button>
          <button
            class="shrink-0 h-10 px-4 bg-[var(--sys-success-soft)] text-[var(--sys-success-text)] rounded-md text-sm font-semibold hover:bg-[var(--sys-success-solid)] hover:text-white transition-all border border-[var(--sys-success-border)] flex items-center gap-2 shadow-sm disabled:opacity-50 disabled:cursor-not-allowed"
            :disabled="!selectedPeriod || isPeriodClosed"
            @click="closeSelectedPeriod"
          >
            <span class="material-symbols-outlined text-[20px]">verified_user</span>
            Chốt kỳ lương
          </button>
        </div>
      </div>

      <!-- Table Section -->
      <div class="overflow-x-auto bg-transparent custom-scrollbar flex-grow">
        <table class="w-full text-left border-collapse">
          <thead class="bg-[var(--sys-bg-page)]">
            <tr>
              <th class="px-4 py-3 text-[12px] font-semibold text-[var(--sys-text-secondary)] border-b border-[var(--sys-border-subtle)] uppercase tracking-wider whitespace-nowrap">Hồ sơ thụ hưởng</th>
              <th class="px-4 py-3 text-[12px] font-semibold text-[var(--sys-text-secondary)] border-b border-[var(--sys-border-subtle)] uppercase tracking-wider text-right whitespace-nowrap">Mức lương cơ bản</th>
              <th class="px-4 py-3 text-[12px] font-semibold text-[var(--sys-text-secondary)] border-b border-[var(--sys-border-subtle)] uppercase tracking-wider text-right whitespace-nowrap">Thu nhập gộp</th>
              <th class="px-4 py-3 text-[12px] font-semibold text-[var(--sys-text-secondary)] border-b border-[var(--sys-border-subtle)] uppercase tracking-wider text-right whitespace-nowrap">Khấu trừ trích nộp</th>
              <th class="px-4 py-3 text-[12px] font-semibold text-[var(--sys-brand-solid)] border-b border-[var(--sys-border-subtle)] uppercase tracking-wider text-right bg-[var(--sys-brand-soft)]/20 whitespace-nowrap">Thực lĩnh (NET)</th>
              <th class="px-4 py-3 text-[12px] font-semibold text-[var(--sys-text-secondary)] border-b border-[var(--sys-border-subtle)] uppercase tracking-wider text-center whitespace-nowrap">Trạng thái</th>
              <th class="px-4 py-3 text-[12px] font-semibold text-[var(--sys-text-secondary)] border-b border-[var(--sys-border-subtle)] uppercase tracking-wider text-right whitespace-nowrap">Thao tác</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-[var(--sys-border-subtle)]">
            <tr v-for="(item, index) in pagedEmployees" :key="item.id || index" 
              class="group hover:bg-[var(--sys-bg-hover)] transition-all">
              <td class="px-4 py-3 whitespace-nowrap bg-transparent">
                <div class="flex flex-col bg-transparent">
                  <span class="text-[13px] font-semibold text-[var(--sys-text-primary)] transition-colors line-clamp-1 max-w-[200px]">{{ item.name }}</span>
                  <span class="text-[12px] text-[var(--sys-text-secondary)]">{{ item.role }}</span>
                </div>
              </td>
              <td class="px-4 py-3 whitespace-nowrap text-right text-[13px] text-[var(--sys-text-secondary)] font-medium">{{ formatCurrency(item.baseSalary) }}</td>
              <td class="px-4 py-3 whitespace-nowrap text-right text-[13px] text-[var(--sys-brand-solid)] font-semibold">+{{ formatCurrency(item.totalIncome) }}</td>
              <td class="px-4 py-3 whitespace-nowrap text-right text-[13px] text-[var(--sys-danger-text)] font-semibold">-{{ formatCurrency(item.deduction) }}</td>
              <td class="px-4 py-3 whitespace-nowrap text-right bg-[var(--sys-brand-soft)]/5">
                <span class="text-[14px] font-bold text-[var(--sys-brand-solid)]">{{ formatCurrency(item.netSalary) }}</span>
              </td>
              <td class="px-4 py-3 whitespace-nowrap text-center bg-transparent">
                <span 
                  class="px-2 py-0.5 rounded-md text-[11px] font-semibold border transition-all uppercase tracking-wide"
                  :class="getStatusClasses(item.status)"
                >
                  {{ item.status }}
                </span>
              </td>
              <td class="px-4 py-3 whitespace-nowrap text-right bg-transparent">
                <div class="flex items-center justify-end gap-1">
                  <button @click="openViewModal(item)" class="w-8 h-8 flex items-center justify-center rounded-md text-[var(--sys-text-secondary)] hover:bg-[var(--sys-brand-soft)] hover:text-[var(--sys-brand-solid)] transition-all" title="Xem chi tiết">
                    <span class="material-symbols-outlined text-[18px]">visibility</span>
                  </button>
                  <button v-if="!isPeriodClosed" @click="openEditModal(item, index)" class="w-8 h-8 flex items-center justify-center rounded-md text-[var(--sys-text-secondary)] hover:bg-[var(--sys-brand-soft)] hover:text-[var(--sys-brand-solid)] transition-all" title="Hiệu chỉnh">
                    <span class="material-symbols-outlined text-[18px]">edit_square</span>
                  </button>
                  <button v-if="!isPeriodClosed" @click="openDeleteModal(item, index)" class="w-8 h-8 flex items-center justify-center rounded-md text-[var(--sys-text-secondary)] hover:bg-[var(--sys-danger-soft)] hover:text-[var(--sys-danger-solid)] transition-all" title="Xóa">
                    <span class="material-symbols-outlined text-[18px]">delete</span>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div class="px-4 py-3 bg-[var(--sys-bg-page)] border-t border-[var(--sys-border-subtle)] flex flex-col sm:flex-row justify-between items-center gap-4 text-[12px] font-medium text-[var(--sys-text-secondary)]">
        <span>Hiển thị {{ pagedEmployees.length }} trên tổng số {{ filteredEmployees.length }} hồ sơ</span>
        <div class="flex items-center gap-1.5">
          <button
            class="w-8 h-8 flex items-center justify-center rounded-md bg-white border border-[var(--sys-border-subtle)] hover:text-[var(--sys-brand-solid)] transition-all disabled:opacity-50 disabled:cursor-not-allowed"
            :disabled="currentPage <= 1"
            @click="goPrevPage"
          >
            <span class="material-symbols-outlined text-[18px]">chevron_left</span>
          </button>
          <button
            v-for="page in visiblePageNumbers"
            :key="page"
            class="w-8 h-8 flex items-center justify-center rounded-md border border-[var(--sys-border-subtle)] transition-all font-bold"
            :class="currentPage === page ? 'bg-[var(--sys-brand-solid)] text-white' : 'bg-white hover:text-[var(--sys-brand-solid)]'"
            @click="currentPage = page"
          >
            {{ page }}
          </button>
          <button
            class="w-8 h-8 flex items-center justify-center rounded-md bg-white border border-[var(--sys-border-subtle)] hover:text-[var(--sys-brand-solid)] transition-all disabled:opacity-50 disabled:cursor-not-allowed"
            :disabled="currentPage >= totalPages"
            @click="goNextPage"
          >
            <span class="material-symbols-outlined text-[18px]">chevron_right</span>
          </button>
        </div>
      </div>
    </div>

    <!-- Modal System -->
    <Teleport to="body">
      <Transition name="modal-fade">
        <div v-if="isAddEditModalOpen" class="fixed inset-0 z-[10000] flex items-center justify-center p-4">
          <div class="fixed inset-0 w-screen h-screen bg-black/50 z-[9999]" @click="closeModal"></div>
          <div class="relative z-[10000] bg-[var(--sys-bg-surface-elevated)] border border-[var(--sys-border-subtle)] w-full max-w-2xl max-h-[90vh] rounded-lg shadow-xl overflow-hidden flex flex-col text-left">
            <!-- Modal Header -->
            <div class="px-6 py-4 border-b border-[var(--sys-border-subtle)] flex justify-between items-center bg-[var(--sys-bg-surface)]">
              <div class="bg-transparent text-left flex flex-col">
                <h3 class="text-lg font-semibold text-[var(--sys-text-primary)] m-0 uppercase tracking-tight">{{ modalTitle }}</h3>
                <p class="text-sm text-[var(--sys-text-secondary)] mt-1">Cấu hình tham số thu nhấp và quyết toán chi trả.</p>
              </div>
              <button @click="closeModal" class="w-8 h-8 flex items-center justify-center rounded-md hover:bg-[var(--sys-bg-hover)] transition-all text-[var(--sys-text-secondary)]">
                <span class="material-symbols-outlined text-xl">close</span>
              </button>
            </div>

            <!-- Modal Body -->
            <div class="flex-1 overflow-y-auto p-6 custom-scrollbar bg-transparent">
              <div class="space-y-6 bg-transparent border-none">

                <!-- Row 1: Employee Combobox + Period -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 bg-transparent border-none">

                  <!-- Employee Searchable Combobox -->
                  <div class="space-y-1.5 bg-transparent border-none relative" ref="empComboRef">
                    <label class="text-[13px] font-medium text-[var(--sys-text-primary)] block">Hồ sơ thụ hưởng *</label>
                    <div class="relative flex items-center gap-2">
                      <div class="relative flex-1">
                        <span v-if="modalMode === 'add'" class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-[18px] text-[var(--sys-brand-solid)] pointer-events-none">person_search</span>
                        <span v-else class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-[18px] text-[var(--sys-brand-solid)] pointer-events-none">badge</span>
                        <input
                          v-model="empSearchText"
                          :disabled="modalMode !== 'add'"
                          @input="onEmpInput"
                          @focus="showEmpDropdown = true"
                          @keydown.esc="showEmpDropdown = false"
                          placeholder="Tìm theo tên hoặc mã nhân viên..."
                          autocomplete="off"
                          class="w-full h-10 pl-9 pr-9 bg-[var(--sys-bg-page)] border border-[var(--sys-border-strong)] rounded-md text-sm text-[var(--sys-text-primary)] focus:border-[var(--sys-brand-solid)] focus:ring-1 focus:ring-[var(--sys-brand-solid)] outline-none transition-all disabled:opacity-80 disabled:bg-[var(--sys-bg-page)] disabled:cursor-not-allowed"
                        />
                        <span v-if="isCalcLoading" class="material-symbols-outlined absolute right-3 top-1/2 -translate-y-1/2 text-[18px] text-[var(--sys-brand-solid)] animate-spin">refresh</span>
                        <span v-else-if="empSearchText && modalMode === 'add'" @click="clearEmpSelection" class="material-symbols-outlined absolute right-3 top-1/2 -translate-y-1/2 text-[18px] text-[var(--sys-text-secondary)] cursor-pointer hover:text-[var(--sys-danger-solid)]">close</span>
                      </div>
                      
                      <!-- Load Button for Edit Mode (Icon only) -->
                      <button 
                        v-if="modalMode === 'edit'"
                        type="button"
                        @click="handleRecalculate"
                        :disabled="isCalcLoading"
                        class="w-10 h-10 flex items-center justify-center bg-[var(--sys-brand-soft)] text-[var(--sys-brand-solid)] border border-[var(--sys-brand-border)] rounded-md hover:bg-[var(--sys-brand-solid)] hover:text-white transition-all shrink-0 shadow-sm"
                        title="Tính toán lại lương dựa trên dữ liệu chấm công mới nhất"
                      >
                        <span class="material-symbols-outlined text-[20px]" :class="{'animate-spin': isCalcLoading}">sync</span>
                      </button>
                    </div>
                    <!-- Dropdown list -->
                    <div
                      v-if="showEmpDropdown && filteredEmpOptions.length > 0"
                      class="absolute z-[20000] left-0 right-0 top-[68px] bg-white border border-[var(--sys-border-strong)] rounded-md shadow-xl max-h-52 overflow-y-auto custom-scrollbar"
                    >
                      <button
                        v-for="emp in filteredEmpOptions"
                        :key="emp.employee_id"
                        type="button"
                        @mousedown.prevent="selectEmployee(emp)"
                        class="w-full text-left px-4 py-2.5 hover:bg-[var(--sys-brand-soft)] transition-colors flex items-center gap-3 border-b border-[var(--sys-border-subtle)] last:border-0"
                      >
                        <div class="w-8 h-8 rounded-full bg-[var(--sys-brand-solid)] flex items-center justify-center text-white text-[12px] font-bold shrink-0">
                          {{ (emp.full_name || '?')[0].toUpperCase() }}
                        </div>
                        <div class="flex flex-col">
                          <span class="text-[13px] font-semibold text-[var(--sys-text-primary)]">{{ emp.full_name }}</span>
                          <span class="text-[11px] text-[var(--sys-text-secondary)]">{{ emp.employee_code }} · {{ emp.department_name || 'Chưa gán phòng ban' }}</span>
                        </div>
                      </button>
                    </div>
                    <p v-if="showEmpDropdown && empSearchText && filteredEmpOptions.length === 0" class="absolute z-[20000] left-0 right-0 top-[68px] bg-white border border-[var(--sys-border-strong)] rounded-md shadow-xl px-4 py-3 text-[13px] text-[var(--sys-text-secondary)] text-center">
                      Không tìm thấy nhân viên nào
                    </p>
                  </div>

                  <!-- Period (read-only display) -->
                  <div class="space-y-1.5 bg-transparent border-none">
                    <label class="text-[13px] font-medium text-[var(--sys-text-primary)] block">Chu kỳ quyết toán</label>
                    <input v-model="formData.period" :disabled="modalMode === 'view'" class="w-full h-10 px-3 bg-[var(--sys-bg-page)] border border-[var(--sys-border-strong)] rounded-md text-sm text-[var(--sys-text-primary)] focus:border-[var(--sys-brand-solid)] outline-none transition-all" />
                  </div>
                </div>

                <!-- Auto-calc info banner -->
                <div v-if="calcPreview" class="flex items-center gap-3 bg-[var(--sys-success-soft)] border border-[var(--sys-success-border)] rounded-lg px-4 py-2.5 text-[12px] text-[var(--sys-success-text)] font-medium">
                  <span class="material-symbols-outlined text-[18px]">check_circle</span>
                  <span>Đã tính lương tự động: <b>{{ calcPreview.tong_cong }} ngày công</b> · OT: <b>{{ formatCurrency(calcPreview.tien_ot) }}</b> · Phạt muộn/sớm: <b>-{{ formatCurrency(calcPreview.tien_tru_muon_som) }}</b></span>
                </div>

                <!-- Row 2: Gross + Total Income -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 bg-transparent border-none">
                  <div class="space-y-1.5 bg-transparent border-none">
                    <label class="text-[13px] font-medium text-[var(--sys-text-primary)] block">Lương định biên (Gross)</label>
                    <input 
                      :value="formatNumber(formData.baseSalary)" 
                      @input="e => formData.baseSalary = parseNumber(e.target.value)"
                      type="text" 
                      :disabled="modalMode === 'view'" 
                      class="w-full h-10 px-3 bg-[var(--sys-bg-page)] border border-[var(--sys-border-strong)] rounded-md text-sm text-[var(--sys-text-primary)] focus:border-[var(--sys-brand-solid)] outline-none transition-all text-right font-semibold" 
                    />
                  </div>
                  <div class="space-y-1.5 bg-transparent border-none">
                    <label class="text-[13px] font-medium text-[var(--sys-text-primary)] block">Tổng thu nhập gộp</label>
                    <input 
                      :value="formatNumber(formData.totalIncome)" 
                      @input="e => formData.totalIncome = parseNumber(e.target.value)"
                      type="text" 
                      :disabled="modalMode === 'view'" 
                      class="w-full h-10 px-3 bg-[var(--sys-bg-page)] border border-[var(--sys-border-strong)] rounded-md text-sm text-[var(--sys-text-primary)] focus:border-[var(--sys-brand-solid)] outline-none transition-all text-right font-semibold" 
                    />
                  </div>
                </div>

                <!-- Row 3: Detailed Deductions -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 bg-transparent border-none">
                  <div class="space-y-1.5 bg-transparent border-none">
                    <label class="text-[13px] font-medium text-[var(--sys-text-primary)] block">Tiền Bảo hiểm (10.5%)</label>
                    <input 
                      :value="formatNumber(toNumber(formData.social_insurance_employee) + toNumber(formData.health_insurance_employee) + toNumber(formData.unemployment_insurance_employee))" 
                      readonly 
                      type="text" 
                      class="w-full h-10 px-3 bg-[var(--sys-bg-page)] border border-[var(--sys-border-strong)] rounded-md text-sm text-[var(--sys-danger-text)] font-semibold text-right outline-none opacity-80" 
                    />
                  </div>
                  <div class="space-y-1.5 bg-transparent border-none">
                    <label class="text-[13px] font-medium text-[var(--sys-text-primary)] block">Tiền Vi phạm / Nghỉ</label>
                    <input 
                      :value="formatNumber(formData.penalty)" 
                      @input="e => formData.penalty = parseNumber(e.target.value)"
                      type="text" 
                      :disabled="modalMode === 'view'"
                      class="w-full h-10 px-3 bg-[var(--sys-bg-page)] border border-[var(--sys-border-strong)] rounded-md text-sm text-[var(--sys-danger-text)] font-semibold text-right focus:border-[var(--sys-danger-solid)] outline-none transition-all" 
                    />
                  </div>
                  <div class="space-y-1.5 bg-transparent border-none">
                    <label class="text-[13px] font-medium text-[var(--sys-text-primary)] block">Tổng khấu trừ</label>
                    <input 
                      :value="formatNumber(toNumber(formData.social_insurance_employee) + toNumber(formData.health_insurance_employee) + toNumber(formData.unemployment_insurance_employee) + toNumber(formData.penalty))" 
                      readonly
                      type="text" 
                      class="w-full h-10 px-3 bg-[var(--sys-brand-soft)] border border-[var(--sys-danger-solid)] rounded-md text-sm text-[var(--sys-danger-text)] font-bold text-right outline-none" 
                    />
                  </div>
                </div>

                <!-- Net Salary card -->
                <div class="p-6 bg-[var(--sys-brand-soft)] rounded-lg border border-[var(--sys-brand-border)] flex items-center justify-between">
                  <div class="bg-transparent text-left">
                    <p class="text-[12px] font-semibold text-[var(--sys-brand-solid)] mb-1 uppercase tracking-wide">Thực nhận (NET Salary)</p>
                    <p v-if="isCalcLoading" class="text-xl font-bold text-[var(--sys-brand-solid)] m-0 leading-none flex items-center gap-2">
                      <span class="material-symbols-outlined text-[20px] animate-spin">refresh</span> Đang tính...
                    </p>
                    <p v-else class="text-3xl font-bold text-[var(--sys-brand-solid)] m-0 leading-none">
                      {{ formatCurrency(toNumber(formData.totalIncome) - (toNumber(formData.social_insurance_employee) + toNumber(formData.health_insurance_employee) + toNumber(formData.unemployment_insurance_employee) + toNumber(formData.penalty))) }}
                    </p>
                  </div>
                  <div class="w-12 h-12 rounded-md bg-white flex items-center justify-center border border-[var(--sys-brand-border)] shadow-sm">
                    <span class="material-symbols-outlined text-[32px] text-[var(--sys-brand-solid)]">payments</span>
                  </div>
                </div>

              </div>
            </div>

            <!-- Modal Footer -->
            <div class="px-6 py-4 border-t border-[var(--sys-border-subtle)] bg-[var(--sys-bg-surface)] flex justify-end gap-3">
              <button @click="closeModal" class="px-4 py-2 text-sm font-medium text-[var(--sys-text-secondary)] hover:bg-[var(--sys-bg-hover)] rounded-md transition-all uppercase tracking-wide">Hủy</button>
              <button v-if="modalMode !== 'view'" @click="saveData" class="px-6 py-2 bg-[var(--sys-brand-solid)] text-white rounded-md font-semibold text-sm hover:brightness-90 transition-all uppercase tracking-wide">
                Lưu hồ sơ tài chính
              </button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch, onUnmounted } from 'vue';
import ExcelJS from 'exceljs';
import { saveAs } from 'file-saver';
import { useConfirm } from '@/composables/useConfirm';
import { BE_API_BASE, getAccessToken } from '@/services/runtimeConfig.js';
import { apiRequest } from '@/services/beApi.js';
import { handleUnauthorized } from '@/services/session.js';
import { getEmployees } from '@/services/employeeStore.js';

const { showAlert, showConfirm } = useConfirm();

const salaryDetailsRaw = ref([]);
const employeesRaw = ref([]);
const periodsRaw = ref([]);
const isLoading = ref(false);

const searchQuery = ref('');
const currentPage = ref(1);
const pageSize = 12;
const selectedPeriodId = ref(null);
const currentYear = new Date().getFullYear();
const currentMonth = new Date().getMonth() + 1;

const toNumber = (value, fallback = 0) => {
  const parsed = Number(value);
  return Number.isFinite(parsed) ? parsed : fallback;
};

const normalizeText = (value) => String(value ?? '').trim().toLowerCase();
const displayText = (value) => String(value ?? '').replace(/\s+/g, ' ').trim();

const formatCurrency = (val) => new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(toNumber(val));
const formatNumber = (val) => new Intl.NumberFormat('vi-VN').format(toNumber(val));

const parseNumber = (val) => {
  if (typeof val === 'number') return val;
  return toNumber(String(val).replace(/[^0-9]/g, ''));
};



const formatPeriodLabel = (period) => {
  const month = toNumber(period.month ?? period.month_number ?? null, null);
  const year = toNumber(period.year ?? null, null);
  if (month && year) return `Tháng ${String(month).padStart(2, '0')} / ${year}`;
  return period.period_name ?? period.periodName ?? period.period_code ?? 'Kỳ lương';
};

const sortedPeriods = computed(() =>
  [...periodsRaw.value].sort((a, b) => {
    const ay = toNumber(a.year, 0);
    const by = toNumber(b.year, 0);
    if (ay !== by) return by - ay;
    return toNumber(b.month, 0) - toNumber(a.month, 0);
  }),
);

const visiblePeriods = computed(() =>
  sortedPeriods.value.map((period) => ({
    ...period,
    id: Number(period.period_id || period.periodId || period.id),
    label: formatPeriodLabel(period),
  })),
);

const selectedPeriod = computed(() => {
  const selectedId = Number(selectedPeriodId.value || 0);
  return sortedPeriods.value.find((period) => Number(period.period_id || period.periodId || period.id) === selectedId) || null;
});

const isPeriodClosed = computed(() => {
  const status = String(selectedPeriod.value?.status || '').toUpperCase();
  return ['CLOSED', 'PAID'].includes(status);
});

const employeeMap = computed(() =>
  new Map(
    employeesRaw.value.map((emp) => [String(emp.employee_id || emp.employeeId || emp.id), emp]),
  ),
);

const periodMap = computed(() =>
  new Map(
    periodsRaw.value.map((period) => [String(period.period_id || period.periodId || period.id), period]),
  ),
);

const employees = computed(() => {
  const selectedId = Number(selectedPeriodId.value || 0);
  return salaryDetailsRaw.value
    .filter((item) => Number(item.period_id || item.periodId) === selectedId)
    .map((item) => {
      const salaryId = Number(item.salary_detail_id || item.salaryDetailId || item.salary_id || item.id || 0);
      const employeeId = Number(item.employee_id || item.employeeId || 0);
      const periodId = Number(item.period_id || item.periodId || 0);
      const emp = employeeMap.value.get(String(employeeId)) || {};
      const period = periodMap.value.get(String(periodId)) || {};
      const base = toNumber(item.basic_salary ?? item.basicSalary);
      const gross = toNumber(item.gross_salary ?? item.grossSalary, base);
      const deductions = toNumber(
        item.total_deductions
          ?? item.totalDeductions
          ?? (toNumber(item.personal_income_tax ?? item.personalIncomeTax) + toNumber(item.penalty)),
      );
      const net = toNumber(item.net_salary ?? item.netSalary, gross - deductions);
      const statusRaw = String(item.transfer_status ?? item.transferStatus ?? '').toUpperCase();
      const status = statusRaw === 'TRANSFERRED' ? 'Đã thanh toán' : 'Chờ thanh toán';
      const employeeCode = displayText(emp.employee_code ?? emp.employeeCode ?? item.employee_code ?? item.employeeCode);
      const employeeName = displayText(emp.full_name ?? emp.fullName ?? item.full_name ?? item.fullName);
      const departmentName = displayText(emp.department_name ?? emp.departmentName ?? item.department_name ?? item.departmentName);
      const positionName = displayText(emp.position_name ?? emp.positionName ?? item.position_name ?? item.positionName);
      const displayRole = departmentName || positionName || 'Chưa gán phòng ban';
      return {
        id: salaryId,
        salaryId,
        period_id: periodId,
        employee_id: employeeId,
        empId: employeeCode || `NV${String(employeeId).padStart(4, '0')}`,
        name: employeeName || (employeeCode ? `Nhân sự ${employeeCode}` : 'Nhân sự chưa cập nhật'),
        role: displayRole,
        baseSalary: base,
        totalIncome: gross,
        deduction: deductions,
        netSalary: net,
        status,
        period: formatPeriodLabel(period),
        social_insurance_employee: toNumber(item.social_insurance_employee ?? item.socialInsuranceEmployee),
        health_insurance_employee: toNumber(item.health_insurance_employee ?? item.healthInsuranceEmployee),
        unemployment_insurance_employee: toNumber(item.unemployment_insurance_employee ?? item.unemploymentInsuranceEmployee),
        penalty: toNumber(item.penalty),
      };
    });
});

const filteredEmployees = computed(() => {
  const q = normalizeText(searchQuery.value);
  if (!q) return employees.value;
  return employees.value.filter((item) => normalizeText(item.name).includes(q) || normalizeText(item.empId).includes(q));
});

const totalPages = computed(() => Math.max(1, Math.ceil(filteredEmployees.value.length / pageSize)));

const pagedEmployees = computed(() => {
  const start = (currentPage.value - 1) * pageSize;
  return filteredEmployees.value.slice(start, start + pageSize);
});

const visiblePageNumbers = computed(() => {
  const total = totalPages.value;
  const current = currentPage.value;
  const start = Math.max(1, current - 2);
  const end = Math.min(total, start + 4);
  const pages = [];
  for (let i = start; i <= end; i += 1) pages.push(i);
  return pages;
});

const goPrevPage = () => {
  if (currentPage.value > 1) currentPage.value -= 1;
};

const goNextPage = () => {
  if (currentPage.value < totalPages.value) currentPage.value += 1;
};

watch([searchQuery, selectedPeriodId], () => {
  currentPage.value = 1;
});

watch(totalPages, (nextTotal) => {
  if (currentPage.value > nextTotal) currentPage.value = nextTotal;
  if (currentPage.value < 1) currentPage.value = 1;
});

const selectPeriod = (periodId) => {
  selectedPeriodId.value = Number(periodId);
  loadData(); // Tải lại dữ liệu cho kỳ được chọn
};

const createNewPeriod = async () => {
  if (sortedPeriods.value.length === 0) {
    const now = new Date();
    await createPeriod(now.getMonth() + 1, now.getFullYear());
    return;
  }

  // sortedPeriods is descending by (year, month)
  const latest = sortedPeriods.value[0];
  let nextMonth = toNumber(latest.month) + 1;
  let nextYear = toNumber(latest.year);

  if (nextMonth > 12) {
    nextMonth = 1;
    nextYear += 1;
  }

  await createPeriod(nextMonth, nextYear);
};

const createPeriod = async (month, year) => {
  const periodCode = `PAY-${year}-${String(month).padStart(2, '0')}`;
  const periodName = `Lương tháng ${String(month).padStart(2, '0')}/${year}`;
  const startDate = `${year}-${String(month).padStart(2, '0')}-01`;
  const lastDay = new Date(year, month, 0).getDate();
  const endDate = `${year}-${String(month).padStart(2, '0')}-${String(lastDay).padStart(2, '0')}`;
  
  let payYear = year;
  let payMonth = month + 1;
  if (payMonth > 12) {
    payMonth = 1;
    payYear += 1;
  }
  const paymentDate = `${payYear}-${String(payMonth).padStart(2, '0')}-05`;

  try {
    isLoading.value = true;
    const res = await apiRequest('/salary-periods', {
      method: 'POST',
      body: {
        period_code: periodCode,
        period_name: periodName,
        period_type: 'MONTHLY',
        year: year,
        month: month,
        start_date: startDate,
        end_date: endDate,
        payment_date: paymentDate,
        standard_working_days: 26,
        status: 'OPEN',
        notes: `Kỳ lương mới được tạo tự động cho tháng ${month}/${year}`
      }
    });

    const periodId = res?.data?.period_id || res?.data?.id;
    /*
    if (periodId) {
      // Tự động tính toán lương cho tất cả nhân viên ngay khi tạo kì
      await apiRequest(`/salary-periods/${periodId}/calculate-and-store`, { method: 'POST' });
    }
    */
    
    await showAlert('Thành công', `Đã tạo kỳ lương ${periodName} thành công.`);
    selectedPeriodId.value = periodId || null;
    await loadData();
  } catch (error) {
    await showAlert('Lỗi', error.message || 'Không thể tạo kỳ lương mới.');
  } finally {
    isLoading.value = false;
  }
};

const loadData = async () => {
  isLoading.value = true;
  try {
    // 1. Tải danh sách kỳ lương và nhân viên trước
    const [employeePayload, periodPayload] = await Promise.all([
      apiRequest('/employees?page=1&per_page=2000', { noGetCache: true }),
      apiRequest('/salary-periods?page=1&per_page=500', { noGetCache: true }),
    ]);

    employeesRaw.value = Array.isArray(employeePayload.data) ? employeePayload.data : (Array.isArray(employeePayload) ? employeePayload : []);
    periodsRaw.value = Array.isArray(periodPayload.data) ? periodPayload.data : (Array.isArray(periodPayload) ? periodPayload : []);

    // 2. Xác định kỳ lương cần hiển thị
    if (periodsRaw.value.length > 0) {
      const getLatestId = () => {
        const sorted = [...periodsRaw.value].sort((a, b) => {
          const ay = Number(a.year || 0);
          const by = Number(b.year || 0);
          if (ay !== by) return by - ay;
          return Number(b.month || 0) - Number(a.month || 0);
        });
        return Number(sorted[0].period_id || sorted[0].periodId || sorted[0].id || 0);
      };

      if (!selectedPeriodId.value || !periodsRaw.value.some((p) => Number(p.period_id || p.periodId || p.id) === Number(selectedPeriodId.value))) {
        selectedPeriodId.value = getLatestId();
      }
    } else {
      selectedPeriodId.value = null;
    }

    // 3. Tải phiếu lương cho kỳ đã chọn (nếu có)
    if (selectedPeriodId.value) {
      const salaryPayload = await apiRequest(`/salary-details?page=1&per_page=3000&period_id=${selectedPeriodId.value}`, { noGetCache: true });
      salaryDetailsRaw.value = Array.isArray(salaryPayload.data) ? salaryPayload.data : (Array.isArray(salaryPayload) ? salaryPayload : []);
    } else {
      salaryDetailsRaw.value = [];
    }

  } catch (error) {
    console.error('Lỗi khi tải dữ liệu:', error);
    await showAlert('Lỗi', error?.message || 'Không thể tải dữ liệu bảng lương.');
  } finally {
    isLoading.value = false;
  }
};

onMounted(() => {
  void loadData();
});

const stats = computed(() => {
  const list = employees.value;
  const totalBudget = list.reduce((acc, item) => acc + toNumber(item.totalIncome), 0);
  const totalTax = list.reduce((acc, item) => acc + toNumber(item.deduction), 0);
  const avgIncome = list.length > 0 ? Math.floor(totalBudget / list.length) : 0;
  return [
    { label: 'Tổng ngân quỹ lương', value: formatCurrency(totalBudget), icon: 'account_balance', trend: 'LIVE' },
    { label: 'Nhân sự thụ hưởng', value: String(list.length), icon: 'badge', trend: 'LIVE' },
    { label: 'Thu nhập bình quân', value: formatCurrency(avgIncome), icon: 'query_stats', trend: 'LIVE' },
    { label: 'Tổng khấu trừ thuế', value: formatCurrency(totalTax), icon: 'savings', trend: 'LIVE' },
  ];
});

const getStatusClasses = (status) => {
  if (status === 'Đã thanh toán') return 'bg-[var(--sys-success-soft)] text-[var(--sys-success-text)] border-[var(--sys-success-border)]';
  if (status === 'Chờ thanh toán') return 'bg-[var(--sys-warning-soft)] text-[var(--sys-warning-text)] border-[var(--sys-warning-border)]';
  return 'bg-[var(--sys-bg-page)] text-[var(--sys-text-disabled)] border-[var(--sys-border-subtle)] opacity-50';
};

const isAddEditModalOpen = ref(false);
const modalMode = ref('add');
const formData = ref({});

// ---- Employee combobox state ----
const empSearchText = ref('');
const showEmpDropdown = ref(false);
const isCalcLoading = ref(false);
const calcPreview = ref(null);
const empComboRef = ref(null);

const filteredEmpOptions = computed(() => {
  const q = normalizeText(empSearchText.value);
  if (!q) return employeesRaw.value.slice(0, 30);
  return employeesRaw.value
    .filter(emp => {
      const name = normalizeText(emp.full_name || emp.fullName || '');
      const code = normalizeText(emp.employee_code || emp.employeeCode || '');
      const dept = normalizeText(emp.department_name || '');
      return name.includes(q) || code.includes(q) || dept.includes(q);
    })
    .slice(0, 30);
});

const onEmpInput = () => {
  showEmpDropdown.value = true;
  calcPreview.value = null;
};

const clearEmpSelection = () => {
  empSearchText.value = '';
  calcPreview.value = null;
  formData.value = { ...formData.value, employee_id: 0, name: '', baseSalary: 0, totalIncome: 0, deduction: 0, netSalary: 0 };
  showEmpDropdown.value = false;
};

const closeEmpDropdownOnOutsideClick = (e) => {
  if (empComboRef.value && !empComboRef.value.contains(e.target)) {
    showEmpDropdown.value = false;
  }
};

onMounted(() => document.addEventListener('mousedown', closeEmpDropdownOnOutsideClick));
onUnmounted(() => document.removeEventListener('mousedown', closeEmpDropdownOnOutsideClick));

const performSalaryCalculation = async (empId) => {
  if (!empId) return;

  // Find period date range for calculation
  const period = selectedPeriod.value || periodsRaw.value[0];
  if (!period) return;

  const dateFrom = period.start_date || period.startDate || '';
  const dateTo = period.end_date || period.endDate || '';
  if (!dateFrom || !dateTo) return;

  // Call preview API
  isCalcLoading.value = true;
  calcPreview.value = null; // Clear previous preview
  try {
    const res = await apiRequest('/salary-details/calculate-preview', {
      method: 'POST',
      body: {
        employee_id: empId,
        date_from: dateFrom,
        date_to: dateTo,
        // Để trống luong_thang để backend tự tra cứu từ hợp đồng
        luong_thang: 0,
        cong_chuan: toNumber(period.standard_working_days, 26),
      },
    });
    
    // apiRequest từ beApi.js trả về payload trực tiếp
    const result = res.data || {};
    calcPreview.value = result;

    const luongCong = toNumber(result.luong_cong);
    const tienOt = toNumber(result.tien_ot);
    const tienBhxh = toNumber(result.tien_bhxh); // Trợ cấp BHXH (ốm đau) nếu có
    const tienTruMuonSom = toNumber(result.tien_tru_muon_som);
    const tienTruKhongPhep = toNumber(result.tien_tru_khong_phep);
    
    // Thu nhập gộp = Lương công + OT + Thưởng/Trợ cấp (BHXH chi trả)
    const gross = luongCong + tienOt + tienBhxh;
    
    // Khấu trừ bảo hiểm từ nhân viên (mới cập nhật từ BE)
    const bhxh_emp = toNumber(result.social_insurance_employee);
    const bhyt_emp = toNumber(result.health_insurance_employee);
    const bhtn_emp = toNumber(result.unemployment_insurance_employee);
    
    const penaltyTotal = tienTruMuonSom + tienTruKhongPhep;
    const totalDeductions = bhxh_emp + bhyt_emp + bhtn_emp + penaltyTotal;
    const net = toNumber(result.luong_thuc_nhan, gross - totalDeductions);

    formData.value = {
      ...formData.value,
      baseSalary: toNumber(result.luong_thang || result.baseSalary || 0),
      totalIncome: gross,
      deduction: totalDeductions,
      netSalary: net,
      // Thêm các trường chi tiết
      social_insurance_employee: bhxh_emp,
      health_insurance_employee: bhyt_emp,
      unemployment_insurance_employee: bhtn_emp,
      penalty: penaltyTotal
    };
  } catch (err) {
    console.error('Calculation Error:', err);
    // Nếu lỗi, thử lấy lương từ thông tin nhân viên (nếu có)
    const emp = employeesRaw.value.find(e => Number(e.employee_id || e.id) === empId);
    const basicSalary = toNumber(emp?.basic_salary || emp?.basicSalary, 0);
    formData.value = { ...formData.value, baseSalary: basicSalary, totalIncome: basicSalary, deduction: 0, netSalary: basicSalary };
  } finally {
    isCalcLoading.value = false;
  }
};

const selectEmployee = async (emp) => {
  showEmpDropdown.value = false;
  const empId = Number(emp.employee_id || emp.employeeId || emp.id || 0);
  empSearchText.value = `${emp.full_name || emp.fullName} (${emp.employee_code || emp.employeeCode})`;
  formData.value.employee_id = empId;
  formData.value.name = emp.full_name || emp.fullName || '';
  
  await performSalaryCalculation(empId);
};

const handleRecalculate = async () => {
  if (formData.value.employee_id) {
    await performSalaryCalculation(formData.value.employee_id);
  }
};

const modalTitle = computed(() => {
  if (modalMode.value === 'add') return 'Tạo hồ sơ quyết toán mới';
  if (modalMode.value === 'edit') return 'Điều chỉnh thông tin tài chính';
  return 'Chi tiết bảng lương nhân sự';
});

const openAddModal = () => {
  const defaultPeriod = selectedPeriod.value || periodsRaw.value[0] || {};
  modalMode.value = 'add';
  empSearchText.value = '';
  calcPreview.value = null;
  formData.value = {
    employee_id: 0,
    period_id: Number(defaultPeriod.period_id || defaultPeriod.periodId || defaultPeriod.id || 0),
    name: '',
    period: formatPeriodLabel(defaultPeriod),
    baseSalary: 0,
    totalIncome: 0,
    deduction: 0,
    netSalary: 0,
    status: 'Chờ thanh toán',
  };
  isAddEditModalOpen.value = true;
};

const openEditModal = (item) => {
  modalMode.value = 'edit';
  empSearchText.value = item.name || '';
  calcPreview.value = null;
  formData.value = { ...item };
  isAddEditModalOpen.value = true;
};

const openViewModal = (item) => {
  modalMode.value = 'view';
  empSearchText.value = item.name || '';
  calcPreview.value = null;
  formData.value = { ...item };
  isAddEditModalOpen.value = true;
};

const closeModal = () => {
  isAddEditModalOpen.value = false;
};

const resolveEmployeeId = () => {
  if (formData.value.employee_id) return Number(formData.value.employee_id);
  const search = normalizeText(formData.value.name);
  const found = employeesRaw.value.find((emp) => {
    const fullName = normalizeText(emp.full_name || emp.fullName);
    const code = normalizeText(emp.employee_code || emp.employeeCode);
    return fullName === search || code === search;
  });
  return Number(found?.employee_id || found?.employeeId || found?.id || 0);
};

const resolvePeriodId = () => {
  if (formData.value.period_id) return Number(formData.value.period_id);
  const search = normalizeText(formData.value.period);
  const found = periodsRaw.value.find((period) =>
    normalizeText(formatPeriodLabel(period)) === search
    || normalizeText(period.period_code || period.periodCode) === search,
  );
  return Number(found?.period_id || found?.periodId || found?.id || 0);
};

// Tính toán toàn bộ nhân viên cho kỳ hiện tại
const performBulkCalculation = async () => {
  if (!selectedPeriodId.value) return;
  const periodId = selectedPeriodId.value;
  isCalcLoading.value = true;
  try {
    const res = await apiRequest(`/salary-periods/${periodId}/calculate-and-store`, {
      method: 'POST'
    });
    
    await showAlert('Thành công', res.message || `Đã tự động tính và tạo phiếu lương thành công.`);
    await loadData(); // Tải lại danh sách
  } catch (error) {
    await showAlert('Lỗi', error.message || 'Không thể thực hiện tính toán hàng loạt.');
  } finally {
    isCalcLoading.value = false;
  }
};

const handleDeleteCurrentPeriod = async () => {
  if (!selectedPeriodId.value) return;
  const periodId = selectedPeriodId.value;
  const period = selectedPeriod.value;
  const label = period ? formatPeriodLabel(period) : 'kỳ lương này';

  const confirmed = await showConfirm(
    'Xác nhận xóa',
    `Bạn có chắc chắn muốn xóa ${label}? Hành động này sẽ xóa toàn bộ phiếu lương bên trong và không thể hoàn tác.`
  );

  if (confirmed) {
    isLoading.value = true;
    try {
      await apiRequest(`/salary-periods/${periodId}`, { 
        method: 'DELETE'
      });
      await showAlert('Thành công', `Đã xóa ${label} thành công.`);
      selectedPeriodId.value = null; // Quay về mặc định
      await loadData();
    } catch (error) {
      await showAlert('Lỗi', error.message || 'Không thể xóa kỳ lương.');
    } finally {
      isLoading.value = false;
    }
  }
};

const saveData = async () => {
  const employeeId = resolveEmployeeId();
  const periodId = resolvePeriodId();
  const baseSalary = toNumber(formData.value.baseSalary);
  const totalIncome = toNumber(formData.value.totalIncome);
  
  const totalIns = toNumber(formData.value.social_insurance_employee) + toNumber(formData.value.health_insurance_employee) + toNumber(formData.value.unemployment_insurance_employee);
  const penalty = toNumber(formData.value.penalty);
  const deduction = totalIns + penalty;
  const net = totalIncome - deduction;

  if (!employeeId || !periodId) {
    await showAlert('Thiếu thông tin', 'Cần chọn nhân sự và kỳ lương hợp lệ trước khi lưu.');
    return;
  }

  const payload = {
    employee_id: employeeId,
    period_id: periodId,
    basic_salary: baseSalary,
    gross_salary: totalIncome,
    total_allowances: Math.max(totalIncome - baseSalary, 0),
    total_deductions: Math.max(deduction, 0),
    net_salary: net,
    social_insurance_employee: toNumber(formData.value.social_insurance_employee),
    health_insurance_employee: toNumber(formData.value.health_insurance_employee),
    unemployment_insurance_employee: toNumber(formData.value.unemployment_insurance_employee),
    penalty: penalty,
    transfer_status: formData.value.status === 'Đã thanh toán' ? 'TRANSFERRED' : 'PENDING',
  };

  try {
    // Đảm bảo các con số không âm trước khi gửi lên backend
    const finalPayload = {
      ...payload,
      net_salary: Math.max(0, payload.net_salary || 0),
      total_deductions: Math.max(0, payload.total_deductions || 0)
    };

    if (modalMode.value === 'add') {
      try {
        await apiRequest('/salary-details', { method: 'POST', body: finalPayload });
      } catch (postErr) {
        // Nếu lỗi Duplicate (1062) từ MySQL, ta thử tìm bản ghi cũ để PATCH
        if (postErr.message.includes('1062') || postErr.message.includes('Duplicate')) {
          // Tìm ID của bản ghi đang bị trùng trong database
          const existing = salaryDetailsRaw.value.find(s => 
            Number(s.employee_id || s.employeeId) === employeeId && 
            Number(s.period_id || s.periodId) === periodId
          );
          if (existing) {
            const sid = Number(existing.salary_detail_id || existing.id);
            await apiRequest(`/salary-details/${sid}`, { method: 'PATCH', body: finalPayload });
          } else {
            // Nếu không tìm thấy trong cache, ta báo lỗi cũ
            throw postErr;
          }
        } else {
          throw postErr;
        }
      }
    } else {
      await apiRequest(`/salary-details/${formData.value.id}`, { method: 'PATCH', body: finalPayload });
    }
    await loadData();
    closeModal();
    await showAlert('Thành công', 'Đã lưu hồ sơ bảng lương.');
  } catch (error) {
    await showAlert('Lưu thất bại', error?.message || 'Không thể lưu hồ sơ bảng lương.');
  }
};

const openDeleteModal = async (item) => {
  // Kiểm tra ID hợp lệ
  const targetId = Number(item.id || item.salaryId || 0);
  
  if (targetId <= 0) {
    // Trường hợp bản ghi "ma" - hiện trên UI nhưng không có trong DB
    const ok = await showConfirm('Dọn dẹp danh sách', 'Bản ghi này chưa được lưu chính thức vào máy chủ. Bạn có muốn xóa nó khỏi danh sách hiển thị không?');
    if (ok) {
      // Chỉ cần reload lại data để xóa các bản ghi tạm
      await loadData();
    }
    return;
  }

  const ok = await showConfirm('Xác nhận xóa phiếu lương', `Bạn có chắc chắn muốn xóa phiếu lương của nhân viên ${item.name} trong kỳ này không? Dữ liệu sau khi xóa sẽ không thể khôi phục.`);
  if (!ok) return;
  try {
    isLoading.value = true;
    await apiRequest(`/salary-details/${targetId}`, {
      method: 'DELETE',
    });
    await loadData();
    await showAlert('Thành công', 'Đã xóa phiếu lương thành công.');
  } catch (error) {
    if (error.status === 404) {
      // Nếu server báo không tìm thấy, có nghĩa là đã bị xóa hoặc chưa từng tồn tại
      await loadData();
      await showAlert('Thông báo', 'Bản ghi đã được dọn dẹp khỏi hệ thống.');
    } else if (error.status === 422 || error.message.includes('chốt')) {
      await showAlert('Không thể xóa', 'Không thể xóa phiếu lương của kỳ lương đã chốt hoặc đã thanh toán.');
    } else {
      await showAlert('Lỗi', error?.message || 'Lỗi khi xóa phiếu lương.');
    }
  } finally {
    isLoading.value = false;
  }
};

const openDeletePeriodModal = async (period) => {
  const ok = await showConfirm('Xác nhận xóa hệ thống kỳ lương', `Bạn chắc chắn muốn xóa hệ thống ${period.label}? Dữ liệu sau khi xóa sẽ không thể khôi phục.`);
  if (!ok) return;
  try {
    isLoading.value = true;
    await apiRequest(`/salary-periods/${period.id}`, { method: 'DELETE' });
    selectedPeriodId.value = null; // Reset selection so it selects the latest correctly
    await loadData();
    await showAlert('Thành công', 'Đã xóa kỳ lương thành công.');
  } catch (error) {
    if (error.status === 422 || error.message.includes('phiếu lương')) {
      await showAlert('Không thể xóa', 'Không thể xóa kỳ lương đã có dữ liệu phiếu lương nhân sự.');
    } else {
      await showAlert('Lỗi', error?.message || 'Không thể xóa kỳ lương.');
    }
  } finally {
    isLoading.value = false;
  }
};

const closeSelectedPeriod = async () => {
  if (!selectedPeriod.value) return;
  const periodId = Number(selectedPeriod.value.period_id || selectedPeriod.value.periodId || selectedPeriod.value.id || 0);
  if (!periodId) return;
  const ok = await showConfirm('Xác nhận chốt kỳ lương', `Bạn chắc chắn muốn chốt ${formatPeriodLabel(selectedPeriod.value)}?`);
  if (!ok) return;
  try {
    await apiRequest(`/salary-periods/${periodId}/close`, { method: 'POST' });
    await loadData();
    await showAlert('Thành công', 'Đã chốt kỳ lương.');
  } catch (error) {
    await showAlert('Chốt kỳ thất bại', error?.message || 'Không thể chốt kỳ lương.');
  }
};

const exportReport = async () => {
  const rows = filteredEmployees.value;
  if (rows.length === 0) {
    void showAlert('Không có dữ liệu', 'Không có dữ liệu để xuất báo cáo.');
    return;
  }

  const workbook = new ExcelJS.Workbook();
  const sheet = workbook.addWorksheet('Bảng Lương');

  const periodLabelStr = selectedPeriod.value ? formatPeriodLabel(selectedPeriod.value) : `Tháng ${currentMonth}/${currentYear}`;
  const totalEmployees = rows.length;
  const totalIncomeSum = rows.reduce((acc, row) => acc + toNumber(row.totalIncome), 0);
  const totalDeductionSum = rows.reduce((acc, row) => acc + toNumber(row.deduction), 0);
  const totalNetSum = rows.reduce((acc, row) => acc + toNumber(row.netSalary), 0);

  // Add Summary Header
  sheet.addRow([`BÁO CÁO TỔNG QUAN BẢNG LƯƠNG - ${periodLabelStr.toUpperCase()}`]);
  sheet.mergeCells('A1:H1');
  sheet.getCell('A1').font = { size: 16, bold: true, color: { argb: 'FF0B5CFF' } };
  sheet.getCell('A1').alignment = { vertical: 'middle', horizontal: 'center' };
  sheet.getRow(1).height = 30;

  sheet.addRow([]); // Row 2
  
  sheet.addRow(['Kỳ thanh toán:', periodLabelStr]);      // Row 3
  sheet.addRow(['Số lượng nhân sự:', totalEmployees]);   // Row 4
  sheet.addRow(['Tổng quỹ lương (Gộp):', totalIncomeSum]);// Row 5
  sheet.addRow(['Tổng phần trích nộp:', totalDeductionSum]);// Row 6
  sheet.addRow(['Tổng khoản thực lĩnh:', totalNetSum]);  // Row 7
  
  // Format summary section
  [3, 4, 5, 6, 7].forEach(rowIndex => {
    sheet.getCell(`A${rowIndex}`).font = { bold: true };
    if (rowIndex >= 5) {
      sheet.getCell(`B${rowIndex}`).numFmt = '#,##0';
      sheet.getCell(`B${rowIndex}`).font = { bold: true, color: { argb: 'FF16A34A' } }; // Subtly highlight totals in green
    }
  });

  sheet.addRow([]); // Row 8

  // Table Data
  const header = ['Mã NV', 'Họ tên', 'Phòng ban', 'Lương cơ bản', 'Thu nhập gộp', 'Khấu trừ', 'Thực lĩnh', 'Trạng thái'];
  const headerRow = sheet.addRow(header); // Row 9

  rows.forEach((row) => {
    sheet.addRow([
      row.empId || '',
      row.name || '',
      row.role || '',
      toNumber(row.baseSalary),
      toNumber(row.totalIncome),
      toNumber(row.deduction),
      toNumber(row.netSalary),
      row.status || '',
    ]);
  });

  // Style Table Header (Row 9)
  headerRow.eachCell((cell) => {
    cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FF0B5CFF' } };
    cell.font = { color: { argb: 'FFFFFFFF' }, bold: true };
    cell.border = { top: { style: 'thin' }, left: { style: 'thin' }, bottom: { style: 'thin' }, right: { style: 'thin' } };
  });

  // Style Data Rows & Format Numbers
  sheet.eachRow((row, rowNumber) => {
    if (rowNumber > 9) {
      row.eachCell((cell, colNumber) => {
        if (colNumber >= 4 && colNumber <= 7) {
          cell.numFmt = '#,##0';
        }
        cell.border = { top: { style: 'thin', color: { argb: 'FFEEEEEE' } }, left: { style: 'thin', color: { argb: 'FFEEEEEE' } }, bottom: { style: 'thin', color: { argb: 'FFEEEEEE' } }, right: { style: 'thin', color: { argb: 'FFEEEEEE' } } };
      });
    }
  });

  // Adjust Column Widths
  sheet.columns = [
    { width: 12 }, { width: 25 }, { width: 20 },
    { width: 18 }, { width: 18 }, { width: 18 }, { width: 18 },
    { width: 20 }
  ];

  const buffer = await workbook.xlsx.writeBuffer();
  const blob = new Blob([buffer], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' });
  const periodLabel = selectedPeriod.value ? formatPeriodLabel(selectedPeriod.value).replace(/\s+/g, '_') : `thang_${currentMonth}_${currentYear}`;
  saveAs(blob, `Bang_Luong_${periodLabel}.xlsx`);
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

.modal-fade-enter-active,
.modal-fade-leave-active {
  transition: opacity 0.2s ease;
}

.modal-fade-enter-from,
.modal-fade-leave-to {
  opacity: 0;
}
</style>
