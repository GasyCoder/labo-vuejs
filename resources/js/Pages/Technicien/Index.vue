<script setup>
import { ref, computed, watch } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { formatDistanceToNow } from 'date-fns';
import { fr } from 'date-fns/locale';

const props = defineProps({
    prescriptions: Object,
    stats: Object,
    filters: Object,
});

const form = useForm({
    tab: props.filters.tab || 'en_attente',
    search: props.filters.search || '',
    date_start: props.filters.date_start || '',
    date_end: props.filters.date_end || '',
});

// View mode state (list or grid)
const viewMode = ref(localStorage.getItem('technicien_view_mode') || 'list');

const toggleViewMode = (mode) => {
    viewMode.value = mode;
    localStorage.setItem('technicien_view_mode', mode);
};

// Debounced search
let searchTimeout = null;
const updateSearch = () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get(route('technicien.index'), {
            tab: form.tab,
            search: form.search,
            date_start: form.date_start,
            date_end: form.date_end,
        }, {
            preserveState: true,
            preserveScroll: true,
        });
    }, 300);
};

watch(() => form.search, updateSearch);
watch(() => form.date_start, updateSearch);
watch(() => form.date_end, updateSearch);

const changeTab = (tab) => {
    form.tab = tab;
    router.get(route('technicien.index'), {
        tab: tab,
        search: form.search,
        date_start: form.date_start,
        date_end: form.date_end,
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

const statusClass = (status) => {
    switch (status) {
        case 'EN_ATTENTE':
        case 'en_attente':
            return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/40 dark:text-yellow-300 border-yellow-200 dark:border-yellow-800';
        case 'EN_COURS':
            return 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300 border-blue-200 dark:border-blue-800';
        case 'TERMINE':
        case 'termine':
            return 'bg-teal-100 text-teal-800 dark:bg-teal-900/40 dark:text-teal-300 border-teal-200 dark:border-teal-800';
        case 'A_REFAIRE':
        case 'a_refaire':
            return 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300 border-red-200 dark:border-red-800';
        case 'VALIDE':
            return 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800';
        default:
            return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300 border-gray-200 dark:border-gray-600';
    }
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

const formatTimeAgo = (dateStr) => {
    if (!dateStr) return '';
    try {
        let distance = formatDistanceToNow(new Date(dateStr), { addSuffix: true, locale: fr });
        let result = distance
            .replace('environ ', '')
            .replace(' minutes', ' min')
            .replace(' minute', ' min');
        
        // Force la première lettre en majuscule et le reste en minuscule
        return result.charAt(0).toUpperCase() + result.slice(1).toLowerCase();
    } catch (e) {
        return '';
    }
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
        <div class="w-full px-2 sm:px-4 py-8">

            <!-- Header -->
            <div class="mb-8 px-2">
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
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8">
                <!-- Toutes (EN_ATTENTE + EN_COURS) -->
                <div class="bg-white dark:bg-slate-800 rounded-xl p-4 shadow-sm border border-slate-200 dark:border-slate-700 cursor-pointer hover:border-blue-500 dark:hover:border-blue-400 transition-all group"
                     @click="changeTab('en_attente')">
                    <div class="flex items-center justify-between">
                        <div class="flex flex-col">
                            <p class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-1">À traiter</p>
                            <div class="flex items-baseline gap-2">
                                <p class="text-2xl font-black text-slate-900 dark:text-white leading-none">{{ stats.toutes || 0 }}</p>
                                <span class="text-[10px] font-bold text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/30 px-1.5 py-0.5 rounded uppercase">{{ stats.en_cours || 0 }} en cours</span>
                            </div>
                        </div>
                        <div class="h-10 w-10 rounded-lg bg-blue-50 dark:bg-blue-900/20 flex items-center justify-center text-blue-600 dark:text-blue-400 group-hover:scale-110 transition-transform">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                    </div>
                </div>

                <!-- Terminé -->
                <div class="bg-white dark:bg-slate-800 rounded-xl p-4 shadow-sm border border-slate-200 dark:border-slate-700 cursor-pointer hover:border-teal-500 dark:hover:border-teal-400 transition-all group"
                     @click="changeTab('termine')">
                    <div class="flex items-center justify-between">
                        <div class="flex flex-col">
                            <p class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-1">Terminé</p>
                            <p class="text-2xl font-black text-slate-900 dark:text-white leading-none">{{ stats.termine || 0 }}</p>
                        </div>
                        <div class="h-10 w-10 rounded-lg bg-teal-50 dark:bg-teal-900/20 flex items-center justify-center text-teal-600 dark:text-teal-400 group-hover:scale-110 transition-transform">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                    </div>
                </div>

                <!-- À refaire -->
                <div class="bg-white dark:bg-slate-800 rounded-xl p-4 shadow-sm border border-slate-200 dark:border-slate-700 cursor-pointer hover:border-red-500 dark:hover:border-red-400 transition-all group"
                     @click="changeTab('a_refaire')">
                    <div class="flex items-center justify-between">
                        <div class="flex flex-col">
                            <p class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-1">À refaire</p>
                            <p class="text-2xl font-black text-slate-900 dark:text-white leading-none">{{ stats.a_refaire || 0 }}</p>
                        </div>
                        <div class="h-10 w-10 rounded-lg bg-red-50 dark:bg-red-900/20 flex items-center justify-center text-red-600 dark:text-red-400 group-hover:scale-110 transition-transform">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Search & View Mode -->
            <div class="mb-6 flex flex-col sm:flex-row gap-4">
                <div class="flex-1 px-2">
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
                            class="w-full pl-10 pr-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors bg-white dark:bg-gray-700 shadow-sm"
                        >
                    </div>
                </div>

                <!-- View Mode Toggle -->
                <div class="flex items-center bg-gray-100 dark:bg-gray-700/50 p-1 rounded-xl border border-gray-200 dark:border-gray-600 h-[50px] mr-2">
                    <button 
                        @click="toggleViewMode('list')"
                        class="flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-black transition-all uppercase tracking-tighter"
                        :class="viewMode === 'list' 
                            ? 'bg-white dark:bg-gray-600 text-primary-600 dark:text-primary-400 shadow-sm' 
                            : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200'"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                        <span class="hidden md:inline">Liste</span>
                    </button>
                    <button 
                        @click="toggleViewMode('grid')"
                        class="flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-black transition-all uppercase tracking-tighter"
                        :class="viewMode === 'grid' 
                            ? 'bg-white dark:bg-gray-600 text-primary-600 dark:text-primary-400 shadow-sm' 
                            : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200'"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2z"/></svg>
                        <span class="hidden md:inline">Grilles</span>
                    </button>
                </div>

                <!-- Date Filters -->
                <div class="flex items-center gap-2">
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <em class="ni ni-calendar-alt text-slate-400 text-xs"></em>
                        </div>
                        <input 
                            type="date" 
                            v-model="form.date_start"
                            class="pl-9 pr-3 py-2.5 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl text-xs font-bold text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 shadow-sm h-[50px]"
                            title="Date de début"
                        >
                    </div>
                    <span class="text-slate-400 font-black">/</span>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <em class="ni ni-calendar-alt text-slate-400 text-xs"></em>
                        </div>
                        <input 
                            type="date" 
                            v-model="form.date_end"
                            class="pl-9 pr-3 py-2.5 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl text-xs font-bold text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 shadow-sm h-[50px]"
                            title="Date de fin"
                        >
                    </div>
                </div>
            </div>

            <!-- Tabs Container -->
            <div class="bg-white dark:bg-gray-800 sm:rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden shadow-sm">
                <!-- Tab Navigation -->
                <div class="border-b border-gray-200 dark:border-gray-700">
                    <nav class="-mb-px flex overflow-x-auto">
                        <button
                            @click="changeTab('en_attente')"
                            class="py-4 px-6 text-sm font-bold border-b-2 transition-colors flex-shrink-0"
                            :class="form.tab === 'en_attente'
                                ? tabClass('en_attente').active
                                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'"
                        >
                            <div class="flex items-center gap-2">
                                <div class="w-2 h-2 rounded-full" :class="tabClass('en_attente').dot"></div>
                                À traiter
                                <span class="text-[10px] px-2 py-0.5 rounded-full font-black" :class="tabClass('en_attente').badge">{{ stats.toutes || 0 }}</span>
                            </div>
                        </button>

                        <button
                            @click="changeTab('termine')"
                            class="py-4 px-6 text-sm font-bold border-b-2 transition-colors flex-shrink-0"
                            :class="form.tab === 'termine'
                                ? tabClass('termine').active
                                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'"
                        >
                            <div class="flex items-center gap-2">
                                <div class="w-2 h-2 rounded-full" :class="tabClass('termine').dot"></div>
                                Terminé
                                <span class="text-[10px] px-2 py-0.5 rounded-full font-black" :class="tabClass('termine').badge">{{ stats.termine || 0 }}</span>
                            </div>
                        </button>

                        <button
                            @click="changeTab('a_refaire')"
                            class="py-4 px-6 text-sm font-bold border-b-2 transition-colors flex-shrink-0"
                            :class="form.tab === 'a_refaire'
                                ? tabClass('a_refaire').active
                                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'"
                        >
                            <div class="flex items-center gap-2">
                                <div class="w-2 h-2 rounded-full" :class="tabClass('a_refaire').dot"></div>
                                À refaire
                                <span class="text-[10px] px-2 py-0.5 rounded-full font-black" :class="tabClass('a_refaire').badge">{{ stats.a_refaire || 0 }}</span>
                            </div>
                        </button>
                    </nav>
                </div>

                <!-- List View (Table) -->
                <div v-if="viewMode === 'list'" class="w-full overflow-x-auto">
                    <table class="w-full divide-y divide-gray-200 dark:divide-gray-700 table-auto">
                        <thead :class="{
                            'bg-blue-50 dark:bg-blue-900/20': form.tab === 'en_attente',
                            'bg-teal-50 dark:bg-teal-900/20': form.tab === 'termine',
                            'bg-red-50 dark:bg-red-900/20': form.tab === 'a_refaire',
                        }">
                            <tr>
                                <th class="px-4 py-4 text-left text-xs font-bold uppercase tracking-wider"
                                    :class="{
                                        'text-blue-700 dark:text-blue-300': form.tab === 'en_attente',
                                        'text-teal-700 dark:text-teal-300': form.tab === 'termine',
                                        'text-red-700 dark:text-red-300': form.tab === 'a_refaire',
                                    }">Réf.</th>
                                <th class="px-4 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500">Personnel</th>
                                <th class="px-4 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500">Patient & Prescripteur</th>
                                <th class="px-4 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500">Analyses</th>
                                <th v-if="form.tab === 'en_attente'" class="px-4 py-4 text-left text-xs font-bold text-blue-700 dark:text-blue-300 uppercase tracking-wider">Statut</th>
                                <th v-if="form.tab === 'a_refaire'" class="px-4 py-4 text-left text-xs font-bold text-red-700 dark:text-red-300 uppercase tracking-wider">Raison</th>
                                <th class="px-4 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500">Date</th>
                                <th v-if="form.tab !== 'termine'" class="px-4 py-4 text-right text-xs font-bold uppercase tracking-wider text-slate-500">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            <tr v-for="prescription in prescriptions.data" :key="prescription.id"
                                class="transition-colors group"
                                :class="{
                                    'hover:bg-blue-50 dark:hover:bg-blue-900/10': form.tab === 'en_attente',
                                    'hover:bg-teal-50 dark:hover:bg-teal-900/10': form.tab === 'termine',
                                    'hover:bg-red-50 dark:hover:bg-red-900/10': form.tab === 'a_refaire',
                                }">
                                <!-- Référence -->
                                <td class="px-4 py-5">
                                    <div class="text-sm font-black text-gray-900 dark:text-white leading-tight">
                                        {{ prescription.reference }}
                                    </div>
                                </td>

                                <!-- Personnel / Traçabilité -->
                                <td class="px-4 py-5">
                                    <div class="flex flex-col gap-2">
                                        <!-- Saisie -->
                                        <div class="flex items-center gap-2" :title="'Saisi par : ' + (prescription.secretaire?.name || 'Système')">
                                            <div class="h-5 w-5 flex-shrink-0 rounded bg-blue-50 text-blue-600 dark:bg-blue-900/30 flex items-center justify-center border border-blue-100 dark:border-blue-800 shadow-sm">
                                                <em class="icon ni ni-user-alt text-xs"></em>
                                            </div>
                                            <span class="text-[11px] font-bold text-slate-700 dark:text-slate-300 truncate max-w-[110px]">{{ prescription.secretaire?.name || 'Système' }}</span>
                                        </div>
                                        <!-- Traitement -->
                                        <div v-if="prescription.technicien" class="flex items-center gap-2" :title="'Traité par : ' + prescription.technicien.name">
                                            <div class="h-5 w-5 flex-shrink-0 rounded bg-teal-50 text-teal-600 dark:bg-teal-900/30 flex items-center justify-center border border-teal-100 dark:border-teal-800 shadow-sm">
                                                <em class="icon ni ni-account-setting-fill text-xs"></em>
                                            </div>
                                            <span class="text-[11px] font-bold text-slate-700 dark:text-slate-300 truncate max-w-[110px]">{{ prescription.technicien.name }}</span>
                                        </div>
                                        <div v-else class="flex items-center gap-2 opacity-40">
                                            <div class="h-5 w-5 flex-shrink-0 rounded border border-dashed border-slate-300 dark:border-slate-700 flex items-center justify-center text-slate-400">
                                                <em class="ni ni-flask text-xs"></em>
                                            </div>
                                            <span class="text-[10px] font-medium text-slate-400 italic">À traiter</span>
                                        </div>
                                    </div>
                                </td>

                                <!-- Patient & Prescripteur -->
                                <td class="px-4 py-5">
                                    <div class="flex items-center min-w-0">
                                        <div class="w-9 h-9 rounded-xl flex-shrink-0 flex items-center justify-center font-black text-xs mr-3 shadow-sm"
                                            :class="{
                                                'bg-blue-100 dark:bg-blue-900 text-blue-600 dark:text-blue-300': form.tab === 'en_attente',
                                                'bg-teal-100 dark:bg-teal-900 text-teal-600 dark:text-teal-300': form.tab === 'termine',
                                                'bg-red-100 dark:bg-red-900 text-red-600 dark:text-red-300': form.tab === 'a_refaire',
                                            }">
                                            {{ getInitials(prescription.patient) }}
                                        </div>
                                        <div class="min-w-0 flex flex-col">
                                            <div class="text-sm font-bold text-gray-900 dark:text-white truncate max-w-[150px]">
                                                {{ (prescription.patient?.nom || 'N/A') + ' ' + (prescription.patient?.prenom || '') }}
                                            </div>
                                            <div class="text-[11px] text-gray-500 font-medium flex flex-wrap items-center gap-x-2 gap-y-0.5 mt-0.5">
                                                <span v-if="prescription.age" class="bg-slate-100 dark:bg-slate-700 px-1 rounded text-[9px] uppercase">{{ prescription.age }} {{ prescription.unite_age }}</span>
                                                <span class="flex items-center gap-1 text-slate-400">
                                                    <em class="ni ni-user text-[10px]"></em>
                                                    {{ prescription.prescripteur?.nom || 'Sans prescripteur' }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Analyses codes -->
                                <td class="px-4 py-5">
                                    <div class="flex items-center gap-2">
                                        <span class="inline-flex h-6 w-6 flex-shrink-0 items-center justify-center rounded-full bg-indigo-50 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300 text-[11px] font-black border border-indigo-100 dark:border-indigo-800 shadow-sm" title="Total des analyses">
                                            {{ prescription.analyses_count }}
                                        </span>
                                        <div class="flex flex-wrap gap-1 max-w-[150px]">
                                            <span v-for="(analyse, aIdx) in prescription.analyses.slice(0, 2)" :key="aIdx" 
                                                class="text-[10px] font-black px-2 py-0.5 rounded bg-slate-50 dark:bg-slate-700 text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-600 uppercase shadow-xs">
                                                {{ analyse.code }}
                                            </span>
                                            <span v-if="prescription.analyses.length > 2" class="text-[10px] font-bold text-slate-400 self-center">+{{ prescription.analyses.length - 2 }}</span>
                                        </div>
                                    </div>
                                </td>

                                <!-- Status (en_attente tab only) -->
                                <td v-if="form.tab === 'en_attente'" class="px-4 py-5">
                                    <span v-if="prescription.status === 'EN_ATTENTE'"
                                        class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-wider bg-yellow-100 text-yellow-800 dark:bg-yellow-900/40 dark:text-yellow-300 border border-yellow-200 dark:border-yellow-800">
                                        <span class="w-1.5 h-1.5 bg-yellow-500 rounded-full animate-pulse"></span>
                                        Attente
                                    </span>
                                    <span v-else-if="prescription.status === 'EN_COURS'"
                                        class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-wider bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300 border border-blue-200 dark:border-blue-800">
                                        <span class="w-1.5 h-1.5 bg-blue-500 rounded-full"></span>
                                        Saisie
                                    </span>
                                </td>

                                <!-- Raison (a_refaire tab only) -->
                                <td v-if="form.tab === 'a_refaire'" class="px-4 py-5">
                                    <div class="text-[11px] font-bold text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/20 p-2 rounded-lg border border-red-100 dark:border-red-800/50 max-w-[180px] line-clamp-2">
                                        {{ prescription.commentaire_biologiste || 'À vérifier' }}
                                    </div>
                                </td>

                                <!-- Date -->
                                <td class="px-4 py-5">
                                    <div class="flex flex-col">
                                        <span class="text-xs font-black text-blue-700 dark:text-blue-400 leading-tight">{{ formatTimeAgo(form.tab === 'termine' ? prescription.updated_at : prescription.created_at) }}</span>
                                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-tighter mt-0.5">{{ formatDate(form.tab === 'termine' ? prescription.updated_at : prescription.created_at) }}</span>
                                    </div>
                                </td>

                                <!-- Actions -->
                                <td v-if="form.tab !== 'termine'" class="px-4 py-5 text-right">
                                    <div class="flex justify-end">
                                        <!-- En attente Tab actions -->
                                        <template v-if="form.tab === 'en_attente'">
                                            <button v-if="prescription.status === 'EN_ATTENTE'"
                                                @click="startAnalysis(prescription.id)"
                                                class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-xl text-xs font-bold transition-all shadow-md hover:shadow-lg active:scale-95 flex items-center gap-2">
                                                🔬 <span>Traiter</span>
                                            </button>
                                            <button v-else-if="prescription.status === 'EN_COURS'"
                                                @click="continueAnalysis(prescription.id)"
                                                class="bg-orange-600 hover:bg-orange-700 text-white px-4 py-2 rounded-xl text-xs font-bold transition-all shadow-md hover:shadow-lg active:scale-95 flex items-center gap-2">
                                                ▶️ <span>Continuer</span>
                                            </button>
                                        </template>

                                        <!-- A refaire Tab actions -->
                                        <template v-if="form.tab === 'a_refaire'">
                                            <button @click="redoAnalysis(prescription.id)"
                                                class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-xl text-xs font-bold shadow-md hover:shadow-lg active:scale-95 transition-all">
                                                Recommencer
                                            </button>
                                        </template>
                                    </div>
                                </td>
                            </tr>

                            <!-- Empty state -->
                            <tr v-if="!prescriptions.data || prescriptions.data.length === 0">
                                <td :colspan="['termine', 'a_refaire'].includes(form.tab) ? 4 : 5" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
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

                <!-- Grid View (Cards) -->
                <div v-else class="p-4 sm:p-6 bg-slate-50/50 dark:bg-gray-900/50">
                    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4 gap-6">
                        <div v-for="prescription in prescriptions.data" :key="`grid-${prescription.id}`" 
                            class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm hover:shadow-md transition-all overflow-hidden flex flex-col group h-full">
                            
                            <!-- Card Header -->
                            <div class="p-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between bg-slate-50/30 dark:bg-gray-800/50">
                                <div class="flex flex-col">
                                    <span class="text-sm font-black text-gray-900 dark:text-white leading-tight">{{ prescription.reference }}</span>
                                    <span class="text-[10px] text-slate-400 font-bold uppercase tracking-tighter">{{ formatTimeAgo(form.tab === 'termine' ? prescription.updated_at : prescription.created_at) }}</span>
                                </div>
                                <span class="inline-flex rounded-full px-2.5 py-0.5 text-[9px] font-black uppercase tracking-widest shadow-sm border border-transparent" :class="statusClass(prescription.status || form.tab)">
                                    {{ prescription.status_label || (form.tab === 'termine' ? 'Terminé' : (form.tab === 'a_refaire' ? 'À refaire' : 'Attente')) }}
                                </span>
                            </div>

                            <!-- Card Body -->
                            <div class="p-5 flex-1 space-y-5">
                                <!-- Patient & Prescripteur -->
                                <div class="flex items-center gap-3">
                                    <div class="w-11 h-11 rounded-2xl flex-shrink-0 flex items-center justify-center font-black text-xs shadow-sm border border-transparent"
                                        :class="{
                                            'bg-blue-100 text-blue-600': form.tab === 'en_attente',
                                            'bg-teal-100 text-teal-600': form.tab === 'termine',
                                            'bg-red-100 text-red-600': form.tab === 'a_refaire',
                                        }">
                                        {{ getInitials(prescription.patient) }}
                                    </div>
                                    <div class="min-w-0 flex flex-col">
                                        <p class="text-sm font-black text-gray-900 dark:text-white truncate">
                                            {{ (prescription.patient?.nom || 'N/A') + ' ' + (prescription.patient?.prenom || '') }}
                                        </p>
                                        <p class="text-[11px] text-slate-500 font-medium truncate flex items-center gap-1">
                                            <em class="ni ni-user text-[10px] text-slate-400"></em>
                                            {{ prescription.prescripteur?.nom || 'Sans prescripteur' }}
                                        </p>
                                    </div>
                                </div>

                                <!-- Traçabilité Badge -->
                                <div class="grid grid-cols-2 gap-2 p-2.5 bg-slate-50 dark:bg-gray-900/50 rounded-xl border border-gray-100 dark:border-gray-700">
                                    <div class="flex flex-col gap-0.5">
                                        <span class="text-[9px] uppercase font-bold text-slate-400 tracking-wider">Saisie</span>
                                        <div class="flex items-center gap-1 text-blue-600">
                                            <em class="icon ni ni-user-alt text-[10px]"></em>
                                            <span class="text-[10px] font-black truncate">{{ prescription.secretaire?.name || 'Système' }}</span>
                                        </div>
                                    </div>
                                    <div class="flex flex-col gap-0.5 border-l border-gray-200 dark:border-gray-700 pl-2.5">
                                        <span class="text-[9px] uppercase font-bold text-slate-400 tracking-wider">Traité par</span>
                                        <div class="flex items-center gap-1" :class="prescription.technicien ? 'text-teal-600' : 'text-slate-300'">
                                            <em class="icon ni ni-account-setting-fill text-[10px]"></em>
                                            <span class="text-[10px] font-black truncate">{{ prescription.technicien?.name || '—' }}</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Analyses Tags -->
                                <div class="flex flex-wrap items-center gap-1.5">
                                    <span class="inline-flex h-5 w-5 items-center justify-center rounded-full bg-indigo-50 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300 text-[10px] font-black border border-indigo-100 dark:border-indigo-800 shadow-sm mr-0.5">
                                        {{ prescription.analyses_count }}
                                    </span>
                                    <span v-for="(analyse, aIdx) in prescription.analyses.slice(0, 6)" :key="aIdx" 
                                        class="text-[9px] font-black px-2 py-0.5 rounded bg-white dark:bg-slate-700 text-slate-500 border border-slate-200 dark:border-slate-600 uppercase shadow-xs">
                                        {{ analyse.code }}
                                    </span>
                                    <span v-if="prescription.analyses.length > 6" class="text-[10px] font-bold text-slate-400 self-center">+{{ prescription.analyses.length - 6 }}</span>
                                </div>

                                <!-- Reject Reason -->
                                <div v-if="form.tab === 'a_refaire'" class="p-3 bg-red-50 dark:bg-red-900/20 border border-red-100 dark:border-red-800 rounded-xl">
                                    <p class="text-[11px] font-bold text-red-600 dark:text-red-400 leading-relaxed">
                                        <span class="uppercase tracking-wider text-[9px] block mb-1 opacity-70">Motif du renvoi:</span>
                                        {{ prescription.commentaire_biologiste || 'À vérifier par le technicien' }}
                                    </p>
                                </div>
                            </div>

                            <!-- Card Footer: Actions -->
                            <div v-if="form.tab !== 'termine'" class="p-5 pt-0">
                                <template v-if="form.tab === 'en_attente'">
                                    <button v-if="prescription.status === 'EN_ATTENTE'"
                                        @click="startAnalysis(prescription.id)"
                                        class="w-full bg-blue-600 hover:bg-blue-700 text-white py-3 rounded-xl text-xs font-black transition-all shadow-md hover:shadow-lg active:scale-95 flex items-center justify-center gap-2 uppercase tracking-widest">
                                        🔬 TRAITER
                                    </button>
                                    <button v-else-if="prescription.status === 'EN_COURS'"
                                        @click="continueAnalysis(prescription.id)"
                                        class="w-full bg-orange-600 hover:bg-orange-700 text-white py-3 rounded-xl text-xs font-black transition-all shadow-md hover:shadow-lg active:scale-95 flex items-center justify-center gap-2 uppercase tracking-widest">
                                        ▶️ CONTINUER
                                    </button>
                                </template>
                                <button v-if="form.tab === 'a_refaire'"
                                    @click="redoAnalysis(prescription.id)"
                                    class="w-full bg-red-600 hover:bg-red-700 text-white py-3 rounded-xl text-xs font-black shadow-md hover:shadow-lg active:scale-95 transition-all uppercase tracking-widest">
                                    Recommencer
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Grid Empty State -->
                    <div v-if="!prescriptions.data || prescriptions.data.length === 0" class="flex flex-col items-center py-20 text-center">
                        <div class="w-20 h-20 bg-slate-100 dark:bg-gray-800 rounded-full flex items-center justify-center mb-4">
                            <svg class="w-10 h-10 text-slate-300 dark:text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-bold text-slate-900 dark:text-white">Aucun dossier</h3>
                        <p class="text-sm text-slate-500 max-w-xs mx-auto">Il n'y a actuellement aucune prescription à afficher dans cette catégorie.</p>
                    </div>
                </div>

                <!-- Pagination -->
                <div v-if="prescriptions.links && prescriptions.links.length > 3" class="px-6 py-6 border-t border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800">
                    <nav class="flex flex-col sm:flex-row items-center justify-between gap-4">
                        <p class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">
                            Affichage {{ prescriptions.from }} - {{ prescriptions.to }} / {{ prescriptions.total }}
                        </p>
                        <div class="flex gap-1.5">
                            <template v-for="(link, k) in prescriptions.links" :key="k">
                                <Link
                                    v-if="link.url"
                                    :href="link.url"
                                    class="px-3.5 py-2 text-xs font-black rounded-xl transition-all border shadow-sm uppercase tracking-tighter"
                                    :class="link.active
                                        ? 'bg-primary-600 text-white border-primary-600'
                                        : 'bg-white dark:bg-gray-700 text-gray-600 dark:text-gray-300 border-gray-200 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600'"
                                    v-html="link.label"
                                    preserve-state
                                />
                                <span v-else class="px-3.5 py-2 text-xs font-black text-gray-300 dark:text-gray-600 border border-transparent uppercase tracking-tighter" v-html="link.label" />
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
