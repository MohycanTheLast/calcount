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
    };
};

// Backend vrací každý den v rozsahu vzestupně – poslední karta je dnešek
const dayCards = computed<DayCard[]>(() => (days.value ?? []).map(toCard));

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
