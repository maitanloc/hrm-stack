<template>
  <div class="space-y-6 pb-8">
    <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4 bg-transparent text-left">
      <div class="bg-transparent text-left">
        <h1 class="text-xl font-bold text-[var(--sys-text-primary)] mb-1 uppercase tracking-tight">Quản lý Phân ca làm việc</h1>
        <p class="text-[13px] text-[var(--sys-text-secondary)]">
          Thiết lập ca mặc định, override theo ngày và publish lịch làm việc cho nhân sự theo đúng workflow hiện có của hệ thống.
        </p>
      </div>

      <div class="flex flex-col md:flex-row items-stretch md:items-center gap-3 w-full lg:w-auto">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3 w-full">
          <Dropdown v-model="filters.departmentId" :options="departmentOptions" class="h-11 min-w-[180px]" />
          <input v-model="filters.fromDate" type="date" class="h-11 px-4 rounded-md bg-[var(--sys-bg-surface)] border border-[var(--sys-border-strong)] text-[13px] font-semibold text-[var(--sys-text-primary)] outline-none focus:border-[var(--sys-brand-solid)] shadow-sm" />
          <input v-model="filters.toDate" type="date" class="h-11 px-4 rounded-md bg-[var(--sys-bg-surface)] border border-[var(--sys-border-strong)] text-[13px] font-semibold text-[var(--sys-text-primary)] outline-none focus:border-[var(--sys-brand-solid)] shadow-sm" />
        </div>
        <button @click="loadScheduleData" class="h-11 px-6 bg-[var(--sys-brand-solid)] rounded-md font-bold text-white hover:brightness-110 active:scale-95 transition-all flex items-center justify-center gap-2 text-[12px] uppercase tracking-wider shadow-lg shrink-0">
          <span class="material-symbols-outlined text-[20px]">sync</span>
          Tải lịch
        </button>
      </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
      <div v-for="stat in stats" :key="stat.label" class="bg-[var(--sys-bg-surface)] p-4 rounded-lg border border-[var(--sys-border-subtle)] shadow-sm flex items-center gap-4">
        <div :class="`w-10 h-10 rounded-md flex items-center justify-center border shrink-0 ${stat.iconClass}`">
          <span class="material-symbols-outlined text-xl">{{ stat.icon }}</span>
        </div>
        <div class="bg-transparent flex flex-col overflow-hidden">
          <p class="text-[11px] font-bold text-[var(--sys-text-secondary)] mb-0.5 uppercase tracking-widest opacity-60 truncate">{{ stat.label }}</p>
          <p class="text-xl font-bold text-[var(--sys-text-primary)] m-0 leading-tight tracking-tight">{{ stat.value }}</p>
          <p class="text-[10px] font-bold text-[var(--sys-text-secondary)] mt-0.5 uppercase tracking-wide opacity-60">{{ stat.note }}</p>
        </div>
      </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-4">
      <div class="bg-[var(--sys-bg-surface)] rounded-lg border border-[var(--sys-border-subtle)] shadow-sm overflow-hidden">
        <div class="px-4 py-3 border-b border-[var(--sys-border-subtle)] bg-[var(--sys-bg-page)]/50 flex items-center gap-2">
          <span class="material-symbols-outlined text-[var(--sys-brand-solid)] text-[20px]">assignment_ind</span>
          <h4 class="text-[13px] font-bold text-[var(--sys-text-primary)] uppercase tracking-widest m-0">Gán ca mặc định</h4>
        </div>
        <div class="p-4 space-y-4">
          <div class="space-y-1.5">
            <label class="text-[11px] font-bold text-[var(--sys-text-primary)] uppercase tracking-wider ml-1">Nhân sự</label>
            <Dropdown v-model="assignForm.employeeId" :options="employeeOptions" class="h-11" />
          </div>
          <div class="space-y-1.5">
            <label class="text-[11px] font-bold text-[var(--sys-text-primary)] uppercase tracking-wider ml-1">Ca áp dụng</label>
            <Dropdown v-model="assignForm.shiftTypeId" :options="shiftOptions" class="h-11" />
          </div>
          <div class="grid grid-cols-2 gap-3">
            <div class="space-y-1.5">
              <label class="text-[11px] font-bold text-[var(--sys-text-primary)] uppercase tracking-wider ml-1">Hiệu lực từ</label>
              <input v-model="assignForm.effectiveDate" type="date" class="w-full h-11 px-4 rounded-md bg-[var(--sys-bg-page)] border border-[var(--sys-border-strong)] text-[13px] font-semibold text-[var(--sys-text-primary)] outline-none focus:border-[var(--sys-brand-solid)] shadow-sm" />
            </div>
            <div class="space-y-1.5">
              <label class="text-[11px] font-bold text-[var(--sys-text-primary)] uppercase tracking-wider ml-1">Hiệu lực đến</label>
              <input v-model="assignForm.expiryDate" type="date" class="w-full h-11 px-4 rounded-md bg-[var(--sys-bg-page)] border border-[var(--sys-border-strong)] text-[13px] font-semibold text-[var(--sys-text-primary)] outline-none focus:border-[var(--sys-brand-solid)] shadow-sm" />
            </div>
          </div>
          <div class="space-y-1.5">
            <label class="text-[11px] font-bold text-[var(--sys-text-primary)] uppercase tracking-wider ml-1">Ghi chú</label>
            <input v-model="assignForm.notes" type="text" placeholder="Ví dụ: Ca hành chính chuẩn cho nhân sự mới..." class="w-full h-11 px-4 rounded-md bg-[var(--sys-bg-page)] border border-[var(--sys-border-strong)] text-[13px] font-semibold text-[var(--sys-text-primary)] outline-none focus:border-[var(--sys-brand-solid)] shadow-sm" />
          </div>
          <button @click="submitAssign" :disabled="savingAssign" class="w-full h-11 bg-[var(--sys-brand-solid)] rounded-md font-bold text-white hover:brightness-110 disabled:opacity-60 disabled:cursor-not-allowed transition-all flex items-center justify-center gap-2 text-[11px] uppercase tracking-widest shadow-lg">
            <span class="material-symbols-outlined text-[18px]">save</span>
            {{ savingAssign ? 'Đang lưu...' : 'Lưu ca mặc định' }}
          </button>
        </div>
      </div>

      <div class="bg-[var(--sys-bg-surface)] rounded-lg border border-[var(--sys-border-subtle)] shadow-sm overflow-hidden">
        <div class="px-4 py-3 border-b border-[var(--sys-border-subtle)] bg-[var(--sys-bg-page)]/50 flex items-center gap-2">
          <span class="material-symbols-outlined text-[var(--sys-warning-text)] text-[20px]">edit_calendar</span>
          <h4 class="text-[13px] font-bold text-[var(--sys-text-primary)] uppercase tracking-widest m-0">Chỉnh ca theo ngày</h4>
        </div>
        <div class="p-4 space-y-4">
          <div class="space-y-1.5">
            <label class="text-[11px] font-bold text-[var(--sys-text-primary)] uppercase tracking-wider ml-1">Nhân sự</label>
            <Dropdown v-model="overrideForm.employeeId" :options="employeeOptions" class="h-11" />
          </div>
          <div class="grid grid-cols-2 gap-3">
            <div class="space-y-1.5">
              <label class="text-[11px] font-bold text-[var(--sys-text-primary)] uppercase tracking-wider ml-1">Ngày làm việc</label>
              <input v-model="overrideForm.workDate" type="date" class="w-full h-11 px-4 rounded-md bg-[var(--sys-bg-page)] border border-[var(--sys-border-strong)] text-[13px] font-semibold text-[var(--sys-text-primary)] outline-none focus:border-[var(--sys-brand-solid)] shadow-sm" />
            </div>
            <div class="space-y-1.5">
              <label class="text-[11px] font-bold text-[var(--sys-text-primary)] uppercase tracking-wider ml-1">Ca override</label>
              <Dropdown v-model="overrideForm.shiftTypeId" :options="overrideShiftOptions" class="h-11" />
            </div>
          </div>
          <div class="space-y-1.5">
            <label class="text-[11px] font-bold text-[var(--sys-text-primary)] uppercase tracking-wider ml-1">Lý do override</label>
            <input v-model="overrideForm.reason" type="text" placeholder="Ví dụ: Điều động họp khách hàng / đổi ca đột xuất..." class="w-full h-11 px-4 rounded-md bg-[var(--sys-bg-page)] border border-[var(--sys-border-strong)] text-[13px] font-semibold text-[var(--sys-text-primary)] outline-none focus:border-[var(--sys-brand-solid)] shadow-sm" />
          </div>
          <button @click="submitOverride" :disabled="savingOverride" class="w-full h-11 bg-[var(--sys-warning-solid)] rounded-md font-bold text-white hover:brightness-110 disabled:opacity-60 disabled:cursor-not-allowed transition-all flex items-center justify-center gap-2 text-[11px] uppercase tracking-widest shadow-lg">
            <span class="material-symbols-outlined text-[18px]">calendar_month</span>
            {{ savingOverride ? 'Đang lưu...' : 'Lưu override' }}
          </button>
        </div>
      </div>

      <div class="bg-[var(--sys-bg-surface)] rounded-lg border border-[var(--sys-border-subtle)] shadow-sm overflow-hidden">
        <div class="px-4 py-3 border-b border-[var(--sys-border-subtle)] bg-[var(--sys-bg-page)]/50 flex items-center gap-2">
          <span class="material-symbols-outlined text-[var(--sys-success-text)] text-[20px]">publish</span>
          <h4 class="text-[13px] font-bold text-[var(--sys-text-primary)] uppercase tracking-widest m-0">Publish lịch làm việc</h4>
        </div>
        <div class="p-4 space-y-4">
          <div class="space-y-1.5">
            <label class="text-[11px] font-bold text-[var(--sys-text-primary)] uppercase tracking-wider ml-1">Phạm vi phòng ban</label>
            <Dropdown v-model="publishForm.departmentId" :options="publishDepartmentOptions" class="h-11" />
          </div>
          <div class="grid grid-cols-2 gap-3">
            <div class="space-y-1.5">
              <label class="text-[11px] font-bold text-[var(--sys-text-primary)] uppercase tracking-wider ml-1">Từ ngày</label>
              <input v-model="publishForm.fromDate" type="date" class="w-full h-11 px-4 rounded-md bg-[var(--sys-bg-page)] border border-[var(--sys-border-strong)] text-[13px] font-semibold text-[var(--sys-text-primary)] outline-none focus:border-[var(--sys-brand-solid)] shadow-sm" />
            </div>
            <div class="space-y-1.5">
              <label class="text-[11px] font-bold text-[var(--sys-text-primary)] uppercase tracking-wider ml-1">Đến ngày</label>
              <input v-model="publishForm.toDate" type="date" class="w-full h-11 px-4 rounded-md bg-[var(--sys-bg-page)] border border-[var(--sys-border-strong)] text-[13px] font-semibold text-[var(--sys-text-primary)] outline-none focus:border-[var(--sys-brand-solid)] shadow-sm" />
            </div>
          </div>
          <div class="space-y-1.5">
            <label class="text-[11px] font-bold text-[var(--sys-text-primary)] uppercase tracking-wider ml-1">Ghi chú publish</label>
            <input v-model="publishForm.notes" type="text" placeholder="Ví dụ: Chốt roster tuần 3 / ca trực cuối tháng..." class="w-full h-11 px-4 rounded-md bg-[var(--sys-bg-page)] border border-[var(--sys-border-strong)] text-[13px] font-semibold text-[var(--sys-text-primary)] outline-none focus:border-[var(--sys-brand-solid)] shadow-sm" />
          </div>
          <button @click="submitPublish" :disabled="savingPublish" class="w-full h-11 bg-[var(--sys-success-solid)] rounded-md font-bold text-white hover:brightness-110 disabled:opacity-60 disabled:cursor-not-allowed transition-all flex items-center justify-center gap-2 text-[11px] uppercase tracking-widest shadow-lg">
            <span class="material-symbols-outlined text-[18px]">campaign</span>
            {{ savingPublish ? 'Đang publish...' : 'Publish lịch làm việc' }}
          </button>
        </div>
      </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-2 gap-4">
      <div class="bg-[var(--sys-bg-surface)] rounded-lg border border-[var(--sys-border-subtle)] shadow-sm overflow-hidden">
        <div class="px-4 py-3 border-b border-[var(--sys-border-subtle)] bg-[var(--sys-bg-page)]/50 flex items-center gap-2">
          <span class="material-symbols-outlined text-[var(--sys-brand-solid)] text-[20px]">bolt</span>
          <h4 class="text-[13px] font-bold text-[var(--sys-text-primary)] uppercase tracking-widest m-0">Gán nhanh hàng loạt</h4>
        </div>
        <div class="p-4 space-y-4">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <div class="space-y-1.5">
              <label class="text-[11px] font-bold text-[var(--sys-text-primary)] uppercase tracking-wider ml-1">Ca gán nhanh</label>
              <Dropdown v-model="bulkForm.shiftTypeId" :options="shiftOptions" class="h-11" />
            </div>
            <div class="space-y-1.5">
              <label class="text-[11px] font-bold text-[var(--sys-text-primary)] uppercase tracking-wider ml-1">Hiệu lực từ</label>
              <input v-model="bulkForm.effectiveDate" type="date" class="w-full h-11 px-4 rounded-md bg-[var(--sys-bg-page)] border border-[var(--sys-border-strong)] text-[13px] font-semibold text-[var(--sys-text-primary)] outline-none focus:border-[var(--sys-brand-solid)] shadow-sm" />
            </div>
          </div>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <div class="space-y-1.5">
              <label class="text-[11px] font-bold text-[var(--sys-text-primary)] uppercase tracking-wider ml-1">Hiệu lực đến</label>
              <input v-model="bulkForm.expiryDate" type="date" class="w-full h-11 px-4 rounded-md bg-[var(--sys-bg-page)] border border-[var(--sys-border-strong)] text-[13px] font-semibold text-[var(--sys-text-primary)] outline-none focus:border-[var(--sys-brand-solid)] shadow-sm" />
            </div>
            <div class="space-y-1.5">
              <label class="text-[11px] font-bold text-[var(--sys-text-primary)] uppercase tracking-wider ml-1">Nhân sự đã chọn</label>
              <div class="w-full h-11 px-4 rounded-md bg-[var(--sys-bg-page)] border border-[var(--sys-border-subtle)] text-[13px] font-semibold text-[var(--sys-brand-solid)] flex items-center">
                {{ selectedRows.length }} nhân sự / {{ visibleRows.length }} dòng lịch
              </div>
            </div>
          </div>
          <div class="flex flex-wrap gap-2">
            <button @click="submitBulkAssign" :disabled="savingBulk" class="h-10 px-4 bg-[var(--sys-brand-solid)] rounded-md font-bold text-white hover:brightness-110 disabled:opacity-60 disabled:cursor-not-allowed transition-all text-[11px] uppercase tracking-widest shadow-lg">
              {{ savingBulk ? 'Đang gán...' : 'Gán nhanh đã chọn' }}
            </button>
            <button @click="applySuggestions" :disabled="savingBulk || suggestionRows.length === 0" class="h-10 px-4 bg-[var(--sys-success-solid)] rounded-md font-bold text-white hover:brightness-110 disabled:opacity-60 disabled:cursor-not-allowed transition-all text-[11px] uppercase tracking-widest shadow-lg">
              Áp dụng gợi ý tự động
            </button>
          </div>
          <p class="text-[11px] font-semibold text-[var(--sys-text-secondary)] opacity-70">
            Tối ưu thao tác trưởng phòng: chọn nhiều dòng lịch rồi gán một lần, giảm thao tác lặp.
          </p>
        </div>
      </div>

      <div class="bg-[var(--sys-bg-surface)] rounded-lg border border-[var(--sys-border-subtle)] shadow-sm overflow-hidden">
        <div class="px-4 py-3 border-b border-[var(--sys-border-subtle)] bg-[var(--sys-bg-page)]/50 flex items-center gap-2">
          <span class="material-symbols-outlined text-[var(--sys-warning-text)] text-[20px]">event_repeat</span>
          <h4 class="text-[13px] font-bold text-[var(--sys-text-primary)] uppercase tracking-widest m-0">Copy tuần + cảnh báo</h4>
        </div>
        <div class="p-4 space-y-4">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <div class="space-y-1.5">
              <label class="text-[11px] font-bold text-[var(--sys-text-primary)] uppercase tracking-wider ml-1">Tuần nguồn (thứ 2)</label>
              <input v-model="copyForm.sourceFromDate" type="date" class="w-full h-11 px-4 rounded-md bg-[var(--sys-bg-page)] border border-[var(--sys-border-strong)] text-[13px] font-semibold text-[var(--sys-text-primary)] outline-none focus:border-[var(--sys-brand-solid)] shadow-sm" />
            </div>
            <div class="space-y-1.5">
              <label class="text-[11px] font-bold text-[var(--sys-text-primary)] uppercase tracking-wider ml-1">Tuần đích (thứ 2)</label>
              <input v-model="copyForm.targetFromDate" type="date" class="w-full h-11 px-4 rounded-md bg-[var(--sys-bg-page)] border border-[var(--sys-border-strong)] text-[13px] font-semibold text-[var(--sys-text-primary)] outline-none focus:border-[var(--sys-brand-solid)] shadow-sm" />
            </div>
          </div>
          <button @click="submitCopyWeek" :disabled="savingCopyWeek" class="h-10 px-4 bg-[var(--sys-warning-solid)] rounded-md font-bold text-white hover:brightness-110 disabled:opacity-60 disabled:cursor-not-allowed transition-all text-[11px] uppercase tracking-widest shadow-lg">
            {{ savingCopyWeek ? 'Đang copy...' : 'Copy lịch tuần trước' }}
          </button>
          <div class="grid grid-cols-2 gap-3">
            <div class="px-3 py-2 rounded-md border bg-[var(--sys-danger-soft)] text-[var(--sys-danger-text)] border-[var(--sys-danger-border)] text-[11px] font-bold uppercase tracking-wider">
              Chưa phân ca: {{ warningSummary.unassigned }}
            </div>
            <div class="px-3 py-2 rounded-md border bg-[var(--sys-warning-soft)] text-[var(--sys-warning-text)] border-[var(--sys-warning-border)] text-[11px] font-bold uppercase tracking-wider">
              Xung đột nghỉ phép: {{ warningSummary.leaveConflicts }}
            </div>
            <div class="px-3 py-2 rounded-md border bg-[var(--sys-warning-soft)] text-[var(--sys-warning-text)] border-[var(--sys-warning-border)] text-[11px] font-bold uppercase tracking-wider">
              Muộn >= 15p: {{ warningSummary.lateRisk }}
            </div>
            <div class="px-3 py-2 rounded-md border bg-[var(--sys-danger-soft)] text-[var(--sys-danger-text)] border-[var(--sys-danger-border)] text-[11px] font-bold uppercase tracking-wider">
              OT >= 4h: {{ warningSummary.overtimeRisk }}
            </div>
          </div>
          <p class="text-[11px] font-semibold text-[var(--sys-text-secondary)] opacity-70">
            Cảnh báo giúp admin/trưởng phòng phát hiện thiếu người, xung đột phép và rủi ro giờ công ngay trên lịch.
          </p>
        </div>
      </div>
    </div>

    <div class="bg-[var(--sys-bg-surface)] rounded-lg border border-[var(--sys-border-subtle)] shadow-sm overflow-hidden flex flex-col">
      <div class="px-4 py-3 border-b border-[var(--sys-border-subtle)] bg-[var(--sys-bg-page)]/50 flex flex-col lg:flex-row gap-3 justify-between lg:items-center">
        <h4 class="text-[13px] font-bold text-[var(--sys-text-primary)] flex items-center gap-2 m-0 uppercase tracking-widest">
          <span class="material-symbols-outlined text-[var(--sys-brand-solid)] text-[20px]">view_timeline</span>
          Lịch phân ca thực tế
        </h4>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 w-full lg:w-auto">
          <Dropdown v-model="filters.employeeId" :options="employeeFilterOptions" class="h-10 min-w-[220px]" />
          <Dropdown v-model="filters.viewMode" :options="viewModeOptions" class="h-10 min-w-[180px]" />
        </div>
      </div>

      <div v-if="flashMessage" class="mx-4 mt-4 px-4 py-3 rounded-md border text-[12px] font-semibold"
        :class="flashMessage.type === 'error'
          ? 'bg-[var(--sys-danger-soft)] text-[var(--sys-danger-text)] border-[var(--sys-danger-border)]'
          : 'bg-[var(--sys-success-soft)] text-[var(--sys-success-text)] border-[var(--sys-success-border)]'">
        {{ flashMessage.text }}
      </div>

      <div class="overflow-x-auto custom-scrollbar">
        <table class="w-full text-left border-collapse min-w-[1180px]">
          <thead class="bg-[var(--sys-bg-page)]">
            <tr>
              <th class="px-4 py-2.5 text-[11px] font-bold text-[var(--sys-text-secondary)] border-b border-[var(--sys-border-subtle)] uppercase tracking-widest whitespace-nowrap">
                <input type="checkbox" :checked="selectedRows.length === visibleRows.length && visibleRows.length > 0" @change="toggleSelectAllVisible($event.target.checked)" />
              </th>
              <th class="px-4 py-2.5 text-[11px] font-bold text-[var(--sys-text-secondary)] border-b border-[var(--sys-border-subtle)] uppercase tracking-widest whitespace-nowrap">Nhân sự</th>
              <th class="px-4 py-2.5 text-[11px] font-bold text-[var(--sys-text-secondary)] border-b border-[var(--sys-border-subtle)] uppercase tracking-widest whitespace-nowrap">Ngày làm việc</th>
              <th class="px-4 py-2.5 text-[11px] font-bold text-[var(--sys-text-secondary)] border-b border-[var(--sys-border-subtle)] uppercase tracking-widest whitespace-nowrap">Ca áp dụng</th>
              <th class="px-4 py-2.5 text-[11px] font-bold text-[var(--sys-text-secondary)] border-b border-[var(--sys-border-subtle)] uppercase tracking-widest whitespace-nowrap">Nguồn lịch</th>
              <th class="px-4 py-2.5 text-[11px] font-bold text-[var(--sys-text-secondary)] border-b border-[var(--sys-border-subtle)] uppercase tracking-widest whitespace-nowrap">Workflow lịch</th>
              <th class="px-4 py-2.5 text-[11px] font-bold text-[var(--sys-text-secondary)] border-b border-[var(--sys-border-subtle)] uppercase tracking-widest whitespace-nowrap">Ghi chú hệ thống</th>
              <th class="px-4 py-2.5 text-[11px] font-bold text-[var(--sys-text-secondary)] border-b border-[var(--sys-border-subtle)] uppercase tracking-widest text-right whitespace-nowrap">Thao tác</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-[var(--sys-border-subtle)]">
            <tr v-for="row in visibleRows" :key="`${row.employee.employee_id}-${row.work_date}`" class="hover:bg-[var(--sys-bg-hover)] transition-all">
              <td class="px-4 py-3 whitespace-nowrap">
                <input
                  type="checkbox"
                  :checked="selectedRowKeys.includes(`${row.employee.employee_id}-${row.work_date}`)"
                  @change="toggleRow(row, $event.target.checked)"
                />
              </td>
              <td class="px-4 py-3 whitespace-nowrap">
                <div class="flex items-center gap-3">
                  <div class="w-9 h-9 rounded-md bg-[var(--sys-brand-soft)] text-[var(--sys-brand-solid)] flex items-center justify-center text-[13px] font-bold border border-[var(--sys-brand-border)] shadow-sm">
                    {{ (row.employee.full_name || 'N').charAt(0) }}
                  </div>
                  <div class="flex flex-col">
                    <span class="text-[13px] font-bold text-[var(--sys-text-primary)]">{{ row.employee.full_name }}</span>
                    <span class="text-[10px] font-bold text-[var(--sys-text-secondary)] uppercase tracking-wider opacity-60">
                      #{{ row.employee.employee_code || row.employee.employee_id }} · {{ row.employee.department_name || 'Chưa rõ phòng ban' }}
                    </span>
                  </div>
                </div>
              </td>
              <td class="px-4 py-3 whitespace-nowrap">
                <span class="text-[12px] font-bold text-[var(--sys-brand-solid)] uppercase tracking-wide">{{ formatDateVi(row.work_date) }}</span>
              </td>
              <td class="px-4 py-3 whitespace-nowrap">
                <div class="flex flex-col">
                  <span class="text-[12px] font-bold text-[var(--sys-text-primary)]">{{ getShiftTitle(row) }}</span>
                  <span class="text-[10px] font-bold text-[var(--sys-text-secondary)] uppercase tracking-wider opacity-60">{{ getShiftTime(row) }}</span>
                </div>
              </td>
              <td class="px-4 py-3 whitespace-nowrap">
                <span class="px-2 py-1 rounded-md text-[10px] font-bold uppercase tracking-widest border shadow-sm" :class="getSourceBadgeClass(row)">
                  {{ getSourceLabel(row) }}
                </span>
              </td>
              <td class="px-4 py-3 whitespace-nowrap">
                <span class="px-2 py-1 rounded-md text-[10px] font-bold uppercase tracking-widest border shadow-sm" :class="getStatusBadgeClass(row)">
                  {{ getScheduleStatus(row) }}
                </span>
              </td>
              <td class="px-4 py-3">
                <div class="flex flex-col max-w-[300px]">
                  <span class="text-[12px] font-semibold text-[var(--sys-text-primary)] truncate">{{ getScheduleNote(row) }}</span>
                  <span class="text-[10px] font-bold text-[var(--sys-text-secondary)] uppercase tracking-wider opacity-60 truncate">{{ getAttendanceMeta(row) }}</span>
                </div>
              </td>
              <td class="px-4 py-3 text-right whitespace-nowrap">
                <div class="flex items-center justify-end gap-1">
                  <button @click="prefillAssign(row)" class="w-8 h-8 flex items-center justify-center rounded-md text-[var(--sys-text-secondary)] hover:text-[var(--sys-brand-solid)] hover:bg-[var(--sys-brand-soft)] transition-all" title="Gán ca mặc định">
                    <span class="material-symbols-outlined text-[18px]">assignment</span>
                  </button>
                  <button @click="prefillOverride(row)" class="w-8 h-8 flex items-center justify-center rounded-md text-[var(--sys-text-secondary)] hover:text-[var(--sys-warning-text)] hover:bg-[var(--sys-warning-soft)] transition-all" title="Override theo ngày">
                    <span class="material-symbols-outlined text-[18px]">edit_calendar</span>
                  </button>
                </div>
              </td>
            </tr>
            <tr v-if="visibleRows.length === 0">
              <td colspan="8" class="px-4 py-8 text-center">
                <div class="flex flex-col items-center gap-3 text-[var(--sys-text-secondary)]">
                  <span class="material-symbols-outlined text-[40px] opacity-20">event_busy</span>
                  <p class="m-0 text-[12px] font-bold uppercase tracking-widest opacity-60">Chưa có dữ liệu phân ca trong phạm vi đang chọn</p>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div class="px-4 py-3 bg-[var(--sys-bg-page)]/50 border-t border-[var(--sys-border-subtle)] flex justify-between items-center">
        <span class="text-[11px] font-bold text-[var(--sys-text-secondary)] uppercase tracking-widest opacity-60">
          Hiển thị {{ visibleRows.length }} bản ghi lịch làm việc trong khoảng {{ formatDateVi(filters.fromDate) }} - {{ formatDateVi(filters.toDate) }}
        </span>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import Dropdown from '@/components/Dropdown.vue'
import { useCurrentUser } from '@/composables/useCurrentUser'
import { apiRequest, toIsoLocalDate } from '@/services/beApi.js'
import {
  assignTeamShift,
  bulkAssignTeamShift,
  copyTeamScheduleWeek,
  fetchShiftCatalog,
  fetchTeamSchedule,
  fetchTeamScheduleSuggestions,
  fetchTeamScheduleWarnings,
  overrideTeamShift,
  publishTeamSchedule,
} from '@/services/workforceApi.js'

const unwrap = (payload) => payload?.data ?? payload ?? []
const today = toIsoLocalDate()
const plusDays = (offset) => {
  const value = new Date()
  value.setDate(value.getDate() + offset)
  return toIsoLocalDate(value)
}

const { deptId, deptName, role } = useCurrentUser()
const isPrivilegedManager = computed(() => ['admin', 'hr'].includes(String(role.value || '').toLowerCase()))

const filters = ref({
  departmentId: deptId.value || '',
  fromDate: today,
  toDate: plusDays(13),
  employeeId: '',
  viewMode: 'ALL',
})

const assignForm = ref({
  employeeId: '',
  shiftTypeId: '',
  effectiveDate: today,
  expiryDate: '',
  notes: '',
})

const overrideForm = ref({
  employeeId: '',
  workDate: today,
  shiftTypeId: '',
  reason: '',
})

const publishForm = ref({
  departmentId: deptId.value || '',
  fromDate: today,
  toDate: plusDays(13),
  notes: '',
})

const departments = ref([])
const employees = ref([])
const shifts = ref([])
const scheduleRows = ref([])
const selectedRowKeys = ref([])
const suggestionRows = ref([])
const warningData = ref({
  unassigned: [],
  leave_conflicts: [],
  late_risk: [],
  overtime_risk: [],
})
const savingAssign = ref(false)
const savingOverride = ref(false)
const savingPublish = ref(false)
const savingBulk = ref(false)
const savingCopyWeek = ref(false)
const flashMessage = ref(null)
const loadingInsight = ref(false)

const bulkForm = ref({
  shiftTypeId: '',
  effectiveDate: today,
  expiryDate: '',
  notes: '',
})

const copyForm = ref({
  sourceFromDate: today,
  targetFromDate: plusDays(7),
  notes: '',
})

const viewModeOptions = [
  { label: 'Toàn bộ lịch', value: 'ALL', icon: 'view_list' },
  { label: 'Chưa phân ca', value: 'UNASSIGNED', icon: 'warning' },
  { label: 'Ngày nghỉ / off', value: 'OFF', icon: 'event_busy' },
  { label: 'Ca đêm', value: 'NIGHT', icon: 'dark_mode' },
]

const departmentOptions = computed(() => {
  const options = [{ label: 'Tất cả phòng ban trong phạm vi', value: '', icon: 'corporate_fare' }]
  return options.concat(
    departments.value.map((department) => ({
      label: department.department_name,
      value: String(department.department_id),
      icon: 'apartment',
    }))
  )
})

const publishDepartmentOptions = computed(() => {
  if (!isPrivilegedManager.value) {
    return [{ label: deptName.value || 'Phòng ban hiện tại', value: String(deptId.value || ''), icon: 'apartment' }]
  }
  return departments.value.map((department) => ({
    label: department.department_name,
    value: String(department.department_id),
    icon: 'apartment',
  }))
})

const employeeOptions = computed(() => [
  { label: 'Chọn nhân sự', value: '', icon: 'person' },
  ...employees.value.map((employee) => ({
    label: `${employee.full_name} · ${employee.employee_code || `#${employee.employee_id}`}`,
    value: String(employee.employee_id),
    icon: 'badge',
  })),
])

const employeeFilterOptions = computed(() => [
  { label: 'Toàn bộ nhân sự', value: '', icon: 'groups' },
  ...employees.value.map((employee) => ({
    label: `${employee.full_name} · ${employee.employee_code || `#${employee.employee_id}`}`,
    value: String(employee.employee_id),
    icon: 'person',
  })),
])

const shiftOptions = computed(() => [
  { label: 'Chọn ca làm việc', value: '', icon: 'schedule' },
  ...shifts.value.map((shift) => ({
    label: `${shift.shift_name} (${shift.start_time || '--:--'} - ${shift.end_time || '--:--'})`,
    value: String(shift.shift_type_id),
    icon: shift.is_night_shift ? 'dark_mode' : 'schedule',
  })),
])

const overrideShiftOptions = computed(() => [
  { label: 'Hủy override / trả về lịch gốc', value: '', icon: 'restart_alt' },
  ...shiftOptions.value.filter((option) => option.value !== ''),
])

const visibleRows = computed(() => {
  return scheduleRows.value.filter((row) => {
    if (filters.value.employeeId && String(row?.employee?.employee_id) !== String(filters.value.employeeId)) {
      return false
    }

    const mode = String(filters.value.viewMode || 'ALL')
    if (mode === 'UNASSIGNED') return !row?.shift
    if (mode === 'OFF') return Boolean(row?.holiday || row?.leave)
    if (mode === 'NIGHT') return Boolean(row?.shift?.is_night_shift)
    return true
  })
})

const selectedRows = computed(() => {
  const selected = new Set(selectedRowKeys.value)
  return visibleRows.value.filter((row) => selected.has(`${row.employee.employee_id}-${row.work_date}`))
})

const warningSummary = computed(() => ({
  unassigned: Array.isArray(warningData.value.unassigned) ? warningData.value.unassigned.length : 0,
  leaveConflicts: Array.isArray(warningData.value.leave_conflicts) ? warningData.value.leave_conflicts.length : 0,
  lateRisk: Array.isArray(warningData.value.late_risk) ? warningData.value.late_risk.length : 0,
  overtimeRisk: Array.isArray(warningData.value.overtime_risk) ? warningData.value.overtime_risk.length : 0,
}))

const stats = computed(() => {
  const employeesSet = new Set()
  let assignedCount = 0
  let offCount = 0
  let unassignedCount = 0
  let nightCount = 0

  scheduleRows.value.forEach((row) => {
    if (row?.employee?.employee_id) employeesSet.add(row.employee.employee_id)
    if (row?.shift) assignedCount += 1
    if (row?.holiday || row?.leave) offCount += 1
    if (!row?.shift) unassignedCount += 1
    if (row?.shift?.is_night_shift) nightCount += 1
  })

  return [
    {
      label: 'Nhân sự trong phạm vi',
      value: employeesSet.size,
      note: 'Nhân sự đang được theo dõi lịch',
      icon: 'groups',
      iconClass: 'bg-[var(--sys-brand-soft)] text-[var(--sys-brand-solid)] border-[var(--sys-brand-border)]',
    },
    {
      label: 'Ngày đã có ca',
      value: assignedCount,
      note: 'Bản ghi có ca làm việc rõ ràng',
      icon: 'calendar_month',
      iconClass: 'bg-[var(--sys-success-soft)] text-[var(--sys-success-text)] border-[var(--sys-success-border)]',
    },
    {
      label: 'Ngày nghỉ / off',
      value: offCount,
      note: 'Holiday hoặc nghỉ phép hợp lệ',
      icon: 'event_busy',
      iconClass: 'bg-[var(--sys-warning-soft)] text-[var(--sys-warning-text)] border-[var(--sys-warning-border)]',
    },
    {
      label: 'Chưa phân ca',
      value: unassignedCount,
      note: `Ca đêm: ${nightCount} bản ghi`,
      icon: 'warning',
      iconClass: 'bg-[var(--sys-danger-soft)] text-[var(--sys-danger-text)] border-[var(--sys-danger-border)]',
    },
  ]
})

const formatDateVi = (value) => {
  if (!value) return '--/--/----'
  const date = new Date(value)
  if (Number.isNaN(date.getTime())) return value
  return date.toLocaleDateString('vi-VN')
}

const formatDateTimeVi = (value) => {
  if (!value) return ''
  const date = new Date(value)
  if (Number.isNaN(date.getTime())) return String(value)
  return date.toLocaleString('vi-VN')
}

const setFlash = (text, type = 'success') => {
  flashMessage.value = { text, type }
  window.clearTimeout(setFlash._timer)
  setFlash._timer = window.setTimeout(() => {
    flashMessage.value = null
  }, 5000)
}

const loadDepartments = async () => {
  const payload = await apiRequest('/departments', { query: { page: 1, per_page: 500 } })
  departments.value = unwrap(payload)
}

const loadEmployees = async () => {
  const query = { page: 1, per_page: 500 }
  if (filters.value.departmentId) query.department_id = Number(filters.value.departmentId)
  const payload = await apiRequest('/employees', { query })
  employees.value = unwrap(payload)
}

const loadShiftCatalog = async () => {
  shifts.value = await fetchShiftCatalog()
}

const loadScheduleData = async () => {
  const rows = await fetchTeamSchedule({
    fromDate: filters.value.fromDate,
    toDate: filters.value.toDate,
    departmentId: filters.value.departmentId ? Number(filters.value.departmentId) : undefined,
  })
  const normalized = Array.isArray(rows) ? rows : []
  const dedup = new Map()
  normalized.forEach((row) => {
    const employeeId = Number(row?.employee?.employee_id || 0)
    const workDate = String(row?.work_date || '')
    if (!employeeId || !workDate) return
    const key = `${employeeId}-${workDate}`
    const existing = dedup.get(key)
    if (!existing) {
      dedup.set(key, row)
      return
    }
    const score = (item) => Number(Boolean(item?.shift)) + Number(Boolean(item?.leave)) + Number(Boolean(item?.holiday)) + Number(Boolean(item?.attendance_result))
    if (score(row) >= score(existing)) {
      dedup.set(key, row)
    }
  })
  scheduleRows.value = Array.from(dedup.values())
  selectedRowKeys.value = []
}

const loadInsights = async () => {
  loadingInsight.value = true
  try {
    const [warnings, suggestions] = await Promise.all([
      fetchTeamScheduleWarnings({
        fromDate: filters.value.fromDate,
        toDate: filters.value.toDate,
        departmentId: filters.value.departmentId ? Number(filters.value.departmentId) : undefined,
      }),
      fetchTeamScheduleSuggestions({
        fromDate: filters.value.fromDate,
        toDate: filters.value.toDate,
        departmentId: filters.value.departmentId ? Number(filters.value.departmentId) : undefined,
      }),
    ])
    warningData.value = warnings || { unassigned: [], leave_conflicts: [], late_risk: [], overtime_risk: [] }
    suggestionRows.value = Array.isArray(suggestions) ? suggestions : []
  } catch (error) {
    warningData.value = { unassigned: [], leave_conflicts: [], late_risk: [], overtime_risk: [] }
    suggestionRows.value = []
    setFlash(error?.message || 'Không tải được cảnh báo/gợi ý phân ca.', 'error')
  } finally {
    loadingInsight.value = false
  }
}

const getShiftTitle = (row) => row?.shift?.shift_name || 'Chưa phân ca'

const getShiftTime = (row) => {
  if (!row?.shift?.start_time || !row?.shift?.end_time) return 'Lịch chưa có giờ chuẩn'
  return `${row.shift.start_time.slice(0, 5)} - ${row.shift.end_time.slice(0, 5)}`
}

const getSourceLabel = (row) => {
  const source = String(row?.shift?.source || '').toUpperCase()
  if (source === 'OVERRIDE') return 'Override ngày'
  if (source === 'EMPLOYEE_DEFAULT') return 'Ca mặc định NV'
  if (source === 'DEPARTMENT_SCHEDULE') return 'Lịch phòng ban'
  return 'Chưa xác định'
}

const getSourceBadgeClass = (row) => {
  const source = String(row?.shift?.source || '').toUpperCase()
  if (source === 'OVERRIDE') return 'bg-[var(--sys-warning-soft)] text-[var(--sys-warning-text)] border-[var(--sys-warning-border)]'
  if (source === 'EMPLOYEE_DEFAULT') return 'bg-[var(--sys-brand-soft)] text-[var(--sys-brand-solid)] border-[var(--sys-brand-border)]'
  if (source === 'DEPARTMENT_SCHEDULE') return 'bg-[var(--sys-success-soft)] text-[var(--sys-success-text)] border-[var(--sys-success-border)]'
  return 'bg-[var(--sys-bg-hover)] text-[var(--sys-text-secondary)] border-[var(--sys-border-strong)]'
}

const getScheduleStatus = (row) => {
  const workflow = row?.workflow
  if (workflow?.label) return workflow.label
  if (!row?.shift && !row?.holiday && !row?.leave && !row?.business_trip && !row?.remote) return 'Unassigned'
  return 'Draft'
}

const getStatusBadgeClass = (row) => {
  const code = String(row?.workflow?.code || '').toUpperCase()
  if (code === 'PUBLISHED') return 'bg-[var(--sys-success-soft)] text-[var(--sys-success-text)] border-[var(--sys-success-border)]'
  if (code === 'ADJUSTED_AFTER_PUBLISH') return 'bg-[var(--sys-warning-soft)] text-[var(--sys-warning-text)] border-[var(--sys-warning-border)]'
  if (code === 'DRAFT') return 'bg-[var(--sys-brand-soft)] text-[var(--sys-brand-solid)] border-[var(--sys-brand-border)]'
  return 'bg-[var(--sys-danger-soft)] text-[var(--sys-danger-text)] border-[var(--sys-danger-border)]'
}

const getScheduleNote = (row) => {
  const workflow = row?.workflow || {}
  const publishActor = workflow?.publish_log?.published_by_name
  const publishAt = workflow?.publish_log?.published_at ? formatDateTimeVi(workflow.publish_log.published_at) : ''
  const adjustedReason = workflow?.adjustment_after_publish?.reason
  const adjustedAt = workflow?.adjustment_after_publish?.changed_at ? formatDateTimeVi(workflow.adjustment_after_publish.changed_at) : ''

  const workflowTail = (() => {
    const code = String(workflow?.code || '').toUpperCase()
    if (code === 'PUBLISHED') {
      return publishActor ? `Đã publish bởi ${publishActor}${publishAt ? ` · ${publishAt}` : ''}` : 'Lịch đã publish chính thức'
    }
    if (code === 'ADJUSTED_AFTER_PUBLISH') {
      return adjustedReason
        ? `Đã chỉnh sau publish: ${adjustedReason}${adjustedAt ? ` · ${adjustedAt}` : ''}`
        : 'Lịch đã bị điều chỉnh sau khi publish'
    }
    if (code === 'DRAFT') {
      return 'Lịch đang ở bản nháp, chưa publish'
    }
    return 'Cần gán ca hoặc cấu hình off hợp lệ trước khi publish'
  })()

  if (row?.holiday?.holiday_name) return `${row.holiday.holiday_name} · ${workflowTail}`
  if (row?.leave?.leave_type_name) return `${row.leave.leave_type_name} · ${workflowTail}`
  if (row?.business_trip) return `Nhân sự đang có công tác được duyệt · ${workflowTail}`
  if (row?.remote) return `Nhân sự đang có lịch làm việc từ xa · ${workflowTail}`
  if (row?.shift?.meta?.reason) return `${row.shift.meta.reason} · ${workflowTail}`
  if (row?.shift?.meta?.schedule_name) return `${row.shift.meta.schedule_name} · ${workflowTail}`
  return workflowTail
}

const getAttendanceMeta = (row) => {
  const result = row?.attendance_result || {}
  const parts = []
  if (result.primary_status_code) parts.push(`Công: ${result.primary_status_code}`)
  if (result.late_minutes) parts.push(`Muộn ${result.late_minutes}p`)
  if (result.early_out_minutes) parts.push(`Sớm ${result.early_out_minutes}p`)
  if (result.overtime_minutes) parts.push(`OT ${result.overtime_minutes}p`)
  return parts.length > 0 ? parts.join(' · ') : 'Chưa phát sinh kết quả công đặc biệt'
}

const prefillAssign = (row) => {
  assignForm.value.employeeId = String(row?.employee?.employee_id || '')
  assignForm.value.shiftTypeId = String(row?.shift?.shift_type_id || '')
  assignForm.value.effectiveDate = row?.work_date || filters.value.fromDate
}

const prefillOverride = (row) => {
  overrideForm.value.employeeId = String(row?.employee?.employee_id || '')
  overrideForm.value.shiftTypeId = String(row?.shift?.shift_type_id || '')
  overrideForm.value.workDate = row?.work_date || filters.value.fromDate
  overrideForm.value.reason = row?.shift?.meta?.reason || ''
}

const submitAssign = async () => {
  if (!assignForm.value.employeeId || !assignForm.value.shiftTypeId || !assignForm.value.effectiveDate) {
    setFlash('Cần chọn nhân sự, ca làm việc và ngày hiệu lực trước khi lưu.', 'error')
    return
  }
  savingAssign.value = true
  try {
    await assignTeamShift({
      employee_id: Number(assignForm.value.employeeId),
      shift_type_id: Number(assignForm.value.shiftTypeId),
      effective_date: assignForm.value.effectiveDate,
      expiry_date: assignForm.value.expiryDate || undefined,
      notes: assignForm.value.notes || undefined,
      is_permanent: !assignForm.value.expiryDate,
    })
    setFlash('Đã lưu ca mặc định cho nhân sự.')
    await loadScheduleData()
  } catch (error) {
    setFlash(error?.message || 'Không lưu được ca mặc định.', 'error')
  } finally {
    savingAssign.value = false
  }
}

const submitOverride = async () => {
  if (!overrideForm.value.employeeId || !overrideForm.value.workDate) {
    setFlash('Cần chọn nhân sự và ngày override.', 'error')
    return
  }
  savingOverride.value = true
  try {
    await overrideTeamShift({
      employee_id: Number(overrideForm.value.employeeId),
      work_date: overrideForm.value.workDate,
      shift_type_id: overrideForm.value.shiftTypeId ? Number(overrideForm.value.shiftTypeId) : undefined,
      reason: overrideForm.value.reason || undefined,
    })
    setFlash(overrideForm.value.shiftTypeId ? 'Đã lưu override ca theo ngày.' : 'Đã xóa override và trả về lịch gốc.')
    await loadScheduleData()
  } catch (error) {
    setFlash(error?.message || 'Không lưu được override ca.', 'error')
  } finally {
    savingOverride.value = false
  }
}

const submitPublish = async () => {
  const selectedDepartmentId = publishForm.value.departmentId || filters.value.departmentId || deptId.value
  if (!selectedDepartmentId || !publishForm.value.fromDate || !publishForm.value.toDate) {
    setFlash('Cần chọn phòng ban và khoảng thời gian publish.', 'error')
    return
  }
  savingPublish.value = true
  try {
    await publishTeamSchedule({
      scope_type: 'DEPARTMENT',
      scope_id: Number(selectedDepartmentId),
      from_date: publishForm.value.fromDate,
      to_date: publishForm.value.toDate,
      notes: publishForm.value.notes || undefined,
    })
    setFlash('Đã publish lịch làm việc cho phòng ban đã chọn.')
    await loadScheduleData()
    await loadInsights()
  } catch (error) {
    setFlash(error?.message || 'Không publish được lịch làm việc.', 'error')
  } finally {
    savingPublish.value = false
  }
}

const toggleRow = (row, checked) => {
  const key = `${row.employee.employee_id}-${row.work_date}`
  if (checked) {
    if (!selectedRowKeys.value.includes(key)) {
      selectedRowKeys.value.push(key)
    }
    return
  }
  selectedRowKeys.value = selectedRowKeys.value.filter((item) => item !== key)
}

const toggleSelectAllVisible = (checked) => {
  if (!checked) {
    selectedRowKeys.value = []
    return
  }
  selectedRowKeys.value = visibleRows.value.map((row) => `${row.employee.employee_id}-${row.work_date}`)
}

const submitBulkAssign = async () => {
  if (!bulkForm.value.shiftTypeId || !bulkForm.value.effectiveDate) {
    setFlash('Cần chọn ca và ngày hiệu lực cho thao tác gán nhanh.', 'error')
    return
  }
  const employeeIds = Array.from(new Set(selectedRows.value.map((row) => Number(row.employee.employee_id || 0)).filter(Boolean)))
  if (employeeIds.length === 0) {
    setFlash('Chưa chọn nhân sự nào trong bảng để gán nhanh.', 'error')
    return
  }
  savingBulk.value = true
  try {
    await bulkAssignTeamShift({
      employee_ids: employeeIds,
      shift_type_id: Number(bulkForm.value.shiftTypeId),
      effective_date: bulkForm.value.effectiveDate,
      expiry_date: bulkForm.value.expiryDate || undefined,
      notes: bulkForm.value.notes || undefined,
      is_permanent: !bulkForm.value.expiryDate,
    })
    setFlash(`Đã gán nhanh ca cho ${employeeIds.length} nhân sự.`)
    await loadScheduleData()
    await loadInsights()
  } catch (error) {
    setFlash(error?.message || 'Không thể gán nhanh ca làm việc.', 'error')
  } finally {
    savingBulk.value = false
  }
}

const submitCopyWeek = async () => {
  const selectedDepartmentId = publishForm.value.departmentId || filters.value.departmentId || deptId.value
  if (!selectedDepartmentId || !copyForm.value.sourceFromDate || !copyForm.value.targetFromDate) {
    setFlash('Cần chọn phòng ban, tuần nguồn và tuần đích để copy.', 'error')
    return
  }
  savingCopyWeek.value = true
  try {
    await copyTeamScheduleWeek({
      scope_type: 'DEPARTMENT',
      scope_id: Number(selectedDepartmentId),
      source_from_date: copyForm.value.sourceFromDate,
      target_from_date: copyForm.value.targetFromDate,
      notes: copyForm.value.notes || undefined,
    })
    setFlash('Đã copy lịch tuần nguồn sang tuần đích.')
    await loadScheduleData()
    await loadInsights()
  } catch (error) {
    setFlash(error?.message || 'Không thể copy lịch tuần.', 'error')
  } finally {
    savingCopyWeek.value = false
  }
}

const applySuggestions = async () => {
  const candidates = suggestionRows.value.filter((item) => Number(item?.recommended_shift?.shift_type_id || 0) > 0)
  if (candidates.length === 0) {
    setFlash('Hiện chưa có gợi ý hợp lệ để áp dụng.', 'error')
    return
  }
  const grouped = new Map()
  candidates.forEach((item) => {
    const shiftTypeId = Number(item.recommended_shift.shift_type_id)
    const workDate = item.work_date
    const key = `${shiftTypeId}-${workDate}`
    if (!grouped.has(key)) {
      grouped.set(key, { shiftTypeId, workDate, employeeIds: [] })
    }
    grouped.get(key).employeeIds.push(Number(item.employee.employee_id))
  })
  savingBulk.value = true
  try {
    for (const group of grouped.values()) {
      await bulkAssignTeamShift({
        employee_ids: Array.from(new Set(group.employeeIds)),
        shift_type_id: group.shiftTypeId,
        effective_date: group.workDate,
        notes: 'Auto assign từ gợi ý hệ thống',
        is_permanent: false,
      })
    }
    setFlash(`Đã áp dụng ${candidates.length} gợi ý phân ca.`)
    await loadScheduleData()
    await loadInsights()
  } catch (error) {
    setFlash(error?.message || 'Không thể áp dụng gợi ý phân ca.', 'error')
  } finally {
    savingBulk.value = false
  }
}

watch(() => filters.value.departmentId, async (value) => {
  if (!isPrivilegedManager.value && !value && deptId.value) {
    filters.value.departmentId = String(deptId.value)
    return
  }
  publishForm.value.departmentId = value || String(deptId.value || '')
  await loadEmployees()
  await loadScheduleData()
  await loadInsights()
})

watch(() => [filters.value.fromDate, filters.value.toDate], async () => {
  await loadScheduleData()
  await loadInsights()
})

onMounted(async () => {
  try {
    if (!isPrivilegedManager.value && deptId.value) {
      filters.value.departmentId = String(deptId.value)
      publishForm.value.departmentId = String(deptId.value)
    }
    await Promise.all([
      loadDepartments(),
      loadShiftCatalog(),
    ])
    await loadEmployees()
    await loadScheduleData()
    await loadInsights()
  } catch (error) {
    setFlash(error?.message || 'Không tải được dữ liệu phân ca từ backend.', 'error')
  }
})
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
</style>
