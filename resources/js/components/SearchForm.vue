<script setup lang="ts">

import { useForm } from '@inertiajs/vue3';
import { Input } from '@/components/ui/input';

type Props = {
    search: boolean
}
defineProps<Props>();


const form = useForm({
    search: ''
})

</script>

<template>
    <form
        action=""
        :class="!search ? 'hidden' : ''"
        class="absolute right-0 -bottom-1 z-20 translate-y-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-lg py-6 px-5"
    >
        <Input
            type="search"
            v-model="form.search"
            placeholder="Search for files and folders"
            class="w-75 bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700"
        />
    </form>
</template>

<style scoped></style>
