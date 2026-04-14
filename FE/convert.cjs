const fs = require('fs');

let html = fs.readFileSync('landing page/index.html', 'utf8');

const bodyMatch = html.match(/<body[^>]*>([\s\S]*?)<\/body>/i);
if(bodyMatch){
  let bodyContent = bodyMatch[1];
  
  // 1. Remove all script tags
  bodyContent = bodyContent.replace(/<script\b[^>]*>([\s\S]*?)<\/script>/gi, '');

  // 2. Asset Path Replacements
  bodyContent = bodyContent.replace(/(src|href)="images\//gi, '$1="/landing/images/');
  bodyContent = bodyContent.replace(/(src|href)="uploads\//gi, '$1="/landing/uploads/');
  bodyContent = bodyContent.replace(/url\('uploads\//gi, "url('/landing/uploads/");
  bodyContent = bodyContent.replace(/url\('images\//gi, "url('/landing/images/");

  // 3. Selective Translation
  const translations = [
    [/>Home</gi, '>Trang chủ<'],
    [/>Features/gi, '>Tính năng'],
    [/>The Agent/gi, '>Nhà tuyển dụng'],
    [/>Gallery</gi, '>Bộ sưu tập<'],
    [/>Testimonials</gi, '>Đánh giá<'],
    [/>Contact</gi, '>Liên hệ<'],
    [/>Social</gi, '>Mạng xã hội<'],
    [/Sell Your Property with Aven/gi, 'Xây dựng tương lai cùng AET'],
    [/View Gallery/gi, 'Xem bộ sưu tập'],
    [/Property Details/gi, 'Chi tiết cơ hội nghề nghiệp'],
    [/All Awesome Property Details/gi, 'Thông tin tuyển dụng'],
    [/Square Feet/gi, 'Môi trường làm việc'],
    [/Ideal for Family/gi, 'Phúc lợi hấp dẫn'],
    [/Garage/gi, 'Cơ sở hiện đại'],
    [/Bedrooms/gi, 'Số lượng tuyển'],
    [/Pool|Swimming Pool/gi, 'Dự án quốc tế'],
    [/Build in/gi, 'Thành lập từ'],
    [/Spacious and Large Garden/gi, 'Văn phòng làm việc sáng tạo'],
    [/With its Own Pool/gi, 'Thu nhập cạnh tranh'],
    [/In Forests- Fresh Clean Air/gi, 'Môi trường năng động'],
    [/Agent Details/gi, 'Thông tin người liên hệ'],
    [/Jenny Martines/gi, 'Trần Thanh Tâm'],
    [/Property Gallery/gi, 'Hoạt động văn hoá'],
    [/Happy Customers/gi, 'Cảm nhận từ thành viên'],
    [/Wonderful Support!/gi, 'Đào tạo chuyên sâu!'],
    [/Awesome Services!/gi, 'Lộ trình thăng tiến!'],
    [/Great & Talented Team!/gi, 'Đội ngũ tài năng!'],
    [/Contact Details/gi, 'Thông tin liên hệ'],
    [/Twitter Feed/gi, 'Tin tuyển dụng mới'],
    [/All Rights Reserved\./gi, 'Bản quyền thuộc về AET.'],
    [/Pricing/gi, 'Đãi ngộ'],
    [/About/gi, 'Giới thiệu'],
    [/Blog/gi, 'Tin tức'],
    [/Faq/gi, 'Hỏi đáp'],
    [/QUICK APPOINTMENT/gi, 'ỨNG TUYỂN NHANH'],
    [/Get an Appointment Today/gi, 'Trở thành một phần của AET ngay hôm nay'],
    [/Get an Appointment/gi, 'Gửi hồ sơ ngay'],
    [/Get Appointment/gi, 'Gửi hồ sơ'],
    [/placeholder="First Name"/gi, 'placeholder="Họ"'],
    [/placeholder="Last Name"/gi, 'placeholder="Tên"'],
    [/placeholder="Your Email"/gi, 'placeholder="Gmail"'],
    [/placeholder="Your Phone"/gi, 'placeholder="Số điện thoại"'],
    [/placeholder="Give us more details\.\./gi, 'placeholder="Để lại lời nhắn cho chúng tôi.."'],
  ];

  const genericTexts = [
    [/With Aven responsive landing page template, you can promote your all property & real estate projects[\s\S]*?\. /gi, 'Hệ thống quản trị nhân sự HRM chuyên nghiệp, giúp tối ưu hóa quy trình làm việc và phát triển nguồn nhân lực bền vững. '],
    [/Quisque eget nisl id nulla sagittis auctor quis id\. Aliquam quis vehicula enim, non aliquam risus\. Sed a tellus quis mi rhoncus dignissim\./gi, 'Chúng tôi luôn tìm kiếm những nhân tài khao khát khẳng định bản thân và đóng góp vào những dự án công nghệ mang tầm quốc tế.'],
    [/Aliquam sagittis ligula et sem lacinia, ut facilisis enim sollicitudin\. Proin nisi est, convallis nec purus vitae, iaculis posuere sapien\. Cum sociis natoque\./gi, 'Gia nhập đội ngũ AET, bạn sẽ được trải nghiệm một môi trường làm việc cởi mở, nơi mọi đóng góp đều được trân trọng và đền đáp xứng đáng.'],
    [/Duis at tellus at dui tincidunt scelerisque nec sed felis\. Suspendisse id dolor sed leo rutrum euismod\. Nullam vestibulum fermentum erat\. It nam auctor\./gi, 'Chúng tôi tin rằng con người chính là tài sản quý giá nhất. Vì vậy, AET luôn đầu tư vào sự phát triển cá nhân của mỗi thành viên qua các khóa đào tạo.'],
    [/Etiam materials ut mollis tellus, vel posuere nulla\. Etiam sit amet lacus vitae massa sodales aliquam at eget quam\. Integer ultricies et magna quis\./gi, 'Hãy cùng chúng tôi xây dựng những giải pháp công nghệ tiên phong, giúp thay đổi cách vận hành của các doanh nghiệp trong tương lai.'],
    [/Integer rutrum ligula eu dignissim laoreet\. Pellentesque venenatis nibh sed tellus faucibus bibendum\. Sed fermentum est vitae rhoncus molestie\. Cum sociis natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus\./gi, 'Tiền thân là một đội ngũ đam mê công nghệ, AET đã vươn mình trở thành đối tác tin cậy của hàng nghìn doanh nghiệp trong lĩnh vực quản trị nhân sự.'],
    [/They have got my project on time with the competition with a sed highly skilled, and experienced & professional team\./gi, 'Tôi thực sự ấn tượng với tốc độ làm việc và sự hỗ trợ nhiệt tình từ đội ngũ AET. Môi trường ở đây rất chuyên nghiệp và thân thiện.'],
    [/Explain to you how all this mistaken idea of denouncing pleasure and praising pain was born and I will give you completed\./gi, 'Làm việc tại đây giúp tôi học hỏi được rất nhiều từ những anh chị đi trước. Các dự án luôn đầy thử thách và giúp tôi tiến bộ mỗi ngày.'],
    [/The master-builder of human happines no one rejects, dislikes avoids pleasure itself, because it is very pursue pleasure\./gi, 'Chế độ đãi ngộ và lộ trình thăng tiến rõ ràng là điều thu hút tôi nhất. Tôi cảm thấy an tâm khi xây dựng sự nghiệp lâu dài tại AET.'],
  ];

  translations.forEach(([regex, replacement]) => {
    bodyContent = bodyContent.replace(regex, replacement);
  });
  genericTexts.forEach(([regex, replacement]) => {
    bodyContent = bodyContent.replace(regex, replacement);
  });

  // 4. Form Customization (For both contactform and contactform1)
  const customizeForm = (formHtml) => {
    let newInner = formHtml;
    // Remove unwanted selects/labels
    newInner = newInner.replace(/<div class="col-lg-6[^>]*>\s*<label[^>]*>(?:Select Time|Lựa chọn thời gian)<\/label>[\s\S]*?<\/div>/gi, '');
    newInner = newInner.replace(/<div class="col-lg-6[^>]*>\s*<label[^>]*>(?:What is max price\?|Mức giá tối đa)<\/label>[\s\S]*?<\/div>/gi, '');
    newInner = newInner.replace(/<div class="col-lg-12[^>]*>\s*<textarea[^>]*name="comments"[\s\S]*?<\/div>/gi, '');

    // Standardize fields: Full Name, Gmail, CV, JD
    newInner = newInner.replace(/<input[^>]*placeholder="Họ"[^>]*>/i, '<input type="text" name="full_name" id="full_name" class="form-control" placeholder="Họ và tên" />');
    newInner = newInner.replace(/<div class="col-lg-6 col-md-6 col-sm-6 col-xs-12">\s*<input[^>]*placeholder="Tên"[^>]*>\s*<\/div>/i, ''); // Remove Last Name div
    
    // Replace Gmail placeholder and add files
    newInner = newInner.replace(/(<div class="col-lg-6[^>]*>\s*<input[^>]*placeholder="Gmail"[^>]*>\s*<\/div>)/i, `$1
                           <div class="col-lg-6 col-md-6 col-sm-6 col-xs-12">
                              <label style="padding-left:15px; color:#666; font-size:12px;">Tải lên CV của bạn:</label>
                              <input type="file" name="cv_file" class="form-control" />
                           </div>
                           <div class="col-lg-6 col-md-6 col-sm-6 col-xs-12">
                              <label style="padding-left:15px; color:#666; font-size:12px;">Tải lên JD (nếu có):</label>
                              <input type="file" name="jd_file" class="form-control" />
                           </div>`);
    
    // Remove phone if still there
    newInner = newInner.replace(/<div class="col-lg-6 col-md-6 col-sm-6 col-xs-12">\s*<input[^>]*placeholder="Số điện thoại"[^>]*>\s*<\/div>/i, '');

    return newInner;
  };

  // Target both forms selectively
  bodyContent = bodyContent.replace(/(<form id="contactform1"[\s\S]*?>)([\s\S]*?)<\/form>/i, (match, openTag, inner) => {
    let newOpenTag = openTag.replace(/>$/, ' enctype="multipart/form-data">');
    return `${newOpenTag}${customizeForm(inner)}</form>`;
  });

  bodyContent = bodyContent.replace(/(<form id="contactform"[\s\S]*?>)([\s\S]*?)<\/form>/i, (match, openTag, inner) => {
    let newOpenTag = openTag.replace(/>$/, ' enctype="multipart/form-data">');
    return `${newOpenTag}${customizeForm(inner)}</form>`;
  });

  // 5. Router Link in Navigation
  bodyContent = bodyContent.replace(/<\/ul>\s*<\/div>\s*<\/div>\s*<\/nav>/i, `
      <li><router-link to="/login" class="btn btn-primary" style="margin-top:20px;margin-left:15px;color:#fff !important;background-color:#007bff;border-color:#007bff;">Truy cập Portal CV / Đăng nhập</router-link></li>
      </ul>
    </div>
  </div>
  </nav>`);

  // 6. Cleanup
  bodyContent = bodyContent.replace(/<(img|input|hr|br)([^>]*?)(?<!\/)>/gi, '<$1$2 />');
  bodyContent = bodyContent.replace(/href="index-real-estate\.html"/gi, 'href="/landing"');

  const vueFile = `
<template>
  <div class="landing-page-component">
    ${bodyContent}
  </div>
</template>

<script setup>
import { onMounted, onUnmounted } from 'vue';

const cssFiles = [
  '/landing/css/bootstrap.min.css',
  '/landing/style.css',
  '/landing/css/colors.css',
  '/landing/css/versions.css',
  '/landing/css/responsive.css',
  '/landing/css/custom.css',
];

const jsFiles = [
  '/landing/js/modernizer.js',
  '/landing/js/all.js',
  '/landing/js/custom.js',
  '/landing/js/portfolio.js',
  '/landing/js/hoverdir.js',
  'http://maps.googleapis.com/maps/api/js?sensor=false&libraries=places',
  '/landing/js/map.js'
];

let addedElements = [];

const loadScript = (src) => {
  return new Promise((resolve) => {
    if(document.querySelector(\`script[src="\${src}"]\`)) {
        resolve();
        return;
    }
    const script = document.createElement('script');
    script.src = src;
    script.dataset.landing = 'true';
    script.onload = resolve;
    script.onerror = () => { console.warn('Failed to load script', src); resolve(); };
    document.body.appendChild(script);
    addedElements.push(script);
  });
};

onMounted(async () => {
  document.body.classList.add("realestate_version");
  cssFiles.forEach(href => {
    if(!document.querySelector(\`link[href="\${href}"]\`)) {
        const link = document.createElement('link');
        link.rel = 'stylesheet';
        link.href = href;
        link.dataset.landing = 'true';
        document.head.appendChild(link);
        addedElements.push(link);
    }
  });
  for (const src of jsFiles) {
    await loadScript(src);
  }
});

onUnmounted(() => {
  document.body.classList.remove("realestate_version");
  addedElements.forEach(el => el.remove());
  document.body.style = '';
});
</script>

<style scoped>
.landing-page-component {
  text-align: left;
}
.landing-page-component label {
  color: #666;
  margin-top: 5px;
  display: block;
}
.landing-page-component .contact_form input {
  margin-bottom: 10px;
}
</style>
`;
  
  fs.writeFileSync('src/View/LandingPage.vue', vueFile);
  console.log('LandingPage.vue recreated successfully.');
}
