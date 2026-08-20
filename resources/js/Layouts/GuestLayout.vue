<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div class="flex min-h-screen bg-slate-50">
        <div class="relative hidden w-1/2 flex-col justify-between bg-primary-900 p-12 lg:flex">
            <Link href="/" class="flex items-center gap-3">
                <ApplicationLogo class="h-10 w-10" />
                <span class="text-lg font-semibold text-white">Motorepuestos Galicia</span>
            </Link>

            <div class="max-w-md">
                <h1 class="text-3xl font-bold leading-tight text-white">
                    Gestión de inventario, repuestos y taller en un solo lugar.
                </h1>
                <p class="mt-4 text-primary-200">
                    Punto de venta, control de stock, proveedores y caja diseñados para el
                    ritmo de tu negocio.
                </p>
            </div>

            <p class="text-sm text-primary-300">
                &copy; {{ new Date().getFullYear() }} Lubricantes y Motorepuestos Galicia
            </p>
        </div>

        <div class="flex w-full flex-col items-center justify-center px-6 py-12 lg:w-1/2">
            <Link href="/" class="mb-8 flex items-center gap-3 lg:hidden">
                <ApplicationLogo class="h-10 w-10" />
                <span class="text-lg font-semibold text-slate-900">Motorepuestos Galicia</span>
            </Link>

            <div class="w-full max-w-sm rounded-xl border border-slate-200 bg-white p-8 shadow-card">
                <slot />
            </div>
        </div>
    </div>
</template>
