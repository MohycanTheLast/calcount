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
                <h1 class="cc-header-title">O aplikaci</h1>
                <div></div>
            </div>
        </header>

        <main class="cc-container pt-4 pb-16 space-y-6">
            <section class="cc-card cc-card-pad text-center space-y-2">
                <img src="/logo-mark.png" alt="" class="w-14 h-14 mx-auto" />
                <h2 class="cc-title">Kalorický deník</h2>
                <p class="text-sm cc-subtle">Jez, co chceš. Ale měj přehled.</p>
                <p class="text-sm">
                    Zapisuj jídlo a pití textem, hlasem nebo fotkou – kalorie a vodu odhadne AI.
                </p>
            </section>

            <section>
                <div class="cc-list">
                    <div class="cc-list-row flex items-center justify-between gap-3 text-base">
                        <span>Verze</span>
                        <span class="cc-subtle tabular-nums">{{ version }}</span>
                    </div>
                    <div class="cc-list-row flex items-center justify-between gap-3 text-base">
                        <span>Sestaveno</span>
                        <span class="cc-subtle tabular-nums">{{ buildDate }}</span>
                    </div>
                </div>
            </section>

            <section>
                <div class="cc-list">
                    <NuxtLink to="/terms" class="cc-list-row flex items-center justify-between gap-3 text-base">
                        <span>Podmínky užívání a ochrana osobních údajů</span>
                        <span class="cc-subtle" aria-hidden="true">›</span>
                    </NuxtLink>
                </div>
            </section>
        </main>
    </div>
</template>

<script setup lang="ts">
definePageMeta({
    middleware: ['auth'],
})

const config = useRuntimeConfig().public
const version = String(config.appVersion)
// Kdy vznikl build – na telefonu s aplikací na ploše tak jde ověřit, že se načetla nová verze
const buildDate = formatDateTime(String(config.buildDate))
</script>
