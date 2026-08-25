<script setup>
import { computed } from 'vue';

const props = defineProps({
    segments: { type: Array, required: true }, // [{ label, value, color }]
    size: { type: Number, default: 128 },
    strokeWidth: { type: Number, default: 18 },
});

const total = computed(() => props.segments.reduce((sum, segment) => sum + segment.value, 0));
const radius = computed(() => (props.size - props.strokeWidth) / 2);
const circumference = computed(() => 2 * Math.PI * radius.value);

const arcs = computed(() => {
    let offset = 0;
    return props.segments
        .filter((segment) => segment.value > 0)
        .map((segment) => {
            const fraction = total.value > 0 ? segment.value / total.value : 0;
            const length = fraction * circumference.value;
            const arc = {
                ...segment,
                fraction,
                dasharray: `${length} ${circumference.value - length}`,
                dashoffset: -offset,
            };
            offset += length;
            return arc;
        });
});
</script>

<template>
    <div class="flex items-center gap-4">
        <svg :width="size" :height="size" :viewBox="`0 0 ${size} ${size}`">
            <circle
                v-if="total === 0"
                :cx="size / 2"
                :cy="size / 2"
                :r="radius"
                fill="none"
                stroke="#e2e8f0"
                :stroke-width="strokeWidth"
            />
            <g :transform="`rotate(-90 ${size / 2} ${size / 2})`">
                <circle
                    v-for="(arc, index) in arcs"
                    :key="index"
                    :cx="size / 2"
                    :cy="size / 2"
                    :r="radius"
                    fill="none"
                    :stroke="arc.color"
                    :stroke-width="strokeWidth"
                    :stroke-dasharray="arc.dasharray"
                    :stroke-dashoffset="arc.dashoffset"
                >
                    <title>{{ arc.label }}: {{ arc.value }} ({{ (arc.fraction * 100).toFixed(0) }}%)</title>
                </circle>
            </g>
        </svg>
        <div class="space-y-1.5">
            <div v-for="(segment, index) in segments" :key="index" class="flex items-center gap-1.5 text-xs">
                <span class="h-2.5 w-2.5 shrink-0 rounded-full" :style="{ backgroundColor: segment.color }"></span>
                <span class="text-slate-600">{{ segment.label }}</span>
                <span class="font-semibold text-slate-900">{{ segment.value }}</span>
            </div>
            <p v-if="total === 0" class="text-xs text-slate-400">Sin datos</p>
        </div>
    </div>
</template>
