<script setup>
import { ref, watch, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import TextInput from '@/Components/TextInput.vue';
import debounce from 'lodash/debounce';
import Swal from 'sweetalert2';

const props = defineProps({
    analyses: Object,
    filters: Object,
    stats: Object,
});

const search = ref(props.filters.search);
const selectedIds = ref([]);

watch(search, debounce((value) => {
    router.get(route('laboratoire.analyses.ranges.index'), { search: value }, { preserveState: true, replace: true });
}, 500));

// Logic for selection
const isAllSelected = computed(() => {
    return props.analyses.data.length > 0 && selectedIds.value.length === props.analyses.data.length;
});

const toggleAll = () => {
    if (isAllSelected.value) {
        selectedIds.value = [];
    } else {
        selectedIds.value = props.analyses.data.map(a => a.id);
    }
};

const resetRanges = (analyse) => {
    Swal.fire({
        title: 'Réinitialiser les bornes ?',
        text: `Voulez-vous vraiment supprimer toutes les plages de référence pour l'analyse "${analyse.designation}" ?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Oui, réinitialiser',
        cancelButtonText: 'Annuler',
        reverseButtons: true,
        customClass: {
            container: 'font-sans',
            popup: 'rounded-2xl',
            confirmButton: 'rounded-lg font-bold uppercase tracking-widest text-xs px-6 py-3',
            cancelButton: 'rounded-lg font-bold uppercase tracking-widest text-xs px-6 py-3'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('laboratoire.analyses.ranges.destroy', analyse.id), {
                preserveScroll: true,
                onSuccess: () => {
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'success',
                        title: 'Réinitialisation réussie',
                        showConfirmButton: false,
                        timer: 2000
                    });
                }
            });
        }
    });
};

const bulkReset = () => {
    if (selectedIds.value.length === 0) return;

    Swal.fire({
        title: 'Action groupée',
        text: `Voulez-vous vraiment réinitialiser les bornes pour les ${selectedIds.value.length} analyses sélectionnées ?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Oui, réinitialiser la sélection',
        cancelButtonText: 'Annuler',
        reverseButtons: true,
        customClass: {
            container: 'font-sans',
            popup: 'rounded-2xl',
            confirmButton: 'rounded-lg font-bold uppercase tracking-widest text-[10px] px-6 py-3',
            cancelButton: 'rounded-lg font-bold uppercase tracking-widest text-[10px] px-6 py-3'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            router.post(route('laboratoire.analyses.ranges.bulk-destroy'), {
                ids: selectedIds.value
            }, {
                preserveScroll: true,
                onSuccess: () => {
                    selectedIds.value = [];
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'success',
                        title: 'Sélection réinitialisée',
                        showConfirmButton: false,
                        timer: 2000
                    });
                }
            });
        }
    });
};
</script>

<template>
    <Head title="Intervalles de référence" />

    <AppLayout>
        <template #header>
            <h2 class="font-black text-xl text-slate-900 dark:text-white leading-tight uppercase tracking-tight">
                Gestion des Intervalles de référence
            </h2>
        </template>

        <div class="py-6">
            <div class="px-4 sm:px-6 lg:px-8 w-full space-y-6">
                <!-- Header avec Titre et Description -->
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6 mb-2">
                    <div>
                        <h1 class="text-3xl font-heading font-black text-slate-900 dark:text-white uppercase tracking-tight">Configuration des Normes</h1>
                        <p class="text-slate-500 dark:text-slate-400 mt-2 font-medium">Paramétrez les seuils de normalité et les alertes critiques pour chaque analyse du catalogue.</p>
                    </div>
                </div>

                <!-- Guide utilisateur / Avantages -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="bg-white dark:bg-slate-800 p-5 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-sm transition-all hover:shadow-md group">
                        <div class="h-12 w-12 bg-blue-50 dark:bg-blue-900/20 rounded-xl flex items-center justify-center mb-4 transition-transform group-hover:scale-110">
                            <em class="ni ni-info-fill text-blue-600 text-2xl"></em>
                        </div>
                        <h4 class="text-sm font-black text-slate-900 dark:text-white uppercase tracking-wider mb-2">Précision Diagnostique</h4>
                        <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed font-medium">
                            Définissez des bornes précises par contexte (Homme, Femme, Enfant) pour garantir une interprétation exacte des résultats biologiques.
                        </p>
                    </div>

                    <div class="bg-white dark:bg-slate-800 p-5 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-sm transition-all hover:shadow-md group">
                        <div class="h-12 w-12 bg-amber-50 dark:bg-amber-900/20 rounded-xl flex items-center justify-center mb-4 transition-transform group-hover:scale-110">
                            <em class="ni ni-alert-fill text-amber-500 text-2xl"></em>
                        </div>
                        <h4 class="text-sm font-black text-slate-900 dark:text-white uppercase tracking-wider mb-2">Alertes Automatiques</h4>
                        <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed font-medium">
                            Le système identifie automatiquement les résultats anormaux (orange) ou critiques (rouge) lors de la saisie technique.
                        </p>
                    </div>

                    <div class="bg-white dark:bg-slate-800 p-5 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-sm transition-all hover:shadow-md group">
                        <div class="h-12 w-12 bg-emerald-50 dark:bg-emerald-900/20 rounded-xl flex items-center justify-center mb-4 transition-transform group-hover:scale-110">
                            <em class="ni ni-check-circle text-emerald-500 text-2xl"></em>
                        </div>
                        <h4 class="text-sm font-black text-slate-900 dark:text-white uppercase tracking-wider mb-2">Sécurité Biologique</h4>
                        <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed font-medium">
                            Les limites critiques bloquent la validation automatique, imposant une revue humaine pour les dossiers présentant un risque vital.
                        </p>
                    </div>
                </div>
                
                <!-- Dashboard / Compteurs -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="bg-white dark:bg-slate-800 p-4 rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm flex items-center justify-between">
                        <div>
                            <p class="text-[10px] font-black uppercase tracking-widest text-slate-400">Total Analyses</p>
                            <h3 class="text-2xl font-black text-slate-900 dark:text-white">{{ stats.total }}</h3>
                        </div>
                        <div class="h-10 w-10 bg-blue-50 text-blue-500 rounded-lg flex items-center justify-center dark:bg-blue-900/30">
                            <em class="ni ni-list-thumb text-xl"></em>
                        </div>
                    </div>
                    
                    <div class="bg-white dark:bg-slate-800 p-4 rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm flex items-center justify-between">
                        <div>
                            <p class="text-[10px] font-black uppercase tracking-widest text-slate-400">Configurées</p>
                            <h3 class="text-2xl font-black text-emerald-600 dark:text-emerald-400">{{ stats.configured }}</h3>
                        </div>
                        <div class="h-10 w-10 bg-emerald-50 text-emerald-500 rounded-lg flex items-center justify-center dark:bg-blue-900/30">
                            <em class="ni ni-check-circle text-xl"></em>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-slate-800 p-4 rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm flex items-center justify-between">
                        <div>
                            <p class="text-[10px] font-black uppercase tracking-widest text-slate-400">À Configurer</p>
                            <h3 class="text-2xl font-black text-red-600 dark:text-red-400">{{ stats.missing }}</h3>
                        </div>
                        <div class="h-10 w-10 bg-red-50 text-red-500 rounded-lg flex items-center justify-center dark:bg-red-900/30">
                            <em class="ni ni-alert-fill text-xl"></em>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg">
                    <!-- Barre de recherche et actions groupées -->
                    <div class="p-4 border-b border-gray-200 dark:border-gray-700 flex flex-col md:flex-row justify-between items-center gap-4 bg-gray-50/50 dark:bg-gray-800/50">
                        <div class="w-full md:w-1/3 flex items-center gap-4">
                            <div class="relative group flex-1">
                                <em class="ni ni-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></em>
                                <input v-model="search" type="text" placeholder="Rechercher..." 
                                    class="w-full rounded-lg border-slate-200 bg-white py-2 pl-10 text-xs font-bold focus:border-primary-500 focus:ring-primary-500 dark:border-slate-700 dark:bg-slate-900">
                            </div>
                        </div>

                        <!-- Actions groupées -->
                        <div class="flex items-center gap-3">
                            <Transition enter-active-class="transition duration-200 ease-out" enter-from-class="opacity-0 -translate-x-2" enter-to-class="opacity-100 translate-x-0">
                                <div v-if="selectedIds.length > 0" class="flex items-center gap-3">
                                    <span class="text-[10px] font-black uppercase text-slate-500 bg-slate-100 px-3 py-1.5 rounded-lg border border-slate-200">
                                        {{ selectedIds.length }} sélectionné(s)
                                    </span>
                                    <button @click="bulkReset" 
                                        class="inline-flex items-center gap-2 rounded-lg bg-red-600 px-4 py-2 text-[10px] font-black uppercase tracking-widest text-white shadow-md transition-all hover:bg-red-700 active:scale-95 animate-in fade-in zoom-in duration-300">
                                        <em class="ni ni-reload"></em> Reset la sélection
                                    </button>
                                </div>
                            </Transition>
                            <div class="hidden md:block">
                                <p class="text-[10px] font-bold uppercase text-slate-400 tracking-wider">Catalogue Laboratoire v1.2</p>
                            </div>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                            <thead class="text-[10px] text-slate-400 uppercase bg-gray-50/50 dark:bg-gray-700/50 border-b border-gray-200 dark:border-gray-600 font-black tracking-widest">
                                <tr>
                                    <th scope="col" class="px-6 py-4 w-10">
                                        <input type="checkbox" :checked="isAllSelected" @change="toggleAll"
                                            class="w-4 h-4 rounded border-slate-300 text-primary-600 focus:ring-primary-500 dark:bg-slate-900 dark:border-slate-700 cursor-pointer">
                                    </th>
                                    <th scope="col" class="px-6 py-4 w-24">Code</th>
                                    <th scope="col" class="px-6 py-4">Désignation</th>
                                    <th scope="col" class="px-6 py-4">Intervalles de référence</th>
                                    <th scope="col" class="px-6 py-4 text-center">Unité</th>
                                    <th scope="col" class="px-6 py-4 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                <tr v-for="analyse in analyses.data" :key="analyse.id" 
                                    :class="['bg-white dark:bg-gray-800 hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors', {'bg-blue-50/30 dark:bg-blue-900/10': selectedIds.includes(analyse.id)}]">
                                    <td class="px-6 py-4">
                                        <input type="checkbox" v-model="selectedIds" :value="analyse.id"
                                            class="w-4 h-4 rounded border-slate-300 text-primary-600 focus:ring-primary-500 dark:bg-slate-900 dark:border-slate-700 cursor-pointer">
                                    </td>
                                    <td class="px-6 py-4 font-mono text-[10px] font-black text-slate-400">
                                        {{ analyse.code }}
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="font-bold text-slate-900 dark:text-gray-100 text-sm">
                                            {{ analyse.designation }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex flex-wrap gap-2">
                                            <div v-for="range in analyse.ranges" :key="range.id" 
                                                class="flex items-center gap-1.5 px-2 py-1 rounded-md text-[10px] font-black bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400 border border-blue-100 dark:border-blue-800">
                                                <span class="opacity-50 uppercase">{{ range.context.replace('ENFANT_', '') }}</span>
                                                <span>{{ range.normal_min ?? '?' }} - {{ range.normal_max ?? '?' }}</span>
                                            </div>
                                            <div v-if="analyse.ranges.length === 0" class="flex items-center gap-1 text-red-400 italic text-[10px] font-bold">
                                                <em class="ni ni-alert-fill"></em> Non configuré
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <span v-if="analyse.unite" class="px-2 py-0.5 rounded text-[10px] font-black uppercase tracking-tighter bg-emerald-50 text-emerald-600 dark:bg-emerald-900/30">
                                            {{ analyse.unite }}
                                        </span>
                                        <span v-else class="text-slate-200">—</span>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <div class="flex justify-end items-center gap-2">
                                            <Link :href="route('laboratoire.analyses.ranges.edit', analyse.id)" 
                                                class="inline-flex items-center gap-2 rounded-lg bg-primary-600 px-4 py-2 text-[10px] font-black uppercase tracking-widest text-white shadow-sm transition-all hover:bg-primary-700 active:scale-95">
                                                <em class="ni ni-edit"></em> Gérer
                                            </Link>
                                            
                                            <button v-if="analyse.ranges.length > 0" @click="resetRanges(analyse)"
                                                class="inline-flex items-center gap-2 rounded-lg bg-slate-100 px-4 py-2 text-[10px] font-black uppercase tracking-widest text-slate-600 shadow-sm transition-all hover:bg-red-50 hover:text-red-600 active:scale-95 dark:bg-slate-700 dark:text-slate-300 dark:hover:bg-red-900/30 border border-slate-200 dark:border-slate-600"
                                                title="Réinitialiser cette analyse">
                                                <em class="ni ni-reload"></em> Reset
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="analyses.data.length === 0">
                                    <td colspan="6" class="px-6 py-16 text-center text-slate-400 font-bold italic">
                                        <em class="ni ni-search text-3xl opacity-20 block mb-2"></em>
                                        Aucune analyse trouvée pour cette recherche.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="p-6 border-t border-gray-200 dark:border-gray-700">
                        <Pagination :links="analyses.links" />
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
