import { useQuery, useQueryClient } from '@tanstack/vue-query'
import type { Ref } from 'vue'
import { useDiaryApi } from '~/api/diary'
import { diaryKeys } from '~/features/diary/queries'
import { addMonths, formatIsoDate, monthRange } from '~/utils/dates'

const isoRange = (month: Date) => {
    const { from, to } = monthRange(month)
    return { from: formatIsoDate(from), to: formatIsoDate(to) }
}

// Souhrny všech dní zobrazeného měsíce jedním dotazem. Klíč je stejný jako v Deníku (diaryKeys.days),
// takže po změně záznamu se kalendář obnoví sám. Předchozí měsíc se přednačítá, aby šipka ‹ byla okamžitá.
export const useMonthDaysQuery = (month: Ref<Date>) => {
    const diaryApi = useDiaryApi()
    const queryClient = useQueryClient()

    const range = computed(() => isoRange(month.value))

    watch(month, (value) => {
        const previous = isoRange(addMonths(value, -1))
        queryClient.prefetchQuery({
            queryKey: diaryKeys.days(previous.from, previous.to),
            queryFn: () => diaryApi.getDays(previous.from, previous.to),
        })
    }, { immediate: true })

    return useQuery({
        queryKey: computed(() => diaryKeys.days(range.value.from, range.value.to)),
        queryFn: () => diaryApi.getDays(range.value.from, range.value.to),
    })
}
