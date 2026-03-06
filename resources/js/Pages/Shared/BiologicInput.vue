<script setup>
import { ref, computed, watch } from 'vue';

const props = defineProps({
    modelValue: [String, Number],
    label: String,
    unite: String,
    suffixe: String,
    ranges: Object,
    validationStatus: String,
    validationMessage: String,
    canEdit: { type: Boolean, default: true },
    saveStatus: String,
});

const emit = defineEmits(['update:modelValue', 'confirm']);

const internalValue = ref(props.modelValue || '');
const showCriticalModal = ref(false);

watch(() => props.modelValue, (newVal) => {
    if (newVal !== internalValue.value) {
        internalValue.value = newVal || '';
    }
});

const numericValue = computed(() => {
    if (!internalValue.value) return null;
    const clean = String(internalValue.value).replace(',', '.').trim();
    const num = Number(clean);
    return isNaN(num) ? null : num;
});

const localStatus = computed(() => {
    const val = numericValue.value;
    if (val === null || !props.ranges) return 'OK';

    // Extraction sécurisée en nombres réels
    const nMin = props.ranges.normal_min !== null ? Number(props.ranges.normal_min) : null;
    const nMax = props.ranges.normal_max !== null ? Number(props.ranges.normal_max) : null;
    const cMin = props.ranges.critical_min !== null ? Number(props.ranges.critical_min) : null;
    const cMax = props.ranges.critical_max !== null ? Number(props.ranges.critical_max) : null;

    // 1. PRIORITÉ ABSOLUE : NORMAL (Inclusif)
    // Si la valeur est dans la norme, c'est OK quoi qu'il arrive.
    const isAboveMin = (nMin === null || val >= nMin);
    const isBelowMax = (nMax === null || val <= nMax);

    if (isAboveMin && isBelowMax) {
        return 'OK';
    }

    // 2. CRITIQUE / IMPOSSIBLE
    // On ne vérifie le critique que si on est hors norme.
    if ((cMin !== null && val < cMin) || (cMax !== null && val > cMax)) {
        return 'BLOCK';
    }

    // 3. Sinon WARNING (Hors plage mais acceptable)
    return 'WARNING';
});

const effectiveStatus = computed(() => (props.validationStatus && props.validationStatus !== 'OK') ? props.validationStatus : localStatus.value);

const inputClasses = computed(() => {
    const base = "block w-full px-4 py-2.5 rounded-xl border-2 transition-all duration-200 focus:outline-none text-base font-bold ";
    if (effectiveStatus.value === 'BLOCK') return base + "border-red-500 bg-red-50 text-red-900 focus:ring-4 focus:ring-red-100";
    if (effectiveStatus.value === 'WARNING') return base + "border-amber-400 bg-amber-50 text-amber-900 focus:ring-4 focus:ring-amber-100";
    if (internalValue.value && effectiveStatus.value === 'OK') return base + "border-emerald-500 bg-emerald-50 text-emerald-900 focus:ring-4 focus:ring-emerald-100";
    return base + "border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10";
});

const handleInput = (e) => {
    internalValue.value = e.target.value;
    emit('update:modelValue', internalValue.value);
};

watch(() => props.validationStatus, (newStatus) => { if (newStatus === 'BLOCK') showCriticalModal.value = true; });
</script>

<template>
    <div class="relative">
        <div class="flex items-center justify-between mb-1.5 px-1">
            <label class="text-xs font-black uppercase tracking-widest text-slate-500 dark:text-slate-400">{{ label || 'Résultat' }}</label>
            <span v-if="internalValue && effectiveStatus !== 'OK'" 
                :class="['text-[10px] font-black px-2 py-0.5 rounded-full uppercase tracking-tight', effectiveStatus === 'BLOCK' ? 'bg-red-600 text-white animate-pulse' : 'bg-amber-500 text-white']">
                {{ effectiveStatus === 'BLOCK' ? 'Valeur Critique' : 'Hors Norme' }}
            </span>
        </div>

        <div class="relative flex items-center">
            <input type="text" :value="internalValue" @input="handleInput" :disabled="!canEdit" :class="inputClasses" placeholder="Saisir la valeur..." />
            <div v-if="unite || suffixe" class="absolute right-3 flex items-center pointer-events-none">
                <span class="text-xs font-black text-slate-400 bg-slate-100 dark:bg-slate-700 px-2 py-1 rounded-lg border border-slate-200 dark:border-slate-600 shadow-sm">{{ suffixe || unite }}</span>
            </div>
        </div>

        <div class="min-h-[20px] mt-1.5 px-1">
            <div v-if="effectiveStatus === 'WARNING'" class="flex items-center justify-between">
                <p class="text-xs font-bold text-amber-600 flex items-center gap-1">
                    <em class="ni ni-alert-fill"></em> Valeur hors plage normale
                </p>
                <button v-if="saveStatus === 'error' || validationStatus === 'WARNING'" @click="emit('confirm')" class="text-[10px] bg-amber-100 text-amber-700 px-2.5 py-1 rounded-lg font-black uppercase hover:bg-amber-200 transition-colors shadow-sm">Confirmer</button>
            </div>
            <p v-else-if="effectiveStatus === 'BLOCK'" class="text-xs font-black text-red-600 uppercase flex items-center gap-1">
                <em class="ni ni-report-fill"></em> Valeur biologiquement incohérente
            </p>
            <p v-else-if="internalValue" class="text-xs font-bold text-emerald-600 flex items-center gap-1">
                <em class="ni ni-check-round-fill"></em> Valeur normale
            </p>
        </div>

        <!-- Modal Critique Standard -->
        <div v-if="showCriticalModal" class="fixed inset-0 z-[9999] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
            <div class="bg-white dark:bg-slate-800 w-full max-w-sm rounded-2xl shadow-2xl border border-red-100 p-8 text-center">
                <div class="w-16 h-16 bg-red-100 text-red-600 rounded-full flex items-center justify-center mx-auto mb-6 ring-8 ring-red-50"><em class="ni ni-alert-fill text-3xl"></em></div>
                <h3 class="text-xl font-black text-slate-900 dark:text-white mb-3 uppercase">Valeur Critique !</h3>
                <p class="text-sm text-slate-500 dark:text-slate-400 mb-8 leading-relaxed">
                    La valeur <span class="font-black text-red-600">{{ internalValue }}</span> est en dehors des seuils de sécurité vitale configurés pour cet examen.
                </p>
                <button @click="showCriticalModal = false" class="w-full py-4 bg-slate-900 text-white rounded-xl font-black uppercase tracking-widest hover:bg-black transition-all shadow-xl">Corriger la saisie</button>
            </div>
        </div>
    </div>
</template>
