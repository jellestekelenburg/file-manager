<script setup lang="ts">

import { useForm } from '@inertiajs/vue3';

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
        class="absolute right-0 -bottom-1 z-20 translate-y-full bg-white p-4"
    >
        <input
            type="search"
            v-model="form.search"
            placeholder="Search for files and folders"
            class="text-900 bg-gray-50 border border-gray-300 px-4 py-2.5"
        />
    </form>
</template>

<style scoped></style>
