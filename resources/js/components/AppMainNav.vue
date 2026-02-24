<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    NavigationMenu,
    NavigationMenuItem,
    NavigationMenuList,
    navigationMenuTriggerStyle,
} from '@/components/ui/navigation-menu';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import type { NavItem } from '@/types';

type Props = {
    items: NavItem[];
    variant: 'mobile' | 'desktop';
};

const props = defineProps<Props>();
const emit = defineEmits<{
    createFolder: [];
    createFile: [];
}>();
const { isCurrentUrl, whenCurrentUrl } = useCurrentUrl();

const activeItemStyles = 'bg-accent';
</script>

<template>
    <nav v-if="props.variant === 'mobile'" class="-mx-3 space-y-1">
        <DropdownMenu>
            <DropdownMenuTrigger :as-child="true">
                <Button variant="outline" class="mt-1 w-full justify-start">
                    Create New
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="start" class="w-56">
                <DropdownMenuItem class="mb-4">
                    Create new folder
                </DropdownMenuItem>
                <DropdownMenuItem>
                    Upload New Folder
                </DropdownMenuItem>
                <DropdownMenuItem>
                    Upload File
                </DropdownMenuItem>
            </DropdownMenuContent>
        </DropdownMenu>

        <div class="mb-4"></div>

        <Link
            v-for="item in props.items"
            :key="item.title"
            :href="item.href"
            class="flex items-center gap-x-3 rounded-lg px-3 py-2 text-sm font-medium hover:bg-accent"
            :class="whenCurrentUrl(item.href, activeItemStyles)"
        >
            <component v-if="item.icon" :is="item.icon" class="size-5" />

            <svg
                v-if="item.svg"
                class="size-5 fill-gray-900 dark:fill-white"
                xmlns="http://www.w3.org/2000/svg"
                viewBox="0 0 640 640"
            >
                <path :d="item.svg" />
            </svg>

            {{ item.title }}
        </Link>
    </nav>

    <div v-else class="ml-10 flex h-full items-center gap-3">
        <NavigationMenu class="flex h-full items-stretch">
            <NavigationMenuList class="flex h-full items-stretch space-x-2">
                <NavigationMenuItem
                    v-for="item in props.items"
                    :key="item.title"
                    class="relative flex h-full items-center"
                >
                    <Link
                        :class="[
                            navigationMenuTriggerStyle(),
                            whenCurrentUrl(item.href, activeItemStyles),
                            'h-9 cursor-pointer px-3',
                        ]"
                        :href="item.href"
                    >
                        <component
                            v-if="item.icon"
                            :is="item.icon"
                            class="mr-2 size-4"
                        />

                        <svg
                            v-if="item.svg"
                            class="mr-2 size-4 fill-gray-900 dark:fill-white"
                            xmlns="http://www.w3.org/2000/svg"
                            viewBox="0 0 640 640"
                        >
                            <path :d="item.svg" />
                        </svg>

                        {{ item.title }}
                    </Link>
                    <div
                        v-if="isCurrentUrl(item.href)"
                        class="absolute bottom-0 left-0 h-0.5 w-full translate-y-px bg-black dark:bg-white"
                    ></div>
                </NavigationMenuItem>
            </NavigationMenuList>
        </NavigationMenu>

        <DropdownMenu>
            <DropdownMenuTrigger :as-child="true">
                <Button variant="outline" class="h-9">Create New</Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="start" class="w-56">
                <DropdownMenuItem class="mb-4">
                    Create new folder
                </DropdownMenuItem>
                <DropdownMenuItem>
                    Upload New Folder
                </DropdownMenuItem>
                <DropdownMenuItem>
                    Upload File
                </DropdownMenuItem>
            </DropdownMenuContent>
        </DropdownMenu>
    </div>
</template>
