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
    categories: Array,
});

const editingId = ref(null);

const form = useForm({
    name: '',
    parent_id: '',
});

function submit() {
    if (editingId.value) {
        form.put(route('categories.update', editingId.value), {
            onSuccess: () => cancelEdit(),
        });
    } else {
        form.post(route('categories.store'), {
            onSuccess: () => form.reset(),
        });
    }
}

function edit(category) {
    editingId.value = category.id;
    form.name = category.name;
    form.parent_id = category.parent_id ?? '';
}

function cancelEdit() {
    editingId.value = null;
    form.reset();
}

function destroy(category) {
    if (confirm(`¿Eliminar la categoría "${category.name}"?`)) {
        router.delete(route('categories.destroy', category.id));
    }
}
</script>

<template>
    <Head title="Categorías" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-lg font-semibold leading-tight text-slate-900">Categorías</h2>
        </template>

        <div class="mx-auto max-w-screen-2xl px-4 py-8 sm:px-6 lg:px-8">
            <div class="grid gap-6 md:grid-cols-3">
                <Card class="md:col-span-2">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr class="text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                <th class="px-6 py-3">Nombre</th>
                                <th class="px-6 py-3">Categoría padre</th>
                                <th class="px-6 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-sm text-slate-700">
                            <tr v-for="category in categories" :key="category.id" class="hover:bg-slate-50">
                                <td class="px-6 py-3 font-medium text-slate-900">{{ category.name }}</td>
                                <td class="px-6 py-3">
                                    {{
                                        categories.find((c) => c.id === category.parent_id)
                                            ?.name ?? '—'
                                    }}
                                </td>
                                <td class="px-6 py-3 text-right">
                                    <button
                                        @click="edit(category)"
                                        class="mr-3 font-medium text-primary-600 hover:text-primary-800"
                                    >
                                        Editar
                                    </button>
                                    <button
                                        @click="destroy(category)"
                                        class="font-medium text-red-600 hover:text-red-800"
                                    >
                                        Eliminar
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="categories.length === 0">
                                <td colspan="3" class="px-6 py-10 text-center text-slate-500">
                                    No hay categorías.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </Card>

                <Card padded>
                    <form @submit.prevent="submit" class="space-y-4">
                        <h3 class="text-sm font-semibold text-slate-900">
                            {{ editingId ? 'Editar categoría' : 'Nueva categoría' }}
                        </h3>

                        <div>
                            <InputLabel for="name" value="Nombre" />
                            <TextInput
                                id="name"
                                v-model="form.name"
                                class="mt-1 block w-full"
                            />
                            <InputError :message="form.errors.name" class="mt-2" />
                        </div>

                        <div>
                            <InputLabel for="parent_id" value="Categoría padre (opcional)" />
                            <select
                                id="parent_id"
                                v-model="form.parent_id"
                                class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500/40"
                            >
                                <option value="">Ninguna</option>
                                <option
                                    v-for="category in categories"
                                    :key="category.id"
                                    :value="category.id"
                                >
                                    {{ category.name }}
                                </option>
                            </select>
                            <InputError :message="form.errors.parent_id" class="mt-2" />
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
