<script setup>
import { ref, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Swal from 'sweetalert2';

const props = defineProps({
    logs: Array,
    files: Array,
    currentFile: String,
});

const searchQuery = ref('');
const filterLevel = ref('all');

const filteredLogs = computed(() => {
    return props.logs.filter(log => {
        const matchesSearch = log.message.toLowerCase().includes(searchQuery.value.toLowerCase()) || 
                             log.stack.toLowerCase().includes(searchQuery.value.toLowerCase());
        const matchesLevel = filterLevel.value === 'all' || log.level.toLowerCase() === filterLevel.value.toLowerCase();
        return matchesSearch && matchesLevel;
    });
});

const copyToClipboard = (text) => {
    navigator.clipboard.writeText(text).then(() => {
        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'success',
            title: 'Copié !',
            showConfirmButton: false,
            timer: 1500,
        });
    });
};

const handleClear = () => {
    Swal.fire({
        title: 'Vider ce log ?',
        text: `Tout le contenu de ${props.currentFile} sera effacé.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#f59e0b',
        confirmButtonText: 'Oui, vider',
        cancelButtonText: 'Annuler'
    }).then((result) => {
        if (result.isConfirmed) {
            router.post(route('admin.logs.clear', props.currentFile));
        }
    });
};

const handleDelete = (fileName = null) => {
    const target = fileName || props.currentFile;
    Swal.fire({
        title: 'Supprimer le fichier ?',
        text: `Le fichier ${target} sera définitivement supprimé.`,
        icon: 'error',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        confirmButtonText: 'Oui, supprimer',
        cancelButtonText: 'Annuler'
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('admin.logs.delete', target));
        }
    });
};

const getLevelClass = (level) => {
    switch (level.toUpperCase()) {
        case 'ERROR': case 'CRITICAL': case 'ALERT': case 'EMERGENCY':
            return 'bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-300';
        case 'WARNING':
            return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/50 dark:text-yellow-300';
        case 'INFO':
            return 'bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-300';
        default:
            return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
    }
};

const expandedLog = ref(null);
const toggleExpand = (id) => {
    expandedLog.value = expandedLog.value === id ? null : id;
};
</script>

<template>
    <Head :title="'Logs - ' + currentFile" />

    <AppLayout>
        <template #header>
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                        Fichier : <span class="text-indigo-600 dark:text-indigo-400">{{ currentFile }}</span>
                    </h2>
                </div>
                <div class="flex items-center gap-2">
                    <button @click="handleClear" class="inline-flex items-center px-4 py-2 bg-amber-500 text-white text-xs font-bold rounded-md hover:bg-amber-600 shadow-md">
                        <em class="ni ni-reload mr-1"></em> VIDER LE LOG
                    </button>
                    <a :href="route('admin.logs.download', currentFile)" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-xs font-bold rounded-md hover:bg-blue-700 shadow-md">
                        <em class="ni ni-download mr-1"></em> TÉLÉCHARGER
                    </a>
                    <button @click="handleDelete()" class="inline-flex items-center px-4 py-2 bg-red-600 text-white text-xs font-bold rounded-md hover:bg-red-700 shadow-md">
                        <em class="ni ni-trash mr-1"></em> SUPPRIMER
                    </button>
                </div>
            </div>
        </template>

        <div class="py-6 px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
                <!-- Sidebar -->
                <div class="lg:col-span-1">
                    <div class="bg-white dark:bg-gray-800 shadow rounded-lg border dark:border-gray-700">
                        <div class="px-4 py-3 border-b dark:border-gray-700 font-bold text-xs uppercase text-gray-500">Liste des fichiers</div>
                        <div class="divide-y dark:divide-gray-700 max-h-[60vh] overflow-y-auto">
                            <div v-for="file in files" :key="file.name" 
                                class="group p-3 flex items-center justify-between hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer"
                                :class="{'bg-indigo-50 dark:bg-indigo-900/20': currentFile === file.name}"
                                @click="router.get(route('admin.logs.viewer'), { file: file.name })">
                                <div class="flex flex-col min-w-0">
                                    <span class="text-sm font-medium truncate" :class="currentFile === file.name ? 'text-indigo-600 font-bold' : 'text-gray-700 dark:text-gray-300'">{{ file.name }}</span>
                                    <span class="text-[10px] text-gray-400">{{ file.size }}</span>
                                </div>
                                <button @click.stop="handleDelete(file.name)" class="opacity-0 group-hover:opacity-100 p-1 text-red-500 hover:text-red-700 transition-opacity">
                                    <em class="ni ni-trash"></em>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Main Content -->
                <div class="lg:col-span-3">
                    <div class="bg-white dark:bg-gray-800 shadow rounded-lg border dark:border-gray-700 flex flex-col h-full min-h-[60vh]">
                        <div class="p-4 border-b dark:border-gray-700 bg-gray-50/50 dark:bg-gray-900/20 flex gap-4">
                            <input v-model="searchQuery" type="text" placeholder="Rechercher..." class="flex-1 rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm">
                            <select v-model="filterLevel" class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm">
                                <option value="all">Tous niveaux</option>
                                <option value="error">Erreurs</option>
                                <option value="warning">Avertissements</option>
                                <option value="info">Infos</option>
                            </select>
                        </div>
                        <div class="flex-1 overflow-y-auto divide-y dark:divide-gray-700">
                            <div v-if="filteredLogs.length === 0" class="p-12 text-center text-gray-400 italic">Le fichier est vide.</div>
                            <div v-for="log in filteredLogs" :key="log.id" class="p-3 hover:bg-gray-50 dark:hover:bg-gray-700/20">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-2 mb-1">
                                            <span :class="['px-1.5 py-0.5 rounded text-[9px] font-bold uppercase', getLevelClass(log.level)]">{{ log.level }}</span>
                                            <span class="text-[10px] font-mono text-gray-400">{{ log.date }}</span>
                                        </div>
                                        <p class="text-xs font-medium text-gray-800 dark:text-gray-200 break-words">{{ log.message }}</p>
                                    </div>
                                    <div class="flex gap-2">
                                        <button @click="copyToClipboard(log.full)" class="p-1 text-gray-400 hover:text-indigo-500"><em class="ni ni-copy text-lg"></em></button>
                                        <button v-if="log.stack" @click="toggleExpand(log.id)" class="p-1 text-gray-400 hover:text-white"><em :class="['ni text-lg', expandedLog === log.id ? 'ni-chevron-up' : 'ni-chevron-down']"></em></button>
                                    </div>
                                </div>
                                <div v-if="expandedLog === log.id && log.stack" class="mt-3 p-3 bg-gray-900 rounded-md text-[10px] text-gray-400 font-mono overflow-x-auto border border-gray-800">
                                    <pre>{{ log.stack }}</pre>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
