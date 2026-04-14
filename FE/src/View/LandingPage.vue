<template>
  <div class="landing-page-v2">
    <!-- NAV -->
    <nav id="nav">
      <a href="#hero" class="nav-logo">AET</a>
      <ul class="nav-links">
        <li><a href="#loi-ich">Lợi Ích</a></li>
        <li><a href="#dich-vu">Dịch Vụ</a></li>
        <li><a href="#quy-trinh">Quy Trình</a></li>
        <li><a href="#bang-gia">Bảng Giá</a></li>
        <li><a href="#danh-gia">Đánh Giá</a></li>
        <li><a href="#tuyen-dung">Tuyển Dụng</a></li>
      </ul>
      <div style="display:flex; align-items:center;">
          <div class="burger" id="burger"><span></span><span></span><span></span></div>
      </div>
    </nav>

    <div id="overlay"></div>
    <nav id="drawer">
      <a href="#loi-ich">Lợi Ích</a>
      <a href="#dich-vu">Dịch Vụ</a>
      <a href="#quy-trinh">Quy Trình</a>
      <a href="#bang-gia">Bảng Giá</a>
      <a href="#danh-gia">Đánh Giá</a>
      <a href="#tuyen-dung">Tuyển Dụng</a>
      <router-link to="/login" class="nav-cta" style="justify-content:center;margin-top:8px; display:inline-flex;">Portal CV / Đăng nhập</router-link>
    </nav>

    <!-- JD MODAL -->
    <div id="jd-modal">
      <div class="jd-box" id="jd-box">
        <button class="jd-close" id="jd-close-btn">✕</button>
        <div id="jd-content"></div>
        <button class="jd-apply-btn" id="jd-apply-btn">
          Ứng Tuyển Ngay →
        </button>
      </div>
    </div>

    <!-- ══ HERO ══ -->
    <section id="hero">
      <div class="hero-glow"></div>
      <div class="hero-inner">
        <div class="h-badge"><i></i>Đối Tác Tự Động Hóa AI Thế Hệ Mới</div>
        <h1 class="h-title">
          Tự Động Thông Minh.<br>
          <span class="accent">Tăng Trưởng Vượt Bậc.</span><br>
          Cùng AI.
        </h1>
        <p class="h-sub">Giải pháp tự động hóa AI cho doanh nghiệp hiện đại — Đơn giản, Hiệu quả, Sẵn sàng mở rộng.</p>
        <div class="h-btns">
          <a href="#apply-form-wrap" class="btn-blue">
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
              <path
                d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07 19.5 19.5 0 01-6-6A19.79 19.79 0 012.12 4.18 2 2 0 014.11 2h3a2 2 0 012 1.72c.127.96.361 1.903.7 2.81a2 2 0 01-.45 2.11L8.09 9.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0122 16.92z" />
            </svg>
            Đặt Lịch Miễn Phí
          </a>
          <a href="#dich-vu" class="btn-ghost">
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <circle cx="12" cy="12" r="10" />
              <polygon points="10 8 16 12 10 16 10 8" fill="currentColor" stroke="none" />
            </svg>
            Xem Dịch Vụ
          </a>
        </div>
        <div class="hero-mockup">
          <div class="mock-bar">
            <div class="mock-dot" style="background:#ff5f57"></div>
            <div class="mock-dot" style="background:#febc2e"></div>
            <div class="mock-dot" style="background:#28c840"></div>
            <span style="margin-left:12px;font-size:12px;color:#666">Bảng Điều Khiển AI — AET</span>
          </div>
          <div class="mock-body">
            <div class="mock-chips">
              <div class="mock-chip hi">⚡ Quy Trình Đang Chạy</div>
              <div class="mock-chip">📊 Phân Tích</div>
              <div class="mock-chip">🤖 Tác Tử AI</div>
              <div class="mock-chip">🔗 Tích Hợp</div>
            </div>
            <div>
              <div class="mock-label">Hiệu Suất Tự Động Hóa</div>
              <div class="mock-bar-row">
                <div class="mock-bar-head"><span>Tự Động Hóa Tác Vụ</span><span>92%</span></div>
                <div class="mock-track">
                  <div class="mock-fill" style="width:92%"></div>
                </div>
              </div>
              <div class="mock-bar-row">
                <div class="mock-bar-head"><span>Xử Lý Khách Hàng</span><span>78%</span></div>
                <div class="mock-track">
                  <div class="mock-fill" style="width:78%"></div>
                </div>
              </div>
              <div class="mock-bar-row">
                <div class="mock-bar-head"><span>Giảm Chi Phí</span><span>55%</span></div>
                <div class="mock-track">
                  <div class="mock-fill" style="width:55%"></div>
                </div>
              </div>
            </div>
            <div class="mock-stats">
              <div class="mock-stat">
                <div class="mock-stat-n">+75%</div>
                <div class="mock-stat-l">Năng suất</div>
              </div>
              <div class="mock-stat">
                <div class="mock-stat-n">3.2x</div>
                <div class="mock-stat-l">Tăng trưởng</div>
              </div>
              <div class="mock-stat">
                <div class="mock-stat-n">−50%</div>
                <div class="mock-stat-l">Chi phí vận hành</div>
              </div>
              <div class="mock-stat">
                <div class="mock-stat-n">24/7</div>
                <div class="mock-stat-l">AI hoạt động</div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- BENEFITS -->
    <section class="section-wrap" id="loi-ich">
      <div class="tag">Lợi Ích</div>
      <h2 class="s-title">Tại Sao Chọn Chúng Tôi?</h2>
      <p class="s-sub">Mọi thứ bạn cần để tự động hóa, tối ưu hóa và mở rộng quy mô doanh nghiệp.</p>
      <div class="benefits-grid">
        <div class="b-card reveal">
          <div class="b-icon"><svg viewBox="0 0 24 24">
              <polyline points="22 12 18 12 15 21 9 3 6 12 2 12" />
            </svg></div>
          <h3>Thông Tin Thời Gian Thực</h3>
          <p>Truy cập dữ liệu chính xác theo thời gian thực để đưa ra quyết định thông minh hơn cho toàn bộ tổ chức.</p>
        </div>
        <div class="b-card reveal">
          <div class="b-icon"><svg viewBox="0 0 24 24">
              <line x1="18" y1="20" x2="18" y2="10" />
              <line x1="12" y1="20" x2="12" y2="4" />
              <line x1="6" y1="20" x2="6" y2="14" />
            </svg></div>
          <h3>Tác Động Đo Lường Được</h3>
          <p>Theo dõi hiệu suất, khám phá insights và đạt tăng trưởng dựa trên dữ liệu với báo cáo ROI rõ ràng.</p>
        </div>
        <div class="b-card reveal">
          <div class="b-icon"><svg viewBox="0 0 24 24">
              <circle cx="18" cy="5" r="3" />
              <circle cx="6" cy="12" r="3" />
              <circle cx="18" cy="19" r="3" />
              <line x1="8.59" y1="13.51" x2="15.42" y2="17.49" />
              <line x1="15.41" y1="6.51" x2="8.59" y2="10.49" />
            </svg></div>
          <h3>Tích Hợp Liền Mạch</h3>
          <p>Kết nối công cụ, nhóm và quy trình làm việc với tự động hóa thông minh phù hợp với hệ thống hiện tại.</p>
        </div>
      </div>
    </section>

    <!-- SERVICES -->
    <section class="section-wrap dark" id="dich-vu">
      <div class="tag">Dịch Vụ</div>
      <h2 class="s-title">Dịch Vụ Thông Minh,<br>Được Xây Dựng Với AI</h2>
      <p class="s-sub">Mọi thứ bạn cần để tự động hóa vận hành và tăng năng suất.</p>
      <div class="services-grid">
        <div class="sv-card reveal">
          <div class="sv-chips"><span class="chip">Quản Lý Chi Phí</span><span class="chip">Nhắc Thanh Toán</span><span
              class="chip">Mạng Xã Hội</span></div>
          <h3>Tự Động Hóa Tác Vụ Lặp Đi Lặp Lại</h3>
          <p>Chúng tôi giúp đơn giản hóa vận hành nội bộ bằng cách tự động hóa quy trình thủ công — từ quản lý chi phí đến
            theo dõi nhân viên và đăng mạng xã hội.</p>
        </div>
        <div class="sv-card reveal">
          <div class="sv-chips"><span class="chip">Quy Trình</span><span class="chip">Đa Bước</span><span class="chip">Đa
              Nền Tảng</span></div>
          <h3>Quy Trình Làm Việc Tự Động</h3>
          <p>Xây dựng quy trình thông minh tự động hóa các bước phức tạp trên nhiều công cụ và nền tảng, tăng hiệu suất
            cho mọi phòng ban.</p>
        </div>
        <div class="sv-card reveal">
          <div class="sv-chips"><span class="chip">Nghiên Cứu</span><span class="chip">Dữ Liệu Sống</span><span
              class="chip">Phân Tích</span></div>
          <h3>Thông Tin Thời Gian Thực</h3>
          <p>Đưa ra quyết định thông minh hơn với dữ liệu trực tiếp. Tiếp cận nghiên cứu thời gian thực trong mọi lĩnh vực
            để luôn dẫn đầu.</p>
        </div>
        <div class="sv-card reveal">
          <div class="code-block">
            <div><span class="c-num">1</span><span class="c-kw">class </span><span class="c-fn">AutomationAgent</span>:
            </div>
            <div><span class="c-num">2</span> <span class="c-kw">def </span><span class="c-fn">__init__</span>(self,
              limit):</div>
            <div><span class="c-num">3</span> self.limit = limit</div>
            <div><span class="c-num">4</span> self.mode = <span class="c-str">"idle"</span></div>
            <div><span class="c-num">5</span> <span class="c-kw">def </span><span class="c-fn">evaluate</span>(self,
              value):</div>
            <div><span class="c-num">6</span> <span class="c-kw">if</span> value &gt; self.limit:</div>
            <div><span class="c-num">7</span> <span class="c-kw">return </span><span class="c-str">"Tác tử đã kích
                hoạt!"</span></div>
          </div>
          <h3>Phát Triển Tác Tử AI Tùy Chỉnh</h3>
          <p>Chúng tôi phát triển tác tử AI tùy chỉnh tích hợp liền mạch với công cụ của bạn và tự động hóa logic nghiệp
            vụ phức tạp.</p>
        </div>
        <div class="sv-card reveal" style="min-height: 250px;">
          <div class="sv-chips"><span class="chip">Chiến Lược</span><span class="chip">Tư Vấn</span><span
              class="chip">Tăng Trưởng</span></div>
          <h3>Tư Vấn Chiến Lược AI</h3>
          <p>Nhận hướng dẫn chuyên gia để triển khai giải pháp AI thúc đẩy tăng trưởng kinh doanh, giảm chi phí và tạo lợi
            thế cạnh tranh.</p>
        </div>
        <div class="sv-card reveal" style="min-height: 250px;">
          <div class="sv-chips"><span class="chip">KPIs</span><span class="chip">Báo Cáo</span><span class="chip">Bảng
              Điều Khiển</span></div>
          <h3>Theo Dõi Hiệu Suất</h3>
          <p>Theo dõi kết quả tự động hóa theo thời gian thực để liên tục cải thiện và mở rộng quy trình làm việc với sự
            tự tin.</p>
        </div>
      </div>
    </section>

    <!-- FEATURES -->
    <section class="section-wrap" id="tinh-nang">
      <div class="tag">Tính Năng</div>
      <h2 class="s-title">Tất Cả Tính Năng Tại Một Nơi</h2>
      <p class="s-sub">Mọi thứ cần thiết để tự động hóa và tăng năng suất.</p>
      <div class="features-grid">
        <div class="ft-item reveal">
          <div class="ft-num">01</div>
          <h4>Tự Động Hóa Quy Trình</h4>
          <p>Tự động hóa các quy trình nghiệp vụ phức tạp để tăng tốc độ, rõ ràng và hiệu quả.</p>
        </div>
        <div class="ft-item reveal">
          <div class="ft-num">02</div>
          <h4>Giải Pháp AI Tùy Chỉnh</h4>
          <p>Xây dựng hệ thống AI phù hợp với mục tiêu và thách thức kinh doanh đặc thù của bạn.</p>
        </div>
        <div class="ft-item reveal">
          <div class="ft-num">03</div>
          <h4>Trợ Lý AI</h4>
          <p>Triển khai tác tử ảo thông minh để đơn giản hóa tác vụ và tăng năng suất nhóm.</p>
        </div>
        <div class="ft-item reveal">
          <div class="ft-num">04</div>
          <h4>Bán Hàng & Marketing</h4>
          <p>Tận dụng AI để tối ưu chiến dịch, theo dõi khách hàng tiềm năng và cá nhân hóa outreach.</p>
        </div>
        <div class="ft-item reveal">
          <div class="ft-num">05</div>
          <h4>Theo Dõi Hiệu Suất</h4>
          <p>Theo dõi kết quả tự động hóa theo thời gian thực để liên tục cải thiện quy trình.</p>
        </div>
        <div class="ft-item reveal">
          <div class="ft-num">06</div>
          <h4>Tích Hợp Liền Mạch</h4>
          <p>Kết nối công cụ và ứng dụng cho quy trình AI thống nhất, mượt mà và không ma sát.</p>
        </div>
      </div>
    </section>

    <!-- PROCESS -->
    <section class="section-wrap dark" id="quy-trinh">
      <div class="tag">Quy Trình</div>
      <h2 class="s-title">Quy Trình Đơn Giản &amp; Thông Minh</h2>
      <p class="s-sub">Mọi thứ bạn cần để cộng tác, sáng tạo và mở rộng quy mô.</p>
      <div id="process-list"></div>
    </section>

    <!-- SUCCESS -->
    <section class="section-wrap" id="thanh-cong">
      <div class="tag">Khách Hàng</div>
      <h2 class="s-title">Câu Chuyện Thành Công</h2>
      <p class="s-sub">Khám phá cách các doanh nghiệp đạt kết quả thực sự, có thể đo lường.</p>
      <div class="success-grid">
        <div class="sc-card reveal">
          <h4>Cuộc Cách Mạng SaaS của Minh</h4>
          <div class="sc-stats">
            <div><span class="sc-stat-n">50%</span><span class="sc-stat-l">Giảm Chi Phí</span></div>
            <div><span class="sc-stat-n">75%</span><span class="sc-stat-l">Tăng Năng Suất</span></div>
          </div>
          <p>Minh tại CloudFlow đã cắt giảm chi phí vận hành và tăng năng suất đội nhóm đáng kể bằng cách triển khai tự
            động hóa AI toàn diện.</p>
        </div>
        <div class="sc-card reveal">
          <h4>Thành Công Thương Mại Điện Tử của Lan</h4>
          <div class="sc-stats">
            <div><span class="sc-stat-n">60%</span><span class="sc-stat-l">Tăng ROI</span></div>
            <div><span class="sc-stat-n">45%</span><span class="sc-stat-l">Cá Nhân Hóa</span></div>
          </div>
          <p>Lan tại BloomTech đã chuyển đổi nỗ lực marketing with AI tools, cải thiện đáng kể mức độ tương tác và lòng trung thành của khách hàng.</p>
        </div>
        <div class="sc-card reveal">
          <h4>Đột Phá Bán Lẻ của Phương</h4>
          <div class="sc-stats">
            <div><span class="sc-stat-n">40%</span><span class="sc-stat-l">Tương Tác</span></div>
            <div><span class="sc-stat-n">30%</span><span class="sc-stat-l">Mua Lặp Lại</span></div>
          </div>
          <p>Phương tại Trendify dùng phân tích AI để hiểu sâu hành vi khách hàng, tạo mối quan hệ lâu dài và tăng trưởng lợi nhuận bền vững.</p>
        </div>
      </div>
    </section>

    <!-- INTEGRATIONS -->
    <section class="section-wrap dark" id="tich-hop" style="padding-left:0;padding-right:0">
      <div class="int-head">
        <div class="tag" style="display:block">Tích Hợp</div>
        <h2 class="s-title">Tích Hợp Liền Mạch</h2>
        <p class="s-sub" style="margin:0 auto">AI của chúng tôi kết nối với toàn bộ công cụ của bạn tạo ra quy trình thông
          minh thống nhất.</p>
      </div>
      <div class="marquee-wrap">
        <div class="marquee-track" id="marquee-track"></div>
      </div>
    </section>

    <!-- REVIEWS -->
    <section class="section-wrap" id="danh-gia">
      <div class="tag">Đánh Giá</div>
      <h2 class="s-title">Được Tin Tưởng Bởi Các Nhà Lãnh Đạo</h2>
      <p class="s-sub">Nghe từ những người dùng thực sự đã đạt thành công với nền tảng tự động hóa của chúng tôi.</p>
      <div class="reviews-grid" id="reviews-grid"></div>
    </section>

    <!-- PRICING -->
    <section class="section-wrap dark" id="bang-gia">
      <div class="tag">Bảng Giá</div>
      <h2 class="s-title">Gói Linh Hoạt Cho Mọi Nhu Cầu</h2>
      <p class="s-sub">Chọn gói phù hợp với mục tiêu và mở rộng khi bạn phát triển.</p>
      <div class="price-toggle">
        <button class="toggle-opt on" id="t-monthly">Hàng Tháng</button>
        <button class="toggle-opt" id="t-yearly">Hàng Năm <span class="save-pill">Tiết kiệm 20%</span></button>
      </div>
      <div class="pricing-grid" id="pricing-grid"></div>
      <div class="donate-note">Chúng tôi đóng góp 2% doanh thu cho phúc lợi nhi đồng</div>
    </section>

    <!-- FAQ -->
    <section class="section-wrap" id="faq">
      <div style="text-align:center">
        <div class="tag">Câu Hỏi Thường Gặp</div>
        <h2 class="s-title">Giải Đáp Thắc Mắc</h2>
        <p class="s-sub" style="margin:0 auto">Tìm câu trả lời nhanh cho những câu hỏi phổ biến nhất.</p>
      </div>
      <div class="faq-wrap" id="faq-list"></div>
    </section>

    <!-- COMPARISON -->
    <section class="section-wrap dark" id="so-sanh">
      <div class="tag">So Sanh</div>
      <h2 class="s-title">Tại Sao Chọn Chúng Tôi Thay Vì Đối Thủ</h2>
      <p class="s-sub">Xem cách chúng tôi vượt trội so với đối thủ cạnh tranh.</p>
      <div class="cmp-table">
        <div class="cmp-head">
          <div class="cmp-h">Tính Năng</div>
          <div class="cmp-h blue">✦ AET AI</div>
          <div class="cmp-h">Đối Thủ</div>
        </div>
        <div class="cmp-row">
          <div class="cmp-feat">Tốc Độ Triển Khai</div>
          <div class="cmp-yes">✓ Nhanh, quy trình AI sẵn sàng</div>
          <div class="cmp-no">✗ Chậm, thiết lập thủ công</div>
        </div>
        <div class="cmp-row">
          <div class="cmp-feat">Khả Năng Mở Rộng</div>
          <div class="cmp-yes">✓ Xây dựng để tăng trưởng</div>
          <div class="cmp-no">✗ Cần cập nhật thủ công</div>
        </div>
        <div class="cmp-row">
          <div class="cmp-feat">Phân Tích</div>
          <div class="cmp-yes">✓ Phân tích AI thời gian thực</div>
          <div class="cmp-no">✗ Hạn chế hoặc chậm trễ</div>
        </div>
        <div class="cmp-row">
          <div class="cmp-feat">Hiệu Quả Chi Phí</div>
          <div class="cmp-yes">✓ Tự động hóa, giảm chi phí</div>
          <div class="cmp-no">✗ Chi phí lao động cao</div>
        </div>
        <div class="cmp-row">
          <div class="cmp-feat">Hỗ Trợ</div>
          <div class="cmp-yes">✓ Chuyên gia AI 24/7</div>
          <div class="cmp-no">✗ Hỗ trợ chung hoặc không có</div>
        </div>
      </div>
    </section>

    <!-- TEAM -->
    <section class="section-wrap" id="doi-ngu">
      <div class="tag">Đội Ngũ</div>
      <h2 class="s-title">Gặp Gỡ Chúng Tôi</h2>
      <p class="s-sub">Những con người đứng sau thành công tự động hóa của bạn.</p>
      <div class="team-grid" id="team-grid"></div>
    </section>

    <!-- ══ TUYỂN DỤNG ══ -->
    <section id="tuyen-dung">
      <div class="td-header reveal">
        <div class="tag">Tuyển Dụng</div>
        <h2 class="s-title">Cơ Hội Nghề Nghiệp</h2>
        <p class="s-sub">Gia nhập đội ngũ tiên phong xây dựng tương lai AI. Chúng tôi đang tìm kiếm những tài năng đam mê
          đổi mới.</p>
      </div>

      <!-- Job Cards -->
      <div class="jobs-grid" id="jobs-grid"></div>

      <!-- Apply Form -->
      <div id="apply-form-wrap" class="reveal">
        <div class="form-header">
          <h3>📋 Nộp Đơn Ứng Tuyển</h3>
          <p>Điền thông tin bên dưới — chúng tôi sẽ liên hệ trong vòng 2–3 ngày làm việc.</p>
        </div>
        <div id="form-body">
          <div class="form-grid">
            <div class="form-group">
              <label>Họ và Tên <span>*</span></label>
              <input type="text" id="f-name" class="form-input" placeholder="Nguyễn Văn A" />
            </div>
            <div class="form-group">
              <label>Email <span>*</span></label>
              <input type="email" id="f-email" class="form-input" placeholder="email@example.com" />
            </div>
            <div class="form-group">
              <label>Số Điện Thoại</label>
              <input type="tel" id="f-phone" class="form-input" placeholder="0912 345 678" />
            </div>
            <div class="form-group">
              <label>Vị Trí Ứng Tuyển <span>*</span></label>
              <select id="f-position" class="form-input">
                <option value="">-- Chọn vị trí --</option>
                <option>Kỹ Sư AI / Machine Learning</option>
                <option>Chuyên Gia Tự Động Hóa</option>
                <option>Tư Vấn Chiến Lược AI</option>
                <option>Fullstack Developer</option>
                <option>Chuyên Viên Marketing</option>
                <option>Chăm Sóc Khách Hàng</option>
              </select>
            </div>
            <div class="form-group">
              <label>LinkedIn / Portfolio</label>
              <input type="url" id="f-linkedin" class="form-input" placeholder="https://linkedin.com/in/..." />
            </div>
            <div class="form-group">
              <label>Mức Lương Kỳ Vọng</label>
              <input type="text" id="f-salary" class="form-input" placeholder="VD: 15.000.000 – 20.000.000 VNĐ" />
            </div>
            <div class="form-group full">
              <label>Thư Giới Thiệu</label>
              <textarea id="f-cover" class="form-input" rows="4"
                placeholder="Giới thiệu ngắn về bản thân và lý do bạn muốn gia nhập AET..."
                style="resize:vertical;min-height:100px"></textarea>
            </div>

            <div class="form-group full">
              <label>Tải Lên CV <span>*</span></label>
              <div class="file-upload-area" id="cv-upload-area">
                <input type="file" id="f-cv" accept=".pdf,.doc,.docx" />
                <div class="file-icon">
                  <svg viewBox="0 0 24 24">
                    <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z" />
                    <polyline points="14 2 14 8 20 8" />
                    <line x1="16" y1="13" x2="8" y2="13" />
                    <line x1="16" y1="17" x2="8" y2="17" />
                    <polyline points="10 9 9 9 8 9" />
                  </svg>
                </div>
                <div class="file-text">Kéo thả hoặc nhấn để tải CV lên</div>
                <div class="file-hint">Hỗ trợ PDF, DOC, DOCX — Tối đa 10MB</div>
                <div class="file-name-display" id="cv-name-display"></div>
              </div>
            </div>

          </div>
          <button class="form-submit" id="submit-btn">
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
              <line x1="22" y1="2" x2="11" y2="13" />
              <polygon points="22 2 15 22 11 13 2 9 22 2" />
            </svg>
            Gửi Đơn Ứng Tuyển
          </button>
          <div class="form-note">🔒 Thông tin của bạn được bảo mật tuyệt đối. Chúng tôi không chia sẻ dữ liệu với bên thứ
            ba.</div>
        </div>
        <div id="form-success">
          <div class="success-icon"><svg viewBox="0 0 24 24">
              <polyline points="20 6 9 17 4 12" />
            </svg></div>
          <h4>Đã Gửi Thành Công! 🎉</h4>
          <p>Cảm ơn bạn đã ứng tuyển tại AET.<br>Chúng tôi sẽ xem xét hồ sơ và liên hệ trong <strong
              style="color:var(--blue2)">2–3 ngày làm việc</strong>.</p>
        </div>
      </div>
    </section>
    <!-- FOOTER -->
    <footer>
      <div class="ft-logo">AET</div>
      <div class="ft-links">
        <a href="#loi-ich">Lợi Ích</a>
        <a href="#dich-vu">Dịch Vụ</a>
        <a href="#bang-gia">Bảng Giá</a>
        <a href="#danh-gia">Đánh Giá</a>
        <a href="#tuyen-dung">Tuyển Dụng</a>
        <a href="#">Bảo Mật</a>
        <a href="#">Điều Khoản</a>
      </div>
      <div class="ft-copy">© 2025 AET · AET@support.com</div>
    </footer>
  </div>
</template>

<script setup>
import { onMounted, onUnmounted } from 'vue';
import { loadMockData, mockJobPostings } from '@/mock-data/index.js';
import { submitApplication } from '@/composables/useRecruitmentStore';

onMounted(() => {
  const mockDataReady = loadMockData().catch(() => false);
  let publicPositions = [];

  const fetchPublicPositions = async () => {
    try {
      const response = await fetch('/api/v1/public/positions?page=1&per_page=500');
      const payload = await response.json().catch(() => ({}));
      if (!response.ok || payload?.success === false) return [];
      const items = Array.isArray(payload?.data) ? payload.data : [];

      return items
        .filter((item) => String(item?.position_name || '').trim() !== '')
        .map((item, index) => ({
          jobId: 100000 + Number(item?.position_id || index + 1),
          positionId: Number(item?.position_id || 0),
          title: String(item?.position_name || '').trim(),
          departmentName: String(item?.position_group || '').trim() || 'Khối vận hành',
          departmentId: null,
          workType: 'FULL_TIME',
          location: 'Văn phòng / Hybrid',
          salaryMin: null,
          salaryMax: null,
          description: `Ứng tuyển vị trí ${String(item?.position_name || '').trim()}.`,
          requirements: [String(item?.position_level || 'MIDDLE').trim() || 'MIDDLE'],
          benefits: ['Lương cạnh tranh', 'Môi trường chuyên nghiệp', 'Cơ hội phát triển'],
          status: 'ĐANG_MỞ',
        }));
    } catch {
      return [];
    }
  };
  const publicPositionsReady = fetchPublicPositions().then((items) => {
    publicPositions = items;
    return items;
  });

  // Add class to body to scope the landing page styles
  document.body.classList.add('landing-v2-active');

  /* ── ASSETS LOADING ── */
  // Load Google Font
  if (!document.getElementById('google-font-be-vietnam')) {
    const link = document.createElement('link');
    link.id = 'google-font-be-vietnam';
    link.rel = 'stylesheet';
    link.href = 'https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@300;400;500;600;700;800;900&display=swap';
    document.head.appendChild(link);
  }

  // Load custom CSS
  if (!document.getElementById('landing-v2-style')) {
    const link = document.createElement('link');
    link.id = 'landing-v2-style';
    link.rel = 'stylesheet';
    link.href = '/landing-v2/style.css';
    document.head.appendChild(link);
  }

  /* ── NAV SCROLL ── */
  const nav = document.getElementById('nav');
  const scrollHandler = () => {
    if (nav) nav.classList.toggle('stuck', window.scrollY > 30);
  };
  window.addEventListener('scroll', scrollHandler);

  /* ── MOBILE DRAWER ── */
  const burger = document.getElementById('burger');
  const drawer = document.getElementById('drawer');
  const overlay = document.getElementById('overlay');
  
  const openDrawer = (s) => {
    if (burger) burger.classList.toggle('open', s);
    if (drawer) drawer.classList.toggle('open', s);
    if (overlay) {
      overlay.style.opacity = s ? '1' : '0';
      overlay.style.display = s ? 'block' : 'none';
    }
    document.body.style.overflow = s ? 'hidden' : '';
  };

  if (burger) burger.addEventListener('click', () => openDrawer(!drawer?.classList.contains('open')));
  if (overlay) overlay.addEventListener('click', () => openDrawer(false));
  if (drawer) {
    drawer.querySelectorAll('a').forEach(a => a.addEventListener('click', () => openDrawer(false)));
  }

  /* ── SCROLL REVEAL ── */
  const obs = new IntersectionObserver(entries => {
    entries.forEach(e => {
      if (e.isIntersecting) e.target.classList.add('on');
    });
  }, { threshold: 0.08 });
  document.querySelectorAll('.reveal').forEach(el => obs.observe(el));

  /* ── PROCESS ── */
  const steps = [
    { n: '01', title: 'Khám Phá & Phân Tích', desc: 'Chúng tôi kiểm tra quy trình, công cụ và dữ liệu khách hàng hiện tại để phát hiện sự kém hiệu quả và cơ hội tự động hóa. Mọi hệ thống đều được lập bản đồ rõ ràng.' },
    { n: '02', title: 'Thiết Kế & Xây Dựng', desc: 'Đội ngũ thiết kế quy trình AI tùy chỉnh theo nhu cầu cụ thể. Chúng tôi xây dựng, kiểm tra và hoàn thiện mỗi tự động hóa trước khi triển khai — không có sự không chắc chắn.' },
    { n: '03', title: 'Triển Khai & Tối Ưu', desc: 'Chúng tôi triển khai tự động hóa, theo dõi hiệu suất theo thời gian thực và liên tục tối ưu mọi quy trình để đạt ROI tối đa và khả năng mở rộng lâu dài.' },
  ];
  const pl = document.getElementById('process-list');
  if (pl) {
    pl.innerHTML = '';
    steps.forEach(s => {
      const el = document.createElement('div');
      el.className = 'pr-item reveal';
      el.innerHTML = `<div class="pr-num">${s.n}</div><div class="pr-content"><h3>${s.title}</h3><p>${s.desc}</p></div>`;
      pl.appendChild(el);
      obs.observe(el);
    });
  }

  /* ── INTEGRATIONS ── */
  const ints = ['Slack', 'HubSpot', 'Salesforce', 'Notion', 'Zapier', 'Make.com', 'Airtable', 'Monday.com', 'Asana', 'Jira', 'Stripe', 'Google Workspace', 'Mailchimp', 'Intercom', 'Twilio', 'Shopify', 'Pipedrive', 'Zendesk', 'ActiveCampaign', 'ClickUp'];
  const track = document.getElementById('marquee-track');
  if (track) {
    track.innerHTML = '';
    [...ints, ...ints].forEach(n => {
      const d = document.createElement('div');
      d.className = 'int-item';
      d.textContent = n;
      track.appendChild(d);
    });
  }

  /* ── REVIEWS ── */
  const revs = [
    { name: 'Hồ Hoàng Chương', role: 'Giám Đốc Tập Đoàn Công Nghệ', text: 'Thực sự ấn tượng. Nền tảng AI xử lý thông minh vượt xa mong đợi và hòa nhập hoàn hảo vào hệ thống của chúng tôi.' },
    { name: 'Phan Hoàng Khải', role: 'Giáo sư tại Đại Học Quốc Gia', text: 'Thay đổi cuộc chơi. Luồng tự động hóa chạy hoàn hảo, giúp chúng tôi nghiên cứu và phân tích dữ liệu lớn một cách dễ dàng.' },
    { name: 'Vũ Thiên Ân', role: 'Tiến sĩ Công nghệ AI', text: 'Triển khai mượt mà. Hệ thống tự động hóa ở mức độ này thực sự là một bước tiến lớn cho các doanh nghiệp Việt.' },
    { name: 'Nguyễn Văn Tâm', role: 'Thạc sĩ - Giám Đốc Vận Hành', text: 'Đơn giản đến ngạc nhiên. Quy trình tự động hóa giúp giảm 50% thời gian xử lý thủ công, tăng đáng kể hiệu suất.' },
    { name: 'Đặng Minh Khôi', role: 'Chuyên gia Cố vấn AI cấp cao', text: 'Tiết kiệm thời gian rất nhiều. Một giải pháp AI thực tế và dễ dàng tích hợp mà mọi doanh nghiệp nên có.' },
    { name: 'Phạm Thu Hà', role: 'PGS.TS - Viện Khoa học Công nghệ', text: 'Rất trực quan. Không rườm rà, chỉ có hiệu suất. Quy trình làm việc thông minh mang lại sự đột phá lâu dài.' },
  ];
  const rg = document.getElementById('reviews-grid');
  if (rg) {
    rg.innerHTML = '';
    revs.forEach(r => {
      const el = document.createElement('div');
      el.className = 'rv-card reveal';
      el.innerHTML = `<div class="rv-stars">★★★★★</div><p class="rv-text">"${r.text}"</p><div class="rv-author"><div class="rv-av">${r.name[0]}</div><div><div class="rv-name">${r.name}</div><div class="rv-role">${r.role}</div></div></div>`;
      rg.appendChild(el);
      obs.observe(el);
    });
  }

  /* ── PRICING ── */
  const plans = [
    {
      name: 'Cơ Bản', mo: 1490000, yo: 1192000, pop: false,
      feats: ['3 Quy Trình Tự Động', 'Truy Cập Trợ Lý AI Cơ Bản', 'Tích Hợp Email + Slack', 'Báo Cáo Hàng Tháng', 'Hỗ Trợ Email']
    },
    {
      name: 'Chuyên Nghiệp', mo: 2490000, yo: 1992000, pop: true,
      feats: ['10+ Quy Trình Tự Động', 'Tính Năng AI Nâng Cao', 'Đánh Giá Chiến Lược 2 Tuần/Lần', 'Tích Hợp CRM + Marketing', 'Hỗ Trợ Ưu Tiên']
    },
    {
      name: 'Doanh Nghiệp', mo: null, yo: null, pop: false,
      feats: ['Quy Trình Không Giới Hạn', 'Chuyên Gia AI Riêng', 'API & Tích Hợp Riêng', 'Bảng Điều Khiển Thời Gian Thực', 'Hỗ Trợ 24/7 + SLA']
    },
  ];
  let billing = 'monthly';
  const fmt = (n) => n ? n.toLocaleString('vi-VN') + '₫' : null;

  const renderPricing = () => {
    const grid = document.getElementById('pricing-grid');
    if (!grid) return;
    grid.innerHTML = '';
    plans.forEach(p => {
      const price = p.mo === null ? null : (billing === 'yearly' ? p.yo : p.mo);
      const card = document.createElement('div');
      card.className = 'pr-card reveal' + (p.pop ? ' pop' : '');
      card.innerHTML = `<div class="pr-name">${p.name}</div>
    <div class="pr-price">${price !== null ? fmt(price) + '<small>/tháng</small>' : 'Liên hệ'}</div>
    <div class="pr-period">${price !== null ? 'thanh toán ' + (billing === 'monthly' ? 'hàng tháng' : 'hàng năm') : 'báo giá theo yêu cầu'}</div>
    <ul class="pr-feats">${p.feats.map(f => `<li>${f}</li>`).join('')}</ul>
    <button class="pr-btn ${p.pop ? 'pr-btn-fill' : 'pr-btn-out'}" onclick="window.location.hash='#apply-form-wrap'">Bắt Đầu Ngay</button>`;
      grid.appendChild(card);
      obs.observe(card);
    });
  };

  const tMonthly = document.getElementById('t-monthly');
  const tYearly = document.getElementById('t-yearly');
  if (tMonthly) tMonthly.addEventListener('click', () => { billing = 'monthly'; tMonthly.classList.add('on'); tYearly?.classList.remove('on'); renderPricing(); });
  if (tYearly) tYearly.addEventListener('click', () => { billing = 'yearly'; tYearly.classList.add('on'); tMonthly?.classList.remove('on'); renderPricing(); });

  renderPricing();

  /* ── FAQ ── */
  const faqs = [
    { q: 'Bạn có thể tự động hóa những loại quy trình nào?', a: 'Chúng tôi chuyên tự động hóa các quy trình lặp đi lặp lại trong vận hành, marketing, bán hàng và hỗ trợ khách hàng bằng AI và logic tùy chỉnh phù hợp với doanh nghiệp của bạn.' },
    { q: 'Tôi có cần kiến thức kỹ thuật để sử dụng dịch vụ không?', a: 'Hoàn toàn không. Đội ngũ của chúng tôi xử lý toàn bộ thiết lập, tích hợp và tối ưu hóa. Bạn chỉ cần tập trung vào mục tiêu kinh doanh — chúng tôi sẽ tự động hóa phần còn lại.' },
    { q: 'Bạn có thể tích hợp với các công cụ hiện tại của chúng tôi không?', a: 'Có! Chúng tôi hỗ trợ tích hợp với CRM, công cụ quản lý dự án, ứng dụng giao tiếp và nhiều hơn nữa — tất cả được điều chỉnh theo stack công nghệ hiện tại của bạn.' },
    { q: 'Mất bao lâu để triển khai?', a: 'Hầu hết khách hàng thấy tự động hóa đầu tiên hoạt động trong vòng 1–2 tuần, tùy thuộc vào độ phức tạp và số lượng quy trình cần triển khai.' },
    { q: 'AI của bạn có bảo mật và tuân thủ quy định không?', a: 'Hoàn toàn. Chúng tôi sử dụng các thực hành bảo mật cấp doanh nghiệp và đảm bảo tuân thủ các tiêu chuẩn bảo mật dữ liệu lớn bao gồm GDPR và các quy định Việt Nam.' },
  ];
  const fl = document.getElementById('faq-list');
  if (fl) {
    fl.innerHTML = '';
    faqs.forEach(f => {
      const item = document.createElement('div');
      item.className = 'fq-item';
      item.innerHTML = `<div class="fq-q">${f.q}<span class="fq-icon">+</span></div><div class="fq-a">${f.a}</div>`;
      item.querySelector('.fq-q').addEventListener('click', () => {
        const was = item.classList.contains('open');
        document.querySelectorAll('.fq-item').forEach(i => i.classList.remove('open'));
        if (!was) item.classList.add('open');
      });
      fl.appendChild(item);
    });
  }

  /* ── TEAM ── */
  const team = [
    { n: 'Phan Thành Danh' },
    { n: 'Mai Tấn Lộc' },
    { n: 'Trương Gia Bảo' },
    { n: 'Bùi Ngọc Bảo Ngân' },
    { n: 'Vũ Ngọc Hoàng' },
  ];
  const tg = document.getElementById('team-grid');
  if (tg) {
    tg.innerHTML = '';
    team.forEach(m => {
      const el = document.createElement('div');
      el.className = 'tm-card reveal';
      el.innerHTML = `<div class="tm-av">${m.n[0]}</div><div class="tm-name">${m.n}</div>${m.r ? `<div class="tm-role">${m.r}</div>` : ''}`;
      tg.appendChild(el);
      obs.observe(el);
    });
  }

  /* ── JOBS DATA ── */
  const jobs = [
    {
      id: 0, title: 'Kỹ Sư AI / Machine Learning', dept: 'Kỹ Thuật', type: 'Toàn Thời Gian', loc: 'Hà Nội / Remote', level: 'Senior', salary: '25–40 triệu VNĐ',
      skills: ['Python', 'TensorFlow', 'LangChain', 'RAG', 'FastAPI'],
      desc: 'Xây dựng và triển khai mô hình AI/ML phục vụ sản phẩm tự động hóa thực tế cho khách hàng doanh nghiệp.',
      responsibilities: ['Nghiên cứu, thiết kế và phát triển các mô hình AI/ML', 'Tích hợp LLM (GPT-4, Claude, Gemini) vào pipeline sản xuất', 'Tối ưu hiệu suất mô hình và giảm chi phí inference', 'Hợp tác với team sản phẩm để hiện thực hóa tính năng AI', 'Viết tài liệu kỹ thuật và code review'],
      requirements: ['3+ năm kinh nghiệm ML/AI trong môi trường production', 'Thành thạo Python, TensorFlow/PyTorch', 'Kinh nghiệm với LangChain, RAG, Vector DB', 'Hiểu biết về MLOps, Docker, Kubernetes', 'Tiếng Anh đọc tài liệu kỹ thuật tốt'],
      benefits: ['Lương cạnh tranh + thưởng hiệu suất', 'Làm việc với công nghệ AI tiên tiến nhất', 'Flexible working hours & remote-friendly', 'Budget học tập 5 triệu/năm', 'Bảo hiểm sức khỏe premium'],
    },
    {
      id: 1, title: 'Chuyên Gia Tự Động Hóa', dept: 'Triển Khai', type: 'Toàn Thời Gian', loc: 'TP.HCM / Hà Nội', level: 'Mid–Senior', salary: '18–30 triệu VNĐ',
      skills: ['Zapier', 'Make.com', 'n8n', 'API', 'Python'],
      desc: 'Thiết kế và triển khai các quy trình tự động hóa cho khách hàng doanh nghiệp vừa và lớn, sử dụng nền tảng no-code/low-code và tích hợp AI.',
      responsibilities: ['Phân tích quy trình kinh doanh và đề xuất giải pháp tự động hóa', 'Xây dựng workflow trên Zapier, Make.com, n8n', 'Tích hợp API với CRM, ERP và công cụ marketing', 'Đào tạo khách hàng sử dụng hệ thống tự động hóa', 'Theo dõi và bảo trì các quy trình đã triển khai'],
      requirements: ['2+ năm kinh nghiệm tự động hóa quy trình', 'Thành thạo ít nhất một nền tảng: Zapier / Make / n8n', 'Hiểu biết cơ bản về API REST, JSON', 'Kỹ năng giao tiếp và tư vấn khách hàng tốt', 'Khả năng làm việc độc lập và quản lý nhiều dự án'],
      benefits: ['Thu nhập hấp dẫn + hoa hồng dự án', 'Đào tạo chuyên sâu về AI và automation', 'Môi trường năng động, học hỏi nhanh', 'Team building hàng quý', 'Cơ hội thăng tiến rõ ràng'],
    },
    {
      id: 2, title: 'Fullstack Developer', dept: 'Kỹ Thuật', type: 'Toàn Thời Gian', loc: 'Remote', level: 'Mid', salary: '20–35 triệu VNĐ',
      skills: ['React', 'Node.js', 'PostgreSQL', 'Docker', 'TypeScript'],
      desc: 'Phát triển các sản phẩm web AI-powered, từ dashboard phân tích đến công cụ quản lý quy trình tự động cho khách hàng.',
      responsibilities: ['Phát triển giao diện người dùng với React/Next.js', 'Xây dựng API backend với Node.js/Express', 'Thiết kế và quản lý database PostgreSQL', 'Tích hợp dịch vụ AI và third-party API', 'Đảm bảo hiệu suất và bảo mật ứng dụng'],
      requirements: ['3+ năm kinh nghiệm fullstack (React + Node.js)', 'Thành thạo TypeScript', 'Kinh nghiệm với Docker và CI/CD', 'Hiểu biết về bảo mật ứng dụng web', 'Portfolio dự án thực tế'],
      benefits: ['100% remote working', 'Thiết bị làm việc được cung cấp', 'Lịch làm việc linh hoạt', 'Văn hóa công ty cởi mở, minh bạch', 'Stock option sau 1 năm'],
    },
    {
      id: 3, title: 'Chuyên Viên Marketing & Growth', dept: 'Marketing', type: 'Toàn Thời Gian', loc: 'TP.HCM', level: 'Mid', salary: '15–22 triệu VNĐ',
      skills: ['Content', 'SEO', 'Meta Ads', 'Analytics', 'AI Tools'],
      desc: 'Xây dựng và thực thi chiến lược marketing tổng thể, tận dụng AI tools để tăng tốc tăng trưởng thương hiệu và khách hàng.',
      responsibilities: ['Lập kế hoạch và triển khai chiến dịch digital marketing', 'Quản lý nội dung website, blog và mạng xã hội', 'Chạy và tối ưu quảng cáo Facebook, Google', 'Phân tích dữ liệu và báo cáo KPI hàng tuần', 'Phối hợp với team sales để generate leads chất lượng'],
      requirements: ['2+ năm kinh nghiệm digital marketing', 'Hiểu biết về SEO, SEM và content marketing', 'Kinh nghiệm dùng AI tools (ChatGPT, Midjourney,...)', 'Kỹ năng phân tích dữ liệu với Google Analytics', 'Tư duy sáng tạo và chủ động trong công việc'],
      benefits: ['Budget thử nghiệm chiến dịch riêng', 'Hoa hồng theo kết quả lead', 'Đào tạo AI marketing tools', 'Môi trường sáng tạo, không giới hạn ý tưởng', 'Review lương 6 tháng/lần'],
    },
  ];

  const getActiveJobs = () => {
    if (publicPositions.length > 0) return publicPositions;

    const backendJobs = mockJobPostings.filter((j) => j.status === 'ĐANG_MỞ');
    if (backendJobs.length > 0) return backendJobs;

    return jobs.map((j, index) => ({
      jobId: 9000 + index,
      title: j.title,
      departmentName: j.dept,
      departmentId: null,
      workType: 'FULL_TIME',
      location: j.loc,
      salaryMin: null,
      salaryMax: null,
      description: j.desc,
      requirements: Array.isArray(j.requirements) ? j.requirements : [],
      benefits: Array.isArray(j.benefits) ? j.benefits : [],
      status: 'ĐANG_MỞ',
    }));
  };

  const renderJobs = () => {
    const grid = document.getElementById('jobs-grid');
    if (!grid) return;
    grid.innerHTML = '';
    
    // Only show open jobs
    const activeJobs = getActiveJobs();
    
    activeJobs.forEach(j => {
      const typeLabel = j.workType === 'FULL_TIME' ? 'Toàn Thời Gian' : (j.workType === 'HYBRID' ? 'Hybrid' : 'Thực tập');
      const salaryLabel = j.salaryMin ? `${(j.salaryMin/1000000).toFixed(0)}tr – ${(j.salaryMax/1000000).toFixed(0)}tr` : 'Thỏa thuận';
      const card = document.createElement('div');
      card.className = 'job-card reveal';
      card.innerHTML = `
        <div class="job-top">
          <div>
            <div class="job-title">${j.title}</div>
            <div class="job-dept">${j.departmentName}</div>
          </div>
          <span class="job-badge ${j.workType === 'FULL_TIME' ? 'full' : ''}">${typeLabel}</span>
        </div>
        <div class="job-meta">
          <span><svg viewBox="0 0 24 24"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/></svg>${j.location}</span>
          <span><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="2"/><path d="M7 20v-2a4 4 0 014-4h2a4 4 0 014 4v2"/></svg>Chuyên viên</span>
          <span><svg viewBox="0 0 24 24"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg>${salaryLabel}</span>
        </div>
        <div class="job-desc">${j.description}</div>
        <div class="job-skills">${j.requirements.slice(0, 3).map(s => `<span class="jskill">${s}</span>`).join('')}</div>
        <div class="job-actions">
          <button class="btn-jd" data-job-id="${j.jobId}">Xem JD Chi Tiết</button>
          <button class="btn-apply" data-job-title="${j.title}">Ứng Tuyển Ngay →</button>
        </div>`;
      grid.appendChild(card);
      obs.observe(card);
    });

    grid.querySelectorAll('.btn-jd').forEach(btn => {
      btn.addEventListener('click', () => openJD(parseInt(btn.dataset.jobId)));
    });
    grid.querySelectorAll('.btn-apply').forEach(btn => {
      btn.addEventListener('click', () => applyJob(btn.dataset.jobTitle));
    });
  };

  const openJD = (id) => {
    const j = getActiveJobs().find(x => x.jobId === id);
    if (!j) return;
    const content = document.getElementById('jd-content');
    if (content) {
      const typeLabel = j.workType === 'FULL_TIME' ? 'Toàn Thời Gian' : (j.workType === 'HYBRID' ? 'Hybrid' : 'Thực tập');
      const salaryLabel = j.salaryMin ? `${(j.salaryMin/1000000).toFixed(0)}tr – ${(j.salaryMax/1000000).toFixed(0)}tr` : 'Thỏa thuận';
      
      content.innerHTML = `
        <div class="jd-dept">${j.departmentName} · ${typeLabel}</div>
        <div class="jd-title">${j.title}</div>
        <div class="jd-meta">
          <span><svg viewBox="0 0 24 24"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/></svg>${j.location}</span>
          <span><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>Chuyên viên</span>
          <span><svg viewBox="0 0 24 24"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg>${salaryLabel}</span>
        </div>
        <div class="jd-section-title">Mô Tả Công Việc</div>
        <p style="font-size:14px;color:#a0a0a0;line-height:1.72;margin-bottom:4px">${j.description}</p>
        <div class="jd-section-title">Yêu Cầu</div>
        <ul class="jd-list">${j.requirements.map(r => `<li>${r}</li>`).join('')}</ul>
        <div class="jd-section-title">Quyền Lợi</div>
        <ul class="jd-list">${j.benefits.map(b => `<li>${b}</li>`).join('')}</ul>
        <div style="margin-top:18px;display:flex;gap:8px;flex-wrap:wrap">${j.requirements.slice(0, 5).map(s => `<span class="chip">${s}</span>`).join('')}</div>`;
    }
    document.getElementById('jd-modal')?.classList.add('open');
    document.body.style.overflow = 'hidden';
  };

  const closeJD = () => {
    document.getElementById('jd-modal')?.classList.remove('open');
    document.body.style.overflow = '';
  };

  document.getElementById('jd-close-btn')?.addEventListener('click', closeJD);
  document.getElementById('jd-apply-btn')?.addEventListener('click', () => { closeJD(); scrollToForm(); });
  const jm = document.getElementById('jd-modal');
  if (jm) jm.addEventListener('click', (e) => { if (e.target === jm) closeJD(); });

  const scrollToForm = () => {
    document.getElementById('apply-form-wrap')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
  };

  const applyJob = (title) => {
    scrollToForm();
    setTimeout(() => {
      const sel = document.getElementById('f-position');
      if (sel) {
        for (let i = 0; i < sel.options.length; i++) {
          // Compare with title from dataset
          if (sel.options[i].getAttribute('data-title') === title) { 
            sel.selectedIndex = i; 
            break; 
          }
        }
      }
    }, 600);
  };

  const populatePositionDropdown = () => {
    const posSelect = document.getElementById('f-position');
    if (!posSelect) return;

    posSelect.innerHTML = '<option value="">-- Chọn vị trí --</option>';
    getActiveJobs().forEach(j => {
      const opt = document.createElement('option');
      opt.value = String(j.jobId || j.positionId || j.title || '');
      opt.textContent = `${j.title}`;
      opt.setAttribute('data-dept-id', j.departmentId);
      opt.setAttribute('data-dept-name', j.departmentName);
      opt.setAttribute('data-title', j.title);
      if (j.positionId) {
        opt.setAttribute('data-position-id', j.positionId);
      } else if (typeof j.jobId === 'number' && j.jobId > 0) {
        opt.setAttribute('data-recruitment-id', String(j.jobId));
      }
      posSelect.appendChild(opt);
    });
  };

  renderJobs();
  populatePositionDropdown();

  // Refresh UI once backend/public position data is loaded.
  void Promise.allSettled([mockDataReady, publicPositionsReady]).then(() => {
    renderJobs();
    populatePositionDropdown();
  });

  /* ── POPULATE POSITION DROPDOWN FROM jobPostings.json ── */
  const posSelect = document.getElementById('f-position');
  if (posSelect && posSelect.options.length === 0) {
    populatePositionDropdown();
  }

  /* ── FILE UPLOAD ── */
  const handleFileChange = (input, areaId, displayId) => {
    const area = document.getElementById(areaId);
    const display = document.getElementById(displayId);
    if (input.files && input.files[0]) {
      area?.classList.add('has-file');
      if (display) {
        display.textContent = '✓ ' + input.files[0].name;
        display.style.display = 'block';
      }
    }
  };

  const cvInput = document.getElementById('f-cv');
  if (cvInput) cvInput.addEventListener('change', () => handleFileChange(cvInput, 'cv-upload-area', 'cv-name-display'));


  /* ── TOAST NOTIFICATION ── */
  const showToast = (msg, type = 'error') => {
    let container = document.getElementById('toast-container');
    if (!container) {
      container = document.createElement('div');
      container.id = 'toast-container';
      document.body.appendChild(container);
    }
    const t = document.createElement('div');
    t.className = `toast ${type}`;
    t.innerHTML = `<svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg><span class="toast-msg">${msg}</span>`;
    container.appendChild(t);
    setTimeout(() => t.style.opacity = '0', 3000);
    setTimeout(() => t.remove(), 3400);
  };

  /* ── SUBMIT FORM ── */
  const submitBtn = document.getElementById('submit-btn');
  if (submitBtn) {
    submitBtn.addEventListener('click', () => {
      const name = document.getElementById('f-name')?.value.trim();
      const email = document.getElementById('f-email')?.value.trim();
      const pos = document.getElementById('f-position')?.value;
      const cv = document.getElementById('f-cv')?.files[0];
      
      if (!name) { showToast('Vui lòng nhập họ và tên.'); return; }
      if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) { showToast('Vui lòng nhập email hợp lệ.'); return; }
      if (!pos) { showToast('Vui lòng chọn vị trí ứng tuyển.'); return; }
      if (!cv) { showToast('Vui lòng tải lên CV của bạn.'); return; }
      
      submitBtn.disabled = true;
      submitBtn.innerHTML = `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="animation:spin 1s linear infinite"><path d="M21 12a9 9 0 11-6.219-8.56"/></svg> Đang Gửi...`;
      
      setTimeout(() => {
        // Forward to recruitment store (backend-first)
        const selectedOption = posSelect?.options[posSelect.selectedIndex];
        const deptId = selectedOption ? parseInt(selectedOption.getAttribute('data-dept-id')) : null;
        const deptName = selectedOption ? selectedOption.getAttribute('data-dept-name') : '';
        const jobTitle = selectedOption ? selectedOption.getAttribute('data-title') : pos;
        const recruitmentJobIdRaw = selectedOption ? selectedOption.getAttribute('data-recruitment-id') : null;
        const jobId = recruitmentJobIdRaw ? parseInt(recruitmentJobIdRaw) : null;
        const phone = document.getElementById('f-phone')?.value.trim() || '';
        const linkedin = document.getElementById('f-linkedin')?.value.trim() || '';
        const salary = document.getElementById('f-salary')?.value.trim() || '';
        const cover = document.getElementById('f-cover')?.value.trim() || '';

        submitApplication({
           jobId: jobId,
           jobTitle: jobTitle,
           departmentId: deptId,
           departmentName: deptName,
           fullName: name,
           email: email,
           phone: phone,
           cvUrl: linkedin || '',
           cvFile: cv,
           coverLetter: cover,
           notes: salary ? `Mức lương kỳ vọng: ${salary}` : '',
        });

        const body = document.getElementById('form-body');
        const success = document.getElementById('form-success');
        if (body) body.style.display = 'none';
        if (success) success.style.display = 'block';
      }, 1800);
    });
  }
});

onUnmounted(() => {
  document.body.classList.remove('landing-v2-active');
  const container = document.getElementById('toast-container');
  if (container) container.remove();
  document.body.style.overflow = '';
  // Clean up injected assets
  document.getElementById('google-font-be-vietnam')?.remove();
  document.getElementById('landing-v2-style')?.remove();
});
</script>

<style scoped>
/* Scoped styles are minimal as most styles are in the external CSS */
.landing-page-v2 {
  position: relative;
  z-index: 10;
}
</style>
