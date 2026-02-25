<template>
<AppLayout>
    <div class="px-4 sm:px-6 lg:px-8 py-6">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
            <div>
                <h2 class="font-heading font-extrabold text-2xl text-gray-900 dark:text-white tracking-tight">
                    Tableau de Bord
                </h2>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                    Vue d'ensemble et suivi des prescriptions du laboratoire
                </p>
            </div>
            <div class="px-4 py-2 bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 text-sm font-medium text-gray-700 dark:text-gray-300 flex items-center">
                <svg class="w-4 h-4 mr-2 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                {{ currentDate }}
            </div>
        </div>

        <!-- Dashboard Stats Section -->
        <DashboardStats :stats="stats" :user="$page.props.auth.user" />

        <!-- Prescription Monitoring Section (Admin only) -->
        <div v-if="$page.props.auth.user.type === 'superadmin'" class="mt-10">
            <DashboardPrescriptions
                :prescriptions="prescriptionsList"
                :secretaires="secretaires"
                :filters="prescriptionsFilters"
            />
        </div>
    </div>
</AppLayout>
</template>

<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import DashboardStats from '@/Pages/Admin/Partials/DashboardStats.vue';
import DashboardPrescriptions from '@/Pages/Admin/Partials/DashboardPrescriptions.vue';

const props = defineProps({
    stats: Object,
    prescriptionsList: Object,
    secretaires: Array,
    prescriptionsFilters: Object,
});

const page = usePage();

const currentDate = computed(() => {
    const now = new Date();
    const day = String(now.getDate()).padStart(2, '0');
    const month = String(now.getMonth() + 1).padStart(2, '0');
    const year = now.getFullYear();
    const hours = String(now.getHours()).padStart(2, '0');
    const minutes = String(now.getMinutes()).padStart(2, '0');
    return `${day}/${month}/${year} à ${hours}:${minutes}`;
});
</script>
