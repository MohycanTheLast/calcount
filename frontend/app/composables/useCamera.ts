import { ref } from 'vue'
import { useDiaryApi } from '~/api/diary'

export function useCamera() {
    const photo = ref<File | null>(null) // Uložený obrázek
    const error = ref<string | null>(null) // Chyby při práci s kamerou
    const loading = ref(false) // Stav během nahrávání fotografie
    const capturedImageURL = ref<string | null>(null) // URL pro náhled snímku

    const diaryApi = useDiaryApi()

    // Funkce pro otevření kamery a zachycení fotografie
    const openCamera = async () => {
        try {
            const fileInput = document.createElement('input')
            fileInput.type = 'file'
            fileInput.accept = 'image/*'
            fileInput.capture = 'environment' // Upřesňuje použití zadní kamery
            fileInput.click()

            // Po výběru obrázku
            return new Promise<File | null>((resolve) => {
                fileInput.onchange = (event: Event) => {
                    const target = event.target as HTMLInputElement
                    const file = target?.files?.[0] || null
                    photo.value = file
                    error.value = null

                    if (file) {
                        // URL pro náhled obrázku (např. při zobrazení vyfocené fotografie); předchozí uvolnit
                        if (capturedImageURL.value) URL.revokeObjectURL(capturedImageURL.value)
                        capturedImageURL.value = URL.createObjectURL(file)
                    }

                    resolve(file)
                }
            })
        } catch (err) {
            error.value = 'Nepodařilo se otevřít kameru.'
            console.error(err)
            return null
        }
    }

    // Funkce pro odeslání fotografie na BE
    const uploadPhoto = async () => {
        if (!photo.value) {
            error.value = 'Neexistuje žádná fotografie k odeslání.'
            return
        }

        loading.value = true
        error.value = null

        try {
            return await diaryApi.uploadPhoto(photo.value)
        } catch (err: any) {
            error.value = err?.data?.error ?? 'Chyba při odesílání fotografie.'
            console.error(err)
        } finally {
            loading.value = false
        }
    }

    // Zahodí fotku i náhled (uvolní paměť po URL.createObjectURL)
    const clearPhoto = () => {
        if (capturedImageURL.value) URL.revokeObjectURL(capturedImageURL.value)
        capturedImageURL.value = null
        photo.value = null
    }

    return {
        photo,
        capturedImageURL,
        error,
        loading,
        openCamera,
        uploadPhoto,
        clearPhoto
    }
}