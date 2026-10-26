import { useQuery } from '@tanstack/vue-query'
import type { AccountType } from '~/api/types'
import { useUserApi } from '~/api/user'
import { DEFAULT_CAFFEINE_LIMIT_MG, DEFAULT_WATER_GOAL_ML } from '~/utils/tracking'

export const CURRENT_USER_KEY = ['user', 'me'] as const

// Přihlášený uživatel: typ účtu a jeho funkce, stav úvodního průvodce
export const useCurrentUserQuery = () => {
    const userApi = useUserApi()

    return useQuery({
        queryKey: CURRENT_USER_KEY,
        queryFn: () => userApi.me(),
        staleTime: Infinity,
    })
}

export const ACCOUNT_TYPE_LABELS: Record<AccountType, string> = {
    free: 'Free',
    premium: 'Premium',
    superior: 'Superior',
}

/**
 * Co se má v aplikaci hlídat: zapnuté v pokročilém nastavení a (u kofeinu a makroživin) dostupné pro typ účtu.
 * Po snížení typu účtu tak placené funkce zmizí, i když zůstaly v nastavení zapnuté.
 */
export const useTracking = () => {
    const { settings } = useUserSettings()
    const { data: user } = useCurrentUserQuery()

    return computed(() => {
        const features = user.value?.features ?? []
        return {
            hydration: settings.value.hydrationEnabled === true,
            waterGoalMl: settings.value.waterGoalMl ?? DEFAULT_WATER_GOAL_ML,
            caffeine: settings.value.caffeineEnabled === true && features.includes('caffeine'),
            caffeineLimitMg: settings.value.caffeineLimitMg ?? DEFAULT_CAFFEINE_LIMIT_MG,
            macros: settings.value.macrosEnabled === true && features.includes('macros'),
        }
    })
}
