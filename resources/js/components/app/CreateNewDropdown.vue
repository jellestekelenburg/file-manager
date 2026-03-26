<script setup lang="ts">
import { ref } from 'vue';
import CreateNewMenuContent from '@/components/app/CreateNewMenuContent.vue';
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

const isOpen = ref(false);
</script>

<template>
    <DropdownMenu :open="isOpen" @update:open="isOpen = $event">
        <DropdownMenuTrigger :as-child="true">
            <Button variant="outline" :class="props.buttonClass">
                Create New
            </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end" class="w-42">
            <CreateNewMenuContent
                :item-component="DropdownMenuItem"
                @close="isOpen = false"
                @create-folder="emit('create-folder')"
            />
        </DropdownMenuContent>
    </DropdownMenu>
</template>
