<script setup>
import { ref, watch, computed } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import Modal from '@/Components/Modal.vue';
import debounce from 'lodash/debounce';

const props = defineProps({
    patients: Object,
    prescriptions: Object,
    filters: Object,
    stats: Object,
});

const search = ref(props.filters.search || '');
const activeTab = ref(props.filters.tab || 'prescriptions');

// Watchers for search and tab changes
watch(search, debounce((value) => {
    router.get(
        route('admin.trace-patients'),
        { search: value, tab: activeTab.value },
        { preserveState: true, replace: true }
    );
}, 300));

watch(activeTab, (value) => {
    search.value = '';
    router.get(
        route('admin.trace-patients'),
        { tab: value },
        { preserveState: true }
    );
});

// Modals State
const showRestoreModal = ref(false);
const showDeleteModal = ref(false);
const showEmptyTrashModal = ref(false);

const actionType = ref(''); // 'restore', 'delete', 'empty'
const itemType = ref(''); // 'patient', 'prescription'
const selectedItem = ref(null);

const formatNumber = (num) => {
    return num ? new Intl.NumberFormat('fr-FR').format(num) : '0';
};
const formatDate = (dateString) => {
    if (!dateString) return '';
    const date = new Date(dateString);
    return date.toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' });
};

// Actions
const confirmRestore = (item, type) => {
    selectedItem.value = item;
    itemType.value = type;
    actionType.value = 'restore';
    showRestoreModal.value = true;
};

const confirmDelete = (item, type) => {
    selectedItem.value = item;
    itemType.value = type;
    actionType.value = 'delete';
    showDeleteModal.value = true;
};

const confirmEmptyTrash = (type) => {
    itemType.value = type;
    actionType.value = 'empty';
    showEmptyTrashModal.value = true;
};

const executeRestore = () => {
    if (!selectedItem.value) return;
    
    const routeName = itemType.value === 'patient' 
        ? 'admin.trace-patient.patients.restore' 
        : 'admin.trace-patient.prescriptions.restore';

    router.post(route(routeName, selectedItem.value.id), {}, {
        preserveScroll: true,
        onSuccess: () => showRestoreModal.value = false
    });
};

const executeDelete = () => {
    if (!selectedItem.value) return;
    
    const routeName = itemType.value === 'patient' 
        ? 'admin.trace-patient.patients.force-delete' 
        : 'admin.trace-patient.prescriptions.force-delete';

    router.delete(route(routeName, selectedItem.value.id), {
        preserveScroll: true,
        onSuccess: () => showDeleteModal.value = false
    });
};

const executeEmptyTrash = () => {
    const routeName = itemType.value === 'patient' 
        ? 'admin.trace-patient.patients.empty' 
        : 'admin.trace-patient.prescriptions.empty';

    router.delete(route(routeName), {
        preserveScroll: true,
        onSuccess: () => showEmptyTrashModal.value = false
    });
};

</script>

<template>
    <Head title="Gestion de la Corbeille" />

    <AppLayout>
        <div class="min-h-screen transition-colors duration-200">
            <!-- Header -->
            <div class="dark:border-gray-700 shadow-sm bg-white dark:bg-gray-800">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-4">
                            <div class="flex items-center">
                                <span class="text-red-600 dark:text-red-400 text-xl mr-3">🗑️</span>
                                <h1 class="text-xl font-semibold text-gray-900 dark:text-white">Gestion de la Corbeille</h1>
                            </div>
                        </div>
                        
                        <div class="flex items-center space-x-4">
                            <!-- Compteur global -->
                            <div class="text-sm bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-200 px-3 py-1 rounded-full">
                                {{ stats.total }} élément(s) supprimé(s)
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 py-6">
                <!-- Onglets -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 mb-6">
                    <div class="border-b border-gray-200 dark:border-gray-700">
                        <nav class="-mb-px flex space-x-8 px-6" aria-label="Tabs">
                            <!-- Onglet Prescriptions -->
                            <button
                                @click="activeTab = 'prescriptions'"
                                :class="[
                                    activeTab === 'prescriptions' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300',
                                    'py-4 px-1 border-b-2 font-medium text-sm flex items-center'
                                ]"
                            >
                                <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 24 24"><path d="M6 2v6h.01L6 8.01 10 12l-4 4 .01.01H6V22h12v-5.99h-.01L18 16l-4-4 4-3.99-.01-.01H18V2H6zm10 14.5V20H8v-3.5l4-4 4 4zm0-9V12l-4 4-4-4V7.5h8z"/></svg>
                                Prescriptions ({{ stats.prescriptions.total }})
                            </button>
                            
                            <!-- Onglet Patients -->
                            <button
                                @click="activeTab = 'patients'"
                                :class="[
                                    activeTab === 'patients' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300',
                                    'py-4 px-1 border-b-2 font-medium text-sm flex items-center'
                                ]"
                            >
                                <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 24 24"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/><path d="M19 3h-1V1h-2v2H8V1H6v2H5c-1.11 0-1.99.9-1.99 2L3 19c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2z"/></svg>
                                Patients ({{ stats.patients.total }})
                            </button>
                        </nav>
                    </div>
                </div>

                <!-- Statistiques Patients -->
                <div v-show="activeTab === 'patients'" class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                    <div class="bg-gradient-to-r from-red-50 to-red-100 dark:from-red-900/20 dark:to-red-800/20 px-4 py-3 rounded-xl border border-red-200 dark:border-red-700">
                        <p class="text-xs font-medium text-red-600 dark:text-red-400 uppercase tracking-wide">Total patients</p>
                        <p class="text-xl font-bold text-red-800 dark:text-red-300">{{ stats.patients.total }}</p>
                    </div>
                    <div class="bg-gradient-to-r from-orange-50 to-orange-100 dark:from-orange-900/20 dark:to-orange-800/20 px-4 py-3 rounded-xl border border-orange-200 dark:border-orange-700">
                        <p class="text-xs font-medium text-orange-600 dark:text-orange-400 uppercase tracking-wide">Récents</p>
                        <p class="text-xl font-bold text-orange-800 dark:text-orange-300">{{ stats.patients.recent }}</p>
                        <p class="text-xs text-orange-700 dark:text-orange-400 mt-1">(7 derniers jours)</p>
                    </div>
                    <div class="bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-900/20 dark:to-gray-800/20 px-4 py-3 rounded-xl border border-gray-200 dark:border-gray-700">
                        <p class="text-xs font-medium text-gray-600 dark:text-gray-400 uppercase tracking-wide">Anciens</p>
                        <p class="text-xl font-bold text-gray-800 dark:text-gray-300">{{ stats.patients.old }}</p>
                        <p class="text-xs text-gray-700 dark:text-gray-400 mt-1">(plus de 30 jours)</p>
                    </div>
                    <div class="bg-gradient-to-r from-blue-50 to-blue-100 dark:from-blue-900/20 dark:to-blue-800/20 px-4 py-3 rounded-xl border border-blue-200 dark:border-blue-700">
                        <p class="text-xs font-medium text-blue-600 dark:text-blue-400 uppercase tracking-wide">Avec prescriptions</p>
                        <p class="text-xl font-bold text-blue-800 dark:text-blue-300">{{ stats.patients.with_prescriptions }}</p>
                        <p class="text-xs text-blue-700 dark:text-blue-400 mt-1">(à vérifier)</p>
                    </div>
                </div>

                <!-- Statistiques Prescriptions -->
                <div v-show="activeTab === 'prescriptions'" class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                    <div class="bg-gradient-to-r from-purple-50 to-purple-100 dark:from-purple-900/20 dark:to-purple-800/20 px-4 py-3 rounded-xl border border-purple-200 dark:border-purple-700">
                        <p class="text-xs font-medium text-purple-600 dark:text-purple-400 uppercase tracking-wide">Total prescriptions</p>
                        <p class="text-xl font-bold text-purple-800 dark:text-purple-300">{{ stats.prescriptions.total }}</p>
                    </div>
                    <div class="bg-gradient-to-r from-orange-50 to-orange-100 dark:from-orange-900/20 dark:to-orange-800/20 px-4 py-3 rounded-xl border border-orange-200 dark:border-orange-700">
                        <p class="text-xs font-medium text-orange-600 dark:text-orange-400 uppercase tracking-wide">Récentes</p>
                        <p class="text-xl font-bold text-orange-800 dark:text-orange-300">{{ stats.prescriptions.recent }}</p>
                        <p class="text-xs text-orange-700 dark:text-orange-400 mt-1">(7 derniers jours)</p>
                    </div>
                    <div class="bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-900/20 dark:to-gray-800/20 px-4 py-3 rounded-xl border border-gray-200 dark:border-gray-700">
                        <p class="text-xs font-medium text-gray-600 dark:text-gray-400 uppercase tracking-wide">Anciennes</p>
                        <p class="text-xl font-bold text-gray-800 dark:text-gray-300">{{ stats.prescriptions.old }}</p>
                        <p class="text-xs text-gray-700 dark:text-gray-400 mt-1">(plus de 30 jours)</p>
                    </div>
                    <div class="bg-gradient-to-r from-green-50 to-green-100 dark:from-green-900/20 dark:to-green-800/20 px-4 py-3 rounded-xl border border-green-200 dark:border-green-700">
                        <p class="text-xs font-medium text-green-600 dark:text-green-400 uppercase tracking-wide">Valeur perdue</p>
                        <p class="text-xl font-bold text-green-800 dark:text-green-300">{{ formatNumber(stats.prescriptions.valeur_totale) }} Ar</p>
                        <p class="text-xs text-green-700 dark:text-green-400 mt-1">(montant total)</p>
                    </div>
                </div>

                <!-- Contenu principal -->
                <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm">
                    <!-- Barre de recherche et actions -->
                    <div class="p-4 border-b border-gray-200 dark:border-gray-700 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                        <div class="relative flex-1 max-w-md">
                            <input 
                                type="text" 
                                v-model="search"
                                class="block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700 focus:ring-2 focus:ring-blue-500 focus:border-transparent text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400"
                                placeholder="Rechercher..."
                            >
                        </div>
                        <div class="flex items-center space-x-2 w-full md:w-auto">
                            <button 
                                v-if="activeTab === 'patients'"
                                @click="confirmEmptyTrash('patient')"
                                :disabled="patients.data.length === 0"
                                class="w-full md:w-auto px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition-colors flex items-center justify-center space-x-2 disabled:opacity-50"
                            >
                                <span>Vider corbeille patients</span>
                            </button>
                            <button 
                                v-else
                                @click="confirmEmptyTrash('prescription')"
                                :disabled="prescriptions.data.length === 0"
                                class="w-full md:w-auto px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition-colors flex items-center justify-center space-x-2 disabled:opacity-50"
                            >
                                <span>Vider corbeille prescriptions</span>
                            </button>
                        </div>
                    </div>

                    <!-- Tableau Patients -->
                    <div v-show="activeTab === 'patients'" class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Patient</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Dossier</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Prescriptions liées</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Supprimé le</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200 dark:bg-gray-800 dark:divide-gray-700">
                                <tr v-for="patient in patients.data" :key="patient.id" class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="font-medium text-gray-900 dark:text-white">{{ patient.nom }} {{ patient.prenom }}</div>
                                        <div class="text-sm text-gray-500 dark:text-gray-400">{{ patient.telephone }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                            {{ patient.numero_dossier }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span v-if="patient.prescriptions_count > 0" class="px-2 py-1 text-xs rounded-full bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">
                                            {{ patient.prescriptions_count }} prescription(s)
                                        </span>
                                        <span v-else class="text-gray-400 dark:text-gray-500 text-xs">Aucune</span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                        {{ formatDate(patient.deleted_at) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <div class="flex space-x-3">
                                            <button @click="confirmRestore(patient, 'patient')" class="text-green-600 hover:text-green-900 dark:text-green-400 dark:hover:text-green-300">Restaurer</button>
                                            <button @click="confirmDelete(patient, 'patient')" class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300">Supprimer</button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="patients.data.length === 0">
                                    <td colspan="5" class="px-6 py-12 text-center text-gray-500">Aucun patient dans la corbeille</td>
                                </tr>
                            </tbody>
                        </table>
                        <Pagination v-if="patients.links && patients.links.length > 3" :links="patients.links" class="p-4" />
                    </div>

                    <!-- Tableau Prescriptions -->
                    <div v-show="activeTab === 'prescriptions'" class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Référence</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Patient</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Prescripteur</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Montant</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Supprimé le</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200 dark:bg-gray-800 dark:divide-gray-700">
                                <tr v-for="prescription in prescriptions.data" :key="prescription.id" class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 py-1 text-xs rounded-full bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200">
                                            {{ prescription.reference }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900 dark:text-white">{{ prescription.patient?.nom || 'N/A' }} {{ prescription.patient?.prenom || '' }}</div>
                                        <div class="text-sm text-gray-500 dark:text-gray-400">{{ prescription.patient?.numero_dossier || '' }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                        {{ prescription.prescripteur?.nom || 'N/A' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="text-sm border py-1 px-2 rounded font-medium text-gray-900 dark:text-white dark:border-gray-600">
                                            {{ formatNumber(prescription.montant_total) }} Ar
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                        {{ formatDate(prescription.deleted_at) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <div class="flex space-x-3">
                                            <button @click="confirmRestore(prescription, 'prescription')" class="text-green-600 hover:text-green-900 dark:text-green-400 dark:hover:text-green-300">Restaurer</button>
                                            <button @click="confirmDelete(prescription, 'prescription')" class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300">Supprimer</button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="prescriptions.data.length === 0">
                                    <td colspan="6" class="px-6 py-12 text-center text-gray-500">Aucune prescription dans la corbeille</td>
                                </tr>
                            </tbody>
                        </table>
                        <Pagination v-if="prescriptions.links && prescriptions.links.length > 3" :links="prescriptions.links" class="p-4" />
                    </div>
                </div>
            </div>
            
            <!-- Modals -->
            <!-- Modal Restore -->
            <Modal :show="showRestoreModal" @close="showRestoreModal = false">
                <div class="p-6">
                    <h2 class="text-lg font-medium text-gray-900 dark:text-white">
                        Restaurer {{ itemType === 'patient' ? 'ce patient' : 'cette prescription' }} ?
                    </h2>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        Êtes-vous sûr de vouloir restaurer l'élément sélectionné ? Il sera à nouveau visible dans l'application.
                    </p>
                    <div class="mt-6 flex justify-end space-x-3">
                        <button @click="showRestoreModal = false" class="px-4 py-2 border rounded-md text-gray-700 dark:text-gray-300">Annuler</button>
                        <button @click="executeRestore" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">Restaurer</button>
                    </div>
                </div>
            </Modal>

            <!-- Modal Delete -->
            <Modal :show="showDeleteModal" @close="showDeleteModal = false">
                <div class="p-6">
                    <h2 class="text-lg font-medium text-gray-900 dark:text-white">
                        Supprimer définitivement {{ itemType === 'patient' ? 'ce patient' : 'cette prescription' }} ?
                    </h2>
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">
                        Cette action est irréversible. Toutes les données associées seront supprimées définitivement de la base de données.
                    </p>
                    <div class="mt-6 flex justify-end space-x-3">
                        <button @click="showDeleteModal = false" class="px-4 py-2 border rounded-md text-gray-700 dark:text-gray-300">Annuler</button>
                        <button @click="executeDelete" class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700">Supprimer définitivement</button>
                    </div>
                </div>
            </Modal>

            <!-- Modal Empty Trash -->
            <Modal :show="showEmptyTrashModal" @close="showEmptyTrashModal = false">
                <div class="p-6">
                    <h2 class="text-lg font-medium text-gray-900 dark:text-white">
                        Vider la corbeille des {{ itemType === 'patient' ? 'patients' : 'prescriptions' }} ?
                    </h2>
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">
                        Cette action est irréversible. <strong>Tous</strong> les éléments de la corbeille seront supprimés définitivement.
                    </p>
                    <div class="mt-6 flex justify-end space-x-3">
                        <button @click="showEmptyTrashModal = false" class="px-4 py-2 border rounded-md text-gray-700 dark:text-gray-300">Annuler</button>
                        <button @click="executeEmptyTrash" class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700">Vider la corbeille complète</button>
                    </div>
                </div>
            </Modal>

        </div>
    </AppLayout>
</template>
