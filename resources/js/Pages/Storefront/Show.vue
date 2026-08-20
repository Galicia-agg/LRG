<script setup>
import { ref } from 'vue';
import StorefrontLayout from '@/Layouts/StorefrontLayout.vue';
import CartDrawer from '@/Components/CartDrawer.vue';
import Icon from '@/Components/Icon.vue';
import { Head, Link } from '@inertiajs/vue3';
import { useCart } from '@/Composables/useCart';

const props = defineProps({
    product: Object,
    breadcrumb: Array,
});

const cartOpen = ref(false);
const activeImageIndex = ref(0);

const { cart, addToCart, changeQuantity, removeFromCart, clearCart, cartCount, cartTotal } = useCart();

function handleAddToCart() {
    addToCart(props.product);
    cartOpen.value = true;
}
</script>

<template>
    <Head :title="product.name" />

    <StorefrontLayout :cart-count="cartCount" @toggle-cart="cartOpen = !cartOpen">
        <div class="mx-auto max-w-5xl px-4 py-6 sm:px-6">
            <!-- Breadcrumb -->
            <nav class="mb-6 flex flex-wrap items-center gap-1 text-xs text-slate-500">
                <Link :href="route('storefront.index')" class="hover:text-primary-700">Tienda</Link>
                <template v-for="crumb in breadcrumb" :key="crumb.id">
                    <Icon name="chevronDown" class="h-3 w-3 -rotate-90 text-slate-300" />
                    <span>{{ crumb.name }}</span>
                </template>
                <Icon name="chevronDown" class="h-3 w-3 -rotate-90 text-slate-300" />
                <span class="truncate text-slate-700">{{ product.name }}</span>
            </nav>

            <div class="grid grid-cols-1 gap-8 sm:grid-cols-2">
                <!-- Gallery -->
                <div>
                    <div class="flex aspect-square items-center justify-center overflow-hidden rounded-xl border border-slate-200 bg-slate-50">
                        <img
                            v-if="product.images?.[activeImageIndex]?.url"
                            :src="product.images[activeImageIndex].url"
                            :alt="product.name"
                            class="h-full w-full object-cover"
                        />
                        <Icon v-else name="image" class="h-16 w-16 text-slate-300" />
                    </div>
                    <div v-if="product.images?.length > 1" class="mt-3 flex gap-2 overflow-x-auto">
                        <button
                            v-for="(image, index) in product.images"
                            :key="index"
                            type="button"
                            @click="activeImageIndex = index"
                            class="h-16 w-16 shrink-0 overflow-hidden rounded-lg border-2"
                            :class="activeImageIndex === index ? 'border-primary-600' : 'border-transparent'"
                        >
                            <img :src="image.url" alt="" class="h-full w-full object-cover" />
                        </button>
                    </div>
                </div>

                <!-- Info -->
                <div>
                    <p v-if="product.brand" class="text-xs font-semibold uppercase tracking-wide text-primary-600">
                        {{ product.brand }}
                    </p>
                    <h1 class="mt-1 text-2xl font-bold text-slate-900">{{ product.name }}</h1>

                    <div class="mt-3 flex items-center gap-3">
                        <span
                            class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-medium"
                            :class="product.in_stock ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500'"
                        >
                            <span
                                class="h-1.5 w-1.5 rounded-full"
                                :class="product.in_stock ? 'bg-emerald-500' : 'bg-slate-400'"
                            />
                            {{ product.in_stock ? 'En existencia' : 'Agotado' }}
                        </span>
                        <span v-if="product.sku" class="text-xs text-slate-400">SKU: {{ product.sku }}</span>
                    </div>

                    <p class="mt-4 text-3xl font-bold text-slate-900">
                        Q {{ product.sale_price.toFixed(2) }}
                        <span class="text-base font-normal text-slate-500">/ {{ product.unit }}</span>
                    </p>

                    <button
                        type="button"
                        :disabled="!product.in_stock"
                        @click="handleAddToCart"
                        class="mt-6 inline-flex w-full items-center justify-center gap-2 rounded-lg bg-primary-600 px-4 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-700 disabled:cursor-not-allowed disabled:opacity-50 sm:w-auto"
                    >
                        <Icon name="cart" class="h-4 w-4" />
                        {{ product.in_stock ? 'Agregar al carrito' : 'Producto agotado' }}
                    </button>

                    <!-- Compatibility -->
                    <div v-if="product.compatibilities?.length > 0" class="mt-6 rounded-lg border border-slate-200 p-4">
                        <h2 class="text-xs font-semibold uppercase tracking-wide text-slate-500">Compatible con</h2>
                        <ul class="mt-2 space-y-1.5 text-sm text-slate-700">
                            <li
                                v-for="(vehicle, index) in product.compatibilities"
                                :key="index"
                                class="flex items-center gap-1.5"
                            >
                                <Icon name="check" class="h-3.5 w-3.5 shrink-0 text-emerald-600" />
                                {{ vehicle.brand }} {{ vehicle.model }}
                                <span v-if="vehicle.year_from || vehicle.year_to" class="text-slate-500">
                                    ({{ vehicle.year_from ?? '—' }}–{{ vehicle.year_to ?? '—' }})
                                </span>
                                <span v-if="vehicle.engine" class="text-slate-500">· {{ vehicle.engine }}</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Description -->
            <div v-if="product.description" class="mt-10 border-t border-slate-200 pt-6">
                <h2 class="text-sm font-semibold text-slate-900">Descripción</h2>
                <p class="mt-2 whitespace-pre-line text-sm text-slate-600">{{ product.description }}</p>
            </div>

            <!-- Specifications -->
            <div v-if="product.specifications?.length > 0" class="mt-10 border-t border-slate-200 pt-6">
                <h2 class="text-sm font-semibold text-slate-900">Especificaciones</h2>
                <dl class="mt-3 divide-y divide-slate-100 overflow-hidden rounded-lg border border-slate-200">
                    <div
                        v-for="spec in product.specifications"
                        :key="spec.label"
                        class="grid grid-cols-2 gap-4 px-4 py-2.5 text-sm odd:bg-slate-50"
                    >
                        <dt class="text-slate-500">{{ spec.label }}</dt>
                        <dd class="text-slate-800">{{ spec.value }}</dd>
                    </div>
                </dl>
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
