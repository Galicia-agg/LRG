<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Card from '@/Components/Card.vue';
import Badge from '@/Components/Badge.vue';
import Icon from '@/Components/Icon.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    quotes: Array,
});

function formatDateTime(iso) {
    return new Date(iso).toLocaleString('es-GT', { dateStyle: 'medium', timeStyle: 'short' });
}

function formatDate(dateStr) {
    return new Date(`${dateStr}T00:00:00`).toLocaleDateString('es-GT', { dateStyle: 'medium' });
}
</script>

<template>
    <Head title="Cotizaciones" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-lg font-semibold leading-tight text-slate-900">Cotizaciones</h2>
        </template>

        <div class="mx-auto max-w-5xl space-y-4 px-4 py-8 sm:px-6 lg:px-8">
            <div class="flex justify-end">
                <Link
                    :href="route('quotes.create')"
                    class="inline-flex items-center justify-center gap-2 rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-700"
                >
                    <Icon name="plus" class="h-4 w-4" />
                    Nueva cotización
                </Link>
            </div>

            <Card>
                <table class="min-w-full divide-y divide-slate-100">
                    <thead>
                        <tr class="text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                            <th class="px-6 py-3">Fecha</th>
                            <th class="px-6 py-3">Cliente</th>
                            <th class="px-6 py-3">Vendedor</th>
                            <th class="px-6 py-3">Productos</th>
                            <th class="px-6 py-3">Total</th>
                            <th class="px-6 py-3">Válida hasta</th>
                            <th class="px-6 py-3">Estado</th>
                            <th class="px-6 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-sm text-slate-700">
                        <tr v-for="quote in quotes" :key="quote.id" class="hover:bg-slate-50">
                            <td class="px-6 py-3">{{ formatDateTime(quote.created_at) }}</td>
                            <td class="px-6 py-3">{{ quote.customer_name }}</td>
                            <td class="px-6 py-3">{{ quote.user ?? '—' }}</td>
                            <td class="px-6 py-3">{{ quote.items_count }}</td>
                            <td class="px-6 py-3 font-medium text-slate-900">Q {{ quote.total.toFixed(2) }}</td>
                            <td class="px-6 py-3">{{ formatDate(quote.valid_until) }}</td>
                            <td class="px-6 py-3">
                                <Badge :tone="quote.is_expired ? 'slate' : 'green'">
                                    {{ quote.is_expired ? 'Vencida' : 'Vigente' }}
                                </Badge>
                            </td>
                            <td class="px-6 py-3 text-right">
                                <Link
                                    :href="route('quotes.show', quote.id)"
                                    target="_blank"
                                    class="font-medium text-primary-600 hover:text-primary-800"
                                >
                                    Ver
                                </Link>
                            </td>
                        </tr>
                        <tr v-if="quotes.length === 0">
                            <td colspan="8" class="px-6 py-10 text-center text-sm text-slate-500">
                                Todavía no hay cotizaciones generadas.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </Card>
        </div>
    </AuthenticatedLayout>
</template>
