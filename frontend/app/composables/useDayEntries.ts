import {useApiFetch} from "~/composables/useApiFetch";

const { apiFetch } = useApiFetch()
export const useDayEntries = () => {
    const dayEntries = ref<Array<{id: bigint, text: string; kcal: number; water: number }>>([]);
    const loading = ref(false);
    const error = ref<string | null>(null);

    const fetchDayEntries = async (date: string) => {
        loading.value = true;
        error.value = null;

        try {
            // Volání API s formátovaným datem YYYY-MM-DD
            dayEntries.value = await apiFetch<{
                id: bigint,
                text: string;
                kcal: number;
                water: number
            }[]>(`/api/diary-day/${date}`); // Nastavení záznamů

        } catch (e) {
            error.value = 'Nepodařilo se načíst detail záznamů';
        } finally {
            loading.value = false;
        }
    };

    return {
        dayEntries,
        loading,
        error,
        fetchDayEntries
    };
};