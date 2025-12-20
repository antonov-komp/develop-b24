import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';
import { resolve } from 'path';

export default defineConfig({
  plugins: [vue()],
  resolve: {
    alias: {
      '@': resolve(__dirname, 'src'),
    },
  },
  base: '/APP-B24/public/dist/', // Базовый путь для production
  build: {
    outDir: '../public/dist',
    assetsDir: 'assets',
    emptyOutDir: true,
    rollupOptions: {
      input: {
        main: resolve(__dirname, 'index.html'),
      },
      output: {
        // Именование файлов для кеширования
        entryFileNames: 'assets/[name]-[hash].js',
        chunkFileNames: 'assets/[name]-[hash].js',
        assetFileNames: 'assets/[name]-[hash].[ext]',
      },
    },
    // Минификация для production
    minify: 'terser',
    terserOptions: {
      compress: {
        drop_console: false, // Временно оставляем console.log для отладки
      },
    },
    // Размер предупреждений
    chunkSizeWarningLimit: 1000,
  },
  server: {
    port: 5173,
    host: '0.0.0.0', // Доступ с других устройств
    proxy: {
      '/api': {
        target: 'http://localhost',
        changeOrigin: true,
        secure: false,
        rewrite: (path) => path.replace(/^\/api/, '/APP-B24/api'),
      },
    },
    // Hot Module Replacement
    hmr: {
      overlay: true,
    },
  },
  // Оптимизация зависимостей
  optimizeDeps: {
    include: ['vue', 'pinia', 'axios'],
  },
});

