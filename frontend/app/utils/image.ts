// Delší strana fotky před odesláním. AI ji stejně zmenší (kratší strana na 768 px), víc by jen zdržovalo upload.
const MAX_PHOTO_SIDE = 1600
const JPEG_QUALITY = 0.85

// Výsledný rozměr se zachovaným poměrem stran; menší fotka se nezvětšuje
export const fitWithin = (width: number, height: number, maxSide: number) => {
    const scale = Math.min(1, maxSide / Math.max(width, height))
    return { width: Math.round(width * scale), height: Math.round(height * scale) }
}

/**
 * Fotku jídla zmenší a uloží znovu jako JPEG. Překreslením se zahodí všechna metadata (EXIF: poloha GPS,
 * zařízení, čas pořízení) – k AI tak jde jen obraz. Když fotku nejde zpracovat, vyhodí chybu; originál
 * s metadaty se záměrně neposílá.
 */
export const prepareFoodPhoto = async (file: File): Promise<File> => {
    const url = URL.createObjectURL(file)
    try {
        const img = new Image()
        img.src = url
        // Prohlížeč při vykreslení otočí fotku podle EXIF orientace, takže výsledek není „na boku“
        await img.decode()

        const { width, height } = fitWithin(img.naturalWidth, img.naturalHeight, MAX_PHOTO_SIDE)
        const canvas = document.createElement('canvas')
        canvas.width = width
        canvas.height = height
        const ctx = canvas.getContext('2d')
        if (!ctx) throw new Error('Canvas 2D is not available')
        // JPEG nemá průhlednost – průhledné PNG by jinak mělo černé pozadí
        ctx.fillStyle = '#ffffff'
        ctx.fillRect(0, 0, width, height)
        ctx.drawImage(img, 0, 0, width, height)

        const blob = await new Promise<Blob | null>((resolve) => canvas.toBlob(resolve, 'image/jpeg', JPEG_QUALITY))
        if (!blob) throw new Error('Photo could not be encoded')

        return new File([blob], 'photo.jpg', { type: 'image/jpeg' })
    } finally {
        URL.revokeObjectURL(url)
    }
}
