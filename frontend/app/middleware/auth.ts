import {useAuth} from "~/composables/useAuth";

export default defineNuxtRouteMiddleware(async (to) => {
    // nechceme měnit root "/"
    if (to.path !== '/' && to.path.endsWith('/')) {
        return navigateTo(to.path.slice(0, -1), { replace: true })
    }

    const { isAuthenticated, loadFromStorage, isAccessTokenValid, refreshAccessToken } = useAuth()
    loadFromStorage()

    if (!isAuthenticated.value) {
        return navigateTo('/')
    }

    // Volitelné: když je access token neplatný, zkus ho potichu obnovit
    if (!isAccessTokenValid.value) {
        try {
            await refreshAccessToken()
        } catch {
            // refresh selhal -> odhlásit a poslat na login
            return navigateTo('/')
        }
    }
})