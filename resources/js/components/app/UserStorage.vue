<script setup lang="ts">
import { ref } from 'vue';

const props = defineProps<{
    storage?: {
        used_bytes: number;
        max_bytes: number;
        used_formatted: string;
        max_formatted: string;
        percentage: number;
        is_full: boolean;
    };
}>();

const showStorageDetails = ref(false);

function storageShow() {
    showStorageDetails.value = true;
}

function storageHide() {
    showStorageDetails.value = false;
}
</script>

<template>
    <div
        class="my-2 inline-block max-w-60 rounded-lg border transition-all border-gray-200 bg-white px-4 py-2 overflow-hidden"
        @mouseenter="storageShow"
        @mouseleave="storageHide"
    >
            <p class="mb-1 text-sm">My cloud storage</p>
            <div class="h-1 w-50 overflow-hidden rounded-full bg-gray-200">
                <div
                    class="h-1 bg-blue-600"
                    :style="{ width: `${props.storage.percentage}%` }"
                ></div>
            </div>
        <div class="flex justify-between text-xs relative z-0 transition-all" :class="! showStorageDetails ? 'h-0 opacity-0' : 'h-4 opacity-100'">
            <p>{{ props.storage.used_formatted }}</p>
            <p>{{ props.storage.max_formatted }}</p>
        </div>
    </div>
</template>

<style scoped></style>
