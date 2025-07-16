<script setup lang="ts">
// import NavFooter from '@/components/NavFooter.vue';
import AppLogo from '@/components/AppLogo.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { Link } from '@inertiajs/vue3';
// import { type NavItem } from '@/types';
// import { HandHelping, LifeBuoy } from 'lucide-vue-next';
import { useSidebar } from '@/components/ui/sidebar/utils';
import { mainNavItems } from '@/constants/nav';
import { computed } from 'vue';

const { state } = useSidebar();
const isCollapsed = computed(() => state.value === 'collapsed');
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader class="flex w-full justify-center">
            <Link :href="route('dashboard')">
                <AppLogo :isCollapsed="isCollapsed" />
            </Link>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child> </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <div class="flex flex-col pt-2">
                <NavMain :items="mainNavItems" :isCollapsed="isCollapsed" />
            </div>
        </SidebarContent>

        <SidebarFooter>
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
