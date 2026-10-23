import { useMutation, useQuery, useQueryClient } from '@tanstack/vue-query'
import { useDiaryApi } from '~/api/diary'
import type { DayEntry, EntryValues } from '~/api/types'
import { addDays, formatIsoDate } from '~/utils/dates'

// Počet denních karet v Deníku (včetně dneška)
export const RECENT_DAYS = 14

// Klíče cache: invalidace prefixu ['diary'] obnoví po změně záznamu Deník, detail dne i budoucí kalendář/statistiky
export const diaryKeys = {
    all: ['diary'] as const,
    days: (from: string, to: string) => ['diary', 'days', from, to] as const,
    dayEntries: (date: string) => ['diary', 'dayEntries', date] as const,
}

// Souhrny posledních RECENT_DAYS dní (každý den v rozsahu, i bez záznamu)
export const useRecentDaysQuery = () => {
    const diaryApi = useDiaryApi()

    const to = formatIsoDate(new Date())
    const from = formatIsoDate(addDays(new Date(), -(RECENT_DAYS - 1)))

    return useQuery({
        queryKey: diaryKeys.days(from, to),
        queryFn: () => diaryApi.getDays(from, to),
    })
}

// Souhrn jednoho dne (date = YYYY-MM-DD)
export const useDaySummaryQuery = (date: string) => {
    const diaryApi = useDiaryApi()

    return useQuery({
        queryKey: diaryKeys.days(date, date),
        queryFn: async () => (await diaryApi.getDays(date, date))[0] ?? null,
    })
}

export const useDayEntriesQuery = (date: string) => {
    const diaryApi = useDiaryApi()

    return useQuery({
        queryKey: diaryKeys.dayEntries(date),
        queryFn: () => diaryApi.getDayEntries(date),
    })
}

export const useDiaryMutations = () => {
    const diaryApi = useDiaryApi()
    const queryClient = useQueryClient()

    const invalidateDiary = () => queryClient.invalidateQueries({ queryKey: diaryKeys.all })

    // Upraví záznam v načtených seznamech dne hned, ještě před obnovou ze serveru
    const patchDayEntries = (update: (entries: DayEntry[]) => DayEntry[]) =>
        queryClient.setQueriesData<DayEntry[]>({ queryKey: ['diary', 'dayEntries'] }, (old) => old && update(old))

    const createEntry = useMutation({
        mutationFn: (text: string) => diaryApi.createEntry(text),
        onSuccess: invalidateDiary,
    })

    const updateEntry = useMutation({
        mutationFn: ({ id, values }: { id: number; values: EntryValues }) => diaryApi.updateEntry(id, values),
        onSuccess: (_result, { id, values }) => {
            patchDayEntries((entries) =>
                entries.map((e) => (e.id === id ? { ...e, kcal: values.kcal, water: values.waterInMl } : e)),
            )
            return invalidateDiary()
        },
    })

    const deleteEntry = useMutation({
        mutationFn: (id: number) => diaryApi.deleteEntry(id),
        onSuccess: (_result, id) => {
            patchDayEntries((entries) => entries.filter((e) => e.id !== id))
            return invalidateDiary()
        },
    })

    return { createEntry, updateEntry, deleteEntry, invalidateDiary }
}
