// Dữ liệu mẫu tĩnh cho màn hình Giám Đốc (KPI, Charts, Notifications, Timeline, Reminders)

export const kpiCards = [
  {
    id: 1,
    label: "Tổng Nhân Sự",
    value: "1,284",
    icon: "groups",
    iconClass: "kpi-icon--blue",
    badge: "+12",
    badgeIcon: "trending_up",
    badgeClass: "kpi-badge--up",
    route: "/giam-doc/nhan-su",
    footerType: "sparkline",
    sparkline: [30, 45, 35, 50, 40, 60, 55],
    meta: "Tăng trưởng 2% tháng này"
  },
  {
    id: 2,
    label: "Quỹ Lương Tháng",
    value: "4.2B",
    icon: "payments",
    iconClass: "kpi-icon--amber",
    badge: "+5.4%",
    badgeIcon: "trending_up",
    badgeClass: "kpi-badge--down",
    route: "/giam-doc/bang-luong",
    footerType: "progress",
    progress: 85,
    progressClass: "kpi-progress-fill--amber",
    meta: "85% ngân sách dự kiến"
  },
  {
    id: 3,
    label: "Tỷ Lệ Nghỉ Việc",
    value: "0.8%",
    icon: "person_exit",
    iconClass: "kpi-icon--rose",
    badge: "-0.2%",
    badgeIcon: "trending_down",
    badgeClass: "kpi-badge--up",
    route: "/giam-doc/nhan-su",
    footerType: "sparkline",
    sparkline: [1.2, 1.1, 1.3, 1.0, 0.9, 0.8, 0.8],
    sparklineDanger: true,
    meta: "Thấp hơn trung bình ngành"
  },
  {
    id: 4,
    label: "Chỉ Số Hài Lòng",
    value: "4.8",
    icon: "sentiment_very_satisfied",
    iconClass: "kpi-icon--green",
    badge: "96%",
    badgeIcon: "grade",
    badgeClass: "kpi-badge--up",
    route: "/giam-doc/danh-gia",
    footerType: "progress",
    progress: 96,
    meta: "Dựa trên khảo sát nội bộ"
  }
];

export const barChartData = [
  { label: 'T1', current: 1150, target: 1100 },
  { label: 'T2', current: 1180, target: 1150 },
  { label: 'T3', current: 1210, target: 1200 },
  { label: 'T4', current: 1240, target: 1250 },
  { label: 'T5', current: 1260, target: 1280 },
  { label: 'T6', current: 1284, target: 1300, active: true }
];

export const barChartYLabels = ['1,400', '1,200', '1,000', '800', '600'];

export const donutData = [
  { label: 'Sản xuất', pct: 45, color: '#3B82F6' },
  { label: 'Kinh doanh', pct: 25, color: '#F59E0B' },
  { label: 'Kỹ thuật', pct: 20, color: '#10B981' },
  { label: 'HCNS', pct: 10, color: '#6366F1' }
];

export const donutTotal = 1284;

export const pendingApprovals = [
  {
    id: "app1",
    title: "Tuyển dụng Chuyên viên Markting",
    meta: "Phòng Marketing - 2 vị trí",
    icon: "person_add",
    iconClass: "approval-icon--blue",
    urgent: true,
    actions: ["Từ chối", "Phê duyệt"]
  },
  {
    id: "app2",
    title: "Đề xuất tăng lương quý II",
    meta: "Phòng Production - 5 nhân sự",
    icon: "trending_up",
    iconClass: "approval-icon--amber",
    urgent: false,
    actions: ["Từ chối", "Phê duyệt"]
  },
  {
    id: "app3",
    title: "Mua sắm thiết bị IT",
    meta: "10 Laptop Dell XPS - 450tr",
    icon: "shopping_cart",
    iconClass: "approval-icon--blue",
    urgent: true,
    actions: ["Từ chối", "Phê duyệt"]
  }
];

// Dành cho Layout_GiamDoc.vue (Notification Popup)
export const approvalRequests = [
  {
    id: "req1",
    initials: "TH",
    name: "Trần Văn Hoàng",
    title: "Xin nghỉ phép 3 ngày",
    urgent: true,
    avatarBg: "bg-pink-100",
    avatarColor: "text-pink-600"
  },
  {
    id: "req2",
    initials: "LM",
    name: "Lê Thị Mai",
    title: "Đề xuất mua sắm VPP",
    urgent: false,
    avatarBg: "bg-indigo-100",
    avatarColor: "text-indigo-600"
  },
  {
    id: "req3",
    initials: "NP",
    name: "Nguyễn Nam Phong",
    title: "Xác nhận tăng ca",
    urgent: false,
    avatarBg: "bg-green-100",
    avatarColor: "text-green-600"
  }
];

export const importantNotifications = [
    {
      id: "n1",
      level: "canh_bao",
      levelLabel: "CẢNH BÁO",
      levelColor: "text-red-700",
      levelBg: "bg-red-50",
      dotColor: "bg-red-500",
      title: "Cảnh báo quỹ lương vượt giới hạn",
      desc: "Phòng Kinh doanh đã vượt 15% quỹ lương dự kiến tháng này.",
      action: "Xem chi tiết",
      actionRoute: "/giamdoc/bangluong",
      time: "10 phút trước"
    },
    {
      id: "n2",
      level: "thong_tin",
      levelLabel: "THÔNG BÁO",
      levelColor: "text-blue-700",
      levelBg: "bg-blue-50",
      dotColor: "bg-blue-500",
      title: "Hoàn thành đánh giá quý I",
      desc: "Hệ thống đã tự động xuất báo cáo đánh giá KPI toàn công ty.",
      action: "Tải báo cáo",
      actionRoute: "/giamdoc/",
      time: "1 giờ trước"
    },
    {
      id: "n3",
      level: "tich_cuc",
      levelLabel: "TIN TỐT",
      levelColor: "text-green-700",
      levelBg: "bg-green-50",
      dotColor: "bg-green-500",
      title: "Tuyển dụng thành công Giám đốc Marketing",
      desc: "Ứng viên Nguyễn Văn A đã xác nhận offer letter.",
      action: "",
      actionRoute: "",
      time: "2 giờ trước"
    }
];

export const timelineEvents = [
  {
    id: 1,
    time: '09:00 Hôm nay',
    title: 'Họp chiến lược nhân sự quý III',
    place: 'Phòng họp Boardroom A',
    placeIcon: 'meeting_room',
    active: true
  },
  {
    id: 2,
    time: '14:30 Chiều nay',
    title: 'Phỏng vấn ứng viên Giám đốc Marketing',
    place: 'Phòng họp 2',
    placeIcon: 'person_search',
    active: false
  },
  {
    id: 3,
    time: '16:00 Ngày mai',
    title: 'Duyệt quỹ thưởng 6 tháng đầu năm',
    place: 'Hệ thống HRM',
    placeIcon: 'payments',
    active: false
  }
];

export const reminderText = "Anh nhớ xử lý gấp 5 đơn xin nghỉ thai sản tồn đọng tuần trước để giải phóng quỹ bảo hiểm phúc lợi.";
