<template>
<AppLayout>
    <div class="px-4 sm:px-6 lg:px-8 py-6 max-w-5xl mx-auto">
        <!-- Header -->
        <div class="mb-6">
            <Link :href="route('admin.features.index')" class="inline-flex items-center text-sm text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300 mb-2">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Retour aux clients
            </Link>
            <h2 class="font-heading font-extrabold text-2xl text-gray-900 dark:text-white tracking-tight">
                Fonctionnalités Premium : {{ client.name }}
            </h2>
            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                Activez ou désactivez les fonctionnalités spécifiques pour l'espace de travail de ce client.
            </p>
        </div>

        <!-- Features Form -->
        <form @submit.prevent="submit" class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="p-6">
                <div class="space-y-6">
                    <div v-for="(feature, index) in form.features" :key="feature.key" 
                         class="flex items-start justify-between p-4 rounded-lg bg-gray-50 dark:bg-gray-900/50 border border-gray-100 dark:border-gray-700">
                        <div class="flex-1 pr-6">
                            <h3 class="text-base font-semibold text-gray-900 dark:text-white">{{ feature.name }}</h3>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ feature.description }}</p>
                            <div class="mt-2 text-xs font-mono text-gray-400">Key: {{ feature.key }}</div>
                        </div>
                        <div class="flex items-center h-full pt-1">
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" v-model="feature.is_enabled" class="sr-only peer">
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-indigo-300 dark:peer-focus:ring-indigo-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-indigo-600"></div>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="px-6 py-4 bg-gray-50 dark:bg-gray-900/50 border-t border-gray-200 dark:border-gray-700 flex justify-end gap-3">
                <Link :href="route('admin.features.index')" class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Annuler
                </Link>
                <button type="submit" :disabled="form.processing" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 transition-colors">
                    <svg v-if="form.processing" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                    Enregistrer les modifications
                </button>
            </div>
        </form>
    </div>
</AppLayout>
</template>

<script setup>
import { useForm, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    client: Object,
    features: Array,
});

const form = useForm({
    features: JSON.parse(JSON.stringify(props.features)),
});

const submit = () => {
    form.put(route('admin.features.update', props.client.id), {
        preserveScroll: true,
    });
};
</script>
