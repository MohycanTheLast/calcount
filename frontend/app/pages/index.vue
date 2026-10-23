<template>
    <div class="cc-page">
        <header class="cc-nav">
            <div class="cc-container py-6 text-center">
                <div class="mt-2 flex items-center justify-center gap-3">
                    <span class="text-base font-semibold tracking-tight">Jez, co chceš.</span>
                    <img src="/logo-mark.png" alt="CalCount" class="w-8 h-8" />
                    <span class="text-base font-semibold tracking-tight">Ale měj přehled.</span>
                </div>
            </div>
        </header>

        <main class="cc-container py-6 pb-24">
            <div class="cc-card cc-card-pad max-w-md mx-auto">
                <h1 class="cc-title text-center mb-5">Přihlášení</h1>

                <div class="space-y-3">
<!--                    <div class="grid grid-cols-2 gap-3">-->
<!--                        <button-->
<!--                            type="button"-->
<!--                            class="w-full inline-flex items-center justify-center gap-2 rounded-2xl border px-4 py-3 text-sm font-semibold transition active:scale-[0.99]"-->
<!--                            style="border-color: rgb(var(&#45;&#45;cc-border)); background: color-mix(in srgb, rgb(var(&#45;&#45;cc-surface)) 92%, white 8%);"-->
<!--                            @click="signInWithGoogle"-->
<!--                            aria-label="Přihlásit přes Google"-->
<!--                        >-->
<!--                            <svg class="w-5 h-5" viewBox="0 0 48 48" aria-hidden="true">-->
<!--                                <path fill="#FFC107" d="M43.611 20.083H42V20H24v8h11.303C33.785 32.657 29.258 36 24 36c-6.627 0-12-5.373-12-12s5.373-12 12-12c3.059 0 5.842 1.154 7.962 3.038l5.657-5.657C34.047 6.053 29.271 4 24 4 12.955 4 4 12.955 4 24s8.955 20 20 20 20-8.955 20-20c0-1.341-.138-2.65-.389-3.917Z"/>-->
<!--                                <path fill="#FF3D00" d="M6.306 14.691 12.88 19.51C14.656 15.108 18.961 12 24 12c3.059 0 5.842 1.154 7.962 3.038l5.657-5.657C34.047 6.053 29.271 4 24 4 16.318 4 9.656 8.342 6.306 14.691Z"/>-->
<!--                                <path fill="#4CAF50" d="M24 44c5.17 0 9.86-1.977 13.409-5.192l-6.19-5.238C29.211 35.091 26.715 36 24 36c-5.237 0-9.753-3.323-11.276-7.964l-6.524 5.025C9.516 39.433 16.227 44 24 44Z"/>-->
<!--                                <path fill="#1976D2" d="M43.611 20.083H42V20H24v8h11.303a12.05 12.05 0 0 1-4.084 5.571h.003l6.19 5.238C36.975 39.205 44 34 44 24c0-1.341-.138-2.65-.389-3.917Z"/>-->
<!--                            </svg>-->
<!--                            <span>Google</span>-->
<!--                        </button>-->

<!--                        <button-->
<!--                            type="button"-->
<!--                            class="w-full inline-flex items-center justify-center gap-2 rounded-2xl px-4 py-3 text-sm font-semibold text-white transition active:scale-[0.99]"-->
<!--                            style="background: #000000;"-->
<!--                            @click="signInWithApple"-->
<!--                            aria-label="Přihlásit přes Apple ID"-->
<!--                        >-->
<!--                            <svg-->
<!--                                class="w-5 h-5"-->
<!--                                viewBox="0 0 24 24"-->
<!--                                fill="currentColor"-->
<!--                                aria-hidden="true"-->
<!--                            >-->
<!--                                <path-->
<!--                                    d="M16.365 1.43c0 1.14-.42 2.21-1.24 3.12-.87.98-2.3 1.74-3.53 1.64-.16-1.1.46-2.27 1.28-3.17.91-1 2.47-1.73 3.49-1.59ZM20.5 17.3c-.6 1.38-.9 2-1.67 3.22-1.08 1.69-2.6 3.8-4.48 3.82-1.67.02-2.1-1.09-4.35-1.08-2.25.01-2.72 1.1-4.39 1.08-1.88-.03-3.32-1.93-4.4-3.62C.42 19.05 0 17.1 0 15.27c0-2.97 1.93-4.56 3.83-4.56 1.62 0 2.64 1.1 3.98 1.1 1.3 0 2.08-1.11 3.98-1.11.71 0 2.6.06 3.83 1.96-3.37 1.85-2.82 6.77.88 7.64Z"-->
<!--                                    transform="translate(2 0)"-->
<!--                                />-->
<!--                            </svg>-->
<!--                            <span>Apple</span>-->
<!--                        </button>-->
<!--                    </div>-->

<!--                    <div class="text-center cc-subtle text-sm py-2">nebo</div>-->

                    <form class="space-y-3" @submit.prevent="onEmailLogin">
                        <input v-model="email" type="email" class="cc-input" placeholder="Email" autocomplete="email" />
                        <input v-model="password" type="password" class="cc-input" placeholder="Heslo" autocomplete="current-password" />

                        <button class="w-full cc-btn-primary" :disabled="loading">
                            <span v-if="!loading">Přihlásit</span>
                            <span v-else>Přihlašuji…</span>
                        </button>
                    </form>

                    <p v-if="error" class="text-sm text-red-400">{{ error }}</p>

                    <p class="text-center text-sm cc-subtle pt-2">
                        Nemáš účet?
                        <NuxtLink to="/register" class="cc-link">Zaregistruj se</NuxtLink>
                    </p>
                </div>
            </div>
        </main>
    </div>
</template>

<script setup lang="ts">
const { signInWithEmail } = useAuth()

const email = ref('')
const password = ref('')
const loading = ref(false)
const error = ref<string | null>(null)

const onEmailLogin = async () => {
    loading.value = true
    error.value = null
    try {
        await signInWithEmail(email.value, password.value)
        await navigateTo('/app')
    } catch (e: any) {
        // 429 = příliš mnoho neúspěšných pokusů, 403 = neaktivovaný účet – server vrací srozumitelnou hlášku
        error.value = [403, 429].includes(e?.status) && e?.data?.error ? e.data.error : 'Přihlášení se nepodařilo.'
    } finally {
        loading.value = false
    }
}

const signInWithGoogle = async () => {
    // TODO: implementace podle zvoleného řešení (viz bod 5)
    error.value = 'Google přihlášení zatím není zapojené.'
}

const signInWithApple = async () => {
    // TODO: implementace podle zvoleného řešení (viz bod 5)
    error.value = 'Apple přihlášení zatím není zapojené.'
}
</script>