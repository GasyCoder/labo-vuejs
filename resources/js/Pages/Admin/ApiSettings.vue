<script setup>
import { ref, computed, watch } from 'vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import AppLayout from '@/Layouts/AppLayout.vue';
import Modal from '@/Components/Modal.vue';

const props = defineProps({
    emailConfig: Object,
    smsDrivers: Object,
    availableDrivers: Object,
    smsProviders: Array,
});

const showToast = (page) => {
    const flash = page?.props?.flash;
    if (flash?.success) {
        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'success',
            title: flash.success,
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true,
        });
    }
    if (flash?.error) {
        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'error',
            title: flash.error,
            showConfirmButton: false,
            timer: 4000,
            timerProgressBar: true,
        });
    }
};

// Tab management
const getTabFromHash = () => {
    const hash = window.location.hash.replace('#', '');
    return ['email', 'sms', 'test'].includes(hash) ? hash : 'email';
};
const activeTab = ref(getTabFromHash());

const changeTab = (tab) => {
    activeTab.value = tab;
    window.location.hash = tab;
};

// Listen to hash changes (e.g., user presses back button)
if (typeof window !== 'undefined') {
    window.addEventListener('hashchange', () => {
        activeTab.value = getTabFromHash();
    });
}

// Email Configuration Form
const emailForm = useForm({
    mail_mailer: props.emailConfig?.mail_mailer || '',
    mail_host: props.emailConfig?.mail_host || '',
    mail_port: props.emailConfig?.mail_port || '',
    mail_username: props.emailConfig?.mail_username || '',
    mail_password: props.emailConfig?.mail_password || '',
    mail_encryption: props.emailConfig?.mail_encryption || '',
    mail_from_address: props.emailConfig?.mail_from_address || '',
    mail_from_name: props.emailConfig?.mail_from_name || '',
    resend_key: props.emailConfig?.resend_key || '',
});

const saveEmailConfig = () => {
    emailForm.post(route('admin.api-settings.update-email'), {
        preserveScroll: true,
        onSuccess: (page) => showToast(page),
    });
};

// SMS Configuration (CRUD logic)
const confirmingProviderDeletion = ref(false);
const showProviderModal = ref(false);
const editingProvider = ref(null);
const providerToDelete = ref(null);

const providerForm = useForm({
    name: '',
    driver: 'mapi',
    credentials: {},
});

const openCreateProviderModal = () => {
    editingProvider.value = null;
    providerForm.reset();
    providerForm.driver = 'mapi';
    providerForm.credentials = { ...props.smsDrivers['mapi']?.values };
    showProviderModal.value = true;
};

const openEditProviderModal = (provider) => {
    editingProvider.value = provider;
    providerForm.name = provider.name;
    providerForm.driver = provider.driver;
    providerForm.credentials = { ...provider.credentials };
    showProviderModal.value = true;
};

const closeProviderModal = () => {
    showProviderModal.value = false;
    providerForm.reset();
    providerForm.clearErrors();
};

watch(() => providerForm.driver, (newDriver) => {
    if (!editingProvider.value || editingProvider.value.driver !== newDriver) {
        providerForm.credentials = { ...props.smsDrivers[newDriver]?.values };
    }
});

const saveProvider = () => {
    if (editingProvider.value) {
        providerForm.put(route('admin.api-settings.update-sms', editingProvider.value.id), {
            preserveScroll: true,
            onSuccess: (page) => {
                closeProviderModal();
                showToast(page);
            },
        });
    } else {
        providerForm.post(route('admin.api-settings.store-sms'), {
            preserveScroll: true,
            onSuccess: (page) => {
                closeProviderModal();
                showToast(page);
            },
        });
    }
};

const confirmProviderDeletion = (provider) => {
    providerToDelete.value = provider;
    confirmingProviderDeletion.value = true;
};

const deleteProvider = () => {
    if (!providerToDelete.value) return;
    
    providerForm.delete(route('admin.api-settings.destroy-sms', providerToDelete.value.id), {
        preserveScroll: true,
        onSuccess: (page) => {
            confirmingProviderDeletion.value = false;
            providerToDelete.value = null;
            showToast(page);
        },
    });
};

const activateProvider = (provider) => {
    providerForm.post(route('admin.api-settings.activate-sms', provider.id), {
        preserveScroll: true,
        onSuccess: (page) => showToast(page),
    });
};

const currentDriverFields = computed(() => {
    const driver = props.smsDrivers?.[providerForm.driver];
    return driver?.fields || {};
});

const activeProvider = computed(() => {
    return props.smsProviders.find((p) => p.is_active);
});
const currentSmsDriverName = computed(() => {
    return activeProvider.value ? activeProvider.value.name : 'Aucun fournisseur actif';
});

// Test forms
const testSmsForm = useForm({ phone: '', message: "Ceci est un test de l'API SMS." });
const testEmailForm = useForm({ email: '', message: "Ceci est un e-mail de test de configuration SMTP." });

const testSms = () => {
    testSmsForm.post(route('admin.api-settings.test-sms'), {
        preserveScroll: true,
        onSuccess: (page) => { testSmsForm.reset(); showToast(page); },
    });
};

const testEmail = () => {
    testEmailForm.post(route('admin.api-settings.test-email'), {
        preserveScroll: true,
        onSuccess: (page) => { testEmailForm.reset(); showToast(page); },
    });
};

// Password visibility toggles
const showMailPassword = ref(false);
const passwordVisibility = ref({});

const toggleFieldVisibility = (driverKey, fieldKey) => {
    const key = `${driverKey}_${fieldKey}`;
    passwordVisibility.value[key] = !passwordVisibility.value[key];
};

const isFieldVisible = (driverKey, fieldKey) => {
    const key = `${driverKey}_${fieldKey}`;
    return !!passwordVisibility.value[key];
};
</script>

<template>
    <Head title="Configuration API, Email & SMS" />

    <AppLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <div class="flex items-center space-x-3">
                        <Link :href="route('admin.settings')" class="text-gray-400 hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-300 transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                        </Link>
                        <div>
                            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                                Configuration API, Email & SMS
                            </h2>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Gestion des services d'envoi d'emails et de SMS</p>
                        </div>
                    </div>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="px-4 sm:px-6 lg:px-8">

                <!-- Tab Navigation -->
                <div class="mb-6 border-b border-gray-200 dark:border-gray-700">
                    <nav class="-mb-px flex space-x-8">
                        <button
                            @click="changeTab('email')"
                            :class="[
                                'group inline-flex items-center py-4 px-1 border-b-2 font-medium text-sm transition-colors',
                                activeTab === 'email'
                                    ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400'
                                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'
                            ]">
                            <svg class="w-5 h-5 mr-2" :class="activeTab === 'email' ? 'text-indigo-500' : 'text-gray-400 group-hover:text-gray-500'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                            E-mail (SMTP)
                        </button>
                        <button
                            @click="changeTab('sms')"
                            :class="[
                                'group inline-flex items-center py-4 px-1 border-b-2 font-medium text-sm transition-colors',
                                activeTab === 'sms'
                                    ? 'border-green-500 text-green-600 dark:text-green-400'
                                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'
                            ]">
                            <svg class="w-5 h-5 mr-2" :class="activeTab === 'sms' ? 'text-green-500' : 'text-gray-400 group-hover:text-gray-500'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                            SMS
                        </button>
                        <button
                            @click="changeTab('test')"
                            :class="[
                                'group inline-flex items-center py-4 px-1 border-b-2 font-medium text-sm transition-colors',
                                activeTab === 'test'
                                    ? 'border-purple-500 text-purple-600 dark:text-purple-400'
                                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'
                            ]">
                            <svg class="w-5 h-5 mr-2" :class="activeTab === 'test' ? 'text-purple-500' : 'text-gray-400 group-hover:text-gray-500'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"></path></svg>
                            Tester les APIs
                        </button>
                    </nav>
                </div>

                <!-- Tab: Email Configuration -->
                <div v-show="activeTab === 'email'">
                    <form @submit.prevent="saveEmailConfig">
                        <div class="bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden border dark:border-gray-700">
                            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 bg-indigo-50 dark:bg-indigo-900/20">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center">
                                        <svg class="w-5 h-5 mr-3 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                                        <div>
                                            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Serveur E-mail (SMTP)</h3>
                                            <p class="text-sm text-gray-500 dark:text-gray-400">Configuration du serveur d'envoi d'e-mails</p>
                                        </div>
                                    </div>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800 dark:bg-indigo-900/50 dark:text-indigo-300">
                                        {{ emailForm.mail_mailer?.toUpperCase() || 'SMTP' }}
                                    </span>
                                </div>
                            </div>
                            <div class="p-6">
                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Mailer</label>
                                        <select v-model="emailForm.mail_mailer" required class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                            <option value="smtp">SMTP</option>
                                            <option value="resend">Resend API</option>
                                            <option value="mailgun">Mailgun</option>
                                            <option value="ses">Amazon SES</option>
                                            <option value="postmark">Postmark</option>
                                            <option value="sendmail">Sendmail</option>
                                            <option value="log">Log (dev)</option>
                                        </select>
                                        <p class="mt-1 text-xs text-gray-400">Type de service d'envoi</p>
                                    </div>
                                    <div v-if="emailForm.mail_mailer === 'resend'">
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Clé API Resend</label>
                                        <div class="relative">
                                            <input v-model="emailForm.resend_key" :type="isFieldVisible('resend', 'key') ? 'text' : 'password'" required class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white pr-10" placeholder="re_...">
                                            <button type="button" @click="toggleFieldVisibility('resend', 'key')" class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600">
                                                <svg v-if="!isFieldVisible('resend', 'key')" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                                <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"></path></svg>
                                            </button>
                                        </div>
                                        <p class="mt-1 text-xs text-gray-400">Votre clé API disponible sur resend.com</p>
                                    </div>
                                    <template v-if="emailForm.mail_mailer !== 'resend'">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Hote (Host)</label>
                                            <input v-model="emailForm.mail_host" type="text" required class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white" placeholder="smtp.gmail.com">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Port</label>
                                            <input v-model="emailForm.mail_port" type="number" required class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white" placeholder="587">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Utilisateur</label>
                                            <input v-model="emailForm.mail_username" type="text" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white" placeholder="user@gmail.com">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Mot de passe</label>
                                            <div class="relative">
                                                <input v-model="emailForm.mail_password" :type="showMailPassword ? 'text' : 'password'" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white pr-10">
                                                <button type="button" @click="showMailPassword = !showMailPassword" class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600">
                                                    <svg v-if="!showMailPassword" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                                    <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"></path></svg>
                                                </button>
                                            </div>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Chiffrement</label>
                                            <select v-model="emailForm.mail_encryption" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                                <option value="">Aucun</option>
                                                <option value="tls">TLS</option>
                                                <option value="ssl">SSL</option>
                                            </select>
                                        </div>
                                    </template>
                                </div>

                                <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                                    <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-4">Informations d'expedition</h4>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Adresse d'expedition</label>
                                            <input v-model="emailForm.mail_from_address" type="email" required class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white" placeholder="noreply@lareference.mg">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nom d'expedition</label>
                                            <input v-model="emailForm.mail_from_name" type="text" required class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white" placeholder="La Reference">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="px-6 py-3 bg-gray-50 dark:bg-gray-700/50 border-t border-gray-200 dark:border-gray-700 flex justify-end">
                                <button type="submit" :disabled="emailForm.processing" class="inline-flex items-center text-sm bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-2 rounded-md transition-colors disabled:opacity-50">
                                    <svg v-if="emailForm.processing" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                    Sauvegarder la configuration E-mail
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Tab: SMS Configuration -->
                <div v-show="activeTab === 'sms'">
                    <div class="bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden border dark:border-gray-700">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 bg-green-50 dark:bg-green-900/20">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <svg class="w-5 h-5 mr-3 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                                    <div>
                                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">Fournisseurs SMS</h3>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">Gérez vos fournisseurs de services SMS</p>
                                    </div>
                                </div>
                                <button type="button" @click="openCreateProviderModal" class="inline-flex items-center text-sm bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md transition-colors">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                    Ajouter un fournisseur
                                </button>
                            </div>
                        </div>
                        <div class="p-0 overflow-x-auto">
                            <div v-if="!smsProviders || smsProviders.length === 0" class="p-6 text-center text-gray-500 dark:text-gray-400">
                                Aucun fournisseur SMS configuré.
                            </div>
                            <table v-else class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-800">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Nom</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Pilote (Driver)</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Statut</th>
                                        <th scope="col" class="relative px-6 py-3"><span class="sr-only">Actions</span></th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                    <tr v-for="provider in smsProviders" :key="provider.id" :class="{'bg-green-50/30 dark:bg-green-900/10': provider.is_active}">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">{{ provider.name }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                            {{ availableDrivers[provider.driver] || provider.driver }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            <span v-if="provider.is_active" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-300">
                                                Actif
                                            </span>
                                            <span v-else class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                                                Inactif
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-3">
                                            <button type="button" v-if="!provider.is_active" @click="activateProvider(provider)" class="text-green-600 hover:text-green-900 dark:text-green-400 dark:hover:text-green-300">Activer</button>
                                            <button type="button" @click="openEditProviderModal(provider)" class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs leading-4 font-medium rounded-md text-indigo-700 bg-indigo-100 hover:bg-indigo-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:bg-indigo-900/50 dark:text-indigo-300 dark:hover:bg-indigo-800/50 transition-colors">
                                                <svg class="mr-1.5 h-3.5 w-3.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                                    <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" />
                                                </svg>
                                                Éditer
                                            </button>
                                            <button type="button" @click="confirmProviderDeletion(provider)" class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs leading-4 font-medium rounded-md text-red-700 bg-red-100 hover:bg-red-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 dark:bg-red-900/50 dark:text-red-300 dark:hover:bg-red-800/50 transition-colors">
                                                <svg class="mr-1.5 h-3.5 w-3.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                                    <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                                                </svg>
                                                Supprimer
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Tab: Test APIs -->
                <div v-show="activeTab === 'test'">
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <!-- Test E-mail -->
                        <div class="bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden border dark:border-gray-700">
                            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 bg-indigo-50 dark:bg-indigo-900/20">
                                <div class="flex items-center">
                                    <svg class="w-5 h-5 mr-3 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Tester l'E-mail</h3>
                                </div>
                            </div>
                            <form @submit.prevent="testEmail" class="p-6">
                                <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Envoyez un e-mail de test pour verifier que votre configuration SMTP fonctionne correctement.</p>
                                <div class="space-y-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Adresse E-mail de reception</label>
                                        <input v-model="testEmailForm.email" type="email" placeholder="test@example.com" required class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                        <div v-if="testEmailForm.errors.email" class="text-red-500 text-xs mt-1">{{ testEmailForm.errors.email }}</div>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Message optionnel</label>
                                        <textarea v-model="testEmailForm.message" rows="3" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"></textarea>
                                        <div v-if="testEmailForm.errors.message" class="text-red-500 text-xs mt-1">{{ testEmailForm.errors.message }}</div>
                                    </div>
                                    <button type="submit" :disabled="testEmailForm.processing" class="w-full inline-flex justify-center items-center text-sm bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2.5 rounded-md transition-colors disabled:opacity-50">
                                        <svg v-if="testEmailForm.processing" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                        {{ testEmailForm.processing ? 'Envoi en cours...' : 'Envoyer E-mail de Test' }}
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- Test SMS -->
                        <div class="bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden border dark:border-gray-700">
                            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 bg-green-50 dark:bg-green-900/20">
                                <div class="flex items-center">
                                    <svg class="w-5 h-5 mr-3 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Tester le SMS</h3>
                                    <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-300">
                                        {{ currentSmsDriverName }}
                                    </span>
                                </div>
                            </div>
                            <form @submit.prevent="testSms" class="p-6">
                                <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Envoyez un SMS de test via <strong>{{ currentSmsDriverName }}</strong> pour verifier que votre configuration fonctionne.</p>
                                <div class="space-y-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Numero de telephone</label>
                                        <input v-model="testSmsForm.phone" type="text" placeholder="+261340000000" required class="block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                        <div v-if="testSmsForm.errors.phone" class="text-red-500 text-xs mt-1">{{ testSmsForm.errors.phone }}</div>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Message Optionnel</label>
                                        <textarea v-model="testSmsForm.message" rows="3" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"></textarea>
                                        <div v-if="testSmsForm.errors.message" class="text-red-500 text-xs mt-1">{{ testSmsForm.errors.message }}</div>
                                    </div>
                                    <button type="submit" :disabled="testSmsForm.processing" class="w-full inline-flex justify-center items-center text-sm bg-green-600 hover:bg-green-700 text-white px-4 py-2.5 rounded-md transition-colors disabled:opacity-50">
                                        <svg v-if="testSmsForm.processing" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                        {{ testSmsForm.processing ? 'Envoi en cours...' : 'Envoyer SMS de Test' }}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Info box -->
                    <div class="mt-6 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                        <div class="flex">
                            <svg class="w-5 h-5 text-blue-400 mt-0.5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            <p class="text-sm text-blue-700 dark:text-blue-300">Assurez-vous d'avoir sauvegarde votre configuration avant de tester. Les messages de test seront envoyes immediatement via le fournisseur actif.</p>
                        </div>
                    </div>
                </div>

            </div>
        </div>
        <!-- Create / Edit Provider Modal -->
        <Modal :show="showProviderModal" @close="closeProviderModal" maxWidth="2xl">
            <form @submit.prevent="saveProvider">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 flex justify-between items-center">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                        {{ editingProvider ? 'Modifier le fournisseur SMS' : 'Ajouter un fournisseur SMS' }}
                    </h3>
                    <button type="button" @click="closeProviderModal" class="text-gray-400 hover:text-gray-500 focus:outline-none">
                        <span class="sr-only">Fermer</span>
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>
                
                <div class="p-6 space-y-4 max-h-[70vh] overflow-y-auto">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nom du fournisseur <span class="text-red-500">*</span></label>
                        <input v-model="providerForm.name" type="text" required class="block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white" placeholder="Mon fournisseur">
                        <div v-if="providerForm.errors.name" class="text-red-500 text-xs mt-1">{{ providerForm.errors.name }}</div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Pilote (Driver) <span class="text-red-500">*</span></label>
                        <select v-model="providerForm.driver" required class="block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            <option v-for="(label, key) in availableDrivers" :key="key" :value="key">{{ label }}</option>
                        </select>
                        <div v-if="providerForm.errors.driver" class="text-red-500 text-xs mt-1">{{ providerForm.errors.driver }}</div>
                    </div>
                    
                    <div class="border-t border-gray-200 dark:border-gray-700 pt-4 mt-4" v-if="Object.keys(currentDriverFields).length > 0">
                        <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-4">Configuration spécifique</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div v-for="(fieldConfig, fieldKey) in currentDriverFields" :key="fieldKey" :class="{ 'md:col-span-2': fieldConfig.full_width }">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    {{ fieldConfig.label }} <span v-if="fieldConfig.required" class="text-red-500">*</span>
                                </label>
                                
                                <div v-if="fieldConfig.type === 'password'" class="relative">
                                    <input v-model="providerForm.credentials[fieldKey]" :type="isFieldVisible('modal', fieldKey) ? 'text' : 'password'" :required="fieldConfig.required" :maxlength="fieldConfig.max" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white pr-10">
                                    <button type="button" @click="toggleFieldVisibility('modal', fieldKey)" class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600">
                                        <svg v-if="!isFieldVisible('modal', fieldKey)" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                        <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"></path></svg>
                                    </button>
                                </div>
                                
                                <input v-else-if="fieldConfig.type === 'url'" v-model="providerForm.credentials[fieldKey]" type="url" :required="fieldConfig.required" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white" :placeholder="fieldConfig.default">
                                
                                <input v-else v-model="providerForm.credentials[fieldKey]" type="text" :required="fieldConfig.required" :maxlength="fieldConfig.max" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                
                                <p v-if="fieldConfig.hint" class="mt-1 text-xs text-gray-400">{{ fieldConfig.hint }}</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="px-6 py-4 bg-gray-50 dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 flex justify-end space-x-3">
                    <button type="button" @click="closeProviderModal" class="inline-flex items-center text-sm px-4 py-2 border border-gray-300 shadow-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-600">
                        Annuler
                    </button>
                    <button type="submit" :disabled="providerForm.processing" class="inline-flex items-center text-sm px-4 py-2 border border-transparent font-medium rounded-md shadow-sm text-white bg-green-600 hover:bg-green-700 focus:outline-none disabled:opacity-50">
                        <svg v-if="providerForm.processing" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        Enregistrer
                    </button>
                </div>
            </form>
        </Modal>

        <!-- Delete Confirmation Modal -->
        <Modal :show="confirmingProviderDeletion" @close="confirmingProviderDeletion = false" maxWidth="sm">
            <div class="p-6">
                <div class="flex items-center justify-center mb-4 text-red-600">
                    <svg class="h-12 w-12" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                </div>
                <h3 class="text-lg font-medium text-gray-900 dark:text-white text-center">Confirmer la suppression</h3>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400 text-center">
                    Êtes-vous sûr de vouloir supprimer le fournisseur <strong>{{ providerToDelete?.name }}</strong> ? Cette action est irréversible.
                </p>
                <div class="mt-6 flex justify-center space-x-3">
                    <button type="button" @click="confirmingProviderDeletion = false" class="inline-flex justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-600">Annuler</button>
                    <button type="button" @click="deleteProvider" :disabled="providerForm.processing" class="inline-flex justify-center rounded-md border border-transparent bg-red-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-red-700 focus:outline-none disabled:opacity-50">Supprimer</button>
                </div>
            </div>
        </Modal>
    </AppLayout>
</template>
