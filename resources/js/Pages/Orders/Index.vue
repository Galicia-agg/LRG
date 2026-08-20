<script setup>
import { ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Card from '@/Components/Card.vue';
import Badge from '@/Components/Badge.vue';
import Icon from '@/Components/Icon.vue';
import { Head, Link, router } from '@inertiajs/vue3';

const props = defineProps({
    orders: Array,
    status: String,
    hasOpenCashSession: Boolean,
});

const expandedId = ref(null);
const paymentMethod = ref({});

const statusFilters = [
    { value: '', label: 'Todos' },
    { value: 'pending', label: 'Pendientes' },
    { value: 'confirmed', label: 'Confirmados' },
    { value: 'completed', label: 'Completados' },
    { value: 'cancelled', label: 'Cancelados' },
];

const statusLabels = {
    pending: { text: 'Pendiente', tone: 'amber' },
    confirmed: { text: 'Confirmado', tone: 'primary' },
    completed: { text: 'Completado', tone: 'green' },
    cancelled: { text: 'Cancelado', tone: 'slate' },
};

function filterBy(value) {
    router.get(route('orders.index'), value ? { status: value } : {}, { preserveState: true, replace: true });
}

function toggleExpand(order) {
    expandedId.value = expandedId.value === order.id ? null : order.id;
}

function confirmOrder(order) {
    router.post(route('orders.confirm', order.id), {}, { preserveScroll: true });
}

function cancelOrder(order) {
    const reason = prompt('¿Por qué se cancela este pedido? (opcional)');
    if (reason === null) return;
    router.post(route('orders.cancel', order.id), { reason }, { preserveScroll: true });
}

function completeOrder(order) {
    if (!confirm(`¿Registrar el pedido #${order.id} como venta? Esto descontará el inventario.`)) return;

    router.post(
        route('orders.complete', order.id),
        { payment_method: paymentMethod.value[order.id] ?? 'efectivo' },
        { preserveScroll: true },
    );
}
</script>

<template>
    <Head title="Pedidos online" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-lg font-semibold leading-tight text-slate-900">Pedidos online</h2>
        </template>

        <div class="mx-auto max-w-6xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
            <div
                v-if="!hasOpenCashSession"
                class="flex items-center gap-3 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800"
            >
                <Icon name="alert" class="h-4 w-4 shrink-0" />
                Necesitas
                <Link :href="route('cash-sessions.create')" class="font-semibold underline">abrir caja</Link>
                para poder completar pedidos como venta.
            </div>

            <div class="flex flex-wrap gap-2">
                <button
                    v-for="filter in statusFilters"
                    :key="filter.value"
                    type="button"
                    @click="filterBy(filter.value)"
                    class="rounded-full border px-3 py-1 text-xs font-medium transition"
                    :class="
                        status === filter.value
                            ? 'border-primary-600 bg-primary-600 text-white'
                            : 'border-slate-200 text-slate-600 hover:border-primary-400 hover:text-primary-700'
                    "
                >
                    {{ filter.label }}
                </button>
            </div>

            <Card>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr class="text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                <th class="px-6 py-3"></th>
                                <th class="px-6 py-3">Fecha</th>
                                <th class="px-6 py-3">Cliente</th>
                                <th class="px-6 py-3">Teléfono</th>
                                <th class="px-6 py-3">Total</th>
                                <th class="px-6 py-3">Estado</th>
                                <th class="px-6 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-sm text-slate-700">
                            <template v-for="order in orders" :key="order.id">
                                <tr class="hover:bg-slate-50">
                                    <td class="px-6 py-3">
                                        <button type="button" @click="toggleExpand(order)" class="text-slate-400 hover:text-slate-600">
                                            <Icon
                                                name="chevronDown"
                                                class="h-4 w-4 transition-transform"
                                                :class="{ 'rotate-180': expandedId === order.id }"
                                            />
                                        </button>
                                    </td>
                                    <td class="px-6 py-3">
                                        {{ new Date(order.created_at).toLocaleString('es-GT', { dateStyle: 'short', timeStyle: 'short' }) }}
                                    </td>
                                    <td class="px-6 py-3 font-medium text-slate-900">{{ order.customer_name }}</td>
                                    <td class="px-6 py-3">{{ order.customer_phone }}</td>
                                    <td class="px-6 py-3 font-medium text-slate-900">Q {{ order.total }}</td>
                                    <td class="px-6 py-3">
                                        <Badge :tone="statusLabels[order.status]?.tone ?? 'slate'">
                                            {{ statusLabels[order.status]?.text ?? order.status }}
                                        </Badge>
                                    </td>
                                    <td class="px-6 py-3 text-right">
                                        <div class="flex items-center justify-end gap-3">
                                            <button
                                                v-if="order.status === 'pending'"
                                                @click="confirmOrder(order)"
                                                class="font-medium text-primary-600 hover:text-primary-800"
                                            >
                                                Confirmar
                                            </button>
                                            <template v-if="order.status === 'pending' || order.status === 'confirmed'">
                                                <select
                                                    v-model="paymentMethod[order.id]"
                                                    class="rounded-md border-slate-300 py-1 text-xs focus:border-primary-500 focus:ring-primary-500/40"
                                                >
                                                    <option value="efectivo">Efectivo</option>
                                                    <option value="tarjeta">Tarjeta</option>
                                                    <option value="transferencia">Transferencia</option>
                                                </select>
                                                <button
                                                    :disabled="!hasOpenCashSession"
                                                    @click="completeOrder(order)"
                                                    class="font-medium text-emerald-600 hover:text-emerald-800 disabled:cursor-not-allowed disabled:opacity-40"
                                                >
                                                    Completar
                                                </button>
                                                <button
                                                    @click="cancelOrder(order)"
                                                    class="font-medium text-red-600 hover:text-red-800"
                                                >
                                                    Cancelar
                                                </button>
                                            </template>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="expandedId === order.id" class="bg-slate-50/60">
                                    <td colspan="7" class="px-6 py-4">
                                        <table class="w-full max-w-lg text-xs text-slate-600">
                                            <tr v-for="item in order.items" :key="item.id">
                                                <td class="py-1 pr-4">{{ item.product?.name ?? '—' }}</td>
                                                <td class="py-1 pr-4">{{ item.quantity }} x Q {{ item.unit_price }}</td>
                                                <td class="py-1 text-right font-medium">Q {{ item.subtotal }}</td>
                                            </tr>
                                        </table>
                                        <p v-if="order.customer_address" class="mt-2 text-xs text-slate-500">
                                            Dirección: {{ order.customer_address }}
                                        </p>
                                        <p v-if="order.customer_email" class="mt-1 text-xs text-slate-500">
                                            Email: {{ order.customer_email }}
                                        </p>
                                        <p v-if="order.notes" class="mt-1 text-xs text-slate-500">
                                            Notas: {{ order.notes }}
                                        </p>
                                        <p v-if="order.status === 'cancelled' && order.cancellation_reason" class="mt-1 text-xs text-red-600">
                                            Motivo de cancelación: {{ order.cancellation_reason }}
                                        </p>
                                    </td>
                                </tr>
                            </template>

                            <tr v-if="orders.length === 0">
                                <td colspan="7" class="px-6 py-10 text-center text-slate-500">
                                    No hay pedidos en este filtro.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </Card>
        </div>
    </AuthenticatedLayout>
</template>
