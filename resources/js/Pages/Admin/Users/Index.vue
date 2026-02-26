<script setup>
import { ref, watch, computed } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import debounce from 'lodash/debounce';

const props = defineProps({
    users: Object,
    filters: Object,
    stats: Object,
    types: Object
});

const search = ref(props.filters.search || '');

// --- Modals State ---
const showEditModal = ref(false);
const showDeleteModal = ref(false);
const userBeingDeleted = ref(null);
const userBeingEdited = ref(null);

// --- Form State ---
const form = useForm({
    name: '',
    username: '',
    type: 'technicien',
    password: '',
    password_confirmation: ''
});

// --- Actions ---
const fetchUsers = debounce(() => {
    router.get(route('admin.users'), { search: search.value }, { preserveState: true, preserveScroll: true });
}, 300);

watch(search, fetchUsers);

const editUser = (user) => {
    userBeingEdited.value = user.id;
    form.clearErrors();
    form.name = user.name;
    form.username = user.username;
    form.type = user.type;
    form.password = '';
    form.password_confirmation = '';
    showEditModal.value = true;
};

const openCreateModal = () => {
    userBeingEdited.value = null;
    form.reset();
    form.clearErrors();
    showEditModal.value = true;
};

const saveUser = () => {
    if (userBeingEdited.value) {
        form.put(route('admin.users.update', userBeingEdited.value), {
            preserveScroll: true,
            onSuccess: () => {
                showEditModal.value = false;
            }
        });
    } else {
        form.post(route('admin.users.store'), {
            preserveScroll: true,
            onSuccess: () => {
                showEditModal.value = false;
                form.reset();
            }
        });
    }
};

const confirmDelete = (user) => {
    userBeingDeleted.value = user;
    showDeleteModal.value = true;
};

const deleteUser = () => {
    if (!userBeingDeleted.value) return;
    router.delete(route('admin.users.destroy', userBeingDeleted.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showDeleteModal.value = false;
            userBeingDeleted.value = null;
        }
    });
};

const getInitials = (name) => {
    if (!name) return '';
    return name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase();
};

const getRelativeTime = (isoString) => {
    if (!isoString) return '';
    const date = new Date(isoString);
    return date.toLocaleDateString();
};

// Calculate active dot color based on connection status
const isActiveSession = (lastActivity) => {
   if (!lastActivity) return false;
   const diffInSeconds = Math.floor(Date.now() / 1000) - lastActivity;
   return diffInSeconds < 300;
};
</script>

<template>
    <Head title="Gestion des Utilisateurs" />

    <AppLayout>
        <template #header>
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                <div>
                    <h2 class="font-bold text-2xl text-gray-900 dark:text-gray-100 leading-tight tracking-tight">
                        Gestion des utilisateurs
                    </h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Supervisez les accès et rôles du personnel du laboratoire</p>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                
                <!-- Statistiques -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
                    <div v-for="(label, key) in types" :key="key" 
                         class="relative overflow-hidden rounded-2xl shadow-sm border p-5 transition-all duration-300 hover:shadow-lg hover:-translate-y-1 group"
                         :class="{
                             'bg-gradient-to-br from-purple-500 to-indigo-600 border-purple-400/50 text-white': key === 'superadmin',
                             'bg-gradient-to-br from-blue-500 to-cyan-500 border-blue-400/50 text-white': key === 'admin',
                             'bg-gradient-to-br from-sky-500 to-blue-600 border-sky-400/50 text-white': key === 'secretaire',
                             'bg-gradient-to-br from-emerald-500 to-teal-600 border-emerald-400/50 text-white': key === 'technicien',
                             'bg-gradient-to-br from-amber-500 to-orange-500 border-amber-400/50 text-white': key === 'biologiste',
                         }">
                         <div class="relative z-10 flex flex-col h-full justify-between">
                             <div class="flex justify-between items-start mb-2">
                                 <h4 class="font-bold text-white/90 text-xs uppercase tracking-wider">{{ label }}</h4>
                                 <div class="p-2 bg-white/20 rounded-xl backdrop-blur-md shadow-sm">
                                     <svg v-if="key === 'superadmin'" class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                                     <svg v-else-if="key === 'admin'" class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                                     <svg v-else-if="key === 'secretaire'" class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                     <svg v-else-if="key === 'technicien'" class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"></path></svg>
                                     <svg v-else-if="key === 'biologiste'" class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                 </div>
                             </div>
                             <div class="mt-4 flex items-baseline gap-2">
                                 <span class="text-4xl font-extrabold text-white tracking-tight">{{ stats[key] || 0 }}</span>
                                 <span class="text-sm text-white/80 font-medium">utilisateurs</span>
                             </div>
                         </div>
                         <div class="absolute -right-4 -bottom-4 opacity-[0.25] transform rotate-12 transition-transform duration-500 group-hover:rotate-0 group-hover:scale-110 pointer-events-none">
                            <svg class="w-24 h-24 text-white" fill="currentColor" viewBox="0 0 24 24">
                                <path v-if="key === 'superadmin'" d="M12 2L2 7l10 5 10-5-10-5zm0 13l-10-5v2l10 5 10-5v-2l-10 5z"></path>
                                <path v-else-if="key === 'admin'" d="M12 14l9-5-9-5-9 5 9 5z"></path>
                                <path v-else-if="key === 'secretaire'" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                <path v-else-if="key === 'technicien'" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"></path>
                                <circle v-else cx="12" cy="12" r="10"></circle>
                            </svg>
                         </div>
                    </div>
                </div>

                <!-- Liste -->
                <div class="bg-white dark:bg-gray-800 shadow sm:rounded-2xl overflow-hidden border border-gray-200 dark:border-gray-700">
                    <div class="px-6 py-5 border-b border-gray-200 dark:border-gray-700 flex flex-col sm:flex-row justify-between items-center gap-4 bg-white dark:bg-gray-800">
                        <div class="relative w-full sm:w-96">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                            </div>
                            <input 
                                v-model="search"
                                type="text" 
                                placeholder="Rechercher par nom ou identifiant..."
                                class="block w-full pl-10 pr-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg leading-5 bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 sm:text-sm transition-colors"
                            >
                        </div>
                        <button 
                            @click="openCreateModal"
                            class="w-full sm:w-auto inline-flex items-center justify-center px-4 py-2 bg-primary-600 border border-transparent rounded-lg font-bold text-sm text-white hover:bg-primary-700 focus:bg-primary-700 active:bg-primary-900 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 transition-all shadow-sm"
                        >
                            <svg class="w-5 h-5 mr-2 -ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                            Ajouter un utilisateur
                        </button>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-800/80 uppercase text-xs">
                                <tr>
                                    <th class="px-6 py-3 text-left font-medium text-gray-500 dark:text-gray-400 tracking-wider">Utilisateur</th>
                                    <th class="px-6 py-3 text-left font-medium text-gray-500 dark:text-gray-400 tracking-wider">Type</th>
                                    <th class="px-6 py-3 text-left font-medium text-gray-500 dark:text-gray-400 tracking-wider">Identifiant</th>
                                    <th class="px-6 py-3 text-left font-medium text-gray-500 dark:text-gray-400 tracking-wider">Statut</th>
                                    <th class="px-6 py-3 text-left font-medium text-gray-500 dark:text-gray-400 tracking-wider">Permissions</th>
                                    <th class="px-6 py-3 text-left font-medium text-gray-500 dark:text-gray-400 tracking-wider flex-shrink-0 w-24">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                <tr v-for="user in users.data" :key="user.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="h-10 w-10 flex-shrink-0">
                                                <div class="h-10 w-10 rounded-full flex items-center justify-center relative"
                                                    :class="isActiveSession(user.session_status?.last_activity) ? 'bg-green-100 text-green-700 dark:bg-green-900/50 dark:text-green-300' : 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/50 dark:text-indigo-300'"
                                                >
                                                    <span class="text-sm font-semibold">{{ getInitials(user.name) }}</span>
                                                    <!-- Green indicator dot for active -->
                                                    <span v-if="isActiveSession(user.session_status?.last_activity)" class="absolute bottom-0 right-0 block h-2.5 w-2.5 rounded-full ring-2 ring-white dark:ring-gray-800" :class="'bg-' + (user.session_status?.color || 'gray-400')"></span>
                                                </div>
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-medium text-gray-900 dark:text-white">{{ user.name }}</div>
                                                <div class="text-xs text-gray-500 dark:text-gray-400">Créé le {{ getRelativeTime(user.created_at) }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex px-3 py-1 text-xs font-bold rounded-full border"
                                            :class="{
                                                'bg-purple-100 text-purple-800 border-purple-200 dark:bg-purple-900/40 dark:text-purple-300 dark:border-purple-800/50': user.type === 'superadmin',
                                                'bg-blue-100 text-blue-800 border-blue-200 dark:bg-blue-900/40 dark:text-blue-300 dark:border-blue-800/50': user.type === 'admin',
                                                'bg-sky-100 text-sky-800 border-sky-200 dark:bg-sky-900/40 dark:text-sky-300 dark:border-sky-800/50': user.type === 'secretaire',
                                                'bg-emerald-100 text-emerald-800 border-emerald-200 dark:bg-emerald-900/40 dark:text-emerald-300 dark:border-emerald-800/50': user.type === 'technicien',
                                                'bg-amber-100 text-amber-800 border-amber-200 dark:bg-amber-900/40 dark:text-amber-300 dark:border-amber-800/50': user.type === 'biologiste'
                                            }"
                                        >
                                            {{ types[user.type] || user.type }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">
                                        {{ user.username }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <div v-if="user.session_status" class="flex flex-col">
                                            <div class="flex items-center">
                                                <span class="h-2 w-2 rounded-full mr-2" :class="'bg-' + user.session_status.color"></span>
                                                <span :class="user.session_status.text_color">{{ user.session_status.text }}</span>
                                            </div>
                                            <span v-if="user.session_status.show_date" class="text-xs text-gray-400 dark:text-gray-500 ml-4 mt-0.5">
                                                {{ user.session_status.last_activity_formatted }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                        <div class="flex flex-wrap gap-1">
                                             <span v-for="role in (user.roles_array || [])" :key="role" class="px-1.5 py-0.5 border border-gray-200 dark:border-gray-600 rounded text-xs">
                                                {{ role }}
                                             </span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <div class="flex items-center space-x-3">
                                            <button v-if="user.type !== 'superadmin' || $page.props.auth.user.type === 'superadmin'" @click="editUser(user)" class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300 transition-colors" title="Modifier">
                                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                            </button>
                                            <button v-if="(user.type !== 'superadmin') || ($page.props.auth.user.type === 'superadmin' && stats.superadmin > 1)" @click="confirmDelete(user)" class="text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300 transition-colors" title="Supprimer">
                                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="users.data.length === 0">
                                    <td colspan="6" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z" />
                                        </svg>
                                        <p class="mt-4 text-sm">Aucun utilisateur trouvé.</p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination Component -->
                    <div v-if="users.links && users.links.length > 3" class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800/50">
                        <div class="flex flex-wrap -mb-1">
                            <template v-for="(link, p) in users.links" :key="p">
                                <div v-if="link.url === null" class="mr-1 mb-1 px-4 py-2 text-sm leading-4 text-gray-400 border rounded dark:border-gray-700" v-html="link.label" />
                                <button v-else @click="router.visit(link.url, {preserveState:true, preserveScroll:true})" class="mr-1 mb-1 px-4 py-2 text-sm leading-4 border rounded hover:bg-gray-100 focus:border-indigo-500 focus:text-indigo-500 dark:hover:bg-gray-700 dark:border-gray-600" :class="{ 'bg-indigo-50 text-indigo-700 border-indigo-200 dark:bg-indigo-900/30 dark:text-indigo-300 dark:border-indigo-700': link.active }" v-html="link.label" />
                            </template>
                        </div>
                    </div>
                </div>

                <!-- Create/Edit Modal -->
                <div v-if="showEditModal" class="fixed inset-0 overflow-y-auto z-50">
                    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                        <div class="fixed inset-0 transition-opacity" aria-hidden="true" @click="showEditModal = false">
                            <div class="absolute inset-0 bg-gray-500 dark:bg-gray-900 opacity-75"></div>
                        </div>
                        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                        <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full border dark:border-gray-700">
                            <form @submit.prevent="saveUser">
                                <div class="px-6 py-5">
                                    <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white mb-4">
                                        {{ userBeingEdited ? 'Modifier l\'utilisateur' : 'Ajouter un utilisateur' }}
                                    </h3>
                                    
                                    <div class="space-y-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nom complet</label>
                                            <input v-model="form.name" type="text" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:text-white sm:text-sm" required>
                                            <div v-if="form.errors.name" class="text-red-500 text-xs mt-1">{{ form.errors.name }}</div>
                                        </div>
                                        
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nom d'utilisateur</label>
                                            <input v-model="form.username" type="text" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:text-white sm:text-sm" required>
                                            <div v-if="form.errors.username" class="text-red-500 text-xs mt-1">{{ form.errors.username }}</div>
                                        </div>

                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Type de profil/rôle</label>
                                            <select v-model="form.type" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:text-white sm:text-sm" required>
                                                <option v-for="(label, key) in types" :key="key" :value="key">{{ label }}</option>
                                            </select>
                                            <div v-if="form.errors.type" class="text-red-500 text-xs mt-1">{{ form.errors.type }}</div>
                                        </div>

                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                                Mot de passe <span v-if="userBeingEdited" class="text-xs text-gray-500 font-normal">(Laisser vide pour ne pas modifier)</span>
                                            </label>
                                            <input v-model="form.password" type="password" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:text-white sm:text-sm" :required="!userBeingEdited">
                                            <div v-if="form.errors.password" class="text-red-500 text-xs mt-1">{{ form.errors.password }}</div>
                                        </div>

                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Confirmer le mot de passe</label>
                                            <input v-model="form.password_confirmation" type="password" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:text-white sm:text-sm" :required="(!userBeingEdited) || form.password.length > 0">
                                        </div>
                                    </div>
                                </div>
                                <div class="bg-gray-50 dark:bg-gray-800/80 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse rounded-b-lg border-t dark:border-gray-700">
                                    <button type="submit" :disabled="form.processing" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50">
                                        {{ userBeingEdited ? 'Mettre à jour' : 'Enregistrer' }}
                                    </button>
                                    <button type="button" @click="showEditModal = false" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-700 text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                                        Annuler
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Delete Confirmation Modal -->
                <div v-if="showDeleteModal" class="fixed inset-0 overflow-y-auto z-50">
                    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                        <div class="fixed inset-0 transition-opacity" aria-hidden="true" @click="showDeleteModal = false">
                            <div class="absolute inset-0 bg-gray-500 dark:bg-gray-900 opacity-75"></div>
                        </div>
                        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                        <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full border dark:border-gray-700">
                            <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                                <div class="sm:flex sm:items-start">
                                    <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 dark:bg-red-900/40 sm:mx-0 sm:h-10 sm:w-10">
                                        <svg class="h-6 w-6 text-red-600 dark:text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                        </svg>
                                    </div>
                                    <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                                        <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white" id="modal-title">Supprimer l'utilisateur</h3>
                                        <div class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                                            <p>Êtes-vous sûr de vouloir supprimer l'utilisateur <strong>{{ userBeingDeleted?.name }}</strong> ? Cette action est irréversible.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="bg-gray-50 dark:bg-gray-800/80 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse border-t dark:border-gray-700">
                                <button type="button" @click="deleteUser" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none sm:ml-3 sm:w-auto sm:text-sm">
                                    Confirmer la suppression
                                </button>
                                <button type="button" @click="showDeleteModal = false" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-700 text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                                    Annuler
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </AppLayout>
</template>
