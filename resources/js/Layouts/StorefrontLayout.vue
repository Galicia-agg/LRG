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

const emit = defineEmits(['toggle-cart']);
</script>

<template>
    <div class="min-h-screen bg-slate-50">
        <FlashMessages />

        <header class="sticky top-0 z-30 border-b border-slate-200 bg-primary-900">
            <div class="mx-auto flex h-16 max-w-6xl items-center justify-between px-4 sm:px-6">
                <Link href="/" class="flex items-center gap-3">
                    <ApplicationLogo class="h-9 w-9" />
                    <span class="text-sm font-semibold text-white">Motorepuestos Galicia</span>
                </Link>

                <button
                    v-if="$slots.cart"
                    type="button"
                    class="relative flex items-center gap-2 rounded-lg bg-white/10 px-3 py-2 text-sm font-medium text-white transition hover:bg-white/20"
                    @click="emit('toggle-cart')"
                >
                    <Icon name="cart" class="h-5 w-5" />
                    Carrito
                    <span
                        v-if="cartCount > 0"
                        class="flex h-5 w-5 items-center justify-center rounded-full bg-amber-400 text-xs font-bold text-primary-950"
                    >
                        {{ cartCount }}
                    </span>
                </button>
            </div>
        </header>

        <main>
            <slot />
        </main>

        <slot name="cart" />

        <footer class="border-t border-slate-200 bg-white py-6 text-center text-xs text-slate-400">
            &copy; {{ new Date().getFullYear() }} Lubricantes y Motorepuestos Galicia
        </footer>
    </div>
</template>
