<script setup>
import { ref, computed, watch, nextTick } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import debounce from 'lodash/debounce';

const props = defineProps({
    roles: Array,
    selectedRole: String,
    permissionsMap: Object,
    rolePermissions: Object
});

// Search string
const search = ref('');

// State to keep track of permissions locally before submitting
const currentPermissions = ref({ ...props.rolePermissions });
const originalPermissions = ref({ ...props.rolePermissions });

// Auto-save state
const saving = ref(false);
const saved = ref(false);
let savedTimeout = null;

// Keep track of which modules are expanded (all expanded by default)
const openModules = ref({});
Object.keys(props.permissionsMap).forEach(key => {
    openModules.value[key] = true;
});

// Computed properties
const hasChanges = computed(() => {
    return JSON.stringify(currentPermissions.value) !== JSON.stringify(originalPermissions.value);
});

const totalPermissions = computed(() => Object.keys(currentPermissions.value).length);
const enabledCount = computed(() => Object.values(currentPermissions.value).filter(val => val).length);

const groupedFiltered = computed(() => {
    if (!search.value) return props.permissionsMap;
    
    const s = search.value.toLowerCase();
    const filtered = {};
    
    for (const [module, perms] of Object.entries(props.permissionsMap)) {
        const matched = {};
        for (const [key, meta] of Object.entries(perms)) {
            if (
                key.toLowerCase().includes(s) ||
                meta.label.toLowerCase().includes(s) ||
                meta.module.toLowerCase().includes(s) ||
                (meta.description && meta.description.toLowerCase().includes(s))
            ) {
                matched[key] = meta;
            }
        }
        if (Object.keys(matched).length > 0) {
            filtered[module] = matched;
        }
    }
    return filtered;
});

// Auto-save function
const autoSave = () => {
    if (!hasChanges.value) return;
    saving.value = true;
    saved.value = false;

    router.put(route('admin.permissions.update'), {
        role: props.selectedRole,
        permissions: currentPermissions.value
    }, {
        preserveScroll: true,
        onSuccess: () => {
            originalPermissions.value = { ...currentPermissions.value };
            saving.value = false;
            saved.value = true;
            clearTimeout(savedTimeout);
            savedTimeout = setTimeout(() => { saved.value = false; }, 2000);
        },
        onError: () => {
            saving.value = false;
        }
    });
};

const debouncedAutoSave = debounce(autoSave, 500);

// Watch for permission changes and auto-save
watch(currentPermissions, () => {
    debouncedAutoSave();
}, { deep: true });

// Methods
const selectRole = (roleName) => {
    router.get(route('admin.permissions'), { role: roleName }, { preserveState: true, preserveScroll: true });
};

// Listen to props changes for new roles
watch(() => props.rolePermissions, (newPerms) => {
    currentPermissions.value = { ...newPerms };
    originalPermissions.value = { ...newPerms };
}, { deep: true });

const togglePermission = (key) => {
    currentPermissions.value[key] = !currentPermissions.value[key];
};

const toggleModule = (moduleName) => {
    const keys = Object.keys(props.permissionsMap[moduleName]);
    const allEnabled = keys.every(key => currentPermissions.value[key]);
    
    keys.forEach(key => {
        currentPermissions.value[key] = !allEnabled;
    });
};

const enableAll = () => {
    for (const key in currentPermissions.value) {
        currentPermissions.value[key] = true;
    }
};

const disableAll = () => {
    for (const key in currentPermissions.value) {
        currentPermissions.value[key] = false;
    }
};

// Auto format tools
const getIconForModule = (moduleName, allOn) => {
    switch (moduleName) {
        case 'Prescriptions': return '<svg class="w-5 h-5 ' + (allOn ? 'text-primary-500' : 'text-gray-400 dark:text-gray-500') + '" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>';
        case 'Analyses': return '<svg class="w-5 h-5 ' + (allOn ? 'text-primary-500' : 'text-gray-400 dark:text-gray-500') + '" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" /></svg>';
        case 'Patients': return '<svg class="w-5 h-5 ' + (allOn ? 'text-primary-500' : 'text-gray-400 dark:text-gray-500') + '" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" /></svg>';
        case 'Prescripteurs': return '<svg class="w-5 h-5 ' + (allOn ? 'text-primary-500' : 'text-gray-400 dark:text-gray-500') + '" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>';
        case 'Laboratoire': return '<svg class="w-5 h-5 ' + (allOn ? 'text-primary-500' : 'text-gray-400 dark:text-gray-500') + '" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>';
        case 'Administration': return '<svg class="w-5 h-5 ' + (allOn ? 'text-primary-500' : 'text-gray-400 dark:text-gray-500') + '" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>';
        case 'Archives': return '<svg class="w-5 h-5 ' + (allOn ? 'text-primary-500' : 'text-gray-400 dark:text-gray-500') + '" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" /></svg>';
        default: return '<svg class="w-5 h-5 ' + (allOn ? 'text-primary-500' : 'text-gray-400 dark:text-gray-500') + '" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" /></svg>';
    }
};

const getModuleKeys = (moduleName) => {
    return Object.keys(props.permissionsMap[moduleName] || {});
};

const getModuleEnabledCount = (moduleName) => {
    const keys = getModuleKeys(moduleName);
    return keys.filter(key => currentPermissions.value[key]).length;
};
</script>

<template>
    <Head title="Gestion des Permissions" />

    <AppLayout>
        <div>
            <!-- ── Page Header ──────────────────────────────────────── -->
            <div class="px-4 sm:px-6 lg:px-8 pt-6 pb-4">
                <!-- Row 1: Title + Role tabs -->
                <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4 mb-5">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-white tracking-tight">Permissions</h1>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Configurez les droits d'accès pour chaque rôle</p>
                    </div>

                    <!-- Role Tabs -->
                    <div class="flex items-center gap-2 flex-wrap">
                        <button
                            v-for="role in roles"
                            :key="role.name"
                            @click="selectRole(role.name)"
                            class="relative px-4 py-2 rounded-lg text-sm font-semibold transition-all duration-200"
                            :class="selectedRole === role.name ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-500/25' : 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700'"
                        >
                            <span class="capitalize">{{ role.name }}</span>
                        </button>
                    </div>
                </div>

                <!-- Row 2: Search + counters + actions (toolbar) -->
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-3">
                    <!-- Search -->
                    <div class="relative w-full sm:w-72">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-4 w-4 text-gray-400 dark:text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                        <input
                            type="text"
                            v-model="search"
                            placeholder="Rechercher une permission..."
                            class="w-full pl-10 pr-4 py-2 text-sm bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent dark:text-white placeholder-gray-400 dark:placeholder-gray-500"
                        >
                    </div>

                    <div class="flex items-center gap-3 flex-wrap">
                        <!-- Counter badge -->
                        <div class="flex items-center gap-2 px-3 py-1.5 bg-gray-100 dark:bg-gray-700 rounded-full">
                            <div class="w-2 h-2 rounded-full" :class="enabledCount > 0 ? 'bg-emerald-500' : 'bg-gray-400 dark:bg-gray-600'"></div>
                            <span class="text-xs font-bold text-gray-700 dark:text-gray-300">
                                {{ enabledCount }} / {{ totalPermissions }}
                            </span>
                            <span class="text-xs text-gray-500 dark:text-gray-400">activées</span>
                        </div>

                        <!-- Auto-save status -->
                        <div v-if="saving" class="flex items-center gap-1.5 px-3 py-1.5 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800/50 rounded-full">
                            <svg class="w-3.5 h-3.5 text-blue-500 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                            <span class="text-xs font-medium text-blue-700 dark:text-blue-400">Enregistrement...</span>
                        </div>
                        <div v-else-if="saved" class="flex items-center gap-1.5 px-3 py-1.5 bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800/50 rounded-full transition-opacity">
                            <svg class="w-3.5 h-3.5 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            <span class="text-xs font-medium text-emerald-700 dark:text-emerald-400">Enregistré</span>
                        </div>

                        <!-- Action buttons -->
                        <div class="flex items-center gap-1.5">
                            <button
                                @click="enableAll"
                                class="px-3 py-1.5 text-xs font-medium text-gray-600 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg transition-colors"
                                title="Tout activer"
                            >
                                Tout
                            </button>
                            <button
                                @click="disableAll"
                                class="px-3 py-1.5 text-xs font-medium text-gray-600 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg transition-colors"
                                title="Tout désactiver"
                            >
                                Aucun
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ── Module Cards ───────────────────────────────────────── -->
            <div class="px-4 sm:px-6 lg:px-8 py-6 pb-24 space-y-4">
                <template v-if="Object.keys(groupedFiltered).length > 0">
                    <div v-for="(permissions, module) in groupedFiltered" :key="module" class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 shadow-sm transition-shadow duration-200 overflow-hidden">
                        
                        <!-- Module Header -->
                        <div
                            class="flex items-center justify-between px-6 py-4 cursor-pointer select-none group"
                            @click="openModules[module] = !openModules[module]"
                        >
                            <div class="flex items-center gap-4">
                                <!-- Module icon -->
                                <div class="flex items-center justify-center w-10 h-10 rounded-xl transition-colors duration-200"
                                    :class="getModuleEnabledCount(module) === Object.keys(permissionsMap[module] || {}).length ? 'bg-indigo-100 dark:bg-indigo-900/30' : 'bg-gray-100 dark:bg-gray-800'"
                                    v-html="getIconForModule(module, getModuleEnabledCount(module) === Object.keys(permissionsMap[module] || {}).length)"
                                >
                                </div>

                                <div>
                                    <h3 class="text-sm font-bold text-gray-900 dark:text-white">{{ module }}</h3>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ Object.keys(permissionsMap[module] || {}).length }} permission{{ Object.keys(permissionsMap[module] || {}).length > 1 ? 's' : '' }}</p>
                                </div>
                            </div>

                            <div class="flex items-center gap-3">
                                <!-- Module counter -->
                                <span class="text-xs font-bold px-2 py-0.5 rounded-full"
                                    :class="getModuleEnabledCount(module) === Object.keys(permissionsMap[module] || {}).length ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400' : (getModuleEnabledCount(module) > 0 ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400' : 'bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-400')">
                                    {{ getModuleEnabledCount(module) }}/{{ Object.keys(permissionsMap[module] || {}).length }}
                                </span>

                                <!-- Module master toggle -->
                                <button
                                    @click.stop="toggleModule(module)"
                                    class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900"
                                    :class="getModuleEnabledCount(module) === Object.keys(permissionsMap[module] || {}).length ? 'bg-indigo-600' : 'bg-gray-300 dark:bg-gray-600'"
                                    :title="getModuleEnabledCount(module) === Object.keys(permissionsMap[module] || {}).length ? 'Désactiver le module' : 'Activer le module'"
                                >
                                    <span class="inline-block h-4 w-4 transform rounded-full bg-white shadow-sm transition-transform duration-200" :class="getModuleEnabledCount(module) === Object.keys(permissionsMap[module] || {}).length ? 'translate-x-[22px]' : 'translate-x-[2px]'"></span>
                                </button>

                                <!-- Chevron -->
                                <svg class="w-5 h-5 text-gray-400 dark:text-gray-500 transition-transform duration-200"
                                     :class="openModules[module] ? 'rotate-180' : ''"
                                     fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </div>
                        </div>

                        <!-- Permission Grid (collapsible) -->
                        <div v-show="openModules[module]" class="border-t border-gray-100 dark:border-gray-800 bg-gray-50/30 dark:bg-gray-900/50 p-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                                <div v-for="(meta, key) in permissions" :key="key" 
                                     @click="togglePermission(key)"
                                     class="flex flex-col justify-between p-4 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm hover:shadow-md hover:border-indigo-300 dark:hover:border-indigo-500/50 transition-all duration-200 cursor-pointer group">
                                    
                                    <div class="flex items-start justify-between mb-3">
                                        <div class="flex flex-col gap-1 pr-4">
                                            <span class="text-sm font-semibold text-gray-900 dark:text-white group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors">{{ meta.label }}</span>
                                            <code class="self-start px-1.5 py-0.5 text-[10px] font-mono font-medium text-gray-500 dark:text-gray-400 bg-gray-100 dark:bg-gray-900 rounded border border-gray-200 dark:border-gray-700">{{ key }}</code>
                                        </div>
                                        
                                        <!-- Toggle Button -->
                                        <button
                                            @click.stop="togglePermission(key)"
                                            class="relative inline-flex h-6 w-11 flex-shrink-0 items-center rounded-full transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900"
                                            :class="currentPermissions[key] ? 'bg-indigo-600' : 'bg-gray-300 dark:bg-gray-600'"
                                        >
                                            <span class="inline-block h-4 w-4 transform rounded-full bg-white shadow-sm transition-transform duration-200" :class="currentPermissions[key] ? 'translate-x-[22px]' : 'translate-x-[2px]'"></span>
                                        </button>
                                    </div>
                                    
                                    <p v-if="meta.description" class="text-xs text-gray-500 dark:text-gray-400 leading-relaxed">{{ meta.description }}</p>
                                    <p v-else class="text-xs text-gray-400 dark:text-gray-500 italic">Aucune description disponible.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
                <div v-else class="flex flex-col items-center justify-center py-16 text-gray-400 dark:text-gray-500 bg-white dark:bg-gray-900 rounded-xl border border-dashed border-gray-300 dark:border-gray-700">
                    <svg class="w-12 h-12 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <p class="text-sm font-medium">Aucune permission trouvée pour "{{ search }}"</p>
                    <button @click="search = ''" class="mt-2 text-xs text-indigo-500 dark:text-indigo-400 hover:underline">Effacer la recherche</button>
                </div>
            </div>

            <!-- ── Floating save bar (mobile friendly) ────────────────── -->
            <div v-show="hasChanges" class="fixed bottom-6 left-1/2 -translate-x-1/2 z-50 transition-all duration-300">
                <div class="flex items-center gap-3 px-5 py-3 bg-gray-900 dark:bg-gray-100 rounded-2xl shadow-2xl shadow-gray-900/20">
                    <div class="w-2 h-2 rounded-full bg-amber-400 animate-pulse"></div>
                    <span class="text-sm font-medium text-white dark:text-gray-900 whitespace-nowrap">Modifications non enregistrées</span>
                    <button
                        @click="savePermissions"
                        class="px-4 py-1.5 min-w-[120px] text-sm font-bold text-gray-900 dark:text-white bg-white dark:bg-gray-900 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
                    >
                        Enregistrer
                    </button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
