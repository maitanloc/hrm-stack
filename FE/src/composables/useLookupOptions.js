import { ref } from 'vue';
import { apiRequest } from '@/services/beApi.js';

const toOptions = (items, valueKey, labelKey) =>
  (Array.isArray(items) ? items : []).map((item) => ({
    value: item[valueKey],
    label: item[labelKey],
  }));

export function useLookupOptions() {
  const loading = ref(false);
  const departments = ref([]);
  const employees = ref([]);
  const positions = ref([]);
  const contractTypes = ref([]);
  const recruitmentPositions = ref([]);
  const salaryPeriods = ref([]);
  const leaveTypes = ref([]);
  const newsCategories = ref([]);
  const serviceCategories = ref([]);

  const loadDepartments = async () => {
    const payload = await apiRequest('/departments', { query: { page: 1, per_page: 200 } });
    departments.value = toOptions(payload?.data, 'department_id', 'department_name');
  };

  const loadEmployees = async () => {
    const payload = await apiRequest('/employees', { query: { page: 1, per_page: 300 } });
    employees.value = (Array.isArray(payload?.data) ? payload.data : []).map((item) => ({
      value: item.employee_id,
      label: `${item.employee_code || item.employee_id} - ${item.full_name || ''}`,
    }));
  };

  const loadPositions = async () => {
    const payload = await apiRequest('/positions', { query: { page: 1, per_page: 200 } });
    positions.value = toOptions(payload?.data, 'position_id', 'position_name');
  };

  const loadContractTypes = async () => {
    const payload = await apiRequest('/contract-types');
    contractTypes.value = toOptions(payload?.data, 'contract_type_id', 'contract_type_name');
  };

  const loadRecruitmentPositions = async () => {
    const payload = await apiRequest('/recruitment-positions', { query: { page: 1, per_page: 200 } });
    recruitmentPositions.value = (Array.isArray(payload?.data) ? payload.data : []).map((item) => ({
      value: item.recruitment_position_id,
      label: `${item.position_code || ''} ${item.position_name || ''}`.trim(),
    }));
  };

  const loadSalaryPeriods = async () => {
    const payload = await apiRequest('/salary-periods', { query: { page: 1, per_page: 120 } });
    salaryPeriods.value = (Array.isArray(payload?.data) ? payload.data : []).map((item) => ({
      value: item.period_id,
      label: `${item.period_code || ''} ${item.period_name || ''}`.trim(),
    }));
  };

  const loadLeaveTypes = async () => {
    const payload = await apiRequest('/request-types', { query: { page: 1, per_page: 200 } });
    leaveTypes.value = (Array.isArray(payload?.data) ? payload.data : []).map((item) => ({
      value: item.request_type_id,
      label: item.request_type_name || `Loai ${item.request_type_id}`,
    }));
  };

  const loadNewsCategories = async () => {
    const payload = await apiRequest('/news-categories', { query: { page: 1, per_page: 200 } });
    newsCategories.value = toOptions(payload?.data, 'category_id', 'category_name');
  };

  const loadServiceCategories = async () => {
    const payload = await apiRequest('/service-categories');
    const items = Array.isArray(payload?.data) ? payload.data : [];
    serviceCategories.value = items.map((item) => ({
      value: item.category_id,
      label: item.category_name || `Loai ${item.category_id}`,
    }));
  };

  const bootstrap = async (keys = []) => {
    loading.value = true;
    try {
      const tasks = [];
      if (keys.includes('departments')) tasks.push(loadDepartments());
      if (keys.includes('employees')) tasks.push(loadEmployees());
      if (keys.includes('positions')) tasks.push(loadPositions());
      if (keys.includes('contractTypes')) tasks.push(loadContractTypes());
      if (keys.includes('recruitmentPositions')) tasks.push(loadRecruitmentPositions());
      if (keys.includes('salaryPeriods')) tasks.push(loadSalaryPeriods());
      if (keys.includes('leaveTypes')) tasks.push(loadLeaveTypes());
      if (keys.includes('newsCategories')) tasks.push(loadNewsCategories());
      if (keys.includes('serviceCategories')) tasks.push(loadServiceCategories());
      await Promise.all(tasks);
    } finally {
      loading.value = false;
    }
  };

  return {
    loading,
    departments,
    employees,
    positions,
    contractTypes,
    recruitmentPositions,
    salaryPeriods,
    leaveTypes,
    newsCategories,
    serviceCategories,
    bootstrap,
  };
}
