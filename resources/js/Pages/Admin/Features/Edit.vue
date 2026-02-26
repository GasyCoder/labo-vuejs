<template>
<AppLayout>
    <div class="px-4 sm:px-6 lg:px-8 pt-2 pb-8 max-w-[1600px] mx-auto">
        <!-- Breadcrumbs / Back -->
        <div class="mb-8">
            <Link :href="route('admin.features.index')" class="group inline-flex items-center text-sm font-semibold text-slate-500 hover:text-indigo-600 dark:text-slate-400 dark:hover:text-indigo-400 transition-colors">
                <div class="w-8 h-8 rounded-lg bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 flex items-center justify-center mr-3 group-hover:border-indigo-200 dark:group-hover:border-indigo-900 transition-all shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                </div>
                Retour à la liste des clients
            </Link>
        </div>

        <!-- Header -->
        <div class="flex flex-col md:flex-row md:items-end justify-between gap-6 mb-10">
            <div>
                <div class="flex items-center gap-3 mb-2">
                    <span class="px-3 py-1 rounded-full bg-indigo-100 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-400 text-[10px] font-bold uppercase tracking-widest">Configuration SaaS</span>
                    <span class="text-slate-300 dark:text-slate-600">/</span>
                    <span class="text-slate-500 dark:text-slate-400 text-sm font-medium">ID: {{ client.id }}</span>
                </div>
                <h2 class="font-heading font-extrabold text-3xl text-gray-900 dark:text-white tracking-tight">
                    {{ client.name }}
                </h2>
                <p class="text-slate-500 dark:text-slate-400 mt-2 max-w-2xl text-lg">
                    Les modifications sont <span class="text-indigo-600 dark:text-indigo-400 font-bold underline decoration-2 underline-offset-4 italic">enregistrées automatiquement</span> à chaque clic.
                </p>
            </div>

            <!-- Auto-save indicator -->
            <div class="flex items-center gap-3 px-5 py-2.5 rounded-2xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 shadow-sm">
                <div class="relative flex items-center justify-center w-3 h-3">
                    <span v-if="form.processing" class="absolute inline-flex h-full w-full rounded-full bg-indigo-400 opacity-75 animate-ping"></span>
                    <span :class="['relative inline-flex rounded-full h-2 w-2', form.processing ? 'bg-indigo-500' : 'bg-emerald-500']"></span>
                </div>
                <span class="text-xs font-bold uppercase tracking-wider" :class="form.processing ? 'text-indigo-600 animate-pulse' : 'text-slate-500'">
                    {{ form.processing ? 'Enregistrement...' : 'Tous les changements sont sauvegardés' }}
                </span>
            </div>
        </div>

        <!-- Features Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 mb-12">
            <div v-for="(feature, index) in form.features" :key="feature.key" 
                 :class="[
                    'group relative rounded-3xl p-6 shadow-sm border transition-all flex flex-col justify-between h-full',
                    feature.is_enabled 
                        ? 'bg-white dark:bg-slate-800 border-indigo-200 dark:border-indigo-500/30' 
                        : 'bg-slate-50/50 dark:bg-slate-900/50 border-slate-200 dark:border-slate-800 grayscale-[0.5] opacity-80'
                 ]">
                
                <div>
                    <!-- Icon & Toggle Header -->
                    <div class="flex items-center justify-between mb-6">
                        <div :class="[
                            'w-14 h-14 rounded-2xl border flex items-center justify-center shrink-0 transition-all',
                            feature.is_enabled 
                                ? 'bg-indigo-50 dark:bg-indigo-900/30 border-indigo-100 dark:border-indigo-800' 
                                : 'bg-white dark:bg-slate-800 border-slate-100 dark:border-slate-700'
                        ]">
                            <em v-if="feature.key === 'prescriptions_tracking'" class="ni ni-list-thumb text-3xl text-indigo-500"></em>
                            <em v-else-if="feature.key === 'notifications_sms_email_validated'" class="ni ni-emails text-3xl text-sky-500"></em>
                            <em v-else-if="feature.key === 'journal_decaissement'" class="ni ni-report-profit text-3xl text-emerald-500"></em>
                            <em v-else-if="feature.key === 'patient_invoice_email'" class="ni ni-mail text-3xl text-amber-500"></em>
                            <em v-else class="ni ni-star text-3xl text-slate-400"></em>
                        </div>

                        <label class="relative inline-flex items-center cursor-pointer scale-125 origin-right">
                            <input type="checkbox" 
                                   v-model="feature.is_enabled" 
                                   @change="autoSave"
                                   :disabled="form.processing"
                                   class="sr-only peer">
                            <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-500/20 dark:peer-focus:ring-indigo-800/20 rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-indigo-600"></div>
                        </label>
                    </div>

                    <!-- Content -->
                    <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-2">
                        {{ feature.name }}
                    </h3>
                    <p class="text-slate-500 dark:text-slate-400 text-sm leading-relaxed">
                        {{ feature.description }}
                    </p>
                </div>

                <!-- Bottom Info -->
                <div class="mt-8 pt-4 border-t border-slate-50 dark:border-slate-700/50 flex items-center justify-between">
                    <span class="text-[10px] font-bold font-mono text-slate-400 uppercase">Module: {{ feature.key }}</span>
                    <div v-if="feature.is_enabled" class="flex items-center gap-1.5 text-[10px] font-bold text-emerald-500 uppercase tracking-wider">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                        </svg>
                        Activé
                    </div>
                    <div v-else class="text-[10px] font-bold text-slate-300 dark:text-slate-600 uppercase tracking-wider">
                        Inactif
                    </div>
                </div>
            </div>
        </div>
    </div>
</AppLayout>
</template>

<script setup>
import { useForm, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    client: Object,
    features: Array,
});

const form = useForm({
    features: JSON.parse(JSON.stringify(props.features)),
});

const autoSave = () => {
    form.put(route('admin.features.update', props.client.id), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            // Optional: Success feedback
        },
    });
};
</script>
