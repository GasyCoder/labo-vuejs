<template>
<AppLayout>
    <!-- Header avec titre -->
    <div class="container mx-auto px-4 py-6 sm:px-6 lg:px-8">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
            <div>
                <h1 class="text-3xl font-heading font-bold text-gray-900 dark:text-white">Journal de Caisse</h1>
                <p class="text-gray-600 dark:text-gray-400 mt-2">LNB SITE MAITRE - Analyses Médicales - Suivi des recettes par date de paiement</p>
            </div>
            
            <!-- Informations période -->
            <div class="bg-gradient-to-r from-blue-50 to-blue-100 dark:from-blue-900/20 dark:to-blue-800/20 px-4 py-3 rounded-xl border border-blue-200 dark:border-blue-700">
                <div class="text-xs font-medium text-blue-600 dark:text-blue-400 uppercase tracking-wide">Période de Paiement</div>
                <div class="text-lg font-bold text-blue-800 dark:text-blue-300">
                    {{ formatDate(dateDebut) }} - {{ formatDate(dateFin) }}
                </div>
                <div class="text-xs text-blue-600 dark:text-blue-400">
                    Basé sur les dates de paiement réelles
                </div>
            </div>
        </div>
    </div>

    <div class="container mx-auto px-4 py-8 sm:px-6 lg:px-8">
        <!-- Filtres et actions -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                <!-- Date début -->
                <div>
                    <label for="dateDebut" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">
                        Date début de paiement
                    </label>
                    <input 
                        v-model="form.dateDebut"
                        @change="updateFilters"
                        type="date" 
                        id="dateDebut"
                        class="w-full py-3 px-4 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 dark:focus:ring-primary-400 focus:border-primary-500 dark:focus:border-primary-400 transition-all"
                    >
                </div>

                <!-- Date fin -->
                <div>
                    <label for="dateFin" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">
                        Date fin de paiement
                    </label>
                    <input 
                        v-model="form.dateFin"
                        @change="updateFilters"
                        type="date" 
                        id="dateFin"
                        class="w-full py-3 px-4 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 dark:focus:ring-primary-400 focus:border-primary-500 dark:focus:border-primary-400 transition-all"
                    >
                </div>

                <!-- Actions -->
                <div class="flex items-end">
                    <a 
                        :href="route('secretaire.journal-caisse.export', { dateDebut: form.dateDebut, dateFin: form.dateFin })"
                        class="w-full inline-flex items-center justify-center px-4 py-3 text-sm font-medium text-white bg-red-600 hover:bg-red-700 border border-transparent rounded-xl shadow-sm focus:ring-2 focus:ring-red-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition-colors"
                    >
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        Export PDF
                    </a>
                </div>
            </div>

            <!-- Information importante -->
            <div class="bg-blue-50 dark:bg-blue-900/20 border-l-4 border-blue-400 p-4 mb-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-blue-700 dark:text-blue-300">
                            <strong>Note importante :</strong> Ce rapport est désormais basé sur les <strong>dates de paiement réelles</strong> 
                            (quand le paiement a été effectivement marqué comme payé) et non plus sur les dates de création des paiements.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Résumé -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                <div class="flex items-center space-x-4">
                    <span class="text-sm font-medium text-gray-600 dark:text-gray-400">
                        {{ totalCount }} paiement(s) effectué(s) dans cette période
                    </span>
                </div>
                <div class="text-lg font-bold text-gray-900 dark:text-white">
                    Total période: {{ formatCurrency(totalMontant) }} Ar.
                </div>
            </div>
        </div>

        <!-- Cartes statistiques -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
            <!-- Carte Total Général -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-lg bg-blue-100 dark:bg-blue-900/30">
                        <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Général (tous paiements payés)</h3>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">
                            {{ formatCurrency(totalGeneral) }} Ar.
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Basé sur dates de paiement</p>
                    </div>
                </div>
            </div>

            <!-- Carte Nombre de Paiements -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-lg bg-green-100 dark:bg-green-900/30">
                        <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Paiements effectués</h3>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">
                            {{ totalCount }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Carte Total Période -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-lg bg-purple-100 dark:bg-purple-900/30">
                        <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Période</h3>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">
                            {{ formatCurrency(totalSemaine) }} Ar.
                        </p>
                        <div class="flex items-center mt-1">
                            <template v-if="evolutionSemaine >= 0">
                                <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"></path>
                                </svg>
                                <span class="text-sm font-medium text-green-500">
                                    +{{ evolutionSemaine.toFixed(1) }}%
                                </span>
                            </template>
                            <template v-else>
                                <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>
                                </svg>
                                <span class="text-sm font-medium text-red-500">
                                    {{ evolutionSemaine.toFixed(1) }}%
                                </span>
                            </template>
                            <span class="text-xs text-gray-500 dark:text-gray-400 ml-1">vs période précédente</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Carte Méthodes de Paiement -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-lg bg-orange-100 dark:bg-orange-900/30">
                        <svg class="w-6 h-6 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Méthodes de Paiement</h3>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">
                            {{ Object.keys(totauxParMethode || {}).length }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Journal de Caisse -->
        <div class="bg-white dark:bg-slate-900 rounded-lg shadow overflow-hidden">
            <!-- En-tête avec période -->
            <div class="px-6 py-4 bg-gray-50 dark:bg-slate-800 border-b border-gray-200 dark:border-slate-700">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-slate-200 text-center">
                    CAISSE - PAIEMENTS du {{ formatDate(dateDebut) }} au {{ formatDate(dateFin) }}
                </h3>
                <p class="text-sm text-gray-600 dark:text-slate-400 text-center mt-1">
                    Basé sur les dates de paiement effectives
                </p>
            </div>

            <template v-if="totalCount > 0">
                <div class="overflow-x-auto">
                    <template v-for="(paiements, methodePaiement) in paiementsGroupes" :key="methodePaiement">
                        <!-- En-tête méthode de paiement -->
                        <div class="px-6 py-3 bg-blue-50 dark:bg-blue-900/30 border-b border-blue-200 dark:border-blue-700">
                            <h4 class="font-bold text-blue-800 dark:text-blue-300 uppercase text-center">
                                {{ methodePaiement }}
                            </h4>
                        </div>

                        <!-- En-tête tableau -->
                        <div class="bg-gray-100 dark:bg-slate-800">
                            <div class="grid grid-cols-4 gap-4 px-6 py-3">
                                <div class="font-semibold text-gray-700 dark:text-gray-300 text-sm uppercase tracking-wide">Date Paiement</div>
                                <div class="font-semibold text-gray-700 dark:text-gray-300 text-sm uppercase tracking-wide">Dossier</div>
                                <div class="font-semibold text-gray-700 dark:text-gray-300 text-sm uppercase tracking-wide">Client</div>
                                <div class="font-semibold text-gray-700 dark:text-gray-300 text-sm uppercase tracking-wide text-right">Montant</div>
                            </div>
                        </div>

                        <!-- Lignes de paiement -->
                        <div v-for="paiement in paiements" :key="paiement.id" class="border-b border-gray-100 dark:border-slate-800 hover:bg-gray-50 dark:hover:bg-slate-800 transition-colors duration-200">
                            <div class="grid grid-cols-4 gap-4 px-6 py-3 text-sm">
                                <!-- Date de paiement -->
                                <div class="text-gray-600 dark:text-gray-400 flex flex-col">
                                    <span class="font-medium">
                                        {{ paiement.date_paiement_format }}
                                    </span>
                                    <span v-if="paiement.heure_paiement_format" class="text-xs text-gray-500">
                                        {{ paiement.heure_paiement_format }}
                                    </span>
                                </div>
                                
                                <!-- Dossier -->
                                <div class="font-medium text-blue-600 dark:text-blue-400 flex items-center flex-wrap">
                                    <span>{{ paiement.prescription?.patient?.numero_dossier || 'N/A' }}</span>
                                    
                                    <!-- Badge "Modifié" si la prescription a été modifiée -->
                                    <span v-if="paiement.prescription?.is_modified" class="ml-2 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400">
                                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" />
                                        </svg>
                                        Modifié-{{ paiement.prescription?.updated_at_format }}
                                    </span>
                                </div>
                                
                                <!-- Client -->
                                <div class="font-medium text-gray-900 dark:text-gray-100">
                                    {{ paiement.prescription?.patient?.nom || 'Client non défini' }} 
                                    {{ paiement.prescription?.patient?.prenom || '' }}
                                </div>
                                
                                <!-- Montant -->
                                <div class="text-right font-bold text-gray-900 dark:text-gray-100">
                                    {{ formatCurrency(paiement.montant) }}
                                </div>
                            </div>
                        </div>

                        <!-- Sous-total -->
                        <div class="bg-yellow-50 dark:bg-yellow-900/20 border-b border-yellow-200 dark:border-yellow-700">
                            <div class="grid grid-cols-4 gap-4 px-6 py-3">
                                <div class="col-span-3 text-right font-bold text-gray-700 dark:text-gray-300">
                                    SOUS TOTAL
                                </div>
                                <div class="text-right font-bold text-lg text-red-600 dark:text-red-400">
                                    {{ formatCurrency(getSousTotal(paiements)) }} Ar.
                                </div>
                            </div>
                        </div>
                    </template>

                    <!-- Total Général (période filtrée) -->
                    <div class="bg-red-100 dark:bg-red-900/30 border-t-2 border-red-300 dark:border-red-600">
                        <div class="grid grid-cols-4 gap-4 px-6 py-4">
                            <div class="col-span-3 text-right font-bold text-gray-800 dark:text-gray-200 text-lg">
                                TOTAL GÉNÉRAL (période de paiement filtrée)
                            </div>
                            <div class="text-right font-bold text-xl text-red-700 dark:text-red-400">
                                {{ formatCurrency(totalMontant) }} Ar.
                            </div>
                        </div>
                    </div>
                </div>
            </template>
            <template v-else>
                <!-- État vide -->
                <div class="px-6 py-16 text-center">
                    <div class="flex flex-col items-center">
                        <div class="w-16 h-16 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mb-4">
                            <svg class="w-8 h-8 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                        </div>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2">Aucun paiement effectué</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 max-w-sm">
                            Aucun paiement n'a été effectué (marqué comme payé) durant cette période. Vérifiez les dates sélectionnées.
                        </p>
                    </div>
                </div>
            </template>

            <!-- Footer avec date d'édition -->
            <div v-if="totalCount > 0" class="px-6 py-3 bg-gray-50 dark:bg-gray-900/50 border-t border-gray-200 dark:border-gray-700">
                <div class="text-right text-sm text-gray-500 dark:text-gray-400">
                    Rapport généré le {{ now }} - Basé sur les dates de paiement effectives
                </div>
            </div>
        </div>
    </div>
</AppLayout>
</template>

<script setup>
import { computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { format } from 'date-fns';

const props = defineProps({
    dateDebut: String,
    dateFin: String,
    totalGeneral: Number,
    paiementsGroupes: Object,
    totauxParMethode: Array,
    totalSemaine: Number,
    evolutionSemaine: Number,
    totalCount: Number,
    totalMontant: Number
});

const form = useForm({
    dateDebut: props.dateDebut,
    dateFin: props.dateFin
});

const now = computed(() => {
    return format(new Date(), 'dd/MM/yyyy HH:mm:ss');
});

const updateFilters = () => {
    form.get(route('secretaire.journal-caisse'), {
        preserveState: true,
        preserveScroll: true
    });
};

const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
    const [year, month, day] = dateString.split('-');
    return `${day}/${month}/${year}`;
};

const formatCurrency = (amount) => {
    if (amount === null || amount === undefined) return '0.00';
    return Number(amount).toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};

const getSousTotal = (paiements) => {
    return paiements.reduce((sum, paiement) => sum + parseFloat(paiement.montant), 0);
};
</script>
