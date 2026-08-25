<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Card from '@/Components/Card.vue';
import Badge from '@/Components/Badge.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import Icon from '@/Components/Icon.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    workOrder: Object,
    products: Array,
    commonFailures: Array,
    commonServices: Array,
    cashSessionOpen: Boolean,
});

const statusLabels = {
    recibido: { text: 'Recibida', tone: 'slate' },
    en_proceso: { text: 'En proceso', tone: 'amber' },
    listo: { text: 'Lista', tone: 'primary' },
    entregado: { text: 'Entregada', tone: 'green' },
    cancelado: { text: 'Cancelada', tone: 'red' },
};

const typeLabels = {
    servicio: { text: 'Servicio', tone: 'accent' },
    reparacion: { text: 'Reparación', tone: 'primary' },
};

const isOpen = computed(() => !['entregado', 'cancelado'].includes(props.workOrder.status));

function formatDateTime(iso) {
    return new Date(iso).toLocaleString('es-GT', { dateStyle: 'medium', timeStyle: 'short' });
}

// --- Diagnóstico + estado ---
const statusForm = useForm({
    status: props.workOrder.status === 'recibido' || props.workOrder.status === 'en_proceso' || props.workOrder.status === 'listo'
        ? props.workOrder.status
        : 'recibido',
    diagnosis: props.workOrder.diagnosis ?? '',
    type: props.workOrder.type ?? 'reparacion',
    service_scope: props.workOrder.service_scope ?? 'menor',
});

function saveStatus() {
    statusForm.patch(route('workshop.status', props.workOrder.id), { preserveScroll: true });
}

const selectedFailureIds = computed(() => new Set(props.workOrder.failures.map((f) => f.id)));

function toggleWorkOrderFailure(failure) {
    const wasSelected = selectedFailureIds.value.has(failure.id);

    router.post(
        route('workshop.failures.toggle', props.workOrder.id),
        { common_failure_id: failure.id },
        { preserveScroll: true },
    );

    if (!wasSelected && failure.suggested_price) {
        router.post(
            route('workshop.labor.store', props.workOrder.id),
            { description: failure.description, amount: failure.suggested_price },
            { preserveScroll: true },
        );
    }
}

const selectedServiceIds = computed(() => new Set(props.workOrder.services.map((s) => s.id)));

function toggleWorkOrderService(service) {
    const wasSelected = selectedServiceIds.value.has(service.id);

    router.post(
        route('workshop.services.toggle', props.workOrder.id),
        { common_service_id: service.id },
        { preserveScroll: true },
    );

    if (!wasSelected && service.suggested_price) {
        router.post(
            route('workshop.labor.store', props.workOrder.id),
            { description: service.description, amount: service.suggested_price },
            { preserveScroll: true },
        );
    }
}

function cancelOrder() {
    const reason = prompt('¿Por qué se cancela esta orden? (opcional)');
    if (reason === null) return;
    router.post(route('workshop.cancel', props.workOrder.id), { reason }, { preserveScroll: true });
}

// --- Mano de obra ---
const laborForm = useForm({ description: '', amount: '' });

function addLabor() {
    laborForm.post(route('workshop.labor.store', props.workOrder.id), {
        preserveScroll: true,
        onSuccess: () => laborForm.reset(),
    });
}

function removeLabor(item) {
    router.delete(route('workshop.labor.destroy', [props.workOrder.id, item.id]), { preserveScroll: true });
}

// --- Repuestos ---
const partSearch = ref('');
const showPartPicker = ref(false);
const partQuantities = ref({});

const filteredProducts = computed(() => {
    const term = partSearch.value.trim().toLowerCase();
    if (!term) return props.products.slice(0, 8);
    return props.products.filter(
        (p) => p.name.toLowerCase().includes(term) || p.sku.toLowerCase().includes(term),
    ).slice(0, 8);
});

function addPart(product) {
    const quantity = Math.max(1, Math.round(partQuantities.value[product.id] || 1));

    router.post(
        route('workshop.parts.store', props.workOrder.id),
        { product_id: product.id, quantity },
        {
            preserveScroll: true,
            onSuccess: () => {
                partSearch.value = '';
                showPartPicker.value = false;
            },
        },
    );
}

function removePart(part) {
    router.delete(route('workshop.parts.destroy', [props.workOrder.id, part.id]), { preserveScroll: true });
}

// --- Completar y cobrar ---
const showBillingModal = ref(false);
const billingForm = useForm({ payments: [{ method: 'efectivo', amount: '0.00' }] });

function openBillingModal() {
    billingForm.payments = [{ method: 'efectivo', amount: props.workOrder.total.toFixed(2) }];
    showBillingModal.value = true;
}

const billingTotal = computed(() =>
    billingForm.payments.reduce((sum, p) => sum + (Number(p.amount) || 0), 0),
);
const billingRemaining = computed(() => Math.round((props.workOrder.total - billingTotal.value) * 100) / 100);

function addBillingPayment() {
    billingForm.payments.push({ method: 'efectivo', amount: billingRemaining.value > 0 ? billingRemaining.value.toFixed(2) : '' });
}

function removeBillingPayment(index) {
    if (billingForm.payments.length === 1) return;
    billingForm.payments.splice(index, 1);
}

function submitBilling() {
    billingForm.post(route('workshop.complete', props.workOrder.id), {
        preserveScroll: true,
        onSuccess: () => (showBillingModal.value = false),
    });
}
</script>

<template>
    <Head :title="`Orden #${workOrder.id}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-3">
                <h2 class="text-lg font-semibold leading-tight text-slate-900">Orden de servicio #{{ workOrder.id }}</h2>
                <Badge :tone="statusLabels[workOrder.status]?.tone ?? 'slate'">
                    {{ statusLabels[workOrder.status]?.text ?? workOrder.status }}
                </Badge>
                <Badge :tone="typeLabels[workOrder.type]?.tone ?? 'slate'">
                    {{ typeLabels[workOrder.type]?.text ?? workOrder.type }}
                </Badge>
                <Badge v-if="workOrder.service_scope" tone="slate">
                    {{ workOrder.service_scope === 'mayor' ? 'Mayor' : 'Menor' }}
                </Badge>
            </div>
        </template>

        <div class="mx-auto max-w-5xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between">
                <Link :href="route('workshop.index')" class="text-sm font-medium text-primary-600 hover:text-primary-800">
                    ← Volver a órdenes
                </Link>
                <div class="flex items-center gap-3">
                    <a
                        v-if="workOrder.sale_id"
                        :href="route('sales.receipt', workOrder.sale_id)"
                        target="_blank"
                        class="text-sm font-medium text-primary-600 hover:text-primary-800"
                    >
                        Ver recibo de venta #{{ workOrder.sale_id }}
                    </a>
                    <Link :href="route('workshop.print', workOrder.id)" target="_blank" class="inline-flex items-center gap-1.5 text-sm font-medium text-primary-600 hover:text-primary-800">
                        <Icon name="printer" class="h-4 w-4" />
                        Imprimir orden
                    </Link>
                </div>
            </div>

            <div class="grid gap-6 lg:grid-cols-3">
                <div class="space-y-6 lg:col-span-2">
                    <!-- Cliente y vehículo -->
                    <Card padded>
                        <div class="grid grid-cols-2 gap-4 text-sm">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Cliente</p>
                                <p class="mt-1 font-medium text-slate-900">{{ workOrder.customer.name }}</p>
                                <p v-if="workOrder.customer.phone" class="text-slate-500">{{ workOrder.customer.phone }}</p>
                            </div>
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Vehículo</p>
                                <p class="mt-1 font-medium text-slate-900">{{ workOrder.vehicle.label }}</p>
                                <p v-if="workOrder.vehicle.plate" class="text-slate-500">Placa: {{ workOrder.vehicle.plate }}</p>
                                <Link :href="route('vehicles.show', workOrder.vehicle.id)" class="text-xs font-medium text-primary-600 hover:text-primary-800">
                                    Ver historial del vehículo
                                </Link>
                            </div>
                            <div v-if="workOrder.mileage_in">
                                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Kilometraje de ingreso</p>
                                <p class="mt-1 text-slate-700">{{ workOrder.mileage_in }} km</p>
                            </div>
                            <div v-if="workOrder.estimated_delivery_date">
                                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Entrega estimada</p>
                                <p class="mt-1 text-slate-700">{{ workOrder.estimated_delivery_date }}</p>
                            </div>
                        </div>

                        <div v-if="workOrder.type !== 'servicio'" class="mt-4 border-t border-slate-100 pt-4">
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Problema reportado</p>
                            <p v-if="workOrder.reported_issue" class="mt-1 whitespace-pre-line text-sm text-slate-700">{{ workOrder.reported_issue }}</p>
                            <div v-if="workOrder.failures.length > 0" class="mt-2 flex flex-wrap gap-1.5">
                                <Badge v-for="failure in workOrder.failures" :key="failure.id" tone="primary">
                                    {{ failure.description }}
                                </Badge>
                            </div>
                        </div>

                        <div v-else class="mt-4 border-t border-slate-100 pt-4">
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                                Tareas realizadas
                                <span v-if="workOrder.service_scope"> · Servicio {{ workOrder.service_scope === 'mayor' ? 'mayor' : 'menor' }}</span>
                            </p>
                            <p v-if="workOrder.reported_issue" class="mt-1 whitespace-pre-line text-sm text-slate-700">{{ workOrder.reported_issue }}</p>
                            <div v-if="workOrder.services.length > 0" class="mt-2 flex flex-wrap gap-1.5">
                                <Badge v-for="service in workOrder.services" :key="service.id" tone="accent">
                                    {{ service.description }}
                                </Badge>
                            </div>
                            <p v-else class="mt-1 text-sm text-slate-400">Sin tareas registradas todavía.</p>
                        </div>
                    </Card>

                    <!-- Diagnóstico y estado -->
                    <Card v-if="isOpen" padded>
                        <h3 class="text-sm font-semibold text-slate-900">Diagnóstico y estado</h3>
                        <div class="mt-3 space-y-3">
                            <div v-if="statusForm.type !== 'servicio'">
                                <InputLabel value="Problemas / fallas identificadas" class="text-xs" />
                                <p class="mt-0.5 text-xs text-slate-400">Selecciona las que apliquen — quedan guardadas en la orden y se pueden filtrar después.</p>
                                <div v-if="commonFailures?.length > 0" class="mt-2 flex flex-wrap gap-1.5">
                                    <button
                                        v-for="failure in commonFailures"
                                        :key="failure.id"
                                        type="button"
                                        @click="toggleWorkOrderFailure(failure)"
                                        class="cursor-pointer rounded-full border px-2.5 py-1 text-xs transition"
                                        :class="
                                            selectedFailureIds.has(failure.id)
                                                ? 'border-primary-600 bg-primary-600 text-white'
                                                : 'border-slate-200 text-slate-600 hover:border-primary-400 hover:bg-primary-50 hover:text-primary-700'
                                        "
                                    >
                                        {{ failure.description }}
                                        <span v-if="failure.suggested_price" :class="selectedFailureIds.has(failure.id) ? 'text-white/80' : 'text-primary-500'">
                                            · Q{{ Number(failure.suggested_price).toFixed(0) }}
                                        </span>
                                    </button>
                                </div>
                            </div>

                            <div v-else>
                                <InputLabel value="Tareas realizadas" class="text-xs" />
                                <p class="mt-0.5 text-xs text-slate-400">Selecciona las que apliquen — quedan guardadas en la orden y se pueden filtrar después.</p>
                                <div v-if="commonServices?.length > 0" class="mt-2 flex flex-wrap gap-1.5">
                                    <button
                                        v-for="service in commonServices"
                                        :key="service.id"
                                        type="button"
                                        @click="toggleWorkOrderService(service)"
                                        class="cursor-pointer rounded-full border px-2.5 py-1 text-xs transition"
                                        :class="
                                            selectedServiceIds.has(service.id)
                                                ? 'border-accent-600 bg-accent-600 text-white'
                                                : 'border-slate-200 text-slate-600 hover:border-accent-400 hover:bg-accent-50 hover:text-accent-700'
                                        "
                                    >
                                        {{ service.description }}
                                        <span v-if="service.suggested_price" :class="selectedServiceIds.has(service.id) ? 'text-white/80' : 'text-accent-600'">
                                            · Q{{ Number(service.suggested_price).toFixed(0) }}
                                        </span>
                                    </button>
                                </div>
                            </div>
                            <div>
                                <InputLabel for="diagnosis" value="Notas del diagnóstico (opcional)" class="text-xs" />
                                <textarea
                                    id="diagnosis"
                                    v-model="statusForm.diagnosis"
                                    rows="2"
                                    placeholder="Detalles adicionales que no cubren las fallas seleccionadas"
                                    class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500/40"
                                ></textarea>
                            </div>
                            <div class="flex items-end gap-3">
                                <div class="flex-1">
                                    <InputLabel for="type" value="Tipo de orden" class="text-xs" />
                                    <select
                                        id="type"
                                        v-model="statusForm.type"
                                        class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500/40"
                                    >
                                        <option value="reparacion">Reparación</option>
                                        <option value="servicio">Servicio / Mantenimiento</option>
                                    </select>
                                </div>
                                <div v-if="statusForm.type === 'servicio'" class="flex-1">
                                    <InputLabel for="service_scope" value="Alcance" class="text-xs" />
                                    <select
                                        id="service_scope"
                                        v-model="statusForm.service_scope"
                                        class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500/40"
                                    >
                                        <option value="menor">Menor</option>
                                        <option value="mayor">Mayor</option>
                                    </select>
                                </div>
                                <div class="flex-1">
                                    <InputLabel for="status" value="Estado" class="text-xs" />
                                    <select
                                        id="status"
                                        v-model="statusForm.status"
                                        class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500/40"
                                    >
                                        <option value="recibido">Recibida</option>
                                        <option value="en_proceso">En proceso</option>
                                        <option value="listo">Lista para entrega</option>
                                    </select>
                                </div>
                                <PrimaryButton class="cursor-pointer" :disabled="statusForm.processing" @click="saveStatus">
                                    Guardar
                                </PrimaryButton>
                            </div>
                        </div>
                    </Card>
                    <Card v-else-if="workOrder.diagnosis" padded>
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Diagnóstico</p>
                        <p class="mt-1 whitespace-pre-line text-sm text-slate-700">{{ workOrder.diagnosis }}</p>
                    </Card>

                    <!-- Mano de obra -->
                    <Card padded>
                        <h3 class="text-sm font-semibold text-slate-900">Mano de obra</h3>
                        <ul class="mt-3 divide-y divide-slate-100 text-sm">
                            <li v-for="item in workOrder.laborItems" :key="item.id" class="flex items-center justify-between py-2">
                                <span class="text-slate-700">{{ item.description }}</span>
                                <div class="flex items-center gap-3">
                                    <span class="font-medium text-slate-900">Q {{ item.amount.toFixed(2) }}</span>
                                    <button v-if="isOpen" type="button" @click="removeLabor(item)" class="cursor-pointer text-slate-400 hover:text-red-600">
                                        <Icon name="close" class="h-4 w-4" />
                                    </button>
                                </div>
                            </li>
                            <li v-if="workOrder.laborItems.length === 0" class="py-2 text-sm text-slate-500">
                                Sin mano de obra registrada.
                            </li>
                        </ul>
                        <div v-if="isOpen" class="mt-3 flex items-end gap-2 border-t border-slate-100 pt-3">
                            <div class="flex-1">
                                <InputLabel for="labor_description" value="Descripción" class="text-xs" />
                                <input
                                    id="labor_description"
                                    v-model="laborForm.description"
                                    type="text"
                                    placeholder="Cambio de aceite, revisión de frenos..."
                                    class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500/40"
                                />
                            </div>
                            <div class="w-28">
                                <InputLabel for="labor_amount" value="Monto Q" class="text-xs" />
                                <input
                                    id="labor_amount"
                                    v-model="laborForm.amount"
                                    type="number"
                                    min="0.01"
                                    step="0.01"
                                    class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500/40"
                                />
                            </div>
                            <PrimaryButton class="cursor-pointer" :disabled="!laborForm.description || !laborForm.amount || laborForm.processing" @click="addLabor">
                                Agregar
                            </PrimaryButton>
                        </div>
                        <InputError :message="laborForm.errors.labor" class="mt-2" />
                    </Card>

                    <!-- Repuestos -->
                    <Card padded>
                        <h3 class="text-sm font-semibold text-slate-900">Repuestos utilizados</h3>
                        <ul class="mt-3 divide-y divide-slate-100 text-sm">
                            <li v-for="part in workOrder.parts" :key="part.id" class="flex items-center justify-between py-2">
                                <span class="text-slate-700">{{ part.name }} <span class="text-xs text-slate-400">x{{ part.quantity }}</span></span>
                                <div class="flex items-center gap-3">
                                    <span class="font-medium text-slate-900">Q {{ part.subtotal.toFixed(2) }}</span>
                                    <button v-if="isOpen" type="button" @click="removePart(part)" class="cursor-pointer text-slate-400 hover:text-red-600">
                                        <Icon name="close" class="h-4 w-4" />
                                    </button>
                                </div>
                            </li>
                            <li v-if="workOrder.parts.length === 0" class="py-2 text-sm text-slate-500">
                                Sin repuestos agregados.
                            </li>
                        </ul>

                        <div v-if="isOpen" class="relative mt-3 border-t border-slate-100 pt-3">
                            <input
                                v-model="partSearch"
                                type="text"
                                placeholder="Buscar repuesto por nombre o SKU"
                                class="block w-full rounded-lg border-slate-300 text-sm shadow-sm placeholder:text-slate-400 focus:border-primary-500 focus:ring-primary-500/40"
                                @focus="showPartPicker = true"
                            />
                            <div v-if="showPartPicker" class="mt-2 max-h-56 space-y-1 overflow-y-auto rounded-lg border border-slate-200 p-2">
                                <div
                                    v-for="product in filteredProducts"
                                    :key="product.id"
                                    class="flex items-center justify-between gap-2 rounded-lg px-2 py-1.5 text-sm hover:bg-slate-50"
                                >
                                    <div class="min-w-0 flex-1">
                                        <p class="truncate text-slate-800">{{ product.name }}</p>
                                        <p class="text-xs text-slate-500">Q {{ product.sale_price }} · Stock {{ product.current_stock }}</p>
                                    </div>
                                    <input
                                        type="number"
                                        min="1"
                                        step="1"
                                        v-model.number="partQuantities[product.id]"
                                        placeholder="1"
                                        class="w-16 rounded-lg border-slate-300 text-sm focus:border-primary-500 focus:ring-primary-500/40"
                                    />
                                    <button
                                        type="button"
                                        @click="addPart(product)"
                                        class="cursor-pointer rounded-lg bg-primary-600 px-2 py-1 text-xs font-semibold text-white hover:bg-primary-700"
                                    >
                                        Agregar
                                    </button>
                                </div>
                                <p v-if="filteredProducts.length === 0" class="py-2 text-center text-xs text-slate-500">
                                    No se encontraron productos.
                                </p>
                                <button type="button" @click="showPartPicker = false" class="mt-1 w-full cursor-pointer text-center text-xs text-slate-400 hover:text-slate-600">
                                    Cerrar
                                </button>
                            </div>
                        </div>
                        <InputError :message="$page.props.errors?.part" class="mt-2" />
                    </Card>
                </div>

                <!-- Resumen -->
                <div>
                    <Card padded class="lg:sticky lg:top-6">
                        <h3 class="text-sm font-semibold text-slate-900">Resumen</h3>
                        <div class="mt-3 space-y-1.5 text-sm">
                            <div class="flex justify-between text-slate-600">
                                <span>Mano de obra</span>
                                <span>Q {{ workOrder.labor_total.toFixed(2) }}</span>
                            </div>
                            <div class="flex justify-between text-slate-600">
                                <span>Repuestos</span>
                                <span>Q {{ workOrder.parts_total.toFixed(2) }}</span>
                            </div>
                            <div class="flex justify-between border-t border-slate-100 pt-1.5 text-base font-bold text-slate-900">
                                <span>Total</span>
                                <span>Q {{ workOrder.total.toFixed(2) }}</span>
                            </div>
                        </div>

                        <div v-if="isOpen" class="mt-4 space-y-2 border-t border-slate-100 pt-4">
                            <button
                                type="button"
                                :disabled="workOrder.total <= 0 || !cashSessionOpen"
                                @click="openBillingModal"
                                class="w-full cursor-pointer rounded-lg bg-accent-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-accent-700 disabled:cursor-not-allowed disabled:bg-slate-300"
                            >
                                Completar y cobrar
                            </button>
                            <p v-if="!cashSessionOpen" class="text-center text-xs text-amber-700">
                                Necesitas abrir una caja para poder cobrar.
                                <Link :href="route('cash-sessions.create')" class="underline">Abrir caja</Link>
                            </p>
                            <button
                                type="button"
                                @click="cancelOrder"
                                class="w-full cursor-pointer text-center text-xs font-medium text-red-600 hover:text-red-800"
                            >
                                Cancelar orden
                            </button>
                        </div>
                        <p v-else-if="workOrder.status === 'entregado'" class="mt-4 border-t border-slate-100 pt-4 text-xs text-slate-500">
                            Entregada el {{ formatDateTime(workOrder.delivered_at) }}.
                        </p>
                    </Card>
                </div>
            </div>
        </div>

        <!-- Modal: cobrar -->
        <div
            v-if="showBillingModal"
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 px-4"
            @click.self="showBillingModal = false"
        >
            <div class="w-full max-w-sm rounded-xl bg-white p-6 shadow-lg">
                <h3 class="text-sm font-semibold text-slate-900">Cobrar orden #{{ workOrder.id }}</h3>
                <p class="mt-1 text-2xl font-bold text-slate-900">Q {{ workOrder.total.toFixed(2) }}</p>

                <div class="mt-4 space-y-2">
                    <div v-for="(payment, index) in billingForm.payments" :key="index" class="flex items-center gap-2">
                        <select
                            v-model="payment.method"
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
                            v-model="payment.amount"
                            class="w-24 rounded-lg border-slate-300 text-sm focus:border-primary-500 focus:ring-primary-500/40"
                        />
                        <button
                            v-if="billingForm.payments.length > 1"
                            type="button"
                            @click="removeBillingPayment(index)"
                            class="cursor-pointer text-slate-400 hover:text-red-600"
                        >
                            <Icon name="close" class="h-4 w-4" />
                        </button>
                    </div>
                    <button type="button" @click="addBillingPayment" class="inline-flex cursor-pointer items-center gap-1 text-xs font-medium text-primary-600 hover:text-primary-800">
                        <Icon name="plus" class="h-3.5 w-3.5" />
                        Agregar método de pago
                    </button>
                    <p v-if="Math.abs(billingRemaining) >= 0.01" class="text-xs font-medium" :class="billingRemaining > 0 ? 'text-amber-700' : 'text-red-600'">
                        {{ billingRemaining > 0 ? `Falta cubrir Q ${billingRemaining.toFixed(2)}` : `Excedente Q ${Math.abs(billingRemaining).toFixed(2)}` }}
                    </p>
                    <InputError :message="billingForm.errors.payments" class="mt-1" />
                </div>

                <div class="mt-5 flex justify-end gap-2">
                    <button type="button" @click="showBillingModal = false" class="cursor-pointer rounded-lg px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100">
                        Cancelar
                    </button>
                    <PrimaryButton
                        class="cursor-pointer"
                        :disabled="Math.abs(billingRemaining) >= 0.01 || billingForm.processing"
                        @click="submitBilling"
                    >
                        Confirmar cobro
                    </PrimaryButton>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
