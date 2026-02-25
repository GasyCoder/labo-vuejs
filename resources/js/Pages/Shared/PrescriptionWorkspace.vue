<script setup>
import { ref, reactive, watch, computed, onMounted, onUnmounted } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import axios from 'axios';
import Swal from 'sweetalert2';
import AppLayout from '@/Layouts/AppLayout.vue';
import AnalyseNode from './AnalyseNode.vue';
import AnalysesSidebar from './AnalysesSidebar.vue';

const props = defineProps({
    context: String,
    title: String,
    prescription: Object,
    analysesTree: Array,
    resultats: Array,
    familles: Array,
    notes: Object,
    conclusionsGroupes: Object,
    canEditConclusions: Boolean,
});

// ----- State -----
const analysesParents = ref([]);
const canFinalize = ref(false);
const isReadyToFinalize = ref(false);
const isFinalizing = ref(false);
const groupSaveStatus = ref('idle'); // 'idle'|'saving'|'saved'|'error'
const groupSavedAt = ref(null);
const mainPanelRef = ref(null);
const showScrollTop = ref(false);

const selectedParentId = ref(null);
const saveStatus = reactive({}); // { [analyseId]: 'saving'|'saved'|'error' }
const savedAt = reactive({});
const formData = reactive({}); // { [analyseId]: { valeur, resultats, interpretation } }

// Notes & Conclusions state
const notesState = reactive(props.notes || {});
const noteDrafts = reactive({});
const noteEditId = reactive({});
const noteEditText = reactive({});
const openNotes = reactive({});
const showAddNote = reactive({});
const groupConclusions = reactive(props.conclusionsGroupes || {});

const toggleNotes = (id) => {
    openNotes[id] = !openNotes[id];
};
const openAddNote = (id) => {
    openNotes[id] = true;
    showAddNote[id] = true;
};

// Initialize form data
const initFormData = () => {
    if (!props.resultats) return;
    props.resultats.forEach(r => {
        formData[r.analyse_id] = {
            valeur: r.valeur || '',
            resultats: r.resultats || '',
            interpretation: r.interpretation || 'NORMAL',
        };
    });
};
initFormData();

const initSidebarData = () => {
    // Determine initially the analyses parents from props analysesTree
    analysesParents.value = props.analysesTree.map(node => {
        const { children, ...parentProps } = node;
        return parentProps;
    });

    canFinalize.value = analysesParents.value.every(a => a.status === 'TERMINE');
    isReadyToFinalize.value = analysesParents.value.every(a => a.status === 'TERMINE' || a.can_complete);

    // Auto-select first if none selected
    if (analysesParents.value.length > 0 && !selectedParentId.value) {
        selectedParentId.value = analysesParents.value[0].id;
    }
};
const handleScroll = () => {
    // Check both window scroll and main panel scroll
    const windowScroll = window.scrollY || document.documentElement.scrollTop;
    const panelScroll = mainPanelRef.value?.scrollTop || 0;
    showScrollTop.value = windowScroll > 300 || panelScroll > 300;
};

const scrollToTop = () => {
    window.scrollTo({ top: 0, behavior: 'smooth' });
    if (mainPanelRef.value) {
        mainPanelRef.value.scrollTo({ top: 0, behavior: 'smooth' });
    }
};

onMounted(() => {
    initSidebarData();
    window.addEventListener('scroll', handleScroll, true); // true = capture phase to catch all scroll events
});

onUnmounted(() => {
    window.removeEventListener('scroll', handleScroll, true);
});

// ----- Computed -----
const selectedParent = computed(() => {
    if (!selectedParentId.value) return null;
    return props.analysesTree.find(a => a.id === selectedParentId.value);
});

// ----- Methods -----
const selectParent = (id) => {
    selectedParentId.value = id;
};

const getFormValue = (analyseId, field) => {
    return formData[analyseId]?.[field] ?? (field === 'interpretation' ? 'NORMAL' : '');
};

const setFormValue = (analyseId, field, value) => {
    if (!formData[analyseId]) {
        formData[analyseId] = { valeur: '', resultats: '', interpretation: 'NORMAL' };
    }
    formData[analyseId][field] = value;
    debouncedSave(analyseId);
};

// Auto-Save
const saveTimers = {};
const debouncedSave = (analyseId) => {
    clearTimeout(saveTimers[analyseId]);
    saveStatus[analyseId] = 'saving';

    saveTimers[analyseId] = setTimeout(() => {
        autoSave(analyseId);
    }, 1000);
};

const autoSave = async (analyseId) => {
    const data = formData[analyseId];
    if (!data) return;

    try {
        const response = await axios.post(route('technicien.resultats.save'), {
            prescription_id: props.prescription.id,
            analyse_id: analyseId,
            valeur: data.valeur || null,
            resultats: data.resultats || null,
            interpretation: data.interpretation || 'NORMAL',
        });
        const result = response.data;
        if (result.success) {
            saveStatus[analyseId] = 'saved';
            savedAt[analyseId] = result.saved_at;
            refreshProgression();
        } else {
            saveStatus[analyseId] = 'error';
        }
    } catch (e) {
        saveStatus[analyseId] = 'error';
    }
};

const refreshProgression = async () => {
    try {
        const response = await axios.get(route('technicien.prescription.progression', props.prescription.id));
        if (response.data.success) {
            analysesParents.value = response.data.analysesParents;
            canFinalize.value = response.data.canFinalize;
            isReadyToFinalize.value = response.data.isReadyToFinalize;
            // Also update the full tree status so the main panel UI reflects changes if needed
            response.data.analysesParents.forEach(updatedParent => {
                const treeNode = props.analysesTree.find(n => n.id === updatedParent.id);
                if (treeNode) {
                    treeNode.status = updatedParent.status;
                    treeNode.enfants_completed = updatedParent.enfants_completed;
                }
            });
        }
    } catch (e) {
        console.error("Erreur refresh progression", e);
    }
};

// Notes Logic
const addConclusionNote = async (analyseId) => {
    const text = noteDrafts[analyseId];
    if (!text || text.trim() === '') return;

    try {
        const response = await axios.post(route('technicien.resultats.addNote'), {
            prescription_id: props.prescription.id, analyse_id: analyseId, note: text.trim() 
        });
        const result = response.data;
        if (result.success) {
            if(!notesState[analyseId]) notesState[analyseId] = [];
            notesState[analyseId].push(result.note);
            noteDrafts[analyseId] = '';
            showAddNote[analyseId] = false;
            openNotes[analyseId] = true;
        }
    } catch (e) { console.error('Erreur add note', e); }
};

const updateConclusionNote = async (analyseId) => {
    const id = noteEditId[analyseId];
    const text = noteEditText[analyseId];
    if (!id || !text || text.trim() === '') return;

    try {
        const response = await axios.put(route('technicien.resultats.updateNote', id), {
            note: text.trim() 
        });
        const result = response.data;
        if (result.success) {
            const index = notesState[analyseId].findIndex(n => n.id === id);
            if (index > -1) notesState[analyseId][index].note = text.trim();
            noteEditId[analyseId] = null;
        }
    } catch (e) { console.error('Erreur update note', e); }
};

const deleteConclusionNote = async (analyseId, noteId) => {
    if (!confirm('Voulez-vous supprimer cette note ?')) return;
    try {
        const response = await axios.delete(route('technicien.resultats.deleteNote', noteId));
        const result = response.data;
        if (result.success) {
            notesState[analyseId] = notesState[analyseId].filter(n => n.id !== noteId);
        }
    } catch (e) { console.error('Erreur delete note', e); }
};

const startEditNote = (analyseId, note) => {
    noteEditId[analyseId] = note.id;
    noteEditText[analyseId] = note.note;
};

// Group Conclusions
const groupConclusionTimers = {};
const saveGroupConclusion = (examenId) => {
    if (!examenId) return;
    clearTimeout(groupConclusionTimers[examenId]);
    groupConclusionTimers[examenId] = setTimeout(async () => {
        try {
            await axios.post(route('technicien.resultats.groupConclusion'), {
                prescription_id: props.prescription.id,
                examen_id: examenId,
                conclusion: groupConclusions[examenId] || ''
            });
        } catch (e) { console.error('Erreur save group conclusion', e); }
    }, 1000);
};

// Finalize 
const finalizePrescription = async () => {
    const result = await Swal.fire({
        title: 'Finaliser les traitements ?',
        text: 'Voulez-vous finaliser cette prescription ? Toutes les analyses seront marquées comme terminées.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Oui, finaliser !',
        cancelButtonText: 'Annuler'
    });

    if (!result.isConfirmed) return;

    isFinalizing.value = true;
    try {
        const response = await axios.post(route('technicien.prescription.finalize', props.prescription.id));
        if (response.data.success) {
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: 'Traitements finalisés avec succès',
                showConfirmButton: false,
                timer: 3000
            });
            router.visit(route('technicien.index'));
        }
    } catch (e) {
        if (e.response && e.response.status === 422) {
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'error',
                title: 'Analyses incomplètes',
                text: e.response.data.message || 'Certaines analyses (groupes) manquent de résultats.',
                showConfirmButton: false,
                timer: 5000
            });
        } else {
            console.error('Erreur finalize', e);
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'error',
                title: 'Erreur',
                text: 'Une erreur est survenue lors de la finalisation.',
                showConfirmButton: false,
                timer: 3000
            });
        }
    } finally {
        isFinalizing.value = false;
    }
};

// Flatten all children IDs from a tree of analyse nodes
const flattenChildIds = (nodes) => {
    const ids = [];
    const walk = (list) => {
        for (const node of list) {
            ids.push(node.id);
            if (node.children && node.children.length > 0) {
                walk(node.children);
            }
        }
    };
    walk(nodes);
    return ids;
};

// Manual save all results for the current group (legacy parity)
const saveAllForGroup = async () => {
    if (!selectedParent.value) return;
    const children = selectedParent.value.children || [];
    const allIds = flattenChildIds(children);
    const results = allIds
        .filter(id => formData[id])
        .map(id => ({ analyse_id: id, ...formData[id] }));
    if (results.length === 0) return;

    groupSaveStatus.value = 'saving';
    try {
        const response = await axios.post(route('technicien.resultats.saveAll'), {
            prescription_id: props.prescription.id,
            results,
        });
        if (response.data.success) {
            groupSaveStatus.value = 'saved';
            groupSavedAt.value = response.data.saved_at;
            Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Résultats enregistrés', showConfirmButton: false, timer: 2000 });
            refreshProgression();
        } else {
            groupSaveStatus.value = 'error';
        }
    } catch (e) {
        groupSaveStatus.value = 'error';
        Swal.fire({ toast: true, position: 'top-end', icon: 'error', title: "Erreur d'enregistrement", showConfirmButton: false, timer: 3000 });
    }
};

const statusBadge = (status) => {
    switch (status) {
        case 'TERMINE': return { label: 'Terminé', class: 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300' };
        case 'EN_COURS': return { label: 'En cours', class: 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300' };
        case 'EN_ATTENTE': return { label: 'En attente', class: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/40 dark:text-yellow-300' };
        default: return { label: 'Vide', class: 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400' };
    }
};
</script>

<template>
    <Head :title="title" />

    <AppLayout>
        <div class="flex flex-col h-full">
            <!-- Header (legacy-faithful) -->
            <div class="bg-white dark:bg-slate-900 shadow-lg border-b border-slate-200 dark:border-slate-800 flex-shrink-0">
                <div class="px-6 py-4 lg:py-6 space-y-4 lg:space-y-6">
                    <!-- Row 1: Reference + Patient Info -->
                    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                        <!-- Reference & Icon -->
                        <div class="flex items-center gap-4">
                            <div class="flex items-center justify-center w-12 h-12 bg-gradient-to-br from-blue-500 to-blue-600 dark:from-blue-600 dark:to-blue-700 rounded-xl shadow-md">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </div>
                            <div>
                                <h1 class="text-xl lg:text-2xl font-bold text-slate-900 dark:text-slate-100">{{ prescription.reference }}</h1>
                                <p class="text-slate-600 dark:text-slate-400 text-sm">Détail de la prescription médicale</p>
                            </div>
                        </div>
                        <!-- Patient Info -->
                        <div class="text-right space-y-1 hidden sm:block">
                            <div class="flex items-center justify-end gap-2 text-lg lg:text-xl font-semibold text-slate-900 dark:text-slate-100">
                                <svg class="w-5 h-5 text-slate-700 dark:text-slate-300" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zM4 20c0-2.21 3.58-4 8-4s8 1.79 8 4v1H4v-1z" />
                                </svg>
                                <span>{{ prescription.patient?.civilite }} {{ prescription.patient?.nom }} {{ prescription.patient?.prenom }}</span>
                            </div>
                            <div v-if="prescription.patient?.age" class="text-slate-600 dark:text-slate-400 text-sm">
                                Âgé de : {{ prescription.patient.age }} {{ prescription.patient.unite_age || 'Ans' }}
                            </div>
                            <div v-if="prescription.patient?.numero_dossier" class="text-slate-600 dark:text-slate-400 text-sm">
                                Num dossier : {{ prescription.patient.numero_dossier }}
                            </div>
                        </div>
                    </div>
                    <!-- Row 2: Back Button + Status -->
                    <div class="flex flex-wrap items-center justify-between gap-4 border-t border-slate-200 dark:border-slate-700/50 pt-4">
                        <div class="flex gap-3">
                            <Link :href="route('technicien.index')"
                                class="inline-flex items-center gap-2 px-5 py-2.5 text-sm font-medium text-slate-700 dark:text-slate-200 bg-transparent hover:bg-slate-200 dark:hover:bg-slate-800 border border-slate-300 dark:border-slate-600 rounded-lg transition-all duration-200 shadow-sm">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                                </svg>
                                Retour
                            </Link>
                        </div>
                        <div class="flex gap-3 items-center">
                            <div class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg text-sm font-semibold border"
                                :class="statusBadge(prescription.status).class">
                                <div class="w-2.5 h-2.5 rounded-full"
                                    :class="{
                                        'bg-yellow-500': prescription.status === 'EN_ATTENTE',
                                        'bg-blue-500': prescription.status === 'EN_COURS',
                                        'bg-green-500': prescription.status === 'TERMINE',
                                        'bg-slate-500': !['EN_ATTENTE','EN_COURS','TERMINE'].includes(prescription.status),
                                    }" />
                                {{ statusBadge(prescription.status).label }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Split-Screen Layout -->
            <div class="flex flex-1 overflow-hidden">
                <!-- Sidebar -->
                <aside class="w-full md:w-80 lg:w-96 flex-shrink-0 transition-all duration-300" 
                    :class="{'hidden md:block': selectedParentId}">
                    <AnalysesSidebar
                        :analyses-parents="analysesParents"
                        :selected-parent-id="selectedParentId"
                        :is-ready-to-finalize="isReadyToFinalize"
                        :can-finalize-prescription="canFinalize"
                        :is-finalizing="isFinalizing"
                        @select-parent="selectParent"
                        @finalize="finalizePrescription"
                    />
                </aside>

                <!-- Sub-panel mobile back button overlay -->
                <div v-if="selectedParentId" class="md:hidden bg-slate-100 border-b border-gray-200 px-4 py-3 flex items-center">
                    <button @click="selectedParentId = null" class="inline-flex items-center text-blue-600 font-medium">
                        <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                        Retour aux groupes
                    </button>
                </div>

                <!-- Main Panel -->
                <main ref="mainPanelRef" class="flex-1 overflow-y-auto bg-gray-50 dark:bg-slate-900/50 flex flex-col relative" :class="{'hidden md:flex': !selectedParentId}">
                    <!-- Selected analyse form -->
                    <template v-if="selectedParent">
                        <div class="flex-1 p-4 lg:p-8 max-w-5xl mx-auto w-full relative">
                            <!-- Form Header -->
                            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-200 dark:border-slate-700 mb-6 overflow-hidden">
                                <div class="bg-gray-50/50 dark:bg-slate-800/50 border-b border-gray-200 dark:border-slate-700 p-5 lg:p-6">
                                    <div class="flex items-center gap-4">
                                        <div class="w-10 h-10 bg-indigo-100 text-indigo-700 dark:bg-indigo-900/50 dark:text-indigo-300 rounded-lg flex items-center justify-center font-bold text-lg shadow-inner">
                                            {{ selectedParent.code ? selectedParent.code.substring(0, 2) : 'AN' }}
                                        </div>
                                        <div class="flex-1">
                                            <h3 class="text-xl font-bold text-gray-900 dark:text-slate-100 mb-1">
                                                {{ selectedParent.designation }}
                                            </h3>
                                            <div class="flex items-center gap-3 text-sm text-gray-500 dark:text-slate-400">
                                                <span class="inline-flex items-center gap-1">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                                    </svg>
                                                    {{ selectedParent.enfants_count }} résultat(s) attendu(s)
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Analyse Nodes using Recursive Component -->
                                <div class="p-6 space-y-6">
                                    <template v-for="child in selectedParent.children" :key="child.id">
                                        <AnalyseNode
                                            :node="child"
                                            :prescriptionId="prescription.id"
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
                                    </template>
                                </div>
                            </div>

                            <!-- Conclusion Groupe -->
                            <div class="mt-8 pt-8 border-t border-slate-200 dark:border-slate-700/80">
                                <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-200 dark:border-slate-700 p-6">
                                    <div class="flex items-center gap-2 mb-4">
                                        <svg class="w-5 h-5 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                        </svg>
                                        <h4 class="text-base font-semibold text-slate-900 dark:text-slate-100">Conclusion générale d'examen (Optionnelle)</h4>
                                    </div>
                                    <textarea :disabled="!canEditConclusions" v-model="groupConclusions[selectedParent.examen_id]" @input="saveGroupConclusion(selectedParent.examen_id)" rows="4"
                                        class="w-full px-4 py-3 bg-gray-50 dark:bg-slate-900/50 border border-slate-300 dark:border-slate-600 rounded-lg text-slate-900 dark:text-slate-100 placeholder-slate-400 dark:placeholder-slate-500 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors resize-y"
                                        placeholder="Saisissez une synthèse globale pour valider ou expliquer cet ensemble de résultats..."></textarea>
                                    <div class="flex justify-between items-center mt-3">
                                        <p class="text-[12px] font-medium text-slate-500 dark:text-slate-400 flex items-center gap-1">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"></path></svg>
                                            Les modifications sont sauvegardées automatiquement.
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Save Footer (legacy parity — Enregistrer button) -->
                            <div class="sticky bottom-0 mt-6 bg-white dark:bg-slate-800 border-t border-gray-200 dark:border-slate-700 rounded-b-xl shadow-lg z-10">
                                <div class="flex items-center justify-between px-6 py-4">
                                    <div class="text-xs text-slate-500 dark:text-slate-400 flex items-center gap-1.5">
                                        <template v-if="groupSaveStatus === 'saving'">
                                            <svg class="animate-spin w-3.5 h-3.5" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                            </svg>
                                            <span>Enregistrement en cours...</span>
                                        </template>
                                        <template v-else-if="groupSaveStatus === 'saved'">
                                            <svg class="w-3.5 h-3.5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                            </svg>
                                            <span>Sauvegardé à {{ groupSavedAt }}</span>
                                        </template>
                                        <template v-else-if="groupSaveStatus === 'error'">
                                            <span class="text-red-500">Erreur de sauvegarde</span>
                                        </template>
                                        <template v-else>
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"></path></svg>
                                            <span>Auto-save actif</span>
                                        </template>
                                    </div>
                                    <button @click="saveAllForGroup"
                                        :disabled="groupSaveStatus === 'saving'"
                                        class="inline-flex items-center gap-2 px-6 py-2.5 bg-blue-600 hover:bg-blue-700 dark:bg-blue-700 dark:hover:bg-blue-600 disabled:opacity-50 disabled:cursor-not-allowed text-white text-sm font-medium rounded-lg transition-colors duration-200 shadow-sm">
                                        <svg v-if="groupSaveStatus !== 'saving'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                        </svg>
                                        <svg v-else class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        Enregistrer
                                    </button>
                                </div>
                            </div>
                        </div>

                    </template>

                    <!-- Empty state -->
                    <template v-else>
                        <div class="flex flex-col items-center justify-center h-full text-center px-4">
                            <div class="w-20 h-20 mb-6 bg-white dark:bg-slate-800 shadow-sm border border-gray-100 dark:border-slate-700 rounded-2xl flex items-center justify-center">
                                <svg class="w-10 h-10 text-gray-400 dark:text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </div>
                            <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">Workspace Technique</h3>
                            <p class="text-base text-gray-500 dark:text-gray-400 max-w-md mx-auto">
                                Sélectionnez un groupe d'analyses dans le panneau latéral gauche pour commencer à saisir ou consulter les résultats.
                            </p>
                            <div class="mt-8 hidden md:flex items-center gap-3">
                                <div class="w-12 h-0.5 bg-gray-200 dark:bg-slate-700"></div>
                                <span class="text-sm font-medium text-gray-400 dark:text-slate-500">Prêt à démarrer</span>
                                <div class="w-12 h-0.5 bg-gray-200 dark:bg-slate-700"></div>
                            </div>
                        </div>
                    </template>
                </main>

                <!-- Scroll to top button -->
                <Transition enter-active-class="transition ease-out duration-200" enter-from-class="opacity-0 translate-y-2" enter-to-class="opacity-100 translate-y-0" leave-active-class="transition ease-in duration-150" leave-from-class="opacity-100 translate-y-0" leave-to-class="opacity-0 translate-y-2">
                    <button v-if="showScrollTop" @click="scrollToTop" class="fixed bottom-24 right-8 z-[9999] px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-full shadow-xl flex items-center gap-2 transition-colors ring-2 ring-white/20" title="Retour en haut">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 15l7-7 7 7"></path></svg>
                        <span class="text-sm font-medium">En Haut</span>
                    </button>
                </Transition>
            </div>
        </div>
    </AppLayout>
</template>
