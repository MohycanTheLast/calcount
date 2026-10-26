<template>
    <div class="cc-page">
        <header class="cc-nav">
            <div class="cc-container cc-header">
                <div>
                    <NuxtLink to="/settings" class="cc-header-action -ml-1">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                            <path d="M15.7 4.3a1 1 0 0 1 0 1.4L9.42 12l6.3 6.3a1 1 0 0 1-1.42 1.4l-7-7a1 1 0 0 1 0-1.4l7-7a1 1 0 0 1 1.4 0Z"/>
                        </svg>
                        Nastavení
                    </NuxtLink>
                </div>
                <h1 class="cc-header-title">Pokročilé</h1>
                <div class="text-right">
                    <button
                        type="button"
                        class="cc-header-action font-semibold"
                        :disabled="saving || !loaded"
                        @click="onSave"
                    >
                        {{ saving ? 'Ukládám…' : 'Uložit' }}
                    </button>
                </div>
            </div>
        </header>

        <main class="cc-container pt-4 pb-16 space-y-6">
            <p v-if="error" class="text-sm text-red-400">
                {{ error }}
            </p>

            <p v-if="loading && !loaded" class="text-sm cc-subtle">
                Načítám nastavení…
            </p>

            <!-- AKTIVNÍ KALORIE -->
            <section>
                <h2 class="cc-section-title">Aktivní kalorie dne</h2>

                <div class="cc-card cc-card-pad space-y-3">
                    <select v-model="settings.activitySource" class="cc-input" aria-label="Aktivní kalorie dne">
                        <option value="static">Vždy průměrná denní aktivita</option>
                        <option value="synced_or_static">Z Apple Zdraví, jinak průměr</option>
                        <option value="synced">Jen z Apple Zdraví</option>
                    </select>

                    <p class="text-xs cc-subtle">
                        Odkud se bere aktivita do denního výdeje. Data z Apple Zdraví posílá Zkratka – nastavíš ji
                        v Propojení níže.
                    </p>
                </div>
            </section>

            <!-- PROPOJENÍ -->
            <section>
                <h2 class="cc-section-title">Propojení</h2>

                <div class="cc-list">
                    <NuxtLink to="/settings/apple-health" class="cc-list-row flex items-center justify-between gap-3 text-base">
                        <span>Apple Zdraví <span class="cc-subtle text-sm">(přes Zkratky)</span></span>
                        <span class="cc-subtle" aria-hidden="true">›</span>
                    </NuxtLink>
                </div>
            </section>

            <!-- HLÍDÁNÍ – ukazatele v Deníku a detailu dne -->
            <section>
                <h2 class="cc-section-title">Hlídání</h2>

                <div class="cc-list">
                    <div class="cc-list-row space-y-3">
                        <label class="flex items-start gap-3 cursor-pointer">
                            <input v-model="settings.hydrationEnabled" type="checkbox" class="cc-checkbox" />
                            <span>
                                <span class="block text-base">Pitný režim</span>
                                <span class="block mt-1 text-xs cc-subtle">Kolik jsi za den vypil(a) oproti cíli.</span>
                            </span>
                        </label>
                        <label v-if="settings.hydrationEnabled" class="relative block pl-8">
                            <span class="block text-sm cc-subtle mb-1">Denní cíl</span>
                            <input
                                v-model.number="waterGoalLiters"
                                type="number"
                                min="0.5"
                                max="6"
                                step="0.1"
                                inputmode="decimal"
                                class="cc-input pr-10"
                                :placeholder="formatLiters(DEFAULT_WATER_GOAL_ML / 1000)"
                            />
                            <span class="absolute right-4 bottom-3.5 cc-subtle text-sm">l</span>
                        </label>
                    </div>

                    <div class="cc-list-row space-y-3">
                        <label class="flex items-start gap-3" :class="hasCaffeine ? 'cursor-pointer' : 'opacity-60'">
                            <input v-model="settings.caffeineEnabled" type="checkbox" class="cc-checkbox" :disabled="!hasCaffeine" />
                            <span>
                                <span class="flex items-center gap-2 text-base">
                                    Kofein
                                    <span v-if="!hasCaffeine" class="cc-chip cc-chip--good">Premium</span>
                                </span>
                                <span class="block mt-1 text-xs cc-subtle">
                                    Kofein z kávy, čaje, energetických nápojů a koly oproti dennímu limitu.
                                </span>
                            </span>
                        </label>
                        <label v-if="hasCaffeine && settings.caffeineEnabled" class="relative block pl-8">
                            <span class="block text-sm cc-subtle mb-1">Denní limit</span>
                            <input
                                v-model.number="settings.caffeineLimitMg"
                                type="number"
                                min="50"
                                max="1000"
                                step="10"
                                inputmode="numeric"
                                class="cc-input pr-14"
                                :placeholder="String(DEFAULT_CAFFEINE_LIMIT_MG)"
                            />
                            <span class="absolute right-4 bottom-3.5 cc-subtle text-sm">mg</span>
                            <span class="block mt-1 text-xs cc-subtle">
                                Pro zdravé dospělé se za bezpečné považuje do 400 mg denně, v těhotenství do 200 mg.
                            </span>
                        </label>
                    </div>

                    <label class="cc-list-row flex items-start gap-3" :class="hasMacros ? 'cursor-pointer' : 'opacity-60'">
                        <input v-model="settings.macrosEnabled" type="checkbox" class="cc-checkbox" :disabled="!hasMacros" />
                        <span>
                            <span class="flex items-center gap-2 text-base">
                                Makroživiny
                                <span v-if="!hasMacros" class="cc-chip cc-chip--good">Premium</span>
                            </span>
                            <span class="block mt-1 text-xs cc-subtle">
                                Bílkoviny, sacharidy (z toho cukry) a tuky u záznamů i za celý den.
                            </span>
                        </span>
                    </label>
                </div>

                <p class="mt-2 px-1 text-xs cc-subtle">
                    Kofein i makroživiny odhaduje AI z textu nebo fotky stejně jako kalorie – ber je jako orientační.
                </p>
            </section>

            <!-- FOTKY -->
            <section>
                <h2 class="cc-section-title">Vkládání obrázků</h2>

                <label class="cc-card cc-card-pad flex items-start gap-3 cursor-pointer">
                    <input v-model="settings.photoGalleryEnabled" type="checkbox" class="cc-checkbox" />
                    <span>
                        <span class="block text-base">Výběr fotky z galerie</span>
                        <span class="block mt-1 text-xs cc-subtle">
                            U zadávání jídla přibude vedle fotoaparátu tlačítko pro fotku vyfocenou dřív –
                            jídlo tak můžeš zapsat i později.
                        </span>
                    </span>
                </label>
            </section>
        </main>
    </div>
</template>

<script setup lang="ts">
import { useCurrentUserQuery } from "~/features/account/useAccount";
import { useDiaryMutations } from "~/features/diary/queries";
import { DEFAULT_CAFFEINE_LIMIT_MG, DEFAULT_WATER_GOAL_ML } from "~/utils/tracking";

// Méně častá nastavení – základní Nastavení zůstává jednoduché. Ukládá se stejný stav jako tam.
definePageMeta({
    middleware: ['auth'],
})

const { settings, loaded, loading, saving, error, fetchFromApi, saveToApi } = useUserSettings()
const { invalidateDiary } = useDiaryMutations()
const { data: currentUser } = useCurrentUserQuery()

// Kofein a makroživiny jen pro typy účtů, které je mají (backend je jinak nezapne)
const hasCaffeine = computed(() => currentUser.value?.features.includes('caffeine') ?? false)
const hasMacros = computed(() => currentUser.value?.features.includes('macros') ?? false)

// Cíl pitného režimu se zadává v litrech, ukládá v ml; prázdné pole = výchozí cíl
const waterGoalLiters = computed<number | null>({
    get: () => (settings.value.waterGoalMl != null ? settings.value.waterGoalMl / 1000 : null),
    set: (liters) => {
        settings.value.waterGoalMl = typeof liters === 'number' && liters > 0 ? Math.round(liters * 1000) : null
    },
})

onMounted(() => {
    if (!loaded.value) fetchFromApi().catch(() => {})
})

const onSave = async () => {
    try {
        await saveToApi()
        // Zdroj aktivních kalorií mění výdej dne – souhrny v Deníku se musí přepočítat
        invalidateDiary()
        await navigateTo('/settings')
    } catch {
        // error je už nastavený v composable
    }
}
</script>
