<script setup lang="ts">
import type * as events from 'node:events';
import { useForm, usePage } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';
import { SidebarInset } from '@/components/ui/sidebar';
import { emitter, FILE_UPLOAD_STARTED } from '@/composables/event-bus';
import file from '@/routes/file';

type Props = {
    variant?: 'header' | 'sidebar';
    class?: string;
};

const dragOver = ref(false);
const page = usePage();
const fileUploadForm = useForm({
    files: [],
    parent_id: null as number | null,
});
const currentFolderId = computed<number | null>(() => {
    const folder = page.props.folder as
        | { id?: number; data?: { id?: number } }
        | undefined;

    return folder?.id ?? folder?.data?.id ?? null;
});

const props = defineProps<Props>();
const className = computed(() => props.class);

function onDragOver() {
    dragOver.value = true;
}
function onDragLeave() {
    dragOver.value = false;
}
function handleDrop(ev: events) {
    dragOver.value = false;
    const files = ev.dataTransfer.files;

    if (!files.length) {
        return
    }

    uploadFiles(files)
}

function uploadFiles(files: any) {
    fileUploadForm.parent_id = currentFolderId.value;
    fileUploadForm.files = files;

    fileUploadForm.post(file.store().url);
}

onMounted(() => {
    emitter.on(FILE_UPLOAD_STARTED, uploadFiles);
});
</script>

<template>
    <SidebarInset v-if="props.variant === 'sidebar'" :class="className">
        <slot />
    </SidebarInset>
    <main
        v-else
        class="mx-auto flex h-[calc(100vh-65px)] w-full max-w-7xl flex-1 flex-col overflow-hidden rounded-xl"
        :class="[className, dragOver ? 'justify-center' : '']"
        @drop.prevent="handleDrop"
        @dragover.prevent="onDragOver"
        @dragleave.prevent="onDragLeave"
    >
        <template v-if="dragOver">
            <div class="flex w-full items-center justify-center">
                <div class="border-2 border-dotted border-gray-300 lg:p-16 p-12 rounded-xl text-gray-700">
                    Drop Files here to upload
                </div>
            </div>
        </template>
        <template v-else>
            <slot />
        </template>
    </main>
</template>
