// Hlídání pitného režimu, kofeinu a makroživin (Nastavení → Pokročilá nastavení)

// Výchozí denní cíl pitného režimu, když si uživatel nenastaví vlastní (běžné doporučení pro dospělé 1,5–2,5 l)
export const DEFAULT_WATER_GOAL_ML = 2000
// Výchozí denní limit kofeinu: bezpečný příjem pro zdravé dospělé podle EFSA (těhotné 200 mg)
export const DEFAULT_CAFFEINE_LIMIT_MG = 400

export type GoalProgress = {
    ratio: number // 0–1 pro ukazatel
    over: boolean // hodnota přesáhla cíl/limit
}

export const goalProgress = (value: number, goal: number): GoalProgress => ({
    ratio: goal > 0 ? Math.min(1, Math.max(0, value / goal)) : 0,
    over: value > goal,
})

// Makroživiny dne nebo záznamu pro zobrazení (celé gramy)
export const MACRO_LABELS = [
    { key: 'proteinG', label: 'Bílkoviny', short: 'B' },
    { key: 'carbsG', label: 'Sacharidy', short: 'S' },
    { key: 'sugarG', label: 'Cukry', short: 'C' },
    { key: 'fatG', label: 'Tuky', short: 'T' },
] as const

export type MacroKey = (typeof MACRO_LABELS)[number]['key']

// Krátký zápis pro řádek záznamu, např. „B 12 g · S 40 g · C 8 g · T 9 g“; null bez odhadu
export const formatMacrosShort = (values: Record<MacroKey, number | null | undefined>): string | null => {
    if (MACRO_LABELS.every(({ key }) => values[key] == null)) return null
    return MACRO_LABELS.map(({ key, short }) => `${short} ${Math.round(values[key] ?? 0)} g`).join(' · ')
}
