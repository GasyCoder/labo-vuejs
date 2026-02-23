<template>
<AppLayout>
    <div class="p-6">
        <!-- Header -->
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6 mb-8">
            <div>
                <h1 class="text-3xl font-heading font-bold text-gray-900 dark:text-white">Gestion des Examens</h1>
                <p class="text-gray-600 dark:text-gray-400 mt-2">Consultez et gérez les examens du laboratoire</p>
            </div>
        </div>

        <!-- Actions -->
        <div class="flex items-center justify-end mb-6">
            <button @click="openModal()" class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg shadow-sm transition-colors">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                Nouvel examen
            </button>
        </div>

        <!-- Table -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900/50">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Nom</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Abréviation</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Statut</th>
                            <th class="px-6 py-4 text-center text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        <tr v-for="examen in examens" :key="examen.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                            <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-white">{{ examen.name }}</td>
                            <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">{{ examen.abr }}</td>
                            <td class="px-6 py-4">
                                <span :class="examen.status ? 'bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300' : 'bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300'" class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium">
                                    {{ examen.status ? 'Actif' : 'Inactif' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <div class="flex justify-center space-x-2">
                                    <button @click="openModal(examen)" class="p-2 text-blue-600 dark:text-blue-400 hover:bg-blue-100 dark:hover:bg-blue-900/30 rounded-lg transition-colors" title="Modifier">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                    </button>
                                    <button @click="confirmDelete(examen)" class="p-2 text-red-600 dark:text-red-400 hover:bg-red-100 dark:hover:bg-red-900/30 rounded-lg transition-colors" title="Supprimer">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="examens.length === 0">
                            <td colspan="4" class="px-6 py-16 text-center text-gray-500 dark:text-gray-400">Aucun examen trouvé</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Modal Create/Edit -->
        <div v-if="showModalState" class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-black/50 transition-opacity" @click="showModalState = false"></div>
                <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-md w-full p-6 z-10">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">{{ formData.id ? 'Modifier' : 'Nouvel' }} examen</h3>
                    <form @submit.prevent="submitForm">
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nom</label>
                                <input v-model="formData.name" type="text" required class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                                <p v-if="formData.errors?.name" class="text-red-500 text-xs mt-1">{{ formData.errors.name }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Abréviation</label>
                                <input v-model="formData.abr" type="text" required maxlength="10" class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                                <p v-if="formData.errors?.abr" class="text-red-500 text-xs mt-1">{{ formData.errors.abr }}</p>
                            </div>
                            <div class="flex items-center">
                                <input v-model="formData.status" type="checkbox" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500">
                                <label class="ml-2 text-sm font-medium text-gray-700 dark:text-gray-300">Actif</label>
                            </div>
                        </div>
                        <div class="flex justify-end space-x-3 mt-6">
                            <button type="button" @click="showModalState = false" class="px-4 py-2 text-sm text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg transition-colors">Annuler</button>
                            <button type="submit" :disabled="formData.processing" class="px-4 py-2 text-sm text-white bg-blue-600 hover:bg-blue-700 rounded-lg shadow-sm transition-colors disabled:opacity-50">
                                {{ formData.id ? 'Modifier' : 'Créer' }}
                            </button>
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
                    <p class="text-gray-600 dark:text-gray-400 mb-6">Êtes-vous sûr de vouloir supprimer l'examen <strong>{{ examenToDelete?.name }}</strong> ?</p>
                    <div class="flex justify-end space-x-3">
                        <button @click="showDeleteModalState = false" class="px-4 py-2 text-sm text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 rounded-lg">Annuler</button>
                        <button @click="deleteExamen" class="px-4 py-2 text-sm text-white bg-red-600 hover:bg-red-700 rounded-lg">Supprimer</button>
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

const props = defineProps({ examens: Array });

const showModalState = ref(false);
const showDeleteModalState = ref(false);
const examenToDelete = ref(null);

const formData = useForm({ id: null, name: '', abr: '', status: true });

const openModal = (examen = null) => {
    if (examen) {
        formData.id = examen.id;
        formData.name = examen.name;
        formData.abr = examen.abr;
        formData.status = examen.status;
    } else {
        formData.reset();
        formData.id = null;
        formData.status = true;
    }
    showModalState.value = true;
};

const submitForm = () => {
    if (formData.id) {
        formData.put(route('laboratoire.analyses.examens.update', formData.id), {
            preserveScroll: true,
            onSuccess: () => { showModalState.value = false; },
        });
    } else {
        formData.post(route('laboratoire.analyses.examens.store'), {
            preserveScroll: true,
            onSuccess: () => { showModalState.value = false; formData.reset(); },
        });
    }
};

const confirmDelete = (examen) => {
    examenToDelete.value = examen;
    showDeleteModalState.value = true;
};

const deleteExamen = () => {
    router.delete(route('laboratoire.analyses.examens.destroy', examenToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => { showDeleteModalState.value = false; },
    });
};
</script>
