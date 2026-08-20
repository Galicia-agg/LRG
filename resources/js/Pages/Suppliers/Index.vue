<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import Checkbox from '@/Components/Checkbox.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import Card from '@/Components/Card.vue';
import Badge from '@/Components/Badge.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    suppliers: Array,
});

const editingId = ref(null);

const form = useForm({
    name: '',
    contact_name: '',
    phone: '',
    email: '',
    nit: '',
    active: true,
});

function submit() {
    if (editingId.value) {
        form.put(route('suppliers.update', editingId.value), {
            onSuccess: () => cancelEdit(),
        });
    } else {
        form.post(route('suppliers.store'), {
            onSuccess: () => form.reset(),
        });
    }
}

function edit(supplier) {
    editingId.value = supplier.id;
    form.name = supplier.name;
    form.contact_name = supplier.contact_name ?? '';
    form.phone = supplier.phone ?? '';
    form.email = supplier.email ?? '';
    form.nit = supplier.nit ?? '';
    form.active = supplier.active;
}

function cancelEdit() {
    editingId.value = null;
    form.reset();
}

function destroy(supplier) {
    if (confirm(`¿Eliminar el proveedor "${supplier.name}"?`)) {
        router.delete(route('suppliers.destroy', supplier.id));
    }
}
</script>

<template>
    <Head title="Proveedores" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-lg font-semibold leading-tight text-slate-900">Proveedores</h2>
        </template>

        <div class="mx-auto max-w-5xl px-4 py-8 sm:px-6 lg:px-8">
            <div class="grid gap-6 md:grid-cols-3">
                <Card class="md:col-span-2">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr class="text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                <th class="px-6 py-3">Nombre</th>
                                <th class="px-6 py-3">Contacto</th>
                                <th class="px-6 py-3">Teléfono</th>
                                <th class="px-6 py-3">Estado</th>
                                <th class="px-6 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-sm text-slate-700">
                            <tr v-for="supplier in suppliers" :key="supplier.id" class="hover:bg-slate-50">
                                <td class="px-6 py-3 font-medium text-slate-900">{{ supplier.name }}</td>
                                <td class="px-6 py-3">{{ supplier.contact_name ?? '—' }}</td>
                                <td class="px-6 py-3">{{ supplier.phone ?? '—' }}</td>
                                <td class="px-6 py-3">
                                    <Badge :tone="supplier.active ? 'green' : 'slate'">
                                        {{ supplier.active ? 'Activo' : 'Inactivo' }}
                                    </Badge>
                                </td>
                                <td class="px-6 py-3 text-right">
                                    <button
                                        @click="edit(supplier)"
                                        class="mr-3 font-medium text-primary-600 hover:text-primary-800"
                                    >
                                        Editar
                                    </button>
                                    <button
                                        @click="destroy(supplier)"
                                        class="font-medium text-red-600 hover:text-red-800"
                                    >
                                        Eliminar
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="suppliers.length === 0">
                                <td colspan="5" class="px-6 py-10 text-center text-slate-500">
                                    No hay proveedores.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </Card>

                <Card padded>
                    <form @submit.prevent="submit" class="space-y-4">
                        <h3 class="text-sm font-semibold text-slate-900">
                            {{ editingId ? 'Editar proveedor' : 'Nuevo proveedor' }}
                        </h3>

                        <div>
                            <InputLabel for="name" value="Nombre" />
                            <TextInput id="name" v-model="form.name" class="mt-1 block w-full" />
                            <InputError :message="form.errors.name" class="mt-2" />
                        </div>

                        <div>
                            <InputLabel for="contact_name" value="Persona de contacto" />
                            <TextInput id="contact_name" v-model="form.contact_name" class="mt-1 block w-full" />
                        </div>

                        <div>
                            <InputLabel for="phone" value="Teléfono" />
                            <TextInput id="phone" v-model="form.phone" class="mt-1 block w-full" />
                        </div>

                        <div>
                            <InputLabel for="email" value="Email" />
                            <TextInput id="email" v-model="form.email" type="email" class="mt-1 block w-full" />
                            <InputError :message="form.errors.email" class="mt-2" />
                        </div>

                        <div>
                            <InputLabel for="nit" value="NIT" />
                            <TextInput id="nit" v-model="form.nit" class="mt-1 block w-full" />
                        </div>

                        <div class="flex items-center gap-2">
                            <Checkbox id="active" v-model:checked="form.active" />
                            <InputLabel for="active" value="Activo" />
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
