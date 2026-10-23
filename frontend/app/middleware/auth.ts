import {useAuth} from "~/composables/useAuth";

export default defineNuxtRouteMiddleware(async (to) => {
    // nechceme měnit root "/"
    // (Apache k adresářům z buildu lomítko přidává, např. /calendar → /calendar/) – query a hash zachovat
    if (to.path !== '/' && to.path.endsWith('/')) {
        return navigateTo({ path: to.path.slice(0, -1), query: to.query, hash: to.hash }, { replace: true })
    }

    const { isAuthenticated, loadFromStorage, hasValidAccessToken, refreshAccessToken } = useAuth()
    loadFromStorage()

    if (!isAuthenticated.value) {
        return navigateTo('/')
    }

    // Volitelné: když je access token neplatný, zkus ho potichu obnovit
    if (!hasValidAccessToken()) {
        try {
            await refreshAccessToken()
        } catch {
            // Na login jen když server session ukončil; při síťové chybě necháme uživatele v aplikaci
            if (!isAuthenticated.value) return navigateTo('/')
        }
    }
})