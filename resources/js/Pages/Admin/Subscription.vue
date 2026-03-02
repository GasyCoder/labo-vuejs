<template>
<AppLayout>
    <div class="px-4 sm:px-6 lg:px-8 pt-2 pb-12 max-w-[1400px] mx-auto">
        <!-- Header Section -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-8">
            <div>
                <div class="flex items-center gap-2 mb-1">
                    <span class="inline-flex items-center px-2 py-0.5 rounded-md bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 text-[10px] font-bold uppercase tracking-widest border border-indigo-100 dark:border-indigo-800/50">
                        Administration
                    </span>
                    <span class="text-slate-300 dark:text-slate-700">•</span>
                    <span class="text-slate-500 dark:text-slate-400 text-xs font-medium">Abonnement & Services</span>
                </div>
                <h2 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight">
                    Gestion du Pack Premium
                </h2>
            </div>

            <!-- Compact Plan Badge -->
            <div class="flex items-center gap-3 pl-2 pr-5 py-2 rounded-2xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 shadow-sm">
                <div :class="[
                    'w-10 h-10 rounded-xl flex items-center justify-center transition-colors',
                    client.plan_name ? 'bg-indigo-600 text-white' : 'bg-slate-100 dark:bg-slate-700 text-slate-400'
                ]">
                    <em class="ni ni-star-fill text-lg"></em>
                </div>
                <div>
                    <div class="text-[10px] font-bold text-slate-400 uppercase tracking-tight leading-none mb-1">Statut</div>
                    <div class="text-sm font-black text-slate-900 dark:text-white leading-none">
                        {{ client.plan_name || 'Aucun pack actif' }}
                    </div>
                </div>
            </div>
        </div>

        <!-- Inactive Features Alert Banner -->
        <transition 
            enter-active-class="transition duration-500 ease-out"
            enter-from-class="transform -translate-y-4 opacity-0"
            enter-to-class="transform translate-y-0 opacity-100"
        >
            <div v-if="enabledCount < features.length" class="mb-8">
                <div :class="[
                    'relative overflow-hidden rounded-[1.5rem] p-4 md:p-5 border flex flex-col md:flex-row md:items-center justify-between gap-4 transition-all',
                    enabledCount === 0 
                        ? 'bg-rose-50 border-rose-200 dark:bg-rose-900/10 dark:border-rose-900/30 text-rose-800 dark:text-rose-400' 
                        : 'bg-amber-50 border-amber-200 dark:bg-amber-900/10 dark:border-amber-900/30 text-amber-800 dark:text-amber-400'
                ]">
                    <!-- Decorative background icon -->
                    <em :class="[
                        'absolute -right-6 -bottom-6 text-8xl opacity-10 ni',
                        enabledCount === 0 ? 'ni-alert-circle' : 'ni-warning'
                    ]"></em>

                    <div class="flex items-center gap-4 relative z-10">
                        <div :class="[
                            'w-12 h-12 rounded-2xl flex items-center justify-center shrink-0 shadow-sm',
                            enabledCount === 0 ? 'bg-rose-600 text-white' : 'bg-amber-500 text-white'
                        ]">
                            <em :class="['text-2xl ni', enabledCount === 0 ? 'ni-alert-circle' : 'ni-warning']"></em>
                        </div>
                        <div>
                            <h4 class="font-black text-lg leading-tight">
                                {{ enabledCount === 0 ? "Modules premium désactivés" : "Modules partiellement activés" }}
                            </h4>
                            <p class="text-sm opacity-80 font-medium">
                                {{ enabledCount === 0 
                                    ? "Votre pack est actif mais aucun module n'est encore configuré." 
                                    : `Il reste ${features.length - enabledCount} module(s) premium à activer pour profiter pleinement de votre pack.` 
                                }}
                            </p>
                        </div>
                    </div>

                    <div class="relative z-10 flex items-center gap-3">
                        <span class="text-[10px] font-black uppercase tracking-widest px-3 py-1.5 rounded-lg bg-white/50 dark:bg-black/20 border border-current/10">
                            Action requise
                        </span>
                    </div>
                </div>
            </div>
        </transition>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            <!-- Left Column: Usage & Pricing -->
            <div class="lg:col-span-8 space-y-6">
                
                <!-- Quotas Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- SMS Card -->
                    <div class="bg-white dark:bg-slate-800 rounded-2xl p-5 border border-slate-200 dark:border-slate-700 shadow-sm hover:border-indigo-200 dark:hover:border-indigo-900 transition-colors">
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-sky-50 dark:bg-sky-900/20 flex items-center justify-center">
                                    <em class="ni ni-emails text-xl text-sky-500"></em>
                                </div>
                                <span class="font-bold text-slate-700 dark:text-slate-200">Quota SMS</span>
                            </div>
                            <div class="text-right">
                                <span class="text-lg font-black text-slate-900 dark:text-white">{{ client.sms_used_this_month }}</span>
                                <span class="text-[10px] font-bold text-slate-400 uppercase ml-1">/ {{ client.sms_quota }}</span>
                            </div>
                        </div>
                        <div class="w-full bg-slate-100 dark:bg-slate-700/50 rounded-full h-1.5 mb-3 overflow-hidden">
                            <div class="bg-sky-500 h-full rounded-full transition-all duration-700" :style="{ width: smsPercentage + '%' }"></div>
                        </div>
                        <div class="flex items-center justify-between text-[10px] font-bold text-slate-400 uppercase tracking-tight">
                            <span>Utilisation mensuelle</span>
                            <span :class="smsPercentage > 90 ? 'text-rose-500' : 'text-slate-500'">{{ Math.round(smsPercentage) }}%</span>
                        </div>
                    </div>

                    <!-- Email Card -->
                    <div class="bg-white dark:bg-slate-800 rounded-2xl p-5 border border-slate-200 dark:border-slate-700 shadow-sm hover:border-indigo-200 dark:hover:border-indigo-900 transition-colors">
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-indigo-50 dark:bg-indigo-900/20 flex items-center justify-center">
                                    <em class="ni ni-mail text-xl text-indigo-500"></em>
                                </div>
                                <span class="font-bold text-slate-700 dark:text-slate-200">Quota Emails</span>
                            </div>
                            <div class="text-right">
                                <span class="text-lg font-black text-slate-900 dark:text-white">{{ client.email_used_this_month }}</span>
                                <span class="text-[10px] font-bold text-slate-400 uppercase ml-1">/ {{ client.email_quota }}</span>
                            </div>
                        </div>
                        <div class="w-full bg-slate-100 dark:bg-slate-700/50 rounded-full h-1.5 mb-3 overflow-hidden">
                            <div class="bg-indigo-500 h-full rounded-full transition-all duration-700" :style="{ width: emailPercentage + '%' }"></div>
                        </div>
                        <div class="flex items-center justify-between text-[10px] font-bold text-slate-400 uppercase tracking-tight">
                            <span>Utilisation mensuelle</span>
                            <span :class="emailPercentage > 90 ? 'text-rose-500' : 'text-slate-500'">{{ Math.round(emailPercentage) }}%</span>
                        </div>
                    </div>
                </div>

                <!-- Features Grid -->
                <h3 class="text-2xl font-black text-slate-900 dark:text-white mb-8 px-2">Détails des Fonctionnalités</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div v-for="feature in features" :key="feature.key" 
                         :class="[
                            'group relative rounded-2xl p-5 border transition-all flex items-center gap-4 shadow-sm',
                            $page.props.enabledFeatures[feature.key] 
                                ? 'bg-white dark:bg-slate-800 border-emerald-100 dark:border-emerald-900/30' 
                                : 'bg-slate-50/50 dark:bg-slate-900/50 border-slate-200 dark:border-slate-800 opacity-60'
                         ]">
                        <div :class="[
                            'w-12 h-12 rounded-xl flex items-center justify-center shrink-0 border transition-all',
                            $page.props.enabledFeatures[feature.key] 
                                ? 'bg-emerald-50 dark:bg-emerald-900/30 border-emerald-100 dark:border-emerald-800 text-emerald-600 dark:text-emerald-400' 
                                : 'bg-white dark:bg-slate-800 border-slate-200 dark:border-slate-700 text-slate-400'
                        ]">
                            <em v-if="feature.key === 'prescriptions_tracking'" class="ni ni-list-thumb text-2xl"></em>
                            <em v-else-if="feature.key === 'notifications_sms_email_validated'" class="ni ni-emails text-2xl"></em>
                            <em v-else-if="feature.key === 'journal_decaissement'" class="ni ni-report-profit text-2xl"></em>
                            <em v-else-if="feature.key === 'patient_invoice_email'" class="ni ni-mail text-2xl"></em>
                            <em v-else class="ni ni-star text-2xl"></em>
                        </div>

                        <div class="flex-grow min-w-0">
                            <div class="flex items-center justify-between mb-0.5">
                                <h4 :class="['text-sm font-bold truncate', $page.props.enabledFeatures[feature.key] ? 'text-slate-900 dark:text-white' : 'text-slate-400 dark:text-slate-500']">
                                    {{ feature.name }}
                                </h4>
                                <div v-if="$page.props.enabledFeatures[feature.key]" class="flex items-center gap-1 text-emerald-500">
                                    <em class="ni ni-check-circle-fill text-[10px]"></em>
                                    <span class="text-[9px] font-black uppercase tracking-tighter">Actif</span>
                                </div>
                                <div v-else class="flex items-center gap-1 text-slate-400">
                                    <em class="ni ni-minus-circle-fill text-[10px]"></em>
                                    <span class="text-[9px] font-black uppercase tracking-tighter">Désactivé</span>
                                </div>
                            </div>
                            <p class="text-[11px] text-slate-500 dark:text-slate-400 leading-tight">
                                {{ feature.description }}
                            </p>
                        </div>
                    </div>
                </div>            </div>

            <!-- Right Column: Plan Details -->
            <div class="lg:col-span-4">
                <div class="sticky top-24 space-y-4">
                    <!-- Pricing Card -->
                    <div class="bg-indigo-600 rounded-3xl p-6 text-white relative overflow-hidden shadow-xl shadow-indigo-100 dark:shadow-none">
                        <div class="absolute -right-6 -top-6 w-32 h-32 bg-white/10 rounded-full blur-2xl"></div>
                        
                        <div class="relative z-10 text-center py-4">
                            <div class="inline-flex items-center px-2 py-1 bg-white/20 rounded-lg text-[9px] font-black uppercase tracking-widest mb-4">
                                Pack Unique SaaS
                            </div>
                            <h3 class="text-xl font-black mb-1">{{ plan.name }}</h3>
                            <div class="flex items-baseline justify-center gap-1.5 mt-2">
                                <span class="text-4xl font-black">{{ formatNumber(plan.price) }}</span>
                                <span class="text-xs font-bold opacity-75">{{ plan.currency }} / mois</span>
                            </div>
                        </div>

                        <div class="relative z-10 mt-6 pt-6 border-t border-white/20 space-y-3">
                            <div v-if="client.plan_name === plan.name" class="flex flex-col gap-2">
                                <div :class="[
                                    'flex items-center justify-center gap-2 py-3 rounded-xl font-black text-xs uppercase tracking-wider transition-colors',
                                    enabledCount === features.length ? 'bg-white text-indigo-600' : (enabledCount > 0 ? 'bg-amber-400 text-amber-950' : 'bg-slate-200 text-slate-600')
                                ]">
                                    <em :class="[
                                        'ni',
                                        enabledCount === features.length ? 'ni-check-circle' : (enabledCount > 0 ? 'ni-alert-circle' : 'ni-pause-circle')
                                    ]"></em>
                                    {{ enabledCount === features.length ? "Activé & Opérationnel" : (enabledCount > 0 ? "Partiellement Actif" : "En attente d'activation") }}
                                </div>
                                <div class="text-[10px] text-center text-indigo-100 font-medium">
                                    Prochain renouvellement : {{ formatDate(client.next_renewal_at) }}
                                </div>
                            </div>
                            <div v-else class="text-center">
                                <p class="text-xs text-indigo-100 mb-4">Le pack premium active tous les modules listés et les quotas SMS/Emails.</p>
                                <button class="w-full py-3 bg-white/20 hover:bg-white/30 text-white rounded-xl font-bold text-xs uppercase tracking-wider transition-colors border border-white/20">
                                    Contacter le support
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Help Card -->
                    <div class="bg-white dark:bg-slate-800 rounded-2xl p-5 border border-slate-100 dark:border-slate-700 shadow-sm">
                        <h4 class="text-xs font-black text-slate-900 dark:text-white uppercase tracking-widest mb-3 flex items-center gap-2">
                            <em class="ni ni-info-fill text-indigo-500 text-sm"></em>
                            Information
                        </h4>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400 leading-relaxed italic">
                            Les quotas sont réinitialisés automatiquement chaque mois à la date anniversaire de votre inscription. En cas de dépassement, contactez votre administrateur pour une extension de quota.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</AppLayout>
</template>

<script setup>
import { computed } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { usePage } from '@inertiajs/vue3';
import { format } from 'date-fns';
import { fr } from 'date-fns/locale';

const props = defineProps({
    client: Object,
    features: Array,
    plan: Object,
});

const page = usePage();

const enabledCount = computed(() => {
    return props.features.filter(f => page.props.enabledFeatures[f.key]).length;
});

const smsPercentage = computed(() => {
    if (!props.client.sms_quota) return 0;
    return Math.min(100, (props.client.sms_used_this_month / props.client.sms_quota) * 100);
});

const emailPercentage = computed(() => {
    if (!props.client.email_quota) return 0;
    return Math.min(100, (props.client.email_used_this_month / props.client.email_quota) * 100);
});

const formatNumber = (num) => {
    return new Intl.NumberFormat('fr-FR').format(num);
};

const formatDate = (dateString) => {
    if (!dateString) return 'Non défini';
    try {
        return format(new Date(dateString), 'dd MMMM yyyy', { locale: fr });
    } catch (e) {
        return dateString;
    }
};
</script>
