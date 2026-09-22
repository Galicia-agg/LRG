<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Card from '@/Components/Card.vue';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
    purchase: Object,
});

function formatDateTime(iso) {
    return new Date(iso).toLocaleString('es-GT', {
        day: '2-digit',
        month: 'long',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

function marginTone(margin) {
    if (margin >= 20) return 'text-emerald-700';
    if (margin >= 0) return 'text-amber-700';
    return 'text-red-700';
}
</script>

<template>
    <Head :title="`Ingreso #${purchase.id}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold leading-tight text-slate-900">Ingreso de mercancía #{{ purchase.id }}</h2>
                <Link :href="route('purchases.index')" class="text-sm font-medium text-primary-600 hover:text-primary-800">
                    Volver al listado
                </Link>
            </div>
        </template>

        <div class="mx-auto max-w-5xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
            <Card padded>
                <div class="grid grid-cols-2 gap-4 text-sm sm:grid-cols-4">
                    <div>
                        <p class="text-xs font-medium text-slate-500">Fecha</p>
                        <p class="mt-1 font-medium text-slate-900">{{ formatDateTime(purchase.purchased_at) }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-slate-500">Proveedor</p>
                        <p class="mt-1 font-medium text-slate-900">{{ purchase.supplier ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-slate-500">No. factura</p>
                        <p class="mt-1 font-medium text-slate-900">{{ purchase.invoice_number ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-slate-500">Registrado por</p>
                        <p class="mt-1 font-medium text-slate-900">{{ purchase.registered_by }}</p>
                    </div>
                </div>
                <p v-if="purchase.notes" class="mt-4 rounded-lg bg-slate-50 p-3 text-sm text-slate-600">
                    {{ purchase.notes }}
                </p>
            </Card>

            <Card>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50">
                            <tr class="text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                <th class="px-4 py-3">Producto</th>
                                <th class="px-4 py-3">Cantidad</th>
                                <th class="px-4 py-3">Costo unitario</th>
                                <th class="px-4 py-3">Subtotal</th>
                                <th class="px-4 py-3">Stock antes → después</th>
                                <th class="px-4 py-3">Costo antes → prom.</th>
                                <th class="px-4 py-3">Precio venta antes → nuevo</th>
                                <th class="px-4 py-3">Margen</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700">
                            <tr v-for="(item, idx) in purchase.items" :key="idx">
                                <td class="px-4 py-3 font-medium text-slate-900">{{ item.name }}</td>
                                <td class="px-4 py-3">{{ item.quantity }} {{ item.unit }}</td>
                                <td class="px-4 py-3">Q {{ item.unit_cost.toFixed(2) }}</td>
                                <td class="px-4 py-3">Q {{ item.subtotal.toFixed(2) }}</td>
                                <td class="px-4 py-3 text-slate-500">
                                    {{ item.previous_stock }} → {{ item.new_stock }}
                                </td>
                                <td class="px-4 py-3 text-slate-500">
                                    Q {{ item.previous_cost_price.toFixed(2) }} → Q {{ item.new_cost_price.toFixed(2) }}
                                </td>
                                <td class="px-4 py-3 text-slate-500">
                                    <span v-if="item.previous_sale_price !== item.sale_price">
                                        Q {{ item.previous_sale_price.toFixed(2) }} → Q {{ item.sale_price.toFixed(2) }}
                                    </span>
                                    <span v-else>Q {{ item.sale_price.toFixed(2) }}</span>
                                </td>
                                <td class="px-4 py-3 font-medium" :class="marginTone(item.margin_percent)">
                                    {{ item.margin_percent }}%
                                </td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr class="border-t border-slate-200 text-sm font-semibold text-slate-900">
                                <td class="px-4 py-3" colspan="3">Total</td>
                                <td class="px-4 py-3">Q {{ purchase.total.toFixed(2) }}</td>
                                <td colspan="4"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </Card>
        </div>
    </AuthenticatedLayout>
</template>
