<template>
    <li class="cc-list-row">
        <!-- ZOBRAZENÍ -->
        <div v-if="!editing" class="flex items-start gap-3">
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
                @click="emit('edit')"
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

            <p v-if="error" class="text-sm text-red-400">{{ error }}</p>

            <div class="flex items-center justify-between">
                <button type="button" class="cc-btn-danger-text" :disabled="busy" @click="onDelete">
                    Smazat
                </button>

                <div class="flex items-center gap-4">
                    <button type="button" class="cc-subtle text-sm" :disabled="busy" @click="emit('cancel')">
                        Zrušit
                    </button>
                    <button type="button" class="cc-btn-primary px-5 py-2" :disabled="busy" @click="onSave">
                        Uložit
                    </button>
                </div>
            </div>
        </div>
    </li>
</template>

<script setup lang="ts">
import type { DayEntry } from "~/api/types";
import { useDiaryMutations } from "~/features/diary/queries";

// Řádek záznamu v detailu dne. Upravovat jde vždy jen jeden řádek – o tom rozhoduje stránka (prop editing).
const props = defineProps<{
    entry: DayEntry
    editing: boolean
}>()

const emit = defineEmits<{
    (e: 'edit'): void
    (e: 'cancel'): void
    (e: 'done'): void
}>()

const { updateEntry, deleteEntry } = useDiaryMutations()

const editKcal = ref<number | null>(null)
const editWaterL = ref<number | null>(null)
const error = ref<string | null>(null)
const busy = computed(() => updateEntry.isPending.value || deleteEntry.isPending.value)

watch(() => props.editing, (editing) => {
    if (!editing) return
    editKcal.value = Number(props.entry.kcal)
    editWaterL.value = Number(props.entry.water) / 1000
    error.value = null
}, { immediate: true })

const onSave = async () => {
    if (editKcal.value == null || editWaterL.value == null) return

    error.value = null
    try {
        await updateEntry.mutateAsync({
            id: props.entry.id,
            values: { kcal: editKcal.value, waterInMl: Math.round(editWaterL.value * 1000) },
        })
        emit('done')
    } catch {
        error.value = 'Nepodařilo se záznam uložit'
    }
}

const onDelete = async () => {
    if (!window.confirm('Opravdu smazat tento záznam?')) return

    error.value = null
    try {
        await deleteEntry.mutateAsync(props.entry.id)
        emit('done')
    } catch {
        error.value = 'Nepodařilo se záznam smazat'
    }
}
</script>
