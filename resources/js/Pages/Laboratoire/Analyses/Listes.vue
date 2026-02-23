<template>
<AppLayout>
    <div class="p-6">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6 mb-8">
            <div>
                <h1 class="text-3xl font-heading font-bold text-gray-900 dark:text-white">Analyses</h1>
                <p class="text-gray-600 dark:text-gray-400 mt-2">Gérez le catalogue des analyses</p>
            </div>
        </div>

        <!-- Tabs + counts -->
        <div class="flex flex-wrap gap-2 mb-6">
            <button v-for="tab in levelTabs" :key="tab.key" @click="filterLevel(tab.key)"
                :class="[ filters.selectedLevel === tab.key ? 'bg-blue-600 text-white' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border border-gray-300 dark:border-gray-600', 'px-4 py-2 rounded-lg text-sm font-medium transition-colors' ]">
                {{ tab.label }} ({{ counts[tab.key] || 0 }})
            </button>
        </div>

        <!-- Filtres -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 mb-6">
            <div class="flex flex-col sm:flex-row sm:items-center gap-4">
                <div class="relative flex-1 max-w-md">
                    <input v-model="form.search" @input="debouncedSearch" type="text" placeholder="Code, désignation..."
                        class="w-full pl-10 pr-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                    <svg class="absolute left-3 top-2.5 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>
                <select v-model="form.selectedExamen" @change="applyFilters" class="py-2 px-3 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-sm text-gray-900 dark:text-white">
                    <option value="">Tous les examens</option>
                    <option v-for="e in examens" :key="e.id" :value="e.id">{{ e.name }}</option>
                </select>
                <select v-model="form.perPage" @change="applyFilters" class="py-2 px-3 bg-gray-50 dark:bg-gray-700 border rounded-lg text-sm">
                    <option value="10">10</option><option value="25">25</option><option value="50">50</option>
                </select>
                <button @click="openCreate()" class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg shadow-sm">+ Nouvelle analyse</button>
            </div>
        </div>

        <!-- Table -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900/50"><tr>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Code</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Désignation</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Niveau</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Examen</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Prix</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Statut</th>
                        <th class="px-4 py-3 text-center text-xs font-bold text-gray-500 uppercase">Actions</th>
                    </tr></thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        <template v-for="a in analyses.data" :key="a.id">
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                <td class="px-4 py-3 text-sm font-mono text-gray-600 dark:text-gray-400">{{ a.code }}</td>
                                <td class="px-4 py-3 text-sm" :class="a.is_bold ? 'font-bold' : ''">
                                    <span class="text-gray-900 dark:text-white">{{ a.designation }}</span>
                                    <span v-if="a.enfants?.length" class="ml-2 text-xs text-blue-500">({{ a.enfants.length }} sous)</span>
                                </td>
                                <td class="px-4 py-3">
                                    <span :class="levelBadge(a.level)" class="px-2 py-0.5 rounded text-xs font-medium">{{ a.level }}</span>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">{{ a.examen?.name || '-' }}</td>
                                <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-white">{{ formatN(a.prix) }} Ar</td>
                                <td class="px-4 py-3">
                                    <span :class="a.status ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'" class="px-2 py-0.5 rounded-full text-xs font-medium">{{ a.status ? 'Actif' : 'Inactif' }}</span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <div class="flex justify-center space-x-1">
                                        <button @click="editAnalyse(a)" class="p-1.5 text-blue-600 hover:bg-blue-100 rounded" title="Modifier">✏️</button>
                                        <button @click="toggleSt(a)" class="p-1.5 hover:bg-gray-100 rounded">{{ a.status ? '🔴' : '🟢' }}</button>
                                        <button @click="confirmDel(a)" class="p-1.5 text-red-600 hover:bg-red-100 rounded">🗑️</button>
                                    </div>
                                </td>
                            </tr>
                        </template>
                        <tr v-if="!analyses.data.length"><td colspan="7" class="px-4 py-16 text-center text-gray-500">Aucune analyse</td></tr>
                    </tbody>
                </table>
            </div>
            <div class="px-6 py-4 bg-gray-50 dark:bg-gray-900/50 border-t"><Pagination :links="analyses.links" /></div>
        </div>

        <!-- Modal Create/Edit -->
        <div v-if="showForm" class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-start justify-center min-h-screen px-4 pt-8 pb-20">
                <div class="fixed inset-0 bg-black/50" @click="showForm = false"></div>
                <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-3xl w-full p-6 z-10 max-h-[90vh] overflow-y-auto">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">{{ fd.id ? 'Modifier' : 'Nouvelle' }} Analyse</h3>
                    <form @submit.prevent="submitForm">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div><label class="block text-sm font-medium mb-1">Code *</label><input v-model="fd.code" required class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white"><p v-if="fd.errors.code" class="text-red-500 text-xs mt-1">{{ fd.errors.code }}</p></div>
                            <div><label class="block text-sm font-medium mb-1">Niveau *</label>
                                <select v-model="fd.level" required class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700 border rounded-lg text-gray-900 dark:text-white">
                                    <option value="NORMAL">Normal</option><option value="PARENT">Parent</option><option value="CHILD">Enfant</option>
                                </select></div>
                            <div class="md:col-span-2"><label class="block text-sm font-medium mb-1">Désignation *</label><input v-model="fd.designation" required class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700 border rounded-lg text-gray-900 dark:text-white"></div>
                            <div><label class="block text-sm font-medium mb-1">Examen *</label>
                                <select v-model="fd.examen_id" required class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700 border rounded-lg text-gray-900 dark:text-white">
                                    <option value="">Sélectionner</option><option v-for="e in examens" :key="e.id" :value="e.id">{{ e.name }}</option>
                                </select></div>
                            <div><label class="block text-sm font-medium mb-1">Type *</label>
                                <select v-model="fd.type_id" required class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700 border rounded-lg text-gray-900 dark:text-white">
                                    <option value="">Sélectionner</option><option v-for="t in types" :key="t.id" :value="t.id">{{ t.name }}</option>
                                </select></div>
                            <div><label class="block text-sm font-medium mb-1">Prix *</label><input v-model.number="fd.prix" type="number" min="0" required class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700 border rounded-lg text-gray-900 dark:text-white"></div>
                            <div><label class="block text-sm font-medium mb-1">Ordre</label><input v-model.number="fd.ordre" type="number" class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700 border rounded-lg text-gray-900 dark:text-white"></div>
                            <div><label class="block text-sm font-medium mb-1">Unité</label><input v-model="fd.unite" class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700 border rounded-lg text-gray-900 dark:text-white"></div>
                            <div><label class="block text-sm font-medium mb-1">Suffixe</label><input v-model="fd.suffixe" class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700 border rounded-lg text-gray-900 dark:text-white"></div>
                            <div><label class="block text-sm font-medium mb-1">Valeur réf.</label><input v-model="fd.valeur_ref" class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700 border rounded-lg text-gray-900 dark:text-white"></div>
                            <div><label class="block text-sm font-medium mb-1">Réf. Homme</label><input v-model="fd.valeur_ref_homme" class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700 border rounded-lg text-gray-900 dark:text-white"></div>
                            <div><label class="block text-sm font-medium mb-1">Réf. Femme</label><input v-model="fd.valeur_ref_femme" class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700 border rounded-lg text-gray-900 dark:text-white"></div>
                            <div><label class="block text-sm font-medium mb-1">Réf. Garçon</label><input v-model="fd.valeur_ref_enfant_garcon" class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700 border rounded-lg text-gray-900 dark:text-white"></div>
                            <div><label class="block text-sm font-medium mb-1">Réf. Fille</label><input v-model="fd.valeur_ref_enfant_fille" class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700 border rounded-lg text-gray-900 dark:text-white"></div>
                            <div v-if="fd.level === 'CHILD'"><label class="block text-sm font-medium mb-1">Parent</label>
                                <select v-model="fd.parent_id" class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700 border rounded-lg text-gray-900 dark:text-white">
                                    <option value="">Aucun</option><option v-for="p in analysesParents" :key="p.id" :value="p.id">{{ p.designation }}</option>
                                </select></div>
                            <div class="md:col-span-2"><label class="block text-sm font-medium mb-1">Description</label><textarea v-model="fd.description" rows="2" class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700 border rounded-lg text-gray-900 dark:text-white"></textarea></div>
                            <div class="flex items-center gap-4">
                                <label class="flex items-center"><input v-model="fd.status" type="checkbox" class="w-4 h-4 text-blue-600 mr-2">Actif</label>
                                <label class="flex items-center"><input v-model="fd.is_bold" type="checkbox" class="w-4 h-4 text-blue-600 mr-2">Gras</label>
                            </div>
                        </div>

                        <!-- Sous-analyses (si PARENT) -->
                        <div v-if="fd.level === 'PARENT'" class="mt-6 border-t pt-4">
                            <div class="flex items-center justify-between mb-3">
                                <h4 class="font-bold text-gray-900 dark:text-white">Sous-analyses</h4>
                                <button type="button" @click="addSousAnalyse" class="text-sm text-blue-600 hover:text-blue-800">+ Ajouter</button>
                            </div>
                            <div v-for="(sa, i) in fd.sousAnalyses" :key="i" class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4 mb-3 border">
                                <div class="flex justify-between items-center mb-2">
                                    <span class="text-sm font-medium">#{{ i + 1 }}</span>
                                    <button type="button" @click="fd.sousAnalyses.splice(i, 1)" class="text-red-500 text-sm">Supprimer</button>
                                </div>
                                <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                                    <input v-model="sa.code" placeholder="Code" class="px-2 py-1.5 bg-white dark:bg-gray-700 border rounded text-sm">
                                    <input v-model="sa.designation" placeholder="Désignation" class="px-2 py-1.5 bg-white dark:bg-gray-700 border rounded text-sm col-span-2">
                                    <input v-model.number="sa.prix" type="number" placeholder="Prix" class="px-2 py-1.5 bg-white dark:bg-gray-700 border rounded text-sm">
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-end space-x-3 mt-6">
                            <button type="button" @click="showForm = false" class="px-4 py-2 text-sm bg-gray-100 dark:bg-gray-700 rounded-lg">Annuler</button>
                            <button type="submit" :disabled="fd.processing" class="px-4 py-2 text-sm text-white bg-blue-600 hover:bg-blue-700 rounded-lg disabled:opacity-50">{{ fd.id ? 'Modifier' : 'Créer' }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Delete -->
        <div v-if="showDel" class="fixed inset-0 z-50 flex items-center justify-center px-4">
            <div class="fixed inset-0 bg-black/50" @click="showDel = false"></div>
            <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-sm w-full p-6 z-10">
                <h3 class="text-lg font-bold mb-2">Confirmer</h3>
                <p class="mb-6">Supprimer <strong>{{ delItem?.designation }}</strong> ?</p>
                <div class="flex justify-end space-x-3">
                    <button @click="showDel = false" class="px-4 py-2 text-sm bg-gray-100 rounded-lg">Annuler</button>
                    <button @click="deleteA" class="px-4 py-2 text-sm text-white bg-red-600 rounded-lg">Supprimer</button>
                </div>
            </div>
        </div>
    </div>
</AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import { debounce } from 'lodash';

const props = defineProps({
    analyses: Object, counts: Object, examens: Array, types: Array, analysesParents: Array, filters: Object,
});

const showForm = ref(false);
const showDel = ref(false);
const delItem = ref(null);

const levelTabs = [
    { key: 'tous', label: 'Tous' },
    { key: 'racines', label: 'Racines' },
    { key: 'parents', label: 'Parents' },
    { key: 'normales', label: 'Normales' },
    { key: 'enfants', label: 'Enfants' },
];

const form = ref({
    search: props.filters.search || '',
    selectedExamen: props.filters.selectedExamen || '',
    selectedLevel: props.filters.selectedLevel || 'tous',
    perPage: props.filters.perPage || 10,
});

const fd = useForm({
    id: null, code: '', level: 'NORMAL', parent_id: '', designation: '', description: '',
    prix: 0, is_bold: false, examen_id: '', type_id: '',
    valeur_ref: '', valeur_ref_homme: '', valeur_ref_femme: '',
    valeur_ref_enfant_garcon: '', valeur_ref_enfant_fille: '',
    unite: '', suffixe: '', ordre: 99, status: true, sousAnalyses: [],
});

const formatN = (n) => Number(n || 0).toLocaleString('fr-FR');
const levelBadge = (l) => ({ PARENT: 'bg-purple-100 text-purple-800', NORMAL: 'bg-blue-100 text-blue-800', CHILD: 'bg-gray-100 text-gray-700' }[l] || 'bg-gray-100 text-gray-700');

const applyFilters = () => router.get(route('laboratoire.analyses.listes'), form.value, { preserveState: true, preserveScroll: true, replace: true });
const debouncedSearch = debounce(applyFilters, 300);
const filterLevel = (key) => { form.value.selectedLevel = key; applyFilters(); };

const openCreate = () => {
    fd.reset(); fd.id = null; fd.status = true; fd.ordre = 99; fd.sousAnalyses = [];
    showForm.value = true;
};

const editAnalyse = (a) => {
    Object.assign(fd, {
        id: a.id, code: a.code, level: a.level, parent_id: a.parent_id || '',
        designation: a.designation, description: a.description || '', prix: a.prix,
        is_bold: a.is_bold, examen_id: a.examen_id, type_id: a.type_id,
        valeur_ref: a.valeur_ref || '', valeur_ref_homme: a.valeur_ref_homme || '',
        valeur_ref_femme: a.valeur_ref_femme || '',
        valeur_ref_enfant_garcon: a.valeur_ref_enfant_garcon || '',
        valeur_ref_enfant_fille: a.valeur_ref_enfant_fille || '',
        unite: a.unite || '', suffixe: a.suffixe || '', ordre: a.ordre, status: a.status,
        sousAnalyses: (a.enfants || []).map(e => ({
            id: e.id, code: e.code, designation: e.designation, prix: e.prix,
            level: e.level, examen_id: e.examen_id, type_id: e.type_id,
            valeur_ref: e.valeur_ref || '', unite: e.unite || '', ordre: e.ordre,
            children: (e.enfants || []).map(c => ({ id: c.id, code: c.code, designation: c.designation, prix: c.prix, level: c.level })),
        })),
    });
    showForm.value = true;
};

const addSousAnalyse = () => {
    fd.sousAnalyses.push({ code: '', designation: '', prix: 0, level: 'CHILD', children: [] });
};

const submitForm = () => {
    const opts = { preserveScroll: true, onSuccess: () => { showForm.value = false; } };
    fd.id ? fd.put(route('laboratoire.analyses.listes.update', fd.id), opts) : fd.post(route('laboratoire.analyses.listes.store'), opts);
};

const toggleSt = (a) => router.post(route('laboratoire.analyses.listes.toggle', a.id), {}, { preserveScroll: true });
const confirmDel = (a) => { delItem.value = a; showDel.value = true; };
const deleteA = () => router.delete(route('laboratoire.analyses.listes.destroy', delItem.value.id), { preserveScroll: true, onSuccess: () => { showDel.value = false; } });
</script>
