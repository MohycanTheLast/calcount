<template>
    <div class="cc-page">
        <DiaryHeader />

        <main class="cc-container pt-3 pb-28">
            <!-- DENNÍ PŘEHLED -->
            <DiaryDayCards />

            <!-- NOVÝ ZÁZNAM -->
            <section class="mt-6">
                <h2 class="cc-section-title">Nový záznam</h2>

                <EntryComposer @created="onCreated" />

                <AlertMessage
                    v-if="confirmationMessage !== null"
                    :key="confirmationMessage + confirmationCounter"
                    :message="confirmationMessage"
                    :duration="3000"
                />

                <!-- VÝSLEDEK POSLEDNÍHO ZÁZNAMU -->
                <Transition name="cc-pop-in">
                    <LastEntryCard
                        v-if="lastEntry"
                        :entry="lastEntry"
                        @updated="lastEntry = $event"
                        @deleted="onDeleted"
                    />
                </Transition>
            </section>
        </main>
    </div>
</template>

<script setup lang="ts">
import type { CreatedEntry } from "~/api/types";
import type { LastEntry } from "~/features/diary/LastEntryCard.vue";

definePageMeta({
    middleware: ['auth'],
    tabBar: true,
})

const lastEntry = ref<LastEntry | null>(null)
const confirmationMessage = ref<string | null>(null)
const confirmationCounter = ref(0)

const onCreated = (entry: CreatedEntry) => {
    lastEntry.value = {
        id: entry.id,
        kcal: entry.kcal,
        waterLiters: entry.waterInMl / 1000,
        message: entry.comment ?? 'Spočítal jsem orientační hodnoty na základě zadaného textu:',
    }
}

const onDeleted = () => {
    lastEntry.value = null
    confirmationMessage.value = 'Záznam byl odstraněn'
    confirmationCounter.value += 1
}
</script>
