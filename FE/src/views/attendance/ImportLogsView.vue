<template>
  <div class="px-6 py-6">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6">
      <h1 class="text-2xl font-bold text-gray-900 border-l-4 border-indigo-600 pl-3">Import Log Chấm Công</h1>
      <div class="mt-4 md:mt-0 flex space-x-3">
         <button @click="triggerFileUpload" class="flex items-center px-4 py-2 bg-indigo-600 text-white rounded shadow hover:bg-indigo-700 transition">
           <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
           Tải lên file (.csv, .xlsx)
         </button>
         <input type="file" ref="fileInput" @change="handleFileUpload" class="hidden" accept=".csv, .xlsx, .xls" />
      </div>
    </div>
    
    <div v-if="parsedData.length > 0" class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden mb-6">
      <div class="p-4 border-b border-gray-100 flex justify-between items-center bg-gray-50">
        <h2 class="font-semibold text-gray-700">Dữ liệu đọc được ({{parsedData.length}} dòng)</h2>
        <button v-if="!isImporting" @click="confirmImport" class="px-4 py-2 bg-green-600 text-white rounded shadow hover:bg-green-700 transition">Tiến hành Import Dữ Liệu</button>
      </div>
      <div class="overflow-x-auto" style="max-height: 400px;">
        <table class="min-w-full divide-y divide-gray-200 sticky-header">
          <thead class="bg-gray-50 sticky top-0">
            <tr>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Mã NV</th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Thời gian</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="(row, idx) in parsedData.slice(0, 100)" :key="idx" class="hover:bg-gray-50">
               <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ row[0] }}</td>
               <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ row[1] }}</td>
            </tr>
            <tr v-if="parsedData.length > 100">
                <td colspan="2" class="px-6 py-4 text-center text-sm text-gray-500 bg-gray-50">Xem trước 100 dòng đầu...</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
    
    <div v-if="importResult" class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
       <h2 class="text-xl font-bold mb-4" :class="importResult.error_count > 0 ? 'text-orange-600' : 'text-green-600'">Kết quả Import</h2>
       <div class="grid grid-cols-2 content-start gap-4 text-sm font-medium">
          <div class="p-4 bg-green-50 rounded-lg shadow-sm border border-green-100 flex items-center justify-between">
              <span class="text-green-800">Thành công:</span>
              <span class="text-xl text-green-600">{{ importResult.imported_count }}</span>
          </div>
          <div class="p-4 bg-red-50 rounded-lg shadow-sm border border-red-100 flex items-center justify-between">
              <span class="text-red-800">Lỗi:</span>
              <span class="text-xl text-red-600">{{ importResult.error_count }}</span>
          </div>
       </div>
       <div v-if="importResult.errors && importResult.errors.length > 0" class="mt-4 p-4 bg-gray-50 rounded border border-gray-200 max-h-64 overflow-y-auto">
          <ul class="list-disc pl-5 space-y-1 text-sm text-red-600">
             <li v-for="(err, i) in importResult.errors" :key="i">{{ err }}</li>
          </ul>
       </div>
    </div>
  </div>
</template>
<script setup>
import { ref } from 'vue'
// Normally we'd use XLSX library here, simulating parse for demo
import { apiRequest as requestApi } from '@/services/beApi.js'


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
        const rows = text.split('\n').map(row => row.split(',').map(c => c.trim())).filter(r => r.length >= 2 && r[0]);
        // Remove header if first row looks like text
        if(rows.length > 0 && isNaN(parseInt(rows[0][0].replace(/\D/g,'')))) rows.shift();
        
        parsedData.value = rows;
        event.target.value = ''; 
    };
    reader.readAsText(file);
}

const confirmImport = async () => {
    if(parsedData.value.length === 0) return;
    
    importResult.value = null;
    isImporting.value = true;
    try {
        const res = await requestApi('/timesheet/import', 'POST', { rows: parsedData.value });
        if(res.success) {
            importResult.value = res.data;
            if(importResult.value.error_count === 0) {
                 alert();
            } else {
                 alert();
            }
        }
    } catch (e) {
        alert();
    } finally {
        isImporting.value = false;
    }
}
</script>
