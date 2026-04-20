<template>
  <div
    class="bg-[#F8FAFC] dark:bg-[#F8FAFC] font-sans antialiased text-slate-900 dark:text-slate-100 min-h-screen flex flex-col"
  >
    <div class="relative flex min-h-screen w-full flex-row overflow-hidden">
      <!-- Left side banner -->
      <div class="hidden lg:flex lg:w-1/2 relative bg-slate-900">
        <div
          class="absolute inset-0 bg-cover bg-center opacity-80"
          data-alt="Modern corporate office with glass walls and bright lighting"
          style="
            background-image: url('https://images.unsplash.com/photo-1497366216548-37526070297c?ixlib=rb-4.0.3&auto=format&fit=crop&w=1200&q=80');
          "
        ></div>
        <div
          class="absolute inset-0 bg-gradient-to-t from-blue-700/90 to-blue-600/40 mix-blend-multiply flex flex-col"
        ></div>
        <!-- Left Side Content -->
        <div
          class="relative z-10 flex flex-col justify-between p-12 h-full text-white w-full"
        >
          <div class="flex items-center gap-2">
            <h1 class="text-2xl font-bold tracking-tight text-white">HRM Portal</h1>
          </div>
          <div class="mb-12">
            <h2 class="text-4xl font-bold mb-6 leading-tight">
              Quản lý nhân sự <br />với sự tự tin.
            </h2>
            <p class="text-lg text-blue-100 max-w-md leading-relaxed">
              Hợp lý hóa quy trình nhân sự, trao quyền cho nhân viên và xây dựng văn hóa
              làm việc tốt hơn với nền tảng tất cả trong một của chúng tôi.
            </p>
          </div>
        </div>
      </div>

      <!-- Right side login -->
      <div
        class="w-full lg:w-1/2 flex items-center justify-center bg-[#F8FAFC] dark:bg-[#F8FAFC] p-6 lg:p-12 relative"
      >
        <div class="absolute top-0 right-0 p-8">
          <div class="h-32 w-32 rounded-full bg-blue-600/5 blur-3xl"></div>
        </div>
        <div
          class="w-full max-w-[480px] bg-white dark:bg-white shadow-[0_8px_30px_rgb(0,0,0,0.08)] dark:shadow-[0_8px_30px_rgb(0,0,0,0.08)] rounded-2xl p-8 md:p-10 border border-slate-100 dark:border-slate-100 relative z-10"
        >
          <h1
            class="text-2xl font-bold text-center text-slate-900 dark:text-slate-900 lg:hidden mb-8 tracking-tight"
          >
            HRM Connect
          </h1>
          <div class="mb-8 text-center lg:text-left">
            <h1
              class="text-3xl font-black text-slate-900 dark:text-slate-900 tracking-tight mb-2"
            >
              Chào mừng trở lại!
            </h1>
            <p class="text-slate-500 dark:text-slate-500 text-sm">
              Vui lòng nhập thông tin của bạn để đăng nhập.
            </p>
            <div
              class="mt-4 p-4 bg-blue-50/80 text-blue-800 rounded-xl text-sm text-left border border-blue-100/50"
            >
              <p class="font-bold mb-2 flex items-center gap-2">
                <span class="material-symbols-outlined text-blue-600 text-[18px]"
                  >info</span
                >
                Tài khoản trải nghiệm:
              </p>
              <ul class="list-disc pl-8 space-y-1 text-blue-700">
                <li>Admin: <b>hai.do@company.com</b></li>
                <li>HR: <b>mai.tran@company.com</b></li>
                <li>Trưởng phòng: <b>an.nguyen@company.com</b></li>
                <li>Nhân viên: <b>huong.pham@company.com</b></li>
                <li class="mt-1 border-t border-blue-200/50 pt-1">
                  <i>Mật khẩu mặc định: <b>employee_code</b> (VD: NV0004)</i>
                </li>
              </ul>
            </div>
          </div>

          <form class="flex flex-col gap-5" @submit.prevent="handleLogin">
            <div class="space-y-1.5 text-left">
              <label
                class="text-sm font-semibold text-slate-700 dark:text-slate-700"
                for="company_email"
                >Email Công Ty</label
              >
              <div class="relative group">
                <div
                  class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none"
                >
                  <span
                    class="material-symbols-outlined text-slate-400 group-focus-within:text-blue-600 transition-colors duration-200 text-[20px]"
                    >mail</span
                  >
                </div>
                <input
                  v-model="companyEmail"
                  class="block w-full rounded-xl border border-slate-200 dark:border-slate-200 bg-slate-50 dark:bg-slate-50 text-slate-900 dark:text-slate-900 pl-11 pr-4 py-3 text-sm focus:border-blue-600 focus:ring-4 focus:ring-blue-600/10 focus:outline-none transition-all duration-200 placeholder:text-slate-400"
                  id="company_email"
                  required
                  placeholder="Nhập Gmail của bạn"
                  type="email"
                />
              </div>
            </div>

            <div class="space-y-1.5 text-left">
              <label
                class="text-sm font-semibold text-slate-700 dark:text-slate-700"
                for="password"
                >Mật khẩu</label
              >
              <div class="relative group">
                <div
                  class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none"
                >
                  <span
                    class="material-symbols-outlined text-slate-400 group-focus-within:text-blue-600 transition-colors duration-200 text-[20px]"
                    >lock</span
                  >
                </div>
                <input
                  v-model="password"
                  class="block w-full rounded-xl border border-slate-200 dark:border-slate-200 bg-slate-50 dark:bg-slate-50 text-slate-900 dark:text-slate-900 pl-11 pr-10 py-3 text-sm focus:border-blue-600 focus:ring-4 focus:ring-blue-600/10 focus:outline-none transition-all duration-200 placeholder:text-slate-400"
                  id="password"
                  required
                  placeholder="Nhập mật khẩu"
                  :type="showPassword ? 'text' : 'password'"
                />
                <button
                  @click="showPassword = !showPassword"
                  class="absolute inset-y-0 right-0 pr-3.5 flex items-center cursor-pointer text-slate-400 hover:text-slate-600 dark:hover:text-slate-600 transition-colors"
                  type="button"
                >
                  <span class="material-symbols-outlined text-[20px]">{{
                    showPassword ? "visibility" : "visibility_off"
                  }}</span>
                </button>
              </div>
            </div>

            <div
              v-if="errorMsg"
              class="text-red-500 text-sm font-medium text-center bg-red-50 p-2 rounded-lg border border-red-100"
            >
              {{ errorMsg }}
            </div>

            <div class="flex items-center justify-between mt-1">
              <label class="flex items-center gap-2 cursor-pointer group">
                <div class="relative flex items-center">
                  <input
                    v-model="rememberLogin"
                    class="peer h-4 w-4 cursor-pointer appearance-none rounded border border-slate-300 dark:border-slate-300 bg-white dark:bg-white checked:border-blue-600 checked:bg-blue-600 transition-all focus:ring-2 focus:ring-blue-600/20 focus:outline-none"
                    type="checkbox"
                  />
                  <span
                    class="material-symbols-outlined absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 text-[14px] text-white opacity-0 peer-checked:opacity-100 pointer-events-none"
                    >check</span
                  >
                </div>
                <span
                  class="text-sm font-medium text-slate-600 dark:text-slate-600 group-hover:text-slate-900 dark:group-hover:text-slate-900 transition-colors"
                  >Ghi nhớ đăng nhập</span
                >
              </label>
              <button
                class="text-sm font-semibold text-blue-600 hover:text-blue-700 dark:text-blue-600 dark:hover:text-blue-700 transition-colors"
                type="button"
                @click="openForgotPassword"
              >
                Quên mật khẩu?
              </button>
            </div>

            <button
              :disabled="isLoggingIn"
              class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3.5 px-4 rounded-xl shadow-lg shadow-blue-600/30 hover:shadow-blue-600/40 transition-all duration-200 transform active:scale-[0.98] mt-4 flex justify-center items-center gap-2 group disabled:opacity-70 disabled:cursor-not-allowed"
              type="submit"
            >
              <span v-if="!isLoggingIn">Đăng nhập ngay</span>
              <span v-else>Đang xác thực...</span>
              <span
                v-if="!isLoggingIn"
                class="material-symbols-outlined text-[20px] group-hover:translate-x-1 transition-transform"
                >arrow_forward</span
              >
              <span v-else class="material-symbols-outlined text-[20px] animate-spin"
                >refresh</span
              >
            </button>
          </form>

          <div
            v-if="showForgotModal"
            class="fixed inset-0 z-50 flex items-center justify-center px-4"
            @click.self="closeForgotPassword"
          >
            <div class="absolute inset-0 bg-slate-900/50 backdrop-blur-[1px]"></div>
            <div
              class="relative z-10 w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl border border-slate-100"
            >
              <div class="flex items-start justify-between gap-3">
                <div>
                  <h3 class="text-lg font-bold text-slate-900">Khôi phục mật khẩu</h3>
                  <p class="mt-1 text-sm text-slate-500">
                    Nhập email công ty để nhận hướng dẫn đặt lại mật khẩu.
                  </p>
                </div>
                <button
                  type="button"
                  class="text-slate-400 hover:text-slate-600"
                  @click="closeForgotPassword"
                >
                  <span class="material-symbols-outlined text-[20px]">close</span>
                </button>
              </div>

              <form class="mt-5 space-y-4" @submit.prevent="submitForgotPassword">
                <div>
                  <label class="text-sm font-semibold text-slate-700" for="forgot-email"
                    >Email công ty</label
                  >
                  <input
                    id="forgot-email"
                    v-model="forgotEmail"
                    type="email"
                    required
                    class="mt-1 w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm text-slate-900 focus:border-blue-600 focus:ring-4 focus:ring-blue-600/10 focus:outline-none"
                    placeholder="name@company.com"
                  />
                </div>
                <p
                  v-if="forgotError"
                  class="text-sm text-red-600 bg-red-50 border border-red-100 rounded-lg px-3 py-2"
                >
                  {{ forgotError }}
                </p>
                <p
                  v-if="forgotSuccess"
                  class="text-sm text-emerald-700 bg-emerald-50 border border-emerald-100 rounded-lg px-3 py-2"
                >
                  {{ forgotSuccess }}
                </p>
                <div class="flex items-center justify-end gap-2">
                  <button
                    type="button"
                    class="px-4 py-2 rounded-lg text-sm font-semibold text-slate-600 hover:bg-slate-100"
                    @click="closeForgotPassword"
                  >
                    Đóng
                  </button>
                  <button
                    type="submit"
                    :disabled="isSendingForgot"
                    class="px-4 py-2 rounded-lg text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 disabled:opacity-70 disabled:cursor-not-allowed"
                  >
                    {{ isSendingForgot ? "Đang gửi..." : "Gửi yêu cầu" }}
                  </button>
                </div>
              </form>
            </div>
          </div>
        </div>

        <div class="absolute bottom-6 text-center w-full">
          <p class="text-xs text-slate-400 dark:text-slate-400 font-medium">
            &copy; 2024 HRM System Inc. Mọi quyền được bảo lưu.
          </p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from "vue";
import { useRouter } from "vue-router";
import { BE_API_BASE } from "@/services/runtimeConfig.js";
import { clearAuthSession, persistAuthSession } from "@/services/session.js";
import { deepFixMojibake } from "@/utils/textEncodingFixed.js";

const router = useRouter();

const companyEmail = ref("");
const password = ref("");
const showPassword = ref(false);
const errorMsg = ref("");
const isLoggingIn = ref(false);
const rememberLogin = ref(true);
const showForgotModal = ref(false);
const forgotEmail = ref("");
const forgotError = ref("");
const forgotSuccess = ref("");
const isSendingForgot = ref(false);
const ATTENDANCE_DEVICE_STORAGE_KEY = "attendance_mobile_device_id";

// Force Light Mode on Login Page
onMounted(() => {
  document.documentElement.classList.remove("dark");
  localStorage.removeItem("theme"); // Fail-safe if a theme key is used
  const remembered = localStorage.getItem("rememberLogin");
  if (remembered !== null) {
    rememberLogin.value = remembered === "1";
  }
});

const mapRoleFromBackend = (roles = []) => {
  const roleCodes = (Array.isArray(roles) ? roles : [])
    .map((r) => String(r?.role_code || "").toUpperCase())
    .filter(Boolean);
  if (roleCodes.includes("ADMIN")) return "admin";
  if (roleCodes.includes("HR")) return "hr";
  if (roleCodes.includes("DIRECTOR")) return "director";
  if (roleCodes.includes("MANAGER")) return "manager";
  return "employee";
};

const mapRouteFromRole = (uiRole) => {
  if (uiRole === "admin" || uiRole === "hr") return "/admin";
  if (uiRole === "director") return "/giamdoc";
  if (uiRole === "manager") return "/truongphong";
  return "/nhanvien";
};

const buildCandidateApiBases = () => {
  if (String(BE_API_BASE || "").startsWith("/")) {
    return ["/api/v1"];
  }
  const bases = [];
  try {
    const parsed = new URL(BE_API_BASE);
    const hosts = new Set([parsed.hostname]);
    if (parsed.hostname === "127.0.0.1") hosts.add("localhost");
    if (parsed.hostname === "localhost") hosts.add("127.0.0.1");
    if (parsed.hostname === "0.0.0.0") {
      hosts.add("127.0.0.1");
      hosts.add("localhost");
    }

    hosts.forEach((host) => {
      const clone = new URL(parsed.toString());
      clone.hostname = host;
      bases.push(clone.toString().replace(/\/+$/, ""));
    });
  } catch {
    bases.push(BE_API_BASE);
  }
  return Array.from(new Set(bases));
};

const parseJsonSafe = async (response) => {
  const text = await response.text();
  try {
    if (!text) return {};
    const obj = JSON.parse(text);
    return deepFixMojibake(obj);
  } catch (err) {
    console.error("JSON Parse Error:", err, "Raw text:", text);
    return { message: text || "Empty response" };
  }
};

const ensureAttendanceDeviceId = () => {
  const fromStorage = localStorage.getItem(ATTENDANCE_DEVICE_STORAGE_KEY);
  if (fromStorage) return fromStorage;
  const generated = `web-${Date.now()}-${Math.random().toString(16).slice(2, 10)}`;
  localStorage.setItem(ATTENDANCE_DEVICE_STORAGE_KEY, generated);
  return generated;
};

const detectPlatformCode = () => {
  const ua = String(navigator.userAgent || "").toLowerCase();
  if (ua.includes("iphone") || ua.includes("ipad") || ua.includes("ios")) return "IOS";
  return "ANDROID";
};

const readGeoPosition = async () => {
  if (!navigator.geolocation) return null;
  return new Promise((resolve) => {
    navigator.geolocation.getCurrentPosition(
      (position) =>
        resolve({
          lat: position.coords.latitude,
          lng: position.coords.longitude,
          accuracy_m: position.coords.accuracy || 999,
        }),
      () => resolve(null),
      {
        enableHighAccuracy: true,
        timeout: 10000,
        maximumAge: 0,
      }
    );
  });
};

const bootstrapAttendanceDevice = async (accessToken, user) => {
  const employeeId = Number(user?.employee_id || 0);
  if (!employeeId || !accessToken) return;

  try {
    const location = await readGeoPosition();
    if (!location) return;

    await fetch(BE_API_BASE + "/attendance/bootstrap", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        Authorization: `Bearer ${accessToken}`,
      },
      body: JSON.stringify({
        employee_id: employeeId,
        device_id: ensureAttendanceDeviceId(),
        platform: detectPlatformCode(),
        lat: location.lat,
        lng: location.lng,
        accuracy_m: location.accuracy_m,
      }),
    });
  } catch (error) {
    console.warn("Attendance bootstrap warning:", error);
  }
};

const tryBackendLogin = async () => {
  // 1. Phá vỡ vòng lặp redirect bằng cách dọn dẹp storage trước khi thử đăng nhập
  localStorage.clear();
  sessionStorage.clear();

  const candidates = buildCandidateApiBases();
  let hasCredentialError = false;
  let lastError = "";

  for (const apiBase of candidates) {
    // 2. Sử dụng AbortController để timeout request sau 8 giây (ngăn treo trang)
    const controller = new AbortController();
    const timeoutId = setTimeout(() => controller.abort(), 8000);

    try {
      const response = await fetch(apiBase + "/auth/login", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        signal: controller.signal,
        body: JSON.stringify({
          company_email: companyEmail.value,
          password: password.value,
        }),
      });

      clearTimeout(timeoutId);
      const payload = await parseJsonSafe(response);

      // Xử lý payload rỗng hoặc lỗi từ phía Server
      if (!payload || Object.keys(payload).length === 0) {
        console.error("Empty payload from:", apiBase);
        lastError = `Server tại ${apiBase} trả về dữ liệu rỗng.`;
        continue;
      }

      if (!response.ok) {
        if ([400, 401, 403, 422].includes(response.status)) {
          hasCredentialError = true;
          lastError = payload.error || payload.message || "Tài khoản hoặc mật khẩu sai";
          continue;
        }
        const serverMessage = String(payload?.message || payload?.error || "").trim();
        lastError = serverMessage
          ? serverMessage
          : `Lỗi máy chủ (HTTP ${response.status})`;
        continue;
      }

      // Handle BE {success: false} even on HTTP 200
      if (payload.success === false) {
        hasCredentialError = true;
        lastError = payload.error || payload.message || "Đăng nhập thất bại";
        continue;
      }

      // Chuẩn hóa dữ liệu trả về từ các phiên bản API khác nhau
      const finalData = payload?.data || payload;
      const tokenCandidates = [
        finalData?.access_token,
        finalData?.token,
        payload?.access_token,
        payload?.token,
        payload?.data?.access_token,
        payload?.data?.token,
      ];
      const accessToken = tokenCandidates.find((t) => t && typeof t === "string");

      const normUser =
        finalData?.user ||
        payload?.user ||
        finalData?.employee ||
        payload?.employee ||
        payload?.data?.user ||
        payload?.data?.employee ||
        (accessToken ? finalData : null);

      const rawEmployeeId =
        normUser?.employee_id ||
        normUser?.id ||
        normUser?.user_id ||
        normUser?.employeeId ||
        normUser?.sub;

      const normalizedEmployeeId = Number(rawEmployeeId);
      const hasValidEmployeeId =
        Number.isFinite(normalizedEmployeeId) && normalizedEmployeeId > 0;

      if (accessToken && hasValidEmployeeId) {
        const finalUser = normUser && typeof normUser === "object" ? normUser : {};
        return {
          access_token: accessToken,
          user: {
            ...finalUser,
            employee_id: normalizedEmployeeId,
          },
        };
      }

      console.warn("Unexpected success payload structure:", payload);
      lastError =
        payload?.error ||
        payload?.message ||
        "Phản hồi từ server không đúng định dạng (thiếu Token/User)";
    } catch (error) {
      clearTimeout(timeoutId);
      console.error("Fetch error @", apiBase, ":", error);

      // 3. Handle lỗi connectivity và timeout một cách tường minh
      if (error.name === "AbortError") {
        lastError = `Kết nối tới máy chủ ${apiBase} bị quá hạn (8 giây).`;
      } else if (!window.navigator.onLine) {
        lastError = "Bạn đang ngoại tuyến. Vui lòng kiểm tra kết nối mạng.";
      } else {
        lastError = `Lỗi kết nối tới máy chủ (${apiBase}): ${error.message}`;
      }
    }
  }

  if (hasCredentialError) return null;
  throw new Error(lastError || "Không thể kết nối tới Backend API");
};

const handleLogin = async () => {
  errorMsg.value = "";
  isLoggingIn.value = true;

  try {
    const backendData = await tryBackendLogin();
    if (backendData?.access_token && backendData?.user) {
      const user = backendData.user;
      const uiRole = mapRoleFromBackend(user.roles || []);
      const targetRoute = mapRouteFromRole(uiRole);
      clearAuthSession();
      persistAuthSession({
        remember: rememberLogin.value,
        uiRole,
        user,
        token: backendData.access_token,
      });
      await bootstrapAttendanceDevice(backendData.access_token, user);
      router.push(targetRoute);
      return;
    }

    errorMsg.value = "Tài khoản hoặc mật khẩu không chính xác!";
  } catch (error) {
    console.error("Login error:", error);
    const message = String(error?.message || "").trim();
    if (!message || message === "Cannot reach BE API") {
      errorMsg.value = `Không kết nối được BE API (${BE_API_BASE}).`;
    } else {
      errorMsg.value = message;
    }
  } finally {
    isLoggingIn.value = false;
  }
};

const openForgotPassword = () => {
  forgotEmail.value = companyEmail.value || "";
  forgotError.value = "";
  forgotSuccess.value = "";
  showForgotModal.value = true;
};

const closeForgotPassword = () => {
  showForgotModal.value = false;
  forgotError.value = "";
  forgotSuccess.value = "";
};

const submitForgotPassword = async () => {
  forgotError.value = "";
  forgotSuccess.value = "";
  isSendingForgot.value = true;
  try {
    const response = await fetch(BE_API_BASE + "/auth/forgot-password", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        company_email: forgotEmail.value,
      }),
    });
    const payload = await parseJsonSafe(response);
    if (!response.ok || payload?.success === false) {
      throw new Error(payload?.message || "Không thể gửi yêu cầu đặt lại mật khẩu.");
    }
    forgotSuccess.value = String(
      payload?.message || "Đã gửi hướng dẫn khôi phục mật khẩu."
    );
  } catch (error) {
    forgotError.value = String(error?.message || "Gửi yêu cầu thất bại.");
  } finally {
    isSendingForgot.value = false;
  }
};
</script>

<style scoped>
/* Removed old global tailwind overrides, tailwind rules are expected to be available globally */
</style>
