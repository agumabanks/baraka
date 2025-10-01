import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'

// https://vite.dev/config/
export default defineConfig({
  base: '/react-dashboard/',
  plugins: [react()],
  build: {
    outDir: '../public/react-dashboard'
  }
})
