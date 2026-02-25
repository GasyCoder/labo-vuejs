<script setup>
import { computed } from 'vue';

const props = defineProps({
    analysesParents: {
        type: Array,
        required: true
    },
    selectedParentId: {
        type: Number,
        default: null
    },
    isReadyToFinalize: {
        type: Boolean,
        default: false
    },
    canFinalizePrescription: {
        type: Boolean,
        default: false
    },
    isFinalizing: {
        type: Boolean,
        default: false
    }
});

const emit = defineEmits(['select-parent', 'finalize']);

const totalAnalyses = computed(() => props.analysesParents.length);
const analysesTerminees = computed(() => props.analysesParents.filter(a => a.status === 'TERMINE').length);

</script>

<template>
<div class="h-full bg-white dark:bg-slate-800 border-r border-gray-200 dark:border-slate-700 flex flex-col transition-colors duration-200">
    <!-- Header -->
    <div class="px-4 py-6 border-b border-gray-200 dark:border-slate-700 flex-shrink-0 transition-colors duration-200">
        <div class="flex items-center gap-3">
            <div class="w-8 h-8 bg-blue-600 dark:bg-blue-700 rounded-lg flex items-center justify-center transition-colors duration-200">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                </svg>
            </div>
            <div>
                <h2 class="text-lg font-semibold text-gray-900 dark:text-slate-100 transition-colors duration-200">Tâche(s) à traiter</h2>
                <p class="text-sm text-gray-500 dark:text-slate-400 transition-colors duration-200">{{ analysesTerminees }}/{{ totalAnalyses }} terminées</p>
            </div>
        </div>
    </div>

    <!-- Liste des analyses et zone de finalisation -->
    <div class="overflow-y-auto flex-1 p-4 flex flex-col gap-4">
        <template v-if="totalAnalyses > 0">
            <div class="space-y-3">
                <div v-for="parent in analysesParents" :key="parent.id" class="relative group">
                    <!-- Bouton principal d'analyse -->
                    <button type="button" 
                        @click.prevent="emit('select-parent', parent.id)"
                        :class="[
                            'w-full text-left p-4 rounded-xl border transition-all duration-200',
                            selectedParentId === parent.id 
                                ? 'border-blue-500 dark:border-blue-600 bg-blue-50 dark:bg-blue-900/30 ring-2 ring-blue-200 dark:ring-blue-800'
                                : 'border-gray-200 dark:border-slate-700 hover:border-blue-300 dark:hover:border-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20',
                            parent.status === 'TERMINE'
                                ? 'bg-green-50 dark:bg-green-900/10 border-green-200 dark:border-green-800'
                                : (parent.status === 'EN_COURS' ? 'bg-orange-50 dark:bg-orange-900/10 border-orange-200 dark:border-orange-800' : 'bg-gray-50 dark:bg-slate-800/50')
                        ]">
                        <div class="flex items-start gap-4">
                            <!-- Indicateur de statut -->
                            <div class="flex-shrink-0 pt-0.5">
                                <div v-if="parent.status === 'TERMINE'" class="w-8 h-8 bg-green-500 dark:bg-green-600 rounded-lg flex items-center justify-center transition-colors duration-200 shadow-sm">
                                    <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                    </svg>
                                </div>
                                <div v-else-if="parent.status === 'EN_COURS'" class="w-8 h-8 bg-[#f97316] rounded-lg flex items-center justify-center transition-colors duration-200 shadow-sm">
                                    <svg class="w-5 h-5 text-white animate-spin" fill="none" viewBox="0 0 24 24">
                                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v2m0 12v2m4-14l-1.4 1.4M7.4 18.6L6 20m14-8h-2M6 12H4m14 4l-1.4-1.4M7.4 5.4L6 4"></path>
                                    </svg>
                                </div>
                                <!-- VIDE state -->
                                <div v-else class="w-8 h-8 bg-gray-300 dark:bg-slate-600 rounded-lg flex items-center justify-center transition-colors duration-200 shadow-sm">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012-2"></path>
                                    </svg>
                                </div>
                            </div>

                            <!-- Contenu principal -->
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between mb-1">
                                    <h3 class="text-base font-bold text-gray-900 dark:text-slate-100 truncate transition-colors duration-200">
                                        {{ parent.code }}
                                    </h3>
                                    <!-- Badge statut -->
                                    <span :class="[
                                        'ml-2 px-3 py-1 text-xs font-semibold rounded-full transition-colors duration-200 whitespace-nowrap',
                                        parent.status === 'TERMINE' ? 'bg-green-100 text-green-800 dark:bg-[#15803d]/30 dark:text-[#4ade80]' : '',
                                        parent.status === 'EN_COURS' ? 'bg-[#ffedd5] text-[#9a3412] dark:bg-[#7c2d12] dark:text-[#fdba74]' : '',
                                        parent.status !== 'TERMINE' && parent.status !== 'EN_COURS' ? 'bg-gray-100 text-gray-800 dark:bg-slate-700 dark:text-slate-300' : ''
                                    ]">
                                        {{ parent.status === 'TERMINE' ? 'Terminé' : (parent.status === 'EN_COURS' ? 'En cours' : 'À faire') }}
                                    </span>
                                </div>
                                <p class="text-sm text-gray-600 dark:text-slate-300 mb-2 truncate transition-colors duration-200">
                                    {{ parent.designation }}
                                </p>
                                
                                <!-- Progression -->
                                <div v-if="parent.enfants_count > 0" class="flex items-center gap-2 mt-2">
                                    <div class="flex-1 bg-gray-200 dark:bg-slate-700 rounded-full h-1.5 overflow-hidden transition-colors duration-200">
                                        <div :class="[
                                                'h-full rounded-full transition-all duration-300',
                                                parent.status === 'TERMINE' ? 'bg-green-500' : (parent.status === 'EN_COURS' ? 'bg-[#f97316]' : 'bg-gray-400 dark:bg-slate-500')
                                            ]"
                                            :style="`width: ${(parent.enfants_completed / parent.enfants_count) * 100}%`">
                                        </div>
                                    </div>
                                    <span class="text-xs font-medium text-gray-500 dark:text-slate-400 transition-colors duration-200">
                                        {{ parent.enfants_completed }}/{{ parent.enfants_count }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </button>
                </div>
            </div>

            <!-- Bouton de Finalisation Globale (dans la liste) -->
            <div class="mt-2">
                <template v-if="canFinalizePrescription">
                    <div class="p-4 bg-green-50 dark:bg-[#064e3b]/30 border border-green-200 dark:border-[#059669]/30 rounded-xl transition-colors duration-200">
                        <div class="flex items-center justify-center">
                            <svg class="w-5 h-5 text-green-600 dark:text-[#34d399] mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                            <span class="text-base font-bold text-green-800 dark:text-[#34d399]">
                                Prescription finalisée
                            </span>
                        </div>
                    </div>
                </template>
                <template v-else-if="isReadyToFinalize">
                    <button @click="emit('finalize')" 
                        :disabled="isFinalizing"
                        class="w-full bg-[#20c997] hover:bg-[#1ba87e] dark:bg-[#10b981] dark:hover:bg-[#059669] disabled:opacity-50 disabled:cursor-not-allowed text-white font-bold text-base py-3.5 px-4 rounded-xl transition-colors shadow-sm flex items-center justify-center">
                        <template v-if="!isFinalizing">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path>
                            </svg>
                            Finaliser la prescription
                        </template>
                        <template v-else>
                            <svg class="animate-spin w-5 h-5 mr-2 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Finalisation...
                        </template>
                    </button>
                </template>
                <template v-else>
                    <!-- Pas encore prêt - Message informatif discret -->
                    <div class="p-4 bg-transparent border border-gray-700/50 rounded-xl transition-colors duration-200">
                        <div class="flex items-start gap-3">
                            <svg class="w-5 h-5 text-gray-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <div>
                                <p class="text-sm font-medium text-gray-300 transition-colors duration-200">
                                    En attente de finalisation
                                </p>
                                <p class="text-xs text-gray-500 mt-1 transition-colors duration-200">
                                    Complétez toutes les analyses pour finaliser.
                                </p>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </template>
        <template v-else>
            <!-- État vide -->
            <div class="text-center py-12">
                <div class="w-16 h-16 mx-auto mb-4 bg-gray-100 dark:bg-slate-700 rounded-full flex items-center justify-center transition-colors duration-200">
                    <svg class="w-8 h-8 text-gray-400 dark:text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012-2"></path>
                    </svg>
                </div>
                <p class="text-sm font-medium text-gray-600 dark:text-slate-400 transition-colors duration-200">Aucune analyse disponible</p>
            </div>
        </template>
    </div>
</div>
</template>
