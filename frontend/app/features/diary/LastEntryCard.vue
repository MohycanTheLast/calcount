<template>
    <div class="cc-card cc-card-pad mt-4">
        <div v-if="mode === 'view'" class="space-y-3">
            <p class="text-sm cc-subtle">
                {{ entry.message }}
            </p>

            <div class="flex flex-wrap gap-x-6 gap-y-1 text-base font-semibold">
                <div>🔥 {{ formatKcal(entry.kcal) }} kcal</div>
                <div>💧 {{ formatLiters(entry.waterLiters) }} l</div>
                <div v-if="tracking.caffeine && entry.caffeineMg">☕ {{ Math.round(entry.caffeineMg) }} mg</div>
            </div>

            <p v-if="macrosText" class="text-sm cc-subtle">{{ macrosText }}</p>

            <div class="flex items-center justify-between pt-1">
                <button type="button" class="cc-link" @click="startEdit">
                    Upravit hodnoty
                </button>

                <button type="button" class="cc-btn-danger-text" :disabled="busy" @click="onDelete">
                    Smazat
                </button>
            </div>
        </div>

        <!-- EDIT MODE -->
        <div v-else class="space-y-4">
            <div class="grid grid-cols-2 gap-3">
                <label class="relative">
                    <span class="sr-only">Kalorie</span>
                    <input
                        v-model.number="kcal"
                        type="number"
                        min="0"
                        inputmode="numeric"
                        class="cc-input pr-14"
                    />
                    <span class="absolute right-4 top-1/2 -translate-y-1/2 cc-subtle text-sm">kcal</span>
                </label>
                <label class="relative">
                    <span class="sr-only">Voda v litrech</span>
                    <input
                        v-model.number="waterInL"
                        type="number"
                        step="any"
                        min="0"
                        inputmode="decimal"
                        class="cc-input pr-10"
                    />
                    <span class="absolute right-4 top-1/2 -translate-y-1/2 cc-subtle text-sm">l</span>
                </label>
            </div>

            <div class="flex justify-between items-center">
                <button type="button" class="cc-subtle text-sm" @click="mode = 'view'">
                    Zrušit
                </button>

                <button type="button" class="cc-btn-primary px-6 py-2" :disabled="busy" @click="onSave">
                    Uložit
                </button>
            </div>
        </div>

        <p v-if="error" class="mt-3 text-sm text-red-400">{{ error }}</p>
    </div>
</template>

<script setup lang="ts">
import type { Nutrients } from "~/api/types";
import { useTracking } from "~/features/account/useAccount";
import { useDiaryMutations } from "~/features/diary/queries";

export type LastEntry = Partial<Nutrients> & {
    id: number
    kcal: number
    waterLiters: number
    message: string
}

// Výsledek posledního zápisu s možností upravit hodnoty nebo záznam smazat
const props = defineProps<{
    entry: LastEntry
}>()

const emit = defineEmits<{
    (e: "updated", entry: LastEntry): void
    (e: "deleted"): void
}>()

const { updateEntry, deleteEntry } = useDiaryMutations()
const tracking = useTracking()

// Makroživiny z odhadu AI, jen se zapnutým hlídáním
const macrosText = computed(() => (tracking.value.macros ? formatMacrosShort({
    proteinG: props.entry.proteinG,
    carbsG: props.entry.carbsG,
    sugarG: props.entry.sugarG,
    fatG: props.entry.fatG,
}) : null))

const mode = ref<'view' | 'edit'>('view')
const kcal = ref<number | null>(null)
const waterInL = ref<number | null>(null)
const error = ref<string | null>(null)
const busy = computed(() => updateEntry.isPending.value || deleteEntry.isPending.value)

// Nový záznam (jiné id) vždy začíná v režimu zobrazení
watch(() => props.entry.id, () => {
    mode.value = 'view'
    error.value = null
})

const startEdit = () => {
    kcal.value = props.entry.kcal
    waterInL.value = props.entry.waterLiters
    error.value = null
    mode.value = 'edit'
}

const onSave = async () => {
    if (kcal.value == null || waterInL.value == null) return

    error.value = null
    try {
        await updateEntry.mutateAsync({
            id: props.entry.id,
            values: { kcal: kcal.value, waterInMl: Math.round(waterInL.value * 1000) },
        })
        emit('updated', { ...props.entry, kcal: kcal.value, waterLiters: waterInL.value, message: 'Upravené hodnoty byly uloženy' })
    } catch {
        error.value = 'Nepodařilo se záznam uložit'
    } finally {
        mode.value = 'view'
    }
}

const onDelete = async () => {
    error.value = null
    try {
        await deleteEntry.mutateAsync(props.entry.id)
        emit('deleted')
    } catch {
        error.value = 'Nepodařilo se záznam smazat'
    }
}
</script>
