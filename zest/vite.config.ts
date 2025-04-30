import { defineConfig } from 'vite';
import inject from '@rollup/plugin-inject';
import path from 'path';

// https://vitejs.dev/config/
export default defineConfig({
    plugins: [
        inject({
            $: 'jquery',
            jQuery: 'jquery',
            include: '*.js'
        }),
    ],
    resolve: {
        alias: {
            '@': path.resolve(__dirname, './src'),
        },
    },

    build: {
        outDir: '../src/Glitch/Renderer/assets',
        assetsDir: '.',
        emptyOutDir: true,
        copyPublicDir: false,
        cssCodeSplit: false,
        manifest: false,
        rollupOptions: {
            input: 'src/main.js',
            output: {
                entryFileNames: '[name].js',
                chunkFileNames: '[name].js',
                assetFileNames: '[name].[ext]',
            },
        }
    },
    server: {
        host: 'localhost',
        port: 5531,
        strictPort: true,
        cors: {
            origin: "*"
        }
    },

    optimizeDeps: {
        include: ["jquery"]
    }
})
