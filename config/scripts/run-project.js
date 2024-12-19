import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

if (!fs.existsSync('./Backoffice')) {
    console.error(`Backoffice not found`);
    process.exit(1); 
}


const filesToCopy = [
    { src: path.join('./Backoffice/configuration/Docker', 'Dockerfile'), dst: path.join('./', 'Dockerfile') },
    { src: path.join('./Backoffice/configuration/', '.env.example'), dst: path.join('./', '.env') },
    { src: path.join('./Backoffice/configuration/Docker', 'docker-compose.yml'), dst: path.join('./', 'docker-compose.yml') },
    { src: path.join('./Backoffice/configuration/Docker', '.dockerignore'), dst: path.join('./', '.dockerignore') },
];

const filesToCopyInDockerFolder = [
    { src: path.join('./Backoffice/configuration/Docker', 'nginx.conf'), dst: path.join(`./Docker`, 'nginx.conf') }
];

const copyFile = (src, dst) => {
    try {
        fs.copyFileSync(src, dst);
        console.log(`[ Ok ] - ${dst}`);
    } catch (err) {
        console.error(`[ Error ] - ${src} to ${dst}:`, err);
    }
}

filesToCopy.forEach(file => copyFile(file.src, file.dst));
filesToCopyInDockerFolder.forEach(file => copyFile(file.src, file.dst));
