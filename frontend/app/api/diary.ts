import type { CreatedEntry, DayEntry, DaySummary, EntryValues, PhotoAnalysis } from '~/api/types'

// Deník: záznamy jídla a pití a souhrny dní. Všechna volání jdou přes apiFetch (token, obnova session).
export const useDiaryApi = () => {
    const { apiFetch } = useApiFetch()

    return {
        // from/to ve formátu YYYY-MM-DD, včetně; vrací každý den v rozsahu (i bez záznamu)
        getDays: (from: string, to: string) =>
            apiFetch<DaySummary[]>('/api/days', { query: { from, to } }),

        getDayEntries: (date: string) =>
            apiFetch<DayEntry[]>(`/api/diary-day/${date}`),

        // Odhad kalorií a vody z textu přes AI; záznam se rovnou uloží k dnešku, nebo k zadanému dni (zpětný zápis)
        createEntry: (text: string, date?: string) =>
            apiFetch<CreatedEntry>('/api/calculate', { method: 'POST', body: { food: text, date } }),

        updateEntry: (id: number, values: EntryValues) =>
            apiFetch<void>(`/api/save/${id}`, { method: 'POST', body: values }),

        deleteEntry: (id: number) =>
            apiFetch<void>(`/api/delete/${id}`, { method: 'POST' }),

        // Rozpozná jídlo na fotce; vrací jen text k doplnění do pole, nic neukládá
        uploadPhoto: (file: File) => {
            const formData = new FormData()
            formData.append('file', file)
            return apiFetch<PhotoAnalysis>('/api/upload', { method: 'POST', body: formData })
        },
    }
}
