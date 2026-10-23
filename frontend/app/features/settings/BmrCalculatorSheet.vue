<template>
    <!-- VÝPOČET BMR + VÁHA -->
    <Transition name="cc-fade">
        <div
            v-if="open"
            class="fixed inset-0 z-[60] bg-black/60 flex items-end sm:items-center justify-center overflow-hidden"
            @click.self="close"
        >
            <div
                class="w-full sm:max-w-md max-h-[92vh] rounded-t-3xl sm:rounded-3xl shadow-2xl flex flex-col overflow-hidden"
                style="background: rgb(var(--cc-surface)); color: rgb(var(--cc-text)); border: 1px solid rgb(var(--cc-border));"
            >
                <div class="px-4 cc-header" style="border-bottom: 1px solid rgb(var(--cc-border));">
                    <div>
                        <button type="button" class="cc-header-action" @click="close">
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
                            <select v-model="form.sex" class="cc-input">
                                <option value="male">Muž</option>
                                <option value="female">Žena</option>
                            </select>
                        </label>

                        <div class="grid grid-cols-3 gap-3">
                            <label class="block">
                                <div class="text-xs cc-subtle mb-1">Věk</div>
                                <input
                                    v-model.number="form.age"
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
                                    v-model.number="form.heightCm"
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
                        <button type="button" class="w-full cc-btn-primary" @click="computeAndApply">
                            Spočítat a vyplnit BMR
                        </button>

                        <p v-if="error" class="text-sm text-red-400">
                            {{ error }}
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
</template>

<script setup lang="ts">
import { calculateBmr } from "~/utils/bmr";

// Panel pro výpočet BMR – vyplní settings.bmrKcal (uloží se až tlačítkem Uložit v Nastavení)
const open = defineModel<boolean>('open', { default: false })

const { settings } = useUserSettings()

const form = ref({
    sex: 'male' as 'male' | 'female',
    age: null as number | null,
    heightCm: null as number | null,
})
const error = ref<string | null>(null)

// Při otevřeném panelu se stránka pod ním neposouvá
watch(open, (isOpen) => {
    error.value = null
    document.body.classList.toggle('overflow-hidden', isOpen)
})

onBeforeUnmount(() => {
    document.body.classList.remove('overflow-hidden')
})

const close = () => {
    open.value = false
}

const computeAndApply = () => {
    const bmr = calculateBmr({ ...form.value, weightKg: settings.value.weightKg })

    if (bmr === null) {
        error.value = 'Vyplň prosím věk, výšku a váhu (kladná čísla).'
        return
    }

    settings.value.bmrKcal = bmr
    close()
}
</script>
