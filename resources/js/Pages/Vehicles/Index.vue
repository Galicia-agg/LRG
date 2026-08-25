<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Card from '@/Components/Card.vue';
import Icon from '@/Components/Icon.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    vehicles: Array,
    filters: Object,
});

const search = ref(props.filters.q ?? '');

function runSearch() {
    router.get(route('vehicles.index'), search.value ? { q: search.value } : {}, { preserveState: true, replace: true });
}
</script>

<template>
    <Head title="Vehículos" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-lg font-semibold leading-tight text-slate-900">Vehículos</h2>
        </template>

        <div class="mx-auto max-w-screen-2xl space-y-4 px-4 py-8 sm:px-6 lg:px-8">
            <form @submit.prevent="runSearch" class="flex max-w-md gap-2">
                <div class="relative flex-1">
                    <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-slate-400">
                        <Icon name="search" class="h-4 w-4" />
                    </span>
                    <input
                        v-model="search"
                        type="text"
                        placeholder="Buscar por placa, marca, modelo o cliente"
                        class="w-full rounded-lg border-slate-300 py-2 pl-9 text-sm shadow-sm placeholder:text-slate-400 focus:border-primary-500 focus:ring-primary-500/40"
                    />
                </div>
                <button type="submit" class="cursor-pointer rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary-700">
                    Buscar
                </button>
            </form>

            <Card>
                <table class="min-w-full divide-y divide-slate-100">
                    <thead>
                        <tr class="text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                            <th class="px-6 py-3">Vehículo</th>
                            <th class="px-6 py-3">Placa</th>
                            <th class="px-6 py-3">Cliente</th>
                            <th class="px-6 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-sm text-slate-700">
                        <tr v-for="vehicle in vehicles" :key="vehicle.id" class="hover:bg-slate-50">
                            <td class="px-6 py-3 font-medium text-slate-900">{{ vehicle.label }}</td>
                            <td class="px-6 py-3">{{ vehicle.plate ?? '—' }}</td>
                            <td class="px-6 py-3">{{ vehicle.customer_name }}</td>
                            <td class="px-6 py-3 text-right">
                                <Link :href="route('vehicles.show', vehicle.id)" class="font-medium text-primary-600 hover:text-primary-800">
                                    Ver historial
                                </Link>
                            </td>
                        </tr>
                        <tr v-if="vehicles.length === 0">
                            <td colspan="4" class="px-6 py-10 text-center text-sm text-slate-500">
                                No se encontraron vehículos.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </Card>
        </div>
    </AuthenticatedLayout>
</template>
