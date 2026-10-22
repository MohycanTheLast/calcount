<template>
    <div class="cc-page">
        <header class="cc-nav">
            <div class="cc-container py-4 flex items-center justify-between">
                <button class="cc-link" type="button" @click="goBack">
                    Zpět
                </button>

                <div class="text-base font-semibold tracking-tight">
                    Nastavení
                </div>

                <div class="w-10"></div>
            </div>
        </header>

        <main class="cc-container py-4 pb-24 space-y-6 max-w-md mx-auto">
            <!-- ÚČET -->
            <section class="cc-card cc-card-pad space-y-3">
                <h2 class="cc-title text-center">Účet</h2>

                <button
                    type="button"
                    class="w-full cc-btn-primary"
                    :disabled="saving || loading"
                    @click="onSave"
                >
                    <span v-if="!saving">Uložit změny</span>
                    <span v-else>Ukládám…</span>
                </button>

                <p v-if="error" class="text-sm text-red-400">
                    {{ error }}
                </p>

                <p v-if="loading" class="text-xs cc-subtle">
                    Načítám nastavení…
                </p>
            </section>

            <!-- METABOLISMUS -->
            <section class="cc-card cc-card-pad space-y-4">
                <h2 class="cc-title text-center">Metabolismus</h2>

                <label class="block">
                    <div class="flex items-center justify-between gap-3 mb-1">
                        <div class="text-sm cc-subtle">BMR (bazální metabolismus) v kcal / den</div>

                        <button type="button" class="cc-link text-sm" @click="openBmrCalc">
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
                    <div class="text-sm cc-subtle mb-1">Průměrná denní aktivita (kcal / den)</div>
                    <input
                        v-model.number="settings.avgDailyActivityKcal"
                        type="number"
                        min="0"
                        inputmode="numeric"
                        class="cc-input"
                        placeholder="Např. 400"
                    />
                </label>
            </section>

            <!-- VZHLED -->
            <section class="cc-card cc-card-pad space-y-3">
                <h2 class="cc-title text-center">Vzhled aplikace</h2>

                <div class="cc-option-grid">
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

                    <label class="cc-option" :class="{ 'cc-option--selected': settings.themeMode === 'dark' }">
                        <input
                            class="sr-only"
                            v-model="settings.themeMode"
                            type="radio"
                            value="dark"
                            aria-label="Tmavý režim"
                        />
                        <div class="cc-option-title">Tmavý</div>
                        <div class="cc-option-desc">Vždy</div>
                    </label>
                </div>
            </section>

            <!-- AI -->
            <section class="cc-card cc-card-pad space-y-3">
                <h2 class="cc-title text-center">Nastavení AI</h2>

                <div class="text-sm cc-subtle">
                    S každým novým zápisem:
                </div>

                <select v-model="settings.aiCommentMode" class="cc-input">
                    <option value="none">Si nepřeji žádný komentář</option>
                    <option value="brief_health">Stručný komentář, zda je jídlo zdravé a vhodné</option>
                    <option value="funny">Si přeji nějaký vtipný komentář</option>
                    <option value="praise_or_criticize">Si přeji pochvalu nebo kritiku</option>
                    <option value="random">Nechám to náhodě (občas komentář, občas ne)</option>
                </select>
            </section>
        </main>

        <!-- FULLSCREEN POPUP: Výpočet BMR + Váha -->
        <div
            v-if="showBmrCalc"
            class="fixed inset-0 z-50 bg-black/60 flex items-end sm:items-center justify-center overflow-hidden"
            @click.self="closeBmrCalc"
        >
            <div
                class="w-full sm:max-w-md max-h-[92vh]
               rounded-t-3xl sm:rounded-3xl
               shadow-2xl flex flex-col overflow-hidden"
                style="background: rgb(var(--cc-surface)); color: rgb(var(--cc-text)); border: 1px solid rgb(var(--cc-border));"
            >
                <div
                    class="p-4 flex items-center justify-between"
                    style="border-bottom: 1px solid rgb(var(--cc-border));"
                >
                    <button type="button" class="cc-link" @click="closeBmrCalc">
                        Zpět
                    </button>
                    <div class="text-base font-semibold">Výpočet BMR</div>
                    <div class="w-10"></div>
                </div>

                <div class="p-4 overflow-y-auto scroll-ios flex-1 space-y-5">
                    <section class="space-y-3">
                        <div class="text-sm font-semibold">Parametry</div>

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
                <div class="text-xs cc-subtle">např. pro pozdější porovnání</div>
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
    </div>
</template>

<script setup lang="ts">
definePageMeta({
    middleware: ['auth'],
})

const { settings, loading, saving, error, fetchFromApi, saveToApi } = useUserSettings()

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

const goBack = async () => {
    await navigateTo('/app')
}

const onSave = async () => {
    try {
        await saveToApi()
        await navigateTo('/app')
    } catch {
        // error je už nastavený v composable
    }

}

const openBmrCalc = () => {
    bmrError.value = null
    showBmrCalc.value = true
    document.body.classList.add('overflow-hidden')
}

const closeBmrCalc = () => {

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

    showBmrCalc.value = false
    document.body.classList.remove('overflow-hidden')
}
</script>