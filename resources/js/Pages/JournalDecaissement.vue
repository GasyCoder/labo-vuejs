<template>
<AppLayout>
    <!-- Header avec titre -->
    <div class="container mx-auto px-4 py-6 sm:px-6 lg:px-8">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
            <div>
                <h1 class="text-3xl font-heading font-bold text-gray-900 dark:text-white">Journal de Décaissement</h1>
                <p class="text-gray-600 dark:text-gray-400 mt-2">Suivi des décaissements (commissions prescripteurs) par
                    date de paiement</p>
            </div>

            <!-- Informations période -->
            <div
                class="bg-gradient-to-r from-orange-50 to-orange-100 dark:from-orange-900/20 dark:to-orange-800/20 px-4 py-3 rounded-xl border border-orange-200 dark:border-orange-700">
                <div class="text-xs font-medium text-orange-600 dark:text-orange-400 uppercase tracking-wide">Période de
                    Décaissement</div>
                <div class="text-lg font-bold text-orange-800 dark:text-orange-300">
                    {{ formatDate(dateDebut) }} - {{ formatDate(dateFin) }}
                </div>
            </div>
        </div>
    </div>

    <div class="container mx-auto px-4 py-8 sm:px-6 lg:px-8">
        <!-- Filtres et actions -->
        <div
            class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                <!-- Date début -->
                <div>
                    <label for="dateDebut" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">
                        Date début
                    </label>
                    <input v-model="form.dateDebut" @change="updateFilters" type="date" id="dateDebut"
                        class="w-full py-3 px-4 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 dark:focus:ring-primary-400 focus:border-primary-500 dark:focus:border-primary-400 transition-all">
                </div>

                <!-- Date fin -->
                <div>
                    <label for="dateFin" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">
                        Date fin
                    </label>
                    <input v-model="form.dateFin" @change="updateFilters" type="date" id="dateFin"
                        class="w-full py-3 px-4 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 dark:focus:ring-primary-400 focus:border-primary-500 dark:focus:border-primary-400 transition-all">
                </div>

                <!-- Actions -->
                <div class="flex items-end">
                    <a :href="route('secretaire.journal-decaissement.export', { dateDebut: form.dateDebut, dateFin: form.dateFin })"
                        class="w-full inline-flex items-center justify-center px-4 py-3 text-sm font-medium text-white bg-red-600 hover:bg-red-700 border border-transparent rounded-xl shadow-sm focus:ring-2 focus:ring-red-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition-colors">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                            </path>
                        </svg>
                        Export PDF
                    </a>
                </div>
            </div>

            <!-- Résumé -->
            <div
                class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                <div class="flex items-center space-x-4">
                    <span class="text-sm font-medium text-gray-600 dark:text-gray-400">
                        {{ decaissements.length }} ligne(s) de commission dans cette période
                    </span>
                </div>
                <div class="text-lg font-bold text-gray-900 dark:text-white">
                    Total Commissions: {{ formatCurrency(totalCommissions) }} Ar.
                </div>
            </div>
        </div>

        <!-- Journal de Décaissement -->
        <div class="bg-white dark:bg-slate-900 rounded-lg shadow overflow-hidden">
            <!-- En-tête avec période -->
            <div class="px-6 py-4 bg-gray-50 dark:bg-slate-800 border-b border-gray-200 dark:border-slate-700">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-slate-200 text-center">
                    JOURNAL DES DÉCAISSEMENTS du {{ formatDate(dateDebut) }} au
                    {{ formatDate(dateFin) }}
                </h3>
            </div>

            <template v-if="decaissements && decaissements.length > 0">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-100 dark:bg-slate-800">
                            <tr>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wide">
                                    Date</th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wide">
                                    Prescripteur</th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wide">
                                    Dossier / Patient</th>
                                <th scope="col"
                                    class="px-6 py-3 text-right text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wide">
                                    Montant Commission</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-slate-900 divide-y divide-gray-100 dark:divide-slate-800">
                            <tr v-for="paiement in decaissements" :key="paiement.id" class="hover:bg-gray-50 dark:hover:bg-slate-800 transition-colors duration-200">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">
                                    {{ paiement.date_paiement_format }}
                                </td>
                                <td
                                    class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">
                                    {{ paiement.prescription?.prescripteur?.nom_complet || 'N/A' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">
                                    <div class="font-medium text-blue-600 dark:text-blue-400">
                                        {{ paiement.prescription?.patient?.numero_dossier || 'N/A' }}
                                    </div>
                                    <div>
                                        {{ paiement.prescription?.patient?.nom_complet || 'N/A' }}
                                    </div>
                                </td>
                                <td
                                    class="px-6 py-4 whitespace-nowrap text-sm text-right font-bold text-red-600 dark:text-red-400">
                                    {{ formatCurrency(paiement.commission_prescripteur) }} Ar.
                                </td>
                            </tr>
                        </tbody>
                        <tfoot class="bg-gray-50 dark:bg-slate-800 font-bold">
                            <tr>
                                <td colspan="3" class="px-6 py-4 text-right text-gray-700 dark:text-gray-300 uppercase">
                                    Total Général</td>
                                <td class="px-6 py-4 text-right text-lg text-red-700 dark:text-red-400">
                                    {{ formatCurrency(totalCommissions) }} Ar.
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </template>
            <template v-else>
                <!-- État vide -->
                <div class="px-6 py-16 text-center">
                    <div class="flex flex-col items-center">
                        <div
                            class="w-16 h-16 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mb-4">
                            <svg class="w-8 h-8 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z">
                                </path>
                            </svg>
                        </div>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2">Aucun décaissement trouvé</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 max-w-sm">
                            Aucune commission n'a été enregistrée durant cette période.
                        </p>
                    </div>
                </div>
            </template>
        </div>
    </div>
</AppLayout>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    dateDebut: String,
    dateFin: String,
    totalCommissions: Number,
    decaissements: Array
});

const form = useForm({
    dateDebut: props.dateDebut,
    dateFin: props.dateFin
});

const updateFilters = () => {
    form.get(route('secretaire.journal-decaissement'), {
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
</script>
