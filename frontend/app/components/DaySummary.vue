<template>
    <div>
        <div class="flex items-baseline gap-1.5">
            <span class="text-3xl font-semibold tracking-tight">{{ formatKcal(kcal) }}</span>
            <span class="text-sm cc-subtle">
                <template v-if="expenditureKcal">/ {{ formatKcal(expenditureKcal) }}</template>
                kcal
            </span>
        </div>

        <div v-if="expenditureKcal" class="cc-progress mt-3" role="presentation">
            <div
                class="cc-progress-bar"
                :class="{ 'cc-progress-bar--over': isOver }"
                :style="{ width: `${Math.min(kcal / expenditureKcal, 1) * 100}%` }"
            />
        </div>

        <div class="mt-3 flex items-center justify-between gap-3 text-sm">
            <span v-if="expenditureKcal" class="cc-chip" :class="isOver ? 'cc-chip--warn' : 'cc-chip--good'">
                {{ isOver ? 'Přebytek' : 'Deficit' }} {{ formatKcal(Math.abs(expenditureKcal - kcal)) }} kcal
            </span>
            <slot v-else name="no-expenditure" />

            <span class="cc-subtle whitespace-nowrap">💧 {{ formatLiters(waterLiters) }} l</span>
        </div>
    </div>
</template>

<script setup lang="ts">
const props = defineProps<{
    kcal: number
    waterLiters: number
    // Denní výdej (BMR + aktivita); bez něj se deficit nezobrazí
    expenditureKcal: number | null
}>()

const isOver = computed(() => props.expenditureKcal != null && props.kcal > props.expenditureKcal)
</script>
