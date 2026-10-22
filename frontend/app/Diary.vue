<script setup lang="ts">
import { computed, nextTick, onMounted, ref, watch } from "vue";
import { useEntries } from "~/composables/useEntries";
import { useDayEntries } from "~/composables/useDayEntries";

const props = defineProps<{
    refreshToken: number;
}>();

// Použití `useEntries` pro načítání všech záznamů
const { entries, loading, error, fetchEntries } = useEntries();

// Použití `useDayEntries` pro načítání detailů
const { dayEntries, loading: loadingDayDetails, error: errorDayDetails, fetchDayEntries } = useDayEntries();

// Správa popupu
const showDetails = ref(false);
const selectedDate = ref<string | null>(null);

// Otevření popupu a načtení dat
const openDetails = async (date: string) => {
    const [day, month, year] = date.split(".");
    const formattedDate = `${year}-${month}-${day}`;

    selectedDate.value = date;
    showDetails.value = true;

    document.body.classList.add("overflow-hidden");
    await fetchDayEntries(formattedDate);
};

// Zavření popupu
const closeDetails = () => {
    showDetails.value = false;
    selectedDate.value = null;
    document.body.classList.remove("overflow-hidden");
};

// Emit pro předání záznamů do komponenty Main
const emit = defineEmits<{
    (e: "update-entry", payload: { id: bigint; kcal: number; water: number; text: string }): void;
}>();

const editEntry = (item: { id: bigint; kcal: number; water: number; text: string }) => {
    emit("update-entry", {
        id: item.id,
        kcal: item.kcal,
        water: item.water,
        text: item.text,
    });
    closeDetails();
};

// ===== iOS Health swipe denní karty =====

const scrollerEl = ref<HTMLElement | null>(null);

const parseCzDate = (s: string): Date | null => {
    const parts = s.split(".");
    if (parts.length !== 3) return null;
    const d = Number(parts[0]);
    const m = Number(parts[1]);
    const y = Number(parts[2]);
    if (!Number.isFinite(d) || !Number.isFinite(m) || !Number.isFinite(y)) return null;
    return new Date(y, m - 1, d);
};

const formatCzDate = (d: Date) => {
    const dd = String(d.getDate()).padStart(2, "0");
    const mm = String(d.getMonth() + 1).padStart(2, "0");
    const yyyy = d.getFullYear();
    return `${dd}.${mm}.${yyyy}`;
};

const isSameDay = (a: Date, b: Date) =>
    a.getFullYear() === b.getFullYear() && a.getMonth() === b.getMonth() && a.getDate() === b.getDate();

const dayLabel = (czDateString: string) => {
    const dt = parseCzDate(czDateString);
    if (!dt) return czDateString;

    const today = new Date();
    const yesterday = new Date();
    yesterday.setDate(today.getDate() - 1);

    if (isSameDay(dt, today)) return "Dnes";
    if (isSameDay(dt, yesterday)) return "Včera";
    return czDateString;
};

const DAYS_WINDOW = 14;

type DayCard = {
    date: string;
    exists: boolean;
    kcal: number;
    kcal_burned: number;
    water: number;
};

const dayCards = computed<DayCard[]>(() => {
    const map = new Map<string, any>();
    for (const e of entries.value as any[]) {
        if (e?.date) map.set(e.date, e);
    }

    const today = new Date();
    const days: DayCard[] = [];

    for (let i = DAYS_WINDOW - 1; i >= 0; i--) {
        const d = new Date(today);
        d.setDate(today.getDate() - i);
        const cz = formatCzDate(d);

        const entry = map.get(cz);
        if (entry) {
            days.push({
                date: cz,
                exists: true,
                kcal: Number(entry.kcal ?? 0),
                kcal_burned: Number(entry.kcal_burned ?? 0),
                water: Number(entry.water ?? 0),
            });
        } else {
            days.push({
                date: cz,
                exists: false,
                kcal: 0,
                kcal_burned: 0,
                water: 0,
            });
        }
    }

    return days;
});

const scrollToTodayCard = async () => {
    await nextTick();
    const scroller = scrollerEl.value;
    if (!scroller) return;

    const indexToday = dayCards.value.length - 1;
    const card = scroller.querySelector<HTMLElement>(`[data-day-index="${indexToday}"]`);
    if (card) card.scrollIntoView({ behavior: "instant" as ScrollBehavior, inline: "center", block: "nearest" });
};

const refresh = async () => {
    await fetchEntries();
    await scrollToTodayCard();
};

onMounted(async () => {
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
    <div class="w-full mt-2">
        <div v-if="loading" class="text-sm text-slate-500 dark:text-slate-400 p-4">
            Načítám…
        </div>

        <div v-else-if="error" class="text-sm text-red-600 p-4">
            {{ error }}
        </div>

        <div v-else>
            <div
                ref="scrollerEl"
                class="overflow-x-auto scroll-ios -mx-4 px-4 py-2 flex gap-4 snap-x snap-mandatory"
                style="scrollbar-width: none;"
            >
                <section
                    v-for="(card, index) in dayCards"
                    :key="card.date"
                    class="cc-card cc-card-pad w-[88%] shrink-0 snap-center"
                    :data-day-index="index"
                >
                    <header class="flex items-start justify-between">
                        <div>
                            <div class="text-sm cc-subtle">
                                {{ dayLabel(card.date) }}
                            </div>
                            <div v-if="dayLabel(card.date) !== card.date" class="text-xs cc-subtle mt-0.5">
                                {{ card.date }}
                            </div>
                        </div>

                        <button
                            v-if="card.exists"
                            class="cc-link"
                            @click="openDetails(card.date)"
                        >
                            Detail
                        </button>
                        <span v-else class="text-xs cc-subtle">
                            Bez záznamu
                        </span>
                    </header>

                    <div class="mt-5 grid grid-cols-2 gap-3">
                        <div
                            class="rounded-3xl border p-4"
                            style="border-color: rgb(var(--cc-border)); background: rgb(var(--cc-surface));"
                        >
                            <div class="text-xs cc-subtle">Příjem</div>
                            <div class="mt-1 text-2xl font-semibold tracking-tight">
                                {{ card.kcal }}
                            </div>
                            <div class="text-xs cc-subtle">kcal</div>
                        </div>

                        <div
                            class="rounded-3xl border p-4"
                            style="border-color: rgb(var(--cc-border)); background: rgb(var(--cc-surface));"
                        >
                            <div class="text-xs cc-subtle">Výdej</div>
                            <div class="mt-1 text-2xl font-semibold tracking-tight">
                                {{ card.kcal_burned }}
                            </div>
                            <div class="text-xs cc-subtle">kcal</div>
                        </div>
                    </div>

                    <div class="mt-4 flex items-center justify-between text-sm">
                        <div class="cc-subtle">Pitný režim</div>
                        <div class="font-medium">
                            {{ card.water }} l
                        </div>
                    </div>
                </section>
            </div>
        </div>

        <!-- Popup pro detailní tabulku (ponecháno) -->
        <div
            v-if="showDetails"
            class="fixed inset-0 z-50 bg-black/60 flex items-end sm:items-center justify-center overflow-hidden"
        >
            <div
                class="w-full sm:max-w-md max-h-[92vh]
                       bg-white dark:bg-slate-950
                       rounded-t-3xl sm:rounded-3xl
                       border border-white/10
                       shadow-2xl flex flex-col overflow-hidden relative"
            >
                <button
                    @click="closeDetails"
                    class="absolute top-4 right-4 text-slate-500 hover:text-red-500 z-10"
                >
                    ✖
                </button>

                <h2 class="text-lg font-semibold p-4 border-b border-slate-200 dark:border-white/10">
                    Záznam za den: {{ selectedDate }}
                </h2>

                <div class="p-4 overflow-x-auto overflow-y-auto scroll-ios max-h-full flex-1">
                    <div v-if="loadingDayDetails" class="text-center text-slate-500 dark:text-slate-400">
                        Načítám záznamy...
                    </div>

                    <div v-else-if="errorDayDetails" class="text-center text-red-500">
                        {{ errorDayDetails }}
                    </div>

                    <table v-else class="w-full text-sm border-collapse table-auto">
                        <thead>
                        <tr class="bg-slate-100 dark:bg-white/5 text-left">
                            <th class="px-4 py-2 font-medium">Popis</th>
                            <th class="px-4 py-2 font-medium">Příjem (kcal)</th>
                            <th class="px-4 py-2 font-medium">Pitný režim (l)</th>
                            <th class="px-4 py-2 font-medium text-right"></th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr
                            v-for="(item, index) in dayEntries"
                            :key="index"
                            class="border-t border-slate-200 dark:border-white/10"
                        >
                            <td class="px-4 py-2 break-words max-w-[260px] whitespace-pre-wrap">
                                {{ item.text }}
                            </td>
                            <td class="px-4 py-2">{{ item.kcal }}</td>
                            <td class="px-4 py-2">{{ item.water }}</td>
                            <td class="px-4 py-2 text-right">
                                <button
                                    @click="editEntry(item)"
                                    class="text-emerald-700 dark:text-emerald-300 hover:underline"
                                >
                                    Upravit
                                </button>
                            </td>
                        </tr>

                        <tr v-if="dayEntries.length === 0">
                            <td colspan="4" class="px-4 py-2 text-center text-slate-500 dark:text-slate-400">
                                Žádné záznamy k zobrazení
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </div>

                <div class="pb-safe"></div>
            </div>
        </div>
    </div>
</template>