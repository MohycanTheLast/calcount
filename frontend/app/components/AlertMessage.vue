<template>
    <transition name="fade">
        <div v-if="visible" class="mt-4 cc-chip cc-chip--good w-full justify-center py-2.5 text-sm" role="status">
            {{ message }}
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
