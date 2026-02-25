<script setup>
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';
const props = defineProps({
    analyse: Object,
    prescriptionId: Number,
    familles: Array,
    getFormValue: Function,
    setFormValue: Function,
});

// Standard options
const standardOptionsList = [
    { value: 'non-rechercher', label: 'Non rechercher' },
    { value: 'en-cours', label: 'En cours' },
    { value: 'culture-sterile', label: 'Culture stérile' },
    { value: 'absence-germe-pathogene', label: 'Absence de germe pathogène' },
    { value: 'Autre', label: 'Autre (préciser)' }
];

// Computed Selection
const getSelectedOptionsArray = () => {
    let res = props.getFormValue(props.analyse.id, 'resultats');
    if (!res) return [];
    if (typeof res === 'string') {
        try {
            res = JSON.parse(res);
        } catch(e) {
            res = [];
        }
    }
    return Array.isArray(res) ? res : [];
};

const selectedOptions = computed(() => getSelectedOptionsArray());
const autreValeur = computed(() => props.getFormValue(props.analyse.id, 'valeur') || '');

const bacteriesSelectionnees = computed(() => {
    return selectedOptions.value
        .filter(o => o.startsWith('bacterie-'))
        .map(o => parseInt(o.replace('bacterie-', '')));
});

const hasStandardOption = computed(() => {
    return selectedOptions.value.some(opt => standardOptionsList.map(s => s.value).includes(opt));
});

const isDirty = ref(false); // True if bacteria selection changed and needs sync

// Actions
const clearGermeSelection = () => {
    if(!confirm("Êtes-vous sûr de vouloir réinitialiser cette analyse bactériologique ?")) return;
    props.setFormValue(props.analyse.id, 'resultats', JSON.stringify([]));
    props.setFormValue(props.analyse.id, 'valeur', '');
    isDirty.value = true;
};

const toggleStandardOption = (opt) => {
    let current = [...selectedOptions.value];
    if (current.includes(opt)) {
        current = current.filter(o => o !== opt);
    } else {
        current.push(opt);
    }
    props.setFormValue(props.analyse.id, 'resultats', JSON.stringify(current));
};

const updateAutreValeur = (val) => {
    props.setFormValue(props.analyse.id, 'valeur', val);
};

const toggleBacterieOption = (bacterieId) => {
    if (hasStandardOption.value) return;
    const optName = 'bacterie-' + bacterieId;
    let current = [...selectedOptions.value];
    if (current.includes(optName)) {
        current = current.filter(o => o !== optName);
    } else {
        current.push(optName);
    }
    props.setFormValue(props.analyse.id, 'resultats', JSON.stringify(current));
    isDirty.value = true;
};

const isSyncing = ref(false);
const antibiogrammesData = ref({}); // { [bacterieId]: data }
const loadStates = ref({}); // { [bacterieId]: boolean }

const syncAntibiogrammes = async () => {
    isSyncing.value = true;
    try {
        const response = await axios.post(route('technicien.resultats.antibiogrammes.sync'), {
            prescription_id: props.prescriptionId,
            analyse_id: props.analyse.id,
            bacteries: bacteriesSelectionnees.value
        });
        const result = response.data;
        if (result.success) {
            isDirty.value = false;
            // Reload all antibiogrammes data
            for (const bacId of bacteriesSelectionnees.value) {
                await fetchAntibiogrammeData(bacId);
            }
        }
    } catch (e) {
        console.error('Erreur sync antibiogrammes', e);
    } finally {
        isSyncing.value = false;
    }
};

const fetchAntibiogrammeData = async (bacterieId) => {
    loadStates.value[bacterieId] = true;
    try {
        const response = await axios.post(route('technicien.resultats.antibiogrammes.data'), {
            prescription_id: props.prescriptionId,
            analyse_id: props.analyse.id,
            bacterie_id: bacterieId
        });
        const result = response.data;
        if (result.success) {
            antibiogrammesData.value[bacterieId] = result;
        }
    } catch (e) {
        console.error('Erreur fetch antibiogramme data', e);
    } finally {
        loadStates.value[bacterieId] = false;
    }
};

onMounted(() => {
    if (bacteriesSelectionnees.value.length > 0) {
        syncAntibiogrammes();
    }
});

const openAccordionIds = ref([]);
const toggleAccordion = (bacterieId) => {
    if (openAccordionIds.value.includes(bacterieId)) {
        openAccordionIds.value = openAccordionIds.value.filter(id => id !== bacterieId);
    } else {
        openAccordionIds.value.push(bacterieId);
    }
};

// Form state for new antibiotique inside the accordion
const newAntibiotiqueInput = ref({});
const newInterpretationInput = ref({});
const newDiametreInput = ref({});

const addAntibiotique = async (bacterieId) => {
    const abId = newAntibiotiqueInput.value[bacterieId];
    const inter = newInterpretationInput.value[bacterieId] || 'S';
    const diam = newDiametreInput.value[bacterieId] || '';

    if (!abId) return alert('Veuillez sélectionner un antibiotique');

    try {
        const response = await axios.post(route('technicien.resultats.antibiogrammes.add'), {
            prescription_id: props.prescriptionId,
            analyse_id: props.analyse.id,
            bacterie_id: bacterieId,
            antibiotique_id: abId,
            interpretation: inter,
            diametre_mm: diam
        });
        const result = response.data;
        if (result.success) {
            newAntibiotiqueInput.value[bacterieId] = '';
            newInterpretationInput.value[bacterieId] = 'S';
            newDiametreInput.value[bacterieId] = '';
            await fetchAntibiogrammeData(bacterieId);
        } else {
            alert('Erreur: ' + (result.error || 'inconnue'));
        }
    } catch(e) {
        console.error(e);
    }
};

const updateResultatAntibiotique = async (bacterieId, resultatId, field, value) => {
    try {
        const response = await axios.put(route('technicien.resultats.antibiogrammes.update', resultatId), {
            field, value 
        });
        const result = response.data;
        if (result.success) {
            await fetchAntibiogrammeData(bacterieId);
        }
    } catch (e) { console.error(e); }
};

const removeResultatAntibiotique = async (bacterieId, resultatId) => {
    if (!confirm('Êtes-vous sûr de vouloir retirer cet antibiotique ?')) return;
    try {
        const response = await axios.delete(route('technicien.resultats.antibiogrammes.delete', resultatId));
        const result = response.data;
        if (result.success) {
            await fetchAntibiogrammeData(bacterieId);
        }
    } catch(e) { console.error(e); }
};
</script>

<template>
<div class="space-y-6 pt-2">
    <!-- Action Barre -->
    <div class="flex justify-end mb-2">
        <button v-if="selectedOptions.length || autreValeur" @click="clearGermeSelection"
            class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium text-red-600 dark:text-red-400 hover:text-red-700 dark:hover:text-red-300 hover:bg-red-50 dark:hover:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg transition-all duration-200">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
            </svg>
            Réinitialiser la sélection
        </button>
    </div>

    <!-- Options standards -->
    <div>
        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Options standards (exclusives)</label>
        <div class="flex flex-wrap gap-2">
            <button v-for="opt in standardOptionsList" :key="opt.value" @click="toggleStandardOption(opt.value)"
                class="px-3 py-1.5 rounded-full text-xs font-medium border transition"
                :class="selectedOptions.includes(opt.value) ? 'bg-yellow-600 text-white border-yellow-700' : 'bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 border-slate-300 dark:border-slate-600 hover:bg-slate-50 dark:hover:bg-slate-700'">
                {{ opt.label }}
            </button>
        </div>
    </div>

    <!-- Champ "Autre" -->
    <div v-if="selectedOptions.includes('Autre')" class="p-4 bg-white dark:bg-slate-800 rounded-lg border border-yellow-200 dark:border-yellow-700">
        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Précisez la bactérie non listée</label>
        <input type="text" :value="autreValeur" @input="updateAutreValeur($event.target.value)"
            class="w-full px-4 py-2.5 bg-white dark:bg-slate-700 border border-slate-300 dark:border-slate-600 rounded-lg text-slate-900 dark:text-slate-100 placeholder-slate-400 focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500 transition-colors"
            placeholder="Ex: Enterococcus faecalis, Candida albicans..." />
    </div>

    <!-- Sélection des bactéries -->
    <div class="space-y-4">
        <template v-for="famille in familles" :key="famille.id">
            <div v-if="famille.bacteries && famille.bacteries.length > 0" class="p-4 bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-xl">
                <div class="text-sm font-semibold text-slate-700 dark:text-slate-300 mb-3 flex items-center gap-2">
                    {{ famille.designation }}
                </div>
                <div class="flex flex-wrap gap-2">
                    <button v-for="bac in famille.bacteries" :key="bac.id" @click="toggleBacterieOption(bac.id)"
                        :disabled="hasStandardOption"
                        class="px-3 py-1.5 rounded-full text-xs font-medium border transition"
                        :class="selectedOptions.includes('bacterie-' + bac.id) ? 'bg-green-600 text-white border-green-700 shadow-sm' : (hasStandardOption ? 'bg-slate-100 dark:bg-slate-900/40 text-slate-400 border-slate-200 opacity-60 cursor-not-allowed' : 'bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 border-slate-300 dark:border-slate-600 hover:bg-slate-100 hover:border-slate-400')">
                        {{ bac.designation }}
                    </button>
                </div>
            </div>
        </template>
    </div>

    <!-- Antibiogrammes -->
    <div v-if="bacteriesSelectionnees.length > 0 && !hasStandardOption" class="border-t border-yellow-200 dark:border-yellow-800 pt-6">
        <div class="flex items-center justify-between gap-3 mb-4">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 bg-green-500 dark:bg-green-600 rounded-lg flex items-center justify-center text-white text-sm font-bold shadow-sm">🦠</div>
                <div>
                    <h5 class="text-base font-semibold text-green-800 dark:text-green-200">Antibiogrammes ({{ bacteriesSelectionnees.length }})</h5>
                    <p class="text-xs text-green-700 dark:text-green-300">Cliquez sur Synchroniser pour charger les tableaux.</p>
                </div>
            </div>
            <button @click="syncAntibiogrammes" :disabled="isSyncing"
                class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 disabled:opacity-60 text-white text-sm font-medium rounded-lg transition-colors shadow-sm">
                {{ isSyncing ? 'Synchronisation…' : 'Synchroniser' }}
            </button>
        </div>

        <div v-if="isDirty" class="p-4 bg-orange-50 dark:bg-orange-900/20 border border-orange-200 dark:border-orange-800 text-orange-800 dark:text-orange-300 rounded-lg text-sm">
            ⚠️ Sélection modifiée — veuillez cliquer sur <strong>Synchroniser</strong> pour mettre à jour les antibiogrammes.
        </div>
        <div v-else class="space-y-4">
            <div v-for="(bacId, index) in bacteriesSelectionnees" :key="bacId" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl overflow-hidden shadow-sm">
                <!-- Accordion Header -->
                <button @click="toggleAccordion(bacId)" class="w-full px-6 py-4 bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 border-b border-green-100 dark:border-green-800 text-left hover:from-green-100 dark:hover:from-green-900/40 transition-colors focus:outline-none">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-6 h-6 bg-green-500 dark:bg-green-600 rounded-md flex items-center justify-center text-white text-xs font-bold">🦠</div>
                            <div>
                                <h6 class="font-semibold text-green-800 dark:text-green-200">Bactérie ID: {{ bacId }}</h6>
                            </div>
                        </div>
                        <svg class="w-5 h-5 text-green-600 transition-transform duration-200" :class="{'rotate-180': openAccordionIds.includes(bacId)}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                    </div>
                </button>

                <!-- Accordion Body -->
                <div v-show="openAccordionIds.includes(bacId) || index === 0" class="p-0 bg-slate-50 dark:bg-slate-900">
                    <div v-if="loadStates[bacId]" class="p-6 text-center text-sm text-slate-500"><div class="w-6 h-6 border-2 border-green-500 border-t-transparent rounded-full animate-spin mx-auto mb-2"></div>Chargement des données...</div>
                    <div v-else-if="antibiogrammesData[bacId]" class="p-4">
                        <!-- Add Form -->
                        <div class="flex flex-wrap gap-3 items-end mb-4 bg-white dark:bg-slate-800 p-4 rounded-lg border border-slate-200 dark:border-slate-700">
                            <div class="flex-1 min-w-[200px]">
                                <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Antibiotique</label>
                                <select v-model="newAntibiotiqueInput[bacId]" class="w-full px-3 py-2 bg-white dark:bg-slate-700 border border-slate-300 dark:border-slate-600 rounded-md text-sm dark:text-white">
                                    <option value="">Choisir...</option>
                                    <option v-for="ab in antibiogrammesData[bacId].antibiotiquesDisponibles" :key="ab.id" :value="ab.id">{{ ab.designation }}</option>
                                </select>
                            </div>
                            <div class="w-24">
                                <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Interprétation</label>
                                <select v-model="newInterpretationInput[bacId]" class="w-full px-3 py-2 bg-white dark:bg-slate-700 border border-slate-300 dark:border-slate-600 rounded-md text-sm dark:text-white">
                                    <option value="S">S</option><option value="I">I</option><option value="R">R</option>
                                </select>
                            </div>
                            <div class="w-24">
                                <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Ø (mm)</label>
                                <input type="number" v-model="newDiametreInput[bacId]" step="0.1" class="w-full px-3 py-2 bg-white dark:bg-slate-700 border border-slate-300 dark:border-slate-600 rounded-md text-sm dark:text-white" placeholder="15.5" />
                            </div>
                            <button @click="addAntibiotique(bacId)" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-md text-sm font-medium transition-colors">Ajouter</button>
                        </div>

                        <!-- Results Table -->
                        <div v-if="antibiogrammesData[bacId].resultats.length > 0" class="overflow-x-auto rounded-lg border border-slate-200 dark:border-slate-700">
                            <table class="w-full text-sm text-left">
                                <thead class="bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-xs uppercase font-semibold">
                                    <tr>
                                        <th class="px-4 py-3">Antibiotique</th>
                                        <th class="px-4 py-3 text-center">Interprétation</th>
                                        <th class="px-4 py-3 text-center">Diamètre (mm)</th>
                                        <th class="px-4 py-3 text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200 dark:divide-slate-700 bg-white dark:bg-slate-900">
                                    <tr v-for="res in antibiogrammesData[bacId].resultats" :key="res.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/50">
                                        <td class="px-4 py-2 font-medium text-slate-900 dark:text-slate-100">{{ res.antibiotique_designation }}</td>
                                        <td class="px-4 py-2 text-center">
                                            <select :value="res.interpretation" @change="updateResultatAntibiotique(bacId, res.id, 'interpretation', $event.target.value)"
                                                class="px-2 py-1 text-xs font-bold rounded border dark:border-slate-600 focus:ring-0"
                                                :class="{'bg-green-50 text-green-700': res.interpretation==='S', 'bg-yellow-50 text-yellow-700': res.interpretation==='I', 'bg-red-50 text-red-700': res.interpretation==='R'}">
                                                <option value="S">S</option><option value="I">I</option><option value="R">R</option>
                                            </select>
                                        </td>
                                        <td class="px-4 py-2 text-center">
                                            <input type="number" step="0.1" :value="res.diametre_mm" @change="updateResultatAntibiotique(bacId, res.id, 'diametre_mm', $event.target.value)"
                                                class="w-16 px-2 py-1 text-center text-xs border border-slate-300 dark:border-slate-600 rounded bg-white dark:bg-slate-800 dark:text-white" placeholder="-" />
                                        </td>
                                        <td class="px-4 py-2 text-center">
                                            <button @click="removeResultatAntibiotique(bacId, res.id)" class="text-red-500 hover:text-red-700 p-1 bg-red-50 dark:bg-red-900/30 rounded"><svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg></button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div v-else class="text-center p-6 bg-white dark:bg-slate-900 rounded-lg border border-slate-200 dark:border-slate-700">
                            <p class="text-sm text-slate-500">Aucun antibiotique testé. Utilisez le formulaire pour en ajouter.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</template>
