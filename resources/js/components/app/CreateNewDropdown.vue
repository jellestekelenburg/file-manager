<script setup lang="ts">
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';

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
</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger :as-child="true">
            <Button variant="outline" :class="props.buttonClass">
                Create New
            </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="start" class="w-56">
            <DropdownMenuItem class="mb-4" @click="emit('create-folder')">
                Create new folder
            </DropdownMenuItem>
            <DropdownMenuItem>
                Upload New Folder
            </DropdownMenuItem>
            <DropdownMenuItem>
                Upload File
            </DropdownMenuItem>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
