<script setup>
import { computed } from 'vue';
import GermeCulture from './GermeCulture.vue';

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

const formValue = computed(() => props.getFormValue(props.node.id));
const interpretation = computed(() => formValue.value?.interpretation || 'NORMAL');
const validationStatus = computed(() => formValue.value?.validation_status || 'OK');
const validationMessage = computed(() => formValue.value?.validation_message);

const setInterpretation = (val) => {
    if (!props.canEditResults) return;
    props.setFormValue(props.node.id, 'interpretation', val);
};

</script>

<template>
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg shadow-sm p-6 mb-4 hover:shadow-md transition-shadow duration-200">
        <!-- Node Header -->
        <div class="flex items-start justify-between mb-4">
            <div class="flex-1">
                <div class="flex items-center gap-3 mb-2 flex-wrap">
                    <span class="px-2 py-1 bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400 rounded-md text-[10px] font-black uppercase tracking-widest border border-slate-200 dark:border-slate-700">
                        {{ node.type_label || node.type }}
                    </span>

                    <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100" :class="{ 'font-bold': node.is_bold }">
                        {{ node.designation }}
                    </h3>
                    
                    <span v-if="validationStatus === 'BLOCK'" class="animate-pulse flex items-center gap-1 text-[10px] px-2 py-0.5 rounded-full bg-red-600 text-white font-black uppercase tracking-widest shadow-sm">
                        <em class="ni ni-alert-fill"></em> {{ validationMessage || 'VALEUR IMPOSSIBLE' }}
                    </span>
                    <span v-else-if="validationStatus === 'WARNING'" class="flex items-center gap-1 text-[10px] px-2 py-0.5 rounded-full bg-amber-500 text-white font-black uppercase tracking-widest shadow-sm">
                        <em class="ni ni-caution-fill"></em> {{ validationMessage || 'HORS NORMES' }}
                    </span>

                    <span v-if="saveStatus[node.id]" class="text-[11px] px-2 py-0.5 rounded-full border"
                        :class="{
                            'border-amber-200 bg-amber-50 text-amber-700 dark:bg-amber-900/20 dark:text-amber-300 dark:border-amber-800': saveStatus[node.id] === 'saving',
                            'border-emerald-200 bg-emerald-50 text-emerald-700 dark:bg-emerald-900/20 dark:text-emerald-300 dark:border-emerald-800': saveStatus[node.id] === 'saved',
                            'border-red-200 bg-red-50 text-red-700 dark:bg-red-900/20 dark:text-red-300 dark:border-red-800': saveStatus[node.id] === 'error',
                        }">
                        {{ saveStatus[node.id] === 'saving' ? 'Enregistrement…' : saveStatus[node.id] === 'saved' ? 'Sauvegardé' + (savedAt[node.id] ? ' à ' + savedAt[node.id] : '') : 'Erreur sauvegarde' }}
                    </span>
                </div>
            </div>

            <!-- Panel de Référence -->
            <div v-if="node.valeur_ref || node.unite || node.full_ranges" class="flex-shrink-0 ml-4 p-4 bg-slate-50 dark:bg-slate-800/50 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-inner min-w-[240px]">
                <div class="space-y-3">
                    <div v-if="node.valeur_ref" class="flex flex-col gap-0.5 pb-2 border-b border-slate-200/50 dark:border-slate-700/50">
                        <span class="text-[9px] font-black uppercase text-slate-400 tracking-tighter">Référence catalogue</span>
                        <div class="flex items-center gap-2 text-sm font-black text-emerald-600 dark:text-emerald-400">
                            {{ node.valeur_ref }}
                            <span v-if="node.unite" class="text-[10px] text-slate-400 uppercase font-bold ml-auto">{{ node.unite }}</span>
                        </div>
                    </div>

                    <div v-if="node.full_ranges && (node.full_ranges.normal_min !== null || node.full_ranges.normal_max !== null)" class="grid grid-cols-2 gap-4">
                        <div class="flex flex-col">
                            <span class="text-[9px] font-black uppercase text-blue-500 tracking-tighter">Min. Normal</span>
                            <span class="text-xs font-black text-slate-700 dark:text-slate-200">{{ node.full_ranges.normal_min ?? '—' }}</span>
                        </div>
                        <div class="flex flex-col text-right">
                            <span class="text-[9px] font-black uppercase text-blue-500 tracking-tighter">Max. Normal</span>
                            <span class="text-xs font-black text-slate-700 dark:text-slate-200">{{ node.full_ranges.normal_max ?? '—' }}</span>
                        </div>
                    </div>

                    <div v-if="node.full_ranges && (node.full_ranges.critical_min !== null || node.full_ranges.critical_max !== null)" class="grid grid-cols-2 gap-4 pt-2 border-t border-slate-100 dark:border-slate-700">
                        <div class="flex flex-col">
                            <span class="text-[9px] font-black uppercase text-red-500 tracking-tighter">Min. Critique</span>
                            <span class="text-xs font-black text-red-600 dark:text-red-400">{{ node.full_ranges.critical_min ?? '—' }}</span>
                        </div>
                        <div class="flex flex-col text-right">
                            <span class="text-[9px] font-black uppercase text-red-500 tracking-tighter">Max. Critique</span>
                            <span class="text-xs font-black text-red-600 dark:text-red-400">{{ node.full_ranges.critical_max ?? '—' }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Form Content -->
        <div class="space-y-4">
            <div v-if="['INPUT','DOSAGE','COMPTAGE','INPUT_SUFFIXE'].includes(node.type)" class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
                <div class="md:col-span-8 space-y-2">
                    <label class="block text-xs text-slate-700 dark:text-slate-300 font-bold">
                        Valeur <span v-if="node.unite">({{ node.unite }})</span>
                    </label>
                    <div class="flex">
                        <input type="text" :disabled="!canEditResults" :value="formValue?.valeur" @input="setFormValue(node.id, 'valeur', $event.target.value)" placeholder="Entrez une valeur"
                            class="flex-1 px-4 py-2 bg-white dark:bg-slate-700 border border-slate-300 dark:border-slate-600 rounded-l-lg text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-blue-500 transition-colors"
                            :class="{ 'border-red-500 bg-red-50 dark:bg-red-900/10': interpretation === 'PATHOLOGIQUE' }" />
                        <span v-if="node.suffixe || node.unite" class="inline-flex items-center px-3 py-2 bg-slate-100 dark:bg-slate-800 border border-l-0 border-slate-300 dark:border-slate-600 rounded-r-lg text-slate-700 dark:text-slate-300 text-xs font-bold uppercase">
                            {{ node.suffixe || node.unite }}
                        </span>
                    </div>
                </div>
                
                <div class="md:col-span-4 space-y-2">
                    <label class="block text-xs font-black uppercase text-slate-400 tracking-widest">Interprétation</label>
                    <div class="flex p-1 bg-slate-100 dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 shadow-inner">
                        <button type="button" @click="setInterpretation('NORMAL')"
                            :class="[
                                'flex-1 py-2 px-3 rounded-lg text-[10px] font-black uppercase transition-all duration-200 flex items-center justify-center gap-1',
                                interpretation === 'NORMAL' ? 'bg-emerald-500 text-white shadow-md' : 'text-slate-400 hover:text-slate-600'
                            ]">
                            <em v-if="interpretation === 'NORMAL'" class="ni ni-check-round-fill text-[12px]"></em>
                            Normal
                        </button>
                        <button type="button" @click="setInterpretation('PATHOLOGIQUE')"
                            :class="[
                                'flex-1 py-2 px-3 rounded-lg text-[10px] font-black uppercase transition-all duration-200 flex items-center justify-center gap-1',
                                interpretation === 'PATHOLOGIQUE' ? 'bg-red-600 text-white shadow-md' : 'text-slate-400 hover:text-slate-600'
                            ]">
                            <em v-if="interpretation === 'PATHOLOGIQUE'" class="ni ni-alert-fill text-[12px]"></em>
                            Pathologique
                        </button>
                    </div>
                </div>
            </div>

            <!-- Children recursive -->
            <div v-if="node.children?.length > 0" class="space-y-4 pl-4 border-l-2 border-slate-200 dark:border-slate-700">
                <AnalyseNode v-for="sub in node.children" :key="sub.id" :node="sub" :prescriptionId="prescriptionId" :familles="familles" :getFormValue="getFormValue" :setFormValue="setFormValue" :saveStatus="saveStatus" :savedAt="savedAt" :canEditResults="canEditResults" :canEditConclusions="canEditConclusions" :notesState="notesState" :noteDrafts="noteDrafts" :noteEditId="noteEditId" :noteEditText="noteEditText" :openNotes="openNotes" :showAddNote="showAddNote" :toggleNotes="toggleNotes" :openAddNote="openAddNote" :addConclusionNote="addConclusionNote" :startEditNote="startEditNote" :updateConclusionNote="updateConclusionNote" :deleteConclusionNote="deleteConclusionNote" />
            </div>
        </div>

        <!-- Notes Section (RESTORED TO ORIGINAL CODES) -->
        <div v-if="!['LABEL', 'MULTIPLE', 'MULTIPLE_SELECTIF'].includes(node.type)" class="pt-4 border-t border-slate-200 dark:border-slate-700 mt-4 space-y-3">
            <button type="button" @click="toggleNotes(node.id)" class="w-full flex items-center justify-between text-left text-sm font-medium text-slate-700 dark:text-slate-300 hover:text-slate-900 transition-colors">
                <span>Conclusion (optionnelle)</span>
                <span class="inline-flex items-center gap-2 text-xs text-slate-500 dark:text-slate-400">
                    <span v-if="notesState[node.id]?.length" class="px-2 py-0.5 bg-slate-100 dark:bg-slate-800 rounded-full">{{ notesState[node.id].length }} note{{ notesState[node.id].length > 1 ? 's' : '' }}</span>
                    <svg class="w-4 h-4 transition-transform" :class="openNotes[node.id] ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                </span>
            </button>

            <div v-show="openNotes[node.id]" class="space-y-3">
                <div v-if="notesState[node.id]?.length" class="space-y-2 mt-2">
                    <div v-for="note in notesState[node.id]" :key="note.id" class="p-3 bg-slate-50 dark:bg-slate-800/50 rounded-lg text-sm border border-slate-200 dark:border-slate-700">
                        <div class="flex justify-between items-start mb-1">
                            <span class="text-xs text-slate-500 dark:text-slate-400">Note</span>
                            <div v-if="canEditConclusions" class="flex gap-2 text-xs font-semibold">
                                <button @click="startEditNote(node.id, note)" class="text-blue-600 dark:text-blue-400 hover:underline">Modifier</button>
                                <button @click="deleteConclusionNote(node.id, note.id)" class="text-red-600 dark:text-red-400 hover:underline">Supprimer</button>
                            </div>
                        </div>
                        <div v-if="noteEditId[node.id] === note.id" class="flex gap-2 mt-2">
                            <input type="text" v-model="noteEditText[node.id]" @keyup.enter="updateConclusionNote(node.id)" class="flex-1 px-2 py-1 text-sm border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-slate-700" />
                            <button @click="updateConclusionNote(node.id)" class="px-2 py-1 bg-green-600 text-white text-xs rounded-md">✓</button>
                        </div>
                        <p v-else class="text-slate-800 dark:text-slate-200 whitespace-pre-wrap">{{ note.note }}</p>
                    </div>
                </div>

                <div v-if="canEditConclusions" class="pt-2">
                    <button v-show="!showAddNote[node.id]" @click="openAddNote(node.id)" type="button" class="inline-flex items-center gap-2 px-3 py-1.5 text-xs font-medium text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/20 hover:bg-blue-100 rounded-lg transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                        Ajouter une note
                    </button>
                    <div v-show="showAddNote[node.id]" class="mt-2 space-y-2">
                        <textarea v-model="noteDrafts[node.id]" rows="2" class="w-full px-3 py-2 text-sm border border-slate-300 dark:border-slate-600 rounded-md bg-white dark:bg-slate-900" placeholder="Note..."></textarea>
                        <div class="flex justify-end gap-2">
                            <button @click="showAddNote[node.id] = false" class="text-xs text-slate-500 uppercase">Annuler</button>
                            <button @click="addConclusionNote(node.id)" class="px-3 py-1 bg-blue-600 text-white text-xs rounded-md font-bold uppercase">Enregistrer</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
