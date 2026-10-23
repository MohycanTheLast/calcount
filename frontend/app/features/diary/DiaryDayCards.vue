<script setup lang="ts">
import { computed, nextTick, onMounted, ref, watch } from "vue";
import type { DaySummary } from "~/api/types";
import { useRecentDaysQuery } from "~/features/diary/queries";

// ===== iOS Health swipe denní karty (souhrny dní ze serveru) =====

const { data: days, isPending, isError, refetch } = useRecentDaysQuery();

const scrollerEl = ref<HTMLElement | null>(null);

type DayCard = {
    date: Date;
    isoDate: string;
    label: string;
    exists: boolean;
    kcal: number;
    water: number; // litry
    expenditureKcal: number | null;
    missingBmr: boolean;
    activeSource: DaySummary["activeSource"];
};

const toCard = (day: DaySummary): DayCard => {
    const date = parseIsoDate(day.date) ?? new Date();
    return {
        date,
        isoDate: day.date,
        label: dayLabel(date),
        exists: day.entryCount > 0,
        kcal: day.kcal,
        water: day.waterMl / 1000,
        expenditureKcal: day.expenditureKcal,
        missingBmr: day.bmrKcal === null,
        activeSource: day.activeSource,
    };
};

// Backend vrací každý den v rozsahu vzestupně – poslední karta je dnešek
const dayCards = computed<DayCard[]>(() => (days.value ?? []).map(toCard));

// Měsíc dne před nejstarší kartou – tam vede odkaz „Starší dny v kalendáři“
const olderDaysMonth = computed(() => {
    const oldest = dayCards.value[0];
    return oldest ? formatMonthParam(addDays(oldest.date, -1)) : null;
});

const scrollToTodayCard = async () => {
    await nextTick();
    const scroller = scrollerEl.value;
    if (!scroller) return;

    const indexToday = dayCards.value.length - 1;
    const card = scroller.querySelector<HTMLElement>(`[data-day-index="${indexToday}"]`);
    if (card) card.scrollIntoView({ behavior: "instant" as ScrollBehavior, inline: "center", block: "nearest" });
};

const openDetail = (card: DayCard) => {
    if (!card.exists) return;
    navigateTo(`/day/${card.isoDate}`);
};

// Data z cache se ukážou hned (návrat na obrazovku), jinak se srolluje na dnešek po prvním načtení
onMounted(() => {
    if (days.value) scrollToTodayCard();
});

watch(days, (value, previous) => {
    if (value && !previous) scrollToTodayCard();
});
</script>

<template>
    <div class="w-full">
        <!-- Kostra karty jen při úplně prvním načtení – při návratu na obrazovku se ukážou data z cache -->
        <div v-if="isPending" class="cc-card cc-card-pad w-[88%] animate-pulse" aria-busy="true">
            <div class="h-4 w-16 rounded-full" style="background: rgb(var(--cc-border));"></div>
            <div class="mt-5 h-8 w-32 rounded-full" style="background: rgb(var(--cc-border));"></div>
            <div class="mt-4 h-2 w-full rounded-full" style="background: rgb(var(--cc-border));"></div>
        </div>

        <div v-else-if="isError && !days" class="cc-card cc-card-pad text-sm">
            <p class="text-red-400">Nepodařilo se načíst data</p>
            <button class="cc-link mt-2" @click="refetch()">Zkusit znovu</button>
        </div>

        <div
            v-else
            ref="scrollerEl"
            class="overflow-x-auto scroll-ios -mx-4 px-4 py-2 flex gap-3 snap-x snap-mandatory"
            style="scrollbar-width: none;"
        >
            <!-- Za nejstarší kartou (vlevo) proklik do kalendáře na měsíc předchozího dne -->
            <NuxtLink
                v-if="olderDaysMonth"
                :to="`/calendar?m=${olderDaysMonth}`"
                class="cc-card cc-card-pad w-[40%] shrink-0 snap-center flex flex-col items-center justify-center gap-2 text-center transition active:scale-[0.99]"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7" style="color: rgb(var(--cc-accent));" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                    <path d="M7 2a1 1 0 0 1 1 1v1h8V3a1 1 0 1 1 2 0v1h1a3 3 0 0 1 3 3v12a3 3 0 0 1-3 3H5a3 3 0 0 1-3-3V7a3 3 0 0 1 3-3h1V3a1 1 0 0 1 1-1Zm13 8H4v9a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1v-9ZM8 13a1.25 1.25 0 1 1 0 2.5A1.25 1.25 0 0 1 8 13Zm4 0a1.25 1.25 0 1 1 0 2.5 1.25 1.25 0 0 1 0-2.5Zm4 0a1.25 1.25 0 1 1 0 2.5 1.25 1.25 0 0 1 0-2.5Z"/>
                </svg>
                <span class="cc-link no-underline">‹ Starší dny v kalendáři</span>
            </NuxtLink>

            <section
                v-for="(card, index) in dayCards"
                :key="card.isoDate"
                class="cc-card cc-card-pad w-[88%] shrink-0 snap-center transition active:scale-[0.99]"
                :class="{ 'cursor-pointer': card.exists }"
                :data-day-index="index"
                :role="card.exists ? 'button' : undefined"
                :tabindex="card.exists ? 0 : undefined"
                @click="openDetail(card)"
                @keydown.enter="openDetail(card)"
            >
                <header class="flex items-center justify-between">
                    <div class="text-sm font-semibold">{{ card.label }}</div>

                    <span v-if="card.exists" class="cc-link no-underline">Detail ›</span>
                    <span v-else class="text-xs cc-subtle">Bez záznamu</span>
                </header>

                <DaySummary
                    class="mt-4"
                    :kcal="card.kcal"
                    :water-liters="card.water"
                    :expenditure-kcal="card.expenditureKcal"
                    :active-source="card.activeSource"
                >
                    <template #no-expenditure>
                        <NuxtLink
                            v-if="card.missingBmr && index === dayCards.length - 1"
                            to="/settings"
                            class="cc-link text-xs"
                            @click.stop
                        >
                            Vyplň BMR a uvidíš deficit
                        </NuxtLink>
                        <span v-else />
                    </template>
                </DaySummary>
            </section>
        </div>
    </div>
</template>
