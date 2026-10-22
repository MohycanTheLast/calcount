import { ref } from 'vue'

export function useSpeechToText(lang = 'cs-CZ') {
    const supported = ref(false)
    const listening = ref(false)
    const transcript = ref('')
    const error = ref<string | null>(null)

    let recognition: SpeechRecognition | null = null

    // Kontrola, zda běžíme na klientovi
    if (typeof window !== 'undefined') {
        const SpeechRecognition =
            window.SpeechRecognition || window.webkitSpeechRecognition

        supported.value = !!SpeechRecognition

        if (SpeechRecognition) {
            recognition = new SpeechRecognition()
            recognition.lang = lang
            recognition.interimResults = true
            recognition.continuous = true

            recognition.onresult = (event: SpeechRecognitionEvent) => {
                transcript.value = Array.from(event.results)
                    .map((result: SpeechRecognitionResult) => result[0]?.transcript || '')
                    .join('')
            }

            recognition.onerror = (e) => {
                error.value = e.error
                listening.value = false
            }

            recognition.onend = () => {
                listening.value = false
            }
        }
    }

    const start = () => {
        if (!recognition || listening.value) return
        transcript.value = ''
        error.value = null
        listening.value = true
        recognition.start()
    }

    const stop = () => {
        if (!recognition || !listening.value) return
        recognition.stop()
        // listening.value bude nastaveno na false v onend,
        // aby watcher viděl finální onresult před změnou stavu
    }

    return {
        supported,
        listening,
        transcript,
        error,
        start,
        stop
    }
}