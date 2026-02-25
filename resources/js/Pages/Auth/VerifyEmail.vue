<template>
    <Head title="Vérification de l'email" />
    <GuestLayout>
        <div class="pb-5">
            <h5 class="mb-2 text-xl font-bold font-heading -tracking-snug text-slate-700 dark:text-white leading-tighter">
                Vérification de l'email
            </h5>
            <p class="text-sm leading-6 text-slate-400">
                Merci pour votre inscription ! Avant de commencer, pourriez-vous vérifier votre adresse email
                en cliquant sur le lien que nous venons de vous envoyer ? Si vous n'avez pas reçu l'email,
                nous vous en enverrons un autre avec plaisir.
            </p>
        </div>

        <!-- Status -->
        <div v-if="verificationLinkSent" class="mb-4 font-medium text-sm text-green-600 dark:text-green-400">
            Un nouveau lien de vérification a été envoyé à l'adresse email que vous avez fournie lors de l'inscription.
        </div>

        <div class="flex items-center justify-between">
            <button
                @click="submit"
                :disabled="form.processing"
                class="relative flex items-center justify-center text-base font-bold leading-4.5 rounded-md px-6 py-3 tracking-wide border border-primary-600 text-white bg-primary-600 hover:bg-primary-700 active:bg-primary-800 transition-all duration-300 disabled:opacity-50 disabled:cursor-not-allowed"
            >
                Renvoyer l'email de vérification
            </button>

            <button
                @click="logout"
                class="underline text-sm text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-100 rounded-md transition-colors"
            >
                Se déconnecter
            </button>
        </div>
    </GuestLayout>
</template>

<script setup>
import { computed } from 'vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/GuestLayout.vue';

const props = defineProps({
    status: String,
});

const form = useForm({});
const logoutForm = useForm({});

const verificationLinkSent = computed(() => props.status === 'verification-link-sent');

const submit = () => {
    form.post(route('verification.send'));
};

const logout = () => {
    logoutForm.post(route('logout'));
};
</script>
