<template>
    <div class="cc-page">
        <!-- TOP / MOTTO -->
        <header class="cc-nav">
            <div class="cc-container py-3 flex items-center justify-center gap-3">
                <span class="text-sm font-semibold tracking-tight">Jez, co chceš.</span>
                <img src="/logo-mark.png" alt="CalCount" class="w-8 h-8" />
                <span class="text-sm font-semibold tracking-tight">Ale měj přehled.</span>
            </div>
        </header>

        <main class="cc-container pt-3 pb-28">
            <!-- DENNÍ PŘEHLED -->
            <Diary :refresh-token="diaryRefreshToken"/>

            <!-- NOVÝ ZÁZNAM -->
            <section class="mt-6">
                <h2 class="cc-section-title">Nový záznam</h2>

                <div class="relative">
                    <textarea
                        v-model="text"
                        placeholder="Co jsi jedl nebo pil? Např. svíčková s 5 knedlíky, k tomu pivo"
                        class="cc-input min-h-[112px] pb-14 resize-none"
                        @keydown.enter="onTextareaEnter"
                    />

                    <div class="absolute right-2 bottom-2 flex items-center gap-2">
                        <button
                            v-if="supported && !listening"
                            type="button"
                            class="cc-icon-btn"
                            style="color: rgb(var(--cc-accent));"
                            title="Diktovat hlasem"
                            aria-label="Diktovat hlasem"
                            @click="start"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                <path d="M12 14a3 3 0 0 0 3-3V6a3 3 0 1 0-6 0v5a3 3 0 0 0 3 3Zm-7-3a1 1 0 1 0-2 0 9 9 0 0 0 8 8.94V22a1 1 0 1 0 2 0v-2.06A9 9 0 0 0 21 11a1 1 0 1 0-2 0 7 7 0 0 1-14 0Z"/>
                            </svg>
                        </button>

                        <button
                            v-if="listening"
                            type="button"
                            class="cc-icon-btn text-red-400 animate-pulse"
                            title="Zastavit nahrávání a zapsat"
                            aria-label="Zastavit nahrávání a zapsat"
                            @click="stopAndSubmit"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                <rect x="6" y="6" width="12" height="12" rx="2" />
                            </svg>
                        </button>

                        <button
                            type="button"
                            class="cc-icon-btn disabled:opacity-50"
                            style="color: rgb(var(--cc-accent));"
                            :disabled="cameraLoading"
                            title="Vyfotit jídlo"
                            aria-label="Vyfotit jídlo"
                            @click="handleOpenCamera"
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
                </div>

                <img
                    v-if="capturedImageURL"
                    :src="capturedImageURL"
                    alt="Vyfocené jídlo"
                    class="mt-3 w-24 h-24 object-cover rounded-2xl"
                />

                <button
                    type="button"
                    :disabled="loadingSave || !text.trim()"
                    class="mt-3 w-full cc-btn-primary disabled:opacity-50"
                    @click="onSubmit"
                >
                    <span v-if="!loadingSave">Zapsat</span>
                    <span v-else>Počítám a ukládám…</span>
                </button>

                <p class="cc-only-fine-pointer mt-2 text-xs text-center cc-subtle">
                    Enter = zapsat, Shift + Enter = nový řádek
                </p>

                <p v-if="error || cameraError" class="mt-3 text-sm text-red-400">
                    {{ error ?? cameraError }}
                </p>

                <AlertMessage
                    v-if="confirmationMessage !== null"
                    :key="confirmationMessage + confirmationCounter"
                    :message="confirmationMessage"
                    :duration="3000"
                />

                <!-- VÝSLEDEK POSLEDNÍHO ZÁZNAMU -->
                <Transition name="cc-pop-in">
                    <div v-if="result" class="cc-card cc-card-pad mt-4">
                        <div v-if="mode !== 'edit'" class="space-y-3">
                            <p class="text-sm cc-subtle">
                                {{ result.message }}
                            </p>

                            <div class="flex gap-6 text-base font-semibold">
                                <div>🔥 {{ formatKcal(result.kcal) }} kcal</div>
                                <div>💧 {{ formatLiters(result.water) }} l</div>
                            </div>

                            <div class="flex items-center justify-between pt-1">
                                <button type="button" class="cc-link" @click="editMode">
                                    Upravit hodnoty
                                </button>

                                <button type="button" class="cc-btn-danger-text" @click="onDelete">
                                    Smazat
                                </button>
                            </div>
                        </div>

                        <!-- EDIT MODE -->
                        <div v-else class="space-y-4">
                            <div class="grid grid-cols-2 gap-3">
                                <label class="relative">
                                    <span class="sr-only">Kalorie</span>
                                    <input
                                        v-model.number="kcal"
                                        type="number"
                                        min="0"
                                        inputmode="numeric"
                                        class="cc-input pr-14"
                                    />
                                    <span class="absolute right-4 top-1/2 -translate-y-1/2 cc-subtle text-sm">kcal</span>
                                </label>
                                <label class="relative">
                                    <span class="sr-only">Voda v litrech</span>
                                    <input
                                        v-model.number="waterInL"
                                        type="number"
                                        step="any"
                                        min="0"
                                        inputmode="decimal"
                                        class="cc-input pr-10"
                                    />
                                    <span class="absolute right-4 top-1/2 -translate-y-1/2 cc-subtle text-sm">l</span>
                                </label>
                            </div>

                            <div class="flex justify-between items-center">
                                <button type="button" class="cc-subtle text-sm" @click="cancelEdit">
                                    Zrušit
                                </button>

                                <button type="button" class="cc-btn-primary px-6 py-2" @click="saveUpdated">
                                    Uložit
                                </button>
                            </div>
                        </div>
                    </div>
                </Transition>
            </section>
        </main>
    </div>
</template>

<script setup lang="ts">
import {useSpeechToText} from "~/composables/useSpeechToText";
import Diary from "~/Diary.vue";
import {useApiFetch} from "~/composables/useApiFetch";
const { apiFetch } = useApiFetch()

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

// Na počítači Enter odešle, Shift+Enter přidá řádek. Na dotykových zařízeních Enter jen zalomí řádek.
const hasFinePointer = import.meta.client && window.matchMedia?.('(pointer: fine)').matches

const onTextareaEnter = (e: KeyboardEvent) => {
    if (!hasFinePointer || e.shiftKey || e.isComposing) return
    e.preventDefault()
    onSubmit()
}

// Pro zpracování fotoaparátu
const {
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

// textový vstup jídla
const text = ref('')
const confirmationMessage = ref<string | null>(null)
const confirmationCounter = ref(0)

// manuální hodnoty
const kcal = ref<number | null>(null)
const recordId = ref<number | null>(null)
const waterInL = ref<number | null>(null)
const loadingSave = ref(false)
const error = ref<string | null>(null)

const result = ref<null | { kcal: number; water: number, originText: string, message: string }>(null)

const showConfirmation = (message: string) => {
    confirmationMessage.value = message
    confirmationCounter.value += 1
}

const cancelEdit = () => {
    mode.value = 'view'
}

const onSubmit = async () => {
    if (!text.value.trim() || loadingSave.value) return

    loadingSave.value = true
    error.value = null

    try {
        const res = await apiFetch<{ kcal: number; waterInMl: number; id: number; comment?: string | null }>('/api/calculate', {
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
        mode.value = 'view'

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
                kcal: kcal.value,
                waterInMl: waterInL.value * 1000,
            },
        })

        result.value = { kcal: kcal.value, water: waterInL.value, originText: text.value, message: 'Upravené hodnoty byly uloženy'}
        refreshDiary()
    } catch (e) {
        error.value = 'Nepodařilo se záznam uložit'
    } finally {
        mode.value = 'view'
        loadingSave.value = false
    }
}

const editMode = () => {
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
        showConfirmation('Záznam byl odstraněn')
        refreshDiary()
    } catch (e) {
        error.value = 'Nepodařilo se záznam smazat'
    } finally {
        mode.value = 'create'
        loadingSave.value = false
    }
}
</script>
