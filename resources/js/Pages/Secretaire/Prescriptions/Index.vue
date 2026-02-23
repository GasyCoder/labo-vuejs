<template>
    <AppLayout>
        <div class="space-y-6">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-semibold text-slate-900 dark:text-white">Preinscriptions</h1>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Liste Inertia des preinscriptions secretaire.</p>
                </div>

                <Link
                    :href="route('secretaire.prescription.create')"
                    class="inline-flex items-center justify-center rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white hover:bg-primary-700 transition-colors"
                >
                    Nouvelle preinscription
                </Link>
            </div>

            <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
                <button
                    type="button"
                    class="rounded-lg border px-4 py-3 text-left transition-colors"
                    :class="form.tab === 'actives' ? 'border-primary-500 bg-primary-50 text-primary-700 dark:bg-primary-900/20 dark:text-primary-300' : 'border-slate-200 bg-white text-slate-700 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200'"
                    @click="changeTab('actives')"
                >
                    <div class="text-xs uppercase tracking-wide">Actives</div>
                    <div class="mt-1 text-xl font-bold">{{ counts.actives }}</div>
                </button>
                <button
                    type="button"
                    class="rounded-lg border px-4 py-3 text-left transition-colors"
                    :class="form.tab === 'valide' ? 'border-primary-500 bg-primary-50 text-primary-700 dark:bg-primary-900/20 dark:text-primary-300' : 'border-slate-200 bg-white text-slate-700 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200'"
                    @click="changeTab('valide')"
                >
                    <div class="text-xs uppercase tracking-wide">Validees</div>
                    <div class="mt-1 text-xl font-bold">{{ counts.valide }}</div>
                </button>
                <button
                    type="button"
                    class="rounded-lg border px-4 py-3 text-left transition-colors"
                    :class="form.tab === 'archive' ? 'border-primary-500 bg-primary-50 text-primary-700 dark:bg-primary-900/20 dark:text-primary-300' : 'border-slate-200 bg-white text-slate-700 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200'"
                    @click="changeTab('archive')"
                >
                    <div class="text-xs uppercase tracking-wide">Archivees</div>
                    <div class="mt-1 text-xl font-bold">{{ counts.archive }}</div>
                </button>
                <button
                    type="button"
                    class="rounded-lg border px-4 py-3 text-left transition-colors"
                    :class="form.tab === 'deleted' ? 'border-primary-500 bg-primary-50 text-primary-700 dark:bg-primary-900/20 dark:text-primary-300' : 'border-slate-200 bg-white text-slate-700 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200'"
                    @click="changeTab('deleted')"
                >
                    <div class="text-xs uppercase tracking-wide">Corbeille</div>
                    <div class="mt-1 text-xl font-bold">{{ counts.deleted }}</div>
                </button>
            </div>

            <form class="grid grid-cols-1 gap-3 sm:grid-cols-4" @submit.prevent="applyFilters">
                <input
                    v-model="form.search"
                    type="text"
                    class="sm:col-span-3 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/30 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                    placeholder="Rechercher reference, nom, telephone..."
                >
                <button
                    type="submit"
                    class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700 transition-colors dark:bg-slate-100 dark:text-slate-900 dark:hover:bg-white"
                >
                    Filtrer
                </button>
            </form>

            <div class="overflow-hidden rounded-xl border border-slate-200 bg-white dark:border-slate-700 dark:bg-slate-800">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                        <thead class="bg-slate-50 dark:bg-slate-900/50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Reference</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Patient</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Prescripteur</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Statut</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Analyses</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Date</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                            <tr v-for="prescription in prescriptions.data" :key="prescription.id">
                                <td class="px-4 py-3 font-semibold text-slate-900 dark:text-white">{{ prescription.reference }}</td>
                                <td class="px-4 py-3 text-slate-700 dark:text-slate-300">
                                    <div>{{ prescription.patient?.nom_complet || '-' }}</div>
                                    <div class="text-xs text-slate-500">{{ prescription.patient?.telephone || '' }}</div>
                                </td>
                                <td class="px-4 py-3 text-slate-700 dark:text-slate-300">{{ prescription.prescripteur?.nom || '-' }}</td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700 dark:bg-slate-700 dark:text-slate-100">
                                        {{ prescription.status_label }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-slate-700 dark:text-slate-300">{{ prescription.analyses_count }}</td>
                                <td class="px-4 py-3 text-slate-700 dark:text-slate-300">{{ prescription.created_at || prescription.deleted_at || '-' }}</td>
                                <td class="px-4 py-3 text-right">
                                    <Link
                                        :href="route('secretaire.prescription.edit', prescription.id)"
                                        class="text-sm font-semibold text-primary-600 hover:text-primary-700 dark:text-primary-400"
                                    >
                                        Ouvrir
                                    </Link>
                                </td>
                            </tr>
                            <tr v-if="prescriptions.data.length === 0">
                                <td colspan="7" class="px-4 py-10 text-center text-sm text-slate-500 dark:text-slate-400">
                                    Aucune preinscription trouvee.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="border-t border-slate-200 px-4 py-4 dark:border-slate-700">
                    <Pagination :links="prescriptions.links" />
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { reactive } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Pagination from '@/Components/Pagination.vue';

const props = defineProps({
    prescriptions: Object,
    filters: Object,
    counts: Object,
});

const form = reactive({
    search: props.filters.search || '',
    tab: props.filters.tab || 'actives',
    perPage: props.filters.perPage || 10,
});

const applyFilters = () => {
    router.get(route('secretaire.prescription.index'), form, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

const changeTab = (tab) => {
    form.tab = tab;
    applyFilters();
};
</script>
