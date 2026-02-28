<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';

const props = defineProps({
    modelValue: [String, Number],
    options: {
        type: Array,
        default: () => []
    },
    placeholder: {
        type: String,
        default: 'Rechercher...'
    },
    labelKey: {
        type: String,
        default: 'label'
    },
    valueKey: {
        type: String,
        default: 'id'
    },
    secondaryKey: {
        type: String,
        default: null
    },
    error: String,
    disabled: Boolean
});

const emit = defineEmits(['update:modelValue']);

const isOpen = ref(false);
const search = ref('');
const container = ref(null);

const selectedOption = computed(() => {
    return props.options.find(opt => opt[props.valueKey] === props.modelValue);
});

const filteredOptions = computed(() => {
    if (!search.value) return props.options;
    
    const query = search.value.toLowerCase();
    return props.options.filter(opt => {
        const label = String(opt[props.labelKey] || '').toLowerCase();
        const secondary = props.secondaryKey ? String(opt[props.secondaryKey] || '').toLowerCase() : '';
        return label.includes(query) || secondary.includes(query);
    });
});

const toggleDropdown = () => {
    if (props.disabled) return;
    isOpen.value = !isOpen.value;
    if (isOpen.value) {
        search.value = '';
    }
};

const selectOption = (option) => {
    emit('update:modelValue', option[props.valueKey]);
    isOpen.value = false;
    search.value = '';
};

const handleClickOutside = (event) => {
    if (container.value && !container.value.contains(event.target)) {
        isOpen.value = false;
    }
};

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
});
</script>

<template>
    <div class="relative" ref="container">
        <div
            @click="toggleDropdown"
            class="flex w-full cursor-pointer items-center justify-between rounded-xl border border-slate-200 bg-white py-2.5 pl-10 pr-10 text-sm text-slate-900 transition hover:border-slate-300 focus:border-primary-500 focus:outline-none focus:ring-4 focus:ring-primary-600/15 dark:border-slate-600 dark:bg-slate-700 dark:text-slate-100 dark:hover:border-slate-500"
            :class="{ 'opacity-50 cursor-not-allowed': disabled, 'border-red-500 ring-red-500/10': error }"
        >
            <span v-if="selectedOption" class="truncate">
                {{ selectedOption[labelKey] }}
                <span v-if="secondaryKey && selectedOption[secondaryKey]" class="ml-1 text-xs text-slate-500 dark:text-slate-400">
                    ({{ selectedOption[secondaryKey] }})
                </span>
            </span>
            <span v-else class="text-slate-400 dark:text-slate-500 truncate">
                {{ placeholder }}
            </span>
            
            <div class="absolute inset-y-0 right-0 flex items-center pr-3.5">
                <em class="ni ni-chevron-down text-xs text-slate-400 dark:text-slate-500 transition-transform duration-200" :class="{ 'rotate-180': isOpen }"></em>
            </div>
        </div>

        <div
            v-if="isOpen"
            class="absolute z-[100] mt-2 w-full overflow-hidden rounded-xl border border-slate-200 bg-white shadow-xl animate-in fade-in slide-in-from-top-2 duration-200 dark:border-slate-700 dark:bg-slate-800"
        >
            <div class="border-b border-slate-100 p-2 dark:border-slate-700">
                <div class="relative">
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                        <em class="ni ni-search text-slate-400 dark:text-slate-500"></em>
                    </div>
                    <input
                        v-model="search"
                        type="text"
                        class="w-full rounded-lg border-0 bg-slate-50 py-2 pl-9 pr-3 text-sm focus:ring-0 dark:bg-slate-900 dark:text-slate-100"
                        :placeholder="placeholder"
                        @click.stop
                    >
                </div>
            </div>

            <div class="max-h-60 overflow-y-auto">
                <div
                    v-for="option in filteredOptions"
                    :key="option[valueKey]"
                    @click="selectOption(option)"
                    class="flex cursor-pointer items-center justify-between px-4 py-2.5 text-sm transition-colors hover:bg-slate-50 dark:hover:bg-slate-700/50"
                    :class="{ 'bg-primary-50 text-primary-700 dark:bg-primary-900/20 dark:text-primary-300': option[valueKey] === modelValue }"
                >
                    <div class="min-w-0">
                        <div class="truncate font-medium">{{ option[labelKey] }}</div>
                        <div v-if="secondaryKey && option[secondaryKey]" class="truncate text-xs text-slate-500 dark:text-slate-400">
                            {{ option[secondaryKey] }}
                        </div>
                    </div>
                    <em v-if="option[valueKey] === modelValue" class="ni ni-check text-primary-600 dark:text-primary-400"></em>
                </div>
                
                <div v-if="filteredOptions.length === 0" class="px-4 py-8 text-center text-sm text-slate-500 dark:text-slate-400">
                    <em class="ni ni-search mb-2 block text-xl opacity-20"></em>
                    Aucun resultat trouvé
                </div>
            </div>
        </div>
    </div>
</template>
