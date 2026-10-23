<template>
    <div>
        <NuxtRouteAnnouncer/>
        <div v-if="authChecking" class="cc-page flex items-center justify-center min-h-screen">
            <div class="flex flex-col items-center gap-4">
                <div class="cc-spinner"></div>
                <p class="text-sm cc-subtle">Načítám...</p>
            </div>
        </div>
        <template v-else>
            <!-- Vlastní obal: přechod stránek (out-in) vkládá novou stránku až po odchodu staré.
                 Bez obalu by se mezitím vložil/odebral tab bar vedle ní a Vue by spadlo na insertBefore. -->
            <div>
                <NuxtPage/>
            </div>

            <!-- Tab bar mimo stránku, aby při přechodu mezi záložkami neproblikával -->
            <Transition name="cc-fade">
                <AppTabBar v-if="route.meta.tabBar"/>
            </Transition>
        </template>
    </div>
</template>
<script setup lang="ts">
// Root shell aplikace – sem se nedává konkrétní obsah typu <Main />.
// Obsah se rendruje podle rout z /pages přes <NuxtPage />.

const route = useRoute()
const { isAuthenticated, loadFromStorage, hasValidAccessToken, refreshAccessToken } = useAuth()
const { fetchFromApi } = useUserSettings()
const authChecking = ref(true)

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
        const path = window.location.pathname
        if (isAuthenticated.value && path === '/') {
            await navigateTo('/app')
        } else if (!isAuthenticated.value && path !== '/') {
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