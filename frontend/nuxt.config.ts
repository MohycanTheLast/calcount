// https://nuxt.com/docs/api/configuration/nuxt-config
import tailwindcss from "@tailwindcss/vite";
import packageJson from "./package.json";

// Verze aplikace – jediný zdroj je package.json (zobrazuje se v Nastavení → O aplikaci, viz docs/operations.md)
const { version } = packageJson;

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
            // @tailwindcss/vite a Nuxt odkazují na jiné typové definice Rollupu (PluginContextMeta),
            // za běhu je plugin v pořádku – přetypování jen umlčí nesoulad typů v `nuxt typecheck`.
            tailwindcss() as any,
        ],
    },
    ssr: false,        // čisté SPA
    runtimeConfig: {
        public: {
            // Adresa backendu (api/client.ts): '' = stejný origin; pro nativní aplikaci NUXT_PUBLIC_API_BASE při buildu
            apiBase: '',
            appVersion: version,
            // Datum sestavení – podle něj jde poznat, jestli telefon už načetl nový build
            buildDate: new Date().toISOString(),
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
            title: 'Kalorický deník',
            viewport: 'width=device-width, initial-scale=1, viewport-fit=cover',
            link: [
                { rel: 'manifest', href: '/manifest.webmanifest' },
                { rel: 'apple-touch-icon', href: '/icon-180.png' }
            ],
            meta: [
                { name: 'mobile-web-app-capable', content: 'yes' },
                { name: 'apple-mobile-web-app-title', content: 'Kalorický deník' },
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