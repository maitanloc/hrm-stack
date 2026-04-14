import { LOGO_COMPANY, LOGO_SCHOOL } from './logoBase64.js'

const fmt = (num) => new Intl.NumberFormat('vi-VN').format(Math.round(num || 0))
const fmtVND = (num) => new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(num || 0)
const today = () => new Date().toLocaleDateString('vi-VN', { day: '2-digit', month: '2-digit', year: 'numeric' })

// ─── SHARED CSS ──────────────────────────────────────────────────────────────
const BASE_CSS = `
  @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap');
  * { margin:0; padding:0; box-sizing:border-box; }
  body { font-family:'Inter',Arial,sans-serif; font-size:12px; color:#1e293b; background:#fff; padding:28px 36px; }

  /* HEADER */
  .pdf-header { display:flex; justify-content:space-between; align-items:center; padding-bottom:16px; border-bottom:2.5px solid #1d4ed8; margin-bottom:20px; }
  .logo-group { display:flex; align-items:center; gap:14px; }
  .logo-group img { height:48px; object-fit:contain; }
  .logo-divider { width:1px; height:40px; background:#cbd5e1; }
  .company-info { }
  .company-name { font-size:15px; font-weight:900; color:#1d4ed8; letter-spacing:-0.3px; }
  .company-sub  { font-size:10px; color:#64748b; margin-top:3px; font-weight:500; text-transform:uppercase; letter-spacing:0.08em; }
  .doc-block    { text-align:right; }
  .doc-label    { font-size:10px; font-weight:800; color:#64748b; text-transform:uppercase; letter-spacing:0.1em; }
  .doc-period   { font-size:20px; font-weight:900; color:#1e293b; margin-top:3px; }
  .doc-date     { font-size:10px; color:#94a3b8; margin-top:2px; }

  /* TITLE BAR */
  .title-bar { background:#1d4ed8; color:#fff; padding:12px 18px; border-radius:8px; margin-bottom:18px; display:flex; justify-content:space-between; align-items:center; }
  .title-bar h1 { font-size:15px; font-weight:800; letter-spacing:0.02em; }
  .title-bar p  { font-size:11px; opacity:0.8; margin-top:2px; }
  .title-bar .badge { background:rgba(255,255,255,0.2); border:1px solid rgba(255,255,255,0.3); border-radius:6px; padding:4px 10px; font-size:10px; font-weight:700; white-space:nowrap; }

  /* SUMMARY CARDS */
  .summary { display:grid; gap:12px; margin-bottom:20px; }
  .summary-3 { grid-template-columns:repeat(3,1fr); }
  .summary-2 { grid-template-columns:repeat(2,1fr); }
  .card { border:1px solid #e2e8f0; border-radius:8px; padding:12px 16px; background:#f8fafc; }
  .card-label { font-size:9px; font-weight:700; text-transform:uppercase; letter-spacing:0.12em; color:#64748b; margin-bottom:4px; }
  .card-value { font-size:16px; font-weight:800; color:#1e293b; }
  .card.blue  .card-value { color:#1d4ed8; }
  .card.green .card-value { color:#16a34a; }
  .card.red   .card-value { color:#dc2626; }

  /* EMPLOYEE INFO BOX */
  .emp-box { display:grid; grid-template-columns:repeat(3,1fr); gap:10px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:14px 18px; margin-bottom:18px; }
  .emp-field-label { font-size:9px; font-weight:700; text-transform:uppercase; letter-spacing:0.1em; color:#64748b; margin-bottom:3px; }
  .emp-field-value { font-size:13px; font-weight:700; color:#1e293b; }

  /* NET BANNER */
  .net-banner { background:#1d4ed8; color:#fff; border-radius:8px; padding:16px 22px; display:flex; justify-content:space-between; align-items:center; margin-bottom:18px; }
  .net-label { font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:0.1em; opacity:0.75; margin-bottom:6px; }
  .net-amount { font-size:28px; font-weight:900; letter-spacing:-0.5px; }
  .net-status { background:rgba(255,255,255,0.2); border:1px solid rgba(255,255,255,0.3); border-radius:6px; padding:6px 14px; font-size:12px; font-weight:700; text-align:center; }

  /* BREAKDOWN */
  .breakdown { display:grid; grid-template-columns:1fr 1fr; gap:20px; margin-bottom:18px; }
  .breakdown-section h5 { font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:0.08em; padding-bottom:8px; margin-bottom:10px; }
  .breakdown-section.income h5 { border-bottom:2px solid #16a34a; color:#16a34a; }
  .breakdown-section.deduct h5 { border-bottom:2px solid #dc2626; color:#dc2626; }
  .line-item { display:flex; justify-content:space-between; padding:5px 0; border-bottom:1px solid #f1f5f9; }
  .line-item:last-child { border-bottom:none; }
  .line-label { font-size:12px; color:#475569; }
  .line-value { font-size:12px; font-weight:700; color:#1e293b; }
  .line-value.green { color:#16a34a; }
  .line-value.red   { color:#dc2626; }
  .line-total { display:flex; justify-content:space-between; padding-top:8px; margin-top:8px; border-top:2px solid #e2e8f0; }
  .line-total-label { font-size:12px; font-weight:800; text-transform:uppercase; color:#1e293b; }
  .line-total-value { font-size:14px; font-weight:800; }
  .line-total-value.green { color:#16a34a; }
  .line-total-value.red   { color:#dc2626; }

  /* TABLE */
  table { width:100%; border-collapse:collapse; font-size:12px; }
  thead tr { background:#1d4ed8; color:#fff; }
  thead th { padding:9px 13px; text-align:left; font-weight:700; font-size:10px; text-transform:uppercase; letter-spacing:0.07em; white-space:nowrap; }
  thead th.right { text-align:right; }
  thead th.center { text-align:center; }
  tbody tr { border-bottom:1px solid #f1f5f9; }
  tbody tr:nth-child(even) { background:#f8fafc; }
  tbody td { padding:10px 13px; vertical-align:middle; }
  tbody td.right  { text-align:right; }
  tbody td.center { text-align:center; }
  tfoot tr { background:#1e293b; color:#fff; }
  tfoot td { padding:11px 13px; font-weight:700; font-size:13px; }
  tfoot td.right { text-align:right; color:#93c5fd; }

  /* INSURANCE TABLE */
  .ins-table-wrapper { border:1px solid #e2e8f0; border-radius:8px; overflow:hidden; margin-bottom:18px; }
  .ins-header { background:#f8fafc; padding:10px 16px; font-size:10px; font-weight:800; text-transform:uppercase; letter-spacing:0.1em; color:#64748b; border-bottom:1px solid #e2e8f0; }

  /* SIGNATURES */
  .signatures { display:grid; grid-template-columns:repeat(3,1fr); gap:20px; margin-top:32px; padding-top:16px; border-top:1px dashed #cbd5e1; }
  .sig-block    { text-align:center; }
  .sig-title    { font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:0.1em; color:#64748b; }
  .sig-note     { font-size:9px; color:#94a3b8; margin-top:2px; }
  .sig-line     { height:55px; border-bottom:1px solid #cbd5e1; margin-top:10px; }
  .sig-name     { font-size:11px; font-weight:700; margin-top:5px; color:#1e293b; }

  /* DISCLAIMER */
  .disclaimer { background:#eff6ff; border:1px dashed #bfdbfe; border-radius:8px; padding:12px 16px; margin-bottom:18px; }
  .disclaimer p { font-size:11px; color:#1d4ed8; font-weight:500; line-height:1.6; }

  /* FOOTER */
  .pdf-footer { margin-top:24px; text-align:center; font-size:9px; color:#94a3b8; border-top:1px solid #e2e8f0; padding-top:12px; }
  .confidential-badge { display:inline-block; background:#fee2e2; color:#dc2626; font-size:9px; font-weight:700; padding:2px 8px; border-radius:4px; border:1px solid #fca5a5; text-transform:uppercase; letter-spacing:0.08em; margin-bottom:5px; }

  @media print {
    body { padding:12px 18px; }
    .title-bar, thead tr, tfoot tr, .net-banner { -webkit-print-color-adjust:exact; print-color-adjust:exact; }
  }
`

const LOGO_HEADER = (deptLabel, periodLabel) => `
  <div class="pdf-header">
    <div class="logo-group">
      <img src="${LOGO_COMPANY}" alt="Company Logo" />
      <div class="logo-divider"></div>
      <img src="${LOGO_SCHOOL}" alt="FPT Polytechnic" />
    </div>
    <div class="doc-block">
      <div class="doc-label">Bảng lương kỳ</div>
      <div class="doc-period">${periodLabel}</div>
      <div class="doc-date">${deptLabel} · Ngày xuất: ${today()}</div>
    </div>
  </div>
`

const SIGNATURES = (role = 'manager') => `
  <div class="signatures">
    <div class="sig-block">
      <div class="sig-title">Người lập bảng</div>
      <div class="sig-note">(Ký, ghi rõ họ tên)</div>
      <div class="sig-line"></div>
      <div class="sig-name">&nbsp;</div>
    </div>
    <div class="sig-block">
      <div class="sig-title">${role === 'manager' ? 'Trưởng phòng xác nhận' : 'Nhân viên xác nhận'}</div>
      <div class="sig-note">(Ký, ghi rõ họ tên)</div>
      <div class="sig-line"></div>
      <div class="sig-name">${role === 'manager' ? 'Trần Thanh Tâm' : '&nbsp;'}</div>
    </div>
    <div class="sig-block">
      <div class="sig-title">Giám đốc phê duyệt</div>
      <div class="sig-note">(Ký, đóng dấu)</div>
      <div class="sig-line"></div>
      <div class="sig-name">&nbsp;</div>
    </div>
  </div>
`

const PDF_FOOTER = (periodLabel) => `
  <div class="pdf-footer">
    <div class="confidential-badge">BÍ MẬT — CHỈ SỬ DỤNG NỘI BỘ</div>
    <div>Tài liệu được tạo tự động bởi AET HRM System · ${today()} · ${periodLabel}</div>
    <div style="margin-top:2px">Mọi sao chép, phân phối trái phép đều bị nghiêm cấm theo Chính sách bảo mật thông tin nội bộ.</div>
  </div>
`

const openPrint = (html) => {
  const win = window.open('', '_blank', 'width=1100,height=850,scrollbars=yes')
  if (win) { win.document.write(html); win.document.close() }
}

// ═══════════════════════════════════════════════════════════════════════════
// EXPORT 1: TRƯỞNG PHÒNG — Bảng lương tổng hợp cả phòng
// ═══════════════════════════════════════════════════════════════════════════
export function exportManagerPayrollPDF({ periodLabel, deptName, payrollList, totalBase, totalBonus, totalDeduct, totalNet }) {
  const rows = payrollList.map((s, i) => `
    <tr>
      <td class="center" style="color:#94a3b8;font-size:11px">${i + 1}</td>
      <td>
        <div style="font-weight:700;font-size:13px;color:#1e293b">${s.name}</div>
        <div style="font-size:10px;color:#64748b;margin-top:2px">${s.position}</div>
      </td>
      <td class="right" style="font-weight:600">${s.base} đ</td>
      <td class="right" style="color:#16a34a;font-weight:700">+ ${s.bonus} đ</td>
      <td class="right" style="color:#dc2626;font-weight:700">- ${s.deduct} đ</td>
      <td class="right" style="font-weight:800;color:#1d4ed8;font-size:13px">${s.total} đ</td>
    </tr>
  `).join('')

  const html = `<!DOCTYPE html><html lang="vi"><head><meta charset="UTF-8"/>
  <title>Bảng Lương ${periodLabel} — ${deptName}</title>
  <style>${BASE_CSS}</style></head><body>

  ${LOGO_HEADER(deptName, periodLabel)}

  <div class="title-bar">
    <div>
      <h1>BẢNG THANH TOÁN TIỀN LƯƠNG THÁNG</h1>
      <p>${deptName} · ${periodLabel} · Tổng: ${payrollList.length} nhân sự</p>
    </div>
    <div class="badge">TRƯỞNG PHÒNG</div>
  </div>

  <div class="summary summary-3">
    <div class="card blue"><div class="card-label">Quỹ lương thực lĩnh</div><div class="card-value">${totalNet} đ</div></div>
    <div class="card green"><div class="card-label">Thưởng & phụ cấp</div><div class="card-value">${totalBonus} đ</div></div>
    <div class="card red"><div class="card-label">Tổng khấu trừ</div><div class="card-value">- ${totalDeduct} đ</div></div>
  </div>

  <table>
    <thead>
      <tr>
        <th class="center" style="width:38px">STT</th>
        <th>Họ và tên / Chức vụ</th>
        <th class="right">Lương cơ bản</th>
        <th class="right">Phụ cấp & thưởng</th>
        <th class="right">Khấu trừ</th>
        <th class="right">Thực lĩnh</th>
      </tr>
    </thead>
    <tbody>${rows}</tbody>
    <tfoot>
      <tr>
        <td colspan="2">TỔNG CỘNG (${payrollList.length} nhân sự)</td>
        <td class="right" style="color:#e2e8f0">${totalBase} đ</td>
        <td class="right" style="color:#86efac">+ ${totalBonus} đ</td>
        <td class="right" style="color:#fca5a5">- ${totalDeduct} đ</td>
        <td class="right">${totalNet} đ</td>
      </tr>
    </tfoot>
  </table>

  ${SIGNATURES('manager')}
  ${PDF_FOOTER(periodLabel)}
  <script>window.onload=function(){window.print()}<\/script>
  </body></html>`

  openPrint(html)
}

// ═══════════════════════════════════════════════════════════════════════════
// EXPORT 2: NHÂN VIÊN — Phiếu lương cá nhân chi tiết
// ═══════════════════════════════════════════════════════════════════════════
export function exportEmployeePayrollPDF({ periodLabel, employeeName, employeeCode, department, position, status, incomes, deductions, totalIncome, totalDeduction, netAmount, insuranceRows }) {
  const incomeRows = incomes.map(i => `
    <div class="line-item">
      <span class="line-label">${i.label}</span>
      <span class="line-value">${fmtVND(i.value)}</span>
    </div>
  `).join('')

  const deductRows = deductions.map(d => `
    <div class="line-item">
      <span class="line-label">${d.label}</span>
      <span class="line-value red">- ${fmtVND(d.value)}</span>
    </div>
  `).join('')

  const insRows = (insuranceRows || []).map(r => `
    <tr>
      <td style="font-weight:700">${r.label}</td>
      <td class="center" style="color:#dc2626;font-weight:700">${r.employeeRate}%</td>
      <td class="center" style="color:#1d4ed8;font-weight:700">${r.companyRate}%</td>
      <td class="right" style="font-weight:700">${fmtVND(r.value)}</td>
    </tr>
  `).join('')

  const html = `<!DOCTYPE html><html lang="vi"><head><meta charset="UTF-8"/>
  <title>Phiếu Lương ${periodLabel} — ${employeeName}</title>
  <style>${BASE_CSS}</style></head><body>

  ${LOGO_HEADER(department || 'Nhân viên', periodLabel)}

  <div class="title-bar">
    <div>
      <h1>PHIẾU THANH TOÁN TIỀN LƯƠNG CÁ NHÂN</h1>
      <p>${department} · ${periodLabel} · Trạng thái: ${status}</p>
    </div>
    <div class="badge">NHÂN VIÊN</div>
  </div>

  <!-- EMPLOYEE INFO -->
  <div class="emp-box">
    <div>
      <div class="emp-field-label">Họ và tên</div>
      <div class="emp-field-value">${employeeName}</div>
    </div>
    <div>
      <div class="emp-field-label">Mã nhân viên</div>
      <div class="emp-field-value">${employeeCode}</div>
    </div>
    <div>
      <div class="emp-field-label">Chức vụ</div>
      <div class="emp-field-value">${position}</div>
    </div>
    <div>
      <div class="emp-field-label">Phòng ban</div>
      <div class="emp-field-value">${department}</div>
    </div>
    <div>
      <div class="emp-field-label">Kỳ lương</div>
      <div class="emp-field-value">${periodLabel}</div>
    </div>
    <div>
      <div class="emp-field-label">Ngày xuất</div>
      <div class="emp-field-value">${today()}</div>
    </div>
  </div>

  <!-- NET BANNER -->
  <div class="net-banner">
    <div>
      <div class="net-label">Thực lĩnh cuối cùng (NET)</div>
      <div class="net-amount">${fmtVND(netAmount)}</div>
    </div>
    <div class="net-status">
      <div style="font-size:9px;opacity:0.7;margin-bottom:4px">Trạng thái thanh toán</div>
      <div style="font-size:13px;font-weight:800">${status}</div>
    </div>
  </div>

  <!-- INCOME & DEDUCTION BREAKDOWN -->
  <div class="breakdown">
    <div class="breakdown-section income">
      <h5>Thu nhập (+)</h5>
      ${incomeRows}
      <div class="line-total">
        <span class="line-total-label">Tổng thu nhập</span>
        <span class="line-total-value green">${fmtVND(totalIncome)}</span>
      </div>
    </div>
    <div class="breakdown-section deduct">
      <h5>Khấu trừ (-)</h5>
      ${deductRows}
      <div class="line-total">
        <span class="line-total-label">Tổng khấu trừ</span>
        <span class="line-total-value red">${fmtVND(totalDeduction)}</span>
      </div>
    </div>
  </div>

  <!-- SUMMARY CARDS -->
  <div class="summary summary-2">
    <div class="card blue"><div class="card-label">Thực nhận (NET)</div><div class="card-value">${fmtVND(netAmount)}</div></div>
    <div class="card red"><div class="card-label">Tổng khấu trừ</div><div class="card-value">- ${fmtVND(totalDeduction)}</div></div>
  </div>

  ${insRows ? `
  <!-- INSURANCE TABLE -->
  <div class="ins-table-wrapper">
    <div class="ins-header">Định mức trích đóng bảo hiểm</div>
    <table>
      <thead>
        <tr>
          <th>Loại quỹ</th>
          <th class="center">NLĐ (%)</th>
          <th class="center">Công ty (%)</th>
          <th class="right">Mức đóng / Tháng</th>
        </tr>
      </thead>
      <tbody>${insRows}</tbody>
    </table>
  </div>` : ''}

  <div class="disclaimer">
    <p>⚡ Chứng từ điện tử được trích xuất tự động từ AET HRM System. Mọi thắc mắc về sai sót vui lòng phản hồi phòng C&B trước ngày 08 của tháng tiếp theo. Tài liệu này có giá trị xác nhận khi có chữ ký và dấu xác nhận của cấp có thẩm quyền.</p>
  </div>

  ${SIGNATURES('employee')}
  ${PDF_FOOTER(periodLabel)}
  <script>window.onload=function(){window.print()}<\/script>
  </body></html>`

  openPrint(html)
}
