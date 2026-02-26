<script setup lang="ts">
import { DropdownMenuItem } from '@/components/ui/dropdown-menu';
</script>

<template>
    <DropdownMenuItem class="relative">
        Upload File(s)

        <input type="file" class="absolute left-0 top-0 right-0 bottom-0 cursor-pointer opacity-0">
    </DropdownMenuItem>
</template>

<style scoped></style>
