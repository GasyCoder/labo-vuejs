<template>
<AppLayout>
    <div class="p-6">
        <!-- HEADER -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800 dark:text-white">
                        Gestion des Étiquettes
                    </h1>
                    <p class="text-gray-600 dark:text-gray-400">
                        Sélectionnez les prescriptions pour impression
                    </p>
                </div>

                <!-- Compteur sélection -->
                <div v-if="prescriptionsSelectionnees.length > 0" class="text-right">
                    <div class="bg-blue-50 dark:bg-blue-900/20 px-4 py-2 rounded-lg">
                        <span class="text-blue-700 dark:text-blue-300 font-medium">
                            {{ prescriptionsSelectionnees.length }} prescription(s) sélectionnée(s)
                        </span>
                    </div>

                    <!-- Résumé de la sélection -->
                    <div v-if="selectionSummary" class="mt-2 text-xs text-gray-600 dark:text-gray-400">
                        <span class="mr-2">{{ selectionSummary.total_tubes }} tubes</span>
                        <span class="mr-2">{{ selectionSummary.total_analyses }} analyses</span>
                        <span v-if="selectionSummary.prescriptions_vides > 0" class="mr-2">{{ selectionSummary.prescriptions_vides }} vides</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- STATISTIQUES COMPLÈTES -->
        <div class="grid grid-cols-1 md:grid-cols-6 gap-4 mb-6">
            <!-- Total prescriptions -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                <div class="flex items-center">
                    <div class="w-8 h-8 bg-blue-100 dark:bg-blue-900/30 rounded flex items-center justify-center">
                        <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-gray-600 dark:text-gray-400">Total prescriptions</p>
                        <p class="text-lg font-semibold text-gray-900 dark:text-white">
                            {{ formatNumber(statistiques.total_prescriptions) }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Avec tubes -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                <div class="flex items-center">
                    <div class="w-8 h-8 bg-green-100 dark:bg-green-900/30 rounded flex items-center justify-center">
                        <svg class="w-4 h-4 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-gray-600 dark:text-gray-400">Avec tubes</p>
                        <p class="text-lg font-semibold text-gray-900 dark:text-white">
                            {{ formatNumber(statistiques.avec_tubes) }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Avec analyses -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                <div class="flex items-center">
                    <div class="w-8 h-8 bg-purple-100 dark:bg-purple-900/30 rounded flex items-center justify-center">
                        <svg class="w-4 h-4 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-gray-600 dark:text-gray-400">Avec analyses</p>
                        <p class="text-lg font-semibold text-gray-900 dark:text-white">
                            {{ formatNumber(statistiques.avec_analyses) }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Sans tubes ni analyses -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                <div class="flex items-center">
                    <div class="w-8 h-8 bg-orange-100 dark:bg-orange-900/30 rounded flex items-center justify-center">
                        <svg class="w-4 h-4 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-gray-600 dark:text-gray-400">Sans contenu</p>
                        <p class="text-lg font-semibold text-gray-900 dark:text-white">
                            {{ formatNumber(statistiques.sans_tubes) }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Tubes réceptionnés -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                <div class="flex items-center">
                    <div class="w-8 h-8 bg-green-100 dark:bg-green-900/30 rounded flex items-center justify-center">
                        <svg class="w-4 h-4 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-gray-600 dark:text-gray-400">Tubes OK</p>
                        <p class="text-lg font-semibold text-gray-900 dark:text-white">
                            {{ formatNumber(statistiques.tubes_receptionnes) }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Tubes non réceptionnés -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                <div class="flex items-center">
                    <div class="w-8 h-8 bg-red-100 dark:bg-red-900/30 rounded flex items-center justify-center">
                        <svg class="w-4 h-4 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-gray-600 dark:text-gray-400">Tubes en attente</p>
                        <p class="text-lg font-semibold text-gray-900 dark:text-white">
                            {{ formatNumber(statistiques.tubes_non_receptionnes) }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- FILTRES ET CONFIGURATION -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-6">
            <!-- Filtres de recherche -->
            <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Recherche
                    </label>
                    <input type="text"
                        v-model="form.recherche"
                        @input="debouncedUpdateFilters"
                        placeholder="Référence, patient, prescripteur..."
                        class="w-full p-2 border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Type d'affichage
                    </label>
                    <select v-model="form.typeAffichage" @change="updateFilters"
                            class="w-full p-2 border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="tous">Toutes les prescriptions</option>
                        <option value="avec_tubes">Avec prélèvements</option>
                        <option value="sans_tubes">Sans prélèvements</option>
                        <option value="avec_analyses">Avec analyses</option>
                        <option value="sans_analyses">Sans analyses</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Statut tubes
                    </label>
                    <select v-model="form.filtreStatut" @change="updateFilters"
                            class="w-full p-2 border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="tous">Tous les statuts</option>
                        <option value="non_receptionnes">Non réceptionnés</option>
                        <option value="receptionnes">Réceptionnés</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Période
                    </label>
                    <select v-model="form.filtreDate" @change="updateFilters"
                            class="w-full p-2 border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="aujourd_hui">Aujourd'hui</option>
                        <option value="hier">Hier</option>
                        <option value="cette_semaine">Cette semaine</option>
                        <option value="ce_mois">Ce mois</option>
                        <option value="custom">Période personnalisée</option>
                    </select>
                </div>

                <div class="flex items-end">
                    <button @click="reinitialiserFiltres"
                            class="w-full px-4 py-2 bg-gray-500 hover:bg-gray-600 text-white rounded-lg transition-colors">
                        Réinitialiser
                    </button>
                </div>
            </div>

            <!-- Custom Date Range (visible only if custom is selected) -->
            <div v-if="form.filtreDate === 'custom'" class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Date début</label>
                    <input type="date" v-model="form.dateDebut" @change="updateFilters" class="w-full p-2 border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Date fin</label>
                    <input type="date" v-model="form.dateFin" @change="updateFilters" class="w-full p-2 border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
            </div>

            <!-- Configuration impression simplifiée -->
            <div class="border-t pt-4">
                <div class="flex justify-between items-center">
                    <div class="flex items-center gap-4">
                        <!-- Inclure patient -->
                        <div class="flex items-center">
                            <label class="flex items-center text-gray-700 dark:text-gray-300 cursor-pointer">
                                <input type="checkbox"
                                    v-model="inclurePatient"
                                    class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600">
                                <span class="ml-2">Inclure infos patient</span>
                            </label>
                        </div>

                        <!-- Aperçu génération -->
                        <div v-if="selectionSummary" class="bg-blue-50 dark:bg-blue-900/20 px-3 py-1 rounded-lg">
                            <div class="text-sm text-blue-700 dark:text-blue-300">
                                <strong>{{ selectionSummary.total_etiquettes || 0 }} étiquettes</strong>
                                ({{ selectionSummary.total_tubes }} tubes + {{ selectionSummary.sans_tubes }} sans tubes)
                            </div>
                        </div>
                    </div>

                    <!-- Bouton génération -->
                    <div>
                        <button @click="imprimerEtiquettes"
                                :disabled="prescriptionsSelectionnees.length === 0 || isGeneratingPDF"
                                class="px-6 py-2 bg-blue-500 hover:bg-blue-600 disabled:bg-gray-400 disabled:cursor-not-allowed text-white font-medium rounded-lg transition-colors inline-flex items-center">
                            <span v-if="!isGeneratingPDF">
                                Générer PDF
                                <template v-if="selectionSummary">
                                    ({{ selectionSummary.total_etiquettes || 0 }} étiquettes)
                                </template>
                                <template v-else>
                                    ({{ prescriptionsSelectionnees.length }} prescriptions)
                                </template>
                            </span>
                            <span v-else class="flex items-center">
                                <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Génération...
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- LISTE DES PRESCRIPTIONS -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
            <!-- EN-TÊTE LISTE -->
            <div class="p-4 border-b dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <label class="flex items-center text-gray-700 dark:text-gray-300 cursor-pointer">
                        <input type="checkbox"
                               v-model="toutSelectionner"
                               @change="toggleToutSelectionner"
                               class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600">
                        <span class="ml-2 font-medium">Tout sélectionner la page courante</span>
                    </label>

                    <div class="flex items-center space-x-4">
                        <span class="text-sm text-gray-500 dark:text-gray-400">
                            {{ prescriptions.data.length }} sur {{ prescriptions.total }} prescription(s)
                        </span>

                        <button v-if="prescriptionsSelectionnees.length > 0"
                                @click="viderSelection"
                                class="text-sm text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300 transition-colors">
                            Vider la sélection globale
                        </button>
                    </div>
                </div>
            </div>

            <!-- LISTE DES PRESCRIPTIONS -->
            <div class="divide-y dark:divide-gray-700">
                <template v-if="prescriptions.data.length > 0">
                    <div v-for="prescription in prescriptions.data" :key="prescription.id"
                         :class="[
                            'p-4 transition-colors duration-150',
                            prescriptionsSelectionnees.includes(prescription.id) ? 'bg-blue-50 dark:bg-blue-900/20' : 'hover:bg-gray-50 dark:hover:bg-gray-700/50'
                         ]">
                        <div class="flex items-center space-x-4">
                            <!-- CHECKBOX -->
                            <input type="checkbox"
                                   v-model="prescriptionsSelectionnees"
                                   :value="prescription.id"
                                   class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 cursor-pointer">

                            <!-- ICÔNE STATUT -->
                            <div :class="[
                                'w-10 h-10 rounded-lg flex items-center justify-center text-white font-bold',
                                prescription.tubes.length === 0 && !prescription.has_analyses ? 'bg-gradient-to-br from-gray-500 to-gray-600' :
                                prescription.tubes.length === 0 && prescription.has_analyses ? 'bg-gradient-to-br from-purple-500 to-purple-600' :
                                'bg-gradient-to-br from-blue-500 to-blue-600'
                            ]">
                                <span v-if="prescription.tubes.length === 0 && !prescription.has_analyses">📄</span>
                                <span v-else-if="prescription.tubes.length === 0 && prescription.has_analyses">📝</span>
                                <span v-else>🧪</span>
                            </div>

                            <!-- INFORMATIONS PRINCIPALES -->
                            <div class="flex-1">
                                <div class="flex items-center space-x-3 mb-1">
                                    <span class="font-bold text-gray-900 dark:text-white">
                                        {{ prescription.reference }}
                                    </span>

                                    <span v-if="prescription.tubes.length === 0 && !prescription.has_analyses" class="text-xs px-2 py-1 rounded-full font-medium bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-300">
                                        PRESCRIPTION VIDE
                                    </span>
                                    <span v-else-if="prescription.tubes.length === 0 && prescription.has_analyses" class="text-xs px-2 py-1 rounded-full font-medium bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-300">
                                        {{ prescription.analyses_count }} ANALYSE(S)
                                    </span>
                                    <template v-else>
                                        <span :class="['text-xs px-2 py-1 rounded-full font-medium', prescription.tubes_receptionnes_count === prescription.total_tubes_count ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300' : 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300']">
                                            {{ prescription.tubes_receptionnes_count }}/{{ prescription.total_tubes_count }} TUBES
                                        </span>
                                        <span v-if="prescription.has_analyses" class="text-xs px-2 py-1 rounded-full font-medium bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-300">
                                            +{{ prescription.analyses_count }} ANALYSE(S)
                                        </span>
                                    </template>
                                </div>

                                <!-- PATIENT -->
                                <div v-if="prescription.patient" class="text-sm text-gray-700 dark:text-gray-300 mb-1">
                                    {{ prescription.patient.civilite }}
                                    {{ prescription.patient.nom }}
                                    {{ prescription.patient.prenom }}
                                    <span v-if="prescription.age">- {{ prescription.age }} {{ prescription.unite_age }}</span>
                                </div>

                                <!-- DÉTAILS -->
                                <div class="flex items-center space-x-4 text-xs text-gray-500 dark:text-gray-400">
                                    <span class="flex items-center">
                                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                        {{ formatDate(prescription.created_at) }}
                                    </span>

                                    <span v-if="prescription.prescripteur" class="flex items-center">
                                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                        </svg>
                                        Dr. {{ prescription.prescripteur.nom }}
                                    </span>

                                    <span v-if="prescription.tubes.length > 0" class="flex items-center">
                                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
                                        </svg>
                                        {{ getUniquePrelevementNames(prescription.tubes) }}
                                    </span>

                                    <span v-if="prescription.has_analyses" class="flex items-center">
                                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                        </svg>
                                        {{ prescription.analyses_count }} analyse(s)
                                    </span>
                                </div>

                                <!-- RENSEIGNEMENT CLINIQUE -->
                                <div v-if="prescription.renseignement_clinique" class="mt-2 text-xs text-gray-600 dark:text-gray-400 italic">
                                    {{ truncate(prescription.renseignement_clinique, 100) }}
                                </div>
                            </div>

                            <!-- ACTIONS -->
                            <div class="flex items-center space-x-2">
                                <template v-if="prescription.tubes.length > 0">
                                    <template v-if="prescription.tubes_receptionnes_count < prescription.total_tubes_count">
                                        <Link :href="route('secretaire.etiquettes.marquer.reception', prescription.id)"
                                              method="post"
                                              as="button"
                                              preserve-scroll
                                                class="p-2 text-green-600 hover:bg-green-100 dark:hover:bg-green-900/30 rounded-lg transition-colors"
                                                title="Marquer tous les tubes comme réceptionnés">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                            </svg>
                                        </Link>
                                    </template>
                                    <template v-else>
                                        <div class="p-2 text-green-600" title="Tous les tubes sont réceptionnés">
                                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                        </div>
                                    </template>
                                </template>
                                <template v-else-if="prescription.has_analyses">
                                    <div class="p-2 text-purple-600" title="Prescription avec analyses seulement">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                        </svg>
                                    </div>
                                </template>
                                <template v-else>
                                    <div class="p-2 text-gray-600" title="Prescription vide">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                        </svg>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </template>
                <template v-else>
                    <!-- AUCUN RÉSULTAT -->
                    <div class="p-12 text-center">
                        <div class="w-16 h-16 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                        </div>
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">
                            Aucune prescription trouvée
                        </h3>
                        <p class="text-gray-500 dark:text-gray-400 mb-4">
                            Aucune prescription ne correspond aux critères de recherche actuels.
                        </p>
                        <button @click="reinitialiserFiltres"
                                class="px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded-lg transition-colors">
                            Réinitialiser les filtres
                        </button>
                    </div>
                </template>
            </div>

            <!-- PAGINATION -->
            <div v-if="prescriptions.links && prescriptions.links.length > 3" class="p-4 border-t dark:border-gray-700">
                <div class="flex flex-wrap -mb-1">
                    <template v-for="(link, p) in prescriptions.links" :key="p">
                        <div v-if="link.url === null" class="mr-1 mb-1 px-4 py-3 text-sm leading-4 text-gray-400 border rounded" v-html="link.label" />
                        <Link v-else class="mr-1 mb-1 px-4 py-3 text-sm leading-4 border rounded hover:bg-gray-100 dark:hover:bg-gray-700 dark:text-white dark:border-gray-600 focus:border-indigo-500 focus:text-indigo-500" :class="{ 'bg-blue-50 dark:bg-blue-900/50': link.active }" :href="link.url" v-html="link.label" preserve-state preserve-scroll />
                    </template>
                </div>
            </div>
        </div>
    </div>
</AppLayout>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { useForm, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { format, parseISO } from 'date-fns';
import debounce from 'lodash/debounce';

const props = defineProps({
    prescriptions: Object,
    statistiques: Object,
    filters: Object
});

const form = useForm({
    recherche: props.filters.recherche || '',
    filtreStatut: props.filters.filtreStatut || 'tous',
    filtreDate: props.filters.filtreDate || 'aujourd_hui',
    dateDebut: props.filters.dateDebut || '',
    dateFin: props.filters.dateFin || '',
    typeAffichage: props.filters.typeAffichage || 'tous'
});

const prescriptionsSelectionnees = ref([]);
const toutSelectionner = ref(false);
const inclurePatient = ref(true);
const isGeneratingPDF = ref(false);

const selectionSummary = computed(() => {
    if (prescriptionsSelectionnees.value.length === 0) return null;

    let total_tubes = 0;
    let total_analyses = 0;
    let sans_tubes = 0;
    let prescriptions_vides = 0;

    props.prescriptions.data.forEach(p => {
        if (prescriptionsSelectionnees.value.includes(p.id)) {
            total_tubes += p.total_tubes_count;
            total_analyses += p.analyses_count;

            if (p.total_tubes_count === 0) {
                sans_tubes++;
                if (!p.has_analyses) {
                    prescriptions_vides++;
                }
            }
        }
    });

    const total_etiquettes = (total_tubes * 5) + (sans_tubes * 5);

    return {
        total_tubes,
        total_analyses,
        sans_tubes,
        prescriptions_vides,
        total_etiquettes
    };
});

const debouncedUpdateFilters = debounce(() => {
    updateFilters();
}, 300);

const updateFilters = () => {
    form.get(route('secretaire.etiquettes'), {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        onSuccess: () => {
             // Optional: uncheck all when filters change? Let's keep existing behavior (not reset unless manual)
        }
    });
};

const reinitialiserFiltres = () => {
    form.recherche = '';
    form.filtreStatut = 'tous';
    form.filtreDate = 'aujourd_hui';
    form.dateDebut = '';
    form.dateFin = '';
    form.typeAffichage = 'tous';
    prescriptionsSelectionnees.value = [];
    toutSelectionner.value = false;
    updateFilters();
};

const toggleToutSelectionner = () => {
    if (toutSelectionner.value) {
        // Select all on current page
        const currentIds = props.prescriptions.data.map(p => p.id);
        const newSelection = [...new Set([...prescriptionsSelectionnees.value, ...currentIds])];
        prescriptionsSelectionnees.value = newSelection;
    } else {
        // Deselect all on current page
        const currentIds = props.prescriptions.data.map(p => p.id);
        prescriptionsSelectionnees.value = prescriptionsSelectionnees.value.filter(id => !currentIds.includes(id));
    }
};

const viderSelection = () => {
    prescriptionsSelectionnees.value = [];
    toutSelectionner.value = false;
};

// Check if all items on current page are selected to update the "toutSelectionner" checkbox state
watch(() => prescriptionsSelectionnees.value, (newSelection) => {
    const currentIds = props.prescriptions.data.map(p => p.id);
    if (currentIds.length > 0 && currentIds.every(id => newSelection.includes(id))) {
        toutSelectionner.value = true;
    } else {
        toutSelectionner.value = false;
    }
}, { deep: true });


const imprimerEtiquettes = () => {
    if (prescriptionsSelectionnees.value.length === 0) return;

    isGeneratingPDF.value = true;

    // Use a standard non-inertia visit to download the file
    const url = new URL(route('secretaire.etiquettes.export'));
    prescriptionsSelectionnees.value.forEach(id => {
        url.searchParams.append('prescriptions[]', id);
    });
    url.searchParams.append('inclurePatient', inclurePatient.value ? '1' : '0');

    window.location.href = url.toString();

    // Resetting loading state after a short delay since browser downloads don't trigger events easily
    setTimeout(() => {
        isGeneratingPDF.value = false;
    }, 2000);
};

// Utils
const formatNumber = (num) => {
    return new Intl.NumberFormat('fr-FR').format(num || 0);
};

const formatDate = (dateString) => {
    if (!dateString) return '';
    try {
        const date = typeof dateString === 'string' ? parseISO(dateString) : dateString;
        return format(date, 'dd/MM/yyyy HH:mm');
    } catch (e) {
        return dateString;
    }
};

const getUniquePrelevementNames = (tubes) => {
    if (!tubes || tubes.length === 0) return '';
    const names = tubes
        .filter(t => t.prelevement && t.prelevement.denomination)
        .map(t => t.prelevement.denomination);
    return [...new Set(names)].join(', ');
};

const truncate = (str, length = 100) => {
    if (!str) return '';
    if (str.length <= length) return str;
    return str.substring(0, length) + '...';
};
</script>
