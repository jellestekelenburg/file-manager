<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import Modal from '@/components/Modal.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

const props = defineProps<{
    modelValue: boolean;
}>();

const emit = defineEmits<{
    (event: 'update:modelValue', value: boolean): void;
}>();

const form = useForm({
    name: '',
});
function createFolder() {}
function closeModal() {
    emit('update:modelValue', false);
    form.clearErrors();
    form.reset();
}
</script>

<template>
    <Modal :show="props.modelValue" @close="emit('update:modelValue', false)">
        <div class="p-6">
            <h2 class="text-lg font-medium text-gray-900">Create New Folder</h2>

            <div class="mt-6">
                <label for="folderName"></label>
                <Input
                    type="text"
                    id="folderName"
                    v-model="form.name"
                    class="mt-1 block w-full"
                    :class="
                        form.errors.name
                            ? 'border-red-500 focus:border-red-500 focus:ring-red-500'
                            : ''
                    "
                    placeholder="Folder Name"
                    @keyup.enter="createFolder"
                />
                <InputError :message="form.errors.name" class="mt-2" />

                <div class="flex justify-end">
                    <Button @click="closeModal" :variant="'secondary'" class="ms-auto mt-3">
                        Close
                    </Button>
                </div>
            </div>
        </div>
    </Modal>
</template>
