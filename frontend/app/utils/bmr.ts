export type BmrInput = {
    sex: 'male' | 'female'
    age: number | null
    heightCm: number | null
    weightKg: number | null
}

// Odhad bazálního metabolismu vzorcem Mifflin–St Jeor; null, pokud chybí nebo nejsou kladné vstupy.
//   muž:   10 × váha + 6,25 × výška − 5 × věk + 5
//   žena:  10 × váha + 6,25 × výška − 5 × věk − 161
export const calculateBmr = ({ sex, age, heightCm, weightKg }: BmrInput): number | null => {
    if (!age || age <= 0 || !heightCm || heightCm <= 0 || !weightKg || weightKg <= 0) return null

    const base = 10 * weightKg + 6.25 * heightCm - 5 * age
    return Math.round(base + (sex === 'male' ? 5 : -161))
}
