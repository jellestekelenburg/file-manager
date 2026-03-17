<script setup lang="ts">
import { onMounted, ref } from 'vue';
import { emitter, SHOW_NOTIFICATION } from '@/composables/event-bus';

const show = ref(false);
const type = ref('success');
const message = ref('');

function close() {
    show.value = false;
    type.value = '';
    message.value = '';
    console.log(show.value);
}

onMounted(() => {
    emitter.on(SHOW_NOTIFICATION, ({ type: t, message: msg }) => {
        show.value = true;
        type.value = t;
        message.value = msg;

        console.log(show.value);
        setTimeout(() => {
            close();
        }, 5000);
    });
});
</script>

<template>
    <transition
        enter-active-class="ease-out duration-300"
        enter-from-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
        enter-to-class="opacity-100 translate-y-0 sm:scale-100"
        leave-active-class="ease-in duration-200"
        leave-from-class="opacity-100 translate-y-0 sm:scale-100"
        leave-to-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
    >
        <div
            v-if="show"
            class="fixed bottom-4 left-4 w-50 rounded-sm bg-white px-4 py-2 text-gray-600 border shadow-md z-[999]"
            :class="{
                'border-emerald-200': type === 'success',
                'border-red-200': type === 'error',
            }"
        >
            {{ message }}
        </div>
    </transition>
</template>
