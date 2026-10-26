<template>
    <div class="cc-page">
        <header class="cc-nav">
            <div class="cc-container cc-header">
                <div>
                    <button type="button" class="cc-header-action -ml-1" @click="goBack">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                            <path d="M15.7 4.3a1 1 0 0 1 0 1.4L9.42 12l6.3 6.3a1 1 0 0 1-1.42 1.4l-7-7a1 1 0 0 1 0-1.4l7-7a1 1 0 0 1 1.4 0Z"/>
                        </svg>
                        Zpět
                    </button>
                </div>
                <h1 class="cc-header-title">{{ title }}</h1>
                <div></div>
            </div>
        </header>

        <main class="cc-container pt-4 pb-16 space-y-6">
            <section class="cc-card cc-card-pad">
                <DaySummary
                    :kcal="totals.kcal"
                    :water-liters="totals.waterLiters"
                    :expenditure-kcal="summary?.expenditureKcal ?? null"
                    :active-source="summary?.activeSource"
                />
            </section>

            <section>
                <h2 class="cc-section-title">Záznamy</h2>

                <div v-if="isPending" class="cc-list">
                    <div v-for="n in 3" :key="n" class="cc-list-row animate-pulse">
                        <div class="h-4 w-3/4 rounded-full" style="background: rgb(var(--cc-border));"></div>
                        <div class="mt-2 h-3 w-1/3 rounded-full" style="background: rgb(var(--cc-border));"></div>
                    </div>
                </div>

                <p v-else-if="isError && !entries" class="text-sm text-red-400">Nepodařilo se načíst detail záznamů</p>

                <p v-else-if="!entries?.length" class="text-sm cc-subtle px-1">
                    Žádné záznamy k zobrazení
                </p>

                <ul v-else class="cc-list">
                    <DayEntryRow
                        v-for="entry in entries"
                        :key="entry.id"
                        :entry="entry"
                        :editing="editingId === entry.id"
                        @edit="editingId = entry.id"
                        @cancel="editingId = null"
                        @done="editingId = null"
                    />
                </ul>
            </section>

            <!-- ZPĚTNÝ ZÁPIS – stejně jako v Deníku, jen k tomuto dni -->
            <section>
                <h2 class="cc-section-title">{{ today ? 'Nový záznam' : 'Doplnit záznam' }}</h2>

                <EntryComposer :date="isoDate" @created="onCreated" />

                <AlertMessage
                    v-if="confirmationMessage !== null"
                    :key="confirmationCounter"
                    :message="confirmationMessage"
                    :duration="5000"
                />
            </section>

            <DayExpenditureCard v-if="summary" :summary="summary" />
        </main>
    </div>
</template>

<script setup lang="ts">
import type { CreatedEntry } from "~/api/types";
import { useDayEntriesQuery, useDaySummaryQuery } from "~/features/diary/queries";

definePageMeta({
    middleware: ['auth'],
})

const route = useRoute()
const router = useRouter()

const isoDate = String(route.params.date)
const date = parseIsoDate(isoDate)
// Neplatné datum nebo budoucí den (do budoucna se nezapisuje) – zpět do Deníku
if (!date || isoDate > formatIsoDate(new Date())) {
    navigateTo('/app', { replace: true })
}

const title = computed(() => (date ? dayTitle(date) : ''))
const today = date ? isToday(date) : false

const confirmationMessage = ref<string | null>(null)
const confirmationCounter = ref(0)

// Nový záznam se objeví v seznamu (obnoví se celý deník), tady jen krátké potvrzení s odhadem a komentářem AI
const onCreated = (entry: CreatedEntry) => {
    const summary = `Zapsáno: ${formatKcal(entry.kcal)} kcal, ${formatLiters(entry.waterInMl / 1000)} l`
    confirmationMessage.value = entry.comment ? `${summary} – ${entry.comment}` : summary
    confirmationCounter.value += 1
}

const { data: entries, isPending, isError } = useDayEntriesQuery(isoDate)
// Výdej dne ze serveru (BMR + aktivní kalorie podle zdroje)
const { data: summary } = useDaySummaryQuery(isoDate)

// Součty z položek – po úpravě nebo smazání se hned přepočítají
const totals = computed(() => {
    const list = entries.value ?? []
    return {
        kcal: list.reduce((sum, e) => sum + Number(e.kcal), 0),
        waterLiters: list.reduce((sum, e) => sum + Number(e.water), 0) / 1000,
    }
})

const editingId = ref<number | null>(null)

// Zpět v historii, pokud jsme přišli z aplikace; jinak (přímý odkaz) na Deník
const goBack = () => {
    if (window.history.state?.back) router.back()
    else navigateTo('/app')
}
</script>
