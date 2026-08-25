<script setup>
import { ref } from 'vue';
import StorefrontLayout from '@/Layouts/StorefrontLayout.vue';
import Icon from '@/Components/Icon.vue';
import { Head, Link } from '@inertiajs/vue3';
import { useCart } from '@/Composables/useCart';

const props = defineProps({
    product: Object,
    breadcrumb: Array,
    related: Array,
});

const activeImageIndex = ref(0);
const justAdded = ref(false);

const { addToCart, cartCount } = useCart();

const discountPercent = props.product.compare_at_price
    ? Math.round(((props.product.compare_at_price - props.product.sale_price) / props.product.compare_at_price) * 100)
    : null;

function relatedDiscountPercent(item) {
    if (!item.compare_at_price) return null;
    return Math.round(((item.compare_at_price - item.sale_price) / item.compare_at_price) * 100);
}

function handleAddToCart() {
    addToCart(props.product);
    justAdded.value = true;
    setTimeout(() => (justAdded.value = false), 1500);
}
</script>

<template>
    <Head :title="product.name" />

    <StorefrontLayout :cart-count="cartCount">
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
                    <div class="relative flex aspect-square items-center justify-center overflow-hidden rounded-xl border border-slate-200 bg-slate-50">
                        <div class="absolute left-3 top-3 z-10 flex flex-col gap-1">
                            <span
                                v-if="discountPercent"
                                class="rounded-full bg-red-600 px-2.5 py-1 text-xs font-bold text-white"
                            >
                                -{{ discountPercent }}%
                            </span>
                            <span
                                v-if="product.is_new"
                                class="rounded-full bg-accent-700 px-2.5 py-1 text-xs font-bold text-white"
                            >
                                Nuevo
                            </span>
                        </div>
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
                            class="h-16 w-16 shrink-0 cursor-pointer overflow-hidden rounded-lg border-2"
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
                            :class="product.in_stock ? 'bg-accent-100 text-accent-800' : 'bg-slate-100 text-slate-500'"
                        >
                            <span
                                class="h-1.5 w-1.5 rounded-full"
                                :class="product.in_stock ? 'bg-accent-600' : 'bg-slate-400'"
                            />
                            {{ product.in_stock ? 'En existencia' : 'Agotado' }}
                        </span>
                        <span v-if="product.sku" class="text-xs text-slate-400">SKU: {{ product.sku }}</span>
                    </div>

                    <div class="mt-4 flex flex-wrap items-baseline gap-2">
                        <p class="text-3xl font-bold text-slate-900">
                            Q {{ product.sale_price.toFixed(2) }}
                            <span class="text-base font-normal text-slate-500">/ {{ product.unit }}</span>
                        </p>
                        <p v-if="product.compare_at_price" class="text-lg text-slate-400 line-through">
                            Q {{ product.compare_at_price.toFixed(2) }}
                        </p>
                        <span v-if="discountPercent" class="rounded-full bg-red-50 px-2 py-0.5 text-xs font-semibold text-red-600">
                            Ahorras Q {{ (product.compare_at_price - product.sale_price).toFixed(2) }}
                        </span>
                    </div>

                    <button
                        type="button"
                        :disabled="!product.in_stock"
                        @click="handleAddToCart"
                        class="mt-6 inline-flex w-full cursor-pointer items-center justify-center gap-2 rounded-lg px-4 py-3 text-sm font-semibold text-white shadow-sm transition disabled:cursor-not-allowed disabled:opacity-50 sm:w-auto"
                        :class="justAdded ? 'bg-accent-600' : 'bg-primary-600 hover:bg-primary-700'"
                    >
                        <Icon :name="justAdded ? 'check' : 'cart'" class="h-4 w-4" />
                        {{ !product.in_stock ? 'Producto agotado' : justAdded ? 'Agregado al carrito' : 'Agregar al carrito' }}
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
                                <Icon name="check" class="h-3.5 w-3.5 shrink-0 text-accent-600" />
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

            <!-- Related products -->
            <div v-if="related?.length > 0" class="mt-10 border-t border-slate-200 pt-6">
                <h2 class="text-sm font-semibold text-slate-900">Productos relacionados</h2>
                <div class="mt-3 grid grid-cols-2 gap-4 sm:grid-cols-4">
                    <Link
                        v-for="item in related"
                        :key="item.id"
                        :href="route('storefront.show', item.id)"
                        target="_blank"
                        class="flex flex-col overflow-hidden rounded-xl border border-slate-200 bg-white shadow-card transition hover:border-primary-300"
                    >
                        <div class="relative flex aspect-square items-center justify-center bg-slate-50">
                            <span
                                v-if="relatedDiscountPercent(item)"
                                class="absolute left-2 top-2 rounded-full bg-red-600 px-2 py-0.5 text-[11px] font-bold text-white"
                            >
                                -{{ relatedDiscountPercent(item) }}%
                            </span>
                            <img
                                v-if="item.images?.[0]?.url"
                                :src="item.images[0].url"
                                :alt="item.name"
                                class="h-full w-full object-cover"
                            />
                            <Icon v-else name="image" class="h-8 w-8 text-slate-300" />
                        </div>
                        <div class="flex flex-1 flex-col p-3">
                            <p v-if="item.category" class="text-xs font-medium text-primary-600">{{ item.category }}</p>
                            <p class="mt-0.5 text-sm font-semibold text-slate-900">{{ item.name }}</p>
                            <div class="mt-1 flex flex-1 items-baseline gap-1.5">
                                <p class="text-xs text-slate-500">Q {{ item.sale_price.toFixed(2) }} / {{ item.unit }}</p>
                                <p v-if="item.compare_at_price" class="text-[11px] text-slate-400 line-through">
                                    Q {{ item.compare_at_price.toFixed(2) }}
                                </p>
                            </div>
                        </div>
                    </Link>
                </div>
            </div>
        </div>
    </StorefrontLayout>
</template>
