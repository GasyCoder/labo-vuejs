<script setup>
defineProps({
    stats: {
        type: Object,
        required: true,
    },
    search: {
        type: String,
        default: '',
    },
});

const emit = defineEmits(['update:search']);
</script>

<template>
    <div class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 px-4 py-3">
        <!-- Mini stats row -->
        <div class="flex items-center gap-3 mb-3">
            <div class="flex items-center gap-1.5 text-xs">
                <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                <span class="font-bold text-gray-900 dark:text-white">{{ stats.a_valider || 0 }}</span>
                <span class="text-gray-500 dark:text-gray-400 hidden sm:inline">en attente</span>
            </div>
            <div class="flex items-center gap-1.5 text-xs">
                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                <span class="font-bold text-gray-900 dark:text-white">{{ stats.valide || 0 }}</span>
                <span class="text-gray-500 dark:text-gray-400 hidden sm:inline">validé(s)</span>
            </div>
            <div class="flex items-center gap-1.5 text-xs">
                <span class="w-2 h-2 rounded-full bg-red-500"></span>
                <span class="font-bold text-gray-900 dark:text-white">{{ stats.a_refaire || 0 }}</span>
                <span class="text-gray-500 dark:text-gray-400 hidden sm:inline">à refaire</span>
            </div>
        </div>

        <!-- Search -->
        <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
            </div>
            <input
                type="text"
                :value="search"
                @input="emit('update:search', $event.target.value)"
                placeholder="Rechercher..."
                class="w-full h-10 pl-9 pr-4 border border-gray-200 dark:border-gray-600 rounded-lg text-sm text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-gray-50 dark:bg-gray-700/50 transition-colors"
                aria-label="Rechercher par référence, patient ou prescripteur"
            >
        </div>
    </div>
</template>
