<template>
  <div class="space-y-6 pb-8 max-w-6xl mx-auto">
    <!-- Header Area -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 bg-transparent text-left">
      <div class="bg-transparent text-left">
        <h1 class="text-xl font-semibold text-[var(--sys-text-primary)] mb-1">Cấu hình Hệ thống</h1>
        <p class="text-sm text-[var(--sys-text-secondary)]">Quản lý tham số vận hành, phân quyền và thiết lập bảo mật toàn hệ thống.</p>
      </div>
      <div class="flex items-center gap-3 bg-transparent shrink-0">
        <button 
          @click="saveAllSettings"
          class="h-10 px-6 bg-[var(--sys-brand-solid)] text-white rounded-md font-semibold text-sm hover:brightness-90 transition-all flex items-center gap-2 shadow-sm"
        >
          <span class="material-symbols-outlined text-[20px]">save</span>
          Lưu cấu hình
        </button>
      </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
      <!-- Sidebar Navigation -->
      <div class="lg:col-span-3 space-y-1 bg-transparent">
        <button 
          v-for="tab in tabs" 
          :key="tab.id"
          @click="activeTab = tab.id"
          :class="[
            'w-full flex items-center gap-3 px-4 py-2.5 rounded-md font-semibold text-[13px] transition-all',
            activeTab === tab.id ? 'bg-[var(--sys-brand-soft)] text-[var(--sys-brand-solid)] shadow-sm' : 'text-[var(--sys-text-secondary)] hover:bg-[var(--sys-bg-hover)]'
          ]"
        >
          <span class="material-symbols-outlined text-[20px]">{{ tab.icon }}</span>
          {{ tab.label }}
        </button>
      </div>

      <!-- Main Content Area -->
      <div class="lg:col-span-9 bg-transparent">
        <div class="bg-[var(--sys-bg-surface)] rounded-lg border border-[var(--sys-border-subtle)] shadow-sm overflow-hidden p-6 md:p-8">
          
          <!-- General Tab -->
          <div v-if="activeTab === 'general'" class="space-y-8 animate-fadeIn">
            <div class="bg-transparent text-left">
              <h3 class="text-base font-semibold text-[var(--sys-text-primary)] flex items-center gap-2 mb-6 uppercase tracking-wide">
                <span class="w-1.5 h-6 bg-[var(--sys-brand-solid)] rounded-full"></span>
                Định danh Tổ chức
              </h3>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6 bg-transparent">
                <div class="space-y-1.5 bg-transparent text-left">
                  <label class="text-[13px] font-medium text-[var(--sys-text-primary)] block">Tên doanh nghiệp chính thức</label>
                  <input v-model="settings.companyName" type="text" class="w-full h-10 px-3 bg-[var(--sys-bg-page)] border border-[var(--sys-border-strong)] rounded-md text-sm text-[var(--sys-text-primary)] focus:border-[var(--sys-brand-solid)] outline-none transition-all">
                </div>
                <div class="space-y-1.5 bg-transparent text-left">
                  <label class="text-[13px] font-medium text-[var(--sys-text-primary)] block">Mã số thuế</label>
                  <input v-model="settings.taxCode" type="text" class="w-full h-10 px-3 bg-[var(--sys-bg-page)] border border-[var(--sys-border-strong)] rounded-md text-sm text-[var(--sys-text-primary)] focus:border-[var(--sys-brand-solid)] outline-none transition-all">
                </div>
                <div class="md:col-span-2 space-y-1.5 bg-transparent text-left">
                  <label class="text-[13px] font-medium text-[var(--sys-text-primary)] block">Địa chỉ trụ sở điều hành</label>
                  <input v-model="settings.address" type="text" class="w-full h-10 px-3 bg-[var(--sys-bg-page)] border border-[var(--sys-border-strong)] rounded-md text-sm text-[var(--sys-text-primary)] focus:border-[var(--sys-brand-solid)] outline-none transition-all">
                </div>
              </div>
            </div>

            <div class="h-px bg-[var(--sys-border-subtle)]"></div>

            <div class="bg-transparent text-left">
              <h3 class="text-base font-semibold text-[var(--sys-text-primary)] flex items-center gap-2 mb-6 uppercase tracking-wide">
                <span class="w-1.5 h-6 bg-[var(--sys-success-solid)] rounded-full"></span>
                Ngôn ngữ & Vùng miền
              </h3>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6 bg-transparent">
                <div class="space-y-1.5 bg-transparent text-left">
                  <label class="text-[13px] font-medium text-[var(--sys-text-primary)] block">Ngôn ngữ mặc định</label>
                  <select v-model="settings.language" class="w-full h-10 px-3 bg-[var(--sys-bg-page)] border border-[var(--sys-border-strong)] rounded-md text-sm text-[var(--sys-text-primary)] focus:border-[var(--sys-brand-solid)] outline-none transition-all">
                    <option value="vi">Tiếng Việt (Vietnam)</option>
                    <option value="en">English (United States)</option>
                  </select>
                </div>
                <div class="space-y-1.5 bg-transparent text-left">
                  <label class="text-[13px] font-medium text-[var(--sys-text-primary)] block">Múi giờ vận hành</label>
                  <select v-model="settings.timezone" class="w-full h-10 px-3 bg-[var(--sys-bg-page)] border border-[var(--sys-border-strong)] rounded-md text-sm text-[var(--sys-text-primary)] focus:border-[var(--sys-brand-solid)] outline-none transition-all">
                    <option value="Asia/Ho_Chi_Minh">(GMT+07:00) Hanoi</option>
                    <option value="UTC">(GMT+00:00) UTC</option>
                  </select>
                </div>
              </div>
            </div>
          </div>

          <!-- GPS Tab -->
          <div v-if="activeTab === 'gps'" class="space-y-8 animate-fadeIn">
            <div class="bg-transparent text-left">
              <h3 class="text-base font-semibold text-[var(--sys-text-primary)] flex items-center gap-2 mb-6 uppercase tracking-wide">
                <span class="w-1.5 h-6 bg-[var(--sys-danger-solid)] rounded-full"></span>
                Vùng Geofence & Chấm công GPS
              </h3>
              <p class="text-sm text-[var(--sys-text-secondary)] mb-6">
                Mọi tài khoản mới sẽ so vị trí thật của thiết bị với các mốc công ty được cấu hình ở đây. GREEN cho phép chấm công, YELLOW cho phép nhưng gắn cờ, RED sẽ chặn.
              </p>

              <div class="space-y-6">
                <div class="flex items-center justify-between p-4 bg-[var(--sys-bg-page)] border border-[var(--sys-border-subtle)] rounded-lg hover:bg-white transition-all shadow-sm">
                  <div class="bg-transparent text-left">
                    <p class="text-[14px] font-semibold text-[var(--sys-text-primary)] mb-1">Khóa GPS bắt buộc</p>
                    <p class="text-[12px] text-[var(--sys-text-secondary)]">Nhân viên phải ở đúng vùng địa lý và cấp quyền GPS thì mới có thể chấm công.</p>
                  </div>
                  <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" v-model="settings.attendanceCompanyGeoLockEnabled" class="sr-only peer">
                    <div class="w-11 h-6 bg-[var(--sys-bg-page)] border border-[var(--sys-border-subtle)] rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[var(--sys-brand-solid)]"></div>
                  </label>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 bg-transparent">
                  <div class="space-y-1.5 bg-transparent text-left">
                    <label class="text-[13px] font-medium text-[var(--sys-text-primary)] block">Bán kính GREEN (m)</label>
                    <input v-model="settings.attendanceGreenRadiusM" type="number" min="10" step="1" class="w-full h-10 px-3 bg-[var(--sys-bg-page)] border border-[var(--sys-border-strong)] rounded-md text-sm text-[var(--sys-text-primary)] focus:border-[var(--sys-brand-solid)] outline-none transition-all">
                  </div>
                  <div class="space-y-1.5 bg-transparent text-left">
                    <label class="text-[13px] font-medium text-[var(--sys-text-primary)] block">Bán kính YELLOW (m)</label>
                    <input v-model="settings.attendanceYellowRadiusM" type="number" min="10" step="1" class="w-full h-10 px-3 bg-[var(--sys-bg-page)] border border-[var(--sys-border-strong)] rounded-md text-sm text-[var(--sys-text-primary)] focus:border-[var(--sys-brand-solid)] outline-none transition-all">
                  </div>
                </div>

                <div class="rounded-lg border border-[var(--sys-border-subtle)] bg-[var(--sys-bg-page)] p-4 space-y-4">
                  <div class="flex flex-col md:flex-row md:items-end gap-3">
                    <div class="flex-1 space-y-1.5">
                      <label class="text-[13px] font-medium text-[var(--sys-text-primary)] block">Nhập địa chỉ hoặc tọa độ</label>
                      <input
                        v-model="gpsLookupQuery"
                        type="text"
                        class="w-full h-10 px-3 bg-white border border-[var(--sys-border-strong)] rounded-md text-sm text-[var(--sys-text-primary)] focus:border-[var(--sys-brand-solid)] outline-none transition-all"
                        placeholder="Ví dụ: 193 Đỗ Văn Thi, Đồng Nai hoặc 10.9440833, 106.8816667"
                      >
                    </div>
                    <div class="flex flex-wrap gap-2">
                      <button
                        type="button"
                        class="h-10 px-4 rounded-md border border-[var(--sys-border-strong)] bg-white text-[13px] font-semibold text-[var(--sys-text-primary)] hover:border-[var(--sys-brand-solid)] transition-all"
                        :disabled="gpsLookupBusy"
                        @click="lookupGpsLocation"
                      >
                        {{ gpsLookupBusy ? 'Đang tìm...' : 'Tìm vị trí' }}
                      </button>
                      <button
                        type="button"
                        class="h-10 px-4 rounded-md bg-[var(--sys-brand-solid)] text-white text-[13px] font-semibold hover:brightness-95 transition-all"
                        :disabled="gpsLocateBusy"
                        @click="useCurrentGpsLocation"
                      >
                        {{ gpsLocateBusy ? 'Đang lấy GPS...' : 'Lấy vị trí của tôi' }}
                      </button>
                    </div>
                  </div>

                  <p v-if="gpsEditorMessage" class="text-[12px] rounded-md border px-3 py-2" :class="gpsEditorMessageClass">
                    {{ gpsEditorMessage }}
                  </p>

                  <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="space-y-1.5">
                      <label class="text-[13px] font-medium text-[var(--sys-text-primary)] block">Tên mốc</label>
                      <input v-model="gpsDraft.label" type="text" class="w-full h-10 px-3 bg-white border border-[var(--sys-border-strong)] rounded-md text-sm text-[var(--sys-text-primary)] focus:border-[var(--sys-brand-solid)] outline-none transition-all" placeholder="Ví dụ: Văn phòng chính">
                    </div>
                    <div class="space-y-1.5">
                      <label class="text-[13px] font-medium text-[var(--sys-text-primary)] block">Địa chỉ hiển thị</label>
                      <input v-model="gpsDraft.address" type="text" class="w-full h-10 px-3 bg-white border border-[var(--sys-border-strong)] rounded-md text-sm text-[var(--sys-text-primary)] focus:border-[var(--sys-brand-solid)] outline-none transition-all" placeholder="Ví dụ: 193 Đỗ Văn Thi, Đồng Nai">
                    </div>
                    <div class="space-y-1.5">
                      <label class="text-[13px] font-medium text-[var(--sys-text-primary)] block">Vĩ độ (lat)</label>
                      <input v-model="gpsDraft.lat" type="number" step="0.000001" class="w-full h-10 px-3 bg-white border border-[var(--sys-border-strong)] rounded-md text-sm text-[var(--sys-text-primary)] focus:border-[var(--sys-brand-solid)] outline-none transition-all" placeholder="10.9440833">
                    </div>
                    <div class="space-y-1.5">
                      <label class="text-[13px] font-medium text-[var(--sys-text-primary)] block">Kinh độ (lng)</label>
                      <input v-model="gpsDraft.lng" type="number" step="0.000001" class="w-full h-10 px-3 bg-white border border-[var(--sys-border-strong)] rounded-md text-sm text-[var(--sys-text-primary)] focus:border-[var(--sys-brand-solid)] outline-none transition-all" placeholder="106.8816667">
                    </div>
                  </div>

                  <div class="flex flex-wrap gap-2">
                    <button
                      type="button"
                      class="h-10 px-4 rounded-md bg-[var(--sys-brand-solid)] text-white text-[13px] font-semibold hover:brightness-95 transition-all"
                      @click="upsertAnchorDraft"
                    >
                      {{ gpsEditingIndex >= 0 ? 'Cập nhật mốc' : 'Thêm mốc' }}
                    </button>
                    <button
                      type="button"
                      class="h-10 px-4 rounded-md border border-[var(--sys-border-strong)] bg-white text-[13px] font-semibold text-[var(--sys-text-primary)] hover:border-[var(--sys-brand-solid)] transition-all"
                      @click="resetGpsDraft"
                    >
                      Làm mới form
                    </button>
                    <button
                      v-if="parsedAnchors.length"
                      type="button"
                      class="h-10 px-4 rounded-md border border-[var(--sys-danger-border)] bg-[var(--sys-danger-soft)] text-[13px] font-semibold text-[var(--sys-danger-text)] hover:brightness-95 transition-all"
                      @click="clearAllAnchors"
                    >
                      Xóa tất cả mốc cũ
                    </button>
                  </div>

                  <div class="rounded-lg overflow-hidden border border-[var(--sys-border-subtle)] bg-white">
                    <div class="px-4 py-3 border-b border-[var(--sys-border-subtle)] bg-[var(--sys-bg-page)]">
                      <p class="m-0 text-[12px] font-bold uppercase tracking-wide text-[var(--sys-text-secondary)]">Bản đồ xem nhanh</p>
                      <p class="m-0 mt-1 text-[12px] text-[var(--sys-text-secondary)]">Map hiển thị theo mốc đang sửa hoặc mốc đầu tiên trong danh sách.</p>
                    </div>
                    <iframe
                      v-if="mapPreviewSrc"
                      :src="mapPreviewSrc"
                      class="w-full h-[320px] border-0"
                      loading="lazy"
                      referrerpolicy="no-referrer-when-downgrade"
                      title="Bản đồ geofence"
                    ></iframe>
                    <div v-else class="h-[220px] flex items-center justify-center text-[13px] text-[var(--sys-text-secondary)] bg-[var(--sys-bg-page)]">
                      Thêm hoặc chọn một mốc để hiển thị map.
                    </div>
                  </div>
                </div>

                <div class="space-y-1.5 bg-transparent text-left">
                  <label class="text-[13px] font-medium text-[var(--sys-text-primary)] block">Mốc công ty (JSON nâng cao)</label>
                  <textarea
                    v-model="settings.attendanceCompanyAnchorPointsJson"
                    rows="7"
                    spellcheck="false"
                    class="w-full px-3 py-3 bg-[var(--sys-bg-page)] border border-[var(--sys-border-strong)] rounded-md text-sm text-[var(--sys-text-primary)] focus:border-[var(--sys-brand-solid)] outline-none transition-all font-mono"
                    placeholder='[{"label":"Văn phòng chính","address":"193 Đỗ Văn Thi, Đồng Nai","lat":10.9350102,"lng":106.8293991}]'
                  ></textarea>
                  <p class="text-[12px] text-[var(--sys-text-secondary)]">Bạn vẫn có thể dán JSON trực tiếp. Danh sách mốc bên dưới sẽ cập nhật theo nội dung này.</p>
                </div>

                <div v-if="parsedAnchors.length" class="rounded-lg border border-[var(--sys-border-subtle)] bg-[var(--sys-bg-page)] p-4">
                  <div class="flex items-center justify-between gap-3 mb-3">
                    <p class="text-[12px] font-bold uppercase tracking-wide text-[var(--sys-text-secondary)] m-0">Mốc đang áp dụng</p>
                    <span class="text-[12px] text-[var(--sys-text-secondary)]">{{ parsedAnchors.length }} mốc</span>
                  </div>
                  <div class="space-y-3">
                    <div v-for="(anchor, index) in parsedAnchors" :key="`${anchor.label || 'anchor'}-${index}`" class="rounded-md border border-[var(--sys-border-subtle)] bg-white px-3 py-3">
                      <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-3">
                        <div>
                          <p class="m-0 text-[13px] font-semibold text-[var(--sys-text-primary)]">{{ anchor.label || `Mốc ${index + 1}` }}</p>
                          <p class="m-0 mt-1 text-[12px] text-[var(--sys-text-secondary)]">{{ anchor.address || 'Không có địa chỉ hiển thị' }}</p>
                          <p class="m-0 mt-1 text-[11px] font-medium text-[var(--sys-text-secondary)]">Lat {{ anchor.lat }}, Lng {{ anchor.lng }}</p>
                        </div>
                        <div class="flex flex-wrap gap-2">
                          <button type="button" class="h-9 px-3 rounded-md border border-[var(--sys-border-strong)] bg-white text-[12px] font-semibold text-[var(--sys-text-primary)] hover:border-[var(--sys-brand-solid)] transition-all" @click="editAnchor(index)">
                            Sửa
                          </button>
                          <button type="button" class="h-9 px-3 rounded-md border border-[var(--sys-danger-border)] bg-[var(--sys-danger-soft)] text-[12px] font-semibold text-[var(--sys-danger-text)] hover:brightness-95 transition-all" @click="removeAnchor(index)">
                            Xóa
                          </button>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Holiday Tab -->
          <div v-if="activeTab === 'holiday'" class="space-y-8 animate-fadeIn">
            <div class="bg-transparent text-left">
              <h3 class="text-base font-semibold text-[var(--sys-text-primary)] flex items-center gap-2 mb-6 uppercase tracking-wide">
                <span class="w-1.5 h-6 bg-[var(--sys-warning-solid)] rounded-full"></span>
                Lịch nghỉ & Ngày lễ hệ thống
              </h3>
              <p class="text-sm text-[var(--sys-text-secondary)] mb-6">
                HR/Admin thiết lập ngày nghỉ lễ áp dụng toàn hệ thống để lịch làm việc, bảng công và luồng OT đọc cùng một nguồn dữ liệu.
              </p>

              <div class="space-y-6">
                <div class="flex flex-col lg:flex-row lg:items-end gap-4 rounded-lg border border-[var(--sys-border-subtle)] bg-[var(--sys-bg-page)] p-4">
                  <div class="space-y-1.5">
                    <label class="text-[13px] font-medium text-[var(--sys-text-primary)] block">Năm vận hành</label>
                    <select v-model="holidayYear" class="w-full lg:w-[160px] h-10 px-3 bg-white border border-[var(--sys-border-strong)] rounded-md text-sm text-[var(--sys-text-primary)] focus:border-[var(--sys-brand-solid)] outline-none transition-all">
                      <option v-for="year in holidayYearOptions" :key="year" :value="String(year)">{{ year }}</option>
                    </select>
                  </div>
                  <button
                    type="button"
                    class="h-10 px-4 rounded-md border border-[var(--sys-border-strong)] bg-white text-[13px] font-semibold text-[var(--sys-text-primary)] hover:border-[var(--sys-brand-solid)] transition-all"
                    :disabled="holidayLoading"
                    @click="loadHolidays"
                  >
                    {{ holidayLoading ? 'Đang tải...' : 'Tải danh sách ngày nghỉ' }}
                  </button>
                  <div class="text-[12px] text-[var(--sys-text-secondary)]">
                    {{ holidayRecords.length }} ngày nghỉ đang hiển thị cho năm {{ holidayYear }}
                  </div>
                </div>

                <div class="rounded-lg border border-[var(--sys-border-subtle)] bg-[var(--sys-bg-page)] p-4 space-y-4">
                  <div class="flex items-center justify-between gap-3">
                    <div>
                      <p class="m-0 text-[13px] font-semibold text-[var(--sys-text-primary)]">
                        {{ holidayEditingId ? 'Cập nhật ngày nghỉ' : 'Thêm ngày nghỉ mới' }}
                      </p>
                      <p class="m-0 mt-1 text-[12px] text-[var(--sys-text-secondary)]">
                        Dùng cho nghỉ lễ toàn hệ thống, nghỉ bù hoặc ngày nghỉ riêng do công ty cấu hình.
                      </p>
                    </div>
                    <span v-if="holidayEditingId" class="inline-flex items-center h-8 px-3 rounded-md bg-[var(--sys-warning-soft)] text-[12px] font-semibold text-[var(--sys-warning-text)] border border-[var(--sys-warning-border)]">
                      Đang sửa bản ghi #{{ holidayEditingId }}
                    </span>
                  </div>

                  <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="space-y-1.5">
                      <label class="text-[13px] font-medium text-[var(--sys-text-primary)] block">Tên ngày nghỉ</label>
                      <input v-model="holidayForm.holiday_name" type="text" class="w-full h-10 px-3 bg-white border border-[var(--sys-border-strong)] rounded-md text-sm text-[var(--sys-text-primary)] focus:border-[var(--sys-brand-solid)] outline-none transition-all" placeholder="Ví dụ: Giỗ Tổ Hùng Vương">
                    </div>
                    <div class="space-y-1.5">
                      <label class="text-[13px] font-medium text-[var(--sys-text-primary)] block">Ngày áp dụng</label>
                      <input v-model="holidayForm.holiday_date" type="date" class="w-full h-10 px-3 bg-white border border-[var(--sys-border-strong)] rounded-md text-sm text-[var(--sys-text-primary)] focus:border-[var(--sys-brand-solid)] outline-none transition-all">
                    </div>
                    <div class="space-y-1.5">
                      <label class="text-[13px] font-medium text-[var(--sys-text-primary)] block">Loại ngày nghỉ</label>
                      <select v-model="holidayForm.holiday_type" class="w-full h-10 px-3 bg-white border border-[var(--sys-border-strong)] rounded-md text-sm text-[var(--sys-text-primary)] focus:border-[var(--sys-brand-solid)] outline-none transition-all">
                        <option v-for="option in holidayTypeOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
                      </select>
                    </div>
                    <div class="space-y-1.5">
                      <label class="text-[13px] font-medium text-[var(--sys-text-primary)] block">Hệ số lương</label>
                      <input v-model="holidayForm.salary_multiplier" type="number" min="0" step="0.1" class="w-full h-10 px-3 bg-white border border-[var(--sys-border-strong)] rounded-md text-sm text-[var(--sys-text-primary)] focus:border-[var(--sys-brand-solid)] outline-none transition-all" placeholder="Ví dụ: 3.0">
                    </div>
                    <div class="space-y-1.5">
                      <label class="text-[13px] font-medium text-[var(--sys-text-primary)] block">Phụ cấp cố định</label>
                      <input v-model="holidayForm.allowance_amount" type="number" min="0" step="1000" class="w-full h-10 px-3 bg-white border border-[var(--sys-border-strong)] rounded-md text-sm text-[var(--sys-text-primary)] focus:border-[var(--sys-brand-solid)] outline-none transition-all" placeholder="Ví dụ: 300000">
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                      <div class="flex items-center justify-between h-10 px-3 rounded-md border border-[var(--sys-border-subtle)] bg-white">
                        <span class="text-[13px] font-medium text-[var(--sys-text-primary)]">Lặp hàng năm</span>
                        <label class="relative inline-flex items-center cursor-pointer">
                          <input type="checkbox" v-model="holidayForm.is_recurring" class="sr-only peer">
                          <div class="w-11 h-6 bg-[var(--sys-bg-page)] border border-[var(--sys-border-subtle)] rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[var(--sys-brand-solid)]"></div>
                        </label>
                      </div>
                      <div class="flex items-center justify-between h-10 px-3 rounded-md border border-[var(--sys-border-subtle)] bg-white">
                        <span class="text-[13px] font-medium text-[var(--sys-text-primary)]">Nghỉ hưởng lương</span>
                        <label class="relative inline-flex items-center cursor-pointer">
                          <input type="checkbox" v-model="holidayForm.paid_holiday" class="sr-only peer">
                          <div class="w-11 h-6 bg-[var(--sys-bg-page)] border border-[var(--sys-border-subtle)] rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[var(--sys-brand-solid)]"></div>
                        </label>
                      </div>
                    </div>
                    <div class="md:col-span-2 space-y-1.5">
                      <label class="text-[13px] font-medium text-[var(--sys-text-primary)] block">Mô tả nội bộ</label>
                      <textarea v-model="holidayForm.description" rows="3" class="w-full px-3 py-3 bg-white border border-[var(--sys-border-strong)] rounded-md text-sm text-[var(--sys-text-primary)] focus:border-[var(--sys-brand-solid)] outline-none transition-all" placeholder="Ví dụ: Áp dụng cho toàn bộ khối văn phòng và hiển thị trên lịch công ty."></textarea>
                    </div>
                  </div>

                  <div class="flex flex-wrap gap-2">
                    <button
                      type="button"
                      class="h-10 px-4 rounded-md bg-[var(--sys-brand-solid)] text-white text-[13px] font-semibold hover:brightness-95 transition-all"
                      :disabled="holidaySubmitting"
                      @click="saveHoliday"
                    >
                      {{ holidaySubmitting ? 'Đang lưu...' : holidayEditingId ? 'Cập nhật ngày nghỉ' : 'Thêm ngày nghỉ' }}
                    </button>
                    <button
                      type="button"
                      class="h-10 px-4 rounded-md border border-[var(--sys-border-strong)] bg-white text-[13px] font-semibold text-[var(--sys-text-primary)] hover:border-[var(--sys-brand-solid)] transition-all"
                      @click="resetHolidayForm"
                    >
                      Làm mới form
                    </button>
                  </div>
                </div>

                <div class="rounded-lg border border-[var(--sys-border-subtle)] bg-[var(--sys-bg-page)] p-4">
                  <div class="flex items-center justify-between gap-3 mb-3">
                    <p class="m-0 text-[12px] font-bold uppercase tracking-wide text-[var(--sys-text-secondary)]">Ngày nghỉ đang áp dụng</p>
                    <span class="text-[12px] text-[var(--sys-text-secondary)]">Dựa trên dữ liệu backend thật</span>
                  </div>

                  <div v-if="holidayLoading" class="rounded-md border border-[var(--sys-border-subtle)] bg-white px-4 py-6 text-[13px] text-[var(--sys-text-secondary)] text-center">
                    Đang tải danh sách ngày nghỉ...
                  </div>

                  <div v-else-if="!holidayRecords.length" class="rounded-md border border-[var(--sys-border-subtle)] bg-white px-4 py-6 text-[13px] text-[var(--sys-text-secondary)] text-center">
                    Chưa có ngày nghỉ nào cho năm {{ holidayYear }}.
                  </div>

                  <div v-else class="space-y-3">
                    <div v-for="item in holidayRecords" :key="item.holiday_id" class="rounded-md border border-[var(--sys-border-subtle)] bg-white px-4 py-3">
                      <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-3">
                        <div>
                          <div class="flex flex-wrap items-center gap-2">
                            <p class="m-0 text-[13px] font-semibold text-[var(--sys-text-primary)]">{{ item.holiday_name }}</p>
                            <span class="inline-flex items-center h-7 px-2.5 rounded-md bg-[var(--sys-brand-soft)] text-[11px] font-semibold text-[var(--sys-brand-solid)] border border-[var(--sys-brand-border)]">
                              {{ formatHolidayTypeLabel(item.holiday_type) }}
                            </span>
                            <span v-if="Number(item.is_recurring) === 1" class="inline-flex items-center h-7 px-2.5 rounded-md bg-[var(--sys-warning-soft)] text-[11px] font-semibold text-[var(--sys-warning-text)] border border-[var(--sys-warning-border)]">
                              Lặp hàng năm
                            </span>
                            <span v-if="Number(item.paid_holiday) === 1" class="inline-flex items-center h-7 px-2.5 rounded-md bg-[var(--sys-success-soft)] text-[11px] font-semibold text-[var(--sys-success-text)] border border-[var(--sys-success-border)]">
                              Hưởng lương
                            </span>
                          </div>
                          <p class="m-0 mt-1 text-[12px] text-[var(--sys-text-secondary)]">
                            {{ formatHolidayDate(item.holiday_date) }}
                            <span class="mx-1">•</span>
                            Hệ số {{ formatHolidayMultiplier(item.salary_multiplier) }}
                            <span class="mx-1">•</span>
                            Phụ cấp {{ formatMoney(item.allowance_amount) }}
                          </p>
                          <p v-if="item.description" class="m-0 mt-2 text-[12px] text-[var(--sys-text-secondary)]">
                            {{ item.description }}
                          </p>
                        </div>
                        <div class="flex flex-wrap gap-2">
                          <button type="button" class="h-9 px-3 rounded-md border border-[var(--sys-border-strong)] bg-white text-[12px] font-semibold text-[var(--sys-text-primary)] hover:border-[var(--sys-brand-solid)] transition-all" @click="editHoliday(item)">
                            Sửa
                          </button>
                          <button type="button" class="h-9 px-3 rounded-md border border-[var(--sys-danger-border)] bg-[var(--sys-danger-soft)] text-[12px] font-semibold text-[var(--sys-danger-text)] hover:brightness-95 transition-all" :disabled="holidaySubmitting" @click="removeHoliday(item)">
                            Xóa
                          </button>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Notification Tab -->
          <div v-if="activeTab === 'notifications'" class="space-y-6 animate-fadeIn">
            <h3 class="text-base font-semibold text-[var(--sys-text-primary)] mb-6 text-left uppercase tracking-wide">Tùy chọn Kênh thông tin</h3>
            
            <div class="space-y-4">
              <div v-for="notify in notificationOptions" :key="notify.id" class="flex items-center justify-between p-4 rounded-lg border border-[var(--sys-border-subtle)] bg-[var(--sys-bg-page)]/50 hover:bg-white transition-all group">
                <div class="flex items-center gap-4 bg-transparent text-left">
                  <div :class="['w-10 h-10 rounded-md flex items-center justify-center border shadow-sm', notify.bg, notify.color]">
                    <span class="material-symbols-outlined text-[20px]">{{ notify.icon }}</span>
                  </div>
                  <div class="bg-transparent text-left">
                    <p class="text-[14px] font-semibold text-[var(--sys-text-primary)] mb-0.5">{{ notify.label }}</p>
                    <p class="text-[12px] text-[var(--sys-text-secondary)]">{{ notify.desc }}</p>
                  </div>
                </div>
                <label class="relative inline-flex items-center cursor-pointer">
                  <input type="checkbox" v-model="notify.enabled" class="sr-only peer">
                  <div class="w-11 h-6 bg-[var(--sys-bg-page)] border border-[var(--sys-border-subtle)] rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border border-gray-300 after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[var(--sys-brand-solid)]"></div>
                </label>
              </div>
            </div>
          </div>

          <!-- Security Tab -->
          <div v-if="activeTab === 'security'" class="space-y-8 animate-fadeIn">
            <div class="p-6 bg-[var(--sys-warning-soft)] rounded-lg border border-[var(--sys-warning-border)] flex items-start gap-4 shadow-sm">
              <div class="w-10 h-10 bg-[var(--sys-warning-solid)] text-white rounded-md flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-[24px]">verified_user</span>
              </div>
              <div class="bg-transparent text-left">
                <h4 class="text-[14px] font-bold text-[var(--sys-warning-text)] mb-1">Giao thức Bảo mật Phê chuẩn</h4>
                <p class="text-[12px] text-[var(--sys-warning-text)] leading-relaxed font-medium">Thiết lập các quy trình xác thực, kiến trúc mã hóa và định danh an toàn cho toàn bộ hệ thống dữ liệu nhân sự.</p>
              </div>
            </div>

            <div class="space-y-6">
              <div class="flex items-center justify-between p-4 bg-[var(--sys-bg-page)] border border-[var(--sys-border-subtle)] rounded-lg hover:bg-white transition-all shadow-sm">
                <div class="bg-transparent text-left">
                  <p class="text-[14px] font-semibold text-[var(--sys-text-primary)] mb-1">Xác thực 2 yếu tố (2FA)</p>
                  <p class="text-[12px] text-[var(--sys-text-secondary)]">Yêu cầu bắt buộc đối với cấp độ quản trị viên</p>
                </div>
                <label class="relative inline-flex items-center cursor-pointer">
                  <input type="checkbox" v-model="settings.require2FA" class="sr-only peer">
                  <div class="w-11 h-6 bg-[var(--sys-bg-page)] border border-[var(--sys-border-subtle)] rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[var(--sys-brand-solid)]"></div>
                </label>
              </div>

              <div class="space-y-1.5 bg-transparent text-left">
                <label class="text-[13px] font-medium text-[var(--sys-text-primary)] block ml-1">Thời gian tự động khóa phiên (Session Timeout)</label>
                <select v-model="settings.sessionTimeout" class="w-full h-10 px-3 bg-[var(--sys-bg-page)] border border-[var(--sys-border-subtle)] rounded-md text-[13px] font-semibold text-[var(--sys-text-primary)] focus:border-[var(--sys-brand-solid)] outline-none">
                  <option value="30">Ngưng hoạt động sau 30 phút (Đề xuất)</option>
                  <option value="60">Ngưng hoạt động sau 60 phút</option>
                  <option value="120">Ngưng hoạt động sau 120 phút</option>
                  <option value="0">Duy trì kết nối vĩnh viễn</option>
                </select>
              </div>
            </div>
          </div>

          <!-- Backup Tab -->
          <div v-if="activeTab === 'backup'" class="space-y-8 animate-fadeIn py-4">
            <div class="flex flex-col items-center justify-center text-center space-y-4">
              <div class="w-16 h-16 bg-[var(--sys-brand-soft)] text-[var(--sys-brand-solid)] rounded-lg flex items-center justify-center shadow-sm border border-[var(--sys-brand-border)]">
                <span class="material-symbols-outlined text-[32px]">cloud_sync</span>
              </div>
              <div class="bg-transparent text-center max-w-lg space-y-2">
                <h3 class="text-xl font-bold text-[var(--sys-text-primary)]">Lưu ký & Phục hồi dữ liệu</h3>
                <p class="text-sm text-[var(--sys-text-secondary)]">Đảm bảo an toàn tuyệt đối cho cơ sở dữ liệu nhân sự bằng hệ thống sao lưu định kỳ.</p>
              </div>
            </div>

            <div class="flex flex-col sm:flex-row items-center justify-center gap-3 bg-transparent">
              <button class="h-10 px-8 bg-[var(--sys-brand-solid)] text-white rounded-md font-bold text-sm hover:brightness-90 shadow-sm transition-all flex items-center gap-2 uppercase tracking-wide">
                <span class="material-symbols-outlined text-[20px]">backup</span>
                Tạo Snapshot
              </button>
              <button class="h-10 px-8 bg-white border border-[var(--sys-border-strong)] text-[var(--sys-text-primary)] rounded-md font-bold text-sm hover:border-[var(--sys-brand-solid)] transition-all flex items-center gap-2 uppercase tracking-wide shadow-sm">
                <span class="material-symbols-outlined text-[20px]">history</span>
                Chọn điểm khôi phục
              </button>
            </div>

            <div class="flex flex-col items-center gap-2 bg-transparent pt-4">
              <div class="inline-flex items-center gap-2 px-3 py-1.5 bg-[var(--sys-bg-page)] rounded-md border border-[var(--sys-border-subtle)] shadow-inner">
                <span class="w-2 h-2 rounded-full bg-[var(--sys-success-solid)] animate-pulse"></span>
                <p class="text-[12px] text-[var(--sys-text-secondary)] font-medium">Lần lưu ký cuối: 14/03/2026 • 02:30 AM</p>
              </div>
            </div>
          </div>

        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
/**
 * TRANG CẤU HÌNH HỆ THỐNG - PHIÊN BẢN ENTERPRISE SaaS
 * Tuân thủ 7 Golden Rules:
 * - Font Inter 14px (text-sm), Tỉ lệ Sidebar/Form cao (text-13px)
 * - Bo góc chuẩn B2B: 6px (MD) cho Input/Button, 8px (LG) cho Card/Thẻ
 * - Hệ màu Blue/White đồng nhất cho Action Icons
 */
import { computed, onMounted, ref, watch } from 'vue';
import { apiRequest } from '@/services/beApi.js';
import { createHoliday, deleteHoliday, fetchHolidays, updateHoliday } from '@/services/workforceApi.js';
import { useConfirm } from '@/composables/useConfirm';

const { showAlert, showConfirm } = useConfirm();

const activeTab = ref('general');

const tabs = [
  { id: 'general', label: 'Cấu hình tổng quan', icon: 'settings' },
  { id: 'gps', label: 'GPS & Geofence', icon: 'location_on' },
  { id: 'holiday', label: 'Ngày nghỉ hệ thống', icon: 'event_available' },
  { id: 'notifications', label: 'Luồng thông tin', icon: 'notifications_active' },
  { id: 'security', label: 'Tường lửa & Bảo mật', icon: 'security' },
  { id: 'backup', label: 'Lưu ký dữ liệu', icon: 'cloud_sync' },
];

const settings = ref({
  companyName: 'HRM Portal',
  taxCode: '',
  address: '',
  language: 'vi',
  timezone: 'Asia/Ho_Chi_Minh',
  require2FA: true,
  sessionTimeout: '60',
  attendanceCompanyGeoLockEnabled: true,
  attendanceCompanyAnchorPointsJson: '',
  attendanceGreenRadiusM: '120',
  attendanceYellowRadiusM: '250',
});

const gpsLookupQuery = ref('');
const gpsLookupBusy = ref(false);
const gpsLocateBusy = ref(false);
const gpsEditingIndex = ref(-1);
const gpsEditorMessage = ref('');
const gpsEditorMessageType = ref('info');
const gpsDraft = ref({
  label: '',
  address: '',
  lat: '',
  lng: '',
});

const notificationOptions = ref([
  { id: 1, label: 'Email thông báo', desc: 'Phê duyệt lương, thông báo nội bộ qua hệ thống Mailing', icon: 'mail', bg: 'bg-[var(--sys-brand-soft)]', color: 'text-[var(--sys-brand-solid)]', enabled: true },
  { id: 2, label: 'Thông báo Real-time', desc: 'Xử lý đơn từ và nhắc lịch làm việc tức thời qua Web Push', icon: 'bolt', bg: 'bg-[var(--sys-success-soft)]', color: 'text-[var(--sys-success-text)]', enabled: true },
  { id: 3, label: 'SMS Gateway', desc: 'Xác thực OTP giao dịch và cảnh báo xâm nhập an ninh', icon: 'sms', bg: 'bg-[var(--sys-warning-soft)]', color: 'text-[var(--sys-warning-text)]', enabled: false },
]);

const holidayYear = ref(String(new Date().getFullYear()));
const holidayLoading = ref(false);
const holidaySubmitting = ref(false);
const holidayEditingId = ref(null);
const holidayRecords = ref([]);
const holidayForm = ref({
  holiday_name: '',
  holiday_date: '',
  holiday_type: 'OTHER',
  is_recurring: false,
  paid_holiday: true,
  salary_multiplier: '1',
  allowance_amount: '0',
  description: '',
});

const holidayYearOptions = computed(() => {
  const currentYear = new Date().getFullYear();
  return Array.from({ length: 7 }, (_, index) => currentYear - 1 + index);
});

const holidayTypeOptions = [
  { value: 'NEW_YEAR', label: 'Tết Dương lịch' },
  { value: 'LUNAR_NEW_YEAR', label: 'Tết Âm lịch' },
  { value: 'HUNG_KINGS', label: 'Giỗ Tổ Hùng Vương' },
  { value: 'LIBERATION_DAY', label: 'Giải phóng miền Nam' },
  { value: 'LABOR_DAY', label: 'Quốc tế Lao động' },
  { value: 'NATIONAL_DAY', label: 'Quốc khánh' },
  { value: 'OTHER', label: 'Ngày nghỉ khác' },
];

const parsedAnchors = computed(() => {
  try {
    const parsed = JSON.parse(settings.value.attendanceCompanyAnchorPointsJson || '[]');
    return Array.isArray(parsed)
      ? parsed
          .map((item) => normalizeAnchor(item))
          .filter((item) => item !== null)
      : [];
  } catch {
    return [];
  }
});

const gpsEditorMessageClass = computed(() => {
  if (gpsEditorMessageType.value === 'danger') {
    return 'bg-[var(--sys-danger-soft)] border-[var(--sys-danger-border)] text-[var(--sys-danger-text)]';
  }
  if (gpsEditorMessageType.value === 'success') {
    return 'bg-[var(--sys-success-soft)] border-[var(--sys-success-border)] text-[var(--sys-success-text)]';
  }
  return 'bg-[var(--sys-info-soft)] border-[var(--sys-info-border)] text-[var(--sys-info-text)]';
});

const activeMapAnchor = computed(() => {
  const lat = Number(gpsDraft.value.lat);
  const lng = Number(gpsDraft.value.lng);
  if (Number.isFinite(lat) && Number.isFinite(lng)) {
    return {
      label: gpsDraft.value.label || 'Mốc đang chỉnh sửa',
      address: gpsDraft.value.address || '',
      lat,
      lng,
    };
  }
  return parsedAnchors.value[0] || null;
});

const mapPreviewSrc = computed(() => {
  const anchor = activeMapAnchor.value;
  if (!anchor) return '';
  const lat = Number(anchor.lat);
  const lng = Number(anchor.lng);
  if (!Number.isFinite(lat) || !Number.isFinite(lng)) return '';
  const spanLat = 0.008;
  const spanLng = 0.01;
  const bbox = [
    (lng - spanLng).toFixed(6),
    (lat - spanLat).toFixed(6),
    (lng + spanLng).toFixed(6),
    (lat + spanLat).toFixed(6),
  ].join('%2C');
  return `https://www.openstreetmap.org/export/embed.html?bbox=${bbox}&layer=mapnik&marker=${lat.toFixed(6)}%2C${lng.toFixed(6)}`;
});

const holidayDateRange = computed(() => ({
  fromDate: `${holidayYear.value}-01-01`,
  toDate: `${holidayYear.value}-12-31`,
}));

const resetHolidayForm = () => {
  holidayEditingId.value = null;
  holidayForm.value = {
    holiday_name: '',
    holiday_date: '',
    holiday_type: 'OTHER',
    is_recurring: false,
    paid_holiday: true,
    salary_multiplier: '1',
    allowance_amount: '0',
    description: '',
  };
};

const normalizeHolidayPayload = () => ({
  holiday_name: String(holidayForm.value.holiday_name || '').trim(),
  holiday_date: String(holidayForm.value.holiday_date || '').trim(),
  holiday_type: String(holidayForm.value.holiday_type || '').trim() || 'OTHER',
  is_recurring: Boolean(holidayForm.value.is_recurring),
  paid_holiday: Boolean(holidayForm.value.paid_holiday),
  salary_multiplier: Number(holidayForm.value.salary_multiplier || 1),
  allowance_amount: Number(holidayForm.value.allowance_amount || 0),
  description: String(holidayForm.value.description || '').trim(),
});

const formatHolidayDate = (value) => {
  const date = new Date(`${value}T00:00:00`);
  if (Number.isNaN(date.getTime())) return value || 'Chưa có ngày';
  return date.toLocaleDateString('vi-VN', {
    weekday: 'long',
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
  });
};

const formatHolidayMultiplier = (value) => {
  const numeric = Number(value || 1);
  return Number.isFinite(numeric) ? numeric.toFixed(numeric % 1 === 0 ? 0 : 1) : '1';
};

const holidayTypeLabelMap = {
  NEW_YEAR: 'Tết Dương lịch',
  LUNAR_NEW_YEAR: 'Tết Âm lịch',
  HUNG_KINGS: 'Giỗ Tổ Hùng Vương',
  LIBERATION_DAY: 'Giải phóng miền Nam',
  LABOR_DAY: 'Quốc tế Lao động',
  NATIONAL_DAY: 'Quốc khánh',
  OTHER: 'Ngày nghỉ khác',
};

const formatHolidayTypeLabel = (value) => holidayTypeLabelMap[String(value || '').toUpperCase()] || 'Ngày nghỉ khác';

const formatMoney = (value) => {
  const numeric = Number(value || 0);
  if (!Number.isFinite(numeric)) return '0 đ';
  return `${numeric.toLocaleString('vi-VN')} đ`;
};

const loadHolidays = async () => {
  holidayLoading.value = true;
  try {
    const items = await fetchHolidays({
      ...holidayDateRange.value,
      perPage: 400,
    });
    holidayRecords.value = Array.isArray(items)
      ? [...items].sort((left, right) => String(left.holiday_date || '').localeCompare(String(right.holiday_date || '')))
      : [];
  } catch {
    holidayRecords.value = [];
    await showAlert('Không tải được ngày nghỉ', 'Backend chưa trả được danh sách holiday. Hãy thử lại sau hoặc kiểm tra quyền truy cập của tài khoản hiện tại.');
  } finally {
    holidayLoading.value = false;
  }
};

const editHoliday = (item) => {
  holidayEditingId.value = Number(item?.holiday_id || 0) || null;
  holidayForm.value = {
    holiday_name: String(item?.holiday_name || ''),
    holiday_date: String(item?.holiday_date || ''),
    holiday_type: String(item?.holiday_type || 'OTHER'),
    is_recurring: Number(item?.is_recurring || 0) === 1,
    paid_holiday: Number(item?.paid_holiday ?? 1) === 1,
    salary_multiplier: String(item?.salary_multiplier ?? '1'),
    allowance_amount: String(item?.allowance_amount ?? '0'),
    description: String(item?.description || ''),
  };
};

const saveHoliday = async () => {
  const payload = normalizeHolidayPayload();
  if (!payload.holiday_name || !payload.holiday_date) {
    await showAlert('Thiếu dữ liệu bắt buộc', 'Hãy nhập tên ngày nghỉ và ngày áp dụng trước khi lưu.');
    return;
  }
  if (!Number.isFinite(payload.salary_multiplier) || payload.salary_multiplier < 0) {
    await showAlert('Hệ số chưa hợp lệ', 'Hệ số lương ngày nghỉ phải là số hợp lệ lớn hơn hoặc bằng 0.');
    return;
  }
  if (!Number.isFinite(payload.allowance_amount) || payload.allowance_amount < 0) {
    await showAlert('Phụ cấp chưa hợp lệ', 'Phụ cấp ngày nghỉ phải là số hợp lệ lớn hơn hoặc bằng 0.');
    return;
  }

  holidaySubmitting.value = true;
  try {
    if (holidayEditingId.value) {
      await updateHoliday(holidayEditingId.value, payload);
      await showAlert('Đã cập nhật', 'Ngày nghỉ hệ thống đã được cập nhật thành công.');
    } else {
      await createHoliday(payload);
      await showAlert('Đã thêm ngày nghỉ', 'Ngày nghỉ mới đã được lưu và sẵn sàng hiển thị trên lịch làm việc.');
    }
    resetHolidayForm();
    await loadHolidays();
  } catch (error) {
    await showAlert('Không lưu được ngày nghỉ', String(error?.message || 'Backend từ chối lưu ngày nghỉ. Hãy kiểm tra lại dữ liệu rồi thử lại.'));
  } finally {
    holidaySubmitting.value = false;
  }
};

const removeHoliday = async (item) => {
  const ok = await showConfirm('Xác nhận xóa ngày nghỉ', `Bạn có chắc chắn muốn xóa "${item?.holiday_name || 'ngày nghỉ này'}" khỏi lịch hệ thống không?`);
  if (!ok) return;

  holidaySubmitting.value = true;
  try {
    await deleteHoliday(item?.holiday_id);
    if (Number(item?.holiday_id || 0) === holidayEditingId.value) {
      resetHolidayForm();
    }
    await loadHolidays();
    await showAlert('Đã xóa ngày nghỉ', 'Bản ghi ngày nghỉ đã được gỡ khỏi lịch hệ thống.');
  } catch (error) {
    await showAlert('Không xóa được ngày nghỉ', String(error?.message || 'Backend chưa cho phép xóa bản ghi này.'));
  } finally {
    holidaySubmitting.value = false;
  }
};

const setGpsMessage = (message, type = 'info') => {
  gpsEditorMessage.value = String(message || '').trim();
  gpsEditorMessageType.value = type;
};

const resetGpsDraft = () => {
  gpsEditingIndex.value = -1;
  gpsDraft.value = {
    label: '',
    address: '',
    lat: '',
    lng: '',
  };
};

const normalizeAnchor = (anchor) => {
  const lat = Number(anchor?.lat);
  const lng = Number(anchor?.lng);
  if (!Number.isFinite(lat) || !Number.isFinite(lng)) return null;
  return {
    label: String(anchor?.label || '').trim() || 'Mốc công ty',
    address: String(anchor?.address || '').trim(),
    lat: Number(lat.toFixed(6)),
    lng: Number(lng.toFixed(6)),
  };
};

const writeAnchors = (anchors) => {
  const normalized = anchors
    .map((item) => normalizeAnchor(item))
    .filter((item) => item !== null);
  settings.value.attendanceCompanyAnchorPointsJson = JSON.stringify(normalized, null, 2);
};

const editAnchor = (index) => {
  const anchor = parsedAnchors.value[index];
  if (!anchor) return;
  gpsEditingIndex.value = index;
  gpsDraft.value = {
    label: anchor.label || '',
    address: anchor.address || '',
    lat: String(anchor.lat),
    lng: String(anchor.lng),
  };
  setGpsMessage(`Đang chỉnh sửa ${anchor.label || `mốc ${index + 1}`}.`, 'info');
};

const removeAnchor = (index) => {
  const anchors = [...parsedAnchors.value];
  if (!anchors[index]) return;
  anchors.splice(index, 1);
  writeAnchors(anchors);
  if (gpsEditingIndex.value === index) {
    resetGpsDraft();
  }
  setGpsMessage('Đã xóa mốc geofence cũ khỏi danh sách.', 'success');
};

const clearAllAnchors = () => {
  writeAnchors([]);
  resetGpsDraft();
  setGpsMessage('Đã xóa toàn bộ mốc geofence cũ. Hãy thêm mốc mới rồi lưu cấu hình.', 'success');
};

const upsertAnchorDraft = () => {
  const normalized = normalizeAnchor({
    ...gpsDraft.value,
    lat: Number(gpsDraft.value.lat),
    lng: Number(gpsDraft.value.lng),
  });
  if (!normalized) {
    setGpsMessage('Lat hoặc lng chưa hợp lệ. Hãy nhập đúng tọa độ rồi thử lại.', 'danger');
    return;
  }

  const anchors = [...parsedAnchors.value];
  if (gpsEditingIndex.value >= 0 && anchors[gpsEditingIndex.value]) {
    anchors[gpsEditingIndex.value] = normalized;
    writeAnchors(anchors);
    setGpsMessage(`Đã cập nhật ${normalized.label}.`, 'success');
  } else {
    anchors.push(normalized);
    writeAnchors(anchors);
    setGpsMessage(`Đã thêm ${normalized.label} vào geofence công ty.`, 'success');
  }

  gpsLookupQuery.value = normalized.address || `${normalized.lat}, ${normalized.lng}`;
  resetGpsDraft();
};

const reverseGeocode = async (lat, lng) => {
  const url = new URL('https://nominatim.openstreetmap.org/reverse');
  url.searchParams.set('format', 'jsonv2');
  url.searchParams.set('lat', String(lat));
  url.searchParams.set('lon', String(lng));
  const response = await fetch(url.toString(), {
    headers: {
      Accept: 'application/json',
    },
  });
  if (!response.ok) {
    throw new Error('Không thể đọc địa chỉ từ tọa độ hiện tại.');
  }
  const payload = await response.json();
  return String(payload?.display_name || '').trim();
};

const readCurrentPosition = () => new Promise((resolve, reject) => {
  navigator.geolocation.getCurrentPosition(
    (position) => resolve(position),
    (error) => reject(error),
    {
      enableHighAccuracy: true,
      timeout: 12000,
      maximumAge: 0,
    }
  );
});

const mapGeoError = (error) => {
  const code = Number(error?.code || 0);
  if (code === 1) return 'Bạn đã từ chối quyền vị trí. Hãy cho phép GPS rồi thử lại.';
  if (code === 2) return 'Không xác định được vị trí hiện tại. Hãy bật GPS/Wi-Fi rồi thử lại.';
  if (code === 3) return 'Hết thời gian chờ GPS. Hãy thử lại ở nơi tín hiệu tốt hơn.';
  return 'Không lấy được GPS hiện tại. Hãy kiểm tra quyền vị trí rồi thử lại.';
};

const tryParseDecimalPair = (query) => {
  const match = String(query || '').match(/(-?\d+(?:\.\d+)?)\s*[, ]\s*(-?\d+(?:\.\d+)?)/);
  if (!match) return null;
  const lat = Number(match[1]);
  const lng = Number(match[2]);
  if (!Number.isFinite(lat) || !Number.isFinite(lng)) return null;
  return { lat, lng };
};

const lookupGpsLocation = async () => {
  const query = String(gpsLookupQuery.value || '').trim();
  if (!query) {
    setGpsMessage('Hãy nhập địa chỉ hoặc tọa độ trước khi tìm vị trí.', 'danger');
    return;
  }

  const decimalPair = tryParseDecimalPair(query);
  if (decimalPair) {
    gpsDraft.value.lat = String(decimalPair.lat);
    gpsDraft.value.lng = String(decimalPair.lng);
    if (!gpsDraft.value.address) {
      gpsDraft.value.address = query;
    }
    if (!gpsDraft.value.label) {
      gpsDraft.value.label = `Mốc công ty ${parsedAnchors.value.length + 1}`;
    }
    setGpsMessage('Đã nhận tọa độ từ dữ liệu bạn nhập.', 'success');
    return;
  }

  gpsLookupBusy.value = true;
  try {
    const url = new URL('https://nominatim.openstreetmap.org/search');
    url.searchParams.set('format', 'jsonv2');
    url.searchParams.set('limit', '1');
    url.searchParams.set('q', query);
    const response = await fetch(url.toString(), {
      headers: {
        Accept: 'application/json',
      },
    });
    if (!response.ok) {
      throw new Error('Không tìm được vị trí từ địa chỉ vừa nhập.');
    }
    const payload = await response.json();
    const first = Array.isArray(payload) ? payload[0] : null;
    if (!first) {
      throw new Error('Không có kết quả vị trí phù hợp.');
    }
    gpsDraft.value.lat = String(Number(first.lat).toFixed(6));
    gpsDraft.value.lng = String(Number(first.lon).toFixed(6));
    gpsDraft.value.address = String(first.display_name || query);
    if (!gpsDraft.value.label) {
      gpsDraft.value.label = `Mốc công ty ${parsedAnchors.value.length + 1}`;
    }
    setGpsMessage('Đã tìm thấy vị trí trên bản đồ. Bạn có thể chỉnh lại rồi bấm Thêm mốc.', 'success');
  } catch (error) {
    setGpsMessage(String(error?.message || 'Không thể tìm vị trí.'), 'danger');
  } finally {
    gpsLookupBusy.value = false;
  }
};

const useCurrentGpsLocation = async () => {
  if (typeof window !== 'undefined' && !window.isSecureContext) {
    setGpsMessage('Trình duyệt chỉ cho phép lấy GPS thật trên kết nối HTTPS an toàn.', 'danger');
    return;
  }

  if (!navigator.geolocation) {
    setGpsMessage('Trình duyệt này chưa hỗ trợ lấy GPS hiện tại.', 'danger');
    return;
  }

  gpsLocateBusy.value = true;
  try {
    let position = await readCurrentPosition();
    let accuracy = Number(position?.coords?.accuracy || 9999);

    // Nếu sai số quá lớn, lấy thêm 1 mẫu để ổn định hơn.
    if (accuracy > 150) {
      try {
        const retryPosition = await readCurrentPosition();
        const retryAccuracy = Number(retryPosition?.coords?.accuracy || 9999);
        if (retryAccuracy < accuracy) {
          position = retryPosition;
          accuracy = retryAccuracy;
        }
      } catch {
        // Không chặn flow nếu lần lấy thứ 2 lỗi.
      }
    }

    const lat = Number(position.coords.latitude.toFixed(6));
    const lng = Number(position.coords.longitude.toFixed(6));
    const capturedAt = new Date(position.timestamp || Date.now());

    gpsDraft.value.lat = String(lat);
    gpsDraft.value.lng = String(lng);

    try {
      gpsDraft.value.address = await reverseGeocode(lat, lng);
    } catch {
      gpsDraft.value.address = `${lat}, ${lng}`;
    }

    if (!gpsDraft.value.label) {
      gpsDraft.value.label = `Mốc công ty ${parsedAnchors.value.length + 1}`;
    }
    gpsLookupQuery.value = gpsDraft.value.address || `${lat}, ${lng}`;

    const accuracyText = Number.isFinite(accuracy) ? `${Math.round(accuracy)}m` : 'không rõ';
    const timeText = capturedAt.toLocaleTimeString('vi-VN');
    const prefix = accuracy <= 120 ? 'Đã lấy GPS thật của thiết bị' : 'Đã lấy GPS, nhưng sai số còn cao';
    setGpsMessage(`${prefix}: ${lat}, ${lng} · sai số ~${accuracyText} · lúc ${timeText}.`, accuracy <= 120 ? 'success' : 'info');
  } catch (error) {
    setGpsMessage(mapGeoError(error), 'danger');
  } finally {
    gpsLocateBusy.value = false;
  }
};

const loadSettings = async () => {
  try {
    const payload = await apiRequest('/settings/general', { noGetCache: true });
    const data = payload?.data || {};
    settings.value = {
      companyName: String(data.company_name || 'HRM Portal'),
      taxCode: String(data.company_tax_code || ''),
      address: String(data.company_address || ''),
      language: String(data.system_language || 'vi'),
      timezone: String(data.system_timezone || 'Asia/Ho_Chi_Minh'),
      require2FA: Boolean(data.security_require_2fa),
      sessionTimeout: String(data.security_session_timeout || '60'),
      attendanceCompanyGeoLockEnabled: Boolean(data.attendance_company_geo_lock_enabled ?? true),
      attendanceCompanyAnchorPointsJson: String(data.attendance_company_anchor_points_json || ''),
      attendanceGreenRadiusM: String(data.attendance_green_radius_m || '120'),
      attendanceYellowRadiusM: String(data.attendance_yellow_radius_m || '250'),
    };
  } catch {
    await showAlert('Không tải được cấu hình', 'Hệ thống chưa đọc được cấu hình thật từ backend. Bạn vẫn có thể chỉnh sửa rồi lưu lại lần nữa.');
  }
};

const saveAllSettings = async () => {
  try {
    JSON.parse(settings.value.attendanceCompanyAnchorPointsJson || '[]');
  } catch {
    await showAlert('JSON chưa hợp lệ', 'Danh sách mốc công ty đang sai định dạng JSON. Hãy kiểm tra lại trước khi lưu.');
    activeTab.value = 'gps';
    return;
  }

  await apiRequest('/settings/general', {
    method: 'PUT',
    body: {
      company_name: settings.value.companyName,
      company_tax_code: settings.value.taxCode,
      company_address: settings.value.address,
      system_language: settings.value.language,
      system_timezone: settings.value.timezone,
      security_require_2fa: settings.value.require2FA,
      security_session_timeout: settings.value.sessionTimeout,
      attendance_company_geo_lock_enabled: settings.value.attendanceCompanyGeoLockEnabled,
      attendance_company_anchor_points_json: settings.value.attendanceCompanyAnchorPointsJson,
      attendance_green_radius_m: settings.value.attendanceGreenRadiusM,
      attendance_yellow_radius_m: settings.value.attendanceYellowRadiusM,
    },
  });

  await showAlert('Phê chuẩn Cấu hình', 'Cấu hình hệ thống và geofence GPS đã được lưu thành công.');
};

onMounted(() => {
  void loadSettings();
  void loadHolidays();
});

watch(holidayYear, () => {
  if (activeTab.value === 'holiday') {
    void loadHolidays();
  }
});
</script>

<style scoped>
@keyframes fadeIn {
  from { opacity: 0; transform: translateY(10px); }
  to { opacity: 1; transform: translateY(0); }
}
.animate-fadeIn {
  animation: fadeIn 0.4s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}

.no-scrollbar::-webkit-scrollbar {
  display: none;
}
</style>
