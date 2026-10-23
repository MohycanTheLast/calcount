import { QueryClient, VueQueryPlugin } from '@tanstack/vue-query'

// Cache dat ze serveru (souhrny dní, záznamy dne, nastavení). Při návratu na obrazovku se hned ukáží
// data z cache a podle potřeby se na pozadí obnoví. Při odhlášení se celá cache maže (useAuth).
export default defineNuxtPlugin((nuxtApp) => {
    const queryClient = new QueryClient({
        defaultOptions: {
            queries: {
                // Krátké okno, aby rychlé přepínání obrazovek nevolalo API pořád dokola
                staleTime: 10_000,
                // Chyby 4xx (neplatný vstup, odhlášení) nemá smysl opakovat
                retry: (failureCount, error: any) =>
                    failureCount < 1 && !(error?.status >= 400 && error?.status < 500),
            },
        },
    })

    nuxtApp.vueApp.use(VueQueryPlugin, { queryClient })

    return { provide: { queryClient } }
})
