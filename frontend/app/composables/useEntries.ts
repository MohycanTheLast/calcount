// Denní souhrn z GET /api/entries (backend: DietDiaryEntryDay)
export type DiaryDay = {
    date: string // d.m.Y
    kcal: number
    kcalBurned: number
    water: number // litry
}

export const useEntries = () => {
    const { apiFetch } = useApiFetch()

    // Sdílený stav: při návratu na Deník se hned zobrazí poslední data a obnoví se na pozadí,
    // takže obrazovka při přechodu neposkakuje kvůli načítání.
    const entries = useState<DiaryDay[]>('diary:entries', () => [])
    const loaded = useState<boolean>('diary:loaded', () => false)
    const loading = ref(false)
    const error = ref<string | null>(null)

    const fetchEntries = async () => {
        loading.value = true
        error.value = null

        try {
            entries.value = await apiFetch<DiaryDay[]>('/api/entries')
            loaded.value = true
        } catch (e) {
            error.value = 'Nepodařilo se načíst data'
        } finally {
            loading.value = false
        }
    }

    return {
        entries,
        loaded,
        loading,
        error,
        fetchEntries
    }
}
