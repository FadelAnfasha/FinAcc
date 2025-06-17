<script setup lang="ts">
import { mainNavItems } from '@/constants/nav';
import AppSidebarLayout from '@/layouts/app/AppSidebarLayout.vue';
import type { BreadcrumbItemType } from '@/types';
import { usePage } from '@inertiajs/vue3';
import Toast from 'primevue/toast';

const page = usePage();
const currentPath = new URL(page.props.ziggy.location).pathname;

// Otomatis generate breadcrumbs
const breadcrumbs: BreadcrumbItemType[] = mainNavItems
    .filter((item) => route().current(item.href))
    .map((item) => ({
        title: item.title,
        href: item.href,
    }));
</script>

<template>
    <AppSidebarLayout :breadcrumbs="breadcrumbs">
        <Toast />
        <slot />
    </AppSidebarLayout>
</template>
