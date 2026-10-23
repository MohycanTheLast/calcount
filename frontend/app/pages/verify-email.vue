<template>
    <div class="cc-page">
        <DiaryHeader />

        <main class="cc-container py-6 pb-24">
            <div class="cc-card cc-card-pad max-w-md mx-auto text-center space-y-4">
                <template v-if="state === 'verifying'">
                    <div class="cc-spinner mx-auto"></div>
                    <p class="text-sm cc-subtle">Ověřuji e-mail…</p>
                </template>

                <template v-else-if="state === 'verified'">
                    <h1 class="cc-title">Účet je aktivní</h1>
                    <p class="text-sm cc-subtle">E-mail je ověřený, teď se můžeš přihlásit.</p>
                </template>

                <template v-else>
                    <h1 class="cc-title">Ověření se nepodařilo</h1>
                    <p class="text-sm cc-subtle">{{ error }}</p>
                </template>

                <NuxtLink v-if="state !== 'verifying'" to="/" class="w-full cc-btn-primary">Přejít na přihlášení</NuxtLink>
            </div>
        </main>
    </div>
</template>

<script setup lang="ts">
import { authApi } from '~/api/auth'

// Cíl odkazu z registračního e-mailu (backend EmailVerifier): /verify-email?uid=…&expires=…&signature=…
const route = useRoute()

const state = ref<'verifying' | 'verified' | 'failed'>('verifying')
const error = ref('Odkaz je neplatný nebo už vypršel.')

onMounted(async () => {
    const { uid, expires, signature } = route.query
    if (typeof uid !== 'string' || typeof expires !== 'string' || typeof signature !== 'string') {
        state.value = 'failed'
        return
    }

    try {
        await authApi.verifyEmail({ uid, expires, signature })
        state.value = 'verified'
    } catch (e: any) {
        if (e?.data?.error) error.value = e.data.error
        else if (!e?.status) error.value = 'Nepodařilo se spojit se serverem. Zkus odkaz otevřít znovu.'
        state.value = 'failed'
    }
})
</script>
