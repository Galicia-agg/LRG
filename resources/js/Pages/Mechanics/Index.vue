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

defineProps({
    mechanics: Array,
});

const editingId = ref(null);

const form = useForm({
    name: '',
    phone: '',
    active: true,
});

function submit() {
    if (editingId.value) {
        form.put(route('mechanics.update', editingId.value), {
            onSuccess: () => cancelEdit(),
        });
    } else {
        form.post(route('mechanics.store'), {
            onSuccess: () => form.reset(),
        });
    }
}

function edit(mechanic) {
    editingId.value = mechanic.id;
    form.name = mechanic.name;
    form.phone = mechanic.phone ?? '';
    form.active = mechanic.active;
}

function cancelEdit() {
    editingId.value = null;
    form.reset();
}

function deactivate(mechanic) {
    if (confirm(`¿Desactivar al mecánico "${mechanic.name}"?`)) {
        router.delete(route('mechanics.destroy', mechanic.id));
    }
}
</script>

<template>
    <Head title="Mecánicos" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-lg font-semibold leading-tight text-slate-900">Mecánicos</h2>
        </template>

        <div class="mx-auto max-w-screen-2xl px-4 py-8 sm:px-6 lg:px-8">
            <div class="grid gap-6 md:grid-cols-3">
                <Card class="md:col-span-2">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr class="text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                <th class="px-6 py-3">Nombre</th>
                                <th class="px-6 py-3">Teléfono</th>
                                <th class="px-6 py-3">Estado</th>
                                <th class="px-6 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-sm text-slate-700">
                            <tr v-for="mechanic in mechanics" :key="mechanic.id" class="hover:bg-slate-50">
                                <td class="px-6 py-3 font-medium text-slate-900">{{ mechanic.name }}</td>
                                <td class="px-6 py-3">{{ mechanic.phone ?? '—' }}</td>
                                <td class="px-6 py-3">
                                    <Badge :tone="mechanic.active ? 'green' : 'slate'">
                                        {{ mechanic.active ? 'Activo' : 'Inactivo' }}
                                    </Badge>
                                </td>
                                <td class="px-6 py-3 text-right">
                                    <button @click="edit(mechanic)" class="mr-3 cursor-pointer font-medium text-primary-600 hover:text-primary-800">
                                        Editar
                                    </button>
                                    <button
                                        v-if="mechanic.active"
                                        @click="deactivate(mechanic)"
                                        class="cursor-pointer font-medium text-red-600 hover:text-red-800"
                                    >
                                        Desactivar
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="mechanics.length === 0">
                                <td colspan="4" class="px-6 py-10 text-center text-slate-500">
                                    No hay mecánicos registrados.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </Card>

                <Card padded>
                    <form @submit.prevent="submit" class="space-y-4">
                        <h3 class="text-sm font-semibold text-slate-900">
                            {{ editingId ? 'Editar mecánico' : 'Nuevo mecánico' }}
                        </h3>

                        <div>
                            <InputLabel for="name" value="Nombre" />
                            <TextInput id="name" v-model="form.name" class="mt-1 block w-full" />
                            <InputError :message="form.errors.name" class="mt-2" />
                        </div>

                        <div>
                            <InputLabel for="phone" value="Teléfono (opcional)" />
                            <TextInput id="phone" v-model="form.phone" class="mt-1 block w-full" />
                        </div>

                        <div v-if="editingId" class="flex items-center gap-2">
                            <Checkbox id="active" v-model:checked="form.active" />
                            <InputLabel for="active" value="Activo" />
                        </div>

                        <div class="flex gap-2">
                            <PrimaryButton class="cursor-pointer" :disabled="form.processing">
                                {{ editingId ? 'Guardar cambios' : 'Registrar' }}
                            </PrimaryButton>
                            <button
                                v-if="editingId"
                                type="button"
                                @click="cancelEdit"
                                class="cursor-pointer rounded-lg px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100"
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
