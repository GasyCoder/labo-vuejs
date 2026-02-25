<script setup>
import { ref, computed, watch } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    prescriptions: Object,
    stats: Object,
    filters: Object,
});

const form = useForm({
    tab: props.filters.tab || 'en_attente',
    search: props.filters.search || '',
});

// Debounced search
let searchTimeout = null;
const updateSearch = (value) => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get(route('technicien.index'), {
            tab: form.tab,
            search: value,
        }, {
            preserveState: true,
            preserveScroll: true,
        });
    }, 300);
};

watch(() => form.search, updateSearch);

const changeTab = (tab) => {
    form.tab = tab;
    router.get(route('technicien.index'), {
        tab: tab,
        search: form.search,
    }, {
        preserveState: true,
        preserveScroll: false,
    });
};

const tabClass = (tab) => {
    const colors = {
        en_attente: {
            active: 'border-blue-500 text-blue-600 bg-blue-50 dark:bg-blue-900/20',
            dot: 'bg-blue-500',
            badge: 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300',
        },
        termine: {
            active: 'border-teal-500 text-teal-600 bg-teal-50 dark:bg-teal-900/20',
            dot: 'bg-teal-500',
            badge: 'bg-teal-100 text-teal-800 dark:bg-teal-900/40 dark:text-teal-300',
        },
        a_refaire: {
            active: 'border-red-500 text-red-600 bg-red-50 dark:bg-red-900/20',
            dot: 'bg-red-500',
            badge: 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300',
        },
    };
    return colors[tab] || colors.en_attente;
};

const getInitials = (patient) => {
    if (!patient) return '??';
    return (patient.prenom?.[0] || '') + (patient.nom?.[0] || '');
};

const formatDate = (dateStr) => {
    if (!dateStr) return '';
    const d = new Date(dateStr);
    return d.toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' });
};

const truncate = (str, len = 18) => {
    if (!str) return '';
    return str.length > len ? str.substring(0, len) + '…' : str;
};

const startAnalysis = (id) => {
    router.post(route('technicien.prescription.start', id), {}, {
        preserveScroll: true,
    });
};

const continueAnalysis = (id) => {
    router.post(route('technicien.prescription.continue', id));
};

// --- Custom Confirmation Modal Logic ---
const showConfirmModal = ref(false);
const confirmTargetId = ref(null);
const actionProcessing = ref(false);

const redoAnalysis = (id) => {
    confirmTargetId.value = id;
    showConfirmModal.value = true;
};

const submitRedoAnalysis = () => {
    if (!confirmTargetId.value) return;
    actionProcessing.value = true;
    router.post(route('technicien.prescription.redo', confirmTargetId.value), {}, {
        preserveScroll: true,
        onSuccess: () => {
            showConfirmModal.value = false;
            actionProcessing.value = false;
        },
        onError: () => {
            actionProcessing.value = false;
        }
    });
};
</script>

<template>
    <Head title="Gestion des Analyses" />

    <AppLayout>
        <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8">

            <!-- Header -->
            <div class="mb-8">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                            Gestion des Analyses
                        </h1>
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                            Saisie et validation des résultats d'analyses
                        </p>
                    </div>
                    <div class="flex items-center gap-2 px-3 py-1.5 bg-green-50 dark:bg-green-900/30 rounded-full border border-green-200 dark:border-green-800">
                        <div class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></div>
                        <span class="text-sm font-medium text-green-700 dark:text-green-400">Système en ligne</span>
                    </div>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <!-- Toutes (EN_ATTENTE + EN_COURS) -->
                <div class="bg-gradient-to-r from-blue-500 to-blue-600 rounded-xl p-5 text-white shadow-lg cursor-pointer hover:shadow-xl transition-shadow"
                     @click="changeTab('en_attente')">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-blue-100 text-xs font-medium uppercase tracking-wider">Toutes</p>
                            <p class="text-3xl font-bold mt-1">{{ stats.toutes || 0 }}</p>
                            <p class="text-blue-200 text-xs mt-1">{{ stats.en_attente || 0 }} en attente · {{ stats.en_cours || 0 }} en cours</p>
                        </div>
                        <div class="p-3 bg-white/20 rounded-lg">
                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd" />
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Terminé -->
                <div class="bg-gradient-to-r from-teal-500 to-teal-600 rounded-xl p-5 text-white shadow-lg cursor-pointer hover:shadow-xl transition-shadow"
                     @click="changeTab('termine')">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-teal-100 text-xs font-medium uppercase tracking-wider">Terminé</p>
                            <p class="text-3xl font-bold mt-1">{{ stats.termine || 0 }}</p>
                        </div>
                        <div class="p-3 bg-white/20 rounded-lg">
                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- À refaire -->
                <div class="bg-gradient-to-r from-red-500 to-red-600 rounded-xl p-5 text-white shadow-lg cursor-pointer hover:shadow-xl transition-shadow"
                     @click="changeTab('a_refaire')">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-red-100 text-xs font-medium uppercase tracking-wider">À refaire</p>
                            <p class="text-3xl font-bold mt-1">{{ stats.a_refaire || 0 }}</p>
                        </div>
                        <div class="p-3 bg-white/20 rounded-lg">
                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Search -->
            <div class="mb-6">
                <div>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                        <input
                            type="text"
                            v-model="form.search"
                            placeholder="Rechercher par référence, patient, prescripteur..."
                            class="w-full pl-10 pr-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors bg-white dark:bg-gray-700"
                        >
                    </div>
                </div>
            </div>

            <!-- Tabs + Table -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                <!-- Tab Navigation -->
                <div class="border-b border-gray-200 dark:border-gray-700">
                    <nav class="-mb-px flex">
                        <button
                            @click="changeTab('en_attente')"
                            class="py-4 px-6 text-sm font-medium border-b-2 transition-colors"
                            :class="form.tab === 'en_attente'
                                ? tabClass('en_attente').active
                                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'"
                        >
                            <div class="flex items-center gap-2">
                                <div class="w-2 h-2 rounded-full" :class="tabClass('en_attente').dot"></div>
                                Toutes
                                <span class="text-xs px-2 py-0.5 rounded-full" :class="tabClass('en_attente').badge">{{ stats.toutes || 0 }}</span>
                            </div>
                        </button>

                        <button
                            @click="changeTab('termine')"
                            class="py-4 px-6 text-sm font-medium border-b-2 transition-colors"
                            :class="form.tab === 'termine'
                                ? tabClass('termine').active
                                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'"
                        >
                            <div class="flex items-center gap-2">
                                <div class="w-2 h-2 rounded-full" :class="tabClass('termine').dot"></div>
                                Terminé
                                <span class="text-xs px-2 py-0.5 rounded-full" :class="tabClass('termine').badge">{{ stats.termine || 0 }}</span>
                            </div>
                        </button>

                        <button
                            @click="changeTab('a_refaire')"
                            class="py-4 px-6 text-sm font-medium border-b-2 transition-colors"
                            :class="form.tab === 'a_refaire'
                                ? tabClass('a_refaire').active
                                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'"
                        >
                            <div class="flex items-center gap-2">
                                <div class="w-2 h-2 rounded-full" :class="tabClass('a_refaire').dot"></div>
                                À refaire
                                <span class="text-xs px-2 py-0.5 rounded-full" :class="tabClass('a_refaire').badge">{{ stats.a_refaire || 0 }}</span>
                            </div>
                        </button>
                    </nav>
                </div>

                <!-- Table -->
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead :class="{
                            'bg-blue-50 dark:bg-blue-900/20': form.tab === 'en_attente',
                            'bg-teal-50 dark:bg-teal-900/20': form.tab === 'termine',
                            'bg-red-50 dark:bg-red-900/20': form.tab === 'a_refaire',
                        }">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider"
                                    :class="{
                                        'text-blue-700 dark:text-blue-300': form.tab === 'en_attente',
                                        'text-teal-700 dark:text-teal-300': form.tab === 'termine',
                                        'text-red-700 dark:text-red-300': form.tab === 'a_refaire',
                                    }">Référence</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider"
                                    :class="{
                                        'text-blue-700 dark:text-blue-300': form.tab === 'en_attente',
                                        'text-teal-700 dark:text-teal-300': form.tab === 'termine',
                                        'text-red-700 dark:text-red-300': form.tab === 'a_refaire',
                                    }">Patient</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider"
                                    :class="{
                                        'text-blue-700 dark:text-blue-300': form.tab === 'en_attente',
                                        'text-teal-700 dark:text-teal-300': form.tab === 'termine',
                                        'text-red-700 dark:text-red-300': form.tab === 'a_refaire',
                                    }">Prescripteur</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider"
                                    :class="{
                                        'text-blue-700 dark:text-blue-300': form.tab === 'en_attente',
                                        'text-teal-700 dark:text-teal-300': form.tab === 'termine',
                                        'text-red-700 dark:text-red-300': form.tab === 'a_refaire',
                                    }">Analyses</th>
                                <th v-if="form.tab === 'en_attente'" class="px-6 py-3 text-left text-xs font-medium text-blue-700 dark:text-blue-300 uppercase tracking-wider">Statut</th>
                                <th v-if="form.tab === 'a_refaire'" class="px-6 py-3 text-left text-xs font-medium text-red-700 dark:text-red-300 uppercase tracking-wider">Raison</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider"
                                    :class="{
                                        'text-blue-700 dark:text-blue-300': form.tab === 'en_attente',
                                        'text-teal-700 dark:text-teal-300': form.tab === 'termine',
                                        'text-red-700 dark:text-red-300': form.tab === 'a_refaire',
                                    }">Date</th>
                                <th v-if="form.tab !== 'termine'" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider"
                                    :class="{
                                        'text-blue-700 dark:text-blue-300': form.tab === 'en_attente',
                                        'text-red-700 dark:text-red-300': form.tab === 'a_refaire',
                                    }">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            <tr v-for="prescription in prescriptions.data" :key="prescription.id"
                                class="transition-colors"
                                :class="{
                                    'hover:bg-blue-50 dark:hover:bg-blue-900/10': form.tab === 'en_attente',
                                    'hover:bg-teal-50 dark:hover:bg-teal-900/10': form.tab === 'termine',
                                    'hover:bg-red-50 dark:hover:bg-red-900/10': form.tab === 'a_refaire',
                                }">
                                <!-- Référence -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-semibold text-gray-900 dark:text-white">
                                        {{ prescription.reference }}
                                    </div>
                                </td>

                                <!-- Patient -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="w-8 h-8 rounded-full flex items-center justify-center font-medium text-sm mr-3"
                                            :class="{
                                                'bg-blue-100 dark:bg-blue-900 text-blue-600 dark:text-blue-300': form.tab === 'en_attente',
                                                'bg-teal-100 dark:bg-teal-900 text-teal-600 dark:text-teal-300': form.tab === 'termine',
                                                'bg-red-100 dark:bg-red-900 text-red-600 dark:text-red-300': form.tab === 'a_refaire',
                                            }">
                                            {{ getInitials(prescription.patient) }}
                                        </div>
                                        <div>
                                            <div class="text-sm font-medium text-gray-900 dark:text-white">
                                                {{ truncate((prescription.patient?.nom || 'N/A') + ' ' + (prescription.patient?.prenom || '')) }}
                                            </div>
                                            <div v-if="prescription.age && prescription.unite_age" class="text-xs text-gray-500 dark:text-gray-400">
                                                {{ prescription.age }} {{ prescription.unite_age }}
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Prescripteur -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900 dark:text-white">
                                        {{ truncate((prescription.prescripteur?.nom || 'N/A') + ' ' + (prescription.prescripteur?.prenom || '')) }}
                                    </div>
                                </td>

                                <!-- Analyses count -->
                                <td class="px-6 py-4">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">
                                        {{ prescription.analyses?.length || 0 }} analyses
                                    </div>
                                </td>

                                <!-- Status (en_attente tab only) -->
                                <td v-if="form.tab === 'en_attente'" class="px-6 py-4 whitespace-nowrap">
                                    <span v-if="prescription.status === 'EN_ATTENTE'"
                                        class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900/40 dark:text-yellow-300">
                                        <span class="w-1.5 h-1.5 bg-yellow-500 rounded-full"></span>
                                        En attente
                                    </span>
                                    <span v-else-if="prescription.status === 'EN_COURS'"
                                        class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300">
                                        <span class="w-1.5 h-1.5 bg-blue-500 rounded-full"></span>
                                        En cours
                                    </span>
                                </td>

                                <!-- Raison (a_refaire tab only) -->
                                <td v-if="form.tab === 'a_refaire'" class="px-6 py-4">
                                    <div class="text-sm text-red-600 dark:text-red-400">
                                        {{ prescription.commentaire_biologiste || 'Résultats à vérifier' }}
                                    </div>
                                </td>

                                <!-- Date -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900 dark:text-white">
                                        {{ formatDate(form.tab === 'termine' ? prescription.updated_at : prescription.created_at) }}
                                    </div>
                                </td>

                                <!-- Actions -->
                                <td v-if="form.tab !== 'termine'" class="px-6 py-4 whitespace-nowrap">
                                    <!-- En attente Tab actions -->
                                    <template v-if="form.tab === 'en_attente'">
                                        <button v-if="prescription.status === 'EN_ATTENTE'"
                                            @click="startAnalysis(prescription.id)"
                                            class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors flex items-center gap-2">
                                            🔬 <span>Traiter</span>
                                        </button>
                                        <button v-else-if="prescription.status === 'EN_COURS'"
                                            @click="continueAnalysis(prescription.id)"
                                            class="bg-orange-600 hover:bg-orange-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors flex items-center gap-2">
                                            ▶️ <span>Continuer</span>
                                        </button>
                                    </template>

                                    <!-- A refaire Tab actions -->
                                    <template v-if="form.tab === 'a_refaire'">
                                        <button @click="redoAnalysis(prescription.id)"
                                            class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                                            Recommencer
                                        </button>
                                    </template>
                                </td>
                            </tr>

                            <!-- Empty state -->
                            <tr v-if="!prescriptions.data || prescriptions.data.length === 0">
                                <td :colspan="form.tab === 'termine' ? 6 : 7" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                                    <div class="flex flex-col items-center">
                                        <svg class="w-12 h-12 mb-3 text-gray-300 dark:text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                        </svg>
                                        <span class="text-sm font-medium">
                                            {{ form.tab === 'en_attente' ? 'Aucune analyse en attente' : (form.tab === 'termine' ? 'Aucune analyse terminée' : 'Aucune analyse à refaire') }}
                                        </span>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="prescriptions.links && prescriptions.links.length > 3" class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                    <nav class="flex items-center justify-between">
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            Affichage de {{ prescriptions.from }} à {{ prescriptions.to }} sur {{ prescriptions.total }} résultats
                        </p>
                        <div class="flex gap-1">
                            <template v-for="link in prescriptions.links" :key="link.label">
                                <Link
                                    v-if="link.url"
                                    :href="link.url"
                                    class="px-3 py-1.5 text-sm rounded-lg transition-colors"
                                    :class="link.active
                                        ? 'bg-primary-600 text-white'
                                        : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'"
                                    v-html="link.label"
                                    preserve-state
                                />
                                <span v-else class="px-3 py-1.5 text-sm text-gray-400 dark:text-gray-500" v-html="link.label" />
                            </template>
                        </div>
                    </nav>
                </div>
            </div>
        </div>

        <!-- Custom Confirmation Modal -->
        <Transition
            enter-active-class="transition ease-out duration-200"
            enter-from-class="opacity-0 scale-95"
            enter-to-class="opacity-100 scale-100"
            leave-active-class="transition ease-in duration-150"
            leave-from-class="opacity-100 scale-100"
            leave-to-class="opacity-0 scale-95"
        >
            <div v-if="showConfirmModal" class="fixed inset-0 z-[1040] flex items-end sm:items-center justify-center p-4 sm:p-0" role="dialog" aria-modal="true" aria-labelledby="confirm-title">
                <div class="fixed inset-0 bg-black/60 backdrop-blur-sm transition-opacity" @click="showConfirmModal = false"></div>
                <div class="relative bg-white dark:bg-gray-800 w-full sm:max-w-md rounded-2xl sm:rounded-2xl shadow-2xl z-10 overflow-hidden">
                    <div class="p-6 text-center">
                        <div class="mx-auto flex items-center justify-center w-16 h-16 rounded-full bg-red-100 dark:bg-red-900/40 mb-5">
                            <svg class="h-8 w-8 text-red-600 dark:text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" />
                            </svg>
                        </div>
                        <h3 id="confirm-title" class="text-xl font-bold text-gray-900 dark:text-white mb-2">Recommencer l'analyse ?</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">
                            Êtes-vous sûr de vouloir recommencer cette analyse ? Les données actuellement saisies ou en cours seront effacées ou réinitialisées.
                        </p>

                        <div class="flex flex-col-reverse sm:flex-row items-center justify-center gap-3">
                            <button
                                @click="showConfirmModal = false"
                                :disabled="actionProcessing"
                                class="w-full sm:w-auto px-6 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 dark:focus:ring-offset-gray-800"
                            >
                                Annuler
                            </button>
                            <button
                                @click="submitRedoAnalysis"
                                :disabled="actionProcessing"
                                class="w-full sm:w-auto px-6 py-2.5 text-sm font-semibold text-white bg-red-600 hover:bg-red-700 active:bg-red-800 rounded-lg shadow-sm transition-all focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 dark:focus:ring-offset-gray-800 flex justify-center items-center disabled:opacity-75 disabled:cursor-not-allowed"
                            >
                                <svg v-if="actionProcessing" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                </svg>
                                <span>{{ actionProcessing ? 'Traitement...' : 'Oui, recommencer' }}</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>

    </AppLayout>
</template>
