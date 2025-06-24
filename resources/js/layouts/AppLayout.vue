<script setup lang="ts">
import { mainNavItems } from '@/constants/nav';
import AppSidebarLayout from '@/layouts/app/AppSidebarLayout.vue';
import type { BreadcrumbItemType } from '@/types';
import { usePage } from '@inertiajs/vue3';
import Toast from 'primevue/toast';

const page = usePage();
const currentPath = new URL(page.props.ziggy.location).pathname;

// Otomatis generate breadcrumbs
const breadcrumbs: BreadcrumbItemType[] = [];

for (const item of mainNavItems) {
    if (item.href && route().current(item.href)) {
        breadcrumbs.push({ title: item.title, href: item.href });
        break;
    }

    if (item.children) {
        const matchedChild = item.children.find((child) => route().current(child.href));
        if (matchedChild) {
            breadcrumbs.push({ title: item.title, href: item.href }); // parent
            breadcrumbs.push({ title: matchedChild.title, href: matchedChild.href }); // child
            break;
        }
    }
}
</script>

<template>
    <AppSidebarLayout :breadcrumbs="breadcrumbs">
        <Toast />
        <slot />
    </AppSidebarLayout>
</template>
