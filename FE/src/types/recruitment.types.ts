// ============================================================
//  RECRUITMENT MODULE – TypeScript Interfaces
//  Dự án: AET HRM System | Module: Tuyển dụng
// ============================================================

/** Trạng thái hồ sơ ứng viên (theo luồng nghiệp vụ chuẩn) */
export enum ApplicationStatus {
  CHO_HR_DUYET    = 'CHỜ_HR_DUYỆT',
  CHO_TP_DUYET    = 'CHỜ_TP_DUYỆT',
  DANG_PHONG_VAN  = 'ĐANG_PHỎNG_VẤN',
  TRUNG_TUYEN     = 'TRÚNG_TUYỂN',
  TU_CHOI         = 'TỪ_CHỐI',
}

/** Trạng thái tin tuyển dụng */
export enum JobPostingStatus {
  DANG_MO    = 'ĐANG_MỞ',
  TAM_DONG   = 'TẠM_ĐÓNG',
  DA_DONG    = 'ĐÃ_ĐÓNG',
}

/** Hình thức làm việc */
export enum WorkType {
  FULL_TIME  = 'FULL_TIME',
  PART_TIME  = 'PART_TIME',
  REMOTE     = 'REMOTE',
  HYBRID     = 'HYBRID',
  INTERNSHIP = 'INTERNSHIP',
}

/** Tin tuyển dụng */
export interface IJobPosting {
  jobId: number;
  title: string;               // Tên vị trí tuyển dụng
  departmentId: number;        // FK → departments.json
  departmentName: string;
  positionId: number;          // FK → positions.json (cấp bậc)
  positionName: string;
  workType: WorkType;
  salaryMin: number;           // VNĐ
  salaryMax: number;
  location: string;
  description: string;
  requirements: string[];      // Danh sách yêu cầu
  benefits: string[];
  openDate: string;            // ISO date YYYY-MM-DD
  closeDate: string | null;
  headcount: number;           // Số lượng cần tuyển
  status: JobPostingStatus;
  postedBy: string;            // Tên HR đăng tin
}

/** Thông tin học vấn của ứng viên */
export interface IEducation {
  school: string;
  major: string;
  degree: string;              // Cử nhân, Thạc sĩ, ...
  graduationYear: number;
}

/** Kinh nghiệm làm việc */
export interface IWorkExperience {
  company: string;
  title: string;
  startDate: string;           // YYYY-MM
  endDate: string | null;      // null = hiện tại
  description: string;
}

/** Hồ sơ ứng viên */
export interface ICandidateApplication {
  applicationId: number;
  jobId: number;               // FK → IJobPosting.jobId
  jobTitle: string;            // Snapshot tên vị trí lúc apply
  departmentId: number;        // FK → departments.json (dùng để route tới Manager)
  departmentName: string;
  positionId: number;          // FK → positions.json
  positionName: string;

  // Thông tin cá nhân
  fullName: string;
  email: string;
  phone: string;
  address: string;
  avatarInitials: string;      // 2 chữ cái đầu họ tên

  // CV & Năng lực
  cvUrl: string;               // Link PDF giả lập (Google Drive / S3)
  skills: string[];
  education: IEducation;
  workExperience: IWorkExperience[];
  coverLetter: string;

  // AI Matching
  aiMatchScore: number;        // 0 → 100 (%)
  aiMatchRemarks: string;      // Nhận xét AI ngắn gọn

  // Workflow
  status: ApplicationStatus;
  appliedDate: string;         // ISO datetime
  reviewedByHR: string | null;
  reviewedByManager: string | null;
  interviewDate: string | null;
  notes: string;
}
