const fs = require('fs');
const files = [
    'src/View/admin/QuanLy/QuanLyNhanSu.vue',
    'src/View/admin/QuanLy/QuanLyPhongban.vue',
    'src/View/admin/QuanLy/QuanLyHopDong.vue',
    'src/View/admin/QuanLy/QuanLyChucDanh.vue'
];

files.forEach(file => {
    let content = fs.readFileSync(file, 'utf8');
    
    // remove some modifiers
    content = content.replace(/font-black/g, 'font-semibold');
    content = content.replace(/\bitalic\b/g, '');
    content = content.replace(/\buppercase\b/g, '');
    content = content.replace(/\btracking-[a-zA-Z0-9\[\]\.\-]+/g, '');
    
    // clean up consecutive spaces
    content = content.replace(/ +(?= )/g, '');
    content = content.replace(/class=" /g, 'class="');
    
    fs.writeFileSync(file, content);
    console.log('Updated ' + file);
});
