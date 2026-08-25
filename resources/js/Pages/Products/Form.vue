<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import Checkbox from '@/Components/Checkbox.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import Card from '@/Components/Card.vue';
import Icon from '@/Components/Icon.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    categories: Array,
    product: Object,
});

const isEditing = !!props.product;

const form = useForm({
    category_id: props.product?.category_id ?? '',
    sku: props.product?.sku ?? '',
    barcode: props.product?.barcode ?? '',
    name: props.product?.name ?? '',
    brand: props.product?.brand ?? '',
    description: props.product?.description ?? '',
    images: [],
    remove_image_ids: [],
    unit: props.product?.unit ?? 'unidad',
    is_bulk: props.product?.is_bulk ?? false,
    cost_price: props.product?.cost_price ?? '0.00',
    sale_price: props.product?.sale_price ?? '0.00',
    compare_at_price: props.product?.compare_at_price ?? '',
    min_stock: props.product?.min_stock ?? '0',
    current_stock: props.product?.current_stock ?? '0',
    expiration_date: props.product?.expiration_date ?? '',
    active: props.product?.active ?? true,
    compatibilities: (props.product?.compatibilities ?? []).map((c) => ({
        brand: c.brand,
        model: c.model,
        year_from: c.year_from ?? '',
        year_to: c.year_to ?? '',
        engine: c.engine ?? '',
    })),
    specifications: (props.product?.specifications ?? []).map((s) => ({
        label: s.label,
        value: s.value,
    })),
});

const existingImages = ref(props.product?.images ?? []);
const newImagePreviews = ref([]);

function onImagesChange(event) {
    const files = Array.from(event.target.files ?? []);

    for (const file of files) {
        form.images.push(file);
        newImagePreviews.value.push({ file, url: URL.createObjectURL(file) });
    }

    event.target.value = '';
}

function removeExistingImage(image) {
    existingImages.value = existingImages.value.filter((img) => img.id !== image.id);
    form.remove_image_ids.push(image.id);
}

function removeNewImage(index) {
    form.images.splice(index, 1);
    newImagePreviews.value.splice(index, 1);
}

function addCompatibilityRow() {
    form.compatibilities.push({ brand: '', model: '', year_from: '', year_to: '', engine: '' });
}

function removeCompatibilityRow(index) {
    form.compatibilities.splice(index, 1);
}

function addSpecificationRow() {
    form.specifications.push({ label: '', value: '' });
}

function removeSpecificationRow(index) {
    form.specifications.splice(index, 1);
}

function submit() {
    if (isEditing) {
        form.put(route('products.update', props.product.id));
    } else {
        form.post(route('products.store'));
    }
}

const selectClasses =
    'mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500/40';
</script>

<template>
    <Head :title="isEditing ? 'Editar producto' : 'Nuevo producto'" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-lg font-semibold leading-tight text-slate-900">
                {{ isEditing ? 'Editar producto' : 'Nuevo producto' }}
            </h2>
        </template>

        <div class="mx-auto max-w-3xl px-4 py-8 sm:px-6 lg:px-8">
            <Card padded>
                <form @submit.prevent="submit" class="space-y-6">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <InputLabel for="category_id" value="Categoría" />
                            <select
                                id="category_id"
                                v-model="form.category_id"
                                :class="selectClasses"
                            >
                                <option value="" disabled>Selecciona una categoría</option>
                                <option
                                    v-for="category in categories"
                                    :key="category.id"
                                    :value="category.id"
                                >
                                    {{ category.name }}
                                </option>
                            </select>
                            <InputError :message="form.errors.category_id" class="mt-2" />
                        </div>

                        <div>
                            <InputLabel for="unit" value="Unidad" />
                            <select id="unit" v-model="form.unit" :class="selectClasses">
                                <option value="unidad">Unidad</option>
                                <option value="litro">Litro</option>
                                <option value="galon">Galón</option>
                                <option value="cuarto">Cuarto</option>
                                <option value="par">Par</option>
                                <option value="juego">Juego</option>
                            </select>
                            <InputError :message="form.errors.unit" class="mt-2" />
                        </div>

                        <div>
                            <InputLabel for="sku" value="SKU" />
                            <TextInput
                                id="sku"
                                v-model="form.sku"
                                class="mt-1 block w-full"
                            />
                            <InputError :message="form.errors.sku" class="mt-2" />
                        </div>

                        <div>
                            <InputLabel for="barcode" value="Código de barras" />
                            <TextInput
                                id="barcode"
                                v-model="form.barcode"
                                class="mt-1 block w-full"
                            />
                            <InputError :message="form.errors.barcode" class="mt-2" />
                        </div>

                        <div class="sm:col-span-2">
                            <InputLabel for="name" value="Nombre" />
                            <TextInput
                                id="name"
                                v-model="form.name"
                                class="mt-1 block w-full"
                            />
                            <InputError :message="form.errors.name" class="mt-2" />
                        </div>

                        <div class="sm:col-span-2">
                            <InputLabel for="brand" value="Marca (opcional)" />
                            <TextInput
                                id="brand"
                                v-model="form.brand"
                                placeholder="Ej. Ichimax, Bosch, NGK"
                                class="mt-1 block w-full"
                            />
                            <InputError :message="form.errors.brand" class="mt-2" />
                        </div>

                        <div class="sm:col-span-2">
                            <InputLabel for="description" value="Descripción" />
                            <textarea
                                id="description"
                                v-model="form.description"
                                rows="2"
                                class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500/40"
                            ></textarea>
                            <InputError :message="form.errors.description" class="mt-2" />
                        </div>

                        <div class="sm:col-span-2">
                            <InputLabel value="Fotos del producto" />
                            <div class="mt-1 flex flex-wrap gap-3">
                                <div
                                    v-for="image in existingImages"
                                    :key="`existing-${image.id}`"
                                    class="group relative h-20 w-20 shrink-0 overflow-hidden rounded-lg border border-slate-200 bg-slate-50"
                                >
                                    <img :src="image.url" alt="" class="h-full w-full object-cover" />
                                    <button
                                        type="button"
                                        @click="removeExistingImage(image)"
                                        class="absolute right-1 top-1 flex h-5 w-5 items-center justify-center rounded-full bg-slate-900/70 text-white opacity-0 transition group-hover:opacity-100"
                                    >
                                        <Icon name="close" class="h-3 w-3" />
                                    </button>
                                </div>

                                <div
                                    v-for="(preview, index) in newImagePreviews"
                                    :key="`new-${index}`"
                                    class="group relative h-20 w-20 shrink-0 overflow-hidden rounded-lg border border-primary-300 bg-slate-50"
                                >
                                    <img :src="preview.url" alt="" class="h-full w-full object-cover" />
                                    <button
                                        type="button"
                                        @click="removeNewImage(index)"
                                        class="absolute right-1 top-1 flex h-5 w-5 items-center justify-center rounded-full bg-slate-900/70 text-white opacity-0 transition group-hover:opacity-100"
                                    >
                                        <Icon name="close" class="h-3 w-3" />
                                    </button>
                                </div>

                                <label
                                    class="flex h-20 w-20 shrink-0 cursor-pointer flex-col items-center justify-center gap-1 rounded-lg border border-dashed border-slate-300 text-slate-400 hover:border-primary-400 hover:text-primary-600"
                                >
                                    <Icon name="image" class="h-6 w-6" />
                                    <span class="text-[10px] font-medium">Agregar</span>
                                    <input
                                        type="file"
                                        accept="image/*"
                                        multiple
                                        class="hidden"
                                        @change="onImagesChange"
                                    />
                                </label>
                            </div>
                            <p class="mt-1 text-xs text-slate-400">Puedes subir varias fotos. La primera es la portada.</p>
                            <InputError :message="form.errors.images" class="mt-2" />
                        </div>

                        <div class="sm:col-span-2 border-t border-slate-100 pt-4">
                            <div class="flex items-center justify-between">
                                <InputLabel value="Compatibilidad con vehículos (para repuestos)" />
                                <button
                                    type="button"
                                    @click="addCompatibilityRow"
                                    class="inline-flex items-center gap-1 text-xs font-semibold text-primary-600 hover:text-primary-800"
                                >
                                    <Icon name="plus" class="h-3.5 w-3.5" />
                                    Agregar vehículo
                                </button>
                            </div>
                            <p class="mt-1 text-xs text-slate-400">
                                Opcional. Indica con qué marcas, modelos y años es compatible este producto.
                            </p>

                            <div v-if="form.compatibilities.length > 0" class="mt-3 space-y-2">
                                <div
                                    v-for="(row, index) in form.compatibilities"
                                    :key="index"
                                    class="grid grid-cols-2 gap-2 rounded-lg border border-slate-200 p-3 sm:grid-cols-5 sm:items-end"
                                >
                                    <div class="sm:col-span-1">
                                        <label class="mb-1 block text-[11px] font-medium text-slate-500">Marca</label>
                                        <input
                                            v-model="row.brand"
                                            type="text"
                                            placeholder="Toyota"
                                            class="w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500/40"
                                        />
                                    </div>
                                    <div class="sm:col-span-1">
                                        <label class="mb-1 block text-[11px] font-medium text-slate-500">Modelo</label>
                                        <input
                                            v-model="row.model"
                                            type="text"
                                            placeholder="Hilux"
                                            class="w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500/40"
                                        />
                                    </div>
                                    <div>
                                        <label class="mb-1 block text-[11px] font-medium text-slate-500">Año desde</label>
                                        <input
                                            v-model="row.year_from"
                                            type="number"
                                            placeholder="2010"
                                            class="w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500/40"
                                        />
                                    </div>
                                    <div>
                                        <label class="mb-1 block text-[11px] font-medium text-slate-500">Año hasta</label>
                                        <input
                                            v-model="row.year_to"
                                            type="number"
                                            placeholder="2018"
                                            class="w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500/40"
                                        />
                                    </div>
                                    <div class="flex items-end gap-1">
                                        <div class="flex-1">
                                            <label class="mb-1 block text-[11px] font-medium text-slate-500">Motor</label>
                                            <input
                                                v-model="row.engine"
                                                type="text"
                                                placeholder="2.7L"
                                                class="w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500/40"
                                            />
                                        </div>
                                        <button
                                            type="button"
                                            @click="removeCompatibilityRow(index)"
                                            class="mb-0.5 shrink-0 rounded-md p-1.5 text-slate-400 hover:bg-red-50 hover:text-red-600"
                                        >
                                            <Icon name="trash" class="h-4 w-4" />
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="sm:col-span-2 border-t border-slate-100 pt-4">
                            <div class="flex items-center justify-between">
                                <InputLabel value="Especificaciones técnicas (opcional)" />
                                <button
                                    type="button"
                                    @click="addSpecificationRow"
                                    class="inline-flex items-center gap-1 text-xs font-semibold text-primary-600 hover:text-primary-800"
                                >
                                    <Icon name="plus" class="h-3.5 w-3.5" />
                                    Agregar especificación
                                </button>
                            </div>
                            <p class="mt-1 text-xs text-slate-400">
                                Ej. Material: Cerámica, Peso: 1.00 libras, Ancho: 102.4 mm. Se muestran tal cual en la ficha del producto.
                            </p>

                            <div v-if="form.specifications.length > 0" class="mt-3 space-y-2">
                                <div
                                    v-for="(row, index) in form.specifications"
                                    :key="index"
                                    class="flex items-end gap-2"
                                >
                                    <div class="flex-1">
                                        <label class="mb-1 block text-[11px] font-medium text-slate-500">Nombre</label>
                                        <input
                                            v-model="row.label"
                                            type="text"
                                            placeholder="Material"
                                            class="w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500/40"
                                        />
                                    </div>
                                    <div class="flex-1">
                                        <label class="mb-1 block text-[11px] font-medium text-slate-500">Valor</label>
                                        <input
                                            v-model="row.value"
                                            type="text"
                                            placeholder="Cerámica"
                                            class="w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500/40"
                                        />
                                    </div>
                                    <button
                                        type="button"
                                        @click="removeSpecificationRow(index)"
                                        class="mb-0.5 shrink-0 rounded-md p-1.5 text-slate-400 hover:bg-red-50 hover:text-red-600"
                                    >
                                        <Icon name="trash" class="h-4 w-4" />
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div>
                            <InputLabel for="cost_price" value="Precio de costo" />
                            <TextInput
                                id="cost_price"
                                v-model="form.cost_price"
                                type="number"
                                step="0.01"
                                class="mt-1 block w-full"
                            />
                            <InputError :message="form.errors.cost_price" class="mt-2" />
                        </div>

                        <div>
                            <InputLabel for="sale_price" value="Precio de venta" />
                            <TextInput
                                id="sale_price"
                                v-model="form.sale_price"
                                type="number"
                                step="0.01"
                                class="mt-1 block w-full"
                            />
                            <InputError :message="form.errors.sale_price" class="mt-2" />
                        </div>

                        <div>
                            <InputLabel for="compare_at_price" value="Precio antes de descuento (opcional)" />
                            <TextInput
                                id="compare_at_price"
                                v-model="form.compare_at_price"
                                type="number"
                                step="0.01"
                                class="mt-1 block w-full"
                            />
                            <p class="mt-1 text-xs text-slate-400">
                                Si lo llenas, la tienda muestra el precio tachado y el % de descuento. Debe ser mayor al precio de venta.
                            </p>
                            <InputError :message="form.errors.compare_at_price" class="mt-2" />
                        </div>

                        <div>
                            <InputLabel for="min_stock" value="Stock mínimo" />
                            <TextInput
                                id="min_stock"
                                v-model="form.min_stock"
                                type="number"
                                step="0.01"
                                class="mt-1 block w-full"
                            />
                            <InputError :message="form.errors.min_stock" class="mt-2" />
                        </div>

                        <div v-if="!isEditing">
                            <InputLabel for="current_stock" value="Stock inicial" />
                            <TextInput
                                id="current_stock"
                                v-model="form.current_stock"
                                type="number"
                                step="0.01"
                                class="mt-1 block w-full"
                            />
                            <InputError :message="form.errors.current_stock" class="mt-2" />
                        </div>

                        <div>
                            <InputLabel for="expiration_date" value="Fecha de vencimiento (opcional)" />
                            <TextInput
                                id="expiration_date"
                                v-model="form.expiration_date"
                                type="date"
                                class="mt-1 block w-full"
                            />
                            <p class="mt-1 text-xs text-slate-400">
                                Solo para productos perecederos (ej. aceites). Se usa para las alertas de vencimiento.
                            </p>
                            <InputError :message="form.errors.expiration_date" class="mt-2" />
                        </div>

                        <div class="flex items-center gap-2">
                            <Checkbox id="is_bulk" v-model:checked="form.is_bulk" />
                            <InputLabel for="is_bulk" value="Se vende a granel" />
                        </div>

                        <div class="flex items-center gap-2">
                            <Checkbox id="active" v-model:checked="form.active" />
                            <InputLabel for="active" value="Activo" />
                        </div>
                    </div>

                    <div class="flex items-center gap-4 border-t border-slate-100 pt-6">
                        <PrimaryButton :disabled="form.processing">
                            Guardar
                        </PrimaryButton>
                    </div>
                </form>
            </Card>
        </div>
    </AuthenticatedLayout>
</template>
