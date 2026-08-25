<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import Card from '@/Components/Card.vue';
import Icon from '@/Components/Icon.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const props = defineProps({
    customers: Array,
    mechanics: Array,
    commonFailures: Array,
    commonServices: Array,
});

const page = usePage();

const customerQuery = ref('');
const showCustomerDropdown = ref(false);
const selectedCustomerId = ref(null);

const selectedCustomer = computed(
    () => props.customers.find((c) => c.id === selectedCustomerId.value) ?? null,
);

const filteredCustomers = computed(() => {
    const term = customerQuery.value.trim().toLowerCase();
    const matches = !term
        ? props.customers
        : props.customers.filter(
              (c) => c.name.toLowerCase().includes(term) || (c.nit ?? '').toLowerCase().includes(term),
          );
    return matches.slice(0, 8);
});

function selectCustomer(customer) {
    selectedCustomerId.value = customer.id;
    customerQuery.value = '';
    showCustomerDropdown.value = false;
    form.customer_vehicle_id = null;
}

function clearCustomer() {
    selectedCustomerId.value = null;
    form.customer_vehicle_id = null;
}

const form = useForm({
    customer_vehicle_id: null,
    mechanic_id: '',
    type: 'reparacion',
    service_scope: 'menor',
    mileage_in: '',
    reported_issue: '',
    estimated_delivery_date: '',
    notes: '',
    failure_ids: [],
    service_ids: [],
});

const selectedFailureIds = ref(new Set());

function toggleFailure(failure) {
    const next = new Set(selectedFailureIds.value);
    if (next.has(failure.id)) {
        next.delete(failure.id);
    } else {
        next.add(failure.id);
    }
    selectedFailureIds.value = next;
    form.failure_ids = [...next];
}

const selectedServiceIds = ref(new Set());

function toggleService(service) {
    const next = new Set(selectedServiceIds.value);
    if (next.has(service.id)) {
        next.delete(service.id);
    } else {
        next.add(service.id);
    }
    selectedServiceIds.value = next;
    form.service_ids = [...next];
}

// --- Crear cliente rápido ---
const showNewCustomerModal = ref(false);
const newCustomerForm = useForm({ name: '', phone: '', nit: '', email: '' });

function submitNewCustomer() {
    newCustomerForm.post(route('customers.store'), {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
            const createdId = page.props.flash?.customerId;
            if (createdId) {
                selectedCustomerId.value = createdId;
            }
            showNewCustomerModal.value = false;
            newCustomerForm.reset();
        },
    });
}

// --- Crear vehículo rápido ---
const showNewVehicleModal = ref(false);
const newVehicleForm = useForm({ customer_id: null, brand: '', model: '', year: '', plate: '', color: '', mileage: '' });

function openNewVehicleModal() {
    newVehicleForm.customer_id = selectedCustomerId.value;
    showNewVehicleModal.value = true;
}

function submitNewVehicle() {
    newVehicleForm.post(route('vehicles.store'), {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
            const createdId = page.props.flash?.vehicleId;
            if (createdId) {
                form.customer_vehicle_id = createdId;
            }
            showNewVehicleModal.value = false;
            newVehicleForm.reset();
        },
    });
}

// --- Crear mecánico rápido ---
const showNewMechanicModal = ref(false);
const newMechanicForm = useForm({ name: '', phone: '' });

function submitNewMechanic() {
    newMechanicForm.post(route('mechanics.store'), {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
            const createdId = page.props.flash?.mechanicId;
            if (createdId) {
                form.mechanic_id = createdId;
            }
            showNewMechanicModal.value = false;
            newMechanicForm.reset();
        },
    });
}

watch(selectedCustomer, (customer) => {
    if (customer?.vehicles?.length === 1) {
        form.customer_vehicle_id = customer.vehicles[0].id;
    }
});

function submitOrder() {
    form.post(route('workshop.store'));
}
</script>

<template>
    <Head title="Nueva orden de servicio" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-lg font-semibold leading-tight text-slate-900">Nueva orden de servicio</h2>
        </template>

        <div class="mx-auto max-w-2xl px-4 py-8 sm:px-6 lg:px-8">
            <Card padded>
                <div class="space-y-5">
                    <!-- Cliente -->
                    <div>
                        <InputLabel value="Cliente" />
                        <div
                            v-if="selectedCustomer"
                            class="mt-1 flex items-center justify-between rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm"
                        >
                            <span class="font-medium text-slate-800">{{ selectedCustomer.name }}</span>
                            <button type="button" @click="clearCustomer" class="cursor-pointer text-slate-400 hover:text-red-600">
                                <Icon name="close" class="h-4 w-4" />
                            </button>
                        </div>
                        <div v-else class="relative mt-1">
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

                    <!-- Vehículo -->
                    <div v-if="selectedCustomer">
                        <InputLabel value="Vehículo" />
                        <select
                            v-model="form.customer_vehicle_id"
                            class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500/40"
                        >
                            <option :value="null" disabled>Selecciona un vehículo</option>
                            <option v-for="vehicle in selectedCustomer.vehicles" :key="vehicle.id" :value="vehicle.id">
                                {{ vehicle.label }}{{ vehicle.plate ? ` — ${vehicle.plate}` : '' }}
                            </option>
                        </select>
                        <button
                            type="button"
                            @click="openNewVehicleModal"
                            class="mt-1.5 inline-flex cursor-pointer items-center gap-1 text-xs font-medium text-primary-600 hover:text-primary-800"
                        >
                            <Icon name="plus" class="h-3.5 w-3.5" />
                            Agregar vehículo nuevo
                        </button>
                        <InputError :message="form.errors.customer_vehicle_id" class="mt-1" />
                    </div>

                    <div>
                        <InputLabel value="Tipo de orden" />
                        <div class="mt-1 grid grid-cols-2 gap-2">
                            <button
                                type="button"
                                @click="form.type = 'reparacion'"
                                class="cursor-pointer rounded-lg border px-3 py-2 text-sm font-medium transition"
                                :class="
                                    form.type === 'reparacion'
                                        ? 'border-primary-600 bg-primary-600 text-white'
                                        : 'border-slate-200 text-slate-600 hover:border-primary-400 hover:bg-primary-50'
                                "
                            >
                                Reparación
                            </button>
                            <button
                                type="button"
                                @click="form.type = 'servicio'"
                                class="cursor-pointer rounded-lg border px-3 py-2 text-sm font-medium transition"
                                :class="
                                    form.type === 'servicio'
                                        ? 'border-accent-600 bg-accent-600 text-white'
                                        : 'border-slate-200 text-slate-600 hover:border-accent-400 hover:bg-accent-50'
                                "
                            >
                                Servicio / Mantenimiento
                            </button>
                        </div>
                        <p class="mt-1 text-xs text-slate-500">
                            El servicio es mantenimiento preventivo (cambio de aceite, revisión periódica). La reparación es por una falla reportada.
                        </p>
                        <InputError :message="form.errors.type" class="mt-1" />
                    </div>

                    <div v-if="form.type === 'servicio'">
                        <InputLabel value="Alcance del servicio" />
                        <div class="mt-1 grid grid-cols-2 gap-2">
                            <button
                                type="button"
                                @click="form.service_scope = 'menor'"
                                class="cursor-pointer rounded-lg border px-3 py-2 text-sm font-medium transition"
                                :class="
                                    form.service_scope === 'menor'
                                        ? 'border-accent-600 bg-accent-600 text-white'
                                        : 'border-slate-200 text-slate-600 hover:border-accent-400 hover:bg-accent-50'
                                "
                            >
                                Menor
                            </button>
                            <button
                                type="button"
                                @click="form.service_scope = 'mayor'"
                                class="cursor-pointer rounded-lg border px-3 py-2 text-sm font-medium transition"
                                :class="
                                    form.service_scope === 'mayor'
                                        ? 'border-accent-600 bg-accent-600 text-white'
                                        : 'border-slate-200 text-slate-600 hover:border-accent-400 hover:bg-accent-50'
                                "
                            >
                                Mayor
                            </button>
                        </div>
                        <InputError :message="form.errors.service_scope" class="mt-1" />
                    </div>

                    <div>
                        <InputLabel for="mechanic_id" value="Mecánico asignado (opcional)" />
                        <select
                            id="mechanic_id"
                            v-model="form.mechanic_id"
                            class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500/40"
                        >
                            <option value="">Sin asignar</option>
                            <option v-for="mechanic in mechanics" :key="mechanic.id" :value="mechanic.id">
                                {{ mechanic.name }}
                            </option>
                        </select>
                        <button
                            type="button"
                            @click="showNewMechanicModal = true"
                            class="mt-1.5 inline-flex cursor-pointer items-center gap-1 text-xs font-medium text-primary-600 hover:text-primary-800"
                        >
                            <Icon name="userPlus" class="h-3.5 w-3.5" />
                            Registrar mecánico nuevo
                        </button>
                    </div>

                    <div>
                        <InputLabel for="mileage_in" value="Kilometraje de ingreso (opcional)" />
                        <input
                            id="mileage_in"
                            v-model="form.mileage_in"
                            type="number"
                            min="0"
                            class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500/40"
                        />
                    </div>

                    <div v-if="form.type !== 'servicio' && commonFailures?.length > 0">
                        <InputLabel value="Problemas reportados (selecciona los que apliquen)" />
                        <div class="mt-1 flex flex-wrap gap-1.5">
                            <button
                                v-for="failure in commonFailures"
                                :key="failure.id"
                                type="button"
                                @click="toggleFailure(failure)"
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

                    <div v-if="form.type === 'servicio' && commonServices?.length > 0">
                        <InputLabel value="Tareas realizadas (selecciona las que apliquen)" />
                        <div class="mt-1 flex flex-wrap gap-1.5">
                            <button
                                v-for="service in commonServices"
                                :key="service.id"
                                type="button"
                                @click="toggleService(service)"
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
                        <InputLabel for="reported_issue" value="Descripción adicional del problema" />
                        <textarea
                            id="reported_issue"
                            v-model="form.reported_issue"
                            rows="3"
                            placeholder="Detalles que el cliente mencionó, síntomas específicos, etc."
                            class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500/40"
                        ></textarea>
                        <InputError :message="form.errors.reported_issue" class="mt-1" />
                    </div>

                    <div>
                        <InputLabel for="estimated_delivery_date" value="Fecha estimada de entrega (opcional)" />
                        <input
                            id="estimated_delivery_date"
                            v-model="form.estimated_delivery_date"
                            type="date"
                            class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500/40"
                        />
                    </div>

                    <div>
                        <InputLabel for="notes" value="Notas internas (opcional)" />
                        <textarea
                            id="notes"
                            v-model="form.notes"
                            rows="2"
                            class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500/40"
                        ></textarea>
                    </div>

                    <PrimaryButton
                        class="w-full cursor-pointer justify-center"
                        :disabled="
                            !form.customer_vehicle_id ||
                            (form.type !== 'servicio' && !form.reported_issue && selectedFailureIds.size === 0) ||
                            form.processing
                        "
                        @click="submitOrder"
                    >
                        Crear orden de servicio
                    </PrimaryButton>
                </div>
            </Card>
        </div>

        <!-- Modal: crear cliente -->
        <div
            v-if="showNewCustomerModal"
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 px-4"
            @click.self="showNewCustomerModal = false"
        >
            <div class="w-full max-w-sm rounded-xl bg-white p-6 shadow-lg">
                <h3 class="text-sm font-semibold text-slate-900">Nuevo cliente</h3>
                <div class="mt-4 space-y-3">
                    <div>
                        <InputLabel for="nc_name" value="Nombre" class="text-xs" />
                        <input id="nc_name" v-model="newCustomerForm.name" type="text" class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500/40" />
                        <InputError :message="newCustomerForm.errors.name" class="mt-1" />
                    </div>
                    <div>
                        <InputLabel for="nc_phone" value="Teléfono (opcional)" class="text-xs" />
                        <input id="nc_phone" v-model="newCustomerForm.phone" type="text" class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500/40" />
                    </div>
                    <div>
                        <InputLabel for="nc_nit" value="NIT (opcional)" class="text-xs" />
                        <input id="nc_nit" v-model="newCustomerForm.nit" type="text" class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500/40" />
                    </div>
                </div>
                <div class="mt-5 flex justify-end gap-2">
                    <button type="button" @click="showNewCustomerModal = false" class="cursor-pointer rounded-lg px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100">Cancelar</button>
                    <PrimaryButton class="cursor-pointer" :disabled="!newCustomerForm.name || newCustomerForm.processing" @click="submitNewCustomer">Crear</PrimaryButton>
                </div>
            </div>
        </div>

        <!-- Modal: crear vehículo -->
        <div
            v-if="showNewVehicleModal"
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 px-4"
            @click.self="showNewVehicleModal = false"
        >
            <div class="w-full max-w-sm rounded-xl bg-white p-6 shadow-lg">
                <h3 class="text-sm font-semibold text-slate-900">Nuevo vehículo</h3>
                <div class="mt-4 space-y-3">
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <InputLabel for="nv_brand" value="Marca" class="text-xs" />
                            <input id="nv_brand" v-model="newVehicleForm.brand" type="text" placeholder="Honda, Toyota..." class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500/40" />
                        </div>
                        <div>
                            <InputLabel for="nv_model" value="Modelo" class="text-xs" />
                            <input id="nv_model" v-model="newVehicleForm.model" type="text" placeholder="CBR150, Corolla..." class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500/40" />
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <InputLabel for="nv_year" value="Año (opcional)" class="text-xs" />
                            <input id="nv_year" v-model="newVehicleForm.year" type="number" class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500/40" />
                        </div>
                        <div>
                            <InputLabel for="nv_plate" value="Placa (opcional)" class="text-xs" />
                            <input id="nv_plate" v-model="newVehicleForm.plate" type="text" class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500/40" />
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <InputLabel for="nv_color" value="Color (opcional)" class="text-xs" />
                            <input id="nv_color" v-model="newVehicleForm.color" type="text" class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500/40" />
                        </div>
                        <div>
                            <InputLabel for="nv_mileage" value="Kilometraje (opcional)" class="text-xs" />
                            <input id="nv_mileage" v-model="newVehicleForm.mileage" type="number" min="0" class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500/40" />
                        </div>
                    </div>
                    <InputError :message="newVehicleForm.errors.brand" class="mt-1" />
                </div>
                <div class="mt-5 flex justify-end gap-2">
                    <button type="button" @click="showNewVehicleModal = false" class="cursor-pointer rounded-lg px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100">Cancelar</button>
                    <PrimaryButton class="cursor-pointer" :disabled="!newVehicleForm.brand || !newVehicleForm.model || newVehicleForm.processing" @click="submitNewVehicle">Agregar</PrimaryButton>
                </div>
            </div>
        </div>

        <!-- Modal: crear mecánico -->
        <div
            v-if="showNewMechanicModal"
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 px-4"
            @click.self="showNewMechanicModal = false"
        >
            <div class="w-full max-w-sm rounded-xl bg-white p-6 shadow-lg">
                <h3 class="text-sm font-semibold text-slate-900">Nuevo mecánico</h3>
                <div class="mt-4 space-y-3">
                    <div>
                        <InputLabel for="nm_name" value="Nombre" class="text-xs" />
                        <input id="nm_name" v-model="newMechanicForm.name" type="text" class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500/40" />
                        <InputError :message="newMechanicForm.errors.name" class="mt-1" />
                    </div>
                    <div>
                        <InputLabel for="nm_phone" value="Teléfono (opcional)" class="text-xs" />
                        <input id="nm_phone" v-model="newMechanicForm.phone" type="text" class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500/40" />
                    </div>
                </div>
                <div class="mt-5 flex justify-end gap-2">
                    <button type="button" @click="showNewMechanicModal = false" class="cursor-pointer rounded-lg px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100">Cancelar</button>
                    <PrimaryButton class="cursor-pointer" :disabled="!newMechanicForm.name || newMechanicForm.processing" @click="submitNewMechanic">Registrar</PrimaryButton>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
