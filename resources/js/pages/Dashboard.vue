<script setup lang="ts">
import { mainNavItems } from '@/constants/nav'; // ✅ Ambil dari constants
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, usePage } from '@inertiajs/vue3';

const page = usePage();
const currentPath = new URL(page.props.ziggy.location).pathname;

const breadcrumbs: BreadcrumbItem[] = mainNavItems
    .filter((item) => route().current(item.href))
    .map((item) => ({
        title: item.title,
        href: item.href,
    }));
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 items-center justify-center rounded-xl p-4">
            <span class="animate-bounce text-7xl">Coming Soon <i class="pi pi-spin pi-cog" style="font-size: 6rem"></i></span>
        </div>
    </AppLayout>
</template>
