<template>
<AppLayout>
    <div class="p-6">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6 mb-8">
            <div>
                <h1 class="text-3xl font-heading font-bold text-gray-900 dark:text-white">Antibiotiques</h1>
                <p class="text-gray-600 dark:text-gray-400 mt-2">Gérez les antibiotiques du laboratoire</p>
            </div>
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
                <div class="bg-gradient-to-r from-blue-50 to-blue-100 dark:from-blue-900/20 dark:to-blue-800/20 px-3 py-2 rounded-xl border border-blue-200 dark:border-blue-700">
                    <div class="text-xs font-medium text-blue-600 dark:text-blue-400 uppercase">Total</div>
                    <div class="text-xl font-bold text-blue-800 dark:text-blue-300">{{ stats.total }}</div>
                </div>
                <div class="bg-gradient-to-r from-green-50 to-green-100 dark:from-green-900/20 dark:to-green-800/20 px-3 py-2 rounded-xl border border-green-200 dark:border-green-700">
                    <div class="text-xs font-medium text-green-600 dark:text-green-400 uppercase">Actifs</div>
                    <div class="text-xl font-bold text-green-800 dark:text-green-300">{{ stats.actifs }}</div>
                </div>
                <div class="bg-gradient-to-r from-red-50 to-red-100 dark:from-red-900/20 dark:to-red-800/20 px-3 py-2 rounded-xl border border-red-200 dark:border-red-700">
                    <div class="text-xs font-medium text-red-600 dark:text-red-400 uppercase">Inactifs</div>
                    <div class="text-xl font-bold text-red-800 dark:text-red-300">{{ stats.inactifs }}</div>
                </div>
                <div class="bg-gradient-to-r from-purple-50 to-purple-100 dark:from-purple-900/20 dark:to-purple-800/20 px-3 py-2 rounded-xl border border-purple-200 dark:border-purple-700">
                    <div class="text-xs font-medium text-purple-600 dark:text-purple-400 uppercase">Avec bactéries</div>
                    <div class="text-xl font-bold text-purple-800 dark:text-purple-300">{{ stats.avec_bacteries }}</div>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 mb-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="flex flex-1 gap-3">
                    <div class="relative flex-1 max-w-md">
                        <input v-model="form.search" @input="debouncedUpdateFilters" type="text" placeholder="Rechercher..."
                            class="w-full pl-10 pr-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                        <svg class="absolute left-3 top-2.5 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    </div>
                    <select v-model="form.familleFilter" @change="updateFilters" class="py-2 px-3 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-sm text-gray-900 dark:text-white">
                        <option value="">Toutes familles</option>
                        <option v-for="f in familles" :key="f.id" :value="f.id">{{ f.designation }}</option>
                    </select>
                </div>
                <button @click="openModal()" class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg shadow-sm">+ Nouvel antibiotique</button>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900/50"><tr>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase">Désignation</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase">Famille</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase">Commentaire</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase">Statut</th>
                        <th class="px-6 py-4 text-center text-xs font-bold text-gray-500 uppercase">Actions</th>
                    </tr></thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        <tr v-for="a in antibiotiques.data" :key="a.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                            <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-white">{{ a.designation }}</td>
                            <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">{{ a.famille?.designation || '-' }}</td>
                            <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400 max-w-xs truncate">{{ a.commentaire || '-' }}</td>
                            <td class="px-6 py-4">
                                <span :class="a.status ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300' : 'bg-red-100 text-red-800'"
                                    class="px-3 py-1 rounded-full text-xs font-medium">{{ a.status ? 'Actif' : 'Inactif' }}</span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <div class="flex justify-center space-x-1">
                                    <button @click="openModal(a)" class="p-2 text-blue-600 hover:bg-blue-100 rounded-lg">✏️</button>
                                    <button @click="toggleStatus(a)" class="p-2 hover:bg-gray-100 rounded-lg">{{ a.status ? '🔴' : '🟢' }}</button>
                                    <button @click="confirmDelete(a)" class="p-2 text-red-600 hover:bg-red-100 rounded-lg">🗑️</button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!antibiotiques.data.length"><td colspan="5" class="px-6 py-16 text-center text-gray-500">Aucun antibiotique</td></tr>
                    </tbody>
                </table>
            </div>
            <div class="px-6 py-4 bg-gray-50 dark:bg-gray-900/50 border-t"><Pagination :links="antibiotiques.links" /></div>
        </div>

        <!-- Modal -->
        <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center px-4">
            <div class="fixed inset-0 bg-black/50" @click="showModal = false"></div>
            <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-md w-full p-6 z-10">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">{{ fd.id ? 'Modifier' : 'Nouvel' }} antibiotique</h3>
                <form @submit.prevent="submitForm"><div class="space-y-4">
                    <div><label class="block text-sm font-medium mb-1">Famille</label>
                        <select v-model="fd.famille_id" required class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white">
                            <option value="">Sélectionner</option><option v-for="f in familles" :key="f.id" :value="f.id">{{ f.designation }}</option>
                        </select></div>
                    <div><label class="block text-sm font-medium mb-1">Désignation</label><input v-model="fd.designation" required class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white"></div>
                    <div><label class="block text-sm font-medium mb-1">Commentaire</label><textarea v-model="fd.commentaire" rows="2" class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white"></textarea></div>
                    <div class="flex items-center"><input v-model="fd.status" type="checkbox" class="w-4 h-4 text-blue-600"><label class="ml-2 text-sm">Actif</label></div>
                </div>
                <div class="flex justify-end space-x-3 mt-6">
                    <button type="button" @click="showModal = false" class="px-4 py-2 text-sm bg-gray-100 dark:bg-gray-700 rounded-lg">Annuler</button>
                    <button type="submit" :disabled="fd.processing" class="px-4 py-2 text-sm text-white bg-blue-600 hover:bg-blue-700 rounded-lg disabled:opacity-50">{{ fd.id ? 'Modifier' : 'Créer' }}</button>
                </div></form>
            </div>
        </div>

        <!-- Delete -->
        <div v-if="showDel" class="fixed inset-0 z-50 flex items-center justify-center px-4">
            <div class="fixed inset-0 bg-black/50" @click="showDel = false"></div>
            <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-sm w-full p-6 z-10">
                <h3 class="text-lg font-bold mb-2">Confirmer</h3>
                <p class="text-gray-600 mb-6">Supprimer <strong>{{ delItem?.designation }}</strong> ?</p>
                <div class="flex justify-end space-x-3">
                    <button @click="showDel = false" class="px-4 py-2 text-sm bg-gray-100 rounded-lg">Annuler</button>
                    <button @click="deleteItem" class="px-4 py-2 text-sm text-white bg-red-600 rounded-lg">Supprimer</button>
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

const props = defineProps({ antibiotiques: Object, familles: Array, filters: Object, stats: Object });
const showModal = ref(false);
const showDel = ref(false);
const delItem = ref(null);
const form = ref({ search: props.filters.search || '', familleFilter: props.filters.familleFilter || '', perPage: props.filters.perPage || 15 });
const fd = useForm({ id: null, famille_id: '', designation: '', commentaire: '', status: true });

const updateFilters = () => router.get(route('laboratoire.microbiologie.antibiotiques'), form.value, { preserveState: true, preserveScroll: true, replace: true });
const debouncedUpdateFilters = debounce(updateFilters, 300);

const openModal = (item = null) => {
    if (item) { Object.assign(fd, { id: item.id, famille_id: item.famille_id, designation: item.designation, commentaire: item.commentaire || '', status: item.status }); }
    else { fd.reset(); fd.id = null; fd.status = true; }
    showModal.value = true;
};

const submitForm = () => {
    const opts = { preserveScroll: true, onSuccess: () => { showModal.value = false; } };
    fd.id ? fd.put(route('laboratoire.microbiologie.antibiotiques.update', fd.id), opts) : fd.post(route('laboratoire.microbiologie.antibiotiques.store'), opts);
};

const toggleStatus = (item) => router.post(route('laboratoire.microbiologie.antibiotiques.toggle', item.id), {}, { preserveScroll: true });
const confirmDelete = (item) => { delItem.value = item; showDel.value = true; };
const deleteItem = () => router.delete(route('laboratoire.microbiologie.antibiotiques.destroy', delItem.value.id), { preserveScroll: true, onSuccess: () => { showDel.value = false; } });
</script>
