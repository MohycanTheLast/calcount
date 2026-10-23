<template>
    <div>
        <div class="flex items-baseline gap-1.5">
            <span class="text-3xl font-semibold tracking-tight">{{ formatKcal(kcal) }}</span>
            <span class="text-sm cc-subtle">
                <template v-if="expenditureKcal">/ {{ formatKcal(expenditureKcal) }}</template>
                kcal
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

            <span class="cc-subtle whitespace-nowrap">💧 {{ formatLiters(waterLiters) }} l</span>
        </div>
    </div>
</template>

<script setup lang="ts">
import { dayBalance } from "~/utils/balance";

const props = defineProps<{
    kcal: number
    waterLiters: number
    // Denní výdej ze serveru (BMR + aktivní kalorie); bez něj se deficit nezobrazí
    expenditureKcal: number | null
}>()

const balance = computed(() => dayBalance(props.kcal, props.expenditureKcal))
</script>
