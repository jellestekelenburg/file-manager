<script setup lang="ts">
import { onMounted, ref } from 'vue';
import { emitter, SHOW_ERROR_DIALOG } from '@/composables/event-bus';

const show = ref(false);
const message = ref('');

function removeShow() {
    setTimeout(() => {
        show.value = false;
    }, 4000);
}

onMounted(() => {
    emitter.on(SHOW_ERROR_DIALOG, ({ message: msg }) => {
        show.value = true;
        message.value = msg;
        removeShow();
    });
});
</script>

<template>
    <div
        :class="!show ? 'hidden' : ''" role="alert" aria-live="assertive"
        class="fixed bottom-4 left-8 max-w-100 rounded-md border border-red-200 bg-white p-4 shadow-lg transition-all z-50"
    >
        <h2 class="text-lg font-bold text-red-500">Whoops!</h2>
        <div class="mt-2 text-wrap wrap-break-word">{{ message }}</div>
    </div>
</template>

<style scoped></style>
