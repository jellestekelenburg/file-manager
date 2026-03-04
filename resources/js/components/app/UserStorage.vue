<script setup lang="ts">
const props = defineProps<{
    storage: {
        used_bytes: number;
        max_bytes: number;
        used_formatted: string;
        max_formatted: string;
        percentage: number;
        is_full: boolean;
    };
}>();
</script>

<template>
    <h4>storage</h4>

    <div class="h-1 w-50 overflow-hidden rounded-full bg-gray-200">
        <div
            class="h-1 bg-blue-400"
            :style="{ width: `${props.storage.percentage}%` }"
        ></div>
    </div>
        <p class="text-gray-800">{{ props.storage.used_formatted }}</p>
        <p>{{ props.storage.max_formatted }}    </p>
</template>

<style scoped></style>
