<template>
    <AppLayout>
        <div class="container mx-auto px-4 py-8 sm:px-6 lg:px-8">
            <div class="mb-6 flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-bold text-slate-900 dark:text-white">Prescriptions</h1>
                    <p class="mt-0.5 text-sm text-slate-500 dark:text-slate-400">Gestion et suivi des prescriptions</p>
                </div>

                <Link
                    v-if="canCreate"
                    :href="route('secretaire.prescription.create')"
                    class="inline-flex items-center gap-2 rounded-lg bg-primary-600 px-4 py-2.5 text-sm font-medium text-white shadow-sm transition-colors hover:bg-primary-700"
                >
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    <span class="hidden sm:inline">Nouvelle prescription</span>
                    <span class="sm:hidden">Nouveau</span>
                </Link>
            </div>

            <div class="mb-6 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
                <div class="relative overflow-hidden rounded-xl border border-slate-200 bg-white p-4 transition-all hover:shadow-md dark:border-slate-700 dark:bg-slate-800">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-lg bg-blue-50 dark:bg-blue-900/30">
                            <svg class="h-5 w-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <p class="text-2xl font-bold leading-none text-slate-900 dark:text-white">{{ number(counts.countActives || counts.actives) }}</p>
                            <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">En traitement</p>
                        </div>
                    </div>
                    <div class="mt-3 flex items-center gap-2 text-[11px] text-slate-400 dark:text-slate-500">
                        <span class="inline-flex items-center gap-1">
                            <span class="h-1.5 w-1.5 rounded-full bg-amber-400"></span>{{ number(counts.countEnAttente) }}
                        </span>
                        <span class="inline-flex items-center gap-1">
                            <span class="h-1.5 w-1.5 rounded-full bg-blue-400"></span>{{ number(counts.countEnCours) }}
                        </span>
                        <span class="inline-flex items-center gap-1">
                            <span class="h-1.5 w-1.5 rounded-full bg-green-400"></span>{{ number(counts.countTermine) }}
                        </span>
                    </div>
                    <div class="absolute -right-4 -top-4 h-16 w-16 rounded-full bg-blue-50 opacity-50 dark:bg-blue-900/10"></div>
                </div>

                <div class="relative overflow-hidden rounded-xl border border-slate-200 bg-white p-4 transition-all hover:shadow-md dark:border-slate-700 dark:bg-slate-800">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-lg bg-green-50 dark:bg-green-900/30">
                            <svg class="h-5 w-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <p class="text-2xl font-bold leading-none text-slate-900 dark:text-white">{{ number(counts.countValide || counts.valide) }}</p>
                            <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">Validees</p>
                        </div>
                    </div>
                    <div class="absolute -right-4 -top-4 h-16 w-16 rounded-full bg-green-50 opacity-50 dark:bg-green-900/10"></div>
                </div>

                <button
                    type="button"
                    class="relative overflow-hidden rounded-xl border p-4 text-left transition-all hover:shadow-md"
                    :class="form.payment === 'paye' ? 'border-emerald-400 ring-1 ring-emerald-400/50 dark:border-emerald-500' : 'border-slate-200 dark:border-slate-700'"
                    @click="filterByPaymentStatus('paye')"
                >
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-lg bg-emerald-50 dark:bg-emerald-900/30">
                            <svg class="h-5 w-5 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75" />
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <p class="text-2xl font-bold leading-none text-emerald-600 dark:text-emerald-400">{{ number(counts.countPaye) }}</p>
                            <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">Payees</p>
                        </div>
                    </div>
                    <div v-if="form.payment === 'paye'" class="absolute right-2 top-2">
                        <span class="relative flex h-2 w-2">
                            <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"></span>
                            <span class="relative inline-flex h-2 w-2 rounded-full bg-emerald-500"></span>
                        </span>
                    </div>
                    <div class="absolute -right-4 -top-4 h-16 w-16 rounded-full bg-emerald-50 opacity-50 dark:bg-emerald-900/10"></div>
                </button>

                <button
                    type="button"
                    class="relative overflow-hidden rounded-xl border p-4 text-left transition-all hover:shadow-md"
                    :class="form.payment === 'non_paye' ? 'border-red-400 ring-1 ring-red-400/50 dark:border-red-500' : 'border-slate-200 dark:border-slate-700'"
                    @click="filterByPaymentStatus('non_paye')"
                >
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-lg bg-red-50 dark:bg-red-900/30">
                            <svg class="h-5 w-5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <p class="text-2xl font-bold leading-none text-red-600 dark:text-red-400">{{ number(counts.countNonPaye) }}</p>
                            <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">Non payees</p>
                        </div>
                    </div>
                    <div v-if="form.payment === 'non_paye'" class="absolute right-2 top-2">
                        <span class="relative flex h-2 w-2">
                            <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-red-400 opacity-75"></span>
                            <span class="relative inline-flex h-2 w-2 rounded-full bg-red-500"></span>
                        </span>
                    </div>
                    <div class="absolute -right-4 -top-4 h-16 w-16 rounded-full bg-red-50 opacity-50 dark:bg-red-900/10"></div>
                </button>

                <div class="relative col-span-2 overflow-hidden rounded-xl border border-slate-200 bg-white p-4 transition-all hover:shadow-md sm:col-span-1 dark:border-slate-700 dark:bg-slate-800">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-lg bg-slate-100 dark:bg-slate-700">
                            <svg class="h-5 w-5 text-slate-600 dark:text-slate-300" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3h16.5v11.25A2.25 2.25 0 0118 16.5H6a2.25 2.25 0 01-2.25-2.25V3z" />
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <p class="text-2xl font-bold leading-none text-slate-900 dark:text-white">{{ number(totalCount) }}</p>
                            <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">Total</p>
                        </div>
                    </div>
                    <div class="mt-3 flex items-center gap-2">
                        <div class="h-1.5 flex-1 overflow-hidden rounded-full bg-slate-100 dark:bg-slate-700">
                            <div class="h-full rounded-full bg-emerald-500 transition-all duration-500" :style="{ width: `${paymentRate}%` }"></div>
                        </div>
                        <span class="text-[11px] font-medium text-slate-500 dark:text-slate-400">{{ paymentRate }}%</span>
                    </div>
                    <div class="absolute -right-4 -top-4 h-16 w-16 rounded-full bg-slate-100 opacity-50 dark:bg-slate-700/30"></div>
                </div>
            </div>

            <div class="mb-6 rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-800">
                <div class="flex flex-col items-stretch gap-3 p-4 sm:flex-row sm:items-center">
                    <div class="relative flex-1">
                        <svg class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                        </svg>
                        <input
                            v-model="form.search"
                            type="text"
                            placeholder="Rechercher par reference, patient, telephone..."
                            class="w-full rounded-lg border border-slate-200 bg-slate-50 py-2.5 pl-10 pr-9 text-sm text-slate-900 placeholder-slate-400 transition-colors focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 dark:border-slate-600 dark:bg-slate-700/50 dark:text-slate-100"
                            @input="debouncedApplyFilters"
                        >
                        <button
                            v-if="form.search"
                            type="button"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 transition-colors hover:text-slate-600 dark:hover:text-slate-300"
                            @click="clearSearch"
                        >
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <div class="hidden items-center gap-1.5 rounded-lg bg-slate-100 p-1 sm:flex dark:bg-slate-700/50">
                        <button type="button" class="rounded-md px-3 py-1.5 text-xs font-medium transition-all" :class="!form.payment ? 'bg-white text-slate-900 shadow-sm dark:bg-slate-600 dark:text-white' : 'text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-300'" @click="filterByPaymentStatus('')">Tous</button>
                        <button type="button" class="rounded-md px-3 py-1.5 text-xs font-medium transition-all" :class="form.payment === 'paye' ? 'bg-emerald-500 text-white shadow-sm' : 'text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-300'" @click="filterByPaymentStatus('paye')">Payees</button>
                        <button type="button" class="rounded-md px-3 py-1.5 text-xs font-medium transition-all" :class="form.payment === 'non_paye' ? 'bg-red-500 text-white shadow-sm' : 'text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-300'" @click="filterByPaymentStatus('non_paye')">Non payees</button>
                    </div>
                </div>

                <div class="border-t border-slate-100 dark:border-slate-700/50">
                    <nav class="flex px-4" aria-label="Tabs">
                        <button type="button" class="relative px-4 py-3 text-sm font-medium transition-colors" :class="tabClass('actives')" @click="changeTab('actives')">
                            <span class="flex items-center gap-2">
                                Actives
                                <span class="inline-flex h-5 min-w-[20px] items-center justify-center rounded-full px-1.5 text-xs font-semibold" :class="form.tab === 'actives' ? 'bg-primary-100 text-primary-700 dark:bg-primary-900/30 dark:text-primary-300' : 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-400'">{{ number(counts.countActives || counts.actives) }}</span>
                            </span>
                            <span v-if="form.tab === 'actives'" class="absolute bottom-0 left-0 right-0 h-0.5 rounded-full bg-primary-600 dark:bg-primary-400"></span>
                        </button>

                        <button type="button" class="relative px-4 py-3 text-sm font-medium transition-colors" :class="tabClass('valide')" @click="changeTab('valide')">
                            <span class="flex items-center gap-2">
                                Validees
                                <span class="inline-flex h-5 min-w-[20px] items-center justify-center rounded-full px-1.5 text-xs font-semibold" :class="form.tab === 'valide' ? 'bg-primary-100 text-primary-700 dark:bg-primary-900/30 dark:text-primary-300' : 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-400'">{{ number(counts.countValide || counts.valide) }}</span>
                            </span>
                            <span v-if="form.tab === 'valide'" class="absolute bottom-0 left-0 right-0 h-0.5 rounded-full bg-primary-600 dark:bg-primary-400"></span>
                        </button>

                        <button v-if="canAccessTrash" type="button" class="relative px-4 py-3 text-sm font-medium transition-colors" :class="tabClass('deleted')" @click="changeTab('deleted')">
                            <span class="flex items-center gap-2">
                                Corbeille
                                <span v-if="number(counts.countDeleted || counts.deleted) > 0" class="inline-flex h-5 min-w-[20px] items-center justify-center rounded-full bg-red-100 px-1.5 text-xs font-semibold text-red-700 dark:bg-red-900/30 dark:text-red-300">{{ number(counts.countDeleted || counts.deleted) }}</span>
                            </span>
                            <span v-if="form.tab === 'deleted'" class="absolute bottom-0 left-0 right-0 h-0.5 rounded-full bg-primary-600 dark:bg-primary-400"></span>
                        </button>
                    </nav>
                </div>
            </div>

            <div v-if="form.search || form.payment" class="mb-4 flex flex-wrap items-center gap-2">
                <span class="text-xs text-slate-500 dark:text-slate-400">Filtres actifs :</span>
                <span v-if="form.search" class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2.5 py-1 text-xs text-slate-700 dark:bg-slate-700 dark:text-slate-300">
                    "{{ form.search }}"
                    <button type="button" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200" @click="clearSearch">
                        <svg class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </span>
                <span v-if="form.payment" class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs" :class="form.payment === 'paye' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300' : 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300'">
                    {{ form.payment === 'paye' ? 'Payees' : 'Non payees' }}
                    <button type="button" class="opacity-60 hover:opacity-100" @click="filterByPaymentStatus('')">
                        <svg class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </span>
                <button type="button" class="text-xs text-slate-400 underline hover:text-slate-600 dark:hover:text-slate-300" @click="clearAllFilters">Tout effacer</button>
            </div>

            <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-800">
                <div class="hidden overflow-x-auto lg:block">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-slate-100 dark:border-slate-700">
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Reference</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Patient</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Prescripteur</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Analyses</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Statut</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Paiement</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Date</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50 dark:divide-slate-700/50">
                            <tr v-for="prescription in prescriptions.data" :key="prescription.id" class="group transition-colors hover:bg-slate-50/50 dark:hover:bg-slate-700/20">
                                <td class="px-4 py-3.5"><span class="text-sm font-semibold text-slate-900 dark:text-white">{{ prescription.reference }}</span></td>
                                <td class="px-4 py-3.5">
                                    <div class="flex items-center gap-2.5">
                                        <div class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-primary-400 to-primary-600">
                                            <span class="text-xs font-semibold text-white">{{ initials(prescription) }}</span>
                                        </div>
                                        <div class="min-w-0">
                                            <p class="max-w-[150px] truncate text-sm font-medium text-slate-900 dark:text-white">{{ prescription.patient?.nom_complet || 'N/A' }}</p>
                                            <p class="text-xs text-slate-400 dark:text-slate-500">{{ prescription.patient?.telephone || '-' }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3.5"><span class="text-sm text-slate-600 dark:text-slate-300">{{ truncate(prescription.prescripteur?.nom || 'N/A', 20) }}</span></td>
                                <td class="px-4 py-3.5 text-center">
                                    <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-blue-50 text-xs font-semibold text-blue-700 dark:bg-blue-900/30 dark:text-blue-300">{{ prescription.analyses_count || 0 }}</span>
                                </td>
                                <td class="px-4 py-3.5"><span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold" :class="statusClass(prescription.status)">{{ prescription.status_label }}</span></td>
                                <td class="px-4 py-3.5">
                                    <span v-if="prescription.paiement" class="inline-flex items-center gap-1 text-xs font-medium" :class="prescription.paiement.status ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-500 dark:text-red-400'">
                                        {{ prescription.paiement.status ? 'Paye' : 'Non paye' }}
                                    </span>
                                    <span v-else class="text-xs italic text-slate-400 dark:text-slate-500">Aucun</span>
                                </td>
                                <td class="px-4 py-3.5">
                                    <p class="text-sm text-slate-600 dark:text-slate-300">{{ prescription.created_at || 'N/A' }}</p>
                                    <p class="text-[11px] text-slate-400">{{ prescription.created_at_relative || '' }}</p>
                                </td>
                                <td class="px-4 py-3.5">
                                    <div class="flex items-center justify-end gap-1 opacity-0 transition-opacity group-hover:opacity-100">
                                        <Link :href="route('secretaire.prescription.edit', prescription.id)" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-slate-500 transition-colors hover:bg-slate-100 dark:hover:bg-slate-700" title="Ouvrir">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12H9m12 0A9 9 0 113 12a9 9 0 0118 0z" />
                                            </svg>
                                        </Link>
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="prescriptions.data.length === 0">
                                <td colspan="8" class="py-16 text-center">
                                    <div class="flex flex-col items-center">
                                        <div class="mb-3 flex h-11 w-11 items-center justify-center rounded-full bg-slate-100 dark:bg-slate-700">
                                            <svg class="h-5 w-5 text-slate-400 dark:text-slate-500" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375H8.25" />
                                            </svg>
                                        </div>
                                        <p class="text-sm font-medium text-slate-600 dark:text-slate-400">Aucune prescription trouvee</p>
                                        <p v-if="form.search" class="mt-1 text-xs text-slate-400 dark:text-slate-500">Essayez de modifier vos criteres de recherche</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="divide-y divide-slate-100 dark:divide-slate-700/50 lg:hidden">
                    <div v-for="prescription in prescriptions.data" :key="`m-${prescription.id}`" class="space-y-3 p-4">
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-bold text-slate-900 dark:text-white">{{ prescription.reference }}</span>
                            <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold" :class="statusClass(prescription.status)">{{ prescription.status_label }}</span>
                        </div>

                        <div class="flex items-center gap-3">
                            <div class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-primary-400 to-primary-600">
                                <span class="text-[11px] font-semibold text-white">{{ initials(prescription) }}</span>
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-medium text-slate-900 dark:text-white">{{ prescription.patient?.nom_complet || 'N/A' }}</p>
                                <p class="text-xs text-slate-400">{{ prescription.prescripteur?.nom || 'N/A' }} · {{ prescription.patient?.telephone || '-' }}</p>
                            </div>
                        </div>

                        <div class="flex items-center justify-between pt-1">
                            <div class="flex items-center gap-3 text-xs text-slate-500 dark:text-slate-400">
                                <span>{{ prescription.analyses_count || 0 }} analyses</span>
                                <span v-if="prescription.paiement" :class="prescription.paiement.status ? 'text-emerald-500' : 'text-red-500'">
                                    {{ prescription.paiement.status ? 'Paye' : 'Non paye' }}
                                </span>
                                <span>{{ prescription.created_at }}</span>
                            </div>
                            <Link :href="route('secretaire.prescription.edit', prescription.id)" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-slate-500 transition-colors hover:bg-slate-100 dark:hover:bg-slate-700" title="Ouvrir">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12H9m12 0A9 9 0 113 12a9 9 0 0118 0z" />
                                </svg>
                            </Link>
                        </div>
                    </div>

                    <div v-if="prescriptions.data.length === 0" class="py-12 text-center">
                        <p class="text-sm text-slate-500">Aucune prescription trouvee</p>
                    </div>
                </div>

                <div v-if="prescriptions.links && prescriptions.links.length > 3" class="border-t border-slate-100 bg-slate-50/50 px-4 py-3 dark:border-slate-700/50 dark:bg-slate-800/50">
                    <div class="flex flex-col items-center justify-between gap-3 sm:flex-row">
                        <p class="text-xs text-slate-500 dark:text-slate-400">
                            <span class="font-medium">{{ prescriptions.from || 0 }}</span> a
                            <span class="font-medium">{{ prescriptions.to || 0 }}</span> sur
                            <span class="font-medium">{{ prescriptions.total || 0 }}</span>
                        </p>
                        <Pagination :links="prescriptions.links" />
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { computed, reactive } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import debounce from 'lodash/debounce';
import AppLayout from '@/Layouts/AppLayout.vue';
import Pagination from '@/Components/Pagination.vue';

const page = usePage();

const props = defineProps({
    prescriptions: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    counts: { type: Object, default: () => ({}) },
});

const form = reactive({
    search: props.filters.search || '',
    tab: props.filters.tab || 'actives',
    perPage: props.filters.perPage || 10,
    payment: props.filters.payment || '',
});

const canCreate = computed(() => {
    const user = page.props.auth?.user;
    if (!user) {
        return false;
    }

    return Boolean(user.isAdmin || user.permissions?.includes('prescriptions.creer'));
});

const canAccessTrash = computed(() => {
    const user = page.props.auth?.user;
    if (!user) {
        return false;
    }

    return Boolean(user.isAdmin || user.permissions?.includes('corbeille.acceder'));
});

const totalCount = computed(() => {
    return Number(props.counts.countActives || props.counts.actives || 0)
        + Number(props.counts.countValide || props.counts.valide || 0)
        + Number(props.counts.countArchive || props.counts.archive || 0)
        + Number(props.counts.countDeleted || props.counts.deleted || 0);
});

const paymentRate = computed(() => {
    const paid = Number(props.counts.countPaye || 0);
    const unpaid = Number(props.counts.countNonPaye || 0);
    const total = paid + unpaid;

    if (total === 0) {
        return 0;
    }

    return Math.round((paid / total) * 100);
});

const applyFilters = () => {
    router.get(route('secretaire.prescription.index'), form, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

const debouncedApplyFilters = debounce(() => {
    applyFilters();
}, 300);

const changeTab = (tab) => {
    form.tab = tab;
    applyFilters();
};

const filterByPaymentStatus = (paymentStatus) => {
    form.payment = paymentStatus;
    applyFilters();
};

const clearSearch = () => {
    form.search = '';
    applyFilters();
};

const clearAllFilters = () => {
    form.search = '';
    form.payment = '';
    applyFilters();
};

const tabClass = (tab) => {
    if (form.tab === tab) {
        return 'text-primary-600 dark:text-primary-400';
    }

    return 'text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-300';
};

const statusClass = (status) => {
    switch (status) {
        case 'EN_ATTENTE':
            return 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300';
        case 'EN_COURS':
            return 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300';
        case 'TERMINE':
            return 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300';
        case 'VALIDE':
            return 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300';
        case 'ARCHIVE':
            return 'bg-slate-200 text-slate-700 dark:bg-slate-700 dark:text-slate-300';
        default:
            return 'bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-300';
    }
};

const initials = (prescription) => {
    const fullName = String(prescription.patient?.nom_complet || 'NA').trim();
    const parts = fullName.split(/\s+/).filter(Boolean);
    const first = parts[0]?.charAt(0) || 'N';
    const second = parts[1]?.charAt(0) || 'A';

    return `${first}${second}`.toUpperCase();
};

const truncate = (value, maxLength) => {
    const text = String(value || '');
    if (text.length <= maxLength) {
        return text;
    }

    return `${text.slice(0, maxLength)}...`;
};

const number = (value) => Number(value || 0).toLocaleString('fr-FR');
</script>
