<template>
  <section class="module-page">
    <div class="module-card">
      <h1 class="module-title">API Workflow Hub</h1>
      <p class="module-subtitle">
        So do man hinh duoc sinh tu backend routes: List - Detail - Create/Edit cho HRM, Attendance, Recruitment, Payroll, Asset, Communication, Service va System.
      </p>
    </div>

    <div class="module-card">
      <div class="row g-3">
        <div class="col-12 col-lg-6" v-for="module in modules" :key="module.title">
          <div class="module-detail-item h-100">
            <h2 class="h5 mb-2">{{ module.title }}</h2>
            <p class="text-secondary mb-2">{{ module.desc }}</p>
            <ul class="mb-3">
              <li v-for="flow in module.flows" :key="flow">{{ flow }}</li>
            </ul>
            <div class="d-flex flex-wrap gap-2">
              <router-link v-for="route in module.routes" :key="route.to" :to="route.to" class="btn btn-outline-primary">
                {{ route.label }}
              </router-link>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
const modules = [
  {
    title: 'HRM Core',
    desc: 'Nhan vien, phong ban, hop dong, chuc danh, lich su hop dong',
    flows: ['Nhan vien: List/Add/Edit/Detail/Delete', 'Phong ban: List/Add/Edit/Detail/Delete', 'Hop dong: List/Add/Edit/Detail', 'Chuc danh: List/Add/Edit/Detail/Delete', 'Lich su hop dong: List/Add'],
    routes: [
      { label: 'Nhan vien', to: '/admin/api-ui/hrm/employees' },
      { label: 'Phong ban', to: '/admin/api-ui/hrm/departments' },
      { label: 'Hop dong', to: '/admin/api-ui/hrm/contracts' },
      { label: 'Chuc danh', to: '/admin/api-ui/hrm/positions' },
      { label: 'LS Hop dong', to: '/admin/api-ui/hrm/contract-change-logs' },
    ],
  },
  {
    title: 'HRM - Don tu & Yeu cau',
    desc: 'Loai don, don tu chung (nghi phep, tang ca, cong tac...)',
    flows: ['Loai don: List/Add/Edit/Detail/Delete', 'Don tu: List/Add/Edit/Detail/Delete'],
    routes: [
      { label: 'Loai don', to: '/admin/api-ui/hrm/request-types' },
      { label: 'Don tu', to: '/admin/api-ui/hrm/requests' },
    ],
  },
  {
    title: 'Time & Attendance',
    desc: 'Cham cong, don nghi, risk alerts, one-tap mobile, tang ca, so du phep',
    flows: ['Cham cong: List/Add/Edit/Detail/Delete', 'Don nghi: List/Add/Edit/Detail/Delete', 'Risk alerts: List/Filter', 'One-tap: Di lam/Ra ve + reverify', 'Tang ca: List/Add/Edit/Detail/Delete', 'So du phep: List/Detail'],
    routes: [
      { label: 'Cham cong', to: '/admin/api-ui/attendance/records' },
      { label: 'Don nghi', to: '/admin/api-ui/attendance/leave-requests' },
      { label: 'Risk alerts', to: '/admin/api-ui/attendance/risk-alerts' },
      { label: 'One-tap mobile', to: '/admin/api-ui/attendance/mobile-one-tap' },
      { label: 'Tang ca', to: '/admin/api-ui/attendance/overtime' },
      { label: 'So du phep', to: '/admin/api-ui/attendance/leave-balances' },
    ],
  },
  {
    title: 'Recruitment',
    desc: 'Vi tri tuyen dung, ung vien, lich phong van',
    flows: ['Vi tri: List/Add/Edit/Detail/Delete', 'Ung vien: List/Add/Edit/Detail', 'Phong van: List/Add/Edit/Detail'],
    routes: [
      { label: 'Vi tri', to: '/admin/api-ui/recruitment/positions' },
      { label: 'Ung vien', to: '/admin/api-ui/recruitment/candidates' },
      { label: 'Phong van', to: '/admin/api-ui/recruitment/interviews' },
    ],
  },
  {
    title: 'Payroll',
    desc: 'Ky luong, chi tiet luong, khoan phu cap/khau tru, dieu chinh luong',
    flows: ['Ky luong: List/Add/Edit/Detail (+Dong ky)', 'Chi tiet luong: List/Add/Edit/Detail', 'Khoan luong: List/Add/Edit/Detail/Delete', 'Dieu chinh: List/Add/Edit/Detail'],
    routes: [
      { label: 'Ky luong', to: '/admin/api-ui/payroll/periods' },
      { label: 'Chi tiet luong', to: '/admin/api-ui/payroll/details' },
      { label: 'Khoan luong', to: '/admin/api-ui/payroll/breakdowns' },
      { label: 'Dieu chinh luong', to: '/admin/api-ui/payroll/adjustments' },
    ],
  },
  {
    title: 'Asset Management',
    desc: 'Tai san cong ty, cap phat / thu hoi',
    flows: ['Tai san: List/Add/Edit/Detail', 'Cap phat: List/Add'],
    routes: [
      { label: 'Tai san', to: '/admin/api-ui/asset/assets' },
      { label: 'Cap phat', to: '/admin/api-ui/asset/assignments' },
    ],
  },
  {
    title: 'Internal Communication',
    desc: 'Danh muc tin, tin tuc, chinh sach noi bo',
    flows: ['Danh muc tin: List/Add/Edit/Detail/Delete', 'Tin tuc: List/Add/Edit/Detail/Delete', 'Chinh sach: List/Add/Edit/Detail/Delete'],
    routes: [
      { label: 'Danh muc tin', to: '/admin/api-ui/comm/news-categories' },
      { label: 'Tin tuc', to: '/admin/api-ui/comm/news' },
      { label: 'Chinh sach', to: '/admin/api-ui/comm/policies' },
    ],
  },
  {
    title: 'Service & System',
    desc: 'Ticket dich vu noi bo, thong bao he thong, cai dat',
    flows: ['Ticket: List/Add/Edit/Detail', 'Thong bao: List/Add/Edit', 'Cai dat: Thong tin chung + cau hinh thong bao'],
    routes: [
      { label: 'Ticket ho tro', to: '/admin/api-ui/service/tickets' },
      { label: 'Thong bao', to: '/admin/api-ui/system/notifications' },
      { label: 'Cai dat', to: '/admin/api-ui/system/settings' },
    ],
  },
];
</script>

