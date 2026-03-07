<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import GroupTagList from './GroupTagList.vue';

const props = defineProps({
    prescription: {
        type: Object,
        required: true,
    },
    tab: {
        type: String,
        required: true,
    },
    actionProcessing: {
        type: String,
        default: null,
    },
});

const emit = defineEmits(['validate', 'reject', 'view', 'preview', 'treat']);

const page = usePage();
const canPerformAnalyses = computed(() => {
    const user = page.props.auth.user;
    return user?.permissions?.includes('analyses.effectuer') || user?.isAdmin;
});

const getInitials = (p) => p ? (p.prenom?.[0] || '') + (p.nom?.[0] || '') : '??';

const formatDate = (d) => {
    if (!d) return '';
    const dt = new Date(d);
    return dt.toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric' });
};

const patientName = (p) => {
    if (!p?.patient) return 'Patient inconnu';
    return `${p.patient.nom || ''} ${p.patient.prenom || ''}`.trim();
};

const prescripteurName = (p) => {
    if (!p?.prescripteur) return '-';
    return `Dr ${p.prescripteur.nom || ''} ${p.prescripteur.prenom || ''}`.trim();
};

const statusConfig = {
    a_valider: { label: 'Terminé', bg: 'bg-amber-100 dark:bg-amber-900/40', text: 'text-amber-800 dark:text-amber-300', dot: 'bg-amber-500' },
    valide: { label: 'Validé', bg: 'bg-emerald-100 dark:bg-emerald-900/40', text: 'text-emerald-800 dark:text-emerald-300', dot: 'bg-emerald-500' },
    a_refaire: { label: 'À refaire', bg: 'bg-red-100 dark:bg-red-900/40', text: 'text-red-800 dark:text-red-300', dot: 'bg-red-500' },
};

const status = statusConfig[props.tab] || statusConfig.a_valider;
</script>

<template>
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm hover:shadow-md transition-shadow overflow-hidden">
        <!-- Card Header: Patient + Status -->
        <div class="p-3 sm:p-4 pb-3">
            <div class="flex items-start gap-3">
                <div class="flex-shrink-0 w-11 h-11 rounded-full flex items-center justify-center text-sm font-bold shadow-sm"
                    :class="tab === 'a_valider' ? 'bg-amber-500 text-white' : (tab === 'valide' ? 'bg-emerald-500 text-white' : 'bg-red-500 text-white')">
                    {{ getInitials(prescription.patient) }}
                </div>
                <div class="flex-1 min-w-0">
                    <h4 class="text-[15px] font-bold text-gray-900 dark:text-white truncate leading-tight">
                        {{ patientName(prescription) }}
                    </h4>
                    <div class="flex items-center gap-2 mt-0.5">
                        <span class="text-[10px] font-mono font-semibold text-gray-500 dark:text-gray-400">{{ prescription.reference }}</span>
                        <span class="text-gray-300 dark:text-gray-600">|</span>
                        <span class="text-xs text-gray-500 dark:text-gray-400">{{ formatDate(prescription.updated_at) }}</span>
                    </div>
                </div>
                <span class="flex-shrink-0 inline-flex items-center gap-1 px-2 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider" :class="[status.bg, status.text]">
                    <span class="w-1.5 h-1.5 rounded-full" :class="status.dot"></span>
                    {{ status.label }}
                </span>
            </div>
        </div>

        <!-- Card Body: Prescripteur + Traçabilité + Analyses -->
        <div class="px-3 sm:px-4 pb-3 space-y-3">
            <!-- Infos Personnel (Traçabilité) -->
            <div class="grid grid-cols-2 gap-2 p-2 bg-gray-50/50 dark:bg-gray-900/30 rounded-lg border border-gray-100 dark:border-gray-700/50">
                <div class="flex flex-col gap-0.5 min-w-0">
                    <span class="text-[9px] uppercase font-bold text-gray-400 dark:text-gray-500 tracking-wider">Saisi par</span>
                    <div class="flex items-center gap-1 text-primary-600 dark:text-primary-400">
                        <em class="icon ni ni-user-alt text-[11px]"></em>
                        <span class="text-[11px] font-bold truncate">{{ prescription.secretaire?.name || 'Système' }}</span>
                    </div>
                </div>
                <div class="flex flex-col gap-0.5 min-w-0 border-l border-gray-200 dark:border-gray-700 pl-2">
                    <span class="text-[9px] uppercase font-bold text-gray-400 dark:text-gray-500 tracking-wider">Traité par</span>
                    <div class="flex items-center gap-1 text-teal-600 dark:text-teal-400">
                        <em class="icon ni ni-account-setting-fill text-[11px]"></em>
                        <span class="text-[11px] font-bold truncate">{{ prescription.technicien?.name || '—' }}</span>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-between gap-2 text-xs text-gray-600 dark:text-gray-400">
                <div class="flex items-center gap-1.5 min-w-0">
                    <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    <span class="truncate">{{ prescripteurName(prescription) }}</span>
                </div>
                <!-- Validé par (only in valide tab) -->
                <div v-if="tab === 'valide' && prescription.validator" class="flex-shrink-0 flex items-center gap-1 text-emerald-700 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-900/20 px-2 py-0.5 rounded-md border border-emerald-100 dark:border-emerald-800/50">
                    <em class="icon ni ni-user-check-fill text-[10px]"></em>
                    <span class="text-[9px] font-black uppercase tracking-tight">Validé par : {{ prescription.validator.name }}</span>
                </div>
            </div>

            <!-- Analyses Design (Secretary Style) -->
            <div class="flex flex-wrap items-center gap-1.5 pt-1">
                <!-- Total Count Badge -->
                <span class="inline-flex h-5 w-5 items-center justify-center rounded-full bg-indigo-100 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300 text-[10px] font-black border border-indigo-200 dark:border-indigo-800 shadow-sm mr-0.5">
                    {{ prescription.analyses_count }}
                </span>

                <span v-for="(analyse, idx) in prescription.analyses.slice(0, 4)" :key="idx" 
                    class="inline-flex items-center rounded bg-blue-50 px-1.5 py-0.5 text-[9px] font-bold text-blue-700 dark:bg-blue-900/30 dark:text-blue-300 border border-blue-100/50"
                    :title="analyse.designation">
                    {{ analyse.code || (analyse.designation?.substring(0, 5) + '...') }}
                </span>
                <span v-if="prescription.analyses.length > 4" class="text-[9px] font-bold text-slate-400 self-center">
                    +{{ prescription.analyses.length - 4 }}
                </span>
            </div>

            <!-- Reject reason -->
            <div v-if="tab === 'a_refaire' && prescription.commentaire_biologiste"
                class="text-xs text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/20 rounded-lg p-2.5 border border-red-100 dark:border-red-800/30 flex flex-col gap-2">
                <div><span class="font-semibold">Motif :</span> {{ prescription.commentaire_biologiste }}</div>
                <button 
                    @click="emit('edit-comment', prescription)"
                    class="self-end text-[10px] font-bold text-indigo-600 dark:text-indigo-400 hover:text-indigo-700 dark:hover:text-indigo-300 flex items-center gap-1 bg-indigo-50/50 dark:bg-indigo-900/20 px-2 py-1 rounded-md transition-colors"
                >
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    <span>Modifier</span>
                </button>
            </div>
        </div>

        <!-- Card Actions -->
        <div class="px-3 sm:px-4 pb-4 pt-1 border-t border-gray-100 dark:border-gray-700/50">
            <!-- À valider tab: Valider (primary full-width) + Aperçu & À refaire row -->
            <div v-if="tab === 'a_valider'" class="space-y-2">
                <div class="flex gap-2">
                    <button
                        @click="emit('validate', prescription)"
                        :disabled="actionProcessing !== null"
                        class="flex-1 flex items-center justify-center gap-2 h-11 px-4 bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 text-white rounded-lg text-sm font-bold transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                        aria-label="Valider cette prescription"
                    >
                        <svg v-if="actionProcessing === 'validate-' + prescription.id" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                        <svg v-else class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                        <span>{{ actionProcessing === 'validate-' + prescription.id ? 'Validation...' : 'Valider' }}</span>
                    </button>
                    <button
                        v-if="canPerformAnalyses"
                        @click="emit('treat', prescription)"
                        class="flex-1 flex items-center justify-center gap-2 h-11 px-4 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-bold transition-colors"
                        aria-label="Traiter comme technicien"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                        <span>Traiter</span>
                    </button>
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <button
                        @click="emit('preview', prescription)"
                        :disabled="actionProcessing !== null"
                        class="flex items-center justify-center gap-1.5 h-10 px-3 bg-gray-100 hover:bg-gray-200 active:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg text-xs font-semibold transition-colors disabled:opacity-50"
                        aria-label="Aperçu PDF"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                        Aperçu
                    </button>
                    <button
                        @click="emit('reject', prescription)"
                        :disabled="actionProcessing !== null"
                        class="flex items-center justify-center gap-1.5 h-10 px-3 bg-red-50 hover:bg-red-100 active:bg-red-200 dark:bg-red-900/20 dark:hover:bg-red-900/40 text-red-600 dark:text-red-400 rounded-lg text-xs font-semibold transition-colors disabled:opacity-50 border border-red-200 dark:border-red-800/40"
                        aria-label="Renvoyer au technicien"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        À refaire
                    </button>
                </div>
            </div>

            <!-- Validé tab: Aperçu only -->
            <div v-else-if="tab === 'valide'" class="flex gap-2">
                <button
                    @click="emit('preview', prescription)"
                    class="flex-1 flex items-center justify-center gap-1.5 h-10 px-3 bg-gray-100 hover:bg-gray-200 active:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg text-xs font-semibold transition-colors"
                    aria-label="Aperçu PDF"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                    Aperçu PDF
                </button>
            </div>

            <!-- À refaire tab: Aperçu + Voir -->
            <div v-else class="grid grid-cols-2 gap-2">
                <button
                    @click="emit('preview', prescription)"
                    class="flex items-center justify-center gap-1.5 h-10 px-3 bg-gray-100 hover:bg-gray-200 active:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg text-xs font-semibold transition-colors"
                    aria-label="Aperçu PDF"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                    Aperçu
                </button>
                <button
                    @click="emit('view', prescription)"
                    class="flex items-center justify-center gap-1.5 h-10 px-3 bg-blue-50 hover:bg-blue-100 active:bg-blue-200 dark:bg-blue-900/20 dark:hover:bg-blue-900/40 text-blue-600 dark:text-blue-400 rounded-lg text-xs font-semibold transition-colors border border-blue-200 dark:border-blue-800/40"
                    aria-label="Voir la prescription"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    Voir
                </button>
            </div>
        </div>
    </div>
</template>
