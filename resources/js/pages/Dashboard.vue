<script setup lang="ts">
import CustomPersonNode from '@/components/CustomPersonNode.vue';
import { mainNavItems } from '@/constants/nav';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, usePage } from '@inertiajs/vue3';
import { Position, VueFlow } from '@vue-flow/core';

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
    customPerson: CustomPersonNode,
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
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="relative min-h-screen">
            <!-- Gambar sebagai latar belakang -->
            <div
                class="absolute inset-0 z-0 bg-cover bg-center bg-no-repeat opacity-20"
                style="background-image: url('/storage/images/topy.png')"
            ></div>

            <!-- Konten di atas gambar -->
            <div class="relative z-10 h-screen p-6">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Finance & Accounting Structure</h1>
                </div>
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
        </div>
    </AppLayout>
</template>
