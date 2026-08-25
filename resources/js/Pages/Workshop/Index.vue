<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Card from '@/Components/Card.vue';
import Badge from '@/Components/Badge.vue';
import Icon from '@/Components/Icon.vue';
import { Head, Link, router } from '@inertiajs/vue3';

const props = defineProps({
    workOrders: Array,
    filters: Object,
    commonFailures: Array,
});

const statusFilters = [
    { value: '', label: 'Todas' },
    { value: 'recibido', label: 'Recibidas' },
    { value: 'en_proceso', label: 'En proceso' },
    { value: 'listo', label: 'Listas' },
    { value: 'entregado', label: 'Entregadas' },
    { value: 'cancelado', label: 'Canceladas' },
];

const statusLabels = {
    recibido: { text: 'Recibida', tone: 'slate' },
    en_proceso: { text: 'En proceso', tone: 'amber' },
    listo: { text: 'Lista', tone: 'primary' },
    entregado: { text: 'Entregada', tone: 'green' },
    cancelado: { text: 'Cancelada', tone: 'red' },
};

const typeFilters = [
    { value: '', label: 'Todos' },
    { value: 'servicio', label: 'Servicios' },
    { value: 'reparacion', label: 'Reparaciones' },
];

const scopeFilters = [
    { value: '', label: 'Todos' },
    { value: 'menor', label: 'Menor' },
    { value: 'mayor', label: 'Mayor' },
];

const typeLabels = {
    servicio: { text: 'Servicio', tone: 'accent' },
    reparacion: { text: 'Reparación', tone: 'primary' },
};

function currentParams(overrides) {
    return {
        ...(props.filters.status ? { status: props.filters.status } : {}),
        ...(props.filters.failure ? { failure: props.filters.failure } : {}),
        ...(props.filters.type ? { type: props.filters.type } : {}),
        ...(props.filters.service_scope ? { service_scope: props.filters.service_scope } : {}),
        ...overrides,
    };
}

function filterBy(value) {
    router.get(route('workshop.index'), currentParams({ status: value || undefined }), { preserveState: true, replace: true });
}

function filterByType(value) {
    const overrides = { type: value || undefined };
    if (value !== 'servicio') {
        overrides.service_scope = undefined;
    }
    router.get(route('workshop.index'), currentParams(overrides), { preserveState: true, replace: true });
}

function filterByScope(value) {
    router.get(route('workshop.index'), currentParams({ service_scope: value || undefined }), { preserveState: true, replace: true });
}

function filterByFailure(event) {
    const failure = event.target.value;
    router.get(route('workshop.index'), currentParams({ failure: failure || undefined }), { preserveState: true, replace: true });
}

function formatDateTime(iso) {
    return new Date(iso).toLocaleString('es-GT', { dateStyle: 'medium', timeStyle: 'short' });
}
</script>

<template>
    <Head title="Taller" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-lg font-semibold leading-tight text-slate-900">Taller mecánico</h2>
        </template>

        <div class="mx-auto max-w-6xl space-y-4 px-4 py-8 sm:px-6 lg:px-8">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex flex-wrap gap-2">
                    <button
                        v-for="option in statusFilters"
                        :key="option.value"
                        type="button"
                        @click="filterBy(option.value)"
                        class="cursor-pointer rounded-full border px-3 py-1 text-xs font-medium transition"
                        :class="
                            (filters.status ?? '') === option.value
                                ? 'border-primary-600 bg-primary-600 text-white'
                                : 'border-slate-200 text-slate-600 hover:border-primary-400 hover:text-primary-700'
                        "
                    >
                        {{ option.label }}
                    </button>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <div class="flex flex-wrap gap-2">
                        <button
                            v-for="option in typeFilters"
                            :key="option.value"
                            type="button"
                            @click="filterByType(option.value)"
                            class="cursor-pointer rounded-full border px-3 py-1 text-xs font-medium transition"
                            :class="
                                (filters.type ?? '') === option.value
                                    ? 'border-accent-600 bg-accent-600 text-white'
                                    : 'border-slate-200 text-slate-600 hover:border-accent-400 hover:text-accent-700'
                            "
                        >
                            {{ option.label }}
                        </button>
                    </div>
                    <div v-if="filters.type === 'servicio'" class="flex flex-wrap gap-2">
                        <button
                            v-for="option in scopeFilters"
                            :key="option.value"
                            type="button"
                            @click="filterByScope(option.value)"
                            class="cursor-pointer rounded-full border px-3 py-1 text-xs font-medium transition"
                            :class="
                                (filters.service_scope ?? '') === option.value
                                    ? 'border-slate-600 bg-slate-600 text-white'
                                    : 'border-slate-200 text-slate-600 hover:border-slate-400 hover:text-slate-700'
                            "
                        >
                            {{ option.label }}
                        </button>
                    </div>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <select
                        :value="filters.failure ?? ''"
                        @change="filterByFailure"
                        class="rounded-lg border-slate-300 py-1.5 text-xs shadow-sm focus:border-primary-500 focus:ring-primary-500/40"
                    >
                        <option value="">Filtrar por falla…</option>
                        <option v-for="failure in commonFailures" :key="failure.id" :value="failure.id">
                            {{ failure.description }}
                        </option>
                    </select>
                    <Link
                        :href="route('workshop.report')"
                        class="inline-flex items-center justify-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50"
                    >
                        <Icon name="document" class="h-4 w-4" />
                        Reportes
                    </Link>
                    <Link
                        :href="route('workshop.create')"
                        class="inline-flex items-center justify-center gap-2 rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-700"
                    >
                        <Icon name="plus" class="h-4 w-4" />
                        Nueva orden
                    </Link>
                </div>
            </div>

            <Card>
                <table class="min-w-full divide-y divide-slate-100">
                    <thead>
                        <tr class="text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                            <th class="px-6 py-3">Fecha</th>
                            <th class="px-6 py-3">Tipo</th>
                            <th class="px-6 py-3">Cliente</th>
                            <th class="px-6 py-3">Vehículo</th>
                            <th class="px-6 py-3">Fallas</th>
                            <th class="px-6 py-3">Mecánico</th>
                            <th class="px-6 py-3">Total</th>
                            <th class="px-6 py-3">Estado</th>
                            <th class="px-6 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-sm text-slate-700">
                        <tr v-for="order in workOrders" :key="order.id" class="hover:bg-slate-50">
                            <td class="px-6 py-3">{{ formatDateTime(order.created_at) }}</td>
                            <td class="px-6 py-3">
                                <Badge :tone="typeLabels[order.type]?.tone ?? 'slate'">
                                    {{ typeLabels[order.type]?.text ?? order.type }}
                                </Badge>
                                <span v-if="order.service_scope" class="ml-1 text-xs text-slate-400">
                                    ({{ order.service_scope === 'mayor' ? 'Mayor' : 'Menor' }})
                                </span>
                            </td>
                            <td class="px-6 py-3">{{ order.customer_name }}</td>
                            <td class="px-6 py-3">
                                {{ order.vehicle_label }}
                                <span v-if="order.vehicle_plate" class="text-xs text-slate-400"> · {{ order.vehicle_plate }}</span>
                            </td>
                            <td class="px-6 py-3">
                                <span v-if="order.failures.length === 0" class="text-slate-400">—</span>
                                <span v-else class="text-xs text-slate-600">{{ order.failures.join(', ') }}</span>
                            </td>
                            <td class="px-6 py-3">{{ order.mechanic ?? '—' }}</td>
                            <td class="px-6 py-3 font-medium text-slate-900">Q {{ order.total.toFixed(2) }}</td>
                            <td class="px-6 py-3">
                                <Badge :tone="statusLabels[order.status]?.tone ?? 'slate'">
                                    {{ statusLabels[order.status]?.text ?? order.status }}
                                </Badge>
                            </td>
                            <td class="px-6 py-3 text-right">
                                <Link :href="route('workshop.show', order.id)" class="font-medium text-primary-600 hover:text-primary-800">
                                    Ver orden
                                </Link>
                            </td>
                        </tr>
                        <tr v-if="workOrders.length === 0">
                            <td colspan="9" class="px-6 py-10 text-center text-sm text-slate-500">
                                No hay órdenes de servicio con este filtro.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </Card>
        </div>
    </AuthenticatedLayout>
</template>
