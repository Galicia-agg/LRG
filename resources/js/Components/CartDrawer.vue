<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import Icon from '@/Components/Icon.vue';

const props = defineProps({
    open: {
        type: Boolean,
        default: false,
    },
    cart: {
        type: Array,
        required: true,
    },
    cartTotal: {
        type: Number,
        required: true,
    },
});

const emit = defineEmits(['close', 'change-quantity', 'remove', 'order-submitted']);

const showCheckout = ref(false);

const form = useForm({
    customer_name: '',
    customer_phone: '',
    customer_email: '',
    customer_address: '',
    notes: '',
    items: [],
});

function submitOrder() {
    form.items = props.cart.map((line) => ({ product_id: line.product_id, quantity: line.quantity }));

    form.post(route('storefront.store'), {
        preserveScroll: true,
        onSuccess: () => {
            showCheckout.value = false;
            emit('order-submitted');
            emit('close');
            form.reset();
        },
    });
}
</script>

<template>
    <div v-show="open" class="fixed inset-0 z-40 bg-slate-900/50" @click="emit('close')" />

    <aside
        class="fixed inset-y-0 right-0 z-50 flex w-full max-w-sm transform flex-col bg-white shadow-xl transition-transform duration-200"
        :class="open ? 'translate-x-0' : 'translate-x-full'"
    >
        <div class="flex items-center justify-between border-b border-slate-200 px-4 py-4">
            <h2 class="text-sm font-semibold text-slate-900">
                {{ showCheckout ? 'Completa tu pedido' : 'Tu carrito' }}
            </h2>
            <button type="button" @click="emit('close')" class="text-slate-400 hover:text-slate-600">
                <Icon name="close" class="h-5 w-5" />
            </button>
        </div>

        <!-- Cart view -->
        <template v-if="!showCheckout">
            <div class="flex-1 space-y-3 overflow-y-auto px-4 py-4">
                <div v-for="(line, index) in cart" :key="line.product_id" class="flex items-center gap-3 text-sm">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center overflow-hidden rounded-md border border-slate-200 bg-slate-50">
                        <img v-if="line.image_url" :src="line.image_url" alt="" class="h-full w-full object-cover" />
                        <Icon v-else name="image" class="h-5 w-5 text-slate-300" />
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="truncate font-medium text-slate-800">{{ line.name }}</p>
                        <p class="text-xs text-slate-500">Q {{ line.unit_price.toFixed(2) }} c/u</p>
                    </div>
                    <div class="flex items-center gap-1">
                        <button type="button" @click="emit('change-quantity', line, -1)" class="h-6 w-6 rounded border border-slate-200 text-slate-500 hover:bg-slate-50">
                            −
                        </button>
                        <span class="w-6 text-center text-sm">{{ line.quantity }}</span>
                        <button type="button" @click="emit('change-quantity', line, 1)" class="h-6 w-6 rounded border border-slate-200 text-slate-500 hover:bg-slate-50">
                            +
                        </button>
                    </div>
                    <button type="button" @click="emit('remove', index)" class="text-slate-400 hover:text-red-600">
                        <Icon name="close" class="h-4 w-4" />
                    </button>
                </div>

                <p v-if="cart.length === 0" class="py-10 text-center text-sm text-slate-500">
                    Tu carrito está vacío.
                </p>
            </div>

            <div v-if="cart.length > 0" class="border-t border-slate-200 px-4 py-4">
                <div class="mb-3 flex justify-between text-sm font-semibold text-slate-900">
                    <span>Total</span>
                    <span>Q {{ cartTotal.toFixed(2) }}</span>
                </div>
                <button
                    type="button"
                    @click="showCheckout = true"
                    class="w-full rounded-lg bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-700"
                >
                    Continuar pedido
                </button>
            </div>
        </template>

        <!-- Checkout view -->
        <template v-else>
            <form @submit.prevent="submitOrder" class="flex flex-1 flex-col overflow-y-auto px-4 py-4">
                <div class="space-y-3">
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
                    <div>
                        <label class="mb-1 block text-xs font-medium text-slate-600">Dirección de entrega (opcional)</label>
                        <input
                            v-model="form.customer_address"
                            type="text"
                            class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500/40"
                        />
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-slate-600">Notas (opcional)</label>
                        <textarea
                            v-model="form.notes"
                            rows="2"
                            class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500/40"
                        ></textarea>
                    </div>
                </div>

                <p v-if="form.errors.items" class="mt-3 text-xs text-red-600">{{ form.errors.items }}</p>

                <div class="mt-4 flex justify-between border-t border-slate-100 pt-3 text-sm font-semibold text-slate-900">
                    <span>Total</span>
                    <span>Q {{ cartTotal.toFixed(2) }}</span>
                </div>

                <div class="mt-4 space-y-2">
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="w-full rounded-lg bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-700 disabled:opacity-50"
                    >
                        Enviar pedido
                    </button>
                    <button
                        type="button"
                        @click="showCheckout = false"
                        class="w-full text-center text-xs font-medium text-slate-500 hover:text-slate-700"
                    >
                        Volver al carrito
                    </button>
                </div>
            </form>
        </template>
    </aside>
</template>
