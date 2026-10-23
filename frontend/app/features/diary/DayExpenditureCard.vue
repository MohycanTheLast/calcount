<template>
    <section>
        <h2 class="cc-section-title">Výdej</h2>

        <div class="cc-card cc-card-pad space-y-3 text-sm">
            <div class="flex items-center justify-between gap-3">
                <span class="cc-subtle">Bazální metabolismus</span>
                <span v-if="summary.bmrKcal !== null">{{ formatKcal(summary.bmrKcal) }} kcal</span>
                <NuxtLink v-else to="/settings" class="cc-link text-xs">Vyplň BMR</NuxtLink>
            </div>

            <div v-if="!editing" class="flex items-center justify-between gap-3">
                <div class="min-w-0">
                    <div class="cc-subtle">Aktivní kalorie</div>
                    <div v-if="sourceLabel" class="text-xs cc-subtle">{{ sourceLabel }}</div>
                </div>
                <div class="flex items-center gap-3 shrink-0">
                    <span>{{ summary.activeKcal !== null ? `${formatKcal(summary.activeKcal)} kcal` : '–' }}</span>
                    <button type="button" class="cc-link" @click="startEdit">Upravit</button>
                </div>
            </div>

            <form v-else class="space-y-2" @submit.prevent="onSave">
                <label class="block">
                    <span class="cc-subtle">Aktivní kalorie tohoto dne</span>
                    <input
                        ref="inputEl"
                        v-model="draft"
                        type="text"
                        inputmode="numeric"
                        class="cc-input mt-1"
                        placeholder="Např. 450"
                    />
                </label>
                <div class="flex items-center gap-3">
                    <button class="cc-btn-primary flex-1" :disabled="busy">{{ setManual.isPending.value ? 'Ukládám…' : 'Uložit' }}</button>
                    <button type="button" class="cc-btn flex-1" :disabled="busy" @click="editing = false">Zrušit</button>
                </div>
                <button
                    v-if="summary.activeSource === 'manual'"
                    type="button"
                    class="cc-link w-full text-center"
                    :disabled="busy"
                    @click="onReset"
                >
                    Vrátit automatickou hodnotu
                </button>
            </form>

            <div
                v-if="summary.expenditureKcal !== null"
                class="flex items-center justify-between gap-3 pt-3 border-t font-semibold"
                style="border-color: rgb(var(--cc-border));"
            >
                <span>Celkem</span>
                <span>{{ formatKcal(summary.expenditureKcal) }} kcal</span>
            </div>

            <p v-if="error" class="text-red-400">{{ error }}</p>
        </div>
    </section>
</template>

<script setup lang="ts">
import type { DaySummary } from '~/api/types'
import { useActiveKcalMutations } from '~/features/diary/queries'
import { MAX_ACTIVE_KCAL, activeSourceLabel, parseActiveKcalInput } from '~/utils/activity'

// Výdej dne (BMR + aktivní kalorie) s ruční úpravou aktivních kalorií – ta má přednost před synchronizací
// i průměrnou aktivitou z nastavení.
const props = defineProps<{
    summary: DaySummary
}>()

const { setManual, resetManual } = useActiveKcalMutations()

const editing = ref(false)
const draft = ref('')
const error = ref<string | null>(null)
const inputEl = ref<HTMLInputElement | null>(null)

const busy = computed(() => setManual.isPending.value || resetManual.isPending.value)
const sourceLabel = computed(() => activeSourceLabel(props.summary.activeSource))

const startEdit = async () => {
    draft.value = props.summary.activeKcal !== null ? String(props.summary.activeKcal) : ''
    error.value = null
    editing.value = true
    await nextTick()
    inputEl.value?.focus()
}

const onSave = async () => {
    const activeKcal = parseActiveKcalInput(draft.value)
    if (activeKcal === null) {
        error.value = `Zadej číslo od 0 do ${formatKcal(MAX_ACTIVE_KCAL)}.`
        return
    }

    error.value = null
    try {
        await setManual.mutateAsync({ date: props.summary.date, activeKcal })
        editing.value = false
    } catch (e: any) {
        error.value = e?.data?.error ?? 'Nepodařilo se uložit.'
    }
}

const onReset = async () => {
    error.value = null
    try {
        await resetManual.mutateAsync(props.summary.date)
        editing.value = false
    } catch {
        error.value = 'Nepodařilo se uložit.'
    }
}
</script>
