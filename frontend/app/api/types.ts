// Typy odpovědí a požadavků backendu – jediné místo, kde jsou popsané (později lze generovat z OpenAPI).

export type ThemeMode = 'system' | 'light' | 'dark'

export type AiCommentMode =
    | 'none'
    | 'brief_health'
    | 'funny'
    | 'praise_or_criticize'
    | 'random'

// Odkud backend bere aktivní kalorie dne (UserSettings::ACTIVITY_SOURCES)
export type ActivitySource = 'static' | 'synced' | 'synced_or_static'

export type UserSettings = {
    bmrKcal: number | null
    avgDailyActivityKcal: number | null
    themeMode: ThemeMode
    aiCommentMode: AiCommentMode
    weightKg: number | null
    saveWeight: boolean
    activitySource?: ActivitySource
}

// GET /api/entries (zastaralé, nahrazuje ho /api/days)
export type RecentDay = {
    date: string // d.m.Y
    kcal: number
    kcalBurned: number
    water: number // litry
}

// GET /api/days – jeden den (backend: App\Model\DaySummary)
export type DaySummary = {
    date: string // YYYY-MM-DD
    kcal: number // příjem
    waterMl: number
    entryCount: number
    bmrKcal: number | null
    activeKcal: number | null
    activeSource: 'static' | 'manual' | 'apple_health' | 'strava' | 'none'
    expenditureKcal: number | null // BMR + aktivní kalorie; null bez vyplněného BMR
    balanceKcal: number | null // příjem − výdej; záporné = deficit
}

// GET /api/diary-day/{date} – jeden záznam dne
export type DayEntry = {
    id: number
    text: string
    kcal: number
    water: number // ml
}

// POST /api/calculate
export type CreatedEntry = {
    id: number
    kcal: number
    waterInMl: number
    comment?: string | null
}

// POST /api/save/{id}
export type EntryValues = {
    kcal: number
    waterInMl: number
}

// POST /api/upload
export type PhotoAnalysis = {
    food: string
}

// POST /api/login, /api/token/refresh
export type AuthTokens = {
    access_token: string
    refresh_token: string
    expires_in: number
}
