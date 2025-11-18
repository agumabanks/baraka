import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'
import { fileURLToPath } from 'url'
import { dirname, resolve } from 'path'

const __filename = fileURLToPath(import.meta.url)
const __dirname = dirname(__filename)

/**
 * Minimal Vite configuration that still keeps a couple of hardening tweaks
 * (removing console noise in production builds) while staying within the
 * dependencies available in package.json.
 */
export default defineConfig(() => ({
  plugins: [
    react(),
  ],
  build: {
    target: 'esnext',
    sourcemap: false,
    chunkSizeWarningLimit: 900,
  },
  resolve: {
    alias: {
      '@': resolve(__dirname, 'src'),
    },
  },
}))
