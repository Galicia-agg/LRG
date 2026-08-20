import { computed, onMounted, ref, watch } from 'vue';

const STORAGE_KEY = 'storefront_cart';

export function useCart() {
    const cart = ref([]);

    onMounted(() => {
        try {
            const raw = localStorage.getItem(STORAGE_KEY);
            cart.value = raw ? JSON.parse(raw) : [];
        } catch {
            cart.value = [];
        }
    });

    watch(
        cart,
        (value) => {
            localStorage.setItem(STORAGE_KEY, JSON.stringify(value));
        },
        { deep: true },
    );

    function addToCart(product) {
        const existing = cart.value.find((line) => line.product_id === product.id);

        if (existing) {
            existing.quantity += 1;
        } else {
            cart.value.push({
                product_id: product.id,
                name: product.name,
                image_url: product.images?.[0]?.url ?? null,
                unit_price: product.sale_price,
                unit: product.unit,
                quantity: 1,
            });
        }
    }

    function changeQuantity(line, delta) {
        line.quantity = Math.max(1, line.quantity + delta);
    }

    function removeFromCart(index) {
        cart.value.splice(index, 1);
    }

    function clearCart() {
        cart.value = [];
        localStorage.removeItem(STORAGE_KEY);
    }

    const cartCount = computed(() => cart.value.reduce((sum, line) => sum + line.quantity, 0));
    const cartTotal = computed(() => cart.value.reduce((sum, line) => sum + line.unit_price * line.quantity, 0));

    return { cart, addToCart, changeQuantity, removeFromCart, clearCart, cartCount, cartTotal };
}
