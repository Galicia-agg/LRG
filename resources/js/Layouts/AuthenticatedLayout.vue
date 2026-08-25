<script setup>
import { computed, ref } from 'vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import FlashMessages from '@/Components/FlashMessages.vue';
import Icon from '@/Components/Icon.vue';
import { usePermissions } from '@/Composables/usePermissions';
import { Link } from '@inertiajs/vue3';

const sidebarOpen = ref(false);
const { canAny } = usePermissions();

const allNavigation = [
    { name: 'Dashboard', href: 'dashboard', icon: 'home', current: 'dashboard', permissions: null },
    { name: 'Punto de venta', href: 'pos.create', icon: 'cart', current: ['pos.*', 'cash-sessions.*'], permissions: ['sales.create'] },
    { name: 'Ventas', href: 'sales.index', icon: 'receipt', current: 'sales.index', permissions: ['sales.view'] },
    { name: 'Cotizaciones', href: 'quotes.index', icon: 'document', current: 'quotes.*', permissions: ['quotes.manage'] },
    { name: 'Taller', href: 'workshop.index', icon: 'wrench', current: 'workshop.*', permissions: ['workshop.manage'] },
    { name: 'Vehículos', href: 'vehicles.index', icon: 'car', current: 'vehicles.*', permissions: ['workshop.manage'] },
    { name: 'Mecánicos', href: 'mechanics.index', icon: 'users', current: 'mechanics.*', permissions: ['workshop.manage'] },
    { name: 'Fallas comunes', href: 'common-failures.index', icon: 'alert', current: 'common-failures.*', permissions: ['workshop.manage'] },
    { name: 'Servicios comunes', href: 'common-services.index', icon: 'check', current: 'common-services.*', permissions: ['workshop.manage'] },
    { name: 'Pedidos online', href: 'orders.index', icon: 'bag', current: 'orders.index', permissions: ['orders.manage'] },
    { name: 'Productos', href: 'products.index', icon: 'box', current: 'products.*', permissions: ['products.view', 'products.manage'] },
    { name: 'Alertas', href: 'alerts.index', icon: 'bell', current: 'alerts.*', permissions: ['products.view', 'products.manage'] },
    { name: 'Categorías', href: 'categories.index', icon: 'tag', current: 'categories.*', permissions: ['categories.manage'] },
    { name: 'Proveedores', href: 'suppliers.index', icon: 'truck', current: 'suppliers.*', permissions: ['suppliers.manage'] },
    { name: 'Clientes', href: 'customers.index', icon: 'users', current: 'customers.*', permissions: ['customers.manage'] },
    { name: 'Configuración', href: 'settings.index', icon: 'gear', current: 'settings.*', permissions: ['settings.manage'] },
];

const navigation = computed(() =>
    allNavigation.filter((item) => !item.permissions || canAny(item.permissions)),
);

function isCurrent(pattern) {
    const patterns = Array.isArray(pattern) ? pattern : [pattern];
    return patterns.some((p) => route().current(p));
}

function initials(name) {
    return (name ?? '')
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part[0]?.toUpperCase())
        .join('');
}
</script>

<template>
    <div class="min-h-screen bg-slate-50">
        <FlashMessages />

        <!-- Mobile sidebar backdrop -->
        <div
            v-show="sidebarOpen"
            class="fixed inset-0 z-40 bg-slate-900/50 lg:hidden"
            @click="sidebarOpen = false"
        />

        <!-- Sidebar -->
        <aside
            class="fixed inset-y-0 left-0 z-50 flex w-72 flex-col bg-primary-900 transition-transform duration-200 ease-in-out lg:translate-x-0"
            :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
        >
            <div class="flex h-16 shrink-0 items-center justify-between px-6">
                <Link :href="route('dashboard')" class="flex items-center gap-3">
                    <ApplicationLogo class="h-9 w-9 shrink-0" />
                    <span class="text-sm font-semibold leading-tight text-white">
                        Motorepuestos Galicia
                    </span>
                </Link>
                <button
                    type="button"
                    class="text-primary-200 hover:text-white lg:hidden"
                    @click="sidebarOpen = false"
                >
                    <Icon name="close" class="h-6 w-6" />
                </button>
            </div>

            <nav class="flex-1 space-y-1 overflow-y-auto px-3 py-4">
                <Link
                    v-for="item in navigation"
                    :key="item.name"
                    :href="route(item.href)"
                    class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition duration-150 ease-in-out"
                    :class="
                        isCurrent(item.current)
                            ? 'bg-white/10 text-white'
                            : 'text-primary-200 hover:bg-white/5 hover:text-white'
                    "
                >
                    <Icon :name="item.icon" class="h-5 w-5 shrink-0" />
                    {{ item.name }}
                </Link>
            </nav>

            <div class="border-t border-white/10 px-6 py-4 text-xs text-primary-300">
                &copy; {{ new Date().getFullYear() }} Lubricantes y Motorepuestos Galicia
            </div>
        </aside>

        <!-- Content column -->
        <div class="flex min-h-screen flex-col lg:pl-72">
            <!-- Topbar -->
            <header class="sticky top-0 z-30 border-b border-slate-200 bg-white/95 backdrop-blur">
                <div class="flex h-16 items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">
                    <div class="flex min-w-0 items-center gap-4">
                        <button
                            type="button"
                            class="text-slate-500 hover:text-slate-700 lg:hidden"
                            @click="sidebarOpen = true"
                        >
                            <Icon name="menu" class="h-6 w-6" />
                        </button>
                        <div v-if="$slots.header" class="min-w-0">
                            <slot name="header" />
                        </div>
                    </div>

                    <Link
                        v-if="$page.props.alertsCount > 0"
                        :href="route('alerts.index')"
                        class="relative flex h-9 w-9 items-center justify-center rounded-full text-slate-500 hover:bg-slate-100 hover:text-slate-700"
                        title="Alertas de inventario"
                    >
                        <Icon name="bell" class="h-5 w-5" />
                        <span
                            class="absolute -right-0.5 -top-0.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-red-600 px-1 text-[10px] font-bold text-white"
                        >
                            {{ $page.props.alertsCount }}
                        </span>
                    </Link>

                    <Dropdown align="right" width="48">
                        <template #trigger>
                            <button
                                type="button"
                                class="flex items-center gap-2 rounded-full py-1 pl-1 pr-2 transition hover:bg-slate-100 focus:outline-none focus:ring-2 focus:ring-primary-500"
                            >
                                <span
                                    class="flex h-8 w-8 items-center justify-center rounded-full bg-primary-100 text-xs font-semibold text-primary-700"
                                >
                                    {{ initials($page.props.auth.user.name) }}
                                </span>
                                <span class="hidden text-sm font-medium text-slate-700 sm:inline">
                                    {{ $page.props.auth.user.name }}
                                </span>
                                <Icon name="chevronDown" class="hidden h-4 w-4 text-slate-400 sm:inline" />
                            </button>
                        </template>

                        <template #content>
                            <DropdownLink :href="route('profile.edit')">
                                Mi perfil
                            </DropdownLink>
                            <DropdownLink :href="route('logout')" method="post" as="button">
                                Cerrar sesión
                            </DropdownLink>
                        </template>
                    </Dropdown>
                </div>
            </header>

            <!-- Page Content -->
            <main class="flex-1">
                <slot />
            </main>
        </div>
    </div>
</template>
