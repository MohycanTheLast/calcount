export type DayBalance = {
    // true = příjem je vyšší než výdej (přebytek), false = deficit (nebo přesně vyrovnáno)
    isOver: boolean
    // rozdíl příjmu a výdeje v kcal, vždy kladný
    differenceKcal: number
    // podíl příjmu na výdeji pro ukazatel průběhu, 0–1
    progress: number
}

// Bilance dne pro zobrazení; null, když výdej neznáme (bez vyplněného BMR)
export const dayBalance = (intakeKcal: number, expenditureKcal: number | null): DayBalance | null => {
    if (!expenditureKcal) return null

    return {
        isOver: intakeKcal > expenditureKcal,
        differenceKcal: Math.abs(expenditureKcal - intakeKcal),
        progress: Math.min(Math.max(intakeKcal / expenditureKcal, 0), 1),
    }
}
