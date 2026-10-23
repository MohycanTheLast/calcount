import { ref } from 'vue'

export function useCamera(apiUrl: string) {
    const photo = ref<File | null>(null) // Uložený obrázek
    const error = ref<string | null>(null) // Chyby při práci s kamerou
    const loading = ref(false) // Stav během nahrávání fotografie
    const capturedImageURL = ref<string | null>(null) // URL pro náhled snímku

    const { apiFetch } = useApiFetch()

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
                        // URL pro náhled obrázku (např. při zobrazení vyfocené fotografie)
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
        if (!photo.value || !apiUrl) {
            error.value = 'Neexistuje žádná fotografie k odeslání nebo chybí API URL.'
            return
        }

        loading.value = true
        error.value = null

        try {
            const formData = new FormData()
            formData.append('file', photo.value)

            const result = await apiFetch(apiUrl, {
                method: 'POST',
                body: formData,
            })

            return result
        } catch (err: any) {
            error.value = err?.data?.error ?? 'Chyba při odesílání fotografie.'
            console.error(err)
        } finally {
            loading.value = false
        }
    }

    return {
        photo,
        capturedImageURL,
        error,
        loading,
        openCamera,
        uploadPhoto
    }
}