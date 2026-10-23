// Směr přechodu: otevření detailu dne vyjede zprava, návrat z detailu zleva, přepnutí záložek prolnutím.
export default defineNuxtRouteMiddleware((to, from) => {
    const isDetail = (path: string) => path.startsWith('/day/')

    if (isDetail(to.path) && !isDetail(from.path)) {
        to.meta.pageTransition = { name: 'cc-push', mode: 'out-in' }
    } else if (isDetail(from.path) && !isDetail(to.path)) {
        to.meta.pageTransition = { name: 'cc-pop', mode: 'out-in' }
    }
})
