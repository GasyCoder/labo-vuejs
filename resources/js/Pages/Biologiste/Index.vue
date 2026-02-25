<script setup>
import { ref, watch, computed } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import StatusTabs from './StatusTabs.vue';
import ValidationStatsBar from './ValidationStatsBar.vue';
import PrescriptionCard from './PrescriptionCard.vue';

const props = defineProps({
    prescriptions: Object,
    stats: Object,
    filters: Object,
});

// --- State ---
const currentTab = ref(props.filters.tab || 'a_valider');
const search = ref(props.filters.search || '');
const actionProcessing = ref(null);
const toast = ref(null);
let toastTimer = null;

// --- Search (debounced 300ms) ---
let searchTimeout = null;
const updateSearch = (value) => {
    search.value = value;
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get(route('biologiste.analyse.index'), { tab: currentTab.value, search: value }, { preserveState: true, preserveScroll: true });
    }, 300);
};

// --- Tab switch ---
const changeTab = (tab) => {
    currentTab.value = tab;
    router.get(route('biologiste.analyse.index'), { tab, search: search.value }, { preserveState: true, preserveScroll: false });
};

// --- Reject modal ---
const showRejectModal = ref(false);
const rejectTarget = ref(null);
const rejectComment = ref('');

const openRejectModal = (p) => {
    rejectTarget.value = p;
    rejectComment.value = '';
    showRejectModal.value = true;
};

const submitReject = () => {
    if (!rejectComment.value.trim()) return;
    actionProcessing.value = 'reject-' + rejectTarget.value.id;
    router.post(route('biologiste.prescription.reject', rejectTarget.value.id), {
        commentaire: rejectComment.value,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            showRejectModal.value = false;
            actionProcessing.value = null;
            showToast('Prescription renvoyée au technicien', 'success');
        },
        onError: () => {
            actionProcessing.value = null;
            showToast('Erreur lors du rejet', 'error');
        },
    });
};

// --- Validate modal ---
const showValidateModal = ref(false);
const validateTarget = ref(null);

const openValidateModal = (p) => {
    validateTarget.value = p;
    showValidateModal.value = true;
};

const submitValidate = () => {
    if (!validateTarget.value) return;
    actionProcessing.value = 'validate-' + validateTarget.value.id;
    router.post(route('biologiste.prescription.validate', validateTarget.value.id), {}, {
        preserveScroll: true,
        onSuccess: () => {
            showValidateModal.value = false;
            actionProcessing.value = null;
            showToast('Prescription validée avec succès', 'success');
        },
        onError: () => {
            actionProcessing.value = null;
            showToast('Erreur lors de la validation', 'error');
        },
    });
};

// --- Preview ---
const handlePreview = (p) => {
    window.open(route('laboratoire.prescription.pdf', p.id), '_blank');
};

// --- View ---
const handleView = (p) => {
    router.get(route('biologiste.prescription.show', p.id));
};

// --- Toast ---
const showToast = (message, type = 'success') => {
    clearTimeout(toastTimer);
    toast.value = { message, type };
    toastTimer = setTimeout(() => { toast.value = null; }, 3500);
};

// --- Empty state messages ---
const emptyMessages = {
    a_valider: 'Aucune prescription en attente de validation',
    valide: 'Aucune prescription validée',
    a_refaire: 'Aucune prescription à refaire',
};
</script>

<template>
    <Head title="Validation Biologiste" />
    <AppLayout>
        <div class="flex flex-col h-full min-h-0">

            <!-- Sticky Header (Mobile) -->
            <div class="sticky top-0 z-30 bg-white dark:bg-gray-900 border-b border-gray-200 dark:border-gray-700 safe-top">
                <!-- Title bar -->
                <div class="px-4 pt-3 pb-2 sm:px-6 lg:px-8">
                    <div class="flex items-center justify-between">
                        <div class="min-w-0">
                            <h1 class="text-lg sm:text-xl lg:text-2xl font-bold text-gray-900 dark:text-white truncate">Validation des Analyses</h1>
                            <p class="text-xs sm:text-sm text-gray-500 dark:text-gray-400 mt-0.5 hidden sm:block">Révision et validation des résultats</p>
                        </div>
                        <div class="flex-shrink-0 flex items-center gap-1.5 px-2.5 py-1 bg-emerald-50 dark:bg-emerald-900/30 rounded-full border border-emerald-200 dark:border-emerald-800">
                            <div class="w-1.5 h-1.5 bg-emerald-500 rounded-full animate-pulse"></div>
                            <span class="text-xs font-semibold text-emerald-700 dark:text-emerald-400">Biologiste</span>
                        </div>
                    </div>
                </div>

                <!-- Stats + Search -->
                <ValidationStatsBar
                    :stats="stats"
                    :search="search"
                    @update:search="updateSearch"
                />

                <!-- Tabs -->
                <StatusTabs
                    :currentTab="currentTab"
                    :stats="stats"
                    @change="changeTab"
                />
            </div>

            <!-- Content: Card Grid -->
            <div class="flex-1 overflow-y-auto bg-gray-50 dark:bg-gray-900">
                <div class="px-3 sm:px-6 lg:px-8 py-4">
                    <!-- Cards grid: 1 col mobile, 2 cols tablet, 3 cols desktop -->
                    <div
                        v-if="prescriptions.data?.length"
                        class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-3 sm:gap-4"
                    >
                        <PrescriptionCard
                            v-for="p in prescriptions.data"
                            :key="p.id"
                            :prescription="p"
                            :tab="currentTab"
                            :actionProcessing="actionProcessing"
                            @validate="openValidateModal"
                            @reject="openRejectModal"
                            @preview="handlePreview"
                            @view="handleView"
                        />
                    </div>

                    <!-- Empty state -->
                    <div v-else class="flex flex-col items-center justify-center py-16 px-4">
                        <div class="w-16 h-16 rounded-full bg-gray-100 dark:bg-gray-800 flex items-center justify-center mb-4">
                            <svg class="w-8 h-8 text-gray-400 dark:text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>
                        </div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400 text-center">{{ emptyMessages[currentTab] }}</p>
                        <p v-if="search" class="text-xs text-gray-400 dark:text-gray-500 mt-1 text-center">
                            Essayez de modifier votre recherche
                        </p>
                    </div>

                    <!-- Pagination -->
                    <div v-if="prescriptions.links?.length > 3" class="mt-6 pb-8">
                        <nav class="flex flex-col sm:flex-row items-center justify-between gap-3" aria-label="Pagination">
                            <p class="text-xs sm:text-sm text-gray-500 dark:text-gray-400">
                                {{ prescriptions.from }}–{{ prescriptions.to }} sur {{ prescriptions.total }}
                            </p>
                            <div class="flex gap-1 flex-wrap justify-center">
                                <template v-for="link in prescriptions.links" :key="link.label">
                                    <Link
                                        v-if="link.url"
                                        :href="link.url"
                                        class="min-w-[36px] h-9 flex items-center justify-center px-3 text-sm rounded-lg transition-colors"
                                        :class="link.active
                                            ? 'bg-blue-600 text-white font-bold'
                                            : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700'"
                                        v-html="link.label"
                                        preserve-state
                                    />
                                    <span v-else class="min-w-[36px] h-9 flex items-center justify-center px-3 text-sm text-gray-400 dark:text-gray-500" v-html="link.label" />
                                </template>
                            </div>
                        </nav>
                    </div>

                    <!-- Bottom safe area for iOS -->
                    <div class="h-6 sm:h-0"></div>
                </div>
            </div>
        </div>

        <!-- Toast notification -->
        <Transition
            enter-active-class="transition ease-out duration-300"
            enter-from-class="translate-y-full opacity-0"
            enter-to-class="translate-y-0 opacity-100"
            leave-active-class="transition ease-in duration-200"
            leave-from-class="translate-y-0 opacity-100"
            leave-to-class="translate-y-full opacity-0"
        >
            <div v-if="toast" class="fixed bottom-6 left-4 right-4 sm:left-auto sm:right-6 sm:w-80 z-[1060]">
                <div class="flex items-center gap-3 px-4 py-3 rounded-xl shadow-lg border"
                    :class="toast.type === 'success'
                        ? 'bg-emerald-50 dark:bg-emerald-900/80 border-emerald-200 dark:border-emerald-700 text-emerald-800 dark:text-emerald-200'
                        : 'bg-red-50 dark:bg-red-900/80 border-red-200 dark:border-red-700 text-red-800 dark:text-red-200'"
                >
                    <svg v-if="toast.type === 'success'" class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                    <svg v-else class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                    <span class="text-sm font-medium">{{ toast.message }}</span>
                    <button @click="toast = null" class="ml-auto flex-shrink-0 p-1 rounded hover:bg-black/10 dark:hover:bg-white/10 transition-colors" aria-label="Fermer">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                    </button>
                </div>
            </div>
        </Transition>

        <!-- Reject Modal -->
        <Transition
            enter-active-class="transition ease-out duration-200"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition ease-in duration-150"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="showRejectModal" class="fixed inset-0 z-[1040] flex items-end sm:items-center justify-center" role="dialog" aria-modal="true" aria-labelledby="reject-title">
                <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" @click="showRejectModal = false"></div>
                <div class="relative bg-white dark:bg-gray-800 w-full sm:max-w-lg sm:rounded-2xl rounded-t-3xl shadow-2xl z-10 safe-bottom">
                    <!-- Drag handle (mobile) -->
                    <div class="sm:hidden flex justify-center pt-3 pb-1">
                        <div class="w-12 h-1.5 bg-gray-300 dark:bg-gray-600 rounded-full"></div>
                    </div>

                    <div class="p-6 sm:p-8">
                        <!-- Icon + Title -->
                        <div class="flex flex-col items-center text-center mb-6">
                            <div class="w-16 h-16 rounded-full bg-gradient-to-br from-red-100 to-red-200 dark:from-red-900/40 dark:to-red-800/30 flex items-center justify-center mb-4 shadow-sm">
                                <svg class="w-8 h-8 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
                            </div>
                            <h3 id="reject-title" class="text-xl font-bold text-gray-900 dark:text-white">Renvoyer au technicien</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                Prescription <span class="font-semibold text-gray-700 dark:text-gray-300">{{ rejectTarget?.reference }}</span>
                            </p>
                        </div>

                        <!-- Textarea -->
                        <div class="mb-6">
                            <label for="reject-comment" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                Motif du renvoi <span class="text-red-500">*</span>
                            </label>
                            <textarea
                                id="reject-comment"
                                v-model="rejectComment"
                                rows="4"
                                required
                                placeholder="Expliquez pourquoi les résultats doivent être refaits..."
                                class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700/70 border-2 border-gray-200 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-red-500 focus:border-red-500 text-sm resize-none transition-colors"
                            ></textarea>
                        </div>

                        <!-- Buttons -->
                        <div class="flex items-center justify-end gap-3">
                            <button
                                @click="showRejectModal = false"
                                class="h-10 px-5 text-sm font-medium text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors"
                            >
                                Annuler
                            </button>
                            <button
                                @click="submitReject"
                                :disabled="!rejectComment.trim() || actionProcessing !== null"
                                class="h-10 px-5 text-sm font-semibold text-white bg-red-600 hover:bg-red-700 active:bg-red-800 rounded-lg shadow-sm transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
                            >
                                <svg v-if="actionProcessing" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                                <span>{{ actionProcessing ? 'Envoi...' : 'Renvoyer' }}</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>

        <!-- Validate Modal -->
        <Transition
            enter-active-class="transition ease-out duration-200"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition ease-in duration-150"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="showValidateModal" class="fixed inset-0 z-[1040] flex items-end sm:items-center justify-center" role="dialog" aria-modal="true" aria-labelledby="validate-title">
                <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" @click="showValidateModal = false"></div>
                <div class="relative bg-white dark:bg-gray-800 w-full sm:max-w-lg sm:rounded-2xl rounded-t-3xl shadow-2xl z-10 safe-bottom">
                    <!-- Drag handle (mobile) -->
                    <div class="sm:hidden flex justify-center pt-3 pb-1">
                        <div class="w-12 h-1.5 bg-gray-300 dark:bg-gray-600 rounded-full"></div>
                    </div>

                    <div class="p-6 sm:p-8 text-center">
                        <!-- Big icon -->
                        <div class="mx-auto flex items-center justify-center w-20 h-20 rounded-full bg-gradient-to-br from-blue-100 to-blue-200 dark:from-blue-900/40 dark:to-blue-800/30 mb-5 shadow-sm">
                            <svg class="h-10 w-10 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>

                        <h3 id="validate-title" class="text-xl font-bold text-gray-900 dark:text-white mb-2">Confirmer la validation</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-2">
                            Êtes-vous sûr de vouloir valider la prescription
                        </p>
                        <p class="text-base font-bold text-gray-900 dark:text-white mb-2">{{ validateTarget?.reference }}</p>
                        <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-700 mb-8">
                            <svg class="w-3.5 h-3.5 text-amber-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                            <span class="text-xs font-semibold text-amber-700 dark:text-amber-300">Les résultats seront définitifs</span>
                        </div>

                        <!-- Buttons -->
                        <div class="flex items-center justify-center gap-3">
                            <button
                                @click="showValidateModal = false"
                                class="h-10 px-5 text-sm font-medium text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors"
                            >
                                Non, annuler
                            </button>
                            <button
                                @click="submitValidate"
                                :disabled="actionProcessing !== null"
                                class="h-10 px-5 text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 active:bg-blue-800 rounded-lg shadow-sm transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
                            >
                                <svg v-if="actionProcessing" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                                <svg v-else class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                <span>{{ actionProcessing ? 'Validation...' : 'Oui, valider' }}</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </AppLayout>
</template>

<style scoped>
.safe-top {
    padding-top: env(safe-area-inset-top, 0px);
}
.safe-bottom {
    padding-bottom: env(safe-area-inset-bottom, 0px);
}
</style>
