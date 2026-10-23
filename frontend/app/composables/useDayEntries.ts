import { useDiaryApi } from '~/api/diary'
import type { DayEntry, EntryValues } from '~/api/types'

export type { DayEntry } from '~/api/types'

export const useDayEntries = () => {
    const diaryApi = useDiaryApi()

    const dayEntries = ref<DayEntry[]>([]);
    const loading = ref(false);
    const error = ref<string | null>(null);

    // date ve formátu YYYY-MM-DD
    const fetchDayEntries = async (date: string) => {
        loading.value = true;
        error.value = null;

        try {
            dayEntries.value = await diaryApi.getDayEntries(date);
        } catch (e) {
            error.value = 'Nepodařilo se načíst detail záznamů';
        } finally {
            loading.value = false;
        }
    };

    const updateEntry = async (entry: DayEntry, values: EntryValues) => {
        await diaryApi.updateEntry(entry.id, values)
        entry.kcal = values.kcal
        entry.water = values.waterInMl
    }

    const deleteEntry = async (entry: DayEntry) => {
        await diaryApi.deleteEntry(entry.id)
        dayEntries.value = dayEntries.value.filter((e) => e.id !== entry.id)
    }

    return {
        dayEntries,
        loading,
        error,
        fetchDayEntries,
        updateEntry,
        deleteEntry,
    };
};
