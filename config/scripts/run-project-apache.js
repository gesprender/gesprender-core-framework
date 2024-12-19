import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);
const folderBackoffice = "Backoffice";
const backofficeDir = path.join(__dirname, folderBackoffice);

if (!fs.existsSync(backofficeDir)) {
    console.error(`Project ${folderBackoffice} not found`);
    process.exit(1); 
}


const filesToCopy = [
    { src: path.join(__dirname, 'Backoffice/configuration/Apache', '.htaccess'), dst: path.join(__dirname, '.htaccess') },
];

const copyFile = (src, dst) => {
    try {
        fs.copyFileSync(src, dst);
        console.log(`[ Ok ]`);
    } catch (err) {
        console.error(`[ Error ] - ${src} to ${dst}:`, err);
    }
}

filesToCopy.forEach(file => copyFile(file.src, file.dst));