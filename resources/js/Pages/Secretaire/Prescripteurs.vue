<template>
<AppLayout>
    <div class="p-6">
        <!-- Header avec statistiques -->
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6 mb-8">
            <div>
                <h1 class="text-3xl font-heading font-bold text-gray-900 dark:text-white">Gestion des Prescripteurs</h1>
                <p class="text-gray-600 dark:text-gray-400 mt-2">Consultez et gérez les prescripteurs associés à votre laboratoire</p>
            </div>
            
            <!-- Statistiques améliorées -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 max-w-md lg:max-w-none">
                <div class="bg-gradient-to-r from-blue-50 to-blue-100 dark:from-blue-900/20 dark:to-blue-800/20 px-3 py-2 rounded-xl border border-blue-200 dark:border-blue-700">
                    <div class="text-xs font-medium text-blue-600 dark:text-blue-400 uppercase tracking-wide">Total</div>
                    <div class="text-xl font-bold text-blue-800 dark:text-blue-300">{{ totalPrescripteurs }}</div>
                </div>
                <div class="bg-gradient-to-r from-green-50 to-green-100 dark:from-green-900/20 dark:to-green-800/20 px-3 py-2 rounded-xl border border-green-200 dark:border-green-700">
                    <div class="text-xs font-medium text-green-600 dark:text-green-400 uppercase tracking-wide">Actifs</div>
                    <div class="text-xl font-bold text-green-800 dark:text-green-300">{{ prescripteursActifs }}</div>
                </div>
                <div class="bg-gradient-to-r from-purple-50 to-purple-100 dark:from-purple-900/20 dark:to-purple-800/20 px-3 py-2 rounded-xl border border-purple-200 dark:border-purple-700">
                    <div class="text-xs font-medium text-purple-600 dark:text-purple-400 uppercase tracking-wide">Prescriptions</div>
                    <div class="text-xl font-bold text-purple-800 dark:text-purple-300">{{ formatNumber(totalPrescriptionsCommissionnables) }}</div>
                </div>
                <div class="bg-gradient-to-r from-yellow-50 to-yellow-100 dark:from-yellow-900/20 dark:to-yellow-800/20 px-3 py-2 rounded-xl border border-yellow-200 dark:border-yellow-700">
                    <div class="text-xs font-medium text-yellow-600 dark:text-yellow-400 uppercase tracking-wide">Commissions</div>
                    <div class="text-xl font-bold text-yellow-800 dark:text-yellow-300">{{ formatNumber(totalCommissions) }} Ar</div>
                </div>
            </div>
        </div>

        <!-- Filtres et recherche -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
                <!-- Recherche -->
                <div class="lg:col-span-2">
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Rechercher un prescripteur</label>
                    <div class="relative">
                        <input 
                            v-model="form.search"
                            @input="debouncedUpdateFilters"
                            type="text" 
                            placeholder="Nom, prénom, grade, spécialité, téléphone, email..."
                            class="w-full pl-12 pr-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-blue-500 dark:focus:border-blue-400 transition-all"
                        >
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <svg class="w-5 h-5 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Filtre statut -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Statut</label>
                    <select v-model="form.statutFilter" @change="updateFilters" class="w-full py-3 px-4 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-blue-500 transition-all">
                        <option value="">Tous</option>
                        <option value="actif">Actif</option>
                        <option value="inactif">Inactif</option>
                    </select>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                <div class="flex items-center space-x-4">
                    <label class="text-sm font-medium text-gray-600 dark:text-gray-400">Afficher :</label>
                    <select v-model="form.perPage" @change="updateFilters" class="py-2 px-3 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-sm text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                        <option value="5">5</option>
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                    </select>
                    <span class="text-sm text-gray-600 dark:text-gray-400">par page</span>
                </div>

                <div class="flex items-center space-x-3">
                    <button 
                        @click="resetFilters"
                        class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 border border-gray-300 dark:border-gray-600 rounded-lg transition-colors"
                    >
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        Réinitialiser
                    </button>

                    <button 
                        v-if="canManagePrescripteurs"
                        @click="openPrescripteurModal()"
                        class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 border border-transparent rounded-lg shadow-sm focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors"
                    >
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                        Nouveau prescripteur
                    </button>

                    <a 
                        :href="route('secretaire.prescripteurs.export', form)"
                        class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-green-600 hover:bg-green-700 border border-transparent rounded-lg shadow-sm focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition-colors"
                    >
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        Exporter
                    </a>
                </div>
            </div>
        </div>

        <!-- Table des prescripteurs -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900/50">
                        <tr>
                            <th class="px-6 py-4 text-left">
                                <button @click="sortBy('nom')" class="group flex items-center text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider hover:text-gray-700 dark:hover:text-gray-300 transition-colors">
                                    Prescripteur
                                    <span v-if="form.sortField === 'nom'" class="ml-2">
                                        <svg v-if="form.sortDirection === 'asc'" class="w-4 h-4 text-blue-500" fill="currentColor" viewBox="0 0 20 20"><path d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"/></svg>
                                        <svg v-else class="w-4 h-4 text-blue-500" fill="currentColor" viewBox="0 0 20 20"><path d="M14.707 12.707a1 1 0 01-1.414 0L10 9.414l-3.293 3.293a1 1 0 01-1.414-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 010 1.414z"/></svg>
                                    </span>
                                </button>
                            </th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Type
                            </th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Contact
                            </th>
                            <th class="px-6 py-4 text-left">
                                <button @click="sortBy('prescriptions_commissionnables')" class="group flex items-center text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider hover:text-gray-700 dark:hover:text-gray-300 transition-colors">
                                    Prescriptions
                                    <span v-if="form.sortField === 'prescriptions_commissionnables'" class="ml-2">
                                        <svg v-if="form.sortDirection === 'asc'" class="w-4 h-4 text-blue-500" fill="currentColor" viewBox="0 0 20 20"><path d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"/></svg>
                                        <svg v-else class="w-4 h-4 text-blue-500" fill="currentColor" viewBox="0 0 20 20"><path d="M14.707 12.707a1 1 0 01-1.414 0L10 9.414l-3.293 3.293a1 1 0 01-1.414-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 010 1.414z"/></svg>
                                    </span>
                                </button>
                            </th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Statut
                            </th>
                            <th class="px-6 py-4 text-center text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        <template v-if="prescripteurs.data.length > 0">
                            <tr v-for="prescripteur in prescripteurs.data" :key="prescripteur.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 w-10 h-10">
                                            <div :class="[
                                                prescripteur.status === 'Professeur' 
                                                    ? 'bg-gradient-to-br from-purple-400 to-purple-600 dark:from-purple-500 dark:to-purple-700' 
                                                    : 'bg-gradient-to-br from-blue-400 to-blue-600 dark:from-blue-500 dark:to-blue-700',
                                                'w-10 h-10 rounded-full flex items-center justify-center shadow-lg'
                                            ]">
                                                <span class="text-white font-bold text-sm">
                                                    {{ getInitials(prescripteur) }}
                                                </span>
                                            </div>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-bold text-gray-900 dark:text-white">
                                                {{ prescripteur.nom_complet }}
                                            </div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                                ID: #{{ prescripteur.id }}
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex flex-col space-y-2">
                                        <div>
                                            <span :class="[
                                                prescripteur.status === 'Professeur'
                                                    ? 'bg-purple-100 dark:bg-purple-900/30 text-purple-800 dark:text-purple-300'
                                                    : 'bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300',
                                                'inline-flex items-center px-3 py-1 rounded-full text-xs font-medium'
                                            ]">
                                                <svg class="w-3 h-3 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                                </svg>
                                                {{ prescripteur.status }}
                                            </span>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900 dark:text-white">
                                        {{ prescripteur.telephone || 'Pas de téléphone' }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center space-x-2">
                                        <span class="inline-flex items-center px-3 py-1.5 bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300 text-sm font-semibold rounded-lg">
                                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                            </svg>
                                            {{ prescripteur.prescriptions_commissionnables }}
                                        </span>
                                        <span v-if="prescripteur.total_prescriptions !== prescripteur.prescriptions_commissionnables" class="text-xs text-gray-500 dark:text-gray-400">
                                            / {{ prescripteur.total_prescriptions }} total
                                        </span>
                                        
                                        <div class="flex flex-col">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400">
                                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                                                </svg>
                                                {{ prescripteur.commission_pourcentage }}% commission
                                            </span>
                                            
                                            <div v-if="prescripteur.brute_mensuel < prescripteur.commission_quota" class="mt-1 text-[10px] text-orange-500 font-medium whitespace-nowrap" :title="`Quota: ${formatNumber(prescripteur.commission_quota)} Ar`">
                                                Quota: {{ formatNumber(prescripteur.brute_mensuel) }} / {{ formatNumber(prescripteur.commission_quota) }}
                                            </div>
                                            <div v-else class="mt-1 text-[10px] text-green-500 font-medium whitespace-nowrap">
                                                Quota atteint ({{ formatNumber(prescripteur.brute_mensuel) }} Ar)
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span v-if="prescripteur.is_active" class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300">
                                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                        </svg>
                                        Actif
                                    </span>
                                    <span v-else class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300">
                                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                        </svg>
                                        Inactif
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <div class="flex justify-center space-x-2">
                                        <button 
                                            @click="showCommissions(prescripteur.id)"
                                            class="p-2 text-yellow-600 dark:text-yellow-400 hover:text-yellow-800 dark:hover:text-yellow-300 hover:bg-yellow-100 dark:hover:bg-yellow-900/30 rounded-lg transition-colors"
                                            title="Voir les commissions"
                                        >
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                        </button>
                                        
                                        <template v-if="canManagePrescripteurs">
                                            <button 
                                                @click="openPrescripteurModal(prescripteur)"
                                                class="p-2 text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 hover:bg-blue-100 dark:hover:bg-blue-900/30 rounded-lg transition-colors" 
                                                title="Modifier"
                                            >
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                                </svg>
                                            </button>

                                            <button 
                                                @click="toggleStatus(prescripteur)"
                                                :class="[
                                                    prescripteur.is_active 
                                                        ? 'text-orange-600 dark:text-orange-400 hover:text-orange-800 dark:hover:text-orange-300 hover:bg-orange-100 dark:hover:bg-orange-900/30' 
                                                        : 'text-green-600 dark:text-green-400 hover:text-green-800 dark:hover:text-green-300 hover:bg-green-100 dark:hover:bg-green-900/30',
                                                    'p-2 rounded-lg transition-colors'
                                                ]" 
                                                :title="prescripteur.is_active ? 'Désactiver' : 'Activer'"
                                            >
                                                <svg v-if="prescripteur.is_active" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path>
                                                </svg>
                                                <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                </svg>
                                            </button>

                                            <button 
                                                @click="confirmDelete(prescripteur)"
                                                class="p-2 text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-300 hover:bg-red-100 dark:hover:bg-red-900/30 rounded-lg transition-colors" 
                                                title="Supprimer"
                                            >
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                </svg>
                                            </button>
                                        </template>
                                    </div>
                                </td>
                            </tr>
                        </template>
                        <template v-else>
                            <tr>
                                <td colspan="6" class="px-6 py-16 text-center">
                                    <div class="flex flex-col items-center">
                                        <div class="w-16 h-16 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mb-4">
                                            <svg class="w-8 h-8 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                            </svg>
                                        </div>
                                        <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2">Aucun prescripteur trouvé</h3>
                                        <p class="text-gray-500 dark:text-gray-400 max-w-sm">
                                            <template v-if="form.search || form.statutFilter">
                                                Essayez de modifier vos critères de recherche pour voir plus de résultats.
                                            </template>
                                            <template v-else>
                                                Votre base de prescripteurs est vide pour le moment.
                                            </template>
                                        </p>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="px-6 py-4 bg-gray-50 dark:bg-gray-900/50 border-t border-gray-200 dark:border-gray-700">
                <Pagination :links="prescripteurs.links" />
            </div>
        </div>
    </div>

    <!-- Modals -->
    <ModalFormPrescripteur 
        :show="showModal" 
        :prescripteur="selectedPrescripteur"
        :grades="grades"
        :status-options="statusOptions"
        :default-commission-quota="defaultCommissionQuota"
        :default-commission-pourcentage="defaultCommissionPourcentage"
        @close="showModal = false"
    />

    <ModalDeletePrescripteur 
        :show="showDeleteModalState"
        :prescripteur="prescripteurToDelete"
        @close="showDeleteModalState = false"
    />

    <ModalCommissionPrescripteur
        :show="showCommissionModalState"
        :prescripteur="prescripteurForCommission"
        @close="showCommissionModalState = false"
    />
</AppLayout>
</template>

<script setup>
import { ref, watch, computed } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import ModalFormPrescripteur from './Modals/ModalFormPrescripteur.vue';
import ModalDeletePrescripteur from './Modals/ModalDeletePrescripteur.vue';
import ModalCommissionPrescripteur from './Modals/ModalCommissionPrescripteur.vue';
import { debounce } from 'lodash';

const props = defineProps({
    prescripteurs: Object,
    grades: Object,
    statusOptions: Object,
    filters: Object,
    totalPrescripteurs: Number,
    prescripteursActifs: Number,
    totalCommissions: [Number, String],
    totalPrescriptionsCommissionnables: [Number, String],
    defaultCommissionQuota: [Number, String],
    defaultCommissionPourcentage: [Number, String],
});

const page = usePage();
// Permission check using Spatie permissions shared via HandleInertiaRequests
const canManagePrescripteurs = computed(() => {
    const user = page.props.auth.user;
    if (!user) return false;
    if (user.isAdmin) return true;
    const perms = user.permissions || [];
    return perms.includes('prescripteurs.gerer');
});

const form = ref({
    search: props.filters.search || '',
    statutFilter: props.filters.statutFilter || '',
    sortField: props.filters.sortField || 'nom',
    sortDirection: props.filters.sortDirection || 'asc',
    perPage: props.filters.perPage || 10,
});

// Modal states
const showModal = ref(false);
const showDeleteModalState = ref(false);
const showCommissionModalState = ref(false);

const selectedPrescripteur = ref(null);
const prescripteurToDelete = ref(null);
const prescripteurForCommission = ref(null);

const formatNumber = (num) => {
    if (num === null || num === undefined) return '0';
    return Number(num).toLocaleString('fr-FR');
};

const getInitials = (prescripteur) => {
    const fn = String(prescripteur.nom || '').charAt(0).toUpperCase();
    const ln = String(prescripteur.prenom || 'X').charAt(0).toUpperCase();
    return `${fn}${ln}`;
};

const updateFilters = () => {
    router.get(route('secretaire.prescripteurs.index'), form.value, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

const debouncedUpdateFilters = debounce(updateFilters, 300);

const resetFilters = () => {
    form.value.search = '';
    form.value.statutFilter = '';
    form.value.sortField = 'nom';
    form.value.sortDirection = 'asc';
    updateFilters();
};

const sortBy = (field) => {
    if (form.value.sortField === field) {
        form.value.sortDirection = form.value.sortDirection === 'asc' ? 'desc' : 'asc';
    } else {
        form.value.sortField = field;
        form.value.sortDirection = 'asc';
    }
    updateFilters();
};

// CRUD Actions
const openPrescripteurModal = (prescripteur = null) => {
    selectedPrescripteur.value = prescripteur;
    showModal.value = true;
};

const confirmDelete = (prescripteur) => {
    prescripteurToDelete.value = prescripteur;
    showDeleteModalState.value = true;
};

const toggleStatus = (prescripteur) => {
    router.post(route('secretaire.prescripteurs.toggle-status', prescripteur.id), {}, {
        preserveScroll: true,
    });
};

// Commissions
const showCommissions = (prescripteurId) => {
    const prescripteur = props.prescripteurs.data.find(p => p.id === prescripteurId);
    if (prescripteur) {
        prescripteurForCommission.value = prescripteur;
        showCommissionModalState.value = true;
    }
};
</script>
