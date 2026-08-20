<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    canLogin: {
        type: Boolean,
    },
    canRegister: {
        type: Boolean,
    },
});
</script>

<template>
    <Head title="Inicio" />

    <div class="flex min-h-screen flex-col items-center justify-center bg-primary-900 px-6 text-center">
        <ApplicationLogo class="h-16 w-16" />
        <h1 class="mt-6 text-3xl font-bold text-white">Lubricantes y Motorepuestos Galicia</h1>
        <p class="mt-2 max-w-md text-primary-200">
            Inventario, punto de venta, proveedores y caja para tu aceitera y taller.
        </p>

        <div class="mt-8 flex flex-wrap items-center justify-center gap-3">
            <Link
                :href="route('storefront.index')"
                class="inline-flex items-center justify-center rounded-lg bg-white px-5 py-2.5 text-sm font-semibold text-primary-900 shadow-sm transition hover:bg-primary-50"
            >
                Ver tienda
            </Link>
            <Link
                v-if="canLogin"
                :href="route('login')"
                class="inline-flex items-center justify-center rounded-lg border border-white/30 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-white/10"
            >
                Iniciar sesión
            </Link>
        </div>
        <p class="mt-3 text-xs text-primary-300">
            ¿Eres del equipo?
            <Link v-if="canRegister" :href="route('register')" class="underline hover:text-white">
                Crear cuenta
            </Link>
        </p>
    </div>
</template>
