const fs = require('fs');
const path = require('path');

function walkDir(dir, callback) {
  fs.readdirSync(dir).forEach(f => {
    let dirPath = path.join(dir, f);
    let isDirectory = fs.statSync(dirPath).isDirectory();
    if (isDirectory) {
        walkDir(dirPath, callback);
    } else {
        callback(dirPath);
    }
  });
}

const targetDir = 'src/View/admin';

let updatedCount = 0;

walkDir(targetDir, (filePath) => {
    if (filePath.endsWith('.vue')) {
        let content = fs.readFileSync(filePath, 'utf8');
        let originalContent = content;
        
        // remove some modifiers
        content = content.replace(/font-black/g, 'font-semibold');
        content = content.replace(/\bitalic\b/g, '');
        content = content.replace(/\buppercase\b/g, '');
        content = content.replace(/\btracking-[a-zA-Z0-9\[\]\.\-]+/g, '');
        
        // clean up consecutive spaces
        content = content.replace(/ +(?= )/g, '');
        content = content.replace(/class=" /g, 'class="');
        content = content.replace(/class=' /g, "class='");
        
        if (content !== originalContent) {
            fs.writeFileSync(filePath, content);
            console.log('Updated ' + filePath);
            updatedCount++;
        }
    }
});

console.log(`Finished updating ${updatedCount} files.`);
