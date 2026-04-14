<template>
  <div class="space-y-6 pb-8">
    <!-- Header Area: SaaS Enterprise Style -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 bg-transparent text-left px-1">
      <div class="bg-transparent text-left">
        <h1 class="text-xl font-bold text-[var(--sys-text-primary)] mb-0.5 tracking-tight uppercase">Quản lý Hợp đồng Phòng ban</h1>
        <p class="text-[13px] text-[var(--sys-text-secondary)] font-medium flex items-center gap-3">
          Tra cứu thông tin và thời hạn các loại hợp đồng lao động của phòng ban.
          <span class="px-2 py-0.5 bg-[var(--sys-brand-soft)] text-[var(--sys-brand-solid)] rounded-md border border-[var(--sys-brand-border)] text-[10px] font-bold uppercase tracking-widest shadow-sm">READ-ONLY ACCESS</span>
        </p>
      </div>
    </div>

    <!-- Contract Table: Compact and Synchronized -->
    <div class="bg-[var(--sys-bg-surface)] rounded-lg border border-[var(--sys-border-subtle)] shadow-sm overflow-hidden">
      <div class="overflow-x-auto custom-scrollbar">
        <table class="w-full text-left border-collapse min-w-[1000px]">
          <thead class="bg-[var(--sys-bg-page)]/40">
            <tr>
              <th class="px-6 py-4 text-[11px] font-bold text-[var(--sys-text-secondary)] border-b border-[var(--sys-border-subtle)] uppercase tracking-widest">Mã Hợp đồng</th>
              <th class="px-6 py-4 text-[11px] font-bold text-[var(--sys-text-secondary)] border-b border-[var(--sys-border-subtle)] uppercase tracking-widest">Nhân sự thực thi</th>
              <th class="px-6 py-4 text-[11px] font-bold text-[var(--sys-text-secondary)] border-b border-[var(--sys-border-subtle)] uppercase tracking-widest">Loại văn bản</th>
              <th class="px-6 py-4 text-[11px] font-bold text-[var(--sys-text-secondary)] border-b border-[var(--sys-border-subtle)] uppercase tracking-widest">Thời gian hiệu lực</th>
              <th class="px-6 py-4 text-[11px] font-bold text-[var(--sys-text-secondary)] border-b border-[var(--sys-border-subtle)] uppercase tracking-widest text-right">Trạng thái pháp lý</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-[var(--sys-border-subtle)]">
            <tr v-for="contract in contracts" :key="contract.id" class="hover:bg-[var(--sys-bg-hover)] transition-all duration-200 group">
              <td class="px-6 py-4 text-[13px] font-bold text-[var(--sys-text-primary)] tracking-tight">
                {{ contract.code }}
              </td>
              <td class="px-6 py-4">
                <div class="flex items-center gap-3">
                  <div class="w-8 h-8 rounded-md bg-[var(--sys-brand-soft)] text-[var(--sys-brand-solid)] flex items-center justify-center font-bold text-[10px] uppercase border border-[var(--sys-brand-border)]">
                    {{ contract.name.charAt(0) }}
                  </div>
                  <div class="flex flex-col">
                    <h4 class="text-[13px] font-bold text-[var(--sys-text-primary)] m-0 uppercase tracking-tight group-hover:text-[var(--sys-brand-solid)] transition-colors">{{ contract.name }}</h4>
                    <p class="text-[10px] font-bold text-[var(--sys-text-secondary)] m-0 uppercase tracking-widest opacity-60">{{ contract.position }}</p>
                  </div>
                </div>
              </td>
              <td class="px-6 py-4 text-[12px] font-bold text-[var(--sys-text-secondary)] uppercase tracking-tight">
                {{ contract.type }}
              </td>
              <td class="px-6 py-4 text-[12px] font-bold text-[var(--sys-text-primary)] opacity-80">
                {{ contract.range }}
              </td>
              <td class="px-6 py-4 text-right">
                <span :class="['px-3 py-1 rounded-md font-bold text-[10px] uppercase border shadow-sm tracking-widest', contract.status === 'Đang hiệu lực' ? 'bg-[var(--sys-success-soft)] text-[var(--sys-success-text)] border-[var(--sys-success-border)]' : 'bg-[var(--sys-warning-soft)] text-[var(--sys-warning-text)] border-[var(--sys-warning-border)]']">
                  {{ contract.status }}
                </span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { mockContracts, mockEmployees, mockPositions } from '@/mock-data/index.js'

const contracts = ref([])

const CONTRACT_TYPE_LABELS = {
  'THỬ_VIỆC': 'HĐ Thử việc',
  'CHÍNH_THỨC_1_NĂM': 'HĐ Chính thức 1 năm',
  'CHÍNH_THỨC_3_NĂM': 'HĐ Chính thức 3 năm',
  'VÔ_THỜI_HẠN': 'HĐ Vô thời hạn',
  'THỰC_TẬP': 'HĐ Thực tập',
}

const loadData = () => {
  const userDeptId = Number(localStorage.getItem('userDeptId')) || 2;
  const allContracts = mockContracts
  const allEmps = mockEmployees.filter(e => {
    const dId = e.department?.departmentId || e.departmentId || e.deptId;
    return Number(dId) === Number(userDeptId);
  })
  const allPositions = mockPositions
  const empIds = allEmps.map(e => e.employeeId)

  let filteredContracts = allContracts.filter(c => empIds.includes(c.employeeId) && c.status !== 'ĐÃ_CHẤM_DỨT');
  if (filteredContracts.length === 0 && allEmps.length > 0) {
    filteredContracts = allEmps.map(emp => ({
      contractId: 2000 + emp.employeeId,
      contractCode: `HDLD-MOCK-${emp.employeeId}`,
      employeeId: emp.employeeId,
      contractType: "CHÍNH_THỨC_3_NĂM",
      startDate: "2024-01-01",
      endDate: "2027-01-01",
      status: "HIỆU_LỰC"
    }));
  }

  contracts.value = filteredContracts
    .slice(0, 10)
    .map(c => {
      const emp = allEmps.find(e => e.employeeId === c.employeeId)
      const pos = allPositions.find(p => p.positionId === emp?.positionId)
      const startDate = c.startDate ? new Date(c.startDate).toLocaleDateString('vi-VN') : 'N/A'
      const endDate = c.endDate ? new Date(c.endDate).toLocaleDateString('vi-VN') : null
      const range = endDate ? `${startDate} - ${endDate}` : `Từ ${startDate}`

      return {
        id: c.contractId,
        code: c.contractCode,
        name: emp?.fullName || 'N/A',
        position: pos?.positionName || emp?.position?.positionName || 'Chuyên viên',
        type: CONTRACT_TYPE_LABELS[c.contractType] || c.contractType,
        range,
        status: c.status === 'HIỆU_LỰC' ? 'Đang hiệu lực' : 'Sắp hết hạn'
      }
    })
}

onMounted(loadData)
</script>

<style scoped>
.custom-scrollbar::-webkit-scrollbar { height: 5px; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: var(--sys-border-subtle); border-radius: 5px; }
* { -webkit-font-smoothing: antialiased; -moz-osx-font-smoothing: grayscale; }
</style>
