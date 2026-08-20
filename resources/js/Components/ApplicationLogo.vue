<script setup>
defineProps({
    monochrome: {
        type: Boolean,
        default: false,
    },
});
</script>

<template>
    <svg viewBox="0 0 40 40" xmlns="http://www.w3.org/2000/svg">
        <rect
            width="40"
            height="40"
            rx="10"
            :class="monochrome ? 'fill-current' : 'fill-primary-600'"
        />
        <path
            d="M20 8.5c-.35 0-.68.15-.9.42-2.55 3.06-6.85 8.72-6.85 12.6 0 4.28 3.47 7.48 7.75 7.48s7.75-3.2 7.75-7.48c0-3.88-4.3-9.54-6.85-12.6a1.18 1.18 0 0 0-.9-.42Z"
            fill="#fff"
        />
        <path
            d="M14.8 22.9a5.4 5.4 0 0 0 5.2 5.55"
            stroke="#3569c2"
            stroke-width="1.6"
            stroke-linecap="round"
            fill="none"
        />
    </svg>
</template>
