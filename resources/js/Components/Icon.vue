<script setup>
import { computed } from 'vue';

const props = defineProps({
    name: {
        type: String,
        required: true,
    },
});

const paths = {
    home: 'M4 11.5 12 4l8 7.5M6 9.5V20h5v-5.5h2V20h5V9.5',
    cart: 'M4 5h2l1.2 10.4A2 2 0 0 0 9.2 17h7.6a2 2 0 0 0 2-1.8L20 8H7M9.5 21a1 1 0 1 0 0-2 1 1 0 0 0 0 2Zm7 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z',
    box: 'm3.5 8 8.5-4.5L20.5 8 12 12.5 3.5 8Zm0 0v8L12 20.5m8.5-12.5v8L12 20.5m0-8v8',
    tag: 'M11.5 4H6a2 2 0 0 0-2 2v5.5a2 2 0 0 0 .59 1.41l8.5 8.5a2 2 0 0 0 2.82 0l5.5-5.5a2 2 0 0 0 0-2.82l-8.5-8.5A2 2 0 0 0 11.5 4Z M8.25 8.25h.01',
    truck: 'M3 7h11v9H3zM14 10h4l3 3v3h-7zM7 19.5a1.75 1.75 0 1 0 0-3.5 1.75 1.75 0 0 0 0 3.5Zm10 0a1.75 1.75 0 1 0 0-3.5 1.75 1.75 0 0 0 0 3.5Z',
    menu: 'M4 6.5h16M4 12h16M4 17.5h16',
    close: 'm5 5 14 14M19 5 5 19',
    chevronDown: 'm6 9 6 6 6-6',
    logout: 'M13 4h4a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2h-4M16 12H4m0 0 4-4m-4 4 4 4',
    search: 'm20 20-3.5-3.5M11 18a7 7 0 1 0 0-14 7 7 0 0 0 0 14Z',
    alert: 'M12 9v4.5m0 3.01.01-.011M10.29 3.86 1.82 18a1.5 1.5 0 0 0 1.3 2.25h17.76a1.5 1.5 0 0 0 1.3-2.25L13.71 3.86a1.5 1.5 0 0 0-2.42 0Z',
    cash: 'M3 7.5h18v9H3zM12 9.75a2.25 2.25 0 1 0 0 4.5 2.25 2.25 0 0 0 0-4.5ZM6 7.5V9m12-1.5V9M6 15v1.5m12-1.5v1.5',
    lock: 'M6.75 10.5V8.25a5.25 5.25 0 0 1 10.5 0v2.25M5.5 10.5h13a1 1 0 0 1 1 1v8a1 1 0 0 1-1 1h-13a1 1 0 0 1-1-1v-8a1 1 0 0 1 1-1Z',
    dots: 'M12 6.01V6m0 6.01V12m0 6.01V18',
    plus: 'M12 5v14M5 12h14',
    check: 'm5 13 4 4L19 7',
    users: 'M9 11.5a3.25 3.25 0 1 0 0-6.5 3.25 3.25 0 0 0 0 6.5ZM2.5 20c0-3.31 2.91-6 6.5-6s6.5 2.69 6.5 6M16.5 5.1a3.25 3.25 0 0 1 0 6.3M19 14.3c2 .6 3.5 2.4 3.5 5.2',
    receipt: 'M6 3.5h12v17l-2.5-1.5L13 20.5l-2.5-1.5L8 20.5l-2-1.5V3.5ZM9 8h6M9 11.5h6M9 15h4',
    bag: 'M6 8h12l-1 12.5a1 1 0 0 1-1 .9H8a1 1 0 0 1-1-.9L6 8Zm3-2a3 3 0 0 1 6 0v2H9V6Z',
    image: 'M4 5.5h16v13H4v-13Zm2.5 9.5 4-4 2.5 2.5 3-3.5L20 15M8.75 9a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Z',
    trash: 'M5 7h14M9 7V5a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2m-7 0 .6 12.6a1 1 0 0 0 1 1h5.8a1 1 0 0 0 1-1L16 7',
};

const d = computed(() => paths[props.name] ?? '');
</script>

<template>
    <svg
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        stroke-width="1.75"
        stroke-linecap="round"
        stroke-linejoin="round"
    >
        <path :d="d" />
    </svg>
</template>
