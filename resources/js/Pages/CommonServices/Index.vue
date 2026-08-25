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
    commonServices: Array,
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
    for (const service of props.commonServices) {
        const key = service.category ?? 'Sin categoría';
        if (!groups[key]) groups[key] = [];
        groups[key].push(service);
    }
    return groups;
});

function submit() {
    if (editingId.value) {
        form.put(route('common-services.update', editingId.value), {
            onSuccess: () => cancelEdit(),
        });
    } else {
        form.post(route('common-services.store'), {
            onSuccess: () => form.reset(),
        });
    }
}

function edit(service) {
    editingId.value = service.id;
    form.description = service.description;
    form.category = service.category ?? '';
    form.suggested_price = service.suggested_price ?? '';
    form.active = service.active;
}

function cancelEdit() {
    editingId.value = null;
    form.reset();
}

function destroy(service) {
    if (confirm(`¿Eliminar "${service.description}" del catálogo?`)) {
        router.delete(route('common-services.destroy', service.id));
    }
}
</script>

<template>
    <Head title="Servicios comunes" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-lg font-semibold leading-tight text-slate-900">Catálogo de servicios de mantenimiento</h2>
        </template>

        <div class="mx-auto max-w-5xl px-4 py-8 sm:px-6 lg:px-8">
            <p class="mb-4 text-sm text-slate-500">
                Estas tareas aparecen como checklist al registrar una orden de tipo "Servicio", para dejar constancia de qué se realizó.
                Agrúpalas en categorías como "Servicio menor" o "Servicio mayor" para organizarlas.
            </p>

            <div class="grid gap-6 md:grid-cols-3">
                <div class="space-y-6 md:col-span-2">
                    <div v-for="(services, category) in grouped" :key="category">
                        <h3 class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">{{ category }}</h3>
                        <Card>
                            <ul class="divide-y divide-slate-100">
                                <li v-for="service in services" :key="service.id" class="flex items-center justify-between px-4 py-2.5 text-sm">
                                    <span class="text-slate-700">
                                        {{ service.description }}
                                        <span v-if="service.suggested_price" class="ml-1 text-xs font-medium text-accent-600">
                                            Q {{ Number(service.suggested_price).toFixed(2) }}
                                        </span>
                                    </span>
                                    <div class="flex items-center gap-3">
                                        <Badge v-if="!service.active" tone="slate">Inactiva</Badge>
                                        <button @click="edit(service)" class="cursor-pointer font-medium text-primary-600 hover:text-primary-800">
                                            Editar
                                        </button>
                                        <button @click="destroy(service)" class="cursor-pointer font-medium text-red-600 hover:text-red-800">
                                            Eliminar
                                        </button>
                                    </div>
                                </li>
                            </ul>
                        </Card>
                    </div>
                    <p v-if="commonServices.length === 0" class="text-sm text-slate-500">
                        Todavía no hay tareas en el catálogo.
                    </p>
                </div>

                <Card padded class="md:sticky md:top-6 md:h-fit">
                    <form @submit.prevent="submit" class="space-y-4">
                        <h3 class="text-sm font-semibold text-slate-900">
                            {{ editingId ? 'Editar tarea' : 'Nueva tarea' }}
                        </h3>

                        <div>
                            <InputLabel for="description" value="Descripción" />
                            <TextInput id="description" v-model="form.description" class="mt-1 block w-full" />
                            <InputError :message="form.errors.description" class="mt-2" />
                        </div>

                        <div>
                            <InputLabel for="category" value="Categoría (opcional)" />
                            <TextInput id="category" v-model="form.category" placeholder="Servicio menor, Servicio mayor..." class="mt-1 block w-full" />
                        </div>

                        <div>
                            <InputLabel for="suggested_price" value="Precio sugerido (opcional)" />
                            <TextInput id="suggested_price" v-model="form.suggested_price" type="number" min="0" step="0.01" class="mt-1 block w-full" />
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
