<script setup lang="ts">
import { computed, nextTick, onMounted, ref, watch } from "vue";
import { useEntries } from "~/composables/useEntries";

const props = defineProps<{
    refreshToken: number;
}>();

const { entries, loaded, loading, error, fetchEntries } = useEntries();
const { settings, loaded: settingsLoaded, dailyExpenditureKcal, ensureLoaded } = useUserSettings();

// ===== iOS Health swipe denní karty =====

const scrollerEl = ref<HTMLElement | null>(null);

const DAYS_WINDOW = 14;

type DayCard = {
    date: Date;
    label: string;
    exists: boolean;
    kcal: number;
    water: number;
};

const dayCards = computed<DayCard[]>(() => {
    const byDate = new Map(entries.value.map((e) => [e.date, e]));

    const today = new Date();
    const days: DayCard[] = [];

    for (let i = DAYS_WINDOW - 1; i >= 0; i--) {
        const d = new Date(today);
        d.setDate(today.getDate() - i);

        const entry = byDate.get(formatCzDate(d));
        days.push({
            date: d,
            label: dayLabel(d),
            exists: !!entry,
            kcal: Number(entry?.kcal ?? 0),
            water: Number(entry?.water ?? 0),
        });
    }

    return days;
});

// Po vyplnění BMR se ukáže deficit; dokud chybí, nabídneme odkaz do nastavení
const missingBmr = computed(() => settingsLoaded.value && !settings.value.bmrKcal);

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
    navigateTo(`/day/${formatIsoDate(card.date)}`);
};

const refresh = async () => {
    await fetchEntries();
    await scrollToTodayCard();
};

onMounted(async () => {
    // Data z minula (sdílený stav) se zobrazí hned, čerstvá se načtou na pozadí
    if (loaded.value) await scrollToTodayCard();
    ensureLoaded();
    await refresh();
});

watch(
    () => props.refreshToken,
    async () => {
        await refresh();
    }
);
</script>

<template>
    <div class="w-full">
        <!-- Kostra karty jen při úplně prvním načtení – při návratu na obrazovku se ukážou data z paměti -->
        <div v-if="!loaded && loading" class="cc-card cc-card-pad w-[88%] animate-pulse" aria-busy="true">
            <div class="h-4 w-16 rounded-full" style="background: rgb(var(--cc-border));"></div>
            <div class="mt-5 h-8 w-32 rounded-full" style="background: rgb(var(--cc-border));"></div>
            <div class="mt-4 h-2 w-full rounded-full" style="background: rgb(var(--cc-border));"></div>
        </div>

        <div v-else-if="!loaded && error" class="cc-card cc-card-pad text-sm">
            <p class="text-red-400">{{ error }}</p>
            <button class="cc-link mt-2" @click="refresh">Zkusit znovu</button>
        </div>

        <div
            v-else
            ref="scrollerEl"
            class="overflow-x-auto scroll-ios -mx-4 px-4 py-2 flex gap-3 snap-x snap-mandatory"
            style="scrollbar-width: none;"
        >
            <section
                v-for="(card, index) in dayCards"
                :key="card.label + index"
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
                    :expenditure-kcal="dailyExpenditureKcal"
                >
                    <template #no-expenditure>
                        <NuxtLink
                            v-if="missingBmr && index === dayCards.length - 1"
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
