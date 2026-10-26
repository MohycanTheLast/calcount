<template>
    <div>
        <div class="flex items-baseline gap-1.5">
            <span class="text-3xl font-semibold tracking-tight">{{ formatKcal(kcal) }}</span>
            <span class="text-sm cc-subtle">
                <template v-if="expenditureKcal">/ {{ formatKcal(expenditureKcal) }}</template>
                kcal
            </span>
            <!-- Odkud jsou aktivní kalorie ve výdeji: hodinky = Apple Zdraví, tužka = ruční úprava dne -->
            <span
                v-if="expenditureKcal && sourceIcon"
                class="self-center cc-subtle"
                :title="`Aktivní kalorie: ${activeSourceLabel(activeSource!)}`"
                :aria-label="`Aktivní kalorie: ${activeSourceLabel(activeSource!)}`"
                role="img"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                    <path :d="sourceIcon" />
                </svg>
            </span>
        </div>

        <div v-if="balance" class="cc-progress mt-3" role="presentation">
            <div
                class="cc-progress-bar"
                :class="{ 'cc-progress-bar--over': balance.isOver }"
                :style="{ width: `${balance.progress * 100}%` }"
            />
        </div>

        <div class="mt-3 flex items-center justify-between gap-3 text-sm">
            <span v-if="balance" class="cc-chip" :class="balance.isOver ? 'cc-chip--warn' : 'cc-chip--good'">
                {{ balance.isOver ? 'Přebytek' : 'Deficit' }} {{ formatKcal(balance.differenceKcal) }} kcal
            </span>
            <slot v-else name="no-expenditure" />

            <span v-if="!hideWater" class="cc-subtle whitespace-nowrap">💧 {{ formatLiters(waterLiters) }} l</span>
        </div>
    </div>
</template>

<script setup lang="ts">
import type { DaySummary } from "~/api/types";
import { activeSourceLabel } from "~/utils/activity";
import { dayBalance } from "~/utils/balance";

const props = defineProps<{
    kcal: number
    waterLiters: number
    // Denní výdej ze serveru (BMR + aktivní kalorie); bez něj se deficit nezobrazí
    expenditureKcal: number | null
    // Zdroj aktivních kalorií ve výdeji – u synchronizovaných a ručně upravených se ukáže ikona
    activeSource?: DaySummary['activeSource']
    // Pitný režim ukazuje DayTracking s cílem (hlídání zapnuté) – tady by byl dvakrát
    hideWater?: boolean
}>()

const balance = computed(() => dayBalance(props.kcal, props.expenditureKcal))

const SOURCE_ICONS: Partial<Record<DaySummary['activeSource'], string>> = {
    // hodinky
    apple_health: 'M9 1a1 1 0 0 0-.98.8L7.4 4.6A3 3 0 0 0 5 7.5v9a3 3 0 0 0 2.4 2.94l.62 2.76A1 1 0 0 0 9 23h6a1 1 0 0 0 .98-.8l.62-2.76A3 3 0 0 0 19 16.5v-9a3 3 0 0 0-2.4-2.94l-.62-2.76A1 1 0 0 0 15 1H9Zm3 6.5a1 1 0 0 1 1 1v3.09l1.7 1.7a1 1 0 0 1-1.4 1.42l-2-2A1 1 0 0 1 11 12V8.5a1 1 0 0 1 1-1Z',
    // tužka
    manual: 'M16.3 2.3a2.4 2.4 0 0 1 3.4 0l2 2a2.4 2.4 0 0 1 0 3.4L9.4 20H4v-5.4L16.3 2.3ZM6 15.4V18h2.6l9.2-9.2-2.6-2.6L6 15.4Z',
}

const sourceIcon = computed(() => (props.activeSource ? SOURCE_ICONS[props.activeSource] : undefined))
</script>
