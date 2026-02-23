<template>
    <AppLayout>
        <div class="px-3 py-3 space-y-4">
            <div class="mb-3">
                <Link
                    :href="route('secretaire.prescription.index')"
                    class="inline-flex items-center px-3 py-1.5 bg-gray-50 dark:bg-slate-700 text-gray-600 dark:text-slate-300 rounded-lg hover:bg-gray-100 dark:hover:bg-slate-600 transition-colors text-sm"
                >
                    <em class="ni ni-arrow-left mr-1.5 text-xs"></em>
                    Retour a la liste
                </Link>
            </div>

            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-100 dark:border-slate-700 p-4">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h1 class="text-base font-semibold text-slate-800 dark:text-slate-100 flex items-center">
                            <em class="ni ni-dashlite text-primary-500 mr-2 text-sm"></em>
                            Nouvelle Prescription
                        </h1>
                        <p class="text-slate-500 dark:text-slate-400 mt-1 text-xs">
                            Reference: <span class="font-semibold">{{ defaultReference }}</span>
                        </p>
                    </div>
                    <div class="text-right">
                        <div class="text-xs text-slate-500 dark:text-slate-400">{{ nowLabel }}</div>
                        <div class="text-xxs text-slate-400 dark:text-slate-500">Cree par: {{ $page.props.auth.user?.name }}</div>
                    </div>
                </div>

                <div class="relative">
                    <div class="absolute top-4 left-4 right-4 h-0.5 bg-gray-100 dark:bg-slate-600 z-0">
                        <div class="h-full bg-gradient-to-r from-primary-400 to-green-400 transition-all duration-300" :style="{ width: `${progress}%` }"></div>
                    </div>

                    <div class="flex items-center justify-between relative z-10">
                        <div v-for="step in steps" :key="step.key" class="flex flex-col items-center">
                            <button
                                type="button"
                                class="relative w-8 h-8 rounded-full flex items-center justify-center mb-1.5 transition-all duration-200"
                                :class="stepClasses(step)"
                                @click="goToStep(step.key)"
                            >
                                <em :class="`ni ni-${step.icon} text-xs`"></em>
                            </button>
                            <span class="text-xxs font-medium block" :class="stepLabelClasses(step)">
                                {{ step.label }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <section v-if="currentStep === 'patient'" class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200/70 dark:border-slate-700/80 overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-200/60 dark:border-slate-700/70">
                    <h2 class="text-sm font-semibold text-slate-900 dark:text-slate-100">Patient - Selection / creation</h2>
                    <p class="text-xs text-slate-600 dark:text-slate-400 mt-1">Selectionnez un patient existant ou creez un nouveau dossier.</p>
                </div>

                <div class="p-5 space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <button
                            type="button"
                            class="w-full rounded-xl border-2 px-4 py-3 text-left transition"
                            :class="!isNewPatient ? 'border-primary-600 bg-primary-50 dark:bg-primary-900/15 text-primary-700 dark:text-primary-300' : 'border-slate-200 dark:border-slate-600 text-slate-800 dark:text-slate-100'"
                            @click="setPatientMode(false)"
                        >
                            <div class="text-sm font-semibold">Patient existant</div>
                            <div class="text-xs text-slate-600 dark:text-slate-400">Rechercher et selectionner</div>
                        </button>
                        <button
                            type="button"
                            class="w-full rounded-xl border-2 px-4 py-3 text-left transition"
                            :class="isNewPatient ? 'border-emerald-600 bg-emerald-50 dark:bg-emerald-900/15 text-emerald-700 dark:text-emerald-300' : 'border-slate-200 dark:border-slate-600 text-slate-800 dark:text-slate-100'"
                            @click="setPatientMode(true)"
                        >
                            <div class="text-sm font-semibold">Nouveau patient</div>
                            <div class="text-xs text-slate-600 dark:text-slate-400">Creer un dossier</div>
                        </button>
                    </div>

                    <div v-if="!isNewPatient" class="space-y-3">
                        <div class="rounded-xl border border-slate-200/70 dark:border-slate-700/70 bg-white dark:bg-slate-800 p-4">
                            <label class="block text-sm font-semibold text-slate-800 dark:text-slate-200 mb-2">Rechercher un patient</label>
                            <input
                                v-model="patientSearch"
                                type="text"
                                placeholder="Nom, prenom, reference ou telephone..."
                                class="w-full px-4 py-2.5 text-sm rounded-xl bg-white dark:bg-slate-700 border border-slate-200 dark:border-slate-600 text-slate-900 dark:text-slate-100"
                                @input="debouncedPatientSearch"
                            >
                        </div>

                        <div v-if="patientResults.length > 0" class="rounded-xl border border-slate-200/70 dark:border-slate-700/70 bg-white dark:bg-slate-800 p-4">
                            <h3 class="text-sm font-semibold text-slate-900 dark:text-slate-100 mb-2">Resultats</h3>
                            <div class="space-y-2 max-h-72 overflow-auto">
                                <button
                                    v-for="result in patientResults"
                                    :key="result.id"
                                    type="button"
                                    class="w-full text-left p-3 rounded-xl border transition"
                                    :class="selectedPatient?.id === result.id ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/20' : 'border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700/40'"
                                    @click="selectPatient(result)"
                                >
                                    <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ result.nom_complet }}</p>
                                    <p class="text-xs text-slate-500 dark:text-slate-400">
                                        {{ result.numero_dossier || '-' }} | {{ result.telephone || 'Sans telephone' }}
                                    </p>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <input v-model="patientForm.nom" type="text" placeholder="Nom" class="w-full px-4 py-2.5 text-sm rounded-xl bg-white dark:bg-slate-700 border border-slate-200 dark:border-slate-600 text-slate-900 dark:text-slate-100">
                        <input v-model="patientForm.prenom" type="text" placeholder="Prenom" class="w-full px-4 py-2.5 text-sm rounded-xl bg-white dark:bg-slate-700 border border-slate-200 dark:border-slate-600 text-slate-900 dark:text-slate-100">
                        <select v-model="patientForm.civilite" class="w-full px-4 py-2.5 text-sm rounded-xl bg-white dark:bg-slate-700 border border-slate-200 dark:border-slate-600 text-slate-900 dark:text-slate-100">
                            <option v-for="civilite in civilites" :key="civilite" :value="civilite">{{ civilite }}</option>
                        </select>
                        <input v-model="patientForm.date_naissance" type="date" class="w-full px-4 py-2.5 text-sm rounded-xl bg-white dark:bg-slate-700 border border-slate-200 dark:border-slate-600 text-slate-900 dark:text-slate-100" @change="syncAgeFromBirthDate">
                        <input v-model="patientForm.telephone" type="text" placeholder="Telephone" class="w-full px-4 py-2.5 text-sm rounded-xl bg-white dark:bg-slate-700 border border-slate-200 dark:border-slate-600 text-slate-900 dark:text-slate-100">
                        <input v-model="patientForm.email" type="email" placeholder="Email" class="w-full px-4 py-2.5 text-sm rounded-xl bg-white dark:bg-slate-700 border border-slate-200 dark:border-slate-600 text-slate-900 dark:text-slate-100">
                        <input v-model="patientForm.adresse" type="text" placeholder="Adresse" class="w-full md:col-span-2 px-4 py-2.5 text-sm rounded-xl bg-white dark:bg-slate-700 border border-slate-200 dark:border-slate-600 text-slate-900 dark:text-slate-100">
                    </div>

                    <div class="flex justify-end">
                        <button type="button" class="px-4 py-2 bg-primary-500 hover:bg-primary-600 text-white rounded-lg transition-colors text-sm" @click="goToStep('clinique')">
                            Clinique <em class="ni ni-arrow-right ml-1.5 text-xs"></em>
                        </button>
                    </div>
                </div>
            </section>

            <section v-if="currentStep === 'clinique'" class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200/70 dark:border-slate-700/80 overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-200/60 dark:border-slate-700/70 bg-gradient-to-r from-cyan-50 to-blue-50 dark:from-slate-800 dark:to-slate-800">
                    <h2 class="text-sm font-semibold text-slate-900 dark:text-slate-100">Informations cliniques</h2>
                </div>
                <div class="p-5 grid grid-cols-1 md:grid-cols-2 gap-4">
                    <select v-model="clinicalForm.prescripteur_id" class="w-full px-4 py-2.5 text-sm rounded-xl bg-white dark:bg-slate-700 border border-slate-200 dark:border-slate-600 text-slate-900 dark:text-slate-100">
                        <option value="">Selectionner un prescripteur</option>
                        <option v-for="prescripteur in prescripteurs" :key="prescripteur.id" :value="prescripteur.id">
                            {{ prescripteur.nom_complet }}
                        </option>
                    </select>
                    <select v-model="clinicalForm.patient_type" class="w-full px-4 py-2.5 text-sm rounded-xl bg-white dark:bg-slate-700 border border-slate-200 dark:border-slate-600 text-slate-900 dark:text-slate-100">
                        <option value="EXTERNE">Externe</option>
                        <option value="HOSPITALISE">Hospitalise</option>
                        <option value="URGENCE-JOUR">Urgence Jour</option>
                        <option value="URGENCE-NUIT">Urgence Nuit</option>
                    </select>
                    <input v-model.number="clinicalForm.age" type="number" min="0" max="150" placeholder="Age" class="w-full px-4 py-2.5 text-sm rounded-xl bg-white dark:bg-slate-700 border border-slate-200 dark:border-slate-600 text-slate-900 dark:text-slate-100">
                    <select v-model="clinicalForm.unite_age" class="w-full px-4 py-2.5 text-sm rounded-xl bg-white dark:bg-slate-700 border border-slate-200 dark:border-slate-600 text-slate-900 dark:text-slate-100">
                        <option value="Ans">Ans</option>
                        <option value="Mois">Mois</option>
                        <option value="Jours">Jours</option>
                    </select>
                    <input v-model.number="clinicalForm.poids" type="number" min="0" step="0.1" placeholder="Poids (kg)" class="w-full px-4 py-2.5 text-sm rounded-xl bg-white dark:bg-slate-700 border border-slate-200 dark:border-slate-600 text-slate-900 dark:text-slate-100">
                    <textarea v-model="clinicalForm.renseignement_clinique" rows="3" placeholder="Renseignement clinique" class="w-full md:col-span-2 px-4 py-2.5 text-sm rounded-xl bg-white dark:bg-slate-700 border border-slate-200 dark:border-slate-600 text-slate-900 dark:text-slate-100"></textarea>
                </div>
                <div class="px-5 pb-5 flex justify-between">
                    <button type="button" class="px-3 py-2 bg-gray-100 dark:bg-slate-700 text-gray-700 dark:text-slate-300 rounded-lg hover:bg-gray-200 dark:hover:bg-slate-600 transition-colors text-sm" @click="goToStep('patient')">
                        <em class="ni ni-arrow-left mr-1.5 text-xs"></em>Patient
                    </button>
                    <button type="button" class="px-4 py-2 bg-primary-500 hover:bg-primary-600 text-white rounded-lg transition-colors text-sm" @click="goToStep('analyses')">
                        Analyses <em class="ni ni-arrow-right ml-1.5 text-xs"></em>
                    </button>
                </div>
            </section>

            <section v-if="currentStep === 'analyses'" class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-100 dark:border-slate-700 p-4">
                <h2 class="text-base font-semibold text-slate-800 dark:text-slate-100 mb-4">
                    <em class="ni ni-test-tube text-green-500 text-sm mr-2"></em>Recherche Analyses
                </h2>
                <input
                    v-model="analyseSearch"
                    type="text"
                    placeholder="Code ou designation..."
                    class="w-full px-4 py-2.5 border border-gray-200 dark:border-slate-600 rounded-lg text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100"
                    @input="debouncedAnalysesSearch"
                >
                <div v-if="analyseResults.length > 0" class="mt-3 space-y-2 max-h-80 overflow-auto">
                    <div v-for="analyse in analyseResults" :key="analyse.id" class="p-3 rounded-lg border border-slate-200 dark:border-slate-700 flex items-center justify-between">
                        <div>
                            <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ analyse.designation }}</p>
                            <p class="text-xs text-slate-500 dark:text-slate-400">
                                {{ analyse.code }} | {{ formatCurrency(analyse.prix) }}
                            </p>
                        </div>
                        <button type="button" class="px-2.5 py-1.5 text-xs rounded bg-primary-500 hover:bg-primary-600 text-white" @click="addAnalyse(analyse)">
                            Ajouter
                        </button>
                    </div>
                </div>
                <div class="mt-4 p-3 rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-700/40">
                    <p class="text-sm font-medium text-slate-800 dark:text-slate-100">Analyses selectionnees</p>
                    <div v-if="selectedAnalyses.length === 0" class="text-xs text-slate-500 dark:text-slate-400 mt-1">Aucune analyse selectionnee</div>
                    <div v-else class="mt-2 space-y-2">
                        <div v-for="analyse in selectedAnalyses" :key="analyse.id" class="flex items-center justify-between text-sm">
                            <span class="text-slate-700 dark:text-slate-300">{{ analyse.designation }}</span>
                            <button type="button" class="text-red-600 hover:text-red-700 text-xs" @click="removeAnalyse(analyse.id)">Retirer</button>
                        </div>
                    </div>
                </div>
                <div class="mt-4 flex justify-between">
                    <button type="button" class="px-3 py-2 bg-gray-100 dark:bg-slate-700 text-gray-700 dark:text-slate-300 rounded-lg hover:bg-gray-200 dark:hover:bg-slate-600 transition-colors text-sm" @click="goToStep('clinique')">
                        <em class="ni ni-arrow-left mr-1.5 text-xs"></em>Clinique
                    </button>
                    <button type="button" class="px-4 py-2 bg-primary-500 hover:bg-primary-600 text-white rounded-lg transition-colors text-sm" @click="goToStep('prelevements')">
                        Prelevements <em class="ni ni-arrow-right ml-1.5 text-xs"></em>
                    </button>
                </div>
            </section>

            <section v-if="currentStep === 'prelevements'" class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-100 dark:border-slate-700 p-4">
                <h2 class="text-base font-semibold text-slate-800 dark:text-slate-100">
                    <em class="ni ni-package text-yellow-500 text-sm mr-2"></em>Prelevements
                </h2>
                <div class="mt-3">
                    <input
                        v-model="prelevementSearch"
                        type="text"
                        placeholder="Rechercher un prelevement..."
                        class="w-full px-4 py-2.5 border border-gray-200 dark:border-slate-600 rounded-lg text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100"
                        @input="debouncedPrelevementsSearch"
                    >
                </div>
                <div v-if="prelevementResults.length > 0" class="mt-3 space-y-2 max-h-72 overflow-auto">
                    <div v-for="prelevement in prelevementResults" :key="prelevement.id" class="p-3 rounded-lg border border-slate-200 dark:border-slate-700 flex items-center justify-between">
                        <div>
                            <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ prelevement.denomination }}</p>
                            <p class="text-xs text-slate-500 dark:text-slate-400">{{ formatCurrency(prelevement.prix) }}</p>
                        </div>
                        <button type="button" class="px-2.5 py-1.5 text-xs rounded bg-primary-500 hover:bg-primary-600 text-white" @click="addPrelevement(prelevement)">
                            Ajouter
                        </button>
                    </div>
                </div>
                <div class="mt-4 p-3 rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-700/40">
                    <p class="text-sm font-medium text-slate-800 dark:text-slate-100">Prelevements selectionnes</p>
                    <div v-if="selectedPrelevements.length === 0" class="text-xs text-slate-500 dark:text-slate-400 mt-1">Aucun prelevement selectionne</div>
                    <div v-else class="mt-2 space-y-2">
                        <div v-for="prelevement in selectedPrelevements" :key="prelevement.id" class="flex items-center justify-between gap-2">
                            <span class="text-sm text-slate-700 dark:text-slate-300">{{ prelevement.denomination }}</span>
                            <div class="flex items-center gap-2">
                                <input v-model.number="prelevement.quantite" type="number" min="1" max="10" class="w-16 px-2 py-1 text-xs rounded border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100">
                                <button type="button" class="text-red-600 hover:text-red-700 text-xs" @click="removePrelevement(prelevement.id)">Retirer</button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="mt-4 flex justify-between">
                    <button type="button" class="px-3 py-2 bg-gray-100 dark:bg-slate-700 text-gray-700 dark:text-slate-300 rounded-lg hover:bg-gray-200 dark:hover:bg-slate-600 transition-colors text-sm" @click="goToStep('analyses')">
                        <em class="ni ni-arrow-left mr-1.5 text-xs"></em>Analyses
                    </button>
                    <button type="button" class="px-4 py-2 bg-primary-500 hover:bg-primary-600 text-white rounded-lg transition-colors text-sm" @click="goToStep('paiement')">
                        Paiement <em class="ni ni-arrow-right ml-1.5 text-xs"></em>
                    </button>
                </div>
            </section>

            <section v-if="currentStep === 'paiement'" class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-100 dark:border-slate-700 p-4">
                <h2 class="text-base font-semibold text-slate-800 dark:text-slate-100">
                    <em class="ni ni-coin text-red-500 text-sm mr-2"></em>Paiement & Facturation
                </h2>
                <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                    <select v-model="paymentForm.payment_method" class="w-full px-4 py-2.5 text-sm rounded-xl bg-white dark:bg-slate-700 border border-slate-200 dark:border-slate-600 text-slate-900 dark:text-slate-100">
                        <option value="">Mode de paiement</option>
                        <option v-for="method in paymentMethods" :key="method.code" :value="method.code">{{ method.label }}</option>
                    </select>
                    <input v-model.number="paymentForm.remise" type="number" min="0" max="100" placeholder="Remise (%)" class="w-full px-4 py-2.5 text-sm rounded-xl bg-white dark:bg-slate-700 border border-slate-200 dark:border-slate-600 text-slate-900 dark:text-slate-100">
                    <label class="inline-flex items-center gap-2 text-sm text-slate-700 dark:text-slate-300">
                        <input v-model="paymentForm.paiement_statut" type="checkbox" class="rounded border-slate-300 dark:border-slate-600">
                        Marquer comme paye
                    </label>
                </div>
                <div class="mt-4 p-3 rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-700/40 text-sm">
                    <div class="flex justify-between"><span>Sous-total analyses</span><span>{{ formatCurrency(analysesSubtotal) }}</span></div>
                    <div class="flex justify-between"><span>Sous-total prelevements</span><span>{{ formatCurrency(prelevementsSubtotal) }}</span></div>
                    <div class="flex justify-between"><span>Frais urgence</span><span>{{ formatCurrency(urgencyFee) }}</span></div>
                    <div class="flex justify-between"><span>Remise</span><span>- {{ formatCurrency(remiseAmount) }}</span></div>
                    <div class="flex justify-between font-semibold border-t border-slate-300 dark:border-slate-600 mt-2 pt-2">
                        <span>Total</span>
                        <span>{{ formatCurrency(totalDue) }}</span>
                    </div>
                </div>
                <div class="mt-4 flex justify-between">
                    <button type="button" class="px-3 py-2 bg-gray-100 dark:bg-slate-700 text-gray-700 dark:text-slate-300 rounded-lg hover:bg-gray-200 dark:hover:bg-slate-600 transition-colors text-sm" @click="goToStep('prelevements')">
                        <em class="ni ni-arrow-left mr-1.5 text-xs"></em>Prelevements
                    </button>
                    <button type="button" class="px-4 py-2 bg-primary-500 hover:bg-primary-600 text-white rounded-lg transition-colors text-sm" :disabled="isSubmitting" @click="submitPrescription">
                        <span v-if="isSubmitting">Enregistrement...</span>
                        <span v-else>Terminer <em class="ni ni-check-circle ml-1.5 text-xs"></em></span>
                    </button>
                </div>
            </section>
        </div>
    </AppLayout>
</template>

<script setup>
import { computed, ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    prescripteurs: { type: Array, default: () => [] },
    paymentMethods: { type: Array, default: () => [] },
    defaultReference: { type: String, default: '' },
    urgenceFees: { type: Object, default: () => ({ jour: 0, nuit: 0 }) },
    civilites: { type: Array, default: () => [] },
});

const steps = [
    { key: 'patient', icon: 'user', label: 'Patient' },
    { key: 'clinique', icon: 'list-round', label: 'Clinique' },
    { key: 'analyses', icon: 'filter', label: 'Analyses' },
    { key: 'prelevements', icon: 'package', label: 'Prelevements' },
    { key: 'paiement', icon: 'coin-alt', label: 'Paiement' },
];

const currentStep = ref('patient');
const isNewPatient = ref(false);
const isSubmitting = ref(false);

const patientSearch = ref('');
const patientResults = ref([]);
const selectedPatient = ref(null);
let patientSearchTimer = null;

const analyseSearch = ref('');
const analyseResults = ref([]);
const selectedAnalyses = ref([]);
let analysesSearchTimer = null;

const prelevementSearch = ref('');
const prelevementResults = ref([]);
const selectedPrelevements = ref([]);
let prelevementsSearchTimer = null;

const patientForm = ref({
    nom: '',
    prenom: '',
    civilite: props.civilites[0] || 'Monsieur',
    date_naissance: '',
    telephone: '',
    email: '',
    adresse: '',
});

const clinicalForm = ref({
    prescripteur_id: '',
    patient_type: 'EXTERNE',
    age: 0,
    unite_age: 'Ans',
    poids: '',
    renseignement_clinique: '',
});

const paymentForm = ref({
    payment_method: props.paymentMethods[0]?.code || '',
    remise: 0,
    paiement_statut: true,
});

const nowLabel = computed(() => new Date().toLocaleString('fr-FR'));
const currentStepIndex = computed(() => steps.findIndex((step) => step.key === currentStep.value));
const progress = computed(() => {
    if (steps.length <= 1) {
        return 0;
    }

    return (currentStepIndex.value / (steps.length - 1)) * 100;
});

const analysesSubtotal = computed(() => {
    let total = 0;
    const countedParents = new Set();

    selectedAnalyses.value.forEach((analyse) => {
        if (analyse.level === 'PARENT') {
            total += Number(analyse.prix || 0);
            countedParents.add(analyse.id);

            return;
        }

        if (analyse.parent_id && countedParents.has(analyse.parent_id)) {
            return;
        }

        if (analyse.parent_id && analyse.parent && Number(analyse.parent.prix || 0) > 0) {
            total += Number(analyse.parent.prix || 0);
            countedParents.add(analyse.parent_id);

            return;
        }

        total += Number(analyse.prix || 0);
    });

    return total;
});

const prelevementsSubtotal = computed(() => {
    return selectedPrelevements.value.reduce((total, prelevement) => {
        const quantity = Math.max(1, Number(prelevement.quantite || 1));
        const promo = Number(prelevement.prix_promotion || 0);
        const unit = quantity > 1 && promo > 0 ? promo : Number(prelevement.prix || 0);

        return total + (unit * quantity);
    }, 0);
});

const urgencyFee = computed(() => {
    if (clinicalForm.value.patient_type === 'URGENCE-JOUR') {
        return Number(props.urgenceFees.jour || 0);
    }

    if (clinicalForm.value.patient_type === 'URGENCE-NUIT') {
        return Number(props.urgenceFees.nuit || 0);
    }

    return 0;
});

const remiseAmount = computed(() => {
    const percent = Math.max(0, Number(paymentForm.value.remise || 0));
    const servicesTotal = analysesSubtotal.value + prelevementsSubtotal.value;

    return servicesTotal * (percent / 100);
});

const totalDue = computed(() => {
    const servicesTotal = analysesSubtotal.value + prelevementsSubtotal.value;

    return Math.max(0, (servicesTotal - remiseAmount.value) + urgencyFee.value);
});

const formatCurrency = (value) => {
    return `${Number(value || 0).toLocaleString('fr-FR')} Ar`;
};

const stepClasses = (step) => {
    const stepIndex = steps.findIndex((item) => item.key === step.key);

    if (stepIndex < currentStepIndex.value) {
        return 'bg-green-500 text-white shadow-sm';
    }

    if (step.key === currentStep.value) {
        return 'bg-primary-500 text-white shadow-md scale-110';
    }

    return 'bg-gray-100 dark:bg-slate-700 text-gray-600 dark:text-slate-300 hover:bg-gray-200 dark:hover:bg-slate-600';
};

const stepLabelClasses = (step) => {
    const stepIndex = steps.findIndex((item) => item.key === step.key);

    if (stepIndex < currentStepIndex.value) {
        return 'text-green-600 dark:text-green-400';
    }

    if (step.key === currentStep.value) {
        return 'text-primary-600 dark:text-primary-400';
    }

    return 'text-slate-500 dark:text-slate-400';
};

const setPatientMode = (newMode) => {
    isNewPatient.value = newMode;
    if (newMode) {
        selectedPatient.value = null;
        patientResults.value = [];
    }
};

const selectPatient = (patient) => {
    selectedPatient.value = patient;
    isNewPatient.value = false;
    patientForm.value = {
        nom: patient.nom || '',
        prenom: patient.prenom || '',
        civilite: patient.civilite || (props.civilites[0] || 'Monsieur'),
        date_naissance: patient.date_naissance || '',
        telephone: patient.telephone || '',
        email: patient.email || '',
        adresse: patient.adresse || '',
    };
    clinicalForm.value.age = Number(patient.age || 0);
    clinicalForm.value.unite_age = patient.unite_age || 'Ans';
};

const syncAgeFromBirthDate = () => {
    const date = patientForm.value.date_naissance;
    if (!date) {
        return;
    }

    const birth = new Date(date);
    const now = new Date();
    const dayMs = 24 * 60 * 60 * 1000;
    const days = Math.floor((now.getTime() - birth.getTime()) / dayMs);
    const months = Math.floor(days / 30.4375);
    const years = Math.floor(months / 12);

    if (days <= 60) {
        clinicalForm.value.age = days;
        clinicalForm.value.unite_age = 'Jours';

        return;
    }

    if (months < 24) {
        clinicalForm.value.age = months;
        clinicalForm.value.unite_age = 'Mois';

        return;
    }

    clinicalForm.value.age = years;
    clinicalForm.value.unite_age = 'Ans';
};

const goToStep = async (stepKey) => {
    if (stepKey === 'clinique' && !patientForm.value.nom.trim()) {
        return;
    }

    if (stepKey === 'analyses' && !clinicalForm.value.prescripteur_id) {
        return;
    }

    if (stepKey === 'prelevements' && selectedAnalyses.value.length === 0) {
        return;
    }

    if (stepKey === 'paiement' && selectedAnalyses.value.length === 0) {
        return;
    }

    currentStep.value = stepKey;

    if (stepKey === 'prelevements' && prelevementResults.value.length === 0) {
        await fetchPrelevements('');
    }
};

const fetchPatients = async (term) => {
    const response = await fetch(`${route('secretaire.prescription.lookup.patients')}?q=${encodeURIComponent(term)}`, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
    });
    const payload = await response.json();
    patientResults.value = payload.data || [];
};

const fetchAnalyses = async (term) => {
    const response = await fetch(`${route('secretaire.prescription.lookup.analyses')}?q=${encodeURIComponent(term)}`, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
    });
    const payload = await response.json();
    analyseResults.value = payload.data || [];
};

const fetchPrelevements = async (term) => {
    const response = await fetch(`${route('secretaire.prescription.lookup.prelevements')}?q=${encodeURIComponent(term)}`, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
    });
    const payload = await response.json();
    prelevementResults.value = payload.data || [];
};

const debouncedPatientSearch = () => {
    if (patientSearchTimer) {
        window.clearTimeout(patientSearchTimer);
    }
    patientSearchTimer = window.setTimeout(async () => {
        if (patientSearch.value.trim().length < 2) {
            patientResults.value = [];

            return;
        }
        await fetchPatients(patientSearch.value.trim());
    }, 300);
};

const debouncedAnalysesSearch = () => {
    if (analysesSearchTimer) {
        window.clearTimeout(analysesSearchTimer);
    }
    analysesSearchTimer = window.setTimeout(async () => {
        if (analyseSearch.value.trim().length < 2) {
            analyseResults.value = [];

            return;
        }
        await fetchAnalyses(analyseSearch.value.trim());
    }, 250);
};

const debouncedPrelevementsSearch = () => {
    if (prelevementsSearchTimer) {
        window.clearTimeout(prelevementsSearchTimer);
    }
    prelevementsSearchTimer = window.setTimeout(async () => {
        await fetchPrelevements(prelevementSearch.value.trim());
    }, 250);
};

const addAnalyse = (analyse) => {
    if (selectedAnalyses.value.some((item) => item.id === analyse.id)) {
        return;
    }

    selectedAnalyses.value.push(analyse);
};

const removeAnalyse = (analyseId) => {
    selectedAnalyses.value = selectedAnalyses.value.filter((analyse) => analyse.id !== analyseId);
};

const addPrelevement = (prelevement) => {
    if (selectedPrelevements.value.some((item) => item.id === prelevement.id)) {
        return;
    }

    selectedPrelevements.value.push({
        ...prelevement,
        quantite: 1,
    });
};

const removePrelevement = (prelevementId) => {
    selectedPrelevements.value = selectedPrelevements.value.filter((prelevement) => prelevement.id !== prelevementId);
};

const submitPrescription = () => {
    if (!patientForm.value.nom.trim() || !clinicalForm.value.prescripteur_id || selectedAnalyses.value.length === 0 || !paymentForm.value.payment_method) {
        return;
    }

    isSubmitting.value = true;

    const payload = {
        patient_id: selectedPatient.value?.id ?? null,
        patient: {
            nom: patientForm.value.nom,
            prenom: patientForm.value.prenom,
            civilite: patientForm.value.civilite,
            date_naissance: patientForm.value.date_naissance || null,
            telephone: patientForm.value.telephone || null,
            email: patientForm.value.email || null,
            adresse: patientForm.value.adresse || null,
        },
        prescripteur_id: Number(clinicalForm.value.prescripteur_id),
        patient_type: clinicalForm.value.patient_type,
        age: Number(clinicalForm.value.age || 0),
        unite_age: clinicalForm.value.unite_age,
        poids: clinicalForm.value.poids === '' ? null : Number(clinicalForm.value.poids),
        renseignement_clinique: clinicalForm.value.renseignement_clinique || null,
        analyse_ids: selectedAnalyses.value.map((analyse) => analyse.id),
        prelevements: selectedPrelevements.value.map((prelevement) => ({
            id: prelevement.id,
            quantite: Math.max(1, Number(prelevement.quantite || 1)),
        })),
        payment_method: paymentForm.value.payment_method,
        remise: Math.max(0, Number(paymentForm.value.remise || 0)),
        paiement_statut: !!paymentForm.value.paiement_statut,
    };

    router.post(route('secretaire.prescription.store'), payload, {
        preserveScroll: true,
        onFinish: () => {
            isSubmitting.value = false;
        },
    });
};
</script>
