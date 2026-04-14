<template>
  <div class="profile-wrapper min-h-screen bg-[var(--sys-bg-page)] text-[var(--sys-text-primary)] p-4 md:p-6 lg:p-8">
    <div class="max-w-7xl mx-auto space-y-6 bg-transparent">
      
      <!-- Top Header Card -->
      <div class="bg-[var(--sys-bg-surface)] rounded-lg shadow-sm border border-[var(--sys-border-subtle)] p-6 md:p-8 relative overflow-hidden group">
        <div class="absolute top-0 right-0 w-64 h-64 bg-[var(--sys-brand-soft)] rounded-full -mr-32 -mt-32 opacity-20 blur-2xl"></div>
        
        <div class="flex flex-col md:flex-row items-center md:items-start gap-8 relative z-10 bg-transparent">
          <!-- Avatar Section -->
          <div class="relative shrink-0">
            <div class="avatar-box rounded-lg overflow-hidden border-4 border-[var(--sys-brand-soft)] shadow-md bg-[var(--sys-bg-page)]">
              <img :src="avatar" :alt="fullName" class="w-32 h-32 md:w-40 md:h-40 object-cover" />
            </div>
            <button class="absolute -right-2 -bottom-2 w-10 h-10 bg-white hover:bg-[var(--sys-brand-solid)] hover:text-white text-[var(--sys-brand-solid)] rounded-md shadow-lg border border-[var(--sys-border-strong)] flex items-center justify-center transition-all active:scale-95 group/cam">
              <span class="material-symbols-outlined text-[20px]">photo_camera</span>
            </button>
          </div>
          
          <!-- Info Section -->
          <div class="grow text-center md:text-left w-full h-full flex flex-col justify-center">
            <div class="flex flex-col md:flex-row items-center gap-4 mb-4">
              <h1 class="text-3xl font-bold text-[var(--sys-text-primary)] tracking-tight">{{ fullName }}</h1>
              <span :class="['text-[11px] font-bold uppercase tracking-wide px-3 py-1 rounded-md border shadow-sm', statusClass[status] || statusClass['ĐANG_LÀM_VIỆC']]">{{ statusLabel[status] || 'Đang làm việc' }}</span>
            </div>
            
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 pt-2">
              <div class="bg-transparent text-left">
                <p class="text-[11px] font-bold text-[var(--sys-text-secondary)] uppercase tracking-wider mb-1 opacity-60">Mã nhân viên</p>
                <p class="text-lg font-bold text-[var(--sys-text-primary)]">#{{ employeeCode }}</p>
              </div>
              <div class="bg-transparent text-left">
                <p class="text-[11px] font-bold text-[var(--sys-text-secondary)] uppercase tracking-wider mb-1 opacity-60">Phòng ban</p>
                <p class="text-lg font-bold text-[var(--sys-brand-solid)]">{{ deptName }}</p>
              </div>
              <div class="bg-transparent text-left">
                <p class="text-[11px] font-bold text-[var(--sys-text-secondary)] uppercase tracking-wider mb-1 opacity-60">Chức vụ</p>
                <p class="text-lg font-bold text-[var(--sys-text-primary)] opacity-80">{{ positionName }}</p>
              </div>
              <div class="bg-transparent text-left">
                <p class="text-[11px] font-bold text-[var(--sys-text-secondary)] uppercase tracking-wider mb-1 opacity-60">Ngày gia nhập</p>
                <p class="text-lg font-bold text-[var(--sys-text-primary)]">{{ hireDateFormatted }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 bg-transparent">
        <!-- Left Column: Personal Info & Education -->
        <div class="lg:col-span-8 flex flex-col gap-6 bg-transparent">
          
          <!-- Main Form -->
          <div class="bg-[var(--sys-bg-surface)] rounded-lg shadow-sm border border-[var(--sys-border-subtle)] p-6 md:p-8">
            <h2 class="text-sm font-semibold text-[var(--sys-text-primary)] uppercase tracking-wide mb-8 flex items-center gap-2">
              <span class="material-symbols-outlined text-[var(--sys-brand-solid)] text-[22px]">person_edit</span>
              Hồ sơ chi tiết & Thông tin cá nhân
            </h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5 bg-transparent">
              <div class="space-y-1.5 text-left">
                <label class="text-[11px] font-bold text-[var(--sys-text-secondary)] uppercase tracking-wide ml-1 opacity-60">Họ và tên</label>
                <div class="relative group">
                  <span class="absolute inset-y-0 left-3 flex items-center text-[var(--sys-text-secondary)] opacity-50 group-focus-within:text-[var(--sys-brand-solid)]">
                    <span class="material-symbols-outlined text-[20px]">person</span>
                  </span>
                  <input v-model="profileForm.full_name" type="text" class="w-full h-10 pl-10 pr-4 bg-white border border-[var(--sys-border-strong)] rounded-md text-[13px] font-semibold text-[var(--sys-text-primary)] outline-none focus:border-[var(--sys-brand-solid)] shadow-sm transition-all">
                </div>
              </div>

              <div class="space-y-1.5 text-left">
                <label class="text-[11px] font-bold text-[var(--sys-text-secondary)] uppercase tracking-wide ml-1 opacity-60">Ngày sinh</label>
                <div class="relative group">
                  <span class="absolute inset-y-0 left-3 flex items-center text-[var(--sys-text-secondary)] opacity-50 group-focus-within:text-[var(--sys-brand-solid)]">
                    <span class="material-symbols-outlined text-[20px]">cake</span>
                  </span>
                  <input v-model="profileForm.date_of_birth" type="date" class="w-full h-10 pl-10 pr-4 bg-white border border-[var(--sys-border-strong)] rounded-md text-[13px] font-semibold text-[var(--sys-text-primary)] outline-none focus:border-[var(--sys-brand-solid)] shadow-sm transition-all">
                </div>
              </div>

              <div class="space-y-1.5 text-left">
                <label class="text-[11px] font-bold text-[var(--sys-text-secondary)] uppercase tracking-wide ml-1 opacity-60">Giới tính</label>
                <select v-model="profileForm.gender" class="w-full h-10 px-3 bg-white border border-[var(--sys-border-strong)] rounded-md text-[13px] font-semibold text-[var(--sys-text-primary)] outline-none focus:border-[var(--sys-brand-solid)] shadow-sm">
                  <option value="">Chọn giới tính</option>
                  <option value="NAM">Nam</option>
                  <option value="NỮ">Nữ</option>
                  <option value="KHÁC">Khác</option>
                </select>
              </div>

              <div class="space-y-1.5 text-left">
                <label class="text-[11px] font-bold text-[var(--sys-text-secondary)] uppercase tracking-wide ml-1 opacity-60">Số điện thoại</label>
                <input v-model="profileForm.phone_number" type="text" class="w-full h-10 px-4 bg-white border border-[var(--sys-border-strong)] rounded-md text-[13px] font-semibold text-[var(--sys-text-primary)] outline-none focus:border-[var(--sys-brand-solid)] shadow-sm">
              </div>

              <div class="col-span-1 md:col-span-2 space-y-1.5 text-left">
                <label class="text-[11px] font-bold text-[var(--sys-text-secondary)] uppercase tracking-wide ml-1 opacity-60">Email công ty</label>
                <input type="email" class="w-full h-10 px-4 bg-white border border-[var(--sys-border-strong)] rounded-md text-[13px] font-semibold text-[var(--sys-text-primary)] outline-none focus:border-[var(--sys-brand-solid)] shadow-sm" :value="profileForm.company_email" readonly>
              </div>

              <div class="space-y-1.5 text-left">
                <label class="text-[11px] font-bold text-[var(--sys-text-secondary)] uppercase tracking-wide ml-1 opacity-60">Email cá nhân</label>
                <input v-model="profileForm.personal_email" type="email" class="w-full h-10 px-4 bg-white border border-[var(--sys-border-strong)] rounded-md text-[13px] font-semibold text-[var(--sys-text-primary)] outline-none focus:border-[var(--sys-brand-solid)] shadow-sm">
              </div>

              <div class="space-y-1.5 text-left">
                <label class="text-[11px] font-bold text-[var(--sys-text-secondary)] uppercase tracking-wide ml-1 opacity-60">Địa chỉ hiện tại</label>
                <input v-model="profileForm.current_address" type="text" class="w-full h-10 px-4 bg-white border border-[var(--sys-border-strong)] rounded-md text-[13px] font-semibold text-[var(--sys-text-primary)] outline-none focus:border-[var(--sys-brand-solid)] shadow-sm">
              </div>

              <div class="col-span-1 md:col-span-2 space-y-1.5 text-left">
                <label class="text-[11px] font-bold text-[var(--sys-text-secondary)] uppercase tracking-wide ml-1 opacity-60">Địa chỉ thường trú</label>
                <input v-model="profileForm.permanent_address" type="text" class="w-full h-10 px-4 bg-white border border-[var(--sys-border-strong)] rounded-md text-[13px] font-semibold text-[var(--sys-text-primary)] outline-none focus:border-[var(--sys-brand-solid)] shadow-sm">
              </div>
            </div>

            <div class="mt-8 pt-6 border-t border-[var(--sys-border-subtle)] flex justify-end">
              <button
                class="h-10 px-8 bg-[var(--sys-brand-solid)] hover:brightness-90 text-white rounded-md font-bold text-[12px] uppercase tracking-wide shadow-sm transition-all flex items-center gap-2 active:scale-95 disabled:opacity-60 disabled:cursor-not-allowed"
                :disabled="savingProfile"
                @click="saveProfile"
              >
                <span class="material-symbols-outlined text-[18px]">save</span>
                {{ savingProfile ? 'Đang lưu...' : 'Lưu thông tin' }}
              </button>
            </div>
          </div>

          <!-- Education Table -->
          <div class="bg-[var(--sys-bg-surface)] rounded-lg shadow-sm border border-[var(--sys-border-subtle)] overflow-hidden">
            <div class="px-6 py-4 border-b border-[var(--sys-border-subtle)] flex justify-between items-center bg-[var(--sys-bg-page)]/50">
              <h2 class="text-sm font-semibold text-[var(--sys-text-primary)] uppercase tracking-wide m-0 flex items-center gap-2">
                <span class="material-symbols-outlined text-[var(--sys-brand-solid)] text-[22px]">school</span>
                Học vấn & Chứng chỉ
              </h2>
              <button class="h-8 px-4 bg-white border border-[var(--sys-border-strong)] text-[var(--sys-brand-solid)] rounded-md font-bold text-[11px] uppercase tracking-wide hover:shadow-sm transition-all flex items-center gap-1 shadow-sm" @click="showCertModal = true">
                <span class="material-symbols-outlined text-[18px]">add</span>
                Thêm mới
              </button>
            </div>
            
            <div class="overflow-x-auto custom-scrollbar">
              <table class="w-full text-left border-collapse">
                <thead class="bg-[var(--sys-bg-page)]">
                  <tr>
                    <th class="px-6 py-3 text-[12px] font-semibold text-[var(--sys-text-secondary)] uppercase border-b border-[var(--sys-border-subtle)]">Nội dung văn bằng</th>
                    <th class="px-6 py-3 text-[12px] font-semibold text-[var(--sys-text-secondary)] uppercase border-b border-[var(--sys-border-subtle)]">Cơ sở đào tạo</th>
                    <th class="px-6 py-3 text-[12px] font-semibold text-[var(--sys-text-secondary)] uppercase border-b border-[var(--sys-border-subtle)] text-center">Năm</th>
                    <th class="px-6 py-3 text-[12px] font-semibold text-[var(--sys-text-secondary)] uppercase border-b border-[var(--sys-border-subtle)] text-right">Tài liệu</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-[var(--sys-border-subtle)]">
                  <tr v-for="item in certificates" :key="item.certificate_id" class="hover:bg-[var(--sys-bg-hover)] transition-colors">
                    <td class="px-6 py-3 text-[13px] font-semibold text-[var(--sys-text-primary)] bg-transparent">{{ item.certificate_name || 'Chưa đặt tên' }}</td>
                    <td class="px-6 py-3 text-[12px] font-medium text-[var(--sys-text-secondary)] bg-transparent">{{ item.issued_by || item.certificate_type_name || '—' }}</td>
                    <td class="px-6 py-3 text-[13px] font-bold text-center bg-transparent">{{ extractYear(item.issued_date) }}</td>
                    <td class="px-6 py-3 text-right bg-transparent">
                      <div class="inline-flex items-center gap-2">
                        <button
                          type="button"
                          class="text-[var(--sys-brand-solid)] hover:opacity-80 transition-opacity inline-flex items-center gap-1 font-bold text-[11px] uppercase tracking-tight disabled:opacity-45 disabled:cursor-not-allowed"
                          :disabled="!resolveCertificateFileUrl(item)"
                          @click="openCertificateDocument(item)"
                        >
                          <span class="material-symbols-outlined text-[16px]">attachment</span>
                          Xem PDF
                        </button>
                        <button class="text-[var(--sys-danger-text)] hover:opacity-80 transition-opacity inline-flex items-center gap-1 font-bold text-[11px] uppercase tracking-tight" @click="removeCertificate(item.certificate_id)">
                          <span class="material-symbols-outlined text-[16px]">delete</span>
                          Xóa
                        </button>
                      </div>
                    </td>
                  </tr>
                  <tr v-if="certificates.length === 0">
                    <td colspan="4" class="px-6 py-6 text-center text-[12px] font-semibold text-[var(--sys-text-secondary)]">Chưa có chứng chỉ nào.</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- Right Column: Work Information -->
        <div class="lg:col-span-4 flex flex-col gap-6">
          <div class="bg-[var(--sys-bg-surface)] rounded-lg shadow-sm border border-[var(--sys-border-subtle)] p-6 md:p-8 flex-grow flex flex-col">
            <h2 class="text-sm font-semibold text-[var(--sys-text-primary)] uppercase tracking-wide mb-8 flex items-center gap-2">
              <span class="material-symbols-outlined text-[var(--sys-brand-solid)] text-[22px]">work_history</span>
              Phân bổ nhân lực hiện tại
            </h2>
            
            <div class="space-y-8 flex-grow">
              <div class="flex gap-4 items-start group">
                <div class="w-10 h-10 rounded-md bg-[var(--sys-brand-soft)] text-[var(--sys-brand-solid)] flex items-center justify-center shrink-0 border border-[var(--sys-brand-border)] group-hover:bg-[var(--sys-brand-solid)] group-hover:text-white transition-all shadow-sm">
                  <span class="material-symbols-outlined text-[20px]">account_tree</span>
                </div>
                <div class="bg-transparent text-left">
                  <p class="text-[11px] font-bold text-[var(--sys-text-secondary)] uppercase tracking-wide mb-1 opacity-60">Phòng ban</p>
                  <p class="text-sm font-bold text-[var(--sys-text-primary)]">{{ deptName }}</p>
                </div>
              </div>

              <div class="flex gap-4 items-start group">
                <div class="w-10 h-10 rounded-md bg-blue-50 text-blue-600 flex items-center justify-center shrink-0 border border-blue-100 group-hover:bg-blue-600 group-hover:text-white transition-all shadow-sm">
                  <span class="material-symbols-outlined text-[20px]">badge</span>
                </div>
                <div class="bg-transparent text-left">
                  <p class="text-[11px] font-bold text-[var(--sys-text-secondary)] uppercase tracking-wide mb-1 opacity-60">Chức danh / Cấp bậc</p>
                  <p class="text-sm font-bold text-[var(--sys-text-primary)]">{{ positionName }}</p>
                </div>
              </div>

              <div class="flex gap-4 items-start group">
                <div class="w-10 h-10 rounded-md bg-[var(--sys-bg-page)] text-[var(--sys-text-primary)] flex items-center justify-center shrink-0 border border-[var(--sys-border-subtle)] hover:border-[var(--sys-brand-solid)] transition-all shadow-sm overflow-hidden">
                  <img :src="managerAvatar" alt="Manager" class="w-full h-full object-cover" />
                </div>
                <div class="bg-transparent text-left">
                  <p class="text-[11px] font-bold text-[var(--sys-text-secondary)] uppercase tracking-wide mb-1 opacity-60">Quản lý trực tiếp</p>
                  <p class="text-sm font-bold text-[var(--sys-text-primary)]">{{ managerName }}</p>
                </div>
              </div>

              <div class="flex gap-4 items-start group">
                <div class="w-10 h-10 rounded-md bg-[var(--sys-success-soft)] text-[var(--sys-success-text)] flex items-center justify-center shrink-0 border border-[var(--sys-success-border)] group-hover:bg-[var(--sys-success-solid)] group-hover:text-white transition-all shadow-sm">
                  <span class="material-symbols-outlined text-[20px]">description</span>
                </div>
                <div class="bg-transparent text-left">
                  <p class="text-[11px] font-bold text-[var(--sys-text-secondary)] uppercase tracking-wide mb-1 opacity-60">Loại hợp đồng</p>
                  <p class="text-sm font-bold text-[var(--sys-text-primary)] leading-snug">Hợp đồng vô thời hạn</p>
                </div>
              </div>
            </div>

            <!-- Retention Metric -->
            <div class="mt-10 pt-8 border-t border-[var(--sys-border-subtle)] bg-transparent">
              <div class="flex justify-between items-end mb-3">
                <p class="text-[11px] font-bold text-[var(--sys-text-secondary)] uppercase tracking-wide opacity-60">Gắn bó</p>
                <p class="text-[12px] font-bold text-[var(--sys-brand-solid)] uppercase tracking-widest">{{ tenure }}</p>
              </div>
              <div class="h-2 w-full bg-[var(--sys-bg-page)] rounded-full overflow-hidden border border-[var(--sys-border-subtle)]">
                <div class="h-full bg-[var(--sys-brand-solid)] transition-all" style="width: 50%"></div>
              </div>
              <button class="w-full mt-8 bg-[var(--sys-bg-page)] hover:bg-white text-[var(--sys-text-secondary)] font-bold text-[11px] uppercase tracking-wide py-3 rounded-md border border-[var(--sys-border-strong)] transition-all active:scale-95 shadow-sm" @click="openPromotionHistory">
                Lịch sử thăng tiến
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Footer Danger Zone -->
      <div class="flex justify-end pt-8 opacity-60 hover:opacity-100 transition-opacity">
        <router-link :to="resignationRoute" class="h-10 px-6 bg-[var(--sys-danger-soft)] text-[var(--sys-danger-text)] rounded-md border border-[var(--sys-danger-border)] font-bold text-[11px] uppercase tracking-wide flex items-center gap-2 hover:bg-[var(--sys-danger-solid)] hover:text-white transition-all">
          <span class="material-symbols-outlined text-[20px]">logout</span>
          Nộp đơn nghỉ việc
        </router-link>
      </div>

      <Teleport to="body">
        <div v-if="showPromotionModal" class="fixed inset-0 z-[120] bg-black/45 flex items-center justify-center p-4" @click.self="showPromotionModal=false">
          <div class="w-full max-w-3xl bg-white rounded-xl border border-[var(--sys-border-subtle)] shadow-2xl overflow-hidden">
            <div class="px-5 py-4 border-b border-[var(--sys-border-subtle)] flex items-center justify-between">
              <h3 class="text-sm font-bold uppercase tracking-wide text-[var(--sys-text-primary)]">Lịch sử thăng tiến</h3>
              <button class="text-[var(--sys-text-secondary)]" @click="showPromotionModal=false">
                <span class="material-symbols-outlined">close</span>
              </button>
            </div>
            <div class="max-h-[70vh] overflow-auto">
              <table class="w-full text-left border-collapse">
                <thead class="bg-[var(--sys-bg-page)]">
                  <tr>
                    <th class="px-4 py-3 text-[11px] uppercase text-[var(--sys-text-secondary)] font-bold">Bắt đầu</th>
                    <th class="px-4 py-3 text-[11px] uppercase text-[var(--sys-text-secondary)] font-bold">Kết thúc</th>
                    <th class="px-4 py-3 text-[11px] uppercase text-[var(--sys-text-secondary)] font-bold">Phòng ban</th>
                    <th class="px-4 py-3 text-[11px] uppercase text-[var(--sys-text-secondary)] font-bold">Chức vụ</th>
                    <th class="px-4 py-3 text-[11px] uppercase text-[var(--sys-text-secondary)] font-bold">Ghi chú</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(row, idx) in employmentHistories" :key="row.history_id || row.id || idx" class="border-b border-[var(--sys-border-subtle)]">
                    <td class="px-4 py-3 text-[13px] font-semibold">{{ formatDate(row.start_date || row.started_at) }}</td>
                    <td class="px-4 py-3 text-[13px]">{{ row.is_current ? 'Hiện tại' : formatDate(row.end_date || row.ended_at) }}</td>
                    <td class="px-4 py-3 text-[13px]">{{ row.department_name || row.department || '—' }}</td>
                    <td class="px-4 py-3 text-[13px]">{{ row.position_name || row.position || '—' }}</td>
                    <td class="px-4 py-3 text-[12px] text-[var(--sys-text-secondary)]">{{ row.notes || row.reason || '—' }}</td>
                  </tr>
                  <tr v-if="employmentHistories.length === 0">
                    <td colspan="5" class="px-4 py-5 text-center text-[12px] font-semibold text-[var(--sys-text-secondary)]">Chưa có lịch sử thăng tiến.</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </Teleport>

      <Teleport to="body">
        <div v-if="showCertModal" class="fixed inset-0 z-[130] bg-black/45 flex items-center justify-center p-4" @click.self="showCertModal=false">
          <div class="w-full max-w-2xl bg-white rounded-xl border border-[var(--sys-border-subtle)] shadow-2xl overflow-hidden">
            <div class="px-5 py-4 border-b border-[var(--sys-border-subtle)] flex items-center justify-between">
              <h3 class="text-sm font-bold uppercase tracking-wide text-[var(--sys-text-primary)]">Thêm chứng chỉ</h3>
              <button class="text-[var(--sys-text-secondary)]" @click="showCertModal=false">
                <span class="material-symbols-outlined">close</span>
              </button>
            </div>
            <div class="p-5 grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="text-[11px] font-bold uppercase tracking-wide text-[var(--sys-text-secondary)]">Tên chứng chỉ *</label>
                <input v-model="certForm.certificate_name" class="mt-1 w-full h-10 px-3 bg-[var(--sys-bg-page)] border border-[var(--sys-border-strong)] rounded-md text-sm font-medium" />
              </div>
              <div>
                <label class="text-[11px] font-bold uppercase tracking-wide text-[var(--sys-text-secondary)]">Loại chứng chỉ</label>
                <select v-model="certForm.certificate_type_id" class="mt-1 w-full h-10 px-3 bg-[var(--sys-bg-page)] border border-[var(--sys-border-strong)] rounded-md text-sm font-medium">
                  <option :value="null">Không chọn</option>
                  <option v-for="t in certificateTypes" :key="t.certificate_type_id" :value="Number(t.certificate_type_id)">
                    {{ t.certificate_type_name }}
                  </option>
                </select>
              </div>
              <div>
                <label class="text-[11px] font-bold uppercase tracking-wide text-[var(--sys-text-secondary)]">Đơn vị cấp</label>
                <input v-model="certForm.issued_by" class="mt-1 w-full h-10 px-3 bg-[var(--sys-bg-page)] border border-[var(--sys-border-strong)] rounded-md text-sm font-medium" />
              </div>
              <div>
                <label class="text-[11px] font-bold uppercase tracking-wide text-[var(--sys-text-secondary)]">Mã chứng chỉ</label>
                <input v-model="certForm.certificate_number" class="mt-1 w-full h-10 px-3 bg-[var(--sys-bg-page)] border border-[var(--sys-border-strong)] rounded-md text-sm font-medium" />
              </div>
              <div>
                <label class="text-[11px] font-bold uppercase tracking-wide text-[var(--sys-text-secondary)]">Ngày cấp</label>
                <input v-model="certForm.issued_date" type="date" class="mt-1 w-full h-10 px-3 bg-[var(--sys-bg-page)] border border-[var(--sys-border-strong)] rounded-md text-sm font-medium" />
              </div>
              <div>
                <label class="text-[11px] font-bold uppercase tracking-wide text-[var(--sys-text-secondary)]">Ngày hết hạn</label>
                <input v-model="certForm.expiry_date" type="date" class="mt-1 w-full h-10 px-3 bg-[var(--sys-bg-page)] border border-[var(--sys-border-strong)] rounded-md text-sm font-medium" />
              </div>
              <div class="md:col-span-2">
                <label class="text-[11px] font-bold uppercase tracking-wide text-[var(--sys-text-secondary)]">Link tài liệu (PDF URL)</label>
                <input v-model="certForm.file_url" class="mt-1 w-full h-10 px-3 bg-[var(--sys-bg-page)] border border-[var(--sys-border-strong)] rounded-md text-sm font-medium" placeholder="https://..." />
              </div>
            </div>
            <div class="px-5 py-4 border-t border-[var(--sys-border-subtle)] flex justify-end gap-2">
              <button class="h-9 px-4 rounded-md border border-[var(--sys-border-strong)] text-[12px] font-bold uppercase tracking-wide" @click="showCertModal=false">Hủy</button>
              <button class="h-9 px-4 rounded-md bg-[var(--sys-brand-solid)] text-white text-[12px] font-bold uppercase tracking-wide disabled:opacity-60" :disabled="savingCertificate" @click="addCertificate">
                {{ savingCertificate ? 'Đang lưu...' : 'Lưu chứng chỉ' }}
              </button>
            </div>
          </div>
        </div>
      </Teleport>
    </div>
  </div>
</template>

<script setup>
import { computed, reactive, ref, watch } from 'vue';
import { useCurrentUser } from '@/composables/useCurrentUser.js';
import { BE_API_BASE, getAccessToken } from '@/services/runtimeConfig.js';
import { getCurrentUserRole, handleUnauthorized } from '@/services/session.js';

const {
  fullName, employeeCode, deptName, positionName, employeeId,
  hireDateFormatted,
  status, avatar, tenure, managerName, managerAvatar
} = useCurrentUser();

const profileForm = reactive({
  full_name: '',
  date_of_birth: '',
  gender: '',
  phone_number: '',
  company_email: '',
  personal_email: '',
  current_address: '',
  permanent_address: '',
});

const employmentHistories = ref([]);
const certificates = ref([]);
const certificateTypes = ref([]);
const showPromotionModal = ref(false);
const showCertModal = ref(false);
const savingProfile = ref(false);
const savingCertificate = ref(false);
const currentRole = getCurrentUserRole();
const resignationRoute = computed(() => (['admin', 'hr'].includes(currentRole) ? '/admin/donnghiviec' : '/nhanvien/donnghiviec'));

const certForm = reactive({
  certificate_type_id: null,
  certificate_name: '',
  issued_by: '',
  issued_date: '',
  expiry_date: '',
  certificate_number: '',
  file_url: '',
});

const authHeaders = () => {
  const token = getAccessToken();
  if (!token) throw new Error('Thiếu phiên đăng nhập, vui lòng đăng nhập lại.');
  return {
    Authorization: `Bearer ${token}`,
    'Content-Type': 'application/json',
  };
};

const apiRequest = async (path, { method = 'GET', body } = {}) => {
  const response = await fetch(`${BE_API_BASE}${path}`, {
    method,
    headers: authHeaders(),
    body: body !== undefined ? JSON.stringify(body) : undefined,
  });

  if (response.status === 401) {
    handleUnauthorized();
    throw new Error('Phiên đăng nhập đã hết hạn.');
  }

  const payload = await response.json().catch(() => ({}));
  if (!response.ok || payload?.success === false) {
    throw new Error(payload?.message || `Request failed (${response.status})`);
  }
  return payload;
};

const formatDate = (value) => {
  const raw = String(value || '').trim();
  if (!raw) return '—';
  const datePart = raw.includes('T') ? raw.slice(0, 10) : raw.slice(0, 10);
  const [y, m, d] = datePart.split('-');
  if (!y || !m || !d) return raw;
  return `${d}/${m}/${y}`;
};

const extractYear = (value) => {
  const raw = String(value || '').trim();
  if (!raw) return '—';
  return raw.slice(0, 4);
};

const resolveCertificateFileUrl = (item = {}) => {
  const raw = String(
    item.file_url ||
      item.fileUrl ||
      item.certificate_file_url ||
      item.certificateFileUrl ||
      item.document_url ||
      item.documentUrl ||
      item.document_path ||
      item.documentPath ||
      item.file_path ||
      item.filePath ||
      ''
  ).trim();
  if (!raw) return '';
  if (/^https?:\/\//i.test(raw)) return raw;
  if (raw.startsWith('/')) return `${window.location.origin}${raw}`;
  return `${window.location.origin}/${raw.replace(/^\.?\//, '')}`;
};

const openCertificateDocument = (item = {}) => {
  const url = resolveCertificateFileUrl(item);
  if (!url) {
    alert('Tài liệu chứng chỉ chưa có file PDF để xem.');
    return;
  }
  window.open(url, '_blank', 'noopener,noreferrer');
};

const hydrateProfileForm = (employee = {}) => {
  profileForm.full_name = String(employee.full_name || fullName.value || '');
  profileForm.date_of_birth = String(employee.date_of_birth || '').slice(0, 10);
  profileForm.gender = String(employee.gender || '');
  profileForm.phone_number = String(employee.phone_number || '');
  profileForm.company_email = String(employee.company_email || '');
  profileForm.personal_email = String(employee.personal_email || '');
  profileForm.current_address = String(employee.current_address || '');
  profileForm.permanent_address = String(employee.permanent_address || '');
};

const loadProfile = async () => {
  if (!employeeId.value) return;
  try {
    const payload = await apiRequest(`/employees/${employeeId.value}/profile`);
    const data = payload?.data || {};
    hydrateProfileForm(data.employee || {});
    employmentHistories.value = Array.isArray(data.employment_histories)
      ? data.employment_histories
      : Array.isArray(data.employment_history)
        ? data.employment_history
        : Array.isArray(data.histories)
          ? data.histories
          : [];
    certificates.value = Array.isArray(data.certificates) ? data.certificates : [];
    certificateTypes.value = Array.isArray(data.certificate_types) ? data.certificate_types : [];
  } catch (error) {
    console.error('Load profile error:', error);
  }
};

const openPromotionHistory = async () => {
  showPromotionModal.value = true;
  if (!employmentHistories.value.length) {
    await loadProfile();
  }
};

const saveProfile = async () => {
  if (!employeeId.value) return;
  savingProfile.value = true;
  try {
    await apiRequest(`/employees/${employeeId.value}/profile`, {
      method: 'PATCH',
      body: {
        full_name: profileForm.full_name,
        date_of_birth: profileForm.date_of_birth || null,
        gender: profileForm.gender || null,
        phone_number: profileForm.phone_number || null,
        personal_email: profileForm.personal_email || null,
        current_address: profileForm.current_address || null,
        permanent_address: profileForm.permanent_address || null,
      },
    });
    await loadProfile();
    alert('Đã lưu thông tin hồ sơ thành công.');
  } catch (error) {
    alert(error?.message || 'Không lưu được hồ sơ.');
  } finally {
    savingProfile.value = false;
  }
};

const resetCertForm = () => {
  certForm.certificate_type_id = null;
  certForm.certificate_name = '';
  certForm.issued_by = '';
  certForm.issued_date = '';
  certForm.expiry_date = '';
  certForm.certificate_number = '';
  certForm.file_url = '';
};

const addCertificate = async () => {
  if (!employeeId.value) return;
  if (!certForm.certificate_name.trim()) {
    alert('Vui lòng nhập tên chứng chỉ.');
    return;
  }

  savingCertificate.value = true;
  try {
    await apiRequest(`/employees/${employeeId.value}/certificates`, {
      method: 'POST',
      body: {
        certificate_type_id: certForm.certificate_type_id,
        certificate_name: certForm.certificate_name.trim(),
        issued_by: certForm.issued_by.trim() || null,
        issued_date: certForm.issued_date || null,
        expiry_date: certForm.expiry_date || null,
        certificate_number: certForm.certificate_number.trim() || null,
        file_url: certForm.file_url.trim() || null,
      },
    });
    await loadProfile();
    resetCertForm();
    showCertModal.value = false;
  } catch (error) {
    alert(error?.message || 'Không thể thêm chứng chỉ.');
  } finally {
    savingCertificate.value = false;
  }
};

const removeCertificate = async (certificateId) => {
  if (!employeeId.value || !certificateId) return;
  const accepted = window.confirm('Bạn có chắc muốn xóa chứng chỉ này?');
  if (!accepted) return;
  try {
    await apiRequest(`/employees/${employeeId.value}/certificates/${certificateId}`, {
      method: 'DELETE',
    });
    await loadProfile();
  } catch (error) {
    alert(error?.message || 'Không thể xóa chứng chỉ.');
  }
};

watch(
  () => employeeId.value,
  () => {
    void loadProfile();
  },
  { immediate: true }
);

const statusLabel = {
  'ĐANG_LÀM_VIỆC': 'Đang làm việc',
  'THỬ_VIỆC': 'Thử việc',
  'ĐÃ_NGHỈ_VIỆC': 'Đã nghỉ việc',
  'NGHỈ_THAI_SẢN': 'Nghỉ thai sản',
};

const statusClass = {
  'ĐANG_LÀM_VIỆC': 'bg-[var(--sys-success-soft)] text-[var(--sys-success-text)] border-[var(--sys-success-border)]',
  'THỬ_VIỆC': 'bg-[var(--sys-warning-soft)] text-[var(--sys-warning-text)] border-[var(--sys-warning-border)]',
  'ĐÃ_NGHỈ_VIỆC': 'bg-[var(--sys-danger-soft)] text-[var(--sys-danger-text)] border-[var(--sys-danger-border)]',
  'NGHỈ_THAI_SẢN': 'bg-[var(--sys-brand-soft)] text-[var(--sys-brand-solid)] border-[var(--sys-border-subtle)]',
};
</script>

<style scoped>
.avatar-box {
  transition: transform 0.2s cubic-bezier(0.16, 1, 0.3, 1);
}
.avatar-box:hover {
  transform: scale(1.02);
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
  border-radius: 5px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
  background: var(--sys-brand-solid);
}
</style>
