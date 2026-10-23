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
                    :expenditure-kcal="dailyExpenditureKcal"
                />
            </section>

            <section>
                <h2 class="cc-section-title">Záznamy</h2>

                <div v-if="loading && !dayEntries.length" class="cc-list">
                    <div v-for="n in 3" :key="n" class="cc-list-row animate-pulse">
                        <div class="h-4 w-3/4 rounded-full" style="background: rgb(var(--cc-border));"></div>
                        <div class="mt-2 h-3 w-1/3 rounded-full" style="background: rgb(var(--cc-border));"></div>
                    </div>
                </div>

                <p v-else-if="error" class="text-sm text-red-400">{{ error }}</p>

                <p v-else-if="!dayEntries.length" class="text-sm cc-subtle px-1">
                    Žádné záznamy k zobrazení
                </p>

                <ul v-else class="cc-list">
                    <li v-for="entry in dayEntries" :key="entry.id" class="cc-list-row">
                        <!-- ZOBRAZENÍ -->
                        <div v-if="editingId !== entry.id" class="flex items-start gap-3">
                            <div class="flex-1 min-w-0">
                                <p class="text-[15px] leading-snug whitespace-pre-wrap break-words">{{ entry.text }}</p>
                                <p class="mt-1 text-sm cc-subtle">
                                    {{ formatKcal(entry.kcal) }} kcal
                                    <template v-if="entry.water"> · {{ formatLiters(entry.water / 1000) }} l</template>
                                </p>
                            </div>

                            <button
                                type="button"
                                class="cc-icon-btn shrink-0 -mr-1"
                                style="color: rgb(var(--cc-accent));"
                                aria-label="Upravit záznam"
                                @click="startEdit(entry)"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                    <path d="M16.3 3.3a2.4 2.4 0 0 1 3.4 3.4L8.5 17.9a1 1 0 0 1-.46.26l-4 1a1 1 0 0 1-1.21-1.21l1-4a1 1 0 0 1 .26-.46L16.3 3.3Z"/>
                                </svg>
                            </button>
                        </div>

                        <!-- ÚPRAVA -->
                        <div v-else class="space-y-3">
                            <p class="text-[15px] leading-snug whitespace-pre-wrap break-words">{{ entry.text }}</p>

                            <div class="grid grid-cols-2 gap-3">
                                <label class="relative">
                                    <span class="sr-only">Kalorie</span>
                                    <input v-model.number="editKcal" type="number" min="0" inputmode="numeric" class="cc-input pr-14" />
                                    <span class="absolute right-4 top-1/2 -translate-y-1/2 cc-subtle text-sm">kcal</span>
                                </label>
                                <label class="relative">
                                    <span class="sr-only">Voda v litrech</span>
                                    <input v-model.number="editWaterL" type="number" min="0" step="any" inputmode="decimal" class="cc-input pr-10" />
                                    <span class="absolute right-4 top-1/2 -translate-y-1/2 cc-subtle text-sm">l</span>
                                </label>
                            </div>

                            <p v-if="rowError" class="text-sm text-red-400">{{ rowError }}</p>

                            <div class="flex items-center justify-between">
                                <button type="button" class="cc-btn-danger-text" :disabled="busy" @click="removeEntry(entry)">
                                    Smazat
                                </button>

                                <div class="flex items-center gap-4">
                                    <button type="button" class="cc-subtle text-sm" :disabled="busy" @click="cancelEdit">
                                        Zrušit
                                    </button>
                                    <button type="button" class="cc-btn-primary px-5 py-2" :disabled="busy" @click="saveEdit(entry)">
                                        Uložit
                                    </button>
                                </div>
                            </div>
                        </div>
                    </li>
                </ul>
            </section>
        </main>
    </div>
</template>

<script setup lang="ts">
import type { DayEntry } from "~/composables/useDayEntries";

definePageMeta({
    middleware: ['auth'],
})

const route = useRoute()
const router = useRouter()

const date = parseIsoDate(String(route.params.date))
if (!date) {
    navigateTo('/app', { replace: true })
}

const title = computed(() => (date ? dayTitle(date) : ''))

const { dayEntries, loading, error, fetchDayEntries, updateEntry, deleteEntry } = useDayEntries()
const { fetchEntries } = useEntries()
const { dailyExpenditureKcal, ensureLoaded } = useUserSettings()

// Součty z položek – po úpravě nebo smazání se hned přepočítají
const totals = computed(() => ({
    kcal: dayEntries.value.reduce((sum, e) => sum + Number(e.kcal), 0),
    waterLiters: dayEntries.value.reduce((sum, e) => sum + Number(e.water), 0) / 1000,
}))

onMounted(() => {
    ensureLoaded()
    if (date) fetchDayEntries(formatIsoDate(date))
})

// Zpět v historii, pokud jsme přišli z aplikace; jinak (přímý odkaz) na Deník
const goBack = () => {
    if (window.history.state?.back) router.back()
    else navigateTo('/app')
}

// ===== úprava řádku =====
const editingId = ref<number | null>(null)
const editKcal = ref<number | null>(null)
const editWaterL = ref<number | null>(null)
const busy = ref(false)
const rowError = ref<string | null>(null)

const startEdit = (entry: DayEntry) => {
    editingId.value = entry.id
    editKcal.value = Number(entry.kcal)
    editWaterL.value = Number(entry.water) / 1000
    rowError.value = null
}

const cancelEdit = () => {
    editingId.value = null
}

const saveEdit = async (entry: DayEntry) => {
    if (editKcal.value == null || editWaterL.value == null) return

    busy.value = true
    rowError.value = null
    try {
        await updateEntry(entry, { kcal: editKcal.value, waterInMl: Math.round(editWaterL.value * 1000) })
        editingId.value = null
        fetchEntries() // denní karty na Deníku
    } catch {
        rowError.value = 'Nepodařilo se záznam uložit'
    } finally {
        busy.value = false
    }
}

const removeEntry = async (entry: DayEntry) => {
    if (!window.confirm('Opravdu smazat tento záznam?')) return

    busy.value = true
    rowError.value = null
    try {
        await deleteEntry(entry)
        editingId.value = null
        fetchEntries()
    } catch {
        rowError.value = 'Nepodařilo se záznam smazat'
    } finally {
        busy.value = false
    }
}
</script>
