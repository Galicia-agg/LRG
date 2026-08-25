<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import Card from '@/Components/Card.vue';
import Badge from '@/Components/Badge.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    commonFailures: Array,
});

const editingId = ref(null);

const form = useForm({
    description: '',
    category: '',
    suggested_price: '',
    active: true,
});

const grouped = computed(() => {
    const groups = {};
    for (const failure of props.commonFailures) {
        const key = failure.category ?? 'Sin categoría';
        if (!groups[key]) groups[key] = [];
        groups[key].push(failure);
    }
    return groups;
});

function submit() {
    if (editingId.value) {
        form.put(route('common-failures.update', editingId.value), {
            onSuccess: () => cancelEdit(),
        });
    } else {
        form.post(route('common-failures.store'), {
            onSuccess: () => form.reset(),
        });
    }
}

function edit(failure) {
    editingId.value = failure.id;
    form.description = failure.description;
    form.category = failure.category ?? '';
    form.suggested_price = failure.suggested_price ?? '';
    form.active = failure.active;
}

function cancelEdit() {
    editingId.value = null;
    form.reset();
}

function destroy(failure) {
    if (confirm(`¿Eliminar "${failure.description}" del catálogo?`)) {
        router.delete(route('common-failures.destroy', failure.id));
    }
}
</script>

<template>
    <Head title="Fallas comunes" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-lg font-semibold leading-tight text-slate-900">Catálogo de fallas comunes</h2>
        </template>

        <div class="mx-auto max-w-5xl px-4 py-8 sm:px-6 lg:px-8">
            <p class="mb-4 text-sm text-slate-500">
                Estas fallas aparecen como sugerencias rápidas al reportar el problema o el diagnóstico de una orden de servicio.
            </p>

            <div class="grid gap-6 md:grid-cols-3">
                <div class="space-y-6 md:col-span-2">
                    <div v-for="(failures, category) in grouped" :key="category">
                        <h3 class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">{{ category }}</h3>
                        <Card>
                            <ul class="divide-y divide-slate-100">
                                <li v-for="failure in failures" :key="failure.id" class="flex items-center justify-between px-4 py-2.5 text-sm">
                                    <span class="text-slate-700">
                                        {{ failure.description }}
                                        <span v-if="failure.suggested_price" class="ml-1 text-xs font-medium text-primary-600">
                                            Q {{ Number(failure.suggested_price).toFixed(2) }}
                                        </span>
                                    </span>
                                    <div class="flex items-center gap-3">
                                        <Badge v-if="!failure.active" tone="slate">Inactiva</Badge>
                                        <button @click="edit(failure)" class="cursor-pointer font-medium text-primary-600 hover:text-primary-800">
                                            Editar
                                        </button>
                                        <button @click="destroy(failure)" class="cursor-pointer font-medium text-red-600 hover:text-red-800">
                                            Eliminar
                                        </button>
                                    </div>
                                </li>
                            </ul>
                        </Card>
                    </div>
                    <p v-if="commonFailures.length === 0" class="text-sm text-slate-500">
                        Todavía no hay fallas en el catálogo.
                    </p>
                </div>

                <Card padded class="md:sticky md:top-6 md:h-fit">
                    <form @submit.prevent="submit" class="space-y-4">
                        <h3 class="text-sm font-semibold text-slate-900">
                            {{ editingId ? 'Editar falla' : 'Nueva falla' }}
                        </h3>

                        <div>
                            <InputLabel for="description" value="Descripción" />
                            <TextInput id="description" v-model="form.description" class="mt-1 block w-full" />
                            <InputError :message="form.errors.description" class="mt-2" />
                        </div>

                        <div>
                            <InputLabel for="category" value="Categoría (opcional)" />
                            <TextInput id="category" v-model="form.category" placeholder="Motor, Frenos, Eléctrico..." class="mt-1 block w-full" />
                        </div>

                        <div>
                            <InputLabel for="suggested_price" value="Precio sugerido de mano de obra (opcional)" />
                            <TextInput id="suggested_price" v-model="form.suggested_price" type="number" min="0" step="0.01" class="mt-1 block w-full" />
                            <p class="mt-1 text-xs text-slate-400">
                                Si lo llenas, al usar esta falla en una orden se agrega automáticamente como mano de obra con este monto.
                            </p>
                            <InputError :message="form.errors.suggested_price" class="mt-1" />
                        </div>

                        <div class="flex gap-2">
                            <PrimaryButton class="cursor-pointer" :disabled="form.processing">
                                {{ editingId ? 'Guardar cambios' : 'Agregar' }}
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
