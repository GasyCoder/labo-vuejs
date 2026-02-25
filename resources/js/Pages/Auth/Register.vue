<template>
    <Head title="Inscription" />
    <GuestLayout>
        <div class="pb-5">
            <h5 class="mb-2 text-xl font-bold font-heading -tracking-snug text-slate-700 dark:text-white leading-tighter">
                Inscription
            </h5>
            <p class="text-sm leading-6 text-slate-400">
                Créez votre compte pour accéder au panneau La Reference.
            </p>
        </div>

        <form @submit.prevent="submit">
            <!-- Name -->
            <div class="relative mb-5 last:mb-0">
                <label for="name" class="flex items-center justify-between mb-2 text-sm font-medium text-slate-700 dark:text-white">
                    Nom
                </label>
                <input
                    id="name"
                    v-model="form.name"
                    type="text"
                    class="block w-full box-border text-sm leading-4.5 px-4 py-2.5 h-11 text-slate-700 dark:text-white placeholder-slate-300 bg-white dark:bg-gray-950 border border-gray-200 dark:border-gray-800 outline-none focus:border-primary-500 focus:dark:border-primary-600 rounded-md transition-all"
                    required
                    autofocus
                    autocomplete="name"
                />
                <div v-if="form.errors.name" class="mt-2 text-sm text-red-600">{{ form.errors.name }}</div>
            </div>

            <!-- Email -->
            <div class="relative mb-5 last:mb-0">
                <label for="email" class="flex items-center justify-between mb-2 text-sm font-medium text-slate-700 dark:text-white">
                    Email
                </label>
                <input
                    id="email"
                    v-model="form.email"
                    type="email"
                    class="block w-full box-border text-sm leading-4.5 px-4 py-2.5 h-11 text-slate-700 dark:text-white placeholder-slate-300 bg-white dark:bg-gray-950 border border-gray-200 dark:border-gray-800 outline-none focus:border-primary-500 focus:dark:border-primary-600 rounded-md transition-all"
                    required
                    autocomplete="username"
                />
                <div v-if="form.errors.email" class="mt-2 text-sm text-red-600">{{ form.errors.email }}</div>
            </div>

            <!-- Password -->
            <div class="relative mb-5 last:mb-0">
                <label for="password" class="flex items-center justify-between mb-2 text-sm font-medium text-slate-700 dark:text-white">
                    Mot de passe
                </label>
                <input
                    id="password"
                    v-model="form.password"
                    type="password"
                    class="block w-full box-border text-sm leading-4.5 px-4 py-2.5 h-11 text-slate-700 dark:text-white placeholder-slate-300 bg-white dark:bg-gray-950 border border-gray-200 dark:border-gray-800 outline-none focus:border-primary-500 focus:dark:border-primary-600 rounded-md transition-all"
                    required
                    autocomplete="new-password"
                />
                <div v-if="form.errors.password" class="mt-2 text-sm text-red-600">{{ form.errors.password }}</div>
            </div>

            <!-- Confirm Password -->
            <div class="relative mb-5 last:mb-0">
                <label for="password_confirmation" class="flex items-center justify-between mb-2 text-sm font-medium text-slate-700 dark:text-white">
                    Confirmer le mot de passe
                </label>
                <input
                    id="password_confirmation"
                    v-model="form.password_confirmation"
                    type="password"
                    class="block w-full box-border text-sm leading-4.5 px-4 py-2.5 h-11 text-slate-700 dark:text-white placeholder-slate-300 bg-white dark:bg-gray-950 border border-gray-200 dark:border-gray-800 outline-none focus:border-primary-500 focus:dark:border-primary-600 rounded-md transition-all"
                    required
                    autocomplete="new-password"
                />
                <div v-if="form.errors.password_confirmation" class="mt-2 text-sm text-red-600">{{ form.errors.password_confirmation }}</div>
            </div>

            <div class="flex items-center justify-between mt-4">
                <a :href="route('login')" class="underline text-sm text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-100 rounded-md transition-colors">
                    Déjà inscrit ?
                </a>
                <button
                    type="submit"
                    :disabled="form.processing"
                    class="relative flex items-center justify-center text-base font-bold leading-4.5 rounded-md px-6 py-3 tracking-wide border border-primary-600 text-white bg-primary-600 hover:bg-primary-700 active:bg-primary-800 transition-all duration-300 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    S'inscrire
                </button>
            </div>
        </form>
    </GuestLayout>
</template>

<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/GuestLayout.vue';

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>
