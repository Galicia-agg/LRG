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
    customers: Array,
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
    form.customer_name = customer.name;
    form.customer_phone = customer.phone ?? '';
    form.customer_email = customer.email ?? '';
    form.customer_nit = customer.nit ?? '';
    customerQuery.value = '';
    showCustomerDropdown.value = false;
}

function clearCustomer() {
    form.customer_id = null;
    form.customer_name = '';
    form.customer_phone = '';
    form.customer_email = '';
    form.customer_nit = '';
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
        existing.quantity += 1;
        return;
    }

    cart.value.push({
        product_id: product.id,
        name: product.name,
        unit: product.unit,
        unit_price: Number(product.sale_price),
        quantity: 1,
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
    customer_name: '',
    customer_phone: '',
    customer_email: '',
    customer_nit: '',
    valid_days: 8,
    notes: '',
    items: [],
});

function submitQuote() {
    form.items = cart.value.map((line) => ({
        product_id: line.product_id,
        quantity: line.quantity,
    }));

    form.post(route('quotes.store'), {
        preserveScroll: true,
    });
}
</script>

<template>
    <Head title="Nueva cotización" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-lg font-semibold leading-tight text-slate-900">Nueva cotización</h2>
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
                                Q {{ product.sale_price }} / {{ product.unit }}
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
                        <Icon name="document" class="h-4 w-4 text-primary-600" />
                        Cotización
                    </h3>

                    <div class="mb-4">
                        <label class="mb-1 block text-xs font-medium text-slate-500">
                            Cliente existente (opcional)
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
                        </div>
                    </div>

                    <div class="space-y-2">
                        <div>
                            <InputLabel for="customer_name" value="Nombre del cliente" class="text-xs" />
                            <input
                                id="customer_name"
                                v-model="form.customer_name"
                                type="text"
                                class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500/40"
                            />
                            <InputError :message="form.errors.customer_name" class="mt-1" />
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <InputLabel for="customer_phone" value="Teléfono" class="text-xs" />
                                <input
                                    id="customer_phone"
                                    v-model="form.customer_phone"
                                    type="text"
                                    class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500/40"
                                />
                            </div>
                            <div>
                                <InputLabel for="customer_nit" value="NIT (opcional)" class="text-xs" />
                                <input
                                    id="customer_nit"
                                    v-model="form.customer_nit"
                                    type="text"
                                    class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500/40"
                                />
                            </div>
                        </div>
                    </div>

                    <InputError :message="form.errors.items" class="mb-2 mt-3" />

                    <div class="mt-3 flex-1 space-y-3 overflow-y-auto border-t border-slate-100 pt-3">
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
                                min="1"
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

                        <p v-if="cart.length === 0" class="text-sm text-slate-500">
                            Agrega productos a la cotización.
                        </p>
                    </div>

                    <div class="mt-4 space-y-2 border-t border-slate-200 pt-4 text-sm">
                        <div class="flex justify-between border-t border-slate-100 pt-2 text-base font-semibold text-slate-900">
                            <span>Total</span>
                            <span>Q {{ subtotal.toFixed(2) }}</span>
                        </div>

                        <div>
                            <InputLabel for="valid_days" value="Válida por (días)" class="text-xs" />
                            <input
                                id="valid_days"
                                v-model.number="form.valid_days"
                                type="number"
                                min="1"
                                max="90"
                                class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500/40"
                            />
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
                            :disabled="cart.length === 0 || !form.customer_name || form.processing"
                            @click="submitQuote"
                        >
                            Generar cotización
                        </PrimaryButton>
                    </div>
                </div>
            </Card>
        </div>
    </AuthenticatedLayout>
</template>
