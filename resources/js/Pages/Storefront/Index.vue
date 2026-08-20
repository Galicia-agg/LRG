<script setup>
import { computed, ref } from 'vue';
import StorefrontLayout from '@/Layouts/StorefrontLayout.vue';
import CartDrawer from '@/Components/CartDrawer.vue';
import Icon from '@/Components/Icon.vue';
import { Head, Link } from '@inertiajs/vue3';
import { useCart } from '@/Composables/useCart';

const props = defineProps({
    products: Array,
});

const search = ref('');
const selectedCategory = ref('Todos');
const cartOpen = ref(false);

const { cart, addToCart, changeQuantity, removeFromCart, clearCart, cartCount, cartTotal } = useCart();

const categories = computed(() => {
    const set = new Set(props.products.map((p) => p.category).filter(Boolean));
    return ['Todos', ...set];
});

const filteredProducts = computed(() => {
    const term = search.value.trim().toLowerCase();

    return props.products.filter((product) => {
        const matchesTerm = !term || product.name.toLowerCase().includes(term);
        const matchesCategory = selectedCategory.value === 'Todos' || product.category === selectedCategory.value;
        return matchesTerm && matchesCategory;
    });
});

function handleAddToCart(product) {
    addToCart(product);
    cartOpen.value = true;
}
</script>

<template>
    <Head title="Tienda" />

    <StorefrontLayout :cart-count="cartCount" @toggle-cart="cartOpen = !cartOpen">
        <div class="mx-auto max-w-6xl space-y-6 px-4 py-8 sm:px-6">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">Nuestros productos</h1>
                <p class="mt-1 text-sm text-slate-500">
                    Arma tu pedido y lo confirmamos por teléfono. El pago se hace en tienda o contra entrega.
                </p>
            </div>

            <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                <div class="relative flex-1 sm:max-w-sm">
                    <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-slate-400">
                        <Icon name="search" class="h-4 w-4" />
                    </span>
                    <input
                        v-model="search"
                        type="text"
                        placeholder="Buscar producto"
                        class="w-full rounded-lg border-slate-300 py-2 pl-9 text-sm shadow-sm placeholder:text-slate-400 focus:border-primary-500 focus:ring-primary-500/40"
                    />
                </div>
                <div class="flex flex-wrap gap-2">
                    <button
                        v-for="category in categories"
                        :key="category"
                        type="button"
                        @click="selectedCategory = category"
                        class="rounded-full border px-3 py-1 text-xs font-medium transition"
                        :class="
                            selectedCategory === category
                                ? 'border-primary-600 bg-primary-600 text-white'
                                : 'border-slate-200 text-slate-600 hover:border-primary-400 hover:text-primary-700'
                        "
                    >
                        {{ category }}
                    </button>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
                <div
                    v-for="product in filteredProducts"
                    :key="product.id"
                    class="flex flex-col overflow-hidden rounded-xl border border-slate-200 bg-white shadow-card"
                >
                    <Link
                        :href="route('storefront.show', product.id)"
                        target="_blank"
                        class="flex aspect-square items-center justify-center bg-slate-50"
                    >
                        <img
                            v-if="product.images?.[0]?.url"
                            :src="product.images[0].url"
                            :alt="product.name"
                            class="h-full w-full object-cover"
                        />
                        <Icon v-else name="image" class="h-10 w-10 text-slate-300" />
                    </Link>
                    <div class="flex flex-1 flex-col p-3">
                        <p v-if="product.category" class="text-xs font-medium text-primary-600">
                            {{ product.category }}
                        </p>
                        <Link
                            :href="route('storefront.show', product.id)"
                            target="_blank"
                            class="mt-0.5 text-sm font-semibold text-slate-900 hover:text-primary-700"
                        >
                            {{ product.name }}
                        </Link>
                        <p class="mt-1 flex-1 text-xs text-slate-500">Q {{ product.sale_price.toFixed(2) }} / {{ product.unit }}</p>
                        <p v-if="product.compatibilities?.length > 0" class="mt-1 text-[11px] text-slate-400">
                            Compatible con {{ product.compatibilities.length }} vehículo(s)
                        </p>
                        <button
                            type="button"
                            @click="handleAddToCart(product)"
                            class="mt-3 inline-flex items-center justify-center gap-1.5 rounded-lg bg-primary-600 px-3 py-2 text-xs font-semibold text-white shadow-sm transition hover:bg-primary-700"
                        >
                            <Icon name="cart" class="h-3.5 w-3.5" />
                            Agregar
                        </button>
                    </div>
                </div>

                <p v-if="filteredProducts.length === 0" class="col-span-full py-16 text-center text-sm text-slate-500">
                    No se encontraron productos.
                </p>
            </div>
        </div>

        <template #cart>
            <CartDrawer
                :open="cartOpen"
                :cart="cart"
                :cart-total="cartTotal"
                @close="cartOpen = false"
                @change-quantity="changeQuantity"
                @remove="removeFromCart"
                @order-submitted="clearCart"
            />
        </template>
    </StorefrontLayout>
</template>
