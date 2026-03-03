<script setup lang="ts">
import type * as events from 'node:events';
import { DropdownMenuItem } from '@/components/ui/dropdown-menu';
import { emitter, FILE_UPLOAD_STARTED } from '@/composables/event-bus';

function onChange(ev: events) {
    emitter.emit(FILE_UPLOAD_STARTED, ev.target.files);
}
</script>

<template>
    <DropdownMenuItem>
        Upload New Folder

        <input
            @change="onChange"
            type="file"
            class="absolute top-0 right-0 bottom-0 left-0 cursor-pointer opacity-0"
            multiple
            directory
            webkitdirectory
        />
    </DropdownMenuItem>
</template>

<style scoped></style>
