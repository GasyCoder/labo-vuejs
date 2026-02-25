<template>
<AppLayout>
    <div class="p-6">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6 mb-8">
            <div>
                <h1 class="text-3xl font-heading font-bold text-gray-900 dark:text-white">Prélèvements</h1>
                <p class="text-gray-600 dark:text-gray-400 mt-2">Gérez les types de prélèvements</p>
            </div>
            <div class="grid grid-cols-3 gap-3">
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
            </div>
        </div>

        <!-- Filtres -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 mb-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="relative flex-1 max-w-md">
                    <input v-model="form.search" @input="debouncedUpdateFilters" type="text" placeholder="Rechercher..."
                        class="w-full pl-10 pr-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                    <svg class="absolute left-3 top-2.5 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </div>
                <button @click="openModal()" class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg shadow-sm transition-colors">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                    Nouveau prélèvement
                </button>
            </div>
        </div>

        <!-- Table -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900/50">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">Dénomination</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">Prix</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">Prix Promo</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">Quantité</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">Type tube</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">Statut</th>
                            <th class="px-6 py-4 text-center text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        <tr v-for="p in prelevements.data" :key="p.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                            <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-white">{{ p.denomination }}</td>
                            <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">{{ formatNumber(p.prix) }} Ar</td>
                            <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">{{ p.prix_promotion ? formatNumber(p.prix_promotion) + ' Ar' : '-' }}</td>
                            <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">{{ p.quantite }}</td>
                            <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">{{ p.type_tube_recommande?.code || '-' }}</td>
                            <td class="px-6 py-4">
                                <span :class="p.is_active ? 'bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300' : 'bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300'" class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium">{{ p.is_active ? 'Actif' : 'Inactif' }}</span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <div class="flex justify-center space-x-2">
                                    <button @click="openModal(p)" class="p-2 text-blue-600 dark:text-blue-400 hover:bg-blue-100 dark:hover:bg-blue-900/30 rounded-lg transition-colors"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg></button>
                                    <button @click="toggleStatus(p)" :class="p.is_active ? 'text-orange-600 hover:bg-orange-100' : 'text-green-600 hover:bg-green-100'" class="p-2 rounded-lg transition-colors"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path></svg></button>
                                    <button @click="confirmDelete(p)" class="p-2 text-red-600 dark:text-red-400 hover:bg-red-100 dark:hover:bg-red-900/30 rounded-lg transition-colors"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg></button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="prelevements.data.length === 0"><td colspan="7" class="px-6 py-16 text-center text-gray-500 dark:text-gray-400">Aucun prélèvement trouvé</td></tr>
                    </tbody>
                </table>
            </div>
            <div class="px-6 py-4 bg-gray-50 dark:bg-gray-900/50 border-t border-gray-200 dark:border-gray-700"><Pagination :links="prelevements.links" /></div>
        </div>

        <!-- Modal Create/Edit -->
        <div v-if="showModalState" class="fixed inset-0 z-[1040] overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-black/50" @click="showModalState = false"></div>
                <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-lg w-full p-6 z-10">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">{{ formData.id ? 'Modifier' : 'Nouveau' }} prélèvement</h3>
                    <form @submit.prevent="submitForm">
                        <div class="space-y-4">
                            <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Dénomination <span class="text-red-500">*</span></label><input v-model="formData.denomination" type="text" required placeholder="Dénomination détaillée du prélèvement" class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500"></div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Prix Standard (Ar) <span class="text-red-500">*</span></label>
                                    <input v-model="formData.prix" type="number" min="0" required class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Prix Promotion (Ar) <span class="text-xs text-blue-500">- Si Qté > 1</span></label>
                                    <input v-model="formData.prix_promotion" type="number" min="0" class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Type de Tube Recommandé</label>
                                    <select v-model="formData.type_tube_id" class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                                        <option value="">-- Sélectionner un type de tube --</option>
                                        <option v-for="tt in typesTubes" :key="tt.id" :value="tt.id">{{ tt.code }} - {{ tt.couleur || '' }}</option>
                                    </select>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Le type de tube sera suggéré automatiquement selon le type de prélèvement</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Quantité par défaut <span class="text-red-500">*</span></label>
                                    <input v-model="formData.quantite" type="number" min="1" required class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                                </div>
                            </div>
                            <div class="flex items-center"><input v-model="formData.is_active" type="checkbox" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500"><label class="ml-2 text-sm font-medium text-gray-700 dark:text-gray-300">Prélèvement actif</label></div>
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
        <div v-if="showDeleteModalState" class="fixed inset-0 z-[1040] overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-black/50" @click="showDeleteModalState = false"></div>
                <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-sm w-full p-6 z-10">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2">Confirmer la suppression</h3>
                    <p class="text-gray-600 dark:text-gray-400 mb-6">Supprimer <strong>{{ itemToDelete?.denomination }}</strong> ?</p>
                    <div class="flex justify-end space-x-3">
                        <button @click="showDeleteModalState = false" class="px-4 py-2 text-sm text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 rounded-lg">Annuler</button>
                        <button @click="deleteItem" class="px-4 py-2 text-sm text-white bg-red-600 hover:bg-red-700 rounded-lg">Supprimer</button>
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

const props = defineProps({ prelevements: Object, typesTubes: Array, filters: Object, stats: Object });

const showModalState = ref(false);
const showDeleteModalState = ref(false);
const itemToDelete = ref(null);
const form = ref({ search: props.filters.search || '', perPage: props.filters.perPage || 15 });
const formData = useForm({ id: null, denomination: '', prix: 0, prix_promotion: 0, quantite: 1, is_active: true, type_tube_id: '' });

const formatNumber = (n) => Number(n || 0).toLocaleString('fr-FR');
const updateFilters = () => { router.get(route('laboratoire.analyses.prelevements'), form.value, { preserveState: true, preserveScroll: true, replace: true }); };
const debouncedUpdateFilters = debounce(updateFilters, 300);

const openModal = (item = null) => {
    if (item) { Object.assign(formData, { id: item.id, denomination: item.denomination, prix: item.prix, prix_promotion: item.prix_promotion, quantite: item.quantite, is_active: item.is_active, type_tube_id: item.type_tube_id || '' }); }
    else { formData.reset(); formData.id = null; formData.is_active = true; formData.quantite = 1; }
    showModalState.value = true;
};

const submitForm = () => {
    const opts = { preserveScroll: true, onSuccess: () => { showModalState.value = false; } };
    if (formData.id) formData.put(route('laboratoire.analyses.prelevements.update', formData.id), opts);
    else formData.post(route('laboratoire.analyses.prelevements.store'), opts);
};

const toggleStatus = (item) => { router.post(route('laboratoire.analyses.prelevements.toggle', item.id), {}, { preserveScroll: true }); };
const confirmDelete = (item) => { itemToDelete.value = item; showDeleteModalState.value = true; };
const deleteItem = () => { router.delete(route('laboratoire.analyses.prelevements.destroy', itemToDelete.value.id), { preserveScroll: true, onSuccess: () => { showDeleteModalState.value = false; } }); };
</script>
