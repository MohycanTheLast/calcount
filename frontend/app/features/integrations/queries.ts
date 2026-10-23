import { useMutation, useQuery, useQueryClient } from '@tanstack/vue-query'
import { useActivityApi } from '~/api/activity'
import { diaryKeys } from '~/features/diary/queries'

// Osobní API tokeny (zápis aktivních kalorií z iOS Zkratek)
export const tokenKeys = {
    all: ['tokens'] as const,
}

export const usePersonalTokensQuery = () => {
    const activityApi = useActivityApi()

    return useQuery({
        queryKey: tokenKeys.all,
        queryFn: () => activityApi.listTokens(),
    })
}

export const usePersonalTokenMutations = () => {
    const activityApi = useActivityApi()
    const queryClient = useQueryClient()
    const { settings } = useUserSettings()

    const createToken = useMutation({
        mutationFn: (name: string) => activityApi.createToken(name),
        onSuccess: (created) => {
            // První token přepíná zdroj aktivních kalorií na „synchronizované, jinak průměr“ – mění se výdej dnů
            if (settings.value.activitySource !== created.activitySource) {
                settings.value.activitySource = created.activitySource
                queryClient.invalidateQueries({ queryKey: ['settings'] })
                queryClient.invalidateQueries({ queryKey: diaryKeys.all })
            }
            return queryClient.invalidateQueries({ queryKey: tokenKeys.all })
        },
    })

    const revokeToken = useMutation({
        mutationFn: (id: number) => activityApi.revokeToken(id),
        onSuccess: () => queryClient.invalidateQueries({ queryKey: tokenKeys.all }),
    })

    return { createToken, revokeToken }
}
