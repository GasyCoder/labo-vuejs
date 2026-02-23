<template>
    <Modal :show="show" max-width="2xl" @close="closeModal">
        <div class="bg-white dark:bg-gray-800">
            <div class="px-6 py-6 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white">
                    {{ isEdit ? 'Modifier le prescripteur' : 'Nouveau prescripteur' }}
                </h2>
                <button type="button" @click="closeModal" class="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300 rounded-lg p-2 hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <form @submit.prevent="submit">
                <div class="px-6 py-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Nom -->
                        <div>
                            <InputLabel for="nom" value="Nom *" />
                            <TextInput
                                id="nom"
                                type="text"
                                class="mt-1 block w-full"
                                v-model="form.nom"
                                required
                                :class="{ 'border-red-500': form.errors.nom }"
                            />
                            <InputError :message="form.errors.nom" class="mt-2" />
                        </div>

                        <!-- Prénom -->
                        <div>
                            <InputLabel for="prenom" value="Prénom" />
                            <TextInput
                                id="prenom"
                                type="text"
                                class="mt-1 block w-full"
                                v-model="form.prenom"
                                :class="{ 'border-red-500': form.errors.prenom }"
                            />
                            <InputError :message="form.errors.prenom" class="mt-2" />
                        </div>

                        <!-- Statut -->
                        <div>
                            <InputLabel value="Statut *" class="mb-3" />
                            <div class="flex flex-wrap gap-6">
                                <label v-for="(label, value) in statusOptions" :key="value" class="inline-flex items-center">
                                    <input 
                                        type="radio" 
                                        v-model="form.status" 
                                        :value="value"
                                        class="text-blue-600 border-gray-300 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:focus:ring-blue-400"
                                    >
                                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">{{ label }}</span>
                                </label>
                            </div>
                            <InputError :message="form.errors.status" class="mt-2" />
                        </div>

                        <!-- Téléphone -->
                        <div>
                            <InputLabel for="telephone" value="Téléphone" />
                            <TextInput
                                id="telephone"
                                type="text"
                                class="mt-1 block w-full"
                                v-model="form.telephone"
                                :class="{ 'border-red-500': form.errors.telephone }"
                            />
                            <InputError :message="form.errors.telephone" class="mt-2" />
                        </div>

                        <!-- Commission Quota -->
                        <div>
                            <InputLabel for="commission_quota" value="Quota mensuel de commission (Ar) *" />
                            <TextInput
                                id="commission_quota"
                                type="number"
                                step="0.01"
                                class="mt-1 block w-full"
                                v-model="form.commission_quota"
                                required
                                :class="{ 'border-red-500': form.errors.commission_quota }"
                            />
                            <InputError :message="form.errors.commission_quota" class="mt-2" />
                        </div>

                        <!-- Commission Pourcentage -->
                        <div>
                            <InputLabel for="commission_pourcentage" value="Pourcentage de commission (%) *" />
                            <TextInput
                                id="commission_pourcentage"
                                type="number"
                                step="0.01"
                                class="mt-1 block w-full"
                                v-model="form.commission_pourcentage"
                                required
                                :class="{ 'border-red-500': form.errors.commission_pourcentage }"
                            />
                            <InputError :message="form.errors.commission_pourcentage" class="mt-2" />
                        </div>

                        <!-- Notes -->
                        <div class="md:col-span-2">
                            <InputLabel for="notes" value="Notes" />
                            <textarea
                                id="notes"
                                v-model="form.notes"
                                rows="3"
                                class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm"
                                :class="{ 'border-red-500': form.errors.notes }"
                            ></textarea>
                            <InputError :message="form.errors.notes" class="mt-2" />
                        </div>

                        <!-- Actif -->
                        <div class="md:col-span-2">
                            <label class="flex items-center">
                                <Checkbox name="is_active" v-model:checked="form.is_active" />
                                <span class="ml-2 text-sm text-gray-600 dark:text-gray-400">Prescripteur actif</span>
                            </label>
                            <InputError :message="form.errors.is_active" class="mt-2" />
                        </div>
                    </div>
                </div>

                <div class="bg-gray-50 dark:bg-gray-700/50 px-6 py-4 border-t border-gray-200 dark:border-gray-700 flex justify-end space-x-3">
                    <SecondaryButton @click="closeModal" :disabled="form.processing">
                        Annuler
                    </SecondaryButton>
                    <PrimaryButton type="submit" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                        {{ isEdit ? 'Mettre à jour' : 'Ajouter' }}
                    </PrimaryButton>
                </div>
            </form>
        </div>
    </Modal>
</template>

<script setup>
import { computed, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import Checkbox from '@/Components/Checkbox.vue';

const props = defineProps({
    show: Boolean,
    prescripteur: Object,
    grades: Object,
    statusOptions: Object,
    defaultCommissionQuota: [Number, String],
    defaultCommissionPourcentage: [Number, String],
});

const emit = defineEmits(['close']);

const isEdit = computed(() => !!props.prescripteur);

const form = useForm({
    nom: '',
    prenom: '',
    grade: '',
    status: 'Medecin',
    telephone: '',
    notes: '',
    is_active: true,
    commission_quota: props.defaultCommissionQuota || 250000,
    commission_pourcentage: props.defaultCommissionPourcentage || 10,
});

watch(() => props.show, (show) => {
    if (show) {
        if (props.prescripteur) {
            form.nom = props.prescripteur.nom;
            form.prenom = props.prescripteur.prenom;
            form.grade = props.prescripteur.grade;
            form.status = props.prescripteur.status || 'Medecin';
            form.telephone = props.prescripteur.telephone;
            form.notes = props.prescripteur.notes;
            form.is_active = !!props.prescripteur.is_active;
            form.commission_quota = props.prescripteur.commission_quota;
            form.commission_pourcentage = props.prescripteur.commission_pourcentage;
        } else {
            form.reset();
            form.commission_quota = props.defaultCommissionQuota || 250000;
            form.commission_pourcentage = props.defaultCommissionPourcentage || 10;
        }
        form.clearErrors();
    }
});

const closeModal = () => {
    emit('close');
};

const submit = () => {
    if (isEdit.value) {
        form.put(route('secretaire.prescripteurs.update', props.prescripteur.id), {
            preserveScroll: true,
            onSuccess: () => closeModal(),
        });
    } else {
        form.post(route('secretaire.prescripteurs.store'), {
            preserveScroll: true,
            onSuccess: () => closeModal(),
        });
    }
};
</script>
