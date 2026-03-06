<template>
    <Modal :show="show" max-width="2xl" @close="closeModal">
        <div class="bg-white dark:bg-gray-800 rounded-xl overflow-hidden shadow-2xl">
            <!-- Header avec dégradé subtil -->
            <div class="px-6 py-5 border-b border-gray-200 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800/50 flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 rounded-lg bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center text-blue-600 dark:text-blue-400">
                        <svg v-if="isEdit" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                        </svg>
                        <svg v-else class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white leading-none">
                            {{ isEdit ? 'Modifier le prescripteur' : 'Nouveau prescripteur' }}
                        </h2>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            {{ isEdit ? 'Mise à jour des informations professionnelles' : 'Enregistrement d\'un nouveau partenaire médical' }}
                        </p>
                    </div>
                </div>
                <button type="button" @click="closeModal" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 rounded-full p-2 hover:bg-gray-200 dark:hover:bg-gray-700 transition-all duration-200">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <form @submit.prevent="submit">
                <div class="px-8 py-8 space-y-8 max-h-[70vh] overflow-y-auto custom-scrollbar">
                    
                    <!-- Section Rôle / Statut -->
                    <div>
                        <div class="flex items-center justify-between mb-4">
                            <InputLabel value="Quel est son rôle ? *" class="text-xs uppercase tracking-widest font-bold text-blue-600 dark:text-blue-400" />
                            <span class="text-[10px] text-gray-400 italic">Sélection obligatoire</span>
                        </div>
                        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-3">
                            <button 
                                v-for="(label, value) in statusOptions" 
                                :key="value"
                                type="button"
                                @click="form.status = value"
                                :class="[
                                    form.status === value 
                                        ? getStatusColorClasses(value) 
                                        : 'bg-white dark:bg-gray-900 border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-400 hover:border-blue-300 dark:hover:border-blue-800 hover:bg-blue-50/30 dark:hover:bg-blue-900/10',
                                    'flex flex-col items-center justify-center p-3 rounded-xl border-2 transition-all duration-200 group relative'
                                ]"
                            >
                                <div :class="[
                                    form.status === value ? 'scale-110' : 'scale-100',
                                    'transition-transform duration-200 mb-2'
                                ]">
                                    <component :is="getStatusIcon(value)" class="w-6 h-6" />
                                </div>
                                <span class="text-[11px] font-bold text-center leading-tight">{{ label }}</span>
                                
                                <!-- Indicateur de sélection -->
                                <div v-if="form.status === value" class="absolute -top-1.5 -right-1.5 w-5 h-5 bg-white dark:bg-gray-800 rounded-full flex items-center justify-center shadow-md border-2 border-current">
                                    <svg class="w-3 h-3 text-current" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                            </button>
                        </div>
                        <InputError :message="form.errors.status" class="mt-2" />
                    </div>

                    <!-- Section Identité -->
                    <div class="space-y-4">
                        <div class="flex items-center space-x-2 pb-2 border-b border-gray-100 dark:border-gray-700">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                            <h3 class="text-sm font-bold text-gray-700 dark:text-gray-300">Identité & Titre</h3>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div>
                                <InputLabel for="nom" value="Nom de famille *" />
                                <div class="mt-1 relative rounded-md shadow-sm">
                                    <TextInput
                                        id="nom"
                                        type="text"
                                        class="block w-full"
                                        v-model="form.nom"
                                        placeholder="Ex: RAKOTO"
                                        required
                                        :class="{ 'border-red-500 ring-red-500': form.errors.nom }"
                                    />
                                </div>
                                <InputError :message="form.errors.nom" class="mt-2" />
                            </div>

                            <div>
                                <InputLabel for="prenom" value="Prénoms" />
                                <TextInput
                                    id="prenom"
                                    type="text"
                                    class="mt-1 block w-full"
                                    v-model="form.prenom"
                                    placeholder="Ex: Jean Luc"
                                />
                                <InputError :message="form.errors.prenom" class="mt-2" />
                            </div>

                            <div>
                                <InputLabel for="grade" value="Titre / Grade" />
                                <select 
                                    id="grade"
                                    v-model="form.grade"
                                    class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-xl shadow-sm transition-all"
                                >
                                    <option value="">Sélectionner un titre</option>
                                    <option v-for="(label, value) in grades" :key="value" :value="value">{{ label }}</option>
                                    <option value="Autre">Autre...</option>
                                </select>
                                <InputError :message="form.errors.grade" class="mt-2" />
                            </div>

                            <div>
                                <InputLabel for="telephone" value="Numéro de téléphone" />
                                <div class="mt-1 relative rounded-md shadow-sm">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <svg class="h-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                        </svg>
                                    </div>
                                    <TextInput
                                        id="telephone"
                                        type="tel"
                                        class="block w-full pl-10"
                                        v-model="form.telephone"
                                        placeholder="034 XX XXX XX"
                                    />
                                </div>
                                <InputError :message="form.errors.telephone" class="mt-2" />
                            </div>
                        </div>
                    </div>

                    <!-- Section Notes & Paramètres -->
                    <div class="space-y-4">
                        <div class="flex items-center space-x-2 pb-2 border-b border-gray-100 dark:border-gray-700">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            <h3 class="text-sm font-bold text-gray-700 dark:text-gray-300">Notes & Configuration</h3>
                        </div>

                        <div class="space-y-5">
                            <div>
                                <InputLabel for="notes" value="Notes ou Observations" />
                                <textarea
                                    id="notes"
                                    v-model="form.notes"
                                    rows="2"
                                    class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-xl shadow-sm transition-all"
                                    placeholder="Informations complémentaires, spécialité, etc."
                                ></textarea>
                                <InputError :message="form.errors.notes" class="mt-2" />
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div 
                                    @click="form.is_active = !form.is_active"
                                    :class="[
                                        form.is_active ? 'bg-green-50 dark:bg-green-900/10 border-green-200 dark:border-green-800' : 'bg-gray-50 dark:bg-gray-900 border-gray-200 dark:border-gray-700',
                                        'p-4 rounded-xl border-2 cursor-pointer transition-all flex items-center space-x-3'
                                    ]"
                                >
                                    <div :class="[
                                        form.is_active ? 'bg-green-500' : 'bg-gray-300 dark:bg-gray-600',
                                        'w-5 h-5 rounded-md flex items-center justify-center text-white transition-colors'
                                    ]">
                                        <svg v-if="form.is_active" class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                    <span :class="form.is_active ? 'text-green-800 dark:text-green-300' : 'text-gray-600 dark:text-gray-400'" class="text-sm font-bold">Compte Actif</span>
                                </div>

                                <div 
                                    @click="form.is_commissionned = !form.is_commissionned"
                                    :class="[
                                        form.is_commissionned ? 'bg-blue-50 dark:bg-blue-900/10 border-blue-200 dark:border-blue-800' : 'bg-gray-50 dark:bg-gray-900 border-gray-200 dark:border-gray-700',
                                        'p-4 rounded-xl border-2 cursor-pointer transition-all flex items-center space-x-3'
                                    ]"
                                >
                                    <div :class="[
                                        form.is_commissionned ? 'bg-blue-500' : 'bg-gray-300 dark:bg-gray-600',
                                        'w-5 h-5 rounded-md flex items-center justify-center text-white transition-colors'
                                    ]">
                                        <svg v-if="form.is_commissionned" class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                    <span :class="form.is_commissionned ? 'text-blue-800 dark:text-blue-300' : 'text-gray-600 dark:text-gray-400'" class="text-sm font-bold">Gère des Commissions</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer amélioré -->
                <div class="bg-gray-50 dark:bg-gray-800/80 px-8 py-5 border-t border-gray-200 dark:border-gray-700 flex flex-col sm:flex-row items-center justify-between gap-4">
                    <p class="text-[10px] text-gray-400 text-center sm:text-left italic">
                        Les champs marqués d'une étoile (*) sont obligatoires.
                    </p>
                    <div class="flex items-center space-x-3 w-full sm:w-auto">
                        <SecondaryButton @click="closeModal" :disabled="form.processing" class="w-full sm:w-auto justify-center">
                            Annuler
                        </SecondaryButton>
                        <PrimaryButton 
                            type="submit" 
                            :class="{ 'opacity-25': form.processing }" 
                            :disabled="form.processing"
                            class="w-full sm:w-auto justify-center bg-blue-600 hover:bg-blue-700 shadow-blue-500/20"
                        >
                            <svg v-if="form.processing" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            {{ isEdit ? 'Mettre à jour' : 'Enregistrer' }}
                        </PrimaryButton>
                    </div>
                </div>
            </form>
        </div>
    </Modal>
</template>

<script setup>
import { computed, watch, h } from 'vue';
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
    is_commissionned: true,
});

// Mapping des icônes par statut
const getStatusIcon = (status) => {
    switch(status) {
        case 'Professeur': return () => h('svg', { fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24', 'stroke-width': '2', class: 'w-6 h-6' }, [h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', d: 'M12 14l9-5-9-5-9 5 9 5z' }), h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', d: 'M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z' })]);
        case 'Biologiste': return () => h('svg', { fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24', 'stroke-width': '2', class: 'w-6 h-6' }, [h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', d: 'M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.691.34a2 2 0 01-1.783 0l-.691-.34a6 6 0 00-3.86-.517l-2.388.477a2 2 0 00-1.022.547l-1.16 1.16a2 2 0 00.586 3.414l7.17 1.792a4 4 0 002.424 0l7.17-1.792a2 2 0 00.586-3.414l-1.16-1.16zM12 10V4m-4 3h8' })]);
        case 'Infirmier': return () => h('svg', { fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24', 'stroke-width': '2', class: 'w-6 h-6' }, [h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', d: 'M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z' })]);
        case 'Partenaires': return () => h('svg', { fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24', 'stroke-width': '2', class: 'w-6 h-6' }, [h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', d: 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z' })]);
        default: return () => h('svg', { fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24', 'stroke-width': '2', class: 'w-6 h-6' }, [h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', d: 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z' })]);
    }
};

// Mapping des couleurs par statut
const getStatusColorClasses = (status) => {
    switch(status) {
        case 'Professeur': return 'bg-purple-50 dark:bg-purple-900/20 border-purple-500 text-purple-700 dark:text-purple-300';
        case 'Biologiste': return 'bg-teal-50 dark:bg-teal-900/20 border-teal-500 text-teal-700 dark:text-teal-300';
        case 'Infirmier': return 'bg-orange-50 dark:bg-orange-900/20 border-orange-500 text-orange-700 dark:text-orange-300';
        case 'Partenaires': return 'bg-pink-50 dark:bg-pink-900/20 border-pink-500 text-pink-700 dark:text-pink-300';
        default: return 'bg-blue-50 dark:bg-blue-900/20 border-blue-500 text-blue-700 dark:text-blue-300';
    }
};

watch(() => props.show, (show) => {
    if (show) {
        if (props.prescripteur) {
            form.nom = props.prescripteur.nom;
            form.prenom = props.prescripteur.prenom;
            form.grade = props.prescripteur.grade || '';
            form.status = props.prescripteur.status || 'Medecin';
            form.telephone = props.prescripteur.telephone;
            form.notes = props.prescripteur.notes;
            form.is_active = !!props.prescripteur.is_active;
            form.is_commissionned = !!props.prescripteur.is_commissionned;
        } else {
            form.reset();
            form.status = 'Medecin';
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

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
    width: 6px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #e2e8f0;
    border-radius: 10px;
}
.dark .custom-scrollbar::-webkit-scrollbar-thumb {
    background: #334155;
}
</style>
