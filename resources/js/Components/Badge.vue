<script setup>
import { computed } from 'vue';

const props = defineProps({
    tone: {
        type: String,
        default: 'slate', // slate | green | red | amber | primary
    },
});

const toneClasses = computed(
    () =>
        ({
            slate: 'bg-slate-100 text-slate-600',
            green: 'bg-emerald-100 text-emerald-700',
            red: 'bg-red-100 text-red-700',
            amber: 'bg-amber-100 text-amber-800',
            primary: 'bg-primary-100 text-primary-700',
        })[props.tone],
);
</script>

<template>
    <span
        class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
        :class="toneClasses"
    >
        <slot />
    </span>
</template>
