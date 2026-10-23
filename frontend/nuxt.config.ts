// https://nuxt.com/docs/api/configuration/nuxt-config
import tailwindcss from "@tailwindcss/vite";

export default defineNuxtConfig({
    compatibilityDate: "2025-07-15",
    devtools: { enabled: true },
    css: ['~/assets/css/main.css'],
    // Komponenty se auto-importují i z features/ (např. features/diary/DiaryDayCards.vue → <DiaryDayCards>)
    components: [
        { path: '~/components', pathPrefix: false },
        { path: '~/features', pathPrefix: false, extensions: ['vue'] },
    ],
    vite: {
        plugins: [
            tailwindcss(),
        ],
    },
    ssr: false,        // čisté SPA
    runtimeConfig: {
        public: {
            // Adresa backendu (api/client.ts): '' = stejný origin; pro nativní aplikaci NUXT_PUBLIC_API_BASE při buildu
            apiBase: '',
        },
    },
    nitro: {
        preset: 'static',
        devProxy: {
            '/api': {
                target: 'http://localhost:80/api',
                changeOrigin: true
            },
        },
    },
    app: {
        // Plynulý přechod mezi obrazovkami; detail dne má vlastní slide (middleware/transition.global.ts)
        pageTransition: { name: 'cc-fade', mode: 'out-in' },
        head: {
            viewport: 'width=device-width, initial-scale=1, viewport-fit=cover',
            link: [
                { rel: 'manifest', href: '/manifest.webmanifest' },
                { rel: 'apple-touch-icon', href: '/icon-180.png' }
            ],
            meta: [
                { name: 'mobile-web-app-capable', content: 'yes' },
                { name: 'apple-mobile-web-app-title', content: 'CalCount' },
                { name: 'apple-mobile-web-app-status-bar-style', content: 'default' },

                { name: 'theme-color', media: '(prefers-color-scheme: light)', content: '#ffffff' },
                { name: 'theme-color', media: '(prefers-color-scheme: dark)', content: '#0b1220' }
            ],
            script: [
                // Aplikuje uložené téma ještě před načtením JS, aby při startu neproblikla světlá verze.
                // Stejná logika jako applyTheme() v useUserSettings – výchozí (nic uloženo) je tmavé.
                {
                    innerHTML: "try{var m=localStorage.getItem('cc_theme');if(m!=='light'&&(m!=='system'||window.matchMedia('(prefers-color-scheme: dark)').matches))document.documentElement.classList.add('dark')}catch(e){document.documentElement.classList.add('dark')}",
                },
            ],
        }
    },
});