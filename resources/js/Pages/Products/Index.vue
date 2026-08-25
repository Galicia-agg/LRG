<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import Card from '@/Components/Card.vue';
import Badge from '@/Components/Badge.vue';
import Icon from '@/Components/Icon.vue';
import { usePermissions } from '@/Composables/usePermissions';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    products: Object,
    filters: Object,
});

const { can } = usePermissions();
const search = ref(props.filters.q ?? '');

function runSearch() {
    router.get(
        route('products.index'),
        { q: search.value },
        { preserveState: true, replace: true },
    );
}

function destroy(product) {
    if (confirm(`¿Desactivar el producto "${product.name}"?`)) {
        router.delete(route('products.destroy', product.id));
    }
}
</script>

<template>
    <Head title="Productos" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-lg font-semibold leading-tight text-slate-900">Productos</h2>
        </template>

        <div class="mx-auto max-w-screen-2xl space-y-4 px-4 py-8 sm:px-6 lg:px-8">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <form @submit.prevent="runSearch" class="flex w-full max-w-md gap-2">
                    <div class="relative flex-1">
                        <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-slate-400">
                            <Icon name="search" class="h-4 w-4" />
                        </span>
                        <input
                            v-model="search"
                            type="text"
                            placeholder="Buscar por nombre, SKU o código de barras"
                            class="w-full rounded-lg border-slate-300 py-2 pl-9 text-sm shadow-sm placeholder:text-slate-400 focus:border-primary-500 focus:ring-primary-500/40"
                        />
                    </div>
                    <PrimaryButton type="submit">Buscar</PrimaryButton>
                </form>

                <Link
                    v-if="can('products.manage')"
                    :href="route('products.create')"
                    class="inline-flex items-center justify-center gap-2 rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-700"
                >
                    <Icon name="plus" class="h-4 w-4" />
                    Nuevo producto
                </Link>
            </div>

            <Card>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr class="text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                <th class="px-6 py-3"></th>
                                <th class="px-6 py-3">SKU</th>
                                <th class="px-6 py-3">Nombre</th>
                                <th class="px-6 py-3">Categoría</th>
                                <th class="px-6 py-3">Unidad</th>
                                <th class="px-6 py-3">Stock</th>
                                <th class="px-6 py-3">Precio</th>
                                <th class="px-6 py-3">Estado</th>
                                <th class="px-6 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-sm text-slate-700">
                            <tr v-for="product in products.data" :key="product.id" class="hover:bg-slate-50">
                                <td class="px-6 py-3">
                                    <div class="flex h-10 w-10 items-center justify-center overflow-hidden rounded-md border border-slate-200 bg-slate-50">
                                        <img
                                            v-if="product.images?.[0]?.url"
                                            :src="product.images[0].url"
                                            alt=""
                                            class="h-full w-full object-cover"
                                        />
                                        <Icon v-else name="image" class="h-4 w-4 text-slate-300" />
                                    </div>
                                </td>
                                <td class="px-6 py-3 font-mono text-xs text-slate-500">{{ product.sku }}</td>
                                <td class="px-6 py-3 font-medium text-slate-900">{{ product.name }}</td>
                                <td class="px-6 py-3">{{ product.category?.name ?? '—' }}</td>
                                <td class="px-6 py-3">{{ product.unit }}</td>
                                <td class="px-6 py-3">
                                    <span
                                        :class="
                                            Number(product.current_stock) <= Number(product.min_stock)
                                                ? 'font-semibold text-amber-600'
                                                : ''
                                        "
                                    >
                                        {{ product.current_stock }}
                                    </span>
                                </td>
                                <td class="px-6 py-3">Q {{ product.sale_price }}</td>
                                <td class="px-6 py-3">
                                    <Badge :tone="product.active ? 'green' : 'slate'">
                                        {{ product.active ? 'Activo' : 'Inactivo' }}
                                    </Badge>
                                </td>
                                <td class="px-6 py-3 text-right">
                                    <template v-if="can('products.manage')">
                                        <Link
                                            :href="route('products.edit', product.id)"
                                            class="mr-3 font-medium text-primary-600 hover:text-primary-800"
                                        >
                                            Editar
                                        </Link>
                                        <button
                                            @click="destroy(product)"
                                            class="font-medium text-red-600 hover:text-red-800"
                                        >
                                            Desactivar
                                        </button>
                                    </template>
                                </td>
                            </tr>
                            <tr v-if="products.data.length === 0">
                                <td colspan="9" class="px-6 py-10 text-center text-slate-500">
                                    No hay productos.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="flex flex-wrap gap-1 border-t border-slate-100 px-6 py-3">
                    <Link
                        v-for="(link, index) in products.links"
                        :key="index"
                        :href="link.url ?? '#'"
                        v-html="link.label"
                        class="rounded-md px-3 py-1 text-sm"
                        :class="[
                            link.active
                                ? 'bg-primary-600 text-white'
                                : 'text-slate-600 hover:bg-slate-100',
                            !link.url && 'pointer-events-none opacity-50',
                        ]"
                    />
                </div>
            </Card>
        </div>
    </AuthenticatedLayout>
</template>
