<template>
    <div
        class="w-52 rounded-xl border border-gray-200 p-4 text-center opacity-75 shadow-md"
        :class="{ 'ring-2 ring-blue-400': selected, 'bg-gray-100 text-gray-900 dark:bg-gray-900 dark:text-gray-100': true }"
    >
        <!-- Handle kiri (untuk masuk) -->
        <Handle type="target" :position="Position.Left" :is-connectable="connectable" />

        <!-- Handle kanan (untuk keluar) -->
        <Handle type="source" :position="Position.Right" :is-connectable="connectable" />
        <img :src="data.image" class="mx-auto mb-2 h-16 w-16 rounded-full object-cover" style="object-position: center 20%" />
        <h3 class="text-base font-semibold">{{ data.name }}</h3>
        <p class="text-sm">{{ data.title }}</p>
        <p class="text-xs">NPK: {{ data.npk }}</p>
    </div>
</template>

<script setup lang="ts">
import { Handle, Position } from '@vue-flow/core';

defineProps<{
    id: string;
    type: string;
    data: {
        name: string;
        title: string;
        npk: string;
        image: string;
    };
    selected: boolean;
    connectable: boolean;
    dragging: boolean;
    position: { x: number; y: number };
    dimensions: { width: number; height: number };
    resizing: boolean;
    zIndex: number;
    events: any;
}>();
</script>
