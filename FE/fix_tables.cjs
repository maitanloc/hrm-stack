const fs = require('fs');
const path = require('path');

function walkDir(dir, callback) {
  fs.readdirSync(dir).forEach(f => {
    let dirPath = path.join(dir, f);
    let isDirectory = fs.statSync(dirPath).isDirectory();
    if (isDirectory) {
        walkDir(dirPath, callback);
    } else {
        if (dirPath.endsWith('.vue')) {
            callback(dirPath);
        }
    }
  });
}

const targetDir = 'src/View/admin';

let updatedCount = 0;

walkDir(targetDir, (filePath) => {
    let content = fs.readFileSync(filePath, 'utf8');
    let originalContent = content;
    
    // Process td and th tags
    content = content.replace(/<(td|th)([^>]*?)>/g, (match, tag, rest) => {
        // Exclude custom elements that might include 'td' or 'th' in name
        // The regex <(td|th)([^>]*?)> already enforces it's exactly td or th starting with < and followed by space or >
        if (!/^\s*$/.test(rest) && !/^\s+/.test(rest)) {
            return match; // Not a valid attribute string (e.g. <td-something>)
        }

        if (!/class=['"]/.test(rest)) {
            return `<${tag} class="whitespace-nowrap"${rest}>`;
        }
        
        return match.replace(/class=(['"])(.*?)\1/, (match_class, quote, classes) => {
            if (!classes.includes('whitespace-nowrap')) {
                return `class=${quote}whitespace-nowrap ${classes}${quote}`;
            }
            return match_class;
        });
    });

    // Process table tags
    content = content.replace(/<table([^>]*?)>/g, (match, rest) => {
        if (!/^\s*$/.test(rest) && !/^\s+/.test(rest)) {
            return match; 
        }

        if (!/class=['"]/.test(rest)) {
            return `<table class="min-w-max w-full text-left border-separate border-spacing-0"${rest}>`;
        }
        
        return match.replace(/class=(['"])(.*?)\1/, (match_class, quote, classes) => {
            let newClasses = classes;
            if (!newClasses.includes('min-w-max')) {
                newClasses = `min-w-max ${newClasses}`;
            }
            if (newClasses !== classes) {
                return `class=${quote}${newClasses}${quote}`;
            }
            return match_class;
        });
    });
    
    // Add custom scrollbar styling if not present
    if (content !== originalContent && !content.includes('.custom-scrollbar')) {
        const scrollbarStyle = `
<style scoped>
.custom-scrollbar::-webkit-scrollbar {
  height: 6px;
  width: 6px;
}
.custom-scrollbar::-webkit-scrollbar-track {
  background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
  background: var(--sys-border-subtle, #e5e7eb);
  border-radius: 10px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
  background: var(--sys-brand-solid, #4f46e5);
}
</style>
`;
        if (content.includes('</style>')) {
            content = content.replace('</style>', `.custom-scrollbar::-webkit-scrollbar { height: 6px; width: 6px; } .custom-scrollbar::-webkit-scrollbar-track { background: transparent; } .custom-scrollbar::-webkit-scrollbar-thumb { background: var(--sys-border-subtle, #e5e7eb); border-radius: 10px; } .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: var(--sys-brand-solid, #4f46e5); }\n</style>`);
        } else {
            content += scrollbarStyle;
        }
        
        // ensure wrapper has custom-scrollbar and overflow-x-auto
        content = content.replace(/class=(['"])(.*?)overflow-x-auto(.*?)\1/g, (match, quote, p1, p2) => {
            if (!match.includes('custom-scrollbar')) {
                return `class=${quote}${p1}overflow-x-auto custom-scrollbar${p2}${quote}`;
            }
            return match;
        });
    }

    if (content !== originalContent) {
        fs.writeFileSync(filePath, content);
        console.log('Updated ' + filePath);
        updatedCount++;
    }
});

console.log(`Finished table updates on ${updatedCount} files.`);
