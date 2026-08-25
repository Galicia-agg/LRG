<script setup>
import Icon from '@/Components/Icon.vue';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
    sale: Object,
    business: Object,
});

const statusLabels = {
    completed: 'Completada',
    returned: 'Anulada',
    cancelled: 'Cancelada',
};

function formatDateTime(iso) {
    return new Date(iso).toLocaleString('es-GT', {
        dateStyle: 'medium',
        timeStyle: 'short',
    });
}

function printReceipt() {
    window.print();
}
</script>

<template>
    <Head :title="`Recibo #${sale.id}`" />

    <div class="min-h-screen bg-slate-100 py-8 print:bg-white print:py-0">
        <div class="mx-auto max-w-md px-4 print:max-w-full print:px-0">
            <!-- Actions (hidden on print) -->
            <div class="mb-4 flex items-center justify-between print:hidden">
                <Link :href="route('sales.index')" class="text-sm font-medium text-primary-600 hover:text-primary-800">
                    ← Volver a ventas
                </Link>
                <button
                    type="button"
                    @click="printReceipt"
                    class="inline-flex cursor-pointer items-center gap-2 rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-700"
                >
                    <Icon name="printer" class="h-4 w-4" />
                    Imprimir
                </button>
            </div>

            <!-- Receipt -->
            <div class="receipt rounded-xl border border-slate-200 bg-white p-6 shadow-card print:rounded-none print:border-0 print:p-0 print:shadow-none">
                <div class="text-center">
                    <h1 class="text-base font-bold text-slate-900">{{ business.name }}</h1>
                    <p class="mt-1 text-xs text-slate-500">Recibo de venta #{{ sale.id }}</p>
                    <p class="text-xs text-slate-500">{{ formatDateTime(sale.sold_at) }}</p>
                    <span
                        v-if="sale.status !== 'completed'"
                        class="mt-2 inline-block rounded-full bg-red-100 px-2 py-0.5 text-[11px] font-semibold text-red-700"
                    >
                        {{ statusLabels[sale.status] ?? sale.status }}
                    </span>
                </div>

                <div class="mt-4 flex justify-between border-t border-dashed border-slate-300 pt-3 text-xs text-slate-600">
                    <span>Atendido por</span>
                    <span class="font-medium text-slate-900">{{ sale.cashier }}</span>
                </div>
                <div class="mt-1 flex justify-between text-xs text-slate-600">
                    <span>Cliente</span>
                    <span class="font-medium text-slate-900">{{ sale.customer?.name ?? 'Consumidor final' }}</span>
                </div>
                <p v-if="sale.customer?.nit" class="mt-1 text-right text-xs text-slate-500">NIT: {{ sale.customer.nit }}</p>

                <div class="receipt-items mt-4 border-t border-dashed border-slate-300 pt-3 text-xs">
                    <div v-for="(item, index) in sale.items" :key="index" class="receipt-item mt-1.5 first:mt-0">
                        <div class="flex justify-between gap-2">
                            <span class="text-slate-800">{{ item.name }}</span>
                            <span class="shrink-0 font-medium text-slate-900">{{ item.subtotal.toFixed(2) }}</span>
                        </div>
                        <p class="text-[11px] text-slate-500">{{ item.quantity }} x Q {{ item.unit_price.toFixed(2) }}</p>
                    </div>
                </div>

                <div class="mt-3 space-y-1 border-t border-dashed border-slate-300 pt-3 text-xs">
                    <div class="flex justify-between text-slate-600">
                        <span>Subtotal</span>
                        <span>Q {{ sale.subtotal.toFixed(2) }}</span>
                    </div>
                    <div v-if="sale.labor_total > 0" class="flex justify-between text-slate-600">
                        <span>Mano de obra</span>
                        <span>Q {{ sale.labor_total.toFixed(2) }}</span>
                    </div>
                    <div v-if="sale.discount > 0" class="flex justify-between text-slate-600">
                        <span>Descuento</span>
                        <span>- Q {{ sale.discount.toFixed(2) }}</span>
                    </div>
                    <div class="flex justify-between border-t border-slate-200 pt-1 text-sm font-bold text-slate-900">
                        <span>Total</span>
                        <span>Q {{ sale.total.toFixed(2) }}</span>
                    </div>
                </div>

                <div class="mt-3 border-t border-dashed border-slate-300 pt-3 text-xs">
                    <p class="font-medium text-slate-500">Forma de pago</p>
                    <div v-for="(payment, index) in sale.payments" :key="index" class="mt-1 flex justify-between text-slate-700">
                        <span>{{ payment.method }}</span>
                        <span>Q {{ payment.amount.toFixed(2) }}</span>
                    </div>
                </div>

                <div class="mt-6 border-t border-dashed border-slate-300 pt-3 text-center text-[11px] text-slate-400">
                    <p>Este comprobante es un recibo interno y no tiene validez fiscal ante la SAT.</p>
                    <p class="mt-1">¡Gracias por su compra!</p>
                </div>
            </div>
        </div>
    </div>
</template>

<style>
/* Tuned for 80mm thermal receipt printers. */
@media print {
    @page {
        size: 80mm auto;
        margin: 0;
    }

    html,
    body {
        margin: 0;
        padding: 0;
        background: #fff;
    }

    .receipt {
        width: 72mm;
        margin: 0 auto;
        padding: 3mm 0 6mm;
        font-family: 'Courier New', Courier, monospace;
        font-size: 11px;
        line-height: 1.35;
    }

    .receipt * {
        color: #000 !important;
        background: transparent !important;
    }
}
</style>
