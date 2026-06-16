<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { Input } from '@/components/ui/input';

type Props = {
    search: boolean;
};
defineProps<Props>();

const form = useForm({
    search: '',
});
</script>

<template>
    <form
        action=""
        :class="!search ? 'hidden' : ''"
        class="absolute right-0 -bottom-1 z-20 translate-y-full rounded-lg border border-gray-200 bg-white px-5 py-6 dark:border-gray-800 dark:bg-gray-900"
    >
        <Input
            type="search"
            v-model="form.search"
            placeholder="Search for files and folders"
            class="w-75 bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700"
        />
    </form>
</template>

<style scoped></style>
