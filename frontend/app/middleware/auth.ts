import {useAuth} from "~/composables/useAuth";

export default defineNuxtRouteMiddleware(async (to) => {
    // nechceme měnit root "/"
    if (to.path !== '/' && to.path.endsWith('/')) {
        return navigateTo(to.path.slice(0, -1), { replace: true })
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