<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();

const reportData = computed(() => page.props.reportData as any);
console.log(reportData.value);

const previewHtml = computed(() => page.props.previewHtml as any);
console.log(previewHtml.value); // ✅ benar, akan menampilkan isi HTML
</script>

<template>
    <Head :title="reportData.item_code" />

    <AppLayout>
        <div v-if="previewHtml" v-html="previewHtml" class="border p-4"></div>
        <div v-else class="text-red-500">Preview kosong atau tidak valid</div>

        <pre class="text-sm whitespace-pre-wrap">{{ previewHtml }}</pre>
    </AppLayout>
</template>
