<template>
<AppLayout>
    <div class="px-4 sm:px-6 lg:px-8 py-6">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
            <div>
                <h2 class="font-heading font-extrabold text-2xl text-gray-900 dark:text-white tracking-tight">
                    Suivi des Prescriptions
                </h2>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                    Suivez et gérez les prescriptions de tous les patients.
                </p>
            </div>
            <div class="flex items-center gap-3">
                <button @click="openEmailModal" type="button" class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg shadow-sm transition-colors">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    Envoyer par email
                </button>
                <a :href="exportUrl" target="_blank"
                   class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg shadow-sm transition-colors">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    Exporter CSV
                </a>
            </div>
        </div>

        <!-- Filtres -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4 mb-6">
            <div class="flex flex-col sm:flex-row sm:items-end gap-4">
                <div class="flex-1 min-w-0">
                    <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1 uppercase tracking-wide">Recherche</label>
                    <div class="relative">
                        <input v-model="form.prescriptionSearch" @input="debouncedFilter" type="text" placeholder="Référence, patient, n° dossier..."
                            class="w-full pl-9 pr-4 py-2 text-sm bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white focus:ring-2 focus:ring-amber-500">
                        <svg class="absolute left-3 top-2.5 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1 uppercase tracking-wide">Date début</label>
                    <input v-model="form.date_from" @change="applyFilters" type="date"
                        class="px-3 py-2 text-sm bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white focus:ring-2 focus:ring-amber-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1 uppercase tracking-wide">Date fin</label>
                    <input v-model="form.date_to" @change="applyFilters" type="date"
                        class="px-3 py-2 text-sm bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white focus:ring-2 focus:ring-amber-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1 uppercase tracking-wide">Secrétaire</label>
                    <select v-model="form.secretaire_id" @change="applyFilters"
                        class="px-3 py-2 text-sm bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white focus:ring-2 focus:ring-amber-500">
                        <option value="">Tous</option>
                        <option v-for="s in secretaires" :key="s.id" :value="s.id">{{ s.name }}</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1 uppercase tracking-wide">Par page</label>
                    <select v-model="form.prescriptionsPerPage" @change="applyFilters"
                        class="px-3 py-2 text-sm bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white">
                        <option value="15">15</option><option value="30">30</option><option value="50">50</option><option value="100">100</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Summary cards -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Total prescriptions</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ prescriptionsList.total || 0 }}</p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                <p class="text-xs font-semibold text-emerald-600 dark:text-emerald-400 uppercase tracking-wide">Montant total</p>
                <p class="text-2xl font-bold text-emerald-700 dark:text-emerald-300 mt-1">{{ formatN(totalMontant) }} <span class="text-sm font-normal">Ar</span></p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                <p class="text-xs font-semibold text-blue-600 dark:text-blue-400 uppercase tracking-wide">Montant payé</p>
                <p class="text-2xl font-bold text-blue-700 dark:text-blue-300 mt-1">{{ formatN(totalPaye) }} <span class="text-sm font-normal">Ar</span></p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                <p class="text-xs font-semibold text-red-600 dark:text-red-400 uppercase tracking-wide">Reste à payer</p>
                <p class="text-2xl font-bold text-red-700 dark:text-red-300 mt-1">{{ formatN(totalReste) }} <span class="text-sm font-normal">Ar</span></p>
            </div>
        </div>

        <!-- Table -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900/50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Référence</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Date</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Patient</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Secrétaire</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Prescripteur</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Analyses</th>
                            <th class="px-4 py-3 text-right text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Montant</th>
                            <th class="px-4 py-3 text-right text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Payé</th>
                            <th class="px-4 py-3 text-right text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Reste</th>
                            <th class="px-4 py-3 text-center text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Paiement</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        <tr v-for="p in prescriptionsList.data" :key="p.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                            <td class="px-4 py-3 text-sm font-mono font-semibold text-indigo-600 dark:text-indigo-400">{{ p.reference }}</td>
                            <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400 whitespace-nowrap">{{ formatDate(p.created_at) }}</td>
                            <td class="px-4 py-3">
                                <div class="text-sm font-medium text-gray-900 dark:text-white">{{ p.patient?.nom }} {{ p.patient?.prenom }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ p.patient?.numero_dossier }}</div>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">{{ p.secretaire?.name || 'N/A' }}</td>
                            <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">{{ p.prescripteur?.nom || 'N/A' }}</td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap gap-1 max-w-xs">
                                    <span v-for="a in getAnalysesCodes(p)" :key="a" class="inline-block px-1.5 py-0.5 text-xs font-semibold bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 rounded">
                                        {{ a }}
                                    </span>
                                    <span v-if="!getAnalysesCodes(p).length" class="text-xs text-gray-400">—</span>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-sm text-right font-semibold text-gray-900 dark:text-white whitespace-nowrap">{{ formatN(getMontantTotal(p)) }} Ar</td>
                            <td class="px-4 py-3 text-sm text-right font-medium text-blue-600 dark:text-blue-400 whitespace-nowrap">{{ formatN(getMontantPaye(p)) }} Ar</td>
                            <td class="px-4 py-3 text-sm text-right font-medium whitespace-nowrap" :class="getReste(p) > 0 ? 'text-red-600 dark:text-red-400' : 'text-gray-500 dark:text-gray-400'">{{ formatN(getReste(p)) }} Ar</td>
                            <td class="px-4 py-3 text-center">
                                <span :class="paymentBadge(p)" class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold">
                                    {{ paymentLabel(p) }}
                                </span>
                            </td>
                        </tr>
                        <tr v-if="!prescriptionsList.data?.length">
                            <td colspan="10" class="px-4 py-12 text-center text-gray-500 dark:text-gray-400">Aucune prescription trouvée</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="px-6 py-4 bg-gray-50 dark:bg-gray-900/50 border-t border-gray-200 dark:border-gray-700">
                <Pagination :links="prescriptionsList.links" />
            </div>
        </div>

        <!-- Modal Envoi Email -->
        <Modal :show="showEmailModal" @close="closeEmailModal">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                    Envoyer l'export par e-mail
                </h2>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    Le fichier CSV sera généré avec les filtres actuels et envoyé en pièce jointe.
                </p>

                <div class="mt-6 space-y-4">
                    <div>
                        <InputLabel for="email" value="Adresse Email Principale *" />
                        <TextInput
                            id="email"
                            type="email"
                            class="mt-1 block w-full"
                            v-model="emailForm.email"
                            required
                            placeholder="exemple@domaine.com"
                            @keyup.enter="sendEmail"
                        />
                        <InputError :message="emailForm.errors.email" class="mt-2" />
                    </div>

                    <div>
                        <InputLabel for="cc_emails" value="En copie (CC) - Optionnel" />
                        <TextInput
                            id="cc_emails"
                            type="text"
                            class="mt-1 block w-full"
                            v-model="emailForm.cc_emails"
                            placeholder="email1@test.com, email2@test.com"
                            @keyup.enter="sendEmail"
                        />
                        <p class="mt-1 text-xs text-gray-500">Séparez plusieurs adresses par des virgules.</p>
                        <InputError :message="emailForm.errors.cc_emails" class="mt-2" />
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <SecondaryButton @click="closeEmailModal">
                        Annuler
                    </SecondaryButton>
                    <PrimaryButton
                        class="ml-3"
                        :class="{ 'opacity-25': emailForm.processing }"
                        :disabled="emailForm.processing"
                        @click="sendEmail"
                    >
                        Envoyer
                    </PrimaryButton>
                </div>
            </div>
        </Modal>
    </div>
</AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { debounce } from 'lodash';

const props = defineProps({
    prescriptionsList: Object,
    secretaires: Array,
    prescriptionsFilters: Object,
});

const form = ref({
    prescriptionSearch: props.prescriptionsFilters.prescriptionSearch || '',
    date_from: props.prescriptionsFilters.date_from || '',
    date_to: props.prescriptionsFilters.date_to || '',
    secretaire_id: props.prescriptionsFilters.secretaire_id || '',
    prescriptionsPerPage: props.prescriptionsFilters.prescriptionsPerPage || 15,
});

const applyFilters = () => {
    router.get(route('admin.prescriptions-tracking.index'), {
        ...form.value,
    }, { preserveState: true, preserveScroll: true, replace: true });
};

const debouncedFilter = debounce(applyFilters, 400);

const showEmailModal = ref(false);

const emailForm = useForm({
    email: '',
    cc_emails: '',
});

const openEmailModal = () => {
    emailForm.clearErrors();
    emailForm.reset();
    showEmailModal.value = true;
};

const closeEmailModal = () => {
    showEmailModal.value = false;
    emailForm.reset();
};

const sendEmail = () => {
    const data = {
        ...emailForm.data(),
        date_from: form.value.date_from,
        date_to: form.value.date_to,
        secretaire_id: form.value.secretaire_id,
        prescriptionSearch: form.value.prescriptionSearch,
    };
    
    emailForm.transform(() => data).post(route('admin.prescriptions.export.email'), {
        preserveScroll: true,
        onSuccess: () => {
            closeEmailModal();
        },
    });
};

const exportUrl = computed(() => {
    const params = new URLSearchParams();
    if (form.value.date_from) params.set('date_from', form.value.date_from);
    if (form.value.date_to) params.set('date_to', form.value.date_to);
    if (form.value.secretaire_id) params.set('secretaire_id', form.value.secretaire_id);
    if (form.value.prescriptionSearch) params.set('prescriptionSearch', form.value.prescriptionSearch);
    return route('admin.prescriptions.export') + '?' + params.toString();
});

// -- Helpers --
const getAnalysesCodes = (p) => {
    if (!p.analyses || !p.analyses.length) return [];
    return [...new Set(p.analyses.map(a => a.code).filter(Boolean))];
};
const getMontantTotal = (p) => p.montant_total || 0;
const getMontantPaye = (p) => (p.paiements || []).reduce((sum, pay) => sum + (parseFloat(pay.montant) || 0), 0);
const getReste = (p) => Math.max(0, getMontantTotal(p) - getMontantPaye(p));

const totalMontant = computed(() => (props.prescriptionsList.data || []).reduce((s, p) => s + getMontantTotal(p), 0));
const totalPaye = computed(() => (props.prescriptionsList.data || []).reduce((s, p) => s + getMontantPaye(p), 0));
const totalReste = computed(() => Math.max(0, totalMontant.value - totalPaye.value));

const formatN = (n) => Number(n || 0).toLocaleString('fr-FR');
const formatDate = (d) => {
    if (!d) return '';
    const dt = new Date(d);
    return dt.toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' });
};

const paymentLabel = (p) => {
    const total = getMontantTotal(p);
    const paye = getMontantPaye(p);
    if (paye >= total && total > 0) return 'Payé';
    if (paye > 0) return 'Partiel';
    return 'Impayé';
};

const paymentBadge = (p) => {
    const label = paymentLabel(p);
    if (label === 'Payé') return 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-800 dark:text-emerald-300';
    if (label === 'Partiel') return 'bg-amber-100 dark:bg-amber-900/30 text-amber-800 dark:text-amber-300';
    return 'bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300';
};
</script>
