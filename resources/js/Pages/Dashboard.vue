<template>
<AppLayout>
    <div class="px-4 sm:px-6 lg:px-8 py-8 w-full max-w-9xl mx-auto">
        
        <!-- Header -->
        <div class="sm:flex sm:justify-between sm:items-center mb-8">
            <div class="mb-4 sm:mb-0">
                <h1 class="text-2xl md:text-3xl text-slate-800 dark:text-slate-100 font-bold mb-1">
                    {{ dashboardTitle }}
                </h1>
                <p class="text-sm text-slate-500 dark:text-slate-400">
                    Bienvenue, {{ $page.props.auth.user.name }} ({{ $page.props.auth.user.type_name }})
                </p>
            </div>
            <div class="grid grid-flow-col sm:auto-cols-max justify-start sm:justify-end gap-2">
                <div class="px-4 py-2 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-sm shadow-sm text-sm font-medium text-slate-600 dark:text-slate-300">
                    {{ currentDate }}
                </div>
            </div>
        </div>

        <!-- ========================================================= -->
        <!--  VUE STRATÉGIQUE (SUPERADMIN / ADMIN)                      -->
        <!-- ========================================================= -->
        <template v-if="isAdmin">
            <div class="grid grid-cols-12 gap-6 mb-6">
                <!-- Chiffre d'Affaire Mois -->
                <div class="flex flex-col col-span-full sm:col-span-6 xl:col-span-4 bg-white dark:bg-slate-800 shadow-lg rounded-xl border border-slate-200 dark:border-slate-700">
                    <div class="px-5 pt-5 pb-4">
                        <header class="flex justify-between items-start mb-2">
                            <h2 class="text-lg font-semibold text-slate-800 dark:text-slate-100 mb-2">CA du mois</h2>
                            <div v-if="strategicData?.monthlyComparison" class="flex items-center" :class="strategicData.monthlyComparison.isPositive ? 'text-emerald-500' : 'text-rose-500'">
                                <span class="text-sm font-medium">{{ Math.abs(strategicData.monthlyComparison.growthPercentage) }}%</span>
                            </div>
                        </header>
                        <div class="text-3xl font-bold text-slate-800 dark:text-slate-100 mr-2">{{ formatN(strategicData?.kpis?.revenueThisMonth) }} <span class="text-sm font-medium text-slate-500">Ar</span></div>
                    </div>
                </div>

                <!-- Recettes du Jour -->
                <div class="flex flex-col col-span-full sm:col-span-6 xl:col-span-4 bg-white dark:bg-slate-800 shadow-lg rounded-xl border border-slate-200 dark:border-slate-700">
                    <div class="px-5 pt-5 pb-4">
                        <header class="flex justify-between items-start mb-2">
                            <h2 class="text-lg font-semibold text-slate-800 dark:text-slate-100 mb-2">Recettes du jour</h2>
                        </header>
                        <div class="text-3xl font-bold text-slate-800 dark:text-slate-100 mr-2">{{ formatN(strategicData?.kpis?.revenueToday) }} <span class="text-sm font-medium text-slate-500">Ar</span></div>
                    </div>
                </div>

                <!-- Impayés globaux -->
                <div class="flex flex-col col-span-full sm:col-span-6 xl:col-span-4 bg-white dark:bg-slate-800 shadow-lg rounded-xl border border-slate-200 dark:border-slate-700">
                    <div class="px-5 pt-5 pb-4">
                        <header class="flex justify-between items-start mb-2">
                            <h2 class="text-lg font-semibold text-slate-800 dark:text-slate-100 mb-2">Impayés globaux</h2>
                        </header>
                        <div class="text-3xl font-bold text-rose-500 mr-2">{{ formatN(strategicData?.kpis?.unpaidAmount) }} <span class="text-sm font-medium text-slate-500">Ar</span></div>
                    </div>
                </div>
            </div>

            <!-- Graphiques -->
            <div class="grid grid-cols-12 gap-6 mb-6">
                <div class="col-span-full xl:col-span-8 bg-white dark:bg-slate-800 shadow-lg rounded-xl border border-slate-200 dark:border-slate-700">
                    <header class="px-5 py-4 border-b border-slate-100 dark:border-slate-700">
                        <h2 class="font-semibold text-slate-800 dark:text-slate-100">Evolution du Revenu (30 jours)</h2>
                    </header>
                    <div class="p-5 h-72">
                        <canvas id="revenueChart"></canvas>
                    </div>
                </div>
                <div class="col-span-full xl:col-span-4 bg-white dark:bg-slate-800 shadow-lg rounded-xl border border-slate-200 dark:border-slate-700">
                    <header class="px-5 py-4 border-b border-slate-100 dark:border-slate-700">
                        <h2 class="font-semibold text-slate-800 dark:text-slate-100">Encaissements du mois</h2>
                    </header>
                    <div class="p-5 h-64 flex justify-center">
                        <canvas id="paymentRatioChart"></canvas>
                    </div>
                </div>
            </div>
        </template>

        <!-- ========================================================= -->
        <!--  VUE MÉTIER : SECRÉTAIRE                                   -->
        <!-- ========================================================= -->
        <template v-else-if="userType === 'secretaire'">
            <div class="grid grid-cols-12 gap-6 mb-8">
                <div class="col-span-full sm:col-span-6 xl:col-span-3 bg-white dark:bg-slate-800 p-5 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700">
                    <div class="flex items-center">
                        <div class="w-10 h-10 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center mr-3">
                            <em class="ni ni-users text-blue-600"></em>
                        </div>
                        <div>
                            <div class="text-xs font-semibold text-slate-400 uppercase">Nouveaux Patients (J)</div>
                            <div class="text-2xl font-bold text-slate-800 dark:text-slate-100">{{ roleData.patients_jour || 0 }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-span-full sm:col-span-6 xl:col-span-3 bg-white dark:bg-slate-800 p-5 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700">
                    <div class="flex items-center">
                        <div class="w-10 h-10 rounded-full bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center mr-3">
                            <em class="ni ni-coins text-emerald-600"></em>
                        </div>
                        <div>
                            <div class="text-xs font-semibold text-slate-400 uppercase">Encaissé ce jour</div>
                            <div class="text-2xl font-bold text-emerald-600">{{ formatN(stats.finances.recettes_jour) }} Ar</div>
                        </div>
                    </div>
                </div>
                <div class="col-span-full sm:col-span-6 xl:col-span-3 bg-white dark:bg-slate-800 p-5 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700">
                    <div class="flex items-center">
                        <div class="w-10 h-10 rounded-full bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center mr-3">
                            <em class="ni ni-wallet-out text-amber-600"></em>
                        </div>
                        <div>
                            <div class="text-xs font-semibold text-slate-400 uppercase">À encaisser (Total)</div>
                            <div class="text-2xl font-bold text-amber-600">{{ roleData.prescriptions_a_encaisser || 0 }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Graphiques Métier Secrétaire -->
            <div class="grid grid-cols-12 gap-6 mb-8">
                <div class="col-span-full xl:col-span-8 bg-white dark:bg-slate-800 shadow-lg rounded-xl border border-slate-200 dark:border-slate-700">
                    <header class="px-5 py-4 border-b border-slate-100 dark:border-slate-700 font-semibold">Evolution Recettes (7j)</header>
                    <div class="p-5 h-64"><canvas id="secrRevenueChart"></canvas></div>
                </div>
                <div class="col-span-full xl:col-span-4 bg-white dark:bg-slate-800 shadow-lg rounded-xl border border-slate-200 dark:border-slate-700">
                    <header class="px-5 py-4 border-b border-slate-100 dark:border-slate-700 font-semibold">Modes de paiement</header>
                    <div class="p-5 h-64 flex justify-center"><canvas id="secrPaymentsChart"></canvas></div>
                </div>
            </div>

            <!-- Dernières prescriptions -->
            <div class="bg-white dark:bg-slate-800 shadow-sm rounded-xl border border-slate-200 dark:border-slate-700 mb-6">
                <header class="px-5 py-4 border-b border-slate-100 dark:border-slate-700 flex justify-between items-center">
                    <h2 class="font-semibold text-slate-800 dark:text-slate-100">Dernières prescriptions enregistrées</h2>
                    <Link :href="route('secretaire.prescription.index')" class="text-sm font-medium text-indigo-500 hover:text-indigo-600">Voir tout -></Link>
                </header>
                <div class="p-3 overflow-x-auto">
                    <table class="table-auto w-full dark:text-slate-300">
                        <thead class="text-xs uppercase text-slate-400 bg-slate-50 dark:bg-slate-700/20">
                            <tr>
                                <th class="px-2 py-3 text-left">Référence</th>
                                <th class="px-2 py-3 text-left">Patient</th>
                                <th class="px-2 py-3 text-left">Statut</th>
                                <th class="px-2 py-3 text-right">Montant</th>
                            </tr>
                        </thead>
                        <tbody class="text-sm divide-y divide-slate-100 dark:divide-slate-700">
                            <tr v-for="p in roleData.dernieres_prescriptions" :key="p.id">
                                <td class="px-2 py-3 font-medium text-slate-800 dark:text-slate-100">{{ p.reference }}</td>
                                <td class="px-2 py-3">{{ p.patient?.nom }} {{ p.patient?.prenom }}</td>
                                <td class="px-2 py-3">
                                    <span :class="p.is_paid ? 'text-emerald-500' : 'text-amber-500'">{{ p.is_paid ? 'Payé' : 'Impayé' }}</span>
                                </td>
                                <td class="px-2 py-3 text-right font-bold">{{ formatN(p.montant_total) }} Ar</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </template>

        <!-- ========================================================= -->
        <!--  VUE MÉTIER : TECHNICIEN                                   -->
        <!-- ========================================================= -->
        <template v-else-if="userType === 'technicien'">
            <div class="grid grid-cols-12 gap-6 mb-8">
                <div class="col-span-full sm:col-span-6 xl:col-span-4 bg-white dark:bg-slate-800 p-5 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700">
                    <div class="flex items-center">
                        <div class="w-10 h-10 rounded-full bg-rose-100 dark:bg-rose-900/30 flex items-center justify-center mr-3">
                            <em class="ni ni-alert text-rose-600"></em>
                        </div>
                        <div>
                            <div class="text-xs font-semibold text-slate-400 uppercase">Urgences à traiter</div>
                            <div class="text-2xl font-bold text-rose-600">{{ roleData.analyses_urgentes || 0 }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-span-full sm:col-span-6 xl:col-span-4 bg-white dark:bg-slate-800 p-5 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700">
                    <div class="flex items-center">
                        <div class="w-10 h-10 rounded-full bg-indigo-100 dark:bg-indigo-900/30 flex items-center justify-center mr-3">
                            <em class="ni ni-flask text-indigo-600"></em>
                        </div>
                        <div>
                            <div class="text-xs font-semibold text-slate-400 uppercase">Analyses en attente</div>
                            <div class="text-2xl font-bold text-indigo-600">{{ roleData.analyses_a_faire || 0 }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-12 gap-6 mb-8">
                <div class="col-span-full xl:col-span-8 bg-white dark:bg-slate-800 shadow-lg rounded-xl border border-slate-200 dark:border-slate-700">
                    <header class="px-5 py-4 border-b border-slate-100 dark:border-slate-700 font-semibold">Analyses traitées (7j)</header>
                    <div class="p-5 h-64"><canvas id="techCompletionChart"></canvas></div>
                </div>
                <div class="col-span-full xl:col-span-4 bg-white dark:bg-slate-800 shadow-lg rounded-xl border border-slate-200 dark:border-slate-700">
                    <header class="px-5 py-4 border-b border-slate-100 dark:border-slate-700 font-semibold">Charge par type d'examen</header>
                    <div class="p-5 overflow-y-auto max-h-64 space-y-3">
                        <div v-for="t in roleData.examens_par_type" :key="t.nom" class="flex justify-between items-center p-2 bg-slate-50 dark:bg-slate-700/30 rounded">
                            <span class="text-sm font-medium">{{ t.nom }}</span>
                            <span class="px-2 py-1 bg-indigo-500 text-white text-xs rounded-full">{{ t.total }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </template>

        <!-- ========================================================= -->
        <!--  VUE MÉTIER : BIOLOGISTE                                   -->
        <!-- ========================================================= -->
        <template v-else-if="userType === 'biologiste'">
            <div class="grid grid-cols-12 gap-6 mb-8">
                <div class="col-span-full sm:col-span-6 xl:col-span-4 bg-white dark:bg-slate-800 p-5 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700">
                    <div class="flex items-center text-amber-600">
                        <em class="ni ni-check-circle-fill text-2xl mr-3"></em>
                        <div>
                            <div class="text-xs font-semibold uppercase">Analyses à valider</div>
                            <div class="text-3xl font-bold">{{ roleData.a_valider || 0 }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-span-full sm:col-span-6 xl:col-span-4 bg-white dark:bg-slate-800 p-5 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700">
                    <div class="flex items-center text-rose-600">
                        <em class="ni ni-activity-round text-2xl mr-3"></em>
                        <div>
                            <div class="text-xs font-semibold uppercase">Pathologiques</div>
                            <div class="text-3xl font-bold">{{ stats.analyses.pathologiques || 0 }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-12 gap-6 mb-8">
                <div class="col-span-full sm:col-span-6 bg-white dark:bg-slate-800 shadow-lg rounded-xl border border-slate-200 dark:border-slate-700">
                    <header class="px-5 py-4 border-b border-slate-100 dark:border-slate-700 font-semibold text-rose-600">Alertes Pathologiques (Priorité)</header>
                    <div class="p-4 overflow-y-auto max-h-72">
                        <ul class="space-y-3">
                            <li v-for="res in roleData.pathologiques_recente" :key="res.id" class="p-3 bg-rose-50 dark:bg-rose-900/10 border border-rose-100 dark:border-rose-900/30 rounded-lg flex justify-between items-center">
                                <div>
                                    <div class="font-bold text-slate-800 dark:text-slate-100">{{ res.analyse?.designation }}</div>
                                    <div class="text-xs text-slate-500">Patient: {{ res.prescription?.patient?.nom }}</div>
                                </div>
                                <Link :href="route('biologiste.prescription.show', res.prescription_id)" class="text-xs bg-rose-600 text-white px-3 py-1 rounded">Valider</Link>
                            </li>
                        </ul>
                    </div>
                </div>
                <div class="col-span-full sm:col-span-6 bg-white dark:bg-slate-800 shadow-lg rounded-xl border border-slate-200 dark:border-slate-700">
                    <header class="px-5 py-4 border-b border-slate-100 dark:border-slate-700 font-semibold">Taux de Pathologie Global</header>
                    <div class="p-5 h-64 flex justify-center"><canvas id="bioPathoChart"></canvas></div>
                </div>
            </div>
        </template>

        <!-- ========================================================= -->
        <!--  JOURNAL D'ACTIVITÉS ENRICHI (Filtres & Recherche)         -->
        <!-- ========================================================= -->
        <div class="mt-12 bg-white dark:bg-slate-800 shadow-lg rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden">
            <header class="px-6 py-5 border-b border-slate-100 dark:border-slate-700 flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div class="flex items-center">
                    <div class="w-8 h-8 rounded-lg bg-indigo-500 flex items-center justify-center mr-3 shadow-indigo-200 shadow-lg">
                        <em class="ni ni-history text-white"></em>
                    </div>
                    <h2 class="text-lg font-bold text-slate-800 dark:text-slate-100">Journal d'activités</h2>
                </div>
                
                <!-- Filtres -->
                <div class="flex flex-wrap items-center gap-3">
                    <div class="relative">
                        <input v-model="filterForm.search" type="text" placeholder="Rechercher..." 
                               class="text-xs border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900 rounded-lg pl-8 focus:ring-indigo-500 w-48">
                        <em class="ni ni-search absolute left-2.5 top-2.5 text-slate-400"></em>
                    </div>
                    <div class="flex items-center gap-2">
                        <input v-model="filterForm.date_from" type="date" class="text-xs border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900 rounded-lg focus:ring-indigo-500">
                        <span class="text-slate-400">à</span>
                        <input v-model="filterForm.date_to" type="date" class="text-xs border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900 rounded-lg focus:ring-indigo-500">
                    </div>
                    <button @click="applyFilters" class="px-3 py-2 bg-indigo-600 text-white text-xs font-bold rounded-lg hover:bg-indigo-700 transition-all">Filtrer</button>
                    <button @click="resetFilters" class="px-3 py-2 bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 text-xs font-bold rounded-lg hover:bg-slate-200">Réinitialiser</button>
                </div>
            </header>

            <div class="p-0">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-700/50">
                            <tr v-for="(act, idx) in stats.activites" :key="idx" class="hover:bg-slate-50/50 dark:hover:bg-slate-700/20 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap w-10">
                                    <div class="w-9 h-9 rounded-xl flex items-center justify-center shadow-sm"
                                         :class="{
                                             'bg-indigo-100 text-indigo-600 dark:bg-indigo-500/20': act.color === 'indigo' || act.color === 'blue',
                                             'bg-emerald-100 text-emerald-600 dark:bg-emerald-500/20': act.color === 'green',
                                             'bg-amber-100 text-amber-600 dark:bg-amber-500/20': act.color === 'yellow',
                                             'bg-rose-100 text-rose-600 dark:bg-rose-500/20': act.color === 'red'
                                         }">
                                        <em v-if="act.type==='patient'" class="ni ni-user-add"></em>
                                        <em v-else-if="act.type==='paiement'" class="ni ni-wallet-in"></em>
                                        <em v-else-if="act.type==='validation'" class="ni ni-check-circle"></em>
                                        <em v-else class="ni ni-activity"></em>
                                    </div>
                                </td>
                                <td class="px-2 py-4">
                                    <div class="text-sm font-semibold text-slate-800 dark:text-slate-200">{{ act.message }}</div>
                                    <div v-if="act.author" class="text-xs text-slate-400 mt-0.5">Par : <span class="text-indigo-500 font-medium">{{ act.author }}</span></div>
                                </td>
                                <td class="px-6 py-4 text-right whitespace-nowrap">
                                    <div class="text-xs font-bold text-slate-500 dark:text-slate-400">{{ act.time }}</div>
                                    <div class="text-[10px] text-slate-300 dark:text-slate-600 uppercase tracking-tighter">{{ act.type }}</div>
                                </td>
                            </tr>
                            <tr v-if="!stats.activites || stats.activites.length === 0">
                                <td colspan="3" class="px-6 py-12 text-center text-slate-400">
                                    <div class="flex flex-col items-center">
                                        <em class="ni ni-inbox text-4xl mb-2 opacity-20"></em>
                                        <p>Aucune activité trouvée pour ces critères.</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</AppLayout>
</template>

<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { usePage, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Chart from 'chart.js/auto';

const props = defineProps({
    stats: Object,
    roleData: Object,
    strategicData: Object,
    filters: Object,
});

const page = usePage();
const userType = computed(() => page.props.auth.user.type);
const isAdmin = computed(() => ['superadmin', 'admin'].includes(userType.value));

const filterForm = ref({
    search: props.filters?.search || '',
    date_from: props.filters?.date_from || '',
    date_to: props.filters?.date_to || '',
});

const applyFilters = () => {
    router.get(route('dashboard'), filterForm.value, {
        preserveState: true,
        preserveScroll: true,
        only: ['stats'],
    });
};

const resetFilters = () => {
    filterForm.value = { search: '', date_from: '', date_to: '' };
    applyFilters();
};

const dashboardTitle = computed(() => {
    if (isAdmin.value) return 'Tableau de bord stratégique 📈';
    if (userType.value === 'secretaire') return 'Espace Secrétariat 📁';
    if (userType.value === 'technicien') return 'Espace Technique 🔬';
    if (userType.value === 'biologiste') return 'Espace Biologiste 🧬';
    return 'Tableau de bord';
});

const currentDate = computed(() => {
    const now = new Date();
    return now.toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' });
});

const formatN = (n) => Number(n || 0).toLocaleString('fr-FR');

const chartInstances = [];

const createChart = (id, type, data, options = {}) => {
    const ctx = document.getElementById(id);
    if (!ctx) return null;
    const chart = new Chart(ctx, { type, data, options: { maintainAspectRatio: false, ...options } });
    chartInstances.push(chart);
    return chart;
};

const renderCharts = () => {
    const darkMode = document.documentElement.classList.contains('dark');
    const colors = {
        indigo: '#6366f1', sky: '#0ea5e9', emerald: '#10b981', rose: '#f43f5e', amber: '#f59e0b',
        grid: darkMode ? '#334155' : '#e2e8f0', ticks: '#475569'
    };

    if (isAdmin.value && props.strategicData) {
        createChart('revenueChart', 'line', {
            labels: props.strategicData.revenueLast30Days.labels,
            datasets: [{ label: 'Revenu', data: props.strategicData.revenueLast30Days.series, borderColor: colors.indigo, backgroundColor: 'rgba(99, 102, 241, 0.1)', fill: true, tension: 0.3 }]
        }, { plugins: { legend: { display: false } } });

        createChart('paymentRatioChart', 'doughnut', {
            labels: props.strategicData.paymentRatio.labels,
            datasets: [{ data: props.strategicData.paymentRatio.series, backgroundColor: [colors.emerald, colors.rose] }]
        }, { cutout: '70%', plugins: { legend: { position: 'bottom' } } });
    }

    if (userType.value === 'secretaire' && props.roleData) {
        createChart('secrRevenueChart', 'line', {
            labels: props.roleData.revenue_trend.labels.slice(-7),
            datasets: [{ label: 'Encaissements', data: props.roleData.revenue_trend.series.slice(-7), borderColor: colors.emerald, backgroundColor: 'rgba(16, 185, 129, 0.1)', fill: true, tension: 0.3 }]
        });
        createChart('secrPaymentsChart', 'pie', {
            labels: props.roleData.payment_methods_chart.labels,
            datasets: [{ data: props.roleData.payment_methods_chart.series, backgroundColor: [colors.indigo, colors.sky, colors.amber, colors.emerald] }]
        });
    }

    if (userType.value === 'technicien' && props.roleData) {
        createChart('techCompletionChart', 'bar', {
            labels: props.roleData.completion_trend.labels,
            datasets: [{ label: 'Analyses traitées', data: props.roleData.completion_trend.series, backgroundColor: colors.sky, borderRadius: 4 }]
        });
    }

    if (userType.value === 'biologiste' && props.roleData) {
        createChart('bioPathoChart', 'doughnut', {
            labels: props.roleData.pathology_ratio.labels,
            datasets: [{ data: props.roleData.pathology_ratio.series, backgroundColor: [colors.indigo, colors.rose] }]
        });
    }
};

onMounted(() => { setTimeout(renderCharts, 100); });
onUnmounted(() => { chartInstances.forEach(c => c.destroy()); });
</script>
