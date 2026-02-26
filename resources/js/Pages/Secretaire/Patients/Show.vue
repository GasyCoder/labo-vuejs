<template>
<AppLayout>
    <!-- Header patient -->
    <div class="container mx-auto px-4 py-4 sm:px-6 lg:px-8">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <!-- Infos patient -->
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-primary-400 to-primary-600 flex items-center justify-center shadow-sm">
                    <span class="text-white font-bold text-sm">
                        {{ getInitials(patient) }}
                    </span>
                </div>
                <div class="min-w-0">
                    <h1 class="text-xl font-bold text-slate-900 dark:text-white truncate">
                        {{ patient.civilite }} {{ patient.nom }}{{ patient.prenom ? ' ' + patient.prenom : '' }}
                    </h1>
                    <div class="flex items-center gap-2 mt-0.5">
                        <span class="inline-flex items-center px-2 py-0.5 bg-primary-50 dark:bg-primary-900/30 text-primary-700 dark:text-primary-300 text-xs font-semibold rounded-md">
                            {{ patient.numero_dossier }}
                        </span>
                        <span v-if="patient.telephone" class="text-xs text-slate-400">{{ patient.telephone }}</span>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex items-center gap-2">
                <Link :href="route('secretaire.patients.index')"
                   class="inline-flex items-center gap-1.5 px-3 py-2 text-sm font-medium text-slate-600 dark:text-slate-300 bg-white dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-600 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/>
                    </svg>
                    Retour
                </Link>

                <button @click="showDeleteConfirm = true"
                   class="inline-flex items-center gap-1.5 px-3 py-2 text-sm font-medium text-red-600 dark:text-red-400 bg-white dark:bg-slate-700 border border-slate-200 dark:border-slate-600 hover:bg-slate-50 dark:hover:bg-slate-600 rounded-lg transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/>
                    </svg>
                    Supprimer
                </button>

                <button @click="editMode = true"
                   class="inline-flex items-center gap-1.5 px-3 py-2 text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 rounded-lg transition-colors shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10"/>
                    </svg>
                    Modifier
                </button>
            </div>
        </div>
    </div>

    <div class="container mx-auto px-4 py-6 sm:px-6 lg:px-8">
        <!-- Flash -->
        <div v-if="$page.props.flash.success" class="mb-4 p-3 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-700 rounded-lg">
            <p class="text-sm text-green-700 dark:text-green-300">{{ $page.props.flash.success }}</p>
        </div>

        <!-- Stats compactes -->
        <div class="grid grid-cols-3 gap-3 mb-6">
            <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-lg bg-blue-50 dark:bg-blue-900/30 flex items-center justify-center">
                        <svg class="w-4.5 h-4.5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-xl font-bold text-slate-900 dark:text-white leading-none">{{ totalAnalyses }}</p>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Prescriptions</p>
                    </div>
                </div>
            </div>
            <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-lg bg-green-50 dark:bg-green-900/30 flex items-center justify-center">
                        <svg class="w-4.5 h-4.5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-xl font-bold text-slate-900 dark:text-white leading-none">{{ totalPaiements }}</p>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Paiements</p>
                    </div>
                </div>
            </div>
            <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-lg bg-purple-50 dark:bg-purple-900/30 flex items-center justify-center">
                        <svg class="w-4.5 h-4.5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-xl font-bold text-slate-900 dark:text-white leading-none">{{ formatNumber(montantTotal) }}</p>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Ar total</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabs -->
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 mb-6">
            <nav class="flex px-4 border-b border-slate-100 dark:border-slate-700" aria-label="Tabs">
                <button @click="activeTab = 'infos'"
                        :class="['relative py-3 px-4 text-sm font-medium transition-colors', activeTab === 'infos' ? 'text-primary-600 dark:text-primary-400' : 'text-slate-500 dark:text-slate-400 hover:text-slate-700']">
                    <span class="flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/>
                        </svg>
                        Informations
                    </span>
                    <span v-if="activeTab === 'infos'" class="absolute bottom-0 left-0 right-0 h-0.5 bg-primary-600 dark:bg-primary-400 rounded-full"></span>
                </button>

                <button @click="activeTab = 'analyses'"
                        :class="['relative py-3 px-4 text-sm font-medium transition-colors', activeTab === 'analyses' ? 'text-primary-600 dark:text-primary-400' : 'text-slate-500 dark:text-slate-400 hover:text-slate-700']">
                    <span class="flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25z"/>
                        </svg>
                        Prescriptions
                        <span v-if="totalAnalyses > 0" :class="['inline-flex items-center justify-center min-w-[18px] h-5 px-1.5 rounded-full text-xs font-semibold', activeTab === 'analyses' ? 'bg-primary-100 dark:bg-primary-900/30 text-primary-700 dark:text-primary-300' : 'bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-400']">
                            {{ totalAnalyses }}
                        </span>
                    </span>
                    <span v-if="activeTab === 'analyses'" class="absolute bottom-0 left-0 right-0 h-0.5 bg-primary-600 dark:bg-primary-400 rounded-full"></span>
                </button>

                <button @click="activeTab = 'paiements'"
                        :class="['relative py-3 px-4 text-sm font-medium transition-colors', activeTab === 'paiements' ? 'text-primary-600 dark:text-primary-400' : 'text-slate-500 dark:text-slate-400 hover:text-slate-700']">
                    <span class="flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z"/>
                        </svg>
                        Paiements
                        <span v-if="totalPaiements > 0" :class="['inline-flex items-center justify-center min-w-[18px] h-5 px-1.5 rounded-full text-xs font-semibold', activeTab === 'paiements' ? 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300' : 'bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-400']">
                            {{ totalPaiements }}
                        </span>
                    </span>
                    <span v-if="activeTab === 'paiements'" class="absolute bottom-0 left-0 right-0 h-0.5 bg-primary-600 dark:bg-primary-400 rounded-full"></span>
                </button>
            </nav>
        </div>

        <!-- Tab: Infos -->
        <div v-if="activeTab === 'infos'" class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700">
            <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-700 flex items-center justify-between">
                <h3 class="text-base font-semibold text-slate-900 dark:text-white">Informations personnelles</h3>
                <button v-if="!editMode" @click="editMode = true"
                   class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-primary-600 dark:text-primary-400 bg-primary-50 dark:bg-primary-900/20 rounded-lg hover:bg-primary-100 dark:hover:bg-primary-900/30 transition-colors">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z"/>
                    </svg>
                    Modifier
                </button>
            </div>

            <div class="p-6">
                <!-- Mode édition -->
                <form v-if="editMode" @submit.prevent="submitEdit" class="space-y-5">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Nom *</label>
                            <input type="text" v-model="editForm.nom"
                                   class="w-full px-3 py-2.5 text-sm border border-slate-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500">
                            <span v-if="editForm.errors.nom" class="text-xs text-red-500 mt-1">{{ editForm.errors.nom }}</span>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Prénom</label>
                            <input type="text" v-model="editForm.prenom"
                                   class="w-full px-3 py-2.5 text-sm border border-slate-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500">
                            <span v-if="editForm.errors.prenom" class="text-xs text-red-500 mt-1">{{ editForm.errors.prenom }}</span>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Civilité *</label>
                            <select v-model="editForm.civilite"
                                    class="w-full px-3 py-2.5 text-sm border border-slate-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500">
                                <option value="Monsieur">Monsieur</option>
                                <option value="Madame">Madame</option>
                                <option value="Mademoiselle">Mademoiselle</option>
                                <option value="Enfant-garçon">Enfant-garçon</option>
                                <option value="Enfant-fille">Enfant-fille</option>
                            </select>
                            <span v-if="editForm.errors.civilite" class="text-xs text-red-500 mt-1">{{ editForm.errors.civilite }}</span>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Date de naissance</label>
                            <input type="date" v-model="editForm.date_naissance"
                                   class="w-full px-3 py-2.5 text-sm border border-slate-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500">
                            <span v-if="editForm.errors.date_naissance" class="text-xs text-red-500 mt-1">{{ editForm.errors.date_naissance }}</span>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Téléphone</label>
                            <input type="text" v-model="editForm.telephone"
                                   class="w-full px-3 py-2.5 text-sm border border-slate-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500">
                            <span v-if="editForm.errors.telephone" class="text-xs text-red-500 mt-1">{{ editForm.errors.telephone }}</span>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Email</label>
                            <input type="email" v-model="editForm.email"
                                   class="w-full px-3 py-2.5 text-sm border border-slate-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500">
                            <span v-if="editForm.errors.email" class="text-xs text-red-500 mt-1">{{ editForm.errors.email }}</span>
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Adresse</label>
                            <input type="text" v-model="editForm.adresse"
                                   class="w-full px-3 py-2.5 text-sm border border-slate-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500">
                            <span v-if="editForm.errors.adresse" class="text-xs text-red-500 mt-1">{{ editForm.errors.adresse }}</span>
                        </div>
                    </div>
                    <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100 dark:border-slate-700">
                        <button type="button" @click="cancelEdit"
                           class="px-4 py-2 text-sm font-medium text-slate-600 dark:text-slate-300 bg-slate-100 dark:bg-slate-700 rounded-lg hover:bg-slate-200 dark:hover:bg-slate-600 transition-colors">
                            Annuler
                        </button>
                        <button type="submit" :disabled="editForm.processing"
                                class="px-4 py-2 text-sm font-medium text-white bg-primary-600 rounded-lg hover:bg-primary-700 transition-colors">
                            Enregistrer
                        </button>
                    </div>
                </form>

                <!-- Mode lecture -->
                <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-xs font-medium text-slate-400 dark:text-slate-500 mb-1 uppercase tracking-wider">Nom complet</label>
                            <p class="text-sm font-medium text-slate-900 dark:text-white">{{ patient.nom }}{{ patient.prenom ? ' ' + patient.prenom : '' }}</p>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-400 dark:text-slate-500 mb-1 uppercase tracking-wider">Civilité</label>
                            <p class="text-sm text-slate-900 dark:text-white">{{ patient.civilite }}</p>
                        </div>
                        <div v-if="patient.date_naissance_formatee">
                            <label class="block text-xs font-medium text-slate-400 dark:text-slate-500 mb-1 uppercase tracking-wider">Date de naissance</label>
                            <p class="text-sm text-slate-900 dark:text-white">{{ patient.date_naissance_formatee }}</p>
                        </div>
                        <div v-if="patient.adresse">
                            <label class="block text-xs font-medium text-slate-400 dark:text-slate-500 mb-1 uppercase tracking-wider">Adresse</label>
                            <p class="text-sm text-slate-900 dark:text-white">{{ patient.adresse }}</p>
                        </div>
                    </div>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-xs font-medium text-slate-400 dark:text-slate-500 mb-1 uppercase tracking-wider">Téléphone</label>
                            <a v-if="patient.telephone" :href="'tel:' + patient.telephone" class="text-sm text-primary-600 dark:text-primary-400 hover:underline">{{ patient.telephone }}</a>
                            <span v-else class="text-sm text-slate-400 italic">Non renseigné</span>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-400 dark:text-slate-500 mb-1 uppercase tracking-wider">Email</label>
                            <a v-if="patient.email" :href="'mailto:' + patient.email" class="text-sm text-primary-600 dark:text-primary-400 hover:underline">{{ patient.email }}</a>
                            <span v-else class="text-sm text-slate-400 italic">Non renseigné</span>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-400 dark:text-slate-500 mb-1 uppercase tracking-wider">Dossier</label>
                            <p class="text-sm text-slate-900 dark:text-white font-mono">{{ patient.numero_dossier }}</p>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-400 dark:text-slate-500 mb-1 uppercase tracking-wider">Enregistré le</label>
                            <p class="text-sm text-slate-900 dark:text-white">{{ patient.created_at_formatted }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tab: Prescriptions -->
        <div v-if="activeTab === 'analyses'" class="bg-white/95 dark:bg-gray-800/95 backdrop-blur-sm rounded-2xl shadow-xl shadow-gray-200/50 dark:shadow-gray-900/50 border border-gray-100 dark:border-gray-700/50 overflow-hidden">
            <!-- Header -->
            <div class="relative px-6 py-4 bg-gradient-to-br from-slate-50 via-white to-blue-50/30 dark:from-gray-800 dark:via-gray-800 dark:to-blue-900/20 border-b border-gray-100/50 dark:border-gray-700/50">
                <div class="relative flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                    <div class="flex items-center space-x-3">
                        <div class="p-2.5 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-lg shadow-lg shadow-blue-500/25">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold bg-gradient-to-r from-gray-900 to-gray-700 dark:from-white dark:to-gray-200 bg-clip-text text-transparent">Prescriptions</h3>
                            <p v-if="searchPrescriptions" class="text-xs text-gray-600 dark:text-gray-400 mt-1">
                                <span class="inline-flex items-center px-2 py-0.5 bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300 rounded-full text-xs font-medium">
                                    {{ filteredPrescriptions.length }} résultat{{ filteredPrescriptions.length > 1 ? 's' : '' }}
                                </span>
                            </p>
                        </div>
                    </div>
                    <!-- Recherche -->
                    <div class="relative max-w-xs w-full">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center">
                            <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                        </div>
                        <input type="text" v-model="searchPrescriptions" placeholder="Rechercher..."
                            class="block w-full pl-10 pr-10 py-2 text-sm bg-white/80 dark:bg-gray-700/80 backdrop-blur-sm border border-gray-200/50 dark:border-gray-600/50 rounded-lg text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500/50 transition-all duration-200 shadow-sm">
                        <button v-if="searchPrescriptions" @click="searchPrescriptions = ''; filtreStatut = ''" class="absolute inset-y-0 right-0 pr-3 flex items-center">
                            <svg class="h-3.5 w-3.5 text-gray-400 hover:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Filtres -->
                <div v-if="!searchPrescriptions" class="flex flex-wrap gap-2 mt-4">
                    <button @click="filtreStatut = ''"
                        :class="['group inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-semibold transition-all duration-200', !filtreStatut ? 'bg-gradient-to-r from-blue-500 to-indigo-600 text-white shadow-md shadow-blue-500/20' : 'bg-white/80 dark:bg-gray-700/80 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 shadow-sm border border-gray-200/50 dark:border-gray-600/50']">
                        <span :class="['w-1.5 h-1.5 rounded-full mr-1.5', !filtreStatut ? 'bg-white/80' : 'bg-blue-500']"></span>
                        Toutes
                        <span :class="['ml-1.5 px-1.5 py-0.5 text-[0.65rem] rounded-full', !filtreStatut ? 'bg-white/20' : 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300']">{{ prescriptions.length }}</span>
                    </button>
                    <button @click="filtreStatut = 'EN_ATTENTE'"
                        :class="['group inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-semibold transition-all duration-200', filtreStatut === 'EN_ATTENTE' ? 'bg-gradient-to-r from-amber-400 to-orange-500 text-white shadow-md' : 'bg-white/80 dark:bg-gray-700/80 text-gray-700 dark:text-gray-300 hover:bg-amber-50 dark:hover:bg-amber-900/20 shadow-sm border border-gray-200/50 dark:border-gray-600/50']">
                        <span :class="['w-1.5 h-1.5 rounded-full mr-1.5 animate-pulse', filtreStatut === 'EN_ATTENTE' ? 'bg-white/80' : 'bg-amber-400']"></span>
                        En attente
                        <span :class="['ml-1.5 px-1.5 py-0.5 text-[0.65rem] rounded-full', filtreStatut === 'EN_ATTENTE' ? 'bg-white/20' : 'bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300']">{{ prescriptionsEnAttente }}</span>
                    </button>
                    <button @click="filtreStatut = 'EN_COURS'"
                        :class="['group inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-semibold transition-all duration-200', filtreStatut === 'EN_COURS' ? 'bg-gradient-to-r from-blue-500 to-cyan-500 text-white shadow-md' : 'bg-white/80 dark:bg-gray-700/80 text-gray-700 dark:text-gray-300 hover:bg-blue-50 dark:hover:bg-blue-900/20 shadow-sm border border-gray-200/50 dark:border-gray-600/50']">
                        <span :class="['w-1.5 h-1.5 rounded-full mr-1.5', filtreStatut === 'EN_COURS' ? 'bg-white/80' : 'bg-blue-500']"></span>
                        En cours
                        <span :class="['ml-1.5 px-1.5 py-0.5 text-[0.65rem] rounded-full', filtreStatut === 'EN_COURS' ? 'bg-white/20' : 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300']">{{ prescriptionsEnCours }}</span>
                    </button>
                    <button @click="filtreStatut = 'TERMINE'"
                        :class="['group inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-semibold transition-all duration-200', filtreStatut === 'TERMINE' ? 'bg-gradient-to-r from-emerald-500 to-green-600 text-white shadow-md' : 'bg-white/80 dark:bg-gray-700/80 text-gray-700 dark:text-gray-300 hover:bg-emerald-50 dark:hover:bg-emerald-900/20 shadow-sm border border-gray-200/50 dark:border-gray-600/50']">
                        <span :class="['w-1.5 h-1.5 rounded-full mr-1.5', filtreStatut === 'TERMINE' ? 'bg-white/80' : 'bg-emerald-500']"></span>
                        Terminées
                        <span :class="['ml-1.5 px-1.5 py-0.5 text-[0.65rem] rounded-full', filtreStatut === 'TERMINE' ? 'bg-white/20' : 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300']">{{ prescriptionsTerminees }}</span>
                    </button>
                </div>
            </div>

            <!-- Prescription cards -->
            <div class="p-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <template v-if="filteredPrescriptions.length > 0">
                    <div v-for="prescription in filteredPrescriptions" :key="prescription.id"
                         :class="['group relative bg-white/60 dark:bg-gray-800/60 backdrop-blur-sm rounded-xl border border-gray-100/50 dark:border-gray-700/50 shadow-lg hover:shadow-xl transition-all duration-300 hover:scale-[1.02] hover:-translate-y-0.5',
                                  isRecent(prescription.created_at) ? 'ring-1 ring-blue-200/50 dark:ring-blue-800/50' : '']">
                        <div v-if="isRecent(prescription.created_at)" class="absolute -top-2 -right-2 z-10">
                            <div class="px-2 py-1 bg-gradient-to-r from-blue-500 to-indigo-600 text-white text-xs font-bold rounded-full shadow-lg animate-pulse">
                                <span class="flex items-center space-x-1"><span class="w-1.5 h-1.5 bg-white rounded-full"></span><span>Récente</span></span>
                            </div>
                        </div>
                        <div class="p-4">
                            <div class="flex items-start justify-between mb-3">
                                <div>
                                    <h4 class="text-sm font-bold text-gray-900 dark:text-white truncate">{{ prescription.reference }}</h4>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ formatDate(prescription.created_at) }}</p>
                                </div>
                                <span :class="getStatusClasses(prescription.status)">
                                    <span :class="getStatusDotClass(prescription.status)"></span>
                                    {{ prescription.status_label }}
                                </span>
                            </div>
                            <div class="flex items-center justify-between text-xs mb-3">
                                <span class="text-gray-600 dark:text-gray-400 font-medium">Dr. {{ prescription.prescripteur?.nom ?? 'Non spécifié' }}</span>
                                <span class="inline-flex items-center px-2 py-0.5 bg-blue-100/80 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300 rounded-full">
                                    {{ prescription.analyses.length }} analyse{{ prescription.analyses.length > 1 ? 's' : '' }}
                                </span>
                            </div>
                            <div class="space-y-2 mb-3">
                                <div v-for="analyse in prescription.analyses.slice(0, 2)" :key="analyse.id" class="flex items-center space-x-2">
                                    <div :class="['flex-shrink-0 w-2 h-2 rounded-full', prescription.status === 'TERMINE' ? 'bg-emerald-500' : prescription.status === 'EN_COURS' ? 'bg-blue-500' : 'bg-amber-400']"></div>
                                    <p class="text-xs font-medium text-gray-700 dark:text-gray-300 truncate">{{ analyse.designation }}</p>
                                </div>
                                <div v-if="prescription.analyses.length > 2" class="text-xs text-gray-500 dark:text-gray-400 font-medium">
                                    +{{ prescription.analyses.length - 2 }} autres analyses...
                                </div>
                            </div>
                            <div class="flex items-center justify-between pt-3 border-t border-gray-100/50 dark:border-gray-600/50">
                                <span v-if="prescription.montant_total > 0" class="text-sm font-bold text-gray-900 dark:text-white">
                                    {{ formatNumber(prescription.montant_total) }} Ar
                                </span>
                                <a v-if="prescription.status === 'VALIDE'" :href="route('laboratoire.prescription.pdf', prescription.id)" target="_blank"
                                   class="text-xs font-semibold text-blue-600 dark:text-blue-400 hover:text-blue-700 flex items-center">
                                    Résultats
                                    <svg class="w-3 h-3 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                </a>
                            </div>
                        </div>
                    </div>
                </template>
                <template v-else>
                    <div class="col-span-full py-12 text-center">
                        <div class="max-w-xs mx-auto">
                            <div class="w-16 h-16 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mx-auto mb-4">
                                <svg class="w-6 h-6 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                </svg>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">{{ searchPrescriptions ? 'Aucun résultat' : 'Aucune prescription' }}</h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">{{ searchPrescriptions ? 'Aucune correspondance pour "' + searchPrescriptions + '"' : "Ce patient n'a pas encore de prescriptions" }}</p>
                            <button v-if="searchPrescriptions" @click="searchPrescriptions = ''; filtreStatut = ''" class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-blue-500 to-indigo-600 text-white text-xs font-semibold rounded-lg shadow-md">Effacer la recherche</button>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <!-- Tab: Paiements -->
        <div v-if="activeTab === 'paiements'" class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Historique des Paiements</h3>
                    <div class="text-sm font-medium text-gray-900 dark:text-white">Total: {{ formatNumber(montantTotal) }} Ar</div>
                </div>
            </div>
            <div class="max-h-96 overflow-y-auto">
                <template v-if="allPaiements.length > 0">
                    <div v-for="paiement in allPaiements" :key="paiement.id" class="p-6 border-b border-gray-200 dark:border-gray-700 last:border-b-0">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-4">
                                <div class="w-10 h-10 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center">
                                    <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                    </svg>
                                </div>
                                <div>
                                    <div class="font-semibold text-gray-900 dark:text-white">{{ formatNumber(paiement.montant) }} Ar</div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400">
                                        {{ formatDateTime(paiement.created_at) }} • {{ paiement.payment_method?.label ?? 'N/A' }}
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center space-x-3">
                                <span class="inline-flex items-center px-2 py-1 bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-300 text-xs font-medium rounded">
                                    Ref #{{ paiement.prescription_reference ?? paiement.prescription_id }}
                                </span>
                                <a :href="route('secretaire.prescription.facture', paiement.prescription_id)" target="_blank"
                                   class="inline-flex items-center px-2 py-1 text-xs font-medium text-blue-600 dark:text-blue-400 bg-blue-100 dark:bg-blue-900/30 hover:bg-blue-200 dark:hover:bg-blue-900/50 rounded transition-colors" title="Voir la facture">
                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                    </svg>
                                    Facture
                                </a>
                                <button v-if="patient.email && $page.props.enabledFeatures?.patient_invoice_email !== false" @click="sendInvoiceByEmail(paiement.prescription_id)"
                                   :disabled="sendingInvoiceId === paiement.prescription_id"
                                   class="inline-flex items-center px-2 py-1 text-xs font-medium text-primary-600 dark:text-primary-400 bg-primary-100 dark:bg-primary-900/30 hover:bg-primary-200 dark:hover:bg-primary-900/50 rounded transition-colors disabled:opacity-50" title="Envoyer par email">
                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                    </svg>
                                    {{ sendingInvoiceId === paiement.prescription_id ? 'Envoi...' : 'Email' }}
                                </button>
                            </div>
                        </div>
                    </div>
                </template>
                <template v-else>
                    <div class="p-12 text-center">
                        <div class="w-16 h-16 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                            </svg>
                        </div>
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">Aucun paiement</h3>
                        <p class="text-gray-500 dark:text-gray-400">Ce patient n'a encore effectué aucun paiement.</p>
                    </div>
                </template>
            </div>
        </div>
    </div>
    <!-- Delete confirmation -->
    <Teleport to="body">
        <div v-if="showDeleteConfirm" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="fixed inset-0 bg-black/50" @click="showDeleteConfirm = false"></div>
            <div class="relative bg-white dark:bg-slate-800 rounded-xl shadow-xl max-w-sm w-full p-6">
                <div class="flex flex-col items-center text-center">
                    <div class="w-12 h-12 rounded-full bg-red-100 dark:bg-red-900/30 flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-2">Supprimer ce patient ?</h3>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mb-6">Cette action supprimera le patient <strong>{{ patient.nom }} {{ patient.prenom }}</strong> et toutes ses prescriptions associées. Cette action est irréversible.</p>
                    <div class="flex items-center gap-3 w-full">
                        <button @click="showDeleteConfirm = false"
                           class="flex-1 px-4 py-2 text-sm font-medium text-slate-600 dark:text-slate-300 bg-slate-100 dark:bg-slate-700 rounded-lg hover:bg-slate-200 dark:hover:bg-slate-600 transition-colors">
                            Annuler
                        </button>
                        <button @click="handleDelete" :disabled="isDeleting"
                           class="flex-1 px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 transition-colors disabled:opacity-50">
                            {{ isDeleting ? 'Suppression...' : 'Supprimer' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </Teleport>

    <!-- Invoice confirmation -->
    <Teleport to="body">
        <div v-if="showInvoiceConfirm" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="fixed inset-0 bg-black/50" @click="showInvoiceConfirm = false"></div>
            <div class="relative bg-white dark:bg-slate-800 rounded-xl shadow-xl max-w-sm w-full p-6">
                <div class="flex flex-col items-center text-center">
                    <div class="w-12 h-12 rounded-full bg-primary-100 dark:bg-primary-900/30 flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-primary-600 dark:text-primary-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-2">Envoyer la facture par email ?</h3>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mb-6">La facture sera envoyée à l'adresse <strong>{{ patient.email }}</strong>.</p>
                    <div class="flex items-center gap-3 w-full">
                        <button @click="showInvoiceConfirm = false"
                           class="flex-1 px-4 py-2 text-sm font-medium text-slate-600 dark:text-slate-300 bg-slate-100 dark:bg-slate-700 rounded-lg hover:bg-slate-200 dark:hover:bg-slate-600 transition-colors">
                            Annuler
                        </button>
                        <button @click="confirmSendInvoice" :disabled="sendingInvoiceId === invoicePrescriptionId"
                           class="flex-1 px-4 py-2 text-sm font-medium text-white bg-primary-600 rounded-lg hover:bg-primary-700 transition-colors disabled:opacity-50">
                            {{ sendingInvoiceId === invoicePrescriptionId ? 'Envoi...' : 'Oui, envoyer' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </Teleport>
</AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useForm, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    patient: Object,
    prescriptions: Array,
    totalAnalyses: Number,
    totalPaiements: Number,
    montantTotal: Number,
    prescriptionsEnAttente: Number,
    prescriptionsEnCours: Number,
    prescriptionsTerminees: Number,
});

const activeTab = ref('infos');
const editMode = ref(false);
const searchPrescriptions = ref('');
const filtreStatut = ref('');
const showDeleteConfirm = ref(false);
const isDeleting = ref(false);
const sendingInvoiceId = ref(null);
const showInvoiceConfirm = ref(false);
const invoicePrescriptionId = ref(null);

const editForm = useForm({
    nom: props.patient.nom,
    prenom: props.patient.prenom,
    civilite: props.patient.civilite,
    date_naissance: props.patient.date_naissance || '',
    telephone: props.patient.telephone || '',
    email: props.patient.email || '',
    adresse: props.patient.adresse || '',
});

const getInitials = (patient) => {
    const f = (patient.nom || '').charAt(0).toUpperCase();
    const l = (patient.prenom || 'X').charAt(0).toUpperCase();
    return `${f}${l}`;
};

const formatNumber = (num) => {
    if (num === null || num === undefined) return '0';
    return Number(num).toLocaleString('fr-FR');
};

const formatDate = (dateString) => {
    if (!dateString) return '';
    return new Date(dateString).toLocaleDateString('fr-FR');
};

const formatDateTime = (dateString) => {
    if (!dateString) return '';
    const d = new Date(dateString);
    return d.toLocaleDateString('fr-FR') + ' à ' + d.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
};

const isRecent = (dateString) => {
    if (!dateString) return false;
    const d = new Date(dateString);
    const thirtyDaysAgo = new Date();
    thirtyDaysAgo.setDate(thirtyDaysAgo.getDate() - 30);
    return d > thirtyDaysAgo;
};

const getStatusClasses = (status) => {
    const base = 'inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold';
    switch (status) {
        case 'EN_ATTENTE': return `${base} bg-gradient-to-r from-amber-100 to-yellow-100 dark:from-amber-900/30 dark:to-yellow-900/30 text-amber-800 dark:text-amber-300`;
        case 'EN_COURS': return `${base} bg-gradient-to-r from-blue-100 to-cyan-100 dark:from-blue-900/30 dark:to-cyan-900/30 text-blue-800 dark:text-blue-300`;
        case 'TERMINE': return `${base} bg-gradient-to-r from-emerald-100 to-green-100 dark:from-emerald-900/30 dark:to-green-900/30 text-emerald-800 dark:text-emerald-300`;
        default: return `${base} bg-gradient-to-r from-gray-100 to-slate-100 dark:from-gray-800 dark:to-slate-800 text-gray-800 dark:text-gray-300`;
    }
};

const getStatusDotClass = (status) => {
    const base = 'w-1.5 h-1.5 rounded-full mr-1';
    switch (status) {
        case 'EN_ATTENTE': return `${base} bg-amber-400 animate-pulse`;
        case 'EN_COURS': return `${base} bg-blue-400`;
        case 'TERMINE': return `${base} bg-emerald-400`;
        default: return `${base} bg-gray-400`;
    }
};

const filteredPrescriptions = computed(() => {
    let result = props.prescriptions || [];
    if (filtreStatut.value) {
        result = result.filter(p => p.status === filtreStatut.value);
    }
    if (searchPrescriptions.value) {
        const term = searchPrescriptions.value.toLowerCase();
        result = result.filter(p =>
            p.reference?.toLowerCase().includes(term) ||
            p.prescripteur?.nom?.toLowerCase().includes(term) ||
            p.analyses?.some(a => a.designation?.toLowerCase().includes(term) || a.code?.toLowerCase().includes(term))
        );
    }
    return result;
});

const allPaiements = computed(() => {
    if (!props.prescriptions) return [];
    return props.prescriptions
        .flatMap(p => p.paiements || [])
        .sort((a, b) => new Date(b.created_at) - new Date(a.created_at));
});

const submitEdit = () => {
    editForm.put(route('secretaire.patient.update', props.patient.id), {
        preserveScroll: true,
        onSuccess: () => { editMode.value = false; },
    });
};

const cancelEdit = () => {
    editMode.value = false;
    editForm.reset();
    editForm.clearErrors();
};

const handleDelete = () => {
    isDeleting.value = true;
    router.delete(route('secretaire.patient.destroy', props.patient.id), {
        onFinish: () => { isDeleting.value = false; showDeleteConfirm.value = false; },
    });
};

const sendInvoiceByEmail = (prescriptionId) => {
    invoicePrescriptionId.value = prescriptionId;
    showInvoiceConfirm.value = true;
};

const confirmSendInvoice = () => {
    if (!invoicePrescriptionId.value) return;

    sendingInvoiceId.value = invoicePrescriptionId.value;
    router.post(route('secretaire.patient.send-invoice', invoicePrescriptionId.value), {}, {
        preserveScroll: true,
        onFinish: () => { 
            sendingInvoiceId.value = null; 
            showInvoiceConfirm.value = false;
        },
    });
};
</script>
