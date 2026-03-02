<template>
<AppLayout>
    <div class="px-4 sm:px-6 lg:px-8 pt-2 pb-12 max-w-[1600px] mx-auto">
        <!-- Header Section -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-8">
            <div>
                <div class="flex items-center gap-2 mb-1">
                    <span class="inline-flex items-center px-2 py-0.5 rounded-md bg-indigo-600 text-white text-[10px] font-black uppercase tracking-widest shadow-sm">
                        SaaS Master
                    </span>
                    <span class="text-slate-300 dark:text-slate-700">•</span>
                    <span class="text-slate-500 dark:text-slate-400 text-xs font-medium">Administration des Clients</span>
                </div>
                <h2 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight">
                    Gestion des Accès Premium
                </h2>
            </div>
            
            <!-- Quick Stats -->
            <div class="flex items-center gap-4">
                <div class="px-5 py-2.5 bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-sm">
                    <div class="text-[10px] font-black text-slate-400 uppercase tracking-widest leading-none mb-1">Total Clients</div>
                    <div class="text-xl font-black text-slate-900 dark:text-white leading-none">{{ clients.length }}</div>
                </div>
            </div>
        </div>

        <!-- Clients Table Card -->
        <div class="bg-white dark:bg-slate-800 rounded-[2rem] border border-slate-200 dark:border-slate-700 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-slate-100 dark:border-slate-700/50">
                            <th class="px-6 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest">Laboratoire / Client</th>
                            <th class="px-6 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest text-center">Plan & Statut</th>
                            <th class="px-6 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest text-center">Modules Actifs</th>
                            <th class="px-6 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest text-center">Consommation (SMS/Email)</th>
                            <th class="px-6 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest text-right whitespace-nowrap">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50 dark:divide-slate-700/50">
                        <tr v-for="client in clients" :key="client.id" class="hover:bg-slate-50/50 dark:hover:bg-slate-900/30 transition-colors group">
                            <!-- Client Info -->
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-4">
                                    <div class="w-10 h-10 rounded-xl bg-slate-100 dark:bg-slate-700 flex items-center justify-center font-black text-slate-500 dark:text-slate-400 shrink-0 group-hover:bg-indigo-600 group-hover:text-white transition-colors">
                                        {{ client.name.charAt(0) }}
                                    </div>
                                    <div>
                                        <div class="font-bold text-slate-900 dark:text-white">{{ client.name }}</div>
                                        <div class="text-[10px] font-medium text-slate-400">ID: #{{ client.id }} • {{ client.users_count }} utilisateur(s)</div>
                                    </div>
                                </div>
                            </td>

                            <!-- Plan & Status -->
                            <td class="px-6 py-4 text-center">
                                <div v-if="client.plan_name" class="inline-flex flex-col items-center">
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-indigo-50 text-indigo-600 dark:bg-indigo-900/30 dark:text-indigo-400 text-[9px] font-black uppercase tracking-wider border border-indigo-100 dark:border-indigo-800">
                                        <em class="ni ni-star-fill"></em>
                                        {{ client.plan_name }}
                                    </span>
                                    <span class="text-[9px] font-bold text-slate-400 mt-1">{{ formatNumber(client.subscription_price) }} Ar / mois</span>
                                </div>
                                <div v-else>
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full bg-slate-100 text-slate-500 dark:bg-slate-700 dark:text-slate-400 text-[9px] font-black uppercase tracking-wider">
                                        Gratuit / Standard
                                    </span>
                                </div>
                            </td>

                            <!-- Active Modules (Visual Dot Indicators) -->
                            <td class="px-6 py-4">
                                <div class="flex items-center justify-center gap-2">
                                    <div v-for="(config, key) in availableFeatures" :key="key" 
                                         class="group/feat relative"
                                         :title="config.name">
                                        <div :class="[
                                            'w-8 h-8 rounded-lg flex items-center justify-center transition-all border',
                                            client.enabled_features?.includes(key) 
                                                ? 'bg-emerald-50 dark:bg-emerald-900/30 border-emerald-100 dark:border-emerald-800 text-emerald-600 dark:text-emerald-400' 
                                                : 'bg-slate-50 dark:bg-slate-900/50 border-slate-100 dark:border-slate-800 text-slate-300 dark:text-slate-700'
                                        ]">
                                            <em :class="[
                                                'text-lg ni',
                                                key === 'prescriptions_tracking' ? 'ni-list-thumb' : 
                                                key === 'notifications_sms_email_validated' ? 'ni-emails' : 
                                                key === 'journal_decaissement' ? 'ni-report-profit' : 'ni-mail'
                                            ]"></em>
                                        </div>
                                        <!-- Tooltip (Simulated) -->
                                        <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 px-2 py-1 bg-slate-900 text-white text-[9px] font-bold rounded opacity-0 invisible group-hover/feat:visible group-hover/feat:opacity-100 transition-all whitespace-nowrap z-20">
                                            {{ config.name }}
                                        </div>
                                    </div>
                                </div>
                            </td>

                            <!-- Consumption -->
                            <td class="px-6 py-4">
                                <div class="flex flex-col gap-2 max-w-[120px] mx-auto">
                                    <div class="flex items-center justify-between text-[9px] font-bold uppercase tracking-tight text-slate-400 leading-none">
                                        <span>SMS: {{ client.sms_used_this_month }}</span>
                                        <span class="opacity-50">/{{ client.sms_quota }}</span>
                                    </div>
                                    <div class="w-full bg-slate-100 dark:bg-slate-700 h-1 rounded-full overflow-hidden">
                                        <div class="bg-sky-500 h-full rounded-full" :style="{ width: Math.min(100, (client.sms_used_this_month / client.sms_quota) * 100) + '%' }"></div>
                                    </div>
                                    <div class="flex items-center justify-between text-[9px] font-bold uppercase tracking-tight text-slate-400 leading-none mt-1">
                                        <span>Email: {{ client.email_used_this_month }}</span>
                                        <span class="opacity-50">/{{ client.email_quota }}</span>
                                    </div>
                                    <div class="w-full bg-slate-100 dark:bg-slate-700 h-1 rounded-full overflow-hidden">
                                        <div class="bg-indigo-500 h-full rounded-full" :style="{ width: Math.min(100, (client.email_used_this_month / client.email_quota) * 100) + '%' }"></div>
                                    </div>
                                </div>
                            </td>

                            <!-- Actions -->
                            <td class="px-6 py-4 text-right">
                                <Link 
                                    :href="route('admin.features.edit', client.id)" 
                                    class="inline-flex items-center justify-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-[10px] font-black uppercase tracking-widest rounded-xl transition-all shadow-lg shadow-indigo-100 dark:shadow-none active:scale-95"
                                >
                                    <em class="ni ni-setting text-sm"></em>
                                    Configurer
                                </Link>
                            </td>
                        </tr>

                        <!-- Empty State -->
                        <tr v-if="!clients?.length">
                            <td colspan="5" class="px-6 py-20 text-center">
                                <div class="w-16 h-16 bg-slate-50 dark:bg-slate-900 rounded-full flex items-center justify-center mx-auto mb-4 border-2 border-dashed border-slate-200 dark:border-slate-700">
                                    <em class="ni ni-users text-3xl text-slate-300"></em>
                                </div>
                                <div class="text-sm font-bold text-slate-900 dark:text-white">Aucun client trouvé</div>
                                <div class="text-xs text-slate-400 mt-1">Vous n'avez pas encore de clients enregistrés dans le système.</div>
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
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

defineProps({
    clients: Array,
    availableFeatures: Object,
});

const formatNumber = (num) => {
    return new Intl.NumberFormat('fr-FR').format(num);
};
</script>
