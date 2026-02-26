<template>
<div
    class="nk-sidebar group/sidebar peer dark fixed w-72 [&.is-compact:not(.has-hover)]:w-[74px] min-h-screen max-h-screen overflow-hidden h-full start-0 top-0 z-[1031] transition-[transform,width] duration-300 -translate-x-full rtl:translate-x-full xl:translate-x-0 xl:rtl:translate-x-0 [&.sidebar-visible]:translate-x-0 [&.sidebar-visible~.sidebar-backdrop]:opacity-100 [&.sidebar-visible~.sidebar-backdrop]:visible [&.sidebar-visible~.sidebar-backdrop]:pointer-events-auto">
    <div
        class="flex items-center min-w-full w-72 h-16 border-b border-e bg-white dark:bg-gray-950 border-gray-200 dark:border-gray-900 px-6 py-3 overflow-hidden">
        <div class="-ms-1 me-4">
            <div class="hidden xl:block">
                <a href="#"
                    class="sidebar-compact-toggle *:pointer-events-none inline-flex items-center isolate relative h-9 w-9 px-1.5 before:content-[''] before:absolute before:-z-[1] before:h-5 before:w-5 hover:before:h-10 hover:before:w-10 before:rounded-full before:opacity-0 hover:before:opacity-100 before:transition-all before:duration-300 before:-translate-x-1/2  before:-translate-y-1/2 before:top-1/2 before:left-1/2 before:bg-gray-200 dark:before:bg-gray-900">
                    <em class="text-2xl text-slate-600 dark:text-slate-300 ni ni-menu"></em>
                </a>
            </div>
            <div class="xl:hidden">
                <a href="#"
                    class="sidebar-toggle *:pointer-events-none inline-flex items-center isolate relative h-9 w-9 px-1.5 before:content-[''] before:absolute before:-z-[1] before:h-5 before:w-5 hover:before:h-10 hover:before:w-10 before:rounded-full before:opacity-0 hover:before:opacity-100 before:transition-all before:duration-300 before:-translate-x-1/2  before:-translate-y-1/2 before:top-1/2 before:left-1/2 before:bg-gray-200 dark:before:bg-gray-900">
                    <em class="text-2xl text-slate-600 dark:text-slate-300 rtl:-scale-x-100 ni ni-arrow-left"></em>
                </a>
            </div>
        </div>
        <div class="relative flex flex-shrink-0">
            <Link :href="baseUrl"
                class="relative inline-flex items-center transition-opacity duration-300 h-9 group-[&.is-compact:not(.has-hover)]/sidebar:opacity-0">
                <img v-if="$page.props.settings?.logo" :src="$page.props.settings.logo" :alt="$page.props.settings?.nom_entreprise || 'Logo'" class="h-8 w-auto object-contain group-[&.is-compact:not(.has-hover)]/sidebar:hidden" />
                <span v-else
                    class="text-xl font-bold text-primary-500 whitespace-nowrap group-[&.is-compact:not(.has-hover)]/sidebar:hidden">{{ $page.props.settings?.nom_entreprise || 'La Reference' }}
                </span>
                <span
                    class="text-xl font-bold text-primary-500 hidden group-[&.is-compact:not(.has-hover)]/sidebar:block">{{ ($page.props.settings?.nom_entreprise || 'L').charAt(0) }}</span>
            </Link>
        </div>
    </div>
    <div
        class="nk-sidebar-body max-h-full relative overflow-hidden w-full bg-white dark:bg-gray-950 border-e border-gray-200 dark:border-gray-900">
        <div class="flex flex-col w-full h-[calc(100vh-3.5rem)]">
            <div class="h-full pt-3 pb-8" data-simplebar>
                <ul class="nk-menu">
                    <!-- Menu principal -->
                    <li
                        class="relative first:pt-1 pt-6 pb-1 px-4 before:absolute before:h-px before:w-full before:start-0 before:top-1/2 before:bg-gray-200 dark:before:bg-gray-900 first:before:hidden before:opacity-0 group-[&.is-compact:not(.has-hover)]/sidebar:before:opacity-100">
                        <h6
                            class="group-[&.is-compact:not(.has-hover)]/sidebar:opacity-0 text-slate-400 dark:text-slate-300 whitespace-nowrap uppercase font-bold text-xs tracking-relaxed leading-tight">
                            Menus</h6>
                    </li>
                    <template v-if="$page.props.auth.user && hasPermission('dashboard.voir')">
                        <li :class="['nk-menu-item py-0.5 group/item', { 'active': route().current('dashboard') }]">
                            <Link :href="route('dashboard')"
                                class="nk-menu-link flex relative items-center align-middle py-2.5 ps-6 pe-10 font-heading font-bold tracking-snug group">
                                <span
                                    class="font-normal tracking-normal w-8 inline-flex flex-grow-0 flex-shrink-0 text-slate-400 group-[.active]/item:text-primary-500 group-hover:text-primary-500">
                                    <em
                                        class="text-xl leading-none text-current transition-all duration-300 icon ni ni-growth"></em>
                                </span>
                                <span
                                    class="group-[&.is-compact:not(.has-hover)]/sidebar:opacity-0 flex-grow-1 inline-block whitespace-nowrap transition-all duration-300 text-sm text-slate-600 dark:text-slate-500 group-[.active]/item:text-primary-500 group-hover:text-primary-500">Tableau
                                    de board</span>
                            </Link>
                        </li>
                    </template>
                    <template v-if="$page.props.auth.user && ['superadmin', 'admin', 'secretaire'].includes($page.props.auth.user.type) && ($page.props.auth.user.type === 'superadmin' || !!$page.props.enabledFeatures?.prescriptions_tracking)">
                        <li :class="['nk-menu-item py-0.5 group/item', { 'active': route().current('admin.prescriptions-tracking.*') }]">
                            <Link :href="route('admin.prescriptions-tracking.index')"
                                class="nk-menu-link flex relative items-center align-middle py-2.5 ps-6 pe-10 font-heading font-bold tracking-snug group">
                                <span
                                    class="font-normal tracking-normal w-8 inline-flex flex-grow-0 flex-shrink-0 text-slate-400 group-[.active]/item:text-primary-500 group-hover:text-primary-500">
                                    <em
                                        class="text-xl leading-none text-current transition-all duration-300 icon ni ni-list-thumb"></em>
                                </span>
                                <span
                                    class="group-[&.is-compact:not(.has-hover)]/sidebar:opacity-0 flex-grow-1 inline-block whitespace-nowrap transition-all duration-300 text-sm text-slate-600 dark:text-slate-500 group-[.active]/item:text-primary-500 group-hover:text-primary-500">Suivi
                                    Opérationnel</span>
                            </Link>
                        </li>
                    </template>

                    <!-- Gestion Analyses merged into Dashboard -->

                    <template v-if="$page.props.auth.user && hasAnyPermission(['prescriptions.voir', 'analyses.voir', 'patients.voir', 'prescripteurs.voir'])">
                        <!-- Section Secrétaire / Gestion Prescriptions -->
                        <template v-if="hasPermission('prescriptions.voir')">
                            <li
                                class="relative first:pt-1 pt-6 pb-1 px-4 before:absolute before:h-px before:w-full before:start-0 before:top-1/2 before:bg-gray-200 dark:before:bg-gray-900 first:before:hidden before:opacity-0 group-[&.is-compact:not(.has-hover)]/sidebar:before:opacity-100">
                                <h6
                                    class="group-[&.is-compact:not(.has-hover)]/sidebar:opacity-0 text-slate-400 dark:text-slate-300 whitespace-nowrap uppercase font-bold text-xs tracking-relaxed leading-tight">
                                    Secrétaire</h6>
                            </li>

                            <li
                                :class="['nk-menu-item py-0.5 group/item', { 'active': route().current('secretaire.prescription.*') }]">
                                <Link :href="route('secretaire.prescription.index')"
                                    class="nk-menu-link flex relative items-center align-middle py-2.5 ps-6 pe-10 font-heading font-bold tracking-snug group">
                                    <span
                                        class="font-normal tracking-normal w-9 inline-flex flex-grow-0 flex-shrink-0 text-slate-400 group-[.active]/item:text-primary-500 group-hover:text-primary-500">
                                        <em
                                            class="text-2xl leading-none text-current transition-all duration-300 icon ni ni-edit-alt"></em>
                                    </span>
                                    <span
                                        class="group-[&.is-compact:not(.has-hover)]/sidebar:opacity-0 flex-grow-1 inline-block whitespace-nowrap transition-all duration-300 text-sm text-slate-600 dark:text-slate-500 group-[.active]/item:text-primary-500 group-hover:text-primary-500">Prescriptions</span>
                                </Link>
                            </li>

                            <li
                                :class="['nk-menu-item py-0 group/item', { 'active': route().current('secretaire.journal-caisse') }]">
                                <Link :href="route('secretaire.journal-caisse')"
                                    class="nk-menu-link flex relative items-center align-middle py-2 ps-5 pe-8 font-heading font-bold tracking-snug group">
                                    <span
                                        class="font-normal tracking-normal w-8 inline-flex flex-grow-0 flex-shrink-0 text-slate-400 group-[.active]/item:text-primary-500 group-hover:text-primary-500">
                                        <em
                                            class="text-xl leading-none text-current transition-all duration-300 icon ni ni-table-view"></em>
                                    </span>
                                    <span
                                        class="group-[&.is-compact:not(.has-hover)]/sidebar:opacity-0 flex-grow-1 inline-block whitespace-nowrap transition-all duration-300 text-sm text-slate-600 dark:text-slate-500 group-[.active]/item:text-primary-500 group-hover:text-primary-500">Journal
                                        Recettes</span>
                                </Link>
                            </li>

                            <li v-if="$page.props.auth.user.type === 'superadmin' || !!$page.props.enabledFeatures?.journal_decaissement"
                                :class="['nk-menu-item py-0 group/item', { 'active': route().current('secretaire.journal-decaissement') }]">
                                <Link :href="route('secretaire.journal-decaissement')"
                                    class="nk-menu-link flex relative items-center align-middle py-2 ps-5 pe-8 font-heading font-bold tracking-snug group">
                                    <span
                                        class="font-normal tracking-normal w-8 inline-flex flex-grow-0 flex-shrink-0 text-slate-400 group-[.active]/item:text-primary-500 group-hover:text-primary-500">
                                        <em
                                            class="text-xl leading-none text-current transition-all duration-300 icon ni ni-report-profit"></em>
                                    </span>
                                    <span
                                        class="group-[&.is-compact:not(.has-hover)]/sidebar:opacity-0 flex-grow-1 inline-block whitespace-nowrap transition-all duration-300 text-sm text-slate-600 dark:text-slate-500 group-[.active]/item:text-primary-500 group-hover:text-primary-500">Journal
                                        Décaissements</span>
                                </Link>
                            </li>

                            <li
                                :class="['nk-menu-item py-0 group/item', { 'active': route().current('secretaire.etiquettes') }]">
                                <Link :href="route('secretaire.etiquettes')"
                                    class="nk-menu-link flex relative items-center align-middle py-2 ps-5 pe-8 font-heading font-bold tracking-snug group">
                                    <span
                                        class="font-normal tracking-normal w-8 inline-flex flex-grow-0 flex-shrink-0 text-slate-400 group-[.active]/item:text-primary-500 group-hover:text-primary-500">
                                        <em
                                            class="text-xl leading-none text-current transition-all duration-300 icon ni ni-tag-alt-fill"></em>
                                    </span>
                                    <span
                                        class="group-[&.is-compact:not(.has-hover)]/sidebar:opacity-0 flex-grow-1 inline-block whitespace-nowrap transition-all duration-300 text-sm text-slate-600 dark:text-slate-500 group-[.active]/item:text-primary-500 group-hover:text-primary-500">Etiquettes</span>
                                </Link>
                            </li>
                        </template>

                        <template v-if="hasPermission('patients.voir')">
                            <li
                                :class="['nk-menu-item py-0 group/item', { 'active': route().current('secretaire.patients.index') || route().current('secretaire.patient.detail') }]">
                                <Link :href="route('secretaire.patients.index')"
                                    class="nk-menu-link flex relative items-center align-middle py-2 ps-5 pe-8 font-heading font-bold tracking-snug group">
                                    <span
                                        class="font-normal tracking-normal w-8 inline-flex flex-grow-0 flex-shrink-0 text-slate-400 group-[.active]/item:text-primary-500 group-hover:text-primary-500">
                                        <em
                                            class="text-xl leading-none text-current transition-all duration-300 icon ni ni-users"></em>
                                    </span>
                                    <span
                                        class="group-[&.is-compact:not(.has-hover)]/sidebar:opacity-0 flex-grow-1 inline-block whitespace-nowrap transition-all duration-300 text-sm text-slate-600 dark:text-slate-500 group-[.active]/item:text-primary-500 group-hover:text-primary-500">Patients</span>
                                </Link>
                            </li>
                        </template>

                        <template v-if="hasPermission('prescripteurs.voir')">
                            <li
                                :class="['nk-menu-item py-0 group/item', { 'active': route().current('secretaire.prescripteurs.*') }]">
                                <Link :href="route('secretaire.prescripteurs.index')"
                                    class="nk-menu-link flex relative items-center align-middle py-2 ps-5 pe-8 font-heading font-bold tracking-snug group">
                                    <span
                                        class="font-normal tracking-normal w-8 inline-flex flex-grow-0 flex-shrink-0 text-slate-400 group-[.active]/item:text-primary-500 group-hover:text-primary-500">
                                        <em
                                            class="text-xl leading-none text-current transition-all duration-300 icon ni ni-user-list"></em>
                                    </span>
                                    <span
                                        class="group-[&.is-compact:not(.has-hover)]/sidebar:opacity-0 flex-grow-1 inline-block whitespace-nowrap transition-all duration-300 text-sm text-slate-600 dark:text-slate-500 group-[.active]/item:text-primary-500 group-hover:text-primary-500">Prescripteurs</span>
                                </Link>
                            </li>
                        </template>
                    </template>

                    <!-- Technicien -->
                    <template v-if="$page.props.auth.user && hasPermission('analyses.effectuer')">
                        <li
                            :class="['nk-menu-item py-0 group/item', { 'active': route().current('technicien.*') }]">
                            <Link :href="route('technicien.index')"
                                class="nk-menu-link flex relative items-center align-middle py-2 ps-5 pe-8 font-heading font-bold tracking-snug group">
                                <span
                                    class="font-normal tracking-normal w-8 inline-flex flex-grow-0 flex-shrink-0 text-slate-400 group-[.active]/item:text-primary-500 group-hover:text-primary-500">
                                    <em
                                        class="text-xl leading-none text-current transition-all duration-300 icon ni ni-account-setting-fill"></em>
                                </span>
                                <span
                                    class="group-[&.is-compact:not(.has-hover)]/sidebar:opacity-0 flex-grow-1 inline-block whitespace-nowrap transition-all duration-300 text-sm text-slate-600 dark:text-slate-500 group-[.active]/item:text-primary-500 group-hover:text-primary-500">
                                    Technicien
                                </span>
                            </Link>
                        </li>
                    </template>

                    <!-- Biologiste -->
                    <template v-if="$page.props.auth.user && hasPermission('analyses.valider')">
                        <li
                            :class="['nk-menu-item py-0 group/item', { 'active': route().current('biologiste.*') }]">
                            <Link :href="route('biologiste.analyse.index')"
                                class="nk-menu-link flex relative items-center align-middle py-2 ps-5 pe-8 font-heading font-bold tracking-snug group">
                                <span
                                    class="font-normal tracking-normal w-8 inline-flex flex-grow-0 flex-shrink-0 text-slate-400 group-[.active]/item:text-primary-500 group-hover:text-primary-500">
                                    <em
                                        class="text-xl leading-none text-current transition-all duration-300 icon ni ni-user-check-fill"></em>
                                </span>
                                <span
                                    class="group-[&.is-compact:not(.has-hover)]/sidebar:opacity-0 flex-grow-1 inline-block whitespace-nowrap transition-all duration-300 text-sm text-slate-600 dark:text-slate-500 group-[.active]/item:text-primary-500 group-hover:text-primary-500">
                                    Biologiste
                                </span>
                            </Link>
                        </li>
                    </template>

                    <template v-if="$page.props.auth.user && hasPermission('corbeille.acceder')">
                        <li
                            :class="['nk-menu-item py-0 group/item', { 'active': route().current('admin.trace-patients') }]">
                            <Link :href="route('admin.trace-patients')"
                                class="nk-menu-link flex relative items-center align-middle py-2 ps-5 pe-8 font-heading font-bold tracking-snug group">
                                <span
                                    class="font-normal tracking-normal w-8 inline-flex flex-grow-0 flex-shrink-0 text-slate-400 group-[.active]/item:text-primary-500 group-hover:text-primary-500">
                                    <em
                                        class="text-xl leading-none text-current transition-all duration-300 icon ni ni-trash"></em>
                                </span>
                                <span
                                    class="group-[&.is-compact:not(.has-hover)]/sidebar:opacity-0 flex-grow-1 inline-block whitespace-nowrap transition-all duration-300 text-sm text-slate-600 dark:text-slate-500 group-[.active]/item:text-primary-500 group-hover:text-primary-500">
                                    Corbeille (<span id="trace-count">{{ $page.props.stats.countTrace }}</span>)
                                </span>
                            </Link>
                        </li>
                    </template>

                    <!-- Archives -->
                    <template v-if="$page.props.auth.user && hasPermission('archives.acceder')">
                        <li :class="['nk-menu-item py-0 group/item', { 'active': route().current('archives') }]">
                            <Link :href="route('archives')"
                                class="nk-menu-link flex relative items-center align-middle py-2 ps-5 pe-8 font-heading font-bold tracking-snug group">
                                <span
                                    class="font-normal tracking-normal w-8 inline-flex flex-grow-0 flex-shrink-0 text-slate-400 group-[.active]/item:text-primary-500 group-hover:text-primary-500">
                                    <em
                                        class="text-xl leading-none text-current transition-all duration-300 icon ni ni-archived"></em>
                                </span>
                                <span
                                    class="group-[&.is-compact:not(.has-hover)]/sidebar:opacity-0 flex-grow-1 inline-block whitespace-nowrap transition-all duration-300 text-sm text-slate-600 dark:text-slate-500 group-[.active]/item:text-primary-500 group-hover:text-primary-500">
                                    Archives (<span
                                        id="archive-count">{{ $page.props.stats.countArchive }}</span>)
                                </span>
                            </Link>
                        </li>
                    </template>

                    <hr class="my-4 border-0 border-t border-gray-300 dark:border-gray-800">
                    
                    <!-- Section Laboratoire -->
                    <template v-if="$page.props.auth.user && ($page.props.auth.user.isAdmin || hasPermission('laboratoire.gerer'))">
                        <li
                            class="relative first:pt-1 pt-6 pb-1 px-4 before:absolute before:h-px before:w-full before:start-0 before:top-1/2 before:bg-gray-200 dark:before:bg-gray-900 first:before:hidden before:opacity-0 group-[&.is-compact:not(.has-hover)]/sidebar:before:opacity-100">
                            <h6
                                class="group-[&.is-compact:not(.has-hover)]/sidebar:opacity-0 text-slate-400 dark:text-slate-300 whitespace-nowrap uppercase font-bold text-xs tracking-relaxed leading-tight">
                                Laboratoire</h6>
                        </li>

                        <!-- Menu Analyses -->
                        <li
                            :class="['nk-menu-item py-0 has-sub group/item', { 'active': route().current('laboratoire.analyses.*') }]">
                            <a href="#"
                                class="nk-menu-link sub nk-menu-toggle flex relative items-center align-middle py-2 ps-5 pe-8 font-heading font-bold tracking-snug group">
                                <span
                                    class="font-normal tracking-normal w-8 inline-flex flex-grow-0 flex-shrink-0 text-slate-400 group-[.active]/item:text-primary-500 group-hover:text-primary-500">
                                    <em
                                        class="text-xl leading-none text-current transition-all duration-300 icon ni ni-coins"></em>
                                </span>
                                <span
                                    class="group-[&.is-compact:not(.has-hover)]/sidebar:opacity-0 flex-grow-1 inline-block whitespace-nowrap transition-all duration-300 text-sm text-slate-600 dark:text-slate-500 group-[.active]/item:text-primary-500 group-hover:text-primary-500">Analyses</span>
                                <em
                                    class="group-[&.is-compact:not(.has-hover)]/sidebar:opacity-0 text-sm leading-none text-slate-400 group-[.active]/item:text-primary-500 absolute end-4 top-1/2 -translate-y-1/2 rtl:-scale-x-100 group-[.active]/item:rotate-90 group-[.active]/item:rtl:-rotate-90 transition-all duration-300 icon ni ni-chevron-right"></em>
                            </a>

                            <ul class="nk-menu-sub mb-1 hidden group-[&.is-compact:not(.has-hover)]/sidebar:!hidden" :style="{ display: route().current('laboratoire.analyses.*') ? 'block' : '' }">
                                <li
                                    :class="['nk-menu-item py-px sub has-sub group/sub1', { 'active': route().current('laboratoire.analyses.examens') }]">
                                    <Link :href="route('laboratoire.analyses.examens')"
                                        class="nk-menu-link flex relative items-center align-middle py-1 pe-8 ps-[calc(theme(spacing.5)+theme(spacing.8))] font-normal leading-5 text-xs tracking-normal normalcase">
                                        <span
                                            class="text-slate-600 dark:text-slate-500 group-[.active]/sub1:text-primary-500 hover:text-primary-500 whitespace-nowrap flex-grow inline-block">Examens</span>
                                    </Link>
                                </li>
                                <li
                                    :class="['nk-menu-item py-px sub has-sub group/sub1', { 'active': route().current('laboratoire.analyses.types') }]">
                                    <Link :href="route('laboratoire.analyses.types')"
                                        class="nk-menu-link flex relative items-center align-middle py-1 pe-8 ps-[calc(theme(spacing.5)+theme(spacing.8))] font-normal leading-5 text-xs tracking-normal normal-case">
                                        <span
                                            class="text-slate-600 dark:text-slate-500 group-[.active]/sub1:text-primary-500 hover:text-primary-500 whitespace-nowrap flex-grow inline-block">Types
                                            d'analyses</span>
                                    </Link>
                                </li>
                                <li
                                    :class="['nk-menu-item py-px sub has-sub group/sub1', { 'active': route().current('laboratoire.analyses.listes') }]">
                                    <Link :href="route('laboratoire.analyses.listes')"
                                        class="nk-menu-link flex relative items-center align-middle py-1 pe-8 ps-[calc(theme(spacing.5)+theme(spacing.8))] font-normal leading-5 text-xs tracking-normal normal-case">
                                        <span
                                            class="text-slate-600 dark:text-slate-500 group-[.active]/sub1:text-primary-500 hover:text-primary-500 whitespace-nowrap flex-grow inline-block">Listes
                                            Analyses</span>
                                    </Link>
                                </li>
                                <li
                                    :class="['nk-menu-item py-px sub has-sub group/sub1', { 'active': route().current('laboratoire.analyses.prelevements') }]">
                                    <Link :href="route('laboratoire.analyses.prelevements')"
                                        class="nk-menu-link flex relative items-center align-middle py-1 pe-8 ps-[calc(theme(spacing.5)+theme(spacing.8))] font-normal leading-5 text-xs tracking-normal normal-case">
                                        <span
                                            class="text-slate-600 dark:text-slate-500 group-[.active]/sub1:text-primary-500 hover:text-primary-500 whitespace-nowrap flex-grow inline-block">Prélèvements</span>
                                    </Link>
                                </li>
                            </ul>
                        </li>

                        <!-- Menu Microbiologie -->
                        <li
                            :class="['nk-menu-item py-0 has-sub group/item', { 'active': route().current('laboratoire.microbiologie.*') }]">
                            <a href="#"
                                class="nk-menu-link sub nk-menu-toggle flex relative items-center align-middle py-2 ps-5 pe-8 font-heading font-bold tracking-snug group">
                                <span
                                    class="font-normal tracking-normal w-8 inline-flex flex-grow-0 flex-shrink-0 text-slate-400 group-[.active]/item:text-primary-500 group-hover:text-primary-500">
                                    <em
                                        class="text-xl leading-none text-current transition-all duration-300 icon ni ni-coins"></em>
                                </span>
                                <span
                                    class="group-[&.is-compact:not(.has-hover)]/sidebar:opacity-0 flex-grow-1 inline-block whitespace-nowrap transition-all duration-300 text-sm text-slate-600 dark:text-slate-500 group-[.active]/item:text-primary-500 group-hover:text-primary-500">Germes</span>
                                <em
                                    class="group-[&.is-compact:not(.has-hover)]/sidebar:opacity-0 text-sm leading-none text-slate-400 group-[.active]/item:text-primary-500 absolute end-4 top-1/2 -translate-y-1/2 rtl:-scale-x-100 group-[.active]/item:rotate-90 group-[.active]/item:rtl:-rotate-90 transition-all duration-300 icon ni ni-chevron-right"></em>
                            </a>

                            <ul class="nk-menu-sub mb-1 hidden group-[&.is-compact:not(.has-hover)]/sidebar:!hidden" :style="{ display: route().current('laboratoire.microbiologie.*') ? 'block' : '' }">
                                <li
                                    :class="['nk-menu-item py-px sub has-sub group/sub1', { 'active': route().current('laboratoire.microbiologie.familles-bacteries') }]">
                                    <Link :href="route('laboratoire.microbiologie.familles-bacteries')"
                                        class="nk-menu-link flex relative items-center align-middle py-1 pe-8 ps-[calc(theme(spacing.5)+theme(spacing.8))] font-normal leading-5 text-xs tracking-normal normal-case">
                                        <span
                                            class="text-slate-600 dark:text-slate-500 group-[.active]/sub1:text-primary-500 hover:text-primary-500 whitespace-nowrap flex-grow inline-block">Familles
                                            bactéries</span>
                                    </Link>
                                </li>
                                <li
                                    :class="['nk-menu-item py-px sub has-sub group/sub1', { 'active': route().current('laboratoire.microbiologie.bacteries') }]">
                                    <Link :href="route('laboratoire.microbiologie.bacteries')"
                                        class="nk-menu-link flex relative items-center align-middle py-1 pe-8 ps-[calc(theme(spacing.5)+theme(spacing.8))] font-normal leading-5 text-xs tracking-normal normal-case">
                                        <span
                                            class="text-slate-600 dark:text-slate-500 group-[.active]/sub1:text-primary-500 hover:text-primary-500 whitespace-nowrap flex-grow inline-block">Bactéries</span>
                                    </Link>
                                </li>
                                <li
                                    :class="['nk-menu-item py-px sub has-sub group/sub1', { 'active': route().current('laboratoire.microbiologie.antibiotiques') }]">
                                    <Link :href="route('laboratoire.microbiologie.antibiotiques')"
                                        class="nk-menu-link flex relative items-center align-middle py-1 pe-8 ps-[calc(theme(spacing.5)+theme(spacing.8))] font-normal leading-5 text-xs tracking-normal normal-case">
                                        <span
                                            class="text-slate-600 dark:text-slate-500 group-[.active]/sub1:text-primary-500 hover:text-primary-500 whitespace-nowrap flex-grow inline-block">Antibiotiques</span>
                                    </Link>
                                </li>
                            </ul>
                        </li>
                    </template>

                    <!-- Section Administration -->
                    <template v-if="$page.props.auth.user && hasAnyPermission(['utilisateurs.gerer', 'parametres.gerer'])">
                        <li
                            class="relative first:pt-1 pt-6 pb-1 px-4 before:absolute before:h-px before:w-full before:start-0 before:top-1/2 before:bg-gray-200 dark:before:bg-gray-900 first:before:hidden before:opacity-0 group-[&.is-compact:not(.has-hover)]/sidebar:before:opacity-100">
                            <h6
                                class="group-[&.is-compact:not(.has-hover)]/sidebar:opacity-0 text-slate-400 dark:text-slate-300 whitespace-nowrap uppercase font-bold text-xs tracking-relaxed leading-tight">
                                Administration</h6>
                        </li>

                        <li :class="['nk-menu-item py-0 group/item', { 'active': route().current('admin.users') }]">
                            <Link :href="route('admin.users')"
                                class="nk-menu-link flex relative items-center align-middle py-2 ps-5 pe-8 font-heading font-bold tracking-snug group">
                                <span
                                    class="font-normal tracking-normal w-8 inline-flex flex-grow-0 flex-shrink-0 text-slate-400 group-[.active]/item:text-primary-500 group-hover:text-primary-500">
                                    <em
                                        class="text-xl leading-none text-current transition-all duration-300 icon ni ni-users"></em>
                                </span>
                                <span
                                    class="group-[&.is-compact:not(.has-hover)]/sidebar:opacity-0 flex-grow-1 inline-block whitespace-nowrap transition-all duration-300 text-sm text-slate-600 dark:text-slate-500 group-[.active]/item:text-primary-500 group-hover:text-primary-500">Utilisateurs</span>
                            </Link>
                        </li>

                        <template v-if="$page.props.auth.user.type === 'superadmin'">
                            <li
                                :class="['nk-menu-item py-0 group/item', { 'active': route().current('admin.permissions') }]">
                                <Link :href="route('admin.permissions')"
                                    class="nk-menu-link flex relative items-center align-middle py-2 ps-5 pe-8 font-heading font-bold tracking-snug group">
                                    <span
                                        class="font-normal tracking-normal w-8 inline-flex flex-grow-0 flex-shrink-0 text-slate-400 group-[.active]/item:text-primary-500 group-hover:text-primary-500">
                                        <em
                                            class="text-xl leading-none text-current transition-all duration-300 icon ni ni-shield-check"></em>
                                    </span>
                                    <span
                                        class="group-[&.is-compact:not(.has-hover)]/sidebar:opacity-0 flex-grow-1 inline-block whitespace-nowrap transition-all duration-300 text-sm text-slate-600 dark:text-slate-500 group-[.active]/item:text-primary-500 group-hover:text-primary-500">Permissions</span>
                                </Link>
                            </li>
                            <li
                                :class="['nk-menu-item py-0 group/item', { 'active': route().current('admin.features.*') }]">
                                <Link :href="route('admin.features.index')"
                                    class="nk-menu-link flex relative items-center align-middle py-2 ps-5 pe-8 font-heading font-bold tracking-snug group">
                                    <span
                                        class="font-normal tracking-normal w-8 inline-flex flex-grow-0 flex-shrink-0 text-slate-400 group-[.active]/item:text-primary-500 group-hover:text-primary-500">
                                        <em
                                            class="text-xl leading-none text-current transition-all duration-300 icon ni ni-star-fill"></em>
                                    </span>
                                    <span
                                        class="group-[&.is-compact:not(.has-hover)]/sidebar:opacity-0 flex-grow-1 inline-block whitespace-nowrap transition-all duration-300 text-sm text-slate-600 dark:text-slate-500 group-[.active]/item:text-primary-500 group-hover:text-primary-500">Premium / Fonctionnalités</span>
                                </Link>
                            </li>
                        </template>
                    </template>

                    <template v-if="$page.props.auth.user && hasPermission('parametres.gerer')">
                        <li :class="['nk-menu-item py-0 group/item', { 'active': route().current('admin.settings') }]">
                            <Link :href="route('admin.settings')"
                                class="nk-menu-link flex relative items-center align-middle py-2 ps-5 pe-8 font-heading font-bold tracking-snug group">
                                <span
                                    class="font-normal tracking-normal w-8 inline-flex flex-grow-0 flex-shrink-0 text-slate-400 group-[.active]/item:text-primary-500 group-hover:text-primary-500">
                                    <em
                                        class="text-xl leading-none text-current transition-all duration-300 icon ni ni-setting"></em>
                                </span>
                                <span
                                    class="group-[&.is-compact:not(.has-hover)]/sidebar:opacity-0 flex-grow-1 inline-block whitespace-nowrap transition-all duration-300 text-sm text-slate-600 dark:text-slate-500 group-[.active]/item:text-primary-500 group-hover:text-primary-500">Paramètres</span>
                            </Link>
                        </li>
                        <li :class="['nk-menu-item py-0 group/item', { 'active': route().current('admin.api-settings') }]">
                            <Link :href="route('admin.api-settings')"
                                class="nk-menu-link flex relative items-center align-middle py-2 ps-5 pe-8 font-heading font-bold tracking-snug group">
                                <span
                                    class="font-normal tracking-normal w-8 inline-flex flex-grow-0 flex-shrink-0 text-slate-400 group-[.active]/item:text-primary-500 group-hover:text-primary-500">
                                    <em
                                        class="text-xl leading-none text-current transition-all duration-300 icon ni ni-link-group"></em>
                                </span>
                                <span
                                    class="group-[&.is-compact:not(.has-hover)]/sidebar:opacity-0 flex-grow-1 inline-block whitespace-nowrap transition-all duration-300 text-sm text-slate-600 dark:text-slate-500 group-[.active]/item:text-primary-500 group-hover:text-primary-500">API & Notifications</span>
                            </Link>
                        </li>
                    </template>
                </ul>
            </div>
        </div>
    </div>
</div>
</template>

<script setup>
import { usePage, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const baseUrl = '/'; 

const auth = computed(() => page.props.auth);

const hasPermission = (permission) => {
    return auth.value?.user?.permissions?.includes(permission);
};

const hasAnyPermission = (permissions) => {
    return permissions.some(p => auth.value?.user?.permissions?.includes(p));
};
</script>
