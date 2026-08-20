import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

export function usePermissions() {
    const page = usePage();

    const permissions = computed(() => page.props.auth?.permissions ?? []);

    function can(permission) {
        return permissions.value.includes(permission);
    }

    function canAny(list) {
        return list.some((permission) => permissions.value.includes(permission));
    }

    return { permissions, can, canAny };
}
