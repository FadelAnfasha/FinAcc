<script setup lang="ts">
import AppLogoIcon from '@/components/AppLogoIcon.vue';
defineProps<{ isCollapsed: boolean }>();
</script>

<!-- AppLogo.vue -->
<template>
    <div class="flex w-full flex-col items-center justify-center">
        <AppLogoIcon />
        <span class="text-center text-lg font-bold text-white" v-if="!isCollapsed">Finance & Accounting Internal System</span>
    </div>
</template>

<!-- 
<template>
    <div class="mt-10 flex w-full flex-col items-center justify-center"> -->
<!-- Logo Image -->
<!-- <AppLogoIcon class="h-20 w-20" /> -->

<!-- Label -->
<!-- <span class="text-red rounded px-2 text-center text-lg font-bold"> FinAcc </span>
    </div>
</template> -->
