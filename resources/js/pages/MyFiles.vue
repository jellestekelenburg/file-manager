<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import BreadCrumbs from '@/components/app/BreadCrumbs.vue';
import DeleteFilesButton from '@/components/app/DeleteFilesButton.vue';
import FileIcon from '@/components/app/FileIcon.vue';
import UserStorage from '@/components/app/UserStorage.vue';
import { Checkbox } from '@/components/ui/checkbox';
import { httpGet } from '@/composables/httpHelper';
import FileLayout from '@/layouts/FileLayout.vue';
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
    links: any;
    data: T[];
};

const props = withDefaults(
    defineProps<{
        files: Paginated<FileListItem>;
        folder?: FileListItem | null;
        ancestors?: { data: FileListItem[] };
        storage?: void;
    }>(),
    {
        folder: null,
        ancestors: () => ({ data: [] }),
    },
);
const loadMoreIntersect = ref(null);
const allFiles = ref({
    data: props.files.data,
    next: props.files.links.next,
});
const isLoadingMore = ref(false);
const allSelected = ref(false);
const selected = ref<Record<number, boolean>>({});
const selectedIds = computed(() =>
    Object.entries(selected.value)
        .filter((a) => a[1])
        .map((a) => a[0]),
);

const currentFolderId = computed(() => props.folder?.id ?? null);
let observer: IntersectionObserver | null = null;

function openFolder(file: FileListItem): void {
    if (!file.is_folder || !file.path) {
        return;
    }

    router.visit(myFiles.get({ folder: file.path }));
}
function mergeIncomingTopPage(
    incoming: FileListItem[],
    previousTopPage: FileListItem[] = [],
) {
    const incomingIds = new Set(incoming.map((file) => file.id));
    const previousTopPageIds = new Set(previousTopPage.map((file) => file.id));
    const existingTail = allFiles.value.data.filter(
        (file) =>
            !incomingIds.has(file.id) && !previousTopPageIds.has(file.id),
    );

    allFiles.value.data = [...incoming, ...existingTail];
    allFiles.value.next = props.files.links.next;
}

function loadMore() {
    if (allFiles.value.next === null || isLoadingMore.value) {
        return;
    }

    isLoadingMore.value = true;
    httpGet(allFiles.value.next)
        .then((res) => {
            allFiles.value.data = [...allFiles.value.data, ...res.data];
            allFiles.value.next = res.links.next;
        })
        .finally(() => {
            isLoadingMore.value = false;
        });
}

function onSelectAllChange() {
    allFiles.value.data.forEach((file) => {
        selected.value[file.id] = allSelected.value;
    });
}

function toggleFileSelect(file: FileListItem) {
    selected.value[file.id] = !selected.value[file.id];

    if (!selected.value[file.id]) {
        allSelected.value = false;
    } else {
        let checked = true;

        for (const file of allFiles.value.data) {
            if (!selected.value[file.id]) {
                checked = false;
                break;
            }
        }

        allSelected.value = checked;
    }
}

watch(
    () => currentFolderId.value,
    (newFolderId, oldFolderId) => {
        if (newFolderId === oldFolderId) {
            return;
        }

        allFiles.value = {
            data: props.files.data,
            next: props.files.links.next,
        };
    },
);

watch(
    () => props.files.data,
    (incoming, previous = []) => {
        mergeIncomingTopPage(incoming, previous);
    },
);

onMounted(() => {
    if (!loadMoreIntersect.value) {
        return;
    }

    observer = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => entry.isIntersecting && loadMore());
        },
        {
            rootMargin: '-250px 0px 0px 0px',
        },
    );

    observer.observe(loadMoreIntersect.value);
});

onBeforeUnmount(() => {
    observer?.disconnect();
});
</script>

<template>
    <Head title="Dashboard" />
    <FileLayout>
        <div class="flex items-center justify-between">
            <BreadCrumbs :ancestors="ancestors"></BreadCrumbs>
            <DeleteFilesButton
                :delete-all="allSelected"
                :delete-ids="selectedIds"
            ></DeleteFilesButton>
        </div>
        <div class="mb-6 flex-1 overflow-auto">
            <table class="min-w-full overflow-hidden rounded-2xl">
                <thead class="border-b bg-gray-100 dark:bg-gray-700">
                    <tr>
                        <th
                            class="w-6 py-4 ps-6 text-start text-sm font-medium"
                        >
                            <Checkbox
                                v-model="allSelected"
                                @update:model-value="onSelectAllChange"
                            >
                            </Checkbox>
                        </th>
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
                <tbody>
                    <tr
                        v-for="file of allFiles.data"
                        :key="file.id"
                        @dblclick="openFolder(file)"
                        @click="toggleFileSelect(file)"
                        class="cursor-pointer transition duration-300 ease-in-out not-last:border-b"
                        :class="
                            selected[file.id] || allSelected
                                ? 'bg-blue-50 hover:bg-blue-100'
                                : 'bg-white hover:bg-gray-100 dark:border-b-gray-600 dark:bg-gray-800'
                        "
                    >
                        <td
                            class="w-4 items-center gap-2 py-4 ps-6 text-sm font-medium whitespace-nowrap text-gray-900 dark:text-white"
                        >
                            <Checkbox
                                :model-value="
                                    allSelected || !!selected[file.id]
                                "
                            />
                        </td>
                        <td
                            class="inline-flex items-center gap-2 px-6 py-4 text-sm font-medium whitespace-nowrap text-gray-900 dark:text-white"
                        >
                            <FileIcon :file="file"></FileIcon>
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
                v-if="!allFiles.data.length"
                class="py-8 text-center text-sm text-gray-400"
            >
                There is no data in this folder.
            </div>
            <div ref="loadMoreIntersect"></div>
        </div>

        <UserStorage :storage="storage"></UserStorage>
    </FileLayout>
</template>

<style scoped></style>
