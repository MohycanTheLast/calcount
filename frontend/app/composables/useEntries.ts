import {useApiFetch} from "~/composables/useApiFetch";

const { apiFetch } = useApiFetch()
export const useEntries = () => {
    const entries = ref<any[]>([])
    const loading = ref(false)
    const error = ref<string | null>(null)

    const fetchEntries = async () => {
        loading.value = true
        error.value = null

        try {
            entries.value = await apiFetch<{ date: string; kcal: number; kcal_burned: number; water: number }[]>(
                '/api/entries'
            )
        } catch (e) {
            error.value = 'Nepodařilo se načíst data'
        } finally {
            loading.value = false
        }
    }

    return {
        entries,
        loading,
        error,
        fetchEntries
    }
}