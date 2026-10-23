import { useDiaryApi } from '~/api/diary'
import type { RecentDay } from '~/api/types'

export type DiaryDay = RecentDay

export const useEntries = () => {
    const diaryApi = useDiaryApi()

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
            entries.value = await diaryApi.getRecentDays()
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
