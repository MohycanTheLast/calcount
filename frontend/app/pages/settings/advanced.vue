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
import { useDiaryMutations } from "~/features/diary/queries";

// Méně častá nastavení – základní Nastavení zůstává jednoduché. Ukládá se stejný stav jako tam.
definePageMeta({
    middleware: ['auth'],
})

const { settings, loaded, loading, saving, error, fetchFromApi, saveToApi } = useUserSettings()
const { invalidateDiary } = useDiaryMutations()

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
