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
    // {
    //     img: 'master_pc',
    //     title: 'Process Cost',
    //     subtitle: 'ProCost',
    //     content:
    //         'This menu is used to automatically calculate Process Costs. It requires four types of master data: Business Partner, Cycle Time, Sales Quantity, and Wages Distribution.',
    //     href: 'pc.master',
    //     guideHref: '/storage/guidance/pc.pdf',
    // },
    {
        img: 'master_pc',
        title: 'Process Cost',
        subtitle: 'ProCost',
        content:
            'This menu is used to automatically calculate Process Costs. It requires four types of master data: Business Partner, Cycle Time, Sales Quantity, and Wages Distribution.',
        href: '#processCost',
        guideHref: '/storage/guidance/pc.pdf',
    },
    {
        img: 'report_bom',
        title: 'Standard Cost',
        subtitle: 'BOM',
        content:
            'This menu is used to automatically explode Bill of Material and calculate Standard/Actual Cost. It requires four types of master data: Standard/Actual Material Price, Valve Price, BOM raw data.',
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

<style>
body {
    box-sizing: border-box;
}

/* Kode ini yang membuat velg berputar */
.wheel-animation {
    animation: rotate 8s linear infinite;
    /* rotate = nama animasi, 8s = durasi 8 detik, linear = kecepatan konstan, infinite = berulang terus */
}

/* Definisi animasi rotasi */
@keyframes rotate {
    from {
        transform: rotate(0deg);
    } /* Mulai dari 0 derajat */
    to {
        transform: rotate(360deg);
    } /* Berakhir di 360 derajat (1 putaran penuh) */
}

.process-step {
    transition: all 0.3s ease;
}

.process-step:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
}

.cost-flow {
    animation: flow 3s ease-in-out infinite;
}

@keyframes flow {
    0%,
    100% {
        opacity: 0.6;
    }
    50% {
        opacity: 1;
    }
}
</style>

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

                <section id="orgChart" class="scroll-mt-16">
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

                <section id="processCost" class="scroll-mt-12">
                    <div
                        class="mx-auto my-32 max-w-full px-6 text-gray-100 lg:max-w-7xl"
                        style="background-color: rgba(0, 0, 0, 0.7); border-radius: 12px; padding: 3rem"
                    >
                        <!-- Header Section -->
                        <div class="mb-16 text-center">
                            <h2 class="mb-6 bg-gradient-to-r from-blue-400 to-purple-500 bg-clip-text text-4xl font-bold text-transparent">
                                Finance & Accounting Structure
                            </h2>
                            <div class="mb-8 flex justify-center">
                                <!-- Animated Wheel SVG -->
                                <div class="wheel-animation">
                                    <svg width="120" height="120" viewBox="0 0 120 120" class="text-blue-400">
                                        <circle cx="60" cy="60" r="50" fill="none" stroke="currentColor" stroke-width="3" />
                                        <circle cx="60" cy="60" r="35" fill="none" stroke="currentColor" stroke-width="2" />
                                        <circle cx="60" cy="60" r="20" fill="none" stroke="currentColor" stroke-width="2" />
                                        <g stroke="currentColor" stroke-width="2">
                                            <line x1="60" y1="10" x2="60" y2="30" />
                                            <line x1="60" y1="90" x2="60" y2="110" />
                                            <line x1="10" y1="60" x2="30" y2="60" />
                                            <line x1="90" y1="60" x2="110" y2="60" />
                                            <line x1="25.86" y1="25.86" x2="39.29" y2="39.29" />
                                            <line x1="80.71" y1="80.71" x2="94.14" y2="94.14" />
                                            <line x1="94.14" y1="25.86" x2="80.71" y2="39.29" />
                                            <line x1="39.29" y1="80.71" x2="25.86" y2="94.14" />
                                        </g>
                                        <circle cx="60" cy="60" r="8" fill="currentColor" />
                                    </svg>
                                </div>
                            </div>
                            <p class="mx-auto max-w-3xl text-xl text-gray-300">
                                Understanding Process Cost in Wheel Manufacturing Industry - An accounting system that tracks production costs through
                                each stage of the manufacturing process
                            </p>
                        </div>

                        <!-- Process Cost Definition -->
                        <div class="mb-12 rounded-xl border border-blue-500/30 bg-gradient-to-r from-blue-900/50 to-purple-900/50 p-8">
                            <h3 class="mb-4 text-2xl font-semibold text-blue-300">What is Process Cost?</h3>
                            <p class="text-lg leading-relaxed text-gray-200">
                                Process Cost is a cost accounting method used to allocate production costs to products that are manufactured in mass
                                and continuous production. In the wheel industry, each production stage has costs that must be calculated and
                                allocated accurately.
                            </p>
                        </div>

                        <!-- Manufacturing Process Steps -->
                        <div class="mb-16">
                            <h3 class="mb-12 text-center text-3xl font-bold text-blue-300">Wheel Manufacturing Process Stages</h3>
                            <div class="grid grid-cols-1 gap-8 md:grid-cols-3">
                                <!-- Step 1: Raw Material -->
                                <div class="process-step rounded-xl border border-gray-600 bg-gradient-to-b from-gray-800 to-gray-900 p-8">
                                    <div class="mb-6 text-center">
                                        <div class="mx-auto mb-4 flex h-20 w-20 items-center justify-center rounded-full bg-red-500">
                                            <svg width="40" height="40" viewBox="0 0 32 32" fill="white">
                                                <rect x="4" y="8" width="24" height="16" rx="2" fill="currentColor" />
                                                <rect x="8" y="12" width="16" height="8" rx="1" fill="rgba(0,0,0,0.3)" />
                                            </svg>
                                        </div>
                                        <h4 class="text-xl font-semibold text-red-300">Raw Material</h4>
                                    </div>
                                    <ul class="space-y-2 text-gray-300">
                                        <li>• Steel Plate/Sheet</li>
                                        <li>• Material Grade</li>
                                        <li>• Waste Factor: 8%</li>
                                        <li>• Cost: $X (example)</li>
                                    </ul>
                                </div>

                                <!-- Step 2: Process -->
                                <div class="process-step rounded-xl border border-gray-600 bg-gradient-to-b from-gray-800 to-gray-900 p-8">
                                    <div class="mb-6 text-center">
                                        <div class="mx-auto mb-4 flex h-20 w-20 items-center justify-center rounded-full bg-blue-500">
                                            <svg width="40" height="40" viewBox="0 0 32 32" fill="white">
                                                <circle cx="16" cy="16" r="12" fill="none" stroke="currentColor" stroke-width="2" />
                                                <circle cx="16" cy="16" r="8" fill="none" stroke="currentColor" stroke-width="2" />
                                                <circle cx="16" cy="16" r="4" fill="currentColor" />
                                                <line x1="16" y1="4" x2="16" y2="8" stroke="currentColor" stroke-width="2" />
                                                <line x1="16" y1="24" x2="16" y2="28" stroke="currentColor" stroke-width="2" />
                                                <line x1="4" y1="16" x2="8" y2="16" stroke="currentColor" stroke-width="2" />
                                                <line x1="24" y1="16" x2="28" y2="16" stroke="currentColor" stroke-width="2" />
                                            </svg>
                                        </div>
                                        <h4 class="text-xl font-semibold text-blue-300">Process</h4>
                                    </div>
                                    <ul class="space-y-2 text-gray-300">
                                        <li>• Steel Forming,Shaping, & Bending</li>
                                        <li>• Precision Cutting</li>
                                        <li>• Welding / Assembly</li>
                                        <li>• Total Cost: $Y</li>
                                    </ul>
                                </div>

                                <!-- Step 3: Finished Goods -->
                                <div class="process-step rounded-xl border border-gray-600 bg-gradient-to-b from-gray-800 to-gray-900 p-8">
                                    <div class="mb-6 text-center">
                                        <div class="mx-auto mb-4 flex h-20 w-20 items-center justify-center rounded-full bg-green-500">
                                            <svg width="40" height="40" viewBox="0 0 32 32" fill="white">
                                                <circle cx="16" cy="16" r="12" fill="none" stroke="currentColor" stroke-width="3" />
                                                <circle cx="16" cy="16" r="8" fill="none" stroke="currentColor" stroke-width="2" />
                                                <circle cx="16" cy="16" r="3" fill="currentColor" />
                                                <path d="M16 4 L18 8 L16 12 L14 8 Z" fill="currentColor" />
                                                <path d="M28 16 L24 18 L20 16 L24 14 Z" fill="currentColor" />
                                                <path d="M16 28 L14 24 L16 20 L18 24 Z" fill="currentColor" />
                                                <path d="M4 16 L8 14 L12 16 L8 18 Z" fill="currentColor" />
                                            </svg>
                                        </div>
                                        <h4 class="text-xl font-semibold text-green-300">Finished Goods</h4>
                                    </div>
                                    <ul class="space-y-2 text-gray-300">
                                        <li>• Quality Control</li>
                                        <li>• Packaging & Labeling</li>
                                        <li>• Delivery</li>
                                        <li>• Final Cost: $Z</li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <!-- Cost Flow Diagram -->
                        <div class="mb-16">
                            <h3 class="mb-12 text-center text-3xl font-bold text-blue-300">Process Cost Components</h3>
                            <div class="rounded-xl border border-gray-600 bg-gray-800/50 p-8">
                                <div class="flex flex-col items-center justify-between space-y-8 lg:flex-row lg:space-y-0 lg:space-x-8">
                                    <!-- Direct Materials -->
                                    <div class="text-center">
                                        <div
                                            class="mx-auto mb-4 flex h-24 w-24 items-center justify-center rounded-full bg-gradient-to-br from-red-500 to-red-600"
                                        >
                                            <span class="text-sm font-bold text-white">Direct<br />Materials</span>
                                        </div>
                                        <!-- <p class="font-semibold text-red-300">$120</p> -->
                                    </div>

                                    <!-- Arrow -->
                                    <div class="cost-flow">
                                        <svg width="60" height="20" viewBox="0 0 60 20" class="text-blue-400">
                                            <path d="M5 10 L50 10 M45 5 L50 10 L45 15" stroke="currentColor" stroke-width="2" fill="none" />
                                        </svg>
                                    </div>

                                    <!-- Direct Labor -->
                                    <div class="text-center">
                                        <div
                                            class="mx-auto mb-4 flex h-24 w-24 items-center justify-center rounded-full bg-gradient-to-br from-orange-500 to-orange-600"
                                        >
                                            <span class="text-sm font-bold text-white">Direct<br />Labor</span>
                                        </div>
                                        <!-- <p class="font-semibold text-orange-300">$110</p> -->
                                    </div>

                                    <!-- Arrow -->
                                    <div class="cost-flow">
                                        <svg width="60" height="20" viewBox="0 0 60 20" class="text-blue-400">
                                            <path d="M5 10 L50 10 M45 5 L50 10 L45 15" stroke="currentColor" stroke-width="2" fill="none" />
                                        </svg>
                                    </div>

                                    <!-- Manufacturing Overhead -->
                                    <div class="text-center">
                                        <div
                                            class="mx-auto mb-4 flex h-24 w-24 items-center justify-center rounded-full bg-gradient-to-br from-purple-500 to-purple-600"
                                        >
                                            <span class="text-sm font-bold text-white">Mfg<br />Overhead</span>
                                        </div>
                                        <!-- <p class="font-semibold text-purple-300">$55</p> -->
                                    </div>

                                    <!-- Arrow -->
                                    <div class="cost-flow">
                                        <svg width="60" height="20" viewBox="0 0 60 20" class="text-blue-400">
                                            <path d="M5 10 L50 10 M45 5 L50 10 L45 15" stroke="currentColor" stroke-width="2" fill="none" />
                                        </svg>
                                    </div>

                                    <!-- Final Product -->
                                    <div class="text-center">
                                        <div
                                            class="mx-auto mb-4 flex h-24 w-24 items-center justify-center rounded-full bg-gradient-to-br from-green-500 to-green-600"
                                        >
                                            <span class="text-sm font-bold text-white">Finished<br />Goods</span>
                                        </div>
                                        <!-- <p class="font-semibold text-green-300">$285</p> -->
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Key Benefits -->
                        <div class="mb-12 grid grid-cols-1 gap-8 md:grid-cols-2">
                            <div class="rounded-xl border border-blue-500/30 bg-gradient-to-br from-blue-900/50 to-blue-800/50 p-6">
                                <h4 class="mb-4 text-xl font-semibold text-blue-300">Benefits of Process Costing</h4>
                                <ul class="space-y-2 text-gray-200">
                                    <li class="flex items-start">
                                        <span class="mr-2 text-green-400">✓</span>
                                        Accurate cost per unit of product
                                    </li>
                                    <li class="flex items-start">
                                        <span class="mr-2 text-green-400">✓</span>
                                        Cost control for each department
                                    </li>
                                    <li class="flex items-start">
                                        <span class="mr-2 text-green-400">✓</span>
                                        Process efficiency evaluation
                                    </li>
                                    <li class="flex items-start">
                                        <span class="mr-2 text-green-400">✓</span>
                                        Proper pricing determination
                                    </li>
                                </ul>
                            </div>

                            <div class="rounded-xl border border-purple-500/30 bg-gradient-to-br from-purple-900/50 to-purple-800/50 p-6">
                                <h4 class="mb-4 text-xl font-semibold text-purple-300">Application in Wheel Industry</h4>
                                <ul class="space-y-2 text-gray-200">
                                    <li class="flex items-start">
                                        <span class="mr-2 text-yellow-400">⚙️</span>
                                        Mass and continuous production
                                    </li>
                                    <li class="flex items-start">
                                        <span class="mr-2 text-yellow-400">⚙️</span>
                                        Homogeneous products (standard wheels)
                                    </li>
                                    <li class="flex items-start">
                                        <span class="mr-2 text-yellow-400">⚙️</span>
                                        Sequential production process
                                    </li>
                                    <li class="flex items-start">
                                        <span class="mr-2 text-yellow-400">⚙️</span>
                                        Cost allocation per department
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </AppLayout>
</template>
