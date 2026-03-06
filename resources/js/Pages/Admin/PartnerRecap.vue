<template>
<AppLayout>
    <div class="px-4 sm:px-6 lg:px-8 py-8 w-full max-w-9xl mx-auto">
        
        <!-- Header -->
        <div class="sm:flex sm:justify-between sm:items-center mb-8">
            <div class="mb-4 sm:mb-0">
                <h1 class="text-2xl md:text-3xl text-slate-800 dark:text-slate-100 font-bold mb-1 flex items-center">
                    <div class="w-10 h-10 rounded-xl bg-pink-500 flex items-center justify-center mr-3 shadow-pink-200 shadow-lg">
                        <em class="ni ni-users text-white text-xl"></em>
                    </div>
                    Récapitulatif Partenaires 🤝
                </h1>
                <p class="text-sm text-slate-500 dark:text-slate-400">
                    Suivi de la facturation et des prescriptions par partenaire business
                </p>
            </div>

            <!-- Filtres de date -->
            <div class="flex flex-wrap items-center gap-3 bg-white dark:bg-slate-800 p-2 rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm">
                <div class="flex items-center gap-2">
                    <label class="text-[10px] font-bold uppercase text-slate-400">Du</label>
                    <input type="date" v-model="form.date_from" class="text-xs border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900 rounded-lg focus:ring-pink-500">
                </div>
                <div class="flex items-center gap-2">
                    <label class="text-[10px] font-bold uppercase text-slate-400">Au</label>
                    <input type="date" v-model="form.date_to" class="text-xs border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900 rounded-lg focus:ring-pink-500">
                </div>
                <button @click="applyFilters" class="px-4 py-2 bg-pink-600 text-white text-xs font-bold rounded-lg hover:bg-pink-700 transition-all shadow-md shadow-pink-200 dark:shadow-none">
                    Actualiser
                </button>
                <div class="h-6 w-px bg-slate-200 dark:bg-slate-700 mx-1"></div>
                <a :href="route('admin.partenaires.export', form)" class="px-4 py-2 bg-emerald-600 text-white text-xs font-bold rounded-lg hover:bg-emerald-700 transition-all shadow-md shadow-emerald-200 dark:shadow-none flex items-center">
                    <em class="ni ni-file-xls mr-1.5 text-sm"></em>
                    Export Excel
                </a>
            </div>
        </div>

        <!-- Cartes de Totaux Globaux -->
        <div class="grid grid-cols-12 gap-6 mb-8">
            <div class="col-span-full sm:col-span-6 xl:col-span-3 bg-white dark:bg-slate-800 p-5 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 border-l-4 border-l-blue-500">
                <div class="text-xs font-semibold text-slate-400 uppercase mb-1">Chiffre d'Affaires Partenaires</div>
                <div class="text-2xl font-bold text-slate-800 dark:text-slate-100">{{ formatN(totals.total_amount) }} Ar</div>
                <div class="text-xs text-slate-400 mt-1">{{ totals.total_prescriptions }} prescriptions au total</div>
            </div>
            <div class="col-span-full sm:col-span-6 xl:col-span-3 bg-white dark:bg-slate-800 p-5 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 border-l-4 border-l-emerald-500">
                <div class="text-xs font-semibold text-slate-400 uppercase mb-1">Total Encaissé</div>
                <div class="text-2xl font-bold text-emerald-600">{{ formatN(totals.total_paid) }} Ar</div>
                <div class="text-xs text-slate-400 mt-1">Sur les dossiers partenaires</div>
            </div>
            <div class="col-span-full sm:col-span-6 xl:col-span-3 bg-white dark:bg-slate-800 p-5 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 border-l-4 border-l-rose-500">
                <div class="text-xs font-semibold text-slate-400 uppercase mb-1">Reste à Recouvrer</div>
                <div class="text-2xl font-bold text-rose-500">{{ formatN(totals.total_pending) }} Ar</div>
                <div class="text-xs text-slate-400 mt-1">Dettes partenaires en attente</div>
            </div>
            <div class="col-span-full sm:col-span-6 xl:col-span-3 bg-white dark:bg-slate-800 p-5 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 border-l-4 border-l-pink-500 text-center flex flex-col justify-center">
                <div class="text-xs font-semibold text-slate-400 uppercase mb-1">Nombre de Partenaires</div>
                <div class="text-2xl font-bold text-pink-600">{{ partners.length }}</div>
            </div>
        </div>

        <!-- Liste des Partenaires -->
        <div class="bg-white dark:bg-slate-800 shadow-xl rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="table-auto w-full dark:text-slate-300">
                    <thead class="text-xs uppercase text-slate-400 bg-slate-50 dark:bg-slate-700/40">
                        <tr>
                            <th class="px-6 py-4 text-left font-bold">Partenaire Business</th>
                            <th class="px-4 py-4 text-center font-bold">Volume</th>
                            <th class="px-4 py-4 text-right font-bold">CA Généré</th>
                            <th class="px-4 py-4 text-right font-bold">Reçu</th>
                            <th class="px-4 py-4 text-right font-bold">Dû / Solde</th>
                            <th class="px-6 py-4 text-center font-bold">Patients</th>
                        </tr>
                    </thead>
                    <tbody class="text-sm divide-y divide-slate-100 dark:divide-slate-700">
                        <template v-for="partner in partners" :key="partner.id">
                            <tr class="hover:bg-pink-50/30 dark:hover:bg-pink-900/10 transition-colors group">
                                <td class="px-6 py-4">
                                    <div class="flex items-center">
                                        <div class="w-8 h-8 rounded-full bg-slate-100 dark:bg-slate-700 flex items-center justify-center mr-3 text-slate-500 font-bold text-xs uppercase">
                                            {{ partner.nom_complet.substring(0, 2) }}
                                        </div>
                                        <div class="font-bold text-slate-800 dark:text-slate-100 group-hover:text-pink-600 transition-colors">{{ partner.nom_complet }}</div>
                                    </div>
                                </td>
                                <td class="px-4 py-4 text-center">
                                    <span class="px-2.5 py-1 rounded-full bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 text-xs font-bold">
                                        {{ partner.nb_prescriptions }} dossier(s)
                                    </span>
                                </td>
                                <td class="px-4 py-4 text-right font-semibold text-slate-700 dark:text-slate-200">{{ formatN(partner.montant_total) }} Ar</td>
                                <td class="px-4 py-4 text-right text-emerald-600 font-medium">{{ formatN(partner.montant_paye) }} Ar</td>
                                <td class="px-4 py-4 text-right text-rose-500 font-black">{{ formatN(partner.reste_a_payer) }} Ar</td>
                                <td class="px-6 py-4 text-center">
                                    <button @click="toggleDetails(partner.id)" class="inline-flex items-center px-3 py-1.5 bg-slate-100 dark:bg-slate-700 hover:bg-pink-100 dark:hover:bg-pink-900/30 text-slate-600 dark:text-slate-300 hover:text-pink-600 rounded-lg transition-all border border-slate-200 dark:border-slate-600">
                                        <em :class="['ni mr-1.5', expanded.includes(partner.id) ? 'ni-chevron-up' : 'ni-chevron-down']"></em>
                                        {{ expanded.includes(partner.id) ? 'Masquer' : 'Voir Patients' }}
                                    </button>
                                </td>
                            </tr>
                            
                            <!-- Sous-tableau des patients -->
                            <tr v-if="expanded.includes(partner.id)" class="bg-slate-50/50 dark:bg-slate-900/40">
                                <td colspan="6" class="px-6 py-6">
                                    <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 shadow-inner overflow-hidden animate-in slide-in-from-top-2 duration-300">
                                        <div class="px-4 py-3 bg-slate-50 dark:bg-slate-700/50 border-b border-slate-200 dark:border-slate-700 flex justify-between items-center">
                                            <span class="text-xs font-bold uppercase text-slate-500">Liste des prescriptions pour {{ partner.nom_complet }}</span>
                                            <span class="text-[10px] text-slate-400 italic">Période du {{ formatDate(filters.date_from) }} au {{ formatDate(filters.date_to) }}</span>
                                        </div>
                                        <table class="w-full text-xs">
                                            <thead class="text-slate-400 border-b border-slate-100 dark:border-slate-700">
                                                <tr>
                                                    <th class="px-4 py-3 text-left">Date</th>
                                                    <th class="px-4 py-3 text-left">Réf. Dossier</th>
                                                    <th class="px-4 py-3 text-left">Patient</th>
                                                    <th class="px-4 py-3 text-right">Montant Analyse</th>
                                                    <th class="px-4 py-3 text-right">Montant Payé</th>
                                                    <th class="px-4 py-3 text-right">Reste à payer</th>
                                                    <th class="px-4 py-3 text-center">Action</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-slate-50 dark:divide-slate-700/50 text-slate-600 dark:text-slate-400">
                                                <tr v-for="item in partner.details" :key="item.id" class="hover:bg-slate-50 dark:hover:bg-slate-700/20">
                                                    <td class="px-4 py-3">{{ item.date }}</td>
                                                    <td class="px-4 py-3 font-mono font-bold text-indigo-500">{{ item.reference }}</td>
                                                    <td class="px-4 py-3 uppercase font-bold text-slate-700 dark:text-slate-300">{{ item.patient }}</td>
                                                    <td class="px-4 py-3 text-right font-medium">{{ formatN(item.montant) }}</td>
                                                    <td class="px-4 py-3 text-right text-emerald-600">{{ formatN(item.paye) }}</td>
                                                    <td class="px-4 py-3 text-right font-bold" :class="item.montant - item.paye > 0 ? 'text-rose-500' : 'text-slate-300'">
                                                        {{ formatN(item.montant - item.paye) }}
                                                    </td>
                                                    <td class="px-4 py-3 text-center">
                                                        <Link :href="route('admin.prescriptions.show', item.id)" class="text-indigo-500 hover:underline">Ouvrir</Link>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </td>
                            </tr>
                        </template>
                        <tr v-if="partners.length === 0">
                            <td colspan="6" class="px-6 py-20 text-center">
                                <div class="flex flex-col items-center">
                                    <em class="ni ni-search text-5xl text-slate-200 mb-4"></em>
                                    <p class="text-slate-400 italic">Aucun partenaire actif ou aucune donnée sur cette période.</p>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    partners: Array,
    filters: Object,
    totals: Object,
});

const form = ref({
    date_from: props.filters.date_from,
    date_to: props.filters.date_to,
});

const expanded = ref([]);

const toggleDetails = (id) => {
    if (expanded.value.includes(id)) {
        expanded.value = expanded.value.filter(item => item !== id);
    } else {
        expanded.value.push(id);
    }
};

const applyFilters = () => {
    router.get(route('admin.partenaires.recap'), form.value, {
        preserveState: true,
        preserveScroll: true,
    });
};

const formatN = (n) => Number(n || 0).toLocaleString('fr-FR');

const formatDate = (dateString) => {
    if (!dateString) return '';
    return new Date(dateString).toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric' });
};
</script>
