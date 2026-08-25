<script setup>
import { computed, ref } from 'vue';
import StorefrontLayout from '@/Layouts/StorefrontLayout.vue';
import Icon from '@/Components/Icon.vue';
import { Head, Link } from '@inertiajs/vue3';
import { useCart } from '@/Composables/useCart';

const props = defineProps({
    products: Array,
});

const search = ref('');
const selectedCategory = ref('Todos');
const minPrice = ref('');
const maxPrice = ref('');
const sortBy = ref('default');
const recentlyAdded = ref(new Set());

const { addToCart, cartCount } = useCart();

const categories = computed(() => {
    const set = new Set(props.products.map((p) => p.category).filter(Boolean));
    return ['Todos', ...set];
});

const priceBounds = computed(() => {
    const prices = props.products.map((p) => p.sale_price);
    return {
        min: prices.length ? Math.min(...prices) : 0,
        max: prices.length ? Math.max(...prices) : 0,
    };
});

const sortOptions = [
    { value: 'default', label: 'Destacados' },
    { value: 'price-asc', label: 'Precio: menor a mayor' },
    { value: 'price-desc', label: 'Precio: mayor a menor' },
    { value: 'name-asc', label: 'Nombre: A-Z' },
];

function resetFilters() {
    search.value = '';
    selectedCategory.value = 'Todos';
    minPrice.value = '';
    maxPrice.value = '';
    sortBy.value = 'default';
}

const filteredProducts = computed(() => {
    const term = search.value.trim().toLowerCase();
    const min = minPrice.value !== '' ? Number(minPrice.value) : null;
    const max = maxPrice.value !== '' ? Number(maxPrice.value) : null;

    const filtered = props.products.filter((product) => {
        const matchesTerm = !term || product.name.toLowerCase().includes(term);
        const matchesCategory = selectedCategory.value === 'Todos' || product.category === selectedCategory.value;
        const matchesMin = min === null || product.sale_price >= min;
        const matchesMax = max === null || product.sale_price <= max;
        return matchesTerm && matchesCategory && matchesMin && matchesMax;
    });

    const sorted = [...filtered];
    if (sortBy.value === 'price-asc') {
        sorted.sort((a, b) => a.sale_price - b.sale_price);
    } else if (sortBy.value === 'price-desc') {
        sorted.sort((a, b) => b.sale_price - a.sale_price);
    } else if (sortBy.value === 'name-asc') {
        sorted.sort((a, b) => a.name.localeCompare(b.name));
    }

    return sorted;
});

function discountPercent(product) {
    if (!product.compare_at_price) return null;
    return Math.round(((product.compare_at_price - product.sale_price) / product.compare_at_price) * 100);
}

function handleAddToCart(product) {
    addToCart(product);
    recentlyAdded.value = new Set([...recentlyAdded.value, product.id]);
    setTimeout(() => {
        recentlyAdded.value = new Set([...recentlyAdded.value].filter((id) => id !== product.id));
    }, 1500);
}
</script>

<template>
    <Head title="Tienda" />

    <StorefrontLayout :cart-count="cartCount">
        <div class="mx-auto max-w-6xl space-y-6 px-4 py-8 sm:px-6">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">Nuestros productos</h1>
                <p class="mt-1 text-sm text-slate-500">
                    Arma tu pedido y lo confirmamos por teléfono. El pago se hace en tienda o contra entrega.
                </p>
            </div>

            <!-- Trust badges -->
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                <div class="flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-xs text-slate-600 shadow-card">
                    <Icon name="cart" class="h-4 w-4 shrink-0 text-primary-600" />
                    Pago en tienda o contra entrega
                </div>
                <div class="flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-xs text-slate-600 shadow-card">
                    <Icon name="check" class="h-4 w-4 shrink-0 text-primary-600" />
                    Confirmación por teléfono
                </div>
                <div class="flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-xs text-slate-600 shadow-card">
                    <Icon name="image" class="h-4 w-4 shrink-0 text-primary-600" />
                    Repuestos con compatibilidad verificada
                </div>
                <div class="flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-xs text-slate-600 shadow-card">
                    <Icon name="search" class="h-4 w-4 shrink-0 text-primary-600" />
                    Atención personalizada
                </div>
            </div>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-4">
                <!-- Sidebar filters -->
                <aside class="space-y-6 lg:col-span-1">
                    <div class="relative">
                        <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-slate-400">
                            <Icon name="search" class="h-4 w-4" />
                        </span>
                        <input
                            v-model="search"
                            type="text"
                            placeholder="Buscar producto"
                            class="w-full rounded-lg border-slate-300 py-2 pl-9 text-sm shadow-sm placeholder:text-slate-400 focus:border-accent-500 focus:ring-accent-500/40"
                        />
                    </div>

                    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-card">
                        <h2 class="text-xs font-semibold uppercase tracking-wide text-slate-500">Categorías</h2>
                        <ul class="mt-3 space-y-1">
                            <li v-for="category in categories" :key="category">
                                <button
                                    type="button"
                                    @click="selectedCategory = category"
                                    class="w-full cursor-pointer rounded-lg px-2 py-1.5 text-left text-sm transition"
                                    :class="
                                        selectedCategory === category
                                            ? 'bg-accent-100 font-semibold text-accent-800'
                                            : 'text-slate-600 hover:bg-slate-50 hover:text-accent-700'
                                    "
                                >
                                    {{ category }}
                                </button>
                            </li>
                        </ul>
                    </div>

                    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-card">
                        <h2 class="text-xs font-semibold uppercase tracking-wide text-slate-500">Precio</h2>
                        <p class="mt-1 text-xs text-slate-400">
                            Q {{ priceBounds.min.toFixed(2) }} — Q {{ priceBounds.max.toFixed(2) }}
                        </p>
                        <div class="mt-3 flex items-center gap-2">
                            <input
                                v-model="minPrice"
                                type="number"
                                min="0"
                                placeholder="Mín"
                                class="w-full rounded-lg border-slate-300 py-1.5 text-sm shadow-sm focus:border-accent-500 focus:ring-accent-500/40"
                            />
                            <span class="text-slate-400">–</span>
                            <input
                                v-model="maxPrice"
                                type="number"
                                min="0"
                                placeholder="Máx"
                                class="w-full rounded-lg border-slate-300 py-1.5 text-sm shadow-sm focus:border-accent-500 focus:ring-accent-500/40"
                            />
                        </div>
                    </div>

                    <button
                        type="button"
                        @click="resetFilters"
                        class="cursor-pointer text-xs font-medium text-slate-500 hover:text-accent-700"
                    >
                        Limpiar filtros
                    </button>
                </aside>

                <!-- Products -->
                <div class="lg:col-span-3">
                    <div class="mb-4 flex items-center justify-between gap-3">
                        <p class="text-sm text-slate-500">{{ filteredProducts.length }} producto(s)</p>
                        <select
                            v-model="sortBy"
                            class="rounded-lg border-slate-300 py-1.5 text-sm shadow-sm focus:border-accent-500 focus:ring-accent-500/40"
                        >
                            <option v-for="option in sortOptions" :key="option.value" :value="option.value">
                                {{ option.label }}
                            </option>
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-4 sm:grid-cols-3">
                        <div
                            v-for="product in filteredProducts"
                            :key="product.id"
                            class="flex flex-col overflow-hidden rounded-xl border border-slate-200 bg-white shadow-card"
                            :class="{ 'opacity-60': !product.in_stock }"
                        >
                            <Link
                                :href="route('storefront.show', product.id)"
                                target="_blank"
                                class="relative flex aspect-square items-center justify-center bg-slate-50"
                            >
                                <div class="absolute left-2 top-2 flex flex-col gap-1">
                                    <span
                                        v-if="!product.in_stock"
                                        class="rounded-full bg-slate-700 px-2 py-0.5 text-[11px] font-bold text-white"
                                    >
                                        Agotado
                                    </span>
                                    <span
                                        v-if="discountPercent(product)"
                                        class="rounded-full bg-red-600 px-2 py-0.5 text-[11px] font-bold text-white"
                                    >
                                        -{{ discountPercent(product) }}%
                                    </span>
                                    <span
                                        v-if="product.is_new"
                                        class="rounded-full bg-accent-700 px-2 py-0.5 text-[11px] font-bold text-white"
                                    >
                                        Nuevo
                                    </span>
                                </div>
                                <img
                                    v-if="product.images?.[0]?.url"
                                    :src="product.images[0].url"
                                    :alt="product.name"
                                    class="h-full w-full object-cover"
                                    :class="{ 'grayscale': !product.in_stock }"
                                />
                                <Icon v-else name="image" class="h-10 w-10 text-slate-300" />
                            </Link>
                            <div class="flex flex-1 flex-col p-3">
                                <div class="flex items-center gap-1.5 text-xs">
                                    <span v-if="product.brand" class="font-semibold uppercase tracking-wide text-slate-500">
                                        {{ product.brand }}
                                    </span>
                                    <span v-if="product.brand && product.category" class="text-slate-300">·</span>
                                    <span v-if="product.category" class="font-medium text-primary-600">{{ product.category }}</span>
                                </div>
                                <Link
                                    :href="route('storefront.show', product.id)"
                                    target="_blank"
                                    class="mt-0.5 text-sm font-semibold text-slate-900 hover:text-primary-700"
                                >
                                    {{ product.name }}
                                </Link>
                                <div class="mt-1 flex flex-1 items-baseline gap-1.5">
                                    <p class="text-xs text-slate-500">Q {{ product.sale_price.toFixed(2) }} / {{ product.unit }}</p>
                                    <p v-if="product.compare_at_price" class="text-[11px] text-slate-400 line-through">
                                        Q {{ product.compare_at_price.toFixed(2) }}
                                    </p>
                                </div>
                                <p v-if="product.compatibilities?.length > 0" class="mt-1 text-[11px] text-slate-400">
                                    Compatible con {{ product.compatibilities.length }} vehículo(s)
                                </p>
                                <button
                                    type="button"
                                    :disabled="!product.in_stock"
                                    @click="handleAddToCart(product)"
                                    class="mt-3 inline-flex cursor-pointer items-center justify-center gap-1.5 rounded-lg px-3 py-2 text-xs font-semibold text-white shadow-sm transition disabled:cursor-not-allowed disabled:bg-slate-300"
                                    :class="product.in_stock ? (recentlyAdded.has(product.id) ? 'bg-accent-600' : 'bg-primary-600 hover:bg-primary-700') : ''"
                                >
                                    <Icon v-if="product.in_stock" :name="recentlyAdded.has(product.id) ? 'check' : 'cart'" class="h-3.5 w-3.5" />
                                    {{ !product.in_stock ? 'Agotado' : recentlyAdded.has(product.id) ? 'Agregado' : 'Agregar' }}
                                </button>
                            </div>
                        </div>

                        <p v-if="filteredProducts.length === 0" class="col-span-full py-16 text-center text-sm text-slate-500">
                            No se encontraron productos.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </StorefrontLayout>
</template>
