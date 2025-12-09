<script lang="ts" setup>
import type { HTMLAttributes } from 'vue'

const props = defineProps<{
  class?: HTMLAttributes['class']
}>()

</script>

<template>
  <nav
    aria-label="breadcrumb"
    data-slot="breadcrumb"
    :class="['animate-in slide-in-from-right motion-safe:animate-bounce zoom-in duration-700', props.class]"  >
    <!--:class="['animate-in slide-in-from-right motion-safe:animate-bounce zoom-in duration-700', props.class]" -->
    <slot />
  </nav>
</template>
