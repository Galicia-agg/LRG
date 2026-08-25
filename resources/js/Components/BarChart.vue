<script setup>
import { computed } from 'vue';

const props = defineProps({
    data: { type: Array, required: true }, // [{ label, value }]
    color: { type: String, default: '#ea580c' },
    height: { type: Number, default: 140 },
    valuePrefix: { type: String, default: '' },
});

const maxValue = computed(() => Math.max(...props.data.map((point) => point.value), 1));

function barHeight(value) {
    return Math.max((value / maxValue.value) * 100, value > 0 ? 3 : 0);
}

function formatValue(value) {
    return `${props.valuePrefix}${value.toFixed(2)}`;
}
</script>

<template>
    <div>
        <div class="flex items-end gap-1" :style="{ height: `${height}px` }">
            <div v-for="(point, index) in data" :key="index" class="flex h-full flex-1 flex-col items-center justify-end">
                <div
                    class="w-full rounded-t transition-all"
                    :style="{ height: `${barHeight(point.value)}%`, backgroundColor: color, minHeight: point.value > 0 ? '2px' : '0' }"
                    :title="`${point.label}: ${formatValue(point.value)}`"
                ></div>
            </div>
        </div>
        <div class="mt-1.5 flex gap-1">
            <div v-for="(point, index) in data" :key="index" class="flex-1 text-center text-[9px] leading-tight text-slate-400">
                {{ point.label }}
            </div>
        </div>
    </div>
</template>
