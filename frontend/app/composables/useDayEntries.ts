export type DayEntry = {
    id: number
    text: string
    kcal: number
    water: number // ml
}

export const useDayEntries = () => {
    const { apiFetch } = useApiFetch()

    const dayEntries = ref<DayEntry[]>([]);
    const loading = ref(false);
    const error = ref<string | null>(null);

    // date ve formátu YYYY-MM-DD
    const fetchDayEntries = async (date: string) => {
        loading.value = true;
        error.value = null;

        try {
            dayEntries.value = await apiFetch<DayEntry[]>(`/api/diary-day/${date}`);
        } catch (e) {
            error.value = 'Nepodařilo se načíst detail záznamů';
        } finally {
            loading.value = false;
        }
    };

    const updateEntry = async (entry: DayEntry, values: { kcal: number; waterInMl: number }) => {
        await apiFetch(`/api/save/${entry.id}`, {
            method: 'POST',
            body: values,
        })
        entry.kcal = values.kcal
        entry.water = values.waterInMl
    }

    const deleteEntry = async (entry: DayEntry) => {
        await apiFetch(`/api/delete/${entry.id}`, { method: 'POST' })
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
