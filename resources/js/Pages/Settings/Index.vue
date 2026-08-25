<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Card from '@/Components/Card.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    deliveryEnabled: Boolean,
});

const form = useForm({
    delivery_enabled: props.deliveryEnabled,
});

function toggleDelivery() {
    form.delivery_enabled = !form.delivery_enabled;
    form.post(route('settings.update'), { preserveScroll: true });
}
</script>

<template>
    <Head title="Configuración" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-lg font-semibold leading-tight text-slate-900">Configuración</h2>
        </template>

        <div class="mx-auto max-w-2xl px-4 py-8 sm:px-6 lg:px-8">
            <Card padded>
                <h3 class="text-sm font-semibold text-slate-900">Tienda en línea</h3>
                <div class="mt-4 flex items-start justify-between gap-4">
                    <div>
                        <p class="text-sm font-medium text-slate-800">Envío a domicilio</p>
                        <p class="mt-1 text-xs text-slate-500">
                            Si lo desactivas (por ejemplo, por falta de repartidor), la opción "A domicilio" desaparece
                            del carrito de la tienda y los clientes solo podrán elegir "Recoger en tienda".
                        </p>
                    </div>
                    <button
                        type="button"
                        role="switch"
                        :aria-checked="form.delivery_enabled"
                        :disabled="form.processing"
                        @click="toggleDelivery"
                        class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer items-center rounded-full transition disabled:cursor-not-allowed disabled:opacity-60"
                        :class="form.delivery_enabled ? 'bg-accent-600' : 'bg-slate-300'"
                    >
                        <span
                            class="inline-block h-4 w-4 transform rounded-full bg-white shadow transition"
                            :class="form.delivery_enabled ? 'translate-x-6' : 'translate-x-1'"
                        />
                    </button>
                </div>
                <p class="mt-3 text-xs font-medium" :class="form.delivery_enabled ? 'text-accent-700' : 'text-slate-500'">
                    {{ form.delivery_enabled ? 'Activo: los clientes pueden pedir a domicilio.' : 'Desactivado: solo se puede recoger en tienda.' }}
                </p>
            </Card>
        </div>
    </AuthenticatedLayout>
</template>
