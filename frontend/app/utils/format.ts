const kcalFormatter = new Intl.NumberFormat('cs-CZ', { maximumFractionDigits: 0 })
const litersFormatter = new Intl.NumberFormat('cs-CZ', { maximumFractionDigits: 2 })

export const formatKcal = (kcal: number) => kcalFormatter.format(kcal)

export const formatLiters = (liters: number) => litersFormatter.format(liters)
