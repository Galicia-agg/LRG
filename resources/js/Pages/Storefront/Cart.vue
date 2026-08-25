<script setup>
import StorefrontLayout from '@/Layouts/StorefrontLayout.vue';
import Card from '@/Components/Card.vue';
import Icon from '@/Components/Icon.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { useCart } from '@/Composables/useCart';

const props = defineProps({
    deliveryEnabled: {
        type: Boolean,
        default: true,
    },
});

const { cart, changeQuantity, removeFromCart, clearCart, cartCount, cartTotal } = useCart();

const form = useForm({
    customer_name: '',
    customer_phone: '',
    customer_email: '',
    delivery_type: props.deliveryEnabled ? 'domicilio' : 'recoger',
    customer_address: '',
    notes: '',
    items: [],
});

function submitOrder() {
    form.items = cart.value.map((line) => ({ product_id: line.product_id, quantity: line.quantity }));

    form.post(route('storefront.store'), {
        preserveScroll: true,
        onSuccess: () => {
            clearCart();
            form.reset();
        },
    });
}
</script>

<template>
    <Head title="Tu carrito" />

    <StorefrontLayout :cart-count="cartCount">
        <div class="mx-auto max-w-6xl px-4 py-8 sm:px-6">
            <div class="mb-6 flex items-center justify-between">
                <h1 class="text-2xl font-bold text-slate-900">Tu carrito</h1>
                <Link :href="route('storefront.index')" class="text-sm font-medium text-primary-600 hover:text-primary-800">
                    ← Seguir comprando
                </Link>
            </div>

            <div v-if="cart.length === 0" class="flex flex-col items-center justify-center py-24 text-center">
                <span class="flex h-16 w-16 items-center justify-center rounded-full bg-primary-50 text-primary-600">
                    <Icon name="cart" class="h-7 w-7" />
                </span>
                <h2 class="mt-4 text-lg font-semibold text-slate-900">Tu carrito está vacío</h2>
                <p class="mt-1 max-w-sm text-sm text-slate-500">
                    Agrega productos desde la tienda para armar tu pedido.
                </p>
                <Link
                    :href="route('storefront.index')"
                    class="mt-6 inline-flex items-center justify-center gap-2 rounded-lg bg-primary-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-700"
                >
                    Ir a la tienda
                </Link>
            </div>

            <div v-else class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <!-- Cart items: full detail -->
                <div class="lg:col-span-2">
                    <Card>
                        <ul class="divide-y divide-slate-100">
                            <li v-for="(line, index) in cart" :key="line.product_id" class="flex gap-4 p-4 sm:p-5">
                                <div class="flex h-20 w-20 shrink-0 items-center justify-center overflow-hidden rounded-lg border border-slate-200 bg-slate-50 sm:h-24 sm:w-24">
                                    <img v-if="line.image_url" :src="line.image_url" alt="" class="h-full w-full object-cover" />
                                    <Icon v-else name="image" class="h-8 w-8 text-slate-300" />
                                </div>

                                <div class="flex flex-1 flex-col justify-between">
                                    <div class="flex items-start justify-between gap-3">
                                        <div>
                                            <h3 class="text-sm font-semibold text-slate-900 sm:text-base">{{ line.name }}</h3>
                                            <p class="mt-0.5 text-xs text-slate-500 sm:text-sm">
                                                Q {{ line.unit_price.toFixed(2) }} / {{ line.unit }}
                                            </p>
                                        </div>
                                        <button
                                            type="button"
                                            @click="removeFromCart(index)"
                                            class="shrink-0 cursor-pointer text-slate-400 hover:text-red-600"
                                        >
                                            <Icon name="trash" class="h-4 w-4" />
                                        </button>
                                    </div>

                                    <div class="mt-3 flex items-center justify-between">
                                        <div class="flex items-center gap-2">
                                            <button
                                                type="button"
                                                @click="changeQuantity(line, -1)"
                                                class="flex h-8 w-8 cursor-pointer items-center justify-center rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50"
                                            >
                                                −
                                            </button>
                                            <span class="w-8 text-center text-sm font-medium">{{ line.quantity }}</span>
                                            <button
                                                type="button"
                                                @click="changeQuantity(line, 1)"
                                                class="flex h-8 w-8 cursor-pointer items-center justify-center rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50"
                                            >
                                                +
                                            </button>
                                        </div>
                                        <p class="text-sm font-semibold text-slate-900 sm:text-base">
                                            Q {{ (line.unit_price * line.quantity).toFixed(2) }}
                                        </p>
                                    </div>
                                </div>
                            </li>
                        </ul>
                    </Card>
                </div>

                <!-- Order summary + checkout -->
                <div class="lg:sticky lg:top-20 lg:h-fit">
                    <Card padded>
                        <h2 class="text-sm font-semibold text-slate-900">Resumen del pedido</h2>
                        <div class="mt-3 space-y-1.5 text-sm">
                            <div class="flex justify-between text-slate-600">
                                <span>{{ cartCount }} artículo(s)</span>
                                <span>Q {{ cartTotal.toFixed(2) }}</span>
                            </div>
                            <div class="flex justify-between border-t border-slate-100 pt-2 text-base font-semibold text-slate-900">
                                <span>Total</span>
                                <span>Q {{ cartTotal.toFixed(2) }}</span>
                            </div>
                        </div>

                        <form @submit.prevent="submitOrder" class="mt-5 space-y-3 border-t border-slate-100 pt-5">
                            <h3 class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                                Completa tu pedido
                            </h3>

                            <div>
                                <label class="mb-1 block text-xs font-medium text-slate-600">Nombre completo</label>
                                <input
                                    v-model="form.customer_name"
                                    type="text"
                                    required
                                    class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500/40"
                                />
                                <p v-if="form.errors.customer_name" class="mt-1 text-xs text-red-600">{{ form.errors.customer_name }}</p>
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-medium text-slate-600">Teléfono</label>
                                <input
                                    v-model="form.customer_phone"
                                    type="tel"
                                    required
                                    class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500/40"
                                />
                                <p v-if="form.errors.customer_phone" class="mt-1 text-xs text-red-600">{{ form.errors.customer_phone }}</p>
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-medium text-slate-600">Email (opcional)</label>
                                <input
                                    v-model="form.customer_email"
                                    type="email"
                                    class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500/40"
                                />
                                <p v-if="form.errors.customer_email" class="mt-1 text-xs text-red-600">{{ form.errors.customer_email }}</p>
                            </div>
                            <div v-if="deliveryEnabled">
                                <label class="mb-1 block text-xs font-medium text-slate-600">¿Cómo lo quieres recibir?</label>
                                <div class="grid grid-cols-2 gap-2">
                                    <label
                                        class="flex cursor-pointer items-center justify-center gap-1.5 rounded-lg border px-3 py-2 text-sm transition"
                                        :class="form.delivery_type === 'domicilio' ? 'border-primary-600 bg-primary-50 text-primary-700' : 'border-slate-300 text-slate-600'"
                                    >
                                        <input v-model="form.delivery_type" type="radio" value="domicilio" class="sr-only" />
                                        A domicilio
                                    </label>
                                    <label
                                        class="flex cursor-pointer items-center justify-center gap-1.5 rounded-lg border px-3 py-2 text-sm transition"
                                        :class="form.delivery_type === 'recoger' ? 'border-primary-600 bg-primary-50 text-primary-700' : 'border-slate-300 text-slate-600'"
                                    >
                                        <input v-model="form.delivery_type" type="radio" value="recoger" class="sr-only" />
                                        Recoger en tienda
                                    </label>
                                </div>
                            </div>
                            <p v-else class="rounded-lg bg-slate-50 px-3 py-2 text-xs text-slate-500">
                                Por el momento solo estamos recibiendo pedidos para recoger en tienda.
                            </p>
                            <div v-if="deliveryEnabled && form.delivery_type === 'domicilio'">
                                <label class="mb-1 block text-xs font-medium text-slate-600">Dirección de entrega</label>
                                <input
                                    v-model="form.customer_address"
                                    type="text"
                                    required
                                    class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500/40"
                                />
                                <p v-if="form.errors.customer_address" class="mt-1 text-xs text-red-600">{{ form.errors.customer_address }}</p>
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-medium text-slate-600">Notas (opcional)</label>
                                <textarea
                                    v-model="form.notes"
                                    rows="2"
                                    class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500/40"
                                ></textarea>
                            </div>

                            <p v-if="form.errors.items" class="text-xs text-red-600">{{ form.errors.items }}</p>

                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="mt-2 w-full cursor-pointer rounded-lg bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-700 disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                Enviar pedido
                            </button>
                            <p class="text-center text-[11px] text-slate-400">
                                Sin pago en línea. Confirmamos por teléfono y pagas en tienda o contra entrega.
                            </p>
                        </form>
                    </Card>
                </div>
            </div>
        </div>
    </StorefrontLayout>
</template>
