<template>
    <Head title="Mon Profil" />
    <AppLayout>
        <div class="py-6">
            <div class="max-w-3xl mx-auto space-y-6">
                <!-- Update Profile Information -->
                <div class="p-6 bg-white dark:bg-slate-800 shadow-sm rounded-lg border border-gray-200 dark:border-gray-700">
                    <header class="mb-6">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                            Informations du profil
                        </h2>
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                            Mettez à jour les informations de votre profil et votre adresse email.
                        </p>
                    </header>

                    <form @submit.prevent="updateProfile" class="space-y-4">
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nom</label>
                            <input
                                id="name"
                                v-model="profileForm.name"
                                type="text"
                                class="block w-full box-border text-sm leading-4.5 px-4 py-2.5 h-11 text-slate-700 dark:text-white placeholder-slate-300 bg-white dark:bg-gray-950 border border-gray-200 dark:border-gray-800 outline-none focus:border-primary-500 focus:dark:border-primary-600 rounded-md transition-all"
                                required
                                autocomplete="name"
                            />
                            <div v-if="profileForm.errors.name" class="mt-2 text-sm text-red-600">{{ profileForm.errors.name }}</div>
                        </div>

                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Email</label>
                            <input
                                id="email"
                                v-model="profileForm.email"
                                type="email"
                                class="block w-full box-border text-sm leading-4.5 px-4 py-2.5 h-11 text-slate-700 dark:text-white placeholder-slate-300 bg-white dark:bg-gray-950 border border-gray-200 dark:border-gray-800 outline-none focus:border-primary-500 focus:dark:border-primary-600 rounded-md transition-all"
                                required
                                autocomplete="username"
                            />
                            <div v-if="profileForm.errors.email" class="mt-2 text-sm text-red-600">{{ profileForm.errors.email }}</div>

                            <div v-if="mustVerifyEmail && !user.email_verified_at">
                                <p class="text-sm mt-2 text-gray-800 dark:text-gray-200">
                                    Votre adresse email n'est pas vérifiée.
                                    <button
                                        type="button"
                                        @click="sendVerification"
                                        class="underline text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 rounded-md transition-colors"
                                    >
                                        Cliquez ici pour renvoyer l'email de vérification.
                                    </button>
                                </p>
                                <p v-if="verificationSent" class="mt-2 font-medium text-sm text-green-600 dark:text-green-400">
                                    Un nouveau lien de vérification a été envoyé.
                                </p>
                            </div>
                        </div>

                        <div class="flex items-center gap-4">
                            <button
                                type="submit"
                                :disabled="profileForm.processing"
                                class="inline-flex items-center px-4 py-2 border border-primary-600 rounded-md font-semibold text-sm text-white bg-primary-600 hover:bg-primary-700 active:bg-primary-800 transition-all duration-300 disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                Enregistrer
                            </button>
                            <Transition enter-from-class="opacity-0" leave-to-class="opacity-0" class="transition ease-in-out">
                                <p v-if="profileForm.recentlySuccessful" class="text-sm text-gray-600 dark:text-gray-400">Enregistré.</p>
                            </Transition>
                        </div>
                    </form>
                </div>

                <!-- Update Password -->
                <div class="p-6 bg-white dark:bg-slate-800 shadow-sm rounded-lg border border-gray-200 dark:border-gray-700">
                    <header class="mb-6">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                            Mise à jour du mot de passe
                        </h2>
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                            Assurez-vous d'utiliser un mot de passe long et aléatoire pour rester en sécurité.
                        </p>
                    </header>

                    <form @submit.prevent="updatePassword" class="space-y-4">
                        <div>
                            <label for="current_password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Mot de passe actuel</label>
                            <input
                                id="current_password"
                                v-model="passwordForm.current_password"
                                type="password"
                                class="block w-full box-border text-sm leading-4.5 px-4 py-2.5 h-11 text-slate-700 dark:text-white placeholder-slate-300 bg-white dark:bg-gray-950 border border-gray-200 dark:border-gray-800 outline-none focus:border-primary-500 focus:dark:border-primary-600 rounded-md transition-all"
                                autocomplete="current-password"
                            />
                            <div v-if="passwordForm.errors.current_password" class="mt-2 text-sm text-red-600">{{ passwordForm.errors.current_password }}</div>
                        </div>

                        <div>
                            <label for="new_password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nouveau mot de passe</label>
                            <input
                                id="new_password"
                                v-model="passwordForm.password"
                                type="password"
                                class="block w-full box-border text-sm leading-4.5 px-4 py-2.5 h-11 text-slate-700 dark:text-white placeholder-slate-300 bg-white dark:bg-gray-950 border border-gray-200 dark:border-gray-800 outline-none focus:border-primary-500 focus:dark:border-primary-600 rounded-md transition-all"
                                autocomplete="new-password"
                            />
                            <div v-if="passwordForm.errors.password" class="mt-2 text-sm text-red-600">{{ passwordForm.errors.password }}</div>
                        </div>

                        <div>
                            <label for="password_confirmation" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Confirmer le mot de passe</label>
                            <input
                                id="password_confirmation"
                                v-model="passwordForm.password_confirmation"
                                type="password"
                                class="block w-full box-border text-sm leading-4.5 px-4 py-2.5 h-11 text-slate-700 dark:text-white placeholder-slate-300 bg-white dark:bg-gray-950 border border-gray-200 dark:border-gray-800 outline-none focus:border-primary-500 focus:dark:border-primary-600 rounded-md transition-all"
                                autocomplete="new-password"
                            />
                            <div v-if="passwordForm.errors.password_confirmation" class="mt-2 text-sm text-red-600">{{ passwordForm.errors.password_confirmation }}</div>
                        </div>

                        <div class="flex items-center gap-4">
                            <button
                                type="submit"
                                :disabled="passwordForm.processing"
                                class="inline-flex items-center px-4 py-2 border border-primary-600 rounded-md font-semibold text-sm text-white bg-primary-600 hover:bg-primary-700 active:bg-primary-800 transition-all duration-300 disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                Enregistrer
                            </button>
                            <Transition enter-from-class="opacity-0" leave-to-class="opacity-0" class="transition ease-in-out">
                                <p v-if="passwordForm.recentlySuccessful" class="text-sm text-gray-600 dark:text-gray-400">Enregistré.</p>
                            </Transition>
                        </div>
                    </form>
                </div>

                <!-- Delete Account -->
                <div class="p-6 bg-white dark:bg-slate-800 shadow-sm rounded-lg border border-gray-200 dark:border-gray-700">
                    <header class="mb-6">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                            Supprimer le compte
                        </h2>
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                            Une fois votre compte supprimé, toutes ses ressources et données seront définitivement supprimées.
                            Avant de supprimer votre compte, veuillez télécharger toutes les données que vous souhaitez conserver.
                        </p>
                    </header>

                    <button
                        @click="showDeleteModal = true"
                        class="inline-flex items-center px-4 py-2 border border-red-600 rounded-md font-semibold text-sm text-white bg-red-600 hover:bg-red-700 active:bg-red-800 transition-all duration-300"
                    >
                        Supprimer le compte
                    </button>

                    <!-- Delete Modal -->
                    <Teleport to="body">
                        <div v-if="showDeleteModal" class="fixed inset-0 z-50 flex items-center justify-center">
                            <div class="fixed inset-0 bg-slate-950/50" @click="showDeleteModal = false"></div>
                            <div class="relative bg-white dark:bg-slate-800 rounded-lg p-6 w-full max-w-md mx-4 shadow-xl">
                                <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">
                                    Êtes-vous sûr de vouloir supprimer votre compte ?
                                </h2>
                                <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                                    Une fois votre compte supprimé, toutes ses ressources et données seront définitivement supprimées.
                                    Veuillez saisir votre mot de passe pour confirmer la suppression définitive.
                                </p>

                                <form @submit.prevent="deleteAccount">
                                    <div class="mb-4">
                                        <label for="delete_password" class="sr-only">Mot de passe</label>
                                        <input
                                            id="delete_password"
                                            ref="deletePasswordInput"
                                            v-model="deleteForm.password"
                                            type="password"
                                            class="block w-full box-border text-sm leading-4.5 px-4 py-2.5 h-11 text-slate-700 dark:text-white placeholder-slate-300 bg-white dark:bg-gray-950 border border-gray-200 dark:border-gray-800 outline-none focus:border-primary-500 focus:dark:border-primary-600 rounded-md transition-all"
                                            placeholder="Mot de passe"
                                        />
                                        <div v-if="deleteForm.errors.password" class="mt-2 text-sm text-red-600">{{ deleteForm.errors.password }}</div>
                                    </div>

                                    <div class="flex justify-end gap-3">
                                        <button
                                            type="button"
                                            @click="showDeleteModal = false"
                                            class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md font-semibold text-sm text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 transition-all duration-300"
                                        >
                                            Annuler
                                        </button>
                                        <button
                                            type="submit"
                                            :disabled="deleteForm.processing"
                                            class="inline-flex items-center px-4 py-2 border border-red-600 rounded-md font-semibold text-sm text-white bg-red-600 hover:bg-red-700 active:bg-red-800 transition-all duration-300 disabled:opacity-50 disabled:cursor-not-allowed"
                                        >
                                            Supprimer le compte
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </Teleport>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Head, useForm, usePage, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    mustVerifyEmail: Boolean,
    status: String,
});

const user = usePage().props.auth.user;
const showDeleteModal = ref(false);
const verificationSent = ref(false);

// Profile form
const profileForm = useForm({
    name: user.name,
    email: user.email,
});

const updateProfile = () => {
    profileForm.patch(route('profile.update'), {
        preserveScroll: true,
    });
};

const sendVerification = () => {
    router.post(route('verification.send'), {}, {
        onSuccess: () => {
            verificationSent.value = true;
        },
    });
};

// Password form
const passwordForm = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const updatePassword = () => {
    passwordForm.put(route('password.update'), {
        preserveScroll: true,
        onSuccess: () => passwordForm.reset(),
    });
};

// Delete form
const deleteForm = useForm({
    password: '',
});

const deleteAccount = () => {
    deleteForm.delete(route('profile.destroy'), {
        preserveScroll: true,
        onSuccess: () => {
            showDeleteModal.value = false;
        },
        onError: () => {
            // Keep modal open on error
        },
        onFinish: () => deleteForm.reset(),
    });
};
</script>
