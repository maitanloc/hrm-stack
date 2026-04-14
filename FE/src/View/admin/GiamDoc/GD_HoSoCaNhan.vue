<template>
  <div>
    <div class="max-w-6xl mx-auto py-6 space-y-10 animate-fade-in text-[#1d3d70] dark:text-white">
    
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-4">
      <div>
        <h1 class="text-[24px] font-[800] text-slate-800 dark:text-white tracking-tight">Thông Tin Cá Nhân</h1>
        <p class="text-[13px] text-slate-500 dark:text-slate-400 mt-1 font-medium flex items-center gap-1.5">
          <span class="material-symbols-rounded text-[16px]">admin_panel_settings</span>
          Quản lý định danh và thiết lập cấu hình tài khoản cấp độ Tối cao
        </p>
      </div>
    </div>

    <!-- Top Bento Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
      
      <!-- Profile Card -->
      <div class="lg:col-span-4 bg-[#2b3c63] dark:bg-[#161f33] rounded-[32px] p-8 flex flex-col items-center justify-center shadow-lg shadow-[#2b3c63]/20 dark:shadow-none dark:border dark:border-white/5 text-center relative overflow-hidden group transition-colors">
        <div class="absolute inset-0 bg-gradient-to-b from-white/5 to-transparent pointer-events-none"></div>
        
        <div class="relative mb-5">
          <div class="w-32 h-32 rounded-2xl overflow-hidden border-4 border-white/10 shadow-xl group-hover:scale-105 transition-transform duration-500 ease-out">
            <!-- Using a placeholder for avatar -->
            <img src="https://i.pravatar.cc/300?img=11" alt="Nguyễn Minh Triết" class="w-full h-full object-cover">
          </div>
          <div class="absolute -bottom-2 -right-2 w-7 h-7 bg-amber-500 rounded-full border-4 border-[#2b3c63] flex items-center justify-center">
            <span class="w-2.5 h-2.5 bg-white rounded-full animate-pulse"></span>
          </div>
        </div>

        <h2 class="text-2xl font-bold text-white mb-1 tracking-tight">{{ ceoData?.fullName || 'Nguyễn Minh Triết' }}</h2>
        <p class="text-blue-200/80 text-[13px] font-medium mb-6">{{ ceoData?.positionName || 'Tổng Giám đốc' }}</p>

        <div class="bg-white/10 backdrop-blur-sm border border-white/10 px-6 py-2 rounded-full text-white/90 text-[11px] font-bold tracking-widest uppercase shadow-inner">
          <span class="opacity-60 mr-2">ID LÃNH ĐẠO</span>
          <span class="font-black text-white">{{ ceoData?.employeeCode || 'CEO-001' }}</span>
        </div>
      </div>

      <!-- Right Top Grid -->
      <div class="lg:col-span-8 grid grid-cols-1 sm:grid-cols-2 gap-6 flex-col">
        <!-- Status -->
        <div class="bg-white dark:bg-[#121827] rounded-3xl p-6 shadow-[0_4px_20px_-4px_rgba(0,0,0,0.03)] dark:shadow-none border-t border-slate-50 dark:border-white/5 flex flex-col justify-center hover:shadow-[0_8px_30px_-4px_rgba(0,0,0,0.06)] dark:hover:bg-[#161e30] transition-all group">
          <p class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-2">Trạng thái công vụ</p>
          <h3 class="text-xl font-bold text-[#1d3d70] dark:text-white mb-4">Đang hoạt động</h3>
          <div class="flex items-center gap-1.5 text-amber-600 dark:text-amber-500 text-[11px] font-bold mt-auto">
            <span class="material-symbols-rounded text-[14px]">schedule</span>
            Cập nhật 2 giờ trước
          </div>
        </div>

        <!-- Security Level -->
        <div class="bg-white dark:bg-[#121827] rounded-3xl p-6 shadow-[0_4px_20px_-4px_rgba(0,0,0,0.03)] dark:shadow-none border-t border-slate-50 dark:border-white/5 flex flex-col justify-center hover:shadow-[0_8px_30px_-4px_rgba(0,0,0,0.06)] dark:hover:bg-[#161e30] transition-all">
          <p class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-2">Độ an toàn tài khoản</p>
          <h3 class="text-xl font-bold text-[#1d3d70] dark:text-white mb-4"><span class="text-blue-800 dark:text-blue-400">Mức 5</span> (Tối cao)</h3>
          <div class="flex items-center gap-1.5 text-blue-600 dark:text-blue-400 text-[11px] font-bold mt-auto">
            <span class="material-symbols-rounded text-[14px]">shield_locked</span>
            Toàn vẹn dữ liệu
          </div>
        </div>

        <!-- Strategy Span -->
        <div class="sm:col-span-2 bg-gradient-to-br from-slate-50 to-[#f1f4f0] dark:from-[#161e30] dark:to-[#121827] rounded-[32px] p-6 sm:p-8 border border-white dark:border-white/5 flex items-center shadow-[0_4px_20px_-4px_rgba(0,0,0,0.02)] transition-colors">
          <div class="w-12 h-12 bg-[#e2e8e3] dark:bg-[#1e293b] text-[#718576] dark:text-slate-400 rounded-xl flex items-center justify-center shrink-0 mr-5">
            <span class="material-symbols-rounded text-2xl font-variation-settings:'FILL' 1">workspace_premium</span>
          </div>
          <div class="flex-1">
            <h3 class="text-base sm:text-lg font-bold text-[#1d3d70] dark:text-white mb-1">Nhiệm kỳ chiến lược <span class="text-slate-600 dark:text-slate-400">2023-2028</span></h3>
            <p class="text-[13px] font-medium text-slate-500 dark:text-slate-500 line-clamp-2">Phát triển hệ sinh thái doanh nghiệp toàn cầu</p>
          </div>
          
        </div>

      </div>
    </div>

    <!-- Personal Info -->
    <div>
      <div class="flex items-center gap-3 mb-6">
        <div class="h-0.5 w-6 bg-amber-500 rounded-full"></div>
        <h3 class="text-sm font-bold text-[#1d3d70] dark:text-white uppercase tracking-wider">Thông tin cá nhân</h3>
      </div>
      
      <div class="grid grid-cols-2 md:grid-cols-4 gap-4 sm:gap-6">
        <div class="bg-slate-50/80 dark:bg-[#121827] border border-slate-100 dark:border-white/5 rounded-2xl p-5 hover:bg-slate-100 dark:hover:bg-[#161e30] transition-colors">
          <span class="material-symbols-rounded text-amber-600 dark:text-amber-500 mb-4 text-[20px] font-variation-settings:'FILL' 1">cake</span>
          <p class="text-[9px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-1.5">Ngày sinh</p>
          <p class="text-[13px] font-bold text-[#1d3d70] dark:text-slate-200">15/05/1982</p>
        </div>
        <div class="bg-slate-50/80 dark:bg-[#121827] border border-slate-100 dark:border-white/5 rounded-2xl p-5 hover:bg-slate-100 dark:hover:bg-[#161e30] transition-colors">
          <span class="material-symbols-rounded text-amber-600 dark:text-amber-500 mb-4 text-[20px] font-variation-settings:'FILL' 1">mail</span>
          <p class="text-[9px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-1.5">Email công việc</p>
          <p class="text-[13px] font-bold text-[#1d3d70] dark:text-slate-200 truncate" :title="ceoData ? ceoData.employeeCode.toLowerCase() + '@hrm.com' : 'ceo@hrm.com'">{{ ceoData ? ceoData.employeeCode.toLowerCase() + '@hrm.com' : 'ceo@hrm.com' }}</p>
        </div>
        <div class="bg-slate-50/80 dark:bg-[#121827] border border-slate-100 dark:border-white/5 rounded-2xl p-5 hover:bg-slate-100 dark:hover:bg-[#161e30] transition-colors">
          <span class="material-symbols-rounded text-amber-600 dark:text-amber-500 mb-4 text-[20px] font-variation-settings:'FILL' 1">smartphone</span>
          <p class="text-[9px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-1.5">Số điện thoại bảo mật</p>
          <p class="text-[13px] font-bold text-[#1d3d70] dark:text-slate-200">(+84) 90 123 4587</p>
        </div>
        <div class="bg-slate-50/80 dark:bg-[#121827] border border-slate-100 dark:border-white/5 rounded-2xl p-5 hover:bg-slate-100 dark:hover:bg-[#161e30] transition-colors">
          <span class="material-symbols-rounded text-amber-600 dark:text-amber-500 mb-4 text-[20px] font-variation-settings:'FILL' 1">location_on</span>
          <p class="text-[9px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-1.5">Địa chỉ</p>
          <p class="text-[13px] font-bold text-[#1d3d70] dark:text-slate-200">Q.1, TP. Hồ Chí Minh</p>
        </div>
      </div>
    </div>

    <!-- Job Info -->
    <div>
      <div class="flex items-center gap-3 mb-6">
        <div class="h-0.5 w-6 bg-slate-300 dark:bg-slate-600 rounded-full"></div>
        <h3 class="text-sm font-bold text-[#1d3d70]/70 dark:text-slate-300 uppercase tracking-wider">Thông tin công việc</h3>
      </div>
      
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white dark:bg-[#121827] border border-slate-200 dark:border-white/5 rounded-3xl p-6 shadow-sm shadow-slate-100 dark:shadow-none hover:shadow-md dark:hover:bg-[#161e30] transition-all">
          <div class="flex items-center gap-3 mb-5">
            <div class="w-8 h-8 rounded-lg bg-slate-100 dark:bg-[#1e293b] text-[#1d3d70] dark:text-blue-400 flex items-center justify-center">
              <span class="material-symbols-rounded text-[18px]">assignment_ind</span>
            </div>
            <p class="text-[11px] font-bold text-[#1d3d70] dark:text-slate-200 uppercase">Ngày bổ nhiệm</p>
          </div>
          <h3 class="text-xl font-bold text-[#1d3d70] dark:text-white mb-2">01/01/2023</h3>
          <p class="text-[11px] font-medium text-slate-500 dark:text-slate-400">Quyết định số: 01/QĐ-HĐQT</p>
        </div>

        <div class="bg-white dark:bg-[#121827] border border-slate-200 dark:border-white/5 rounded-3xl p-6 shadow-sm shadow-slate-100 dark:shadow-none hover:shadow-md dark:hover:bg-[#161e30] transition-all">
          <div class="flex items-center gap-3 mb-5">
            <div class="w-8 h-8 rounded-lg bg-slate-100 dark:bg-[#1e293b] text-[#1d3d70] dark:text-blue-400 flex items-center justify-center">
              <span class="material-symbols-rounded text-[18px]">hourglass_empty</span>
            </div>
            <p class="text-[11px] font-bold text-[#1d3d70] dark:text-slate-200 uppercase">Nhiệm kỳ</p>
          </div>
          <h3 class="text-xl font-bold text-[#1d3d70] dark:text-white mb-2">05 Năm</h3>
          <p class="text-[11px] font-medium text-slate-500 dark:text-slate-400">Kết thúc: 31/12/2027</p>
        </div>

        <div class="bg-white dark:bg-[#121827] border border-slate-200 dark:border-white/5 rounded-3xl p-6 shadow-sm shadow-slate-100 dark:shadow-none hover:shadow-md dark:hover:bg-[#161e30] transition-all">
          <div class="flex items-center gap-3 mb-5">
            <div class="w-8 h-8 rounded-lg bg-slate-100 dark:bg-[#1e293b] text-[#1d3d70] dark:text-blue-400 flex items-center justify-center">
              <span class="material-symbols-rounded text-[18px]">account_balance</span>
            </div>
            <p class="text-[11px] font-bold text-[#1d3d70] dark:text-slate-200 uppercase">Quản trị trực tiếp</p>
          </div>
          <h3 class="text-xl font-bold text-[#1d3d70] dark:text-white mb-2">Hội đồng quản trị</h3>
          <p class="text-[11px] font-medium text-slate-500 dark:text-slate-400">Cấp bậc: Lãnh đạo cấp cao nhất</p>
        </div>
      </div>
    </div>

    <!-- Security Panel -->
    <div class="bg-[#121827] rounded-[40px] p-8 lg:p-10 shadow-2xl relative overflow-hidden text-white">
      <div class="absolute top-0 right-0 w-96 h-96 bg-blue-500/5 rounded-full blur-3xl -translate-y-1/2 translate-x-1/2"></div>
      
      <div class="flex items-center gap-3 mb-8 relative z-10">
        <div class="h-0.5 w-6 bg-amber-500 rounded-full"></div>
        <h3 class="text-sm font-bold text-amber-500 uppercase tracking-wider">Bảo mật tài khoản</h3>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-6 relative z-10">
        <!-- Item: Đổi mật khẩu -->
        <button @click="isChangePasswordModalOpen = true" class="w-full flex items-center justify-between p-6 rounded-3xl bg-white/[0.03] hover:bg-white/10 border border-white/5 transition-all duration-300 group hover:border-white/10 hover:shadow-[0_4px_24px_-4px_rgba(251,191,36,0.1)]">
          <div class="flex items-center gap-5">
            <div class="w-12 h-12 rounded-2xl bg-amber-500/10 flex items-center justify-center border border-amber-500/20 group-hover:bg-amber-500/20 transition-colors">
              <span class="material-symbols-rounded text-amber-500 text-[24px]">password</span>
            </div>
            <div class="text-left">
              <p class="text-[15px] font-bold text-white mb-1 group-hover:text-amber-400 transition-colors">Đổi mật khẩu</p>
              <p class="text-[11px] text-slate-400 font-medium">Lần cuối thay đổi: 30 ngày trước</p>
            </div>
          </div>
          <span class="material-symbols-rounded text-slate-600 text-[24px] group-hover:text-amber-400 group-hover:translate-x-1 transition-all">chevron_right</span>
        </button>
        
        <!-- Item: Thay đổi thông tin cá nhân -->
        <button @click="isChangeProfileModalOpen = true" class="w-full flex items-center justify-between p-6 rounded-3xl bg-white/[0.03] hover:bg-white/10 border border-white/5 transition-all duration-300 group hover:border-white/10 hover:shadow-[0_4px_24px_-4px_rgba(251,191,36,0.1)]">
          <div class="flex items-center gap-5">
             <div class="w-12 h-12 rounded-2xl bg-blue-500/10 flex items-center justify-center border border-blue-500/20 group-hover:bg-blue-500/20 transition-colors">
              <span class="material-symbols-rounded text-blue-400 text-[24px]">manage_accounts</span>
            </div>
            <div class="text-left">
              <p class="text-[15px] font-bold text-white mb-1 group-hover:text-amber-400 transition-colors">Thay đổi thông tin cá nhân</p>
              <p class="text-[11px] text-slate-400 font-medium">Cập nhật số điện thoại, địa chỉ, v.v.</p>
            </div>
          </div>
          <span class="material-symbols-rounded text-slate-600 text-[24px] group-hover:text-amber-400 group-hover:translate-x-1 transition-all">chevron_right</span>
        </button>
      </div>
    </div>

  </div>
  <!-- Modal Đổi Mật Khẩu -->
  <teleport to="body">
    <div v-if="isChangePasswordModalOpen" class="fixed inset-0 z-[100] flex items-center justify-center">
      <!-- Backdrop -->
      <div class="absolute inset-0 bg-[#0f172a]/80 backdrop-blur-sm transition-opacity" @click="isChangePasswordModalOpen = false"></div>
      
      <!-- Modal Content -->
      <div class="bg-[#121827] border border-white/10 rounded-[32px] shadow-2xl w-[90%] max-w-md relative z-10 overflow-hidden animate-zoom-in">
        <div class="p-8">
          <div class="flex items-center gap-4 mb-8">
            <div class="w-12 h-12 rounded-2xl bg-amber-500/10 flex items-center justify-center border border-amber-500/20">
              <span class="material-symbols-rounded text-amber-500 text-[24px]">key</span>
            </div>
            <div>
              <h3 class="text-xl font-bold text-white tracking-tight">Cập nhật Mật Khẩu</h3>
              <p class="text-[12px] text-slate-400 font-medium">Đảm bảo tài khoản của bạn luôn an toàn</p>
            </div>
          </div>

          <div class="space-y-5">
            <div>
              <label class="block text-[11px] font-bold text-slate-300 uppercase tracking-widest pl-1 mb-2">Mật khẩu hiện tại</label>
              <div class="relative">
                <input type="password" placeholder="••••••••" class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white text-sm focus:outline-none focus:border-amber-500/50 focus:ring-1 focus:ring-amber-500/50 transition-all">
                <span class="material-symbols-rounded absolute right-4 top-1/2 -translate-y-1/2 text-slate-500 text-[18px]">visibility_off</span>
              </div>
            </div>

            <div>
              <label class="block text-[11px] font-bold text-slate-300 uppercase tracking-widest pl-1 mb-2">Mật khẩu mới</label>
              <div class="relative">
                <input type="password" placeholder="••••••••" class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white text-sm focus:outline-none focus:border-amber-500/50 focus:ring-1 focus:ring-amber-500/50 transition-all">
                <span class="material-symbols-rounded absolute right-4 top-1/2 -translate-y-1/2 text-slate-500 text-[18px]">visibility_off</span>
              </div>
              <p class="text-[10px] text-slate-500 mt-2 pl-1">Phải dài ít nhất 8 ký tự với chữ số & ký tự đặc biệt.</p>
            </div>

            <div>
              <label class="block text-[11px] font-bold text-slate-300 uppercase tracking-widest pl-1 mb-2">Xác nhận mật khẩu mới</label>
              <div class="relative">
                <input type="password" placeholder="••••••••" class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white text-sm focus:outline-none focus:border-amber-500/50 focus:ring-1 focus:ring-amber-500/50 transition-all">
              </div>
            </div>
          </div>

          <div class="mt-8 flex gap-3">
            <button @click="isChangePasswordModalOpen = false" class="flex-1 py-3 px-4 rounded-xl border border-white/10 text-[12px] font-bold text-white hover:bg-white/5 transition-colors">
              Hủy bỏ
            </button>
            <button @click="isChangePasswordModalOpen = false" class="flex-1 py-3 px-4 rounded-xl bg-gradient-to-r from-amber-500 to-amber-600 text-[12px] font-bold text-white hover:opacity-90 shadow-lg shadow-amber-500/25 transition-all">
              Lưu thay đổi
            </button>
          </div>
        </div>
      </div>
    </div>
  </teleport>

  <!-- Modal Thay Đổi Thông Tin Cá Nhân -->
  <teleport to="body">
    <div v-if="isChangeProfileModalOpen" class="fixed inset-0 z-[100] flex items-center justify-center">
      <!-- Backdrop -->
      <div class="absolute inset-0 bg-[#0f172a]/60 backdrop-blur-sm transition-opacity" @click="isChangeProfileModalOpen = false"></div>
      
      <!-- Modal Content (Sáng / Light Theme) -->
      <div class="bg-white rounded-[32px] shadow-2xl w-[90%] max-w-xl relative z-10 overflow-hidden animate-zoom-in border border-slate-100">
        
        <!-- Header -->
        <div class="px-8 py-6 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-blue-50 flex items-center justify-center text-blue-600">
              <span class="material-symbols-rounded">manage_accounts</span>
            </div>
            <div>
              <h3 class="text-[17px] font-bold text-[#1d3d70] tracking-tight">Cập Nhật Hồ Sơ</h3>
              <p class="text-[11px] text-slate-500 font-medium">Thay đổi thông tin liên lạc và nhân dạng.</p>
            </div>
          </div>
          <button @click="isChangeProfileModalOpen = false" class="w-8 h-8 rounded-full bg-slate-100 text-slate-400 hover:text-slate-600 hover:bg-slate-200 flex items-center justify-center transition-colors">
             <span class="material-symbols-rounded text-[20px]">close</span>
          </button>
        </div>

        <!-- Body -->
        <div class="p-8">
          
          <!-- Avatar Section -->
          <div class="flex flex-col items-center justify-center mb-8 relative">
            
            <!-- Hidden Input File -->
            <input type="file" ref="fileInput" accept="image/*" class="hidden" @change="onFileChange">

            <div class="relative group cursor-pointer" @click="triggerUpload">
              <!-- Avatar -->
              <div class="w-24 h-24 rounded-full overflow-hidden border-4 border-slate-50 shadow-md transition-transform duration-300 group-hover:scale-105">
                <img :src="avatarUrl" alt="Avatar" class="w-full h-full object-cover">
              </div>
              <!-- Hover Overlay -->
              <div class="absolute inset-0 rounded-full bg-black/40 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                <span class="material-symbols-rounded text-white">photo_camera</span>
              </div>
            </div>
            
            <button @click="triggerUpload" class="mt-3 flex items-center justify-center gap-1.5 text-slate-400 hover:text-blue-600 transition-colors group/edit">
              <span class="text-[11px] font-bold tracking-widest uppercase leading-none translate-y-[1px]">Ảnh đại diện</span>
              <span class="material-symbols-rounded text-[14px] opacity-70 group-hover/edit:opacity-100 leading-none">edit</span>
            </button>
          </div>

          <!-- Form Area -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div class="md:col-span-2">
              <label class="block text-[11px] font-bold text-slate-400 uppercase tracking-widest pl-1 mb-2">Họ và tên</label>
              <div class="relative">
                <input type="text" :value="ceoData?.fullName || 'Nguyễn Minh Triết'" class="w-full bg-white border border-slate-200 rounded-xl px-4 py-3 text-[#1d3d70] text-sm focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition-all font-semibold">
                <span class="material-symbols-rounded absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 text-[18px]">person</span>
              </div>
            </div>
            
            <div>
              <label class="block text-[11px] font-bold text-slate-400 uppercase tracking-widest pl-1 mb-2">Ngày sinh</label>
              <CalendarCustom v-model="birthDate" placeholder="Chọn ngày sinh" class="w-full" />
            </div>

            <div>
              <label class="block text-[11px] font-bold text-slate-400 uppercase tracking-widest pl-1 mb-2">Số điện thoại bảo mật</label>
              <div class="relative">
                <input type="text" value="(+84) 90 123 4587" class="w-full bg-white border border-slate-200 rounded-xl px-4 py-3 text-[#1d3d70] text-sm focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition-all font-semibold">
                <span class="material-symbols-rounded absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 text-[18px]">smartphone</span>
              </div>
            </div>

            <div class="md:col-span-2">
              <label class="block text-[11px] font-bold text-slate-400 uppercase tracking-widest pl-1 mb-2">Địa chỉ hiện tại</label>
              <div class="relative">
                <input type="text" value="Q.1, TP. Hồ Chí Minh" class="w-full bg-white border border-slate-200 rounded-xl px-4 py-3 text-[#1d3d70] text-sm focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition-all font-semibold">
                <span class="material-symbols-rounded absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 text-[18px]">location_on</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Footer Actions -->
        <div class="px-8 py-5 border-t border-slate-100 bg-slate-50 flex gap-3 justify-end items-center">
          <button @click="isChangeProfileModalOpen = false" class="py-2.5 px-6 rounded-xl text-[13px] font-bold text-slate-500 hover:bg-slate-200 transition-colors">
            Hủy bỏ
          </button>
          <button @click="isChangeProfileModalOpen = false" class="py-2.5 px-6 rounded-xl bg-blue-600 text-[13px] font-bold text-white hover:bg-blue-700 shadow-lg shadow-blue-500/25 transition-all">
            Cập nhật hồ sơ
          </button>
        </div>

      </div>
    </div>
    </teleport>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import CalendarCustom from '@/components/CalendarCustom.vue';
import { mockEmployees, mockPositions } from '@/mock-data/index.js';

const ceoData = computed(() => {
  const emps = mockEmployees;
  const ceo = emps.find(e => e.positionId === 1) || emps[0];
  if(ceo) {
    const posName = mockPositions.getById(ceo.positionId)?.positionName || 'Giám đốc';
    return {
      ...ceo,
      positionName: posName
    };
  }
  return null;
});

const isChangePasswordModalOpen = ref(false);
const isChangeProfileModalOpen = ref(false);
const birthDate = ref('1982-05-15');

const avatarUrl = ref(ceoData.value?.avatarUrl || 'https://i.pravatar.cc/300?img=3');
const fileInput = ref(null);

const triggerUpload = () => {
  if (fileInput.value) {
    fileInput.value.click();
  }
};

const onFileChange = (event) => {
  const file = event.target.files[0];
  if (file) {
    // Generate a quick local preview URL for the selected image
    avatarUrl.value = URL.createObjectURL(file);
  }
};
</script>

<style scoped>
@keyframes fade-in {
  from { opacity: 0; transform: translateY(10px); }
  to { opacity: 1; transform: translateY(0); }
}
.animate-fade-in {
  animation: fade-in 0.4s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}

@keyframes zoom-in {
  from { opacity: 0; transform: scale(0.95); }
  to { opacity: 1; transform: scale(1); }
}
.animate-zoom-in {
  animation: zoom-in 0.25s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}
</style>
