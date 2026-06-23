<script setup lang="ts">
import { useForm, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { computed, onMounted, ref } from 'vue';
import FormProgress from '@/components/app/FormProgress.vue';
import Notification from '@/components/app/global/Notification.vue';
import { SidebarInset } from '@/components/ui/sidebar';
import {
    emitter,
    FILE_UPLOAD_STARTED,
    showErrorNotification,
} from '@/composables/event-bus';

type Props = {
    variant?: 'header' | 'sidebar';
    class?: string;
};

type UploadQueueItem = {
    client_id: string;
    file: File;
    name: string;
    size: number;
    relative_path: string;
    status: 'queued' | 'planning' | 'uploading' | 'done' | 'failed';
    progress: number;
    error?: string;
};

const dragOver = ref(false);
const page = usePage();
const fileUploadForm = useForm<{
    files: File[];
    relative_paths: string[];
    parent_id: number | null;
}>({
    files: [],
    relative_paths: [],
    parent_id: null,
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
function handleDrop(ev: DragEvent) {
    dragOver.value = false;
    const files = ev.dataTransfer?.files;

    if (!files?.length) {
        return;
    }

    uploadFiles(files);
}

async function uploadFiles(files: any) {
    const uploadItems = createUploadQueue(files);

    fileUploadForm.parent_id = currentFolderId.value;
    fileUploadForm.files = uploadItems.map((item) => item.file);
    fileUploadForm.relative_paths = uploadItems.map(
        (item) => item.relative_path,
    );
    try {
        //| Step 1:
        //| Check if the upload is possible based on available storage left
        //| Generate a plan for the upload, split larger files from smaller ones
        const plan = await planUpload(uploadItems);

        if (!plan.ok) {
            showErrorNotification(plan.errors?.[0]?.message ?? plan.message);
            return;
        }

        console.log('plan:', plan);

        // Step 2A: upload data.small_file_batches.
        // Missing:
        // - Find File objects by client_id.
        // - Send one FormData request per batch to api.uploads.batches.store.
        // - Update uploadItems status/progress per file.
        if (plan.small_file_batches.length > 0) {
            await uploadInBatches({
                uploadId: plan.upload_id,
                batches: plan.small_file_batches,
                uploadItems,
            });
        }

        // Step 2B: upload data.chunked_files.
        // Missing:
        // - Slice each large File with file.slice(start, end).
        // - POST every chunk to api.uploads.chunks.store.
        // - Call api.uploads.chunks.complete when all chunks are uploaded.
        // - Update uploadItems status/progress per chunk.

        // Step 3: refresh the file list/storage UI after all planned uploads finish.
    } catch (error) {
        handleError(error);
    }
}

function createUploadQueue(files: FileList | File[]): UploadQueueItem[] {
    return Array.from(files).map((file) => ({
        client_id: crypto.randomUUID(),
        file,
        name: file.name,
        size: file.size,
        relative_path: file.webkitRelativePath || '',
        status: 'queued',
        progress: 0,
    }));
}

async function planUpload(uploadItems: UploadQueueItem[]) {
    const { data } = await axios.post('/api/uploads/plan', {
        parent_id: currentFolderId.value,
        files: uploadItems.map(({ client_id, name, size, relative_path }) => ({
            client_id,
            name,
            size,
            relative_path,
        })),
    });

    return data;
}

type UploadPlanBatch = {
    batch_id: string;
    files: string[]; // client_ids
};

async function uploadInBatches({
    uploadId,
    batches,
    uploadItems,
}: {
    uploadId: string;
    batches: UploadPlanBatch[];
    uploadItems: UploadQueueItem[];
}) {
    const itemByClientId = new Map(
        uploadItems.map((item) => [item.client_id, item]),
    );

    for (const batch of batches) {
        const form = new FormData();

        if (currentFolderId.value !== null) {
            form.append('parent_id', String(currentFolderId.value));
        }

        for (const clientId of batch.files) {
            const item = itemByClientId.get(clientId);

            if (!item) {
                throw new Error(
                    `Upload item not found for client_id: ${clientId}`,
                );
            }

            item.status = 'uploading';

            form.append('files[]', item.file);
            form.append('client_ids[]', item.client_id);
            form.append('relative_paths[]', item.relative_path);
        }

        const { data } = await axios.post(
            `/api/uploads/${uploadId}/batches/${batch.batch_id}`,
            form,
        );

        if (!data.ok) {
            throw new Error(data.message ?? 'Batch upload failed.');
        }

        for (const clientId of batch.files) {
            const item = itemByClientId.get(clientId);

            if (item) {
                item.status = 'done';
                item.progress = 100;
            }
        }
    }
}

// TODO:
// Implement this after ChunkUploadController starts storing chunks.
// Keep this separate from uploadInBatches so the flow stays readable.
async function uploadChunkedFiles() {}

function handleError(error: any) {
    if (axios.isAxiosError(error) && error.response) {
        const data = error.response.data;

        showErrorNotification(
            data.errors?.[0]?.message ??
                data.message ??
                'Upload is not allowed.',
        );

        return;
    }
}

onMounted(() => {
    emitter.on(FILE_UPLOAD_STARTED, uploadFiles);
});
</script>

<template>
    <SidebarInset
        :class="[className, dragOver ? 'justify-center' : '']"
        @drop.prevent="handleDrop"
        @dragover.prevent="onDragOver"
        @dragleave.prevent="onDragLeave"
    >
        <template v-if="dragOver">
            <div class="flex w-full items-center justify-center">
                <div
                    class="rounded-xl border-2 border-dotted border-gray-300 p-12 text-gray-700 lg:p-16"
                >
                    Drop Files here to upload!
                </div>
            </div>
        </template>
        <template v-else>
            <slot />
            <FormProgress :form="fileUploadForm" />
            <Notification />
        </template>
    </SidebarInset>
</template>
