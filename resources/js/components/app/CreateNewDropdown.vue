<script setup lang="ts">
import { nextTick, ref } from 'vue';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import FileUploadMenuItem from '@/components/app/FileUploadMenuItem.vue';
import FolderUploadMenuItem from '@/components/app/FolderUploadMenuItem.vue';

const props = withDefaults(
    defineProps<{
        buttonClass?: string;
    }>(),
    {
        buttonClass: '',
    },
);

const emit = defineEmits<{
    (event: 'create-folder'): void;
}>();

const isOpen = ref(false);

function handleCreateFolderSelect(event: Event) {
    event.preventDefault();
    isOpen.value = false;

    // Open de modal pas nadat de dropdown volledig gesloten is.
    nextTick(() => {
        requestAnimationFrame(() => emit('create-folder'));
    });
}
</script>

<template>
    <DropdownMenu :open="isOpen" @update:open="isOpen = $event">
        <DropdownMenuTrigger :as-child="true">
            <Button variant="outline" :class="props.buttonClass">
                Create New
            </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="start" class="w-56">
            <DropdownMenuItem class="mb-4" @select="handleCreateFolderSelect">
                Create new folder
            </DropdownMenuItem>
            <FileUploadMenuItem/>
            <FolderUploadMenuItem/>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
