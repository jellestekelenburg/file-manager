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
        class="absolute right-8 bottom-4 flex w-60 flex-col rounded-lg border border-gray-200 bg-white p-4 text-end shadow-lg dark:border-gray-700 dark:bg-gray-900"
    >
        <div class="mb-2 text-sm">
            Uploading {{ props.form.files?.length ?? 0 }} file(s)
        </div>
        <div class="h-1 rounded-md bg-gray-100 dark:bg-gray-800">
            <div
                class="h-full rounded-md bg-gray-900 transition-all dark:bg-gray-200"
                :style="{ width: `${props.form.progress?.percentage ?? 0}%` }"
            ></div>
        </div>
    </div>
</template>

<style scoped></style>
