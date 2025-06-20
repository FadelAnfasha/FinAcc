<script setup lang="ts">
import { SidebarGroup, SidebarGroupLabel, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';

defineProps<{
    items: NavItem[];
}>();

const page = usePage();
const role = (page.props.auth.user as { role?: string })?.role ?? '';
</script>

<template>
    <SidebarGroup class="px-2 py-0">
        <SidebarGroupLabel>Menu</SidebarGroupLabel>
        <SidebarMenu>
            <template v-for="item in items" :key="item.title">
                <SidebarMenuItem v-if="!item.onlyFor || item.onlyFor.includes(role)">
                    <SidebarMenuButton size="lg" :is-active="item.href === page.url" :tooltip="item.title">
                        <Link :href="route(item.href)" class="flex items-center gap-2">
                            <component :is="item.icon" class="h-5 w-5" />
                            <span class="text-sm">{{ item.title }}</span>
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </template>
        </SidebarMenu>
    </SidebarGroup>
</template>
