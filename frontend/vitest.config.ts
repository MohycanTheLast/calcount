import { fileURLToPath } from 'node:url'
import { defineConfig } from 'vitest/config'

// Testy čistých funkcí (app/utils) – bez Nuxtu a prohlížeče
export default defineConfig({
    resolve: {
        alias: {
            '~': fileURLToPath(new URL('./app', import.meta.url)),
        },
    },
    test: {
        include: ['tests/**/*.test.ts'],
        environment: 'node',
    },
})
