<script setup lang="ts">
import { CircleCheck, CircleX } from 'lucide-vue-next';
import { computed, onMounted, ref } from 'vue';
import { emitter, SHOW_NOTIFICATION } from '@/composables/event-bus';

const show = ref(false);
const type = ref<'success' | 'error' | ''>('success');
const message = ref('');

const icon = computed(() => (type.value === 'error' ? CircleX : CircleCheck));
const iconClass = computed(() =>
    type.value === 'error' ? 'text-red-600' : 'text-green-600',
);

function close() {
    show.value = false;
    type.value = '';
    message.value = '';
}

onMounted(() => {
    let timeout: number | undefined;
    emitter.on(SHOW_NOTIFICATION, ({ type: t, message: msg }) => {
        show.value = true;
        type.value = t;
        message.value = msg;

        if (timeout) clearTimeout(timeout);
        timeout = setTimeout(() => {
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
            class="fixed bottom-4 left-4 z-999 flex w-70 max-w-full items-center gap-2 rounded-sm border border-gray-400 bg-white px-4 py-3 shadow-md"
        >
            <component
                :is="icon"
                :class="iconClass"
                class="mt-0.5 size-5 shrink-0"
            />
            <p class="text-sm leading-5">
                {{ message }}
            </p>
        </div>
    </transition>
</template>
