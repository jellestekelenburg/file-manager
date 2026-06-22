<script setup lang="ts">
import { useForm, usePage } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';
import FormProgress from '@/components/app/FormProgress.vue';
import Notification from '@/components/app/global/Notification.vue';
import { SidebarInset } from '@/components/ui/sidebar';
import {
    emitter,
    FILE_UPLOAD_STARTED,
    showErrorNotification,
    showSuccessNotification,
} from '@/composables/event-bus';
import file from '@/routes/file';
import axios from 'axios';

type Props = {
    variant?: 'header' | 'sidebar';
    class?: string;
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
    fileUploadForm.parent_id = currentFolderId.value;
    fileUploadForm.files = files;
    fileUploadForm.relative_paths = [...files].map((f) => f.webkitRelativePath);

    //STEP 1: Checkup
    const { check } = await checkUpload(files, currentFolderId.value);

    console.log(check)

    //STEP 2: Do actual upload

    // fileUploadForm.post(file.store().url, {
    //     onSuccess: () => {
    //         showSuccessNotification(
    //             `${files.length} files have been uploaded.`,
    //         );
    //     },
    //     onError: (errors) => {
    //         let message = '';
    //
    //         if (Object.keys(errors).length > 0) {
    //             message = errors[Object.keys(errors)[0]];
    //         } else {
    //             message = 'Error during file upload, please try again.';
    //         }
    //
    //         showErrorNotification(message);
    //     },
    //     onFinish: () => {
    //         fileUploadForm.clearErrors();
    //         fileUploadForm.reset();
    //     },
    // });
}

async function checkUpload(files: File[], parentId: number | null) {
    const payload = {
        parent_id: parentId,
        files: files.map((file) => ({
            name: file.name,
            size: file.size,
            relative_path: file.webkitRelativePath || '',
        })),
    };

    const { data } = await axios.post('/api/uploads/check', payload);

    if (!data.ok) {
        throw new Error('Upload is not allowed');
    }

    return data;
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
