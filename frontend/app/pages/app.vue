<template>
    <Main />
</template>

<script setup lang="ts">
import Main from "~/Main.vue";
definePageMeta({
    middleware: ['auth'],
})
</script>