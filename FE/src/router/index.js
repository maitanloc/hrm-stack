import { createRouter, createWebHistory } from 'vue-router'
import Layout_NhanVien from '../components/Layout_NhanVien.vue'
import Dashboard_portal from '../View/portal_nhanvien/Dashboard_portal.vue'
import Cham_cong from '../View/portal_nhanvien/Cham_cong.vue'
import Layout_Admin from '../components/Layout_Admin.vue'
import Layout_GiamDoc from '../components/Layout_GiamDoc.vue'
import { getAccessToken } from '@/services/runtimeConfig.js'
import { clearAuthSession, getCurrentUserRole } from '@/services/session.js'

const routes = [
      {
            path: '/',
            redirect: '/login'
      },
      {
            path: '/landing',
            name: 'landing',
            component: () => import('../View/LandingPage.vue')
      },
      {
            path: '/login',
            name: 'login',
            component: () => import('../components/Login.vue')
      },
      {
            path: '/nhanvien',
            component: Layout_NhanVien,
            children: [
                  {
                        path: '',
                        name: 'dashboard',
                        component: Dashboard_portal,
                        meta: { index: 1 }
                  },
                  {
                        path: 'chamcong',
                        name: 'cham-cong',
                        component: Cham_cong,
                        meta: { index: 2 }
                  },
                  {
                        path: 'luong',
                        name: 'luong',
                        component: () => import('../View/portal_nhanvien/PhieuLuong.vue'),
                        meta: { index: 3 }
                  },
                  {
                        path: 'hoso',
                        name: 'ho-so',
                        component: () => import('../View/portal_nhanvien/HoSoCaNhan.vue'),
                        meta: { index: 4 }
                  },
                  {
                        path: 'nghiphep',
                        name: 'nghi-phep',
                        component: () => import('../View/portal_nhanvien/NghiPhep.vue'),
                        meta: { index: 5 }
                  },
                  {
                        path: 'donnghiviec',
                        name: 'don-nghi-viec',
                        component: () => import('../View/portal_nhanvien/GiayPhep/DonNghiViec.vue'),
                        meta: { index: 6 }
                  },
                  {
                        path: 'dichvu',
                        name: 'dich-vu',
                        component: () => import('../View/portal_nhanvien/DichVuNoiBo.vue'),
                        meta: { index: 7 }
                  },
                  {
                        path: 'giaitrinhchamcong',
                        name: 'giai-trinh-cham-cong',
                        component: () => import('../View/portal_nhanvien/GiayPhep/GiaiTrinhChamCong.vue'),
                        meta: { index: 8 }
                  },
                  {
                        path: 'thongbao',
                        name: 'thong-bao',
                        component: () => import('../View/portal_nhanvien/ThongBao.vue'),
                        meta: { index: 9 }
                  }
            ]
      },
      {
            path: '/admin',
            component: Layout_Admin,
            children: [
                  {
                        path: '',
                        name: 'dashboard-admin',
                        component: () => import('../View/admin/Dashboard_admin.vue'),
                        meta: { index: 1 }
                  },
                  {
                        path: 'tuyendung',
                        name: 'admin-tuyen-dung',
                        component: () => import('../View/admin/HR/TuyenDung.vue'),
                        meta: { index: 2 }
                  },
                  {
                        path: 'nhansu',
                        name: 'admin-nhan-su',
                        component: () => import('../View/admin/QuanLy/QuanLyNhanSu.vue'),
                        meta: { index: 3 }
                  },
                  {
                        path: 'chucdanh',
                        name: 'admin-chuc-danh',
                        component: () => import('../View/admin/QuanLy/QuanLyChucDanh.vue'),
                        meta: { index: 4 }
                  },
                  {
                        path: 'phongban',
                        name: 'admin-phong-ban',
                        component: () => import('../View/admin/QuanLy/QuanLyPhongban.vue'),
                        meta: { index: 5 }
                  },
                  {
                        path: 'hopdong',
                        name: 'admin-hop-dong',
                        component: () => import('../View/admin/QuanLy/QuanLyHopDong.vue'),
                        meta: { index: 6 }
                  },
                  {
                        path: 'chamcong',
                        name: 'admin-cham-cong',
                        component: () => import('../View/admin/ChamCongAdmin.vue'),
                        meta: { index: 7 }
                  },
                  {
                        path: 'phanca',
                        name: 'admin-phan-ca',
                        component: () => import('../views/attendance/ShiftSchedulingView.vue'),
                        meta: { index: 7.5 }
                  },
                  {
                        path: 'nghiphep',
                        name: 'admin-nghi-phep',
                        component: () => import('../View/admin/HR/NghiPhep.vue'),
                        meta: { index: 8 }
                  },
                  {
                        path: 'lichphongvan',
                        name: 'admin-lich-phong-van',
                        component: () => import('../View/admin/HR/LichPhongVan.vue'),
                        meta: { index: 9 }
                  },
                  {
                        path: 'bangluong',
                        name: 'admin-bang-luong',
                        component: () => import('../View/admin/QuanLy/QuanLyBangLuong.vue'),
                        meta: { index: 10 }
                  },
                  {
                        path: 'taisan',
                        name: 'admin-tai-san',
                        component: () => import('../View/admin/QuanLy/QuanLyTaiSan.vue'),
                        meta: { index: 11 }
                  },
                  {
                        path: 'pheduyet',
                        name: 'admin-phe-duyet',
                        component: () => import('../View/admin/QuanLy/PheDuyetDon.vue'),
                        meta: { index: 12 }
                  },
                  {
                        path: 'hotro',
                        name: 'admin-ho-tro',
                        component: () => import('../View/admin/QuanLy/QuanLyHoTro.vue'),
                        meta: { index: 13 }
                  },
                  {
                        path: 'caidat',
                        name: 'admin-cai-dat',
                        component: () => import('../View/admin/Setting_admin.vue'),
                        meta: { index: 14 }
                  },
                  {
                        path: 'policy-center',
                        alias: 'policy',
                        name: 'admin-policy-center',
                        component: () => import('../views/system/PolicyCenterView.vue'),
                        meta: { index: 14.1 }
                  },
                  {
                        path: 'audit-logs',
                        name: 'admin-audit-logs',
                        component: () => import('../views/system/AuditLogsView.vue'),
                        meta: { index: 14.2 }
                  },
                  {
                        path: 'giam-sat-gps',
                        name: 'admin-giam-sat-gps',
                        component: () => import('../views/attendance/RiskAlertsView.vue'),
                        meta: { index: 15 }
                  },
                  {
                        path: 'timesheet-period',
                        alias: 'timesheet',
                        name: 'admin-timesheet-period',
                        component: () => import('../views/attendance/TimesheetPeriodView.vue'),
                        meta: { index: 15.1 }
                  },
                  {
                        path: 'timesheet-exceptions',
                        alias: 'exceptions',
                        name: 'admin-timesheet-exceptions',
                        component: () => import('../views/attendance/ExceptionDashboardView.vue'),
                        meta: { index: 15.2 }
                  },
                  {
                        path: 'timesheet-import',
                        alias: 'import-logs',
                        name: 'admin-timesheet-import',
                        component: () => import('../views/attendance/ImportLogsView.vue'),
                        meta: { index: 15.3 }
                  },
                  {
                        path: 'payroll-export',
                        name: 'admin-payroll-export',
                        component: () => import('../views/attendance/PayrollExportView.vue'),
                        meta: { index: 15.4 }
                  },
                  {
                        path: 'api-ui',

                        name: 'admin-api-workflow',
                        component: () => import('../views/api/WorkflowHub.vue'),
                        meta: { index: 40 }
                  },
                  {
                        path: 'api-ui/hrm/employees',
                        name: 'admin-api-hrm-employees',
                        component: () => import('../views/hrm/EmployeesView.vue'),
                        meta: { index: 41 }
                  },
                  {
                        path: 'api-ui/hrm/departments',
                        name: 'admin-api-hrm-departments',
                        component: () => import('../views/hrm/DepartmentsView.vue'),
                        meta: { index: 42 }
                  },
                  {
                        path: 'api-ui/hrm/contracts',
                        name: 'admin-api-hrm-contracts',
                        component: () => import('../views/hrm/ContractsView.vue'),
                        meta: { index: 43 }
                  },
                  {
                        path: 'api-ui/attendance/records',
                        name: 'admin-api-attendance-records',
                        component: () => import('../views/attendance/AttendancesView.vue'),
                        meta: { index: 44 }
                  },
                  {
                        path: 'api-ui/attendance/leave-requests',
                        name: 'admin-api-attendance-leave-requests',
                        component: () => import('../views/attendance/LeaveRequestsView.vue'),
                        meta: { index: 45 }
                  },
                  {
                        path: 'api-ui/attendance/risk-alerts',
                        name: 'admin-api-attendance-risk-alerts',
                        component: () => import('../views/attendance/RiskAlertsView.vue'),
                        meta: { index: 46 }
                  },
                  {
                        path: 'api-ui/attendance/mobile-one-tap',
                        name: 'admin-api-attendance-mobile-one-tap',
                        component: () => import('../views/attendance/MobileClockOneTap.vue'),
                        meta: { index: 53 }
                  },
                  {
                        path: 'api-ui/recruitment/positions',
                        name: 'admin-api-recruitment-positions',
                        component: () => import('../views/recruitment/RecruitmentPositionsView.vue'),
                        meta: { index: 47 }
                  },
                  {
                        path: 'api-ui/recruitment/candidates',
                        name: 'admin-api-recruitment-candidates',
                        component: () => import('../views/recruitment/RecruitmentCandidatesView.vue'),
                        meta: { index: 48 }
                  },
                  {
                        path: 'api-ui/recruitment/interviews',
                        name: 'admin-api-recruitment-interviews',
                        component: () => import('../views/recruitment/InterviewsView.vue'),
                        meta: { index: 49 }
                  },
                  {
                        path: 'api-ui/payroll/periods',
                        name: 'admin-api-payroll-periods',
                        component: () => import('../views/payroll/SalaryPeriodsView.vue'),
                        meta: { index: 50 }
                  },
                  {
                        path: 'api-ui/payroll/details',
                        name: 'admin-api-payroll-details',
                        component: () => import('../views/payroll/SalaryDetailsView.vue'),
                        meta: { index: 51 }
                  },
                  {
                        path: 'api-ui/payroll/adjustments',
                        name: 'admin-api-payroll-adjustments',
                        component: () => import('../views/payroll/PayrollAdjustmentsView.vue'),
                        meta: { index: 52 }
                  },
                  {
                        path: 'api-ui/payroll/breakdowns',
                        name: 'admin-api-payroll-breakdowns',
                        component: () => import('../views/payroll/SalaryBreakdownsView.vue'),
                        meta: { index: 54 }
                  },
                  {
                        path: 'api-ui/hrm/positions',
                        name: 'admin-api-hrm-positions',
                        component: () => import('../views/hrm/PositionsView.vue'),
                        meta: { index: 55 }
                  },
                  {
                        path: 'api-ui/hrm/request-types',
                        name: 'admin-api-hrm-request-types',
                        component: () => import('../views/hrm/RequestTypesView.vue'),
                        meta: { index: 56 }
                  },
                  {
                        path: 'api-ui/hrm/requests',
                        name: 'admin-api-hrm-requests',
                        component: () => import('../views/hrm/RequestsView.vue'),
                        meta: { index: 57 }
                  },
                  {
                        path: 'api-ui/attendance/overtime',
                        name: 'admin-api-attendance-overtime',
                        component: () => import('../views/attendance/OvertimeRequestsView.vue'),
                        meta: { index: 58 }
                  },
                  {
                        path: 'api-ui/attendance/leave-balances',
                        name: 'admin-api-attendance-leave-balances',
                        component: () => import('../views/attendance/LeaveBalancesView.vue'),
                        meta: { index: 59 }
                  },
                  {
                        path: 'api-ui/asset/assets',
                        name: 'admin-api-asset-assets',
                        component: () => import('../views/asset/AssetsView.vue'),
                        meta: { index: 60 }
                  },
                  {
                        path: 'api-ui/asset/assignments',
                        name: 'admin-api-asset-assignments',
                        component: () => import('../views/asset/AssetAssignmentsView.vue'),
                        meta: { index: 61 }
                  },
                  {
                        path: 'api-ui/comm/news-categories',
                        name: 'admin-api-comm-news-categories',
                        component: () => import('../views/communication/NewsCategoriesView.vue'),
                        meta: { index: 62 }
                  },
                  {
                        path: 'api-ui/comm/news',
                        name: 'admin-api-comm-news',
                        component: () => import('../views/communication/NewsView.vue'),
                        meta: { index: 63 }
                  },
                  {
                        path: 'api-ui/comm/policies',
                        name: 'admin-api-comm-policies',
                        component: () => import('../views/communication/PoliciesView.vue'),
                        meta: { index: 64 }
                  },
                  {
                        path: 'api-ui/service/tickets',
                        name: 'admin-api-service-tickets',
                        component: () => import('../views/service/ServiceTicketsView.vue'),
                        meta: { index: 65 }
                  },
                  {
                        path: 'api-ui/system/notifications',
                        name: 'admin-api-system-notifications',
                        component: () => import('../views/system/NotificationsView.vue'),
                        meta: { index: 66 }
                  },
                  {
                        path: 'api-ui/hrm/contract-change-logs',
                        name: 'admin-api-hrm-contract-change-logs',
                        component: () => import('../views/hrm/ContractChangeLogsView.vue'),
                        meta: { index: 67 }
                  },
                  {
                        path: 'api-ui/system/settings',
                        name: 'admin-api-system-settings',
                        component: () => import('../views/system/SettingsView.vue'),
                        meta: { index: 68 }
                  },
                  {
                        path: 'hoso',
                        name: 'admin-ho-so',
                        component: () => import('../View/portal_nhanvien/HoSoCaNhan.vue'),
                        meta: { index: 16 }
                  },
                  {
                        path: 'donnghiviec',
                        name: 'admin-don-nghi-viec',
                        component: () => import('../View/portal_nhanvien/GiayPhep/DonNghiViec.vue'),
                        meta: { index: 17 }
                  }
            ]
      },
      {
            path: '/giamdoc',
            component: Layout_GiamDoc,
            children: [
                  {
                        path: '',
                        name: 'dashboard-giam-doc',
                        component: () => import('../View/admin/GiamDoc/GD_TrangChu.vue'),
                        meta: { index: 1 }
                  },
                  {
                        path: 'nhansu',
                        name: 'giam-doc-nhan-su',
                        component: () => import('../View/admin/GiamDoc/DBGD_NhanSu.vue'),
                        meta: { index: 2 }
                  },
                  {
                        path: 'bangluong',
                        name: 'giam-doc-bang-luong',
                        component: () => import('../View/admin/GiamDoc/DBGD_BangLuong.vue'),
                        meta: { index: 3 }
                  },
                  {
                        path: 'chuyencan',
                        name: 'giam-doc-chuyen-can',
                        component: () => import('../View/admin/GiamDoc/DBGD_ChuyenCan.vue'),
                        meta: { index: 4 }
                  },
                  {
                        path: 'biendong',
                        name: 'giam-doc-bien-dong',
                        component: () => import('../View/admin/GiamDoc/DBGD_BienDong.vue'),
                        meta: { index: 5 }
                  },
                  {
                        path: 'hoso',
                        name: 'giam-doc-ho-so',
                        component: () => import('../View/admin/GiamDoc/GD_HoSoCaNhan.vue'),
                        meta: { index: 6 }
                  },
                  {
                        path: 'thongbao',
                        name: 'giam-doc-thong-bao',
                        component: () => import('../View/admin/GiamDoc/GD_TTThongBao.vue'),
                        meta: { index: 7 }
                  }
            ]
      },
      {
            path: '/truongphong',
            component: () => import('../components/Layout_TruongPhong.vue'),
            children: [
                  {
                        path: '',
                        redirect: '/truongphong/dashboard'
                  },
                  {
                        path: 'dashboard',
                        name: 'dashboard-truong-phong',
                        component: () => import('../View/truongphong/Dashboard_TP.vue'),
                        meta: { index: 1 }
                  },
                  {
                        path: 'nhansu',
                        name: 'tp-nhan-su',
                        component: () => import('../View/truongphong/TP_NhanSu.vue'),
                        meta: { index: 2 }
                  },
                  {
                        path: 'chamcong',
                        name: 'tp-cham-cong',
                        component: () => import('../View/truongphong/TP_ChamCong.vue'),
                        meta: { index: 3 }
                  },
                  {
                        path: 'phanca',
                        name: 'tp-phan-ca',
                        component: () => import('../views/attendance/ShiftSchedulingView.vue'),
                        meta: { index: 3.5 }
                  },
                  {
                        path: 'nghiphep',
                        name: 'tp-nghi-phep',
                        component: () => import('../View/truongphong/TP_NghiPhep.vue'),
                        meta: { index: 4 }
                  },
                  {
                        path: 'bangluong',
                        name: 'tp-bang-luong',
                        component: () => import('../View/truongphong/TP_BangLuong.vue'),
                        meta: { index: 5 }
                  },
                  {
                        path: 'tuyendung',
                        name: 'tp-tuyen-dung',
                        component: () => import('../View/truongphong/TP_TuyenDung.vue'),
                        meta: { index: 6 }
                  },
                  {
                        path: 'danhgiaungvien',
                        name: 'tp-danh-gia',
                        component: () => import('../View/truongphong/DanhGiaUngVien.vue'),
                        meta: { index: 10 }
                  },
                  {
                        path: 'taisan',
                        name: 'tp-tai-san',
                        component: () => import('../View/truongphong/TP_TaiSan.vue'),
                        meta: { index: 7 }
                  },
                  {
                        path: 'hopdong',
                        name: 'tp-hop-dong',
                        component: () => import('../View/truongphong/TP_HopDong.vue'),
                        meta: { index: 8 }
                  },
                  {
                        path: 'hoso',
                        name: 'tp-ho-so',
                        component: () => import('../View/truongphong/TP_Profile.vue'),
                        meta: { index: 9 }
                  }
            ]
      }
]

const router = createRouter({
      history: createWebHistory(),
      routes
})

const roleHomeMap = {
      admin: '/admin',
      hr: '/admin',
      director: '/giamdoc',
      manager: '/truongphong',
      employee: '/nhanvien',
}

const requiredRoleByPath = (path) => {
      if (path.startsWith('/admin')) return ['admin', 'hr']
      if (path.startsWith('/giamdoc')) return ['director']
      if (path.startsWith('/truongphong')) return ['manager']
      if (path.startsWith('/nhanvien')) return ['employee']
      return []
}

router.beforeEach((to) => {
      const isPublic = to.path === '/login' || to.path === '/landing'
      const token = getAccessToken()
      const role = getCurrentUserRole()

      // 1. No token -> Force login unless already on a public page
      if (!token && !isPublic) {
            clearAuthSession()
            return '/login'
      }

      // 2. Already logged in and trying to go to login page
      if (token && to.path === '/login') {
            const home = roleHomeMap[role]
            if (home) return home
            
            // If role is invalid but we have a token, something is wrong.
            // Clear session to break potential loops and stay on login.
            clearAuthSession()
            return true
      }

      // 3. Logged in -> Check role permissions for non-public pages
      if (token && !isPublic) {
            const acceptedRoles = requiredRoleByPath(to.path)
            
            // If the path requires specific roles and user doesn't have them
            if (acceptedRoles.length > 0 && !acceptedRoles.includes(role)) {
                  const home = roleHomeMap[role]
                  if (home && home !== to.path) return home
                  
                  // If even the home is unreachable or user has no valid role, force logout
                  clearAuthSession()
                  return '/login'
            }
      }

      return true
})

export default router
