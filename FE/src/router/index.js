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
                        path: 'hoso',
                        name: 'admin-ho-so',
                        component: () => import('../View/portal_nhanvien/HoSoCaNhan.vue'),
                        meta: { index: 15 }
                  },
                  {
                        path: 'donnghiviec',
                        name: 'admin-don-nghi-viec',
                        component: () => import('../View/portal_nhanvien/GiayPhep/DonNghiViec.vue'),
                        meta: { index: 16 }
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

      if (!token && !isPublic) {
            clearAuthSession()
            return '/login'
      }

      if (token && to.path === '/login') {
            return roleHomeMap[role] || '/nhanvien'
      }

      if (!isPublic) {
            const acceptedRoles = requiredRoleByPath(to.path)
            if (acceptedRoles.length > 0 && !acceptedRoles.includes(role)) {
                  return roleHomeMap[role] || '/login'
            }
      }

      return true
})

export default router
