<script setup lang="ts">
import { SidebarGroup, SidebarGroupLabel, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';

// Import the route helper if using Ziggy, otherwise define a dummy route function
// import route from 'ziggy-js';
declare function route(name: string): string;

defineProps<{ items: NavItem[] }>();

const page = usePage();
const role = (page.props.auth.user as { role?: string })?.role ?? '';

// Untuk toggle submenu
const expandedItems = ref<string[]>([]);

const toggleExpand = (key: string) => {
    if (expandedItems.value.includes(key)) {
        expandedItems.value = expandedItems.value.filter((k) => k !== key);
    } else {
        expandedItems.value.push(key);
    }
};

const isExpanded = (key: string) => expandedItems.value.includes(key);
</script>

<template>
    <SidebarGroup class="px-2 py-0">
        <SidebarGroupLabel>Menu</SidebarGroupLabel>
        <SidebarMenu>
            <template v-for="item in items" :key="item.title">
                <SidebarMenuItem v-if="!item.onlyFor || item.onlyFor.includes(role)">
                    <SidebarMenuButton size="lg" :is-active="item.href === page.url" :tooltip="item.title">
                        <!-- Tampilkan Link jika tidak punya children -->
                        <Link v-if="!item.children" :href="route(item.href)" class="flex items-center gap-2">
                            <component :is="item.icon" class="h-5 w-5" />
                            <span class="text-sm">{{ item.title }}</span>
                        </Link>

                        <!-- Jika punya children, tampilkan label biasa (non-link) -->
                        <div v-else class="flex items-center gap-2">
                            <component :is="item.icon" class="h-5 w-5" />
                            <span class="text-sm">{{ item.title }}</span>
                        </div>
                    </SidebarMenuButton>

                    <!-- Render submenu jika ada children -->
                    <template v-if="item.children">
                        <SidebarMenu class="pl-4">
                            <SidebarMenuItem v-for="child in item.children" :key="child.title">
                                <SidebarMenuButton size="lg" :is-active="child.href === page.url" :tooltip="child.title">
                                    <Link :href="route(child.href)" class="flex items-center gap-2">
                                        <component :is="child.icon" class="h-4 w-4" />
                                        <span class="text-sm">{{ child.title }}</span>
                                    </Link>
                                </SidebarMenuButton>
                            </SidebarMenuItem>
                        </SidebarMenu>
                    </template>
                </SidebarMenuItem>
            </template>
        </SidebarMenu>
    </SidebarGroup>
</template>
