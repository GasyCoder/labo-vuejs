<script setup>
import { computed } from 'vue';
import GermeCulture from './GermeCulture.vue';

const props = defineProps({
    node: {
        type: Object,
        required: true,
    },
    prescriptionId: {
        type: Number,
        required: true,
    },
    familles: {
        type: Array,
        required: true,
    },
    getFormValue: {
        type: Function,
        required: true,
    },
    setFormValue: {
        type: Function,
        required: true,
    },
    saveStatus: {
        type: Object,
        required: true,
    },
    savedAt: {
        type: Object,
        required: true,
    },
    canEditConclusions: {
        type: Boolean,
        default: false,
    },
    notesState: {
        type: Object,
        required: true,
    },
    noteDrafts: {
        type: Object,
        required: true,
    },
    noteEditId: {
        type: Object,
        required: true,
    },
    noteEditText: {
        type: Object,
        required: true,
    },
    openNotes: {
        type: Object,
        required: true,
    },
    showAddNote: {
        type: Object,
        required: true,
    },
    toggleNotes: {
        type: Function,
        required: true,
    },
    openAddNote: {
        type: Function,
        required: true,
    },
    addConclusionNote: {
        type: Function,
        required: true,
    },
    startEditNote: {
        type: Function,
        required: true,
    },
    updateConclusionNote: {
        type: Function,
        required: true,
    },
    deleteConclusionNote: {
        type: Function,
        required: true,
    },
});

</script>

<template>
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg shadow-sm p-6 mb-4 hover:shadow-md transition-shadow duration-200">
        <!-- Node Header with badges -->
        <div class="flex items-start justify-between mb-4">
            <div class="flex-1">
                <div class="flex items-center gap-3 mb-2 flex-wrap">
                    <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100" :class="{ 'font-bold': node.is_bold }">
                        {{ node.designation }}
                    </h3>
                    <span v-if="saveStatus[node.id]" class="text-[11px] px-2 py-0.5 rounded-full border"
                        :class="{
                            'border-amber-200 bg-amber-50 text-amber-700 dark:bg-amber-900/20 dark:text-amber-300 dark:border-amber-800': saveStatus[node.id] === 'saving',
                            'border-emerald-200 bg-emerald-50 text-emerald-700 dark:bg-emerald-900/20 dark:text-emerald-300 dark:border-emerald-800': saveStatus[node.id] === 'saved',
                            'border-red-200 bg-red-50 text-red-700 dark:bg-red-900/20 dark:text-red-300 dark:border-red-800': saveStatus[node.id] === 'error',
                        }">
                        {{ saveStatus[node.id] === 'saving' ? 'Enregistrement…' : saveStatus[node.id] === 'saved' ? 'Sauvegardé' + (savedAt[node.id] ? ' à ' + savedAt[node.id] : '') : 'Erreur sauvegarde' }}
                    </span>
                    <span v-if="node.type_label" class="inline-flex items-center px-3 py-1 text-xs font-medium bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 rounded-lg">
                        {{ node.type_label }}
                    </span>
                </div>
            </div>
            <!-- Reference values panel -->
            <div v-if="node.valeur_ref || node.unite || node.suffixe" class="flex-shrink-0 ml-4 p-3 bg-slate-50 dark:bg-slate-800/50 rounded-lg border border-slate-200 dark:border-slate-700">
                <div class="text-xs space-y-1">
                    <div v-if="node.valeur_ref" class="flex items-center gap-2">
                        <span class="text-slate-500 dark:text-slate-400">{{ node.valeur_ref_label }}:</span>
                        <span class="font-medium text-green-600 dark:text-green-400">{{ node.valeur_ref }}</span>
                    </div>
                    <div v-if="node.unite" class="flex items-center gap-2">
                        <span class="text-slate-500 dark:text-slate-400">Unité:</span>
                        <span class="font-medium text-slate-700 dark:text-slate-300">{{ node.unite }}</span>
                    </div>
                    <div v-if="node.suffixe" class="flex items-center gap-2">
                        <span class="text-slate-500 dark:text-slate-400">Suffixe:</span>
                        <span class="font-medium text-slate-700 dark:text-slate-300">{{ node.suffixe }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recursive children if present -->
        <div v-if="node.children?.length > 0" class="space-y-4 pl-4 border-l-2 border-slate-200 dark:border-slate-700">
            <AnalyseNode
                v-for="sub in node.children"
                :key="sub.id"
                :node="sub"
                :prescriptionId="prescriptionId"
                :familles="familles"
                :getFormValue="getFormValue"
                :setFormValue="setFormValue"
                :saveStatus="saveStatus"
                :savedAt="savedAt"
                :canEditConclusions="canEditConclusions"
                :notesState="notesState"
                :noteDrafts="noteDrafts"
                :noteEditId="noteEditId"
                :noteEditText="noteEditText"
                :openNotes="openNotes"
                :showAddNote="showAddNote"
                :toggleNotes="toggleNotes"
                :openAddNote="openAddNote"
                :addConclusionNote="addConclusionNote"
                :startEditNote="startEditNote"
                :updateConclusionNote="updateConclusionNote"
                :deleteConclusionNote="deleteConclusionNote"
            />
        </div>

        <!-- Form by type (leaf nodes only) -->
        <div v-else class="space-y-4">
            <!-- LABEL type -->
            <div v-if="node.type === 'LABEL'" class="p-4 bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-lg">
                <p class="text-slate-700 dark:text-slate-200 text-sm leading-relaxed">{{ node.designation }}</p>
            </div>

            <!-- GERME / CULTURE type -->
            <div v-else-if="['GERME', 'CULTURE'].includes(node.type)" class="mt-2">
                <GermeCulture
                    :analyse="node"
                    :prescriptionId="prescriptionId"
                    :familles="familles"
                    :getFormValue="getFormValue"
                    :setFormValue="setFormValue"
                />
            </div>

            <!-- INPUT / DOSAGE / COMPTAGE -->
            <div v-else-if="['INPUT','DOSAGE','COMPTAGE'].includes(node.type)" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="space-y-2">
                    <label class="block text-xs text-slate-700 dark:text-slate-300">
                        Valeur <span v-if="node.unite">({{ node.unite }})</span>
                        <span v-if="node.valeur_ref" class="ml-2 text-green-600 dark:text-green-400 font-medium">({{ node.valeur_ref_label }}: {{ node.valeur_ref }}{{ node.unite ? ' ' + node.unite : '' }})</span>
                    </label>
                    <input type="text" :value="getFormValue(node.id, 'valeur')" @input="setFormValue(node.id, 'valeur', $event.target.value)" placeholder="Entrez une valeur"
                        class="w-full px-4 py-2 bg-white dark:bg-slate-700 border border-slate-300 dark:border-slate-600 rounded-lg text-slate-900 dark:text-slate-100 placeholder-slate-400 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors" />
                </div>
                <div class="space-y-2">
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">Interprétation</label>
                    <select :value="getFormValue(node.id, 'interpretation')" @change="setFormValue(node.id, 'interpretation', $event.target.value)"
                        class="w-full px-4 py-2 bg-white dark:bg-slate-700 border border-slate-300 dark:border-slate-600 rounded-lg text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                        <option value="">Sélectionner</option>
                        <option value="NORMAL">Normal</option>
                        <option value="PATHOLOGIQUE">Pathologique</option>
                    </select>
                </div>
            </div>

            <!-- INPUT_SUFFIXE -->
            <div v-else-if="node.type === 'INPUT_SUFFIXE'" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="space-y-2">
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">
                        Valeur <span v-if="node.unite">({{ node.unite }})</span>
                    </label>
                    <div class="flex">
                        <input type="text" :value="getFormValue(node.id, 'valeur')" @input="setFormValue(node.id, 'valeur', $event.target.value)" placeholder="Entrez une valeur"
                            class="flex-1 px-4 py-2 bg-white dark:bg-slate-700 border border-slate-300 dark:border-slate-600 rounded-l-lg text-slate-900 dark:text-slate-100 placeholder-slate-400 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors" />
                        <span v-if="node.suffixe" class="inline-flex items-center px-3 py-2 bg-slate-100 dark:bg-slate-800 border border-l-0 border-slate-300 dark:border-slate-600 rounded-r-lg text-slate-700 dark:text-slate-300 text-sm font-medium">
                            {{ node.suffixe }}
                        </span>
                    </div>
                </div>
                <div class="space-y-2">
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">Interprétation</label>
                    <select :value="getFormValue(node.id, 'interpretation')" @change="setFormValue(node.id, 'interpretation', $event.target.value)"
                        class="w-full px-4 py-2 bg-white dark:bg-slate-700 border border-slate-300 dark:border-slate-600 rounded-lg text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                        <option value="">Sélectionner</option>
                        <option value="NORMAL">Normal</option>
                        <option value="PATHOLOGIQUE">Pathologique</option>
                    </select>
                </div>
            </div>

            <!-- SELECT / TEST -->
            <div v-else-if="['SELECT','TEST'].includes(node.type)" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="space-y-2">
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">Sélection</label>
                    <div v-if="node.valeurs_predefinies?.length" class="space-y-2">
                        <select :value="getFormValue(node.id, 'resultats')" @change="setFormValue(node.id, 'resultats', $event.target.value)"
                            class="w-full px-4 py-2 bg-white dark:bg-slate-700 border border-slate-300 dark:border-slate-600 rounded-lg text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                            <option value="">Veuillez choisir</option>
                            <option v-for="opt in node.valeurs_predefinies" :key="opt" :value="opt">{{ opt }}</option>
                        </select>
                        <input v-if="getFormValue(node.id, 'resultats') === 'Autre'" type="text" :value="getFormValue(node.id, 'valeur')" @input="setFormValue(node.id, 'valeur', $event.target.value)" placeholder="Précisez..."
                            class="w-full px-4 py-2 bg-white dark:bg-slate-700 border border-slate-300 dark:border-slate-600 rounded-lg text-slate-900 dark:text-slate-100 placeholder-slate-400 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors" />
                    </div>
                    <input v-else type="text" :value="getFormValue(node.id, 'valeur')" @input="setFormValue(node.id, 'valeur', $event.target.value)" placeholder="Entrez une valeur"
                        class="w-full px-4 py-2 bg-white dark:bg-slate-700 border border-slate-300 dark:border-slate-600 rounded-lg text-slate-900 dark:text-slate-100 placeholder-slate-400 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors" />
                </div>
                <div class="space-y-2">
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">Interprétation</label>
                    <select :value="getFormValue(node.id, 'interpretation')" @change="setFormValue(node.id, 'interpretation', $event.target.value)"
                        class="w-full px-4 py-2 bg-white dark:bg-slate-700 border border-slate-300 dark:border-slate-600 rounded-lg text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                        <option value="">Sélectionner</option>
                        <option value="NORMAL">Normal</option>
                        <option value="PATHOLOGIQUE">Pathologique</option>
                    </select>
                </div>
            </div>

            <!-- NEGATIF_POSITIF_1 / NEGATIF_POSITIF_3 -->
            <div v-else-if="['NEGATIF_POSITIF_1','NEGATIF_POSITIF_3'].includes(node.type)" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="space-y-3">
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">Résultat</label>
                    <div class="flex gap-6">
                        <label class="flex items-center gap-3 cursor-pointer group">
                            <input type="radio" value="NEGATIF" :checked="getFormValue(node.id, 'valeur') === 'NEGATIF'" @change="setFormValue(node.id, 'valeur', 'NEGATIF')"
                                class="w-4 h-4 text-green-600 border-slate-300 focus:ring-green-500" />
                            <span class="text-green-600 dark:text-green-400 font-medium text-sm">Négatif</span>
                        </label>
                        <label class="flex items-center gap-3 cursor-pointer group">
                            <input type="radio" value="POSITIF" :checked="getFormValue(node.id, 'valeur') === 'POSITIF'" @change="setFormValue(node.id, 'valeur', 'POSITIF')"
                                class="w-4 h-4 text-red-600 border-slate-300 focus:ring-red-500" />
                            <span class="text-red-600 dark:text-red-400 font-medium text-sm">Positif</span>
                        </label>
                    </div>
                </div>
                <div class="space-y-2">
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">Interprétation</label>
                    <select :value="getFormValue(node.id, 'interpretation')" @change="setFormValue(node.id, 'interpretation', $event.target.value)"
                        class="w-full px-4 py-2 bg-white dark:bg-slate-700 border border-slate-300 dark:border-slate-600 rounded-lg text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                        <option value="">Sélectionner</option>
                        <option value="NORMAL">Normal</option>
                        <option value="PATHOLOGIQUE">Pathologique</option>
                    </select>
                </div>
            </div>

            <!-- NEGATIF_POSITIF_2 -->
            <div v-else-if="node.type === 'NEGATIF_POSITIF_2'" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="space-y-3">
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">Résultat</label>
                    <div class="flex gap-6">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" value="NEGATIF" :checked="getFormValue(node.id, 'valeur') === 'NEGATIF'" @change="setFormValue(node.id, 'valeur', 'NEGATIF')"
                                class="w-4 h-4 text-green-600 border-slate-300 focus:ring-green-500" />
                            <span class="text-green-600 dark:text-green-400 font-medium text-sm">Négatif</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" value="POSITIF" :checked="getFormValue(node.id, 'valeur') === 'POSITIF'" @change="setFormValue(node.id, 'valeur', 'POSITIF')"
                                class="w-4 h-4 text-red-600 border-slate-300 focus:ring-red-500" />
                            <span class="text-red-600 dark:text-red-400 font-medium text-sm">Positif</span>
                        </label>
                    </div>
                </div>
                <div class="space-y-2">
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">Valeur <span v-if="node.unite">({{ node.unite }})</span></label>
                    <input type="text" :value="getFormValue(node.id, 'resultats')" @input="setFormValue(node.id, 'resultats', $event.target.value)" placeholder="Valeur de référence"
                        class="w-full px-4 py-2 bg-white dark:bg-slate-700 border border-slate-300 dark:border-slate-600 rounded-lg text-slate-900 dark:text-slate-100 placeholder-slate-400 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors" />
                </div>
            </div>

            <!-- ABSENCE_PRESENCE_2 -->
            <div v-else-if="node.type === 'ABSENCE_PRESENCE_2'" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="space-y-3">
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">Résultat</label>
                    <div class="flex gap-4">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" value="ABSENCE" :checked="getFormValue(node.id, 'valeur') === 'ABSENCE'" @change="setFormValue(node.id, 'valeur', 'ABSENCE')"
                                class="w-4 h-4 text-green-600 border-slate-300 focus:ring-green-500" />
                            <span class="text-green-600 dark:text-green-400 font-medium text-sm">Absence</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" value="PRESENCE" :checked="getFormValue(node.id, 'valeur') === 'PRESENCE'" @change="setFormValue(node.id, 'valeur', 'PRESENCE')"
                                class="w-4 h-4 text-red-600 border-slate-300 focus:ring-red-500" />
                            <span class="text-red-600 dark:text-red-400 font-medium text-sm">Présence</span>
                        </label>
                    </div>
                </div>
                <div class="space-y-2">
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">Valeur <span v-if="node.unite">({{ node.unite }})</span></label>
                    <input type="text" :value="getFormValue(node.id, 'resultats')" @input="setFormValue(node.id, 'resultats', $event.target.value)" placeholder="Préciser la valeur"
                        class="w-full px-4 py-2 bg-white dark:bg-slate-700 border border-slate-300 dark:border-slate-600 rounded-lg text-slate-900 dark:text-slate-100 placeholder-slate-400 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors" />
                </div>
                <div class="space-y-2">
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">Interprétation</label>
                    <select :value="getFormValue(node.id, 'interpretation')" @change="setFormValue(node.id, 'interpretation', $event.target.value)"
                        class="w-full px-4 py-2 bg-white dark:bg-slate-700 border border-slate-300 dark:border-slate-600 rounded-lg text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                        <option value="">Sélectionner</option>
                        <option value="NORMAL">Normal</option>
                        <option value="PATHOLOGIQUE">Pathologique</option>
                    </select>
                </div>
            </div>

            <!-- LEUCOCYTES -->
            <div v-else-if="node.type === 'LEUCOCYTES'" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="space-y-2">
                    <label class="block text-xs text-slate-700 dark:text-slate-300">Valeur</label>
                    <input type="number" :value="getFormValue(node.id, 'valeur')" @input="setFormValue(node.id, 'valeur', $event.target.value)" placeholder="Valeur"
                        class="w-full px-4 py-2 bg-white dark:bg-slate-700 border border-slate-300 dark:border-slate-600 rounded-lg text-slate-900 dark:text-slate-100 placeholder-slate-400 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors" />
                </div>
                <div class="space-y-2">
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">Polynucléaires (%)</label>
                    <input type="number" min="0" max="100" :value="getFormValue(node.id, 'polynucleaires')" @input="setFormValue(node.id, 'polynucleaires', $event.target.value)"
                        class="w-full px-4 py-2 bg-white dark:bg-slate-700 border border-slate-300 dark:border-slate-600 rounded-lg text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors" />
                </div>
                <div class="space-y-2">
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">Lymphocytes (%)</label>
                    <input type="number" min="0" max="100" :value="getFormValue(node.id, 'lymphocytes')" @input="setFormValue(node.id, 'lymphocytes', $event.target.value)"
                        class="w-full px-4 py-2 bg-white dark:bg-slate-700 border border-slate-300 dark:border-slate-600 rounded-lg text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors" />
                </div>
            </div>

            <!-- FV (Flore Vaginale) -->
            <div v-else-if="node.type === 'FV'" class="bg-gradient-to-r from-pink-50 to-pink-100 dark:from-pink-900/20 dark:to-pink-800/20 border border-pink-200 dark:border-pink-800 rounded-xl p-6">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-10 h-10 bg-pink-500 dark:bg-pink-600 rounded-lg flex items-center justify-center text-white text-lg font-bold shadow-sm">🔬</div>
                    <div>
                        <h4 class="text-lg font-semibold text-pink-800 dark:text-pink-200">Flore Vaginale</h4>
                        <p class="text-sm text-pink-700 dark:text-pink-300">Analyse spécialisée de la flore vaginale</p>
                    </div>
                </div>
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                    <div class="space-y-2">
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">Résultat</label>
                        <div v-if="node.valeurs_predefinies?.length" class="space-y-2">
                            <select :value="getFormValue(node.id, 'resultats')" @change="setFormValue(node.id, 'resultats', $event.target.value)"
                                class="w-full px-4 py-2.5 bg-white dark:bg-slate-700 border border-slate-300 dark:border-slate-600 rounded-lg text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-pink-500 focus:border-pink-500 transition-colors">
                                <option value="">Veuillez choisir</option>
                                <option v-for="opt in node.valeurs_predefinies" :key="opt" :value="opt">{{ opt }}</option>
                            </select>
                            <input v-if="getFormValue(node.id, 'resultats') === 'Autre'" type="text" :value="getFormValue(node.id, 'valeur')" @input="setFormValue(node.id, 'valeur', $event.target.value)" placeholder="Précisez..."
                                class="w-full px-4 py-2 bg-white dark:bg-slate-700 border border-slate-300 dark:border-slate-600 rounded-lg text-slate-900 dark:text-slate-100 placeholder-slate-400 focus:ring-2 focus:ring-pink-500 focus:border-pink-500 transition-colors" />
                        </div>
                        <textarea v-else rows="3" :value="getFormValue(node.id, 'resultats')" @input="setFormValue(node.id, 'resultats', $event.target.value)" placeholder="Décrivez la flore vaginale..."
                            class="w-full px-4 py-3 bg-white dark:bg-slate-700 border border-slate-300 dark:border-slate-600 rounded-lg text-slate-900 dark:text-slate-100 placeholder-slate-400 resize-none focus:ring-2 focus:ring-pink-500 focus:border-pink-500 transition-colors" />
                    </div>
                    <div class="space-y-2">
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">Interprétation</label>
                        <select :value="getFormValue(node.id, 'interpretation')" @change="setFormValue(node.id, 'interpretation', $event.target.value)"
                            class="w-full px-4 py-2.5 bg-white dark:bg-slate-700 border border-slate-300 dark:border-slate-600 rounded-lg text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-pink-500 focus:border-pink-500 transition-colors">
                            <option value="">Sélectionner</option>
                            <option value="NORMAL">Normal</option>
                            <option value="PATHOLOGIQUE">Pathologique</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- DEFAULT: free text or predefined select -->
            <div v-else class="space-y-2">
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">
                    Résultat
                    <span v-if="node.valeur_ref" class="ml-2 text-green-600 dark:text-green-400 font-medium text-xs">({{ node.valeur_ref_label }}: {{ node.valeur_ref }}{{ node.unite ? ' ' + node.unite : '' }})</span>
                </label>
                <div v-if="node.valeurs_predefinies?.length" class="space-y-2">
                    <select :value="getFormValue(node.id, 'resultats')" @change="setFormValue(node.id, 'resultats', $event.target.value)"
                        class="w-full px-4 py-2 bg-white dark:bg-slate-700 border border-slate-300 dark:border-slate-600 rounded-lg text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                        <option value="">Veuillez choisir</option>
                        <option v-for="opt in node.valeurs_predefinies" :key="opt" :value="opt">{{ opt }}</option>
                    </select>
                    <input v-if="getFormValue(node.id, 'resultats') === 'Autre'" type="text" :value="getFormValue(node.id, 'valeur')" @input="setFormValue(node.id, 'valeur', $event.target.value)" placeholder="Précisez..."
                        class="w-full px-4 py-2 bg-white dark:bg-slate-700 border border-slate-300 dark:border-slate-600 rounded-lg text-slate-900 dark:text-slate-100 placeholder-slate-400 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors" />
                </div>
                <textarea v-else rows="4" :value="getFormValue(node.id, 'resultats')" @input="setFormValue(node.id, 'resultats', $event.target.value)" placeholder="Saisie libre…"
                    class="w-full px-4 py-3 bg-white dark:bg-slate-700 border border-slate-300 dark:border-slate-600 rounded-lg text-slate-900 dark:text-slate-100 placeholder-slate-400 resize-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors" />
            </div>
        </div>

        <!-- Notes Partielles -->
        <div v-if="!['LABEL', 'MULTIPLE', 'MULTIPLE_SELECTIF'].includes(node.type)" class="pt-4 border-t border-slate-200 dark:border-slate-700 mt-4 space-y-3">
            <button type="button" @click="toggleNotes(node.id)" class="w-full flex items-center justify-between text-left text-sm font-medium text-slate-700 dark:text-slate-300 hover:text-slate-900 transition-colors">
                <span>Conclusion (optionnelle)</span>
                <span class="inline-flex items-center gap-2 text-xs text-slate-500 dark:text-slate-400">
                    <span v-if="notesState[node.id]?.length" class="px-2 py-0.5 bg-slate-100 dark:bg-slate-800 rounded-full">{{ notesState[node.id].length }} note{{ notesState[node.id].length > 1 ? 's' : '' }}</span>
                    <svg class="w-4 h-4 transition-transform" :class="openNotes[node.id] ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                </span>
            </button>

            <div v-show="openNotes[node.id]" class="space-y-3">
                <!-- List notes -->
                <div v-if="notesState[node.id]?.length" class="space-y-2 mt-2">
                    <div v-for="note in notesState[node.id]" :key="note.id" class="p-3 bg-slate-50 dark:bg-slate-800/50 rounded-lg text-sm border border-slate-200 dark:border-slate-700">
                        <div class="flex justify-between items-start mb-1">
                            <span class="text-xs text-slate-500 dark:text-slate-400">{{ note.created_at }} par {{ note.technicien_name }}</span>
                            <div v-if="canEditConclusions" class="flex gap-2 text-xs font-semibold">
                                <button @click="startEditNote(node.id, note)" class="text-blue-600 dark:text-blue-400 hover:underline">Modifier</button>
                                <button @click="deleteConclusionNote(node.id, note.id)" class="text-red-600 dark:text-red-400 hover:underline">Supprimer</button>
                            </div>
                        </div>
                        <div v-if="noteEditId[node.id] === note.id" class="flex gap-2 mt-2">
                            <input type="text" v-model="noteEditText[node.id]" @keyup.enter="updateConclusionNote(node.id)" class="flex-1 px-2 py-1 text-sm border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-slate-700 dark:text-white" />
                            <button @click="updateConclusionNote(node.id)" class="px-2 py-1 bg-green-600 text-white text-xs rounded-md">✓</button>
                            <button @click="noteEditId[node.id] = null" class="px-2 py-1 bg-gray-500 text-white text-xs rounded-md">✗</button>
                        </div>
                        <p v-else class="text-slate-800 dark:text-slate-200 whitespace-pre-wrap">{{ note.note }}</p>
                    </div>
                </div>

                <!-- Add Button -->
                <div v-if="canEditConclusions" class="pt-2">
                    <button v-show="!showAddNote[node.id]" @click="openAddNote(node.id)" type="button" class="inline-flex items-center gap-2 px-3 py-1.5 text-xs font-medium text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/20 hover:bg-blue-100 dark:hover:bg-blue-900/40 rounded-lg transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                        Ajouter une note
                    </button>
                    
                    <div v-show="showAddNote[node.id]" class="mt-2 bg-white dark:bg-slate-800 p-3 rounded-lg border border-slate-200 dark:border-slate-700 shadow-sm relative">
                        <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-2">Nouvelle note</label>
                        <textarea v-model="noteDrafts[node.id]" rows="3" class="w-full px-3 py-2 text-sm bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-600 rounded-md focus:ring-blue-500" placeholder="Saisissez votre note..."></textarea>
                        <div class="flex justify-end gap-2 mt-2">
                            <button type="button" @click="showAddNote[node.id] = false; noteDrafts[node.id] = ''" class="px-3 py-1.5 text-xs font-medium text-slate-700 dark:text-slate-300 bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 rounded-md transition-colors">Annuler</button>
                            <button type="button" @click="addConclusionNote(node.id)" class="px-3 py-1.5 text-xs font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-md transition-colors">Enregistrer</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
<script>
export default {
    name: 'AnalyseNode'
}
</script>
