<script setup>
import { useForm, Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';

const props = defineProps({
    analyse: Object,
    contexts: Array,
});

const form = useForm({
    ranges: props.contexts.map(context => {
        const existing = props.analyse.ranges.find(r => r.context === context);
        return {
            context,
            normal_min: existing?.normal_min || '',
            normal_max: existing?.normal_max || '',
            critical_min: existing?.critical_min || '',
            critical_max: existing?.critical_max || '',
        };
    }),
});

const contextIcons = {
    'HOMME': 'ni-user-fill',
    'FEMME': 'ni-user-fill',
    'ENFANT_GARCON': 'ni-user-alt-fill',
    'ENFANT_FILLE': 'ni-user-alt-fill'
};

const contextLabels = {
    'HOMME': 'Homme',
    'FEMME': 'Femme',
    'ENFANT_GARCON': 'Enfant (Garçon)',
    'ENFANT_FILLE': 'Enfant (Fille)'
};

const submit = () => {
    form.post(route('laboratoire.analyses.ranges.store', props.analyse.id));
};
</script>

<template>
    <Head :title="'Bornes - ' + analyse.designation" />

    <AppLayout>
        <div class="px-4 py-6 space-y-6">
            <!-- Header -->
            <div class="mb-2">
                <Link :href="route('laboratoire.analyses.ranges.index')" 
                    class="inline-flex items-center text-xs font-bold text-slate-500 transition-colors hover:text-primary-600 dark:text-slate-400 dark:hover:text-primary-400">
                    <em class="ni ni-arrow-left mr-1"></em> Retour aux bornes
                </Link>
                <div class="mt-2 flex items-center justify-between border-b border-slate-100 pb-4 dark:border-slate-800">
                    <div>
                        <h1 class="text-xl font-black tracking-tight text-slate-900 dark:text-white uppercase">
                            Configuration : {{ analyse.designation }}
                        </h1>
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mt-1">
                            CODE : {{ analyse.code }} | UNITÉ : {{ analyse.unite || 'N/A' }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Guide UX Rapide -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="rounded-xl bg-blue-50/50 p-4 border border-blue-100 dark:bg-blue-900/10 dark:border-blue-900/30">
                    <div class="flex gap-3">
                        <em class="ni ni-info-fill text-blue-500 text-lg"></em>
                        <div>
                            <h4 class="text-xs font-black text-blue-700 dark:text-blue-400 uppercase tracking-wider">Intervalles Normaux</h4>
                            <p class="text-[10px] text-blue-600/80 dark:text-blue-500 font-medium leading-relaxed mt-1">
                                Valeurs de référence pour un patient sain. Un résultat en dehors de cette plage affichera un <span class="font-bold underline">avertissement orange</span> lors de la saisie.
                            </p>
                        </div>
                    </div>
                </div>
                <div class="rounded-xl bg-red-50/50 p-4 border border-red-100 dark:bg-red-900/10 dark:border-red-900/30">
                    <div class="flex gap-3">
                        <em class="ni ni-alert-fill text-red-500 text-lg"></em>
                        <div>
                            <h4 class="text-xs font-black text-red-700 dark:text-red-400 uppercase tracking-wider">Limites Critiques</h4>
                            <p class="text-[10px] text-red-600/80 dark:text-red-500 font-medium leading-relaxed mt-1">
                                Seuils de danger vital. Un résultat dépassant ces limites provoquera un <span class="font-bold underline">blocage rouge</span> nécessitant une validation biologique immédiate.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div v-for="(range, index) in form.ranges" :key="range.context" 
                        class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-800 transition-all hover:border-primary-200">
                        
                        <div class="mb-5 flex items-center gap-3 border-b border-slate-50 pb-3 dark:border-slate-700">
                            <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-primary-500 text-white shadow-sm">
                                <em :class="['ni', contextIcons[range.context], 'text-sm']"></em>
                            </div>
                            <h2 class="text-xs font-black text-slate-900 dark:text-white uppercase tracking-tight">
                                Pour {{ contextLabels[range.context] }}
                            </h2>
                        </div>

                        <div class="grid grid-cols-2 gap-x-6 gap-y-4">
                            <!-- Bloc Normal -->
                            <div class="space-y-1.5">
                                <label class="flex items-center gap-1 text-[10px] font-bold uppercase tracking-wider text-slate-500">
                                    Min. Normal
                                </label>
                                <input v-model="range.normal_min" type="number" step="0.001" 
                                    class="w-full rounded-lg border-slate-200 bg-slate-50 py-2.5 text-xs font-bold focus:border-blue-500 focus:ring-blue-500 dark:border-slate-700 dark:bg-slate-900" 
                                    placeholder="0.00">
                            </div>
                            <div class="space-y-1.5">
                                <label class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Max. Normal</label>
                                <input v-model="range.normal_max" type="number" step="0.001" 
                                    class="w-full rounded-lg border-slate-200 bg-slate-50 py-2.5 text-xs font-bold focus:border-blue-500 focus:ring-blue-500 dark:border-slate-700 dark:bg-slate-900" 
                                    placeholder="0.00">
                            </div>

                            <!-- Bloc Critique -->
                            <div class="space-y-1.5">
                                <label class="flex items-center gap-1 text-[10px] font-bold uppercase tracking-wider text-red-500">
                                    Min. Critique
                                </label>
                                <input v-model="range.critical_min" type="number" step="0.001" 
                                    class="w-full rounded-lg border-red-100 bg-red-50/20 py-2.5 text-xs font-bold focus:border-red-500 focus:ring-red-500 dark:border-red-900/10 dark:bg-slate-900 dark:text-red-400" 
                                    placeholder="Danger Bas">
                            </div>
                            <div class="space-y-1.5">
                                <label class="text-[10px] font-bold uppercase tracking-wider text-red-500">Max. Critique</label>
                                <input v-model="range.critical_max" type="number" step="0.001" 
                                    class="w-full rounded-lg border-red-100 bg-red-50/20 py-2.5 text-xs font-bold focus:border-red-500 focus:ring-red-500 dark:border-red-900/10 dark:bg-slate-900 dark:text-red-400" 
                                    placeholder="Danger Haut">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 pt-4">
                    <button type="submit" :disabled="form.processing"
                        class="inline-flex items-center gap-2 rounded-lg bg-primary-600 px-8 py-3 text-xs font-bold text-white shadow-lg transition-all hover:bg-primary-700 active:scale-95 disabled:opacity-50 uppercase tracking-widest">
                        <em v-if="!form.processing" class="ni ni-save"></em>
                        {{ form.processing ? 'Enregistrement...' : 'Enregistrer les normes' }}
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>

<style scoped>
input[type=number]::-webkit-inner-spin-button, 
input[type=number]::-webkit-outer-spin-button { 
  -webkit-appearance: none; 
  margin: 0; 
}
</style>
