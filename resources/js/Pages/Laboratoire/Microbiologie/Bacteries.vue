<template>
<AppLayout>
    <div class="p-6">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6 mb-8">
            <div>
                <h1 class="text-3xl font-heading font-bold text-gray-900 dark:text-white">Bactéries</h1>
                <p class="text-gray-600 dark:text-gray-400 mt-2">Gérez les bactéries du laboratoire</p>
            </div>
            <div class="grid grid-cols-3 gap-3">
                <div class="bg-gradient-to-r from-blue-50 to-blue-100 dark:from-blue-900/20 dark:to-blue-800/20 px-3 py-2 rounded-xl border border-blue-200 dark:border-blue-700">
                    <div class="text-xs font-medium text-blue-600 dark:text-blue-400 uppercase">Total</div>
                    <div class="text-xl font-bold text-blue-800 dark:text-blue-300">{{ stats.total }}</div>
                </div>
                <div class="bg-gradient-to-r from-green-50 to-green-100 dark:from-green-900/20 dark:to-green-800/20 px-3 py-2 rounded-xl border border-green-200 dark:border-green-700">
                    <div class="text-xs font-medium text-green-600 dark:text-green-400 uppercase">Actives</div>
                    <div class="text-xl font-bold text-green-800 dark:text-green-300">{{ stats.actives }}</div>
                </div>
                <div class="bg-gradient-to-r from-red-50 to-red-100 dark:from-red-900/20 dark:to-red-800/20 px-3 py-2 rounded-xl border border-red-200 dark:border-red-700">
                    <div class="text-xs font-medium text-red-600 dark:text-red-400 uppercase">Inactives</div>
                    <div class="text-xl font-bold text-red-800 dark:text-red-300">{{ stats.inactives }}</div>
                </div>
            </div>
        </div>

        <!-- Filtres -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 mb-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="flex flex-1 gap-3">
                    <div class="relative flex-1 max-w-md">
                        <input v-model="form.search" @input="debouncedUpdateFilters" type="text" placeholder="Rechercher..."
                            class="w-full pl-10 pr-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                        <svg class="absolute left-3 top-2.5 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </div>
                    <select v-model="form.familleFilter" @change="updateFilters"
                        class="py-2 px-3 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-sm text-gray-900 dark:text-white">
                        <option value="">Toutes les familles</option>
                        <option v-for="f in familles" :key="f.id" :value="f.id">{{ f.designation }}</option>
                    </select>
                </div>
                <button @click="openModal()" class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg shadow-sm transition-colors">
                    + Nouvelle bactérie
                </button>
            </div>
        </div>

        <!-- Table -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900/50">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">Désignation</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">Famille</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">Statut</th>
                            <th class="px-6 py-4 text-center text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        <tr v-for="b in bacteries.data" :key="b.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                            <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-white">{{ b.designation }}</td>
                            <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">{{ b.famille?.designation || '-' }}</td>
                            <td class="px-6 py-4">
                                <span :class="b.status ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300' : 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300'"
                                    class="px-3 py-1 rounded-full text-xs font-medium">{{ b.status ? 'Active' : 'Inactive' }}</span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <div class="flex justify-center items-center gap-1">
                                    <button @click="openModal(b)" class="p-1.5 rounded-lg text-indigo-600 hover:bg-indigo-50 dark:text-indigo-400 dark:hover:bg-indigo-900/30 transition-colors" title="Modifier">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    </button>
                                    <button @click="toggleStatus(b)" class="p-1.5 rounded-lg transition-colors" :class="b.status ? 'text-red-500 hover:bg-red-50 dark:hover:bg-red-900/30' : 'text-emerald-500 hover:bg-emerald-50 dark:hover:bg-emerald-900/30'" :title="b.status ? 'Désactiver' : 'Activer'">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636a9 9 0 010 12.728M5.636 5.636a9 9 0 000 12.728M12 3v2m0 14v2"/></svg>
                                    </button>
                                    <button @click="confirmDelete(b)" class="p-1.5 rounded-lg text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-900/30 transition-colors" title="Supprimer">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!bacteries.data.length"><td colspan="4" class="px-6 py-16 text-center text-gray-500">Aucune bactérie</td></tr>
                    </tbody>
                </table>
            </div>
            <div class="px-6 py-4 bg-gray-50 dark:bg-gray-900/50 border-t border-gray-200 dark:border-gray-700">
                <Pagination :links="bacteries.links" />
            </div>
        </div>

        <!-- Modal Create/Edit -->
        <div v-if="showModalState" class="fixed inset-0 z-50 flex items-center justify-center px-4">
            <div class="fixed inset-0 bg-black/50" @click="showModalState = false"></div>
            <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-md w-full p-6 z-10">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">{{ fd.id ? 'Modifier' : 'Nouvelle' }} bactérie</h3>
                <form @submit.prevent="submitForm">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Famille</label>
                            <select v-model="fd.famille_id" required class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white">
                                <option value="">Sélectionner</option>
                                <option v-for="f in familles" :key="f.id" :value="f.id">{{ f.designation }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Désignation</label>
                            <input v-model="fd.designation" type="text" required class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white">
                        </div>
                        <div class="flex items-center">
                            <input v-model="fd.status" type="checkbox" class="w-4 h-4 text-blue-600">
                            <label class="ml-2 text-sm text-gray-700 dark:text-gray-300">Active</label>
                        </div>
                    </div>
                    <div class="flex justify-end space-x-3 mt-6">
                        <button type="button" @click="showModalState = false" class="px-4 py-2 text-sm bg-gray-100 dark:bg-gray-700 rounded-lg">Annuler</button>
                        <button type="submit" :disabled="fd.processing" class="px-4 py-2 text-sm text-white bg-blue-600 hover:bg-blue-700 rounded-lg disabled:opacity-50">{{ fd.id ? 'Modifier' : 'Créer' }}</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Delete Modal -->
        <div v-if="showDeleteModal" class="fixed inset-0 z-50 flex items-center justify-center px-4">
            <div class="fixed inset-0 bg-black/50" @click="showDeleteModal = false"></div>
            <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-sm w-full p-6 z-10">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2">Confirmer</h3>
                <p class="text-gray-600 dark:text-gray-400 mb-6">Supprimer <strong>{{ delItem?.designation }}</strong> ?</p>
                <div class="flex justify-end space-x-3">
                    <button @click="showDeleteModal = false" class="px-4 py-2 text-sm bg-gray-100 dark:bg-gray-700 rounded-lg">Annuler</button>
                    <button @click="deleteItem" class="px-4 py-2 text-sm text-white bg-red-600 hover:bg-red-700 rounded-lg">Supprimer</button>
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

const props = defineProps({ bacteries: Object, familles: Array, filters: Object, stats: Object });
const showModalState = ref(false);
const showDeleteModal = ref(false);
const delItem = ref(null);
const form = ref({ search: props.filters.search || '', familleFilter: props.filters.familleFilter || '', perPage: props.filters.perPage || 15 });
const fd = useForm({ id: null, famille_id: '', designation: '', status: true });

const updateFilters = () => router.get(route('laboratoire.microbiologie.bacteries'), form.value, { preserveState: true, preserveScroll: true, replace: true });
const debouncedUpdateFilters = debounce(updateFilters, 300);

const openModal = (item = null) => {
    if (item) { fd.id = item.id; fd.famille_id = item.famille_id; fd.designation = item.designation; fd.status = item.status; }
    else { fd.reset(); fd.id = null; fd.status = true; }
    showModalState.value = true;
};

const submitForm = () => {
    const opts = { preserveScroll: true, onSuccess: () => { showModalState.value = false; } };
    fd.id ? fd.put(route('laboratoire.microbiologie.bacteries.update', fd.id), opts) : fd.post(route('laboratoire.microbiologie.bacteries.store'), opts);
};

const toggleStatus = (item) => router.post(route('laboratoire.microbiologie.bacteries.toggle', item.id), {}, { preserveScroll: true });
const confirmDelete = (item) => { delItem.value = item; showDeleteModal.value = true; };
const deleteItem = () => router.delete(route('laboratoire.microbiologie.bacteries.destroy', delItem.value.id), { preserveScroll: true, onSuccess: () => { showDeleteModal.value = false; } });
</script>
