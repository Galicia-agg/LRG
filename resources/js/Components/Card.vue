<script setup>
defineProps({
    padded: {
        type: Boolean,
        default: false,
    },
});
</script>

<template>
    <div
        class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-card"
        :class="padded ? 'p-6' : ''"
    >
        <slot />
    </div>
</template>
