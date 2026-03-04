<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import BreadCrumbs from '@/components/app/BreadCrumbs.vue';
import UserStorage from '@/components/app/UserStorage.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { myFiles } from '@/routes';

type FileListItem = {
    id: number;
    name: string;
    parent_id: number | null;
    owner: string;
    updated_at: string | null;
    size: string | null;
    is_folder: boolean;
    path: string | null;
};

type Paginated<T> = {
    data: T[];
};

const props = withDefaults(
    defineProps<{
        files: Paginated<FileListItem>;
        folder?: FileListItem | null;
        ancestors?: { data: FileListItem[] };
        storage?: void
    }>(),
    {
        folder: null,
        ancestors: () => ({ data: [] }),
    },
);

function openFolder(file: FileListItem): void {
    if (!file.is_folder || !file.path) {
        return;
    }

    router.visit(myFiles.get({ folder: file.path }));
}
</script>

<template>
    <Head title="Dashboard" />
    <AppLayout>

        <BreadCrumbs :ancestors="ancestors"></BreadCrumbs>

        <table class="min-w-full mt-5 rounded-2xl overflow-hidden">
            <thead class="border-b bg-gray-100 dark:bg-gray-700">
                <tr>
                    <th
                        class="px-6 py-4 text-start text-sm font-medium text-gray-900 dark:text-white"
                    >
                        Name
                    </th>
                    <th
                        class="px-6 py-4 text-start text-sm font-medium text-gray-900 dark:text-white"
                    >
                        Owner
                    </th>
                    <th
                        class="px-6 py-4 text-start text-sm font-medium text-gray-900 dark:text-white"
                    >
                        Last modified
                    </th>
                    <th
                        class="px-6 py-4 text-start text-sm font-medium text-gray-900 dark:text-white"
                    >
                        Size
                    </th>
                </tr>
            </thead>
            <tbody v-if="props.files.data.length">
                <tr
                    v-for="file of props.files.data"
                    :key="file.id"
                    @dblclick="openFolder(file)"
                    class="cursor-pointer dark:border-b-gray-600 bg-white dark:bg-gray-800 transition duration-300 ease-in-out hover:bg-gray-100 not-last:border-b"
                >
                    <td
                        class="px-6 py-4 text-sm font-medium whitespace-nowrap text-gray-900 dark:text-white"
                    >
                        {{ file.name }}
                    </td>
                    <td
                        class="px-6 py-4 text-sm font-medium whitespace-nowrap text-gray-900 dark:text-white"
                    >
                        {{ file.owner }}
                    </td>
                    <td
                        class="px-6 py-4 text-sm font-medium whitespace-nowrap text-gray-900 dark:text-white"
                    >
                        {{ file.updated_at }}
                    </td>
                    <td
                        class="px-6 py-4 text-sm font-medium whitespace-nowrap text-gray-900 dark:text-white"
                    >
                        {{ file.size }}
                    </td>
                </tr>
            </tbody>
        </table>
        <div
            v-if="!props.files.data.length"
            class="py-8 text-center text-sm text-gray-400"
        >
            There is no data in this folder.
        </div>

        <UserStorage :storage="storage"></UserStorage>
    </AppLayout>
</template>

<style scoped></style>
