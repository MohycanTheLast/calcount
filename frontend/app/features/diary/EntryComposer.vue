<template>
    <div>
        <!-- Náhled fotky nad polem: během rozpoznávání a pak dokud se záznam nezapíše -->
        <div v-if="capturedImageURL" class="mb-3 flex items-center gap-3">
            <img
                :src="capturedImageURL"
                alt="Vyfocené jídlo"
                class="w-16 h-16 shrink-0 object-cover rounded-2xl"
            />
            <p class="flex-1 text-sm cc-subtle">
                <template v-if="cameraLoading">Rozpoznávám jídlo na fotce…</template>
                <template v-else-if="photoRecognized">
                    Takhle jsme jídlo z fotky rozpoznali. Text můžeš před zapsáním upravit.
                </template>
                <template v-else>Jídlo na fotce se nepodařilo rozpoznat. Popiš ho prosím textem.</template>
            </p>
            <button
                v-if="!cameraLoading"
                type="button"
                class="cc-icon-btn shrink-0 cc-subtle"
                title="Zahodit fotku"
                aria-label="Zahodit fotku"
                @click="clearPhoto"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                    <path d="M6.7 5.3a1 1 0 0 0-1.4 1.4L10.58 12l-5.3 5.3a1 1 0 1 0 1.42 1.4L12 13.42l5.3 5.3a1 1 0 0 0 1.4-1.42L13.42 12l5.3-5.3a1 1 0 0 0-1.42-1.4L12 10.58 6.7 5.3Z"/>
                </svg>
            </button>
        </div>

        <div class="relative" data-tour="entry-text">
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
                    data-tour="entry-voice"
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
                    data-tour="entry-photo"
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

        <button
            type="button"
            :disabled="saving || !text.trim()"
            class="mt-3 w-full cc-btn-primary disabled:opacity-50"
            @click="onSubmit"
        >
            <span v-if="!saving">Zapsat</span>
            <span v-else>Počítám a ukládám…</span>
        </button>

        <p class="cc-only-fine-pointer mt-2 text-xs text-center cc-subtle">
            Enter = zapsat, Shift + Enter = nový řádek
        </p>

        <p v-if="error || cameraError" class="mt-3 text-sm text-red-400">
            {{ error ?? cameraError }}
        </p>
    </div>
</template>

<script setup lang="ts">
import type { CreatedEntry } from "~/api/types";
import { useDiaryMutations } from "~/features/diary/queries";

// Zadání nového záznamu textem, hlasem nebo fotkou. Po uložení vyšle „created“ s výsledkem od AI.
const props = defineProps<{
    // Den, ke kterému se záznam zapíše (YYYY-MM-DD) – zpětný zápis z detailu dne; bez něj dnešek
    date?: string
}>();

const emit = defineEmits<{
    (e: "created", entry: CreatedEntry): void;
}>();

const { createEntry } = useDiaryMutations();

const text = ref('')
const error = ref<string | null>(null)
const saving = computed(() => createEntry.isPending.value)

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
    uploadPhoto,
    clearPhoto
} = useCamera()

const photoRecognized = ref(false)

// Aktivace fotoaparátu a zpracování snímku – rozpoznané jídlo se jen doplní do pole, uživatel ho může upravit
const handleOpenCamera = async () => {
    const capturedPhoto = await openCamera()
    if (capturedPhoto) {
        photoRecognized.value = false
        const result = await uploadPhoto()
        if (result?.food) {
            text.value = result.food
            photoRecognized.value = true
        }
    }
}

onBeforeUnmount(clearPhoto)

const onSubmit = async () => {
    if (!text.value.trim() || saving.value) return

    error.value = null

    try {
        const entry = await createEntry.mutateAsync({ text: text.value, date: props.date })
        text.value = ''
        clearPhoto()
        emit('created', entry)
    } catch (e: any) {
        // Server vrací srozumitelné hlášky (např. vyčerpaný denní limit AI, příliš dlouhý text)
        error.value = e?.data?.error ?? 'Nepodařilo se záznam uložit'
    }
}
</script>
