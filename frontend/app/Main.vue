<template>
    <div class="cc-page">
        <!-- TOP / MOTTO -->
        <header class="cc-nav">
            <div class="cc-container py-4">
                <div class="text-center">
                    <div class="mt-2 flex items-center justify-center gap-3">
                        <span class="text-base font-semibold tracking-tight">
                            Jez, co chceš.
                        </span>

                        <img src="/logo-512.png" alt="logo" class="w-8 h-8 rounded-xl" />

                        <span class="text-base font-semibold tracking-tight">
                            Ale měj přehled.
                        </span>
                    </div>
                </div>
            </div>
        </header>

        <!-- MAIN -->
        <main class="cc-container py-2 pb-24">
            <!-- DAILY OVERVIEW (bez nadpisu) -->
            <Diary :refresh-token="diaryRefreshToken" @update-entry="updateEntry"/>

            <!-- INPUT CARD -->
            <div class="cc-card cc-card-pad mt-6">
                <h2 class="cc-title mb-4 text-center">
                    Nový záznam
                </h2>

                <div class="relative">
                    <textarea
                        v-model="text"
                        placeholder="Např. Svíčková s 5 knedlíky, k tomu pivo"
                        class="cc-input min-h-[120px] pr-24 resize-none"
                    />

                    <!-- iOS-like icon buttons -->
                    <div class="absolute right-3 bottom-3 flex items-center gap-2">
                        <button
                            v-if="supported && !listening"
                            @click="start"
                            class="w-10 h-10 rounded-full border flex items-center justify-center transition active:scale-[0.98] text-emerald-400"
                            style="border-color: rgb(var(--cc-border)); background: color-mix(in srgb, rgb(var(--cc-surface)) 88%, transparent);"
                            title="Diktovat hlasem"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                <path d="M12 14a3 3 0 0 0 3-3V6a3 3 0 1 0-6 0v5a3 3 0 0 0 3 3Zm-7-3a1 1 0 1 0-2 0 9 9 0 0 0 8 8.94V22a1 1 0 1 0 2 0v-2.06A9 9 0 0 0 21 11a1 1 0 1 0-2 0 7 7 0 0 1-14 0Z"/>
                            </svg>
                        </button>

                        <button
                            v-if="listening"
                            @click="stopAndSubmit"
                            class="w-10 h-10 rounded-full border flex items-center justify-center transition active:scale-[0.98] text-red-400 animate-pulse"
                            style="border-color: rgb(var(--cc-border)); background: color-mix(in srgb, rgb(var(--cc-surface)) 88%, transparent);"
                            title="Zastavit nahrávání"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                <rect x="6" y="6" width="12" height="12" rx="2" />
                            </svg>
                        </button>

                        <button
                            @click="handleOpenCamera"
                            :disabled="cameraLoading"
                            class="w-10 h-10 rounded-full border flex items-center justify-center transition active:scale-[0.98] text-emerald-400 disabled:opacity-50"
                            style="border-color: rgb(var(--cc-border)); background: color-mix(in srgb, rgb(var(--cc-surface)) 88%, transparent);"
                            title="Vyfotit jídlo"
                        >
                            <svg v-if="!cameraLoading" xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                <path d="M9 3a1 1 0 0 0-.8.4L6.5 5.5H5a3 3 0 0 0-3 3V18a3 3 0 0 0 3 3h14a3 3 0 0 0 3-3V8.5a3 3 0 0 0-3-3h-1.5l-1.7-2.1A1 1 0 0 0 15 3H9Zm3 16a5 5 0 1 1 0-10 5 5 0 0 1 0 10Z"/>
                            </svg>
                            <svg v-else xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
                            </svg>
                        </button>
                    </div>

                    <img
                        v-if="capturedImageURL"
                        :src="capturedImageURL"
                        alt="Vyfocené jídlo"
                        class="mt-4 w-32 h-32 object-cover rounded-2xl border"
                        style="border-color: rgb(var(--cc-border));"
                    />
                </div>

                <!-- ACTION BUTTON -->
                <div class="flex justify-center mt-6">
                    <button
                        :disabled="loadingSave"
                        @click="onSubmit"
                        class="w-full cc-btn-primary disabled:opacity-60"
                    >
                        <span v-if="!loadingSave">Zapsat</span>
                        <span v-else>Ukládám…</span>
                    </button>
                </div>

                <AlertMessage
                    v-if="confirmationMessage !== null"
                    :message="confirmationMessage"
                    :duration="3000"
                />

                <!-- RESULT -->
                <div
                    v-if="result"
                    class="mt-6 rounded-3xl border p-5"
                    style="border-color: rgb(var(--cc-border)); background: color-mix(in srgb, rgb(var(--cc-surface)) 86%, rgb(var(--cc-accent)) 6%);"
                >
                    <div v-if="mode !== 'edit'" class="space-y-3">
                        <p class="text-sm cc-subtle">
                            {{ result.message }}
                        </p>

                        <div class="flex gap-6 text-base font-semibold">
                            <div>🔥 {{ result.kcal }} kcal</div>
                            <div>💧 {{ result.water }} l</div>
                        </div>

                        <div class="mt-3 flex items-center justify-between">
                            <button @click="editMode" class="cc-link">
                                Upravit záznam
                            </button>

                            <button
                                @click="onDelete"
                                title="Smazat záznam"
                                class="cc-subtle hover:text-red-500 transition"
                            >
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    class="w-5 h-5"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3m-4 0h14"
                                    />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- EDIT MODE -->
                    <div v-if="mode === 'edit'" class="space-y-4">
                        <div class="grid grid-cols-2 gap-3">
                            <div class="relative">
                                <input
                                    v-model.number="kcal"
                                    type="number"
                                    min="0"
                                    class="cc-input pr-14"
                                />
                                <span class="absolute right-4 top-1/2 -translate-y-1/2 cc-subtle text-sm">kcal</span>
                            </div>
                            <div class="relative">
                                <input
                                    v-model.number="waterInL"
                                    type="number"
                                    step="any"
                                    min="0"
                                    class="cc-input pr-10"
                                />
                                <span class="absolute right-4 top-1/2 -translate-y-1/2 cc-subtle text-sm">l</span>
                            </div>
                        </div>

                        <div class="flex justify-between items-center pt-1">
                            <button @click="cancelEdit" class="cc-subtle hover:underline text-sm">
                                Zpět
                            </button>

                            <button @click="saveUpdated" class="cc-btn-primary px-6 py-2">
                                Uložit
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <!-- BOTTOM USER MENU -->
        <nav
            class="fixed bottom-0 left-0 right-0 z-50 pb-safe"
            style="background: color-mix(in srgb, rgb(var(--cc-bg)) 82%, transparent); border-top: 1px solid rgb(var(--cc-border)); backdrop-filter: blur(10px);"
        >
            <div class="cc-container h-16 flex items-center justify-around">
                <button class="flex flex-col items-center gap-1 text-xs cc-subtle hover:opacity-80 transition" @click="onLogout">
                <span
                    class="w-9 h-9 rounded-full border flex items-center justify-center"
                    style="border-color: rgb(var(--cc-border)); background: color-mix(in srgb, rgb(var(--cc-surface)) 88%, transparent);"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                        <path d="M10 17a1 1 0 0 0 1-1v-1h7a1 1 0 0 0 0-2h-7v-1a1 1 0 0 0-1.707-.707l-3 3a1 1 0 0 0 0 1.414l3 3A1 1 0 0 0 10 17Z"/>
                        <path d="M20 3H12a1 1 0 0 0 0 2h8v14h-8a1 1 0 0 0 0 2h8a2 2 0 0 0 2-2V5a2 2 0 0 0-2-2Z"/>
                    </svg>
                </span>
                    Odhlásit
                </button>

                <button
                    class="flex flex-col items-center gap-1 text-xs cc-subtle hover:opacity-80 transition"
                    @click="goToSettings"
                    type="button"
                >
                    <span class="w-9 h-9 rounded-full border flex items-center justify-center"
                          style="border-color: rgb(var(--cc-border)); background: color-mix(in srgb, rgb(var(--cc-surface)) 88%, transparent);">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                            <path d="M19.14 12.94a7.43 7.43 0 0 0 .05-.94 7.43 7.43 0 0 0-.05-.94l2.03-1.58a.5.5 0 0 0 .12-.64l-1.92-3.32a.5.5 0 0 0-.6-.22l-2.39.96a7.06 7.06 0 0 0-1.63-.94l-.36-2.54A.5.5 0 0 0 13.9 1h-3.8a.5.5 0 0 0-.49.42l-.36 2.54a7.06 7.06 0 0 0-1.63.94l-2.39-.96a.5.5 0 0 0-.6.22L2.71 7.48a.5.5 0 0 0 .12.64l2.03 1.58a7.43 7.43 0 0 0-.05.94c0 .32.02.63.05.94l-2.03 1.58a.5.5 0 0 0-.12.64l1.92 3.32a.5.5 0 0 0 .6.22l2.39-.96c.5.38 1.05.7 1.63.94l.36 2.54a.5.5 0 0 0 .49.42h3.8a.5.5 0 0 0 .49-.42l.36-2.54c.58-.24 1.13-.56 1.63-.94l2.39.96a.5.5 0 0 0 .6-.22l1.92-3.32a.5.5 0 0 0-.12-.64ZM12 15.5A3.5 3.5 0 1 1 15.5 12 3.5 3.5 0 0 1 12 15.5Z"/>
                        </svg>
                    </span>
                    Nastavení
                </button>
            </div>
        </nav>
    </div>
</template>

<script setup lang="ts">
import {useSpeechToText} from "~/composables/useSpeechToText";
import Diary from "~/Diary.vue";
import {useApiFetch} from "~/composables/useApiFetch";
const { signOut } = useAuth()
const { apiFetch } = useApiFetch()

const goToSettings = async () => {
    await navigateTo('/settings')
}

const diaryRefreshToken = ref(0);

const refreshDiary = () => {
    diaryRefreshToken.value += 1;
};

const mode = ref<'create' | 'view' | 'edit'>('create')

const {
    supported,
    listening,
    transcript,
    start,
    stop,
} = useSpeechToText('cs-CZ')

watch(transcript, (value) => {
    if (!value) return
    text.value = value
})

// Po ručním zastavení nahrávání automaticky odešle záznam (jako ChatGPT).
// Při chybě nebo samovolném ukončení rozpoznávání text jen zůstane v poli.
let submitAfterStop = false

const stopAndSubmit = () => {
    submitAfterStop = true
    stop()
}

watch(listening, async (isListening, wasListening) => {
    if (!wasListening || isListening) return

    const shouldSubmit = submitAfterStop
    submitAfterStop = false

    if (shouldSubmit && text.value.trim()) {
        await nextTick()
        onSubmit()
    }
})

// Pro zpracování fotoaparátu
const {
    photo,
    capturedImageURL,
    error: cameraError,
    loading: cameraLoading,
    openCamera,
    uploadPhoto
} = useCamera('/api/upload')

// Aktivace fotoaparátu a zpracování snímku
const handleOpenCamera = async () => {
    const capturedPhoto = await openCamera()
    if (capturedPhoto) {
        const result = await uploadPhoto() as any
        if (result?.food) {
            text.value = result.food
        }
    }
}

// Funkce pro přijetí dat a nastavení proměnných
const updateEntry = (data: { id: bigint; kcal: number; water: number, text: string }) => {
    result.value = { kcal: data.kcal, water: data.water * 0.001, originText: data.text, message: data.text}

    recordId.value = data.id;
    kcal.value = data.kcal;
    waterInL.value = data.water;
    mode.value = 'view'
};

// textový vstup jídla
const text = ref('')
const confirmationMessage = ref<string | null>(null)

// manuální hodnoty
const kcal = ref<number | null>(null)
const recordId = ref<bigint | null>(null)
const waterInL = ref<number | null>(null)
const loadingSave = ref(false)
const error = ref<string | null>(null)

const result = ref<null | { kcal: number; water: number, originText: string, message: string }>(null)

const cancelEdit = () => {
    mode.value = 'view'
}

const onSubmit = async () => {
    if (!text.value.trim()) return

    loadingSave.value = true
    error.value = null

    try {
        const res = await apiFetch<{ kcal: number; waterInMl: number; id: bigint; comment?: string | null }>('/api/calculate', {
            method: 'POST',
            body: {
                food: text.value,
            },
        })

        result.value = {
            kcal: res.kcal,
            water: res.waterInMl * 0.001,
            originText: text.value,
            message: res.comment ?? 'Spočítal jsem orientační hodnoty na základě zadaného textu:',
        }
        kcal.value = res.kcal
        waterInL.value = res.waterInMl * 0.001
        recordId.value = res.id

        text.value = ''
        refreshDiary()
    } catch (e) {
        error.value = 'Nepodařilo se záznam uložit'
    } finally {
        loadingSave.value = false
    }
}

const saveUpdated = async () => {
    if (recordId.value == null) return
    if (kcal.value == null) return
    if (waterInL.value == null) return

    loadingSave.value = true
    error.value = null

    try {
        await apiFetch('/api/save/' + recordId.value, {
            method: 'POST',
            body: {
                id: recordId.value,
                kcal: kcal.value,
                waterInMl: waterInL.value * 1000,
            },
        })

        result.value = { kcal: kcal.value, water: waterInL.value, originText: text.value, message: 'Upravené hodnoty byly uloženy'}
        refreshDiary()
    } catch (e) {
        error.value = 'Nepodařilo se záznam uložit'
    } finally {
        mode.value = 'create'
        loadingSave.value = false
    }
}

const editMode = async () => {
    mode.value = 'edit'
}

const onDelete = async () => {
    if (recordId.value == null) return

    loadingSave.value = true
    error.value = null

    try {
        await apiFetch('/api/delete/' + recordId.value, {
            method: 'POST'
        })
        result.value = null
        confirmationMessage.value = 'Záznam byl odstraněn'
        refreshDiary()
    } catch (e) {
        error.value = 'Nepodařilo se záznam smazat'
    } finally {
        mode.value = 'create'
        loadingSave.value = false
    }
}

const onLogout = async () => {
    await signOut()
}

</script>
