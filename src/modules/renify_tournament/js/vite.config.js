import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'

// https://vitejs.dev/config/
export default defineConfig({
  plugins: [react()],
  server: {
    port: 3000, // Optional: fixed port for easy access
    open: true  // Optional: opens browser on start
  },
  build: {
      rollupOptions: {
        output: {
          // Fixes the main entry JS file name
          entryFileNames: `assets/[name].js`,
          // Fixes names for dynamically imported chunks
          chunkFileNames: `assets/[name].js`,
          // Fixes names for CSS and other assets (images, etc)
          assetFileNames: `assets/[name].[ext]`
        },
      },
    },
})
