<template>
  <div>
    <!-- Modal Gán ca / Edit Override -->
    <div v-if="store.modals.overrideEdit.open" class="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @click="store.closeModal('overrideEdit')"></div>
      <div class="relative bg-white rounded-3xl shadow-2xl w-full max-w-md overflow-hidden animate-in fade-in zoom-in duration-200">
        <div class="p-6 border-b border-slate-100 flex items-center justify-between">
          <h3 class="text-xl font-bold text-slate-900">
            {{ store.activeTab === 2 ? 'Ghi đè ca làm việc' : 'Gán ca cho 1 ngày' }}
          </h3>
          <button @click="store.closeModal('overrideEdit')" class="text-slate-400 hover:text-slate-600">
             <span class="material-symbols-outlined">close</span>
          </button>
        </div>
        
        <div class="p-6 space-y-4">
          <div class="flex items-center gap-4 p-3 bg-blue-50/50 rounded-2xl border border-blue-100">
             <div class="w-10 h-10 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center font-bold shadow-sm">
               {{ store.modals.overrideEdit.data.emp?.full_name?.[0] || '?' }}
             </div>
             <div>
               <p class="text-sm font-black text-slate-900">{{ store.modals.overrideEdit.data.emp?.full_name }}</p>
               <p class="text-[11px] font-bold text-blue-600 uppercase tracking-tight">Chỉ áp dụng cho: {{ formatDate(store.modals.overrideEdit.data.date) }}</p>
             </div>
          </div>

          <div>
            <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">Chọn Ca</label>
            <div class="grid grid-cols-2 gap-2 mt-2">
               <button 
                 v-for="s in store.shifts" :key="s.shift_type_id"
                 @click="selectedShiftId = s.shift_type_id"
                 class="p-3 rounded-xl border-2 text-left transition-all"
                 :class="selectedShiftId === s.shift_type_id ? 'border-blue-600 bg-blue-50 text-blue-700' : 'border-slate-100 text-slate-600 hover:border-slate-200'"
               >
                 <div class="text-sm font-black">{{ s.shift_code || s.code }}</div>
                 <div class="text-[10px] font-medium opacity-70">{{ s.shift_name || s.name }}</div>
               </button>
            </div>
          </div>

          <div v-if="store.isPublished || store.activeTab === 2">
            <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">Lý do điều chỉnh</label>
            <textarea 
              v-model="reason"
              rows="2"
              class="w-full mt-2 bg-slate-50 border border-slate-200 rounded-xl p-3 text-sm outline-none focus:ring-2 focus:ring-blue-500"
              placeholder="E.g. Đổi ca, tăng cường, nghỉ phép..."
            ></textarea>
          </div>
        </div>

        <div class="p-6 bg-slate-50 border-t border-slate-100 flex items-center gap-3">
          <button @click="store.closeModal('overrideEdit')" class="flex-1 py-3 text-sm font-bold text-slate-600 hover:bg-white rounded-xl transition-all">Hủy</button>
          <button 
            @click="handleSave"
            :disabled="!selectedShiftId || store.submitting"
            class="flex-1 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-sm font-bold shadow-lg shadow-blue-200 transition-all disabled:opacity-50"
          >
            {{ store.submitting ? 'Đang lưu...' : 'Xác nhận' }}
          </button>
        </div>
      </div>
    </div>

    <!-- Modal Suggestion Preview (Phase 3) -->
    <div v-if="store.modals.suggestionPreview.open" class="fixed inset-0 z-[70] flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @click="store.closeModal('suggestionPreview')"></div>
      <div class="relative bg-white rounded-3xl shadow-2xl w-full max-w-4xl max-h-[85vh] overflow-hidden flex flex-col animate-in fade-in zoom-in duration-200">
        <div class="p-6 border-b border-slate-100 flex items-center justify-between bg-blue-50/50">
          <div>
            <h3 class="text-xl font-bold text-slate-900 flex items-center gap-2">
              <span class="material-symbols-outlined text-blue-600">auto_awesome</span>
              {{ store.modals.suggestionPreview.data.title }}
            </h3>
            <p class="text-xs text-slate-500 mt-1">{{ store.modals.suggestionPreview.data.description }}</p>
          </div>
          <button @click="store.closeModal('suggestionPreview')" class="text-slate-400 hover:text-slate-600">
             <span class="material-symbols-outlined">close</span>
          </button>
        </div>

        <div class="flex-1 overflow-y-auto p-6">
          <!-- Summary Header -->
          <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
             <div class="bg-blue-50 p-3 rounded-2xl border border-blue-100 text-center">
                <p class="text-[10px] font-bold text-blue-600 uppercase">Sẽ thay đổi</p>
                <p class="text-2xl font-black text-blue-700">{{ store.modals.suggestionPreview.data.summary.affected }} ô</p>
             </div>
             <div class="bg-slate-50 p-3 rounded-2xl border border-slate-100 text-center">
                <p class="text-[10px] font-bold text-slate-500 uppercase">Bị bỏ qua</p>
                <p class="text-2xl font-black text-slate-700">{{ store.modals.suggestionPreview.data.summary.ignored }} ô</p>
             </div>
             <div class="bg-emerald-50 p-3 rounded-2xl border border-emerald-100 text-center">
                <p class="text-[10px] font-bold text-emerald-600 uppercase">Hiệu quả</p>
                <p class="text-2xl font-black text-emerald-700">+{{ Math.round((store.modals.suggestionPreview.data.summary.affected / 70) * 100) || 0 }}%</p>
             </div>
             <div class="bg-rose-50 p-3 rounded-2xl border border-rose-100 text-center">
                <p class="text-[10px] font-bold text-rose-600 uppercase">Conflict</p>
                <p class="text-2xl font-black text-rose-700">0</p>
             </div>
          </div>

          <!-- Diff Table -->
          <div class="rounded-2xl border border-slate-100 overflow-hidden">
            <table class="w-full text-left text-sm">
              <thead class="bg-slate-50 text-slate-500 font-bold">
                <tr>
                  <th class="p-4">Nhân sự</th>
                  <th class="p-4">Ngày</th>
                  <th class="p-4">Hiện tại</th>
                  <th class="p-4">Đề xuất mới</th>
                  <th class="p-4">Lý do</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100">
                <tr v-for="(item, idx) in store.modals.suggestionPreview.data.items" :key="idx" class="hover:bg-slate-50/50">
                  <td class="p-4">
                    <div class="flex items-center gap-2">
                       <div class="w-6 h-6 rounded-full bg-slate-100 text-[10px] flex items-center justify-center font-bold">
                          {{ item.employee.full_name[0] }}
                       </div>
                       <span class="font-bold text-slate-700">{{ item.employee.full_name }}</span>
                    </div>
                  </td>
                  <td class="p-4 text-slate-500">{{ item.date }}</td>
                  <td class="p-4">
                    <span class="text-slate-400 italic">Trống</span>
                  </td>
                  <td class="p-4">
                    <div class="flex items-center gap-2">
                       <span class="px-2 py-1 bg-blue-50 text-blue-700 rounded-lg font-black text-xs">{{ item.new_shift.shift_code }}</span>
                       <span class="text-[10px] text-slate-500">{{ item.new_shift.start_time?.slice(0,5) }} - {{ item.new_shift.end_time?.slice(0,5) }}</span>
                    </div>
                  </td>
                  <td class="p-4">
                    <span class="text-[10px] bg-slate-100 px-2 py-1 rounded text-slate-600">{{ item.reason }}</span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <div class="p-6 bg-slate-50 border-t border-slate-100 flex items-center justify-between">
          <div class="flex items-center gap-2 text-rose-600">
             <span class="material-symbols-outlined text-[18px]">info</span>
             <span class="text-xs font-bold uppercase italic">Suggestion won't overwrite manual overrides.</span>
          </div>
          <div class="flex items-center gap-3">
            <button @click="store.closeModal('suggestionPreview')" class="px-6 py-3 text-sm font-bold text-slate-600 hover:bg-white rounded-xl transition-all">Hủy</button>
            <button 
              @click="handleApplySuggestion"
              :disabled="store.submitting"
              class="px-8 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-sm font-bold shadow-lg shadow-blue-200 transition-all disabled:opacity-50 flex items-center gap-2"
            >
              <span class="material-symbols-outlined text-[20px]">check_circle</span>
              {{ store.submitting ? 'Đang áp dụng...' : 'Áp dụng ngay' }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal Export Settings (Phase 4) -->
    <div v-if="store.modals.exportSettings.open" class="fixed inset-0 z-[80] flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @click="store.closeModal('exportSettings')"></div>
      <div class="relative bg-white rounded-3xl shadow-2xl w-full max-w-sm overflow-hidden animate-in fade-in zoom-in duration-200">
        <div class="p-6 border-b border-slate-100 flex items-center justify-between">
          <h3 class="text-lg font-bold text-slate-900">Xuất dữ liệu lịch</h3>
          <button @click="store.closeModal('exportSettings')" class="text-slate-400 hover:text-slate-600">
             <span class="material-symbols-outlined">close</span>
          </button>
        </div>
        
        <div class="p-6 space-y-6">
           <div>
              <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">Định dạng file</label>
              <div class="grid grid-cols-2 gap-3 mt-2">
                 <button class="p-4 rounded-2xl border-2 border-blue-600 bg-blue-50 text-blue-700 text-center">
                    <span class="material-symbols-outlined text-3xl mb-1">description</span>
                    <p class="text-xs font-bold uppercase">Excel (.xlsx)</p>
                 </button>
                 <button class="p-4 rounded-2xl border-2 border-slate-100 hover:border-slate-200 text-slate-400 text-center">
                    <span class="material-symbols-outlined text-3xl mb-1">csv</span>
                    <p class="text-xs font-bold uppercase">CSV (.csv)</p>
                 </button>
              </div>
           </div>

           <div class="p-4 bg-slate-50 rounded-2xl border border-slate-100">
              <p class="text-[10px] font-bold text-slate-400 uppercase mb-2">Thông tin xuất</p>
              <div class="space-y-2">
                 <div class="flex justify-between text-xs">
                    <span class="text-slate-500">Phòng ban:</span>
                    <span class="font-bold text-slate-700">{{ store.selectedDepartmentId }}</span>
                 </div>
                 <div class="flex justify-between text-xs">
                    <span class="text-slate-500">Thời gian:</span>
                    <span class="font-bold text-slate-700">Tuần hiện tại</span>
                 </div>
                 <div class="flex justify-between text-xs">
                    <span class="text-slate-500">Số nhân sự:</span>
                    <span class="font-bold text-slate-700">{{ store.totalEmployees }}</span>
                 </div>
              </div>
           </div>
        </div>

        <div class="p-4 bg-slate-50 border-t border-slate-100 flex gap-3">
           <button @click="store.closeModal('exportSettings')" class="flex-1 py-3 text-sm font-bold text-slate-600 hover:bg-white rounded-xl transition-all">Hủy</button>
           <button 
             @click="handleExport"
             class="flex-1 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-sm font-bold shadow-lg shadow-blue-200 transition-all flex items-center justify-center gap-2"
           >
             <span class="material-symbols-outlined text-[20px]">download</span>
             Tải xuống
           </button>
        </div>
      </div>
    </div>

    <!-- Modal Confirm General -->
    <div v-if="store.modals.confirmAction.open" class="fixed inset-0 z-[60] flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @click="store.closeModal('confirmAction')"></div>
      <div class="relative bg-white rounded-3xl shadow-2xl w-full max-w-sm overflow-hidden animate-in fade-in slide-in-from-bottom-4 duration-300">
        <div class="p-8 text-center">
          <div class="w-16 h-16 bg-amber-50 text-amber-600 rounded-full flex items-center justify-center mx-auto mb-4">
             <span class="material-symbols-outlined text-3xl">help</span>
          </div>
          <h3 class="text-xl font-bold text-slate-900">{{ store.modals.confirmAction.data?.title }}</h3>
          <p class="text-slate-500 text-sm mt-2 leading-relaxed">{{ store.modals.confirmAction.data?.message }}</p>
        </div>
        <div class="p-4 bg-slate-50 flex items-center gap-3">
           <button @click="store.closeModal('confirmAction')" class="flex-1 py-3 text-sm font-bold text-slate-500 hover:bg-white rounded-xl transition-all">Hủy</button>
           <button 
             @click="confirmAction"
             :disabled="store.submitting"
             class="flex-1 py-3 bg-slate-900 hover:bg-slate-800 text-white rounded-xl text-sm font-bold shadow-xl transition-all disabled:opacity-50"
           >
             Đồng ý
           </button>
        </div>
      </div>
    </div>

    <!-- Toast Success -->
    <div v-if="showToast" class="fixed bottom-6 right-6 z-[100] animate-in slide-in-from-right-10 duration-300">
       <div class="bg-slate-900 text-white px-6 py-4 rounded-2xl shadow-2xl flex items-center gap-3 border border-slate-800">
          <div class="w-8 h-8 rounded-full bg-emerald-500 flex items-center justify-center">
             <span class="material-symbols-outlined text-white text-[18px]">check</span>
          </div>
          <div>
            <p class="text-sm font-bold">Thành công!</p>
            <p class="text-[10px] text-slate-400 font-medium uppercase">Dữ liệu đã được cập nhật</p>
          </div>
       </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue';
import { useScheduleStore } from '@/stores/useScheduleStore';

const store = useScheduleStore();
const selectedShiftId = ref(null);
const reason = ref('');
const showToast = ref(false);

watch(() => store.modals.overrideEdit.open, (val) => {
  if (val) {
    selectedShiftId.value =
      store.modals.overrideEdit.data.current?.shift_type_id ||
      store.modals.overrideEdit.data.override?.shift_type_id ||
      null;
    reason.value = store.modals.overrideEdit.data.override?.reason || '';
  }
});

const formatDate = (date) => new Date(date).toLocaleDateString('vi-VN', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });

const triggerToast = () => {
  showToast.value = true;
  setTimeout(() => { showToast.value = false; }, 3000);
};

const handleApplySuggestion = async () => {
  const modalData = store.modals.suggestionPreview.data;
  try {
    let res;
    if (modalData.type === 'COPY_WEEK') {
      res = await store.copyScheduleWeek(modalData);
    } else {
      res = await store.applySuggestionBatch(modalData.items || []);
    }
    if (res.success) {
      store.closeModal('suggestionPreview');
      triggerToast();
    }
  } catch (error) {
    alert(error.message);
  }
};

const handleExport = () => {
  // Logic to trigger real export would go here
  // For now, simulate a successful download trigger
  store.closeModal('exportSettings');
  triggerToast();
};

const handleSave = async () => {
  const { emp, date } = store.modals.overrideEdit.data;
  
  try {
    if (store.isPublished || store.activeTab === 2) {
      await store.createOrUpdateOverride({
        employee_id: emp.employee_id,
        work_date: date,
        shift_type_id: selectedShiftId.value,
        reason: reason.value
      });
    } else {
      await store.assignShift({
        employee_id: emp.employee_id,
        work_date: date,
        shift_type_id: selectedShiftId.value
      });
    }
    store.closeModal('overrideEdit');
    triggerToast();
  } catch (error) {
    alert(error.message);
  }
};

const confirmAction = async () => {
  const { type, shift_type_id, target, employeeId, date } = store.modals.confirmAction.data || {};
  try {
    if (type === 'publish') {
      await store.publishSchedule();
    } else if (type === 'copy_week') {
      await store.copyScheduleWeek();
    } else if (type === 'delete_override') {
      await store.deleteOverride(employeeId, date);
    } else if (type === 'bulk') {
      await store.bulkAssignShift({ shift_type_id, target });
    } else if (type === 'assign') {
      if (store.modals.confirmAction.data.action) {
        await store.modals.confirmAction.data.action();
      }
    }
    store.closeModal('confirmAction');
    triggerToast();
  } catch (error) {
    console.error('Action failed:', error);
    alert(error.message || 'Thao tác thất bại');
  }
};
</script>
