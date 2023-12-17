import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';

export default defineConfig({
  server: {
    port: 4444
  },
  plugins: [
    react({
      exclude: /\.stories\.(t|j)sx?$/,
      include: '**/*.tsx'
    })
  ],
});
