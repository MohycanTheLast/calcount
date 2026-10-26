import { onScopeDispose, ref } from 'vue'
import { useDiaryApi } from '~/api/diary'
import type { PhotoAnalysis } from '~/api/types'

// Pauza před opakováním odeslání po síťové chybě
const UPLOAD_RETRY_DELAY_MS = 800

// Síťová chyba = server vůbec neodpověděl (chybí HTTP status)
const isNetworkError = (err: any) => !err?.status

// camera = rovnou fotoaparát, gallery = výběr hotové fotky (pokročilé nastavení photoGalleryEnabled)
export type PhotoSource = 'camera' | 'gallery'

export function useCamera() {
    const photo = ref<File | null>(null) // Uložený obrázek
    const error = ref<string | null>(null) // Chyby při práci s kamerou
    const loading = ref(false) // Stav během nahrávání fotografie
    const capturedImageURL = ref<string | null>(null) // URL pro náhled snímku

    const diaryApi = useDiaryApi()

    // Jeden skrytý <input type="file"> vložený do stránky a znovu používaný. Input jen v paměti (mimo DOM)
    // může iOS Safari při focení uvolnit (fotoaparát zabere hodně paměti, typicky po delší době na pozadí)
    // a „change“ pak nepřijde – vyfocená fotka by se tiše zahodila.
    let fileInput: HTMLInputElement | null = null
    let pendingResolve: ((file: File | null) => void) | null = null

    const settle = (file: File | null) => {
        const resolve = pendingResolve
        pendingResolve = null
        resolve?.(file)
    }

    const getFileInput = () => {
        if (fileInput?.isConnected) return fileInput

        const input = document.createElement('input')
        input.type = 'file'
        input.accept = 'image/*'
        input.tabIndex = -1
        input.setAttribute('aria-hidden', 'true')
        // Neviditelný, ale ne display: none – kvůli spolehlivému click() ve starších prohlížečích
        input.style.cssText = 'position:fixed;left:0;top:0;width:1px;height:1px;opacity:0;pointer-events:none;'

        input.addEventListener('change', () => settle(input.files?.[0] ?? null))
        // Zrušené focení (prohlížeče s událostí „cancel“) – čekající volání neskončí navždy
        input.addEventListener('cancel', () => settle(null))

        document.body.appendChild(input)
        fileInput = input
        return input
    }

    onScopeDispose(() => {
        settle(null)
        fileInput?.remove()
        fileInput = null
    })

    // Vyfocení jídla, nebo výběr hotové fotky z galerie (zapsat jídlo i později)
    const openCamera = async (source: PhotoSource = 'camera') => {
        try {
            const input = getFileInput()
            // Předchozí nedokončené otevření (zavřené bez události) už nečeká
            settle(null)
            // Vyprázdnit, aby „change“ přišel i pro stejný soubor jako minule
            input.value = ''
            // capture otevře rovnou zadní fotoaparát; bez něj telefon nabídne knihovnu fotek
            if (source === 'camera') input.setAttribute('capture', 'environment')
            else input.removeAttribute('capture')

            const file = await new Promise<File | null>((resolve) => {
                pendingResolve = resolve
                input.click()
            })

            if (file) {
                photo.value = file
                error.value = null
                // URL pro náhled obrázku (např. při zobrazení vyfocené fotografie); předchozí uvolnit
                if (capturedImageURL.value) URL.revokeObjectURL(capturedImageURL.value)
                capturedImageURL.value = URL.createObjectURL(file)
            }

            return file
        } catch (err) {
            error.value = 'Nepodařilo se otevřít kameru.'
            console.error(err)
            return null
        }
    }

    // Funkce pro odeslání fotografie na BE
    const uploadPhoto = async (): Promise<PhotoAnalysis | undefined> => {
        const file = photo.value
        if (!file) {
            error.value = 'Neexistuje žádná fotografie k odeslání.'
            return
        }

        loading.value = true
        error.value = null

        try {
            try {
                return await diaryApi.uploadPhoto(file)
            } catch (err: any) {
                // iOS po návratu z pozadí (zamčený telefon, přepnutí do fotoaparátu) občas shodí první
                // požadavek na starém spojení. Rozpoznání fotky nic neukládá, takže jeden nový pokus je bezpečný.
                if (!isNetworkError(err)) throw err
                await new Promise((resolve) => setTimeout(resolve, UPLOAD_RETRY_DELAY_MS))
                return await diaryApi.uploadPhoto(file)
            }
        } catch (err: any) {
            error.value = err?.data?.error ?? (isNetworkError(err)
                ? 'Nepodařilo se spojit se serverem. Zkontroluj připojení a zkus to znovu.'
                : 'Chyba při odesílání fotografie.')
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
