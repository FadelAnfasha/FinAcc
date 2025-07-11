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
    styleClass: 'rounded-xl w-3xs !bg-slate-500',
    data: {
        image: 'https://primefaces.org/cdn/primevue/images/avatar/amyelsner.png',
        name: 'Tuti Pustikasari',
        title: 'Finance Director',
    },
    children: [
        {
            key: '0_0',
            type: 'person',
            styleClass: '!bg-slate-500 text-white rounded-xl w-3xs',
            data: {
                image: 'https://primefaces.org/cdn/primevue/images/avatar/annafali.png',
                name: 'Akinori Matsuyama',
                title: 'Deputy Division',
            },
            children: [
                {
                    key: '0_0_0',
                    type: 'person',
                    styleClass: '!bg-slate-500 text-white rounded-xl w-3xs',
                    data: {
                        image: 'https://primefaces.org/cdn/primevue/images/avatar/annafali.png',
                        name: 'Inge William',
                        title: 'Deputy Department',
                    },
                    children: [
                        {
                            key: '0_0_0_0',
                            type: 'person',
                            styleClass: '!bg-slate-500 text-white rounded-xl w-3xs',
                            data: {
                                image: 'https://primefaces.org/cdn/primevue/images/avatar/annafali.png',
                                name: 'Rudi Juniarto',
                                title: 'Staff',
                            },
                        },
                        {
                            key: '0_0_0_1',
                            type: 'person',
                            styleClass: '!bg-slate-500 text-white rounded-xl w-3xs',
                            data: {
                                image: 'https://primefaces.org/cdn/primevue/images/avatar/annafali.png',
                                name: 'Setyaningsih',
                                title: 'Staff',
                            },
                        },
                        {
                            key: '0_0_0_2',
                            type: 'person',
                            styleClass: '!bg-slate-500 text-white rounded-xl w-3xs',
                            data: {
                                image: 'https://primefaces.org/cdn/primevue/images/avatar/annafali.png',
                                name: 'Ayu Lestari',
                                title: 'Staff',
                            },
                        },
                        {
                            key: '0_0_0_3',
                            type: 'person',
                            styleClass: '!bg-slate-500 text-white rounded-xl w-3xs',
                            data: {
                                image: 'https://primefaces.org/cdn/primevue/images/avatar/annafali.png',
                                name: 'Fadel Anfasha Putra',
                                title: 'Staff',
                            },
                        },
                    ],
                },
            ],
        },
    ],
});
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="relative my-8 text-center">
            <h1 class="relative z-10 inline-block bg-white px-4 text-4xl font-semibold text-gray-600 dark:bg-gray-800 dark:text-gray-300">
                Finance & Accounting Team
            </h1>
            <hr class="absolute top-1/2 left-0 z-0 w-full -translate-y-1/2 border-gray-300 dark:border-gray-600" />
        </div>
        <template class="flex h-full flex-1 items-center justify-center rounded-xl p-4">
            <div class="card overflow-x-auto">
                <OrganizationChart :value="data">
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
    </AppLayout>
</template>
