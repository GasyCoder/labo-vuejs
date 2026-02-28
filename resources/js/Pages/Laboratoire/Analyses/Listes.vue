<template>
<AppLayout>
    <div class="p-6">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6 mb-8">
            <div>
                <h1 class="text-3xl font-heading font-bold text-gray-900 dark:text-white">Analyses</h1>
                <p class="text-gray-600 dark:text-gray-400 mt-2">Gérez le catalogue des analyses</p>
            </div>
        </div>

        <!-- Tabs + counts -->
        <div class="flex flex-wrap gap-2 mb-6">
            <button v-for="tab in levelTabs" :key="tab.key" @click="filterLevel(tab.key)"
                :class="[ filters.selectedLevel === tab.key ? 'bg-blue-600 text-white' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border border-gray-300 dark:border-gray-600', 'px-4 py-2 rounded-lg text-sm font-medium transition-colors' ]">
                {{ tab.label }} ({{ counts[tab.key] || 0 }})
            </button>
        </div>

        <!-- Filtres -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 mb-6">
            <div class="flex flex-col sm:flex-row sm:items-center gap-4">
                <div class="relative flex-1 max-w-md">
                    <input v-model="form.search" @input="debouncedSearch" type="text" placeholder="Code, désignation..."
                        class="w-full pl-10 pr-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                    <svg class="absolute left-3 top-2.5 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>
                <select v-model="form.selectedExamen" @change="applyFilters" class="py-2 px-3 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-sm text-gray-900 dark:text-white">
                    <option value="">Tous les examens</option>
                    <option v-for="e in examens" :key="e.id" :value="e.id">{{ e.name }}</option>
                </select>
                <select v-model="form.perPage" @change="applyFilters" class="py-2 px-3 bg-gray-50 dark:bg-gray-700 border rounded-lg text-sm">
                    <option value="10">10</option><option value="25">25</option><option value="50">50</option>
                </select>
                <button @click="openCreate()" class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg shadow-sm">+ Nouvelle analyse</button>
            </div>
        </div>

        <!-- Table -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900/50"><tr>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Code</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Désignation</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Niveau</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Examen</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Prix</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Statut</th>
                        <th class="px-6 py-4 text-center text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                    </tr></thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        <template v-for="a in analyses.data" :key="a.id">
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                <td class="px-6 py-4 text-sm font-mono text-gray-600 dark:text-gray-400">{{ a.code }}</td>
                                <td class="px-6 py-4 text-sm" :class="a.is_bold ? 'font-bold' : ''">
                                    <span class="text-gray-900 dark:text-white">{{ a.designation }}</span>
                                    <span v-if="a.enfants?.length" class="ml-2 inline-flex items-center px-1.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300">{{ a.enfants.length }} sous</span>
                                </td>
                                <td class="px-6 py-4">
                                    <span :class="levelBadge(a.level)" class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium">{{ a.level }}</span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">{{ a.examen?.name || '-' }}</td>
                                <td class="px-6 py-4 text-sm font-semibold text-gray-900 dark:text-white">{{ formatN(a.prix) }} Ar</td>
                                <td class="px-6 py-4">
                                    <span :class="a.status ? 'bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300' : 'bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300'" class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium">{{ a.status ? 'Actif' : 'Inactif' }}</span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <div class="flex justify-center space-x-2">
                                        <button @click="showAnalyseDetail(a)" class="p-2 text-indigo-600 dark:text-indigo-400 hover:bg-indigo-100 dark:hover:bg-indigo-900/30 rounded-lg transition-colors" title="Voir détails">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                        </button>
                                        <button @click="editAnalyse(a)" class="p-2 text-blue-600 dark:text-blue-400 hover:bg-blue-100 dark:hover:bg-blue-900/30 rounded-lg transition-colors" title="Modifier">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                        </button>
                                        <button @click="toggleSt(a)" :class="a.status ? 'text-orange-600 dark:text-orange-400 hover:bg-orange-100 dark:hover:bg-orange-900/30' : 'text-green-600 dark:text-green-400 hover:bg-green-100 dark:hover:bg-green-900/30'" class="p-2 rounded-lg transition-colors" :title="a.status ? 'Désactiver' : 'Activer'">
                                            <svg v-if="a.status" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path></svg>
                                            <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                        </button>
                                        <button @click="confirmDel(a)" class="p-2 text-red-600 dark:text-red-400 hover:bg-red-100 dark:hover:bg-red-900/30 rounded-lg transition-colors" title="Supprimer">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </template>
                        <tr v-if="!analyses.data.length"><td colspan="7" class="px-4 py-16 text-center text-gray-500">Aucune analyse</td></tr>
                    </tbody>
                </table>
            </div>
            <div class="px-6 py-4 bg-gray-50 dark:bg-gray-900/50 border-t"><Pagination :links="analyses.links" /></div>
        </div>

        <!-- ====== MODAL CREATE / EDIT ====== -->
        <div v-if="showForm" class="fixed inset-0 z-[1040] overflow-y-auto">
            <div class="flex items-start justify-center min-h-screen px-4 pt-8 pb-20">
                <div class="fixed inset-0 bg-black/50" @click="showForm = false"></div>
                <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-6xl w-full z-10 max-h-[90vh] overflow-y-auto">
                    <!-- Modal Header -->
                    <div class="sticky top-0 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 px-6 py-4 rounded-t-xl z-10">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center">
                                <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                {{ fd.id ? 'Modifier' : 'Nouvelle' }} Analyse
                            </h3>
                            <button type="button" @click="showForm = false" class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                    </div>
                    <form @submit.prevent="submitForm" class="p-6">

                        <!-- Section 1: Informations principales -->
                        <div class="border border-gray-200 dark:border-gray-600 rounded-lg p-4 bg-gray-50 dark:bg-gray-700/50 mb-6">
                            <h4 class="text-base font-medium text-gray-900 dark:text-white mb-4 flex items-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                Informations principales
                            </h4>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Code <span class="text-red-500">*</span></label>
                                    <input v-model="fd.code" required placeholder="Ex: GLY" class="w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white text-sm">
                                    <p v-if="fd.errors.code" class="text-red-500 text-xs mt-1">{{ fd.errors.code }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Niveau <span class="text-red-500">*</span></label>
                                    <select v-model="fd.level" @change="onLevelChange" required class="w-full px-3 py-2 bg-white dark:bg-gray-700 border rounded-lg text-sm text-gray-900 dark:text-white">
                                        <option value="NORMAL">NORMAL</option>
                                        <option value="PARENT">PARENT (Panel)</option>
                                        <option value="CHILD">CHILD (Sous-analyse)</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Parent</label>
                                    <select v-model="fd.parent_id" class="w-full px-3 py-2 bg-white dark:bg-gray-700 border rounded-lg text-sm text-gray-900 dark:text-white">
                                        <option value="">Aucun parent</option>
                                        <option v-for="p in analysesParents" :key="p.id" :value="p.id">{{ p.code }} - {{ p.designation }}</option>
                                    </select>
                                </div>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Désignation <span class="text-red-500">*</span></label>
                                    <input v-model="fd.designation" required placeholder="Ex: Glycémie" class="w-full px-3 py-2 bg-white dark:bg-gray-700 border rounded-lg text-sm text-gray-900 dark:text-white">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Prix (Ar) <span class="text-red-500">*</span>
                                        <span v-if="fd.level === 'PARENT' && autoSum > 0" class="text-xs text-green-600 ml-2">(Calcul automatique: {{ formatN(autoSum) }} Ar)</span>
                                        <span v-else-if="fd.level === 'PARENT' && fd.sousAnalyses.length > 0" class="text-xs text-blue-600 ml-2">(Prix manuel, car toutes sous-analyses à 0 Ar)</span>
                                    </label>
                                    <input v-model.number="fd.prix" type="number" min="0" step="0.01" required :readonly="fd.level === 'PARENT' && autoSum > 0"
                                        class="w-full px-3 py-2 bg-white dark:bg-gray-700 border rounded-lg text-sm text-gray-900 dark:text-white" :class="fd.level === 'PARENT' && autoSum > 0 ? 'bg-gray-100 dark:bg-gray-600' : ''">
                                    <p v-if="fd.level === 'PARENT' && fd.sousAnalyses.length > 0" class="mt-1 text-xs text-blue-600 dark:text-blue-400">
                                        <template v-if="autoSum > 0">Le prix est calculé automatiquement à partir des sous-analyses</template>
                                        <template v-else>Prix manuel car toutes les sous-analyses sont à 0 Ar</template>
                                    </p>
                                </div>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mt-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Examen <span class="text-red-500">*</span></label>
                                    <select v-model="fd.examen_id" required class="w-full px-3 py-2 bg-white dark:bg-gray-700 border rounded-lg text-sm text-gray-900 dark:text-white">
                                        <option value="">Sélectionner</option>
                                        <option v-for="e in examens" :key="e.id" :value="e.id">{{ e.name }}</option>
                                    </select>
                                </div>
                                <div>
                                    <div class="flex items-center justify-between mb-1">
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Type <span class="text-red-500">*</span></label>
                                        <button type="button" @click="showTypeHelp = true" class="text-blue-600 hover:text-blue-700 dark:text-blue-400 p-0.5" title="Aide sur les types">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        </button>
                                    </div>
                                    <select v-model="fd.type_id" required class="w-full px-3 py-2 bg-white dark:bg-gray-700 border rounded-lg text-sm text-gray-900 dark:text-white">
                                        <option value="">Sélectionner</option>
                                        <option v-for="t in types" :key="t.id" :value="t.id">{{ t.libelle }}</option>
                                    </select>
                                    <!-- Aide contextuelle sous le champ -->
                                    <div v-if="selectedType?.meta" class="mt-2 p-2 bg-blue-50 dark:bg-blue-900/20 border border-blue-100 dark:border-blue-800 rounded text-[11px] text-blue-800 dark:text-blue-300">
                                        <p class="font-bold mb-0.5">{{ selectedType.meta.label_metier }}</p>
                                        <p class="mb-1 italic opacity-80">{{ selectedType.meta.description }}</p>
                                        <p><span class="font-semibold">Ex:</span> {{ selectedType.meta.exemple }}</p>
                                    </div>
                                </div>
                                <div v-if="!selectedTypeFlags?.is_title">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Unité</label>
                                    <input v-model="fd.unite" placeholder="Ex: g/l" class="w-full px-3 py-2 bg-white dark:bg-gray-700 border rounded-lg text-sm text-gray-900 dark:text-white">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Ordre</label>
                                    <input v-model.number="fd.ordre" type="number" class="w-full px-3 py-2 bg-white dark:bg-gray-700 border rounded-lg text-sm text-gray-900 dark:text-white">
                                </div>
                            </div>
                            <div class="mt-4">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Description</label>
                                <textarea v-model="fd.description" rows="2" class="w-full px-3 py-2 bg-white dark:bg-gray-700 border rounded-lg text-sm text-gray-900 dark:text-white resize-none"></textarea>
                            </div>

                            <!-- Options de sélection (si type à choix) -->
                            <div v-if="selectedTypeFlags?.is_choice" class="mt-4 border border-indigo-200 dark:border-indigo-600 rounded-lg p-4 bg-indigo-50 dark:bg-indigo-900/20">
                                <h5 class="text-sm font-medium text-indigo-900 dark:text-indigo-200 mb-2 flex items-center">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7"/></svg>
                                    Options de sélection
                                </h5>
                                <p class="text-[11px] text-indigo-700 dark:text-indigo-300 mb-2">Saisissez les options possibles (une par ligne).</p>
                                <textarea v-model="fd.valeurs_predefinies" rows="4" placeholder="Option 1&#10;Option 2&#10;Autre"
                                    class="w-full px-3 py-2 bg-white dark:bg-gray-700 border border-indigo-300 dark:border-indigo-600 rounded-lg text-sm text-gray-900 dark:text-white"></textarea>
                            </div>

                            <!-- Valeurs de référence -->
                            <div v-if="!selectedTypeFlags?.is_title" class="mt-4 border border-blue-200 dark:border-blue-600 rounded-lg p-4 bg-blue-50 dark:bg-blue-900/20" :class="{'ring-2 ring-blue-400': selectedTypeFlags?.uses_ref}">
                                <h5 class="text-sm font-medium text-blue-900 dark:text-blue-200 mb-3 flex items-center">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                                    Valeurs de Référence <span v-if="selectedTypeFlags?.uses_ref" class="ml-2 text-[10px] bg-blue-200 dark:bg-blue-800 px-1.5 py-0.5 rounded uppercase">Recommandé</span>
                                </h5>
                                <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                                    <div><label class="block text-xs font-medium mb-1">Réf. Homme</label><input v-model="fd.valeur_ref_homme" placeholder="Ex: 3.89 - 6.05" class="w-full px-2 py-1.5 text-xs bg-white dark:bg-gray-700 border rounded"></div>
                                    <div><label class="block text-xs font-medium mb-1">Réf. Femme</label><input v-model="fd.valeur_ref_femme" placeholder="Ex: 3.89 - 6.05" class="w-full px-2 py-1.5 text-xs bg-white dark:bg-gray-700 border rounded"></div>
                                    <div><label class="block text-xs font-medium mb-1">Réf. Garçon</label><input v-model="fd.valeur_ref_enfant_garcon" placeholder="Ex: 3.5 - 5.5" class="w-full px-2 py-1.5 text-xs bg-white dark:bg-gray-700 border rounded"></div>
                                    <div><label class="block text-xs font-medium mb-1">Réf. Fille</label><input v-model="fd.valeur_ref_enfant_fille" placeholder="Ex: 3.5 - 5.5" class="w-full px-2 py-1.5 text-xs bg-white dark:bg-gray-700 border rounded"></div>
                                </div>
                                <div class="grid grid-cols-1 gap-3 mt-3">
                                    <div v-if="selectedTypeFlags?.uses_suffix || fd.suffixe"><label class="block text-xs font-medium mb-1">Suffixe</label><input v-model="fd.suffixe" placeholder="Suffixe optionnel" class="w-full px-2 py-1.5 text-xs bg-white dark:bg-gray-700 border rounded"></div>
                                </div>
                            </div>

                            <div v-if="selectedTypeFlags?.is_title" class="mt-4 p-4 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg text-sm text-amber-800 dark:text-amber-300 flex items-start">
                                <svg class="w-5 h-5 mr-3 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                <p>Ce type sert de <strong>titre ou séparateur</strong>. Il sera affiché en gras sur le compte-rendu mais ne permettra pas la saisie de résultats. Les unités et valeurs de référence sont masquées car non pertinentes.</p>
                            </div>

                            <div class="flex items-center gap-6 mt-4">
                                <label class="flex items-center text-sm"><input v-model="fd.status" type="checkbox" class="w-4 h-4 text-blue-600 mr-2">Actif</label>
                                <label class="flex items-center text-sm"><input v-model="fd.is_bold" type="checkbox" class="w-4 h-4 text-blue-600 mr-2">Gras</label>
                            </div>
                        </div>

                        <!-- Section 2: Sous-analyses (si PARENT) -->
                        <div v-if="fd.level === 'PARENT'" class="border border-purple-200 dark:border-purple-600 rounded-lg p-4 bg-purple-50 dark:bg-purple-900/20 mb-6">
                            <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center mb-4">
                                <h4 class="text-base font-medium text-purple-900 dark:text-purple-200 flex items-center">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                                    Sous-analyses
                                    <span v-if="fd.sousAnalyses.length" class="ml-2 bg-purple-200 dark:bg-purple-800 text-purple-800 dark:text-purple-200 px-2 py-0.5 rounded-full text-xs">{{ fd.sousAnalyses.length }}</span>
                                </h4>
                                <div class="flex gap-2 mt-2 sm:mt-0">
                                    <button type="button" @click="addSousAnalyse" class="bg-purple-600 hover:bg-purple-700 text-white px-3 py-1.5 rounded-lg flex items-center text-sm">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                                        Ajouter
                                    </button>
                                    <button v-if="fd.sousAnalyses.length" type="button" @click="recalculerTousLesPrix" class="bg-green-600 hover:bg-green-700 text-white px-3 py-1.5 rounded-lg flex items-center text-sm">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                        Recalculer prix
                                    </button>
                                </div>
                            </div>

                            <!-- Sous-analyse items -->
                            <div v-for="(sa, i) in fd.sousAnalyses" :key="i" class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-lg p-4 mb-3">
                                <!-- Header with move/delete -->
                                <div class="flex justify-between items-center mb-3">
                                    <h5 class="font-medium text-sm text-gray-900 dark:text-white">
                                        Sous-analyse #{{ i + 1 }}
                                        <span v-if="sa.level === 'PARENT' && childrenSum(sa) > 0" class="text-xs text-green-600 ml-2">(Total: {{ formatN(sa.prix) }} Ar)</span>
                                    </h5>
                                    <div class="flex space-x-1">
                                        <button v-if="i > 0" type="button" @click="moveSousUp(i)" class="p-1 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 rounded text-gray-600" title="Monter">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>
                                        </button>
                                        <button v-if="i < fd.sousAnalyses.length - 1" type="button" @click="moveSousDown(i)" class="p-1 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 rounded text-gray-600" title="Descendre">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                        </button>
                                        <button type="button" @click="removeSousAnalyse(i)" class="p-1 bg-red-100 hover:bg-red-200 dark:bg-red-900 rounded text-red-600" title="Supprimer">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </div>
                                </div>

                                <!-- Row 1: Code, Designation, Prix, Level -->
                                <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                                    <div><label class="block text-xs font-medium mb-1">Code *</label><input v-model="sa.code" placeholder="Code" class="w-full px-2 py-1.5 text-sm bg-white dark:bg-gray-700 border rounded"></div>
                                    <div><label class="block text-xs font-medium mb-1">Désignation *</label><input v-model="sa.designation" placeholder="Désignation" class="w-full px-2 py-1.5 text-sm bg-white dark:bg-gray-700 border rounded"></div>
                                    <div>
                                        <label class="block text-xs font-medium mb-1">
                                            Prix *
                                            <span v-if="sa.level === 'PARENT' && childrenSum(sa) > 0" class="text-xs text-green-600">(auto)</span>
                                        </label>
                                        <input v-model.number="sa.prix" type="number" step="0.01" :readonly="sa.level === 'PARENT' && childrenSum(sa) > 0" placeholder="0" class="w-full px-2 py-1.5 text-sm bg-white dark:bg-gray-700 border rounded" :class="sa.level === 'PARENT' && childrenSum(sa) > 0 ? 'bg-gray-100' : ''">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium mb-1">Niveau *</label>
                                        <select v-model="sa.level" @change="onSousLevelChange(i)" class="w-full px-2 py-1.5 text-sm bg-white dark:bg-gray-700 border rounded">
                                            <option value="CHILD">CHILD</option><option value="NORMAL">NORMAL</option><option value="PARENT">PARENT (Panel)</option>
                                        </select>
                                    </div>
                                </div>

                                <!-- Row 2: Examen, Type, Unite, Ordre -->
                                <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mt-3">
                                    <div><label class="block text-xs font-medium mb-1">Examen</label>
                                        <select v-model="sa.examen_id" class="w-full px-2 py-1.5 text-sm bg-white dark:bg-gray-700 border rounded">
                                            <option value="">Hériter parent</option><option v-for="e in examens" :key="e.id" :value="e.id">{{ e.name }}</option>
                                        </select>
                                    </div>
                                    <div><label class="block text-xs font-medium mb-1">Type</label>
                                        <select v-model="sa.type_id" class="w-full px-2 py-1.5 text-sm bg-white dark:bg-gray-700 border rounded">
                                            <option value="">Hériter parent</option><option v-for="t in types" :key="t.id" :value="t.id">{{ t.name }}</option>
                                        </select>
                                    </div>
                                    <div><label class="block text-xs font-medium mb-1">Unité</label><input v-model="sa.unite" placeholder="Ex: g/l" class="w-full px-2 py-1.5 text-sm bg-white dark:bg-gray-700 border rounded"></div>
                                    <div><label class="block text-xs font-medium mb-1">Ordre</label><input v-model.number="sa.ordre" type="number" readonly class="w-full px-2 py-1.5 text-sm bg-gray-100 dark:bg-gray-600 border rounded"></div>
                                </div>

                                <!-- Row 3: Valeurs de référence -->
                                <div class="mt-3 border border-blue-200 dark:border-blue-600 rounded p-3 bg-blue-50 dark:bg-blue-900/20">
                                    <h6 class="text-xs font-medium text-blue-900 dark:text-blue-200 mb-2">Valeurs de Référence</h6>
                                    <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                                        <div><label class="block text-xs mb-1">Homme</label><input v-model="sa.valeur_ref_homme" placeholder="Ex: 3.89-6.05" class="w-full px-2 py-1 text-xs bg-white dark:bg-gray-700 border rounded"></div>
                                        <div><label class="block text-xs mb-1">Femme</label><input v-model="sa.valeur_ref_femme" placeholder="Ex: 3.89-6.05" class="w-full px-2 py-1 text-xs bg-white dark:bg-gray-700 border rounded"></div>
                                        <div><label class="block text-xs mb-1">Garçon</label><input v-model="sa.valeur_ref_enfant_garcon" placeholder="Ex: 3.5-5.5" class="w-full px-2 py-1 text-xs bg-white dark:bg-gray-700 border rounded"></div>
                                        <div><label class="block text-xs mb-1">Fille</label><input v-model="sa.valeur_ref_enfant_fille" placeholder="Ex: 3.5-5.5" class="w-full px-2 py-1 text-xs bg-white dark:bg-gray-700 border rounded"></div>
                                    </div>
                                    <div class="grid grid-cols-1 gap-2 mt-2">
                                        <div><label class="block text-xs mb-1">Suffixe</label><input v-model="sa.suffixe" placeholder="Suffixe" class="w-full px-2 py-1 text-xs bg-white dark:bg-gray-700 border rounded"></div>
                                    </div>

                                </div>

                                <div class="flex items-center gap-4 mt-3">
                                    <label class="flex items-center text-xs"><input v-model="sa.status" type="checkbox" class="w-3.5 h-3.5 mr-1.5">Actif</label>
                                    <label class="flex items-center text-xs"><input v-model="sa.is_bold" type="checkbox" class="w-3.5 h-3.5 mr-1.5">Gras</label>
                                </div>

                                <!-- ====== CHILDREN OF SUB-ANALYSE (3rd level) ====== -->
                                <div v-if="sa.level === 'PARENT'" class="mt-4 pt-3 border-t border-purple-200 dark:border-purple-600">
                                    <div class="flex justify-between items-center mb-3">
                                        <h6 class="text-sm font-medium text-purple-800 dark:text-purple-300 flex items-center">
                                            Sous-sous-analyses
                                            <span v-if="sa.children?.length" class="ml-1 bg-purple-200 dark:bg-purple-800 text-purple-800 dark:text-purple-200 px-1.5 py-0.5 rounded-full text-xs">{{ sa.children.length }}</span>
                                        </h6>
                                        <button type="button" @click="addChildToSous(i)" class="bg-purple-500 hover:bg-purple-600 text-white px-3 py-1 rounded-md text-xs flex items-center">
                                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                                            Ajouter
                                        </button>
                                    </div>

                                    <div v-for="(child, ci) in sa.children" :key="ci" class="bg-gray-50 dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-md p-3 mb-2 ml-0 sm:ml-4">
                                        <div class="flex justify-between items-center mb-2">
                                            <span class="text-xs font-medium text-gray-800 dark:text-gray-200">Sous-sous #{{ ci + 1 }}</span>
                                            <div class="flex space-x-1">
                                                <button v-if="ci > 0" type="button" @click="moveChildUp(i, ci)" class="p-0.5 bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 rounded"><svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg></button>
                                                <button v-if="ci < sa.children.length - 1" type="button" @click="moveChildDown(i, ci)" class="p-0.5 bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 rounded"><svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg></button>
                                                <button type="button" @click="removeChild(i, ci)" class="p-0.5 bg-red-100 hover:bg-red-200 dark:bg-red-900 rounded text-red-600"><svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></button>
                                            </div>
                                        </div>
                                        <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                                            <div><label class="block text-xs mb-1">Code *</label><input v-model="child.code" placeholder="Code" class="w-full px-2 py-1 text-xs bg-white dark:bg-gray-700 border rounded"></div>
                                            <div><label class="block text-xs mb-1">Désignation *</label><input v-model="child.designation" placeholder="Désignation" class="w-full px-2 py-1 text-xs bg-white dark:bg-gray-700 border rounded"></div>
                                            <div><label class="block text-xs mb-1">Prix *</label><input v-model.number="child.prix" type="number" step="0.01" placeholder="0" class="w-full px-2 py-1 text-xs bg-white dark:bg-gray-700 border rounded"></div>
                                            <div><label class="block text-xs mb-1">Niveau</label>
                                                <select v-model="child.level" class="w-full px-2 py-1 text-xs bg-white dark:bg-gray-700 border rounded">
                                                    <option value="CHILD">CHILD</option><option value="NORMAL">NORMAL</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="grid grid-cols-2 md:grid-cols-4 gap-2 mt-2">
                                            <div><label class="block text-xs mb-1">Examen</label>
                                                <select v-model="child.examen_id" class="w-full px-2 py-1 text-xs bg-white dark:bg-gray-700 border rounded">
                                                    <option value="">Hériter</option><option v-for="e in examens" :key="e.id" :value="e.id">{{ e.name }}</option>
                                                </select>
                                            </div>
                                            <div><label class="block text-xs mb-1">Type</label>
                                                <select v-model="child.type_id" class="w-full px-2 py-1 text-xs bg-white dark:bg-gray-700 border rounded">
                                                    <option value="">Hériter</option><option v-for="t in types" :key="t.id" :value="t.id">{{ t.name }}</option>
                                                </select>
                                            </div>
                                            <div><label class="block text-xs mb-1">Unité</label><input v-model="child.unite" placeholder="g/l" class="w-full px-2 py-1 text-xs bg-white dark:bg-gray-700 border rounded"></div>
                                            <div><label class="block text-xs mb-1">Ordre</label><input v-model.number="child.ordre" type="number" readonly class="w-full px-2 py-1 text-xs bg-gray-100 dark:bg-gray-600 border rounded"></div>
                                        </div>
                                        <!-- Child ref values -->
                                        <div class="grid grid-cols-2 md:grid-cols-4 gap-2 mt-2">
                                            <div><label class="block text-xs mb-1">Réf. H</label><input v-model="child.valeur_ref_homme" class="w-full px-2 py-1 text-xs bg-white dark:bg-gray-700 border rounded"></div>
                                            <div><label class="block text-xs mb-1">Réf. F</label><input v-model="child.valeur_ref_femme" class="w-full px-2 py-1 text-xs bg-white dark:bg-gray-700 border rounded"></div>
                                            <div><label class="block text-xs mb-1">Réf. G</label><input v-model="child.valeur_ref_enfant_garcon" class="w-full px-2 py-1 text-xs bg-white dark:bg-gray-700 border rounded"></div>
                                            <div><label class="block text-xs mb-1">Réf. Fi</label><input v-model="child.valeur_ref_enfant_fille" class="w-full px-2 py-1 text-xs bg-white dark:bg-gray-700 border rounded"></div>
                                        </div>
                                        <div class="flex items-center gap-3 mt-2">
                                            <label class="flex items-center text-xs"><input v-model="child.status" type="checkbox" class="w-3 h-3 mr-1">Actif</label>
                                            <label class="flex items-center text-xs"><input v-model="child.is_bold" type="checkbox" class="w-3 h-3 mr-1">Gras</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Submit buttons -->
                        <div class="flex justify-end space-x-3">
                            <button type="button" @click="showForm = false" class="px-4 py-2 text-sm bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200">Annuler</button>
                            <button type="submit" :disabled="fd.processing" class="px-4 py-2 text-sm text-white bg-blue-600 hover:bg-blue-700 rounded-lg disabled:opacity-50">{{ fd.id ? 'Modifier' : 'Créer' }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Delete confirm -->
        <div v-if="showDel" class="fixed inset-0 z-[1040] overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-black/50 transition-opacity" @click="showDel = false"></div>
                <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-sm w-full p-6 z-10">
                    <div class="flex items-start mb-4">
                        <div class="flex-shrink-0 w-10 h-10 rounded-full bg-red-100 dark:bg-red-900/30 flex items-center justify-center mr-3">
                            <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white">Confirmer la suppression</h3>
                            <p class="text-gray-600 dark:text-gray-400 mt-1">Êtes-vous sûr de vouloir supprimer <strong class="text-gray-900 dark:text-white">{{ delItem?.designation }}</strong> ?</p>
                        </div>
                    </div>
                    <div class="flex justify-end space-x-3">
                        <button @click="showDel = false" class="px-4 py-2 text-sm text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg transition-colors">Annuler</button>
                        <button @click="deleteA" class="px-4 py-2 text-sm text-white bg-red-600 hover:bg-red-700 rounded-lg transition-colors">Supprimer</button>
                    </div>
                </div>
            </div>
        </div>
        <!-- ====== MODAL DETAIL ====== -->
        <div v-if="showDetail" class="fixed inset-0 z-[1040] overflow-y-auto">
            <div class="flex items-start justify-center min-h-screen px-4 pt-8 pb-20">
                <div class="fixed inset-0 bg-black/60 transition-opacity" @click="showDetail = false"></div>
                <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-4xl w-full z-10 overflow-hidden">
                    <!-- Header -->
                    <div class="bg-indigo-600 px-6 py-4 flex items-center justify-between">
                        <h3 class="text-lg font-bold text-white flex items-center">
                            <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            Détails de l'analyse : {{ viewItem?.designation }}
                        </h3>
                        <button @click="showDetail = false" class="text-white/80 hover:text-white transition-colors">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <div class="p-6 bg-gray-50 dark:bg-gray-900/50">
                        <!-- Info Cards -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                            <div class="bg-white dark:bg-gray-800 p-4 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm">
                                <span class="text-[10px] text-gray-500 uppercase font-bold tracking-wider">Code & Niveau</span>
                                <div class="mt-1 flex items-center gap-2">
                                    <span class="font-mono font-bold text-indigo-600 dark:text-indigo-400">{{ viewItem?.code }}</span>
                                    <span :class="levelBadge(viewItem?.level)" class="px-2 py-0.5 rounded text-[10px]">{{ viewItem?.level }}</span>
                                </div>
                            </div>
                            <div class="bg-white dark:bg-gray-800 p-4 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm">
                                <span class="text-[10px] text-gray-500 uppercase font-bold tracking-wider">Prix & Examen</span>
                                <div class="mt-1">
                                    <span class="font-bold text-gray-900 dark:text-white">{{ formatN(viewItem?.prix) }} Ar</span>
                                    <span class="mx-2 text-gray-300">|</span>
                                    <span class="text-sm text-gray-600 dark:text-gray-400">{{ viewItem?.examen?.name }}</span>
                                </div>
                            </div>
                            <div class="bg-white dark:bg-gray-800 p-4 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm">
                                <span class="text-[10px] text-gray-500 uppercase font-bold tracking-wider">Type de saisie</span>
                                <div class="mt-1">
                                    <span class="text-sm font-medium text-blue-600 dark:text-blue-400">{{ viewItem?.type?.libelle || viewItem?.type?.name }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Arborescence -->
                        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
                            <div class="px-4 py-3 border-b border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50 flex items-center justify-between">
                                <h4 class="text-sm font-bold text-gray-700 dark:text-gray-300 flex items-center">
                                    <svg class="w-4 h-4 mr-2 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                                    Structure & Hiérarchie
                                </h4>
                                <span v-if="viewItem?.enfants?.length" class="text-[10px] bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-400 px-2 py-0.5 rounded-full font-bold">
                                    {{ viewItem.enfants.length }} SOUS-ANALYSES
                                </span>
                            </div>
                            <div class="p-4 overflow-y-auto max-h-[400px]">
                                <div v-if="!viewItem?.enfants?.length" class="text-center py-8 text-gray-400 italic text-sm">
                                    Cette analyse n'a pas de sous-analyses.
                                </div>
                                <div v-else class="space-y-4">
                                    <div v-for="sa in viewItem.enfants" :key="sa.id" class="border-l-2 border-indigo-200 dark:border-indigo-800 pl-4 py-1">
                                        <div class="flex items-center justify-between">
                                            <div>
                                                <span class="text-xs font-mono font-bold text-gray-500">{{ sa.code }}</span>
                                                <h5 class="text-sm font-bold text-gray-800 dark:text-gray-200">{{ sa.designation }}</h5>
                                            </div>
                                            <div class="text-right">
                                                <div class="text-xs font-bold">{{ formatN(sa.prix) }} Ar</div>
                                                <div class="text-[10px] text-gray-500">{{ sa.unite || 'Pas d\'unité' }}</div>
                                            </div>
                                        </div>
                                        <!-- Sub-sub children -->
                                        <div v-if="sa.enfants?.length" class="mt-3 ml-4 space-y-2">
                                            <div v-for="child in sa.enfants" :key="child.id" class="flex items-center justify-between p-2 bg-gray-50 dark:bg-gray-700/30 rounded-lg border border-gray-100 dark:border-gray-700">
                                                <div class="flex items-center">
                                                    <svg class="w-3 h-3 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                                    <div>
                                                        <div class="text-[10px] font-mono text-gray-400 leading-none">{{ child.code }}</div>
                                                        <div class="text-xs font-medium text-gray-700 dark:text-gray-300">{{ child.designation }}</div>
                                                    </div>
                                                </div>
                                                <div class="text-[10px] font-bold text-gray-600 dark:text-gray-400">{{ formatN(child.prix) }} Ar</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="p-4 bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 flex justify-end gap-3">
                        <button @click="editAnalyse(viewItem); showDetail = false" class="px-4 py-2 text-sm font-bold text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition-colors flex items-center shadow-sm">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                            Modifier cette analyse
                        </button>
                        <button @click="showDetail = false" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg transition-colors">
                            Fermer
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Help Modal for Types -->
        <div v-if="showTypeHelp" class="fixed inset-0 z-[1050] overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4 py-12">
                <div class="fixed inset-0 bg-black/60 transition-opacity" @click="showTypeHelp = false"></div>
                <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-4xl w-full p-0 z-10 overflow-hidden">
                    <div class="bg-blue-600 px-6 py-4 flex items-center justify-between">
                        <h3 class="text-lg font-bold text-white flex items-center">
                            <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            Guide des Types d'Analyses
                        </h3>
                        <button @click="showTypeHelp = false" class="text-white/80 hover:text-white transition-colors">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                    <div class="p-6 max-h-[70vh] overflow-y-auto bg-gray-50 dark:bg-gray-900/50">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div v-for="t in types" :key="t.id" class="bg-white dark:bg-gray-800 p-4 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm hover:border-blue-400 dark:hover:border-blue-500 transition-all cursor-pointer group" @click="fd.type_id = t.id; showTypeHelp = false">
                                <div class="flex items-start justify-between mb-2">
                                    <h4 class="font-bold text-blue-700 dark:text-blue-400 group-hover:text-blue-600">{{ t.meta?.label_metier || t.libelle }}</h4>
                                    <span v-if="t.meta?.flags?.is_title" class="text-[10px] bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400 px-1.5 py-0.5 rounded font-bold uppercase">Titre</span>
                                </div>
                                <p class="text-xs text-gray-600 dark:text-gray-400 mb-3 leading-relaxed">{{ t.meta?.description }}</p>
                                <div class="bg-gray-50 dark:bg-gray-700/50 p-2 rounded text-[11px] mb-3">
                                    <span class="font-bold text-gray-500">Exemple:</span> <span class="text-gray-700 dark:text-gray-300">{{ t.meta?.exemple }}</span>
                                </div>
                                <div class="flex flex-wrap gap-1">
                                    <span v-if="t.meta?.flags?.uses_ref" class="text-[9px] bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 px-1.5 py-0.5 rounded">Réf. conseillée</span>
                                    <span v-if="t.meta?.flags?.uses_suffix" class="text-[9px] bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400 px-1.5 py-0.5 rounded">Suffixe possible</span>
                                    <span v-if="t.meta?.flags?.is_choice" class="text-[9px] bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-400 px-1.5 py-0.5 rounded">Liste de choix</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="p-4 bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 text-center">
                        <p class="text-xs text-gray-500">Cliquez sur un type pour le sélectionner dans le formulaire.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</AppLayout>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import { debounce } from 'lodash';

const props = defineProps({
    analyses: Object, counts: Object, examens: Array, types: Array, analysesParents: Array, filters: Object,
});

const showForm = ref(false);
const showDel = ref(false);
const showTypeHelp = ref(false);
const showDetail = ref(false);
const delItem = ref(null);
const viewItem = ref(null);

const showAnalyseDetail = (a) => {
    viewItem.value = a;
    showDetail.value = true;
};

const levelTabs = [
    { key: 'tous', label: 'Tous' },
    { key: 'racines', label: 'Racines' },
    { key: 'parents', label: 'Parents' },
    { key: 'normales', label: 'Normales' },
    { key: 'enfants', label: 'Enfants' },
];

const form = ref({
    search: props.filters.search || '',
    selectedExamen: props.filters.selectedExamen || '',
    selectedLevel: props.filters.selectedLevel || 'tous',
    perPage: props.filters.perPage || 10,
});

const fd = useForm({
    id: null, code: '', level: 'NORMAL', parent_id: '', designation: '', description: '',
    prix: 0, is_bold: false, examen_id: '', type_id: '',
    valeur_ref_homme: '', valeur_ref_femme: '',
    valeur_ref_enfant_garcon: '', valeur_ref_enfant_fille: '',
    unite: '', suffixe: '', ordre: 99, status: true, sousAnalyses: [],
    valeurs_predefinies: '',
});

const selectedType = computed(() => props.types.find(t => t.id === fd.type_id));
const selectedTypeFlags = computed(() => selectedType.value?.meta?.flags || {});

const formatN = (n) => Number(n || 0).toLocaleString('fr-FR');
const levelBadge = (l) => ({
    PARENT: 'bg-purple-100 dark:bg-purple-900/30 text-purple-800 dark:text-purple-300',
    NORMAL: 'bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300',
    CHILD: 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300',
}[l] || 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300');

const applyFilters = () => router.get(route('laboratoire.analyses.listes'), form.value, { preserveState: true, preserveScroll: true, replace: true });
const debouncedSearch = debounce(applyFilters, 300);
const filterLevel = (key) => { form.value.selectedLevel = key; applyFilters(); };

// ---- Auto price calculation ----
const childrenSum = (sa) => {
    if (!sa.children || !sa.children.length) return 0;
    return sa.children.reduce((sum, c) => sum + (parseFloat(c.prix) || 0), 0);
};

const autoSum = computed(() => {
    if (fd.level !== 'PARENT' || !fd.sousAnalyses.length) return 0;
    return fd.sousAnalyses.reduce((sum, sa) => sum + (parseFloat(sa.prix) || 0), 0);
});

// Watch to auto-update parent prix
watch(autoSum, (val) => {
    if (val > 0 && fd.level === 'PARENT') fd.prix = val;
});

// Watch each sous-analyse children to update sous prix
watch(() => fd.sousAnalyses.map(sa => JSON.stringify(sa.children?.map(c => c.prix))), () => {
    fd.sousAnalyses.forEach((sa) => {
        if (sa.level === 'PARENT' && sa.children?.length) {
            const sum = childrenSum(sa);
            if (sum > 0) sa.prix = sum;
        }
    });
}, { deep: true });

// ---- Level changes ----
const onLevelChange = () => {
    if (fd.level === 'PARENT' && fd.sousAnalyses.length === 0) {
        addSousAnalyse();
    } else if (fd.level !== 'PARENT') {
        fd.sousAnalyses = [];
    }
};

const onSousLevelChange = (i) => {
    if (fd.sousAnalyses[i].level === 'PARENT' && (!fd.sousAnalyses[i].children || fd.sousAnalyses[i].children.length === 0)) {
        addChildToSous(i);
    } else if (fd.sousAnalyses[i].level !== 'PARENT') {
        fd.sousAnalyses[i].children = [];
    }
};

// ---- CRUD helpers ----
const makeSousAnalyse = () => ({
    id: null, code: '', designation: '', prix: 0, level: 'CHILD',
    examen_id: fd.examen_id || '', type_id: fd.type_id || '',
    unite: '', suffixe: '', 
    valeur_ref_homme: '', valeur_ref_femme: '',
    valeur_ref_enfant_garcon: '', valeur_ref_enfant_fille: '',
    ordre: fd.sousAnalyses.length + 1, is_bold: false, status: true, children: [],
});

const makeChild = (parentSa) => ({
    id: null, code: '', designation: '', prix: 0, level: 'CHILD',
    examen_id: parentSa.examen_id || fd.examen_id || '',
    type_id: parentSa.type_id || fd.type_id || '',
    unite: '', suffixe: '', 
    valeur_ref_homme: '',
    valeur_ref_femme: '',
    valeur_ref_enfant_garcon: '', valeur_ref_enfant_fille: '',
    ordre: (parentSa.children?.length || 0) + 1, is_bold: false, status: true,
});

const addSousAnalyse = () => fd.sousAnalyses.push(makeSousAnalyse());

const removeSousAnalyse = (i) => {
    fd.sousAnalyses.splice(i, 1);
    fd.sousAnalyses.forEach((sa, idx) => sa.ordre = idx + 1);
};

const moveSousUp = (i) => {
    if (i > 0) {
        [fd.sousAnalyses[i], fd.sousAnalyses[i - 1]] = [fd.sousAnalyses[i - 1], fd.sousAnalyses[i]];
        fd.sousAnalyses[i].ordre = i + 1;
        fd.sousAnalyses[i - 1].ordre = i;
    }
};

const moveSousDown = (i) => {
    if (i < fd.sousAnalyses.length - 1) {
        [fd.sousAnalyses[i], fd.sousAnalyses[i + 1]] = [fd.sousAnalyses[i + 1], fd.sousAnalyses[i]];
        fd.sousAnalyses[i].ordre = i + 1;
        fd.sousAnalyses[i + 1].ordre = i + 2;
    }
};

const addChildToSous = (i) => {
    if (!fd.sousAnalyses[i].children) fd.sousAnalyses[i].children = [];
    fd.sousAnalyses[i].children.push(makeChild(fd.sousAnalyses[i]));
};

const removeChild = (pi, ci) => {
    fd.sousAnalyses[pi].children.splice(ci, 1);
    fd.sousAnalyses[pi].children.forEach((c, idx) => c.ordre = idx + 1);
};

const moveChildUp = (pi, ci) => {
    if (ci > 0) {
        const arr = fd.sousAnalyses[pi].children;
        [arr[ci], arr[ci - 1]] = [arr[ci - 1], arr[ci]];
        arr[ci].ordre = ci + 1;
        arr[ci - 1].ordre = ci;
    }
};

const moveChildDown = (pi, ci) => {
    const arr = fd.sousAnalyses[pi].children;
    if (ci < arr.length - 1) {
        [arr[ci], arr[ci + 1]] = [arr[ci + 1], arr[ci]];
        arr[ci].ordre = ci + 1;
        arr[ci + 1].ordre = ci + 2;
    }
};

const recalculerTousLesPrix = () => {
    fd.sousAnalyses.forEach((sa) => {
        if (sa.level === 'PARENT' && sa.children?.length) {
            const sum = childrenSum(sa);
            if (sum > 0) sa.prix = sum;
        }
    });
    const total = fd.sousAnalyses.reduce((s, sa) => s + (parseFloat(sa.prix) || 0), 0);
    if (total > 0) fd.prix = total;
};

// ---- Open/Edit ----
const openCreate = () => {
    fd.reset();
    fd.id = null; fd.status = true; fd.ordre = 99; fd.sousAnalyses = []; fd.valeurs_predefinies = '';
    showForm.value = true;
};

const editAnalyse = (a) => {
    // Format predefined values for textarea (1 per line)
    let vPredef = '';
    if (a.valeurs_predefinies) {
        try {
            const parsed = typeof a.valeurs_predefinies === 'string' ? JSON.parse(a.valeurs_predefinies) : a.valeurs_predefinies;
            if (Array.isArray(parsed)) vPredef = parsed.join('\n');
            else vPredef = a.valeurs_predefinies;
        } catch (e) { vPredef = a.valeurs_predefinies; }
    }

    Object.assign(fd, {
        id: a.id, code: a.code, level: a.level, parent_id: a.parent_id || '',
        designation: a.designation, description: a.description || '', prix: a.prix,
        is_bold: a.is_bold, examen_id: a.examen_id, type_id: a.type_id,
        valeur_ref_homme: a.valeur_ref_homme || '',
        valeur_ref_femme: a.valeur_ref_femme || '',
        valeur_ref_enfant_garcon: a.valeur_ref_enfant_garcon || '',
        valeur_ref_enfant_fille: a.valeur_ref_enfant_fille || '',
        unite: a.unite || '', suffixe: a.suffixe || '', ordre: a.ordre, status: a.status,
        valeurs_predefinies: vPredef,
        sousAnalyses: (a.enfants || []).map(e => ({
            id: e.id, code: e.code, designation: e.designation, prix: e.prix,
            level: e.level, examen_id: e.examen_id, type_id: e.type_id,
            unite: e.unite || '', suffixe: e.suffixe || '', ordre: e.ordre,
            valeur_ref_homme: e.valeur_ref_homme || '',
            valeur_ref_femme: e.valeur_ref_femme || '',
            valeur_ref_enfant_garcon: e.valeur_ref_enfant_garcon || '',
            valeur_ref_enfant_fille: e.valeur_ref_enfant_fille || '',
            is_bold: e.is_bold || false, status: e.status ?? true,
            children: (e.enfants || []).map(c => ({
                id: c.id, code: c.code, designation: c.designation, prix: c.prix,
                level: c.level, examen_id: c.examen_id, type_id: c.type_id,
                unite: c.unite || '', suffixe: c.suffixe || '', ordre: c.ordre,
                valeur_ref_homme: c.valeur_ref_homme || '',
                valeur_ref_femme: c.valeur_ref_femme || '',
                valeur_ref_enfant_garcon: c.valeur_ref_enfant_garcon || '',
                valeur_ref_enfant_fille: c.valeur_ref_enfant_fille || '',
                is_bold: c.is_bold || false, status: c.status ?? true,
            })),
        })),
    });
    showForm.value = true;
};

// ---- Submit ----
const submitForm = () => {
    // Process predefined values back to array
    const data = { ...fd.data() };
    if (fd.valeurs_predefinies) {
        data.valeurs_predefinies = fd.valeurs_predefinies
            .split('\n')
            .map(v => v.trim())
            .filter(v => v !== '');
    } else {
        data.valeurs_predefinies = null;
    }

    const opts = { preserveScroll: true, onSuccess: () => { showForm.value = false; } };
    fd.id ? router.put(route('laboratoire.analyses.listes.update', fd.id), data, opts) : router.post(route('laboratoire.analyses.listes.store'), data, opts);
};

const toggleSt = (a) => router.post(route('laboratoire.analyses.listes.toggle', a.id), {}, { preserveScroll: true });
const confirmDel = (a) => { delItem.value = a; showDel.value = true; };
const deleteA = () => router.delete(route('laboratoire.analyses.listes.destroy', delItem.value.id), { preserveScroll: true, onSuccess: () => { showDel.value = false; } });
</script>
