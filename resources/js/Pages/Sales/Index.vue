<script setup>
import { computed, ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Card from '@/Components/Card.vue';
import Badge from '@/Components/Badge.vue';
import Icon from '@/Components/Icon.vue';
import { usePermissions } from '@/Composables/usePermissions';
import { Head, router } from '@inertiajs/vue3';

const props = defineProps({
    sales: Array,
    from: String,
    to: String,
    summary: Object,
});

const { can } = usePermissions();
const fromDate = ref(props.from);
const toDate = ref(props.to);
const expandedId = ref(null);

const methodLabels = {
    efectivo: 'Efectivo',
    tarjeta: 'Tarjeta',
    transferencia: 'Transferencia',
};

const statusLabels = {
    completed: { text: 'Completada', tone: 'green' },
    returned: { text: 'Anulada', tone: 'amber' },
    cancelled: { text: 'Cancelada', tone: 'slate' },
};

function toISODate(date) {
    return date.toLocaleDateString('sv-SE'); // sv-SE = ISO 8601 yyyy-mm-dd
}

function startOfWeek(date) {
    const d = new Date(date);
    const day = (d.getDay() + 6) % 7; // Monday = 0
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
        label: 'Ayer',
        range: () => {
            const yesterday = new Date();
            yesterday.setDate(yesterday.getDate() - 1);
            return [yesterday, yesterday];
        },
    },
    {
        label: 'Esta semana',
        range: () => [startOfWeek(new Date()), new Date()],
    },
    {
        label: 'Semana pasada',
        range: () => {
            const start = startOfWeek(new Date());
            start.setDate(start.getDate() - 7);
            const end = new Date(start);
            end.setDate(end.getDate() + 6);
            return [start, end];
        },
    },
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
        route('sales.index'),
        { from: fromDate.value, to: toDate.value },
        { preserveState: true, replace: true },
    );
}

const exportUrl = computed(() => route('sales.export', { from: fromDate.value, to: toDate.value }));

const methodBreakdown = computed(() =>
    Object.entries(props.summary.byMethod ?? {}).map(([method, amount]) => ({
        method,
        label: methodLabels[method] ?? method,
        amount: Number(amount),
    })),
);

const maxDayTotal = computed(() =>
    Math.max(1, ...(props.summary.byDay ?? []).map((d) => Number(d.total))),
);

function toggleExpand(sale) {
    expandedId.value = expandedId.value === sale.id ? null : sale.id;
}

function voidSale(sale) {
    if (confirm(`¿Anular la venta #${sale.id}? Esto devolverá el stock al inventario.`)) {
        router.post(route('sales.void', sale.id), {}, { preserveScroll: true });
    }
}

function formatTime(datetime) {
    return new Date(datetime).toLocaleTimeString('es-GT', { hour: '2-digit', minute: '2-digit' });
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
    <Head title="Ventas" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-lg font-semibold leading-tight text-slate-900">Ventas</h2>
        </template>

        <div class="mx-auto max-w-7xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
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
                    <a
                        :href="exportUrl"
                        class="ml-auto inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50"
                    >
                        <Icon name="receipt" class="h-4 w-4" />
                        Descargar CSV
                    </a>
                </div>
            </Card>

            <!-- KPI cards -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <Card padded>
                    <p class="text-sm font-medium text-slate-500">Total del período</p>
                    <p class="mt-1 text-2xl font-semibold text-slate-900">Q {{ summary.total.toFixed(2) }}</p>
                </Card>
                <Card padded>
                    <p class="text-sm font-medium text-slate-500">Ventas completadas</p>
                    <p class="mt-1 text-2xl font-semibold text-slate-900">{{ summary.count }}</p>
                </Card>
                <Card padded>
                    <p class="text-sm font-medium text-slate-500">Ticket promedio</p>
                    <p class="mt-1 text-2xl font-semibold text-slate-900">Q {{ summary.averageTicket.toFixed(2) }}</p>
                </Card>
                <Card padded>
                    <p class="text-sm font-medium text-slate-500">Ventas anuladas</p>
                    <p class="mt-1 text-2xl font-semibold" :class="summary.voidedCount > 0 ? 'text-amber-600' : 'text-slate-900'">
                        {{ summary.voidedCount }}
                    </p>
                </Card>
                <Card v-if="summary.profit !== undefined" padded>
                    <p class="text-sm font-medium text-slate-500">Ganancia estimada</p>
                    <p class="mt-1 text-2xl font-semibold text-emerald-700">Q {{ summary.profit.toFixed(2) }}</p>
                    <p class="mt-1 text-xs text-slate-500">Margen: {{ summary.profitMargin }}%</p>
                </Card>
            </div>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <!-- Ventas por día -->
                <Card padded class="lg:col-span-2">
                    <h3 class="mb-4 text-sm font-semibold text-slate-900">Ventas por día</h3>
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
                            <span class="w-24 shrink-0 text-right font-medium text-slate-700">
                                Q {{ Number(day.total).toFixed(2) }}
                            </span>
                        </div>
                    </div>
                    <p v-else class="text-sm text-slate-400">Sin ventas en este período</p>
                </Card>

                <!-- Por método de pago -->
                <Card padded>
                    <h3 class="mb-3 text-sm font-semibold text-slate-900">Por método de pago</h3>
                    <div v-if="methodBreakdown.length > 0" class="space-y-2">
                        <div
                            v-for="entry in methodBreakdown"
                            :key="entry.method"
                            class="flex justify-between text-sm text-slate-700"
                        >
                            <span>{{ entry.label }}</span>
                            <span class="font-medium">Q {{ entry.amount.toFixed(2) }}</span>
                        </div>
                    </div>
                    <p v-else class="text-sm text-slate-400">Sin ventas todavía</p>
                </Card>
            </div>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <!-- Top productos -->
                <Card>
                    <h3 class="border-b border-slate-100 px-6 py-3 text-sm font-semibold text-slate-900">
                        Productos más vendidos
                    </h3>
                    <table class="min-w-full divide-y divide-slate-100 text-sm">
                        <thead v-if="summary.topProducts.length > 0">
                            <tr class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                                <th class="px-6 pb-2 pt-3 text-left">Producto</th>
                                <th class="px-6 pb-2 pt-3 text-left">Cantidad</th>
                                <th class="px-6 pb-2 pt-3 text-right">Ingresos</th>
                                <th
                                    v-if="summary.topProducts[0]?.profit !== undefined"
                                    class="px-6 pb-2 pt-3 text-right"
                                >
                                    Ganancia
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="product in summary.topProducts" :key="product.name">
                                <td class="px-6 py-2.5 text-slate-700">{{ product.name }}</td>
                                <td class="px-6 py-2.5 text-slate-500">{{ product.quantity }} und.</td>
                                <td class="px-6 py-2.5 text-right font-medium text-slate-900">
                                    Q {{ Number(product.revenue).toFixed(2) }}
                                </td>
                                <td v-if="product.profit !== undefined" class="px-6 py-2.5 text-right text-emerald-700">
                                    Q {{ Number(product.profit).toFixed(2) }}
                                </td>
                            </tr>
                            <tr v-if="summary.topProducts.length === 0">
                                <td colspan="4" class="px-6 py-6 text-center text-slate-400">Sin datos</td>
                            </tr>
                        </tbody>
                    </table>
                </Card>

                <!-- Por cajero -->
                <Card>
                    <h3 class="border-b border-slate-100 px-6 py-3 text-sm font-semibold text-slate-900">
                        Ventas por cajero
                    </h3>
                    <table class="min-w-full divide-y divide-slate-100 text-sm">
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="cashier in summary.byCashier" :key="cashier.name">
                                <td class="px-6 py-2.5 text-slate-700">{{ cashier.name }}</td>
                                <td class="px-6 py-2.5 text-slate-500">{{ cashier.count }} venta(s)</td>
                                <td class="px-6 py-2.5 text-right font-medium text-slate-900">
                                    Q {{ Number(cashier.total).toFixed(2) }}
                                </td>
                            </tr>
                            <tr v-if="summary.byCashier.length === 0">
                                <td colspan="3" class="px-6 py-6 text-center text-slate-400">Sin datos</td>
                            </tr>
                        </tbody>
                    </table>
                </Card>
            </div>

            <!-- Detalle de ventas -->
            <Card>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr class="text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                <th class="px-6 py-3"></th>
                                <th class="px-6 py-3">Fecha</th>
                                <th class="px-6 py-3">Cliente</th>
                                <th class="px-6 py-3">Cajero</th>
                                <th class="px-6 py-3">Método</th>
                                <th class="px-6 py-3">Total</th>
                                <th class="px-6 py-3">Estado</th>
                                <th class="px-6 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-sm text-slate-700">
                            <template v-for="sale in sales" :key="sale.id">
                                <tr class="hover:bg-slate-50">
                                    <td class="px-6 py-3">
                                        <button
                                            type="button"
                                            @click="toggleExpand(sale)"
                                            class="text-slate-400 hover:text-slate-600"
                                        >
                                            <Icon
                                                name="chevronDown"
                                                class="h-4 w-4 transition-transform"
                                                :class="{ 'rotate-180': expandedId === sale.id }"
                                            />
                                        </button>
                                    </td>
                                    <td class="px-6 py-3">
                                        {{ new Date(sale.sold_at).toLocaleDateString('es-GT') }}
                                        {{ formatTime(sale.sold_at) }}
                                    </td>
                                    <td class="px-6 py-3">{{ sale.customer?.name ?? 'Consumidor final' }}</td>
                                    <td class="px-6 py-3">{{ sale.user?.name ?? '—' }}</td>
                                    <td class="px-6 py-3">
                                        {{ sale.payments.map((p) => methodLabels[p.method] ?? p.method).join(', ') }}
                                    </td>
                                    <td class="px-6 py-3 font-medium text-slate-900">Q {{ sale.total }}</td>
                                    <td class="px-6 py-3">
                                        <Badge :tone="statusLabels[sale.status]?.tone ?? 'slate'">
                                            {{ statusLabels[sale.status]?.text ?? sale.status }}
                                        </Badge>
                                    </td>
                                    <td class="px-6 py-3 text-right">
                                        <button
                                            v-if="sale.status === 'completed' && can('sales.void')"
                                            @click="voidSale(sale)"
                                            class="font-medium text-red-600 hover:text-red-800"
                                        >
                                            Anular
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="expandedId === sale.id" class="bg-slate-50/60">
                                    <td colspan="8" class="px-6 py-4">
                                        <table class="w-full max-w-lg text-xs text-slate-600">
                                            <tr v-for="item in sale.items" :key="item.id">
                                                <td class="py-1 pr-4">{{ item.product?.name ?? '—' }}</td>
                                                <td class="py-1 pr-4">{{ item.quantity }} x Q {{ item.unit_price }}</td>
                                                <td class="py-1 text-right font-medium">Q {{ item.subtotal }}</td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </template>

                            <tr v-if="sales.length === 0">
                                <td colspan="8" class="px-6 py-10 text-center text-slate-500">
                                    No hay ventas registradas en este período.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </Card>
        </div>
    </AuthenticatedLayout>
</template>
