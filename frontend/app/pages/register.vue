<template>
    <div class="cc-page">
        <header class="cc-nav">
            <div class="cc-container cc-header">
                <div>
                    <NuxtLink to="/" class="cc-header-action -ml-1">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                            <path d="M15.7 4.3a1 1 0 0 1 0 1.4L9.42 12l6.3 6.3a1 1 0 0 1-1.42 1.4l-7-7a1 1 0 0 1 0-1.4l7-7a1 1 0 0 1 1.4 0Z"/>
                        </svg>
                        Přihlášení
                    </NuxtLink>
                </div>
                <h1 class="cc-header-title">Registrace</h1>
                <div></div>
            </div>
        </header>

        <main class="cc-container py-6 pb-24">
            <div class="cc-card cc-card-pad max-w-md mx-auto">
                <!-- Jen se zapnutým ověřením e-mailem na backendu (EMAIL_VERIFICATION_ENABLED) -->
                <div v-if="verificationPending" class="text-center space-y-4">
                    <h2 class="cc-title">Zkontroluj e-mail</h2>
                    <p class="text-sm cc-subtle">
                        Na adresu <strong>{{ pendingEmail }}</strong> jsme poslali odkaz pro aktivaci účtu.
                        Po kliknutí na něj se můžeš přihlásit.
                    </p>
                    <NuxtLink to="/" class="w-full cc-btn-primary">Zpět na přihlášení</NuxtLink>
                </div>

                <template v-else>
                    <h2 class="cc-title text-center mb-5">Vytvoř si účet</h2>

                    <form class="space-y-3" novalidate @submit.prevent="onRegister">
                        <input v-model="form.email" type="email" class="cc-input" placeholder="Email" autocomplete="email" />
                        <input
                            v-model="form.password"
                            type="password"
                            class="cc-input"
                            :placeholder="`Heslo (aspoň ${PASSWORD_MIN_LENGTH} znaků)`"
                            autocomplete="new-password"
                        />
                        <input v-model="form.passwordAgain" type="password" class="cc-input" placeholder="Heslo znovu" autocomplete="new-password" />

                        <p class="text-xs cc-subtle">
                            Kalorie odhaduje AI. Posíláme jí jen text záznamu nebo fotku – nikdy tvé jméno, e-mail
                            ani tvé další záznamy.
                        </p>

                        <label class="flex items-start gap-3 py-1 text-sm cursor-pointer">
                            <input v-model="form.termsAccepted" type="checkbox" class="cc-checkbox" />
                            <span>
                                Souhlasím s <NuxtLink to="/terms" class="cc-link">podmínkami užívání</NuxtLink>
                                a se zpracováním svých osobních údajů, včetně údajů o stravování a zdraví, podle
                                <NuxtLink to="/terms#gdpr" class="cc-link">zásad ochrany osobních údajů</NuxtLink>.
                            </span>
                        </label>

                        <button class="w-full cc-btn-primary" :disabled="loading">
                            <span v-if="!loading">Zaregistrovat</span>
                            <span v-else>Registruji…</span>
                        </button>
                    </form>

                    <p v-if="error" class="mt-3 text-sm text-red-400">{{ error }}</p>
                </template>
            </div>
        </main>
    </div>
</template>

<script setup lang="ts">
// Stejné minimum jako RegistrationController::PASSWORD_MIN_LENGTH
const PASSWORD_MIN_LENGTH = 8

const { registerWithEmail } = useAuth()

// Formulář přežije odskok na podmínky užívání a zpět (stránka se mezitím odmontuje).
// Jen v paměti – nic z toho se neukládá do úložiště zařízení.
const emptyForm = () => ({ email: '', password: '', passwordAgain: '', termsAccepted: false })
const form = useState('register:form', emptyForm)

const loading = ref(false)
const error = ref<string | null>(null)
const verificationPending = ref(false)
const pendingEmail = ref('')

const validate = (): string | null => {
    if (!/^\S+@\S+\.\S+$/.test(form.value.email.trim())) return 'Zadej platný e-mail.'
    if (form.value.password.length < PASSWORD_MIN_LENGTH) return `Heslo musí mít aspoň ${PASSWORD_MIN_LENGTH} znaků.`
    if (form.value.password !== form.value.passwordAgain) return 'Hesla se neshodují.'
    if (!form.value.termsAccepted) return 'Pro registraci je potřeba souhlasit s podmínkami užívání.'
    return null
}

const onRegister = async () => {
    error.value = validate()
    if (error.value) return

    loading.value = true
    try {
        const email = form.value.email.trim()
        const status = await registerWithEmail(email, form.value.password, form.value.termsAccepted)
        form.value = emptyForm()

        if (status === 'verification_pending') {
            pendingEmail.value = email
            verificationPending.value = true
            return
        }
        await navigateTo('/app')
    } catch (e: any) {
        // 400 (neplatné údaje), 409 (účet existuje), 429 (moc registrací) – server vrací srozumitelnou hlášku
        error.value = e?.data?.error ?? 'Registrace se nepodařila. Zkus to prosím znovu.'
    } finally {
        loading.value = false
    }
}
</script>
