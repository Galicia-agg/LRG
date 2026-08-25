<script setup>
import { computed, ref, watch } from 'vue';
import { usePage, Link } from '@inertiajs/vue3';
import Icon from '@/Components/Icon.vue';
import { usePermissions } from '@/Composables/usePermissions';

const page = usePage();
const { can } = usePermissions();
const dismissed = ref(new Set());

const messages = computed(() => {
    const flash = page.props.flash ?? {};
    const list = [];

    if (flash.success) {
        list.push({
            id: `success-${flash.success}`,
            tone: 'success',
            text: flash.success,
            receiptUrl: flash.saleId && can('sales.view') ? route('sales.receipt', flash.saleId) : null,
        });
    }
    if (flash.warning) list.push({ id: `warning-${flash.warning}`, tone: 'warning', text: flash.warning });

    return list.filter((m) => !dismissed.value.has(m.id));
});

watch(
    () => page.props.flash,
    () => {
        dismissed.value = new Set();
    },
);

function dismiss(id) {
    dismissed.value = new Set([...dismissed.value, id]);
}
</script>

<template>
    <div v-if="messages.length > 0" class="fixed inset-x-0 top-4 z-[60] flex flex-col items-center gap-2 px-4">
        <div
            v-for="message in messages"
            :key="message.id"
            class="flex w-full max-w-md items-start gap-3 rounded-lg border px-4 py-3 shadow-lg"
            :class="
                message.tone === 'success'
                    ? 'border-emerald-200 bg-emerald-50 text-emerald-800'
                    : 'border-amber-200 bg-amber-50 text-amber-800'
            "
        >
            <Icon
                :name="message.tone === 'success' ? 'check' : 'alert'"
                class="mt-0.5 h-4 w-4 shrink-0"
            />
            <div class="flex-1">
                <p class="text-sm">{{ message.text }}</p>
                <Link
                    v-if="message.receiptUrl"
                    :href="message.receiptUrl"
                    target="_blank"
                    class="mt-1 inline-block text-xs font-semibold underline hover:no-underline"
                >
                    Ver recibo
                </Link>
            </div>
            <button type="button" @click="dismiss(message.id)" class="shrink-0 opacity-60 hover:opacity-100">
                <Icon name="close" class="h-4 w-4" />
            </button>
        </div>
    </div>
</template>
