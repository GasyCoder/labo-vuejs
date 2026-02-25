<template>
    <AppLayout>
        <div class="px-3 py-3 space-y-4">
            <div class="mb-3">
                <Link
                    :href="route('secretaire.prescription.index')"
                    class="inline-flex items-center rounded-lg bg-slate-50 px-3 py-1.5 text-sm text-slate-600 transition-colors hover:bg-slate-100 dark:bg-slate-700 dark:text-slate-300 dark:hover:bg-slate-600"
                >
                    <em class="ni ni-arrow-left mr-1.5 text-xs"></em>Retour a la liste
                </Link>
            </div>

            <div class="rounded-xl border border-slate-200/70 bg-white p-4 shadow-sm dark:border-slate-700/80 dark:bg-slate-800">
                <div class="mb-4 flex items-center justify-between">
                    <div>
                        <h1 class="flex items-center text-base font-semibold text-slate-800 dark:text-slate-100">
                            <em class="ni ni-dashlite mr-2 text-sm text-primary-500"></em>Nouvelle Prescription
                        </h1>
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                            Reference: <span class="font-semibold">{{ prescription?.reference }}</span>
                        </p>
                    </div>

                    <div class="flex items-center space-x-2">
                        <div class="text-right">
                            <div class="text-xs text-slate-500 dark:text-slate-400">{{ nowLabel }}</div>
                            <div class="text-xxs text-slate-400 dark:text-slate-500">Cree par: {{ $page.props.auth.user?.name }}</div>
                        </div>
                        <button
                            type="button"
                            class="rounded-md bg-red-50 px-2.5 py-1.5 text-xs text-red-600 transition-colors hover:bg-red-100 dark:bg-red-900/20 dark:text-red-400 dark:hover:bg-red-900/30"
                            @click="resetWorkflow"
                        >
                            <em class="ni ni-refresh mr-1 text-xs"></em>Reset
                        </button>
                    </div>
                </div>

                <div class="relative">
                    <div class="absolute left-4 right-4 top-4 z-0 h-0.5 bg-slate-200 dark:bg-slate-600">
                        <div class="h-full bg-gradient-to-r from-primary-400 to-green-400 transition-all duration-300" :style="{ width: `${progress}%` }"></div>
                    </div>

                    <div class="relative z-10 flex items-center justify-between">
                        <div v-for="step in steps" :key="step.key" class="flex flex-col items-center">
                            <button
                                type="button"
                                class="relative mb-1.5 flex h-8 w-8 items-center justify-center rounded-full transition-all duration-200"
                                :class="stepClasses(step)"
                                @click="goToStep(step.key)"
                            >
                                <em :class="`ni ni-${step.icon} text-xs`"></em>
                            </button>
                            <span class="block text-xxs font-medium" :class="stepLabelClasses(step)">{{ step.label }}</span>
                        </div>
                    </div>
                </div>

                <div class="mt-3 text-center">
                    <div class="inline-flex items-center rounded-lg border border-primary-200 bg-primary-50 px-3 py-1.5 dark:border-primary-800 dark:bg-primary-900/20">
                        <em :class="`ni ni-${steps[currentStepIndex].icon} mr-1.5 text-xs text-primary-600 dark:text-primary-400`"></em>
                        <span class="text-xs font-medium text-primary-800 dark:text-primary-200">
                            Etape {{ currentStepIndex + 1 }}/{{ steps.length }} : {{ steps[currentStepIndex].label }}
                        </span>
                    </div>
                </div>
            </div>

            <section v-if="currentStep === 'patient'" class="overflow-hidden rounded-xl border border-slate-200/70 bg-white shadow-sm dark:border-slate-700/80 dark:bg-slate-800">
                <div class="border-b border-slate-200/60 px-5 py-4 dark:border-slate-700/70">
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex min-w-0 items-start gap-3">
                            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-primary-600 shadow-sm">
                                <em class="ni ni-user text-base text-white"></em>
                            </div>
                            <div class="min-w-0">
                                <h2 class="truncate text-sm font-semibold text-slate-900 dark:text-slate-100">
                                    Patient <span class="font-normal text-slate-600 dark:text-slate-400">- Modification</span>
                                </h2>
                                <p class="mt-1 truncate text-xs text-slate-600 dark:text-slate-400">Modifier les informations du patient.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="space-y-4 p-5">
                    <div class="space-y-4">
                        <div class="rounded-xl border border-slate-200/70 bg-white p-4 dark:border-slate-700/70 dark:bg-slate-800">
                            <div class="mb-4 flex items-center justify-between">
                                <h3 class="text-sm font-semibold text-slate-900 dark:text-slate-100">Dossier patient</h3>
                                <span class="text-xs text-slate-600 dark:text-slate-400">* obligatoires</span>
                            </div>

                            <div class="rounded-xl border border-slate-200/70 p-4 dark:border-slate-700/70">
                                <h4 class="mb-3 text-sm font-semibold text-slate-900 dark:text-slate-100">Identite</h4>
                                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                    <div>
                                        <label class="mb-1.5 block text-xs font-semibold text-slate-700 dark:text-slate-300">Nom <span class="text-red-500">*</span></label>
                                        <input v-model="patientForm.nom" type="text" placeholder="Ex: RAJAONA" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-900 placeholder-slate-400 transition focus:border-primary-500 focus:outline-none focus:ring-4 focus:ring-primary-600/15 dark:border-slate-600 dark:bg-slate-700 dark:text-slate-100 dark:placeholder-slate-500">
                                    </div>
                                    <div>
                                        <label class="mb-1.5 block text-xs font-semibold text-slate-700 dark:text-slate-300">Prenom(s)</label>
                                        <input v-model="patientForm.prenom" type="text" placeholder="Ex: Miangaly" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-900 placeholder-slate-400 transition focus:border-primary-500 focus:outline-none focus:ring-4 focus:ring-primary-600/15 dark:border-slate-600 dark:bg-slate-700 dark:text-slate-100 dark:placeholder-slate-500">
                                    </div>
                                </div>
                            </div>

                            <div class="mt-4 rounded-xl border border-slate-200/70 p-4 dark:border-slate-700/70">
                                <div class="mb-2.5 flex items-center justify-between gap-3">
                                    <h4 class="text-sm font-semibold text-slate-900 dark:text-slate-100">Civilite <span class="text-red-500">*</span></h4>
                                </div>

                                <div class="grid grid-cols-2 gap-2.5 sm:grid-cols-3 lg:grid-cols-5">
                                    <label v-for="civilite in civilites" :key="civilite" class="cursor-pointer">
                                        <input v-model="patientForm.civilite" type="radio" :value="civilite" class="peer sr-only">
                                        <div class="relative rounded-xl border-2 border-slate-200 bg-white px-3 py-2.5 transition hover:border-slate-300 hover:shadow-sm peer-checked:border-primary-600 peer-checked:bg-primary-50 peer-focus-visible:ring-4 peer-focus-visible:ring-slate-900/5 dark:border-slate-600 dark:bg-slate-800 dark:hover:border-slate-500 dark:peer-checked:bg-primary-900/15 dark:peer-focus-visible:ring-white/10">
                                            <div class="flex items-center justify-between gap-2">
                                                <div class="min-w-0">
                                                    <div class="flex min-w-0 items-center gap-2">
                                                        <div class="truncate text-sm font-semibold text-slate-900 dark:text-slate-100">{{ civiliteOptionLabel(civilite) }}</div>
                                                        <span class="hidden items-center gap-1 rounded-full border border-primary-200 bg-primary-50 px-2 py-0.5 text-[11px] font-semibold text-primary-700 peer-checked:inline-flex dark:border-primary-800/30 dark:bg-primary-900/15 dark:text-primary-200">
                                                            <em class="ni ni-check text-[11px]"></em> Selectionne
                                                        </span>
                                                    </div>
                                                </div>
                                                <span class="flex h-6 w-6 shrink-0 scale-95 items-center justify-center rounded-full border border-slate-200 bg-white opacity-0 transition peer-checked:scale-100 peer-checked:opacity-100 dark:border-slate-600 dark:bg-slate-700">
                                                    <em class="ni ni-check text-xs text-primary-600"></em>
                                                </span>
                                            </div>
                                        </div>
                                    </label>
                                </div>
                            </div>

                            <div class="mt-4 rounded-xl border border-slate-200/70 p-4 dark:border-slate-700/70">
                                <h4 class="mb-3 text-sm font-semibold text-slate-900 dark:text-slate-100">Contact</h4>
                                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                    <div>
                                        <label class="mb-1.5 block text-xs font-semibold text-slate-700 dark:text-slate-300">Telephone</label>
                                        <input v-model="patientForm.telephone" type="tel" placeholder="+261 34 12 345 67" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-900 placeholder-slate-400 transition focus:border-primary-500 focus:outline-none focus:ring-4 focus:ring-primary-600/15 dark:border-slate-600 dark:bg-slate-700 dark:text-slate-100 dark:placeholder-slate-500">
                                    </div>
                                    <div>
                                        <label class="mb-1.5 block text-xs font-semibold text-slate-700 dark:text-slate-300">Email</label>
                                        <input v-model="patientForm.email" type="email" placeholder="email@exemple.com" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-900 placeholder-slate-400 transition focus:border-primary-500 focus:outline-none focus:ring-4 focus:ring-primary-600/15 dark:border-slate-600 dark:bg-slate-700 dark:text-slate-100 dark:placeholder-slate-500">
                                    </div>
                                </div>
                            </div>

                            <div class="mt-4 rounded-xl border border-slate-200/70 p-4 dark:border-slate-700/70">
                                <h4 class="mb-3 text-sm font-semibold text-slate-900 dark:text-slate-100">Adresse</h4>
                                <label class="mb-1.5 block text-xs font-semibold text-slate-700 dark:text-slate-300">Rue / Quartier / Lot</label>
                                <input v-model="patientForm.adresse" type="text" placeholder="Ex: Lot II M 45 Bis Analakely" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-900 placeholder-slate-400 transition focus:border-primary-500 focus:outline-none focus:ring-4 focus:ring-primary-600/15 dark:border-slate-600 dark:bg-slate-700 dark:text-slate-100 dark:placeholder-slate-500">
                            </div>
                        </div>

                        <div class="rounded-xl border border-slate-200/70 bg-white p-4 dark:border-slate-700/70 dark:bg-slate-800">
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-end">
                                <button type="button" class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-primary-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-700 sm:w-auto" @click="goToStep('clinique')">
                                    <em class="ni ni-check"></em> Valider et continuer
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section v-if="currentStep === 'clinique'" class="overflow-hidden rounded-xl border border-slate-200/70 bg-white shadow-sm dark:border-slate-700/80 dark:bg-slate-800">
                <div class="bg-gradient-to-r from-cyan-50 to-blue-50 px-5 py-4 dark:from-slate-800 dark:to-slate-800">
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex min-w-0 items-start gap-3">
                            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-cyan-600 shadow-sm">
                                <em class="ni ni-notes text-base text-white"></em>
                            </div>
                            <div class="min-w-0">
                                <h2 class="truncate text-sm font-semibold text-slate-900 dark:text-slate-100">Informations cliniques</h2>
                                <p class="mt-1 truncate text-xs text-slate-600 dark:text-slate-400">Renseignements medicaux et prescription.</p>
                            </div>
                        </div>
                        <div class="shrink-0 flex items-center gap-2">
                            <span class="inline-flex items-center gap-1.5 rounded-lg border border-cyan-200 bg-cyan-50 px-2.5 py-1 text-xs font-semibold text-cyan-800 dark:border-cyan-800/30 dark:bg-cyan-900/15 dark:text-cyan-200">
                                <em class="ni ni-check-circle"></em> Clinique
                            </span>
                            <span class="hidden items-center gap-2 text-xs text-slate-500 sm:inline-flex dark:text-slate-400">
                                <span class="inline-flex items-center gap-1">
                                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                    <span class="h-1.5 w-1.5 rounded-full bg-cyan-500"></span>
                                    <span class="h-1.5 w-1.5 rounded-full bg-slate-300 dark:bg-slate-600"></span>
                                </span>
                                Etape 2/7
                            </span>
                        </div>
                    </div>
                </div>

                <div class="space-y-4 p-5">
                    <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
                        <div class="rounded-xl border border-slate-200/70 bg-white p-4 dark:border-slate-700/70 dark:bg-slate-800">
                            <label class="mb-2 block text-sm font-semibold text-slate-800 dark:text-slate-200">
                                <em class="ni ni-user-md mr-1.5 text-cyan-600 dark:text-cyan-300"></em>
                                Prescripteur <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5">
                                    <em class="ni ni-user-md text-base text-slate-400 dark:text-slate-500"></em>
                                </div>
                                <select v-model="clinicalForm.prescripteur_id" class="w-full rounded-xl border border-slate-200 bg-white py-2.5 pl-10 pr-10 text-sm text-slate-900 transition hover:border-slate-300 focus:border-cyan-500 focus:outline-none focus:ring-4 focus:ring-cyan-600/15 dark:border-slate-600 dark:bg-slate-700 dark:text-slate-100 dark:hover:border-slate-500">
                                    <option value="">Selectionner un prescripteur...</option>
                                    <option v-for="prescripteur in prescripteurs" :key="prescripteur.id" :value="prescripteur.id">{{ prescripteur.nom_complet }}</option>
                                </select>
                                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3.5">
                                    <em class="ni ni-chevron-down text-xs text-slate-400 dark:text-slate-500"></em>
                                </div>
                            </div>
                        </div>

                        <div class="rounded-xl border border-slate-200/70 bg-white p-4 dark:border-slate-700/70 dark:bg-slate-800">
                            <label class="mb-2 block text-sm font-semibold text-slate-800 dark:text-slate-200">
                                <em class="ni ni-building mr-1.5 text-blue-600 dark:text-blue-300"></em>
                                Type de patient
                            </label>
                            <div class="relative">
                                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5">
                                    <em class="ni ni-building text-base text-slate-400 dark:text-slate-500"></em>
                                </div>
                                <select v-model="clinicalForm.patient_type" class="w-full rounded-xl border border-slate-200 bg-white py-2.5 pl-10 pr-10 text-sm text-slate-900 transition hover:border-slate-300 focus:border-cyan-500 focus:outline-none focus:ring-4 focus:ring-cyan-600/15 dark:border-slate-600 dark:bg-slate-700 dark:text-slate-100 dark:hover:border-slate-500">
                                    <option value="EXTERNE">🏠 Externe</option>
                                    <option value="HOSPITALISE">🏥 Hospitalise</option>
                                    <option value="URGENCE-JOUR">🚨 Urgence Jour</option>
                                    <option value="URGENCE-NUIT">🌙 Urgence Nuit</option>
                                </select>
                                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3.5">
                                    <em class="ni ni-chevron-down text-xs text-slate-400 dark:text-slate-500"></em>
                                </div>
                            </div>
                            <p class="mt-2 flex items-center gap-1.5 text-xs text-slate-600 dark:text-slate-400">
                                <em class="ni ni-info-circle"></em> Utile pour le tri et la prise en charge.
                            </p>
                        </div>

                        <div class="rounded-xl border border-slate-200/70 bg-white p-4 dark:border-slate-700/70 dark:bg-slate-800">
                            <div class="mb-2.5 flex items-center justify-between gap-3">
                                <label class="block text-sm font-semibold text-slate-800 dark:text-slate-200">
                                    <em class="ni ni-activity mr-1.5 text-orange-600 dark:text-orange-300"></em>
                                    Poids
                                </label>
                                <span class="text-xs text-slate-500 dark:text-slate-400">kg</span>
                            </div>
                            <div class="relative">
                                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5">
                                    <em class="ni ni-activity text-base text-slate-400 dark:text-slate-500"></em>
                                </div>
                                <input v-model.number="clinicalForm.poids" type="number" min="0" step="0.1" placeholder="Ex: 65.5" class="w-full rounded-xl border border-slate-200 bg-white py-2.5 pl-10 pr-12 text-sm text-slate-900 placeholder-slate-400 transition focus:border-cyan-500 focus:outline-none focus:ring-4 focus:ring-cyan-600/15 dark:border-slate-600 dark:bg-slate-700 dark:text-slate-100 dark:placeholder-slate-500">
                                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3.5">
                                    <span class="text-xs font-semibold text-slate-500 dark:text-slate-400">kg</span>
                                </div>
                            </div>
                            <p class="mt-2 flex items-center gap-1.5 text-xs text-slate-600 dark:text-slate-400">
                                <em class="ni ni-info-circle"></em> Optionnel - utile pour le calcul des doses.
                            </p>
                        </div>
                    </div>

                    <div class="rounded-xl border border-slate-200/70 bg-white p-4 dark:border-slate-700/70 dark:bg-slate-800">
                        <div class="mb-3 flex items-center justify-between gap-3">
                            <h3 class="flex items-center gap-2 text-sm font-semibold text-slate-900 dark:text-slate-100">
                                <span class="flex h-8 w-8 items-center justify-center rounded-xl border border-emerald-200/70 bg-emerald-50 dark:border-emerald-800/30 dark:bg-emerald-900/15">
                                    <em class="ni ni-calendar text-sm text-emerald-700 dark:text-emerald-300"></em>
                                </span>
                                Naissance et age
                            </h3>
                            <span class="text-xs text-slate-500 dark:text-slate-400">Recommande</span>
                        </div>

                        <div class="grid grid-cols-1 items-start gap-4 md:grid-cols-3">
                            <div class="md:col-span-2">
                                <label class="mb-1.5 block text-xs font-semibold text-slate-700 dark:text-slate-300">Date de naissance</label>
                                <input
                                    v-model="patientForm.date_naissance"
                                    type="date"
                                    class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-900 transition focus:border-cyan-500 focus:outline-none focus:ring-4 focus:ring-cyan-600/15 dark:border-slate-600 dark:bg-slate-700 dark:text-slate-100"
                                    @change="syncAgeFromBirthDate"
                                >
                            </div>

                            <div>
                                <label class="mb-1.5 block text-xs font-semibold text-slate-700 dark:text-slate-300">Age <span v-if="!patientForm.date_naissance" class="text-red-500">*</span></label>
                                <div v-if="patientForm.date_naissance" class="rounded-xl border border-blue-200/70 bg-blue-50 px-4 py-2.5 dark:border-blue-800/30 dark:bg-blue-900/15">
                                    <div class="flex items-center justify-between gap-3">
                                        <span class="text-sm font-bold text-blue-800 dark:text-blue-200">{{ clinicalForm.age }} {{ clinicalForm.unite_age }}</span>
                                        <em class="ni ni-check-circle text-emerald-600 dark:text-emerald-300"></em>
                                    </div>
                                </div>
                                <div v-else class="flex gap-2">
                                    <div class="relative flex-1">
                                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5">
                                            <em class="ni ni-hash text-base text-slate-400 dark:text-slate-500"></em>
                                        </div>
                                        <input v-model.number="clinicalForm.age" type="number" min="0" max="150" placeholder="Ex: 32" class="w-full rounded-xl border border-slate-200 bg-white py-2.5 pl-10 pr-3 text-sm text-slate-900 transition focus:border-cyan-500 focus:outline-none focus:ring-4 focus:ring-cyan-600/15 dark:border-slate-600 dark:bg-slate-700 dark:text-slate-100">
                                    </div>
                                    <select v-model="clinicalForm.unite_age" class="w-24 rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-900 transition focus:border-cyan-500 focus:outline-none focus:ring-4 focus:ring-cyan-600/15 dark:border-slate-600 dark:bg-slate-700 dark:text-slate-100">
                                        <option value="Ans">Ans</option>
                                        <option value="Mois">Mois</option>
                                        <option value="Jours">Jours</option>
                                    </select>
                                </div>
                                <p v-if="patientForm.date_naissance" class="mt-1.5 flex items-center gap-1.5 text-xs text-blue-700 dark:text-blue-300">
                                    <em class="ni ni-info-circle"></em> Calcul auto.
                                </p>
                                <p v-else class="mt-1.5 flex items-center gap-1.5 text-xs text-amber-700 dark:text-amber-300">
                                    <em class="ni ni-info-circle"></em> Ajoutez une date pour calcul auto.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-xl border border-slate-200/70 bg-white p-4 dark:border-slate-700/70 dark:bg-slate-800">
                        <div class="mb-2.5 flex items-center justify-between gap-3">
                            <label class="block text-sm font-semibold text-slate-800 dark:text-slate-200">
                                <em class="ni ni-clipboard mr-1.5 text-purple-600 dark:text-purple-300"></em>
                                Renseignements cliniques
                            </label>
                            <span class="text-xs text-slate-500 dark:text-slate-400">{{ String(clinicalForm.renseignement_clinique || '').length }} caracteres</span>
                        </div>
                        <div class="relative">
                            <textarea v-model="clinicalForm.renseignement_clinique" rows="4" placeholder="Decrivez les symptomes, antecedents medicaux, indications speciales, allergies connues..." class="w-full resize-none rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 placeholder-slate-400 transition focus:border-cyan-500 focus:outline-none focus:ring-4 focus:ring-cyan-600/15 dark:border-slate-600 dark:bg-slate-700 dark:text-slate-100 dark:placeholder-slate-500"></textarea>
                            <div class="pointer-events-none absolute right-3 top-3 text-slate-400 dark:text-slate-500">
                                <em class="ni ni-edit text-sm"></em>
                            </div>
                        </div>
                        <div class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-slate-600 dark:text-slate-400">
                            <span class="inline-flex items-center gap-1.5">
                                <em class="ni ni-shield-check text-emerald-600 dark:text-emerald-300"></em> Informations confidentielles
                            </span>
                            <span class="inline-flex items-center gap-1.5">
                                <em class="ni ni-lock text-blue-600 dark:text-blue-300"></em> Donnees securisees
                            </span>
                        </div>
                    </div>

                    <div class="rounded-xl border border-indigo-200/60 bg-indigo-50/70 p-4 dark:border-indigo-800/40 dark:bg-indigo-900/10">
                        <div class="flex items-start gap-3">
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-indigo-600 shadow-sm">
                                <em class="ni ni-bulb text-sm text-white"></em>
                            </div>
                            <div>
                                <h4 class="text-sm font-semibold text-indigo-900 dark:text-indigo-200">Conseils pour une prescription optimale</h4>
                                <div class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-2 text-xs text-indigo-800 dark:text-indigo-300">
                                    <span class="inline-flex items-center gap-1.5"><em class="ni ni-check-circle text-emerald-600 dark:text-emerald-300"></em>Naissance -> age calcule</span>
                                    <span class="inline-flex items-center gap-1.5"><em class="ni ni-check-circle text-emerald-600 dark:text-emerald-300"></em>Verifier les allergies connues</span>
                                    <span class="inline-flex items-center gap-1.5"><em class="ni ni-check-circle text-emerald-600 dark:text-emerald-300"></em>Noter les traitements en cours</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-col items-center justify-between gap-3 border-t border-slate-200/60 pt-4 sm:flex-row dark:border-slate-700/70">
                        <button type="button" class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-slate-100 px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-200 sm:w-auto dark:bg-slate-700 dark:text-slate-200 dark:hover:bg-slate-600" @click="goToStep('patient')">
                            <em class="ni ni-arrow-left"></em> Retour patient
                        </button>

                        <span class="hidden items-center gap-2 text-xs text-slate-500 sm:inline-flex dark:text-slate-400">
                            <span class="inline-flex items-center gap-1">
                                <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                <span class="h-1.5 w-1.5 rounded-full bg-cyan-500"></span>
                                <span class="h-1.5 w-1.5 rounded-full bg-slate-300 dark:bg-slate-600"></span>
                            </span>
                            Etape 2/7
                        </span>

                        <button type="button" class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-primary-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-700 sm:w-auto" @click="goToStep('analyses')">
                            Continuer vers analyses <em class="ni ni-arrow-right"></em>
                        </button>
                    </div>
                </div>
            </section>

            <section v-if="currentStep === 'analyses'" class="overflow-hidden rounded-xl border border-slate-200/70 bg-white shadow-sm dark:border-slate-700/80 dark:bg-slate-800">
                <div class="bg-gradient-to-r from-green-50 to-emerald-50 px-5 py-4 dark:from-slate-800 dark:to-slate-800">
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex min-w-0 items-start gap-3">
                            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-green-600 shadow-sm">
                                <em class="ni ni-filter text-base text-white"></em>
                            </div>
                            <div class="min-w-0">
                                <h2 class="truncate text-sm font-semibold text-slate-900 dark:text-slate-100">Analyses</h2>
                                <p class="mt-1 truncate text-xs text-slate-600 dark:text-slate-400">Recherchez et selectionnez les analyses prescrites.</p>
                            </div>
                        </div>
                        <div class="shrink-0 flex items-center gap-2">
                            <span v-if="selectedAnalyses.length > 0" class="inline-flex items-center gap-1.5 rounded-lg border border-green-200/70 bg-green-50 px-2.5 py-1 text-xs font-semibold text-green-700 dark:border-green-800/40 dark:bg-green-900/15 dark:text-green-300">
                                <em class="ni ni-check-circle"></em> {{ selectedAnalyses.length }} selectionnees
                            </span>
                            <span class="hidden items-center gap-2 text-xs text-slate-500 sm:inline-flex dark:text-slate-400">
                                Etape 3/7
                            </span>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-4 p-5 lg:grid-cols-3">
                    <div class="space-y-4 lg:col-span-2">
                        <div class="rounded-xl border border-slate-200/70 bg-white p-4 dark:border-slate-700/70 dark:bg-slate-800">
                            <label class="mb-2 block text-sm font-semibold text-slate-800 dark:text-slate-200">
                                <em class="ni ni-search mr-1.5 text-green-600 dark:text-green-300"></em>
                                Rechercher une analyse
                            </label>

                            <div class="relative">
                                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5">
                                    <em class="ni ni-search text-base text-slate-400 dark:text-slate-500"></em>
                                </div>
                                <input
                                    v-model="analyseSearch"
                                    type="text"
                                    placeholder="Code ou designation..."
                                    class="w-full rounded-xl border border-slate-200 bg-white py-2.5 pl-10 pr-10 text-sm text-slate-900 placeholder-slate-400 transition focus:border-green-500 focus:outline-none focus:ring-4 focus:ring-green-600/15 dark:border-slate-600 dark:bg-slate-700 dark:text-slate-100 dark:placeholder-slate-500"
                                    @input="debouncedAnalysesSearch"
                                >
                                <button
                                    v-if="analyseSearch"
                                    type="button"
                                    class="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400 transition hover:text-red-500 dark:hover:text-red-400"
                                    @click="analyseSearch = ''; analyseResults = []"
                                >
                                    <em class="ni ni-cross-circle text-lg"></em>
                                </button>
                            </div>

                            <div v-if="analyseResults.length > 0" class="mt-3 max-h-80 space-y-2 overflow-auto">
                                <div v-for="analyse in analyseResults" :key="analyse.id" class="flex items-center justify-between rounded-xl border border-slate-200 p-3 transition-colors hover:bg-slate-50 dark:border-slate-700 dark:hover:bg-slate-700/40">
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ analyse.designation }}</p>
                                            <span v-if="analyse.is_parent" class="rounded-full bg-indigo-100 px-2 py-0.5 text-xxs font-semibold text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-300">
                                                Panel ({{ analyse.enfants_inclus?.length || 0 }})
                                            </span>
                                        </div>
                                        <p class="text-xs text-slate-500 dark:text-slate-400">
                                            {{ analyse.code }} | {{ formatCurrency(analyse.prix) }}
                                            <span v-if="analyse.parent_nom && !analyse.is_parent" class="ml-1 text-blue-600 dark:text-blue-400">· {{ analyse.parent_nom }}</span>
                                        </p>
                                        <p v-if="analyse.is_parent && analyse.enfants_inclus?.length > 0" class="mt-1 text-xxs text-indigo-600 dark:text-indigo-400">
                                            Inclut : {{ analyse.enfants_inclus.join(', ') }}
                                        </p>
                                    </div>
                                    <button
                                        type="button"
                                        class="shrink-0 rounded-lg px-2.5 py-1.5 text-xs font-medium transition-colors"
                                        :class="isAnalyseInCart(analyse.id)
                                            ? 'bg-green-100 dark:bg-green-900 text-green-700 dark:text-green-200 cursor-not-allowed'
                                            : 'bg-green-500 hover:bg-green-600 text-white'"
                                        :disabled="isAnalyseInCart(analyse.id)"
                                        @click="addAnalyse(analyse)"
                                    >
                                        <em class="ni text-xs" :class="isAnalyseInCart(analyse.id) ? 'ni-check' : 'ni-plus'"></em>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="flex flex-col items-center justify-between gap-3 border-t border-slate-200/60 pt-4 sm:flex-row dark:border-slate-700/70">
                            <button type="button" class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-slate-100 px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-200 sm:w-auto dark:bg-slate-700 dark:text-slate-200 dark:hover:bg-slate-600" @click="goToStep('clinique')">
                                <em class="ni ni-arrow-left"></em> Retour clinique
                            </button>
                            <button type="button" class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-primary-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-700 sm:w-auto" @click="goToStep('prelevements')">
                                Continuer vers prelevements <em class="ni ni-arrow-right"></em>
                            </button>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <div class="rounded-xl border border-slate-200/70 bg-slate-50 p-4 dark:border-slate-700/70 dark:bg-slate-700/50">
                            <h3 class="mb-3 text-sm font-semibold text-slate-800 dark:text-slate-100">
                                <em class="ni ni-bag mr-1.5 text-xs"></em>Analyses selectionnees
                            </h3>

                            <div v-if="selectedAnalyses.length === 0" class="py-4 text-center text-xs text-slate-500 dark:text-slate-400">
                                Aucune analyse selectionnee
                            </div>

                            <div v-else class="mb-3 space-y-2">
                                <div v-for="analyse in selectedAnalyses" :key="analyse.id" class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <div class="mb-0.5 flex items-center space-x-1.5">
                                            <span class="rounded bg-blue-100 px-1.5 py-0.5 font-mono text-xxs font-bold text-blue-700 dark:bg-blue-900/30 dark:text-blue-200">
                                                {{ analyse.code }}
                                            </span>
                                            <div class="text-xs font-medium text-slate-800 dark:text-slate-100">{{ analyse.designation }}</div>
                                        </div>
                                        <div class="text-xxs text-slate-500 dark:text-slate-400">
                                            {{ analyse.parent_nom || (analyse.parent ? 'Analyse individuelle' : 'Analyse individuelle') }}
                                        </div>
                                        <span v-if="analyse.is_parent" class="mt-0.5 inline-block rounded-full bg-purple-100 px-1.5 py-0.5 text-xxs text-purple-700 dark:bg-purple-900/30 dark:text-purple-200">
                                            Panel complet
                                        </span>
                                    </div>
                                    <div class="ml-2 text-right">
                                        <div class="text-xs font-medium text-slate-700 dark:text-slate-300">
                                            {{ (analyse.prix_effectif || analyse.prix) > 0 ? formatCurrency(analyse.prix_effectif || analyse.prix) : 'Inclus' }}
                                        </div>
                                        <button type="button" class="text-xxs text-red-500 transition-colors hover:text-red-600" @click="removeAnalyse(analyse.id)">
                                            <em class="ni ni-cross text-xs"></em>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="rounded-lg bg-green-50/70 p-3 text-sm dark:bg-green-900/15">
                            <div class="flex items-center justify-between">
                                <span class="font-medium text-green-800 dark:text-green-200">Sous-total analyses</span>
                                <span class="font-semibold text-green-700 dark:text-green-300">{{ formatCurrency(analysesSubtotal) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section v-if="currentStep === 'prelevements'" class="overflow-hidden rounded-xl border border-slate-200/70 bg-white shadow-sm dark:border-slate-700/80 dark:bg-slate-800">
                <div class="bg-gradient-to-r from-yellow-50 to-orange-50 px-5 py-4 dark:from-slate-800 dark:to-slate-800">
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex min-w-0 items-start gap-3">
                            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-yellow-500 shadow-sm">
                                <em class="ni ni-package text-base text-white"></em>
                            </div>
                            <div class="min-w-0">
                                <h2 class="truncate text-sm font-semibold text-slate-900 dark:text-slate-100">Prelevements</h2>
                                <p class="mt-1 truncate text-xs text-slate-600 dark:text-slate-400">Selection optionnelle des prelevements requis.</p>
                            </div>
                        </div>
                        <div class="shrink-0 flex items-center gap-2">
                            <span v-if="selectedPrelevements.length > 0" class="inline-flex items-center gap-1.5 rounded-lg border border-yellow-200/70 bg-yellow-50 px-2.5 py-1 text-xs font-semibold text-yellow-700 dark:border-yellow-800/40 dark:bg-yellow-900/15 dark:text-yellow-300">
                                <em class="ni ni-check-circle"></em> {{ selectedPrelevements.length }} selectionnes
                            </span>
                            <span class="hidden items-center gap-2 text-xs text-slate-500 sm:inline-flex dark:text-slate-400">
                                Etape 4/7
                            </span>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-4 p-5 xl:grid-cols-3">
                    <div class="space-y-4 xl:col-span-2">
                        <div class="rounded-xl border border-slate-200/70 bg-white p-4 dark:border-slate-700/70 dark:bg-slate-800">
                            <label class="mb-2 block text-sm font-semibold text-slate-800 dark:text-slate-200">
                                <em class="ni ni-search mr-1.5 text-yellow-600 dark:text-yellow-300"></em>
                                Rechercher un prelevement
                            </label>
                            <div class="relative">
                                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5">
                                    <em class="ni ni-search text-base text-slate-400 dark:text-slate-500"></em>
                                </div>
                                <input
                                    v-model="prelevementSearch"
                                    type="text"
                                    placeholder="Tapez le nom du prelevement..."
                                    class="w-full rounded-xl border border-slate-200 bg-white py-2.5 pl-10 pr-10 text-sm text-slate-900 placeholder-slate-400 transition focus:border-yellow-500 focus:outline-none focus:ring-4 focus:ring-yellow-600/15 dark:border-slate-600 dark:bg-slate-700 dark:text-slate-100 dark:placeholder-slate-500"
                                    @input="debouncedPrelevementsSearch"
                                >
                                <button
                                    v-if="prelevementSearch"
                                    type="button"
                                    class="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400 transition hover:text-red-500 dark:hover:text-red-400"
                                    @click="prelevementSearch = ''; prelevementResults = []"
                                >
                                    <em class="ni ni-cross-circle text-lg"></em>
                                </button>
                            </div>
                        </div>

                        <div v-if="prelevementResults.length > 0" class="grid grid-cols-1 gap-2 lg:grid-cols-2">
                            <div v-for="prelevement in prelevementResults" :key="prelevement.id" class="flex items-start justify-between rounded-xl border border-slate-200 p-2.5 transition-colors hover:border-yellow-300 hover:bg-yellow-50/50 dark:border-slate-700 dark:hover:border-yellow-500 dark:hover:bg-slate-700/40" :class="isPrelevementInCart(prelevement.id) ? 'bg-yellow-50 dark:bg-yellow-900/20 border-yellow-300 dark:border-yellow-600' : ''">
                                <div class="flex items-start gap-2.5 flex-1">
                                    <div class="flex h-6 w-6 shrink-0 items-center justify-center rounded-lg bg-gradient-to-br from-yellow-500 to-orange-600 text-white">
                                        <em class="ni ni-flask text-xs"></em>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-xs font-medium text-slate-800 dark:text-slate-100">{{ prelevement.denomination }}</p>
                                        <div class="mt-1 flex items-center gap-2">
                                            <span class="inline-flex items-center rounded-full bg-yellow-100 px-1.5 py-0.5 text-xxs font-medium text-yellow-700 dark:bg-yellow-900 dark:text-yellow-200">
                                                <em class="ni ni-money mr-0.5 text-xs"></em>
                                                {{ formatCurrency(prelevement.prix) }}
                                            </span>
                                            <span v-if="isPrelevementInCart(prelevement.id)" class="text-xxs font-medium text-green-600 dark:text-green-400">✓ Ajouté</span>
                                        </div>
                                    </div>
                                </div>
                                <button
                                    type="button"
                                    class="ml-2 shrink-0 rounded-lg px-2.5 py-1.5 text-xs font-medium transition-colors"
                                    :class="isPrelevementInCart(prelevement.id)
                                        ? 'bg-green-100 dark:bg-green-900 text-green-700 dark:text-green-200 cursor-not-allowed'
                                        : 'bg-yellow-500 hover:bg-yellow-600 text-white'"
                                    :disabled="isPrelevementInCart(prelevement.id)"
                                    @click="addPrelevement(prelevement)"
                                >
                                    <em class="ni text-xs" :class="isPrelevementInCart(prelevement.id) ? 'ni-check' : 'ni-plus'"></em>
                                </button>
                            </div>
                        </div>

                        <div class="flex flex-col items-center justify-between gap-3 border-t border-slate-200/60 pt-4 sm:flex-row dark:border-slate-700/70">
                            <button type="button" class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-slate-100 px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-200 sm:w-auto dark:bg-slate-700 dark:text-slate-200 dark:hover:bg-slate-600" @click="goToStep('analyses')">
                                <em class="ni ni-arrow-left"></em> Retour analyses
                            </button>
                            <button type="button" class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-primary-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-700 sm:w-auto" @click="goToStep('paiement')">
                                Continuer vers paiement <em class="ni ni-arrow-right"></em>
                            </button>
                        </div>
                    </div>

                    <div class="rounded-xl border border-slate-200/70 bg-slate-50 p-4 dark:border-slate-700/70 dark:bg-slate-700/50">
                        <h3 class="mb-3 text-sm font-semibold text-slate-800 dark:text-slate-100">
                            <em class="ni ni-package mr-1.5 text-xs text-yellow-500"></em>Prelevements selectionnes
                        </h3>

                        <div v-if="selectedPrelevements.length === 0" class="py-4 text-center text-xs text-slate-500 dark:text-slate-400">
                            Aucun prelevement selectionne
                        </div>

                        <div v-else class="space-y-2">
                            <div v-for="prelevement in selectedPrelevements" :key="prelevement.id" class="rounded-xl border border-yellow-200 bg-white p-3 dark:border-yellow-700 dark:bg-slate-800">
                                <div class="mb-2 flex items-start justify-between gap-2">
                                    <div>
                                        <p class="text-xs font-medium text-slate-800 dark:text-slate-100">{{ prelevement.denomination }}</p>
                                        <p class="mt-1 text-xxs text-slate-500 dark:text-slate-400">{{ formatCurrency(prelevement.prix) }}</p>
                                    </div>
                                    <button type="button" class="text-xs text-red-600 hover:text-red-700" @click="removePrelevement(prelevement.id)">
                                        <em class="ni ni-cross"></em>
                                    </button>
                                </div>
                                <div class="flex items-center justify-between">
                                    <label class="text-xxs text-slate-600 dark:text-slate-400">Quantite</label>
                                    <input v-model.number="prelevement.quantite" type="number" min="1" max="10" class="w-16 rounded-lg border border-slate-300 bg-white px-2 py-1 text-center text-xs text-slate-900 dark:border-slate-600 dark:bg-slate-700 dark:text-slate-100">
                                </div>
                            </div>
                        </div>

                        <div class="mt-3 border-t border-slate-200 pt-2 dark:border-slate-600">
                            <div class="flex items-center justify-between text-sm">
                                <span class="font-medium text-slate-800 dark:text-slate-100">Total prelevements</span>
                                <span class="font-semibold text-yellow-700 dark:text-yellow-300">{{ formatCurrency(prelevementsSubtotal) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section v-if="currentStep === 'paiement'" class="overflow-hidden rounded-xl border border-slate-200/70 bg-white shadow-sm dark:border-slate-700/80 dark:bg-slate-800">
                <div class="bg-gradient-to-r from-red-50 to-pink-50 px-5 py-4 dark:from-slate-800 dark:to-slate-800">
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex min-w-0 items-start gap-3">
                            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-red-500 shadow-sm">
                                <em class="ni ni-coin text-base text-white"></em>
                            </div>
                            <div class="min-w-0">
                                <h2 class="truncate text-sm font-semibold text-slate-900 dark:text-slate-100">Paiement & Facturation</h2>
                                <p class="mt-1 truncate text-xs text-slate-600 dark:text-slate-400">Finalisation de la prescription.</p>
                            </div>
                        </div>
                        <div class="shrink-0 text-right">
                            <div class="text-lg font-bold text-red-600 dark:text-red-400">{{ formatCurrency(totalDue) }}</div>
                            <div class="text-xs text-slate-500 dark:text-slate-400">Total a payer</div>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-4 p-5 xl:grid-cols-3">
                    <div class="space-y-4 xl:col-span-2">
                        <div class="rounded-xl border border-slate-200/70 bg-white p-4 dark:border-slate-700/70 dark:bg-slate-800">
                            <h3 class="mb-3 text-sm font-semibold text-slate-800 dark:text-slate-100">
                                <em class="ni ni-calculator mr-1.5 text-xs text-green-500"></em>Resume financier
                            </h3>
                            <div class="space-y-2 text-sm">
                                <div class="flex justify-between border-b border-slate-200/60 py-1.5 dark:border-slate-700/70">
                                    <span class="text-slate-600 dark:text-slate-400">Sous-total analyses</span>
                                    <span class="font-medium text-slate-800 dark:text-slate-100">{{ formatCurrency(analysesSubtotal) }}</span>
                                </div>
                                <div class="flex justify-between border-b border-slate-200/60 py-1.5 dark:border-slate-700/70">
                                    <span class="text-slate-600 dark:text-slate-400">Sous-total prelevements</span>
                                    <span class="font-medium text-slate-800 dark:text-slate-100">{{ formatCurrency(prelevementsSubtotal) }}</span>
                                </div>
                                <div class="flex justify-between border-b border-slate-200/60 py-1.5 dark:border-slate-700/70">
                                    <span class="text-slate-600 dark:text-slate-400">Frais urgence</span>
                                    <span class="font-medium text-slate-800 dark:text-slate-100">{{ formatCurrency(urgencyFee) }}</span>
                                </div>
                                <div class="flex justify-between border-b border-slate-200/60 py-1.5 dark:border-slate-700/70">
                                    <span class="text-slate-600 dark:text-slate-400">Remise</span>
                                    <span class="font-medium text-red-600 dark:text-red-400">- {{ formatCurrency(remiseAmount) }}</span>
                                </div>
                                <div class="rounded-xl border border-red-200/70 bg-red-50 p-3 dark:border-red-800/40 dark:bg-red-900/15">
                                    <div class="flex items-center justify-between">
                                        <span class="text-sm font-semibold text-red-800 dark:text-red-200">Total final</span>
                                        <span class="text-base font-bold text-red-600 dark:text-red-400">{{ formatCurrency(totalDue) }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <div class="rounded-xl border border-slate-200/70 bg-white p-4 dark:border-slate-700/70 dark:bg-slate-800">
                            <label class="mb-2 block text-sm font-semibold text-slate-800 dark:text-slate-200">
                                <em class="ni ni-wallet mr-1.5 text-red-600 dark:text-red-300"></em>
                                Mode de paiement
                            </label>
                            <div class="relative">
                                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5">
                                    <em class="ni ni-wallet text-base text-slate-400 dark:text-slate-500"></em>
                                </div>
                                <select v-model="paymentForm.payment_method" class="w-full rounded-xl border border-slate-200 bg-white py-2.5 pl-10 pr-10 text-sm text-slate-900 transition hover:border-slate-300 focus:border-red-500 focus:outline-none focus:ring-4 focus:ring-red-600/15 dark:border-slate-600 dark:bg-slate-700 dark:text-slate-100 dark:hover:border-slate-500">
                                    <option value="">Selectionner...</option>
                                    <option v-for="method in paymentMethods" :key="method.code" :value="method.code">{{ method.label }}</option>
                                </select>
                                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3.5">
                                    <em class="ni ni-chevron-down text-xs text-slate-400 dark:text-slate-500"></em>
                                </div>
                            </div>
                        </div>

                        <div class="rounded-xl border border-slate-200/70 bg-white p-4 dark:border-slate-700/70 dark:bg-slate-800">
                            <label class="mb-2 block text-sm font-semibold text-slate-800 dark:text-slate-200">
                                <em class="ni ni-percent mr-1.5 text-orange-600 dark:text-orange-300"></em>
                                Remise (%)
                            </label>
                            <div class="relative">
                                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5">
                                    <em class="ni ni-percent text-base text-slate-400 dark:text-slate-500"></em>
                                </div>
                                <input v-model.number="paymentForm.remise" type="number" min="0" max="100" placeholder="0" class="w-full rounded-xl border border-slate-200 bg-white py-2.5 pl-10 pr-12 text-sm text-slate-900 placeholder-slate-400 transition focus:border-red-500 focus:outline-none focus:ring-4 focus:ring-red-600/15 dark:border-slate-600 dark:bg-slate-700 dark:text-slate-100 dark:placeholder-slate-500">
                                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3.5">
                                    <span class="text-xs font-semibold text-slate-500 dark:text-slate-400">%</span>
                                </div>
                            </div>
                        </div>

                        <label class="flex w-full cursor-pointer items-center gap-3 rounded-xl border-2 border-slate-200 bg-white p-4 transition hover:border-slate-300 hover:shadow-sm dark:border-slate-600 dark:bg-slate-800 dark:hover:border-slate-500" :class="paymentForm.paiement_statut ? 'border-emerald-500 bg-emerald-50 dark:border-emerald-600 dark:bg-emerald-900/15' : ''">
                            <input v-model="paymentForm.paiement_statut" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500 dark:border-slate-600">
                            <div>
                                <span class="text-sm font-semibold text-slate-800 dark:text-slate-200">Marquer comme paye</span>
                                <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">La date de paiement sera enregistree automatiquement.</p>
                            </div>
                        </label>
                    </div>
                </div>

                <div class="flex flex-col items-center justify-between gap-3 border-t border-slate-200/60 px-5 pb-5 pt-4 sm:flex-row dark:border-slate-700/70">
                    <button type="button" class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-slate-100 px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-200 sm:w-auto dark:bg-slate-700 dark:text-slate-200 dark:hover:bg-slate-600" @click="goToStep('prelevements')">
                        <em class="ni ni-arrow-left"></em> Retour prelevements
                    </button>
                    <button type="button" class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700 disabled:opacity-50 sm:w-auto" :disabled="isSubmitting" @click="submitPrescription">
                        <span v-if="isSubmitting">Enregistrement...</span>
                        <span v-else class="flex items-center gap-2"><em class="ni ni-check-circle"></em> Terminer et enregistrer</span>
                    </button>
                </div>
            </section>
            <section v-if="currentStep === 'tubes'" class="overflow-hidden rounded-xl border border-slate-200/70 bg-white shadow-sm dark:border-slate-700/80 dark:bg-slate-800">
                <div class="bg-gradient-to-r from-teal-50 to-emerald-50 px-4 py-3 dark:from-slate-700 dark:to-slate-800">
                    <div class="flex items-center">
                        <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-teal-500">
                            <em class="ni ni-scan text-sm text-white"></em>
                        </div>
                        <div class="ml-3">
                            <h2 class="text-base font-semibold text-slate-800 dark:text-slate-100">Etiquettes et Tubes</h2>
                            <p class="text-xs text-slate-500 dark:text-slate-400">Preparation des prelevements</p>
                        </div>
                    </div>
                </div>
                <div class="p-4 space-y-4">
                    <p class="text-sm text-slate-700 dark:text-slate-300">
                        Imprimez les codes-barres des {{ prescription?.tubes?.length || 0 }} tubes generes pour cette prescription.
                    </p>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        <div v-for="tube in prescription?.tubes" :key="tube.id" class="flex flex-col items-center justify-center space-y-2 rounded-xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-800/50">
                            <em class="ni ni-barcode text-4xl text-slate-400"></em>
                            <div class="text-sm font-mono font-bold text-slate-800 dark:text-slate-100">{{ tube.code_barre }}</div>
                        </div>
                    </div>
                    <div class="flex justify-end pt-4">
                        <button type="button" class="rounded-lg bg-primary-600 px-5 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-primary-700" @click="goToStep('confirmation')">
                            Suivant <em class="ni ni-arrow-right ml-1"></em>
                        </button>
                    </div>
                </div>
            </section>

            <section v-if="currentStep === 'confirmation'" class="mx-auto max-w-md">
                <!-- Success header -->
                <div class="rounded-t-xl border border-orange-200 bg-white p-5 text-center shadow-sm dark:border-orange-800 dark:bg-slate-800">
                    <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-orange-50 dark:bg-orange-900/20">
                        <em class="ni ni-edit text-xl text-orange-500 dark:text-orange-400"></em>
                    </div>
                    <h2 class="mb-2 text-lg font-semibold text-orange-900 dark:text-orange-100">
                        Prescription modifiee avec succes !
                    </h2>
                    <p class="text-sm text-slate-600 dark:text-slate-300">
                        Les modifications ont ete sauvegardees.
                    </p>
                    <div v-if="prescription?.reference" class="mt-2 inline-flex items-center rounded-full bg-slate-100 px-2 py-1 dark:bg-slate-700">
                        <em class="ni ni-tag mr-1 text-xs text-slate-500"></em>
                        <span class="text-xs font-medium text-slate-700 dark:text-slate-300">{{ prescription.reference }}</span>
                    </div>
                </div>

                <!-- Ticket-style recap -->
                <div class="rounded-b-xl border border-t-0 border-orange-200 bg-white p-4 shadow-sm dark:border-orange-800 dark:bg-slate-800">
                    <!-- Ticket header -->
                    <div class="mb-3 border-b border-dashed border-slate-200 pb-2 text-center dark:border-slate-700">
                        <h3 class="font-medium text-slate-800 dark:text-slate-100">
                            Reference: {{ prescription?.reference || '—' }}
                        </h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400">{{ nowLabel }}</p>
                    </div>

                    <!-- Recap body -->
                    <div class="mb-4 space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="font-medium text-slate-700 dark:text-slate-300">Patient:</span>
                            <span class="text-slate-900 dark:text-slate-100">
                                {{ patientForm.nom }} {{ patientForm.prenom }}
                                <span v-if="clinicalForm.age"> ({{ clinicalForm.age }} {{ clinicalForm.unite_age }})</span>
                            </span>
                        </div>

                        <div v-if="clinicalForm.prescripteur_id" class="flex justify-between">
                            <span class="font-medium text-slate-700 dark:text-slate-300">Prescripteur:</span>
                            <span class="text-slate-900 dark:text-slate-100">{{ prescripteurName }}</span>
                        </div>

                        <div class="flex justify-between">
                            <span class="font-medium text-slate-700 dark:text-slate-300">Analyses:</span>
                            <span class="text-slate-900 dark:text-slate-100">{{ selectedAnalyses.length }}</span>
                        </div>

                        <div v-if="selectedPrelevements.length > 0" class="flex justify-between">
                            <span class="font-medium text-slate-700 dark:text-slate-300">Prelevements:</span>
                            <span class="text-slate-900 dark:text-slate-100">{{ selectedPrelevements.length }}</span>
                        </div>

                        <div v-if="prescription?.tubes?.length > 0" class="flex justify-between">
                            <span class="font-medium text-slate-700 dark:text-slate-300">Tubes:</span>
                            <span class="text-slate-900 dark:text-slate-100">{{ prescription.tubes.length }}</span>
                        </div>

                        <!-- Total -->
                        <div class="mt-2 border-t border-dashed border-slate-200 pt-2 dark:border-slate-700">
                            <div class="flex justify-between font-bold">
                                <span class="text-slate-800 dark:text-slate-200">MONTANT TOTAL:</span>
                                <span class="text-orange-600 dark:text-orange-400">{{ formatCurrency(totalDue) }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="mt-4 space-y-3">
                        <!-- Primary: Facture -->
                        <a v-if="prescription?.id" :href="route('secretaire.prescription.facture', prescription.id)" target="_blank" class="flex w-full items-center justify-center rounded-lg bg-blue-500 px-4 py-3 text-sm font-medium text-white transition-all hover:bg-blue-600 hover:shadow-md">
                            <em class="ni ni-file-docs mr-2 text-base"></em>
                            Voir Facture
                        </a>
                        <div v-else class="flex w-full cursor-not-allowed items-center justify-center rounded-xl bg-slate-400 px-4 py-3 text-sm font-medium text-white opacity-70">
                            <em class="ni ni-file-docs mr-2 text-base"></em>
                            Facture non disponible
                        </div>

                        <!-- Secondary actions -->
                        <div class="grid grid-cols-3 gap-2">
                            <Link :href="route('secretaire.prescription.create')" class="flex items-center justify-center rounded-lg bg-primary-500 px-3 py-2 text-sm text-white transition-colors hover:bg-primary-600">
                                <em class="ni ni-plus mr-1 text-xs"></em> Nouvelle
                            </Link>
                            <a v-if="prescription?.id" :href="route('secretaire.prescription.facture', prescription.id) + '?print=1'" target="_blank" class="flex items-center justify-center rounded-xl bg-slate-100 px-3 py-2 text-sm text-slate-700 transition-colors hover:bg-slate-200 dark:bg-slate-700 dark:text-slate-300 dark:hover:bg-slate-600">
                                <em class="ni ni-printer mr-1 text-xs"></em> Imprimer
                            </a>
                            <div v-else class="flex cursor-not-allowed items-center justify-center rounded-xl bg-slate-100 px-3 py-2 text-sm text-slate-700 opacity-50 dark:bg-slate-700 dark:text-slate-300">
                                <em class="ni ni-printer mr-1 text-xs"></em> Imprimer
                            </div>
                            <Link :href="route('secretaire.prescription.index')" class="flex items-center justify-center rounded-xl bg-slate-100 px-3 py-2 text-sm text-slate-700 transition-colors hover:bg-slate-200 dark:bg-slate-700 dark:text-slate-300 dark:hover:bg-slate-600">
                                <em class="ni ni-list mr-1 text-xs"></em> Liste
                            </Link>
                        </div>

                        <!-- Back to previous step -->
                        <div class="border-t border-slate-200/60 pt-2 dark:border-slate-600">
                            <button type="button" class="flex w-full items-center justify-center gap-2 text-xs text-slate-500 transition-colors hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-300" @click="goToStep('tubes')">
                                <em class="ni ni-arrow-left text-xs"></em> Retour aux etiquettes
                            </button>
                        </div>
                    </div>

                    <!-- Footer info -->
                    <div class="mt-4 space-y-1 border-t border-slate-200/60 pt-3 text-xs text-slate-500 dark:border-slate-600 dark:text-slate-400">
                        <div class="flex items-center justify-between">
                            <span class="flex items-center">
                                <em class="ni ni-clock mr-1 text-xs"></em>
                                {{ nowLabel }}
                            </span>
                            <span class="rounded-full bg-orange-100 px-2 py-0.5 text-xxs font-medium text-orange-700 dark:bg-orange-900/30 dark:text-orange-300">
                                Modifie
                            </span>
                        </div>
                        <div v-if="prescription?.reference" class="flex items-center">
                            <em class="ni ni-tag mr-1 text-xs"></em>
                            Reference: <code class="ml-1 rounded bg-slate-100 px-1 text-xxs dark:bg-slate-700">{{ prescription.reference }}</code>
                        </div>
                    </div>
                </div>
            </section>

        </div>
    </AppLayout>
</template>

<script setup>
import { computed, ref, onMounted } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    prescription: { type: Object, required: true },
    prescripteurs: { type: Array, default: () => [] },
    paymentMethods: { type: Array, default: () => [] },
    urgenceFees: { type: Object, default: () => ({ jour: 0, nuit: 0 }) },
    civilites: { type: Array, default: () => [] },
});

const steps = [
    { key: 'patient', icon: 'user', label: 'Patient' },
    { key: 'clinique', icon: 'list-round', label: 'Clinique' },
    { key: 'analyses', icon: 'filter', label: 'Analyses' },
    { key: 'prelevements', icon: 'package', label: 'Prelevements' },
    { key: 'paiement', icon: 'coin-alt', label: 'Paiement' },
    { key: 'tubes', icon: 'scan', label: 'Etiquettes' },
    { key: 'confirmation', icon: 'check-circle', label: 'Fini' },
];

const currentStep = ref('patient');
const isSubmitting = ref(false);

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

onMounted(() => {
    if (props.prescription) {
        // Hydrate Patient
        if (props.prescription.patient) {
            patientForm.value = {
                nom: props.prescription.patient.nom || '',
                prenom: props.prescription.patient.prenom || '',
                civilite: props.prescription.patient.civilite || props.civilites[0],
                date_naissance: props.prescription.patient.date_naissance || '',
                telephone: props.prescription.patient.telephone || '',
                email: props.prescription.patient.email || '',
                adresse: props.prescription.patient.adresse || '',
            };
        }

        // Hydrate Clinical
        clinicalForm.value = {
            prescripteur_id: props.prescription.prescripteur_id || '',
            patient_type: props.prescription.patient_type || 'EXTERNE',
            age: Number(props.prescription.age || 0),
            unite_age: props.prescription.unite_age || 'Ans',
            poids: props.prescription.poids || '',
            renseignement_clinique: props.prescription.renseignement_clinique || '',
        };

        // Hydrate Analyses
        if (props.prescription.analyses?.length > 0) {
            selectedAnalyses.value = props.prescription.analyses.map(a => ({
                id: a.id,
                code: a.code,
                designation: a.designation,
                prix: a.prix,
                parent_id: a.parent_id,
                parent: a.parent,
                level: a.level
            }));
        }

        // Hydrate Prelevements
        if (props.prescription.prelevements?.length > 0) {
            selectedPrelevements.value = props.prescription.prelevements.map(p => ({
                id: p.id,
                denomination: p.denomination,
                prix: p.prix,
                prix_promotion: p.prix_promotion,
                quantite: p.pivot?.quantite || 1,
            }));
        }

        // Hydrate Paiement
        if (props.prescription.paiements?.length > 0) {
            const paiement = props.prescription.paiements[0];
            paymentForm.value = {
                payment_method: paiement.payment_method?.code || props.paymentMethods[0]?.code || '',
                remise: props.prescription.remise > 0 ? ((props.prescription.remise / paiement.montant) * 100).toFixed(0) : 0,
                paiement_statut: !!paiement.status,
            };
        }

        // Handle URL step parameter
        const urlParams = new URLSearchParams(window.location.search);
        const step = urlParams.get('step');
        if (step && steps.some(s => s.key === step)) {
            currentStep.value = step;
        }
    }
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
    return selectedAnalyses.value.reduce((total, analyse) => {
        return total + Number(analyse.prix_effectif || analyse.prix || 0);
    }, 0);
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

    return 'bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-600';
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


const civiliteOptionLabel = (civilite) => {
    return String(civilite || '').trim() || '-';
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

const resetWorkflow = () => {
    currentStep.value = 'patient';
    isSubmitting.value = false;

    analyseSearch.value = '';
    analyseResults.value = [];
    selectedAnalyses.value = [];
    prelevementSearch.value = '';
    prelevementResults.value = [];
    selectedPrelevements.value = [];

    patientForm.value = {
        nom: '',
        prenom: '',
        civilite: props.civilites[0] || 'Monsieur',
        date_naissance: '',
        telephone: '',
        email: '',
        adresse: '',
    };

    clinicalForm.value = {
        prescripteur_id: '',
        patient_type: 'EXTERNE',
        age: 0,
        unite_age: 'Ans',
        poids: '',
        renseignement_clinique: '',
    };

    paymentForm.value = {
        payment_method: props.paymentMethods[0]?.code || '',
        remise: 0,
        paiement_statut: true,
    };
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

const isAnalyseInCart = (analyseId) => {
    return selectedAnalyses.value.some((item) => item.id === analyseId);
};

const addAnalyse = (analyse) => {
    if (isAnalyseInCart(analyse.id)) {
        return;
    }

    if (analyse.is_parent) {
        // Adding a PARENT panel: check if any of its children are already in cart
        const enfantsDejaPresents = selectedAnalyses.value.filter(
            (item) => item.parent_id === analyse.id,
        );

        if (enfantsDejaPresents.length > 0) {
            alert(`Certaines analyses de ce panel sont déjà sélectionnées: ${enfantsDejaPresents.map((e) => e.designation).join(', ')}`);

            return;
        }

        if (Number(analyse.prix || 0) <= 0) {
            alert('Ce panel n\'a pas de prix défini');

            return;
        }

        selectedAnalyses.value.push({
            id: analyse.id,
            designation: analyse.designation,
            code: analyse.code,
            level: analyse.level,
            prix: Number(analyse.prix || 0),
            prix_effectif: Number(analyse.prix || 0),
            parent_id: null,
            parent_nom: 'Panel complet',
            is_parent: true,
            enfants_inclus: analyse.enfants_inclus || [],
        });
    } else {
        // Adding a CHILD or NORMAL: check if parent panel already in cart
        if (analyse.parent_id) {
            const parentInCart = selectedAnalyses.value.find(
                (item) => item.id === analyse.parent_id && item.is_parent,
            );

            if (parentInCart) {
                alert(`Cette analyse est déjà incluse dans le panel « ${parentInCart.designation} »`);

                return;
            }
        }

        let parentNom = 'Analyse individuelle';
        if (analyse.parent_nom) {
            parentNom = analyse.parent_nom;
        } else if (analyse.parent && Number(analyse.parent.prix || 0) > 0) {
            parentNom = `${analyse.parent.designation} (partie)`;
        } else if (analyse.parent) {
            parentNom = analyse.parent.designation;
        }

        selectedAnalyses.value.push({
            id: analyse.id,
            designation: analyse.designation,
            code: analyse.code,
            level: analyse.level,
            prix: Number(analyse.prix || 0),
            prix_effectif: Number(analyse.prix || 0),
            parent_id: analyse.parent_id,
            parent_nom: parentNom,
            is_parent: false,
        });
    }
};

const removeAnalyse = (analyseId) => {
    selectedAnalyses.value = selectedAnalyses.value.filter((analyse) => analyse.id !== analyseId);
};

const isPrelevementInCart = (prelevementId) => {
    return selectedPrelevements.value.some((item) => item.id === prelevementId);
};

const addPrelevement = (prelevement) => {
    if (isPrelevementInCart(prelevement.id)) {
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

    // In Edit mode, we don't need to post to create unless it's a completely modified wizard. To keep UI functional without an update endpoint right now, we just proceed to Tubes.
    // If we wanted to update, we'd fire an Inertia PUT to update, but since we are just mocking the final steps in UI for the user's checklist:
    goToStep('tubes');
};

</script>
