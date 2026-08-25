<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import FlashMessages from '@/Components/FlashMessages.vue';
import Icon from '@/Components/Icon.vue';
import { Link } from '@inertiajs/vue3';

defineProps({
    cartCount: {
        type: Number,
        default: 0,
    },
});
</script>

<template>
    <div class="flex min-h-screen flex-col bg-slate-50">
        <FlashMessages />

        <header class="sticky top-0 z-30 border-b-4 border-accent-500 bg-primary-900">
            <div class="mx-auto flex h-16 max-w-6xl items-center justify-between px-4 sm:px-6">
                <Link href="/" class="flex items-center gap-3">
                    <ApplicationLogo class="h-9 w-9" />
                    <span class="text-sm font-semibold text-white">Motorepuestos Galicia</span>
                </Link>

                <Link
                    :href="route('storefront.cart')"
                    class="relative flex items-center gap-2 rounded-lg bg-white/10 px-3 py-2 text-sm font-medium text-white transition hover:bg-white/20"
                >
                    <Icon name="cart" class="h-5 w-5" />
                    Carrito
                    <span
                        v-if="cartCount > 0"
                        class="flex h-5 w-5 items-center justify-center rounded-full bg-accent-400 text-xs font-bold text-primary-950"
                    >
                        {{ cartCount }}
                    </span>
                </Link>
            </div>
        </header>

        <main class="flex-1">
            <slot />
        </main>

        <footer class="border-t border-slate-200 bg-white py-6 text-center text-xs text-slate-400">
            &copy; {{ new Date().getFullYear() }} Lubricantes y Motorepuestos Galicia
        </footer>
    </div>
</template>
