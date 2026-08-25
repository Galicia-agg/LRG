<script setup>
import { ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Card from '@/Components/Card.vue';
import { Head, Link, router } from '@inertiajs/vue3';

const props = defineProps({
    from: String,
    to: String,
    summary: Object,
});

const fromDate = ref(props.from);
const toDate = ref(props.to);

const statusLabels = {
    recibido: 'Recibida',
    en_proceso: 'En proceso',
    listo: 'Lista',
    entregado: 'Entregada',
    cancelado: 'Cancelada',
};

const typeLabels = {
    servicio: 'Servicios',
    reparacion: 'Reparaciones',
};

const scopeLabels = {
    menor: 'Servicio menor',
    mayor: 'Servicio mayor',
    sin_definir: 'Sin definir',
};

function toISODate(date) {
    return date.toLocaleDateString('sv-SE');
}

const presets = [
    {
        label: 'Últimos 7 días',
        range: () => {
            const end = new Date();
            const start = new Date();
            start.setDate(start.getDate() - 6);
            return [start, end];
        },
    },
    {
        label: 'Últimos 30 días',
        range: () => {
            const end = new Date();
            const start = new Date();
            start.setDate(start.getDate() - 29);
            return [start, end];
        },
    },
    {
        label: 'Este mes',
        range: () => {
            const now = new Date();
            return [new Date(now.getFullYear(), now.getMonth(), 1), now];
        },
    },
];

function applyPreset(preset) {
    const [start, end] = preset.range();
    fromDate.value = toISODate(start);
    toDate.value = toISODate(end);
    applyRange();
}

function applyRange() {
    router.get(
        route('workshop.report'),
        { from: fromDate.value, to: toDate.value },
        { preserveState: true, replace: true },
    );
}
</script>

<template>
    <Head title="Reportes del taller" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-lg font-semibold leading-tight text-slate-900">Reportes del taller</h2>
        </template>

        <div class="mx-auto max-w-6xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
            <Link :href="route('workshop.index')" class="text-sm font-medium text-primary-600 hover:text-primary-800">
                ← Volver a órdenes
            </Link>

            <!-- Filters -->
            <Card padded>
                <div class="flex flex-wrap gap-2">
                    <button
                        v-for="preset in presets"
                        :key="preset.label"
                        type="button"
                        @click="applyPreset(preset)"
                        class="cursor-pointer rounded-full border border-slate-200 px-3 py-1 text-xs font-medium text-slate-600 transition hover:border-primary-400 hover:text-primary-700"
                    >
                        {{ preset.label }}
                    </button>
                </div>
                <div class="mt-4 flex flex-wrap items-end gap-3">
                    <div>
                        <label for="from" class="mb-1 block text-xs font-medium text-slate-500">Desde</label>
                        <input id="from" v-model="fromDate" type="date" class="rounded-lg border-slate-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500/40" />
                    </div>
                    <div>
                        <label for="to" class="mb-1 block text-xs font-medium text-slate-500">Hasta</label>
                        <input id="to" v-model="toDate" type="date" class="rounded-lg border-slate-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500/40" />
                    </div>
                    <button
                        type="button"
                        @click="applyRange"
                        class="cursor-pointer rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-700"
                    >
                        Aplicar
                    </button>
                </div>
            </Card>

            <!-- KPIs -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <Card padded>
                    <p class="text-sm font-medium text-slate-500">Órdenes en el período</p>
                    <p class="mt-1 text-2xl font-semibold text-slate-900">{{ summary.ordersCount }}</p>
                    <p class="mt-1 text-xs text-slate-500">{{ summary.billedCount }} entregadas y cobradas</p>
                </Card>
                <Card padded>
                    <p class="text-sm font-medium text-slate-500">Ingresos por mano de obra</p>
                    <p class="mt-1 text-2xl font-semibold text-primary-700">Q {{ summary.laborTotal.toFixed(2) }}</p>
                </Card>
                <Card padded>
                    <p class="text-sm font-medium text-slate-500">Ingresos por repuestos</p>
                    <p class="mt-1 text-2xl font-semibold text-slate-900">Q {{ summary.partsTotal.toFixed(2) }}</p>
                </Card>
                <Card padded>
                    <p class="text-sm font-medium text-slate-500">Total facturado</p>
                    <p class="mt-1 text-2xl font-semibold text-accent-700">Q {{ summary.total.toFixed(2) }}</p>
                </Card>
            </div>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <!-- Reparaciones más comunes -->
                <Card>
                    <h3 class="border-b border-slate-100 px-6 py-3 text-sm font-semibold text-slate-900">
                        Reparaciones más comunes
                    </h3>
                    <table class="min-w-full divide-y divide-slate-100 text-sm">
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="repair in summary.topRepairs" :key="repair.description">
                                <td class="px-6 py-2.5 text-slate-700">{{ repair.description }}</td>
                                <td class="px-6 py-2.5 text-slate-500">{{ repair.count }} vez(ces)</td>
                                <td class="px-6 py-2.5 text-right font-medium text-slate-900">Q {{ Number(repair.total).toFixed(2) }}</td>
                            </tr>
                            <tr v-if="summary.topRepairs.length === 0">
                                <td colspan="3" class="px-6 py-6 text-center text-slate-400">Sin datos</td>
                            </tr>
                        </tbody>
                    </table>
                </Card>

                <!-- Tareas de servicio más comunes -->
                <Card>
                    <h3 class="border-b border-slate-100 px-6 py-3 text-sm font-semibold text-slate-900">
                        Tareas de servicio más comunes
                    </h3>
                    <table class="min-w-full divide-y divide-slate-100 text-sm">
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="task in summary.topServiceTasks" :key="task.description">
                                <td class="px-6 py-2.5 text-slate-700">{{ task.description }}</td>
                                <td class="px-6 py-2.5 text-right text-slate-500">{{ task.count }} vez(ces)</td>
                            </tr>
                            <tr v-if="summary.topServiceTasks.length === 0">
                                <td colspan="2" class="px-6 py-6 text-center text-slate-400">Sin datos</td>
                            </tr>
                        </tbody>
                    </table>
                </Card>

                <!-- Repuestos más usados -->
                <Card>
                    <h3 class="border-b border-slate-100 px-6 py-3 text-sm font-semibold text-slate-900">
                        Repuestos más usados
                    </h3>
                    <table class="min-w-full divide-y divide-slate-100 text-sm">
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="part in summary.topParts" :key="part.name">
                                <td class="px-6 py-2.5 text-slate-700">{{ part.name }}</td>
                                <td class="px-6 py-2.5 text-slate-500">{{ part.quantity }} und.</td>
                                <td class="px-6 py-2.5 text-right font-medium text-slate-900">Q {{ Number(part.total).toFixed(2) }}</td>
                            </tr>
                            <tr v-if="summary.topParts.length === 0">
                                <td colspan="3" class="px-6 py-6 text-center text-slate-400">Sin datos</td>
                            </tr>
                        </tbody>
                    </table>
                </Card>

                <!-- Por mecánico -->
                <Card>
                    <h3 class="border-b border-slate-100 px-6 py-3 text-sm font-semibold text-slate-900">
                        Facturado por mecánico
                    </h3>
                    <table class="min-w-full divide-y divide-slate-100 text-sm">
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="entry in summary.byMechanic" :key="entry.name">
                                <td class="px-6 py-2.5 text-slate-700">{{ entry.name }}</td>
                                <td class="px-6 py-2.5 text-slate-500">{{ entry.count }} orden(es)</td>
                                <td class="px-6 py-2.5 text-right font-medium text-slate-900">Q {{ Number(entry.total).toFixed(2) }}</td>
                            </tr>
                            <tr v-if="summary.byMechanic.length === 0">
                                <td colspan="3" class="px-6 py-6 text-center text-slate-400">Sin datos</td>
                            </tr>
                        </tbody>
                    </table>
                </Card>

                <!-- Por estado -->
                <Card>
                    <h3 class="border-b border-slate-100 px-6 py-3 text-sm font-semibold text-slate-900">
                        Órdenes por estado
                    </h3>
                    <table class="min-w-full divide-y divide-slate-100 text-sm">
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="entry in summary.byStatus" :key="entry.status">
                                <td class="px-6 py-2.5 text-slate-700">{{ statusLabels[entry.status] ?? entry.status }}</td>
                                <td class="px-6 py-2.5 text-right font-medium text-slate-900">{{ entry.count }}</td>
                            </tr>
                            <tr v-if="summary.byStatus.length === 0">
                                <td colspan="2" class="px-6 py-6 text-center text-slate-400">Sin datos</td>
                            </tr>
                        </tbody>
                    </table>
                </Card>

                <!-- Servicios vs. reparaciones -->
                <Card>
                    <h3 class="border-b border-slate-100 px-6 py-3 text-sm font-semibold text-slate-900">
                        Servicios vs. reparaciones (entregadas y cobradas)
                    </h3>
                    <table class="min-w-full divide-y divide-slate-100 text-sm">
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="entry in summary.byType" :key="entry.type">
                                <td class="px-6 py-2.5 text-slate-700">{{ typeLabels[entry.type] ?? entry.type }}</td>
                                <td class="px-6 py-2.5 text-slate-500">{{ entry.count }} orden(es)</td>
                                <td class="px-6 py-2.5 text-right font-medium text-slate-900">Q {{ Number(entry.total).toFixed(2) }}</td>
                            </tr>
                            <tr v-if="summary.byType.length === 0">
                                <td colspan="3" class="px-6 py-6 text-center text-slate-400">Sin datos</td>
                            </tr>
                        </tbody>
                    </table>
                </Card>

                <!-- Servicio menor vs. mayor -->
                <Card>
                    <h3 class="border-b border-slate-100 px-6 py-3 text-sm font-semibold text-slate-900">
                        Servicio menor vs. mayor (entregados y cobrados)
                    </h3>
                    <table class="min-w-full divide-y divide-slate-100 text-sm">
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="entry in summary.byServiceScope" :key="entry.scope">
                                <td class="px-6 py-2.5 text-slate-700">{{ scopeLabels[entry.scope] ?? entry.scope }}</td>
                                <td class="px-6 py-2.5 text-slate-500">{{ entry.count }} orden(es)</td>
                                <td class="px-6 py-2.5 text-right font-medium text-slate-900">Q {{ Number(entry.total).toFixed(2) }}</td>
                            </tr>
                            <tr v-if="summary.byServiceScope.length === 0">
                                <td colspan="3" class="px-6 py-6 text-center text-slate-400">Sin datos</td>
                            </tr>
                        </tbody>
                    </table>
                </Card>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
