<template>
<AppLayout>
    <div class="px-4 sm:px-6 lg:px-8 py-8">
        <!-- Header -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
            <div>
                <h2 class="font-heading font-extrabold text-3xl text-gray-900 dark:text-white tracking-tight flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-indigo-600 flex items-center justify-center shadow-lg shadow-indigo-200 dark:shadow-none">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-7.714 2.143L11 21l-2.286-6.857L1 12l7.714-2.143L11 3z"/>
                        </svg>
                    </div>
                    Fonctionnalités Premium
                </h2>
                <p class="text-slate-500 dark:text-slate-400 mt-2 max-w-2xl">
                    Gestion centralisée des accès aux modules avancés pour chaque laboratoire client. 
                    Les modifications sont appliquées en temps réel.
                </p>
            </div>
        </div>

        <!-- Grid of Clients -->
        <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
            <div v-for="client in clients" :key="client.id" 
                 class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden hover:shadow-md transition-shadow">
                
                <div class="p-6">
                    <div class="flex items-start justify-between mb-6">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 rounded-full bg-slate-100 dark:bg-slate-700 flex items-center justify-center text-xl font-bold text-slate-600 dark:text-slate-300">
                                {{ client.name.charAt(0) }}
                            </div>
                            <div>
                                <h3 class="text-lg font-bold text-slate-900 dark:text-white">{{ client.name }}</h3>
                                <div class="flex items-center gap-3 mt-1">
                                    <span :class="[
                                        'inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold uppercase tracking-wider',
                                        client.is_active ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400' : 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-400'
                                    ]">
                                        {{ client.is_active ? 'Compte Actif' : 'Compte Suspendu' }}
                                    </span>
                                    <span class="text-xs text-slate-400 font-medium">
                                        {{ client.users_count }} utilisateur(s)
                                    </span>
                                </div>
                            </div>
                        </div>
                        
                        <Link 
                            :href="route('admin.features.edit', client.id)" 
                            class="p-2 text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 rounded-xl transition-all"
                            title="Configurer"
                        >
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                        </Link>
                    </div>

                    <div class="space-y-3">
                        <div class="text-xs font-bold text-slate-400 uppercase tracking-widest">Fonctionnalités activées</div>
                        <div class="flex flex-wrap gap-2">
                            <template v-if="client.enabled_features?.length">
                                <div v-for="key in client.enabled_features" :key="key" 
                                     class="inline-flex items-center px-3 py-1.5 rounded-lg bg-indigo-50 dark:bg-indigo-900/20 text-indigo-700 dark:text-indigo-300 text-xs font-semibold border border-indigo-100 dark:border-indigo-800/50">
                                    <svg class="w-3.5 h-3.5 mr-1.5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    {{ availableFeatures[key]?.name || key }}
                                </div>
                            </template>
                            <div v-else class="text-sm text-slate-400 italic py-2">
                                Aucune fonctionnalité premium activée.
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="px-6 py-4 bg-slate-50 dark:bg-slate-900/50 border-t border-slate-100 dark:border-slate-700 flex justify-end">
                    <Link 
                        :href="route('admin.features.edit', client.id)" 
                        class="text-sm font-bold text-indigo-600 dark:text-indigo-400 hover:text-indigo-700 dark:hover:text-indigo-300 flex items-center gap-1"
                    >
                        Modifier les accès
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </Link>
                </div>
            </div>

            <!-- Empty State -->
            <div v-if="!clients?.length" class="col-span-full py-20 text-center bg-white dark:bg-slate-800 rounded-3xl border border-dashed border-slate-300 dark:border-slate-700">
                <div class="w-20 h-20 bg-slate-100 dark:bg-slate-700 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-10 h-10 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-slate-900 dark:text-white">Aucun client enregistré</h3>
                <p class="text-slate-500 mt-1">Commencez par ajouter des clients pour gérer leurs accès.</p>
            </div>
        </div>
    </div>
</AppLayout>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

defineProps({
    clients: Array,
    availableFeatures: Object,
});
</script>
