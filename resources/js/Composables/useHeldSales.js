import { ref, watch } from 'vue';

export function useHeldSales(userId) {
    const storageKey = `pos_held_sales_${userId}`;
    const heldSales = ref([]);

    try {
        const stored = localStorage.getItem(storageKey);
        heldSales.value = stored ? JSON.parse(stored) : [];
    } catch {
        heldSales.value = [];
    }

    watch(
        heldSales,
        (value) => {
            localStorage.setItem(storageKey, JSON.stringify(value));
        },
        { deep: true },
    );

    function holdSale(snapshot) {
        heldSales.value.push({
            id: `${Date.now()}-${Math.random().toString(36).slice(2, 8)}`,
            heldAt: new Date().toISOString(),
            ...snapshot,
        });
    }

    function resumeSale(id) {
        const index = heldSales.value.findIndex((sale) => sale.id === id);
        if (index === -1) return null;

        const [sale] = heldSales.value.splice(index, 1);
        return sale;
    }

    function discardSale(id) {
        heldSales.value = heldSales.value.filter((sale) => sale.id !== id);
    }

    return { heldSales, holdSale, resumeSale, discardSale };
}
