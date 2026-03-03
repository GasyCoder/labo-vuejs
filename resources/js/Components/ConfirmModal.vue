<script setup>
import { ref, onMounted, onUnmounted, watch, nextTick } from 'vue';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    title: {
        type: String,
        required: true,
    },
    description: {
        type: String,
        required: true,
    },
    confirmText: {
        type: String,
        default: 'Confirmer',
    },
    cancelText: {
        type: String,
        default: 'Annuler',
    },
    variant: {
        type: String,
        default: 'danger', // danger, warning, success, info
    },
    processing: {
        type: Boolean,
        default: false,
    },
    maxWidth: {
        type: String,
        default: 'sm',
    },
});

const emit = defineEmits(['close', 'confirm']);

const confirmButton = ref(null);
const modalContainer = ref(null);

const close = () => {
    if (!props.processing) {
        emit('close');
    }
};

const confirm = () => {
    emit('confirm');
};

const handleEscape = (e) => {
    if (e.key === 'Escape' && props.show) {
        close();
    }
};

const handleTab = (e) => {
    if (!props.show || !modalContainer.value) return;
    
    const focusableElements = modalContainer.value.querySelectorAll(
        'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
    );
    const firstElement = focusableElements[0];
    const lastElement = focusableElements[focusableElements.length - 1];

    if (e.shiftKey) {
        if (document.activeElement === firstElement) {
            lastElement.focus();
            e.preventDefault();
        }
    } else {
        if (document.activeElement === lastElement) {
            firstElement.focus();
            e.preventDefault();
        }
    }
};

watch(() => props.show, (value) => {
    if (value) {
        document.body.style.overflow = 'hidden';
        nextTick(() => {
            confirmButton.value?.focus();
        });
    } else {
        document.body.style.overflow = null;
    }
});

onMounted(() => {
    document.addEventListener('keydown', handleEscape);
});

onUnmounted(() => {
    document.removeEventListener('keydown', handleEscape);
    document.body.style.overflow = null;
});

const maxWidthClass = {
    'sm': 'max-w-sm',
    'md': 'max-w-md',
    'lg': 'max-w-lg',
    'xl': 'max-w-xl',
}[props.maxWidth];

const variantClasses = {
    danger: {
        iconBg: 'bg-red-100 dark:bg-red-900/30',
        iconText: 'text-red-600 dark:text-red-400',
        button: 'bg-red-600 hover:bg-red-700 focus:ring-red-500',
    },
    warning: {
        iconBg: 'bg-amber-100 dark:bg-amber-900/30',
        iconText: 'text-amber-600 dark:text-amber-400',
        button: 'bg-amber-600 hover:bg-amber-700 focus:ring-amber-500',
    },
    success: {
        iconBg: 'bg-emerald-100 dark:bg-emerald-900/30',
        iconText: 'text-emerald-600 dark:text-emerald-400',
        button: 'bg-emerald-600 hover:bg-emerald-700 focus:ring-emerald-500',
    },
    info: {
        iconBg: 'bg-blue-100 dark:bg-blue-900/30',
        iconText: 'text-blue-600 dark:text-blue-400',
        button: 'bg-blue-600 hover:bg-blue-700 focus:ring-blue-500',
    },
    slate: {
        iconBg: 'bg-slate-100 dark:bg-slate-700',
        iconText: 'text-slate-600 dark:text-slate-400',
        button: 'bg-slate-600 hover:bg-slate-700 focus:ring-slate-500',
    }
}[props.variant] || variantClasses.danger;

</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="ease-out duration-300"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="ease-in duration-200"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="show" class="fixed inset-0 z-[100] bg-slate-900/50 backdrop-blur-sm transition-opacity" aria-hidden="true" @click="close"></div>
        </Transition>

        <Transition
            enter-active-class="ease-out duration-300"
            enter-from-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            enter-to-class="opacity-100 translate-y-0 sm:scale-100"
            leave-active-class="ease-in duration-200"
            leave-from-class="opacity-100 translate-y-0 sm:scale-100"
            leave-to-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
        >
            <div v-if="show" class="fixed inset-0 z-[101] overflow-y-auto">
                <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
                    <div 
                        ref="modalContainer"
                        class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-xl transition-all sm:my-8 w-full dark:bg-slate-800"
                        :class="maxWidthClass"
                        role="dialog"
                        aria-modal="true"
                        aria-labelledby="modal-title"
                        @click.stop
                        @keydown.tab="handleTab"
                    >
                        <div class="px-6 pt-6 pb-4">
                            <div class="sm:flex sm:items-start">
                                <div :class="['mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full sm:mx-0 sm:h-10 sm:w-10', variantClasses.iconBg]">
                                    <svg v-if="variant === 'danger' || variant === 'warning'" class="h-6 w-6" :class="variantClasses.iconText" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                                    </svg>
                                    <svg v-else-if="variant === 'success'" class="h-6 w-6" :class="variantClasses.iconText" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <svg v-else class="h-6 w-6" :class="variantClasses.iconText" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
                                    </svg>
                                </div>
                                <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                                    <h3 class="text-lg font-bold leading-6 text-slate-900 dark:text-white" id="modal-title">
                                        {{ title }}
                                    </h3>
                                    <div class="mt-2">
                                        <p class="text-sm text-slate-500 dark:text-slate-400">
                                            {{ description }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="bg-slate-50 px-6 py-4 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end dark:bg-slate-700/50">
                            <button 
                                type="button" 
                                class="inline-flex w-full justify-center rounded-xl bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50 sm:w-auto dark:bg-slate-800 dark:text-slate-300 dark:ring-slate-600 dark:hover:bg-slate-700 transition-all" 
                                :disabled="processing"
                                @click="close"
                            >
                                {{ cancelText }}
                            </button>
                            <button 
                                ref="confirmButton"
                                type="button" 
                                class="inline-flex w-full justify-center rounded-xl px-4 py-2.5 text-sm font-semibold text-white shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 sm:w-auto transition-all disabled:opacity-50 disabled:cursor-not-allowed items-center gap-2"
                                :class="variantClasses.button"
                                :disabled="processing"
                                @click="confirm"
                            >
                                <svg v-if="processing" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                                {{ processing ? 'Traitement...' : confirmText }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
