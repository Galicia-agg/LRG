<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import Card from '@/Components/Card.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    customers: Array,
});

const editingId = ref(null);

const form = useForm({
    name: '',
    nit: '',
    phone: '',
    email: '',
    address: '',
});

function submit() {
    if (editingId.value) {
        form.put(route('customers.update', editingId.value), {
            onSuccess: () => cancelEdit(),
        });
    } else {
        form.post(route('customers.store'), {
            onSuccess: () => form.reset(),
        });
    }
}

function edit(customer) {
    editingId.value = customer.id;
    form.name = customer.name;
    form.nit = customer.nit ?? '';
    form.phone = customer.phone ?? '';
    form.email = customer.email ?? '';
    form.address = customer.address ?? '';
}

function cancelEdit() {
    editingId.value = null;
    form.reset();
}

function destroy(customer) {
    if (confirm(`¿Eliminar al cliente "${customer.name}"?`)) {
        router.delete(route('customers.destroy', customer.id));
    }
}
</script>

<template>
    <Head title="Clientes" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-lg font-semibold leading-tight text-slate-900">Clientes</h2>
        </template>

        <div class="mx-auto max-w-screen-2xl px-4 py-8 sm:px-6 lg:px-8">
            <div class="grid gap-6 md:grid-cols-3">
                <Card class="md:col-span-2">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr class="text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                <th class="px-6 py-3">Nombre</th>
                                <th class="px-6 py-3">NIT</th>
                                <th class="px-6 py-3">Teléfono</th>
                                <th class="px-6 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-sm text-slate-700">
                            <tr v-for="customer in customers" :key="customer.id" class="hover:bg-slate-50">
                                <td class="px-6 py-3 font-medium text-slate-900">{{ customer.name }}</td>
                                <td class="px-6 py-3">{{ customer.nit ?? '—' }}</td>
                                <td class="px-6 py-3">{{ customer.phone ?? '—' }}</td>
                                <td class="px-6 py-3 text-right">
                                    <button
                                        @click="edit(customer)"
                                        class="mr-3 font-medium text-primary-600 hover:text-primary-800"
                                    >
                                        Editar
                                    </button>
                                    <button
                                        @click="destroy(customer)"
                                        class="font-medium text-red-600 hover:text-red-800"
                                    >
                                        Eliminar
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="customers.length === 0">
                                <td colspan="4" class="px-6 py-10 text-center text-slate-500">
                                    No hay clientes.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </Card>

                <Card padded>
                    <form @submit.prevent="submit" class="space-y-4">
                        <h3 class="text-sm font-semibold text-slate-900">
                            {{ editingId ? 'Editar cliente' : 'Nuevo cliente' }}
                        </h3>

                        <div>
                            <InputLabel for="name" value="Nombre" />
                            <TextInput id="name" v-model="form.name" class="mt-1 block w-full" />
                            <InputError :message="form.errors.name" class="mt-2" />
                        </div>

                        <div>
                            <InputLabel for="nit" value="NIT" />
                            <TextInput id="nit" v-model="form.nit" class="mt-1 block w-full" />
                            <InputError :message="form.errors.nit" class="mt-2" />
                        </div>

                        <div>
                            <InputLabel for="phone" value="Teléfono" />
                            <TextInput id="phone" v-model="form.phone" class="mt-1 block w-full" />
                            <InputError :message="form.errors.phone" class="mt-2" />
                        </div>

                        <div>
                            <InputLabel for="email" value="Email" />
                            <TextInput id="email" v-model="form.email" type="email" class="mt-1 block w-full" />
                            <InputError :message="form.errors.email" class="mt-2" />
                        </div>

                        <div>
                            <InputLabel for="address" value="Dirección" />
                            <TextInput id="address" v-model="form.address" class="mt-1 block w-full" />
                            <InputError :message="form.errors.address" class="mt-2" />
                        </div>

                        <div class="flex items-center gap-3">
                            <PrimaryButton :disabled="form.processing">
                                Guardar
                            </PrimaryButton>
                            <button
                                v-if="editingId"
                                type="button"
                                @click="cancelEdit"
                                class="text-sm font-medium text-slate-500 hover:text-slate-700"
                            >
                                Cancelar
                            </button>
                        </div>
                    </form>
                </Card>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
