<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Card from '@/Components/Card.vue';
import Badge from '@/Components/Badge.vue';
import Icon from '@/Components/Icon.vue';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
    lowStock: Array,
    expiringSoon: Array,
});

function formatDate(dateStr) {
    return new Date(`${dateStr}T00:00:00`).toLocaleDateString('es-GT', { dateStyle: 'medium' });
}

function daysUntil(dateStr) {
    const diff = new Date(`${dateStr}T00:00:00`) - new Date(new Date().toDateString());
    return Math.round(diff / (1000 * 60 * 60 * 24));
}
</script>

<template>
    <Head title="Alertas" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-lg font-semibold leading-tight text-slate-900">Alertas</h2>
        </template>

        <div class="mx-auto max-w-5xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
            <!-- Stock bajo -->
            <div>
                <div class="mb-2 flex items-center justify-between">
                    <h3 class="flex items-center gap-2 text-sm font-semibold text-slate-900">
                        <Icon name="alert" class="h-4 w-4 text-amber-600" />
                        Stock bajo ({{ lowStock.length }})
                    </h3>
                    <a
                        v-if="lowStock.length > 0"
                        :href="route('alerts.export-low-stock')"
                        class="inline-flex items-center gap-1.5 text-xs font-medium text-primary-600 hover:text-primary-800"
                    >
                        <Icon name="document" class="h-3.5 w-3.5" />
                        Exportar a Excel (CSV)
                    </a>
                </div>
                <Card>
                    <table class="min-w-full divide-y divide-slate-100">
                        <thead>
                            <tr class="text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                <th class="px-6 py-3">Producto</th>
                                <th class="px-6 py-3">SKU</th>
                                <th class="px-6 py-3">Stock actual</th>
                                <th class="px-6 py-3">Stock mínimo</th>
                                <th class="px-6 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-sm text-slate-700">
                            <tr v-for="product in lowStock" :key="product.id" class="hover:bg-slate-50">
                                <td class="px-6 py-3 font-medium text-slate-900">{{ product.name }}</td>
                                <td class="px-6 py-3 text-slate-500">{{ product.sku }}</td>
                                <td class="px-6 py-3">
                                    <Badge :tone="product.current_stock <= 0 ? 'red' : 'amber'">
                                        {{ product.current_stock }} {{ product.unit }}
                                    </Badge>
                                </td>
                                <td class="px-6 py-3 text-slate-500">{{ product.min_stock }} {{ product.unit }}</td>
                                <td class="px-6 py-3 text-right">
                                    <Link
                                        :href="route('products.index', { q: product.sku })"
                                        class="font-medium text-primary-600 hover:text-primary-800"
                                    >
                                        Ver producto
                                    </Link>
                                </td>
                            </tr>
                            <tr v-if="lowStock.length === 0">
                                <td colspan="5" class="px-6 py-10 text-center text-sm text-slate-500">
                                    Ningún producto con stock bajo. Todo en orden.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </Card>
            </div>

            <!-- Próximos a vencer -->
            <div>
                <h3 class="mb-2 flex items-center gap-2 text-sm font-semibold text-slate-900">
                    <Icon name="clock" class="h-4 w-4 text-red-600" />
                    Próximos a vencer o vencidos ({{ expiringSoon.length }})
                </h3>
                <Card>
                    <table class="min-w-full divide-y divide-slate-100">
                        <thead>
                            <tr class="text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                <th class="px-6 py-3">Producto</th>
                                <th class="px-6 py-3">SKU</th>
                                <th class="px-6 py-3">Stock actual</th>
                                <th class="px-6 py-3">Vence</th>
                                <th class="px-6 py-3">Estado</th>
                                <th class="px-6 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-sm text-slate-700">
                            <tr v-for="product in expiringSoon" :key="product.id" class="hover:bg-slate-50">
                                <td class="px-6 py-3 font-medium text-slate-900">{{ product.name }}</td>
                                <td class="px-6 py-3 text-slate-500">{{ product.sku }}</td>
                                <td class="px-6 py-3">{{ product.current_stock }}</td>
                                <td class="px-6 py-3">{{ formatDate(product.expiration_date) }}</td>
                                <td class="px-6 py-3">
                                    <Badge :tone="product.is_expired ? 'red' : 'amber'">
                                        {{ product.is_expired ? 'Vencido' : `Vence en ${daysUntil(product.expiration_date)} día(s)` }}
                                    </Badge>
                                </td>
                                <td class="px-6 py-3 text-right">
                                    <Link
                                        :href="route('products.index', { q: product.sku })"
                                        class="font-medium text-primary-600 hover:text-primary-800"
                                    >
                                        Ver producto
                                    </Link>
                                </td>
                            </tr>
                            <tr v-if="expiringSoon.length === 0">
                                <td colspan="6" class="px-6 py-10 text-center text-sm text-slate-500">
                                    Ningún producto próximo a vencer en los próximos 30 días.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </Card>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
