# Báo cáo hướng dẫn demo toàn bộ module HRM Attendance / Scheduling / Timesheet / Payroll

Ngày cập nhật: `2026-04-18`  
Phạm vi tài liệu: dùng cho demo khách hàng, training nội bộ, UAT, BA handoff, QA regression.

## Xác nhận chạy thực tế trên current build

### Kết luận nhanh
- Demo theo luồng `phân ca -> publish lịch -> chấm công / đơn từ -> exception -> timesheet -> payroll-ready -> audit` đã chạy được trên BE bằng API thật.
- FE hiện tại build thành công bằng `npm run build`, route/view cho các màn hình scheduling, timesheet, exceptions, payroll export, leave, OT, audit đã tồn tại và `http://localhost/login` trả `200`.
- Dữ liệu có thay đổi thật sau demo: đã sinh mới `shift assignment`, `publish log`, `attendance`, `leave request`, `business trip request`, `overtime request`, `exception request`, `workflow audit logs`.

### Case đã verify live
- Đi làm bình thường: `attendance_id=2384`, trạng thái ngày `P`, `payroll_ready=true`.
- Quên checkout -> xin exception -> duyệt -> HR bổ sung công: `attendance_id=2385`, `exception_id=4`, trước sửa có `MISSING_CHECKOUT`, sau sửa hết flag, `payroll_ready=true`.
- Nghỉ phép cả ngày: `request_id=54`, trạng thái bảng công `AL`, `payroll_ready=true`.
- Đi công cả ngày: `request_id=55`, trạng thái bảng công `CT`, `payroll_ready=true`.
- OT hợp lệ: `overtime_id=4`, bảng công có `overtime_minutes=45`, payroll export có `ot_hours=0.75`, `payroll_ready=true`.
- Checkout muộn nhưng không có OT approved: `attendance_id=2387`, `overtime_minutes=0`, `payroll_ready=true`.
- Audit log có phát sinh thật: tail gần nhất có `audit_log_id` từ `74` đến `79` cho `REQUEST`, `SCHEDULE_ASSIGNMENT`, `SCHEDULE_SCOPE`.

### Cách rerun verify
- Script: [verify_hrm_demo_e2e.ps1](/d:/dev/hrm-stack/BE/scripts/verify_hrm_demo_e2e.ps1)
- Tài khoản verify: `hai.do@company.com / NV0009`
- Base URL đúng để demo local: `http://localhost/api/v1`

### Ghi chú trung thực về current build
- Dữ liệu seed hiện tại còn lỗi encoding ở một số tên hiển thị và enum (`CH?_DUY?T`, `??_DUY?T`, tên nhân viên bị lỗi font). Luồng nghiệp vụ đã chạy được, nhưng trước demo khách hàng nên seed lại bộ tên hiển thị sạch.
- Chưa thấy module/request type riêng cho `Xin về sớm` trong current build. Tài liệu bên dưới vẫn mô tả luồng chuẩn để BA/PM/HR demo; nếu dùng đúng môi trường hiện tại thì cần cấu hình thêm request type hoặc demo bằng luồng generic request + attendance exception.
- Các case `chỉ có checkout`, `ngày off nhưng có log`, `nửa ngày nghỉ`, `đi công nửa ngày` đã được thiết kế trong tài liệu để demo/UAT nhưng chưa được live-verify trong vòng chạy ngày `2026-04-18`.

---

## Phần 1. Demo Overview

### Mục tiêu buổi demo
- Giúp người xem hiểu toàn bộ chuỗi nghiệp vụ HRM attendance theo đúng logic doanh nghiệp thật.
- Giúp người demo biết thao tác theo đúng thứ tự, không bị demo rời rạc từng màn hình.
- Giúp BA/QA/dev tái sử dụng cùng một kịch bản cho demo, UAT, training và regression.
- Giúp xác nhận rằng payroll không lấy log thô, mà lấy dữ liệu đã chuẩn hóa từ timesheet.

### Đối tượng xem demo
- Khách hàng doanh nghiệp.
- HR vận hành.
- Trưởng phòng / line manager.
- Admin hệ thống.
- BA / QA / Dev nội bộ.

### Phạm vi demo
- Demo end-to-end, không demo rời rạc từng màn hình.
- Dữ liệu phải chạy xuyên suốt qua chuỗi:
  `Cấu hình ca -> Phân ca -> Publish lịch -> Chấm công / Đơn từ -> Exception -> Tính công -> Payroll input / Payroll-ready -> Audit / Dashboard`

### Giả định dữ liệu
- Doanh nghiệp văn phòng, giờ hành chính, lương tháng.
- Có phân ca chuẩn `08:00-17:30`, nghỉ trưa `12:00-13:30`.
- Grace period đi muộn / về sớm: `5 phút`.
- OT chỉ tính khi:
  `có OT approved` và `checkout muộn ít nhất 30 phút`.
- Đi muộn / về sớm mặc định là vi phạm.
- Đi công đã duyệt không tính absent.
- Thiếu check-in / check-out sinh exception.
- Payroll chỉ lấy dữ liệu từ timesheet đã confirmed / locked / payroll-ready.

### Kết quả mong đợi sau buổi demo
- Người xem hiểu chuẩn vận hành là `schedule published`.
- Người xem hiểu log attendance chỉ là dữ liệu thực tế, chưa phải dữ liệu payroll.
- Người xem hiểu exception là lớp kiểm soát phát sinh.
- Người xem hiểu trưởng phòng xử lý cấp vận hành, HR chốt cấp nghiệp vụ.
- Người xem thấy rõ payroll nhận dữ liệu đã chuẩn hóa từ timesheet.

---

## Phần 2. Danh sách module cần demo

| Module | Mục tiêu demo | Vai trò tham gia | Dữ liệu cần chuẩn bị | Output cần thấy |
|---|---|---|---|---|
| Cấu hình tổ chức và nhân sự nền | Tạo khung tổ chức, quyền và phạm vi quản lý | Admin, HR | Company, department, employee, role | Scope dữ liệu và quyền đúng |
| Cấu hình ca làm việc | Xác lập chuẩn đối chiếu attendance | Admin, HR | Ca `08:00-17:30`, OT rule, grace rule | Shift catalog dùng được |
| Phân ca | Gán chuẩn làm việc cho nhân viên | Manager, HR | Nhân viên, shift type, ngày hiệu lực | Nhân viên có ca theo ngày |
| Publish lịch | Chốt chuẩn đối chiếu chính thức | Manager, HR | Lịch đã gán | Publish log, attendance result seed |
| Chấm công thường | Ghi nhận log thực tế | Employee, HR | Thiết bị/app/web/kiosk, ngày có ca | Check-in/out log theo ngày |
| Attendance exception | Quản lý phát sinh bất thường | Employee, Manager, HR | Missing punch, duplicate, off-day log | Danh sách exception và trạng thái xử lý |
| Nghỉ phép | Xin nghỉ và phản ánh vào bảng công | Employee, Manager, HR | Leave type, số ngày, lý do | Timesheet ra `AL/SL/UNP` |
| Xin về sớm | Phân biệt về sớm hợp lệ và tự ý về sớm | Employee, Manager, HR | Early leave request / exception rule | Early leave có căn cứ nghiệp vụ |
| Đi công / công tác | Không absent dù không có log văn phòng | Employee, Manager, HR | Business trip request | Timesheet ra `CT` |
| OT | Chỉ tính OT hợp lệ | Employee, Manager, HR | OT request, checkout muộn | OT minutes / OT hours |
| Điều chỉnh công | HR sửa sau xác minh | HR | Attendance record, note, approver | Công được chuẩn hóa lại |
| Tính công | Tổng hợp schedule + log + request + rule | HR | Dữ liệu đủ đầu vào | Daily timesheet, period summary |
| Cấu hình lương văn phòng | Thể hiện policy lương tháng / chuyên cần / OT | Payroll, HR | Salary config, allowance, coefficient | Rule lương rõ và khóa được |
| Payroll input / chốt kỳ công | Tạo dữ liệu đầu vào payroll | Payroll, HR | Timesheet confirmed | Payroll-ready dataset |
| Approval workflow | Theo dõi trạng thái duyệt xuyên suốt | Employee, Manager, HR | Requests, approval steps | Status lifecycle rõ ràng |
| Audit log | Truy vết thay đổi | Admin, HR, QA | User actions | Ai sửa gì, khi nào |
| Dashboard / báo cáo | Tổng hợp nhanh trạng thái vận hành | Manager, HR, Payroll | Attendance results, requests, exceptions | Late, missing, OT, payroll-ready |

---

## Phần 3. Thứ tự demo chuẩn

### Thứ tự đề xuất
`Dữ liệu nền -> Cấu hình ca -> Phân ca -> Publish lịch -> Nhân viên chấm công / tạo đơn -> Phát sinh exception -> Trưởng phòng xử lý -> HR rà soát / điều chỉnh -> Tính công ngày -> Tính công kỳ -> Payroll input / payroll-ready -> Audit log -> Dashboard / báo cáo`

### Vì sao phải demo theo thứ tự này
- Nếu chưa có ca và chưa publish lịch thì attendance không có “chuẩn để đối chiếu”.
- Nếu chưa có attendance/request thì exception và timesheet không có dữ liệu đầu vào.
- Nếu chưa xử lý exception / approval thì payroll-ready sẽ sai hoặc chưa sẵn sàng.
- Nếu chưa chốt timesheet thì nói về payroll sẽ thành lý thuyết, không thấy logic dữ liệu chảy.
- Audit log và dashboard nên demo cuối để người xem thấy hệ thống không chỉ “làm được” mà còn “kiểm soát được”.

---

## Phần 4. Bộ dữ liệu mẫu dùng để demo

### Tổ chức mẫu
- `1 công ty`: Công ty ABC Office Services
- `2 phòng ban`: Hành chính Nhân sự, Tài chính Kế toán
- `1 trưởng phòng mỗi phòng`
- `1 HR`
- `1 admin`
- `8 nhân viên`

### Ca làm việc mẫu
- Ca văn phòng chuẩn: `08:00-17:30`
- Nghỉ trưa: `12:00-13:30`
- Cuối tuần: `Saturday/Sunday off`
- Ngày lễ mẫu: `2026-09-02`

### Danh sách persona đề xuất

| Persona | Vai trò | Phòng ban | Tình huống demo chính |
|---|---|---|---|
| Nguyễn Minh Anh | Nhân viên | HCNS | Đi làm đúng giờ |
| Trần Thu Linh | Nhân viên | HCNS | Đi muộn |
| Phạm Quốc Bảo | Nhân viên | HCNS | Quên checkout |
| Lê Mỹ Hạnh | Nhân viên | Kế toán | Xin về sớm |
| Vũ Đức Thắng | Nhân viên | Kế toán | Làm OT |
| Đặng Ngọc Hà | Nhân viên | HCNS | Đi công tác |
| Hoàng Gia Hân | Nhân viên | HCNS | Nghỉ phép |
| Bùi Thanh Nam | Nhân viên | Kế toán | Ngày off nhưng có log |
| Mai Phương Vy | Nhân viên | HCNS | Chỉ có checkout / thiếu check-in |
| Đỗ Văn Hải | Admin | HCNS | Quản trị, cấu hình, rà soát audit |

### Gợi ý mapping với current build đã verify
- `NV0022` = case đi làm bình thường
- `NV0023` = case quên checkout
- `NV0024` = case nghỉ phép
- `NV0025` = case đi công
- `NV0026` = case OT hợp lệ
- `NV0027` = case checkout muộn nhưng không có OT approved

---

## Phần 5. Kịch bản demo chi tiết theo từng module

### 5.1. Demo cấu hình ca làm việc

**Mục tiêu demo**  
Thiết lập “chuẩn vận hành” để toàn bộ attendance và timesheet đối chiếu theo cùng một rule.

**Ai thao tác**  
Admin hoặc HR.

**Dữ liệu đầu vào**
- Shift code: `HC`
- Shift name: `Ca hành chính`
- Start: `08:00`
- End: `17:30`
- Break: `12:00-13:30`
- Grace late: `5`
- Grace early leave: `5`
- OT allowed: `Yes`
- OT minimum after shift: `30`

**Thao tác từng bước**
1. Vào màn hình cấu hình shift.
2. Tạo ca mới hoặc mở ca chuẩn hiện có.
3. Nhập giờ vào/ra.
4. Cấu hình grace period đi muộn / về sớm.
5. Bật rule cho phép OT.
6. Lưu ca và kiểm tra shift xuất hiện trong shift catalog.

**Hệ thống phải phản hồi gì**
- Shift được lưu thành công.
- Shift xuất hiện trong danh mục ca active.
- Có thể dùng ngay khi phân ca.

**Người demo nên giải thích gì**
- Ca làm việc là chuẩn so sánh, không phải log thực tế.
- Nếu không có chuẩn ca, hệ thống không thể kết luận đúng sai về đi muộn, về sớm, absent, OT.

**Kết quả mong đợi cuối cùng**
- Có `1 ca văn phòng chuẩn` dùng xuyên suốt buổi demo.

### 5.2. Demo phân ca

**Mục tiêu demo**  
Gán ca cho đúng nhân viên đúng ngày để sinh chuẩn vận hành.

**Ai thao tác**  
Trưởng phòng hoặc HR.

**Dữ liệu đầu vào**
- Danh sách nhân viên
- Shift `HC`
- Ngày cần demo

**Thao tác từng bước**
1. Chọn phòng ban hoặc chọn trực tiếp nhân viên.
2. Gán ca mặc định cho ngày demo.
3. Nếu có ngoại lệ, override ca cho cá nhân.
4. Kiểm tra nhân viên đã có ca theo ngày.
5. Publish lịch.

**Hệ thống phải phản hồi gì**
- Tạo `shift assignment`.
- Tạo `publish log`.
- Sau publish, hệ thống có chuẩn chính thức để đối chiếu attendance.

**Người demo nên giải thích gì**
- `Published schedule` là lịch chuẩn để hệ thống tính đúng/sai.
- Có phân ca nhưng chưa publish thì nên xem như “chưa chốt chuẩn”.

**Kết quả mong đợi cuối cùng**
- Nhân viên trong ngày demo đều có lịch published.

### 5.3. Demo chấm công thường

**Mục tiêu demo**  
Cho người xem thấy log thực tế được ghi nhận và so với lịch chuẩn như thế nào.

**Ai thao tác**  
Nhân viên hoặc HR thao tác giả lập.

**Dữ liệu đầu vào**
- Nhân viên đã có ca published
- Thiết bị/app/web/kiosk

**Thao tác từng bước**
1. Thực hiện check-in.
2. Hệ thống ghi log thời gian vào.
3. Thực hiện check-out.
4. Hệ thống ghi log thời gian ra.
5. Mở daily timesheet / attendance result để xem trạng thái.

**Hệ thống phải phản hồi gì**
- Tạo attendance log.
- So sánh với ca chuẩn.
- Hiển thị trạng thái trong ngày: `P`, `L`, `EO`, `AB`, `CT`, `AL`, `OT`.

**Người demo nên giải thích gì**
- Attendance log là dữ liệu thực tế.
- Log đúng chưa đồng nghĩa payroll dùng ngay; còn phải qua đối soát và timesheet.

**Kết quả mong đợi cuối cùng**
- Nhân viên có bản ghi attendance theo ngày.

### 5.4. Demo attendance exception

**Mục tiêu demo**  
Cho thấy hệ thống không “kết luận bừa”, mà sinh exception để có người chịu trách nhiệm xác nhận.

**Ai thao tác**  
Nhân viên, trưởng phòng, HR.

**Case bắt buộc phải cover**
- Checkout sớm
- Thiếu checkout
- Chỉ có checkout
- Log trùng
- Ngày off nhưng có log
- Có ca nhưng không có log

**Exception sinh ở đâu**
- Từ lớp đối soát `schedule + attendance + request + rule`.
- Trong current build, có thể xem qua `timesheet/exceptions`, `Exception Dashboard`, `risk/exception flow`.

**Ai nhìn thấy**
- Trưởng phòng và HR.
- Nhân viên nhìn thấy exception/request của chính mình.

**Trạng thái exception thay đổi ra sao**
- `PENDING -> APPROVED_ONCE / REJECTED`
- Sau khi HR chỉnh attendance và recalculate, exception vận hành phải biến mất khỏi timesheet exceptions.

**Kịch bản bước mẫu**
1. Nhân viên check-in nhưng quên checkout.
2. Mở bảng công ngày để thấy flag `MISSING_CHECKOUT`.
3. Nhân viên gửi exception request.
4. Trưởng phòng approve one-time exception.
5. HR cập nhật checkout thủ công.
6. Mở lại daily timesheet để xác nhận flag đã hết.

**Expected result**
- Trước xử lý: có flag bất thường.
- Sau xử lý: daily timesheet sạch bất thường, payroll-ready trở lại.

### 5.5. Demo nghỉ phép

**Mục tiêu demo**  
Cho thấy đơn nghỉ đi vào bảng công, không bị tính absent.

**Ai thao tác**  
Nhân viên, trưởng phòng, HR.

**Thao tác từng bước**
1. Nhân viên tạo đơn nghỉ phép.
2. Trưởng phòng xử lý bước duyệt vận hành.
3. HR xác nhận bước cuối.
4. Mở bảng công ngày để thấy trạng thái nghỉ.

**Hệ thống phải phản hồi gì**
- Tạo request và leave request.
- Approval workflow đổi trạng thái.
- Daily timesheet đổi sang `AL`, `SL` hoặc `UNP`.

**Người demo nên giải thích gì**
- Nghỉ phép đã duyệt thay thế absent.
- Sự khác biệt giữa `raw attendance = không có log` và `timesheet = nghỉ phép hợp lệ`.

**Kết quả mong đợi cuối cùng**
- Bảng công ra nghỉ hợp lệ, payroll-ready.

### 5.6. Demo xin về sớm

**Mục tiêu demo**  
Phân biệt giữa `về sớm có phê duyệt` và `tự ý checkout sớm`.

**Ai thao tác**  
Nhân viên, trưởng phòng, HR.

**Luồng chuẩn để demo**
1. Nhân viên tạo request xin về sớm trước giờ checkout.
2. Trưởng phòng duyệt.
3. Nhân viên checkout sớm.
4. HR/manager kiểm tra xem early checkout đó có request hợp lệ đi kèm hay không.
5. Timesheet phản ánh theo policy của doanh nghiệp.

**Người demo nên giải thích gì**
- Hệ thống không chỉ nhìn vào giờ checkout.
- Hệ thống cần ngữ cảnh nghiệp vụ: request đã duyệt hay chưa.

**Current build note**
- Chưa tìm thấy request type / endpoint chuyên biệt cho `Xin về sớm`.
- Nếu demo trên current build, cần một trong hai cách:
  - cấu hình thêm request type `EARLY_LEAVE` rồi map vào policy;
  - hoặc demo bằng generic request + attendance exception, và nói rõ đây là workaround của môi trường demo hiện tại.

### 5.7. Demo đi công / công tác

**Mục tiêu demo**  
Cho thấy nhân viên không chấm công tại văn phòng nhưng vẫn không bị absent.

**Ai thao tác**  
Nhân viên, trưởng phòng, HR.

**Thao tác từng bước**
1. Nhân viên tạo đơn đi công / công tác.
2. Trưởng phòng duyệt.
3. HR xác nhận nếu policy cần.
4. Không tạo attendance log tại văn phòng.
5. Mở bảng công ngày.

**Expected result**
- Daily timesheet ra `CT`.
- Không bị `AB`.
- Payroll-ready vẫn `true`.

### 5.8. Demo OT

**Mục tiêu demo**  
Chứng minh OT chỉ được tính khi đúng policy.

**Ai thao tác**  
Nhân viên, trưởng phòng, HR.

**Thao tác từng bước**
1. Nhân viên tạo OT request.
2. Quản lý/HR approve OT.
3. Nhân viên checkout muộn.
4. Mở bảng công ngày và payroll export.
5. Chạy thêm case đối chứng: checkout muộn nhưng không có OT approved.

**Expected result**
- Có OT approved + checkout muộn ít nhất 30 phút: sinh `overtime_minutes`.
- Checkout muộn nhưng không có OT approved: `overtime_minutes = 0`.

### 5.9. Demo tính công

**Mục tiêu demo**  
Cho người xem thấy timesheet là lớp chuẩn hóa dữ liệu.

**Hệ thống phải tổng hợp**
- Schedule
- Attendance log
- Leave
- OT
- Business trip / duty
- Holiday / off-day

**Kết quả phải cho thấy**
- Daily timesheet
- Period summary
- Cờ `late`, `early leave`, `missing punch`, `leave`, `on duty`, `holiday`, `off`

### 5.10. Demo cấu hình lương văn phòng

**Mục tiêu demo**
- Thể hiện rằng hệ thống hỗ trợ lương tháng, công chuẩn tháng, OT, nghỉ không lương, chuyên cần, phụ cấp.

**Điểm bắt buộc phải nói**
- Lương tháng là cấu hình.
- Công chuẩn tháng là cấu hình.
- Nghỉ không lương ảnh hưởng giảm lương.
- Chuyên cần có thể bị ảnh hưởng bởi ngưỡng vi phạm.
- OT ngày thường là cấu hình hệ số.
- Phụ cấp cố định có thể tách riêng.
- Sau khi chốt kỳ công / khóa payroll period thì các phần tác động lương phải bị khóa hoặc đi qua rollback policy.

### 5.11. Demo payroll input

**Mục tiêu demo**
- Nhấn mạnh payroll không lấy trực tiếp từ raw attendance.

**Thao tác demo**
1. Mở payroll export / payroll-ready data.
2. So sánh với raw attendance cùng ngày.
3. Chỉ ra rằng payroll nhận:
   - working days
   - present days
   - leave days
   - OT hours
   - late / early aggregates
   - exception_count
   - ready_for_payroll

**Thông điệp chính**
- Payroll lấy từ `timesheet đã chuẩn hóa`, không lấy từ `log thô`.

### 5.12. Demo approval workflow

**Mục tiêu demo**
- Cho người xem thấy tất cả request đều có vòng đời trạng thái rõ ràng.

**Current build đã verify**
- Generic request: `CHỜ_DUYỆT -> ĐANG_XỬ_LÝ -> ĐÃ_DUYỆT`
- Leave request hiện dùng cùng logic persistence này để phù hợp enum DB hiện có.

**Điểm cần nói**
- Nhân viên gửi đơn.
- Trưởng phòng xử lý bước vận hành.
- HR xác nhận bước nghiệp vụ cuối.
- Trạng thái phải thay đổi có kiểm soát, không sửa trực tiếp kiểu “nhảy cóc”.

### 5.13. Demo audit log

**Mục tiêu demo**
- Chứng minh hệ thống minh bạch và truy vết được.

**Phải cho thấy**
- Ai sửa lịch.
- Ai duyệt exception.
- Ai chỉnh attendance.
- Ai chốt / publish lịch.
- Ai đổi trạng thái request.

**Current build đã verify**
- Có audit log cho `SCHEDULE_ASSIGNMENT`, `SCHEDULE_SCOPE`, `REQUEST`, `LEAVE_REQUEST`.

### 5.14. Demo dashboard / báo cáo

**Mục tiêu demo**
- Cho quản lý thấy bức tranh tổng hợp, không phải đọc từng record.

**Báo cáo phải xem được**
- Ai đi muộn
- Ai thiếu log
- Ai chưa có ca
- OT đã duyệt
- Exception chưa xử lý
- Dữ liệu payroll-ready

**Người demo nên giải thích gì**
- Dashboard là lớp điều hành.
- Audit log là lớp kiểm soát.
- Timesheet là lớp chuẩn hóa.

---

## Phần 6. Kịch bản demo end-to-end đề xuất

### Kịch bản 1: Nhân viên đi làm bình thường
- Có phân ca và lịch đã publish.
- Check-in / check-out đúng giờ.
- Bảng công ra `P`.
- Payroll nhận `1 working day`, `ready_for_payroll=true`.

### Kịch bản 2: Nhân viên quên checkout
- Có phân ca.
- Chỉ check-in, thiếu checkout.
- Hệ thống sinh `MISSING_CHECKOUT`.
- Nhân viên gửi exception.
- Trưởng phòng duyệt.
- HR điều chỉnh checkout.
- Timesheet sạch lỗi, payroll-ready trở lại.

### Kịch bản 3: Nhân viên nghỉ phép cả ngày
- Có lịch published.
- Đơn nghỉ được duyệt.
- Không có attendance log.
- Bảng công ra `AL`.
- Payroll không tính absent.

### Kịch bản 4: Nhân viên đi công cả ngày
- Có lịch published.
- Đơn đi công được duyệt.
- Không có log văn phòng.
- Bảng công ra `CT`.

### Kịch bản 5: Nhân viên OT hợp lệ
- Có lịch published.
- Có OT approved.
- Checkout muộn ít nhất 30 phút.
- Bảng công có OT minutes.
- Payroll export có OT hours.

### Kịch bản 6: Checkout muộn nhưng không có OT approved
- Có log checkout muộn.
- Không có OT request / approved.
- OT minutes bằng `0`.
- Hệ thống không tự động cộng OT chỉ vì checkout muộn.

### Kịch bản bổ sung nên dùng cho UAT / demo mở rộng
- Checkout sớm không có đơn -> `EO` hoặc vi phạm policy.
- Checkout sớm có đơn xin về sớm -> xử lý theo policy đã duyệt.
- Chỉ có checkout -> sinh exception.
- Ngày off nhưng có log -> cảnh báo làm ngoài lịch.
- Không có log nào trong ngày -> `AB`.
- Nghỉ nửa ngày -> hệ số công `0.5`.
- Đi công nửa ngày + có log phần còn lại -> cần rule phối hợp request và attendance.

### Ma trận coverage case

| Case bắt buộc | Cách cover trong tài liệu | Current build |
|---|---|---|
| Đi làm đúng giờ | Kịch bản 1 | Đã verify live |
| Đi muộn | Dùng chấm công sau grace | Thiết kế sẵn, chưa live-verify trong batch cuối |
| Checkout sớm | Dùng early checkout case | Thiết kế sẵn |
| Checkout sớm có đơn | Luồng 5.6 | Cần cấu hình request/module riêng |
| Quên checkout | Kịch bản 2 | Đã verify live |
| Chỉ có checkout | Attendance exception | Thiết kế sẵn |
| Ngày off nhưng có log | Exception / dashboard | Thiết kế sẵn |
| Không có log nào trong ngày | Timesheet absent | Thiết kế sẵn |
| OT hợp lệ | Kịch bản 5 | Đã verify live |
| Log checkout muộn nhưng không có OT approved | Kịch bản 6 | Đã verify live |
| Đi công cả ngày | Kịch bản 4 | Đã verify live |
| Đi công nửa ngày | Request + partial attendance | Thiết kế sẵn |
| Nghỉ phép cả ngày | Kịch bản 3 | Đã verify live |
| Nghỉ nửa ngày | Leave half-day | Thiết kế sẵn |
| Điều chỉnh công bởi HR | Kịch bản 2 | Đã verify live |
| Chốt bảng công / payroll input | Payroll-ready | Đã verify live ở mức export |
| Audit log sau khi sửa dữ liệu | Audit tail | Đã verify live |

---

## Phần 7. Script nói khi demo

### Khi cấu hình ca
- “Ở đây chúng ta đang tạo chuẩn làm việc để toàn bộ attendance về sau có thứ để đối chiếu.”
- “Nếu chưa có ca chuẩn thì hệ thống chưa thể kết luận ai đi muộn, ai về sớm hay ai được tính OT.”

### Khi phân ca và publish
- “Phân ca là bước lập kế hoạch, còn publish lịch là bước chốt chuẩn vận hành.”
- “Sau khi publish, lịch này trở thành chuẩn để so với attendance thực tế.”

### Khi nhân viên chấm công
- “Đây là log thực tế từ app/web/kiosk, tức là dữ liệu phát sinh ngoài hiện trường.”
- “Nhưng payroll chưa lấy trực tiếp từ đây; hệ thống còn phải đối soát thêm request, exception và policy.”

### Khi sinh exception
- “Hệ thống không tự kết luận ngay là hợp lệ hay không hợp lệ, mà sinh exception để quản lý xác nhận.”
- “Điều này giúp doanh nghiệp giảm xử lý tay nhưng vẫn giữ được kiểm soát.”

### Khi duyệt nghỉ / đi công / OT
- “Điểm quan trọng là request hợp lệ sẽ thay đổi cách hiểu của attendance.”
- “Không có log văn phòng chưa chắc là absent; nếu có công tác hoặc nghỉ phép đã duyệt thì bảng công sẽ phản ánh đúng bản chất.”

### Khi vào timesheet
- “Timesheet là lớp chuẩn hóa. Đây mới là nơi payroll nên lấy dữ liệu.”
- “Tại đây hệ thống đã ghép lịch, log, đơn từ và policy để ra kết quả dùng cho lương.”

### Khi vào payroll input
- “Payroll không lấy từ log chấm công thô mà lấy từ timesheet đã được xác nhận.”
- “Điều này rất quan trọng vì payroll cần dữ liệu đã qua chuẩn hóa và kiểm soát.”

### Khi vào audit log
- “Phần này giúp trả lời câu hỏi ai đã sửa lịch, ai đã duyệt ngoại lệ, ai đã chỉnh công và khi nào.”
- “Với doanh nghiệp, tính minh bạch này quan trọng không kém việc hệ thống có nhiều tính năng.”

---

## Phần 8. Những điểm cần nhấn mạnh khi demo cho doanh nghiệp

- Phân ca là chuẩn vận hành.
- Chấm công là dữ liệu thực tế.
- Exception là lớp kiểm soát phát sinh.
- Trưởng phòng xử lý cấp vận hành.
- HR chốt cấp nghiệp vụ.
- Payroll lấy từ dữ liệu đã chuẩn hóa.
- Audit log giúp kiểm soát minh bạch.
- Hệ thống giảm thao tác tay, giảm sai sót, giảm tranh cãi sau kỳ công.

---

## Phần 9. Lỗi dễ gặp khi demo và cách tránh

| Lỗi dễ gặp | Hậu quả | Cách phòng tránh |
|---|---|---|
| Dữ liệu demo không đồng nhất | Màn trước và màn sau không nối được nhau | Chốt trước 1 bộ persona, 1 bộ ngày demo, 1 bộ shift |
| Chưa publish lịch nhưng đi demo chấm công | Attendance không có chuẩn đối chiếu | Luôn publish xong mới sang phần attendance |
| Demo OT mà chưa có OT request | Checkout muộn nhưng không có OT | Tạo và approve OT request trước khi chấm công |
| Dữ liệu thiếu nên exception không hiện đúng | Người xem không thấy giá trị module exception | Chuẩn bị sẵn ít nhất 1 case missing checkout, 1 case no log |
| Payroll chưa có timesheet ready | Không chứng minh được luồng payroll | Recalculate / export sau khi xử lý hết exception |
| Quyền vai trò chưa đúng | Không thao tác được các bước duyệt | Kiểm tra role của employee, manager, HR, admin trước buổi demo |
| Seed data lỗi encoding | Tên hiển thị xấu, status khó đọc | Reseed data client-facing trước demo khách hàng |

---

## Phần 10. Checklist chuẩn bị trước buổi demo

- [ ] Dữ liệu master đã có
- [ ] Company / department / position / employee đã tạo
- [ ] User role đã cấp đúng
- [ ] Shift chuẩn đã tạo
- [ ] Lịch đã assign và publish
- [ ] Dữ liệu log đã seed hoặc tạo nhanh được
- [ ] Form nghỉ phép / OT / công tác đã sẵn
- [ ] Có ít nhất 1 case exception để demo
- [ ] Timesheet có thể recalculate / refresh
- [ ] Payroll export có dữ liệu mẫu
- [ ] Dashboard có số liệu hiển thị
- [ ] Audit log đang bật
- [ ] Kiểm tra local URL đúng là `http://localhost`
- [ ] Nếu demo current build, chuẩn bị sẵn lời giải thích cho phần `xin về sớm` và lỗi encoding dữ liệu seed

---

## Phần 11. Checklist sau buổi demo

- [ ] Người xem đã hiểu mối liên hệ giữa phân ca và chấm công
- [ ] Người xem đã hiểu exception workflow
- [ ] Người xem đã hiểu timesheet khác raw attendance
- [ ] Người xem đã hiểu payroll lấy dữ liệu từ đâu
- [ ] Đã chốt được policy đặc thù nào của doanh nghiệp cần customize
- [ ] Đã chốt được màn nào cần thêm role / approval step
- [ ] Đã chốt được báo cáo nào doanh nghiệp cần ưu tiên
- [ ] Đã chốt được có cần module riêng cho `xin về sớm` hay không

---

## Phụ lục A. Bằng chứng verify current build

### Lệnh đã chạy
- `php .\scripts\be_first_gate_smoke.php`
- `powershell -ExecutionPolicy Bypass -File .\scripts\run_be_first_scenarios.ps1 -BaseUrl http://localhost/api/v1`
- `powershell -ExecutionPolicy Bypass -File .\scripts\verify_hrm_demo_e2e.ps1`
- `npm run build` trong thư mục `FE`

### Tóm tắt verify gần nhất
- Normal: `attendance_id=2384`, `daily=P`, `payroll_ready=true`
- Missing checkout recovery: `attendance_id=2385`, `exception_id=4`, trước có `MISSING_CHECKOUT`, sau sửa sạch flag
- Leave: `request_id=54`, `daily=AL`, `payroll_ready=true`
- Business trip: `request_id=55`, `daily=CT`, `payroll_ready=true`
- Approved OT: `overtime_id=4`, `overtime_minutes=45`, `ot_hours=0.75`, `payroll_ready=true`
- Late checkout without OT: `attendance_id=2387`, `overtime_minutes=0`, `payroll_ready=true`

### Audit tail đã thấy
- `audit_log_id=79`: `SCHEDULE_SCOPE`
- `audit_log_id=78`: `SCHEDULE_ASSIGNMENT`
- `audit_log_id=77`: `SCHEDULE_SCOPE`
- `audit_log_id=76`: `SCHEDULE_ASSIGNMENT`
- `audit_log_id=75`: `REQUEST`
- `audit_log_id=74`: `REQUEST`

---

## Phụ lục B. Khuyến nghị trước khi demo khách hàng thật

- Seed lại tên nhân viên / tên phòng ban / label trạng thái để bỏ lỗi encoding.
- Nếu muốn demo `xin về sớm` bài bản, bổ sung request type và rule mapping riêng.
- Nếu muốn demo `nửa ngày nghỉ / nửa ngày công tác`, cần seed case rõ ràng và xác nhận rule hệ số công.
- Nếu muốn nói sâu về payroll configuration, nên chuẩn bị thêm bộ `salary period`, `salary detail`, `allowance`, `deduction`, `attendance allowance`.

