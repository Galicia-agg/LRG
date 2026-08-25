<script setup>
import Icon from '@/Components/Icon.vue';
import { Head, Link } from '@inertiajs/vue3';
import { usePermissions } from '@/Composables/usePermissions';

const props = defineProps({
    quote: Object,
    business: Object,
});

const { can } = usePermissions();

function formatDate(dateStr) {
    return new Date(`${dateStr}T00:00:00`).toLocaleDateString('es-GT', { dateStyle: 'long' });
}

function formatDateTime(iso) {
    return new Date(iso).toLocaleString('es-GT', { dateStyle: 'long', timeStyle: 'short' });
}

function printQuote() {
    window.print();
}
</script>

<template>
    <Head :title="`Cotización #${quote.id}`" />

    <div class="min-h-screen bg-slate-100 py-8 print:bg-white print:py-0">
        <div class="mx-auto max-w-3xl px-4 print:max-w-full print:px-0">
            <!-- Actions (hidden on print) -->
            <div class="mb-4 flex items-center justify-between print:hidden">
                <Link :href="route('quotes.index')" class="text-sm font-medium text-primary-600 hover:text-primary-800">
                    ← Volver a cotizaciones
                </Link>
                <div class="flex items-center gap-2">
                    <span
                        v-if="quote.is_converted"
                        class="inline-flex items-center gap-1.5 rounded-lg bg-accent-100 px-3 py-2 text-sm font-medium text-accent-800"
                    >
                        <Icon name="check" class="h-4 w-4" />
                        Convertida en venta #{{ quote.sale_id }}
                    </span>
                    <Link
                        v-else-if="!quote.is_expired && can('sales.create')"
                        :href="route('pos.create', { quote: quote.id })"
                        class="inline-flex items-center gap-2 rounded-lg bg-accent-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-accent-700"
                    >
                        <Icon name="cart" class="h-4 w-4" />
                        Convertir en venta
                    </Link>
                    <button
                        type="button"
                        @click="printQuote"
                        class="inline-flex cursor-pointer items-center gap-2 rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-700"
                    >
                        <Icon name="printer" class="h-4 w-4" />
                        Imprimir / Guardar PDF
                    </button>
                </div>
            </div>

            <!-- Document -->
            <div class="quote-doc rounded-xl border border-slate-200 bg-white p-8 shadow-card print:rounded-none print:border-0 print:p-0 print:shadow-none">
                <div class="flex items-start justify-between gap-4 border-b border-slate-200 pb-6">
                    <div>
                        <h1 class="text-xl font-bold text-slate-900">{{ business.name }}</h1>
                        <p class="mt-1 text-sm text-slate-500">Cotización de productos</p>
                    </div>
                    <div class="text-right">
                        <p class="text-lg font-bold text-primary-700">Cotización #{{ quote.id }}</p>
                        <p class="mt-1 text-xs text-slate-500">Emitida: {{ formatDateTime(quote.created_at) }}</p>
                        <p class="text-xs font-semibold" :class="quote.is_expired ? 'text-red-600' : 'text-slate-600'">
                            Válida hasta: {{ formatDate(quote.valid_until) }}
                            <span v-if="quote.is_expired">(vencida)</span>
                        </p>
                    </div>
                </div>

                <div class="mt-6 grid grid-cols-2 gap-6 text-sm">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Cliente</p>
                        <p class="mt-1 font-medium text-slate-900">{{ quote.customer.name }}</p>
                        <p v-if="quote.customer.phone" class="text-slate-600">Tel: {{ quote.customer.phone }}</p>
                        <p v-if="quote.customer.email" class="text-slate-600">{{ quote.customer.email }}</p>
                        <p v-if="quote.customer.nit" class="text-slate-600">NIT: {{ quote.customer.nit }}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Atendido por</p>
                        <p class="mt-1 font-medium text-slate-900">{{ quote.seller }}</p>
                    </div>
                </div>

                <table class="mt-8 w-full text-sm">
                    <thead>
                        <tr class="border-b-2 border-slate-300 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                            <th class="pb-2">Producto</th>
                            <th class="pb-2 text-center">Cantidad</th>
                            <th class="pb-2 text-right">Precio unitario</th>
                            <th class="pb-2 text-right">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(item, index) in quote.items" :key="index" class="border-b border-slate-100">
                            <td class="py-2.5 text-slate-800">{{ item.name }}</td>
                            <td class="py-2.5 text-center text-slate-600">{{ item.quantity }} {{ item.unit }}</td>
                            <td class="py-2.5 text-right text-slate-600">Q {{ item.unit_price.toFixed(2) }}</td>
                            <td class="py-2.5 text-right font-medium text-slate-900">Q {{ item.subtotal.toFixed(2) }}</td>
                        </tr>
                    </tbody>
                </table>

                <div class="mt-4 flex justify-end">
                    <div class="w-64 space-y-1.5 text-sm">
                        <div class="flex justify-between text-slate-600">
                            <span>Subtotal</span>
                            <span>Q {{ quote.subtotal.toFixed(2) }}</span>
                        </div>
                        <div v-if="quote.discount > 0" class="flex justify-between text-slate-600">
                            <span>Descuento</span>
                            <span>- Q {{ quote.discount.toFixed(2) }}</span>
                        </div>
                        <div class="flex justify-between border-t border-slate-300 pt-1.5 text-base font-bold text-slate-900">
                            <span>Total</span>
                            <span>Q {{ quote.total.toFixed(2) }}</span>
                        </div>
                    </div>
                </div>

                <div v-if="quote.notes" class="mt-6 border-t border-slate-200 pt-4 text-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Notas</p>
                    <p class="mt-1 whitespace-pre-line text-slate-700">{{ quote.notes }}</p>
                </div>

                <div class="mt-10 border-t border-slate-200 pt-4 text-center text-xs text-slate-400">
                    <p>
                        Esta cotización tiene una vigencia hasta el {{ formatDate(quote.valid_until) }}. Los precios están
                        sujetos a cambio sin previo aviso después de esa fecha y no representan una reserva de inventario.
                    </p>
                    <p class="mt-1">¡Gracias por su preferencia!</p>
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

    .quote-doc * {
        color: #000 !important;
        background: transparent !important;
    }
}
</style>
