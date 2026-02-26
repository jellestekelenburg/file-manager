<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import { SidebarInset } from '@/components/ui/sidebar';
import { emitter, FILE_UPLOAD_STARTED } from '@/composables/event-bus';

type Props = {
    variant?: 'header' | 'sidebar';
    class?: string;
};

const dragOver = ref(false);

const props = defineProps<Props>();
const className = computed(() => props.class);

function onDragOver() {
    dragOver.value = true;
}
function onDragLeave() {
    dragOver.value = false;
}
function handleDrop(ev) {
    dragOver.value = false;
    const files = ev.dataTransfer.files;

    if (!files.length) {
        return
    }

    uploadFiles(files)
}

function uploadFiles(files) {
    console.log(files)
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
