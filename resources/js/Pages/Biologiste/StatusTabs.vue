<script setup>
defineProps({
    currentTab: {
        type: String,
        required: true,
    },
    stats: {
        type: Object,
        required: true,
    },
});

const emit = defineEmits(['change']);

const tabs = [
    { key: 'a_valider', label: 'À valider', color: 'amber' },
    { key: 'valide', label: 'Validé', color: 'emerald' },
    { key: 'a_refaire', label: 'À refaire', color: 'red' },
];

const tabClasses = {
    amber: {
        active: 'border-amber-500 text-amber-700 dark:text-amber-400 bg-amber-50/80 dark:bg-amber-900/20',
        dot: 'bg-amber-500',
        badge: 'bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-300',
    },
    emerald: {
        active: 'border-emerald-500 text-emerald-700 dark:text-emerald-400 bg-emerald-50/80 dark:bg-emerald-900/20',
        dot: 'bg-emerald-500',
        badge: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300',
    },
    red: {
        active: 'border-red-500 text-red-700 dark:text-red-400 bg-red-50/80 dark:bg-red-900/20',
        dot: 'bg-red-500',
        badge: 'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300',
    },
};
</script>

<template>
    <div class="sticky top-0 z-20 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
        <nav class="flex" role="tablist" aria-label="Onglets de validation">
            <button
                v-for="t in tabs"
                :key="t.key"
                @click="emit('change', t.key)"
                :aria-selected="currentTab === t.key"
                :aria-controls="'panel-' + t.key"
                role="tab"
                class="flex-1 relative py-3 px-2 sm:px-4 text-center text-xs sm:text-sm font-semibold border-b-[3px] transition-all duration-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-1 focus-visible:ring-blue-500"
                :class="currentTab === t.key
                    ? tabClasses[t.color].active + ' border-b-[3px]'
                    : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50'"
            >
                <div class="flex items-center justify-center gap-1.5">
                    <span class="w-2 h-2 rounded-full flex-shrink-0" :class="tabClasses[t.color].dot"></span>
                    <span class="hidden sm:inline">{{ t.label }}</span>
                    <span class="sm:hidden">{{ t.key === 'a_valider' ? 'Valider' : (t.key === 'valide' ? 'Validé' : 'Refaire') }}</span>
                    <span
                        class="text-[10px] sm:text-xs font-bold px-1.5 sm:px-2 py-0.5 rounded-full min-w-[20px]"
                        :class="currentTab === t.key ? tabClasses[t.color].badge : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400'"
                    >
                        {{ stats[t.key] || 0 }}
                    </span>
                </div>
            </button>
        </nav>
    </div>
</template>
