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

            <!-- ÚČET -->
            <section>
                <h2 class="cc-section-title">Účet</h2>

                <div class="cc-list">
                    <button type="button" class="cc-list-row w-full text-left cc-btn-danger-text text-base" @click="onLogout">
                        Odhlásit se
                    </button>
                </div>
            </section>
        </main>

        <!-- VÝPOČET BMR + VÁHA -->
        <Transition name="cc-fade">
            <div
                v-if="showBmrCalc"
                class="fixed inset-0 z-[60] bg-black/60 flex items-end sm:items-center justify-center overflow-hidden"
                @click.self="closeBmrCalc"
            >
                <div
                    class="w-full sm:max-w-md max-h-[92vh] rounded-t-3xl sm:rounded-3xl shadow-2xl flex flex-col overflow-hidden"
                    style="background: rgb(var(--cc-surface)); color: rgb(var(--cc-text)); border: 1px solid rgb(var(--cc-border));"
                >
                    <div class="px-4 cc-header" style="border-bottom: 1px solid rgb(var(--cc-border));">
                        <div>
                            <button type="button" class="cc-header-action" @click="closeBmrCalc">
                                Zpět
                            </button>
                        </div>
                        <div class="cc-header-title">Výpočet BMR</div>
                        <div></div>
                    </div>

                    <div class="p-4 overflow-y-auto scroll-ios flex-1 space-y-5">
                        <section class="space-y-3">
                            <label class="block">
                                <div class="text-xs cc-subtle mb-1">Pohlaví</div>
                                <select v-model="bmr.sex" class="cc-input">
                                    <option value="male">Muž</option>
                                    <option value="female">Žena</option>
                                </select>
                            </label>

                            <div class="grid grid-cols-3 gap-3">
                                <label class="block">
                                    <div class="text-xs cc-subtle mb-1">Věk</div>
                                    <input
                                        v-model.number="bmr.age"
                                        type="number"
                                        min="0"
                                        inputmode="numeric"
                                        class="cc-input"
                                        placeholder="let"
                                    />
                                </label>

                                <label class="block">
                                    <div class="text-xs cc-subtle mb-1">Výška</div>
                                    <input
                                        v-model.number="bmr.heightCm"
                                        type="number"
                                        min="0"
                                        inputmode="numeric"
                                        class="cc-input"
                                        placeholder="cm"
                                    />
                                </label>

                                <label class="block">
                                    <div class="text-xs cc-subtle mb-1">Váha</div>
                                    <input
                                        v-model.number="settings.weightKg"
                                        type="number"
                                        min="0"
                                        step="any"
                                        inputmode="decimal"
                                        class="cc-input"
                                        placeholder="kg"
                                    />
                                </label>
                            </div>

                            <label class="flex items-start gap-3 pt-1">
                                <input v-model="settings.saveWeight" type="checkbox" class="mt-1" />
                                <span class="text-sm">
                                    Uložit váhu
                                    <span class="block text-xs cc-subtle">např. pro pozdější porovnání</span>
                                </span>
                            </label>
                        </section>

                        <section class="space-y-2">
                            <button type="button" class="w-full cc-btn-primary" @click="computeAndApplyBmr">
                                Spočítat a vyplnit BMR
                            </button>

                            <p v-if="bmrError" class="text-sm text-red-400">
                                {{ bmrError }}
                            </p>

                            <p class="text-xs cc-subtle">
                                Použit vzorec Mifflin–St Jeor. Je to odhad.
                            </p>
                        </section>
                    </div>

                    <div class="pb-safe"></div>
                </div>
            </div>
        </Transition>
    </div>
</template>

<script setup lang="ts">
definePageMeta({
    middleware: ['auth'],
    tabBar: true,
})

const { settings, loaded, loading, saving, error, fetchFromApi, saveToApi } = useUserSettings()
const { signOut } = useAuth()

const showBmrCalc = ref(false)
const bmrError = ref<string | null>(null)

const bmr = ref({
    sex: 'male' as 'male' | 'female',
    age: null as number | null,
    heightCm: null as number | null,
})

onMounted(async () => {
    try {
        await fetchFromApi()
    } catch {
        // error je už nastavený v composable
    }
})

onBeforeUnmount(() => {
    document.body.classList.remove('overflow-hidden')
})

const onSave = async () => {
    try {
        await saveToApi()
        await navigateTo('/app')
    } catch {
        // error je už nastavený v composable
    }
}

const onLogout = async () => {
    await signOut()
}

const openBmrCalc = () => {
    bmrError.value = null
    showBmrCalc.value = true
    document.body.classList.add('overflow-hidden')
}

const closeBmrCalc = () => {
    showBmrCalc.value = false
    document.body.classList.remove('overflow-hidden')
}

const computeAndApplyBmr = () => {
    bmrError.value = null

    const age = bmr.value.age
    const height = bmr.value.heightCm
    const weight = settings.value.weightKg

    if (!age || age <= 0 || !height || height <= 0 || !weight || weight <= 0) {
        bmrError.value = 'Vyplň prosím věk, výšku a váhu (kladná čísla).'
        return
    }

    // Mifflin–St Jeor:
    // muž:   10W + 6.25H - 5A + 5
    // žena:  10W + 6.25H - 5A - 161
    const base = 10 * weight + 6.25 * height - 5 * age
    const sexConst = bmr.value.sex === 'male' ? 5 : -161

    settings.value.bmrKcal = Math.round(base + sexConst)

    closeBmrCalc()
}
</script>
