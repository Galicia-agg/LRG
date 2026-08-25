<script setup>
import Icon from '@/Components/Icon.vue';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
    workOrder: Object,
    business: Object,
});

const statusLabels = {
    recibido: 'Recibida',
    en_proceso: 'En proceso',
    listo: 'Lista para entrega',
    entregado: 'Entregada',
    cancelado: 'Cancelada',
};

const typeLabels = {
    servicio: 'Servicio / Mantenimiento',
    reparacion: 'Reparación',
};

function formatDateTime(iso) {
    return new Date(iso).toLocaleString('es-GT', { dateStyle: 'long', timeStyle: 'short' });
}

function printOrder() {
    window.print();
}
</script>

<template>
    <Head :title="`Orden de servicio #${workOrder.id}`" />

    <div class="min-h-screen bg-slate-100 py-8 print:bg-white print:py-0">
        <div class="mx-auto max-w-3xl px-4 print:max-w-full print:px-0">
            <div class="mb-4 flex items-center justify-between print:hidden">
                <Link :href="route('workshop.show', workOrder.id)" class="text-sm font-medium text-primary-600 hover:text-primary-800">
                    ← Volver a la orden
                </Link>
                <button
                    type="button"
                    @click="printOrder"
                    class="inline-flex cursor-pointer items-center gap-2 rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-700"
                >
                    <Icon name="printer" class="h-4 w-4" />
                    Imprimir
                </button>
            </div>

            <div class="workorder-doc rounded-xl border border-slate-200 bg-white p-8 shadow-card print:rounded-none print:border-0 print:p-0 print:shadow-none">
                <div class="flex items-start justify-between gap-4 border-b border-slate-200 pb-6">
                    <div>
                        <h1 class="text-xl font-bold text-slate-900">{{ business.name }}</h1>
                        <p class="mt-1 text-sm text-slate-500">Orden de servicio — Taller mecánico</p>
                    </div>
                    <div class="text-right">
                        <p class="text-lg font-bold text-primary-700">Orden #{{ workOrder.id }}</p>
                        <p class="mt-1 text-xs text-slate-500">Ingreso: {{ formatDateTime(workOrder.created_at) }}</p>
                        <p class="text-xs font-semibold text-slate-600">
                            Tipo: {{ typeLabels[workOrder.type] ?? workOrder.type }}
                            <span v-if="workOrder.service_scope"> ({{ workOrder.service_scope === 'mayor' ? 'Mayor' : 'Menor' }})</span>
                        </p>
                        <p class="text-xs font-semibold text-slate-600">Estado: {{ statusLabels[workOrder.status] ?? workOrder.status }}</p>
                    </div>
                </div>

                <div class="mt-6 grid grid-cols-2 gap-6 text-sm">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Cliente</p>
                        <p class="mt-1 font-medium text-slate-900">{{ workOrder.customer.name }}</p>
                        <p v-if="workOrder.customer.phone" class="text-slate-600">Tel: {{ workOrder.customer.phone }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Vehículo</p>
                        <p class="mt-1 font-medium text-slate-900">{{ workOrder.vehicle.label }}</p>
                        <p v-if="workOrder.vehicle.plate" class="text-slate-600">Placa: {{ workOrder.vehicle.plate }}</p>
                        <p v-if="workOrder.vehicle.color" class="text-slate-600">Color: {{ workOrder.vehicle.color }}</p>
                        <p v-if="workOrder.mileage_in" class="text-slate-600">Kilometraje de ingreso: {{ workOrder.mileage_in }}</p>
                    </div>
                </div>

                <div v-if="workOrder.type !== 'servicio'" class="mt-6 border-t border-slate-200 pt-4 text-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Problema reportado</p>
                    <p v-if="workOrder.reported_issue" class="mt-1 whitespace-pre-line text-slate-700">{{ workOrder.reported_issue }}</p>
                    <ul v-if="workOrder.failures.length > 0" class="mt-1.5 list-inside list-disc text-slate-700">
                        <li v-for="failure in workOrder.failures" :key="failure.id">{{ failure.description }}</li>
                    </ul>
                </div>

                <div v-else class="mt-6 border-t border-slate-200 pt-4 text-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Tareas realizadas</p>
                    <p v-if="workOrder.reported_issue" class="mt-1 whitespace-pre-line text-slate-700">{{ workOrder.reported_issue }}</p>
                    <ul v-if="workOrder.services.length > 0" class="mt-1.5 list-inside list-disc text-slate-700">
                        <li v-for="service in workOrder.services" :key="service.id">{{ service.description }}</li>
                    </ul>
                </div>

                <div v-if="workOrder.diagnosis" class="mt-4 border-t border-slate-200 pt-4 text-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Diagnóstico</p>
                    <p class="mt-1 whitespace-pre-line text-slate-700">{{ workOrder.diagnosis }}</p>
                </div>

                <div v-if="workOrder.laborItems.length > 0" class="mt-6 border-t border-slate-200 pt-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Mano de obra</p>
                    <table class="mt-2 w-full text-sm">
                        <tbody>
                            <tr v-for="item in workOrder.laborItems" :key="item.id" class="border-b border-slate-100">
                                <td class="py-1.5 text-slate-700">{{ item.description }}</td>
                                <td class="py-1.5 text-right font-medium text-slate-900">Q {{ item.amount.toFixed(2) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="workOrder.parts.length > 0" class="mt-6 border-t border-slate-200 pt-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Repuestos</p>
                    <table class="mt-2 w-full text-sm">
                        <thead>
                            <tr class="text-left text-xs text-slate-500">
                                <th class="pb-1">Producto</th>
                                <th class="pb-1 text-center">Cant.</th>
                                <th class="pb-1 text-right">P. Unit.</th>
                                <th class="pb-1 text-right">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="part in workOrder.parts" :key="part.id" class="border-b border-slate-100">
                                <td class="py-1.5 text-slate-700">{{ part.name }}</td>
                                <td class="py-1.5 text-center text-slate-600">{{ part.quantity }}</td>
                                <td class="py-1.5 text-right text-slate-600">Q {{ part.unit_price.toFixed(2) }}</td>
                                <td class="py-1.5 text-right font-medium text-slate-900">Q {{ part.subtotal.toFixed(2) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="mt-4 flex justify-end">
                    <div class="w-64 space-y-1.5 text-sm">
                        <div class="flex justify-between text-slate-600">
                            <span>Mano de obra</span>
                            <span>Q {{ workOrder.labor_total.toFixed(2) }}</span>
                        </div>
                        <div class="flex justify-between text-slate-600">
                            <span>Repuestos</span>
                            <span>Q {{ workOrder.parts_total.toFixed(2) }}</span>
                        </div>
                        <div class="flex justify-between border-t border-slate-300 pt-1.5 text-base font-bold text-slate-900">
                            <span>Total</span>
                            <span>Q {{ workOrder.total.toFixed(2) }}</span>
                        </div>
                    </div>
                </div>

                <div class="mt-10 grid grid-cols-2 gap-8 border-t border-slate-200 pt-8 text-center text-xs text-slate-500">
                    <div>
                        <div class="border-t border-slate-400 pt-1">Firma del cliente</div>
                    </div>
                    <div>
                        <div class="border-t border-slate-400 pt-1">Firma del mecánico{{ workOrder.mechanic ? ` — ${workOrder.mechanic}` : '' }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style>
@media print {
    @page {
        size: letter;
        margin: 15mm;
    }

    html,
    body {
        margin: 0;
        padding: 0;
        background: #fff;
    }

    .workorder-doc * {
        color: #000 !important;
        background: transparent !important;
    }
}
</style>
