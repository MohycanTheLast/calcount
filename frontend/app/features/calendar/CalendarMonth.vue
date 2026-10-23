<template>
    <section>
        <!-- Měsíc a přepínání -->
        <div class="flex items-center justify-between mb-3">
            <button
                type="button"
                class="cc-icon-btn"
                style="color: rgb(var(--cc-accent));"
                aria-label="Předchozí měsíc"
                @click="goToPrevious"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                    <path d="M15.7 4.3a1 1 0 0 1 0 1.4L9.42 12l6.3 6.3a1 1 0 0 1-1.42 1.4l-7-7a1 1 0 0 1 0-1.4l7-7a1 1 0 0 1 1.4 0Z"/>
                </svg>
            </button>

            <h2 class="text-lg font-semibold tracking-tight first-letter:uppercase">{{ formatMonthTitle(month) }}</h2>

            <button
                type="button"
                class="cc-icon-btn disabled:opacity-30"
                style="color: rgb(var(--cc-accent));"
                aria-label="Další měsíc"
                :disabled="isCurrentMonth"
                @click="goToNext"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                    <path d="M8.3 19.7a1 1 0 0 1 0-1.4l6.28-6.3-6.3-6.3a1 1 0 0 1 1.42-1.4l7 7a1 1 0 0 1 0 1.4l-7 7a1 1 0 0 1-1.4 0Z"/>
                </svg>
            </button>
        </div>

        <p v-if="missingBmr" class="mb-3 text-xs cc-subtle">
            <NuxtLink to="/settings" class="cc-link text-xs">Vyplň BMR</NuxtLink> a uvidíš deficit u jednotlivých dní.
        </p>

        <!-- Mřížka; přejetím prstem doleva/doprava se přepíná měsíc -->
        <div
            class="cc-card p-3 overflow-hidden touch-pan-y"
            :aria-busy="isPending"
            @touchstart.passive="onTouchStart"
            @touchend="onTouchEnd"
        >
            <div class="grid grid-cols-7 gap-1 mb-1 text-center text-xs cc-subtle">
                <div v-for="weekday in WEEKDAYS_MONDAY_FIRST" :key="weekday">{{ weekday }}</div>
            </div>

            <!-- Posun ve směru přepnutí: další měsíc přijede zprava, předchozí zleva (stejně jako detail dne) -->
            <Transition :name="direction === 'next' ? 'cc-push' : 'cc-pop'" mode="out-in">
                <div :key="formatMonthParam(month)" class="grid grid-cols-7 gap-1" :class="{ 'animate-pulse': isPending }">
                    <template v-for="(week, weekIndex) in weeks" :key="weekIndex">
                        <template v-for="(date, dayIndex) in week" :key="`${weekIndex}-${dayIndex}`">
                            <CalendarDayCell
                                v-if="date"
                                :date="date"
                                :day="daysByDate.get(formatIsoDate(date))"
                                :future="date > today"
                            />
                            <div v-else aria-hidden="true" />
                        </template>
                    </template>
                </div>
            </Transition>
        </div>

        <p v-if="isError && !days" class="mt-3 text-sm text-red-400">
            Nepodařilo se načíst data. <button class="cc-link" @click="refetch()">Zkusit znovu</button>
        </p>

        <!-- Legenda -->
        <div class="mt-4 flex flex-wrap items-center gap-x-4 gap-y-2 text-xs cc-subtle">
            <span class="inline-flex items-center gap-1.5"><span class="cc-day-swatch cc-day--deficit" /> deficit</span>
            <span class="inline-flex items-center gap-1.5"><span class="cc-day-swatch cc-day--surplus" /> přebytek</span>
            <span class="inline-flex items-center gap-1.5"><span class="cc-day-dot" /> bez bilance (chybí BMR)</span>
        </div>

        <!-- Krátké shrnutí měsíce -->
        <p v-if="days" class="mt-3 text-xs cc-subtle">
            <template v-if="stats.recordedDays">
                Zapsáno {{ stats.recordedDays }} {{ daysWord(stats.recordedDays) }}
                <template v-if="stats.deficitDays || stats.surplusDays">
                    · {{ stats.deficitDays }} v deficitu · {{ stats.surplusDays }} v přebytku
                </template>
            </template>
            <template v-else>V tomto měsíci nejsou žádné záznamy.</template>
        </p>
    </section>
</template>

<script setup lang="ts">
import { useMonthDaysQuery } from "~/features/calendar/queries";
import { monthStats } from "~/utils/dayStatus";

const props = defineProps<{
    month: Date // první den zobrazeného měsíce
}>()

const emit = defineEmits<{
    (e: 'update:month', month: Date): void
}>()

const month = computed(() => props.month)
const { data: days, isPending, isError, refetch } = useMonthDaysQuery(month)

const today = new Date()
const isCurrentMonth = computed(() =>
    props.month.getFullYear() === today.getFullYear() && props.month.getMonth() === today.getMonth())

const weeks = computed(() => monthGrid(props.month))
const daysByDate = computed(() => new Map((days.value ?? []).map((day) => [day.date, day])))
const stats = computed(() => monthStats(days.value ?? []))
const missingBmr = computed(() => (days.value ?? []).some((day) => day.entryCount > 0 && day.bmrKcal === null))

const daysWord = (count: number) => (count === 1 ? 'den' : count >= 2 && count <= 4 ? 'dny' : 'dní')

// ===== Přepínání měsíců (šipky i přejetí prstem) =====

const direction = ref<'next' | 'previous'>('previous')

const goToPrevious = () => {
    direction.value = 'previous'
    emit('update:month', addMonths(props.month, -1))
}

const goToNext = () => {
    if (isCurrentMonth.value) return
    direction.value = 'next'
    emit('update:month', addMonths(props.month, 1))
}

// Gesto musí být převážně vodorovné a dost dlouhé, aby se nepletlo se svislým posouváním stránky
const SWIPE_MIN_PX = 50
let touchStart: { x: number; y: number } | null = null

const onTouchStart = (e: TouchEvent) => {
    const touch = e.touches[0]
    touchStart = touch && e.touches.length === 1 ? { x: touch.clientX, y: touch.clientY } : null
}

const onTouchEnd = (e: TouchEvent) => {
    const touch = e.changedTouches[0]
    if (!touchStart || !touch) return

    const dx = touch.clientX - touchStart.x
    const dy = touch.clientY - touchStart.y
    touchStart = null

    if (Math.abs(dx) < SWIPE_MIN_PX || Math.abs(dx) < Math.abs(dy) * 1.5) return

    // Doleva = další měsíc (jako listování stránek), doprava = předchozí
    if (dx < 0) goToNext()
    else goToPrevious()
}
</script>
