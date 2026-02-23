<template>
    <AppLayout>
        <div class="max-w-4xl space-y-6">
            <div>
                <h1 class="text-2xl font-semibold text-slate-900 dark:text-white">{{ title }}</h1>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                    Route migree vers Inertia (contexte: {{ context }}).
                </p>
            </div>

            <div class="rounded-xl border border-slate-200 bg-white p-6 dark:border-slate-700 dark:bg-slate-800">
                <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <dt class="text-xs uppercase tracking-wide text-slate-500">Reference</dt>
                        <dd class="mt-1 text-sm font-semibold text-slate-900 dark:text-white">{{ prescription.reference }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-wide text-slate-500">Statut</dt>
                        <dd class="mt-1 text-sm font-semibold text-slate-900 dark:text-white">{{ prescription.status }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-wide text-slate-500">Patient</dt>
                        <dd class="mt-1 text-sm text-slate-700 dark:text-slate-300">{{ prescription.patient?.nom_complet || '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-wide text-slate-500">Telephone</dt>
                        <dd class="mt-1 text-sm text-slate-700 dark:text-slate-300">{{ prescription.patient?.telephone || '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-wide text-slate-500">Prescripteur</dt>
                        <dd class="mt-1 text-sm text-slate-700 dark:text-slate-300">{{ prescription.prescripteur || '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-wide text-slate-500">Analyses</dt>
                        <dd class="mt-1 text-sm text-slate-700 dark:text-slate-300">{{ prescription.analyses_count }}</dd>
                    </div>
                </dl>

                <div class="mt-6 flex flex-wrap gap-3">
                    <a
                        :href="route('laboratoire.prescription.pdf', prescription.id)"
                        target="_blank"
                        class="inline-flex items-center justify-center rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white hover:bg-primary-700 transition-colors"
                    >
                        Ouvrir PDF
                    </a>
                    <Link
                        :href="route('laboratoire.analyses.listes')"
                        class="inline-flex items-center justify-center rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 transition-colors dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-700"
                    >
                        Retour aux analyses
                    </Link>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

defineProps({
    context: String,
    title: String,
    prescription: Object,
});
</script>
