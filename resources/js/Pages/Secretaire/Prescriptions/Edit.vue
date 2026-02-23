<template>
    <AppLayout>
        <div class="max-w-3xl space-y-6">
            <div>
                <h1 class="text-2xl font-semibold text-slate-900 dark:text-white">Modifier preinscription</h1>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                    Chargement Inertia actif sur cette route.
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
                        <dd class="mt-1 text-sm text-slate-700 dark:text-slate-300">{{ prescription.patient || '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-wide text-slate-500">Prescripteur</dt>
                        <dd class="mt-1 text-sm text-slate-700 dark:text-slate-300">{{ prescription.prescripteur || '-' }}</dd>
                    </div>
                </dl>

                <p class="mt-6 text-sm text-slate-700 dark:text-slate-300">
                    L'editeur complet de prescription est en migration vers Inertia.
                </p>

                <div class="mt-6">
                    <Link
                        :href="route('secretaire.prescription.index')"
                        class="inline-flex items-center justify-center rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white hover:bg-primary-700 transition-colors"
                    >
                        Retour a la liste
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
    prescription: Object,
});
</script>
