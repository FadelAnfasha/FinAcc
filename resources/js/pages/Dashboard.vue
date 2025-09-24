<script setup lang="ts">
import CustomPersonNode from '@/components/CustomPersonNode.vue';
import { mainNavItems } from '@/constants/nav';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { Position, VueFlow } from '@vue-flow/core';
import Button from 'primevue/button';
import Card from 'primevue/card';
import Panel from 'primevue/panel';
import { shallowRef } from 'vue';
// Import CSS Vue Flow
import '@vue-flow/core/dist/style.css';
import '@vue-flow/core/dist/theme-default.css';

const page = usePage();
const currentPath = new URL(page.props.ziggy.location).pathname;

const breadcrumbs: BreadcrumbItem[] = mainNavItems
    .filter((item) => route().current(item.href))
    .map((item) => ({
        title: item.title,
        href: item.href,
    }));

const customNodeTypes = {
    customPerson: shallowRef(CustomPersonNode),
};

const nodes = [
    {
        id: '1',
        type: 'customPerson',
        position: { x: 100, y: 300 },
        sourcePosition: Position.Right,
        targetPosition: Position.Left,
        data: {
            name: 'Tuti Pustikasari',
            title: 'Finance Director',
            npk: '140025',
            image: '/storage/profilepic/Tuti.png',
        },
    },
    {
        id: '2',
        type: 'customPerson',
        position: { x: 400, y: 300 },
        sourcePosition: Position.Right,
        targetPosition: Position.Left,
        data: {
            name: 'Akinori Matsuyama',
            title: 'Deputy Division',
            npk: '240458',
            image: '/storage/profilepic/Matsuyama.png',
        },
    },
    {
        id: '3',
        type: 'customPerson',
        position: { x: 700, y: 300 },
        sourcePosition: Position.Right,
        targetPosition: Position.Left,
        data: {
            name: 'Inge William',
            title: 'Deputy Department',
            npk: '100101',
            image: '/storage/profilepic/Inge.png',
        },
    },
    {
        id: '4',
        type: 'customPerson',
        position: { x: 1000, y: 0 },
        sourcePosition: Position.Right,
        targetPosition: Position.Left,
        data: {
            name: 'Rudi Juniarto',
            title: 'Staff',
            npk: '140023',
            image: '/storage/profilepic/Rudi.png',
        },
    },
    {
        id: '5',
        type: 'customPerson',
        position: { x: 1000, y: 200 },
        sourcePosition: Position.Right,
        targetPosition: Position.Left,
        data: {
            name: 'Setyaningsih',
            title: 'Staff',
            npk: '140207',
            image: '/storage/profilepic/Setyaningsih.png',
        },
    },
    {
        id: '6',
        type: 'customPerson',
        position: { x: 1000, y: 400 },
        sourcePosition: Position.Right,
        targetPosition: Position.Left,
        data: {
            name: 'Ayu Lestari',
            title: 'Staff',
            npk: '190349',
            image: '/storage/profilepic/Ayu.png',
        },
    },
    {
        id: '7',
        type: 'customPerson',
        position: { x: 1000, y: 600 },
        sourcePosition: Position.Right,
        targetPosition: Position.Left,
        data: {
            name: 'Fadel Anfasha',
            title: 'Staff',
            npk: '240473',
            image: '/storage/profilepic/Fadel.png',
        },
    },
];

const edges = [
    {
        id: 'e1-2',
        source: '1',
        target: '2',
    },
    {
        id: 'e2-3',
        source: '2',
        target: '3',
    },
    {
        id: 'e3-4',
        source: '3',
        target: '4',
    },
    {
        id: 'e3-5',
        source: '3',
        target: '5',
    },
    {
        id: 'e3-6',
        source: '3',
        target: '6',
    },
    {
        id: 'e3-7',
        source: '3',
        target: '7',
    },
];

const cardData = [
    {
        img: 'profile',
        title: 'Profile Configuration',
        subtitle: 'Profile',
        content: 'Change profile configuration like account password, delete account, change website theme.',
        href: 'profile.index',
    },
    {
        img: 'rfs',
        title: 'Request for Service',
        subtitle: 'RFS',
        content: 'This menu is used to submit support requests to developers regarding Information Technology issues.',
        href: 'rfs.index',
        guideHref: '/storage/guidance/rfs.pdf',
    },
    {
        img: 'master_pc',
        title: 'Process Cost',
        subtitle: 'ProCost',
        content:
            'This menu is used to automatically calculate Process Costs. It requires four types of master data: Business Partner, Cycle Time, Sales Quantity, and Wages Distribution.',
        href: 'pc.master',
        guideHref: '/storage/guidance/pc.pdf',
    },
    {
        img: 'report_bom',
        title: 'Bill of Material',
        subtitle: 'BOM',
        content:
            'This menu is used to automatically calculate Bill of Material Report. It requires three types of master data: Material Price, Valve Price, BOM raw data.',
        href: 'bom.master',
        guideHref: '/storage/guidance/bom.pdf',
    },
    {
        img: 'orgChart',
        title: 'Organization Structure',
        subtitle: 'OrgChart',
        content: 'Finance & Accounting Organization Structure.',
        href: '#orgChart',
    },
];

// Fungsi untuk scroll ke section
const scrollToSection = (hash: string) => {
    // Tambahkan : string di sini
    const element = document.querySelector(hash);
    if (element) {
        element.scrollIntoView({ behavior: 'smooth' });
    }
};
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="relative min-h-screen">
            <!-- Gambar sebagai latar belakang -->
            <div
                class="absolute inset-0 z-0 bg-center bg-no-repeat opacity-70"
                style="
                    background-image: url('/storage/images/topy.png');
                    background-size: cover; /* Ganti 'contain' dengan 'cover' */
                    background-attachment: fixed;
                    background-size: 50%;
                "
            ></div>

            <!-- Konten di atas gambar -->
            <div class="relative z-10 px-6 py-2">
                <h1 class="mb-10 text-3xl font-bold text-gray-900 dark:text-white">Feature :</h1>
                <div class="grid grid-cols-1 justify-items-center gap-8 md:grid-cols-2 lg:grid-cols-3">
                    <Card
                        v-for="(card, index) in cardData"
                        :key="index"
                        class="relative"
                        style="width: 25rem; height: 30rem; display: flex; flex-direction: column"
                    >
                        <template #header>
                            <img
                                :alt="card.title + ' header'"
                                class="px-4 pt-4"
                                style="height: 200px; width: 500px"
                                :src="'/finacc/storage/images/' + card.img + '.png'"
                            />
                        </template>
                        <template #title>
                            <div class="text-center">{{ card.title }}</div>
                        </template>
                        <template #subtitle>#{{ card.subtitle }}</template>
                        <template #content>
                            <p class="m-0 flex-grow">
                                {{ card.content.length > 200 ? card.content.substring(0, 200) + '...' : card.content }}
                            </p>
                        </template>
                        <template #footer>
                            <div class="absolute right-4 bottom-4 mt-1 flex justify-end gap-4 px-4 pb-4">
                                <template v-if="card.guideHref">
                                    <a :href="card.guideHref" target="_blank" rel="noopener noreferrer">
                                        <Button label="Guidance" severity="info" class="w-20" />
                                    </a>
                                </template>
                                <template v-if="card.href && card.href.startsWith('#')">
                                    <Button label="Go" class="w-20" @click="scrollToSection(card.href)" />
                                </template>
                                <template v-else>
                                    <Link :href="route(card.href)">
                                        <Button label="Go" class="w-20" />
                                    </Link>
                                </template>
                            </div>
                        </template>
                    </Card>
                </div>

                <section id="orgChart">
                    <Panel
                        header="Finance & Accounting Structure"
                        class="mx-auto my-32 max-w-full text-gray-700 lg:max-w-7xl dark:text-gray-100"
                        style="background-color: rgba(0, 0, 0, 0.7)"
                    >
                        <div style="height: 800px; width: 100%">
                            <VueFlow
                                :nodes="nodes"
                                :edges="edges"
                                :node-types="customNodeTypes"
                                :zoom-on-scroll="false"
                                :zoom-on-pinch="false"
                                :zoom-on-double-click="false"
                                fit-view
                            />
                        </div>
                    </Panel>
                </section>
            </div>
        </div>
    </AppLayout>
</template>
