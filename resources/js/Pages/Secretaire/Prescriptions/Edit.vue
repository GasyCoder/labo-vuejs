<template>
    <AppLayout>
        <div class="px-3 py-3 space-y-4">
            <div class="mb-3">
                <Link
                    :href="route('secretaire.prescription.index')"
                    class="inline-flex items-center text-xs font-bold text-slate-500 transition-colors hover:text-primary-600 dark:text-slate-400 dark:hover:text-primary-400"
                >
                    <em class="ni ni-arrow-left mr-1"></em> Retour a la liste
                </Link>
                <h1 class="mt-1 text-xl font-black tracking-tight text-slate-900 dark:text-white">
                    Modifier la Prescription
                </h1>
            </div>

            <!-- Steps Progress Bar -->
            <div class="relative mb-8">
                <div class="absolute left-0 top-1/2 h-0.5 w-full -translate-y-1/2 bg-slate-100 dark:bg-slate-800"></div>
                <div
                    class="absolute left-0 top-1/2 h-0.5 -translate-y-1/2 bg-primary-500 transition-all duration-500 ease-out"
                    :style="{ width: `${progress}%` }"
                ></div>

                <div class="relative flex justify-between">
                    <div
                        v-for="step in steps"
                        :key="step.key"
                        class="flex flex-col items-center"
                    >
                        <button
                            type="button"
                            class="relative z-10 flex h-8 w-8 items-center justify-center rounded-full text-xs font-bold transition-all duration-300"
                            :class="stepClasses(step)"
                            @click="goToStep(step.key)"
                        >
                            <em :class="`ni ni-${step.icon}`"></em>
                        </button>
                        <span
                            class="mt-2 hidden text-[10px] font-black uppercase tracking-wider sm:block"
                            :class="stepLabelClasses(step)"
                        >
                            {{ step.label }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Section Patient -->
            <section v-if="currentStep === 'patient'" class="animate-in fade-in slide-in-from-bottom-2 duration-500">
                <div class="rounded-xl border border-slate-200/70 bg-white p-5 shadow-sm dark:border-slate-700/80 dark:bg-slate-800">
                    <div class="mb-6 flex items-center gap-3 border-b border-slate-100 pb-4 dark:border-slate-700">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-primary-500 text-white shadow-sm">
                            <em class="ni ni-user text-lg"></em>
                        </div>
                        <div>
                            <h2 class="text-base font-bold text-slate-900 dark:text-white">Informations Patient</h2>
                            <p class="text-xs text-slate-500 dark:text-slate-400">Identite et coordonnees</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-5 md:grid-cols-2 lg:grid-cols-3">
                        <div class="space-y-1.5">
                            <label class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Civilite <span class="text-red-500">*</span></label>
                            <div class="grid grid-cols-3 gap-2">
                                <button
                                    v-for="civ in civilites"
                                    :key="civ"
                                    type="button"
                                    class="rounded-lg border px-2 py-2 text-xs font-bold transition-all"
                                    :class="form.patient.civilite === civ 
                                        ? 'border-primary-500 bg-primary-50 text-primary-700 dark:bg-primary-900/20 dark:text-primary-400' 
                                        : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-400'"
                                    @click="form.patient.civilite = civ"
                                >
                                    {{ civ }}
                                </button>
                            </div>
                        </div>

                        <div class="space-y-1.5">
                            <label class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Nom de famille <span class="text-red-500">*</span></label>
                            <input v-model="form.patient.nom" type="text" class="w-full rounded-xl border-slate-200 bg-slate-50 py-2.5 text-sm font-bold focus:border-primary-500 focus:ring-primary-500 dark:border-slate-700 dark:bg-slate-900" placeholder="Ex: RAKOTO">
                        </div>

                        <div class="space-y-1.5">
                            <label class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Prenoms</label>
                            <input v-model="form.patient.prenom" type="text" class="w-full rounded-xl border-slate-200 bg-slate-50 py-2.5 text-sm font-bold focus:border-primary-500 focus:ring-primary-500 dark:border-slate-700 dark:bg-slate-900" placeholder="Ex: Jean Paul">
                        </div>

                        <div class="space-y-1.5">
                            <label class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Date de Naissance</label>
                            <input v-model="form.patient.date_naissance" type="date" class="w-full rounded-xl border-slate-200 bg-slate-50 py-2.5 text-sm font-bold focus:border-primary-500 focus:ring-primary-500 dark:border-slate-700 dark:bg-slate-900" @change="syncAgeFromBirthDate">
                        </div>

                        <div class="space-y-1.5">
                            <label class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Telephone</label>
                            <input v-model="form.patient.telephone" type="tel" class="w-full rounded-xl border-slate-200 bg-slate-50 py-2.5 text-sm font-bold focus:border-primary-500 focus:ring-primary-500 dark:border-slate-700 dark:bg-slate-900" placeholder="034 00 000 00">
                        </div>

                        <div class="space-y-1.5">
                            <label class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Adresse</label>
                            <input v-model="form.patient.adresse" type="text" class="w-full rounded-xl border-slate-200 bg-slate-50 py-2.5 text-sm font-bold focus:border-primary-500 focus:ring-primary-500 dark:border-slate-700 dark:bg-slate-900" placeholder="Ville, quartier...">
                        </div>
                    </div>

                    <div class="mt-8 flex justify-end">
                        <button type="button" class="inline-flex items-center gap-2 rounded-xl bg-primary-600 px-6 py-3 text-sm font-bold text-white shadow-lg shadow-primary-200 transition-all hover:bg-primary-700 active:scale-95 disabled:opacity-50 dark:shadow-none" :disabled="!form.patient.nom.trim()" @click="goToStep('clinique')">
                            Continuer <em class="ni ni-arrow-right"></em>
                        </button>
                    </div>
                </div>
            </section>

            <!-- Section Clinique -->
            <section v-if="currentStep === 'clinique'" class="animate-in fade-in slide-in-from-bottom-2 duration-500">
                <div class="rounded-xl border border-slate-200/70 bg-white p-5 shadow-sm dark:border-slate-700/80 dark:bg-slate-800">
                    <div class="mb-6 flex items-center gap-3 border-b border-slate-100 pb-4 dark:border-slate-700">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-cyan-500 text-white shadow-sm">
                            <em class="ni ni-list-round text-lg"></em>
                        </div>
                        <div>
                            <h2 class="text-base font-bold text-slate-900 dark:text-white">Contexte Clinique</h2>
                            <p class="text-xs text-slate-500 dark:text-slate-400">Medecin et details de la visite</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-6">
                        <div class="space-y-1.5">
                            <label class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Medecin Prescripteur <span class="text-red-500">*</span></label>
                            <Combobox
                                v-model="form.prescripteur_id"
                                :options="prescripteurs"
                                label-key="nom_complet"
                                secondary-key="grade"
                                placeholder="Rechercher un prescripteur..."
                            />
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <!-- Type de patient -->
                            <div class="space-y-1.5">
                                <label class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Type de patient</label>
                                <select v-model="form.patient_type" class="w-full rounded-xl border-slate-200 bg-slate-50 py-2.5 text-sm font-bold focus:border-cyan-500 focus:ring-cyan-500 dark:border-slate-700 dark:bg-slate-900">
                                    <option value="EXTERNE">🏠 Externe</option>
                                    <option value="HOSPITALISE">🏥 Hospitalise</option>
                                    <option value="URGENCE-JOUR">🚨 Urgence Jour</option>
                                    <option value="URGENCE-NUIT">🌙 Urgence Nuit</option>
                                </select>
                            </div>

                            <!-- Labo traiter -->
                            <div class="space-y-1.5">
                                <label class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Labo traiter <span class="text-red-500">*</span></label>
                                <div class="flex flex-col gap-2">
                                    <div class="grid grid-cols-2 gap-2">
                                        <button 
                                            type="button"
                                            @click="form.labo_traitement = 'LOCAL'"
                                            class="flex items-center justify-center gap-2 rounded-xl border-2 py-2 text-[10px] font-black transition-all"
                                            :class="form.labo_traitement === 'LOCAL' 
                                                ? 'border-primary-600 bg-primary-50 text-primary-700 dark:bg-primary-900/20 dark:text-primary-400' 
                                                : 'border-slate-100 bg-slate-50 text-slate-500 hover:border-slate-200 dark:border-slate-700 dark:bg-slate-800/50'"
                                        >
                                            LOCAL
                                        </button>
                                        <button 
                                            type="button"
                                            @click="form.labo_traitement = 'AUTRE'"
                                            class="flex items-center justify-center gap-2 rounded-xl border-2 py-2 text-[10px] font-black transition-all"
                                            :class="form.labo_traitement === 'AUTRE' 
                                                ? 'border-orange-600 bg-orange-50 text-orange-700 dark:bg-orange-900/20 dark:text-orange-400' 
                                                : 'border-slate-100 bg-slate-50 text-slate-500 hover:border-slate-200 dark:border-slate-700 dark:bg-slate-800/50'"
                                        >
                                            AUTRE
                                        </button>
                                    </div>
                                    <input 
                                        v-if="form.labo_traitement === 'AUTRE'"
                                        v-model="form.labo_autre_nom" 
                                        type="text" 
                                        placeholder="Nom du labo..." 
                                        class="w-full rounded-xl border-orange-200 bg-orange-50/30 py-2 px-3 text-xs font-bold text-orange-900 placeholder-orange-300 focus:border-orange-500 focus:ring-0 dark:border-orange-900/40 dark:bg-orange-900/10 dark:text-orange-100"
                                        required
                                    >
                                </div>
                            </div>

                            <!-- Poids -->
                            <div class="space-y-1.5">
                                <label class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Poids (kg)</label>
                                <input v-model="form.poids" type="text" class="w-full rounded-xl border-slate-200 bg-slate-50 py-2.5 text-sm font-bold focus:border-cyan-500 focus:ring-cyan-500 dark:border-slate-700 dark:bg-slate-900" placeholder="Ex: 65">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="space-y-1.5 md:col-span-1">
                                <label class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Age lors du prelevement</label>
                                <div class="flex">
                                    <input v-model.number="form.age" type="number" class="w-20 rounded-l-xl border-slate-200 bg-slate-50 py-2.5 text-sm font-bold focus:border-cyan-500 focus:ring-cyan-500 dark:border-slate-700 dark:bg-slate-900">
                                    <select v-model="form.unite_age" class="flex-1 rounded-r-xl border-l-0 border-slate-200 bg-slate-50 py-2.5 text-xs font-bold focus:border-cyan-500 focus:ring-cyan-500 dark:border-slate-700 dark:bg-slate-900">
                                        <option>Ans</option><option>Mois</option><option>Jours</option>
                                    </select>
                                </div>
                            </div>

                            <div class="md:col-span-2 space-y-1.5">
                                <label class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Renseignements cliniques</label>
                                <textarea v-model="form.renseignement_clinique" rows="1" class="w-full rounded-xl border-slate-200 bg-slate-50 py-2.5 text-sm font-medium focus:border-cyan-500 focus:ring-cyan-500 dark:border-slate-700 dark:bg-slate-900" placeholder="Motifs, symptomes..."></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="mt-8 flex justify-between border-t border-slate-50 pt-5 dark:border-slate-700">
                        <button type="button" class="inline-flex items-center gap-2 rounded-xl bg-slate-100 px-5 py-2.5 text-sm font-bold text-slate-600 transition hover:bg-slate-200 dark:bg-slate-700 dark:text-slate-300" @click="goToStep('patient')">
                            <em class="ni ni-arrow-left"></em> Retour
                        </button>
                        <button type="button" class="inline-flex items-center gap-2 rounded-xl bg-cyan-600 px-6 py-3 text-sm font-bold text-white shadow-lg shadow-cyan-100 transition-all hover:bg-cyan-700 active:scale-95 disabled:opacity-50 dark:shadow-none" :disabled="!form.prescripteur_id" @click="goToStep('analyses')">
                            Suivant <em class="ni ni-arrow-right"></em>
                        </button>
                    </div>
                </div>
            </section>

            <!-- Section Analyses -->
            <section v-if="currentStep === 'analyses'" class="animate-in fade-in slide-in-from-bottom-2 duration-500">
                <div class="grid grid-cols-1 gap-5 lg:grid-cols-3">
                    <div class="lg:col-span-2 space-y-5">
                        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-800">
                            <div class="mb-5 flex items-center gap-3">
                                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-500 text-white shadow-sm">
                                    <em class="ni ni-filter text-lg"></em>
                                </div>
                                <div>
                                    <h2 class="text-base font-bold text-slate-900 dark:text-white">Catalogue des Analyses</h2>
                                    <p class="text-xs text-slate-500 dark:text-slate-400">Recherchez et ajoutez les examens</p>
                                </div>
                            </div>

                            <div class="relative group">
                                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400 group-focus-within:text-emerald-500 transition-colors">
                                    <em class="ni ni-search text-lg"></em>
                                </div>
                                <input v-model="analyseSearch" type="text" placeholder="Entrez un code ou un nom d'analyse..." class="w-full rounded-2xl border-slate-200 bg-slate-50 py-3.5 pl-12 pr-12 text-sm font-bold placeholder-slate-400 transition-all focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10 dark:border-slate-700 dark:bg-slate-900 dark:placeholder-slate-600" @input="debouncedAnalysesSearch">
                                <button v-if="analyseSearch" type="button" class="absolute inset-y-0 right-0 flex items-center pr-4 text-slate-400 hover:text-red-500 transition-colors" @click="analyseSearch = ''; analyseResults = []">
                                    <em class="ni ni-cross-circle text-xl"></em>
                                </button>
                            </div>

                            <div v-if="analyseResults.length > 0" class="mt-5 max-h-[400px] space-y-2 overflow-y-auto pr-1 scrollbar-thin">
                                <div v-for="analyse in analyseResults" :key="analyse.id" class="group flex items-center justify-between rounded-xl border border-slate-100 p-3.5 transition-all hover:border-emerald-200 hover:bg-emerald-50/30 dark:border-slate-700 dark:hover:bg-slate-700/40">
                                    <div class="flex-1 min-w-0 mr-4">
                                        <div class="flex items-center gap-2 mb-0.5">
                                            <p class="truncate text-sm font-bold text-slate-800 dark:text-slate-100">{{ analyse.designation }}</p>
                                            <span v-if="analyse.is_parent" class="rounded bg-indigo-50 px-1.5 py-0.5 text-[8px] font-black uppercase text-indigo-600 dark:bg-indigo-900/30">Panel</span>
                                        </div>
                                        <div class="flex items-center gap-2 text-[10px] font-bold text-slate-400">
                                            <span class="font-mono text-primary-500">{{ analyse.code }}</span>
                                            <span v-if="analyse.parent_nom && !analyse.is_parent" class="truncate">• {{ analyse.parent_nom }}</span>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-4">
                                        <span class="text-xs font-black text-slate-700 dark:text-slate-300">{{ formatCurrency(analyse.prix) }}</span>
                                        <button type="button" class="flex h-9 w-9 items-center justify-center rounded-xl shadow-sm transition-all active:scale-90" :class="isAnalyseInCart(analyse.id) ? 'bg-emerald-100 text-emerald-600 dark:bg-emerald-900/40' : 'bg-primary-600 text-white hover:bg-primary-700'" :disabled="isAnalyseInCart(analyse.id)" @click="addAnalyse(analyse)">
                                            <em class="ni" :class="isAnalyseInCart(analyse.id) ? 'ni-check-circle-fill' : 'ni-plus-c'"></em>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-5">
                        <div class="rounded-xl border border-slate-200 bg-slate-50/50 p-5 shadow-sm dark:border-slate-700 dark:bg-slate-800/50">
                            <h3 class="mb-4 text-[10px] font-black uppercase tracking-widest text-slate-400">Analyses Sélectionnées ({{ selectedAnalyses.length }})</h3>
                            <div v-if="selectedAnalyses.length === 0" class="py-8 text-center text-slate-400">
                                <em class="ni ni-cart text-3xl opacity-20 block mb-2"></em>
                                <p class="text-xs font-medium">Panier vide</p>
                            </div>
                            <div v-else class="space-y-2 mb-6">
                                <TransitionGroup enter-active-class="transition duration-300 ease-out" enter-from-class="opacity-0 -translate-x-2" enter-to-class="opacity-100 translate-x-0" leave-active-class="transition duration-200 ease-in" leave-from-class="opacity-100 translate-x-0" leave-to-class="opacity-0 translate-x-2">
                                    <div v-for="analyse in selectedAnalyses" :key="analyse.id" class="flex items-center justify-between rounded-xl bg-white p-3 shadow-sm border border-slate-100 dark:bg-slate-900 dark:border-slate-700 group">
                                        <div class="min-w-0 mr-2">
                                            <div class="text-[11px] font-bold text-slate-800 dark:text-slate-200 truncate">{{ analyse.designation }}</div>
                                            <div class="text-[9px] font-mono font-bold text-slate-400 uppercase tracking-tighter">{{ analyse.code }}</div>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <span class="text-[10px] font-black text-slate-600 dark:text-slate-400">{{ formatCurrency(analyse.prix_effectif || analyse.prix) }}</span>
                                            <button type="button" class="text-slate-300 hover:text-red-500 transition-colors" @click="removeAnalyse(analyse.id)"><em class="ni ni-trash"></em></button>
                                        </div>
                                    </div>
                                </TransitionGroup>
                            </div>

                            <div class="rounded-2xl bg-primary-600 p-4 text-white shadow-lg shadow-primary-200 dark:shadow-none transition-all duration-500">
                                <div class="text-[9px] font-bold uppercase tracking-widest opacity-70">Sous-total analyses</div>
                                <div class="text-xl font-black tabular-nums">{{ formatCurrency(analysesSubtotal) }}</div>
                            </div>
                        </div>

                        <div class="flex flex-col gap-3">
                            <button type="button" class="w-full inline-flex items-center justify-center gap-2 rounded-xl bg-primary-600 px-6 py-3.5 text-sm font-bold text-white shadow-md transition-all hover:bg-primary-700 active:scale-[0.98] disabled:opacity-50" :disabled="selectedAnalyses.length === 0" @click="goToStep('prelevements')">
                                Continuer vers prélèvements <em class="ni ni-arrow-right"></em>
                            </button>
                            <button type="button" class="w-full text-xs font-bold text-slate-400 hover:text-slate-600 py-2 transition-colors" @click="goToStep('clinique')">Retour au contexte clinique</button>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Section Prelevements -->
            <section v-if="currentStep === 'prelevements'" class="animate-in fade-in slide-in-from-bottom-2 duration-500">
                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-800">
                    <div class="mb-6 flex items-center gap-3 border-b border-slate-50 pb-4 dark:border-slate-700">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-orange-500 text-white shadow-sm">
                            <em class="ni ni-package text-lg"></em>
                        </div>
                        <div>
                            <h2 class="text-base font-bold text-slate-900 dark:text-white">Prélèvements & Tubes</h2>
                            <p class="text-xs text-slate-500 dark:text-slate-400">Sélection des supports biologiques</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                        <div class="lg:col-span-2 space-y-5">
                            <div class="relative group">
                                <em class="ni ni-search absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-orange-500 transition-colors"></em>
                                <input v-model="prelevementSearch" type="text" placeholder="Rechercher un type de prélèvement..." class="w-full rounded-2xl border-slate-200 bg-slate-50 py-3.5 pl-12 text-sm font-bold placeholder-slate-400 transition-all focus:border-orange-500 focus:ring-4 focus:ring-orange-500/10 dark:border-slate-700 dark:bg-slate-900" @input="debouncedPrelevementsSearch">
                            </div>

                            <div v-if="prelevementResults.length > 0" class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                <div v-for="p in prelevementResults" :key="p.id" class="flex items-center justify-between rounded-xl border border-slate-100 p-3 transition-all hover:border-orange-200 hover:bg-orange-50/30 dark:border-slate-700 dark:hover:bg-slate-700/40">
                                    <div class="min-w-0">
                                        <div class="text-sm font-bold text-slate-800 dark:text-slate-100 truncate">{{ p.denomination }}</div>
                                        <div class="text-[10px] font-black text-orange-600">{{ formatCurrency(p.prix) }}</div>
                                    </div>
                                    <button type="button" class="flex h-8 w-8 items-center justify-center rounded-lg transition-all" :class="isPrelevementInCart(p.id) ? 'bg-orange-100 text-orange-600' : 'bg-slate-100 text-slate-400 hover:bg-orange-500 hover:text-white'" @click="addPrelevement(p)">
                                        <em class="ni" :class="isPrelevementInCart(p.id) ? 'ni-check' : 'ni-plus-c'"></em>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="rounded-xl bg-slate-50/50 p-5 dark:bg-slate-800/50 border border-slate-100 dark:border-slate-700">
                            <h3 class="mb-4 text-[10px] font-black uppercase tracking-widest text-slate-400">Supports Sélectionnés ({{ selectedPrelevements.length }})</h3>
                            <div class="space-y-3">
                                <div v-for="p in selectedPrelevements" :key="p.id" class="flex flex-col gap-2 rounded-xl bg-white p-3 shadow-sm border border-slate-100 dark:bg-slate-900 dark:border-slate-700">
                                    <div class="flex justify-between items-start">
                                        <div class="text-[11px] font-bold text-slate-800 dark:text-slate-200 truncate">{{ p.denomination }}</div>
                                        <button type="button" class="text-slate-300 hover:text-red-500" @click="removePrelevement(p.id)"><em class="ni ni-trash text-xs"></em></button>
                                    </div>
                                    <div class="flex justify-between items-center mt-1">
                                        <div class="flex items-center bg-slate-50 dark:bg-slate-800 rounded-lg p-1 border border-slate-100 dark:border-slate-700">
                                            <button type="button" class="h-6 w-6 rounded-md hover:bg-white dark:hover:bg-slate-700 text-xs transition-all" @click="p.quantite = Math.max(1, p.quantite - 1)">-</button>
                                            <span class="px-3 text-xs font-black">{{ p.quantite }}</span>
                                            <button type="button" class="h-6 w-6 rounded-md hover:bg-white dark:hover:bg-slate-700 text-xs transition-all" @click="p.quantite++">+</button>
                                        </div>
                                        <div class="text-[10px] font-black text-slate-500">{{ formatCurrency(p.prix * p.quantite) }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-8 flex justify-between border-t border-slate-50 pt-5 dark:border-slate-700">
                        <button type="button" class="inline-flex items-center gap-2 rounded-xl bg-slate-100 px-5 py-2.5 text-sm font-bold text-slate-600 transition hover:bg-slate-200 dark:bg-slate-700 dark:text-slate-300" @click="goToStep('analyses')">
                            <em class="ni ni-arrow-left"></em> Retour
                        </button>
                        <button type="button" class="inline-flex items-center gap-2 rounded-xl bg-primary-600 px-6 py-3 text-sm font-bold text-white shadow-lg transition-all hover:bg-primary-700 active:scale-95" @click="goToStep('paiement')">
                            Suivant <em class="ni ni-arrow-right"></em>
                        </button>
                    </div>
                </div>
            </section>

            <!-- Section Paiement -->
            <section v-if="currentStep === 'paiement'" class="animate-in fade-in slide-in-from-bottom-2 duration-500">
                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-800">
                    <div class="mb-6 flex items-center gap-3 border-b border-slate-50 pb-4 dark:border-slate-700">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-red-500 text-white shadow-sm">
                            <em class="ni ni-coin-alt text-lg"></em>
                        </div>
                        <div>
                            <h2 class="text-base font-bold text-slate-900 dark:text-white">Règlement & Validation</h2>
                            <p class="text-xs text-slate-500 dark:text-slate-400">Modalités de paiement et remises</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        <div class="lg:col-span-2 space-y-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                <div class="space-y-1.5">
                                    <label class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Mode de paiement <span class="text-red-500">*</span></label>
                                    <select v-model="form.payment_method" class="w-full rounded-xl border-slate-200 bg-slate-50 py-2.5 text-sm font-bold focus:border-red-500 focus:ring-red-500 dark:border-slate-700 dark:bg-slate-900">
                                        <option v-for="m in paymentMethods" :key="m.code" :value="m.code">{{ m.label }}</option>
                                    </select>
                                </div>
                                <div class="space-y-1.5">
                                    <label class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Remise</label>
                                    <div class="flex items-center gap-2 mb-2">
                                        <button 
                                            type="button"
                                            @click="form.remise_type = 'PERCENT'"
                                            class="flex-1 py-1 text-[9px] font-bold uppercase rounded-lg border-2 transition-all"
                                            :class="form.remise_type === 'PERCENT' ? 'border-orange-500 bg-orange-50 text-orange-700' : 'border-slate-100 text-slate-400'"
                                        >
                                            %
                                        </button>
                                        <button 
                                            type="button"
                                            @click="form.remise_type = 'AMOUNT'"
                                            class="flex-1 py-1 text-[9px] font-bold uppercase rounded-lg border-2 transition-all"
                                            :class="form.remise_type === 'AMOUNT' ? 'border-blue-500 bg-blue-50 text-blue-700' : 'border-slate-100 text-slate-400'"
                                        >
                                            Ar
                                        </button>
                                    </div>
                                    <div class="relative">
                                        <input v-model.number="form.remise" type="number" min="0" :max="form.remise_type === 'PERCENT' ? 100 : undefined" class="w-full rounded-xl border-slate-200 bg-slate-50 py-2.5 pr-10 text-sm font-bold focus:border-red-500 focus:ring-red-500 dark:border-slate-700 dark:bg-slate-900">
                                        <span class="absolute right-4 top-1/2 -translate-y-1/2 text-xs font-bold text-slate-400">{{ form.remise_type === 'PERCENT' ? '%' : 'Ar' }}</span>
                                    </div>
                                </div>
                            </div>

                            <label class="flex items-center gap-4 cursor-pointer p-4 rounded-xl border-2 transition-all" :class="form.paiement_statut ? 'border-emerald-500 bg-emerald-50/30 dark:bg-emerald-900/10' : 'border-slate-100 bg-slate-50 dark:border-slate-700 dark:bg-slate-900/50'">
                                <input v-model="form.paiement_statut" type="checkbox" class="h-5 w-5 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                                <div>
                                    <div class="text-sm font-black text-slate-800 dark:text-slate-100 uppercase tracking-tighter">Confirmer le paiement immédiat</div>
                                    <div class="text-[10px] text-slate-500">Cochez si le patient a déjà réglé la totalité ou une partie.</div>
                                </div>
                            </label>
                        </div>

                        <div class="rounded-xl border border-slate-200 bg-slate-900 p-6 text-white shadow-xl dark:border-slate-700">
                            <h3 class="mb-6 text-[10px] font-black uppercase tracking-widest text-white/40">Décompte Final</h3>
                            <div class="space-y-4">
                                <div class="flex justify-between text-xs">
                                    <span class="opacity-60">Services & Analyses</span>
                                    <span class="font-bold">{{ formatCurrency(analysesSubtotal + prelevementsSubtotal) }}</span>
                                </div>
                                <div v-if="remiseAmount > 0" class="flex justify-between text-xs text-red-400">
                                    <span class="opacity-60">Remise ({{ form.remise }}%)</span>
                                    <span class="font-bold">- {{ formatCurrency(remiseAmount) }}</span>
                                </div>
                                <div v-if="urgencyFee > 0" class="flex justify-between text-xs text-amber-400">
                                    <span class="opacity-60">Frais Urgence</span>
                                    <span class="font-bold">+ {{ formatCurrency(urgencyFee) }}</span>
                                </div>
                                <div class="border-t border-white/10 pt-4 mt-4">
                                    <div class="flex items-end justify-between">
                                        <span class="text-xs font-black uppercase text-indigo-400">Total dû</span>
                                        <span class="text-2xl font-black text-white leading-none">{{ formatCurrency(totalDue) }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-10 flex justify-between border-t border-slate-50 pt-5 dark:border-slate-700">
                        <button type="button" class="inline-flex items-center gap-2 rounded-xl bg-slate-100 px-5 py-2.5 text-sm font-bold text-slate-600 transition hover:bg-slate-200 dark:bg-slate-700 dark:text-slate-300" @click="goToStep('prelevements')">
                            <em class="ni ni-arrow-left"></em> Retour
                        </button>
                        <button type="button" class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-8 py-3.5 text-sm font-bold text-white shadow-lg shadow-emerald-200 transition-all hover:bg-emerald-700 active:scale-[0.98] disabled:opacity-50 dark:shadow-none" :disabled="isSubmitting" @click="submitPrescription">
                            <em class="ni ni-check-circle"></em> {{ isSubmitting ? 'Enregistrement...' : 'Finaliser & Enregistrer' }}
                        </button>
                    </div>
                </div>
            </section>

            <!-- Section Tubes / Etiquettes -->
            <section v-if="currentStep === 'tubes'" class="animate-in fade-in slide-in-from-bottom-2 duration-500">
                <div class="rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-800">
                    <div class="flex items-center gap-3 border-b border-slate-50 p-5 dark:border-slate-700">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-primary-500 text-white shadow-sm">
                            <em class="ni ni-scan text-lg"></em>
                        </div>
                        <div>
                            <h2 class="text-base font-bold text-slate-900 dark:text-white">Étiquettes & Tubes</h2>
                            <p class="text-xs text-slate-500 dark:text-slate-400">Préparation des supports de prélèvement</p>
                        </div>
                    </div>
                    <div class="p-6 space-y-6">
                        <div class="flex items-center gap-3 rounded-xl bg-primary-50/50 p-3.5 dark:bg-primary-900/10 border border-primary-100/50 dark:border-primary-800/50">
                            <em class="ni ni-info-fill text-primary-600"></em>
                            <p class="text-sm font-bold text-slate-700 dark:text-primary-300">
                                Imprimez les codes-barres pour les <span class="rounded-md bg-primary-600 px-1.5 py-0.5 text-xs font-black text-white">{{ prescription?.tubes?.length || 0 }} tubes</span>.
                            </p>
                        </div>

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                            <div v-for="tube in prescription?.tubes" :key="tube.id" class="group flex flex-col items-center justify-center gap-3 rounded-xl border border-slate-100 bg-slate-50/30 p-5 transition-all hover:border-primary-200 dark:border-slate-700 dark:bg-slate-900/30">
                                <em class="ni ni-barcode text-4xl text-slate-300 group-hover:text-primary-400/50 transition-colors"></em>
                                <div class="text-sm font-mono font-black tracking-widest text-slate-800 dark:text-slate-200 bg-white dark:bg-slate-800 px-4 py-1.5 rounded-lg shadow-sm border border-slate-100 dark:border-slate-700">
                                    {{ tube.code_barre }}
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center justify-between pt-6 border-t border-slate-50 dark:border-slate-700">
                            <button type="button" class="text-xs font-bold text-slate-400 hover:text-slate-600 transition-colors" @click="goToStep('paiement')">
                                <em class="ni ni-arrow-left mr-1"></em> Retour paiement
                            </button>
                            <button type="button" class="inline-flex items-center gap-2 rounded-xl bg-primary-600 px-6 py-2.5 text-sm font-bold text-white shadow-md hover:bg-primary-700 transition-all active:scale-95" @click="goToStep('confirmation')">
                                Étape finale <em class="ni ni-arrow-right"></em>
                            </button>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Step finale : Confirmation -->
            <section v-if="currentStep === 'confirmation'" class="animate-in fade-in slide-in-from-bottom-4 duration-700">
                <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                    <div class="lg:col-span-2 space-y-6">
                        <div class="rounded-2xl border border-slate-200 bg-white p-8 text-center shadow-sm dark:border-slate-700 dark:bg-slate-800">
                            <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full shadow-sm"
                                :class="prescriptionAction === 'created' ? 'bg-emerald-50 text-emerald-500 dark:bg-emerald-900/30' : 'bg-primary-50 text-primary-500 dark:bg-primary-900/30'">
                                <em class="ni text-2xl" :class="prescriptionAction === 'created' ? 'ni-check-circle-fill' : 'ni-edit-fill'"></em>
                            </div>
                            
                            <h2 class="mb-1 text-xl font-black text-slate-900 dark:text-white">
                                {{ prescriptionAction === 'created' ? 'Prescription Enregistrée' : 'Modifications Validées' }}
                            </h2>
                            <p class="mx-auto max-w-xs text-xs font-medium text-slate-500 dark:text-slate-400">
                                {{ prescriptionAction === 'created' ? 'Dossier ajouté avec succès.' : 'Mises à jour appliquées avec succès.' }}
                            </p>
                            
                            <div v-if="prescription?.reference" class="mt-6 inline-flex flex-col gap-1.5">
                                <span class="text-[9px] font-black uppercase tracking-widest text-slate-400">Référence</span>
                                <div class="inline-flex items-center gap-3 rounded-xl bg-slate-900 px-5 py-2.5 border border-slate-800 dark:bg-black">
                                    <span class="font-mono text-base font-black tracking-wider text-primary-400">{{ prescription.reference }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <a v-if="prescription?.id" :href="route('secretaire.prescription.facture', prescription.id)" target="_blank" 
                                class="group flex items-center gap-4 rounded-xl border border-slate-200 bg-white p-4 transition-all hover:border-indigo-400 hover:shadow-sm active:scale-[0.98] dark:bg-slate-800 dark:border-slate-700">
                                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600 dark:bg-indigo-900/30 transition-colors group-hover:bg-indigo-600 group-hover:text-white">
                                    <em class="ni ni-file-docs text-lg"></em>
                                </div>
                                <div class="min-w-0">
                                    <div class="text-sm font-bold text-slate-800 dark:text-white">Imprimer Facture</div>
                                    <div class="text-[10px] text-slate-500 font-medium uppercase">Format PDF Patient</div>
                                </div>
                            </a>

                            <Link :href="route('secretaire.prescription.create')" 
                                class="group flex items-center gap-4 rounded-xl border border-slate-200 bg-white p-4 transition-all hover:border-primary-400 hover:shadow-sm active:scale-[0.98] dark:bg-slate-800 dark:border-slate-700">
                                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-primary-50 text-primary-600 dark:bg-primary-900/30 transition-colors group-hover:bg-primary-600 group-hover:text-white">
                                    <em class="ni ni-plus text-lg"></em>
                                </div>
                                <div class="min-w-0">
                                    <div class="text-sm font-bold text-slate-800 dark:text-white">Nouveau Dossier</div>
                                    <div class="text-[10px] text-slate-500 font-medium uppercase">Nouvelle Saisie</div>
                                </div>
                            </Link>
                        </div>

                        <div class="flex items-center justify-center gap-4 pt-2">
                            <Link :href="route('secretaire.prescription.index')" class="text-[10px] font-black uppercase tracking-widest text-slate-400 hover:text-primary-600 transition-colors">
                                <em class="ni ni-list-check mr-1"></em> Retour à la liste
                            </Link>
                            <span class="h-1 w-1 rounded-full bg-slate-300"></span>
                            <button type="button" @click="goToStep('tubes')" class="text-[10px] font-black uppercase tracking-widest text-slate-400 hover:text-primary-600 transition-colors">
                                <em class="ni ni-scan mr-1"></em> Revoir les codes-barres
                            </button>
                        </div>
                    </div>

                    <div class="space-y-5">
                        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-800">
                            <div class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-3 flex items-center gap-2">
                                <em class="ni ni-user text-primary-500"></em> Détails Patient
                            </div>
                            <div class="text-sm font-bold text-slate-900 dark:text-white">{{ form.patient.nom }} {{ form.patient.prenom }}</div>
                            <div class="mt-1 text-[11px] font-medium text-slate-500">{{ form.age }} {{ form.unite_age }} • {{ form.patient.civilite }}</div>
                            
                            <div v-if="prescripteurName" class="mt-4 pt-4 border-t border-dashed border-slate-100 dark:border-slate-700">
                                <div class="text-[9px] font-bold uppercase text-slate-400 mb-1">Médecin</div>
                                <div class="text-xs font-bold text-primary-600 dark:text-primary-400">{{ prescripteurName }}</div>
                            </div>
                        </div>

                        <!-- Liste des Analyses -->
                        <div class="rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden dark:border-slate-700 dark:bg-slate-800">
                            <div class="bg-slate-50 px-4 py-3 border-b border-slate-100 dark:bg-slate-900/50 dark:border-slate-700 flex justify-between items-center">
                                <h3 class="text-[10px] font-black uppercase tracking-widest text-slate-500">Analyses ({{ selectedAnalyses.length }})</h3>
                                <span class="text-xs font-black text-primary-600">{{ formatCurrency(analysesSubtotal) }}</span>
                            </div>
                            <div class="max-h-[300px] overflow-y-auto px-2 py-2 scrollbar-thin scrollbar-thumb-slate-200 dark:scrollbar-thumb-slate-700">
                                <div class="space-y-1">
                                    <div v-for="analyse in selectedAnalyses" :key="analyse.id" class="flex items-center justify-between rounded-lg p-2.5 hover:bg-slate-50 dark:hover:bg-slate-700/30 transition-colors border border-transparent hover:border-slate-100 dark:hover:border-slate-600">
                                        <div class="min-w-0 mr-3">
                                            <div class="flex flex-col gap-0.5">
                                                <span class="font-mono text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-tight">{{ analyse.code }}</span>
                                                <div class="truncate text-xs font-bold text-slate-700 dark:text-slate-200">{{ analyse.designation }}</div>
                                            </div>
                                        </div>
                                        <div class="text-[11px] font-black text-slate-500 dark:text-slate-400 whitespace-nowrap tabular-nums">
                                            {{ (analyse.prix_effectif || analyse.prix) > 0 ? formatCurrency(analyse.prix_effectif || analyse.prix) : '-' }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="rounded-xl border border-slate-200 bg-slate-50/50 p-5 dark:border-slate-700 dark:bg-slate-800/50">
                            <div class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-4 flex justify-between">
                                <span>Résumé Final (Base de données)</span>
                                <span class="text-primary-600">{{ formatCurrency(prescription.totals_summary?.net_a_payer) }}</span>
                            </div>
                            <div class="space-y-2.5">
                                <div class="flex justify-between text-xs font-bold">
                                    <span class="text-slate-500">Total Brut</span>
                                    <span class="text-slate-900 dark:text-slate-100">{{ formatCurrency(prescription.totals_summary?.total_brut) }}</span>
                                </div>
                                <div v-if="prescription.totals_summary?.remise_amount > 0" class="flex justify-between text-xs font-bold text-red-500">
                                    <span>Remise</span>
                                    <span>-{{ formatCurrency(prescription.totals_summary?.remise_amount) }}</span>
                                </div>
                                <div class="pt-3 border-t border-slate-200 dark:border-slate-700 flex items-end justify-between">
                                    <span class="text-[10px] font-black uppercase text-slate-400">Net à payer</span>
                                    <span class="text-base font-black text-primary-600 leading-none">{{ formatCurrency(prescription.totals_summary?.net_a_payer) }}</span>
                                </div>
                                <div class="flex justify-between text-[10px] font-bold mt-2">
                                    <span class="text-slate-400">Statut Paiement</span>
                                    <span :class="prescription.totals_summary?.is_fully_paid ? 'text-emerald-500' : 'text-amber-500'">
                                        {{ prescription.totals_summary?.is_fully_paid ? 'Réglé' : 'Non soldé' }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

        </div>
    </AppLayout>
</template>

<script setup>
import { computed, ref, onMounted, watch } from 'vue';
import { Link, router, usePage, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Combobox from '@/Components/Combobox.vue';

const page = usePage();
const prescriptionAction = computed(() => {
    if (page.props.flash?.prescription_action) return page.props.flash.prescription_action;
    if (props.wasRecentlyCreated) return 'created';
    return 'updated';
});

const props = defineProps({
    prescription: { type: Object, required: true },
    wasRecentlyCreated: { type: Boolean, default: false },
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

// Helper to get initial step from URL
const getInitialStep = () => {
    const urlParams = new URLSearchParams(window.location.search);
    const step = urlParams.get('step');
    return (step && steps.some(s => s.key === step)) ? step : 'patient';
};

const currentStep = ref(getInitialStep());

// FIX: Robust reactive step synchronization for Inertia redirects
const syncStepFromUrl = () => {
    try {
        // usePage().url includes the full path + query string
        const url = new URL(page.url, window.location.origin);
        const step = url.searchParams.get('step');
        if (step && steps.some(s => s.key === step)) {
            currentStep.value = step;
        }
    } catch (e) {
        // Fallback to manual check if URL object fails
        const urlParams = new URLSearchParams(window.location.search);
        const step = urlParams.get('step');
        if (step && steps.some(s => s.key === step)) {
            currentStep.value = step;
        }
    }
};

watch(() => page.url, () => {
    syncStepFromUrl();
});

const form = useForm({
    patient_id: props.prescription.patient_id,
    patient: {
        nom: props.prescription.patient.nom || '',
        prenom: props.prescription.patient.prenom || '',
        civilite: props.prescription.patient.civilite || props.civilites[0],
        date_naissance: props.prescription.patient.date_naissance || '',
        telephone: props.prescription.patient.telephone || '',
        email: props.prescription.patient.email || '',
        adresse: props.prescription.patient.adresse || '',
    },
    prescripteur_id: props.prescription.prescripteur_id || '',
    patient_type: props.prescription.patient_type || 'EXTERNE',
    age: Number(props.prescription.age || 0),
    unite_age: props.prescription.unite_age || 'Ans',
    poids: props.prescription.poids || '',
    renseignement_clinique: props.prescription.renseignement_clinique || '',
    labo_traitement: props.prescription.labo_traitement || 'LOCAL',
    labo_autre_nom: props.prescription.labo_autre_nom || '',
    analyse_ids: props.prescription.analyses?.map(a => a.id) || [],
    prelevements: props.prescription.prelevements?.map(p => ({ 
        id: p.id, 
        quantite: p.pivot?.quantite || 1 
    })) || [],
    payment_method: props.prescription.paiements?.[0]?.payment_method?.code || props.paymentMethods[0]?.code || '',
    remise: props.prescription.remise_valeur || props.prescription.remise || 0,
    remise_type: props.prescription.remise_type || 'PERCENT',
    paiement_statut: !!props.prescription.paiements?.[0]?.status,
});

const analyseSearch = ref('');
const analyseResults = ref([]);
const selectedAnalyses = ref([]);
let analysesSearchTimer = null;

const prelevementSearch = ref('');
const prelevementResults = ref([]);
const selectedPrelevements = ref([]);
let prelevementsSearchTimer = null;

onMounted(() => {
    if (props.prescription.analyses?.length > 0) {
        selectedAnalyses.value = props.prescription.analyses.map(a => ({
            id: a.id,
            code: a.code,
            designation: a.designation,
            prix: a.prix,
            prix_effectif: a.pivot?.prix || a.prix,
            parent_id: a.parent_id,
            parent: a.parent,
            level: a.level
        }));
    }

    if (props.prescription.prelevements?.length > 0) {
        selectedPrelevements.value = props.prescription.prelevements.map(p => ({
            id: p.id,
            denomination: p.denomination,
            prix: p.prix,
            prix_promotion: p.prix_promotion,
            quantite: p.pivot?.quantite || 1,
        }));
    }
});

const nowLabel = computed(() => new Date().toLocaleString('fr-FR'));

const prescripteurName = computed(() => {
    const p = props.prescripteurs.find(item => item.id === form.prescripteur_id);
    return p ? p.nom_complet : 'Non spécifié';
});

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
    if (form.patient_type === 'URGENCE-JOUR') {
        return Number(props.urgenceFees.jour || 0);
    }

    if (form.patient_type === 'URGENCE-NUIT') {
        return Number(props.urgenceFees.nuit || 0);
    }

    return 0;
});

const remiseAmount = computed(() => {
    const val = Math.max(0, Number(form.remise || 0));
    const servicesTotal = analysesSubtotal.value + prelevementsSubtotal.value;

    if (form.remise_type === 'PERCENT') {
        return servicesTotal * (val / 100);
    } else {
        return val;
    }
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
    const date = form.patient.date_naissance;
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
        form.age = days;
        form.unite_age = 'Jours';

        return;
    }

    if (months < 24) {
        form.age = months;
        form.unite_age = 'Mois';

        return;
    }

    form.age = years;
    form.unite_age = 'Ans';
};

const goToStep = async (stepKey) => {
    if (stepKey === 'clinique' && !form.patient.nom.trim()) {
        return;
    }

    if (stepKey === 'analyses' && !form.prescripteur_id) {
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
    
    analyseSearch.value = '';
    analyseResults.value = [];
    selectedAnalyses.value = [];
    prelevementSearch.value = '';
    prelevementResults.value = [];
    selectedPrelevements.value = [];

    form.reset();
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
    if (!form.patient.nom.trim() || !form.prescripteur_id || selectedAnalyses.value.length === 0 || !form.payment_method) {
        return;
    }

    form.analyse_ids = selectedAnalyses.value.map(a => a.id);
    form.prelevements = selectedPrelevements.value.map(p => ({ id: p.id, quantite: p.quantite }));
    form.remise_type = form.remise_type || 'PERCENT';

    form.put(route('secretaire.prescription.update', props.prescription.id), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            // FIX: Force navigation to the final step to avoid returning to step 1
            currentStep.value = 'confirmation';
            console.log('Update success, forced confirmation step.');
        },
        onError: (errors) => {
            console.error('Validation errors:', errors);
        }
    });
};

</script>
