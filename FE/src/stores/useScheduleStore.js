import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import { apiRequest } from '@/services/beApi.js';

export const useScheduleStore = defineStore('schedule', () => {
  // ========== STATE ==========
  const selectedDepartmentId = ref(null);
  const selectedDateRange = ref({ from: null, to: null });
  const employees = ref([]);
  const assignments = ref([]);
  const overrides = ref([]);
  const shifts = ref([]); // Raw shift types
  const warnings = ref({ unassigned: [], leave_conflicts: [], late_risk: [], overtime_risk: [] });
  const isPublished = ref(false);
  const isLockedForEditing = ref(false);
  const publishedAt = ref(null);
  const publishedBy = ref(null);
  const activeTab = ref(1);
  const loading = ref(false);
  const submitting = ref(false);

  // Selection for bulk actions
  const selectedEmployeeIds = ref(new Set());

  const suggestions = ref([]);
  const previewData = ref({ open: false, type: '', items: [], summary: {} });

  const auditLogs = ref([]);
  const publishLogs = ref([]);
  const governanceOverview = ref(null);

  const modals = ref({
    overrideEdit: { open: false, data: null },
    bulkAssign: { open: false, data: null },
    copyWeek: { open: false, data: null },
    confirmAction: { open: false, data: null },
    preview: { open: false, data: null },
    blockPublish: { open: false, data: null },
    success: { open: false, data: null },
    suggestionPreview: { open: false, data: null },
    exportSettings: { open: false, data: null }, // Phase 4
  });

  // ... (getters)

  // ========== ACTIONS ==========

  const fetchAuditLogs = async () => {
    try {
      const res = await apiRequest('/workflow-governance/audit-logs', { 
        query: { per_page: 50, entity_type: 'SHIFT_ASSIGNMENT' } 
      });
      if (res?.success) auditLogs.value = res.data || [];
    } catch (err) { console.error('Audit load failed', err); }
  };

  const fetchPublishHistory = async () => {
    try {
      const res = await apiRequest('/team-schedule/publish-logs', { 
        query: { department_id: selectedDepartmentId.value } 
      });
      if (res?.success) publishLogs.value = res.data || [];
    } catch (err) { console.error('Publish history load failed', err); }
  };

  const fetchGovernanceOverview = async () => {
    try {
      const res = await apiRequest('/workflow-governance/overview');
      if (res?.success) governanceOverview.value = res.data;
    } catch (err) { console.error('Governance overview load failed', err); }
  };

  const fetchSuggestions = async () => {
    if (!selectedDepartmentId.value || !selectedDateRange.value.from) return;
    try {
      const res = await apiRequest('/team-schedule/suggestions', { 
        query: { 
          department_id: selectedDepartmentId.value, 
          from_date: selectedDateRange.value.from, 
          to_date: selectedDateRange.value.to 
        } 
      });
      if (res?.success) {
        suggestions.value = res.data || [];
      }
    } catch (err) {
      console.error('Failed to fetch suggestions', err);
    }
  };

  const previewSuggestion = async (type) => {
    submitting.value = true;
    try {
      if (type === 'fill_empty') {
        // Prepare preview for filling empty slots
        const items = suggestions.value.map(s => ({
          employee: s.employee,
          date: s.work_date,
          old_shift: null,
          new_shift: s.recommended_shift,
          reason: s.reason
        }));
        
        modals.value.suggestionPreview.open = true;
        modals.value.suggestionPreview.data = {
          type: 'FILL_EMPTY',
          title: 'Gợi ý: Điền các ô trống',
          description: 'Hệ thống gợi ý điền ca cho các ô chưa được phân lịch dựa trên lịch sử gần nhất.',
          items,
          summary: { affected: items.length, ignored: 0 }
        };
      } else if (type === 'copy_week') {
        if (!selectedDepartmentId.value) {
          throw new Error('Vui lòng chọn phòng ban cụ thể trước khi sao chép tuần.');
        }
        const start = new Date(selectedDateRange.value.from + 'T00:00:00');
        start.setDate(start.getDate() - 7);
        const prevWeekFrom = start.getFullYear() + '-' + 
                           String(start.getMonth() + 1).padStart(2, '0') + '-' + 
                           String(start.getDate()).padStart(2, '0');
        const currentWeekTo = selectedDateRange.value.to;
        const prevWeekToDate = new Date(prevWeekFrom + 'T00:00:00');
        prevWeekToDate.setDate(prevWeekToDate.getDate() + 6);
        const prevWeekTo = prevWeekToDate.getFullYear() + '-' +
          String(prevWeekToDate.getMonth() + 1).padStart(2, '0') + '-' +
          String(prevWeekToDate.getDate()).padStart(2, '0');

        const [resPrev, resCurrent] = await Promise.all([
          apiRequest('/team-schedule', { 
            query: { department_id: selectedDepartmentId.value, from_date: prevWeekFrom, to_date: prevWeekTo },
            noGetCache: true,
          }),
          apiRequest('/team-schedule', {
            query: { department_id: selectedDepartmentId.value, from_date: selectedDateRange.value.from, to_date: currentWeekTo },
            noGetCache: true,
          })
        ]);

        const prevRows = Array.isArray(resPrev?.data) ? resPrev.data : [];
        const currentRows = Array.isArray(resCurrent?.data) ? resCurrent.data : [];
        const currentMap = new Map(
          currentRows.map((row) => [`${row.employee?.employee_id}|${row.work_date}`, row])
        );

        const items = [];
        let ignored = 0;
        prevRows.forEach((row) => {
          if (!row?.shift?.shift_type_id || !row?.employee?.employee_id || !row?.work_date) return;
          const offsetDays = Math.floor(
            (new Date(row.work_date + 'T00:00:00').getTime() - new Date(prevWeekFrom + 'T00:00:00').getTime()) / 86400000
          );
          if (offsetDays < 0 || offsetDays > 6) return;

          const targetDate = new Date(selectedDateRange.value.from + 'T00:00:00');
          targetDate.setDate(targetDate.getDate() + offsetDays);
          const targetDateStr = targetDate.getFullYear() + '-' +
            String(targetDate.getMonth() + 1).padStart(2, '0') + '-' +
            String(targetDate.getDate()).padStart(2, '0');

          const currentRow = currentMap.get(`${row.employee.employee_id}|${targetDateStr}`);
          const currentShiftId = currentRow?.shift?.shift_type_id || null;
          if (currentShiftId === row.shift.shift_type_id) {
            ignored += 1;
            return;
          }

          items.push({
            employee: row.employee,
            date: targetDateStr,
            old_shift: currentRow?.shift || null,
            new_shift: row.shift,
            reason: `Sao chép từ tuần ${prevWeekFrom} - ${prevWeekTo}`
          });
        });
        
        modals.value.suggestionPreview.open = true;
        modals.value.suggestionPreview.data = {
          type: 'COPY_WEEK',
          title: 'Xem trước: Sao chép tuần trước',
          description: 'Lấy lịch làm việc của tuần trước áp dụng cho tuần này. Backend sẽ lưu thật xuống DB khi bạn xác nhận.',
          items,
          summary: { affected: items.length, ignored },
          source_from_date: prevWeekFrom,
          target_from_date: selectedDateRange.value.from,
        };
      }
    } finally {
      submitting.value = false;
    }
  };

  const applySuggestionBatch = async (items) => {
    submitting.value = true;
    try {
      // Requirements: Apply in batch
      // Since we don't have a specific batch endpoint for suggestions, 
      // we'll use bulk-assign or individual assigns if needed.
      // For filling empty slots, we can group by shift_type_id and call bulk-assign.
      
      const grouped = items.reduce((acc, item) => {
        const sid = item.new_shift.shift_type_id;
        if (!acc[sid]) acc[sid] = [];
        acc[sid].push(item.employee.employee_id);
        return acc;
      }, {});

      for (const [shiftId, empIds] of Object.entries(grouped)) {
        await apiRequest('/team-schedule/bulk-assign', { 
          method: 'POST', 
          body: { 
            shift_type_id: Number(shiftId), 
            effective_date: selectedDateRange.value.from, 
            employee_ids: empIds,
            notes: 'Applied via Smart Suggestion'
          } 
        });
      }

      await fetchScheduleData(selectedDepartmentId.value, selectedDateRange.value.from, selectedDateRange.value.to, true);
      return { success: true };
    } finally {
      submitting.value = false;
    }
  };
  const allWarnings = computed(() => {
    const w = warnings.value;
    return [
      ...(w.unassigned || []),
      ...(w.leave_conflicts || []),
      ...(w.late_risk || []),
      ...(w.overtime_risk || [])
    ];
  });

  const canEditBaseSchedule = computed(() => !isLockedForEditing.value);
  const totalEmployees = computed(() => employees.value.length);
  const selectedCount = computed(() => selectedEmployeeIds.value.size);
  
  const unassignedEmployees = computed(() => {
    if (!warnings.value.unassigned) return 0;
    return new Set(warnings.value.unassigned.map(u => u.employee?.employee_id)).size;
  });

  const warningCount = computed(() => {
    const w = warnings.value;
    return (w.unassigned?.length || 0) + (w.leave_conflicts?.length || 0) + (w.late_risk?.length || 0) + (w.overtime_risk?.length || 0);
  });

  const statusLabel = computed(() => isPublished.value ? 'ĐÃ PUBLISH' : 'BẢN NHÁP');

  // Map shifts for system standard Dropdown
  const shiftOptions = computed(() => {
    return shifts.value.map(s => ({
      label: `${s.shift_code || s.code} - ${s.shift_name || s.name} (${s.start_time?.slice(0,5)} - ${s.end_time?.slice(0,5)})`,
      value: s.shift_type_id || s.id,
      icon: 'schedule'
    }));
  });

  // ========== ACTIONS ==========
  const toggleEmployeeSelection = (id) => {
    if (selectedEmployeeIds.value.has(id)) selectedEmployeeIds.value.delete(id);
    else selectedEmployeeIds.value.add(id);
  };

  const selectAllEmployees = (checked) => {
    if (checked) selectedEmployeeIds.value = new Set(employees.value.map(e => e.employee_id));
    else selectedEmployeeIds.value.clear();
  };

  const fetchScheduleData = async (deptId, dateFrom, dateTo, forceRefresh = false) => {
    if (!dateFrom || !dateTo) return;
    loading.value = true;
    if (forceRefresh) resetState();
    try {
      const [resSchedule, resWarnings] = await Promise.allSettled([
        apiRequest('/team-schedule', { 
          query: { department_id: deptId ?? undefined, from_date: dateFrom, to_date: dateTo }, 
          noGetCache: true 
        }),
        apiRequest('/team-schedule/warnings', { 
          query: { department_id: deptId ?? undefined, from_date: dateFrom, to_date: dateTo }, 
          noGetCache: true 
        })
      ]);

      if (resSchedule.status !== 'fulfilled' || !resSchedule.value?.success || !Array.isArray(resSchedule.value.data)) {
        const message = resSchedule.status === 'rejected'
          ? (resSchedule.reason?.message || 'Không tải được dữ liệu phân ca')
          : (resSchedule.value?.message || 'Không tải được dữ liệu phân ca');
        throw new Error(message);
      }
      
      const data = resSchedule.value.data;
      const empMap = new Map();
      const assigns = [];
      const overs = [];
      let hasPublishedRow = false;
      let pubInfo = null;

      data.forEach(row => {
        if (row.employee && !empMap.has(row.employee.employee_id)) {
          empMap.set(row.employee.employee_id, {
            ...row.employee,
            id: row.employee.employee_id
          });
        }
        if (row.shift && row.shift.shift_type_id) {
           const record = { 
             employee_id: row.employee.employee_id, 
             work_date: row.work_date, 
             shift_type_id: row.shift.shift_type_id, 
             reason: row.shift.meta?.reason || row.shift.reason || '',
           };
           if (row.shift.source?.toUpperCase() === 'OVERRIDE') overs.push(record);
           else assigns.push(record);
        }
        if (row.workflow?.code === 'PUBLISHED' || row.workflow?.code === 'ADJUSTED_AFTER_PUBLISH') {
           hasPublishedRow = true;
           if (row.workflow.publish_log) pubInfo = row.workflow.publish_log;
        }
      });

      employees.value = Array.from(empMap.values());
      assignments.value = assigns;
      overrides.value = overs;
      isPublished.value = hasPublishedRow;
      isLockedForEditing.value = hasPublishedRow;
      publishedAt.value = pubInfo?.published_at || null;
      publishedBy.value = pubInfo?.published_by_name || pubInfo?.published_by || null;
      
      if (resWarnings.status === 'fulfilled' && resWarnings.value?.success && resWarnings.value.data) {
         const w = resWarnings.value.data;
         warnings.value = {
            unassigned: w.unassigned || [],
            leave_conflicts: w.leave_conflicts || [],
            late_risk: w.late_risk || [],
            overtime_risk: w.overtime_risk || []
         };
      } else {
        warnings.value = { unassigned: [], leave_conflicts: [], late_risk: [], overtime_risk: [] };
      }
    } catch (error) {
      console.error('Failed to fetch schedule:', error);
      throw error;
    } finally {
      loading.value = false;
    }
  };

  const fetchShiftTypes = async () => {
    try {
      // Backend route is /api/v1/shifts
      const response = await apiRequest('/shifts', { noGetCache: true });
      shifts.value = response?.data || [];
    } catch (error) { 
      console.error('Failed to fetch shifts:', error);
      shifts.value = []; 
    }
  };

  const assignShift = async (payload) => {
    submitting.value = true;
    try {
      // Logic 1: Click 1 ô = đổi 1 ngày
      const response = await apiRequest('/team-schedule/assign', { 
        method: 'POST', 
        body: { 
          employee_id: payload.employee_id, 
          shift_type_id: payload.shift_type_id, 
          effective_date: payload.work_date, // Đúng 1 ngày được click
          is_permanent: false, 
          notes: 'Single cell assignment' 
        } 
      });
      if (response?.success) {
        await fetchScheduleData(selectedDepartmentId.value, selectedDateRange.value.from, selectedDateRange.value.to, true);
        return response;
      }
      throw new Error(response?.message || 'Failed to assign shift');
    } finally { submitting.value = false; }
  };

  const bulkAssignShift = async (payload) => {
    submitting.value = true;
    try {
      // Logic 2: Gán nhanh = đổi 1 tuần
      let targetIds = [];
      if (payload.target === 'selected') targetIds = Array.from(selectedEmployeeIds.value);
      else if (payload.target === 'all') targetIds = employees.value.map(e => e.employee_id);
      else targetIds = employees.value.filter(e => warnings.value.unassigned.some(u => u.employee?.employee_id === e.employee_id)).map(e => e.employee_id);

      if (targetIds.length === 0) throw new Error('Vui lòng chọn nhân sự để áp dụng');

      const response = await apiRequest('/team-schedule/bulk-assign', { 
        method: 'POST', 
        body: { 
          shift_type_id: payload.shift_type_id, 
          effective_date: selectedDateRange.value.from, // Ngày bắt đầu tuần
          employee_ids: targetIds, 
          notes: 'Bulk weekly assignment' 
        } 
      });
      if (response?.success) {
        selectedEmployeeIds.value.clear();
        await fetchScheduleData(selectedDepartmentId.value, selectedDateRange.value.from, selectedDateRange.value.to, true);
        return response;
      }
      throw new Error(response?.message || 'Failed to bulk assign');
    } finally { submitting.value = false; }
  };

  const createOrUpdateOverride = async (payload) => {
    submitting.value = true;
    try {
      const response = await apiRequest('/team-schedule/override', { method: 'PATCH', body: payload });
      if (response?.success) {
        await fetchScheduleData(selectedDepartmentId.value, selectedDateRange.value.from, selectedDateRange.value.to, true);
        return response;
      }
      throw new Error(response?.message || 'Failed to override');
    } finally { submitting.value = false; }
  };

  const deleteOverride = async (employeeId, workDate) => {
    submitting.value = true;
    try {
      const response = await apiRequest('/team-schedule/override', { method: 'PATCH', body: { employee_id: employeeId, work_date: workDate, shift_type_id: null, reason: 'Deleted' } });
      if (response?.success) {
        await fetchScheduleData(selectedDepartmentId.value, selectedDateRange.value.from, selectedDateRange.value.to, true);
        return response;
      }
      throw new Error(response?.message || 'Failed to delete override');
    } finally { submitting.value = false; }
  };

  const copyScheduleWeek = async (options = {}) => {
    submitting.value = true;
    try {
      if (!selectedDepartmentId.value) {
        throw new Error('Vui lòng chọn phòng ban cụ thể trước khi sao chép tuần.');
      }

      const sourceFromDate = options.source_from_date || (() => {
        const start = new Date(selectedDateRange.value.from + 'T00:00:00');
        start.setDate(start.getDate() - 7);
        return start.getFullYear() + '-' + 
          String(start.getMonth() + 1).padStart(2, '0') + '-' + 
          String(start.getDate()).padStart(2, '0');
      })();

      const targetFromDate = options.target_from_date || selectedDateRange.value.from;
      const response = await apiRequest('/team-schedule/copy-week', {
        method: 'POST',
        body: {
          scope_type: 'DEPARTMENT',
          scope_id: selectedDepartmentId.value,
          source_from_date: sourceFromDate,
          target_from_date: targetFromDate,
          notes: options.notes,
        }
      });
      if (response?.success) {
        await fetchScheduleData(selectedDepartmentId.value, selectedDateRange.value.from, selectedDateRange.value.to, true);
        return response;
      }
      throw new Error(response?.message || 'Lỗi khi sao chép tuần');
    } finally { submitting.value = false; }
  };

  const validatePublish = async () => {
    if (!selectedDepartmentId.value) {
      throw new Error('Vui lòng chọn phòng ban cụ thể trước khi kiểm tra publish.');
    }
    return await apiRequest('/workflow-governance/validate-schedule-publish', { method: 'POST', body: { scope_type: 'DEPARTMENT', scope_id: selectedDepartmentId.value, from_date: selectedDateRange.value.from, to_date: selectedDateRange.value.to } });
  };

  const publishSchedule = async () => {
    submitting.value = true;
    try {
      if (!selectedDepartmentId.value) {
        throw new Error('Vui lòng chọn phòng ban cụ thể trước khi publish lịch.');
      }
      const response = await apiRequest('/team-schedule/publish', { 
        method: 'POST', 
        body: { 
          scope_type: 'DEPARTMENT', 
          scope_id: selectedDepartmentId.value, 
          from_date: selectedDateRange.value.from, 
          to_date: selectedDateRange.value.to,
          strict_mode: true // Mandatory strict mode for production
        } 
      });
      if (response?.success) {
        await fetchScheduleData(selectedDepartmentId.value, selectedDateRange.value.from, selectedDateRange.value.to);
        return response;
      }
      throw new Error(response?.message || 'Publish failed');
    } catch (err) {
      // Friendly message for validation issues
      const msg = err.message || '';
      if (msg.includes('chua duoc phan ca') || msg.includes('unassigned')) {
         throw new Error("Lịch chưa đầy đủ cho tuần này. Vui lòng kiểm tra các ô trống.");
      }
      throw err;
    } finally { submitting.value = false; }
  };

  const openModal = (type, data = null) => { if (modals.value[type]) { modals.value[type].open = true; modals.value[type].data = data; } };
  const closeModal = (type) => { if (modals.value[type]) { modals.value[type].open = false; modals.value[type].data = null; } };
  const setActiveTab = (tabNumber) => { activeTab.value = tabNumber; };
  const resetState = () => {
    employees.value = []; assignments.value = []; overrides.value = [];
    warnings.value = { unassigned: [], leave_conflicts: [], late_risk: [], overtime_risk: [] };
    isPublished.value = false; isLockedForEditing.value = false;
    selectedEmployeeIds.value.clear();
  };

  const applySuggestion = async (id) => {
    alert('Áp dụng gợi ý #' + id + ' (Phase 3 Feature)');
  };
  const skipSuggestion = (id) => {
    suggestions.value = suggestions.value.filter(s => s.id !== id);
  };

  return {
    selectedDepartmentId, selectedDateRange, employees, assignments, overrides, shifts, shiftOptions, warnings,
    isPublished, isLockedForEditing, publishedAt, publishedBy, activeTab, loading, submitting, modals,
    selectedEmployeeIds, selectedCount,
    suggestions, previewData, auditLogs, publishLogs, governanceOverview, allWarnings,
    canEditBaseSchedule, totalEmployees, unassignedEmployees, warningCount, statusLabel,
    toggleEmployeeSelection, selectAllEmployees,
    fetchScheduleData, fetchShiftTypes, assignShift, bulkAssignShift, createOrUpdateOverride, deleteOverride,
    copyScheduleWeek, validatePublish, publishSchedule, openModal, closeModal, setActiveTab, resetState,
    fetchAuditLogs, fetchPublishHistory, fetchGovernanceOverview, fetchSuggestions, previewSuggestion, applySuggestionBatch,
    applySuggestion, skipSuggestion
  };
});
