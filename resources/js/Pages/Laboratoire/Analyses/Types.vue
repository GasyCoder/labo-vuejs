<template>
<AppLayout>
    <div class="p-6">
        <!-- Header avec statistiques -->
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6 mb-8">
            <div>
                <h1 class="text-3xl font-heading font-bold text-gray-900 dark:text-white">Types d'analyses</h1>
                <p class="text-gray-600 dark:text-gray-400 mt-2">Gérez les types d'analyses du laboratoire</p>
            </div>
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
                <div class="bg-gradient-to-r from-blue-50 to-blue-100 dark:from-blue-900/20 dark:to-blue-800/20 px-3 py-2 rounded-xl border border-blue-200 dark:border-blue-700">
                    <div class="text-xs font-medium text-blue-600 dark:text-blue-400 uppercase tracking-wide">Total</div>
                    <div class="text-xl font-bold text-blue-800 dark:text-blue-300">{{ stats.total }}</div>
                </div>
                <div class="bg-gradient-to-r from-green-50 to-green-100 dark:from-green-900/20 dark:to-green-800/20 px-3 py-2 rounded-xl border border-green-200 dark:border-green-700">
                    <div class="text-xs font-medium text-green-600 dark:text-green-400 uppercase tracking-wide">Actifs</div>
                    <div class="text-xl font-bold text-green-800 dark:text-green-300">{{ stats.actifs }}</div>
                </div>
                <div class="bg-gradient-to-r from-red-50 to-red-100 dark:from-red-900/20 dark:to-red-800/20 px-3 py-2 rounded-xl border border-red-200 dark:border-red-700">
                    <div class="text-xs font-medium text-red-600 dark:text-red-400 uppercase tracking-wide">Inactifs</div>
                    <div class="text-xl font-bold text-red-800 dark:text-red-300">{{ stats.inactifs }}</div>
                </div>
                <div class="bg-gradient-to-r from-purple-50 to-purple-100 dark:from-purple-900/20 dark:to-purple-800/20 px-3 py-2 rounded-xl border border-purple-200 dark:border-purple-700">
                    <div class="text-xs font-medium text-purple-600 dark:text-purple-400 uppercase tracking-wide">Avec analyses</div>
                    <div class="text-xl font-bold text-purple-800 dark:text-purple-300">{{ stats.avec_analyses }}</div>
                </div>
            </div>
        </div>

        <!-- Filtres -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 mb-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="relative flex-1 max-w-md">
                    <input v-model="form.search" @input="debouncedUpdateFilters" type="text" placeholder="Rechercher un type..."
                        class="w-full pl-10 pr-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                    <svg class="absolute left-3 top-2.5 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </div>
                <div class="flex items-center space-x-3">
                    <select v-model="form.perPage" @change="updateFilters" class="py-2 px-3 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-sm text-gray-900 dark:text-white">
                        <option value="10">10</option><option value="25">25</option><option value="50">50</option>
                    </select>
                    <button @click="openModal()" class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg shadow-sm transition-colors">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                        Nouveau type
                    </button>
                </div>
            </div>
        </div>

        <!-- Table -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900/50">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">Nom</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">Libellé</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">Analyses</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">Statut</th>
                            <th class="px-6 py-4 text-center text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        <tr v-for="type in types.data" :key="type.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                            <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-white">{{ type.name }}</td>
                            <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">{{ type.libelle }}</td>
                            <td class="px-6 py-4"><span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300">{{ type.analyses_count }}</span></td>
                            <td class="px-6 py-4">
                                <span :class="type.status ? 'bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300' : 'bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300'" class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium">{{ type.status ? 'Actif' : 'Inactif' }}</span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <div class="flex justify-center space-x-2">
                                    <button @click="openModal(type)" class="p-2 text-blue-600 dark:text-blue-400 hover:bg-blue-100 dark:hover:bg-blue-900/30 rounded-lg transition-colors" title="Modifier">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                    </button>
                                    <button @click="toggleStatus(type)" :class="type.status ? 'text-orange-600 hover:bg-orange-100 dark:text-orange-400 dark:hover:bg-orange-900/30' : 'text-green-600 hover:bg-green-100 dark:text-green-400 dark:hover:bg-green-900/30'" class="p-2 rounded-lg transition-colors" :title="type.status ? 'Désactiver' : 'Activer'">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path></svg>
                                    </button>
                                    <button @click="confirmDelete(type)" class="p-2 text-red-600 dark:text-red-400 hover:bg-red-100 dark:hover:bg-red-900/30 rounded-lg transition-colors" title="Supprimer">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="types.data.length === 0"><td colspan="5" class="px-6 py-16 text-center text-gray-500 dark:text-gray-400">Aucun type trouvé</td></tr>
                    </tbody>
                </table>
            </div>
            <div class="px-6 py-4 bg-gray-50 dark:bg-gray-900/50 border-t border-gray-200 dark:border-gray-700">
                <Pagination :links="types.links" />
            </div>
        </div>

        <!-- Modal Create/Edit -->
        <div v-if="showModalState" class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-black/50" @click="showModalState = false"></div>
                <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-md w-full p-6 z-10">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">{{ formData.id ? 'Modifier' : 'Nouveau' }} type</h3>
                    <form @submit.prevent="submitForm">
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nom</label>
                                <input v-model="formData.name" type="text" required class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                                <p v-if="formData.errors?.name" class="text-red-500 text-xs mt-1">{{ formData.errors.name }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Libellé</label>
                                <input v-model="formData.libelle" type="text" required class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                                <p v-if="formData.errors?.libelle" class="text-red-500 text-xs mt-1">{{ formData.errors.libelle }}</p>
                            </div>
                            <div class="flex items-center">
                                <input v-model="formData.status" type="checkbox" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500">
                                <label class="ml-2 text-sm font-medium text-gray-700 dark:text-gray-300">Actif</label>
                            </div>
                        </div>
                        <div class="flex justify-end space-x-3 mt-6">
                            <button type="button" @click="showModalState = false" class="px-4 py-2 text-sm text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 rounded-lg">Annuler</button>
                            <button type="submit" :disabled="formData.processing" class="px-4 py-2 text-sm text-white bg-blue-600 hover:bg-blue-700 rounded-lg disabled:opacity-50">{{ formData.id ? 'Modifier' : 'Créer' }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Modal Delete -->
        <div v-if="showDeleteModalState" class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-black/50" @click="showDeleteModalState = false"></div>
                <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-sm w-full p-6 z-10">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2">Confirmer la suppression</h3>
                    <p class="text-gray-600 dark:text-gray-400 mb-6">Supprimer le type <strong>{{ typeToDelete?.name }}</strong> ?</p>
                    <div class="flex justify-end space-x-3">
                        <button @click="showDeleteModalState = false" class="px-4 py-2 text-sm text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 rounded-lg">Annuler</button>
                        <button @click="deleteType" class="px-4 py-2 text-sm text-white bg-red-600 hover:bg-red-700 rounded-lg">Supprimer</button>
                    </div>
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

const props = defineProps({ types: Object, filters: Object, stats: Object });

const showModalState = ref(false);
const showDeleteModalState = ref(false);
const typeToDelete = ref(null);

const form = ref({ search: props.filters.search || '', perPage: props.filters.perPage || 10 });
const formData = useForm({ id: null, name: '', libelle: '', status: true });

const updateFilters = () => { router.get(route('laboratoire.analyses.types'), form.value, { preserveState: true, preserveScroll: true, replace: true }); };
const debouncedUpdateFilters = debounce(updateFilters, 300);

const openModal = (type = null) => {
    if (type) { formData.id = type.id; formData.name = type.name; formData.libelle = type.libelle; formData.status = type.status; }
    else { formData.reset(); formData.id = null; formData.status = true; }
    showModalState.value = true;
};

const submitForm = () => {
    if (formData.id) { formData.put(route('laboratoire.analyses.types.update', formData.id), { preserveScroll: true, onSuccess: () => { showModalState.value = false; } }); }
    else { formData.post(route('laboratoire.analyses.types.store'), { preserveScroll: true, onSuccess: () => { showModalState.value = false; formData.reset(); } }); }
};

const toggleStatus = (type) => { router.post(route('laboratoire.analyses.types.toggle', type.id), {}, { preserveScroll: true }); };
const confirmDelete = (type) => { typeToDelete.value = type; showDeleteModalState.value = true; };
const deleteType = () => { router.delete(route('laboratoire.analyses.types.destroy', typeToDelete.value.id), { preserveScroll: true, onSuccess: () => { showDeleteModalState.value = false; } }); };
</script>
