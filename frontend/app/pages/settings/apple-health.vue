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
                <h1 class="cc-header-title">Apple Zdraví</h1>
                <div></div>
            </div>
        </header>

        <main class="cc-container pt-4 pb-16 space-y-6">
            <p class="text-sm cc-subtle px-1">
                Aktivní kalorie z Apple Watch a iPhonu může do CalCountu každý den posílat aplikace Zkratky.
                Potřebuje k tomu osobní token – smí jen zapisovat aktivní kalorie, nic jiného z účtu nevidí.
            </p>

            <!-- NOVÝ TOKEN – zobrazí se jen jednou -->
            <section v-if="createdToken" class="cc-card cc-card-pad space-y-3">
                <h2 class="text-base font-semibold">Token je vytvořený</h2>
                <p class="text-sm cc-subtle">
                    Zkopíruj si ho a vlož do Zkratky (krok 4 návodu). <strong>Znovu se už nezobrazí.</strong>
                </p>
                <code class="block break-all rounded-xl px-3 py-2.5 text-sm select-all" style="background: rgb(var(--cc-border) / 0.4);">
                    {{ createdToken }}
                </code>
                <button type="button" class="w-full cc-btn-primary" @click="copy(createdToken)">
                    {{ copied ? 'Zkopírováno ✓' : 'Kopírovat token' }}
                </button>
            </section>

            <!-- TOKENY -->
            <section>
                <h2 class="cc-section-title">Tokeny</h2>

                <p v-if="isPending" class="text-sm cc-subtle px-1">Načítám…</p>
                <p v-else-if="isError" class="text-sm text-red-400 px-1">Tokeny se nepodařilo načíst.</p>

                <ul v-else-if="tokens?.length" class="cc-list">
                    <li v-for="token in tokens" :key="token.id" class="cc-list-row flex items-center justify-between gap-3">
                        <div class="min-w-0">
                            <div class="text-base truncate">{{ token.name }}</div>
                            <div class="text-xs cc-subtle">
                                {{ token.prefix }}… ·
                                {{ token.lastUsedAt ? `naposledy ${formatDateTime(token.lastUsedAt)}` : 'zatím nepoužitý' }}
                            </div>
                        </div>
                        <button
                            type="button"
                            class="cc-btn-danger-text text-sm shrink-0"
                            :disabled="revokeToken.isPending.value"
                            @click="onRevoke(token)"
                        >
                            Odvolat
                        </button>
                    </li>
                </ul>

                <p v-else class="text-sm cc-subtle px-1">Zatím nemáš žádný token.</p>

                <form class="mt-3 flex gap-2" @submit.prevent="onCreate">
                    <input v-model="newTokenName" type="text" class="cc-input" maxlength="100" placeholder="Název, např. iPhone – Zkratky" />
                    <button class="cc-btn-primary shrink-0" :disabled="createToken.isPending.value">
                        {{ createToken.isPending.value ? 'Vytvářím…' : 'Vytvořit' }}
                    </button>
                </form>

                <p v-if="error" class="mt-2 text-sm text-red-400">{{ error }}</p>
            </section>

            <!-- NÁVOD -->
            <section>
                <h2 class="cc-section-title">Návod (iOS 17 a novější)</h2>

                <div class="cc-card cc-card-pad cc-prose">
                    <p>V aplikaci <strong>Zkratky</strong> otevři záložku <strong>Automatizace</strong> → <strong>+</strong> →
                        <strong>Denní doba</strong>, nastav čas (např. 21:30) a zvol <strong>Spustit okamžitě</strong>.
                        Pak přidej tyto akce:</p>
                    <ol class="list-decimal pl-5 space-y-2">
                        <li><strong>Najít vzorky zdraví</strong> – typ <em>Aktivní energie</em>, filtr <em>Datum začátku</em> je <em>dnes</em>.</li>
                        <li><strong>Spočítat statistiky</strong> – <em>Součet</em> z výsledku kroku 1.</li>
                        <li><strong>Formátovat datum</strong> – <em>Aktuální datum</em>, vlastní formát <code>yyyy-MM-dd</code>.</li>
                        <li>
                            <strong>Získat obsah URL</strong>:
                            <ul>
                                <li>URL: <code class="break-all">{{ activityUrl }}</code>
                                    <button type="button" class="cc-link ml-1" @click="copy(activityUrl, 'url')">{{ copiedUrl ? 'zkopírováno' : 'kopírovat' }}</button>
                                </li>
                                <li>Metoda <em>POST</em>, záhlaví <code>Authorization</code> s hodnotou <code>Bearer</code>, mezera a tvůj token.</li>
                                <li>Tělo požadavku <em>JSON</em>: pole <code>date</code> (text) = výsledek kroku 3,
                                    pole <code>activeKcal</code> (číslo) = výsledek kroku 2.</li>
                            </ul>
                        </li>
                    </ol>
                    <p>
                        Zkratku můžeš spustit i ručně, kdykoli během dne. Hodnota za den se pokaždé přepíše, nic se
                        nesčítá dvakrát. Pokud je telefon v čase automatizace zamčený, iOS nemusí data ze Zdraví vydat –
                        zvol čas, kdy telefon obvykle používáš.
                    </p>
                    <p>
                        Aktuální zdroj aktivních kalorií: <strong>{{ sourceLabel }}</strong>.
                        Změníš ho v <NuxtLink to="/settings">Nastavení</NuxtLink>.
                    </p>
                </div>
            </section>
        </main>
    </div>
</template>

<script setup lang="ts">
import { getApiBase } from '~/api/client'
import type { PersonalAccessToken } from '~/api/types'
import { usePersonalTokenMutations, usePersonalTokensQuery } from '~/features/integrations/queries'

definePageMeta({
    middleware: ['auth'],
})

const { data: tokens, isPending, isError } = usePersonalTokensQuery()
const { createToken, revokeToken } = usePersonalTokenMutations()
const { settings, loaded, fetchFromApi } = useUserSettings()

const newTokenName = ref('')
const createdToken = ref<string | null>(null)
const error = ref<string | null>(null)
const copied = ref(false)
const copiedUrl = ref(false)

// Adresa pro Zkratku – stejný origin, v nativní aplikaci adresa backendu
const activityUrl = computed(() => `${getApiBase() || window.location.origin}/api/activity`)

const SOURCE_LABELS = {
    static: 'vždy průměrná denní aktivita',
    synced_or_static: 'z Apple Zdraví, jinak průměr',
    synced: 'jen z Apple Zdraví',
}
const sourceLabel = computed(() => SOURCE_LABELS[settings.value.activitySource ?? 'static'])

onMounted(() => {
    if (!loaded.value) fetchFromApi().catch(() => {})
})

const onCreate = async () => {
    error.value = null
    try {
        const created = await createToken.mutateAsync(newTokenName.value.trim())
        createdToken.value = created.token
        copied.value = false
        newTokenName.value = ''
    } catch (e: any) {
        error.value = e?.data?.error ?? 'Token se nepodařilo vytvořit.'
    }
}

const onRevoke = async (token: PersonalAccessToken) => {
    if (!window.confirm(`Odvolat token „${token.name}“? Zkratka s ním přestane fungovat.`)) return
    error.value = null
    try {
        await revokeToken.mutateAsync(token.id)
    } catch {
        error.value = 'Token se nepodařilo odvolat.'
    }
}

const copy = async (text: string, what: 'token' | 'url' = 'token') => {
    try {
        await navigator.clipboard.writeText(text)
        if (what === 'url') copiedUrl.value = true
        else copied.value = true
    } catch {
        // Bez přístupu ke schránce (např. http) – text jde označit a zkopírovat ručně
        error.value = 'Kopírování se nepodařilo, označ text a zkopíruj ho ručně.'
    }
}
</script>
