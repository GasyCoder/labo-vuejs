<script setup>
import { ref, watch } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Swal from 'sweetalert2';

const props = defineProps({
    branding: Object,
});

const logoPreview = ref(props.branding.logo_path ? `/storage/${props.branding.logo_path}` : null);
const signaturePreview = ref(props.branding.signature_image_path ? `/storage/${props.branding.signature_image_path}` : null);

const form = useForm({
    logo: null,
    exam_color: props.branding.exam_color || '#d10000',
    highlight_color: props.branding.highlight_color || '#d10000',
    signature_image: null,
});

const onLogoChange = (e) => {
    const file = e.target.files[0];
    if (file) {
        form.logo = file;
        logoPreview.value = URL.createObjectURL(file);
    }
};

const onSignatureChange = (e) => {
    const file = e.target.files[0];
    if (file) {
        form.signature_image = file;
        signaturePreview.value = URL.createObjectURL(file);
    }
};

const submit = () => {
    form.post(route('admin.pdf-branding.store'), {
        onSuccess: () => {
            Swal.fire({
                icon: 'success',
                title: 'Configuration enregistrée',
                text: 'L\'identité visuelle de vos rapports PDF a été mise à jour.',
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
            });
        },
    });
};
</script>

<template>
    <Head title="Branding PDF" />

    <AppLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-2xl text-gray-800 dark:text-gray-200 leading-tight">
                    Personnalisation des Rapports PDF
                </h2>
                <div class="flex items-center gap-2">
                    <span class="px-3 py-1 bg-primary-100 text-primary-700 dark:bg-primary-900/30 dark:text-primary-400 text-xs font-bold rounded-full border border-primary-200 dark:border-primary-800 uppercase tracking-widest">
                        Premium
                    </span>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-[1600px] mx-auto sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 xl:grid-cols-12 gap-8">
                    
                    <!-- FORMULARIO (Left Column) -->
                    <div class="xl:col-span-5 space-y-6">
                        <div class="bg-white dark:bg-gray-800 shadow-xl sm:rounded-2xl border border-gray-100 dark:border-gray-700 overflow-hidden">
                            <div class="p-6 sm:p-8">
                                <form @submit.prevent="submit" class="space-y-8">
                                    
                                    <!-- Logo Section -->
                                    <section>
                                        <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-4">
                                            En-tête & Logo
                                        </label>
                                        <div class="group relative flex items-center justify-center border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-xl p-6 transition-all hover:border-primary-400 dark:hover:border-primary-500 bg-gray-50 dark:bg-gray-900/50">
                                            <div class="text-center space-y-2">
                                                <div v-if="logoPreview" class="relative inline-block">
                                                    <img :src="logoPreview" alt="Logo preview" class="max-h-24 mx-auto rounded shadow-sm" />
                                                    <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center rounded">
                                                        <em class="ni ni-camera text-white text-2xl"></em>
                                                    </div>
                                                </div>
                                                <div v-else class="py-4">
                                                    <em class="ni ni-img text-4xl text-gray-400"></em>
                                                    <p class="text-xs text-gray-500 mt-2">Cliquez pour ajouter un logo</p>
                                                </div>
                                                <input type="file" @change="onLogoChange" accept="image/*" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer" />
                                            </div>
                                        </div>
                                        <p class="mt-2 text-[11px] text-gray-500 italic">Format recommandé : PNG transparent, Max 2 Mo.</p>
                                        <div v-if="form.errors.logo" class="text-red-500 text-xs mt-1 font-medium">{{ form.errors.logo }}</div>
                                    </section>

                                    <!-- Colors Section -->
                                    <section class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                                        <div>
                                            <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-2">Couleur des Titres</label>
                                            <div class="flex items-center gap-3 p-2 bg-gray-50 dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-700">
                                                <input type="color" v-model="form.exam_color" class="h-10 w-12 rounded border-0 cursor-pointer bg-transparent" />
                                                <input type="text" v-model="form.exam_color" class="flex-1 uppercase bg-transparent border-0 text-sm font-mono dark:text-white focus:ring-0" />
                                            </div>
                                            <div v-if="form.errors.exam_color" class="text-red-500 text-xs mt-1">{{ form.errors.exam_color }}</div>
                                        </div>
                                        <div>
                                            <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-2">Couleur des Traits</label>
                                            <div class="flex items-center gap-3 p-2 bg-gray-50 dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-700">
                                                <input type="color" v-model="form.highlight_color" class="h-10 w-12 rounded border-0 cursor-pointer bg-transparent" />
                                                <input type="text" v-model="form.highlight_color" class="flex-1 uppercase bg-transparent border-0 text-sm font-mono dark:text-white focus:ring-0" />
                                            </div>
                                            <div v-if="form.errors.highlight_color" class="text-red-500 text-xs mt-1">{{ form.errors.highlight_color }}</div>
                                        </div>
                                    </section>

                                    <!-- Signature Section -->
                                    <section>
                                        <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-4">
                                            Signature Numérique (Obligatoire)
                                        </label>
                                        <div class="group relative flex items-center justify-center border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-xl p-6 transition-all hover:border-primary-400 bg-gray-50 dark:bg-gray-900/50">
                                            <div class="text-center">
                                                <div v-if="signaturePreview" class="relative inline-block">
                                                    <img :src="signaturePreview" alt="Signature preview" class="max-h-20 mx-auto rounded" />
                                                    <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center rounded">
                                                        <em class="ni ni-pen text-white text-2xl"></em>
                                                    </div>
                                                </div>
                                                <div v-else class="py-4">
                                                    <em class="ni ni-edit-alt text-4xl text-gray-400"></em>
                                                    <p class="text-xs text-gray-500 mt-2 font-medium text-red-500">Signature requise pour valider les rapports</p>
                                                </div>
                                                <input type="file" @change="onSignatureChange" accept="image/*" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer" />
                                            </div>
                                        </div>
                                        <div v-if="form.errors.signature_image" class="text-red-500 text-xs mt-1 font-medium">{{ form.errors.signature_image }}</div>
                                    </section>

                                    <div class="pt-4">
                                        <button type="submit" :disabled="form.processing" class="w-full inline-flex items-center justify-center px-6 py-4 border border-transparent text-base font-bold rounded-xl shadow-lg text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition-all transform active:scale-95 disabled:opacity-50 disabled:active:scale-100">
                                            <em v-if="form.processing" class="ni ni-loader animate-spin mr-2"></em>
                                            <em v-else class="ni ni-check-circle-cut mr-2"></em>
                                            {{ form.processing ? 'Mise à jour...' : 'Appliquer les changements' }}
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- PREVIEW (Right Column) -->
                    <div class="xl:col-span-7">
                        <div class="sticky top-8">
                            <label class="block text-sm font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-4">
                                Aperçu en temps réel
                            </label>
                            
                            <!-- PDF Mockup -->
                            <div class="bg-gray-200 dark:bg-gray-900 p-8 rounded-2xl shadow-inner border border-gray-300 dark:border-gray-700 min-h-[800px] flex justify-center">
                                <div class="bg-white w-full max-w-[600px] shadow-2xl p-10 flex flex-col font-sans pointer-events-none select-none">
                                    
                                    <!-- PDF Header Mockup -->
                                    <div class="mb-4">
                                        <div class="flex justify-center mb-2">
                                            <img v-if="logoPreview" :src="logoPreview" class="max-h-20 object-contain" />
                                            <div v-else class="h-16 w-48 bg-gray-100 border rounded flex items-center justify-center italic text-gray-400 text-xs">
                                                Votre Logo Ici
                                            </div>
                                        </div>
                                        <div class="h-px w-full" :style="{ backgroundColor: form.highlight_color }"></div>
                                    </div>

                                    <!-- PDF Content Mockup -->
                                    <div class="flex-1 mt-6">
                                        <div class="flex justify-between mb-8 border-b pb-4 border-gray-100">
                                            <div class="space-y-1">
                                                <div class="h-3 w-32 bg-gray-100 rounded"></div>
                                                <div class="h-4 w-48 bg-gray-200 rounded"></div>
                                                <div class="h-3 w-40 bg-gray-100 rounded"></div>
                                            </div>
                                            <div class="text-right space-y-1">
                                                <div class="h-3 w-24 bg-gray-100 rounded ml-auto"></div>
                                                <div class="h-3 w-32 bg-gray-100 rounded ml-auto"></div>
                                            </div>
                                        </div>

                                        <!-- Exam Header Mockup -->
                                        <div class="flex items-center gap-4 mb-4">
                                            <div class="text-lg font-bold uppercase tracking-wide" :style="{ color: form.exam_color }">
                                                HÉMATOLOGIE
                                            </div>
                                            <div class="flex-1 h-[0.5px] bg-gray-200"></div>
                                        </div>

                                        <!-- Table Mockup -->
                                        <div class="space-y-3">
                                            <div class="flex border-b border-gray-100 pb-1 italic text-[10px] text-gray-400">
                                                <div class="w-1/2">Désignation</div>
                                                <div class="w-1/4">Résultat</div>
                                                <div class="w-1/4">Valeur Réf.</div>
                                            </div>
                                            <div v-for="i in 4" :key="i" class="flex items-center py-1">
                                                <div class="w-1/2 h-3 w-32 bg-gray-50 rounded"></div>
                                                <div class="w-1/4 h-3 w-16 bg-gray-100 rounded"></div>
                                                <div class="w-1/4 h-3 w-20 bg-gray-50 rounded"></div>
                                            </div>
                                        </div>

                                        <!-- Another Exam Mockup -->
                                        <div class="mt-10 flex items-center gap-4 mb-4">
                                            <div class="text-lg font-bold uppercase tracking-wide" :style="{ color: form.exam_color }">
                                                BIOCHIMIE
                                            </div>
                                            <div class="flex-1 h-[0.5px] bg-gray-200"></div>
                                        </div>
                                        <div class="space-y-3">
                                            <div v-for="i in 3" :key="i" class="flex items-center py-1">
                                                <div class="w-1/2 h-3 w-40 bg-gray-50 rounded"></div>
                                                <div class="w-1/4 h-3 w-12 bg-gray-100 rounded"></div>
                                                <div class="w-1/4 h-3 w-24 bg-gray-50 rounded"></div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- PDF Footer Mockup -->
                                    <div class="mt-auto pt-8 border-t border-gray-100 flex justify-between items-end">
                                        <div class="text-[8px] text-gray-400">
                                            Généré le {{ new Date().toLocaleDateString() }}
                                        </div>
                                        <div class="text-center">
                                            <div class="text-[9px] font-bold text-gray-600 mb-2 uppercase tracking-tighter">Le Biologiste</div>
                                            <img v-if="signaturePreview" :src="signaturePreview" class="max-h-16 mx-auto opacity-90" />
                                            <div v-else class="h-12 w-32 bg-gray-50 rounded border border-dashed flex items-center justify-center text-[8px] text-gray-300 italic">
                                                Signature manquante
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
input[type="color"]::-webkit-color-swatch-wrapper {
	padding: 0;
}
input[type="color"]::-webkit-color-swatch {
	border: none;
    border-radius: 6px;
}
</style>
