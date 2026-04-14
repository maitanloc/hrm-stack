const fs = require('fs');
const path = require('path');

const vueFile = 'src/View/LandingPage.vue';
const publicDir = 'public';

if (!fs.existsSync(vueFile)) {
    console.error('LandingPage.vue not found');
    process.exit(1);
}

const content = fs.readFileSync(vueFile, 'utf8');
const regex = /["'](\/landing\/[^"']+\.(png|jpg|gif|ico))["']/gi;
let match;
const foundPaths = new Set();

while ((match = regex.exec(content)) !== null) {
    foundPaths.add(match[1]);
}

foundPaths.forEach(assetPath => {
    const fullPath = path.join(process.cwd(), publicDir, assetPath);
    const dir = path.dirname(fullPath);

    if (!fs.existsSync(fullPath)) {
        console.log(`Creating dummy for missing asset: ${assetPath}`);
        if (!fs.existsSync(dir)) {
            fs.mkdirSync(dir, { recursive: true });
        }
        // Create a 1x1 transparent PNG if it ends with .png, else just an empty file
        if (assetPath.endsWith('.png')) {
            const transparentPng = Buffer.from('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=', 'base64');
            fs.writeFileSync(fullPath, transparentPng);
        } else {
            fs.writeFileSync(fullPath, '');
        }
    }
});

console.log('Finished checking/creating assets.');
