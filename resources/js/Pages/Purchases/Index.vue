<script setup>
import { computed, ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Card from '@/Components/Card.vue';
import Icon from '@/Components/Icon.vue';
import { Head, Link, router } from '@inertiajs/vue3';

const props = defineProps({
    purchases: Array,
    from: String,
    to: String,
    summary: Object,
});

const fromDate = ref(props.from);
const toDate = ref(props.to);
const expandedId = ref(null);

function toISODate(date) {
    return date.toLocaleDateString('sv-SE');
}

function startOfWeek(date) {
    const d = new Date(date);
    const day = (d.getDay() + 6) % 7;
    d.setDate(d.getDate() - day);
    return d;
}

const presets = [
    {
        label: 'Hoy',
        range: () => {
            const today = new Date();
            return [today, today];
        },
    },
    {
        label: 'Esta semana',
        range: () => [startOfWeek(new Date()), new Date()],
    },
    {
        label: 'Este mes',
        range: () => {
            const now = new Date();
            return [new Date(now.getFullYear(), now.getMonth(), 1), now];
        },
    },
    {
        label: 'Mes pasado',
        range: () => {
            const now = new Date();
            const start = new Date(now.getFullYear(), now.getMonth() - 1, 1);
            const end = new Date(now.getFullYear(), now.getMonth(), 0);
            return [start, end];
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
        route('purchases.index'),
        { from: fromDate.value, to: toDate.value },
        { preserveState: true, replace: true },
    );
}

const maxDayTotal = computed(() =>
    Math.max(1, ...(props.summary.byDay ?? []).map((d) => Number(d.total))),
);

function toggleExpand(purchase) {
    expandedId.value = expandedId.value === purchase.id ? null : purchase.id;
}

function formatDateTime(iso) {
    return new Date(iso).toLocaleString('es-GT', {
        day: '2-digit',
        month: 'short',
        hour: '2-digit',
        minute: '2-digit',
    });
}

function formatDay(dateStr) {
    return new Date(`${dateStr}T00:00:00`).toLocaleDateString('es-GT', {
        weekday: 'short',
        day: '2-digit',
        month: 'short',
    });
}
</script>

<template>
    <Head title="Compras" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-lg font-semibold leading-tight text-slate-900">Compras / Ingresos de mercancía</h2>
        </template>

        <div class="mx-auto max-w-screen-2xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
            <div class="flex justify-end">
                <Link
                    :href="route('purchases.create')"
                    class="inline-flex items-center justify-center gap-2 rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-700"
                >
                    <Icon name="plus" class="h-4 w-4" />
                    Registrar ingreso
                </Link>
            </div>

            <!-- Filters -->
            <Card padded>
                <div class="flex flex-wrap gap-2">
                    <button
                        v-for="preset in presets"
                        :key="preset.label"
                        type="button"
                        @click="applyPreset(preset)"
                        class="rounded-full border border-slate-200 px-3 py-1 text-xs font-medium text-slate-600 transition hover:border-primary-400 hover:text-primary-700"
                    >
                        {{ preset.label }}
                    </button>
                </div>

                <div class="mt-4 flex flex-wrap items-end gap-3">
                    <div>
                        <label for="from" class="mb-1 block text-xs font-medium text-slate-500">Desde</label>
                        <input
                            id="from"
                            v-model="fromDate"
                            type="date"
                            class="rounded-lg border-slate-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500/40"
                        />
                    </div>
                    <div>
                        <label for="to" class="mb-1 block text-xs font-medium text-slate-500">Hasta</label>
                        <input
                            id="to"
                            v-model="toDate"
                            type="date"
                            class="rounded-lg border-slate-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500/40"
                        />
                    </div>
                    <button
                        type="button"
                        @click="applyRange"
                        class="inline-flex items-center justify-center rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-700"
                    >
                        Aplicar
                    </button>
                </div>
            </Card>

            <!-- KPI cards -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <Card padded>
                    <p class="text-sm font-medium text-slate-500">Total invertido</p>
                    <p class="mt-1 text-2xl font-semibold text-slate-900">Q {{ summary.total.toFixed(2) }}</p>
                </Card>
                <Card padded>
                    <p class="text-sm font-medium text-slate-500">Ingresos registrados</p>
                    <p class="mt-1 text-2xl font-semibold text-slate-900">{{ summary.count }}</p>
                </Card>
                <Card padded>
                    <p class="text-sm font-medium text-slate-500">Líneas de producto</p>
                    <p class="mt-1 text-2xl font-semibold text-slate-900">{{ summary.itemsCount }}</p>
                </Card>
            </div>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <!-- Compras por día -->
                <Card padded class="lg:col-span-2">
                    <h3 class="mb-4 text-sm font-semibold text-slate-900">Ingresos por día</h3>
                    <div v-if="summary.byDay.length > 0" class="space-y-2">
                        <div
                            v-for="day in summary.byDay"
                            :key="day.date"
                            class="flex items-center gap-3 text-sm"
                        >
                            <span class="w-24 shrink-0 capitalize text-slate-500">{{ formatDay(day.date) }}</span>
                            <div class="h-2 flex-1 overflow-hidden rounded-full bg-slate-100">
                                <div
                                    class="h-full rounded-full bg-primary-500"
                                    :style="{ width: `${(Number(day.total) / maxDayTotal) * 100}%` }"
                                ></div>
                            </div>
                            <span class="w-16 shrink-0 text-center text-slate-400">{{ day.count }}x</span>
                            <span class="w-24 shrink-0 text-right font-medium text-slate-700">
                                Q {{ Number(day.total).toFixed(2) }}
                            </span>
                        </div>
                    </div>
                    <p v-else class="text-sm text-slate-400">Sin ingresos en este período</p>
                </Card>

                <!-- Por proveedor -->
                <Card padded>
                    <h3 class="mb-3 text-sm font-semibold text-slate-900">Por proveedor</h3>
                    <div v-if="(summary.bySupplier ?? []).length > 0" class="space-y-2">
                        <div
                            v-for="entry in summary.bySupplier"
                            :key="entry.name"
                            class="flex justify-between text-sm text-slate-700"
                        >
                            <span class="truncate">{{ entry.name }}</span>
                            <span class="shrink-0 font-medium">Q {{ entry.total.toFixed(2) }}</span>
                        </div>
                    </div>
                    <p v-else class="text-sm text-slate-400">Sin datos</p>
                </Card>
            </div>

            <!-- Totales por factura -->
            <Card>
                <h3 class="border-b border-slate-100 px-6 py-3 text-sm font-semibold text-slate-900">
                    Totales por factura
                    <span class="font-normal text-slate-400">— compará contra la factura física del proveedor</span>
                </h3>
                <table class="min-w-full divide-y divide-slate-100 text-sm">
                    <thead v-if="(summary.byInvoice ?? []).length > 0">
                        <tr class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                            <th class="px-6 pb-2 pt-3 text-left">Factura</th>
                            <th class="px-6 pb-2 pt-3 text-left">Proveedor</th>
                            <th class="px-6 pb-2 pt-3 text-left">Registros</th>
                            <th class="px-6 pb-2 pt-3 text-right">Total registrado</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="entry in summary.byInvoice" :key="entry.invoice_number">
                            <td class="px-6 py-2.5 font-medium text-slate-900">{{ entry.invoice_number }}</td>
                            <td class="px-6 py-2.5 text-slate-500">{{ entry.supplier }}</td>
                            <td class="px-6 py-2.5">
                                <span
                                    v-if="entry.has_multiple_entries"
                                    class="inline-flex items-center rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-800"
                                >
                                    {{ entry.entries }} registros — revisar
                                </span>
                                <span v-else class="text-slate-500">1 registro</span>
                            </td>
                            <td class="px-6 py-2.5 text-right font-medium text-slate-900">
                                Q {{ Number(entry.total).toFixed(2) }}
                            </td>
                        </tr>
                        <tr v-if="(summary.byInvoice ?? []).length === 0">
                            <td colspan="4" class="px-6 py-6 text-center text-slate-400">
                                No hay compras con número de factura en este período.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </Card>

            <!-- Productos más reabastecidos -->
            <Card>
                <h3 class="border-b border-slate-100 px-6 py-3 text-sm font-semibold text-slate-900">
                    Productos más reabastecidos en este período
                </h3>
                <table class="min-w-full divide-y divide-slate-100 text-sm">
                    <thead v-if="summary.topProducts.length > 0">
                        <tr class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                            <th class="px-6 pb-2 pt-3 text-left">Producto</th>
                            <th class="px-6 pb-2 pt-3 text-left">Cantidad ingresada</th>
                            <th class="px-6 pb-2 pt-3 text-left">Veces comprado</th>
                            <th class="px-6 pb-2 pt-3 text-right">Invertido</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="product in summary.topProducts" :key="product.name">
                            <td class="px-6 py-2.5 text-slate-700">{{ product.name }}</td>
                            <td class="px-6 py-2.5 text-slate-500">{{ product.quantity }}</td>
                            <td class="px-6 py-2.5 text-slate-500">{{ product.entries }}</td>
                            <td class="px-6 py-2.5 text-right font-medium text-slate-900">
                                Q {{ Number(product.spent).toFixed(2) }}
                            </td>
                        </tr>
                        <tr v-if="summary.topProducts.length === 0">
                            <td colspan="4" class="px-6 py-6 text-center text-slate-400">Sin datos</td>
                        </tr>
                    </tbody>
                </table>
            </Card>

            <!-- Detalle de ingresos -->
            <Card>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr class="text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                <th class="px-6 py-3"></th>
                                <th class="px-6 py-3">Fecha</th>
                                <th class="px-6 py-3">Proveedor</th>
                                <th class="px-6 py-3">Factura</th>
                                <th class="px-6 py-3">Registrado por</th>
                                <th class="px-6 py-3">Productos</th>
                                <th class="px-6 py-3">Total</th>
                                <th class="px-6 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-sm text-slate-700">
                            <template v-for="purchase in purchases" :key="purchase.id">
                                <tr class="hover:bg-slate-50">
                                    <td class="px-6 py-3">
                                        <button
                                            type="button"
                                            @click="toggleExpand(purchase)"
                                            class="text-slate-400 hover:text-slate-600"
                                        >
                                            <Icon
                                                name="dots"
                                                class="h-4 w-4 rotate-90 transition-transform"
                                                :class="{ 'rotate-0': expandedId === purchase.id }"
                                            />
                                        </button>
                                    </td>
                                    <td class="px-6 py-3">{{ formatDateTime(purchase.purchased_at) }}</td>
                                    <td class="px-6 py-3">{{ purchase.supplier ?? '—' }}</td>
                                    <td class="px-6 py-3">{{ purchase.invoice_number ?? '—' }}</td>
                                    <td class="px-6 py-3">{{ purchase.user ?? '—' }}</td>
                                    <td class="px-6 py-3">{{ purchase.items_count }}</td>
                                    <td class="px-6 py-3 font-medium text-slate-900">Q {{ purchase.total.toFixed(2) }}</td>
                                    <td class="px-6 py-3 text-right">
                                        <Link
                                            :href="route('purchases.show', purchase.id)"
                                            class="font-medium text-primary-600 hover:text-primary-800"
                                        >
                                            Ver
                                        </Link>
                                    </td>
                                </tr>
                                <tr v-if="expandedId === purchase.id" class="bg-slate-50/60">
                                    <td colspan="8" class="px-6 py-4">
                                        <table class="w-full max-w-lg text-xs text-slate-600">
                                            <tr v-for="(item, idx) in purchase.items" :key="idx">
                                                <td class="py-1 pr-4">{{ item.name }}</td>
                                                <td class="py-1 pr-4">{{ item.quantity }} x Q {{ item.unit_cost.toFixed(2) }}</td>
                                                <td class="py-1 text-right font-medium">Q {{ item.subtotal.toFixed(2) }}</td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </template>

                            <tr v-if="purchases.length === 0">
                                <td colspan="8" class="px-6 py-10 text-center text-slate-500">
                                    No hay ingresos registrados en este período.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </Card>
        </div>
    </AuthenticatedLayout>
</template>
