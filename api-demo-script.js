const BASE_URL = 'http://127.0.0.1/api/v1';

async function request(endpoint, method = 'GET', body = null, token = null) {
  const headers = { 'Content-Type': 'application/json' };
  if (token) headers['Authorization'] = `Bearer ${token}`;

  const res = await fetch(`${BASE_URL}${endpoint}`, {
    method,
    headers,
    body: body ? JSON.stringify(body) : null
  });
  
  const text = await res.text();
  try {
    return JSON.parse(text);
  } catch(e) {
    console.error("Non-JSON response:", text);
    throw e;
  }
}

async function runDemo() {
  console.log("=== BẮT ĐẦU DEMO TỰ ĐỘNG ===");

  // 1. Login
  console.log("\n[1] Đăng nhập với Admin...");
  const loginRes = await request('/auth/login', 'POST', {
    company_email: 'hai.do@company.com',
    password: 'NV0009'
  });
  const token = loginRes.data.access_token;
  console.log(`✅ Đăng nhập thành công! Token: ${token.substring(0, 20)}...`);

  // 2. Tạo record ở HRM -> Positions
  console.log("\n[2] HRM: Tạo vị trí công việc mới (Position)...");
  const posRes = await request('/positions', 'POST', {
    position_code: 'DEV_AI_' + Date.now(),
    position_name: 'AI Engineer Demo',
    description: 'Vị trí test tự động',
    is_manager: 0
  }, token);
  console.log(posRes.success ? `✅ Tạo Position thành công: ID ${posRes.data.id}` : `❌ Thất bại: ${posRes.message}`);

  // 3. Time & Attendance -> Overtime
  console.log("\n[3] Attendance: Gửi đơn làm thêm giờ (Overtime)...");
  const otRes = await request('/overtime-requests', 'POST', {
    overtime_date: new Date().toISOString().split('T')[0],
    start_time: '18:00',
    end_time: '20:00',
    reason: 'Test demo tự động làm thêm',
    status: 'PENDING'
  }, token);
  console.log(otRes.success ? `✅ Gửi đơn Overtime thành công: ID ${otRes.data.id}` : `❌ Thất bại: ${otRes.message}`);

  // 4. Asset -> Asset Management
  console.log("\n[4] Asset: Thêm tài sản mới...");
  const assetRes = await request('/assets', 'POST', {
    asset_code: 'MAC_' + Date.now().toString().slice(-4),
    asset_name: 'MacBook Pro M3 Max Demo',
    category: 'LAPTOP',
    purchase_date: new Date().toISOString().split('T')[0],
    status: 'AVAILABLE'
  }, token);
  console.log(assetRes.success ? `✅ Tạo Asset thành công: ID ${assetRes.data.id}` : `❌ Thất bại: ${assetRes.message}`);

  // 5. Comm -> News
  console.log("\n[5] Comm: Đăng bản tin nội bộ (News Category & News)...");
  const catRes = await request('/news-categories', 'POST', {
    name: 'Bản tin Demo ' + Date.now(),
    description: 'Sự kiện hệ thống',
    status: 'ACTIVE'
  }, token);
  
  if(catRes.success) {
    console.log(`✅ Tạo Danh mục News thành công: ID ${catRes.data.id}`);
    const newsRes = await request('/news', 'POST', {
      category_id: catRes.data.id,
      title: 'Hệ thống HRM-Stack đã sẵn sàng!',
      content: 'API Server và Vite Dev Server đang chạy ổn định. Mọi luồng hoạt động mượt mà.',
      is_published: 1
    }, token);
    console.log(newsRes.success ? `✅ Đăng News thành công: ID ${newsRes.data.id}` : `❌ Lỗi đăng News: ${newsRes.message}`);
  }

  // 6. Report
  console.log("\n=== HOÀN TẤT DEMO ===");
  console.log("Dữ liệu đã được ghi vào Database qua API và có thể xem trực tiếp trên Frontend!");
}

runDemo().catch(console.error);
