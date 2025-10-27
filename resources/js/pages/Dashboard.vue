<script setup lang="ts">
import CustomPersonNode from '@/components/CustomPersonNode.vue';
import { mainNavItems } from '@/constants/nav';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { Position, VueFlow } from '@vue-flow/core';
import Button from 'primevue/button';
import Card from 'primevue/card';
import { computed, shallowRef } from 'vue';

// Import CSS Vue Flow
import '@vue-flow/core/dist/style.css';
import '@vue-flow/core/dist/theme-default.css';

const page = usePage();
const currentPath = new URL(page.props.ziggy.location).pathname;

const topActualCost = computed(() =>
    (page.props.topActualCost as any[]).map((topActualCost, index) => ({
        ...topActualCost,
        no: index + 1,
    })),
);

const topQuantity = computed(() =>
    (page.props.topQuantity as any[]).map((topQuantity, index) => ({
        ...topQuantity,
        no: index + 1,
    })),
);

const topDifferenceCost = computed(() =>
    (page.props.topDifferenceCost as any[]).map((topDifferenceCost, index) => ({
        ...topDifferenceCost,
        no: index + 1,
    })),
);

const lastMonth = computed(() => page.props.lastMonth);

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
        position: { x: 500, y: 0 },
        sourcePosition: Position.Bottom,
        targetPosition: Position.Top,
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
        position: { x: 500, y: 200 },
        sourcePosition: Position.Bottom,
        targetPosition: Position.Top,
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
        position: { x: 500, y: 400 },
        sourcePosition: Position.Bottom,
        targetPosition: Position.Top,
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
        position: { x: 250, y: 600 },
        sourcePosition: Position.Bottom,
        targetPosition: Position.Top,
        data: {
            name: 'Rudi Juniarto',
            title: 'Supervisor',
            npk: '140023',
            image: '/storage/profilepic/Rudi.png',
        },
    },
    {
        id: '5',
        type: 'customPerson',
        position: { x: 100, y: 800 },
        sourcePosition: Position.Bottom,
        targetPosition: Position.Top,
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
        position: { x: 400, y: 800 },
        sourcePosition: Position.Bottom,
        targetPosition: Position.Top,
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
        position: { x: 750, y: 600 },
        sourcePosition: Position.Right,
        targetPosition: Position.Left,
        data: {
            name: 'Fadel Anfasha',
            title: 'Supervisor',
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
        id: 'e3-7',
        source: '3',
        target: '7',
    },
    {
        id: 'e4-5',
        source: '4',
        target: '5',
    },
    {
        id: 'e4-6',
        source: '4',
        target: '6',
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
        href: '#standardCost',
        guideHref: '/storage/guidance/bom.pdf',
    },
    // {
    //     img: 'report_bom',
    //     title: 'Standard Cost',
    //     subtitle: 'BOM',
    //     content:
    //         'This menu is used to automatically explode Bill of Material and calculate Standard/Actual Cost. It requires four types of master data: Standard/Actual Material Price, Valve Price, BOM raw data.',
    //     href: 'bom.master',
    //     guideHref: '/storage/guidance/bom.pdf',
    // },
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

/* Realistic Wheel Animation Styles */
.wheel-container {
    display: flex;
    justify-content: center;
    align-items: center;
    width: 200px;
    height: 200px;
    margin: 0 auto;
}

.animated-wheel {
    animation: wheelRotate 4s linear infinite;
    filter: drop-shadow(0 10px 20px rgba(0, 0, 0, 0.3));
    transition: transform 0.3s ease;
}

.animated-wheel:hover {
    transform: scale(1.05);
    filter: drop-shadow(0 15px 30px rgba(74, 144, 226, 0.4));
}

@keyframes wheelRotate {
    from {
        transform: rotate(0deg);
    }
    to {
        transform: rotate(360deg);
    }
}

/* Wheel component animations */
.spokes {
    animation: spokeShine 2s ease-in-out infinite alternate;
}

@keyframes spokeShine {
    0% {
        opacity: 0.8;
    }
    100% {
        opacity: 1;
    }
}

.bolt-pattern {
    animation: boltGlow 3s ease-in-out infinite;
}

@keyframes boltGlow {
    0%,
    100% {
        opacity: 0.7;
    }
    50% {
        opacity: 1;
    }
}

.tread-pattern {
    animation: treadMove 1s ease-in-out infinite alternate;
}

@keyframes treadMove {
    0% {
        opacity: 0.6;
    }
    100% {
        opacity: 0.9;
    }
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

                <section id="orgChart" class="scroll-mt-12">
                    <div class="mx-auto my-32 max-w-full text-gray-100 lg:max-w-7xl dark:text-gray-100" style="height: 1200px; width: 100%">
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
                </section>

                <section id="processCost" class="scroll-mt-12">
                    <div
                        class="mx-auto my-32 max-w-full px-6 text-gray-100 lg:max-w-7xl"
                        style="background-color: rgba(0, 0, 0, 0.9); border-radius: 12px; padding: 3rem"
                    >
                        <!-- Header Section -->
                        <div class="mb-16 text-center">
                            <h2
                                class="mb-6 bg-gradient-to-r from-yellow-400 via-orange-500 to-red-600 bg-clip-text text-4xl font-bold text-transparent"
                            >
                                Process Cost
                            </h2>
                            <div class="mb-8 flex justify-center">
                                <!-- Animated Wheel SVG -->
                                <div class="wheel-container">
                                    <svg width="200" height="200" viewBox="0 0 200 200" class="animated-wheel">
                                        <!-- 3D Rim Base Shadow for Depth -->
                                        <ellipse cx="100" cy="108" rx="92" ry="82" fill="url(#rimShadowGradient)" opacity="0.4" />

                                        <!-- Outer Rim Ring with 3D Depth -->
                                        <circle cx="100" cy="100" r="92" fill="url(#rimOuter3DGradient)" />
                                        <circle cx="100" cy="100" r="88" fill="url(#rimOuterInner3DGradient)" />
                                        <circle cx="100" cy="100" r="84" fill="url(#rimOuterDeep3DGradient)" />

                                        <!-- Rim Lip with Realistic Beveling -->
                                        <circle cx="100" cy="100" r="80" fill="none" stroke="url(#rimLipGradient)" stroke-width="3" />
                                        <circle cx="100" cy="100" r="82" fill="none" stroke="url(#rimLipHighlight)" stroke-width="1" opacity="0.8" />

                                        <!-- Main Rim Body -->
                                        <circle cx="100" cy="100" r="78" fill="url(#rimMain3DGradient)" />
                                        <circle cx="100" cy="100" r="74" fill="url(#rimMainInner3DGradient)" />
                                        <circle cx="100" cy="100" r="70" fill="url(#rimMainDeep3DGradient)" />

                                        <!-- 5-Spoke Design with Ultra Realistic 3D -->
                                        <g class="spokes">
                                            <!-- Spoke Shadows for 3D Depth -->
                                            <g opacity="0.25">
                                                <path
                                                    d="M 100 30 L 90 25 L 110 25 L 100 30 L 95 65 L 105 65 Z"
                                                    fill="#000000"
                                                    transform="rotate(0 100 100) translate(3,3)"
                                                />
                                                <path
                                                    d="M 100 30 L 90 25 L 110 25 L 100 30 L 95 65 L 105 65 Z"
                                                    fill="#000000"
                                                    transform="rotate(72 100 100) translate(3,3)"
                                                />
                                                <path
                                                    d="M 100 30 L 90 25 L 110 25 L 100 30 L 95 65 L 105 65 Z"
                                                    fill="#000000"
                                                    transform="rotate(144 100 100) translate(3,3)"
                                                />
                                                <path
                                                    d="M 100 30 L 90 25 L 110 25 L 100 30 L 95 65 L 105 65 Z"
                                                    fill="#000000"
                                                    transform="rotate(216 100 100) translate(3,3)"
                                                />
                                                <path
                                                    d="M 100 30 L 90 25 L 110 25 L 100 30 L 95 65 L 105 65 Z"
                                                    fill="#000000"
                                                    transform="rotate(288 100 100) translate(3,3)"
                                                />
                                            </g>

                                            <!-- Main 3D Spokes with Realistic Curves -->
                                            <path
                                                d="M 100 30 L 90 25 L 110 25 L 100 30 L 95 65 L 105 65 Z"
                                                fill="url(#spoke3DGradient)"
                                                transform="rotate(0 100 100)"
                                            />
                                            <path
                                                d="M 100 30 L 90 25 L 110 25 L 100 30 L 95 65 L 105 65 Z"
                                                fill="url(#spoke3DGradient)"
                                                transform="rotate(72 100 100)"
                                            />
                                            <path
                                                d="M 100 30 L 90 25 L 110 25 L 100 30 L 95 65 L 105 65 Z"
                                                fill="url(#spoke3DGradient)"
                                                transform="rotate(144 100 100)"
                                            />
                                            <path
                                                d="M 100 30 L 90 25 L 110 25 L 100 30 L 95 65 L 105 65 Z"
                                                fill="url(#spoke3DGradient)"
                                                transform="rotate(216 100 100)"
                                            />
                                            <path
                                                d="M 100 30 L 90 25 L 110 25 L 100 30 L 95 65 L 105 65 Z"
                                                fill="url(#spoke3DGradient)"
                                                transform="rotate(288 100 100)"
                                            />

                                            <!-- Spoke Center Lines for 3D Effect -->
                                            <line
                                                x1="100"
                                                y1="30"
                                                x2="100"
                                                y2="65"
                                                stroke="url(#spokeCenter3DGradient)"
                                                stroke-width="2"
                                                transform="rotate(0 100 100)"
                                            />
                                            <line
                                                x1="100"
                                                y1="30"
                                                x2="100"
                                                y2="65"
                                                stroke="url(#spokeCenter3DGradient)"
                                                stroke-width="2"
                                                transform="rotate(72 100 100)"
                                            />
                                            <line
                                                x1="100"
                                                y1="30"
                                                x2="100"
                                                y2="65"
                                                stroke="url(#spokeCenter3DGradient)"
                                                stroke-width="2"
                                                transform="rotate(144 100 100)"
                                            />
                                            <line
                                                x1="100"
                                                y1="30"
                                                x2="100"
                                                y2="65"
                                                stroke="url(#spokeCenter3DGradient)"
                                                stroke-width="2"
                                                transform="rotate(216 100 100)"
                                            />
                                            <line
                                                x1="100"
                                                y1="30"
                                                x2="100"
                                                y2="65"
                                                stroke="url(#spokeCenter3DGradient)"
                                                stroke-width="2"
                                                transform="rotate(288 100 100)"
                                            />

                                            <!-- Spoke Highlights for Ultra Realism -->
                                            <path
                                                d="M 100 30 L 95 27 L 100 32 L 98 65"
                                                fill="url(#spokeHighlight3D)"
                                                transform="rotate(0 100 100)"
                                                opacity="0.7"
                                            />
                                            <path
                                                d="M 100 30 L 95 27 L 100 32 L 98 65"
                                                fill="url(#spokeHighlight3D)"
                                                transform="rotate(72 100 100)"
                                                opacity="0.7"
                                            />
                                            <path
                                                d="M 100 30 L 95 27 L 100 32 L 98 65"
                                                fill="url(#spokeHighlight3D)"
                                                transform="rotate(144 100 100)"
                                                opacity="0.7"
                                            />
                                            <path
                                                d="M 100 30 L 95 27 L 100 32 L 98 65"
                                                fill="url(#spokeHighlight3D)"
                                                transform="rotate(216 100 100)"
                                                opacity="0.7"
                                            />
                                            <path
                                                d="M 100 30 L 95 27 L 100 32 L 98 65"
                                                fill="url(#spokeHighlight3D)"
                                                transform="rotate(288 100 100)"
                                                opacity="0.7"
                                            />
                                        </g>

                                        <!-- Center Hub with Ultra Realistic 3D -->
                                        <ellipse cx="100" cy="104" rx="28" ry="25" fill="url(#hubShadow3DGradient)" opacity="0.3" />
                                        <circle cx="100" cy="100" r="28" fill="url(#hub3DGradient)" />
                                        <circle cx="100" cy="100" r="24" fill="url(#hubInner3DGradient)" />
                                        <circle cx="100" cy="100" r="20" fill="url(#hubDeep3DGradient)" />
                                        <circle cx="100" cy="100" r="16" fill="url(#hubCore3DGradient)" />

                                        <!-- Hub Rings for Detail -->
                                        <circle cx="100" cy="100" r="26" fill="none" stroke="url(#hubRing1Gradient)" stroke-width="1" opacity="0.8" />
                                        <circle cx="100" cy="100" r="22" fill="none" stroke="url(#hubRing2Gradient)" stroke-width="1" opacity="0.6" />
                                        <circle cx="100" cy="100" r="18" fill="none" stroke="url(#hubRing3Gradient)" stroke-width="1" opacity="0.4" />

                                        <!-- Center Logo Area -->
                                        <circle cx="100" cy="100" r="12" fill="url(#hubCenter3DGradient)" />
                                        <circle cx="100" cy="100" r="8" fill="url(#hubCenterDeep3DGradient)" />

                                        <!-- 5-Bolt Pattern with Ultra Realistic 3D -->
                                        <g class="bolt-pattern">
                                            <!-- Bolt Holes Shadows -->
                                            <circle cx="101" cy="82" r="4" fill="#000000" opacity="0.4" />
                                            <circle cx="118" cy="94" r="4" fill="#000000" opacity="0.4" />
                                            <circle cx="118" cy="106" r="4" fill="#000000" opacity="0.4" />
                                            <circle cx="101" cy="118" r="4" fill="#000000" opacity="0.4" />
                                            <circle cx="82" cy="106" r="4" fill="#000000" opacity="0.4" />
                                            <circle cx="82" cy="94" r="4" fill="#000000" opacity="0.4" />

                                            <!-- Main Bolt Holes -->
                                            <circle cx="100" cy="81" r="4" fill="url(#boltHole3DGradient)" />
                                            <circle cx="117" cy="93" r="4" fill="url(#boltHole3DGradient)" />
                                            <circle cx="117" cy="107" r="4" fill="url(#boltHole3DGradient)" />
                                            <circle cx="100" cy="119" r="4" fill="url(#boltHole3DGradient)" />
                                            <circle cx="83" cy="107" r="4" fill="url(#boltHole3DGradient)" />
                                            <circle cx="83" cy="93" r="4" fill="url(#boltHole3DGradient)" />

                                            <!-- Bolt Hole Inner Depth -->
                                            <circle cx="100" cy="81" r="2.5" fill="url(#boltHoleInner3DGradient)" />
                                            <circle cx="117" cy="93" r="2.5" fill="url(#boltHoleInner3DGradient)" />
                                            <circle cx="117" cy="107" r="2.5" fill="url(#boltHoleInner3DGradient)" />
                                            <circle cx="100" cy="119" r="2.5" fill="url(#boltHoleInner3DGradient)" />
                                            <circle cx="83" cy="107" r="2.5" fill="url(#boltHoleInner3DGradient)" />
                                            <circle cx="83" cy="93" r="2.5" fill="url(#boltHoleInner3DGradient)" />

                                            <!-- Bolt Hole Highlights -->
                                            <circle cx="99" cy="80" r="1" fill="#ffffff" opacity="0.6" />
                                            <circle cx="116" cy="92" r="1" fill="#ffffff" opacity="0.6" />
                                            <circle cx="116" cy="106" r="1" fill="#ffffff" opacity="0.6" />
                                            <circle cx="99" cy="118" r="1" fill="#ffffff" opacity="0.6" />
                                            <circle cx="82" cy="106" r="1" fill="#ffffff" opacity="0.6" />
                                            <circle cx="82" cy="92" r="1" fill="#ffffff" opacity="0.6" />
                                        </g>

                                        <!-- Ultra Realistic 3D Gradients -->
                                        <defs>
                                            <!-- Rim Shadow -->
                                            <radialGradient id="rimShadowGradient" cx="0.5" cy="0.3">
                                                <stop offset="0%" stop-color="#000000" stop-opacity="0" />
                                                <stop offset="70%" stop-color="#000000" stop-opacity="0.3" />
                                                <stop offset="100%" stop-color="#000000" stop-opacity="0.6" />
                                            </radialGradient>

                                            <!-- Outer Rim 3D Gradients -->
                                            <radialGradient id="rimOuter3DGradient" cx="0.2" cy="0.2">
                                                <stop offset="0%" stop-color="#ffffff" />
                                                <stop offset="15%" stop-color="#e2e8f0" />
                                                <stop offset="35%" stop-color="#cbd5e1" />
                                                <stop offset="60%" stop-color="#94a3b8" />
                                                <stop offset="85%" stop-color="#64748b" />
                                                <stop offset="100%" stop-color="#475569" />
                                            </radialGradient>
                                            <radialGradient id="rimOuterInner3DGradient" cx="0.25" cy="0.25">
                                                <stop offset="0%" stop-color="#f1f5f9" />
                                                <stop offset="40%" stop-color="#cbd5e1" />
                                                <stop offset="80%" stop-color="#64748b" />
                                                <stop offset="100%" stop-color="#334155" />
                                            </radialGradient>
                                            <radialGradient id="rimOuterDeep3DGradient" cx="0.3" cy="0.3">
                                                <stop offset="0%" stop-color="#e2e8f0" />
                                                <stop offset="50%" stop-color="#94a3b8" />
                                                <stop offset="100%" stop-color="#1e293b" />
                                            </radialGradient>

                                            <!-- Rim Lip Gradients -->
                                            <linearGradient id="rimLipGradient" x1="0%" y1="0%" x2="100%" y2="100%">
                                                <stop offset="0%" stop-color="#ffffff" />
                                                <stop offset="30%" stop-color="#cbd5e1" />
                                                <stop offset="70%" stop-color="#64748b" />
                                                <stop offset="100%" stop-color="#334155" />
                                            </linearGradient>
                                            <linearGradient id="rimLipHighlight" x1="0%" y1="0%" x2="50%" y2="50%">
                                                <stop offset="0%" stop-color="#ffffff" />
                                                <stop offset="100%" stop-color="#e2e8f0" />
                                            </linearGradient>

                                            <!-- Main Rim Body Gradients -->
                                            <radialGradient id="rimMain3DGradient" cx="0.25" cy="0.25">
                                                <stop offset="0%" stop-color="#f8fafc" />
                                                <stop offset="20%" stop-color="#e2e8f0" />
                                                <stop offset="45%" stop-color="#cbd5e1" />
                                                <stop offset="70%" stop-color="#94a3b8" />
                                                <stop offset="90%" stop-color="#64748b" />
                                                <stop offset="100%" stop-color="#475569" />
                                            </radialGradient>
                                            <radialGradient id="rimMainInner3DGradient" cx="0.3" cy="0.3">
                                                <stop offset="0%" stop-color="#e2e8f0" />
                                                <stop offset="50%" stop-color="#94a3b8" />
                                                <stop offset="100%" stop-color="#334155" />
                                            </radialGradient>
                                            <radialGradient id="rimMainDeep3DGradient" cx="0.4" cy="0.4">
                                                <stop offset="0%" stop-color="#cbd5e1" />
                                                <stop offset="100%" stop-color="#1e293b" />
                                            </radialGradient>

                                            <!-- Spoke 3D Gradients -->
                                            <linearGradient id="spoke3DGradient" x1="0%" y1="0%" x2="100%" y2="100%">
                                                <stop offset="0%" stop-color="#ffffff" />
                                                <stop offset="20%" stop-color="#f1f5f9" />
                                                <stop offset="40%" stop-color="#e2e8f0" />
                                                <stop offset="60%" stop-color="#cbd5e1" />
                                                <stop offset="80%" stop-color="#94a3b8" />
                                                <stop offset="100%" stop-color="#64748b" />
                                            </linearGradient>
                                            <linearGradient id="spokeCenter3DGradient" x1="0%" y1="0%" x2="100%" y2="100%">
                                                <stop offset="0%" stop-color="#ffffff" stop-opacity="0.8" />
                                                <stop offset="50%" stop-color="#cbd5e1" stop-opacity="0.6" />
                                                <stop offset="100%" stop-color="#64748b" stop-opacity="0.4" />
                                            </linearGradient>
                                            <linearGradient id="spokeHighlight3D" x1="0%" y1="0%" x2="100%" y2="0%">
                                                <stop offset="0%" stop-color="#ffffff" />
                                                <stop offset="50%" stop-color="#f1f5f9" />
                                                <stop offset="100%" stop-color="#e2e8f0" />
                                            </linearGradient>

                                            <!-- Hub 3D Gradients -->
                                            <radialGradient id="hubShadow3DGradient" cx="0.5" cy="0.3">
                                                <stop offset="0%" stop-color="#000000" stop-opacity="0" />
                                                <stop offset="100%" stop-color="#000000" stop-opacity="0.8" />
                                            </radialGradient>
                                            <radialGradient id="hub3DGradient" cx="0.2" cy="0.2">
                                                <stop offset="0%" stop-color="#ffffff" />
                                                <stop offset="25%" stop-color="#f1f5f9" />
                                                <stop offset="50%" stop-color="#e2e8f0" />
                                                <stop offset="75%" stop-color="#cbd5e1" />
                                                <stop offset="100%" stop-color="#94a3b8" />
                                            </radialGradient>
                                            <radialGradient id="hubInner3DGradient" cx="0.25" cy="0.25">
                                                <stop offset="0%" stop-color="#f1f5f9" />
                                                <stop offset="50%" stop-color="#cbd5e1" />
                                                <stop offset="100%" stop-color="#64748b" />
                                            </radialGradient>
                                            <radialGradient id="hubDeep3DGradient" cx="0.3" cy="0.3">
                                                <stop offset="0%" stop-color="#e2e8f0" />
                                                <stop offset="100%" stop-color="#475569" />
                                            </radialGradient>
                                            <radialGradient id="hubCore3DGradient" cx="0.35" cy="0.35">
                                                <stop offset="0%" stop-color="#cbd5e1" />
                                                <stop offset="100%" stop-color="#334155" />
                                            </radialGradient>
                                            <radialGradient id="hubCenter3DGradient" cx="0.3" cy="0.3">
                                                <stop offset="0%" stop-color="#f8fafc" />
                                                <stop offset="50%" stop-color="#e2e8f0" />
                                                <stop offset="100%" stop-color="#94a3b8" />
                                            </radialGradient>
                                            <radialGradient id="hubCenterDeep3DGradient" cx="0.4" cy="0.4">
                                                <stop offset="0%" stop-color="#cbd5e1" />
                                                <stop offset="100%" stop-color="#1e293b" />
                                            </radialGradient>

                                            <!-- Hub Ring Gradients -->
                                            <linearGradient id="hubRing1Gradient" x1="0%" y1="0%" x2="100%" y2="100%">
                                                <stop offset="0%" stop-color="#ffffff" />
                                                <stop offset="100%" stop-color="#64748b" />
                                            </linearGradient>
                                            <linearGradient id="hubRing2Gradient" x1="0%" y1="0%" x2="100%" y2="100%">
                                                <stop offset="0%" stop-color="#e2e8f0" />
                                                <stop offset="100%" stop-color="#475569" />
                                            </linearGradient>
                                            <linearGradient id="hubRing3Gradient" x1="0%" y1="0%" x2="100%" y2="100%">
                                                <stop offset="0%" stop-color="#cbd5e1" />
                                                <stop offset="100%" stop-color="#334155" />
                                            </linearGradient>

                                            <!-- Bolt Hole 3D Gradients -->
                                            <radialGradient id="boltHole3DGradient" cx="0.3" cy="0.3">
                                                <stop offset="0%" stop-color="#64748b" />
                                                <stop offset="40%" stop-color="#475569" />
                                                <stop offset="70%" stop-color="#334155" />
                                                <stop offset="100%" stop-color="#1e293b" />
                                            </radialGradient>
                                            <radialGradient id="boltHoleInner3DGradient" cx="0.4" cy="0.4">
                                                <stop offset="0%" stop-color="#334155" />
                                                <stop offset="100%" stop-color="#0f172a" />
                                            </radialGradient>
                                        </defs>
                                    </svg>
                                </div>
                            </div>
                            <p class="mx-auto max-w-3xl text-xl text-gray-300">
                                Understanding Process Cost in Wheel Manufacturing Industry - An accounting system that tracks production costs through
                                each stage of the manufacturing process
                            </p>
                        </div>

                        <!-- Process Cost Definition -->
                        <div
                            class="mb-12 rounded-xl border border-yellow-500/30 bg-gradient-to-r from-yellow-900/50 via-orange-500/50 to-red-900/50 p-8"
                        >
                            <h3 class="mb-4 text-2xl font-semibold text-gray-300">What is Process Cost?</h3>
                            <p class="text-lg leading-relaxed text-gray-200">
                                Process Cost is a cost accounting method used to allocate production costs to products that are manufactured in mass
                                and continuous production. In the wheel industry, each production stage has costs that must be calculated and
                                allocated accurately.
                            </p>
                        </div>

                        <!-- Manufacturing Process Steps -->
                        <div class="mb-16">
                            <h3
                                class="mb-12 bg-gradient-to-r from-yellow-400 via-orange-500 to-red-600 bg-clip-text text-center text-3xl font-bold text-transparent"
                            >
                                Wheel Manufacturing Process Stages
                            </h3>
                            <div class="grid grid-cols-1 gap-8 md:grid-cols-3">
                                <!-- Step 1: Raw Material -->
                                <div class="rounded-xl border border-red-500/30 bg-gradient-to-b from-red-900/50 to-red-800/50 p-8">
                                    <div class="mb-6 text-center">
                                        <div class="mx-auto mb-4 flex h-20 w-20 items-center justify-center rounded-full bg-red-500">
                                            <svg width="40" height="40" viewBox="0 0 32 32" fill="white">
                                                <rect x="6" y="10" width="20" height="12" rx="2" fill="currentColor" />
                                                <rect x="10" y="14" width="12" height="4" rx="1" fill="rgba(0,0,0,0.3)" />
                                            </svg>
                                        </div>
                                        <h4 class="text-xl font-semibold text-red-300">Raw Material</h4>
                                    </div>
                                    <div class="space-y-4">
                                        <div class="rounded-lg bg-gray-800/50 p-4">
                                            <h5 class="font-semibold text-red-200">Steel Plate/Sheet</h5>
                                        </div>
                                        <div class="rounded-lg bg-gray-800/50 p-4">
                                            <h5 class="font-semibold text-red-200">Material Grade</h5>
                                        </div>
                                        <div class="rounded-lg bg-gray-800/50 p-4">
                                            <h5 class="font-semibold text-red-200">Waste Factor: 8%</h5>
                                        </div>
                                        <div class="rounded-lg bg-gray-800/50 p-4">
                                            <p class="text-md font-bold text-red-400">Cost: $X (example)</p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Step 2: Process -->
                                <div class="rounded-xl border border-orange-500/30 bg-gradient-to-b from-orange-900/50 to-orange-800/50 p-8">
                                    <div class="mb-6 text-center">
                                        <div class="mx-auto mb-4 flex h-20 w-20 items-center justify-center rounded-full bg-orange-500">
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
                                        <h4 class="text-xl font-semibold text-orange-300">Process</h4>
                                    </div>
                                    <div class="space-y-4">
                                        <div class="rounded-lg bg-gray-800/50 p-4">
                                            <h5 class="font-semibold text-orange-200">Steel Forming,Shaping, & Bending</h5>
                                        </div>
                                        <div class="rounded-lg bg-gray-800/50 p-4">
                                            <h5 class="font-semibold text-orange-200">Precision Cutting</h5>
                                        </div>
                                        <div class="rounded-lg bg-gray-800/50 p-4">
                                            <h5 class="font-semibold text-orange-200">Welding / Assembly</h5>
                                        </div>
                                        <div class="rounded-lg bg-gray-800/50 p-4">
                                            <p class="text-md font-bold text-orange-400">Total Cost: $Y (example)</p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Step 3: Finished Goods -->
                                <div class="rounded-xl border border-green-500/30 bg-gradient-to-b from-green-900/50 to-green-800/50 p-8">
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
                                        <h4 class="text-xl font-semibold text-green-300">Finish Good</h4>
                                    </div>
                                    <div class="space-y-4">
                                        <div class="rounded-lg bg-gray-800/50 p-4">
                                            <h5 class="font-semibold text-green-200">Quality Control</h5>
                                        </div>
                                        <div class="rounded-lg bg-gray-800/50 p-4">
                                            <h5 class="font-semibold text-green-200">Packaging & Labeling</h5>
                                        </div>
                                        <div class="rounded-lg bg-gray-800/50 p-4">
                                            <h5 class="font-semibold text-green-200">Delivery</h5>
                                        </div>
                                        <div class="rounded-lg bg-gray-800/50 p-4">
                                            <p class="text-md font-bold text-green-400">Final Cost: $Z (example)</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Cost Flow Diagram -->
                        <div class="mb-16">
                            <h3
                                class="mb-12 bg-gradient-to-r from-yellow-400 via-orange-500 to-red-600 bg-clip-text text-center text-3xl font-bold text-transparent"
                            >
                                Process Cost Components
                            </h3>

                            <div
                                class="rounded-xl border border-yellow-500/30 bg-gradient-to-r from-yellow-900/50 via-orange-500/50 to-red-900/50 p-8"
                            >
                                <div class="flex flex-col items-center justify-between space-y-8 lg:flex-row lg:space-y-0 lg:space-x-8">
                                    <!-- Direct Materials -->
                                    <div class="text-center">
                                        <div
                                            class="mx-auto mb-4 flex h-24 w-24 items-center justify-center rounded-full bg-gradient-to-br from-red-500 to-red-600"
                                        >
                                            <span class="text-sm font-bold text-white">Cycle Time<br />Data</span>
                                        </div>
                                        <!-- <p class="font-semibold text-red-300">$120</p> -->
                                    </div>

                                    <!-- Arrow -->
                                    <div class="cost-flow">
                                        <svg width="40" height="40" viewBox="0 0 20 20" class="text-white hover:text-orange-500">
                                            <line x1="5" y1="10" x2="15" y2="10" stroke="currentColor" stroke-width="2" />
                                            <line x1="10" y1="5" x2="10" y2="15" stroke="currentColor" stroke-width="2" />
                                        </svg>
                                    </div>

                                    <!-- Direct Labor -->
                                    <div class="text-center">
                                        <div
                                            class="mx-auto mb-4 flex h-24 w-24 items-center justify-center rounded-full bg-gradient-to-br from-orange-500 to-orange-600"
                                        >
                                            <span class="text-sm font-bold text-white">Sales<br />Quantity</span>
                                        </div>
                                    </div>

                                    <!-- Arrow -->
                                    <div class="cost-flow">
                                        <svg width="40" height="40" viewBox="0 0 20 20" class="text-white hover:text-orange-500">
                                            <line x1="5" y1="10" x2="15" y2="10" stroke="currentColor" stroke-width="2" />
                                            <line x1="10" y1="5" x2="10" y2="15" stroke="currentColor" stroke-width="2" />
                                        </svg>
                                    </div>

                                    <!-- Manufacturing Overhead -->
                                    <div class="text-center">
                                        <div
                                            class="mx-auto mb-4 flex h-24 w-24 items-center justify-center rounded-full bg-gradient-to-br from-purple-500 to-purple-600"
                                        >
                                            <span class="text-sm font-bold text-white">Wages<br />Distribution</span>
                                        </div>
                                    </div>

                                    <!-- Arrow -->
                                    <div class="cost-flow">
                                        <svg width="40" height="40" viewBox="0 0 20 20" class="text-white hover:text-orange-500">
                                            <line x1="5" y1="7" x2="25" y2="7" stroke="currentColor" stroke-width="2" />
                                            <line x1="5" y1="13" x2="25" y2="13" stroke="currentColor" stroke-width="2" />
                                        </svg>
                                    </div>

                                    <!-- Final Product -->
                                    <div class="text-center">
                                        <div
                                            class="mx-auto mb-4 flex h-24 w-24 items-center justify-center rounded-full bg-gradient-to-br from-green-500 to-green-600"
                                        >
                                            <span class="text-sm font-bold text-white">Process<br />Cost</span>
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
                                        Process Cost to produce 1 product.
                                    </li>
                                    <li class="flex items-start">
                                        <span class="mr-2 text-green-400">✓</span>
                                        Monitor and controlling each departmet each line.
                                    </li>
                                    <li class="flex items-start">
                                        <span class="mr-2 text-green-400">✓</span>
                                        Evaluation line of process.
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
                                        Cost allocation per line
                                    </li>
                                </ul>
                            </div>
                        </div>

                        <!-- Navigation Button to Process Cost -->
                        <div class="mt-16 text-center">
                            <Link :href="route('pc.report')">
                                <Button
                                    icon="pi pi-wrench"
                                    label=" Explore Process Cost Calculation System"
                                    unstyled
                                    class="w-full cursor-pointer rounded-xl bg-gradient-to-r from-yellow-400 via-orange-500 to-red-600 px-4 py-2 text-center font-bold text-slate-900 hover:from-yellow-500 hover:via-orange-600 hover:to-red-700 sm:w-auto"
                                />
                            </Link>
                        </div>
                    </div>
                </section>

                <section id="standardCost" class="scroll-mt-12">
                    <div
                        class="mx-auto my-32 max-w-full px-6 text-gray-100 lg:max-w-7xl"
                        style="background-color: rgba(0, 0, 0, 0.9); border-radius: 12px; padding: 3rem"
                    >
                        <!-- Header Section -->
                        <div class="mb-16 text-center">
                            <h2 class="mb-6 bg-gradient-to-r from-green-400 to-blue-500 bg-clip-text text-4xl font-bold text-transparent">
                                Standard Cost System
                            </h2>
                            <div class="mb-8 flex justify-center">
                                <!-- Standard Cost Icon -->
                                <div class="relative">
                                    <svg width="120" height="120" viewBox="0 0 120 120" class="text-green-400">
                                        <rect x="20" y="30" width="80" height="60" rx="8" fill="none" stroke="currentColor" stroke-width="3" />
                                        <rect x="30" y="40" width="60" height="40" rx="4" fill="currentColor" opacity="0.2" />
                                        <line x1="40" y1="50" x2="80" y2="50" stroke="currentColor" stroke-width="2" />
                                        <line x1="40" y1="60" x2="70" y2="60" stroke="currentColor" stroke-width="2" />
                                        <line x1="40" y1="70" x2="75" y2="70" stroke="currentColor" stroke-width="2" />
                                        <circle cx="90" cy="40" r="8" fill="currentColor" />
                                        <text x="90" y="45" text-anchor="middle" class="fill-gray-900 text-xs font-bold">$</text>
                                    </svg>
                                </div>
                            </div>
                            <p class="mx-auto max-w-3xl text-xl text-gray-300">
                                Standard Cost System - A predetermined cost system that establishes standard costs for materials, labor, and overhead
                                to measure performance and control costs
                            </p>
                        </div>

                        <!-- Standard Cost Definition -->
                        <div class="mb-12 rounded-xl border border-green-500/30 bg-gradient-to-r from-green-900/50 to-blue-900/50 p-8">
                            <h3 class="mb-4 text-2xl font-semibold text-green-300">What is Standard Cost?</h3>
                            <p class="mb-4 text-lg leading-relaxed text-gray-200">
                                Standard Cost is a predetermined cost that serves as a benchmark for measuring actual performance. It represents what
                                costs should be under normal operating conditions and efficient performance levels.
                            </p>
                            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                                <div class="rounded-lg bg-gray-800/50 p-4">
                                    <h4 class="mb-2 text-lg font-semibold text-green-300">Key Purposes:</h4>
                                    <ul class="space-y-1 text-gray-200">
                                        <li>• Cost control and performance measurement</li>
                                        <li>• Budget preparation and planning</li>
                                        <li>• Variance analysis and investigation</li>
                                        <li>• Pricing decisions and profitability analysis</li>
                                    </ul>
                                </div>
                                <div class="rounded-lg bg-gray-800/50 p-4">
                                    <h4 class="mb-2 text-lg font-semibold text-blue-300">Types of Standards:</h4>
                                    <ul class="space-y-1 text-gray-200">
                                        <li>• <span class="text-green-400">Ideal Standards</span> - Based on ideal conditions</li>
                                        <li>• <span class="text-orange-400">Current Standards</span> - Based on actual conditions</li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <!-- Standard Cost Components -->
                        <div class="mb-16">
                            <h3
                                class="mb-12 bg-gradient-to-r from-green-400 to-blue-500 bg-clip-text text-center text-3xl font-bold text-transparent"
                            >
                                Standard Cost Components for Wheel Production
                            </h3>
                            <div class="grid grid-cols-1 gap-8 lg:grid-cols-2">
                                <!-- Direct Material Standards -->
                                <div class="rounded-xl border border-yellow-500/30 bg-gradient-to-b from-yellow-900/50 to-yellow-800/50 p-8">
                                    <div class="mb-6 text-center">
                                        <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-full bg-yellow-500">
                                            <svg
                                                width="40"
                                                height="40"
                                                viewBox="0 0 24 24"
                                                fill="none"
                                                stroke="currentColor"
                                                stroke-width="2"
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                            >
                                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />

                                                <polyline points="14 2 14 8 20 8" />

                                                <line x1="16" y1="13" x2="8" y2="13" />
                                                <line x1="16" y1="17" x2="8" y2="17" />
                                                <line x1="10" y1="9" x2="8" y2="9" />
                                            </svg>
                                        </div>
                                        <h4 class="text-2xl font-semibold text-yellow-300">Bill of Material</h4>
                                    </div>
                                    <div class="space-y-4">
                                        <div class="rounded-lg bg-gray-800/50 p-4">
                                            <h5 class="mb-2 text-xl font-semibold text-yellow-200">Bill of Material raw data contain :</h5>
                                            <p class="text-lg text-gray-300">Finish Good (Item Code Ex : F15W02)</p>
                                            <p class="text-lg text-gray-300">Work in Progress (Item Code : WB2D-031)</p>
                                            <p class="text-lg text-gray-300">Process (Item Code : RS-DC02)</p>
                                            <p class="text-lg text-gray-300">Raw Material (Item Code : RFD002)</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="rounded-xl border border-orange-500/30 bg-gradient-to-b from-orange-900/50 to-orange-800/50 p-8">
                                    <div class="mb-6 text-center">
                                        <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-full bg-orange-500">
                                            <svg
                                                width="40"
                                                height="40"
                                                viewBox="0 0 24 24"
                                                fill="none"
                                                stroke="currentColor"
                                                stroke-width="2"
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                            >
                                                <circle cx="12" cy="12" r="10" />
                                                <path d="M12 16V8M8 12h8" />

                                                <path d="M17 17l-1.5-1.5M17 17h-3M17 17v-3" />
                                                <path d="M7 7l1.5 1.5M7 7h3M7 7v3" />
                                            </svg>
                                        </div>
                                        <h4 class="text-2xl font-semibold text-orange-300">Process Cost</h4>
                                    </div>
                                    <div class="space-y-4">
                                        <div class="rounded-lg bg-gray-800/50 p-4">
                                            <h5 class="mb-2 text-xl font-semibold text-orange-200">Process Cost data include :</h5>
                                            <p class="text-lg text-gray-300">Consumables</p>
                                            <p class="text-lg text-gray-300">Manufacturing Overhead</p>
                                            <p class="text-lg text-gray-300">Labor Cost</p>
                                            <p class="text-lg text-gray-300">Indirect Material</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Total Standard Cost Summary -->
                        <div class="mb-12 rounded-xl border border-gray-600 bg-gradient-to-r from-gray-800/80 to-gray-900/80 p-8">
                            <h3 class="mb-8 text-center text-2xl font-bold text-green-300">Summary from {{ lastMonth }}</h3>
                            <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
                                <div class="text-center">
                                    <div class="mb-2 rounded-lg bg-red-500/20 p-4">
                                        <p class="font-semibold text-red-300">Top 5 Highest Actual Cost</p>

                                        <ul class="mt-2 space-y-1 text-sm text-red-100">
                                            <li v-for="item in topActualCost" :key="item.item_code" class="flex justify-between">
                                                <div>
                                                    <span class="font-bold">{{ item.no }}.</span>
                                                    {{ item.item_code }}
                                                </div>

                                                <div class="font-bold">
                                                    {{ 'Rp. ' + Number(item.total).toLocaleString('id-ID') }}
                                                </div>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                                <div class="text-center">
                                    <div class="mb-2 rounded-lg bg-orange-500/20 p-4">
                                        <p class="font-semibold text-orange-300">Top 5 Highest Sales Quantity</p>
                                        <ul class="mt-2 space-y-1 text-sm text-red-100">
                                            <li v-for="item in topQuantity" :key="item.item_code" class="flex justify-between">
                                                <div>
                                                    <span class="font-bold">{{ item.no }}.</span>
                                                    {{ item.item_code }}
                                                </div>

                                                <div class="font-bold">
                                                    {{ Number(item.quantity).toLocaleString('id-ID') + ' pcs' }}
                                                </div>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                                <div class="text-center">
                                    <div class="mb-2 rounded-lg bg-purple-500/20 p-4">
                                        <p class="font-semibold text-purple-300">Top 5 Highest Difference Cost</p>
                                        <ul class="mt-2 space-y-1 text-sm text-red-100">
                                            <li v-for="item in topDifferenceCost" :key="item.item_code" class="flex justify-between">
                                                <div>
                                                    <span class="font-bold">{{ item.no }}.</span>
                                                    {{ item.item_code }}
                                                </div>

                                                <div class="font-bold">
                                                    {{ Number(item.quantity).toLocaleString('id-ID') + ' pcs' }}
                                                </div>

                                                <div class="font-bold">
                                                    {{ Number(item.total).toLocaleString('id-ID') + ' pcs' }}
                                                </div>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Variance Analysis -->

                        <!-- Navigation Button to Process Cost -->
                        <div class="mt-16 text-center">
                            <Link :href="route('bom.report')">
                                <Button
                                    icon="pi pi-wrench"
                                    label=" Explore Standard Cost System"
                                    unstyled
                                    class="w-full cursor-pointer rounded-xl bg-gradient-to-r from-green-400 via-cyan-500 to-blue-600 px-4 py-2 text-center font-bold text-slate-900 hover:from-green-500 hover:via-cyan-600 hover:to-blue-700 sm:w-auto"
                                />
                            </Link>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </AppLayout>
</template>
