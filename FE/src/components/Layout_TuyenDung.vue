<template>
  <div class="container-fluid py-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-3">
      <div>
        <h1 class="h3 mb-1 text-dark fw-bold">Quản lý Tuyển dụng & Ứng viên</h1>
        <p class="text-muted small mb-0">Hệ thống auto sàng lọc và đánh giá ứng viên bằng AI</p>
      </div>
      <button class="btn btn-primary d-flex align-items-center gap-2 fw-medium rounded-3 px-4 py-2">
        <span class="material-symbols-outlined fs-5">add</span>
        Đăng tin tuyển dụng
      </button>
    </div>

    <!-- Tabs -->
    <ul class="nav nav-tabs border-bottom-0 mb-4 gap-3 fw-medium">
      <li class="nav-item">
        <router-link to="/admin/tuyen-dung" :class="['nav-link px-1 pb-2 d-flex align-items-center gap-2 cursor-pointer', isTabActive('all') ? 'active rounded-0 border-0 border-bottom border-primary border-3 text-primary' : 'text-muted border-0']">
          <span class="material-symbols-outlined fs-5">group</span>
          Tất cả ứng viên
        </router-link>
      </li>
      <li class="nav-item">
        <router-link to="/admin/tuyen-dung/lich-phong-van" :class="['nav-link px-1 pb-2 d-flex align-items-center gap-2 cursor-pointer', route.path.includes('lich-phong-van') ? 'active rounded-0 border-0 border-bottom border-primary border-3 text-primary' : 'text-muted border-0']">
          <span class="material-symbols-outlined fs-5">calendar_month</span>
          Lịch phỏng vấn
        </router-link>
      </li>
      <li class="nav-item">
        <router-link to="/admin/tuyen-dung?status=pass" :class="['nav-link px-1 pb-2 d-flex align-items-center gap-2 cursor-pointer', isTabActive('pass') ? 'active rounded-0 border-0 border-bottom border-primary border-3 text-primary' : 'text-muted border-0']">
          <span class="material-symbols-outlined fs-5">check_circle</span>
          Trúng tuyển (Pass)
        </router-link>
      </li>
      <li class="nav-item">
        <router-link to="/admin/tuyen-dung?status=fail" :class="['nav-link px-1 pb-2 d-flex align-items-center gap-2 cursor-pointer', isTabActive('fail') ? 'active rounded-0 border-0 border-bottom border-primary border-3 text-primary' : 'text-muted border-0']">
          <span class="material-symbols-outlined fs-5">cancel</span>
          Từ chối (Fail)
        </router-link>
      </li>
      <li class="nav-item">
        <a class="nav-link text-muted border-0 px-1 pb-2 d-flex align-items-center gap-2 cursor-pointer">
          <span class="material-symbols-outlined fs-5">mail</span>
          Nhật ký Email
        </a>
      </li>
    </ul>

    <!-- Child View injected here -->
    <router-view></router-view>
  </div>
</template>

<script setup>
import { useRoute } from 'vue-router'

const route = useRoute()

const isTabActive = (status) => {
  if (route.path.includes('lich-phong-van')) return false;
  
  // If no status query, then 'all' is active
  if (status === 'all' && !route.query.status) return true;
  
  return route.query.status === status;
}
</script>

<style scoped>
.cursor-pointer { cursor: pointer; }
.nav-link:hover { color: #3b82f6 !important; }
</style>
