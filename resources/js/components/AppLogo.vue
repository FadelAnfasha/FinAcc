<script setup lang="ts">
import AppLogoIcon from '@/components/AppLogoIcon.vue';
defineProps<{ isCollapsed: boolean }>();
</script>

<!-- AppLogo.vue -->
<template>
    <div class="flex w-full flex-col items-center justify-center">
        <AppLogoIcon />
        <span class="text-center text-lg font-bold text-slate-900 dark:text-white" v-if="!isCollapsed">Finance & Accounting Internal System</span>
    </div>
</template>
