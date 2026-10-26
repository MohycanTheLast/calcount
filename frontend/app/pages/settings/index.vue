<template>
    <div class="cc-page">
        <header class="cc-nav">
            <div class="cc-container cc-header">
                <div></div>
                <h1 class="cc-header-title">Nastavení</h1>
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

        <main class="cc-container pt-4 pb-28 space-y-6">
            <p v-if="error" class="text-sm text-red-400">
                {{ error }}
            </p>

            <p v-if="loading && !loaded" class="text-sm cc-subtle">
                Načítám nastavení…
            </p>

            <!-- METABOLISMUS -->
            <section>
                <h2 class="cc-section-title">Metabolismus</h2>

                <div class="cc-card cc-card-pad space-y-4">
                    <label class="block">
                        <div class="flex items-center justify-between gap-3 mb-1">
                            <div class="text-sm cc-subtle">BMR (bazální metabolismus), kcal / den</div>

                            <button type="button" class="cc-link" @click.prevent="openBmrCalc">
                                Spočítat
                            </button>
                        </div>

                        <input
                            v-model.number="settings.bmrKcal"
                            type="number"
                            min="0"
                            inputmode="numeric"
                            class="cc-input"
                            placeholder="Např. 1650"
                        />
                    </label>

                    <label class="block">
                        <div class="text-sm cc-subtle mb-1">Průměrná denní aktivita, kcal / den</div>
                        <input
                            v-model.number="settings.avgDailyActivityKcal"
                            type="number"
                            min="0"
                            inputmode="numeric"
                            class="cc-input"
                            placeholder="Např. 400"
                        />
                    </label>

                    <p class="text-xs cc-subtle">
                        Denní výdej = BMR + aktivita. Z něj se v Deníku počítá kalorický deficit.
                        Aktivitu konkrétního dne můžeš upravit v jeho detailu.
                    </p>
                </div>
            </section>

            <!-- VZHLED -->
            <section>
                <h2 class="cc-section-title">Vzhled aplikace</h2>

                <div class="cc-option-grid">
                    <label class="cc-option" :class="{ 'cc-option--selected': settings.themeMode === 'dark' }">
                        <input
                            class="sr-only"
                            v-model="settings.themeMode"
                            type="radio"
                            value="dark"
                            aria-label="Tmavý režim"
                        />
                        <div class="cc-option-title">Tmavý</div>
                        <div class="cc-option-desc">Výchozí</div>
                    </label>

                    <label class="cc-option" :class="{ 'cc-option--selected': settings.themeMode === 'light' }">
                        <input
                            class="sr-only"
                            v-model="settings.themeMode"
                            type="radio"
                            value="light"
                            aria-label="Světlý režim"
                        />
                        <div class="cc-option-title">Světlý</div>
                        <div class="cc-option-desc">Vždy</div>
                    </label>

                    <label class="cc-option" :class="{ 'cc-option--selected': settings.themeMode === 'system' }">
                        <input
                            class="sr-only"
                            v-model="settings.themeMode"
                            type="radio"
                            value="system"
                            aria-label="Dle nastavení telefonu"
                        />
                        <div class="cc-option-title">Auto</div>
                        <div class="cc-option-desc">Dle telefonu</div>
                    </label>
                </div>
            </section>

            <!-- AI -->
            <section>
                <h2 class="cc-section-title">Komentář AI k zápisu</h2>

                <select v-model="settings.aiCommentMode" class="cc-input">
                    <option value="none">Bez komentáře</option>
                    <option value="brief_health">Stručně, zda je jídlo zdravé a vhodné</option>
                    <option value="funny">Vtipný komentář</option>
                    <option value="praise_or_criticize">Pochvala nebo kritika</option>
                    <option value="random">Nechám to náhodě</option>
                </select>
            </section>

            <!-- POKROČILÉ a O APLIKACI – méně častá nastavení, aby základní zůstalo jednoduché -->
            <section>
                <div class="cc-list">
                    <NuxtLink to="/settings/advanced" class="cc-list-row flex items-center justify-between gap-3 text-base">
                        <span>Pokročilá nastavení</span>
                        <span class="cc-subtle" aria-hidden="true">›</span>
                    </NuxtLink>
                    <NuxtLink to="/settings/about" class="cc-list-row flex items-center justify-between gap-3 text-base">
                        <span>O aplikaci</span>
                        <span class="cc-subtle text-sm">{{ appVersion }} <span aria-hidden="true">›</span></span>
                    </NuxtLink>
                </div>
            </section>

            <!-- ÚČET -->
            <section>
                <h2 class="cc-section-title">Účet</h2>

                <div class="cc-list">
                    <button type="button" class="cc-list-row block w-full text-left text-base" @click="replayOnboarding">
                        Průvodce aplikací
                    </button>
                    <NuxtLink to="/terms" class="cc-list-row block w-full text-left text-base">
                        Podmínky užívání a ochrana osobních údajů
                    </NuxtLink>
                    <button type="button" class="cc-list-row w-full text-left cc-btn-danger-text text-base" @click="onLogout">
                        Odhlásit se
                    </button>
                </div>
            </section>
        </main>

        <BmrCalculatorSheet v-model:open="showBmrCalc" />
    </div>
</template>

<script setup lang="ts">
import { useDiaryMutations } from "~/features/diary/queries";
import { useOnboarding } from "~/features/onboarding/useOnboarding";

definePageMeta({
    middleware: ['auth'],
    layout: 'tabs',
})

const { settings, loaded, loading, saving, error, fetchFromApi, saveToApi } = useUserSettings()
const { signOut } = useAuth()
const { invalidateDiary } = useDiaryMutations()
const { replay: replayOnboarding } = useOnboarding()

const appVersion = useRuntimeConfig().public.appVersion

const showBmrCalc = ref(false)

onMounted(async () => {
    try {
        await fetchFromApi()
    } catch {
        // error je už nastavený v composable
    }
})

const onSave = async () => {
    try {
        await saveToApi()
        // BMR a aktivita mění výdej dne – souhrny v Deníku se musí přepočítat
        invalidateDiary()
        await navigateTo('/app')
    } catch {
        // error je už nastavený v composable
    }
}

const onLogout = async () => {
    await signOut()
}

const openBmrCalc = () => {
    showBmrCalc.value = true
}
</script>
