<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import Card from '@/Components/Card.vue';
import Icon from '@/Components/Icon.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    products: Array,
    suppliers: Array,
});

function toISODate(date) {
    return date.toLocaleDateString('sv-SE');
}

const search = ref('');
const lines = ref([]);

const filteredProducts = computed(() => {
    const term = search.value.trim().toLowerCase();

    if (!term) {
        return props.products;
    }

    return props.products.filter(
        (product) =>
            product.name.toLowerCase().includes(term) ||
            product.sku.toLowerCase().includes(term),
    );
});

function addLine(product) {
    const existing = lines.value.find((line) => line.product_id === product.id);

    if (existing) {
        existing.quantity += 1;
        return;
    }

    lines.value.push({
        product_id: product.id,
        name: product.name,
        unit: product.unit,
        current_stock: product.current_stock,
        current_cost: product.cost_price,
        quantity: 1,
        unit_cost: product.cost_price,
        sale_price: product.sale_price,
    });
}

function removeLine(index) {
    lines.value.splice(index, 1);
}

function projectedCost(line) {
    const totalUnits = Number(line.current_stock) + Number(line.quantity || 0);

    if (totalUnits <= 0) {
        return Number(line.unit_cost || 0);
    }

    return (
        (Number(line.current_stock) * Number(line.current_cost) +
            Number(line.quantity || 0) * Number(line.unit_cost || 0)) /
        totalUnits
    );
}

function margin(line) {
    const cost = projectedCost(line);
    const price = Number(line.sale_price || 0);

    if (price <= 0) {
        return 0;
    }

    return ((price - cost) / price) * 100;
}

const subtotal = computed(() =>
    lines.value.reduce((sum, line) => sum + Number(line.quantity || 0) * Number(line.unit_cost || 0), 0),
);

const form = useForm({
    supplier_id: null,
    purchased_at: toISODate(new Date()),
    invoice_number: '',
    notes: '',
    items: [],
});

function submit() {
    form.items = lines.value.map((line) => ({
        product_id: line.product_id,
        quantity: line.quantity,
        unit_cost: line.unit_cost,
        sale_price: line.sale_price,
    }));

    form.post(route('purchases.store'), {
        preserveScroll: true,
    });
}
</script>

<template>
    <Head title="Nuevo ingreso de mercancía" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-lg font-semibold leading-tight text-slate-900">Nuevo ingreso de mercancía</h2>
        </template>

        <div class="mx-auto grid max-w-7xl gap-6 px-4 py-8 sm:px-6 lg:grid-cols-3 lg:px-8">
            <Card class="lg:col-span-2" padded>
                <div class="relative mb-4">
                    <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-slate-400">
                        <Icon name="search" class="h-4 w-4" />
                    </span>
                    <input
                        v-model="search"
                        type="text"
                        placeholder="Buscar producto por nombre o SKU"
                        class="block w-full rounded-lg border-slate-300 py-2.5 pl-9 text-sm shadow-sm placeholder:text-slate-400 focus:border-primary-500 focus:ring-primary-500/40"
                        autofocus
                    />
                </div>

                <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
                    <button
                        v-for="product in filteredProducts"
                        :key="product.id"
                        type="button"
                        @click="addLine(product)"
                        class="flex cursor-pointer flex-col gap-1 rounded-lg border border-slate-200 p-3 text-left text-sm transition hover:border-primary-400 hover:bg-primary-50/50 hover:shadow-sm"
                    >
                        <div class="truncate font-medium text-slate-800">{{ product.name }}</div>
                        <div class="text-xs text-slate-500">
                            Stock: {{ product.current_stock }} {{ product.unit }} · Costo Q {{ product.cost_price.toFixed(2) }}
                        </div>
                    </button>

                    <p v-if="filteredProducts.length === 0" class="col-span-full py-10 text-center text-sm text-slate-500">
                        No se encontraron productos.
                    </p>
                </div>
            </Card>

            <Card padded>
                <div class="flex h-full flex-col">
                    <h3 class="mb-4 flex items-center gap-2 text-sm font-semibold text-slate-900">
                        <Icon name="inbox" class="h-4 w-4 text-primary-600" />
                        Ingreso de mercancía
                    </h3>

                    <div class="space-y-3">
                        <div>
                            <InputLabel for="supplier_id" value="Proveedor (opcional)" class="text-xs" />
                            <select
                                id="supplier_id"
                                v-model="form.supplier_id"
                                class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500/40"
                            >
                                <option :value="null">Sin proveedor</option>
                                <option v-for="supplier in suppliers" :key="supplier.id" :value="supplier.id">
                                    {{ supplier.name }}
                                </option>
                            </select>
                        </div>

                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <InputLabel for="purchased_at" value="Fecha" class="text-xs" />
                                <input
                                    id="purchased_at"
                                    v-model="form.purchased_at"
                                    type="date"
                                    class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500/40"
                                />
                            </div>
                            <div>
                                <InputLabel for="invoice_number" value="No. factura (opcional)" class="text-xs" />
                                <input
                                    id="invoice_number"
                                    v-model="form.invoice_number"
                                    type="text"
                                    class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500/40"
                                />
                            </div>
                        </div>
                    </div>

                    <InputError :message="form.errors.items" class="mb-2 mt-3" />

                    <div class="mt-3 flex-1 space-y-4 overflow-y-auto border-t border-slate-100 pt-3">
                        <div
                            v-for="(line, index) in lines"
                            :key="line.product_id"
                            class="rounded-lg border border-slate-200 p-3 text-sm"
                        >
                            <div class="mb-2 flex items-center justify-between gap-2">
                                <span class="min-w-0 truncate font-medium text-slate-800">{{ line.name }}</span>
                                <button
                                    type="button"
                                    @click="removeLine(index)"
                                    class="shrink-0 cursor-pointer text-slate-400 hover:text-red-600"
                                >
                                    <Icon name="close" class="h-4 w-4" />
                                </button>
                            </div>

                            <div class="grid grid-cols-3 gap-2">
                                <div>
                                    <label class="mb-1 block text-[11px] text-slate-500">Cantidad</label>
                                    <input
                                        type="number"
                                        min="0.01"
                                        step="0.01"
                                        v-model.number="line.quantity"
                                        class="w-full rounded-md border-slate-300 text-sm focus:border-primary-500 focus:ring-primary-500/40"
                                    />
                                </div>
                                <div>
                                    <label class="mb-1 block text-[11px] text-slate-500">Costo unitario</label>
                                    <input
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        v-model.number="line.unit_cost"
                                        class="w-full rounded-md border-slate-300 text-sm focus:border-primary-500 focus:ring-primary-500/40"
                                    />
                                </div>
                                <div>
                                    <label class="mb-1 block text-[11px] text-slate-500">Precio venta</label>
                                    <input
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        v-model.number="line.sale_price"
                                        class="w-full rounded-md border-slate-300 text-sm focus:border-primary-500 focus:ring-primary-500/40"
                                    />
                                </div>
                            </div>

                            <div class="mt-2 flex items-center justify-between text-xs text-slate-500">
                                <span>
                                    Stock: {{ line.current_stock }} → {{ (Number(line.current_stock) + Number(line.quantity || 0)).toFixed(2) }} {{ line.unit }}
                                </span>
                                <span>
                                    Costo prom.: Q {{ projectedCost(line).toFixed(2) }}
                                </span>
                            </div>
                            <div class="mt-1 flex items-center justify-between text-xs">
                                <span class="text-slate-500">
                                    Subtotal: Q {{ (Number(line.quantity || 0) * Number(line.unit_cost || 0)).toFixed(2) }}
                                </span>
                                <span
                                    class="font-medium"
                                    :class="margin(line) >= 20 ? 'text-emerald-700' : margin(line) >= 0 ? 'text-amber-700' : 'text-red-700'"
                                >
                                    Margen: {{ margin(line).toFixed(1) }}%
                                </span>
                            </div>
                        </div>

                        <p v-if="lines.length === 0" class="text-sm text-slate-500">
                            Agrega los productos que llegaron en este pedido.
                        </p>
                    </div>

                    <div class="mt-4 space-y-2 border-t border-slate-200 pt-4 text-sm">
                        <div class="flex justify-between border-t border-slate-100 pt-2 text-base font-semibold text-slate-900">
                            <span>Total invertido</span>
                            <span>Q {{ subtotal.toFixed(2) }}</span>
                        </div>

                        <div>
                            <InputLabel for="notes" value="Notas (opcional)" class="text-xs" />
                            <textarea
                                id="notes"
                                v-model="form.notes"
                                rows="2"
                                class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500/40"
                            ></textarea>
                        </div>

                        <PrimaryButton
                            class="w-full cursor-pointer justify-center"
                            :disabled="lines.length === 0 || form.processing"
                            @click="submit"
                        >
                            Registrar ingreso
                        </PrimaryButton>
                    </div>
                </div>
            </Card>
        </div>
    </AuthenticatedLayout>
</template>
