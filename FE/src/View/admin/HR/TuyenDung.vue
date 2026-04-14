<template>
  <div class="space-y-6 pb-8">
    <!-- Header Area -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 bg-transparent text-left">
      <div class="bg-transparent text-left">
        <h1 class="text-xl font-semibold text-[var(--sys-text-primary)] mb-1">Quản lý Tuyển dụng & Thu hút Nhân tài</h1>
        <p class="text-sm text-[var(--sys-text-secondary)]">Sàng lọc hồ sơ, điều phối phỏng vấn và đánh giá năng lực ứng viên toàn diện.</p>
      </div>
      <div class="bg-transparent hidden xl:block shrink-0">
      </div>
    </div>

    <!-- Main Content Split -->
    <div class="grid grid-cols-1 xl:grid-cols-12 gap-6 items-stretch">
      <!-- Left Column: Pipeline & List -->
      <div class="xl:col-span-7 space-y-4 flex flex-col">
        <!-- Toolbar Filters -->
        <div class="bg-[var(--sys-bg-surface)] px-4 py-3 rounded-lg border border-[var(--sys-border-subtle)] shadow-sm">
          <div class="flex flex-row items-center gap-3 bg-transparent">
            <!-- Search Bar - Optimized for visibility -->
            <div class="flex-1 relative group bg-transparent">
              <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-[18px] text-[var(--sys-brand-solid)]">search</span>
              <input 
                v-model="searchQuery"
                type="text" 
                placeholder="Tìm tên ứng viên, vị trí..." 
                class="w-full h-10 pl-10 pr-4 bg-white border border-[var(--sys-border-strong)] rounded-md text-[13px] font-medium text-[var(--sys-text-primary)] outline-none focus:border-[var(--sys-brand-solid)] shadow-sm transition-all placeholder:text-[var(--sys-text-disabled)]"
              >
            </div>
            
            <!-- Compact Dropdowns (Ultra Compact) -->
            <div class="flex items-center gap-2 shrink-0 bg-transparent">
              <Dropdown v-model="filterPosition" :options="positionOptions" class="min-w-[125px] max-w-[180px] h-10" />
            </div>
          </div>
        </div>

        <!-- Candidate Data Table -->
        <div class="bg-[var(--sys-bg-surface)] rounded-lg border border-[var(--sys-border-subtle)] shadow-sm overflow-hidden flex-grow flex flex-col">
          <div class="overflow-x-auto custom-scrollbar flex-grow">
            <table class="w-full text-left border-collapse">
              <thead class="bg-[var(--sys-bg-page)]">
                <tr>
                  <th class="px-4 py-3 text-[12px] font-semibold text-[var(--sys-text-secondary)] border-b border-[var(--sys-border-subtle)] uppercase tracking-wider">Hồ sơ ứng viên</th>
                  <th class="px-4 py-3 text-[12px] font-semibold text-[var(--sys-text-secondary)] border-b border-[var(--sys-border-subtle)] uppercase tracking-wider">Vị trí</th>
                  <th class="px-4 py-3 text-[12px] font-semibold text-[var(--sys-text-secondary)] border-b border-[var(--sys-border-subtle)] uppercase tracking-wider">AI</th>
                  <th class="px-4 py-3 text-[12px] font-semibold text-[var(--sys-text-secondary)] border-b border-[var(--sys-border-subtle)] uppercase tracking-wider text-right">Ngày nộp</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-[var(--sys-border-subtle)]">
                <tr v-for="candidate in paginatedCandidates" :key="candidate.id" 
                  @click="selectCandidate(candidate.id)"
                  class="group cursor-pointer transition-colors"
                  :class="String(activeCandidateId) === String(candidate.id) ? 'bg-[var(--sys-brand-soft)]/50' : 'hover:bg-[var(--sys-bg-hover)]'">
                  <td class="px-4 py-3 whitespace-nowrap bg-transparent">
                    <div class="flex flex-col bg-transparent">
                      <span class="text-[13px] font-semibold text-[var(--sys-text-primary)] mb-0.5 truncate max-w-[180px]">{{ candidate.name }}</span>
                      <span class="text-[11px] font-bold text-[var(--sys-brand-solid)] opacity-60 uppercase tracking-tight">CAND-{{ candidate.id }}</span>
                    </div>
                  </td>
                  <td class="px-4 py-3 whitespace-nowrap bg-transparent">
                    <span class="text-[12px] font-semibold text-[var(--sys-text-secondary)] uppercase tracking-wide opacity-80">{{ candidate.position }}</span>
                  </td>
                  <td class="px-4 py-3 whitespace-nowrap bg-transparent">
                    <div class="flex flex-col gap-1">
                      <span :class="['inline-flex w-fit px-2 py-0.5 rounded-md text-[10px] font-bold uppercase tracking-wide border', getStatusClass(candidate.status)]">
                        {{ candidate.statusLabel }}
                      </span>
                      <span :class="['inline-flex w-fit px-2 py-0.5 rounded-md text-[10px] font-bold uppercase tracking-wide border', getAiScoreChipClass(candidate)]">
                        {{ formatCandidateAiSummary(candidate) }}
                      </span>
                    </div>
                  </td>
                  <td class="px-4 py-3 text-right whitespace-nowrap bg-transparent">
                    <span class="text-[12px] font-medium text-[var(--sys-text-disabled)] opacity-60">{{ candidate.date }}</span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Pagination Footer -->
          <div class="px-4 py-3 bg-[var(--sys-bg-page)]/50 border-t border-[var(--sys-border-subtle)] flex justify-between items-center text-[11px] font-bold text-[var(--sys-text-secondary)]">
            <span class="uppercase tracking-widest opacity-60">Hiển thị {{ paginatedCandidates.length }} / {{ filteredCandidates.length }} hồ sơ</span>
            <div class="flex items-center gap-1.5">
              <button 
                @click="currentPage > 1 ? currentPage-- : null"
                :disabled="currentPage === 1"
                :class="['w-7 h-7 flex items-center justify-center rounded-md border border-[var(--sys-border-subtle)] transition-all', currentPage === 1 ? 'opacity-30 cursor-not-allowed' : 'bg-white hover:text-[var(--sys-brand-solid)]']"
              >
                <span class="material-symbols-outlined text-[16px]">chevron_left</span>
              </button>
              
              <div class="flex items-center gap-1 px-1">
                <span class="text-[var(--sys-text-primary)]">{{ currentPage }}</span>
                <span class="opacity-30">/</span>
                <span class="opacity-60">{{ totalPages }}</span>
              </div>

              <button 
                @click="currentPage < totalPages ? currentPage++ : null"
                :disabled="currentPage === totalPages"
                :class="['w-7 h-7 flex items-center justify-center rounded-md border border-[var(--sys-border-subtle)] transition-all', currentPage === totalPages ? 'opacity-30 cursor-not-allowed' : 'bg-white hover:text-[var(--sys-brand-solid)]']"
              >
                <span class="material-symbols-outlined text-[16px]">chevron_right</span>
              </button>
            </div>
          </div>
          
          <div v-if="filteredCandidates.length === 0" class="flex flex-col items-center justify-center py-12 bg-[var(--sys-bg-page)]/20 border-t border-[var(--sys-border-subtle)] flex-grow">
            <span class="material-symbols-outlined text-4xl text-[var(--sys-text-disabled)] opacity-20 mb-2">find_in_page</span>
            <p class="text-[12px] font-semibold text-[var(--sys-text-disabled)] opacity-40 uppercase">Không tìm thấy kết quả</p>
          </div>
        </div>
      </div>

      <!-- Right Column: CV Insight & Decision -->
      <div class="xl:col-span-5 flex flex-col bg-transparent">
        <div class="bg-[var(--sys-bg-surface)] rounded-lg border border-[var(--sys-border-subtle)] shadow-sm flex-grow overflow-hidden flex flex-col">
          <!-- CV Tab Header -->
          <div class="px-5 py-4 border-b border-[var(--sys-border-subtle)] flex items-center justify-between bg-[var(--sys-bg-page)]/50">
            <div class="flex items-center gap-2">
              <span class="material-symbols-outlined text-[var(--sys-brand-solid)] text-[20px]">description</span>
              <span class="text-[12px] font-bold text-[var(--sys-text-primary)] uppercase tracking-widest truncate max-w-[200px]">{{ activeCandidate?.name || 'Chọn hồ sơ' }}</span>
            </div>
            <div class="flex gap-1 items-center">
              <span v-if="activeCandidate" :class="['px-2 py-0.5 rounded-md text-[10px] font-bold border uppercase tracking-wide', getStatusClass(activeCandidate.status)]">{{ activeCandidate.statusLabel }}</span>
            </div>
          </div>
          
          <!-- CV Link Info / Action -->
          <div class="p-4 bg-[var(--sys-bg-page)] border-b border-[var(--sys-border-subtle)] flex items-center justify-between" v-if="activeCandidate">
            <div class="flex items-center gap-3">
              <span class="material-symbols-outlined text-[var(--sys-text-secondary)] text-[18px]">link</span>
              <a
                v-if="hasRealCv"
                :href="activeCandidate.cvUrl"
                target="_blank"
                class="text-[13px] font-medium text-[var(--sys-brand-solid)] hover:underline flex items-center gap-1"
              >
                Xem chi tiết CV / Portfolio (LinkedIn)
                <span class="material-symbols-outlined text-[14px]">open_in_new</span>
              </a>
              <span v-else class="text-[12px] font-semibold text-[var(--sys-text-disabled)] uppercase tracking-wide">
                Chưa có file CV hợp lệ
              </span>
            </div>
            <!-- If cover letter, we'll display below -->
          </div>

          <!-- CV Viewer Controls -->
          <div v-if="activeCandidate" class="px-4 py-2 border-b border-[var(--sys-border-subtle)] bg-[var(--sys-bg-surface)]">
            <div class="flex flex-wrap items-center gap-2 text-[11px]">
              <button
                @click="bumpCvFrameHeight(-60)"
                class="h-7 px-2 rounded border border-[var(--sys-border-subtle)] text-[var(--sys-text-secondary)] hover:bg-[var(--sys-bg-hover)]"
              >
                Khung -
              </button>
              <button
                @click="bumpCvFrameHeight(60)"
                class="h-7 px-2 rounded border border-[var(--sys-border-subtle)] text-[var(--sys-text-secondary)] hover:bg-[var(--sys-bg-hover)]"
              >
                Khung +
              </button>
              <button
                @click="bumpCvZoom(-10)"
                class="h-7 px-2 rounded border border-[var(--sys-border-subtle)] text-[var(--sys-text-secondary)] hover:bg-[var(--sys-bg-hover)]"
              >
                Zoom -
              </button>
              <button
                @click="bumpCvZoom(10)"
                class="h-7 px-2 rounded border border-[var(--sys-border-subtle)] text-[var(--sys-text-secondary)] hover:bg-[var(--sys-bg-hover)]"
              >
                Zoom +
              </button>
              <button
                @click="resetCvView"
                class="h-7 px-2 rounded border border-[var(--sys-brand-border)] text-[var(--sys-brand-solid)] hover:bg-[var(--sys-brand-soft)]"
              >
                Reset
              </button>
              <span class="ml-auto text-[var(--sys-text-secondary)] font-semibold">Zoom: {{ cvZoom }}% · Cao: {{ cvFrameHeight }}px</span>
            </div>
          </div>

          <!-- Real PDF Viewer -->
          <div
            v-if="hasRealCv"
            class="p-4 bg-[var(--sys-bg-hover)]/20 relative overflow-hidden hidden md:block"
            :style="{ height: `${cvFrameHeight}px` }"
          >
            <iframe 
              :key="iframeKey"
              :src="cvViewerSrc"
              class="w-full h-full border-none rounded-md shadow-lg bg-white transition-opacity"
              title="CV Viewer"
            ></iframe>
          </div>
          <div v-else class="p-6 bg-[var(--sys-bg-hover)]/20 hidden md:flex items-center justify-center border-b border-[var(--sys-border-subtle)]">
            <div class="text-center">
              <span class="material-symbols-outlined text-3xl text-[var(--sys-text-disabled)] opacity-50">description_off</span>
              <p class="mt-2 text-[12px] font-semibold text-[var(--sys-text-secondary)] uppercase tracking-wide">Ứng viên chưa có CV để xem</p>
            </div>
          </div>

          <!-- Decision Panel -->
          <div class="p-5 border-t border-[var(--sys-border-subtle)] bg-[var(--sys-bg-surface)]" v-if="activeCandidate">
            <!-- Candidate Quick Info -->
            <div class="mb-4 p-4 rounded-md bg-[var(--sys-bg-page)] border border-[var(--sys-border-subtle)] space-y-2">
              <div class="flex justify-between items-start">
                <div>
                  <p class="text-[13px] font-bold text-[var(--sys-text-primary)]">{{ activeCandidate.name }}</p>
                  <p class="text-[11px] text-[var(--sys-text-secondary)]">{{ activeCandidate.email }} · {{ activeCandidate.phone }}</p>
                  <p class="text-[11px] text-[var(--sys-brand-solid)] font-bold mt-1">{{ activeCandidate.position }} — {{ activeCandidate.department }}</p>
                </div>
                <div class="text-right">
                  <p class="text-[11px] font-bold text-[var(--sys-text-secondary)] uppercase tracking-wider mb-0.5">Mức lương kỳ vọng</p>
                  <p class="text-[13px] font-bold text-[var(--sys-text-primary)]">{{ activeCandidate.notes ? activeCandidate.notes.replace('Mức lương kỳ vọng:', '').trim() || 'Thỏa thuận' : 'Thỏa thuận' }}</p>
                </div>
              </div>

              <!-- Cover Letter Snippet -->
              <div v-if="activeCandidate.coverLetter" class="mt-3 p-2.5 bg-white border border-[var(--sys-border-subtle)] rounded shadow-sm">
                <p class="text-[10px] font-bold text-[var(--sys-text-secondary)] uppercase tracking-widest mb-1 flex items-center gap-1">
                  <span class="material-symbols-outlined text-[14px]">format_quote</span> Thư tự giới thiệu
                </p>
                <p class="text-[12px] text-[var(--sys-text-primary)] italic line-clamp-3">"{{ activeCandidate.coverLetter }}"</p>
              </div>

              <div class="mt-3 p-3 rounded-md bg-white border border-[var(--sys-border-subtle)] shadow-sm">
                <div class="flex items-center justify-between gap-3">
                  <p class="text-[10px] font-bold text-[var(--sys-text-secondary)] uppercase tracking-widest flex items-center gap-1">
                    <span class="material-symbols-outlined text-[14px]">psychology</span>
                    Chấm CV AI
                  </p>
                  <span class="text-[11px] font-bold" :class="getAiStatusClass(activeCandidate.aiScoringStatus)">
                    {{ getAiStatusLabel(activeCandidate.aiScoringStatus) }}
                  </span>
                </div>

                <div class="mt-2 flex items-center justify-between text-[12px]">
                  <span class="text-[var(--sys-text-secondary)]">Điểm tổng:</span>
                  <span class="font-bold text-[var(--sys-text-primary)]">{{ formatCandidateAiScore(activeCandidate) }}</span>
                </div>

                <div class="mt-2 grid grid-cols-2 gap-2 text-[11px] text-[var(--sys-text-secondary)]">
                  <div>Semantic: <span class="font-semibold text-[var(--sys-text-primary)]">{{ formatAiPart(activeCandidate.aiSemanticScore) }}</span></div>
                  <div>Must-have: <span class="font-semibold text-[var(--sys-text-primary)]">{{ formatAiPart(activeCandidate.aiMustHaveScore) }}</span></div>
                  <div>Nice-to-have: <span class="font-semibold text-[var(--sys-text-primary)]">{{ formatAiPart(activeCandidate.aiNiceScore) }}</span></div>
                  <div>Kinh nghiệm: <span class="font-semibold text-[var(--sys-text-primary)]">{{ formatAiPart(activeCandidate.aiExpScore) }}</span></div>
                </div>

                <p v-if="activeCandidate.aiScoringError" class="mt-2 text-[11px] text-[var(--sys-danger-text)]">
                  {{ activeCandidate.aiScoringError }}
                </p>

                <div v-if="activeCandidate.aiMatchedSkills?.length" class="mt-2">
                  <p class="text-[10px] font-bold text-[var(--sys-success-text)] uppercase tracking-wide mb-1">Kỹ năng khớp</p>
                  <div class="flex flex-wrap gap-1">
                    <span v-for="skill in activeCandidate.aiMatchedSkills" :key="`matched-${skill}`" class="px-2 py-0.5 rounded text-[10px] font-semibold bg-[var(--sys-success-soft)] text-[var(--sys-success-text)] border border-[var(--sys-success-border)]">
                      {{ skill }}
                    </span>
                  </div>
                </div>

                <div v-if="activeCandidate.aiMissingSkills?.length" class="mt-2">
                  <p class="text-[10px] font-bold text-[var(--sys-danger-text)] uppercase tracking-wide mb-1">Kỹ năng thiếu</p>
                  <div class="flex flex-wrap gap-1">
                    <span v-for="skill in activeCandidate.aiMissingSkills" :key="`missing-${skill}`" class="px-2 py-0.5 rounded text-[10px] font-semibold bg-[var(--sys-danger-soft)] text-[var(--sys-danger-text)] border border-[var(--sys-danger-border)]">
                      {{ skill }}
                    </span>
                  </div>
                </div>
                <button
                  v-if="activeCandidate.aiScoringStatus === 'FAILED'"
                  @click="handleRetryAiScore"
                  class="mt-2 h-8 px-3 rounded-md border border-[var(--sys-brand-border)] text-[var(--sys-brand-solid)] text-[11px] font-bold hover:bg-[var(--sys-brand-soft)] transition-all"
                >
                  Retry chấm điểm
                </button>
              </div>

              </div>

            <!-- STATE: CHỜ_HR_DUYỆT → Phê duyệt + Tạo lịch PV -->
            <div v-if="activeCandidate.status === 'pending_hr'" class="animate-fadeIn">
              <h4 class="text-[12px] font-bold text-[var(--sys-warning-text)] flex items-center gap-2 mb-4 uppercase tracking-wide">
                <span class="material-symbols-outlined text-[18px]">pending_actions</span> Chờ HR xét duyệt
              </h4>

              <!-- Bước 1: Chưa phê duyệt → hiện 2 nút -->
              <div v-if="!showScheduleForm" class="flex gap-3">
                <button @click="showScheduleForm = true"
                  class="flex-1 h-10 bg-[var(--sys-brand-solid)] text-white rounded-md text-[13px] font-bold hover:brightness-90 shadow-sm transition-all flex items-center justify-center gap-2 uppercase tracking-wide">
                  <span class="material-symbols-outlined text-[18px]">task_alt</span>
                  Phê duyệt
                </button>
                <button @click="handleFinalDecision('fail')"
                  class="flex-1 h-10 border border-[var(--sys-danger-border)] text-[var(--sys-danger-text)] rounded-md text-[13px] font-bold hover:bg-[var(--sys-danger-soft)] transition-all uppercase tracking-wide">
                  Từ chối
                </button>
              </div>

              <!-- Bước 2: Sau khi phê duyệt → hiện form lịch PV -->
              <div v-else class="animate-fadeIn space-y-3">
                <div class="p-3 rounded-md bg-[var(--sys-success-soft)] border border-[var(--sys-success-border)] flex items-center gap-2">
                  <span class="material-symbols-outlined text-[var(--sys-success-text)] text-[18px]">check_circle</span>
                  <p class="text-[12px] font-bold text-[var(--sys-success-text)] uppercase tracking-wide">Đã phê duyệt — Tạo lịch phỏng vấn</p>
                </div>
                <div class="grid grid-cols-2 gap-3">
                  <div class="space-y-1.5">
                    <label class="text-[11px] font-bold text-[var(--sys-text-secondary)] uppercase tracking-wider ml-1">Ngày phỏng vấn</label>
                    <input type="date" v-model="interviewDate" class="w-full h-9 px-3 bg-[var(--sys-bg-page)] border border-[var(--sys-border-strong)] rounded-md text-[13px] font-medium text-[var(--sys-text-primary)] outline-none focus:border-[var(--sys-brand-solid)] shadow-sm">
                  </div>
                  <div class="space-y-1.5">
                    <label class="text-[11px] font-bold text-[var(--sys-text-secondary)] uppercase tracking-wider ml-1">Giờ hẹn</label>
                    <input type="time" v-model="interviewTime" class="w-full h-9 px-3 bg-[var(--sys-bg-page)] border border-[var(--sys-border-strong)] rounded-md text-[13px] font-medium text-[var(--sys-text-primary)] outline-none focus:border-[var(--sys-brand-solid)] shadow-sm">
                  </div>
                </div>
                <div class="flex gap-2">
                  <button @click="handleApproveAndSchedule"
                    class="flex-1 h-10 bg-[var(--sys-brand-solid)] text-white rounded-md text-[13px] font-bold hover:brightness-90 shadow-sm transition-all flex items-center justify-center gap-2 uppercase tracking-wide">
                    <span class="material-symbols-outlined text-[18px]">send</span>
                    Gửi CV &amp; Lịch PV cho Trưởng phòng
                  </button>
                  <button @click="showScheduleForm = false"
                    class="h-10 px-3 border border-[var(--sys-border-subtle)] text-[var(--sys-text-secondary)] rounded-md text-[12px] font-bold hover:bg-[var(--sys-bg-hover)] transition-all">
                    Hủy
                  </button>
                </div>
              </div>
            </div>

            <!-- STATE: CHỜ_TP_DUYỆT → Hiển thị thông tin hoặc Lên lịch nếu chưa có -->
            <div v-else-if="['pending_mgr', 'mgr_approved'].includes(activeCandidate.status)" class="animate-fadeIn">
              <h4 class="text-[12px] font-bold text-[var(--sys-brand-solid)] flex items-center gap-2 mb-4 uppercase tracking-wide">
                <span class="material-symbols-outlined text-[18px]">event</span> 
                {{ activeCandidate.interviewDate ? 'Hồ sơ đang chờ Trưởng phòng đánh giá' : 'Chờ Trưởng phòng thẩm định → Lên lịch' }}
              </h4>

              <!-- Display Manager's Review if available -->
              <div v-if="activeCandidate.managerReview" class="mb-5 p-3.5 rounded-md bg-[var(--sys-success-soft)] border border-[var(--sys-success-border)] shadow-sm">
                <p class="text-[11px] font-bold text-[var(--sys-success-text)] uppercase mb-1.5 flex items-center gap-1.5 tracking-wider">
                  <span class="material-symbols-outlined text-[16px]">rate_review</span> Yêu cầu Phỏng vấn từ Trưởng Phòng:
                </p>
                <p class="text-[13px] text-[var(--sys-text-primary)] italic font-semibold">"{{ activeCandidate.managerReview }}"</p>
              </div>

              <!-- Nếu ĐÃ CÓ lịch phỏng vấn từ bước HR phê duyệt -->
              <div v-if="activeCandidate.interviewDate" class="mb-5 p-4 rounded-md bg-[var(--sys-brand-soft)]/30 border border-[var(--sys-brand-border)]">
                <div class="flex items-center gap-2 mb-2">
                  <span class="material-symbols-outlined text-[var(--sys-brand-solid)] text-[20px]">calendar_month</span>
                  <p class="text-[12px] font-bold text-[var(--sys-brand-solid)] uppercase tracking-wide">Lịch phỏng vấn đã thiết lập</p>
                </div>
                <p class="text-[13px] font-semibold text-[var(--sys-text-primary)]">
                  Thời gian: {{ new Date(activeCandidate.interviewDate).toLocaleString('vi-VN') }}
                </p>
                <p class="text-[11px] text-[var(--sys-text-secondary)] mt-1 italic">Ứng viên đã nhận được thông báo. Chờ Trưởng phòng phản hồi sau phỏng vấn.</p>
              </div>
              
              <!-- Nếu CHƯA CÓ lịch (luồng cũ hoặc TP yêu cầu phỏng vấn) -->
              <div v-else>
                <div class="mb-5 p-3 rounded-md bg-[var(--sys-bg-page)] border border-[var(--sys-border-subtle)] border-dashed text-center">
                  <p class="text-[11px] font-medium text-[var(--sys-text-disabled)] tracking-wide">Trạng thái: Đang chờ Trưởng phòng phản hồi thẩm định CV.</p>
                </div>

                <div class="grid grid-cols-2 gap-3 mb-4">
                  <div class="space-y-1.5">
                    <label class="text-[11px] font-bold text-[var(--sys-text-secondary)] uppercase tracking-wider ml-1">Ngày chọn</label>
                    <input type="date" v-model="interviewDate" class="w-full h-9 px-3 bg-[var(--sys-bg-page)] border border-[var(--sys-border-strong)] rounded-md text-[13px] font-medium text-[var(--sys-text-primary)] outline-none focus:border-[var(--sys-brand-solid)] shadow-sm">
                  </div>
                  <div class="space-y-1.5">
                    <label class="text-[11px] font-bold text-[var(--sys-text-secondary)] uppercase tracking-wider ml-1">Giờ hẹn</label>
                    <input type="time" v-model="interviewTime" class="w-full h-9 px-3 bg-[var(--sys-bg-page)] border border-[var(--sys-border-strong)] rounded-md text-[13px] font-medium text-[var(--sys-text-primary)] outline-none focus:border-[var(--sys-brand-solid)] shadow-sm">
                  </div>
                </div>
                <button @click="handleScheduleInterview"
                  class="w-full h-10 bg-[var(--sys-brand-solid)] text-white rounded-md text-[13px] font-bold hover:brightness-90 shadow-sm transition-all flex items-center justify-center gap-2 uppercase tracking-wide">
                  <span class="material-symbols-outlined text-[20px]">send</span>
                  Gửi triệu tập phỏng vấn
                </button>
              </div>
            </div>

            <!-- STATE: ĐANG_PHỎNG_VẤN → Quyết định cuối -->
            <div v-else-if="isAwaitingFinalDecision" class="animate-fadeIn">
              <h4 class="text-[12px] font-bold text-purple-600 flex items-center gap-2 mb-4 uppercase tracking-wide">
                <span class="material-symbols-outlined text-[18px]">fact_check</span> Quyết định tuyển dụng
              </h4>
              <div v-if="!activeCandidate.managerReview" class="mb-4 p-3 rounded-md bg-[var(--sys-warning-soft)] border border-[var(--sys-warning-border)]">
                <p class="text-[11px] font-bold text-[var(--sys-warning-text)] uppercase mb-1">Đang chờ phản hồi Trưởng phòng</p>
                <p class="text-[12px] text-[var(--sys-text-secondary)]">HR chỉ chốt trúng tuyển/từ chối sau khi Trưởng phòng gửi điểm và nhận xét phỏng vấn.</p>
              </div>
              <div v-if="activeCandidate.managerReview" class="mb-4 p-3 rounded-md bg-[var(--sys-brand-soft)] border border-[var(--sys-brand-border)]">
                <p class="text-[11px] font-bold text-[var(--sys-brand-solid)] uppercase mb-1">Đánh giá từ Trưởng phòng:</p>
                <p class="text-[13px] text-[var(--sys-text-primary)] italic">"{{ activeCandidate.managerReview }}"</p>
              </div>
              <div v-if="activeCandidate.interviewDate" class="mb-4 text-[12px] text-[var(--sys-text-secondary)]">
                <span class="font-bold">Lịch phỏng vấn:</span> {{ activeCandidate.interviewDate }}
              </div>
              <div class="flex items-center gap-4">
                <button @click="handleFinalDecision('pass')"
                  :disabled="!canFinalizeDecision"
                  :class="['flex-1 h-10 rounded-md text-[13px] font-bold shadow-sm transition-all uppercase tracking-wide', canFinalizeDecision ? 'bg-[var(--sys-success-solid)] text-white hover:brightness-90' : 'bg-[var(--sys-bg-hover)] text-[var(--sys-text-disabled)] cursor-not-allowed']">Trúng tuyển</button>
                <button @click="handleFinalDecision('fail')"
                  :disabled="!canFinalizeDecision"
                  :class="['flex-1 h-10 rounded-md text-[13px] font-bold shadow-sm transition-all uppercase tracking-wide', canFinalizeDecision ? 'bg-[var(--sys-danger-solid)] text-white hover:brightness-90' : 'bg-[var(--sys-bg-hover)] text-[var(--sys-text-disabled)] cursor-not-allowed']">Từ chối</button>
              </div>
            </div>

            <!-- STATE: TRÚNG_TUYỂN / TỪ_CHỐI → readonly -->
            <div v-else class="animate-fadeIn text-center py-4">
              <span :class="['px-4 py-2 rounded-lg text-[13px] font-bold border inline-block', getStatusClass(activeCandidate.status)]">{{ activeCandidate.statusLabel }}</span>
              <p class="text-[11px] text-[var(--sys-text-disabled)] mt-2">Hồ sơ đã được xử lý.</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Bottom Lists Section -->
    <div class="bg-[var(--sys-bg-surface)] rounded-lg border border-[var(--sys-border-subtle)] shadow-sm overflow-hidden">
      <div class="flex items-center gap-2 p-2 bg-[var(--sys-bg-page)]/50 border-b border-[var(--sys-border-subtle)]">
        <button
          @click="resultTab = 'passed'"
          :class="[
            'h-8 px-5 rounded-md text-[11px] font-bold shadow-sm flex items-center gap-2 uppercase tracking-wide transition-all',
            resultTab === 'passed'
              ? 'bg-[var(--sys-success-solid)] text-white'
              : 'text-[var(--sys-text-secondary)] hover:bg-[var(--sys-bg-hover)]'
          ]"
        >
          <span class="w-1.5 h-1.5 rounded-full" :class="resultTab === 'passed' ? 'bg-white' : 'bg-[var(--sys-success-solid)]'"></span>
          Đã trúng tuyển ({{ passedCandidates.length }})
        </button>
        <button
          @click="resultTab = 'archive'"
          :class="[
            'h-8 px-5 rounded-md text-[11px] font-bold shadow-sm flex items-center gap-2 uppercase tracking-wide transition-all',
            resultTab === 'archive'
              ? 'bg-[var(--sys-danger-solid)] text-white'
              : 'text-[var(--sys-text-secondary)] hover:bg-[var(--sys-bg-hover)]'
          ]"
        >
          <span class="w-1.5 h-1.5 rounded-full" :class="resultTab === 'archive' ? 'bg-white' : 'bg-[var(--sys-danger-solid)]'"></span>
          Kho lưu trữ từ chối ({{ rejectedCandidates.length }})
        </button>
      </div>

      <div class="p-5">
        <div v-if="resultTab === 'passed'">
          <div v-if="passedCandidates.length === 0" class="py-10 text-center rounded-lg border border-dashed border-[var(--sys-border-subtle)] bg-[var(--sys-bg-page)]/30">
            <span class="material-symbols-outlined text-3xl text-[var(--sys-text-disabled)] opacity-40">verified</span>
            <p class="mt-2 text-[12px] font-semibold text-[var(--sys-text-secondary)]">Chưa có ứng viên trúng tuyển</p>
            <p class="text-[11px] text-[var(--sys-text-disabled)]">Danh sách chỉ hiển thị khi HR chốt trạng thái Trúng tuyển.</p>
          </div>
          <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <article
              v-for="candidate in passedCandidates"
              :key="`passed-${candidate.id}`"
              class="p-4 rounded-lg border border-[var(--sys-success-border)] bg-[var(--sys-success-soft)]/40 shadow-sm"
            >
              <div class="flex items-start justify-between gap-3">
                <div>
                  <h6 class="text-[13px] font-semibold text-[var(--sys-text-primary)]">{{ candidate.name }}</h6>
                  <p class="text-[11px] font-bold text-[var(--sys-success-text)] uppercase tracking-wide">{{ candidate.position }}</p>
                </div>
                <span class="px-2 py-0.5 rounded-md text-[10px] font-bold border border-[var(--sys-success-border)] text-[var(--sys-success-text)] bg-white">PASS</span>
              </div>
              <div class="mt-3 space-y-1 text-[11px] text-[var(--sys-text-secondary)]">
                <p><span class="font-semibold">Ngày nộp:</span> {{ candidate.date }}</p>
                <p><span class="font-semibold">Điểm AI:</span> {{ formatCandidateAiScore(candidate) }}</p>
                <p class="truncate"><span class="font-semibold">Email:</span> {{ candidate.email }}</p>
              </div>
            </article>
          </div>
        </div>

        <div v-else>
          <div v-if="rejectedCandidates.length === 0" class="py-10 text-center rounded-lg border border-dashed border-[var(--sys-border-subtle)] bg-[var(--sys-bg-page)]/30">
            <span class="material-symbols-outlined text-3xl text-[var(--sys-text-disabled)] opacity-40">inventory_2</span>
            <p class="mt-2 text-[12px] font-semibold text-[var(--sys-text-secondary)]">Kho lưu trữ đang trống</p>
            <p class="text-[11px] text-[var(--sys-text-disabled)]">Ứng viên bị từ chối sẽ được lưu tại đây để tiện tra cứu.</p>
          </div>
          <div v-else class="overflow-x-auto custom-scrollbar rounded-lg border border-[var(--sys-border-subtle)]">
            <table class="w-full text-left border-collapse">
              <thead class="bg-[var(--sys-bg-page)]">
                <tr>
                  <th class="px-4 py-3 text-[11px] font-bold text-[var(--sys-text-secondary)] uppercase tracking-wide border-b border-[var(--sys-border-subtle)]">Ứng viên</th>
                  <th class="px-4 py-3 text-[11px] font-bold text-[var(--sys-text-secondary)] uppercase tracking-wide border-b border-[var(--sys-border-subtle)]">Vị trí</th>
                  <th class="px-4 py-3 text-[11px] font-bold text-[var(--sys-text-secondary)] uppercase tracking-wide border-b border-[var(--sys-border-subtle)]">Liên hệ</th>
                  <th class="px-4 py-3 text-[11px] font-bold text-[var(--sys-text-secondary)] uppercase tracking-wide border-b border-[var(--sys-border-subtle)]">Điểm AI</th>
                  <th class="px-4 py-3 text-[11px] font-bold text-[var(--sys-text-secondary)] uppercase tracking-wide border-b border-[var(--sys-border-subtle)]">Lý do</th>
                  <th class="px-4 py-3 text-[11px] font-bold text-[var(--sys-text-secondary)] uppercase tracking-wide border-b border-[var(--sys-border-subtle)] text-right">Ngày xử lý</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-[var(--sys-border-subtle)]">
                <tr v-for="candidate in rejectedCandidates" :key="`archive-${candidate.id}`" class="hover:bg-[var(--sys-bg-hover)]/70">
                  <td class="px-4 py-3">
                    <p class="text-[12px] font-semibold text-[var(--sys-text-primary)]">{{ candidate.name }}</p>
                    <p class="text-[10px] font-bold text-[var(--sys-danger-text)] uppercase tracking-wide">REJECTED</p>
                  </td>
                  <td class="px-4 py-3 text-[12px] font-medium text-[var(--sys-text-secondary)]">{{ candidate.position }}</td>
                  <td class="px-4 py-3 text-[11px] text-[var(--sys-text-secondary)]">
                    <p>{{ candidate.email || 'N/A' }}</p>
                    <p>{{ candidate.phone || 'N/A' }}</p>
                  </td>
                  <td class="px-4 py-3 text-[11px] font-semibold text-[var(--sys-text-primary)]">{{ formatCandidateAiScore(candidate) }}</td>
                  <td class="px-4 py-3 text-[11px] text-[var(--sys-text-secondary)] max-w-[260px]">
                    <p class="line-clamp-2">{{ formatArchiveReason(candidate) }}</p>
                  </td>
                  <td class="px-4 py-3 text-right text-[11px] text-[var(--sys-text-disabled)]">{{ candidate.date }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { useRoute } from 'vue-router';
import Dropdown from '@/components/Dropdown.vue';
import {
  useHRApplications,
  forwardToManager,
  scheduleInterview,
  finalizeCandidate,
  rejectApplication,
  retryCandidateAiScore,
  refreshRecruitmentCandidates,
} from '@/composables/useRecruitmentStore';
import { useConfirm } from '@/composables/useConfirm';

const route = useRoute();
const { all: candidates } = useHRApplications();
const { showAlert } = useConfirm();

const iframeKey = ref(0);
const showScheduleForm = ref(false);
const resultTab = ref('passed');
const cvFrameHeight = ref(360);
const cvZoom = ref(100);

const activeCandidateId = ref(null);

function selectCandidate(id) {
  activeCandidateId.value = id;
  iframeKey.value++; // force iframe to reload with new src
  cvZoom.value = 100;
  showScheduleForm.value = false; // reset form khi đổi ứng viên
  interviewDate.value = '';
  interviewTime.value = '';
}
const searchQuery = ref('');
const filterPosition = ref('');
const filterAiScore = ref('');
const currentPage = ref(1);
const pageSize = 10;

const interviewDate = ref('');
const interviewTime = ref('');
let aiPollingTimer = null;
let candidateSyncTimer = null;

// Auto-select first on load
watch(candidates, (list) => {
  if (!activeCandidateId.value && list.length > 0) {
    activeCandidateId.value = list[0].id;
  }
}, { immediate: true });

const positionOptions = computed(() => {
  const positions = [...new Set(candidates.value.map(c => c.position))];
  return [
    { label: 'Vị trí: Tất cả', value: '' },
    ...positions.map(p => ({ label: p, value: p }))
  ];
});

const aiScoreOptions = [
  { label: 'AI: Mặc định', value: '' },
  { label: 'AI: > 80%', value: '80' },
  { label: 'AI: > 90%', value: '90' }
];

const activeCandidate = computed(() => {
  if (activeCandidateId.value === null || activeCandidateId.value === undefined) {
    return candidates.value[0] || null;
  }
  // Dùng == để tránh lỗi type mismatch giữa string và number
  return candidates.value.find(c => String(c.id) === String(activeCandidateId.value)) || null;
});

const hasRealCv = computed(() => {
  const raw = String(activeCandidate.value?.cvUrl || '').trim();
  if (raw === '') return false;
  const lower = raw.toLowerCase();
  if (lower.includes('cv-template.pdf')) return false;
  return lower.includes('.pdf') || lower.includes('/recruitment-candidates/') || lower.startsWith('http');
});

const cvViewerSrc = computed(() => {
  if (!hasRealCv.value) return '';
  const raw = String(activeCandidate.value?.cvUrl || '').trim();
  if (raw === '') return '';

  const lower = raw.toLowerCase();
  const likelyPdf = lower.includes('.pdf') || lower.includes('/cv');
  if (!likelyPdf) return raw;

  const base = raw.split('#')[0];
  return `${base}#toolbar=1&navpanes=0&zoom=${cvZoom.value}`;
});

const canFinalizeDecision = computed(() => {
  const candidate = activeCandidate.value
  if (!candidate) return false
  const proposal = String(candidate.managerDecisionProposal || '').toUpperCase()
  return Boolean(candidate.managerReview) && (proposal === 'PASS' || proposal === 'FAIL')
});

const isAwaitingFinalDecision = computed(() => {
  const candidate = activeCandidate.value
  if (!candidate) return false
  const proposal = String(candidate.managerDecisionProposal || '').toUpperCase()
  return candidate.status === 'interviewing' || proposal === 'PASS' || proposal === 'FAIL'
});

const filteredCandidates = computed(() => {
  let list = candidates.value;
  const currentStatus = route.query.status;
  if (currentStatus === 'pass') list = list.filter(c => c.status === 'pass');
  else if (currentStatus === 'fail') list = list.filter(c => c.status === 'fail');
  if (filterAiScore.value) list = list.filter(c => c.aiScore >= parseInt(filterAiScore.value));
  if (filterPosition.value) list = list.filter(c => c.position === filterPosition.value);
  if (searchQuery.value) {
    const q = searchQuery.value.toLowerCase();
    list = list.filter(c => c.name.toLowerCase().includes(q) || c.email.toLowerCase().includes(q));
  }
  // Fallback safety:
  // If URL keeps an old pass/fail filter and hides all records, fall back to full list
  // so HR/Admin can still see newly submitted CVs immediately.
  if (
    list.length === 0 &&
    candidates.value.length > 0 &&
    (currentStatus === 'pass' || currentStatus === 'fail') &&
    !filterAiScore.value &&
    !filterPosition.value &&
    !searchQuery.value
  ) {
    return candidates.value;
  }
  return list;
});

const passedCandidates = computed(() =>
  candidates.value
    .filter((candidate) => candidate.status === 'pass' && Number(candidate.backendCandidateId || 0) > 0)
    .sort((a, b) => new Date(b.appliedDate || 0) - new Date(a.appliedDate || 0))
);

const rejectedCandidates = computed(() =>
  candidates.value
    .filter((candidate) => candidate.status === 'fail' && Number(candidate.backendCandidateId || 0) > 0)
    .sort((a, b) => new Date(b.appliedDate || 0) - new Date(a.appliedDate || 0))
);

const hasPendingAiScoring = computed(() =>
  candidates.value.some((candidate) => {
    const status = String(candidate?.aiScoringStatus || '').toUpperCase();
    return status === 'PENDING' || status === 'RUNNING';
  })
);

function stopAiPolling() {
  if (aiPollingTimer) {
    clearInterval(aiPollingTimer);
    aiPollingTimer = null;
  }
}

function startAiPolling() {
  if (aiPollingTimer) return;
  aiPollingTimer = setInterval(async () => {
    if (!hasPendingAiScoring.value) {
      stopAiPolling();
      return;
    }
    try {
      await refreshRecruitmentCandidates();
    } catch (error) {
      console.warn('[tuyendung] auto refresh failed:', error?.message || error);
    }
  }, 5000);
}

watch([passedCandidates, rejectedCandidates], ([passed, rejected]) => {
  if (resultTab.value === 'passed' && passed.length === 0 && rejected.length > 0) {
    resultTab.value = 'archive'
  }
  if (resultTab.value === 'archive' && rejected.length === 0) {
    resultTab.value = 'passed'
  }
}, { immediate: true })

const totalPages = computed(() => Math.ceil(filteredCandidates.value.length / pageSize) || 1);

const paginatedCandidates = computed(() => {
  const start = (currentPage.value - 1) * pageSize;
  return filteredCandidates.value.slice(start, start + pageSize);
});

watch([searchQuery, filterPosition, filterAiScore], () => { currentPage.value = 1; });

const getAiStatusLabel = (status) => {
  const key = String(status || '').toUpperCase()
  if (key === 'RUNNING') return 'Đang chấm'
  if (key === 'DONE') return 'Hoàn tất'
  if (key === 'FAILED') return 'Lỗi chấm'
  return 'Chờ xử lý'
}

const getAiStatusClass = (status) => {
  const key = String(status || '').toUpperCase()
  if (key === 'RUNNING') return 'text-[var(--sys-brand-solid)]'
  if (key === 'DONE') return 'text-[var(--sys-success-text)]'
  if (key === 'FAILED') return 'text-[var(--sys-danger-text)]'
  return 'text-[var(--sys-warning-text)]'
}

const formatAiPart = (value) => {
  if (value === null || value === undefined || Number.isNaN(Number(value))) return 'N/A'
  const numeric = Number(value)
  return `${Math.round(numeric * 100)}%`
}

const formatCandidateAiScore = (candidate) => {
  const status = String(candidate?.aiScoringStatus || '').toUpperCase()
  if (status !== 'DONE') return getAiStatusLabel(status)
  const score = Number(candidate?.aiScore)
  if (!Number.isFinite(score)) return 'N/A'
  return `${Math.round(score)} / 100`
}

const formatCandidateAiSummary = (candidate) => {
  const status = String(candidate?.aiScoringStatus || '').toUpperCase()
  if (status === 'DONE') {
    const score = Math.round(Number(candidate?.aiScore || 0))
    const ranking = getCandidateRankInSamePosition(candidate)
    if (ranking.total > 1) {
      return `AI ${score}/100 · #${ranking.rank}/${ranking.total}`
    }
    return `AI ${score}/100`
  }
  if (status === 'FAILED') return 'AI lỗi chấm'
  if (status === 'RUNNING') return 'AI đang chấm'
  return 'AI chờ xử lý'
}

const getCandidateRankInSamePosition = (candidate) => {
  if (!candidate) return { rank: 0, total: 0 }
  const samePosition = candidates.value
    .filter((item) => String(item.position || '').trim() === String(candidate.position || '').trim())
    .filter((item) => String(item.aiScoringStatus || '').toUpperCase() === 'DONE')
    .sort((a, b) => Number(b.aiScore || 0) - Number(a.aiScore || 0))

  const total = samePosition.length
  const index = samePosition.findIndex((item) => String(item.id) === String(candidate.id))
  return { rank: index >= 0 ? index + 1 : 0, total }
}

const getAiScoreChipClass = (candidate) => {
  const status = String(candidate?.aiScoringStatus || '').toUpperCase()
  const score = Number(candidate?.aiScore || 0)
  if (status !== 'DONE') {
    if (status === 'FAILED') return 'bg-[var(--sys-danger-soft)] text-[var(--sys-danger-text)] border-[var(--sys-danger-border)]'
    if (status === 'RUNNING') return 'bg-[var(--sys-brand-soft)] text-[var(--sys-brand-solid)] border-[var(--sys-brand-border)]'
    return 'bg-[var(--sys-warning-soft)] text-[var(--sys-warning-text)] border-[var(--sys-warning-border)]'
  }
  if (score >= 85) return 'bg-[var(--sys-success-soft)] text-[var(--sys-success-text)] border-[var(--sys-success-border)]'
  if (score >= 70) return 'bg-[var(--sys-warning-soft)] text-[var(--sys-warning-text)] border-[var(--sys-warning-border)]'
  return 'bg-[var(--sys-danger-soft)] text-[var(--sys-danger-text)] border-[var(--sys-danger-border)]'
}

const formatArchiveReason = (candidate) => {
  const managerReason = String(candidate?.managerReview || '').trim()
  const hrReason = String(candidate?.notes || '').trim()
  return managerReason || hrReason || 'Không đạt theo đánh giá phỏng vấn.'
}

const bumpCvZoom = (delta) => {
  const next = cvZoom.value + delta;
  cvZoom.value = Math.max(50, Math.min(200, next));
};

const bumpCvFrameHeight = (delta) => {
  const next = cvFrameHeight.value + delta;
  cvFrameHeight.value = Math.max(220, Math.min(820, next));
};

const resetCvView = () => {
  cvZoom.value = 100;
  cvFrameHeight.value = 360;
};

const getStatusClass = (status) => {
  const map = {
    pending_hr:   'bg-[var(--sys-warning-soft)] text-[var(--sys-warning-text)] border-[var(--sys-warning-border)]',
    pending_mgr:  'bg-[var(--sys-brand-soft)] text-[var(--sys-brand-solid)] border-[var(--sys-brand-border)]',
    mgr_approved: 'bg-indigo-50 text-indigo-700 border-indigo-200',
    interviewing: 'bg-purple-50 text-purple-700 border-purple-200',
    pass:         'bg-[var(--sys-success-soft)] text-[var(--sys-success-text)] border-[var(--sys-success-border)]',
    fail:         'bg-[var(--sys-danger-soft)] text-[var(--sys-danger-text)] border-[var(--sys-danger-border)]',
  };
  return map[status] || map['pending_hr'];
};

// ACTION: HR phê duyệt + tạo lịch PV → forward cho TP (từ trạng thái CHỜ_HR_DUYỆT)
async function handleApproveAndSchedule() {
  if (!activeCandidate.value) return;
  if (!interviewDate.value || !interviewTime.value) {
    await showAlert('THIẾU THÔNG TIN', 'Vui lòng chọn ngày và giờ phỏng vấn trước khi gửi cho Trưởng phòng!');
    return;
  }
  
  // 1. Forward hồ sơ kèm lịch phỏng vấn (Vẫn giữ trạng thái CHỜ_TP_DUYỆT)
  forwardToManager(activeCandidate.value.id, 'HR Admin', { 
    date: interviewDate.value, 
    time: interviewTime.value 
  });
  
  await showAlert(
    'ĐÃ GỬI CHO TRƯỞNG PHÒNG',
    `Hồ sơ và lịch phỏng vấn của ${activeCandidate.value.name} đã được gửi tới Trưởng phòng ${activeCandidate.value.department}.\nLịch hẹn: ${interviewDate.value} lúc ${interviewTime.value}.\nHệ thống đã tạo block lịch phỏng vấn cho ứng viên.`
  );
  
  // Reset
  showScheduleForm.value = false;
  interviewDate.value = '';
  interviewTime.value = '';
}

// ACTION: Lên lịch phỏng vấn (từ trạng thái CHỜ_TP_DUYỆT / TP đã duyệt)
async function handleScheduleInterview() {
  if (!interviewDate.value || !interviewTime.value) {
    await showAlert('THIẾU THÔNG TIN', 'Vui lòng chọn ngày và giờ phỏng vấn!');
    return;
  }
  scheduleInterview(activeCandidate.value.id, interviewDate.value, interviewTime.value);
  await showAlert('THÀNH CÔNG', `Đã gửi lời mời phỏng vấn cho ${activeCandidate.value.name}.`);
  interviewDate.value = '';
  interviewTime.value = '';
}

const validateCandidateBeforePass = (candidate) => {
  const missing = []
  if (!String(candidate?.name || '').trim()) missing.push('Họ và tên')
  if (!String(candidate?.email || '').trim()) missing.push('Email')
  if (!String(candidate?.phone || '').trim()) missing.push('Số điện thoại')
  if (!String(candidate?.position || '').trim()) missing.push('Vị trí ứng tuyển')
  if (!String(candidate?.department || '').trim()) missing.push('Phòng ban')
  if (!String(candidate?.interviewDate || '').trim()) missing.push('Lịch phỏng vấn')
  if (!String(candidate?.managerReview || '').trim()) missing.push('Nhận xét trưởng phòng')
  if (!Number.isFinite(Number(candidate?.managerScore))) missing.push('Điểm phỏng vấn của trưởng phòng')
  return missing
}

// ACTION: Quyết định cuối (pass/fail)
async function handleFinalDecision(finalStatus) {
  if (!canFinalizeDecision.value) {
    await showAlert('CHƯA THỂ CHỐT KẾT QUẢ', 'Vui lòng chờ Trưởng phòng gửi đánh giá và điểm phỏng vấn trước khi HR ra quyết định.');
    return;
  }
  if (finalStatus === 'pass') {
    const missingFields = validateCandidateBeforePass(activeCandidate.value)
    if (missingFields.length) {
      await showAlert(
        'THIẾU THÔNG TIN ỨNG VIÊN',
        `Cần bổ sung trước khi xác nhận trúng tuyển:\n- ${missingFields.join('\n- ')}`
      )
      return
    }
  }
  finalizeCandidate(activeCandidate.value.id, finalStatus);
  const msg = finalStatus === 'pass' ? `Đã xác nhận TRÚNG TUYỂN cho ${activeCandidate.value.name}.` : `Đã từ chối hồ sơ của ${activeCandidate.value.name}.`;
  await showAlert('CẬP NHẬT', msg);
}

async function handleRetryAiScore() {
  if (!activeCandidate.value) return
  try {
    await retryCandidateAiScore(activeCandidate.value.id)
    await showAlert('ĐÃ QUEUE LẠI', `Đã đưa hồ sơ ${activeCandidate.value.name} vào hàng đợi chấm AI.`)
  } catch (error) {
    await showAlert('LỖI', error?.message || 'Không thể retry chấm điểm AI.')
  }
}

watch(hasPendingAiScoring, (pending) => {
  if (pending) {
    startAiPolling();
    return;
  }
  stopAiPolling();
}, { immediate: true });

onMounted(async () => {
  try {
    await refreshRecruitmentCandidates();
  } catch (error) {
    console.warn('[tuyendung] initial refresh failed:', error?.message || error);
  }

  candidateSyncTimer = setInterval(async () => {
    try {
      await refreshRecruitmentCandidates();
    } catch {
      // best-effort periodic sync between HR and manager sessions
    }
  }, 10000);
});

onUnmounted(() => {
  stopAiPolling();
  if (candidateSyncTimer) {
    clearInterval(candidateSyncTimer);
    candidateSyncTimer = null;
  }
});
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

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(8px); }
  to { opacity: 1; transform: translateY(0); }
}
.animate-fadeIn {
  animation: fadeIn 0.4s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}
</style>
