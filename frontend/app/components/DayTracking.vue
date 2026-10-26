<template>
    <!-- Hlídání pitného režimu, kofeinu a makroživin – jen to, co má uživatel zapnuté v pokročilém nastavení -->
    <div v-if="tracking.hydration || tracking.caffeine || tracking.macros" class="space-y-3">
        <div v-if="tracking.hydration">
            <div class="flex items-center justify-between gap-3 text-xs">
                <span class="cc-subtle">💧 Pitný režim</span>
                <span class="tabular-nums">
                    {{ formatLiters(waterMl / 1000) }} / {{ formatLiters(tracking.waterGoalMl / 1000) }} l
                </span>
            </div>
            <div
                class="cc-progress cc-progress--thin mt-1.5"
                role="progressbar"
                aria-label="Pitný režim"
                :aria-valuenow="Math.round(water.ratio * 100)"
                aria-valuemin="0"
                aria-valuemax="100"
            >
                <div class="cc-progress-bar cc-progress-bar--water" :style="{ width: `${water.ratio * 100}%` }" />
            </div>
        </div>

        <div v-if="tracking.caffeine">
            <div class="flex items-center justify-between gap-3 text-xs">
                <span class="cc-subtle">☕ Kofein</span>
                <span class="tabular-nums" :class="{ 'cc-text-warn font-semibold': caffeine.over }">
                    {{ Math.round(caffeineMg) }} / {{ tracking.caffeineLimitMg }} mg
                </span>
            </div>
            <div
                class="cc-progress cc-progress--thin mt-1.5"
                role="progressbar"
                aria-label="Kofein"
                :aria-valuenow="Math.round(caffeine.ratio * 100)"
                aria-valuemin="0"
                aria-valuemax="100"
            >
                <div
                    class="cc-progress-bar cc-progress-bar--caffeine"
                    :class="{ 'cc-progress-bar--over': caffeine.over }"
                    :style="{ width: `${caffeine.ratio * 100}%` }"
                />
            </div>
        </div>

        <dl v-if="tracking.macros" class="grid grid-cols-4 gap-2 text-center">
            <!-- Hodnota nad popiskem; v DOM popisek první kvůli čtečkám -->
            <div v-for="macro in MACRO_LABELS" :key="macro.key" class="flex flex-col-reverse">
                <dt class="text-[11px] cc-subtle">{{ macro.label }}</dt>
                <dd class="text-sm font-semibold tabular-nums">{{ Math.round(macros[macro.key]) }} g</dd>
            </div>
        </dl>

        <p v-if="(tracking.caffeine || tracking.macros) && missingCount > 0" class="text-[11px] cc-subtle">
            {{ missingText }} odhad kofeinu a makroživin.
        </p>
    </div>
</template>

<script setup lang="ts">
import { useTracking } from "~/features/account/useAccount";
import { goalProgress, MACRO_LABELS, type MacroKey } from "~/utils/tracking";

const props = defineProps<{
    waterMl: number
    caffeineMg: number
    macros: Record<MacroKey, number>
    // Kolik záznamů dne je a kolik z nich má odhad makroživin a kofeinu (starší ho nemají)
    entryCount: number
    nutrientsEntryCount: number
}>()

const tracking = useTracking()

const water = computed(() => goalProgress(props.waterMl, tracking.value.waterGoalMl))
const caffeine = computed(() => goalProgress(props.caffeineMg, tracking.value.caffeineLimitMg))
const missingCount = computed(() => Math.max(0, props.entryCount - props.nutrientsEntryCount))
const missingText = computed(() => {
    const n = missingCount.value
    if (n === 1) return '1 starší záznam nemá'
    return n < 5 ? `${n} starší záznamy nemají` : `${n} starších záznamů nemá`
})
</script>
