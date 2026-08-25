<script setup>
import { computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Card from '@/Components/Card.vue';
import Badge from '@/Components/Badge.vue';
import Icon from '@/Components/Icon.vue';
import BarChart from '@/Components/BarChart.vue';
import DonutChart from '@/Components/DonutChart.vue';
import { usePermissions } from '@/Composables/usePermissions';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
    stats: Object,
    charts: Object,
});

const { can, canAny } = usePermissions();

const salesTrendTotal = computed(() => (props.charts.salesTrend ?? []).reduce((sum, point) => sum + point.total, 0));

const maxProductRevenue = computed(() => Math.max(...(props.charts.topProducts ?? []).map((p) => p.revenue), 1));

const workshopSegments = computed(() => [
    { label: 'Servicios', value: props.charts.workshopByType?.servicio ?? 0, color: '#65a30d' },
    { label: 'Reparaciones', value: props.charts.workshopByType?.reparacion ?? 0, color: '#ea580c' },
]);

const orderStatusMeta = {
    pending: { label: 'Pendientes', color: '#f59e0b' },
    confirmed: { label: 'Confirmados', color: '#ea580c' },
    completed: { label: 'Completados', color: '#059669' },
    cancelled: { label: 'Cancelados', color: '#94a3b8' },
};

const orderStatusSegments = computed(() =>
    Object.entries(orderStatusMeta).map(([status, meta]) => ({
        label: meta.label,
        color: meta.color,
        value: props.charts.ordersByStatus?.[status] ?? 0,
    })),
);

const allQuickLinks = [
    { name: 'Punto de venta', description: 'Registrar una venta', href: 'pos.create', icon: 'cart', permissions: ['sales.create'] },
    { name: 'Ventas', description: 'Historial y anulaciones', href: 'sales.index', icon: 'receipt', permissions: ['sales.view'] },
    { name: 'Productos', description: 'Ver y editar inventario', href: 'products.index', icon: 'box', permissions: ['products.view', 'products.manage'] },
    { name: 'Categorías', description: 'Organizar el catálogo', href: 'categories.index', icon: 'tag', permissions: ['categories.manage'] },
    { name: 'Proveedores', description: 'Administrar contactos', href: 'suppliers.index', icon: 'truck', permissions: ['suppliers.manage'] },
    { name: 'Clientes', description: 'Administrar clientes', href: 'customers.index', icon: 'users', permissions: ['customers.manage'] },
    { name: 'Pedidos online', description: 'Confirmar y completar pedidos', href: 'orders.index', icon: 'bag', permissions: ['orders.manage'] },
];

const quickLinks = computed(() => allQuickLinks.filter((link) => canAny(link.permissions)));
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-lg font-semibold leading-tight text-slate-900">Dashboard</h2>
        </template>

        <div class="mx-auto max-w-screen-2xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
            <!-- Stat cards -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <Card padded>
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-sm font-medium text-slate-500">Ventas de hoy</p>
                            <p class="mt-1 text-2xl font-semibold text-slate-900">
                                Q {{ stats.salesToday.total.toFixed(2) }}
                            </p>
                            <p class="mt-1 text-xs text-slate-500">
                                {{ stats.salesToday.count }} venta(s)
                            </p>
                            <Link
                                v-if="can('sales.view')"
                                :href="route('sales.index')"
                                class="mt-1 inline-block text-xs font-medium text-primary-600 hover:underline"
                            >
                                Ver ventas
                            </Link>
                        </div>
                        <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-primary-50 text-primary-600">
                            <Icon name="cash" class="h-5 w-5" />
                        </span>
                    </div>
                </Card>

                <Card padded>
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-sm font-medium text-slate-500">Productos activos</p>
                            <p class="mt-1 text-2xl font-semibold text-slate-900">
                                {{ stats.activeProducts }}
                            </p>
                            <Link :href="route('products.index')" class="mt-1 inline-block text-xs font-medium text-primary-600 hover:underline">
                                Ver catálogo
                            </Link>
                        </div>
                        <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-primary-50 text-primary-600">
                            <Icon name="box" class="h-5 w-5" />
                        </span>
                    </div>
                </Card>

                <Card padded>
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-sm font-medium text-slate-500">Stock bajo</p>
                            <p
                                class="mt-1 text-2xl font-semibold"
                                :class="stats.lowStockProducts > 0 ? 'text-amber-600' : 'text-slate-900'"
                            >
                                {{ stats.lowStockProducts }}
                            </p>
                            <p class="mt-1 text-xs text-slate-500">productos bajo el mínimo</p>
                            <Link
                                v-if="can('products.view') || can('products.manage')"
                                :href="route('alerts.index')"
                                class="mt-1 inline-block text-xs font-medium text-primary-600 hover:underline"
                            >
                                Ver alertas
                            </Link>
                        </div>
                        <span
                            class="flex h-10 w-10 items-center justify-center rounded-lg"
                            :class="stats.lowStockProducts > 0 ? 'bg-amber-50 text-amber-600' : 'bg-primary-50 text-primary-600'"
                        >
                            <Icon name="alert" class="h-5 w-5" />
                        </span>
                    </div>
                </Card>

                <Card padded>
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-sm font-medium text-slate-500">Próximos a vencer</p>
                            <p
                                class="mt-1 text-2xl font-semibold"
                                :class="stats.expiringSoonProducts > 0 ? 'text-red-600' : 'text-slate-900'"
                            >
                                {{ stats.expiringSoonProducts }}
                            </p>
                            <p class="mt-1 text-xs text-slate-500">en los próximos 30 días</p>
                            <Link
                                v-if="can('products.view') || can('products.manage')"
                                :href="route('alerts.index')"
                                class="mt-1 inline-block text-xs font-medium text-primary-600 hover:underline"
                            >
                                Ver alertas
                            </Link>
                        </div>
                        <span
                            class="flex h-10 w-10 items-center justify-center rounded-lg"
                            :class="stats.expiringSoonProducts > 0 ? 'bg-red-50 text-red-600' : 'bg-primary-50 text-primary-600'"
                        >
                            <Icon name="clock" class="h-5 w-5" />
                        </span>
                    </div>
                </Card>

                <Card padded>
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-sm font-medium text-slate-500">Caja</p>
                            <div class="mt-1">
                                <Badge :tone="stats.cashSessionOpen ? 'green' : 'slate'">
                                    {{ stats.cashSessionOpen ? 'Abierta' : 'Cerrada' }}
                                </Badge>
                            </div>
                            <p v-if="stats.cashSessionOpen" class="mt-1 text-xs text-slate-500">
                                Inicial: Q {{ Number(stats.cashSessionOpeningAmount).toFixed(2) }}
                            </p>
                            <Link v-else :href="route('cash-sessions.create')" class="mt-1 inline-block text-xs font-medium text-primary-600 hover:underline">
                                Abrir caja
                            </Link>
                        </div>
                        <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-primary-50 text-primary-600">
                            <Icon name="lock" class="h-5 w-5" />
                        </span>
                    </div>
                </Card>

                <Card v-if="can('orders.manage')" padded>
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-sm font-medium text-slate-500">Pedidos pendientes</p>
                            <p
                                class="mt-1 text-2xl font-semibold"
                                :class="stats.pendingOrders > 0 ? 'text-amber-600' : 'text-slate-900'"
                            >
                                {{ stats.pendingOrders }}
                            </p>
                            <Link :href="route('orders.index')" class="mt-1 inline-block text-xs font-medium text-primary-600 hover:underline">
                                Ver pedidos
                            </Link>
                        </div>
                        <span
                            class="flex h-10 w-10 items-center justify-center rounded-lg"
                            :class="stats.pendingOrders > 0 ? 'bg-amber-50 text-amber-600' : 'bg-primary-50 text-primary-600'"
                        >
                            <Icon name="bag" class="h-5 w-5" />
                        </span>
                    </div>
                </Card>
            </div>

            <!-- Charts -->
            <div v-if="charts.salesTrend || charts.topProducts || charts.workshopByType || charts.ordersByStatus">
                <h3 class="mb-3 text-sm font-semibold text-slate-700">Resultados del sistema</h3>
                <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                    <Card v-if="charts.salesTrend" padded>
                        <div class="flex items-center justify-between">
                            <p class="text-sm font-semibold text-slate-900">Ventas de los últimos 14 días</p>
                            <p class="text-xs text-slate-500">Total: Q {{ salesTrendTotal.toFixed(2) }}</p>
                        </div>
                        <div class="mt-4">
                            <BarChart :data="charts.salesTrend.map((p) => ({ label: p.label, value: p.total }))" color="#ea580c" value-prefix="Q " />
                        </div>
                    </Card>

                    <Card v-if="charts.topProducts" padded>
                        <p class="text-sm font-semibold text-slate-900">Productos más vendidos (30 días)</p>
                        <div class="mt-4 space-y-3">
                            <div v-for="product in charts.topProducts" :key="product.name">
                                <div class="flex items-center justify-between text-xs">
                                    <span class="font-medium text-slate-700">{{ product.name }}</span>
                                    <span class="text-slate-500">Q {{ product.revenue.toFixed(2) }}</span>
                                </div>
                                <div class="mt-1 h-2 w-full overflow-hidden rounded-full bg-slate-100">
                                    <div
                                        class="h-full rounded-full bg-primary-600"
                                        :style="{ width: `${Math.max((product.revenue / maxProductRevenue) * 100, 3)}%` }"
                                    ></div>
                                </div>
                            </div>
                            <p v-if="charts.topProducts.length === 0" class="text-xs text-slate-400">Sin ventas en los últimos 30 días.</p>
                        </div>
                    </Card>

                    <Card v-if="charts.workshopByType" padded>
                        <p class="text-sm font-semibold text-slate-900">Taller: servicios vs. reparaciones</p>
                        <div class="mt-4">
                            <DonutChart :segments="workshopSegments" />
                        </div>
                    </Card>

                    <Card v-if="charts.ordersByStatus" padded>
                        <p class="text-sm font-semibold text-slate-900">Pedidos online por estado</p>
                        <div class="mt-4">
                            <DonutChart :segments="orderStatusSegments" />
                        </div>
                    </Card>
                </div>
            </div>

            <!-- Quick links -->
            <div>
                <div class="mb-3 flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-slate-700">Accesos rápidos</h3>
                    <a
                        :href="route('storefront.index')"
                        target="_blank"
                        rel="noopener"
                        class="inline-flex items-center gap-1 text-xs font-medium text-primary-600 hover:underline"
                    >
                        Ver tienda pública
                        <Icon name="cart" class="h-3.5 w-3.5" />
                    </a>
                </div>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <Link
                        v-for="link in quickLinks"
                        :key="link.name"
                        :href="route(link.href)"
                        class="group"
                    >
                        <Card padded class="h-full transition hover:border-primary-300 hover:shadow-md">
                            <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-primary-50 text-primary-600 group-hover:bg-primary-600 group-hover:text-white">
                                <Icon :name="link.icon" class="h-5 w-5" />
                            </span>
                            <p class="mt-3 text-sm font-semibold text-slate-900">{{ link.name }}</p>
                            <p class="mt-0.5 text-xs text-slate-500">{{ link.description }}</p>
                        </Card>
                    </Link>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
