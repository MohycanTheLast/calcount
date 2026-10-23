// Backend posílá datum dne jako d.m.Y (DietDiaryEntryDay), detail dne bere YYYY-MM-DD.

const pad = (n: number) => String(n).padStart(2, '0')

export const parseCzDate = (s: string): Date | null => {
    const [d, m, y] = s.split('.').map(Number)
    if (!d || !m || !y) return null
    return new Date(y, m - 1, d)
}

export const parseIsoDate = (s: string): Date | null => {
    const match = /^(\d{4})-(\d{2})-(\d{2})$/.exec(s)
    if (!match) return null
    return new Date(Number(match[1]), Number(match[2]) - 1, Number(match[3]))
}

export const formatCzDate = (d: Date) => `${pad(d.getDate())}.${pad(d.getMonth() + 1)}.${d.getFullYear()}`

export const formatIsoDate = (d: Date) => `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`

const isSameDay = (a: Date, b: Date) =>
    a.getFullYear() === b.getFullYear() && a.getMonth() === b.getMonth() && a.getDate() === b.getDate()

const WEEKDAYS = ['Ne', 'Po', 'Út', 'St', 'Čt', 'Pá', 'So']

// „Dnes“, „Včera“, jinak např. „Po 20. 10.“
export const dayLabel = (d: Date) => {
    const today = new Date()
    const yesterday = new Date()
    yesterday.setDate(today.getDate() - 1)

    if (isSameDay(d, today)) return 'Dnes'
    if (isSameDay(d, yesterday)) return 'Včera'
    return `${WEEKDAYS[d.getDay()]} ${d.getDate()}. ${d.getMonth() + 1}.`
}

// Vždy s datem, např. „Út 22. 10.“ (pro titulek detailu)
export const dayTitle = (d: Date) => `${WEEKDAYS[d.getDay()]} ${d.getDate()}. ${d.getMonth() + 1}.`
