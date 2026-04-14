import { reactive } from 'vue';
import {
  loadMockData,
  mockEmployees,
  mockDepartments,
  mockLeaveRequests,
  mockAttendances,
  mockPositions,
  mockRequestTypes,
  mockAssets,
  mockContracts,
  mockCandidates,
  mockSalaryDetails,
} from '@/mock-data/index.js';

const supportRequests = reactive([]);

export const mockDB = reactive({
  requests: mockLeaveRequests,
  employees: mockEmployees,
  departments: mockDepartments,
  positions: mockPositions,
  requestTypes: mockRequestTypes,
  assets: mockAssets,
  contracts: mockContracts,
  candidates: mockCandidates,
  salaries: mockSalaryDetails,
  supportRequests,
  attendances: mockAttendances,
});

const ensureLoaded = async () => {
  await loadMockData();
};

export const requestsAPI = {
  async getAll() {
    await ensureLoaded();
    return mockDB.requests;
  },
  add(data) {
    return mockDB.requests.add({ ...data, request_id: Date.now(), id: Date.now() });
  },
  update(id, data) {
    return mockDB.requests.update(id, data);
  },
  approve(id) {
    return mockDB.requests.approve(id);
  },
  directorApprove(id) {
    return mockDB.requests.directorApprove(id);
  },
  reject(id, reason) {
    return mockDB.requests.reject(id, reason);
  },
  delete(id) {
    return mockDB.requests.delete(id);
  },
};

export const employeesAPI = {
  async getAll() {
    await ensureLoaded();
    return mockDB.employees;
  },
  getById(id) {
    return mockDB.employees.getById(id);
  },
  add(data) {
    return mockDB.employees.add(data);
  },
  update(id, data) {
    return mockDB.employees.update(id, data);
  },
  delete(id) {
    return mockDB.employees.update(id, { status: 'ĐÃ_NGHỈ_VIỆC' });
  },
};

export const departmentsAPI = {
  async getAll() {
    await ensureLoaded();
    return mockDB.departments;
  },
  getById(id) {
    return mockDB.departments.getById(id);
  },
  add(data) {
    return mockDB.departments.add(data);
  },
  update(id, data) {
    return mockDB.departments.update(id, data);
  },
  delete(id) {
    return mockDB.departments.update(id, { status: false });
  },
};

export const positionsAPI = {
  async getAll() {
    await ensureLoaded();
    return mockDB.positions;
  },
  getById(id) {
    return mockDB.positions.getById(id);
  },
  add(data) {
    return mockDB.positions.add(data);
  },
  update(id, data) {
    return mockDB.positions.update(id, data);
  },
  delete(id) {
    return mockDB.positions.delete(id);
  },
};

export const requestTypesAPI = {
  async getAll() {
    await ensureLoaded();
    return mockDB.requestTypes;
  },
  getById(id) {
    return mockDB.requestTypes.getById(id);
  },
};

export const assetsAPI = {
  async getAll() {
    await ensureLoaded();
    return mockDB.assets;
  },
  getById(id) {
    return mockDB.assets.getById(id);
  },
  add(data) {
    return mockDB.assets.add(data);
  },
  update(id, data) {
    return mockDB.assets.update(id, data);
  },
  delete(id) {
    return mockDB.assets.delete(id);
  },
};

export const contractsAPI = {
  async getAll() {
    await ensureLoaded();
    return mockDB.contracts;
  },
  getById(id) {
    return mockDB.contracts.getById(id);
  },
  add(data) {
    return mockDB.contracts.add(data);
  },
  update(id, data) {
    return mockDB.contracts.update(id, data);
  },
  delete(id) {
    return mockDB.contracts.update(id, { status: 'ĐÃ_CHẤM_DỨT' });
  },
};

export const candidatesAPI = {
  async getAll() {
    await ensureLoaded();
    return mockDB.candidates;
  },
  getById(id) {
    return mockDB.candidates.getById(id);
  },
  add(data) {
    return mockDB.candidates.add(data);
  },
  update(id, data) {
    return mockDB.candidates.update(id, data);
  },
  delete(id) {
    return mockDB.candidates.update(id, { status: 'TỪ_CHỐI' });
  },
};

export const salariesAPI = {
  async getAll() {
    await ensureLoaded();
    return mockDB.salaries;
  },
  getById(id) {
    return mockDB.salaries.getById(id);
  },
  add(data) {
    return mockDB.salaries.add(data);
  },
  update(id, data) {
    return mockDB.salaries.update(id, data);
  },
  delete(id) {
    return mockDB.salaries.delete(id);
  },
};

export const supportRequestsAPI = {
  getAll() {
    return supportRequests;
  },
  getById(id) {
    return supportRequests.find((item) => String(item.id) === String(id));
  },
  add(data) {
    const item = { ...data, id: Date.now().toString() };
    supportRequests.unshift(item);
    return item;
  },
  update(id, data) {
    const index = supportRequests.findIndex((item) => String(item.id) === String(id));
    if (index === -1) return null;
    Object.assign(supportRequests[index], data || {});
    return supportRequests[index];
  },
  delete(id) {
    const index = supportRequests.findIndex((item) => String(item.id) === String(id));
    if (index === -1) return false;
    supportRequests.splice(index, 1);
    return true;
  },
};
