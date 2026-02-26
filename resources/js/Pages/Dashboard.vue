<template>
<AppLayout>
    <div class="px-4 sm:px-6 lg:px-8 py-8 w-full max-w-9xl mx-auto">
        
        <!-- Header -->
        <div class="sm:flex sm:justify-between sm:items-center mb-8">
            <div class="mb-4 sm:mb-0">
                <h1 class="text-2xl md:text-3xl text-slate-800 dark:text-slate-100 font-bold mb-1">Tableau de bord stratégique 📈</h1>
                <p class="text-sm text-slate-500 dark:text-slate-400">
                    Bienvenue, {{ $page.props.auth.user.name }}
                </p>
            </div>
            <div class="grid grid-flow-col sm:auto-cols-max justify-start sm:justify-end gap-2">
                <div class="px-4 py-2 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-sm shadow-sm text-sm font-medium text-slate-600 dark:text-slate-300">
                    {{ currentDate }}
                </div>
            </div>
        </div>

        <!-- ========================================================= -->
        <!--  VUE SUPERADMIN / ADMIN (Stratégique SaaS)                 -->
        <!-- ========================================================= -->
        <template v-if="['superadmin', 'admin'].includes($page.props.auth.user.type)">
            
            <!-- KPIs Ligne 1 -->
            <div class="grid grid-cols-12 gap-6 mb-6">
                <!-- Chiffre d'Affaire Mois -->
                <div class="flex flex-col col-span-full sm:col-span-6 xl:col-span-4 bg-white dark:bg-slate-800 shadow-lg rounded-xl border border-slate-200 dark:border-slate-700">
                    <div class="px-5 pt-5 pb-4">
                        <header class="flex justify-between items-start mb-2">
                            <h2 class="text-lg font-semibold text-slate-800 dark:text-slate-100 mb-2">CA du mois</h2>
                            <div class="flex items-center" :class="strategicData.monthlyComparison.isPositive ? 'text-emerald-500' : 'text-rose-500'">
                                <svg v-if="strategicData.monthlyComparison.isPositive" class="w-4 h-4 fill-current mr-1" viewBox="0 0 16 16"><path d="M8 0L9.4 1.4 3.8 7H16v2H3.8l5.6 5.6L8 16 0 8z" transform="scale(1 -1) translate(-16 -16)"/></svg>
                                <svg v-else class="w-4 h-4 fill-current mr-1" viewBox="0 0 16 16"><path d="M8 16l1.4-1.4-5.6-5.6H16V7H3.8l5.6-5.6L8 0 0 8z" /></svg>
                                <span class="text-sm font-medium">{{ Math.abs(strategicData.monthlyComparison.growthPercentage) }}%</span>
                            </div>
                        </header>
                        <div class="text-3xl font-bold text-slate-800 dark:text-slate-100 mr-2">{{ formatN(strategicData.kpis.revenueThisMonth) }} <span class="text-sm font-medium text-slate-500">Ar</span></div>
                        <div class="text-sm text-slate-500 dark:text-slate-400 mt-1">Comparé au mois précédent</div>
                    </div>
                </div>

                <!-- Recettes du Jour -->
                <div class="flex flex-col col-span-full sm:col-span-6 xl:col-span-4 bg-white dark:bg-slate-800 shadow-lg rounded-xl border border-slate-200 dark:border-slate-700">
                    <div class="px-5 pt-5 pb-4">
                        <header class="flex justify-between items-start mb-2">
                            <h2 class="text-lg font-semibold text-slate-800 dark:text-slate-100 mb-2">Recettes du jour</h2>
                        </header>
                        <div class="text-3xl font-bold text-slate-800 dark:text-slate-100 mr-2">{{ formatN(strategicData.kpis.revenueToday) }} <span class="text-sm font-medium text-slate-500">Ar</span></div>
                        <div class="text-sm text-slate-500 dark:text-slate-400 mt-1">Total encaissé aujourd'hui</div>
                    </div>
                </div>

                <!-- Impayés globaux -->
                <div class="flex flex-col col-span-full sm:col-span-6 xl:col-span-4 bg-white dark:bg-slate-800 shadow-lg rounded-xl border border-slate-200 dark:border-slate-700">
                    <div class="px-5 pt-5 pb-4">
                        <header class="flex justify-between items-start mb-2">
                            <h2 class="text-lg font-semibold text-slate-800 dark:text-slate-100 mb-2">Impayés & Restes à payer</h2>
                        </header>
                        <div class="text-3xl font-bold text-rose-500 mr-2">{{ formatN(strategicData.kpis.unpaidAmount) }} <span class="text-sm font-medium text-slate-500">Ar</span></div>
                        <div class="text-sm text-slate-500 dark:text-slate-400 mt-1">Taux d'encaissement: <span class="font-bold text-slate-700 dark:text-slate-300">{{ strategicData.kpis.paymentRate }}%</span></div>
                    </div>
                </div>
            </div>

            <!-- Graphiques Ligne 2 -->
            <div class="grid grid-cols-12 gap-6 mb-6">
                <!-- Chiffre d'affaire (Line Chart) -->
                <div class="flex flex-col col-span-full xl:col-span-8 bg-white dark:bg-slate-800 shadow-lg rounded-xl border border-slate-200 dark:border-slate-700">
                    <header class="px-5 py-4 border-b border-slate-100 dark:border-slate-700">
                        <h2 class="font-semibold text-slate-800 dark:text-slate-100">Evolution du Revenu (30 derniers jours)</h2>
                    </header>
                    <div class="p-5 flex-grow">
                        <div class="h-72">
                            <canvas id="revenueChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Ratio de Paiement (Doughnut) -->
                <div class="flex flex-col col-span-full xl:col-span-4 bg-white dark:bg-slate-800 shadow-lg rounded-xl border border-slate-200 dark:border-slate-700">
                    <header class="px-5 py-4 border-b border-slate-100 dark:border-slate-700">
                        <h2 class="font-semibold text-slate-800 dark:text-slate-100">Encaissement du mois</h2>
                    </header>
                    <div class="p-5 flex-grow flex items-center justify-center">
                        <div class="h-64 w-full">
                            <canvas id="paymentRatioChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Graphiques Ligne 3 -->
            <div class="grid grid-cols-12 gap-6">
                <!-- Total Prescriptions par jour (Bar Chart) -->
                <div class="flex flex-col col-span-full xl:col-span-6 bg-white dark:bg-slate-800 shadow-lg rounded-xl border border-slate-200 dark:border-slate-700">
                    <header class="px-5 py-4 border-b border-slate-100 dark:border-slate-700">
                        <h2 class="font-semibold text-slate-800 dark:text-slate-100">Volume Prescriptions (30 jours)</h2>
                    </header>
                    <div class="p-5 flex-grow">
                        <div class="h-64">
                            <canvas id="prescriptionsChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Top 5 analyses (Pie Chart) -->
                <div class="flex flex-col col-span-full xl:col-span-6 bg-white dark:bg-slate-800 shadow-lg rounded-xl border border-slate-200 dark:border-slate-700">
                    <header class="px-5 py-4 border-b border-slate-100 dark:border-slate-700">
                        <h2 class="font-semibold text-slate-800 dark:text-slate-100">Analyses les plus demandées (Mois)</h2>
                    </header>
                    <div class="p-5 flex-grow flex items-center justify-center">
                        <div class="h-64 w-full">
                            <canvas id="topAnalysesChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </template>

        <!-- ========================================================= -->
        <!--  AUTRES VUES DE BASE (Secrétaire / Technicien / Bio)       -->
        <!-- ========================================================= -->
        <template v-else>
            <!-- Ligne 1: Résumé Synthétique Simple -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <!-- Box 1 -->
                <div v-if="['secretaire'].includes($page.props.auth.user.type)" class="bg-white dark:bg-slate-800 p-6 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700">
                    <div class="text-sm font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-1">Prescriptions du jour</div>
                    <div class="text-3xl font-bold text-slate-800 dark:text-slate-100">{{ stats.finances.nb_paiements || 0 }}</div>
                </div>

                <div v-if="['secretaire'].includes($page.props.auth.user.type)" class="bg-white dark:bg-slate-800 p-6 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700">
                    <div class="text-sm font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-1">Recettes du jour</div>
                    <div class="text-3xl font-bold text-emerald-600 dark:text-emerald-400">{{ formatN(stats.finances.recettes_jour) }} Ar</div>
                </div>

                <div v-if="['technicien', 'biologiste'].includes($page.props.auth.user.type)" class="bg-white dark:bg-slate-800 p-6 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700">
                    <div class="text-sm font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-1">Analyses Terminées</div>
                    <div class="text-3xl font-bold text-indigo-600 dark:text-indigo-400">{{ stats.analyses.terminees || 0 }}</div>
                </div>

                <div v-if="['technicien', 'biologiste'].includes($page.props.auth.user.type)" class="bg-white dark:bg-slate-800 p-6 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700">
                    <div class="text-sm font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-1">En attente / En Cours</div>
                    <div class="text-3xl font-bold text-amber-500">{{ (stats.analyses.en_attente || 0) + (stats.analyses.en_cours || 0) }}</div>
                </div>

                <div v-if="['biologiste'].includes($page.props.auth.user.type)" class="bg-white dark:bg-slate-800 p-6 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700">
                    <div class="text-sm font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-1">Validées</div>
                    <div class="text-3xl font-bold text-emerald-600 dark:text-emerald-400">{{ stats.analyses.valides || 0 }}</div>
                </div>
                
                <div v-if="['technicien', 'biologiste'].includes($page.props.auth.user.type)" class="bg-white dark:bg-slate-800 p-6 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700">
                    <div class="text-sm font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-1">Pathologiques</div>
                    <div class="text-3xl font-bold text-rose-500">{{ stats.analyses.pathologiques || 0 }}</div>
                </div>
            </div>

            <!-- Activités récentes générales (Style Timeline) -->
            <div class="bg-white dark:bg-slate-800 shadow-sm rounded-xl border border-slate-200 dark:border-slate-700">
                <header class="px-5 py-4 border-b border-slate-100 dark:border-slate-700">
                    <h2 class="font-semibold text-slate-800 dark:text-slate-100">Activités récentes</h2>
                </header>
                <div class="p-3">
                    <ul class="my-1">
                        <li v-for="(act, idx) in stats.activites" :key="idx" class="flex px-2 py-3 border-b border-slate-100 dark:border-slate-700 last:border-0 last:pb-1">
                            <div class="w-9 h-9 rounded-full shrink-0 flex items-center justify-center mr-3"
                                 :class="{
                                     'bg-indigo-100 text-indigo-500 dark:bg-indigo-500/20': act.color === 'indigo',
                                     'bg-emerald-100 text-emerald-500 dark:bg-emerald-500/20': act.color === 'green',
                                     'bg-amber-100 text-amber-500 dark:bg-amber-500/20': act.color === 'yellow',
                                     'bg-blue-100 text-blue-500 dark:bg-blue-500/20': act.color === 'blue'
                                 }">
                                <svg v-if="act.type==='patient'" class="w-4 h-4 fill-current" viewBox="0 0 16 16"><path d="M8 0C3.6 0 0 3.6 0 8s3.6 8 8 8 8-3.6 8-8-3.6-8-8-8zm0 14c-3.3 0-6-2.7-6-6s2.7-6 6-6 6 2.7 6 6-2.7 6-6 6zm0-11c-2.8 0-5 2.2-5 5s2.2 5 5 5 5-2.2 5-5-2.2-5-5-5z"/></svg>
                                <svg v-else-if="act.type==='paiement'" class="w-4 h-4 fill-current" viewBox="0 0 16 16"><path d="M15 4c-.6 0-1 .4-1 1v6c0 .6.4 1 1 1s1-.4 1-1V5c0-.6-.4-1-1-1zm-3 0H4c-.6 0-1 .4-1 1v6c0 .6.4 1 1 1h8c.6 0 1-.4 1-1V5c0-.6-.4-1-1-1zM2 5H1c-.6 0-1 .4-1 1v4c0 .6.4 1 1 1h1c.6 0 1-.4 1-1V6c0-.6-.4-1-1-1z"/></svg>
                                <svg v-else class="w-4 h-4 fill-current" viewBox="0 0 16 16"><path d="M8 0C3.6 0 0 3.6 0 8s3.6 8 8 8 8-3.6 8-8-3.6-8-8-8zm0 14c-3.3 0-6-2.7-6-6s2.7-6 6-6 6 2.7 6 6-2.7 6-6 6z"/></svg>
                            </div>
                            <div class="grow flex items-center text-sm">
                                <span class="text-slate-800 dark:text-slate-200 font-medium">{{ act.message }}</span>
                            </div>
                            <div class="shrink-0 text-sm text-slate-500 flex items-center ml-2">{{ act.time }}</div>
                        </li>
                        <li v-if="!stats.activites || stats.activites.length === 0" class="p-6 text-center text-slate-500">
                            Aucune activité récente.
                        </li>
                    </ul>
                </div>
            </div>
        </template>
    </div>
</AppLayout>
</template>

<script setup>
import { computed, onMounted, onUnmounted } from 'vue';
import { usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Chart from 'chart.js/auto';

const props = defineProps({
    stats: Object,
    strategicData: Object,
});

const page = usePage();
const userType = computed(() => page.props.auth.user.type);

const currentDate = computed(() => {
    const now = new Date();
    const day = String(now.getDate()).padStart(2, '0');
    const month = String(now.getMonth() + 1).padStart(2, '0');
    const year = now.getFullYear();
    const hours = String(now.getHours()).padStart(2, '0');
    const minutes = String(now.getMinutes()).padStart(2, '0');
    return `${day}/${month}/${year} à ${hours}:${minutes}`;
});

const formatN = (n) => Number(n || 0).toLocaleString('fr-FR');

let revenueChartInstance = null;
let prescriptionsChartInstance = null;
let topAnalysesChartInstance = null;
let paymentRatioChartInstance = null;

const renderCharts = () => {
    if (!['superadmin', 'admin'].includes(userType.value) || !props.strategicData) {
        return;
    }

    const tailwindColors = {
        indigo500: '#6366f1',
        sky500: '#0ea5e9',
        emerald500: '#10b981',
        rose500: '#f43f5e',
        amber500: '#f59e0b',
        slate200: '#e2e8f0',
        slate700: '#334155',
        slate600: '#475569',
        transparent: 'transparent'
    };

    const darkMode = document.documentElement.classList.contains('dark');
    const gridColor = darkMode ? tailwindColors.slate700 : tailwindColors.slate200;
    const ticksColor = darkMode ? tailwindColors.slate600 : tailwindColors.slate600;

    // 1. Revenue Line Chart
    const revCtx = document.getElementById('revenueChart');
    if (revCtx) {
        if(revenueChartInstance) revenueChartInstance.destroy();
        revenueChartInstance = new Chart(revCtx, {
            type: 'line',
            data: {
                labels: props.strategicData.revenueLast30Days.labels,
                datasets: [{
                    label: 'Revenu (Ar)',
                    data: props.strategicData.revenueLast30Days.series,
                    borderColor: tailwindColors.indigo500,
                    backgroundColor: 'rgba(99, 102, 241, 0.1)',
                    borderWidth: 2,
                    tension: 0.3,
                    fill: true,
                    pointRadius: 0,
                    pointHoverRadius: 4,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, grid: { color: gridColor }, ticks: { color: ticksColor } },
                    x: { grid: { display: false }, ticks: { color: ticksColor } }
                }
            }
        });
    }

    // 2. Prescriptions Bar Chart
    const presCtx = document.getElementById('prescriptionsChart');
    if (presCtx) {
        if(prescriptionsChartInstance) prescriptionsChartInstance.destroy();
        prescriptionsChartInstance = new Chart(presCtx, {
            type: 'bar',
            data: {
                labels: props.strategicData.prescriptionsLast30Days.labels,
                datasets: [{
                    label: 'Prescriptions',
                    data: props.strategicData.prescriptionsLast30Days.series,
                    backgroundColor: tailwindColors.sky500,
                    borderRadius: 4,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, grid: { color: gridColor }, ticks: { color: ticksColor } },
                    x: { grid: { display: false }, ticks: { color: ticksColor } }
                }
            }
        });
    }

    // 3. Top Analyses Pie Chart
    const topCtx = document.getElementById('topAnalysesChart');
    if (topCtx) {
        if(topAnalysesChartInstance) topAnalysesChartInstance.destroy();
        topAnalysesChartInstance = new Chart(topCtx, {
            type: 'pie',
            data: {
                labels: props.strategicData.topAnalyses.labels,
                datasets: [{
                    data: props.strategicData.topAnalyses.series,
                    backgroundColor: [
                        tailwindColors.indigo500,
                        tailwindColors.sky500,
                        tailwindColors.emerald500,
                        tailwindColors.amber500,
                        tailwindColors.rose500
                    ],
                    borderWidth: darkMode ? 2 : 1,
                    borderColor: darkMode ? '#1e293b' : '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                        labels: { color: ticksColor }
                    }
                }
            }
        });
    }

    // 4. Payment Ratio Doughnut
    const payCtx = document.getElementById('paymentRatioChart');
    if (payCtx) {
        if(paymentRatioChartInstance) paymentRatioChartInstance.destroy();
        paymentRatioChartInstance = new Chart(payCtx, {
            type: 'doughnut',
            data: {
                labels: props.strategicData.paymentRatio.labels,
                datasets: [{
                    data: props.strategicData.paymentRatio.series,
                    backgroundColor: [tailwindColors.emerald500, tailwindColors.rose500],
                    borderWidth: darkMode ? 2 : 1,
                    borderColor: darkMode ? '#1e293b' : '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '70%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { color: ticksColor }
                    }
                }
            }
        });
    }
};

onMounted(() => {
    // Render chart immediately
    renderCharts();
});

onUnmounted(() => {
    if(revenueChartInstance) revenueChartInstance.destroy();
    if(prescriptionsChartInstance) prescriptionsChartInstance.destroy();
    if(topAnalysesChartInstance) topAnalysesChartInstance.destroy();
    if(paymentRatioChartInstance) paymentRatioChartInstance.destroy();
});
</script>
