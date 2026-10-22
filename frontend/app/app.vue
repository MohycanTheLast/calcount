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

const { isAuthenticated, loadFromStorage, isAccessTokenValid, refreshAccessToken } = useAuth()
const authChecking = ref(true)

onMounted(async () => {
    loadFromStorage()

    // Pokud je uživatel přihlášen, zkontroluj platnost tokenu
    if (isAuthenticated.value && !isAccessTokenValid.value) {
        try {
            await refreshAccessToken()
        } catch {
            // Token refresh selhal, necháme to na middleware
        }
    }

    // Po kontrole autentizace přesměruj podle stavu
    if (isAuthenticated.value && window.location.pathname === '/') {
        await navigateTo('/app')
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