<script setup>
import { ref, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import debounce from 'lodash/debounce';

const props = defineProps({
    patients: Object,
    prescriptions: Object,
    filters: Object,
    stats: Object,
});

const search = ref(props.filters.search || '');
const activeTab = ref(props.filters.tab || 'prescriptions');

watch(search, debounce((value) => {
    router.get(route('admin.trace-patients'), { search: value, tab: activeTab.value }, { preserveState: true, replace: true });
}, 300));

watch(activeTab, (value) => {
    search.value = '';
    router.get(route('admin.trace-patients'), { tab: value }, { preserveState: true });
});

const clearSearch = () => { search.value = ''; };

// Modal state
const modal = ref({ show: false, type: '', itemType: '', item: null, processing: false });

const openModal = (type, itemType, item = null) => {
    modal.value = { show: true, type, itemType, item, processing: false };
};
const closeModal = () => {
    modal.value = { show: false, type: '', itemType: '', item: null, processing: false };
};

const modalTitle = () => {
    const m = modal.value;
    if (m.type === 'restore') return `Restaurer ${m.itemType === 'patient' ? 'ce patient' : 'cette prescription'} ?`;
    if (m.type === 'delete') return `Supprimer définitivement ?`;
    if (m.type === 'empty') return `Vider la corbeille des ${m.itemType === 'patient' ? 'patients' : 'prescriptions'} ?`;
    return '';
};
const modalDesc = () => {
    const m = modal.value;
    if (m.type === 'restore') return 'L\'élément sera restauré et redeviendra visible dans l\'application.';
    if (m.type === 'delete') return 'Cette action est irréversible. Toutes les données associées seront supprimées définitivement.';
    if (m.type === 'empty') return 'Cette action est irréversible. Tous les éléments de la corbeille seront supprimés définitivement.';
    return '';
};
const modalColor = () => modal.value.type === 'restore' ? 'amber' : 'red';
const modalBtn = () => {
    if (modal.value.type === 'restore') return 'Restaurer';
    if (modal.value.type === 'delete') return 'Supprimer';
    if (modal.value.type === 'empty') return 'Vider la corbeille';
    return '';
};

const executeAction = () => {
    const m = modal.value;
    if (m.processing) return;
    modal.value.processing = true;

    const routes = {
        restore: { patient: 'admin.trace-patient.patients.restore', prescription: 'admin.trace-patient.prescriptions.restore' },
        delete: { patient: 'admin.trace-patient.patients.force-delete', prescription: 'admin.trace-patient.prescriptions.force-delete' },
        empty: { patient: 'admin.trace-patient.patients.empty', prescription: 'admin.trace-patient.prescriptions.empty' },
    };
    const routeName = routes[m.type]?.[m.itemType];
    if (!routeName) return;

    const url = m.type === 'empty' ? route(routeName) : route(routeName, m.item.id);
    const opts = {
        preserveScroll: true,
        onSuccess: () => closeModal(),
        onError: () => { modal.value.processing = false; },
        onFinish: () => { modal.value.processing = false; },
    };

    if (m.type === 'delete' || m.type === 'empty') {
        router.delete(url, opts);
    } else {
        router.post(url, {}, opts);
    }
};

const formatDate = (d) => {
    if (!d) return '';
    return new Date(d).toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' });
};
const initials = (nom, prenom) => {
    return `${(nom || 'N').charAt(0)}${(prenom || 'A').charAt(0)}`.toUpperCase();
};
</script>

<template>
    <Head title="Corbeille" />

    <AppLayout>
        <div class="container mx-auto px-4 py-8 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-red-100 text-red-600 dark:bg-red-900/30 dark:text-red-400">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    </div>
                    <div>
                        <h1 class="text-xl font-bold text-slate-900 dark:text-white">Corbeille</h1>
                        <p class="text-sm text-slate-500 dark:text-slate-400">Éléments supprimés — restaurer ou supprimer définitivement</p>
                    </div>
                </div>
                <span v-if="stats.total > 0" class="inline-flex items-center gap-1.5 rounded-full bg-red-100 px-3 py-1.5 text-xs font-semibold text-red-700 dark:bg-red-900/30 dark:text-red-300">
                    <span class="h-1.5 w-1.5 rounded-full bg-red-500"></span>
                    {{ stats.total }} élément(s)
                </span>
            </div>

            <!-- Stats Cards -->
            <div class="mb-6 grid grid-cols-2 gap-3 sm:grid-cols-3">
                <div class="rounded-xl border border-slate-200 bg-white p-3 dark:border-slate-700 dark:bg-slate-800">
                    <div class="flex flex-col items-center gap-1 text-center">
                        <div class="flex h-8 w-8 items-center justify-center text-red-500">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        </div>
                        <p class="text-xl font-bold text-slate-900 dark:text-white">{{ stats.total }}</p>
                        <p class="text-[11px] font-medium text-slate-500 dark:text-slate-400">Total</p>
                    </div>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white p-3 dark:border-slate-700 dark:bg-slate-800">
                    <div class="flex flex-col items-center gap-1 text-center">
                        <div class="flex h-8 w-8 items-center justify-center text-purple-500">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        </div>
                        <p class="text-xl font-bold text-slate-900 dark:text-white">{{ stats.prescriptions.total }}</p>
                        <p class="text-[11px] font-medium text-slate-500 dark:text-slate-400">Prescriptions</p>
                    </div>
                </div>
                <div class="col-span-2 rounded-xl border border-slate-200 bg-white p-3 sm:col-span-1 dark:border-slate-700 dark:bg-slate-800">
                    <div class="flex flex-col items-center gap-1 text-center">
                        <div class="flex h-8 w-8 items-center justify-center text-blue-500">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        </div>
                        <p class="text-xl font-bold text-slate-900 dark:text-white">{{ stats.patients.total }}</p>
                        <p class="text-[11px] font-medium text-slate-500 dark:text-slate-400">Patients</p>
                    </div>
                </div>
            </div>

            <!-- Main Card -->
            <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-800">
                <!-- Toolbar: Search + Tabs + Actions -->
                <div class="flex flex-col items-center gap-3 p-4 md:flex-row">
                    <!-- Search -->
                    <div class="relative w-full flex-1">
                        <svg class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
                        <input v-model="search" type="text" placeholder="Rechercher..." class="w-full rounded-lg border border-slate-200 bg-slate-50 py-2.5 pl-10 pr-9 text-sm text-slate-900 placeholder-slate-400 transition-colors focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 dark:border-slate-600 dark:bg-slate-700/50 dark:text-slate-100">
                        <button v-if="search" type="button" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-300" @click="clearSearch">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <!-- Tab pills -->
                    <div class="flex w-full items-center gap-1.5 rounded-lg bg-slate-100 p-1 md:w-auto dark:bg-slate-700/50">
                        <button type="button" class="flex items-center gap-1.5 rounded-md px-3 py-1.5 text-xs font-medium transition-all" :class="activeTab === 'prescriptions' ? 'bg-white text-slate-900 shadow-sm dark:bg-slate-600 dark:text-white' : 'text-slate-500 hover:text-slate-700 dark:text-slate-400'" @click="activeTab = 'prescriptions'">
                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            Prescriptions ({{ stats.prescriptions.total }})
                        </button>
                        <button type="button" class="flex items-center gap-1.5 rounded-md px-3 py-1.5 text-xs font-medium transition-all" :class="activeTab === 'patients' ? 'bg-white text-slate-900 shadow-sm dark:bg-slate-600 dark:text-white' : 'text-slate-500 hover:text-slate-700 dark:text-slate-400'" @click="activeTab = 'patients'">
                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            Patients ({{ stats.patients.total }})
                        </button>
                    </div>

                    <!-- Empty trash button -->
                    <button v-if="activeTab === 'prescriptions' && prescriptions.data.length > 0" type="button" class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-red-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-red-700 md:w-auto" @click="openModal('empty', 'prescription')">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        Vider
                    </button>
                    <button v-if="activeTab === 'patients' && patients.data.length > 0" type="button" class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-red-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-red-700 md:w-auto" @click="openModal('empty', 'patient')">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        Vider
                    </button>
                </div>

                <!-- Prescriptions Table -->
                <div v-show="activeTab === 'prescriptions'" class="border-t border-slate-100 dark:border-slate-700/50">
                    <div class="hidden overflow-x-auto lg:block">
                        <table class="w-full">
                            <thead>
                                <tr class="border-b border-slate-100 dark:border-slate-700">
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Référence</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Patient</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Prescripteur</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Supprimé le</th>
                                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50 dark:divide-slate-700/50">
                                <tr v-for="p in prescriptions.data" :key="p.id" class="group transition-colors hover:bg-slate-50/50 dark:hover:bg-slate-700/20">
                                    <td class="px-4 py-3.5">
                                        <span class="inline-flex rounded-full bg-primary-100 px-2.5 py-1 text-xs font-semibold text-primary-700 dark:bg-primary-900/30 dark:text-primary-300">{{ p.reference }}</span>
                                    </td>
                                    <td class="px-4 py-3.5">
                                        <div class="flex items-center gap-2.5">
                                            <div class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-primary-400 to-primary-600">
                                                <span class="text-xs font-semibold text-white">{{ initials(p.patient?.nom, p.patient?.prenom) }}</span>
                                            </div>
                                            <div class="min-w-0">
                                                <p class="truncate text-sm font-medium text-slate-900 dark:text-white">{{ p.patient?.nom || 'N/A' }} {{ p.patient?.prenom || '' }}</p>
                                                <p class="text-xs text-slate-400">{{ p.patient?.numero_dossier || '' }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3.5 text-sm text-slate-600 dark:text-slate-300">{{ p.prescripteur?.nom || 'N/A' }}</td>
                                    <td class="px-4 py-3.5">
                                        <p class="text-sm text-slate-600 dark:text-slate-300">{{ formatDate(p.deleted_at) }}</p>
                                    </td>
                                    <td class="px-4 py-3.5">
                                        <div class="flex items-center justify-end gap-1 opacity-0 transition-opacity group-hover:opacity-100">
                                            <button type="button" class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-amber-50 text-amber-600 hover:bg-amber-100 dark:bg-amber-900/20 dark:text-amber-400 dark:hover:bg-amber-900/40 transition-colors" title="Restaurer" @click="openModal('restore', 'prescription', p)">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3"/></svg>
                                            </button>
                                            <button type="button" class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-red-50 text-red-600 hover:bg-red-100 dark:bg-red-900/20 dark:text-red-400 dark:hover:bg-red-900/40 transition-colors" title="Supprimer définitivement" @click="openModal('delete', 'prescription', p)">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="prescriptions.data.length === 0">
                                    <td colspan="5" class="py-16 text-center">
                                        <div class="flex flex-col items-center">
                                            <div class="mb-3 flex h-11 w-11 items-center justify-center rounded-full bg-slate-100 dark:bg-slate-700">
                                                <svg class="h-5 w-5 text-slate-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                            </div>
                                            <p class="text-sm font-medium text-slate-600 dark:text-slate-400">Aucune prescription dans la corbeille</p>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Mobile cards for prescriptions -->
                    <div class="divide-y divide-slate-100 dark:divide-slate-700/50 lg:hidden">
                        <div v-for="p in prescriptions.data" :key="`m-${p.id}`" class="flex items-center justify-between gap-3 p-4">
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="inline-flex rounded-full bg-primary-100 px-2 py-0.5 text-[11px] font-semibold text-primary-700 dark:bg-primary-900/30 dark:text-primary-300">{{ p.reference }}</span>
                                </div>
                                <p class="truncate text-sm font-medium text-slate-900 dark:text-white">{{ p.patient?.nom || 'N/A' }} {{ p.patient?.prenom || '' }}</p>
                                <p class="text-xs text-slate-400">{{ formatDate(p.deleted_at) }}</p>
                            </div>
                            <div class="flex items-center gap-1">
                                <button type="button" class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-amber-50 text-amber-600 dark:bg-amber-900/20 dark:text-amber-400 transition-colors" @click="openModal('restore', 'prescription', p)">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3"/></svg>
                                </button>
                                <button type="button" class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-red-50 text-red-600 dark:bg-red-900/20 dark:text-red-400 transition-colors" @click="openModal('delete', 'prescription', p)">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                                </button>
                            </div>
                        </div>
                        <div v-if="prescriptions.data.length === 0" class="py-12 text-center">
                            <p class="text-sm text-slate-500">Aucune prescription dans la corbeille</p>
                        </div>
                    </div>

                    <div v-if="prescriptions.links && prescriptions.links.length > 3" class="border-t border-slate-100 bg-slate-50/50 px-4 py-3 dark:border-slate-700/50 dark:bg-slate-800/50">
                        <Pagination :links="prescriptions.links" />
                    </div>
                </div>

                <!-- Patients Table -->
                <div v-show="activeTab === 'patients'" class="border-t border-slate-100 dark:border-slate-700/50">
                    <div class="hidden overflow-x-auto lg:block">
                        <table class="w-full">
                            <thead>
                                <tr class="border-b border-slate-100 dark:border-slate-700">
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Patient</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Dossier</th>
                                    <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Prescriptions</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Supprimé le</th>
                                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50 dark:divide-slate-700/50">
                                <tr v-for="pat in patients.data" :key="pat.id" class="group transition-colors hover:bg-slate-50/50 dark:hover:bg-slate-700/20">
                                    <td class="px-4 py-3.5">
                                        <div class="flex items-center gap-2.5">
                                            <div class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-blue-400 to-blue-600">
                                                <span class="text-xs font-semibold text-white">{{ initials(pat.nom, pat.prenom) }}</span>
                                            </div>
                                            <div class="min-w-0">
                                                <p class="truncate text-sm font-medium text-slate-900 dark:text-white">{{ pat.nom }} {{ pat.prenom }}</p>
                                                <p class="text-xs text-slate-400">{{ pat.telephone || '-' }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3.5">
                                        <span class="inline-flex rounded-full bg-blue-100 px-2.5 py-1 text-xs font-semibold text-blue-700 dark:bg-blue-900/30 dark:text-blue-300">{{ pat.numero_dossier }}</span>
                                    </td>
                                    <td class="px-4 py-3.5 text-center">
                                        <span v-if="pat.prescriptions_count > 0" class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-amber-100 text-xs font-semibold text-amber-700 dark:bg-amber-900/30 dark:text-amber-300">{{ pat.prescriptions_count }}</span>
                                        <span v-else class="text-xs text-slate-400">0</span>
                                    </td>
                                    <td class="px-4 py-3.5 text-sm text-slate-600 dark:text-slate-300">{{ formatDate(pat.deleted_at) }}</td>
                                    <td class="px-4 py-3.5">
                                        <div class="flex items-center justify-end gap-1 opacity-0 transition-opacity group-hover:opacity-100">
                                            <button type="button" class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-amber-50 text-amber-600 hover:bg-amber-100 dark:bg-amber-900/20 dark:text-amber-400 dark:hover:bg-amber-900/40 transition-colors" title="Restaurer" @click="openModal('restore', 'patient', pat)">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3"/></svg>
                                            </button>
                                            <button type="button" class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-red-50 text-red-600 hover:bg-red-100 dark:bg-red-900/20 dark:text-red-400 dark:hover:bg-red-900/40 transition-colors" title="Supprimer définitivement" @click="openModal('delete', 'patient', pat)">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="patients.data.length === 0">
                                    <td colspan="5" class="py-16 text-center">
                                        <div class="flex flex-col items-center">
                                            <div class="mb-3 flex h-11 w-11 items-center justify-center rounded-full bg-slate-100 dark:bg-slate-700">
                                                <svg class="h-5 w-5 text-slate-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                            </div>
                                            <p class="text-sm font-medium text-slate-600 dark:text-slate-400">Aucun patient dans la corbeille</p>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Mobile cards for patients -->
                    <div class="divide-y divide-slate-100 dark:divide-slate-700/50 lg:hidden">
                        <div v-for="pat in patients.data" :key="`mp-${pat.id}`" class="flex items-center justify-between gap-3 p-4">
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-medium text-slate-900 dark:text-white">{{ pat.nom }} {{ pat.prenom }}</p>
                                <p class="text-xs text-slate-400">{{ pat.numero_dossier }} · {{ formatDate(pat.deleted_at) }}</p>
                            </div>
                            <div class="flex items-center gap-1">
                                <button type="button" class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-amber-50 text-amber-600 dark:bg-amber-900/20 dark:text-amber-400" @click="openModal('restore', 'patient', pat)">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3"/></svg>
                                </button>
                                <button type="button" class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-red-50 text-red-600 dark:bg-red-900/20 dark:text-red-400" @click="openModal('delete', 'patient', pat)">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                                </button>
                            </div>
                        </div>
                        <div v-if="patients.data.length === 0" class="py-12 text-center">
                            <p class="text-sm text-slate-500">Aucun patient dans la corbeille</p>
                        </div>
                    </div>

                    <div v-if="patients.links && patients.links.length > 3" class="border-t border-slate-100 bg-slate-50/50 px-4 py-3 dark:border-slate-700/50 dark:bg-slate-800/50">
                        <Pagination :links="patients.links" />
                    </div>
                </div>
            </div>
        </div>

        <!-- Unified Confirmation Modal -->
        <Teleport to="body">
            <div v-if="modal.show" class="fixed inset-0 z-[60] overflow-y-auto">
                <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm" @click="closeModal"></div>
                <div class="flex min-h-full items-center justify-center p-4">
                    <div class="relative w-full max-w-sm rounded-2xl bg-white shadow-xl dark:bg-slate-800" @click.stop>
                        <div class="p-6 text-center">
                            <div class="mx-auto mb-4 flex h-11 w-11 items-center justify-center rounded-full" :class="modalColor() === 'amber' ? 'bg-amber-100 dark:bg-amber-900/30' : 'bg-red-100 dark:bg-red-900/30'">
                                <svg class="h-5 w-5" :class="modalColor() === 'amber' ? 'text-amber-600 dark:text-amber-400' : 'text-red-600 dark:text-red-400'" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                                </svg>
                            </div>
                            <h3 class="mb-1 text-lg font-semibold text-slate-900 dark:text-white">{{ modalTitle() }}</h3>
                            <p class="text-sm text-slate-500 dark:text-slate-400">{{ modalDesc() }}</p>
                        </div>
                        <div class="flex gap-3 px-6 pb-6">
                            <button type="button" class="flex-1 rounded-lg bg-slate-100 px-4 py-2.5 text-sm font-medium text-slate-700 transition-colors hover:bg-slate-200 dark:bg-slate-700 dark:text-slate-300 dark:hover:bg-slate-600" @click="closeModal">Annuler</button>
                            <button type="button" class="flex-1 rounded-lg px-4 py-2.5 text-sm font-medium text-white transition-colors" :class="modalColor() === 'amber' ? 'bg-amber-600 hover:bg-amber-700' : 'bg-red-600 hover:bg-red-700'" :disabled="modal.processing" @click="executeAction">{{ modal.processing ? '...' : modalBtn() }}</button>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>
