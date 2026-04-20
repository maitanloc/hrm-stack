<template>
  <div class="px-6 py-6 bg-[#F8FAFC] min-h-screen">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
      <div>
        <h1 class="text-2xl font-bold text-slate-900 tracking-tight flex items-center gap-2">
          <span class="material-symbols-outlined text-indigo-600">upload_file</span>
          Import Log Chấm Công
        </h1>
        <p class="text-slate-500 text-sm mt-1">Tải lên dữ liệu thô từ máy chấm công để hệ thống xử lý.</p>
      </div>
      <div class="flex space-x-3">
         <button @click="triggerFileUpload" class="flex items-center px-4 py-2 bg-indigo-600 text-white rounded-xl shadow-lg shadow-indigo-200 hover:bg-indigo-700 transition font-bold text-sm">
           <span class="material-symbols-outlined mr-2 text-[20px]">add</span>
           Tải lên file (.csv)
         </button>
         <input type="file" ref="fileInput" @change="handleFileUpload" class="hidden" accept=".csv" />
      </div>
    </div>
    
    <!-- Empty State -->
    <div v-if="!parsedData || parsedData.length === 0" class="bg-white rounded-2xl shadow-sm border border-slate-200 p-16 text-center mb-6">
       <div class="w-20 h-20 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-4">
         <span class="material-symbols-outlined text-slate-300 text-4xl">cloud_upload</span>
       </div>
       <h2 class="text-lg font-bold text-slate-800">Chưa có dữ liệu xem trước</h2>
       <p class="text-slate-500 text-sm max-w-xs mx-auto mt-2">Vui lòng chọn file CSV chứa mã nhân viên và thời gian check-in/out để bắt đầu.</p>
    </div>

    <!-- Preview Table -->
    <div v-if="parsedData && parsedData.length > 0" class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden mb-6 animate-in fade-in duration-300">
      <div class="p-4 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
        <h2 class="font-bold text-slate-700 flex items-center gap-2">
          <span class="material-symbols-outlined text-[18px]">preview</span>
          Dữ liệu đọc được ({{parsedData.length}} dòng)
        </h2>
        <button v-if="!isImporting" @click="confirmImport" class="px-4 py-2 bg-emerald-600 text-white rounded-lg shadow-md shadow-emerald-100 hover:bg-emerald-700 transition font-bold text-sm">
          TIẾN HÀNH IMPORT
        </button>
      </div>
      <div class="overflow-x-auto" style="max-height: 400px;">
        <table class="min-w-full divide-y divide-slate-200">
          <thead class="bg-slate-50 sticky top-0 z-10">
            <tr>
              <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Mã NV</th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Thời gian</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-slate-100">
            <tr v-for="(row, idx) in parsedData.slice(0, 100)" :key="idx" class="hover:bg-slate-50 transition-colors">
               <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-slate-900">{{ row[0] }}</td>
               <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600">{{ row[1] }}</td>
            </tr>
            <tr v-if="parsedData.length > 100">
                <td colspan="2" class="px-6 py-4 text-center text-xs font-bold text-slate-400 bg-slate-50/50">Đang hiển thị 100 dòng đầu tiên...</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
    
    <!-- Result Section -->
    <div v-if="importResult" class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 animate-in slide-in-from-bottom-4 duration-500">
       <h2 class="text-xl font-bold mb-6 flex items-center gap-2" :class="importResult.error_count > 0 ? 'text-amber-600' : 'text-emerald-600'">
          <span class="material-symbols-outlined">{{ importResult.error_count > 0 ? 'warning' : 'check_circle' }}</span>
          Kết quả xử lý
       </h2>
       <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
          <div class="p-4 bg-emerald-50 rounded-xl border border-emerald-100 flex items-center justify-between">
              <div>
                <p class="text-xs font-bold text-emerald-700 uppercase tracking-wider">Thành công</p>
                <p class="text-2xl font-black text-emerald-600">{{ importResult.imported_count }}</p>
              </div>
              <span class="material-symbols-outlined text-emerald-300 text-4xl">done_all</span>
          </div>
          <div class="p-4 bg-rose-50 rounded-xl border border-rose-100 flex items-center justify-between">
              <div>
                <p class="text-xs font-bold text-rose-700 uppercase tracking-wider">Số lỗi</p>
                <p class="text-2xl font-black text-rose-600">{{ importResult.error_count }}</p>
              </div>
              <span class="material-symbols-outlined text-rose-300 text-4xl">error</span>
          </div>
       </div>
       <div v-if="importResult.errors && importResult.errors.length > 0" class="mt-4 p-4 bg-slate-50 rounded-xl border border-slate-200 max-h-64 overflow-y-auto">
          <p class="text-xs font-bold text-slate-400 uppercase mb-2">Chi tiết lỗi:</p>
          <ul class="space-y-1 text-sm text-rose-600 font-medium">
             <li v-for="(err, i) in importResult.errors" :key="i" class="flex items-start gap-2">
               <span class="text-[10px] mt-1">•</span>
               {{ err }}
             </li>
          </ul>
       </div>
    </div>

    <!-- Loading Overlay -->
    <div v-if="isImporting" class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm z-[100] flex items-center justify-center">
        <div class="bg-white p-8 rounded-3xl shadow-2xl flex flex-col items-center max-w-xs w-full">
            <div class="w-12 h-12 border-4 border-indigo-600 border-t-transparent rounded-full animate-spin mb-4"></div>
            <p class="font-black text-slate-900 text-lg">Đang xử lý...</p>
            <p class="text-slate-500 text-sm text-center mt-2">Vui lòng không đóng trình duyệt trong quá trình import dữ liệu.</p>
        </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { apiRequest } from '@/services/beApi.js'

const fileInput = ref(null)
const parsedData = ref([])
const isImporting = ref(false)
const importResult = ref(null)

const triggerFileUpload = () => {
    fileInput.value.click();
}

const handleFileUpload = (event) => {
    const file = event.target.files[0]
    if (!file) return;
    
    const reader = new FileReader();
    reader.onload = (e) => {
        const text = e.target.result;
        const rows = text.split('\n')
            .map(row => row.split(',').map(c => c.trim()))
            .filter(r => r.length >= 2 && r[0]);
            
        // Simple header skip
        if(rows.length > 0 && isNaN(parseInt(rows[0][0].replace(/\D/g,'')))) {
            rows.shift();
        }
        
        parsedData.value = rows;
        importResult.value = null;
        event.target.value = ''; 
    };
    reader.readAsText(file);
}

const confirmImport = async () => {
    if(!parsedData.value || parsedData.value.length === 0) return;
    
    importResult.value = null;
    isImporting.value = true;
    try {
        const res = await apiRequest('/timesheet/import', {
            method: 'POST', 
            body: { rows: parsedData.value }
        });
        if(res.success) {
            importResult.value = res.data;
        } else {
            alert("Lỗi: " + (res.message || "Không thể thực hiện import"));
        }
    } catch (e) {
        console.error("Import error:", e);
        alert("Lỗi hệ thống khi import: " + (e.message || "Vui lòng thử lại sau."));
    } finally {
        isImporting.value = false;
    }
}
</script>
