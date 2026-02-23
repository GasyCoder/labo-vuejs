<template>
    <Modal :show="show" max-width="4xl" @close="closeModal">
        <div class="bg-white dark:bg-gray-800">
            <div class="px-6 py-6" v-if="prescripteur">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
                        Commissions de {{ prescripteur.nom_complet }}
                    </h2>
                    <button @click="closeModal" class="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300 rounded-lg p-2 hover:bg-gray-100 dark:hover:bg-gray-700">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <!-- Filtres de dates -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div class="bg-gray-50 dark:bg-gray-700/50 p-4 rounded-lg border border-gray-200 dark:border-gray-600">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Date de début</label>
                        <input type="date" v-model="dateDebut" @change="loadCommissions(true)" class="w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 dark:focus:ring-blue-400">
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-700/50 p-4 rounded-lg border border-gray-200 dark:border-gray-600">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Date de fin</label>
                        <input type="date" v-model="dateFin" @change="loadCommissions(true)" class="w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 dark:focus:ring-blue-400">
                    </div>
                </div>

                <!-- Loader -->
                <div v-if="loading" class="flex justify-center p-8">
                    <svg class="animate-spin h-8 w-8 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </div>

                <template v-else>
                    <!-- Statistiques de la période -->
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                        <div class="bg-gradient-to-r from-green-50 to-green-100 dark:from-green-900/20 dark:to-green-800/20 p-4 rounded-lg border border-green-200 dark:border-green-700">
                            <div class="text-sm font-medium text-green-600 dark:text-green-400">Prescriptions</div>
                            <div class="text-2xl font-bold text-green-800 dark:text-green-300">{{ formatNumber(commissionDetails.total_prescriptions) }}</div>
                        </div>
                        <div class="bg-gradient-to-r from-blue-50 to-blue-100 dark:from-blue-900/20 dark:to-blue-800/20 p-4 rounded-lg border border-blue-200 dark:border-blue-700">
                            <div class="text-sm font-medium text-blue-600 dark:text-blue-400">Montant analyses</div>
                            <div class="text-2xl font-bold text-blue-800 dark:text-blue-300">{{ formatNumber(commissionDetails.montant_total_analyses) }} Ar</div>
                        </div>
                        <div class="bg-gradient-to-r from-purple-50 to-purple-100 dark:from-purple-900/20 dark:to-purple-800/20 p-4 rounded-lg border border-purple-200 dark:border-purple-700">
                            <div class="text-sm font-medium text-purple-600 dark:text-purple-400">Total payé</div>
                            <div class="text-2xl font-bold text-purple-800 dark:text-purple-300">{{ formatNumber(commissionDetails.montant_total_paye) }} Ar</div>
                        </div>
                        <div class="bg-gradient-to-r from-yellow-50 to-yellow-100 dark:from-yellow-900/20 dark:to-yellow-800/20 p-4 rounded-lg border border-yellow-200 dark:border-yellow-700">
                            <div class="text-sm font-medium text-yellow-600 dark:text-yellow-400">Commission totale ({{ prescripteur.commission_pourcentage }}%)</div>
                            <div class="text-2xl font-bold text-yellow-800 dark:text-yellow-300">{{ formatNumber(commissionDetails.total_commission) }} Ar</div>
                        </div>
                    </div>

                    <!-- Tableau par mois -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                        <div class="px-6 py-4 bg-gray-50 dark:bg-gray-700/50 border-b border-gray-200 dark:border-gray-700">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Répartition par mois</h3>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-700/50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Mois / Année</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Prescriptions</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Montant analyses (Brut)</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status Quota</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Commission ({{ prescripteur.commission_pourcentage }}%)</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                    <template v-if="commissionDetails.data && commissionDetails.data.length > 0">
                                        <template v-for="(detail, index) in commissionDetails.data" :key="index">
                                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 cursor-pointer" @click="toggleRow(index)">
                                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white flex items-center">
                                                    <svg class="h-4 w-4 mr-2 text-gray-500 transition-transform" :class="{'rotate-90': expandedRows[index]}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                                    </svg>
                                                    {{ getMonthName(detail.mois) }} {{ detail.annee }}
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                                        {{ detail.nombre_prescriptions }}
                                                    </span>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100 font-medium">
                                                    {{ formatNumber(detail.montant_analyses) }} Ar
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                                    <span v-if="detail.quota_atteint" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                        </svg>
                                                        Quota atteint
                                                    </span>
                                                    <div v-else class="flex flex-col">
                                                        <span class="inline-flex items-center px-2.5 py-0.5 w-max rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">
                                                            Sous quota
                                                        </span>
                                                        <span class="text-[10px] text-gray-500 mt-1">
                                                            {{ formatNumber(detail.montant_analyses) }} / {{ formatNumber(detail.quota_montant) }}
                                                        </span>
                                                    </div>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold" :class="detail.commission > 0 ? 'text-green-600 dark:text-green-400' : 'text-gray-400'">
                                                    <template v-if="detail.commission > 0">
                                                        {{ formatNumber(detail.commission) }} Ar
                                                    </template>
                                                    <template v-else>
                                                        0 Ar
                                                        <div v-if="!detail.quota_atteint" class="text-[10px] font-normal text-red-500 italic">Quota non atteint</div>
                                                    </template>
                                                </td>
                                            </tr>
                                            <!-- Séparateur pour les prescriptions du mois -->
                                            <tr v-if="expandedRows[index] && detail.prescriptions && detail.prescriptions.length" class="bg-gray-50/50 dark:bg-gray-800/50">
                                                <td colspan="5" class="px-10 py-4">
                                                    <div class="bg-white dark:bg-gray-900 rounded-lg shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
                                                        <div class="px-4 py-2 bg-gray-100 dark:bg-gray-800 text-xs font-semibold text-gray-600 dark:text-gray-300">
                                                            Détails des prescriptions
                                                        </div>
                                                        <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-700">
                                                            <thead class="bg-gray-50 dark:bg-gray-800/70">
                                                                <tr>
                                                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400">Patient</th>
                                                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400">N° Dossier</th>
                                                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400">Date</th>
                                                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400">Montant analyses</th>
                                                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400">Commission</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                                                <tr v-for="presc in detail.prescriptions" :key="presc.id">
                                                                    <td class="px-4 py-2 text-sm text-gray-700 dark:text-gray-300">{{ presc.patient_nom_complet }}</td>
                                                                    <td class="px-4 py-2 text-sm text-gray-500 dark:text-gray-400">{{ presc.patient_numero_dossier }}</td>
                                                                    <td class="px-4 py-2 text-sm text-gray-500 dark:text-gray-400">{{ presc.date }}</td>
                                                                    <td class="px-4 py-2 text-sm text-gray-700 dark:text-gray-300">{{ formatNumber(presc.montant_analyses) }} Ar</td>
                                                                    <td class="px-4 py-2 text-sm font-medium text-green-600 dark:text-green-400">{{ formatNumber(presc.commission) }} Ar</td>
                                                                </tr>
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </td>
                                            </tr>
                                        </template>
                                    </template>
                                    <template v-else>
                                        <tr>
                                            <td colspan="5" class="px-6 py-12 text-center">
                                                <div class="flex flex-col items-center">
                                                    <div class="w-16 h-16 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mb-4">
                                                        <svg class="w-8 h-8 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                                        </svg>
                                                    </div>
                                                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-1">Aucune commission</h3>
                                                    <p class="text-sm text-gray-500 dark:text-gray-400">
                                                        Aucune commission trouvée pour la période sélectionnée
                                                    </p>
                                                </div>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Résumé final -->
                    <div v-if="commissionDetails.total_commission > 0" class="mt-6 bg-gradient-to-r from-green-50 to-blue-50 dark:from-green-900/20 dark:to-blue-900/20 p-6 rounded-lg border border-green-200 dark:border-green-700">
                        <div class="flex items-center justify-between">
                            <div>
                                <h4 class="text-lg font-semibold text-gray-900 dark:text-white">Commission totale à percevoir</h4>
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    Sur {{ commissionDetails.total_prescriptions }} prescription(s) payée(s)
                                </p>
                            </div>
                            <div class="text-right">
                                <div class="text-3xl font-bold text-green-600 dark:text-green-400">
                                    {{ formatNumber(commissionDetails.total_commission) }} Ar
                                </div>
                                <div class="text-sm text-gray-600 dark:text-gray-400">
                                    Taux : {{ prescripteur.commission_pourcentage }}% du prix d'analyse brute
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            <div class="bg-gray-50 dark:bg-gray-700/50 px-6 py-4 border-t border-gray-200 dark:border-gray-700 flex justify-end space-x-3">
                <SecondaryButton @click="closeModal">
                    Fermer
                </SecondaryButton>
                <a 
                    v-if="prescripteur && commissionDetails.total_commission > 0"
                    :href="route('secretaire.prescripteurs.commissions.pdf', { prescripteur: prescripteur.id, dateDebut: dateDebut, dateFin: dateFin })"
                    target="_blank"
                    class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 border border-transparent rounded-md shadow-sm text-sm font-medium text-white focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors"
                >
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    Télécharger PDF
                </a>
            </div>
        </div>
    </Modal>
</template>

<script setup>
import { ref, watch } from 'vue';
import axios from 'axios';
import Modal from '@/Components/Modal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';

const props = defineProps({
    show: Boolean,
    prescripteur: Object,
});

const emit = defineEmits(['close']);

const loading = ref(false);
// First day of the year to today
const today = new Date();
const dateDebut = ref(`${today.getFullYear()}-01-01`);
const dateFin = ref(today.toISOString().split('T')[0]);

const commissionDetails = ref({
    data: [],
    total_commission: 0,
    total_prescriptions: 0,
    montant_total_analyses: 0,
    montant_total_paye: 0,
    commission_moyenne: 0
});

const expandedRows = ref({});

const formatNumber = (num) => {
    if (num === null || num === undefined) return '0';
    return Number(num).toLocaleString('fr-FR');
};

const getMonthName = (monthNum) => {
    const d = new Date();
    d.setMonth(monthNum - 1);
    const monthName = d.toLocaleString('fr-FR', { month: 'long' });
    return monthName.charAt(0).toUpperCase() + monthName.slice(1);
};

const loadCommissions = async (withLoading = true) => {
    if (!props.prescripteur) return;
    
    if (withLoading) loading.value = true;
    expandedRows.value = {}; // Reset expanded rows
    
    try {
        const response = await axios.get(route('secretaire.prescripteurs.commissions.api', props.prescripteur.id), {
            params: {
                dateDebut: dateDebut.value,
                dateFin: dateFin.value
            }
        });
        commissionDetails.value = response.data;
    } catch (error) {
        console.error('Erreur lors du chargement des commissions', error);
    } finally {
        if (withLoading) loading.value = false;
    }
};

const toggleRow = (index) => {
    expandedRows.value[index] = !expandedRows.value[index];
};

watch(() => props.show, (show) => {
    if (show && props.prescripteur) {
        loadCommissions();
    } else {
        expandedRows.value = {};
    }
});

const closeModal = () => {
    emit('close');
};
</script>
