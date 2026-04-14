# 🤖 AI AGENT INSTRUCTIONS: HRM B2B ENTERPRISE SAAS FRONTEND GUIDELINES

**CRITICAL INSTRUCTION FOR AI AGENT:**
Before generating or modifying any component, layout, or page in this repository, you **MUST** read and strictly adhere to the following UI/UX architecture and design tokens. This is the "Single Source of Truth" for this HRM Web Application. Do NOT deviate from these standards.

## 1. Môi trường & Hệ sinh thái (Tech Stack)
- **Framework:** Vue 3 sử dụng `<script setup>` (Composition API).
- **Styling:** Kết hợp CSS Variables tùy chỉnh (định nghĩa trong `src/style.css`) và các Utility Classes của Tailwind CSS.
- **State Management & Routing:** Sử dụng các composable pattern để lưu trữ trạng thái cục bộ tĩnh (ví dụ: `useSupportStore`) và thư viện `vue-router`.

## 2. Global Design Tokens (Bắt buộc tuân thủ)
Tất cả các thành phần HTML phải sử dụng các token màu sắc dưới đây thông qua hằng số CSS `var(...)`. Không được phép sử dụng màu hard-code bừa bãi (như `bg-blue-500` hay `#123456`).

- **Background & Surfaces:**
  - Nền trang (Page Background): `bg-[var(--sys-bg-page)]`
  - Nền thẻ (Card/Surface/Modal): `bg-[var(--sys-bg-surface)]`
  - Nền khi Hover mục: `bg-[var(--sys-bg-hover)]`
  - Nền khi Active/Chọn: `bg-[var(--sys-bg-active)]`

- **Typography & Text (Font: Inter):**
  - Text chính (Tiêu đề, Dữ liệu): `text-[var(--sys-text-primary)]`
  - Text phụ/Mô tả/Metadata: `text-[var(--sys-text-secondary)]`
  - Text vô hiệu hóa: `text-[var(--sys-text-disabled)]`

- **Borders & Dividers:**
  - Viền mỏng phân cách (Line divider): `border-[var(--sys-border-subtle)]`
  - Viền mạnh (Form/Input/Card border): `border-[var(--sys-border-strong)]`

- **Thương hiệu chính (Brand - Blue 600 system):**
  - Màu nền nhẹ cho item đang chọn: `bg-[var(--sys-brand-soft)]`
  - Màu chữ sắc nét trên nền nhẹ: `text-[var(--sys-brand-solid)]`
  - Màu nút bấm/hành động chính: `bg-[var(--sys-brand-solid)] text-white`
  - Viền màu brand: `border-[var(--sys-brand-border)]` hoặc `border-[var(--sys-brand-solid)]`

- **Semantic Colors (Success, Danger, Warning, Info):**
  - Luôn sử dụng bộ 3 (Nền Soft, Viền Border, Chữ Solid tương ứng). Ví dụ với dạng thẻ Danger/Từ chối: `bg-[var(--sys-danger-soft)] text-[var(--sys-danger-text)] border border-[var(--sys-danger-border)]`.

## 3. Quy chuẩn Icon (Iconography)
- Dùng `material-symbols-outlined` hoặc `material-symbols-rounded` làm class hiển thị icon.
- **Semantic Icon Wrapper (Bắt buộc):** Các icon không được đứng trơ trọi độc lập. Hãy bọc chúng trong một thẻ `div` hoặc `button` với cấu trúc chuẩn: kích thước vuông (`w-8 h-8` hoặc `w-10 h-10`), bo góc (`rounded-md`), căn giữa (`flex items-center justify-center`), màu nền `soft` và màu chữ `text` sáng sủa. Đi kèm `shrink-0` để không bị bóp méo giao diện.
  - *Ví dụ mẫu 1:* `div class="w-10 h-10 rounded-md bg-[var(--sys-warning-soft)] text-[var(--sys-warning-text)] border border-[var(--sys-warning-border)] flex items-center justify-center shrink-0"`

## 4. Enterprise UI/UX Master Rules (Luật ngầm)
Bạn phải áp dụng tư duy "Premium B2B Anti-Mobile" - giao diện tối đa hóa độ chuyên nghiệp và mật độ khối lượng lớn cho Desktop.
- **Anti-Rounding (Độ bo góc nghiêm ngặt):** Input, Button, Dropdown bắt buộc dùng `rounded-md` (6px). Thẻ Cards, Bảng Tables, Khung lớn Modals dùng `rounded-lg` (8px). TUYỆT ĐỐI KHÔNG dùng `rounded-xl`, `rounded-2xl` hay `rounded-full` (chỉ dùng thẻ full cho avatar hoặc icon dot tròn mục trạng thái).
- **High Data Density (Mật độ dữ liệu cao):** Căn lề padding của bảng biểu cần hẹp trọn chữ (`px-4 py-2.5` cho Header, `px-4 py-3` cho từng dòng). Thẻ thống kê KPI `p-4`.
- **Anti-Text-Wrapping (Đảm bảo Layout nguyên khối):** Để giao diện không bị vỡ hoặc rớt dòng thô kệch, LUÔN dùng `whitespace-nowrap` cho Tiêu đề Header cột trong bảng, cho các Badges thẻ tên Trạng thái, các Table Cell ngắn (như Mã, Ngày tháng). Đối với Text/Đoạn văn dài, buộc dùng `truncate` hoặc `line-clamp-2`.
- **Small Typography Details (Chi tiết chữ mảnh):** Chìa khóa thiết kế "Pro B2B" nằm ở phông chữ phụ. Sử dụng cực kỳ thường xuyên cấu trúc text cực nhỏ (`text-[10px]` hoặc `text-[11px]`) đi kèm in hoa, tracking rộng và đậm nét `uppercase tracking-widest font-bold` cho các nhãn Labels phụ, tiêu đề bảng, phần caption mô tả thứ yếu để tạo sự sang trọng và gọn gàng phân mảnh.
- **Progressive Disclosure (Phân bổ hiển thị thông minh):** Tránh thiết kế nhồi nhét chữ trên màn hình chính. Nếu có thao tác phụ, chi tiết đối tượng, form chỉnh sửa... LUÔN giấu vào trong slide-up Modals đè trượt toàn màn hình (Dùng biến thư viện `<Teleport to="body">`) với lớp che phủ mờ `bg-black/50 backdrop-blur-sm`.

## 5. Dữ liệu & Render UI Component
- Kết xuất UI từ Dữ liệu Mock thông qua biến `ref([])` hoặc `computed` thay vì hard-code HTML lặp lại. Giữ HTML sạch nhất có thể.
- Sử dụng Object mapping / Helper Function cho trạng thái màu sắc biến đổi linh hoạt. Ví dụ: gọi hàm `:class="getPriorityClass(item.priority)"` để sinh ra class đuôi `bg-` / `text-` đúng chuẩn Semantic Color phía trên.

---
**FINAL COMMITMENT:** Nhắc nhở cho AI - mọi tính năng, mọi bản sửa lỗi hoặc code sinh mới được viết tiếp trong dự án này phải trải qua filter đối chiếu 5 luồng quy tắc ở trên. Hệ thống HRM này không chấp nhận thiết kế mặc định cẩu thả của frameworks thuần.
