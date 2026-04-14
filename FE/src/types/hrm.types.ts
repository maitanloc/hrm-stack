export interface IDepartment {
  departmentId: number;
  departmentCode?: string;
  departmentName: string;
}

export interface IPosition {
  positionId: number;
  positionName: string;
}

export interface IEmployee {
  employeeId: number;
  employeeCode: string;
  fullName: string;
  dateOfBirth: string;
  gender: 'NAM' | 'NỮ' | 'KHÁC';
  companyEmail: string;
  phoneNumber: string;
  status: 'ĐANG_LÀM_VIỆC' | 'ĐÃ_NGHỈ_VIỆC' | 'THỬ_VIỆC' | 'NGHỈ_THAI_SẢN' | 'TẠM_HOÃN_CÔNG_TÁC';
  hireDate: string;
  baseLeaveDays: number;
  department: IDepartment;
  position: IPosition;
  role: 'ADMIN' | 'MANAGER' | 'EMPLOYEE';
}

export interface IAttendance {
  attendanceId: number;
  employeeId: number;
  attendanceDate: string;
  checkInTime: string | null;
  checkOutTime: string | null;
  workType: 'VĂN_PHÒNG' | 'LÀM_TỪ_XA' | 'CÔNG_TÁC' | 'ĐI_CÔNG_TÁC';
  actualWorkingHours: number;
  overtimeHours: number;
  lateMinutes: number;
  isHoliday: boolean;
  notes?: string;
  status: 'CHỜ_DUYỆT' | 'ĐÃ_DUYỆT' | 'TỪ_CHỐI' | 'NHẬP_THỦ_CÔNG';
}

export interface ILeaveDetails {
  leaveTypeCode: string;
  fromDate: string;
  toDate: string;
  numberOfDays: number;
}

export interface ILeaveRequest {
  requestId: number;
  requestCode: string;
  requesterId: number;
  requesterName: string;
  requestDate: string;
  leaveDetails: ILeaveDetails;
  reason: string;
  status: 'CHỜ_DUYỆT' | 'ĐÃ_DUYỆT' | 'TỪ_CHỐI' | 'ĐÃ_HỦY' | 'HOÀN_THÀNH';
  currentApprover: string;
  completedDate?: string;
  rejectionReason?: string;
}

export interface ISalaryDetail {
  salaryDetailId: number;
  periodCode: string;
  periodName: string;
  employeeId: number;
  basicSalary: number;
  grossSalary: number;
  netSalary: number;
  shiftPay: number;
  overtimePay: number;
  totalAllowances: number;
  bonus: number;
  penalty: number;
  socialInsuranceEmployee: number;
  healthInsuranceEmployee: number;
  unemploymentInsuranceEmployee: number;
  personalIncomeTax: number;
  transferStatus: string;
  notes?: string;
}
