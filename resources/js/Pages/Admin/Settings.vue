<script setup>
import { ref, computed } from 'vue';
import { Head, useForm, router, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    settings: Object,
    paymentMethods: Array,
});

// --- Forms ---
const enterpriseForm = useForm({
    nom_entreprise: props.settings.nom_entreprise || '',
    nif: props.settings.nif || '',
    statut: props.settings.statut || '',
    format_unite_argent: props.settings.format_unite_argent || 'Ar',
    logo: null,
    favicon: null,
});

const discountForm = useForm({
    activer_remise: !!props.settings.activer_remise,
    remise_pourcentage: parseFloat(props.settings.remise_pourcentage) || 0,
});

const commissionForm = useForm({
    commission_prescripteur: !!props.settings.commission_prescripteur,
    commission_prescripteur_pourcentage: parseFloat(props.settings.commission_prescripteur_pourcentage) || 10,
    commission_prescripteur_quota: parseFloat(props.settings.commission_prescripteur_quota) || 250000,
});

const urgenceForm = useForm({
    tarif_urgence_jour: parseFloat(props.settings.tarif_urgence_jour) || 15000,
    tarif_urgence_nuit: parseFloat(props.settings.tarif_urgence_nuit) || 20000,
});

// For payment methods
const showPaymentModal = ref(false);
const editingPayment = ref(null);
const paymentForm = useForm({
    code: '',
    label: '',
    is_active: true,
    display_order: 1,
});

// --- Computed & Watchers ---
const oldCommissionPourcentage = ref(parseFloat(props.settings.commission_prescripteur_pourcentage) || 10);
const showCommissionAlert = computed(() => {
    return Math.abs(commissionForm.commission_prescripteur_pourcentage - oldCommissionPourcentage.value) > 0.01;
});

// --- Methods ---
const updateEnterprise = () => {
    enterpriseForm.post(route('admin.settings.enterprise'), {
        preserveScroll: true,
        onSuccess: () => {
            enterpriseForm.logo = null;
            enterpriseForm.favicon = null;
        }
    });
};

const removeImage = (type) => {
    if (confirm(`Voulez-vous vraiment supprimer le ${type} ?`)) {
        router.post(route('admin.settings.remove-image'), { type }, {
            preserveScroll: true
        });
    }
};

const updateDiscount = () => {
    discountForm.post(route('admin.settings.discount'), { preserveScroll: true });
};

const updateCommission = () => {
    commissionForm.post(route('admin.settings.commission'), {
        preserveScroll: true,
        onSuccess: () => {
            oldCommissionPourcentage.value = commissionForm.commission_prescripteur_pourcentage;
        }
    });
};

const updateEmergency = () => {
    urgenceForm.post(route('admin.settings.emergency'), { preserveScroll: true });
};

const openPaymentModal = (method = null) => {
    editingPayment.value = method;
    paymentForm.clearErrors();
    if (method) {
        paymentForm.code = method.code;
        paymentForm.label = method.label;
        paymentForm.is_active = !!method.is_active;
        paymentForm.display_order = method.display_order;
    } else {
        paymentForm.reset();
        paymentForm.display_order = props.paymentMethods.length > 0
            ? Math.max(...props.paymentMethods.map(m => m.display_order)) + 1
            : 1;
    }
    showPaymentModal.value = true;
};

const savePaymentMethod = () => {
    if (editingPayment.value) {
        paymentForm.put(route('admin.settings.payment-method.update', editingPayment.value.id), {
            preserveScroll: true,
            onSuccess: () => showPaymentModal.value = false
        });
    } else {
        paymentForm.post(route('admin.settings.payment-method.store'), {
            preserveScroll: true,
            onSuccess: () => showPaymentModal.value = false
        });
    }
};

const confirmDeletePayment = (id) => {
    if (confirm("Êtes-vous sûr de vouloir supprimer ce moyen de paiement ?")) {
        router.delete(route('admin.settings.payment-method.destroy', id), { preserveScroll: true });
    }
};
</script>

<template>
    <Head title="Paramètres du Système" />

    <AppLayout>
        <template #header>
            <div>
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                    ⚙️ Paramètres du système
                </h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Configuration générale de l'application</p>
            </div>
        </template>

        <div class="py-12">
            <div class="px-4 sm:px-6 lg:px-8">
                
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
                    <!-- MAIN CONTENT COLUMN -->
                    <div class="lg:col-span-8 space-y-8">
                        
                        <!-- Entreprise Info -->
                        <div class="bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden border dark:border-gray-700">
                            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50">
                                <h3 class="text-lg font-medium text-gray-900 dark:text-white flex items-center">
                                    <svg class="w-5 h-5 mr-3 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                                    Informations de l'entreprise
                                </h3>
                            </div>
                            <form @submit.prevent="updateEnterprise" class="p-6">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                                    <div class="md:col-span-2">
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nom de l'entreprise *</label>
                                        <input v-model="enterpriseForm.nom_entreprise" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                        <div v-if="enterpriseForm.errors.nom_entreprise" class="text-red-500 text-xs mt-1">{{ enterpriseForm.errors.nom_entreprise }}</div>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">NIF</label>
                                        <input v-model="enterpriseForm.nif" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Statut (STAT)</label>
                                        <input v-model="enterpriseForm.statut" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                    </div>
                                    <div class="md:col-span-2">
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Format de l'unité monétaire *</label>
                                        <input v-model="enterpriseForm.format_unite_argent" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                                    <!-- Logo Upload -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Logo</label>
                                        <div v-if="settings.logo" class="mb-3 relative w-32 h-32 border rounded-lg p-2 bg-gray-50 flex items-center justify-center dark:bg-gray-700 dark:border-gray-600">
                                            <img :src="'/storage/' + settings.logo" alt="Logo" class="max-w-full max-h-full object-contain">
                                            <button @click.prevent="removeImage('logo')" class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full p-1 shadow hover:bg-red-600" title="Supprimer">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                            </button>
                                        </div>
                                        <input type="file" @input="enterpriseForm.logo = $event.target.files[0]" accept="image/*" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 dark:file:bg-gray-700 dark:file:text-indigo-300">
                                        <div v-if="enterpriseForm.errors.logo" class="text-red-500 text-xs mt-1">{{ enterpriseForm.errors.logo }}</div>
                                    </div>

                                    <!-- Favicon Upload -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Favicon</label>
                                        <div v-if="settings.favicon" class="mb-3 relative w-16 h-16 border rounded-lg p-2 bg-gray-50 flex items-center justify-center dark:bg-gray-700 dark:border-gray-600">
                                            <img :src="'/storage/' + settings.favicon" alt="Favicon" class="max-w-full max-h-full object-contain">
                                            <button @click.prevent="removeImage('favicon')" class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full p-1 shadow hover:bg-red-600" title="Supprimer">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                            </button>
                                        </div>
                                        <input type="file" @input="enterpriseForm.favicon = $event.target.files[0]" accept="image/png, image/x-icon, image/ico" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 dark:file:bg-gray-700 dark:file:text-indigo-300">
                                        <div v-if="enterpriseForm.errors.favicon" class="text-red-500 text-xs mt-1">{{ enterpriseForm.errors.favicon }}</div>
                                    </div>
                                </div>

                                <div class="mt-6 flex justify-end">
                                    <button type="submit" :disabled="enterpriseForm.processing" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-md transition-colors disabled:opacity-50 inline-flex items-center">
                                        <span v-if="enterpriseForm.processing">Sauvegarde...</span>
                                        <span v-else>Sauvegarder l'entreprise</span>
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- Payment Methods -->
                        <div class="bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden border dark:border-gray-700">
                            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center bg-gray-50 dark:bg-gray-700/50">
                                <h3 class="text-lg font-medium text-gray-900 dark:text-white flex items-center">
                                    <svg class="w-5 h-5 mr-3 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>
                                    Modes de paiement
                                </h3>
                                <button @click="openPaymentModal()" class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1.5 rounded-md text-sm transition-colors">
                                    + Ajouter
                                </button>
                            </div>
                            <div class="p-0">
                                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                    <thead class="bg-gray-50 dark:bg-gray-800 text-xs uppercase">
                                        <tr>
                                            <th class="px-6 py-3 text-left font-medium text-gray-500 dark:text-gray-400">Ordre</th>
                                            <th class="px-6 py-3 text-left font-medium text-gray-500 dark:text-gray-400">Code</th>
                                            <th class="px-6 py-3 text-left font-medium text-gray-500 dark:text-gray-400">Libellé</th>
                                            <th class="px-6 py-3 text-left font-medium text-gray-500 dark:text-gray-400">Statut</th>
                                            <th class="px-6 py-3 text-right font-medium text-gray-500 dark:text-gray-400">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                        <tr v-for="method in paymentMethods" :key="method.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                            <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-300">{{ method.display_order }}</td>
                                            <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-gray-300">{{ method.code }}</td>
                                            <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-300">{{ method.label }}</td>
                                            <td class="px-6 py-4">
                                                <span class="px-2 py-1 text-xs rounded-full" :class="method.is_active ? 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300' : 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300'">
                                                    {{ method.is_active ? 'Actif' : 'Inactif' }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 text-right text-sm font-medium">
                                                <button @click="openPaymentModal(method)" class="p-1.5 rounded-lg text-indigo-600 hover:bg-indigo-50 dark:text-indigo-400 dark:hover:bg-indigo-900/30 transition-colors mr-1" title="Modifier">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                                </button>
                                                <button @click="confirmDeletePayment(method.id)" class="p-1.5 rounded-lg text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-900/30 transition-colors" title="Supprimer">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                </button>
                                            </td>
                                        </tr>
                                        <tr v-if="paymentMethods.length === 0">
                                            <td colspan="5" class="px-6 py-8 text-center text-gray-500">Aucun mode de paiement</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                    </div>

                    <!-- SIDEBAR COLUMN -->
                    <div class="lg:col-span-4 space-y-6">
                        
                        <!-- Remises -->
                        <div class="bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden border dark:border-gray-700">
                            <div class="px-6 py-4 bg-gray-50 dark:bg-gray-700/50 border-b border-gray-200 dark:border-gray-700">
                                <h3 class="text-lg font-medium text-gray-900 dark:text-white flex items-center">
                                    <svg class="w-5 h-5 mr-3 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path></svg>
                                    Remises
                                </h3>
                            </div>
                            <form @submit.prevent="updateDiscount">
                                <div class="p-6 space-y-4">
                                    <div class="flex items-center">
                                        <input type="checkbox" id="activer_remise" v-model="discountForm.activer_remise" class="h-4 w-4 text-green-600 border-gray-300 rounded focus:ring-green-500">
                                        <label for="activer_remise" class="ml-2 block text-sm text-gray-900 dark:text-gray-300">Activer les remises</label>
                                    </div>
                                    <div v-if="discountForm.activer_remise">
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Pourcentage par défaut (%)</label>
                                        <input v-model="discountForm.remise_pourcentage" type="number" step="0.01" min="0" max="100" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                    </div>
                                </div>
                                <div class="px-6 py-3 bg-gray-50 dark:bg-gray-700/50 border-t border-gray-200 dark:border-gray-700 flex justify-end">
                                    <button type="submit" :disabled="discountForm.processing" class="text-sm bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md transition-colors disabled:opacity-50">Sauvegarder</button>
                                </div>
                            </form>
                        </div>

                        <!-- Commissions -->
                        <div class="bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden border dark:border-gray-700">
                            <div class="px-6 py-4 bg-orange-50 dark:bg-orange-900/20 border-b border-orange-100 dark:border-orange-800">
                                <h3 class="text-lg font-medium text-gray-900 dark:text-white flex items-center">
                                    <svg class="w-5 h-5 mr-3 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path></svg>
                                    Commissions
                                </h3>
                            </div>
                            <form @submit.prevent="updateCommission">
                                <div class="p-6 space-y-4">
                                    <div v-if="showCommissionAlert" class="p-3 bg-yellow-50 text-yellow-800 rounded text-sm border border-yellow-200 dark:bg-yellow-900/30 dark:text-yellow-200 dark:border-yellow-700">
                                        ⚠️ Le taux de commission a changé. Le recalcul sera automatique à la sauvegarde.
                                    </div>
                                    <div class="flex items-center">
                                        <input type="checkbox" id="commission" v-model="commissionForm.commission_prescripteur" class="h-4 w-4 text-orange-600 border-gray-300 rounded focus:ring-orange-500">
                                        <label for="commission" class="ml-2 block text-sm text-gray-900 dark:text-gray-300">Activer les commissions prescripteurs</label>
                                    </div>
                                    <div v-if="commissionForm.commission_prescripteur" class="space-y-4 pt-2">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Taux global (%)</label>
                                            <input v-model="commissionForm.commission_prescripteur_pourcentage" type="number" step="0.01" min="0" max="100" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white text-lg font-bold">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Quota mensuel ({{ enterpriseForm.format_unite_argent }})</label>
                                            <input v-model="commissionForm.commission_prescripteur_quota" type="number" min="0" step="1000" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                        </div>
                                    </div>
                                </div>
                                <div class="px-6 py-3 bg-gray-50 dark:bg-gray-700/50 border-t border-gray-200 dark:border-gray-700 flex justify-end">
                                    <button type="submit" :disabled="commissionForm.processing" class="text-sm bg-orange-500 hover:bg-orange-600 text-white px-4 py-2 rounded-md transition-colors disabled:opacity-50">Sauvegarder</button>
                                </div>
                            </form>
                        </div>

                        <!-- Urgences -->
                        <div class="bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden border dark:border-gray-700">
                            <div class="px-6 py-4 bg-red-50 dark:bg-red-900/20 border-b border-red-100 dark:border-red-800">
                                <h3 class="text-lg font-medium text-gray-900 dark:text-white flex items-center">
                                    <svg class="w-5 h-5 mr-3 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                    Urgences ({{ enterpriseForm.format_unite_argent }})
                                </h3>
                            </div>
                            <form @submit.prevent="updateEmergency">
                                <div class="p-6 space-y-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Urgence Jour</label>
                                        <input v-model="urgenceForm.tarif_urgence_jour" type="number" min="0" step="500" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Urgence Nuit</label>
                                        <input v-model="urgenceForm.tarif_urgence_nuit" type="number" min="0" step="500" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                    </div>
                                </div>
                                <div class="px-6 py-3 bg-gray-50 dark:bg-gray-700/50 border-t border-gray-200 dark:border-gray-700 flex justify-end">
                                    <button type="submit" :disabled="urgenceForm.processing" class="text-sm bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-md transition-colors disabled:opacity-50">Sauvegarder</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Lien vers la configuration API -->
                <div class="mt-8">
                    <Link :href="route('admin.api-settings')" class="block bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden border dark:border-gray-700 hover:border-blue-300 dark:hover:border-blue-600 transition-colors group">
                        <div class="px-6 py-5 flex items-center justify-between">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 w-12 h-12 bg-blue-100 dark:bg-blue-900/40 rounded-lg flex items-center justify-center mr-4">
                                    <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                                </div>
                                <div>
                                    <h3 class="text-lg font-medium text-gray-900 dark:text-white group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">Configuration API, Email & SMS</h3>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Configurer les services d'envoi d'emails et de SMS, tester les APIs</p>
                                </div>
                            </div>
                            <svg class="w-5 h-5 text-gray-400 group-hover:text-blue-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                        </div>
                    </Link>
                </div>
                </div>

                <!-- Modals -->
                <!-- Payment Modal -->
                <div v-if="showPaymentModal" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
                    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity dark:bg-gray-900 dark:bg-opacity-80" aria-hidden="true" @click="showPaymentModal = false"></div>
                        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full dark:bg-gray-800 border dark:border-gray-700">
                            <form @submit.prevent="savePaymentMethod">
                                <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                                    <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white mb-4">
                                        {{ editingPayment ? 'Modifier le mode de paiement' : 'Ajouter un mode de paiement' }}
                                    </h3>
                                    <div class="space-y-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Code (unique) *</label>
                                            <input v-model="paymentForm.code" type="text" :disabled="!!editingPayment" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white disabled:opacity-50">
                                            <div v-if="paymentForm.errors.code" class="text-red-500 text-xs mt-1">{{ paymentForm.errors.code }}</div>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Libellé (ex: Espèces, Mvola) *</label>
                                            <input v-model="paymentForm.label" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                            <div v-if="paymentForm.errors.label" class="text-red-500 text-xs mt-1">{{ paymentForm.errors.label }}</div>
                                        </div>
                                        <div class="grid grid-cols-2 gap-4">
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Ordre d'affichage</label>
                                                <input v-model="paymentForm.display_order" type="number" min="1" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                            </div>
                                            <div class="flex items-center pt-6">
                                                <input type="checkbox" id="is_active" v-model="paymentForm.is_active" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                                <label for="is_active" class="ml-2 block text-sm text-gray-900 dark:text-gray-300">Actif</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="bg-gray-50 dark:bg-gray-700/50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse border-t dark:border-gray-700">
                                    <button type="submit" :disabled="paymentForm.processing" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50">
                                        Enregistrer
                                    </button>
                                    <button type="button" @click="showPaymentModal = false" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700">
                                        Annuler
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

        </div>
    </AppLayout>
</template>
