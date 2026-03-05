<script setup lang="ts">
type UploadForm = {
    files?: unknown[];
    progress?: {
        percentage?: number;
    } | null;
};

const props = defineProps<{
    form: UploadForm;
}>();
</script>

<template>
    <div
        v-if="props.form.progress"
        class="absolute p-4 right-8 text-end bottom-4 flex w-60 flex-col rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 shadow-lg"
    >
        <div class="mb-2 text-sm">Uploading {{ props.form.files?.length ?? 0 }} file(s)</div>
        <div class="h-1 bg-gray-100 dark:bg-gray-800 rounded-md">
            <div
                class="h-full rounded-md bg-gray-900 dark:bg-gray-200 transition-all"
                :style="{ width: `${props.form.progress?.percentage ?? 0}%` }"
            ></div>
        </div>
    </div>
</template>

<style scoped></style>
