<template>
    <Modal :show="show" max-width="lg" @close="closeModal">
        <div class="bg-white dark:bg-gray-800">
            <div class="px-6 py-6">
                <div class="flex items-center mb-4">
                    <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 dark:bg-red-900/30">
                        <svg class="h-6 w-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                        </svg>
                    </div>
                </div>
                
                <div class="text-center">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">Déplacer vers la corbeille</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                        Êtes-vous sûr de vouloir déplacer <strong>{{ prescripteur?.nom_complet }}</strong> vers la corbeille ?
                        <template v-if="prescripteur && prescripteur.total_prescriptions > 0">
                            <br>
                            <span class="text-orange-600 dark:text-orange-400 font-medium">
                                Ce prescripteur a {{ prescripteur.total_prescriptions }} prescription(s) associée(s).
                            </span>
                        </template>
                        <br>
                        <span class="text-gray-600 dark:text-gray-400 text-xs mt-2 block">
                            Le prescripteur sera archivé et pourra être restauré ultérieurement.
                        </span>
                    </p>
                </div>
            </div>

            <div class="bg-gray-50 dark:bg-gray-700/50 px-6 py-4 border-t border-gray-200 dark:border-gray-700 flex justify-end space-x-3">
                <SecondaryButton @click="closeModal" :disabled="form.processing">
                    Annuler
                </SecondaryButton>
                
                <DangerButton @click="deletePrescripteur" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                    Déplacer vers la corbeille
                </DangerButton>
            </div>
        </div>
    </Modal>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';

const props = defineProps({
    show: Boolean,
    prescripteur: Object,
});

const emit = defineEmits(['close']);

const form = useForm({});

const closeModal = () => {
    emit('close');
};

const deletePrescripteur = () => {
    if (props.prescripteur) {
        form.delete(route('secretaire.prescripteurs.destroy', props.prescripteur.id), {
            preserveScroll: true,
            onSuccess: () => closeModal(),
        });
    }
};
</script>
