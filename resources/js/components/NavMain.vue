<script setup lang="ts">
import { SidebarGroup, SidebarGroupLabel, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';

defineProps<{ items: NavItem[]; isCollapsed: boolean }>();

const page = usePage();
const role = (page.props.auth.user as { role?: string })?.role ?? '';

// Simpan state menu yang terbuka
const expandedItems = ref<string[]>(JSON.parse(localStorage.getItem('expandedMenu') || '[]'));

// Toggle function
function toggleExpand(key: string) {
    if (expandedItems.value.includes(key)) {
        expandedItems.value = expandedItems.value.filter((k) => k !== key);
    } else {
        expandedItems.value.push(key);
    }
    localStorage.setItem('expandedMenu', JSON.stringify(expandedItems.value));
}

// Cek apakah menu terbuka
function isExpanded(key: string) {
    return expandedItems.value.includes(key);
}
</script>

<template>
    <SidebarGroup class="px-2 py-0">
        <SidebarGroupLabel>Menu</SidebarGroupLabel>
        <SidebarMenu>
            <template v-for="item in items" :key="item.key">
                <!-- Parent menu (with children) -->
                <SidebarMenuItem v-if="item.children && (!item.onlyFor || item.onlyFor.includes(role))">
                    <SidebarMenuButton
                        size="lg"
                        :is-active="item.children.some((child) => route(child.href ?? '') === page.url)"
                        :tooltip="isCollapsed ? item.title : ''"
                        @click="toggleExpand(item.key ?? '')"
                        class="justify-between"
                    >
                        <div class="flex items-center" :class="[isCollapsed ? 'w-full justify-center' : 'gap-2']">
                            <i :class="['pi', item.icon, isCollapsed ? 'text-xl' : 'h-5 w-5']" />
                            <span v-if="!isCollapsed" class="text-sm">{{ item.title }}</span>
                        </div>
                        <i v-if="!isCollapsed" :class="['pi', isExpanded(item.key ?? '') ? 'pi-chevron-down' : 'pi-chevron-right']" />
                    </SidebarMenuButton>

                    <!-- Children -->
                    <div v-if="isExpanded(item.key ?? '')" class="mt-1 ml-3">
                        <SidebarMenuItem v-for="child in item.children" :key="child.key">
                            <SidebarMenuButton size="lg" :is-active="route(child.href ?? '') === page.url" :tooltip="isCollapsed ? child.title : ''">
                                <Link
                                    :href="route(child.href ?? '')"
                                    class="flex items-center"
                                    :class="[isCollapsed ? 'w-full justify-center' : 'gap-2']"
                                >
                                    <i :class="['pi', child.icon, isCollapsed ? 'text-xl' : 'h-4 w-4']" />
                                    <span v-if="!isCollapsed" class="text-sm">{{ child.title }}</span>
                                </Link>
                            </SidebarMenuButton>
                        </SidebarMenuItem>
                    </div>
                </SidebarMenuItem>

                <!-- Single (non-parent) -->
                <SidebarMenuItem v-else-if="!item.onlyFor || item.onlyFor.includes(role)">
                    <SidebarMenuButton size="lg" :is-active="route(item.href ?? '') === page.url" :tooltip="item.title">
                        <Link :href="route(item.href ?? '')" class="flex items-center" :class="[isCollapsed ? 'w-full justify-center' : 'gap-2']">
                            <i :class="['pi h-5 w-5', item.icon]" />
                            <span v-if="!isCollapsed" class="text-sm">{{ item.title }}</span>
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </template>
        </SidebarMenu>
    </SidebarGroup>
</template>
