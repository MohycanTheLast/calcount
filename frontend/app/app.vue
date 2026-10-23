<template>
    <div>
        <NuxtRouteAnnouncer/>
        <div v-if="authChecking" class="cc-page flex items-center justify-center min-h-screen">
            <div class="flex flex-col items-center gap-4">
                <div class="cc-spinner"></div>
                <p class="text-sm cc-subtle">Načítám...</p>
            </div>
        </div>
        <!-- Vlastní obal: přechod stránek (out-in) vkládá novou stránku až po odchodu staré.
             Bez stabilního rodiče by Vue při současné změně okolí spadlo na insertBefore. -->
        <div v-else>
            <!-- Tab bar je v layouts/tabs.vue; stránky si layout volí přes definePageMeta({ layout }) -->
            <NuxtLayout>
                <NuxtPage/>
            </NuxtLayout>
        </div>
    </div>
</template>
<script setup lang="ts">
// Root shell aplikace – sem se nedává konkrétní obsah typu <Main />.
// Obsah se rendruje podle rout z /pages přes <NuxtPage />.

const { isAuthenticated, loadFromStorage, hasValidAccessToken, refreshAccessToken } = useAuth()
const { fetchFromApi } = useUserSettings()
const authChecking = ref(true)

// Stránky dostupné bez přihlášení: přihlášení, registrace, podmínky užívání, potvrzení e-mailu
const PUBLIC_PATHS = ['/', '/register', '/terms', '/verify-email']

// Nejdéle tak dlouho čekáme na server, než aplikaci ukážeme (pomalá síť / zaseknutý request)
const AUTH_CHECK_TIMEOUT_MS = 6000

const withTimeout = (promise: Promise<unknown>) =>
    Promise.race([promise.catch(() => {}), new Promise((resolve) => setTimeout(resolve, AUTH_CHECK_TIMEOUT_MS))])

onMounted(async () => {
    try {
        loadFromStorage()

        // Pokud je uživatel přihlášen, zkontroluj platnost tokenu
        if (isAuthenticated.value && !hasValidAccessToken()) {
            await withTimeout(refreshAccessToken())
        }

        // Ověření session na serveru (lokálně platný token může server odmítnout, např. po změně JWT klíčů)
        // a zároveň synchronizace nastavení (hlavně tématu). Při neplatné session apiFetch uživatele odhlásí,
        // síťové chyby ignorujeme – téma je aplikované z localStorage.
        if (isAuthenticated.value) {
            await withTimeout(fetchFromApi())
        }

        // Po kontrole autentizace přesměruj podle stavu
        // (Apache k adresářům z buildu přidává lomítko, např. /register → /register/)
        const path = window.location.pathname.replace(/(.)\/$/, '$1')
        if (isAuthenticated.value && (path === '/' || path === '/register')) {
            await navigateTo('/app')
        } else if (!isAuthenticated.value && !PUBLIC_PATHS.includes(path)) {
            await navigateTo('/')
        }
    } catch (e) {
        console.error('Kontrola přihlášení selhala', e)
    } finally {
        // Spinner nesmí zůstat viset, ať se stane cokoli
        authChecking.value = false
    }
})
</script>
<style>
.cc-spinner {
    width: 40px;
    height: 40px;
    border: 3px solid rgb(var(--cc-border) / 0.5);
    border-top-color: rgb(var(--cc-accent));
    border-radius: 50%;
    animation: cc-spin 0.8s linear infinite;
}

@keyframes cc-spin {
    to { transform: rotate(360deg); }
}
</style>