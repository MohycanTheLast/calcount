<template>
    <div class="cc-page">
        <header class="cc-nav">
            <div class="cc-container cc-header">
                <div></div>
                <h1 class="cc-header-title">Kalendář</h1>
                <div></div>
            </div>
        </header>

        <main class="cc-container pt-4 pb-28">
            <CalendarMonth :month="month" @update:month="setMonth" />
        </main>
    </div>
</template>

<script setup lang="ts">
definePageMeta({
    middleware: ['auth'],
    layout: 'tabs',
})

const route = useRoute()
const router = useRouter()

const currentMonth = monthRange(new Date()).from

// Zobrazený měsíc je v URL (?m=2026-10) – návrat z detailu dne i obnovení stránky ho zachová.
// Budoucí nebo neplatný měsíc → aktuální.
const month = computed(() => {
    const fromUrl = parseMonthParam(route.query.m)
    return fromUrl && fromUrl <= currentMonth ? fromUrl : currentMonth
})

// replace: přepínání měsíců nemá plnit historii – „Zpět“ z detailu vrací rovnou do kalendáře
const setMonth = (value: Date) => router.replace({ query: { ...route.query, m: formatMonthParam(value) } })
</script>
