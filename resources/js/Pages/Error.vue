<script setup>
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';

const props = defineProps({
    status: Number,
});

const content = computed(() => ({
    403: {
        title: 'Acceso restringido',
        description: 'No tienes permiso para ver esta página. Si crees que deberías tenerlo, contacta a un administrador.',
    },
    404: {
        title: 'Página no encontrada',
        description: 'La página que buscas no existe o fue movida.',
    },
    419: {
        title: 'Página expirada',
        description: 'Tu sesión expiró. Recarga la página e intenta de nuevo.',
    },
    429: {
        title: 'Demasiadas solicitudes',
        description: 'Estás enviando demasiadas solicitudes. Espera un momento e intenta de nuevo.',
    },
    500: {
        title: 'Error del servidor',
        description: 'Algo salió mal de nuestro lado. Intenta de nuevo en unos momentos.',
    },
    503: {
        title: 'Servicio no disponible',
        description: 'Estamos en mantenimiento. Vuelve a intentarlo en unos minutos.',
    },
}[props.status] ?? {
    title: 'Ocurrió un error',
    description: 'Algo no salió como esperábamos.',
}));
</script>

<template>
    <Head :title="content.title" />

    <div class="flex min-h-screen flex-col items-center justify-center bg-slate-50 px-6 text-center">
        <ApplicationLogo class="h-12 w-12" />
        <p class="mt-6 text-sm font-semibold text-primary-600">Error {{ status }}</p>
        <h1 class="mt-2 text-2xl font-bold text-slate-900">{{ content.title }}</h1>
        <p class="mt-2 max-w-sm text-sm text-slate-500">{{ content.description }}</p>

        <Link
            href="/dashboard"
            class="mt-8 inline-flex items-center justify-center rounded-lg bg-primary-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-700"
        >
            Volver al dashboard
        </Link>
    </div>
</template>
