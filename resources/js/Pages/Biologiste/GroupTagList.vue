<script setup>
defineProps({
    analyses: {
        type: Array,
        default: () => [],
    },
    max: {
        type: Number,
        default: 4,
    },
});
</script>

<template>
    <div class="flex flex-wrap gap-1.5">
        <span
            v-for="a in analyses.slice(0, max)"
            :key="a.id"
            class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-semibold uppercase tracking-wider bg-indigo-50 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-300 border border-indigo-100 dark:border-indigo-800/40"
        >
            {{ (a.designation || 'Analyse').substring(0, 15) }}
        </span>
        <span
            v-if="analyses.length > max"
            class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-semibold bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400"
        >
            +{{ analyses.length - max }}
        </span>
    </div>
</template>
