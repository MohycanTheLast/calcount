import { useQuery, useQueryClient } from '@tanstack/vue-query'
import type { CurrentUser } from '~/api/types'
import { useUserApi } from '~/api/user'

// welcome = uvítání po prvním přihlášení, tour = průvodce po prvcích Deníku
export type OnboardingPhase = 'idle' | 'welcome' | 'tour'

const CURRENT_USER_KEY = ['user', 'me'] as const

// Přihlášený uživatel – podle onboardingCompleted se po prvním přihlášení ukáže uvítání
export const useCurrentUserQuery = () => {
    const userApi = useUserApi()

    return useQuery({
        queryKey: CURRENT_USER_KEY,
        queryFn: () => userApi.me(),
        staleTime: Infinity,
    })
}

export const useOnboarding = () => {
    const phase = useState<OnboardingPhase>('onboarding:phase', () => 'idle')
    const userApi = useUserApi()
    const queryClient = useQueryClient()

    // Dokončení i přeskočení: poznamená se na serveru, aby se průvodce sám nespustil ani na jiném zařízení
    const finish = async () => {
        phase.value = 'idle'

        const user = queryClient.getQueryData<CurrentUser>(CURRENT_USER_KEY)
        if (user?.onboardingCompleted) return

        if (user) queryClient.setQueryData<CurrentUser>(CURRENT_USER_KEY, { ...user, onboardingCompleted: true })
        try {
            await userApi.completeOnboarding()
        } catch {
            // Nevadí – průvodce se jen při dalším přihlášení nabídne znovu
        }
    }

    // Znovu spustit z Nastavení – rovnou průvodce (bez uvítání), spustí ho Deník
    const replay = () => {
        phase.value = 'tour'
        return navigateTo('/app')
    }

    return { phase, finish, replay }
}
