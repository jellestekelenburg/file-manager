<script setup lang="ts">
import { useForm, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import file from '@/routes/file';
import { showErrorDialog } from '@/composables/event-bus';

const props = defineProps<{
    deleteAll: {
        type: boolean;
        required: false;
        default: false;
    };
    deleteIds: {
        type: Array<any>;
        required: false;
    };
}>();

const emit = defineEmits(['delete']);
const showModal = ref(false);
const page = usePage();
const deleteFilesForm = useForm({
    all: null,
    ids: [],
    parent_id: null,
});

function clickOnDelete() {
    if (!props.deleteAll && !props.deleteIds.length) {
        showErrorDialog('Please select at least one file or folder to delete')
        return;
    }
    showModal.value = true;
}
function triggerModal() {
    showModal.value = !showModal.value;
}

function onDeleteConfirm() {
    deleteFilesForm.parent_id = page.props.folder.id;
    if (props.deleteAll) {
        deleteFilesForm.all = true;
    } else {
        deleteFilesForm.ids = props.deleteIds;
    }

    deleteFilesForm.delete(file.delete().url, {
        onSuccess: () => {
            showModal.value = false;
            emit('delete');
        },
    });
}
</script>

<template>
    <button
        class="inline-flex h-9 shrink-0 cursor-pointer items-center justify-center gap-2 rounded-md bg-primary px-4 py-2 text-sm font-medium whitespace-nowrap text-primary-foreground transition-all outline-none hover:bg-primary/90 focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:pointer-events-none disabled:opacity-50 has-[>svg]:px-3 aria-invalid:border-destructive aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40 [&_svg]:pointer-events-none [&_svg]:shrink-0 [&_svg:not([class*='size-'])]:size-4"
        @click="clickOnDelete"
    >
        <svg
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 640 640"
            fill="currentColor"
        >
            <path
                d="M240 48L400 48L424 112L544 112L544 160L96 160L96 112L216 112L240 48zM152 576L124.7 208L172.8 208L196.5 528L443.3 528L467 208L515.1 208L487.8 576L151.8 576z"
            />
        </svg>
        Delete
    </button>

    <Dialog :open="showModal">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>Are you sure?</DialogTitle>
            </DialogHeader>

            <DialogFooter>
                <Button @click="onDeleteConfirm" :variant="'destructive'"
                    >Yes, Delete</Button
                >
                <Button @click="triggerModal" :variant="'secondary'">
                    Close
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>

<style scoped></style>
