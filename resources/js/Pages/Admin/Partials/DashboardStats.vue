<template>
<div class="space-y-6">
    <!-- Message de bienvenue -->
    <div class="bg-gradient-to-r from-primary-500 to-primary-600 rounded-lg shadow-lg p-6 text-white">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-lg font-semibold">Bienvenue, {{ user.name }}</h3>
                <p class="text-primary-100">{{ capitalizeFirstLetter(user.type) }} - Tableau de bord</p>
            </div>
            <div class="text-4xl">
                <template v-if="user.type === 'superadmin'">⚙️</template>
                <template v-else-if="user.type === 'biologiste'">🔬</template>
                <template v-else-if="user.type === 'technicien'">🧪</template>
                <template v-else-if="user.type === 'secretaire'">📋</template>
            </div>
        </div>
    </div>

    <template v-if="hasPermission('prescriptions.voir')">
        <!-- Statistiques des Patients -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h4 class="text-lg font-semibold text-gray-900 dark:text-gray-100 flex items-center">
                    <span class="w-2 h-2 bg-cyan-500 rounded-full mr-2"></span>
                    Patients
                </h4>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="bg-cyan-50 dark:bg-cyan-900/20 rounded-lg p-4 border border-cyan-200 dark:border-cyan-800">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-cyan-700 dark:text-cyan-300">Total</p>
                                <p class="text-2xl font-bold text-cyan-900 dark:text-cyan-100">{{ stats.patients?.total || 0 }}</p>
                            </div>
                            <div class="w-8 h-8 bg-cyan-100 dark:bg-cyan-800 rounded-full flex items-center justify-center">
                                <svg class="w-4 h-4 text-cyan-600 dark:text-cyan-400" fill="currentColor" viewBox="0 0 20 20"><path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z" /></svg>
                            </div>
                        </div>
                    </div>
                    <!-- ... Nouveaux, Fidèles, VIP ... -->
                    <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-4 border border-green-200 dark:border-green-800">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-green-700 dark:text-green-300">Nouveaux</p>
                                <p class="text-2xl font-bold text-green-900 dark:text-green-100">{{ stats.patients?.nouveaux || 0 }}</p>
                            </div>
                            <div class="w-8 h-8 bg-green-100 dark:bg-green-800 rounded-full flex items-center justify-center">
                                <svg class="w-4 h-4 text-green-600 dark:text-green-400" fill="currentColor" viewBox="0 0 20 20"><path d="M8 9a3 3 0 100-6 3 3 0 000 6zM8 11a6 6 0 016 6H2a6 6 0 016-6zM16 7a1 1 0 10-2 0v1h-1a1 1 0 100 2h1v1a1 1 0 102 0v-1h1a1 1 0 100-2h-1V7z" /></svg>
                            </div>
                        </div>
                    </div>
                    <div class="bg-yellow-50 dark:bg-yellow-900/20 rounded-lg p-4 border border-yellow-200 dark:border-yellow-800">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-yellow-700 dark:text-yellow-300">Fidèles</p>
                                <p class="text-2xl font-bold text-yellow-900 dark:text-yellow-100">{{ stats.patients?.fideles || 0 }}</p>
                            </div>
                            <div class="w-8 h-8 bg-yellow-100 dark:bg-yellow-800 rounded-full flex items-center justify-center">
                                <svg class="w-4 h-4 text-yellow-600 dark:text-yellow-400" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" /></svg>
                            </div>
                        </div>
                    </div>
                    <div class="bg-red-50 dark:bg-red-900/20 rounded-lg p-4 border border-red-200 dark:border-red-800">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-red-700 dark:text-red-300">VIP</p>
                                <p class="text-2xl font-bold text-red-900 dark:text-red-100">{{ stats.patients?.vip || 0 }}</p>
                            </div>
                            <div class="w-8 h-8 bg-red-100 dark:bg-red-800 rounded-full flex items-center justify-center">
                                <svg class="w-4 h-4 text-red-600 dark:text-red-400" fill="currentColor" viewBox="0 0 20 20"><path d="M9.663 17h4.673a1.305 1.305 0 001.305-1.305v-5.39a3.26 3.26 0 00-3.26-3.26H9.663a3.26 3.26 0 00-3.26 3.26v5.39A1.305 1.305 0 009.663 17z" /><path d="M5.5 7c.828 0 1.5-.895 1.5-2s-.672-2-1.5-2S4 3.895 4 5s.672 2 1.5 2zm9 0c.828 0 1.5-.895 1.5-2s-.672-2-1.5-2S13 3.895 13 5s.672 2 1.5 2z" /></svg>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </template>

    <template v-if="hasAnyPermission(['analyses.voir', 'analyses.effectuer', 'analyses.valider'])">
        <!-- Statistiques des Analyses -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h4 class="text-lg font-semibold text-gray-900 dark:text-gray-100 flex items-center">
                    <span class="w-2 h-2 bg-primary-500 rounded-full mr-2"></span>
                    Analyses & Résultats
                </h4>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <!-- ... En attente, En cours, Terminées, Validées ... -->
                    <div class="bg-slate-50 dark:bg-slate-900/20 rounded-lg p-4 border border-slate-200 dark:border-slate-700">
                        <div class="flex items-center justify-between">
                            <div><p class="text-sm font-medium text-slate-700 dark:text-slate-300">En attente</p><p class="text-2xl font-bold text-slate-900 dark:text-slate-100">{{ stats.analyses?.en_attente || 0 }}</p></div>
                        </div>
                    </div>
                    <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4 border border-blue-200 dark:border-blue-800">
                        <div class="flex items-center justify-between">
                            <div><p class="text-sm font-medium text-blue-700 dark:text-blue-300">En cours</p><p class="text-2xl font-bold text-blue-900 dark:text-blue-100">{{ stats.analyses?.en_cours || 0 }}</p></div>
                        </div>
                    </div>
                    <div class="bg-orange-50 dark:bg-orange-900/20 rounded-lg p-4 border border-orange-200 dark:border-orange-800">
                        <div class="flex items-center justify-between">
                            <div><p class="text-sm font-medium text-orange-700 dark:text-orange-300">Terminées</p><p class="text-2xl font-bold text-orange-900 dark:text-orange-100">{{ stats.analyses?.terminees || 0 }}</p></div>
                        </div>
                    </div>
                    <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-4 border border-green-200 dark:border-green-800">
                        <div class="flex items-center justify-between">
                            <div><p class="text-sm font-medium text-green-700 dark:text-green-300">Validées</p><p class="text-2xl font-bold text-green-900 dark:text-green-100">{{ stats.analyses?.valides || 0 }}</p></div>
                        </div>
                    </div>
                </div>

                <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-2">
                            <span class="w-3 h-3 bg-red-400 rounded-full"></span>
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Résultats pathologiques</span>
                        </div>
                        <span class="text-lg font-bold text-red-600 dark:text-red-400">{{ stats.analyses?.pathologiques || 0 }}</span>
                    </div>
                </div>
            </div>
        </div>
    </template>

    <template v-if="hasPermission('prescriptions.voir')">
        <!-- Statistiques Financières -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h4 class="text-lg font-semibold text-gray-900 dark:text-gray-100 flex items-center">
                    <span class="w-2 h-2 bg-green-500 rounded-full mr-2"></span>
                    Finances
                </h4>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-4 border border-green-200 dark:border-green-800">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-green-700 dark:text-green-300">Recettes du jour</p>
                                <p class="text-xl font-bold text-green-900 dark:text-green-100">{{ formatCurrency(stats.finances?.recettes_jour) }} Ar</p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4 border border-blue-200 dark:border-blue-800">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-blue-700 dark:text-blue-300">Recettes du mois</p>
                                <p class="text-xl font-bold text-blue-900 dark:text-blue-100">{{ formatCurrency(stats.finances?.recettes_mois) }} Ar</p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-purple-50 dark:bg-purple-900/20 rounded-lg p-4 border border-purple-200 dark:border-purple-800">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-purple-700 dark:text-purple-300">Nombre de paiements</p>
                                <p class="text-xl font-bold text-purple-900 dark:text-purple-100">{{ stats.finances?.nb_paiements || 0 }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </template>
</div>
</template>

<script setup>
import { computed } from 'vue';
import { usePage, Link } from '@inertiajs/vue3';

const props = defineProps({
    stats: Object,
    user: Object,
});

const page = usePage();

const capitalizeFirstLetter = (string) => {
    if (!string) return '';
    return string.charAt(0).toUpperCase() + string.slice(1);
};

const hasPermission = (permission) => {
    return page.props.auth?.user?.permissions?.includes(permission);
};

const hasAnyPermission = (permissions) => {
    return permissions.some(p => page.props.auth?.user?.permissions?.includes(p));
};

const formatCurrency = (value) => {
    if (value === undefined || value === null) return 0;
    return value.toString().replace(/\B(?=(\d{3})+(?!\d))/g, " ");
};
</script>
