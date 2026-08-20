<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import InputError from '@/Components/InputError.vue';
import Card from '@/Components/Card.vue';
import Icon from '@/Components/Icon.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    products: Array,
    customers: Array,
    cashSession: Object,
});

const search = ref('');
const cart = ref([]);

const customerQuery = ref('');
const showCustomerDropdown = ref(false);

const selectedCustomer = computed(
    () => props.customers.find((customer) => customer.id === form.customer_id) ?? null,
);

const filteredCustomers = computed(() => {
    const term = customerQuery.value.trim().toLowerCase();

    const matches = !term
        ? props.customers
        : props.customers.filter(
              (customer) =>
                  customer.name.toLowerCase().includes(term) ||
                  (customer.nit ?? '').toLowerCase().includes(term),
          );

    return matches.slice(0, 8);
});

function selectCustomer(customer) {
    form.customer_id = customer.id;
    customerQuery.value = '';
    showCustomerDropdown.value = false;
}

function clearCustomer() {
    form.customer_id = null;
    customerQuery.value = '';
}

const filteredProducts = computed(() => {
    const term = search.value.trim().toLowerCase();

    if (!term) {
        return props.products;
    }

    return props.products.filter(
        (product) =>
            product.name.toLowerCase().includes(term) ||
            product.sku.toLowerCase().includes(term) ||
            (product.barcode ?? '').toLowerCase().includes(term),
    );
});

function addToCart(product) {
    const existing = cart.value.find((line) => line.product_id === product.id);

    if (existing) {
        if (existing.quantity < Number(product.current_stock)) {
            existing.quantity += 1;
        }
        return;
    }

    cart.value.push({
        product_id: product.id,
        name: product.name,
        unit: product.unit,
        unit_price: Number(product.sale_price),
        quantity: 1,
        maxStock: Number(product.current_stock),
    });
}

function removeFromCart(index) {
    cart.value.splice(index, 1);
}

const subtotal = computed(() =>
    cart.value.reduce((sum, line) => sum + line.unit_price * line.quantity, 0),
);

const form = useForm({
    customer_id: null,
    discount: '0.00',
    items: [],
    payments: [],
});

const total = computed(() => Math.max(subtotal.value - Number(form.discount || 0), 0));

const paymentMethod = ref('efectivo');

function submitSale() {
    form.items = cart.value.map((line) => ({
        product_id: line.product_id,
        quantity: line.quantity,
        unit_price: line.unit_price,
    }));

    form.payments = [{ method: paymentMethod.value, amount: total.value.toFixed(2) }];

    form.post(route('pos.store'), {
        onSuccess: () => {
            cart.value = [];
            form.discount = '0.00';
            form.customer_id = null;
            customerQuery.value = '';
        },
        preserveScroll: true,
    });
}
</script>

<template>
    <Head title="Punto de venta" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-3">
                <h2 class="text-lg font-semibold leading-tight text-slate-900">Punto de venta</h2>
                <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-medium text-emerald-700">
                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                    Caja abierta · Q {{ cashSession.opening_amount }}
                </span>
            </div>
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
                        placeholder="Buscar producto por nombre, SKU o código de barras"
                        class="block w-full rounded-lg border-slate-300 py-2.5 pl-9 text-sm shadow-sm placeholder:text-slate-400 focus:border-primary-500 focus:ring-primary-500/40"
                        autofocus
                    />
                </div>

                <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
                    <button
                        v-for="product in filteredProducts"
                        :key="product.id"
                        type="button"
                        @click="addToCart(product)"
                        class="flex gap-3 rounded-lg border border-slate-200 p-3 text-left text-sm transition hover:border-primary-400 hover:bg-primary-50/50 hover:shadow-sm"
                    >
                        <div class="flex h-12 w-12 shrink-0 items-center justify-center overflow-hidden rounded-md border border-slate-200 bg-slate-50">
                            <img
                                v-if="product.images?.[0]?.url"
                                :src="product.images[0].url"
                                alt=""
                                class="h-full w-full object-cover"
                            />
                            <Icon v-else name="image" class="h-5 w-5 text-slate-300" />
                        </div>
                        <div class="min-w-0">
                            <div class="truncate font-medium text-slate-800">
                                {{ product.name }}
                            </div>
                            <div class="mt-1 text-xs text-slate-500">
                                Q {{ product.sale_price }} · Stock {{ product.current_stock }}
                                {{ product.unit }}
                            </div>
                        </div>
                    </button>

                    <p
                        v-if="filteredProducts.length === 0"
                        class="col-span-full py-10 text-center text-sm text-slate-500"
                    >
                        No se encontraron productos.
                    </p>
                </div>
            </Card>

            <Card padded>
                <div class="flex h-full flex-col">
                    <h3 class="mb-4 flex items-center gap-2 text-sm font-semibold text-slate-900">
                        <Icon name="cart" class="h-4 w-4 text-primary-600" />
                        Carrito
                    </h3>

                    <div class="mb-4">
                        <label class="mb-1 block text-xs font-medium text-slate-500">
                            Cliente (opcional)
                        </label>

                        <div
                            v-if="selectedCustomer"
                            class="flex items-center justify-between rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm"
                        >
                            <span class="truncate font-medium text-slate-800">{{ selectedCustomer.name }}</span>
                            <button
                                type="button"
                                @click="clearCustomer"
                                class="text-slate-400 hover:text-red-600"
                            >
                                <Icon name="close" class="h-4 w-4" />
                            </button>
                        </div>

                        <div v-else class="relative">
                            <input
                                v-model="customerQuery"
                                type="text"
                                placeholder="Buscar cliente por nombre o NIT"
                                class="block w-full rounded-lg border-slate-300 text-sm shadow-sm placeholder:text-slate-400 focus:border-primary-500 focus:ring-primary-500/40"
                                @focus="showCustomerDropdown = true"
                                @blur="showCustomerDropdown = false"
                            />

                            <div
                                v-if="showCustomerDropdown && filteredCustomers.length > 0"
                                class="absolute z-10 mt-1 max-h-48 w-full overflow-y-auto rounded-lg border border-slate-200 bg-white py-1 shadow-lg"
                            >
                                <button
                                    v-for="customer in filteredCustomers"
                                    :key="customer.id"
                                    type="button"
                                    @mousedown.prevent="selectCustomer(customer)"
                                    class="block w-full px-3 py-2 text-left text-sm hover:bg-slate-50"
                                >
                                    <span class="font-medium text-slate-800">{{ customer.name }}</span>
                                    <span v-if="customer.nit" class="text-xs text-slate-500"> · {{ customer.nit }}</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <InputError :message="form.errors.items" class="mb-2" />

                    <div class="flex-1 space-y-3 overflow-y-auto">
                        <div
                            v-for="(line, index) in cart"
                            :key="line.product_id"
                            class="flex items-center justify-between gap-2 text-sm"
                        >
                            <div class="min-w-0 flex-1">
                                <div class="truncate text-slate-800">
                                    {{ line.name }}
                                </div>
                                <div class="text-xs text-slate-500">
                                    Q {{ line.unit_price.toFixed(2) }} c/u
                                </div>
                            </div>
                            <input
                                type="number"
                                min="0.01"
                                :max="line.maxStock"
                                step="0.01"
                                v-model.number="line.quantity"
                                class="w-16 rounded-lg border-slate-300 text-sm focus:border-primary-500 focus:ring-primary-500/40"
                            />
                            <button
                                type="button"
                                @click="removeFromCart(index)"
                                class="text-slate-400 hover:text-red-600"
                            >
                                <Icon name="close" class="h-4 w-4" />
                            </button>
                        </div>

                        <p v-if="cart.length === 0" class="text-sm text-slate-500">
                            Agrega productos al carrito.
                        </p>
                    </div>

                    <div class="mt-4 space-y-2 border-t border-slate-200 pt-4 text-sm">
                        <div class="flex justify-between text-slate-600">
                            <span>Subtotal</span>
                            <span>Q {{ subtotal.toFixed(2) }}</span>
                        </div>
                        <div class="flex items-center justify-between text-slate-600">
                            <span>Descuento</span>
                            <input
                                type="number"
                                min="0"
                                step="0.01"
                                v-model="form.discount"
                                class="w-24 rounded-lg border-slate-300 text-sm focus:border-primary-500 focus:ring-primary-500/40"
                            />
                        </div>
                        <div class="flex justify-between border-t border-slate-100 pt-2 text-base font-semibold text-slate-900">
                            <span>Total</span>
                            <span>Q {{ total.toFixed(2) }}</span>
                        </div>

                        <div>
                            <select
                                v-model="paymentMethod"
                                class="mt-2 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500/40"
                            >
                                <option value="efectivo">Efectivo</option>
                                <option value="tarjeta">Tarjeta</option>
                                <option value="transferencia">Transferencia</option>
                            </select>
                        </div>

                        <PrimaryButton
                            class="w-full justify-center"
                            :disabled="cart.length === 0 || form.processing"
                            @click="submitSale"
                        >
                            Registrar venta
                        </PrimaryButton>

                        <Link
                            :href="route('cash-sessions.edit', cashSession.id)"
                            class="block text-center text-xs font-medium text-slate-500 hover:text-slate-700"
                        >
                            Cerrar caja
                        </Link>
                    </div>
                </div>
            </Card>
        </div>
    </AuthenticatedLayout>
</template>
