<template>
    <Head title="Mot de passe oublié" />
    <GuestLayout>
        <div class="pb-5">
            <h5 class="mb-2 text-xl font-bold font-heading -tracking-snug text-slate-700 dark:text-white leading-tighter">
                Mot de passe oublié
            </h5>
            <p class="text-sm leading-6 text-slate-400">
                Entrez votre adresse email et nous vous enverrons un lien de réinitialisation de mot de passe.
            </p>
        </div>

        <!-- Status -->
        <div v-if="status" class="mb-4 font-medium text-sm text-green-600 dark:text-green-400">
            {{ status }}
        </div>

        <form @submit.prevent="submit">
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
                    autofocus
                />
                <div v-if="form.errors.email" class="mt-2 text-sm text-red-600">{{ form.errors.email }}</div>
            </div>

            <div class="flex items-center justify-end">
                <button
                    type="submit"
                    :disabled="form.processing"
                    class="relative flex items-center justify-center text-base font-bold leading-4.5 rounded-md px-6 py-3 tracking-wide border border-primary-600 text-white bg-primary-600 hover:bg-primary-700 active:bg-primary-800 transition-all duration-300 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    Envoyer le lien
                </button>
            </div>
        </form>
    </GuestLayout>
</template>

<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/GuestLayout.vue';

const props = defineProps({
    status: String,
});

const form = useForm({
    email: '',
});

const submit = () => {
    form.post(route('password.email'));
};
</script>
