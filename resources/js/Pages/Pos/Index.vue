<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import Card from '@/Components/Card.vue';
import Icon from '@/Components/Icon.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, nextTick, ref, watch } from 'vue';
import { useHeldSales } from '@/Composables/useHeldSales';

const props = defineProps({
    products: Array,
    customers: Array,
    cashSession: Object,
    quote: Object,
});

const page = usePage();
const userId = computed(() => page.props.auth.user.id);

const search = ref('');
const searchInput = ref(null);
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
        discount: 0,
        maxStock: Number(product.current_stock),
    });
}

function removeFromCart(index) {
    cart.value.splice(index, 1);
}

// --- Feature: atajo de teclado / lector de código de barras ---
function handleSearchEnter() {
    if (filteredProducts.value.length === 1) {
        addToCart(filteredProducts.value[0]);
        search.value = '';
    }
}

const lineSubtotal = (line) => Math.max(line.quantity * line.unit_price - (Number(line.discount) || 0), 0);

const subtotal = computed(() => cart.value.reduce((sum, line) => sum + lineSubtotal(line), 0));

const form = useForm({
    customer_id: null,
    quote_id: null,
    discount: '0.00',
    items: [],
    payments: [],
});

const total = computed(() => Math.max(subtotal.value - Number(form.discount || 0), 0));

// --- Feature: pago mixto + cálculo de vuelto ---
const paymentLines = ref([{ method: 'efectivo', amount: '', received: '' }]);

const paymentsTotal = computed(() =>
    paymentLines.value.reduce((sum, line) => sum + (Number(line.amount) || 0), 0),
);

const remaining = computed(() => Math.round((total.value - paymentsTotal.value) * 100) / 100);

function addPaymentLine() {
    const amount = remaining.value > 0 ? remaining.value.toFixed(2) : '';
    paymentLines.value.push({ method: 'efectivo', amount, received: '' });
}

function removePaymentLine(index) {
    if (paymentLines.value.length === 1) return;
    paymentLines.value.splice(index, 1);
}

function changeFor(line) {
    const received = Number(line.received) || 0;
    const amount = Number(line.amount) || 0;
    return received > amount ? received - amount : 0;
}

// Keep the single payment line in sync with the total while there's only one.
watch(total, (value) => {
    if (paymentLines.value.length === 1) {
        paymentLines.value[0].amount = value.toFixed(2);
    }
});

// --- Feature: descuento por línea ya soportado vía line.discount ---

// --- Feature: crear cliente rápido desde el POS ---
const showNewCustomerModal = ref(false);
const newCustomerForm = useForm({
    name: '',
    phone: '',
    nit: '',
    email: '',
});

function submitNewCustomer() {
    newCustomerForm.post(route('customers.store'), {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
            const createdId = page.props.flash?.customerId;
            if (createdId) {
                form.customer_id = createdId;
            }
            showNewCustomerModal.value = false;
            newCustomerForm.reset();
        },
    });
}

// --- Feature: ventas en espera ---
const { heldSales, holdSale, resumeSale, discardSale } = useHeldSales(userId.value);

function holdCurrentSale() {
    if (cart.value.length === 0) return;

    holdSale({
        customer_id: form.customer_id,
        customerLabel: selectedCustomer.value?.name ?? null,
        discount: form.discount,
        items: cart.value.map((line) => ({ ...line })),
    });

    cart.value = [];
    form.discount = '0.00';
    form.customer_id = null;
    customerQuery.value = '';
}

function resumeHeldSale(id) {
    if (cart.value.length > 0) return;

    const sale = resumeSale(id);
    if (!sale) return;

    cart.value = sale.items;
    form.discount = sale.discount ?? '0.00';
    form.customer_id = sale.customer_id ?? null;
}

// --- Feature: convertir cotización en venta ---
const quoteNeedsCustomer = computed(() => props.quote && !props.quote.customer_id);

if (props.quote) {
    form.quote_id = props.quote.id;
    if (props.quote.customer_id) {
        form.customer_id = props.quote.customer_id;
    }
    cart.value = props.quote.items.map((item) => {
        const product = props.products.find((p) => p.id === item.product_id);
        return {
            product_id: item.product_id,
            name: item.name,
            unit: item.unit,
            unit_price: item.unit_price,
            quantity: Math.max(1, Math.round(item.quantity)),
            discount: 0,
            maxStock: product ? Number(product.current_stock) : item.quantity,
        };
    });
}

function submitSale() {
    form.items = cart.value.map((line) => ({
        product_id: line.product_id,
        quantity: line.quantity,
        unit_price: line.unit_price,
        discount: Number(line.discount) || 0,
    }));

    form.payments = paymentLines.value.map((line) => ({
        method: line.method,
        amount: Number(line.amount).toFixed(2),
    }));

    form.post(route('pos.store'), {
        onSuccess: () => {
            cart.value = [];
            form.discount = '0.00';
            form.customer_id = null;
            form.quote_id = null;
            customerQuery.value = '';
            paymentLines.value = [{ method: 'efectivo', amount: '', received: '' }];
            nextTick(() => searchInput.value?.focus());
        },
        preserveScroll: true,
    });
}

const canSubmit = computed(
    () => cart.value.length > 0 && Math.abs(remaining.value) < 0.01 && !form.processing,
);
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

        <div class="mx-auto max-w-[1600px] px-4 py-8 sm:px-6 lg:px-8">
            <!-- Banner: cotización cargada -->
            <div
                v-if="quote"
                class="mb-4 flex items-start gap-3 rounded-lg border border-accent-200 bg-accent-50 px-4 py-3 text-sm text-accent-900"
            >
                <Icon name="document" class="mt-0.5 h-4 w-4 shrink-0" />
                <div>
                    <p class="font-medium">
                        Cotización #{{ quote.id }} cargada — {{ quote.customer_name }}
                    </p>
                    <p v-if="quoteNeedsCustomer" class="mt-0.5 text-xs text-accent-700">
                        Esta cotización no tiene un cliente registrado. Selecciona o crea uno para completar la venta.
                    </p>
                </div>
            </div>

            <!-- Ventas en espera -->
            <div v-if="heldSales.length > 0" class="mb-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3">
                <p class="mb-2 flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wide text-amber-800">
                    <Icon name="pause" class="h-3.5 w-3.5" />
                    Ventas en espera ({{ heldSales.length }})
                </p>
                <div class="flex flex-wrap gap-2">
                    <div
                        v-for="held in heldSales"
                        :key="held.id"
                        class="flex items-center gap-2 rounded-lg border border-amber-300 bg-white px-3 py-1.5 text-xs"
                    >
                        <span class="font-medium text-slate-800">{{ held.customerLabel ?? 'Sin cliente' }}</span>
                        <span class="text-slate-500">
                            Q {{ held.items.reduce((s, l) => s + l.quantity * l.unit_price - (Number(l.discount) || 0), 0).toFixed(2) }}
                        </span>
                        <button
                            type="button"
                            :disabled="cart.length > 0"
                            :title="cart.length > 0 ? 'Vacía el carrito actual para reanudar' : 'Reanudar'"
                            @click="resumeHeldSale(held.id)"
                            class="cursor-pointer font-medium text-primary-600 hover:text-primary-800 disabled:cursor-not-allowed disabled:text-slate-300"
                        >
                            Reanudar
                        </button>
                        <button
                            type="button"
                            @click="discardSale(held.id)"
                            class="cursor-pointer text-slate-400 hover:text-red-600"
                        >
                            <Icon name="close" class="h-3.5 w-3.5" />
                        </button>
                    </div>
                </div>
            </div>

            <div class="grid gap-6 lg:grid-cols-5">
                <Card class="lg:col-span-3" padded>
                    <div class="relative mb-4">
                        <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-slate-400">
                            <Icon name="barcode" class="h-4 w-4" />
                        </span>
                        <input
                            ref="searchInput"
                            v-model="search"
                            type="text"
                            placeholder="Buscar producto o escanear código de barras"
                            class="block w-full rounded-lg border-slate-300 py-2.5 pl-9 text-sm shadow-sm placeholder:text-slate-400 focus:border-primary-500 focus:ring-primary-500/40"
                            autofocus
                            @keydown.enter.prevent="handleSearchEnter"
                        />
                    </div>

                    <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 xl:grid-cols-4">
                        <button
                            v-for="product in filteredProducts"
                            :key="product.id"
                            type="button"
                            @click="addToCart(product)"
                            class="flex cursor-pointer gap-3 rounded-lg border border-slate-200 p-3 text-left text-sm transition hover:border-primary-400 hover:bg-primary-50/50 hover:shadow-sm"
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

                <Card class="lg:col-span-2" padded>
                    <div class="flex h-full flex-col">
                        <div class="mb-4 flex items-center justify-between">
                            <h3 class="flex items-center gap-2 text-sm font-semibold text-slate-900">
                                <Icon name="cart" class="h-4 w-4 text-primary-600" />
                                Carrito
                            </h3>
                            <button
                                type="button"
                                :disabled="cart.length === 0"
                                @click="holdCurrentSale"
                                class="inline-flex cursor-pointer items-center gap-1 text-xs font-medium text-amber-700 hover:text-amber-900 disabled:cursor-not-allowed disabled:text-slate-300"
                            >
                                <Icon name="pause" class="h-3.5 w-3.5" />
                                Poner en espera
                            </button>
                        </div>

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
                                    class="cursor-pointer text-slate-400 hover:text-red-600"
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
                                        class="block w-full cursor-pointer px-3 py-2 text-left text-sm hover:bg-slate-50"
                                    >
                                        <span class="font-medium text-slate-800">{{ customer.name }}</span>
                                        <span v-if="customer.nit" class="text-xs text-slate-500"> · {{ customer.nit }}</span>
                                    </button>
                                </div>

                                <button
                                    type="button"
                                    @click="showNewCustomerModal = true"
                                    class="mt-1.5 inline-flex cursor-pointer items-center gap-1 text-xs font-medium text-primary-600 hover:text-primary-800"
                                >
                                    <Icon name="userPlus" class="h-3.5 w-3.5" />
                                    Crear cliente nuevo
                                </button>
                            </div>
                        </div>

                        <InputError :message="form.errors.items" class="mb-2" />

                        <div class="flex-1 space-y-3 overflow-y-auto">
                            <div
                                v-for="(line, index) in cart"
                                :key="line.product_id"
                                class="rounded-lg border border-slate-100 p-2 text-sm"
                            >
                                <div class="flex items-center justify-between gap-2">
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
                                        min="1"
                                        :max="Math.floor(line.maxStock)"
                                        step="1"
                                        v-model.number="line.quantity"
                                        @change="line.quantity = Math.max(1, Math.round(line.quantity || 1))"
                                        class="w-16 rounded-lg border-slate-300 text-sm focus:border-primary-500 focus:ring-primary-500/40"
                                    />
                                    <button
                                        type="button"
                                        @click="removeFromCart(index)"
                                        class="cursor-pointer text-slate-400 hover:text-red-600"
                                    >
                                        <Icon name="close" class="h-4 w-4" />
                                    </button>
                                </div>
                                <div class="mt-1.5 flex items-center justify-between gap-2 text-xs text-slate-500">
                                    <div class="flex items-center gap-1.5">
                                        <span>Descuento Q</span>
                                        <input
                                            type="number"
                                            min="0"
                                            step="0.01"
                                            v-model.number="line.discount"
                                            class="w-16 rounded-lg border-slate-300 text-xs focus:border-primary-500 focus:ring-primary-500/40"
                                        />
                                    </div>
                                    <span class="font-medium text-slate-700">Q {{ lineSubtotal(line).toFixed(2) }}</span>
                                </div>
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
                                <span>Descuento general</span>
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

                            <!-- Pago mixto -->
                            <div class="space-y-2 border-t border-slate-100 pt-2">
                                <div
                                    v-for="(line, index) in paymentLines"
                                    :key="index"
                                    class="rounded-lg border border-slate-100 p-2"
                                >
                                    <div class="flex items-center gap-2">
                                        <select
                                            v-model="line.method"
                                            class="flex-1 rounded-lg border-slate-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500/40"
                                        >
                                            <option value="efectivo">Efectivo</option>
                                            <option value="tarjeta">Tarjeta</option>
                                            <option value="transferencia">Transferencia</option>
                                        </select>
                                        <input
                                            type="number"
                                            min="0"
                                            step="0.01"
                                            v-model="line.amount"
                                            placeholder="Monto"
                                            class="w-24 rounded-lg border-slate-300 text-sm focus:border-primary-500 focus:ring-primary-500/40"
                                        />
                                        <button
                                            v-if="paymentLines.length > 1"
                                            type="button"
                                            @click="removePaymentLine(index)"
                                            class="cursor-pointer text-slate-400 hover:text-red-600"
                                        >
                                            <Icon name="close" class="h-4 w-4" />
                                        </button>
                                    </div>
                                    <div v-if="line.method === 'efectivo'" class="mt-1.5 flex items-center gap-2 text-xs text-slate-500">
                                        <span>Recibido</span>
                                        <input
                                            type="number"
                                            min="0"
                                            step="0.01"
                                            v-model="line.received"
                                            class="w-20 rounded-lg border-slate-300 text-xs focus:border-primary-500 focus:ring-primary-500/40"
                                        />
                                        <span v-if="changeFor(line) > 0" class="font-medium text-emerald-700">
                                            Cambio: Q {{ changeFor(line).toFixed(2) }}
                                        </span>
                                    </div>
                                </div>

                                <button
                                    type="button"
                                    @click="addPaymentLine"
                                    class="inline-flex cursor-pointer items-center gap-1 text-xs font-medium text-primary-600 hover:text-primary-800"
                                >
                                    <Icon name="plus" class="h-3.5 w-3.5" />
                                    Agregar método de pago
                                </button>

                                <p
                                    v-if="Math.abs(remaining) >= 0.01"
                                    class="text-xs font-medium"
                                    :class="remaining > 0 ? 'text-amber-700' : 'text-red-600'"
                                >
                                    {{ remaining > 0 ? `Falta cubrir Q ${remaining.toFixed(2)}` : `Excedente Q ${Math.abs(remaining).toFixed(2)}` }}
                                </p>
                            </div>

                            <PrimaryButton
                                class="w-full cursor-pointer justify-center"
                                :disabled="!canSubmit"
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
        </div>

        <!-- Modal: crear cliente nuevo -->
        <div
            v-if="showNewCustomerModal"
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 px-4"
            @click.self="showNewCustomerModal = false"
        >
            <div class="w-full max-w-sm rounded-xl bg-white p-6 shadow-lg">
                <h3 class="text-sm font-semibold text-slate-900">Nuevo cliente</h3>
                <div class="mt-4 space-y-3">
                    <div>
                        <InputLabel for="new_customer_name" value="Nombre" class="text-xs" />
                        <input
                            id="new_customer_name"
                            v-model="newCustomerForm.name"
                            type="text"
                            class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500/40"
                        />
                        <InputError :message="newCustomerForm.errors.name" class="mt-1" />
                    </div>
                    <div>
                        <InputLabel for="new_customer_phone" value="Teléfono (opcional)" class="text-xs" />
                        <input
                            id="new_customer_phone"
                            v-model="newCustomerForm.phone"
                            type="text"
                            class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500/40"
                        />
                    </div>
                    <div>
                        <InputLabel for="new_customer_nit" value="NIT (opcional)" class="text-xs" />
                        <input
                            id="new_customer_nit"
                            v-model="newCustomerForm.nit"
                            type="text"
                            class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500/40"
                        />
                    </div>
                </div>
                <div class="mt-5 flex justify-end gap-2">
                    <button
                        type="button"
                        @click="showNewCustomerModal = false"
                        class="cursor-pointer rounded-lg px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100"
                    >
                        Cancelar
                    </button>
                    <PrimaryButton
                        class="cursor-pointer"
                        :disabled="!newCustomerForm.name || newCustomerForm.processing"
                        @click="submitNewCustomer"
                    >
                        Crear
                    </PrimaryButton>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
