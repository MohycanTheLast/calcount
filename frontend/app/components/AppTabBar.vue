<template>
    <nav class="cc-tabbar" aria-label="Hlavní navigace">
        <div class="cc-container flex">
            <NuxtLink
                v-for="tab in tabs"
                :key="tab.to"
                :to="tab.to"
                class="cc-tab"
                :class="{ 'cc-tab--active': route.path === tab.to }"
                :aria-current="route.path === tab.to ? 'page' : undefined"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                    <path :d="tab.icon" />
                </svg>
                {{ tab.label }}
            </NuxtLink>
        </div>
    </nav>
</template>

<script setup lang="ts">
const route = useRoute()

const tabs = [
    {
        to: '/app',
        label: 'Deník',
        // kniha
        icon: 'M5 3.5A2.5 2.5 0 0 1 7.5 1H19a1 1 0 0 1 1 1v17a1 1 0 0 1-1 1H7.5a.5.5 0 0 0 0 1H19a1 1 0 1 1 0 2H7.5A2.5 2.5 0 0 1 5 20.5v-17ZM9 6a1 1 0 0 0 0 2h7a1 1 0 1 0 0-2H9Zm0 4a1 1 0 1 0 0 2h5a1 1 0 1 0 0-2H9Z',
    },
    {
        to: '/settings',
        label: 'Nastavení',
        // ozubené kolo
        icon: 'M19.14 12.94a7.43 7.43 0 0 0 .05-.94 7.43 7.43 0 0 0-.05-.94l2.03-1.58a.5.5 0 0 0 .12-.64l-1.92-3.32a.5.5 0 0 0-.6-.22l-2.39.96a7.06 7.06 0 0 0-1.63-.94l-.36-2.54A.5.5 0 0 0 13.9 1h-3.8a.5.5 0 0 0-.49.42l-.36 2.54a7.06 7.06 0 0 0-1.63.94l-2.39-.96a.5.5 0 0 0-.6.22L2.71 7.48a.5.5 0 0 0 .12.64l2.03 1.58a7.43 7.43 0 0 0-.05.94c0 .32.02.63.05.94l-2.03 1.58a.5.5 0 0 0-.12.64l1.92 3.32a.5.5 0 0 0 .6.22l2.39-.96c.5.38 1.05.7 1.63.94l.36 2.54a.5.5 0 0 0 .49.42h3.8a.5.5 0 0 0 .49-.42l.36-2.54c.58-.24 1.13-.56 1.63-.94l2.39.96a.5.5 0 0 0 .6-.22l1.92-3.32a.5.5 0 0 0-.12-.64ZM12 15.5A3.5 3.5 0 1 1 15.5 12 3.5 3.5 0 0 1 12 15.5Z',
    },
]
</script>
