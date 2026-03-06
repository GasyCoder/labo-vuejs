<script setup>
import { computed } from 'vue';
import GermeCulture from './GermeCulture.vue';
import BiologicInput from './BiologicInput.vue';

const props = defineProps({
    node: { type: Object, required: true },
    prescriptionId: { type: Number, required: true },
    familles: { type: Array, required: true },
    getFormValue: { type: Function, required: true },
    setFormValue: { type: Function, required: true },
    saveStatus: { type: Object, required: true },
    savedAt: { type: Object, required: true },
    canEditResults: { type: Boolean, default: false },
    canEditConclusions: { type: Boolean, default: false },
    notesState: { type: Object, required: true },
    noteDrafts: { type: Object, required: true },
    noteEditId: { type: Object, required: true },
    noteEditText: { type: Object, required: true },
    openNotes: { type: Object, required: true },
    showAddNote: { type: Object, required: true },
    toggleNotes: { type: Function, required: true },
    openAddNote: { type: Function, required: true },
    addConclusionNote: { type: Function, required: true },
    startEditNote: { type: Function, required: true },
    updateConclusionNote: { type: Function, required: true },
    deleteConclusionNote: { type: Function, required: true },
});

const emit = defineEmits(['confirm-save']);

const formValue = computed(() => props.getFormValue(props.node.id));
const interpretation = computed(() => formValue.value?.interpretation || 'NORMAL');
const validationStatus = computed(() => formValue.value?.validation_status || 'OK');
const validationMessage = computed(() => formValue.value?.validation_message);

const setInterpretation = (val) => {
    if (props.canEditResults === false) return;
    props.setFormValue(props.node.id, 'interpretation', val);
};

const isNumericType = computed(() => {
    return ['INPUT', 'DOSAGE', 'COMPTAGE', 'INPUT_SUFFIXE'].includes(props.node.type);
});

const hasNoInterpretation = computed(() => {
    return ['GERME', 'CULTURE', 'LABEL', 'MULTIPLE', 'MULTIPLE_SELECTIF'].includes(props.node.type);
});

</script>

<template>
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl shadow-sm p-6 mb-5 hover:shadow-md transition-shadow duration-200">
        <!-- Header -->
        <div class="flex items-start justify-between mb-5 gap-6">
            <div class="flex-1">
                <div class="flex items-center gap-3 mb-2 flex-wrap">
                    <span class="px-2 py-0.5 bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400 rounded text-[10px] font-black uppercase tracking-widest border border-slate-200 dark:border-slate-700">
                        {{ node.type_label || node.type }}
                    </span>
                    <h2 class="text-base font-bold text-slate-900 dark:text-slate-100" :class="{ 'font-black underline decoration-blue-500/20': node.is_bold }">
                        {{ node.designation }}
                    </h2>
                    <span v-if="saveStatus[node.id]" class="text-[9px] px-2 py-0.5 rounded-full border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 font-bold uppercase tracking-tight text-slate-500">
                        {{ saveStatus[node.id] === 'saving' ? 'Enregistrement...' : 'Sauvegardé' }}
                    </span>
                </div>
            </div>
            
            <!-- Norme Référence -->
            <div v-if="node.valeur_ref || (node.full_ranges && (node.full_ranges.normal_min !== null || node.full_ranges.normal_max !== null))" class="flex flex-col items-end shrink-0">
                <div class="flex items-center gap-2 bg-slate-50 dark:bg-slate-800 px-3 py-1.5 rounded-lg border border-slate-100 dark:border-slate-700 shadow-sm">
                    <span class="text-[9px] font-black uppercase text-slate-400">Norme :</span>
                    <span class="text-sm font-black text-emerald-600 dark:text-emerald-400">
                        {{ node.valeur_ref || (node.full_ranges ? node.full_ranges.normal_min + ' - ' + node.full_ranges.normal_max : '') }}
                    </span>
                    <span v-if="node.unite" class="text-[10px] font-bold text-slate-400 uppercase ml-0.5">{{ node.unite }}</span>
                </div>
            </div>
        </div>

        <!-- Structure Récursive -->
        <div v-if="node.children?.length > 0" class="space-y-5 pl-5 border-l-2 border-slate-200 dark:border-slate-700 mt-2">
            <AnalyseNode v-for="sub in node.children" :key="sub.id" :node="sub" :prescriptionId="prescriptionId" :familles="familles" :getFormValue="getFormValue" :setFormValue="setFormValue" :saveStatus="saveStatus" :savedAt="savedAt" :canEditResults="canEditResults" :canEditConclusions="canEditConclusions" :notesState="notesState" :noteDrafts="noteDrafts" :noteEditId="noteEditId" :noteEditText="noteEditText" :openNotes="openNotes" :showAddNote="showAddNote" :toggleNotes="toggleNotes" :openAddNote="openAddNote" :addConclusionNote="addConclusionNote" :startEditNote="startEditNote" :updateConclusionNote="updateConclusionNote" :deleteConclusionNote="deleteConclusionNote" @confirm-save="$emit('confirm-save', $event)" />
        </div>

        <!-- Saisie PARALLÈLE -->
        <div v-else class="mt-2">
            <div class="grid grid-cols-1 md:grid-cols-12 gap-6 items-start">
                
                <!-- Zone Saisie (Labels et Champs alignés) -->
                <div :class="hasNoInterpretation ? 'md:col-span-12' : 'md:col-span-7 lg:col-span-8'">
                    
                    <!-- 1. MICROBIOLOGIE -->
                    <div v-if="['GERME', 'CULTURE'].includes(node.type)">
                        <GermeCulture :analyse="node" :prescriptionId="prescriptionId" :familles="familles" :getFormValue="getFormValue" :setFormValue="setFormValue" :canEditResults="canEditResults" />
                    </div>
                    
                    <!-- 2. NUMÉRIQUES -->
                    <div v-else-if="isNumericType">
                        <BiologicInput :model-value="getFormValue(node.id, 'valeur')" @update:model-value="setFormValue(node.id, 'valeur', $event)" @confirm="$emit('confirm-save', node.id)" :unite="node.unite" :ranges="node.full_ranges" :validation-status="validationStatus" :can-edit="canEditResults" :save-status="saveStatus[node.id]" />
                    </div>

                    <!-- 3. SELECT / TEST -->
                    <div v-else-if="['SELECT','TEST'].includes(node.type)" class="flex flex-col">
                        <label class="text-xs font-black uppercase tracking-widest text-slate-500 mb-1.5 px-1">Résultat</label>
                        <div class="relative">
                            <select v-if="node.valeurs_predefinies?.length" :disabled="!canEditResults" :value="getFormValue(node.id, 'resultats')" @change="setFormValue(node.id, 'resultats', $event.target.value)" class="w-full px-4 py-2.5 bg-white dark:bg-slate-800 border-2 border-slate-200 dark:border-slate-700 rounded-xl text-base font-bold text-slate-900 dark:text-slate-100 focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all shadow-sm">
                                <option value="">-- Sélectionner --</option>
                                <option v-for="opt in node.valeurs_predefinies" :key="opt" :value="opt">{{ opt }}</option>
                            </select>
                            <input v-else type="text" :disabled="!canEditResults" :value="getFormValue(node.id, 'valeur')" @input="setFormValue(node.id, 'valeur', $event.target.value)" class="w-full px-4 py-2.5 border-2 border-slate-200 dark:border-slate-700 rounded-xl text-base font-bold dark:bg-slate-800 dark:text-white" placeholder="Saisir résultat..." />
                        </div>
                        <div class="min-h-[20px] mt-1.5"></div> <!-- Spacer pour alignement -->
                    </div>

                    <!-- 4. NEGATIF / POSITIF -->
                    <div v-else-if="['NEGATIF_POSITIF_1','NEGATIF_POSITIF_2','NEGATIF_POSITIF_3'].includes(node.type)" class="flex flex-col">
                        <label class="text-xs font-black uppercase tracking-widest text-slate-500 mb-1.5 px-1">Résultat</label>
                        <div class="flex gap-3">
                            <button @click="setFormValue(node.id, 'valeur', 'NEGATIF')" :disabled="!canEditResults" :class="['flex-1 py-2.5 rounded-xl text-sm font-black uppercase transition-all border-2 shadow-sm', getFormValue(node.id, 'valeur') === 'NEGATIF' ? 'bg-emerald-500 text-white border-emerald-500 shadow-emerald-100' : 'bg-white dark:bg-slate-800 text-slate-400 border-slate-200 dark:border-slate-700']">Négatif</button>
                            <button @click="setFormValue(node.id, 'valeur', 'POSITIF')" :disabled="!canEditResults" :class="['flex-1 py-2.5 rounded-xl text-sm font-black uppercase transition-all border-2 shadow-sm', getFormValue(node.id, 'valeur') === 'POSITIF' ? 'bg-red-600 text-white border-red-600 shadow-red-100' : 'bg-white dark:bg-slate-800 text-slate-400 border-slate-200 dark:border-slate-700']">Positif</button>
                        </div>
                        <div class="min-h-[20px] mt-1.5"></div> <!-- Spacer pour alignement -->
                    </div>

                    <!-- 5. DEFAULT -->
                    <div v-else-if="node.type !== 'LABEL'" class="flex flex-col">
                        <label class="text-xs font-black uppercase tracking-widest text-slate-500 mb-1.5 px-1">Observations</label>
                        <textarea :disabled="!canEditResults" :value="getFormValue(node.id, 'resultats')" @input="setFormValue(node.id, 'resultats', $event.target.value)" rows="2" class="w-full px-4 py-2 border-2 border-slate-200 dark:border-slate-700 rounded-xl text-base font-medium dark:bg-slate-800 dark:text-white" placeholder="Saisie libre..."></textarea>
                        <div class="min-h-[20px] mt-1.5"></div>
                    </div>
                </div>

                <!-- ZONE INTERPRÉTATION (Parallèle à la Zone Saisie) -->
                <div v-if="!hasNoInterpretation" class="md:col-span-5 lg:col-span-4 flex flex-col">
                    <label class="text-xs font-black uppercase tracking-widest text-slate-500 mb-1.5 px-1">Interprétation</label>
                    <div class="flex p-1 bg-slate-100 dark:bg-slate-800 rounded-xl border-2 border-slate-200 dark:border-slate-700 h-[48px] items-stretch">
                        <button type="button" @click="setInterpretation('NORMAL')"
                            :class="[
                                'flex-1 rounded-lg text-[11px] font-black uppercase transition-all flex items-center justify-center gap-1.5 border-2',
                                interpretation === 'NORMAL' ? 'bg-emerald-500 text-white border-emerald-500 shadow-md scale-[1.02]' : 'text-slate-400 border-transparent hover:text-slate-600'
                            ]">
                            <em v-if="interpretation === 'NORMAL'" class="ni ni-check-round-fill text-xs"></em> Normal
                        </button>
                        <button type="button" @click="setInterpretation('PATHOLOGIQUE')"
                            :class="[
                                'flex-1 rounded-lg text-[11px] font-black uppercase transition-all flex items-center justify-center gap-1.5 border-2',
                                interpretation === 'PATHOLOGIQUE' ? 'bg-red-600 text-white border-red-600 shadow-md scale-[1.02]' : 'text-slate-400 border-transparent hover:text-slate-600'
                            ]">
                            <em v-if="interpretation === 'PATHOLOGIQUE'" class="ni ni-alert-fill text-xs"></em> Patho
                        </button>
                    </div>
                    <!-- Spacer de compensation pour l'alignement parallèle avec le feedback de BiologicInput -->
                    <div class="min-h-[20px] mt-1.5"></div>
                </div>
            </div>
        </div>

        <!-- Section Conclusions -->
        <div v-if="!['LABEL', 'MULTIPLE'].includes(node.type)" class="mt-6 pt-4 border-t border-slate-100 dark:border-slate-800">
            <button @click="toggleNotes(node.id)" class="flex items-center gap-2 text-xs font-black uppercase text-slate-400 hover:text-blue-600 transition-colors tracking-widest">
                <em class="ni ni-notes text-sm"></em>
                Conclusion ({{ notesState[node.id]?.length || 0 }})
            </button>
            <div v-show="openNotes[node.id]" class="mt-4 space-y-3">
                <div v-for="note in notesState[node.id]" :key="note.id" class="p-3 bg-slate-50 dark:bg-slate-800 rounded-xl text-sm border border-slate-200 dark:border-slate-700">
                    <div class="flex justify-between items-start mb-1 text-[9px] font-bold text-slate-400 uppercase">
                        <span>Saisi par {{ note.technicien_name }}</span>
                        <div v-if="canEditConclusions" class="flex gap-3">
                            <button @click="startEditNote(node.id, note)" class="text-blue-600 hover:underline">Modifier</button>
                            <button @click="deleteConclusionNote(node.id, note.id)" class="text-red-600 hover:underline">Supprimer</button>
                        </div>
                    </div>
                    <p class="text-slate-700 dark:text-slate-300 leading-snug">{{ note.note }}</p>
                </div>
                <div v-if="canEditConclusions" class="pt-2">
                    <button v-show="!showAddNote[node.id]" @click="openAddNote(node.id)" type="button" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-[10px] font-black text-blue-600 bg-blue-50 dark:bg-blue-900/20 rounded-lg uppercase transition-all">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                        Ajouter une conclusion
                    </button>
                    <div v-show="showAddNote[node.id]" class="mt-3">
                        <textarea v-model="noteDrafts[node.id]" class="w-full p-3 bg-white dark:bg-slate-800 border-2 border-slate-200 dark:border-slate-700 rounded-xl text-sm text-slate-900 dark:text-slate-100" placeholder="Note..."></textarea>
                        <div class="flex justify-end gap-2 mt-2">
                            <button @click="showAddNote[node.id] = false" class="text-[10px] font-black text-slate-400 uppercase">Annuler</button>
                            <button @click="addConclusionNote(node.id)" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-[10px] font-black uppercase shadow-lg">Valider</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default { name: 'AnalyseNode' }
</script>
