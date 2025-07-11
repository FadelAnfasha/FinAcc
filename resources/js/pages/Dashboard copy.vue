<script setup lang="ts">
import { mainNavItems } from '@/constants/nav'; // ✅ Ambil dari constants
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, usePage } from '@inertiajs/vue3';
import OrganizationChart from 'primevue/organizationchart';
import { ref } from 'vue';
const page = usePage();
const currentPath = new URL(page.props.ziggy.location).pathname;

const breadcrumbs: BreadcrumbItem[] = mainNavItems
    .filter((item) => route().current(item.href))
    .map((item) => ({
        title: item.title,
        href: item.href,
    }));

const data = ref({
    key: '0',
    type: 'person',
    styleClass: '!bg-indigo-100 text-white rounded-xl',
    data: {
        image: 'https://primefaces.org/cdn/primevue/images/avatar/amyelsner.png',
        name: 'Amy Elsner',
        title: 'CEO',
    },
    children: [
        {
            key: '0_0',
            type: 'person',
            styleClass: '!bg-purple-100 text-white rounded-xl',
            data: {
                image: 'https://primefaces.org/cdn/primevue/images/avatar/annafali.png',
                name: 'Anna Fali',
                title: 'CMO',
            },
            children: [
                {
                    label: 'Sales',
                    styleClass: '!bg-purple-100 text-white rounded-xl',
                },
                {
                    label: 'Marketing',
                    styleClass: '!bg-purple-100 text-white rounded-xl',
                },
            ],
        },
        {
            key: '0_1',
            type: 'person',
            styleClass: '!bg-teal-100 text-white rounded-xl',
            data: {
                image: 'https://primefaces.org/cdn/primevue/images/avatar/stephenshaw.png',
                name: 'Stephen Shaw',
                title: 'CTO',
            },
            children: [
                {
                    label: 'Development',
                    styleClass: '!bg-teal-100 text-white rounded-xl',
                },
                {
                    label: 'UI/UX Design',
                    styleClass: '!bg-teal-100 text-white rounded-xl',
                },
            ],
        },
    ],
});
const selection = ref({});
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <!-- <div class="flex h-full flex-1 items-center justify-center rounded-xl p-4">
            <span class="animate-bounce text-7xl">Under Development <i class="pi pi-spin pi-cog" style="font-size: 6rem"></i></span>
        </div> -->
        <template>
            <template>
                <div class="card overflow-x-auto">
                    <OrganizationChart :value="data" collapsible>
                        <template #person="slotProps">
                            <div class="flex flex-col">
                                <div class="flex flex-col items-center">
                                    <img :alt="slotProps.node.data.name" :src="slotProps.node.data.image" class="mb-4 h-12 w-12" />
                                    <span class="mb-2 font-bold">{{ slotProps.node.data.name }}</span>
                                    <span>{{ slotProps.node.data.title }}</span>
                                </div>
                            </div>
                        </template>
                        <template #default="slotProps">
                            <span>{{ slotProps.node.label }}</span>
                        </template>
                    </OrganizationChart>
                </div>
            </template>
        </template>
    </AppLayout>
</template>
