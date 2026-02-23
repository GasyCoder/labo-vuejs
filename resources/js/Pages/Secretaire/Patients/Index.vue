<template>
<AppLayout>
    <div class="container mx-auto px-4 py-6 sm:px-6 lg:px-8">
        <!-- Header avec stats -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
            <div>
                <h1 class="text-xl font-bold text-slate-900 dark:text-white">Patients</h1>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">{{ totalPatients }} patients enregistrés</p>
            </div>
        </div>

        <!-- Stats compactes -->
        <div v-if="totalPatients > 0" class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-6">
            <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-primary-50 dark:bg-primary-900/30 flex items-center justify-center">
                        <svg class="w-5 h-5 text-primary-600 dark:text-primary-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-slate-900 dark:text-white leading-none">{{ totalPatients }}</p>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Total</p>
                    </div>
                </div>
            </div>
            <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-slate-100 dark:bg-slate-700 flex items-center justify-center">
                        <svg class="w-5 h-5 text-slate-600 dark:text-slate-300" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zM4 19.235v-.11a6.375 6.375 0 0112.75 0v.109A12.318 12.318 0 0110.374 21c-2.331 0-4.512-.645-6.374-1.766z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-slate-900 dark:text-white leading-none">{{ patientsNouveaux }}</p>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Nouveaux</p>
                    </div>
                </div>
            </div>
            <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-green-50 dark:bg-green-900/30 flex items-center justify-center">
                        <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-slate-900 dark:text-white leading-none">{{ patientsFideles }}</p>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Fidèles</p>
                    </div>
                </div>
            </div>
            <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-amber-50 dark:bg-amber-900/30 flex items-center justify-center">
                        <svg class="w-5 h-5 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.563.563 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-slate-900 dark:text-white leading-none">{{ patientsVip }}</p>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">VIP</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recherche + Filtres -->
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 mb-6">
            <div class="p-4 flex flex-col lg:flex-row items-stretch lg:items-center gap-3">
                <!-- Recherche -->
                <div class="relative flex-1">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/>
                    </svg>
                    <input v-model="form.search" @input="debouncedUpdateFilters" type="text"
                           placeholder="Rechercher par nom, telephone, email, dossier..."
                           class="w-full pl-10 pr-4 py-2.5 text-sm border border-slate-200 dark:border-slate-600 rounded-lg bg-slate-50 dark:bg-slate-700/50 text-slate-900 dark:text-slate-100 placeholder-slate-400 focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition-colors">
                </div>

                <!-- Filtre civilite -->
                <select v-model="form.civiliteFilter" @change="updateFilters"
                        class="py-2.5 px-3 text-sm border border-slate-200 dark:border-slate-600 rounded-lg bg-slate-50 dark:bg-slate-700/50 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500">
                    <option value="">Civilité</option>
                    <option value="Monsieur">Monsieur</option>
                    <option value="Madame">Madame</option>
                    <option value="Mademoiselle">Mademoiselle</option>
                    <option value="Enfant-garçon">Enfant-garçon</option>
                    <option value="Enfant-fille">Enfant-fille</option>
                </select>

                <!-- Filtre statut -->
                <select v-model="form.statutFilter" @change="updateFilters"
                        class="py-2.5 px-3 text-sm border border-slate-200 dark:border-slate-600 rounded-lg bg-slate-50 dark:bg-slate-700/50 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500">
                    <option value="">Statut</option>
                    <option value="NOUVEAU">Nouveau</option>
                    <option value="FIDELE">Fidèle</option>
                    <option value="VIP">VIP</option>
                </select>

                <!-- Per page -->
                <select v-model="form.perPage" @change="updateFilters"
                        class="py-2.5 px-3 text-sm border border-slate-200 dark:border-slate-600 rounded-lg bg-slate-50 dark:bg-slate-700/50 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500">
                    <option value="10">10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                </select>

                <button v-if="form.search || form.civiliteFilter || form.statutFilter"
                        @click="resetFilters"
                        class="inline-flex items-center gap-1.5 px-3 py-2.5 text-sm text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-lg transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                    Effacer
                </button>
            </div>
        </div>

        <!-- Table -->
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
            <!-- Desktop -->
            <div class="hidden lg:block overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-slate-100 dark:border-slate-700">
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider w-12">#</th>
                            <th class="px-4 py-3 text-left">
                                <button @click="sortBy('nom')" class="flex items-center gap-1 text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider hover:text-slate-700 dark:hover:text-slate-300">
                                    Patient
                                    <template v-if="form.sortField === 'nom'">
                                        <svg class="w-3.5 h-3.5 text-primary-500" fill="currentColor" viewBox="0 0 20 20">
                                            <path v-if="form.sortDirection === 'asc'" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"/>
                                            <path v-else d="M14.707 12.707a1 1 0 01-1.414 0L10 9.414l-3.293 3.293a1 1 0 01-1.414-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 010 1.414z"/>
                                        </svg>
                                    </template>
                                </button>
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Contact</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Prescriptions</th>
                            <th class="px-4 py-3 text-left">
                                <button @click="sortBy('created_at')" class="flex items-center gap-1 text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider hover:text-slate-700 dark:hover:text-slate-300">
                                    Date
                                    <template v-if="form.sortField === 'created_at'">
                                        <svg class="w-3.5 h-3.5 text-primary-500" fill="currentColor" viewBox="0 0 20 20">
                                            <path v-if="form.sortDirection === 'asc'" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"/>
                                            <path v-else d="M14.707 12.707a1 1 0 01-1.414 0L10 9.414l-3.293 3.293a1 1 0 01-1.414-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 010 1.414z"/>
                                        </svg>
                                    </template>
                                </button>
                            </th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50 dark:divide-slate-700/50">
                        <template v-if="patients.data.length > 0">
                            <tr v-for="(patient, index) in patients.data" :key="patient.id" class="group hover:bg-slate-50/50 dark:hover:bg-slate-700/20 transition-colors">
                                <td class="px-4 py-3.5">
                                    <span class="text-xs font-medium text-slate-400">{{ patients.from + index }}</span>
                                </td>
                                <td class="px-4 py-3.5">
                                    <div class="flex items-center gap-2.5">
                                        <div class="flex-shrink-0 w-8 h-8 rounded-full bg-gradient-to-br from-primary-400 to-primary-600 flex items-center justify-center">
                                            <span class="text-xs font-semibold text-white">
                                                {{ getInitials(patient) }}
                                            </span>
                                        </div>
                                        <div class="min-w-0">
                                            <p class="text-sm font-medium text-slate-900 dark:text-white truncate">
                                                {{ patient.nom }}{{ patient.prenom ? ' ' + patient.prenom : '' }}
                                            </p>
                                            <span :class="[
                                                'inline-flex items-center px-1.5 py-0.5 rounded text-[11px] font-medium',
                                                patient.civilite === 'Monsieur' ? 'bg-blue-50 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400' :
                                                patient.civilite === 'Madame' ? 'bg-pink-50 text-pink-600 dark:bg-pink-900/30 dark:text-pink-400' :
                                                patient.civilite === 'Mademoiselle' ? 'bg-purple-50 text-purple-600 dark:bg-purple-900/30 dark:text-purple-400' :
                                                'bg-orange-50 text-orange-600 dark:bg-orange-900/30 dark:text-orange-400'
                                            ]">
                                                {{ patient.civilite }}
                                            </span>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3.5">
                                    <div class="space-y-0.5">
                                        <p v-if="patient.telephone" class="text-sm text-slate-600 dark:text-slate-300">{{ patient.telephone }}</p>
                                        <p v-if="patient.email" class="text-xs text-slate-400 truncate max-w-[180px]">{{ patient.email }}</p>
                                        <span v-if="!patient.telephone && !patient.email" class="text-xs text-slate-400 italic">Non renseigné</span>
                                    </div>
                                </td>
                                <td class="px-4 py-3.5 text-center">
                                    <span class="inline-flex items-center justify-center w-7 h-7 rounded-full text-xs font-semibold bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300">
                                        {{ patient.prescriptions_count }}
                                    </span>
                                </td>
                                <td class="px-4 py-3.5">
                                    <p class="text-sm text-slate-600 dark:text-slate-300">{{ formatDate(patient.created_at) }}</p>
                                    <p class="text-[11px] text-slate-400">{{ timeAgo(patient.created_at) }}</p>
                                </td>
                                <td class="px-4 py-3.5">
                                    <div class="flex items-center justify-end gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                        <Link :href="route('secretaire.patient.detail', patient.id)"
                                           class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors"
                                           title="Voir le profil">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            </svg>
                                        </Link>
                                    </div>
                                </td>
                            </tr>
                        </template>
                        <template v-else>
                            <tr>
                                <td colspan="6" class="py-16 text-center">
                                    <div class="flex flex-col items-center">
                                        <div class="w-11 h-11 rounded-full bg-slate-100 dark:bg-slate-700 flex items-center justify-center mb-3">
                                            <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/>
                                            </svg>
                                        </div>
                                        <p class="text-sm font-medium text-slate-600 dark:text-slate-400">Aucun patient trouvé</p>
                                        <p v-if="form.search || form.civiliteFilter || form.statutFilter" class="text-xs text-slate-400 mt-1">Modifiez vos critères de recherche</p>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <!-- Mobile cards -->
            <div class="lg:hidden divide-y divide-slate-100 dark:divide-slate-700/50">
                <template v-if="patients.data.length > 0">
                    <Link v-for="patient in patients.data" :key="patient.id" :href="route('secretaire.patient.detail', patient.id)" class="block p-4 hover:bg-slate-50 dark:hover:bg-slate-700/20 transition-colors">
                        <div class="flex items-center gap-3">
                            <div class="flex-shrink-0 w-9 h-9 rounded-full bg-gradient-to-br from-primary-400 to-primary-600 flex items-center justify-center">
                                <span class="text-[11px] font-semibold text-white">
                                    {{ getInitials(patient) }}
                                </span>
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center justify-between">
                                    <p class="text-sm font-medium text-slate-900 dark:text-white truncate">
                                        {{ patient.nom }}{{ patient.prenom ? ' ' + patient.prenom : '' }}
                                    </p>
                                    <span class="inline-flex items-center justify-center w-6 h-6 rounded-full text-[11px] font-semibold bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 ml-2">
                                        {{ patient.prescriptions_count }}
                                    </span>
                                </div>
                                <div class="flex items-center gap-2 mt-0.5 text-xs text-slate-400">
                                    <span>{{ patient.telephone ?? 'Pas de tel' }}</span>
                                    <span>&middot;</span>
                                    <span>{{ formatDate(patient.created_at) }}</span>
                                </div>
                            </div>
                            <svg class="w-4 h-4 text-slate-300 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/>
                            </svg>
                        </div>
                    </Link>
                </template>
                <template v-else>
                    <div class="py-12 text-center">
                        <p class="text-sm text-slate-500">Aucun patient trouvé</p>
                    </div>
                </template>
            </div>

            <!-- Pagination -->
            <div v-if="patients.links && patients.links.length > 3" class="px-4 py-3 border-t border-slate-100 dark:border-slate-700/50 bg-slate-50/50 dark:bg-slate-800/50">
                <div class="flex flex-col sm:flex-row items-center justify-between gap-3">
                    <p class="text-xs text-slate-500 dark:text-slate-400">
                        <span class="font-medium">{{ patients.from }}</span> à
                        <span class="font-medium">{{ patients.to }}</span> sur
                        <span class="font-medium">{{ patients.total }}</span>
                    </p>
                    <Pagination :links="patients.links" />
                </div>
            </div>
        </div>
    </div>
</AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import { debounce } from 'lodash';

const props = defineProps({
    patients: Object,
    filters: Object,
    totalPatients: Number,
    patientsNouveaux: Number,
    patientsFideles: Number,
    patientsVip: Number,
});

const form = ref({
    search: props.filters.search || '',
    civiliteFilter: props.filters.civiliteFilter || '',
    statutFilter: props.filters.statutFilter || '',
    perPage: props.filters.perPage || 10,
    sortField: props.filters.sortField || 'created_at',
    sortDirection: props.filters.sortDirection || 'desc',
});

const getInitials = (patient) => {
    const f = String(patient.nom || '').charAt(0).toUpperCase();
    const l = String(patient.prenom || 'X').charAt(0).toUpperCase();
    return `${f}${l}`;
};

const formatDate = (dateString) => {
    if (!dateString) return '';
    const d = new Date(dateString);
    return d.toLocaleDateString('fr-FR');
};

const timeAgo = (dateString) => {
    if (!dateString) return '';
    const d = new Date(dateString);
    const now = new Date();
    const diff = now - d;
    const days = Math.floor(diff / (1000 * 60 * 60 * 24));
    if (days === 0) return "Aujourd'hui";
    if (days === 1) return 'Hier';
    if (days < 30) return `Il y a ${days} jours`;
    if (days < 365) return `Il y a ${Math.floor(days / 30)} mois`;
    return `Il y a ${Math.floor(days / 365)} ans`;
};

const updateFilters = () => {
    router.get(route('secretaire.patients.index'), form.value, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

const debouncedUpdateFilters = debounce(updateFilters, 300);

const resetFilters = () => {
    form.value.search = '';
    form.value.civiliteFilter = '';
    form.value.statutFilter = '';
    form.value.perPage = 10;
    form.value.sortField = 'created_at';
    form.value.sortDirection = 'desc';
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
</script>
