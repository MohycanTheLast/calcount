<template>
    <transition name="fade">
        <div v-if="visible" class="mt-10 rounded-2xl bg-emerald-50 border border-emerald-100 p-3">
            <div class="space-y-3">
                <p class="text-sm text-slate-600">
                    {{ message }}
                </p>
            </div>
        </div>
    </transition>
</template>

<script>
export default {
    props: {
        // Zpráva, která se zobrazí
        message: {
            type: String,
            required: true,
        },
        // Délka zobrazení v milisekundách
        duration: {
            type: Number,
            default: 5000, // 5 sekund
        },
    },
    data() {
        return {
            visible: false, // Stavy pro zobrazení alertu
        };
    },
    mounted() {
        // Zobrazíme na začátku a spustíme časovač
        this.visible = true;
        setTimeout(() => {
            this.visible = false; // Po uplynutí času skrýt
        }, this.duration);
    },
};
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.5s;
}
.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>