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

// POST /api/register – registered = lze se rovnou přihlásit, verification_pending = čeká na odkaz z e-mailu
export type RegisterResult = {
    status: 'registered' | 'verification_pending'
}

// GET /api/tokens – osobní API token (tajná část se nevrací)
export type PersonalAccessToken = {
    id: number
    name: string
    prefix: string // začátek tokenu pro rozpoznání, např. cc_pat_ab12cd
    createdAt: string // ISO 8601
    lastUsedAt: string | null
}

// POST /api/tokens – plný token je v odpovědi jen jednou; první token může přepnout zdroj aktivních kalorií
export type CreatedPersonalAccessToken = PersonalAccessToken & {
    token: string
    activitySource: ActivitySource
}

// GET /api/user/me
export type CurrentUser = {
    id: number
    user_identifier: string // e-mail
    roles: string[]
    onboardingCompleted: boolean // false = při otevření Deníku se ukáže úvodní průvodce
}
