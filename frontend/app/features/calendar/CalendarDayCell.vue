<template>
    <button
        v-if="clickable"
        type="button"
        class="cc-day"
        :class="classes"
        :aria-label="ariaLabel"
        @click="navigateTo(`/day/${formatIsoDate(date)}`)"
    >
        <span>{{ date.getDate() }}</span>
        <span v-if="status === 'unknown'" class="cc-day-dot" aria-hidden="true" />
    </button>
    <div v-else class="cc-day" :class="classes" :aria-label="ariaLabel">
        <span>{{ date.getDate() }}</span>
    </div>
</template>

<script setup lang="ts">
import type { DaySummary } from "~/api/types";
import { dayStatus } from "~/utils/dayStatus";

// Jeden den v mřížce kalendáře – barva podle bilance, klepnutí otevře detail dne (i prázdného – dá se do něj
// zpětně zapsat; budoucí dny ne)
const props = defineProps<{
    date: Date
    day?: DaySummary
    future: boolean
}>()

const status = computed(() => (props.day ? dayStatus(props.day) : 'none'))
const clickable = computed(() => !props.future)

const classes = computed(() => ({
    'cc-day--deficit': status.value === 'deficit',
    'cc-day--surplus': status.value === 'surplus',
    'cc-day--today': isToday(props.date),
    'cc-day--future': props.future,
}))

const ariaLabel = computed(() => {
    const balance = props.day?.balanceKcal ?? 0
    const detail = {
        none: 'bez záznamu',
        unknown: 'zapsáno, bilance neznámá',
        deficit: `deficit ${formatKcal(Math.abs(balance))} kcal`,
        surplus: `přebytek ${formatKcal(balance)} kcal`,
    }[status.value]

    return `${dayTitle(props.date)}: ${detail}`
})
</script>
