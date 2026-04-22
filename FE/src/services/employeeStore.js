import { ref } from 'vue';
import { apiRequest } from '@/services/beApi.js';

export const employeesState = ref([]);
export const isEmployeesLoaded = ref(false);
export const isLoadingEmployees = ref(false);

const mapEmployee = (emp) => ({
  ...emp,
  id: emp.employee_id || emp.employeeId || emp.id,
  employeeId: emp.employee_id || emp.employeeId || emp.id,
  employee_id: emp.employee_id || emp.employeeId || emp.id,
  employeeCode: emp.employee_code || emp.employeeCode,
  employee_code: emp.employee_code || emp.employeeCode,
  fullName: emp.full_name || emp.fullName,
  full_name: emp.full_name || emp.fullName,
  departmentName: emp.department_name || emp.departmentName || '',
  department_name: emp.department_name || emp.departmentName || '',
  positionName: emp.position_name || emp.positionName || '',
  position_name: emp.position_name || emp.positionName || '',
  phone: emp.phone_number || emp.phone || '',
  email: emp.company_email || emp.email || '',
});

export const getEmployees = async (forceRefresh = false) => {
  if (isEmployeesLoaded.value && !forceRefresh) {
    return employeesState.value;
  }
  
  if (isLoadingEmployees.value) {
    while (isLoadingEmployees.value) {
      await new Promise(resolve => setTimeout(resolve, 100));
    }
    return employeesState.value;
  }

  isLoadingEmployees.value = true;
  try {
    const payload = await apiRequest('/employees', { query: { page: 1, per_page: 5000 } });
    const rawList = Array.isArray(payload?.data) ? payload.data : [];
    employeesState.value = rawList.map(mapEmployee);
    isEmployeesLoaded.value = true;
  } catch (error) {
    console.error('Failed to load employees:', error);
    if (!isEmployeesLoaded.value) employeesState.value = [];
  } finally {
    isLoadingEmployees.value = false;
  }
  
  return employeesState.value;
};

export const updateEmployeeInStore = (updatedEmp) => {
  const index = employeesState.value.findIndex(e => String(e.id) === String(updatedEmp.id || updatedEmp.employee_id));
  if (index !== -1) {
    employeesState.value[index] = mapEmployee({ ...employeesState.value[index], ...updatedEmp });
  } else {
    employeesState.value.unshift(mapEmployee(updatedEmp));
  }
};
