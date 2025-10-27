<script setup lang="ts">
import { SidebarGroup, SidebarGroupLabel, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps<{ items: NavItem[]; isCollapsed: boolean }>();

const page = usePage();

const userRoles = computed<string[]>(() => {
    const user = page.props.auth.user as { roles?: string[] };

    if (user?.roles && Array.isArray(user.roles)) {
        return user.roles;
    }
    return [];
});

/**
 * Memeriksa apakah pengguna saat ini memiliki setidaknya salah satu peran yang dibutuhkan.
 * @param requiredRoles Array dari peran yang diizinkan.
 * @returns true jika pengguna dapat melihat item, false jika tidak.
 */
function canView(requiredRoles: string[] | undefined): boolean {
    if (!requiredRoles || requiredRoles.length === 0) {
        return true;
    }
    return requiredRoles.some((requiredRole) => userRoles.value.includes(requiredRole));
}

const visibleNavItems = computed<NavItem[]>(() => {
    return props.items.reduce((acc: NavItem[], item: NavItem) => {
        if (!item.children) {
            if (canView(item.onlyFor)) {
                acc.push(item);
            }
            return acc;
        }

        const visibleChildren = item.children.filter((child) => canView(child.onlyFor));

        if (visibleChildren.length > 0) {
            acc.push({ ...item, children: visibleChildren });
        }
        return acc;
    }, []);
});

const expandedItems = ref<string[]>(JSON.parse(localStorage.getItem('expandedMenu') || '[]'));

function toggleExpand(key: string) {
    if (expandedItems.value.includes(key)) {
        expandedItems.value = expandedItems.value.filter((k) => k !== key);
    } else {
        expandedItems.value.push(key);
    }
    localStorage.setItem('expandedMenu', JSON.stringify(expandedItems.value));
}

function isExpanded(key: string) {
    return expandedItems.value.includes(key);
}
</script>

<template>
    <SidebarGroup>
        <SidebarGroupLabel class="my-4">Menu</SidebarGroupLabel>
        <SidebarMenu>
            <template v-for="item in visibleNavItems" :key="item.key">
                <SidebarMenuItem v-if="item.children">
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

                    <div v-if="isExpanded(item.key ?? '')" class="mt-1 ml-3">
                        <SidebarMenuItem v-for="child in item.children" :key="child.key">
                            <SidebarMenuButton size="lg" :is-active="route(child.href ?? '') === page.url" :tooltip="isCollapsed ? child.title : ''">
                                <Link
                                    :href="route(child.href ?? '')"
                                    class="flex w-full items-center"
                                    :class="[isCollapsed ? 'w-full justify-center' : 'gap-2']"
                                >
                                    <i :class="['pi', child.icon, isCollapsed ? 'text-xl' : 'h-4 w-4']" />
                                    <span v-if="!isCollapsed" class="text-sm">{{ child.title }}</span>
                                </Link>
                            </SidebarMenuButton>
                        </SidebarMenuItem>
                    </div>
                </SidebarMenuItem>

                <SidebarMenuItem v-else>
                    <SidebarMenuButton size="lg" :is-active="route(item.href ?? '') === page.url" :tooltip="item.title">
                        <Link
                            :href="route(item.href ?? '')"
                            class="flex w-full items-center"
                            :class="[isCollapsed ? 'w-full justify-center' : 'gap-2']"
                        >
                            <i :class="['pi h-5 w-5', item.icon]" />
                            <span v-if="!isCollapsed" class="text-sm">{{ item.title }}</span>
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </template>
        </SidebarMenu>
    </SidebarGroup>
</template>
