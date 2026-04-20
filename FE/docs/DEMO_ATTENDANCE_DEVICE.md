# Kịch bản demo: Chấm công một chạm & Cảnh báo rủi ro

Thời lượng gợi ý: **15–20 phút**. Chuẩn bị: tài khoản nhân viên, tài khoản quản lý/HR, trình duyệt có GPS (hoặc công cụ giả lập vị trí), backend đã bật API `/attendance/precheck`, `/attendance/checkin`, `/attendance/checkout`, `/device/reverify`, `/risk-alerts`.

**Lưu ý kỹ thuật (BE):** Vùng an toàn mặc định trong code backend tính khoảng cách tới tọa độ văn phòng mẫu (OFFICE_LAT/LNG). Để demo “trong vùng / ngoài vùng”, dùng công cụ đổi vị trí trình duyệt hoặc chỉnh tọa độ test cho phù hợp.

---

## Phần A — Nhân viên (portal)

| # | Tình huống | Các bước | Kỳ vọng giao diện |
|---|------------|----------|-------------------|
| A1 | Lần đầu / thiết bị chưa tin cậy | Đăng nhập nhân viên → menu **Chấm công nhanh** (`/nhanvien/chamcong-1cham`) → bật **Đi làm** (cho phép GPS) | Nếu BE trả `DEVICE_NOT_TRUSTED`: thông báo tiếng Việt có dấu, khóa hai nút chấm, hiện khối **Mã OTP** + **Xác minh lại** |
| A2 | Sau khi xác minh OTP (nếu A1 xảy ra) | Nhập OTP đúng → **Xác minh lại** | Thông báo thành công kiểu “Thiết bị đã được xác minh…”, có thể chấm công tiếp |
| A3 | Trong vùng GREEN | Giả lập vị trí trong bán kính xanh (theo cấu hình BE) → **Đi làm** hoặc **Ra về** | Dải trạng thái **GREEN**, precheck OK → chấm công thành công, dòng “Lần chấm công gần nhất” cập nhật |
| A4 | Vàng YELLOW | Vị trí lệch nhẹ / độ chính xác kém (theo BE) → chấm công | Vẫn chấm được; banner **vàng** nhắc có thể kiểm tra sau; có thể có cảnh báo trên `/risk-alerts` |
| A5 | Ngoài vùng | Giả lập vị trí xa điểm chuẩn → **Đi làm** | Không có `precheck_token`: hai nút **Đi làm** / **Ra về** bị khóa; khối **Chỉ xem thông tin** với link Lương / Nghỉ phép / Hồ sơ; nút **Thử lại** vẫn bấm được |
| A6 | GPS tắt / từ chối quyền | Tắt quyền định vị → **Đi làm** | Một thông báo đơn giản (bật GPS / cho phép), **Thử lại** để thử lại |
| A7 | Mã QR khu vực (tuỳ chọn) | Nhập “Mã khu vực từ QR” nếu quy trình công ty dùng → chấm lại | Giá trị được lưu (localStorage); gửi kèm `qr_area_code` trong precheck nếu có nhập |

---

## Phần B — Quản lý / HR

| # | Tình huống | Các bước | Kỳ vọng giao diện |
|---|------------|----------|-------------------|
| B1 | Xem danh sách cảnh báo | Đăng nhập quản lý → **API UI** → **Chấm công — Cảnh báo rủi ro** (`/admin/.../api-ui/attendance/risk-alerts`) | Bảng có cột **Diễn giải** (tiếng Việt đầy đủ dấu) + cột **Mã**; lọc mức độ / trạng thái / ngày |
| B2 | Hai thiết bị cùng user (nếu BE tạo log) | Sau khi A1/A4 hoặc kịch bản đổi thiết bị nhanh trong BE | Dòng cảnh báo tương ứng (ví dụ `MULTI_DEVICE_SHORT_TIME`), diễn giải đọc được |

---

## Thứ tự demo nhanh (gợi ý)

1. NV: **Chấm công nhanh** → thử **Đi làm** (GPS bật) → xử lý luồng tin cậy / OTP nếu có.  
2. NV: đổi vị trí → trong vùng → chấm thành công (GREEN).  
3. NV: đổi vị trí → ngoài vùng → quan sát khóa nút + **Chỉ xem thông tin** → **Thử lại** sau khi vào lại vùng.  
4. QL: mở **Cảnh báo rủi ro** → lọc **Đỏ** / **Vàng** → đối chiếu diễn giải tiếng Việt.

---

## Gợi ý kiểm tra lỗi chữ `?` (encoding)

- Trang `index.html` đặt `lang="vi"` và `charset="UTF-8"`.  
- Request gửi `Accept: application/json; charset=utf-8`.  
- Nếu vẫn thấy `?` trong JSON: kiểm tra phản hồi BE (`Content-Type` UTF-8) và file cấu hình PHP/DB.
