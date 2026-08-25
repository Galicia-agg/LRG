<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Card from '@/Components/Card.vue';
import Badge from '@/Components/Badge.vue';
import Icon from '@/Components/Icon.vue';
import { Head, Link, router } from '@inertiajs/vue3';

const props = defineProps({
    vehicle: Object,
    workOrders: Array,
    maintenanceSummary: Object,
    filters: Object,
});

const statusLabels = {
    recibido: { text: 'Recibida', tone: 'slate' },
    en_proceso: { text: 'En proceso', tone: 'amber' },
    listo: { text: 'Lista', tone: 'primary' },
    entregado: { text: 'Entregada', tone: 'green' },
    cancelado: { text: 'Cancelada', tone: 'red' },
};

const typeLabels = {
    servicio: { text: 'Servicio', tone: 'accent' },
    reparacion: { text: 'Reparación', tone: 'primary' },
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

const statusFilters = [
    { value: '', label: 'Todas' },
    { value: 'recibido', label: 'Recibidas' },
    { value: 'en_proceso', label: 'En proceso' },
    { value: 'listo', label: 'Listas' },
    { value: 'entregado', label: 'Entregadas' },
    { value: 'cancelado', label: 'Canceladas' },
];

function currentParams(overrides) {
    return {
        ...(props.filters.type ? { type: props.filters.type } : {}),
        ...(props.filters.service_scope ? { service_scope: props.filters.service_scope } : {}),
        ...(props.filters.status ? { status: props.filters.status } : {}),
        ...overrides,
    };
}

function filterByType(value) {
    const overrides = { type: value || undefined };
    if (value !== 'servicio') {
        overrides.service_scope = undefined;
    }
    router.get(route('vehicles.show', props.vehicle.id), currentParams(overrides), { preserveState: true, replace: true, preserveScroll: true });
}

function filterByScope(value) {
    router.get(route('vehicles.show', props.vehicle.id), currentParams({ service_scope: value || undefined }), { preserveState: true, replace: true, preserveScroll: true });
}

function filterByStatus(value) {
    router.get(route('vehicles.show', props.vehicle.id), currentParams({ status: value || undefined }), { preserveState: true, replace: true, preserveScroll: true });
}

function formatDateTime(iso) {
    return new Date(iso).toLocaleString('es-GT', { dateStyle: 'medium', timeStyle: 'short' });
}

function formatDate(iso) {
    return iso ? new Date(iso).toLocaleDateString('es-GT', { dateStyle: 'medium' }) : '—';
}
</script>

<template>
    <Head :title="`${vehicle.brand} ${vehicle.model}`" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-lg font-semibold leading-tight text-slate-900">{{ vehicle.brand }} {{ vehicle.model }}</h2>
        </template>

        <div class="mx-auto max-w-screen-2xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
            <Link :href="route('vehicles.index')" class="text-sm font-medium text-primary-600 hover:text-primary-800">
                ← Volver a vehículos
            </Link>

            <Card padded>
                <div class="flex items-start gap-4">
                    <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-lg bg-primary-50 text-primary-600">
                        <Icon name="car" class="h-6 w-6" />
                    </span>
                    <div class="grid flex-1 grid-cols-2 gap-3 text-sm sm:grid-cols-3">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Vehículo</p>
                            <p class="mt-0.5 font-medium text-slate-900">{{ vehicle.brand }} {{ vehicle.model }} <span v-if="vehicle.year">({{ vehicle.year }})</span></p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Placa</p>
                            <p class="mt-0.5 text-slate-700">{{ vehicle.plate ?? '—' }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Color</p>
                            <p class="mt-0.5 text-slate-700">{{ vehicle.color ?? '—' }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Kilometraje registrado</p>
                            <p class="mt-0.5 text-slate-700">{{ vehicle.mileage ? `${vehicle.mileage} km` : '—' }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">VIN</p>
                            <p class="mt-0.5 text-slate-700">{{ vehicle.vin ?? '—' }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Propietario</p>
                            <p class="mt-0.5 font-medium text-slate-900">{{ vehicle.customer.name }}</p>
                            <p v-if="vehicle.customer.phone" class="text-xs text-slate-500">{{ vehicle.customer.phone }}</p>
                        </div>
                    </div>
                </div>
            </Card>

            <div>
                <h3 class="mb-2 text-sm font-semibold text-slate-900">Comportamiento de mantenimiento</h3>
                <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                    <Card padded>
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Servicios</p>
                        <p class="mt-1 text-2xl font-semibold text-accent-700">{{ maintenanceSummary.services_count }}</p>
                        <p class="mt-1 text-xs text-slate-500">Último: {{ formatDate(maintenanceSummary.last_service_at) }}</p>
                    </Card>
                    <Card padded>
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Reparaciones</p>
                        <p class="mt-1 text-2xl font-semibold text-primary-700">{{ maintenanceSummary.repairs_count }}</p>
                        <p class="mt-1 text-xs text-slate-500">Última: {{ formatDate(maintenanceSummary.last_repair_at) }}</p>
                    </Card>
                    <Card padded>
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Servicio menor</p>
                        <p class="mt-1 text-2xl font-semibold text-slate-900">{{ maintenanceSummary.minor_services_count }}</p>
                    </Card>
                    <Card padded>
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Servicio mayor</p>
                        <p class="mt-1 text-2xl font-semibold text-slate-900">{{ maintenanceSummary.major_services_count }}</p>
                    </Card>
                </div>
            </div>

            <div>
                <div class="mb-2 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <h3 class="text-sm font-semibold text-slate-900">Historial de servicios ({{ workOrders.length }})</h3>
                </div>

                <div class="mb-3 flex flex-wrap gap-2">
                    <button
                        v-for="option in typeFilters"
                        :key="option.value"
                        type="button"
                        @click="filterByType(option.value)"
                        class="cursor-pointer rounded-full border px-3 py-1 text-xs font-medium transition"
                        :class="
                            (filters.type ?? '') === option.value
                                ? 'border-primary-600 bg-primary-600 text-white'
                                : 'border-slate-200 text-slate-600 hover:border-primary-400 hover:text-primary-700'
                        "
                    >
                        {{ option.label }}
                    </button>
                </div>

                <div v-if="filters.type === 'servicio'" class="mb-3 flex flex-wrap gap-2">
                    <button
                        v-for="option in scopeFilters"
                        :key="option.value"
                        type="button"
                        @click="filterByScope(option.value)"
                        class="cursor-pointer rounded-full border px-3 py-1 text-xs font-medium transition"
                        :class="
                            (filters.service_scope ?? '') === option.value
                                ? 'border-accent-600 bg-accent-600 text-white'
                                : 'border-slate-200 text-slate-600 hover:border-accent-400 hover:text-accent-700'
                        "
                    >
                        {{ option.label }}
                    </button>
                </div>

                <div class="mb-3 flex flex-wrap gap-2">
                    <button
                        v-for="option in statusFilters"
                        :key="option.value"
                        type="button"
                        @click="filterByStatus(option.value)"
                        class="cursor-pointer rounded-full border px-3 py-1 text-xs font-medium transition"
                        :class="
                            (filters.status ?? '') === option.value
                                ? 'border-slate-600 bg-slate-600 text-white'
                                : 'border-slate-200 text-slate-600 hover:border-slate-400 hover:text-slate-700'
                        "
                    >
                        {{ option.label }}
                    </button>
                </div>

                <Card>
                    <ul class="divide-y divide-slate-100">
                        <li v-for="order in workOrders" :key="order.id" class="p-4">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <div class="flex items-center gap-2">
                                        <Link :href="route('workshop.show', order.id)" class="font-medium text-primary-600 hover:text-primary-800">
                                            Orden #{{ order.id }}
                                        </Link>
                                        <Badge :tone="statusLabels[order.status]?.tone ?? 'slate'">
                                            {{ statusLabels[order.status]?.text ?? order.status }}
                                        </Badge>
                                        <Badge :tone="typeLabels[order.type]?.tone ?? 'slate'">
                                            {{ typeLabels[order.type]?.text ?? order.type }}
                                        </Badge>
                                        <Badge v-if="order.service_scope" tone="slate">
                                            {{ order.service_scope === 'mayor' ? 'Mayor' : 'Menor' }}
                                        </Badge>
                                    </div>
                                    <p class="mt-1 text-xs text-slate-500">
                                        {{ formatDateTime(order.created_at) }}
                                        <span v-if="order.mechanic"> · Mecánico: {{ order.mechanic }}</span>
                                        <span v-if="order.mileage_in"> · {{ order.mileage_in }} km</span>
                                    </p>
                                    <p v-if="order.type !== 'servicio'" class="mt-1.5 text-sm text-slate-700">{{ order.reported_issue }}</p>
                                    <p v-else-if="order.services.length > 0" class="mt-1.5 text-sm text-slate-700">
                                        {{ order.services.join(', ') }}
                                    </p>
                                    <p v-if="order.diagnosis" class="mt-1 text-xs text-slate-500">
                                        Diagnóstico: {{ order.diagnosis }}
                                    </p>
                                </div>
                                <p class="shrink-0 font-medium text-slate-900">Q {{ order.total.toFixed(2) }}</p>
                            </div>
                        </li>
                        <li v-if="workOrders.length === 0" class="p-10 text-center text-sm text-slate-500">
                            {{
                                filters.type || filters.service_scope || filters.status
                                    ? 'No hay órdenes que coincidan con este filtro.'
                                    : 'Este vehículo no tiene servicios registrados todavía.'
                            }}
                        </li>
                    </ul>
                </Card>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
