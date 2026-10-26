<template>
    <!-- V body: stránky při přechodu používají transform, uvnitř by position: fixed nefungovalo -->
    <Teleport to="body">
        <!-- UVÍTÁNÍ po prvním přihlášení -->
        <div
            v-if="phase === 'welcome'"
            class="cc-tour-backdrop fixed inset-0 z-[60] flex items-center justify-center p-4"
            role="dialog"
            aria-modal="true"
            aria-labelledby="cc-welcome-title"
        >
            <div class="cc-card cc-card-pad max-w-sm w-full text-center space-y-4">
                <img src="/logo-mark.png" alt="" class="w-12 h-12 mx-auto" />
                <h2 id="cc-welcome-title" class="cc-title">Vítej ve svém deníku!</h2>
                <p class="text-sm cc-subtle">
                    Zapisuj, co sníš a vypiješ – kalorie spočítá AI a ty budeš mít přehled o každém dni.
                    Za chvilku ti ukážu, co kde najdeš.
                </p>
                <button ref="startBtn" type="button" class="w-full cc-btn-primary" @click="startTour">Ukaž mi to</button>
                <button type="button" class="w-full cc-btn cc-subtle" @click="finish">Přeskočit</button>
            </div>
        </div>

        <!-- PRŮVODCE: zvýrazněný prvek + bublina -->
        <div v-else-if="phase === 'tour' && current" class="fixed inset-0 z-[60]" style="touch-action: none;">
            <div
                v-if="spotlight"
                class="cc-tour-spotlight"
                :style="{
                    top: `${spotlight.top}px`,
                    left: `${spotlight.left}px`,
                    width: `${spotlight.width}px`,
                    height: `${spotlight.height}px`,
                }"
            />

            <div
                ref="bubbleEl"
                class="cc-tour-bubble"
                :style="placement
                    ? { top: `${placement.top}px`, left: `${placement.left}px` }
                    : { top: '0px', left: '0px', visibility: 'hidden' }"
                role="dialog"
                aria-modal="true"
                aria-labelledby="cc-tour-title"
                aria-describedby="cc-tour-text"
            >
                <span
                    v-if="placement"
                    class="cc-tour-arrow"
                    :class="placement.side === 'below' ? 'cc-tour-arrow--below' : 'cc-tour-arrow--above'"
                    :style="{ left: `${placement.arrowLeft}px` }"
                    aria-hidden="true"
                />

                <div class="flex items-center justify-between gap-3 text-xs cc-subtle">
                    <span>{{ index + 1 }} / {{ steps.length }}</span>
                    <button type="button" class="underline-offset-4 hover:underline" @click="finish">Ukončit průvodce</button>
                </div>

                <h3 id="cc-tour-title" class="mt-2 text-base font-semibold tracking-tight">{{ current.title }}</h3>
                <p id="cc-tour-text" class="mt-1 text-sm leading-relaxed">{{ current.text }}</p>

                <div class="mt-4 flex items-center justify-between gap-3">
                    <button v-if="index > 0" type="button" class="cc-btn px-4 cc-subtle" @click="showStep(index - 1)">Zpět</button>
                    <span v-else />
                    <button ref="nextBtn" type="button" class="cc-btn-primary px-6" @click="next">
                        {{ isLast ? 'Hotovo' : 'Další' }}
                    </button>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<script setup lang="ts">
import { useRecentDaysQuery } from '~/features/diary/queries'
import { TOUR_STEPS, type TourStep } from '~/features/onboarding/steps'
import { useCurrentUserQuery } from '~/features/account/useAccount'
import { useOnboarding } from '~/features/onboarding/useOnboarding'
import { type BubblePlacement, type Box, placeBubble, spotlightBox } from '~/utils/tour'

// Uvítání a průvodce po Deníku. Patří na stránku Deníku – zvýrazňuje její prvky (atribut data-tour).
const { phase, finish } = useOnboarding()
const { data: currentUser } = useCurrentUserQuery()
// Stejná data jako denní karty (sdílená cache) – průvodce čeká, až se karty vykreslí
const { isPending: daysPending } = useRecentDaysQuery()

const steps = ref<TourStep[]>([])
const index = ref(0)
const spotlight = ref<Box | null>(null)
const placement = ref<BubblePlacement | null>(null)
const bubbleEl = ref<HTMLElement | null>(null)
const nextBtn = ref<HTMLButtonElement | null>(null)
const startBtn = ref<HTMLButtonElement | null>(null)

const current = computed(() => steps.value[index.value] ?? null)
const isLast = computed(() => index.value === steps.value.length - 1)

const findTarget = (step: TourStep) => document.querySelector<HTMLElement>(`[data-tour="${step.target}"]`)

const startTour = () => {
    phase.value = 'tour'
}

const prepareTour = async () => {
    await nextTick()
    // Jen kroky, jejichž prvek je na stránce vidět (bez rozpoznávání řeči chybí tlačítko diktování)
    steps.value = TOUR_STEPS.filter((step) => (findTarget(step)?.getClientRects().length ?? 0) > 0)
    if (!steps.value.length) return finish()
    await showStep(0)
}

const measure = () => {
    const step = current.value
    const el = step && findTarget(step)
    if (!el) return

    const viewport = { width: window.innerWidth, height: window.innerHeight }
    const box = spotlightBox(el.getBoundingClientRect(), viewport)
    const bubble = bubbleEl.value
        ? { width: bubbleEl.value.offsetWidth, height: bubbleEl.value.offsetHeight }
        : { width: 320, height: 180 }

    spotlight.value = box
    placement.value = placeBubble(box, bubble, viewport)
}

const showStep = async (i: number) => {
    index.value = i
    await nextTick()

    const step = current.value
    if (step) findTarget(step)?.scrollIntoView({ block: 'center', inline: 'nearest' })

    // Podruhé po vykreslení textu kroku – bublina mohla změnit výšku
    measure()
    await nextTick()
    measure()
    nextBtn.value?.focus()
}

const next = () => (isLast.value ? finish() : showStep(index.value + 1))

let frame = 0
const scheduleMeasure = () => {
    cancelAnimationFrame(frame)
    frame = requestAnimationFrame(measure)
}

const onKeydown = (e: KeyboardEvent) => {
    if (phase.value === 'idle') return
    if (e.key === 'Escape') finish()
    else if (phase.value === 'tour' && e.key === 'ArrowRight') next()
    else if (phase.value === 'tour' && e.key === 'ArrowLeft' && index.value > 0) showStep(index.value - 1)
}

// Watchery až za definicemi funkcí: s immediate se volají hned při vytvoření komponenty (např. průvodce
// spuštěný z Nastavení při načtených kartách), dřív by funkce ještě neexistovaly.

// Nový uživatel: uvítání hned po načtení údajů o účtu
watch(currentUser, (user) => {
    if (user && !user.onboardingCompleted && phase.value === 'idle') phase.value = 'welcome'
}, { immediate: true })

watch(phase, async (value) => {
    if (value !== 'tour') {
        steps.value = []
        spotlight.value = null
        placement.value = null
    }
    if (value === 'welcome') {
        await nextTick()
        startBtn.value?.focus()
    }
}, { immediate: true })

// Průvodce (z uvítání nebo z Nastavení) se rozběhne, až jsou na stránce denní karty. Při spuštění z Nastavení
// je fáze „tour“ už při vytvoření komponenty – start ale musí počkat na onMounted, dřív obsah Deníku
// (karty, zadávání) při přechodu stránek v DOM ještě není a jeho kroky by se vynechaly.
const maybeStartTour = () => {
    if (phase.value === 'tour' && !daysPending.value && !steps.value.length) prepareTour()
}

watch([phase, daysPending], maybeStartTour)

onMounted(() => {
    window.addEventListener('resize', scheduleMeasure)
    // capture: i posun uvnitř vodorovného seznamu karet
    window.addEventListener('scroll', scheduleMeasure, { capture: true, passive: true })
    window.addEventListener('keydown', onKeydown)
    maybeStartTour()
})

onBeforeUnmount(() => {
    cancelAnimationFrame(frame)
    window.removeEventListener('resize', scheduleMeasure)
    window.removeEventListener('scroll', scheduleMeasure, { capture: true })
    window.removeEventListener('keydown', onKeydown)
})
</script>
