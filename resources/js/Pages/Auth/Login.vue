<template>
    <Head title="Connexion" />
    <GuestLayout>
        <div class="pb-5">
            <h5 class="mb-2 text-xl font-bold font-heading -tracking-snug text-slate-700 dark:text-white leading-tighter">
                Connexion
            </h5>
            <p class="text-sm leading-6 text-slate-400">
                Accédez au panneau La Reference en utilisant votre identifiant et votre mot de passe.
            </p>
        </div>

        <!-- Session Status -->
        <div v-if="status" class="mb-4 font-medium text-sm text-green-600 dark:text-green-400">
            {{ status }}
        </div>

        <form @submit.prevent="submit">
            <!-- Username -->
            <div class="relative mb-5 last:mb-0">
                <label for="username"
                    class="flex items-center justify-between mb-2 text-sm font-medium text-slate-700 dark:text-white">
                    Identifiant
                </label>
                <div class="relative">
                    <input
                        id="username"
                        v-model="form.username"
                        type="text"
                        class="block w-full box-border text-sm leading-4.5 px-4 py-2.5 h-11 text-slate-700 dark:text-white placeholder-slate-300 bg-white dark:bg-gray-950 border border-gray-200 dark:border-gray-800 outline-none focus:border-primary-500 focus:dark:border-primary-600 focus:outline-offset-0 focus:outline-primary-200 focus:dark:outline-primary-950 disabled:bg-slate-50 disabled:dark:bg-slate-950 disabled:cursor-not-allowed rounded-md transition-all"
                        placeholder="Entrez votre identifiant"
                        required
                        autofocus
                        autocomplete="username"
                    />
                </div>
                <div v-if="form.errors.username" class="mt-2 text-sm text-red-600">{{ form.errors.username }}</div>
            </div>

            <!-- Password -->
            <div class="relative mb-5 last:mb-0">
                <div class="relative">
                    <button
                        type="button"
                        tabindex="-1"
                        class="absolute top-0 flex items-center justify-center h-11 w-11 end-0 group/password"
                        @click="showPassword = !showPassword"
                    >
                        <em v-if="!showPassword" class="text-slate-400 text-base leading-none ni ni-eye"></em>
                        <em v-else class="text-slate-400 text-base leading-none ni ni-eye-off"></em>
                    </button>
                    <input
                        id="password"
                        v-model="form.password"
                        :type="showPassword ? 'text' : 'password'"
                        class="block w-full box-border text-sm leading-4.5 px-4 py-2.5 h-11 text-slate-700 dark:text-white placeholder-slate-300 bg-white dark:bg-gray-950 border border-gray-200 dark:border-gray-800 outline-none focus:border-primary-500 focus:dark:border-primary-600 focus:outline-offset-0 focus:outline-primary-200 focus:dark:outline-primary-950 disabled:bg-slate-50 disabled:dark:bg-slate-950 disabled:cursor-not-allowed rounded-md transition-all"
                        placeholder="Entrez votre mot de passe"
                        required
                        autocomplete="current-password"
                    />
                </div>
                <div v-if="form.errors.password" class="mt-2 text-sm text-red-600">{{ form.errors.password }}</div>
            </div>

            <!-- Remember Me -->
            <div class="relative mb-5 last:mb-0">
                <label for="remember_me" class="inline-flex items-center">
                    <input
                        id="remember_me"
                        v-model="form.remember"
                        type="checkbox"
                        class="border-gray-300 rounded shadow-sm text-primary-600 dark:bg-gray-900 dark:border-gray-700 focus:ring-primary-500 dark:focus:ring-primary-600 dark:focus:ring-offset-gray-800"
                    />
                    <span class="text-sm text-slate-600 ms-2 dark:text-slate-400">Se souvenir de moi</span>
                </label>
            </div>

            <!-- Submit -->
            <div class="relative mb-5 last:mb-0">
                <button
                    type="submit"
                    :disabled="form.processing"
                    class="relative w-full flex items-center justify-center text-center align-middle text-base font-bold leading-4.5 rounded-md px-6 py-3 tracking-wide border border-primary-600 text-white bg-primary-600 hover:bg-primary-700 active:bg-primary-800 transition-all duration-300 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    Se connecter
                </button>
            </div>
        </form>
    </GuestLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/GuestLayout.vue';

const props = defineProps({
    status: String,
});

const showPassword = ref(false);

const form = useForm({
    username: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>
