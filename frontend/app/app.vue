<template>
    <div>
        <NuxtRouteAnnouncer/>
        <div v-if="authChecking" class="cc-page flex items-center justify-center min-h-screen">
            <div class="flex flex-col items-center gap-4">
                <div class="cc-spinner"></div>
                <p class="text-sm cc-subtle">Načítám...</p>
            </div>
        </div>
        <NuxtPage v-else/>
    </div>
</template>
<script setup lang="ts">
// Root shell aplikace – sem se nedává konkrétní obsah typu <Main />.
// Obsah se rendruje podle rout z /pages přes <NuxtPage />.

const { isAuthenticated, loadFromStorage, hasValidAccessToken, refreshAccessToken } = useAuth()
const { fetchFromApi } = useUserSettings()
const authChecking = ref(true)

onMounted(async () => {
    loadFromStorage()

    // Pokud je uživatel přihlášen, zkontroluj platnost tokenu
    if (isAuthenticated.value && !hasValidAccessToken()) {
        try {
            await refreshAccessToken()
        } catch {
            // Token refresh selhal, necháme to na middleware
        }
    }

    // Ověření session na serveru (lokálně platný token může server odmítnout, např. po změně JWT klíčů)
    // a zároveň synchronizace nastavení (hlavně tématu). Při neplatné session apiFetch uživatele odhlásí,
    // síťové chyby ignorujeme – téma je aplikované z localStorage.
    if (isAuthenticated.value) {
        await fetchFromApi().catch(() => {})
    }

    // Po kontrole autentizace přesměruj podle stavu
    const path = window.location.pathname
    if (isAuthenticated.value && path === '/') {
        await navigateTo('/app')
    } else if (!isAuthenticated.value && path !== '/') {
        await navigateTo('/')
    }

    authChecking.value = false
})
</script>
<style>
.cc-spinner {
    width: 40px;
    height: 40px;
    border: 3px solid rgba(var(--cc-border), 0.3);
    border-top-color: rgb(var(--cc-primary));
    border-radius: 50%;
    animation: cc-spin 0.8s linear infinite;
}

@keyframes cc-spin {
    to { transform: rotate(360deg); }
}
</style>